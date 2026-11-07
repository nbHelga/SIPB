<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservasi;
use App\Models\Meja;
use App\Models\DetailReservasi;

// use Illuminate\Support\Facades\DB;

class DetailReservasiSeeder extends Seeder
{
    public function run(): void
    {
        $mejas = Meja::all();
        if ($mejas->isEmpty()) {
            return;
        }

        // -------------------------------------------------------------
        // A. ALOKASI MEJA UNTUK SKENARIO TESTING BENTROK JAM (A s/d K)
        // -------------------------------------------------------------
        // Peta ID Reservasi ke ID Meja sesuai Skenario Testing A-K
        $skenarioMejaMapping = [
            // Skenario A (ID 1): Di-plot ke Meja #2 dan #3 (Bentrok dengan B)
            1 => [2, 3],

            // Skenario B (ID 2): Belum di-plot (Target test bentrok terhadap Meja #2 & #3)
            2 => [],

            // Skenario C (ID 3): Di-plot ke Meja #5
            3 => [5],

            // Skenario D (ID 4): Belum di-plot (Target test jam aman terhadap Meja #5)
            4 => [],

            // Skenario E (ID 5): Di-plot ke Meja #8
            5 => [8],

            // Skenario F & G (ID 6 & 7): Belum di-plot (Target test batas toleransi 120 menit)
            6 => [],
            7 => [],

            // Skenario H (ID 8): Di-plot ke Meja #1 dan #2 (Lantai 1)
            8 => [1, 2],

            // Skenario I (ID 9): Di-plot ke Meja #17 dan #18 (Lantai 2 - Beda meja walau jam sama)
            9 => [17, 18],

            // Skenario J (ID 10): Di-plot ke Meja #1 (Beda tanggal)
            10 => [1],

            // Skenario K (ID 11): Belum di-plot (Target test bentrok gabungan dengan H)
            11 => [],
        ];

        foreach ($skenarioMejaMapping as $reservasiId => $mejaIds) {
            // Pastikan reservasi terkait memang ada
            $reservasi = Reservasi::find($reservasiId);
            if (!$reservasi) {
                continue;
            }

            foreach ($mejaIds as $mejaId) {
                // Pastikan meja tersedia
                if ($mejas->contains('id', $mejaId)) {
                    DetailReservasi::create([
                        'id_reservasi' => $reservasiId,
                        'id_meja'      => $mejaId,
                    ]);
                }
            }
        }

        // -------------------------------------------------------------
        // B. ALOKASI MEJA UNTUK RESERVASI HISTORIS (ID 1001 - 1020)
        // -------------------------------------------------------------
        $reservasiHistoris = Reservasi::where('id', '>=', 1001)
            ->where('id', '<=', 1020)
            ->get();

        foreach ($reservasiHistoris as $res) {
            // Berikan 1 meja acak untuk setiap reservasi historis
            $mejaRandom = $mejas->random();

            DetailReservasi::firstOrCreate([
                'id_reservasi' => $res->id,
                'id_meja'      => $mejaRandom->id,
            ]);
        }
    }
}
