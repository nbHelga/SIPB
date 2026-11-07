<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\NotaService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    // SELF ORDER (Pesanan)
    public function selfOrder(Request $request, $id = null)
    {
        $validated = $request->validate([
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'filter.status' => [
                'nullable',
                'in:pending,paid,processing,completed,cancelled',
            ],
        ]);

        $filterTanggal = $validated['tanggal'] ?? today()->toDateString();
        $start = Carbon::parse($filterTanggal)->startOfDay();
        $end = $start->copy()->addDay();
        $search = trim((string) ($validated['search'] ?? ''));
        $filterStatus = strtolower(trim((string) data_get($validated, 'filter.status', '')));

        $query = Pesanan::with(['meja', 'user', 'detailPesanan.menu', 'pembayaran'])
            ->whereDoesntHave('reservasi')
            ->where('pesanan.created_at', '>=', $start)
            ->where('pesanan.created_at', '<', $end);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pesanan.id', 'like', "%{$search}%")
                    ->orWhere('pesanan.kode_pesanan', 'like', "%{$search}%")
                    ->orWhereHas('meja', fn ($meja) =>
                        $meja->where('nomor_meja', 'like', "%{$search}%")
                    );
            });
        }

        if ($filterStatus !== '') {
            $query->whereRaw('LOWER(TRIM(pesanan.status)) = ?', [$filterStatus]);
        } else {
            // Default mengecualikan Cancelled; pending hanya pembayaran tunai.
            $query->where(function ($q) {
                $q->whereRaw(
                    "LOWER(TRIM(pesanan.status)) IN ('paid', 'processing', 'completed')"
                )->orWhere(function ($pending) {
                    $pending->whereRaw("LOWER(TRIM(pesanan.status)) = 'pending'")
                        ->whereHas('pembayaran', fn ($payment) => $payment
                            ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                            ->whereRaw("LOWER(TRIM(metode)) = 'tunai'")
                            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                        );
                });
            });
        }

        $pesananList = $query
            ->orderByDesc('pesanan.created_at')
            ->orderByDesc('pesanan.id')
            ->paginate(20)
            ->withQueryString();

        $selectedId = $id ?? $request->query('id');

        $selectedPesanan = $selectedId
            ? Pesanan::with(['meja', 'user', 'detailPesanan.menu', 'pembayaran'])
                ->whereDoesntHave('reservasi')
                ->find($selectedId)
            : null;

        return view('admin.selforder', compact(
            'pesananList',
            'selectedPesanan',
            'selectedId',
            'filterTanggal'
        ));
    }

    // public function orderUpdate(Request $request, $id)
    // {
    //     $request->validate([
    //         'status' => ['required', 'in:pending,paid,Paid,Processing,Completed,processing,completed'],
    //     ]);

    //     $pesanan = Pesanan::whereDoesntHave('reservasi')->findOrFail($id);
    //     $current = strtolower($pesanan->status);
    //     $next = strtolower($request->status);
    //     $fullPayment = $pesanan->pembayaran()
    //         ->where('jenis', 'Full')
    //         ->latest('id')
    //         ->first();

    //     $validTransition =
    //         ($current === 'pending' && $next === 'paid'
    //             && $fullPayment
    //             && $fullPayment->metode === 'tunai'
    //             && $fullPayment->status === 'Pending') ||
    //         ($current === 'paid' && $next === 'processing') ||
    //         ($current === 'processing' && $next === 'completed');

    //     if (!$validTransition) {
    //         return back()->with('error', 'Perubahan status pesanan tidak valid.');
    //     }

    //     DB::transaction(function () use ($pesanan, $fullPayment, $next) {
    //         if ($next === 'paid') {
    //             $fullPayment->update(['status' => 'Success']);
    //         }

    //         $pesanan->status = match ($next) {
    //             'paid' => 'Paid',
    //             'processing' => 'Processing',
    //             'completed' => 'Completed',
    //         };
    //         $pesanan->id_user = Auth::id();

    //         if ($next === 'completed' && !$pesanan->completed_at) {
    //             $pesanan->completed_at = now();
    //         }

    //         $pesanan->save();
    //     });

    //     return redirect()->route('admin.selforder', $id)
    //         ->with('success', 'Status pesanan berhasil diperbarui.');
    // }

    public function orderUpdate(Request $request, $id)
{
    $request->validate([
        'status' => ['required', 'string'],
    ]);

    $next = strtolower(trim($request->input('status')));
    abort_unless(in_array($next, ['paid', 'processing', 'completed'], true), 422);

    $pesanan = Pesanan::whereDoesntHave('reservasi')->findOrFail($id);
    $current = strtolower(trim((string) $pesanan->status));

    $fullPayment = $pesanan->pembayaran()
        ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
        ->latest('id')
        ->first();

    if ($next === 'paid') {
        // abort_unless(
        //     $fullPayment
        //         && strtolower(trim((string) $fullPayment->metode)) === 'tunai'
        //         && strtolower(trim((string) $fullPayment->status)) === 'pending',
        //     422,
        //     'Pembayaran tunai tidak valid.'
        // );
        if (
            $current !== 'pending'
            || !$fullPayment
            || strtolower(trim((string) $fullPayment->metode)) !== 'tunai'
            || strtolower(trim((string) $fullPayment->status)) !== 'pending'
        ) {
            return back()->with('error', 'Pembayaran tunai tidak valid atau sudah diproses.');
        }

        $request->validate([
            'uang_diterima' => [
                'required',
                'integer',
                'min:' . (int) $pesanan->total_harga,
            ],
        ]);
    } elseif (
        !(($current === 'paid' && $next === 'processing')
            || ($current === 'processing' && $next === 'completed'))
    ) {
        return back()->with('error', 'Perubahan status pesanan tidak valid.');
    }

    DB::transaction(function () use ($pesanan, $next, $request) {
        $pesanan = Pesanan::whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

        if ($next === 'paid') {
            $payment = $pesanan->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                ->lockForUpdate()
                ->latest('id')
                ->firstOrFail();

            $total = (int) $pesanan->total_harga;
            $diterima = (int) $request->input('uang_diterima');

            $payment->update([
                'nominal' => $total,
                'status' => 'Success',
                'uang_diterima' => $diterima,
                'uang_kembali' => $diterima - $total,
            ]);
        }

        $pesanan->status = match ($next) {
            'paid' => 'Paid',
            'processing' => 'Processing',
            'completed' => 'Completed',
        };
        $pesanan->id_user = Auth::id();

        if ($next === 'completed' && !$pesanan->completed_at) {
            $pesanan->completed_at = now();
        }

        $pesanan->save();
    });

    return redirect()->route('admin.selforder', $id)
        ->with('success', 'Status pesanan berhasil diperbarui.');
}

    public function nota($id, NotaService $notaService)
    {
        $pesanan = Pesanan::whereDoesntHave('reservasi')->findOrFail($id);
        $jenis = request('jenis');

        abort_unless(in_array($jenis, ['self_customer', 'self_kitchen'], true), 404);

        return $notaService->render($pesanan, $jenis);
    }
}
