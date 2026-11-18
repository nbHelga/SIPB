<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use App\Models\DetailReservasi;
use App\Models\Meja;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TableController extends Controller
{
    // MEJA
    public function table(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $allMeja = Meja::query()->get()->sort(function (Meja $left, Meja $right) {
            $floorOrder = (int) $left->lantai <=> (int) $right->lantai;

            return $floorOrder !== 0
                ? $floorOrder
                : strnatcasecmp($left->nomor_meja, $right->nomor_meja);
        })->values();
        $denahMeja = collect([
            '1' => $allMeja->where('lantai', '1')->values(),
            '2' => $allMeja->where('lantai', '2')->values(),
        ]);
        $lantaiList = collect(['1', '2']);

        // Hanya reservasi yang statusnya CONFIRMED untuk alokasi
        $allReservations = Reservasi::with(['customer', 'detailReservasi.meja', 'pesanan'])
            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'Confirmed');
            })
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($r) {
                // PARSING AMAN menggunakan Carbon
                $startCarbon = Carbon::parse($r->jam_mulai);
                $endCarbon = $r->jam_selesai ? Carbon::parse($r->jam_selesai) : $startCarbon->copy()->addMinutes(120);

                return [
                    'id' => (string) $r->id,
                    'customer_nama' => $r->customer->nama ?? 'Guest',
                    'jml_org' => $r->jml_org ?? 0,
                    'tanggal' => Carbon::parse($r->tanggal)->format('Y-m-d'),
                    'tgl_formatted' => Carbon::parse($r->tanggal)->format('d/m'),
                    'jam_mulai' => $startCarbon->format('H:i'),
                    'jam_selesai' => $endCarbon->format('H:i'),
                    // Kalkulasi Menit dari Pukul 00:00 (Dijamin akurat)
                    'start_minutes' => ($startCarbon->hour * 60) + $startCarbon->minute,
                    'end_minutes' => ($endCarbon->hour * 60) + $endCarbon->minute,
                    'status' => $r->pesanan->status ?? 'Confirmed',
                    'meja_ids' => $r->detailReservasi
                        ->filter(fn ($detail) => (bool) $detail->meja?->is_active)
                        ->pluck('id_meja')
                        ->map(fn ($id) => (string) $id)
                        ->values()
                        ->toArray(),
                ];
            });

        // 1B. Hitung Meja Tabrakan murni di Controller
        // $reservasiConfirmed = $allReservations->map(function ($current) use ($allReservations) {
        //     $conflictTableIds = [];

        //     foreach ($allReservations as $other) {
        //         if ($other['id'] === $current['id']) continue; // Lewati dirinya sendiri

        //         $tanggalSama = $other['tanggal'] === $current['tanggal'];
        //         // LOGIKA OVERLAP: (Mulai A < Selesai B) DAN (Mulai B < Selesai A)
        //         $waktuBertabrakan = ($current['start_minutes'] < $other['end_minutes']) && ($other['start_minutes'] < $current['end_minutes']);

        //         if ($tanggalSama && $waktuBertabrakan && !empty($other['meja_ids'])) {
        //             $conflictTableIds = array_merge($conflictTableIds, $other['meja_ids']);
        //         }
        //     }

        //     // Pastikan nilainya string dan unik
        //     $current['conflict_meja_ids'] = array_values(array_unique(array_map('strval', $conflictTableIds)));
        //     return $current;
        // })->values();

        // 2. Hitung Status Lampu Awal Meja (Available/Occupied/Reserved)
        $mejaStatusAwal = [];
        

        // $allReservations = $reservasiConfirmed;

        // $reservasiConfirmed = $allReservations->map(function ($current) use ($allReservations) {      

        // Hitung conflict hanya saat reservasi dipilih
        $reservasiConfirmed = $allReservations->map(function ($current) use ($allReservations) {
            // $conflictTableIds = [];
            $conflictTableIds = [];

            foreach ($allReservations as $other) {
                if ((string) $other['id'] === (string) $current['id']) {
                    continue;
                }

                $tanggalSama = $other['tanggal'] === $current['tanggal'];
                $waktuBertabrakan =
                    $current['start_minutes'] < $other['end_minutes'] &&
                    $other['start_minutes'] < $current['end_minutes'];

                if ($tanggalSama && $waktuBertabrakan && !empty($other['meja_ids'])) {
                    // Gabungkan meja yang bentrok
                    $conflictTableIds = array_merge($conflictTableIds, $other['meja_ids']);
                }
            }

            // Hapus duplikat dan pastikan semua ID menjadi string flat array
            $current['conflict_meja_ids'] = array_values(array_unique(array_map('strval', $conflictTableIds)));

            return $current;
        })->values();

        foreach ($allMeja as $meja) {
            $status = !$meja->is_active ? 'inactive' : $meja->status;

            if ($meja->is_active && $status === 'available') {
                foreach ($reservasiConfirmed as $reservation) {
                    if (
                        $reservation['tanggal'] === Carbon::today()->toDateString()
                        && in_array((string) $meja->id, $reservation['meja_ids'], true)
                    ) {
                        $status = 'reserved';
                        break;
                    }
                }
            }

            $mejaStatusAwal[(string) $meja->id] = $status;
        }

        $canManageTables = strtolower(trim((string) auth()->user()?->status)) === 'admin';

        return view('admin.meja', compact(
            'allMeja',
            'denahMeja',
            'lantaiList',
            'reservasiConfirmed',
            'mejaStatusAwal',
            'canManageTables'
        ));
    }

    public function mejaJson($id)
    {
        $meja = Meja::findOrFail($id);

        // Cek apakah ada reservasi untuk tanggal hari ini yang memanfaatkan meja ini
        // Status diambil dari tabel pesanan via whereHas
        $reservasiHariIni = Reservasi::whereDate('tanggal', Carbon::today())
            ->whereHas('pesanan', function ($q) {
                $q->whereIn('status', ['confirmed', 'reserved', 'processing']);
            })
            ->whereHas('detailReservasi', function ($q) use ($meja) {
                $q->where('id_meja', $meja->id);
            })
            ->with(['pesanan.detailPesanan.menu', 'customer'])
            ->first();

        if ($reservasiHariIni) {
            $pesanan = $reservasiHariIni->pesanan;
            $menuDipesan = [];

            if ($pesanan && $pesanan->detailPesanan) {
                $menuDipesan = $pesanan->detailPesanan->map(function ($dp) {
                    return [
                        'nama' => $dp->menu->nama ?? '-',
                        'jumlah' => $dp->jumlah,
                    ];
                });
            }

            $meja->reservasi_aktif = [
                'id_reservasi' => $reservasiHariIni->id,
                'nama_customer' => $reservasiHariIni->customer->nama ?? null,
                'jumlah_orang' => $reservasiHariIni->jml_org ?? null,
                'jam' => Carbon::parse($reservasiHariIni->jam_mulai)->format('H:i'),
                'menu_dipesan' => $menuDipesan,
            ];
        } else {
            $meja->reservasi_aktif = null;
        }

        return response()->json($meja);
    }

    public function mejaStore(Request $request)
    {
        $validated = $request->validate([
            'nomor_meja' => ['required', 'string', 'max:10', 'unique:meja,nomor_meja'],
            'lantai' => ['required', 'in:1,2'],
        ]);

        try {
            Meja::create([
                'nomor_meja' => trim($validated['nomor_meja']),
                'lantai' => $validated['lantai'],
                'status' => 'available',
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menambahkan meja.', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Meja gagal ditambahkan. Silakan coba lagi.');
        }

        return redirect()->route('admin.meja')->with('success', 'Meja berhasil ditambahkan.');
    }

    public function mejaUpdate(Request $request, int $id)
    {
        $validated = $request->validate([
            'nomor_meja' => ['required', 'string', 'max:10', 'unique:meja,nomor_meja,' . $id],
            'lantai' => ['required', 'in:1,2'],
        ]);

        Meja::whereKey($id)->update([
            'nomor_meja' => trim($validated['nomor_meja']),
            'lantai' => $validated['lantai'],
        ]);

        return redirect()->route('admin.meja')->with('success', 'Nomor dan lantai meja berhasil diperbarui.');
    }

    public function mejaDelete(int $id)
    {
        $table = Meja::findOrFail($id);
        $hasActiveSelfOrder = Pesanan::query()
            ->where('id_meja', $table->id)
            ->whereIn('status', ['Pending', 'Paid', 'Processing'])
            ->exists();
        $hasActiveReservation = Reservasi::query()
            ->whereHas('detailReservasi', fn ($details) => $details->where('id_meja', $table->id))
            ->whereHas('pesanan', fn ($orders) => $orders->whereIn('status', ['Pending', 'Waiting', 'Reserved', 'Confirmed', 'Processing']))
            ->exists();

        if ($hasActiveSelfOrder || $hasActiveReservation) {
            return back()->with('error', 'Meja tidak dapat dihapus karena masih digunakan pesanan atau reservasi aktif.');
        }

        $table->delete();

        return redirect()->route('admin.meja')->with('success', 'Meja berhasil dihapus.');
    }

    public function mejaToggleActive(Request $request, int $id)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $active = (bool) $validated['is_active'];

        DB::transaction(function () use ($id, $active) {
            $table = Meja::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if (!$active) {
                $hasActiveSelfOrder = Pesanan::query()
                    ->where('id_meja', $table->id)
                    ->whereIn('status', ['Pending', 'Paid', 'Processing'])
                    ->exists();

                $hasActiveReservation = Reservasi::query()
                    ->whereHas('detailReservasi', fn ($details) =>
                        $details->where('id_meja', $table->id)
                    )
                    ->whereHas('pesanan', fn ($orders) => $orders->whereIn('status', ['Pending', 'Reserved', 'Confirmed', 'Processing']))
                    ->exists();

                if ($hasActiveSelfOrder || $hasActiveReservation) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Meja masih terpakai oleh pesanan atau reservasi aktif.',
                    ]);
                }
            }

            $table->update(['is_active' => $active]);
        }, 3);

        return redirect()->route('admin.meja')->with(
            'success',
            $active ? 'Meja berhasil diaktifkan.' : 'Meja berhasil dinonaktifkan.'
        );
    }

    public function assignMeja(Request $request, $id)
    {
        $validated = $request->validate([
            'meja_dipilih' => ['nullable', 'array'],
            'meja_dipilih.*' => ['required', 'integer', 'distinct', 'exists:meja,id'],
        ]);

        $reservasi = Reservasi::with('pesanan')->findOrFail($id);

        abort_unless(
            strtolower((string) $reservasi->pesanan?->status) === 'confirmed',
            422,
            'Meja hanya dapat dialokasikan untuk reservasi terkonfirmasi.'
        );

        $selectedTableIds = collect($validated['meja_dipilih'] ?? [])
            ->map(fn ($tableId) => (int) $tableId)
            ->unique()
            ->values();

        DB::transaction(function () use ($reservasi, $selectedTableIds) {
            $lockedTables = Meja::query()
                ->whereIn('id', $selectedTableIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedTables->contains(fn (Meja $meja) => !$meja->is_active)) {
                throw ValidationException::withMessages([
                    'meja_dipilih' => 'Meja nonaktif tidak dapat dialokasikan.',
                ]);
            }

            $otherReservations = Reservasi::with(['pesanan', 'detailReservasi'])
                ->whereKeyNot($reservasi->id)
                ->whereDate('tanggal', $reservasi->tanggal)
                ->whereHas('pesanan', fn ($q) => $q->where('status', 'Confirmed'))
                ->lockForUpdate()
                ->get();

            $start = Carbon::parse($reservasi->jam_mulai);
            $end = Carbon::parse($reservasi->jam_selesai);

            foreach ($otherReservations as $other) {
                $otherStart = Carbon::parse($other->jam_mulai);
                $otherEnd = Carbon::parse($other->jam_selesai);

                // Batas aman 120 menit: tepat 120 menit tidak dianggap bentrok.
                $conflict = $start->lt($otherEnd->copy()->addMinutes(120))
                    && $otherStart->lt($end->copy()->addMinutes(120));

                if (!$conflict) {
                    continue;
                }

                $occupiedIds = $other->detailReservasi
                    ->pluck('id_meja')
                    ->map(fn ($tableId) => (int) $tableId);

                if ($selectedTableIds->intersect($occupiedIds)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'meja_dipilih' => 'Meja yang dipilih bentrok dengan reservasi lain dalam rentang waktu tersebut.',
                    ]);
                }
            }

            $reservasi->detailReservasi()->delete();

            foreach ($selectedTableIds as $tableId) {
                DetailReservasi::create([
                    'id_reservasi' => $reservasi->id,
                    'id_meja' => $tableId,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Meja reservasi berhasil diperbarui!');
    }
}
