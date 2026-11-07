<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use App\Models\DetailReservasi;
use App\Models\Meja;
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

        $allMeja = Meja::orderBy('lantai')->orderBy('nomor_meja')->get();
        $denahMeja = $allMeja->groupBy('lantai');
        $lantaiList = $allMeja->pluck('lantai')->unique()->values();

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
                    'tanggal' => Carbon::parse($r->tanggal)->format('Y-m-d'),
                    'tgl_formatted' => Carbon::parse($r->tanggal)->format('d/m'),
                    'jam_mulai' => $startCarbon->format('H:i'),
                    'jam_selesai' => $endCarbon->format('H:i'),
                    // Kalkulasi Menit dari Pukul 00:00 (Dijamin akurat)
                    'start_minutes' => ($startCarbon->hour * 60) + $startCarbon->minute,
                    'end_minutes' => ($endCarbon->hour * 60) + $endCarbon->minute,
                    'status' => $r->pesanan->status ?? 'Confirmed',
                    'meja_ids' => $r->detailReservasi->pluck('id_meja')->map(fn($id) => (string) $id)->toArray()
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
        foreach ($allMeja as $meja) {
        //     if ($meja->status === 'occupied') {
        //         $mejaStatusAwal[$meja->id] = 'occupied';
        //     } else {
        //         $mejaStatusAwal[$meja->id] = 'available';
        //         // ini buat kuning awal2
        //         foreach ($reservasiConfirmed as $res) {
        //             if (in_array((string)$meja->id, $res['meja_ids'])) {
        //                 $mejaStatusAwal[$meja->id] = 'reserved';
        //                 break;
        //             }
        //         }
        //     }
        // }
            $mejaStatusAwal[(string) $meja->id] = 'available';
        }

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

        return view('admin.meja', compact('allMeja', 'denahMeja', 'lantaiList', 'reservasiConfirmed', 'mejaStatusAwal'));
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
            'nomor_meja' => 'required|string|max:10|unique:meja,nomor_meja',
            'lantai' => 'required|string|max:50',
            'status' => 'required|in:available,occupied,reserved',
        ]);

        Meja::create($validated);

        return redirect()->route('admin.meja')->with('success', 'Alokasi meja berhasil ditambahkan!');
    }

    public function mejaUpdate(Request $request, $id)
    {
        $meja = Meja::findOrFail($id);

        $validated = $request->validate([
            'nomor_meja' => 'required|string|max:20',
            'lantai' => 'required|string|max:50',
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $meja->update($validated);

        return redirect()->route('admin.meja')->with('success', 'Meja berhasil diupdate!');
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
