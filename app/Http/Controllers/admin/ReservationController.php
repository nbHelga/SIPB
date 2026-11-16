<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use App\Models\Pesanan;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\NotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Http\Controllers\customer\BookingController;

class ReservationController extends Controller
{
    // RESERVASI
    public function reservasi(Request $request, $id = null)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filter.status' => [
                'nullable',
                'in:waiting,pending,reserved,confirmed,completed,cancelled,forfeited',
            ],
            'waktu_mulai' => ['nullable', 'date_format:Y-m-d'],
            'waktu_selesai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = Auth::user();
        $role = strtolower(trim((string) (
            $user?->role ?? $user?->status ?? ''
        )));
        $isKasir = in_array($role, ['kasir', 'cashier'], true);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = strtolower(trim((string) data_get($validated, 'filter.status', '')));
        $waktuMulai = $validated['waktu_mulai'] ?? today()->toDateString();
        $waktuSelesai = $validated['waktu_selesai'] ?? '';

        if (
            $waktuSelesai !== ''
            && $waktuSelesai < $waktuMulai
        ) {
            throw ValidationException::withMessages([
                'waktu_selesai' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            ]);
        }

        $query = Reservasi::with([
            'customer',
            'detailReservasi.meja',
            'pesanan.user',
            'pesanan.detailPesanan.menu',
            'pesanan.pembayaran',
        ]);

        // Admin yang mencari nama/telepon mencari lintas tanggal.
        // Kasir tetap dibatasi oleh rentang waktu yang dipilih.
        $searchAcrossDates = $search !== '' && !$isKasir;

        if (!$searchAcrossDates) {
            $query->whereDate('reservasi.tanggal', '>=', $waktuMulai);

            if ($waktuSelesai !== '') {
                $query->whereDate('reservasi.tanggal', '<=', $waktuSelesai);
            }
        }

        if ($search !== '') {
            $query->whereHas('customer', function ($customer) use ($search) {
                $customer->where('nama', 'like', "%{$search}%")
                    ->orWhere('nomor_telp', 'like', "%{$search}%");
            });
        }

        if ($status === 'forfeited') {
            $query->where('reservasi.dp_forfeited', true)
                ->whereHas('pesanan', fn ($order) =>
                    $order->whereRaw("LOWER(TRIM(status)) = 'cancelled'")
                );
        } elseif ($status !== '') {
            $query->whereHas('pesanan', fn ($order) =>
                $order->whereRaw('LOWER(TRIM(status)) = ?', [$status])
            );
        } else {
            $query->whereHas('pesanan', fn ($order) =>
                $order->whereRaw(
                    "LOWER(TRIM(status)) IN ('waiting', 'reserved', 'confirmed', 'completed')"
                )
            );
        }

        $reservasiList = $query
            ->orderByDesc('reservasi.created_at')
            ->orderByDesc('reservasi.id')
            ->paginate(50)
            ->withQueryString();

        $selectedId = $id ?? $request->query('id');

        $selectedReservasi = $selectedId
            ? Reservasi::with([
                'customer',
                'detailReservasi.meja',
                'pesanan.user',
                'pesanan.detailPesanan.menu',
                'pesanan.pembayaran',
            ])->find($selectedId)
            : null;

        $dpBayar = 0;
        $sisaTagihan = 0;
        if ($selectedReservasi) {
            $totalHarga = (float) ($selectedReservasi->pesanan->total_harga ?? 0);
            $dpBayar = (float) ($selectedReservasi->pesanan?->pembayaran
                ?->filter(fn ($p) => strtolower(trim((string) $p->jenis)) === 'dp' && strtolower(trim((string) $p->status)) === 'success')
                ->sum('nominal') ?? 0);
            $sisaTagihan = max(0, $totalHarga - $dpBayar);
        }

        return view('admin.reservasi', compact(
            'reservasiList',
            'selectedReservasi',
            'selectedId',
            'dpBayar',
            'sisaTagihan',
            // 'isKasir',
            'waktuMulai',
            'waktuSelesai'
        ));
    }

    public function decideApproval(Request $request, int $id, BookingController $bookingController)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $id) {
            $reservation = Reservasi::whereKey($id)->lockForUpdate()->firstOrFail();
            $order = Pesanan::whereKey($reservation->id_pesanan)->lockForUpdate()->firstOrFail();
            if (strtolower(trim((string) $order->status)) !== 'waiting') {
                throw ValidationException::withMessages(['status' => 'Reservasi ini sudah diproses.']);
            }

            if ($data['decision'] === 'approve') {
                $order->status = 'Pending';
            } else {
                $order->status = 'Cancelled';
                $reservation->rejection_reason = trim((string) $data['rejection_reason']);
                $reservation->save();
                $order->pembayaran()
                    ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                    ->whereRaw("LOWER(TRIM(status)) IN ('pending', 'waiting')")
                    ->update(['status' => 'Failed']);
            }
            $order->save();
        }, 3);

        $reservation = Reservasi::with(['customer', 'pesanan'])->findOrFail($id);
        $bookingController->notifyApprovalDecision($reservation);

        return redirect()->route('admin.reservasi', $id)->with('success',
            $data['decision'] === 'approve' ? 'Reservasi disetujui.' : 'Reservasi ditolak.'
        );
    }

    public function reservasiUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,completed,cancelled'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $reservasi = Reservasi::findOrFail($id);

            $pesanan = Pesanan::whereKey($reservasi->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            $statusSaatIni = strtolower(trim((string) $pesanan->status));

            if ($validated['status'] === 'confirmed') {
                if ($statusSaatIni !== 'reserved') {
                    throw ValidationException::withMessages([
                        'status' => 'Hanya reservasi berstatus reserved yang dapat dikonfirmasi.',
                    ]);
                }

                $pesanan->status = 'Confirmed';
            } elseif ($validated['status'] === 'cancelled') {
                if ($statusSaatIni !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'status' => 'Hanya reservasi confirmed yang dapat dibatalkan.',
                    ]);
                }
                $pesanan->status = 'Cancelled';
                $reservasi->cancellation_reason = 'Dibatalkan oleh admin.';
                $reservasi->save();
                $reservasi->detailReservasi()->delete();
            } else {
                if ($statusSaatIni !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'status' => 'Hanya reservasi berstatus confirmed yang dapat diselesaikan.',
                    ]);
                }

                $totalDpSukses = (float) $pesanan->pembayaran()
                    ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                    ->whereRaw("LOWER(TRIM(status)) = 'success'")
                    ->sum('nominal');

                $totalPesanan = (float) $pesanan->total_harga;

                if ($totalPesanan <= 0 || $totalDpSukses < $totalPesanan) {
                    throw ValidationException::withMessages([
                        'status' => 'Total DP yang berhasil dibayar belum melunasi pesanan.',
                    ]);
                }

                // DP sudah melunasi seluruh tagihan: cukup ubah status pesanan.
                // Tidak membuat record pembayaran pelunasan.
                $pesanan->status = 'Completed';
                $pesanan->completed_at = now();
            }

            if (Auth::check()) {
                $pesanan->id_user = Auth::id();
            }

            $pesanan->save();
        });

        $message = $validated['status'] === 'completed'
            ? 'Reservasi berhasil diselesaikan.'
            : 'Reservasi berhasil dikonfirmasi.';

        return redirect()
            ->route('admin.reservasi', $id)
            ->with('success', $message);
    }

    public function lunas(Request $request, $id)
    {
        $validated = $request->validate([
            'metode_pelunasan' => ['required', 'in:tunai,qris,debit,transfer_bank'],
            'uang_diterima' => [
                'required_if:metode_pelunasan,tunai',
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $reservasi = Reservasi::with(['pesanan.pembayaran'])->findOrFail($id);
        $pesanan = $reservasi->pesanan;

        if (!$pesanan || strtolower($pesanan->status) !== 'confirmed') {
            return back()->with('error', 'Reservasi harus berstatus confirmed untuk dilunasi.');
        }

        $dp = (float) $pesanan->pembayaran()
            ->where('jenis', 'DP')
            ->where('status', 'Success')
            ->sum('nominal');

        $sisa = max(0, (float) $pesanan->total_harga - $dp);

        if ($sisa <= 0) {
            return back()->with('error', 'Tidak ada sisa tagihan yang perlu dilunasi.');
        }

        DB::transaction(function () use ($validated, $pesanan, $sisa) {
        $paymentData = [
            'id_pesanan' => $pesanan->id,
            'jenis' => 'Pelunasan',
            'metode' => strtolower($validated['metode_pelunasan']),
            'nominal' => $sisa,
            'status' => 'Success',
        ];

        if ($paymentData['metode'] === 'tunai') {
            $diterima = (float) ($validated['uang_diterima'] ?? $sisa);
            if ($diterima < $sisa) {
                throw ValidationException::withMessages([
                    'uang_diterima' => 'Uang diterima kurang dari sisa tagihan.',
                ]);
            }

            $paymentData['uang_diterima'] = $diterima;
            $paymentData['uang_kembali'] = $diterima - $sisa;
        }

        Pembayaran::create($paymentData);

        $pesanan->status = 'Completed';
        $pesanan->completed_at = now();
        if (Auth::check()) {
            $pesanan->id_user = Auth::id();
        }
        $pesanan->save();
    });

        return back()->with('success', 'Pelunasan berhasil dicatat.');
    }

    public function nota($id, NotaService $notaService)
    {
        $reservasi = Reservasi::with('pesanan')->findOrFail($id);
        abort_if(!$reservasi->pesanan, 404);

        $jenis = request('jenis');

        abort_unless(in_array($jenis, [
            'reservation_customer',
            'reservation_kitchen',
            'reservation_dp',
        ], true), 404);

        return $notaService->render($reservasi->pesanan, $jenis, $reservasi);
    }
}
