<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        
        // Ambil master data yang diasumsikan sudah ada
        $menus = DB::table('menu')->get();
        $mejas = DB::table('meja')->get();
        $customers = DB::table('customer')->get();

        if ($menus->isEmpty() || $mejas->isEmpty() || $customers->isEmpty()) {
            $this->command->error('Pastikan MenuSeeder, MejaSeeder, dan CustomerSeeder sudah dijalankan!');
            return;
        }

        // =====================================================================
        // 1. DATA HISTORIS SELF-ORDER (1 BULAN TERAKHIR)
        // =====================================================================
        $this->command->info('Membuat Data Historis Self-Order (1 Bulan Terakhir)...');
        for ($i = 0; $i < 60; $i++) {
            $date = Carbon::now()->subDays(rand(1, 30))->subHours(rand(1, 24));
            $isCompleted = rand(1, 100) <= 85; // 85% Sukses, 15% Batal
            $status = $isCompleted ? 'Completed' : 'Cancelled';
            $kode = 'SOD-' . $date->format('md') . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            
            // Insert Pesanan
            $pesananId = DB::table('pesanan')->insertGetId([
                'kode_pesanan' => $kode,
                'id_user'      => 2, // Sesuai instruksi (Kasir 1)
                'id_meja'      => $mejas->random()->id,
                'total_harga'  => 0, // Akan diupdate
                'status'       => $status,
                'created_at'   => $date,
                'completed_at' => $isCompleted ? $date->copy()->addMinutes(rand(30, 60)) : null,
                'updated_at'   => $date,
            ]);

            // Insert Detail Pesanan
            $totalHarga = 0;
            $items = [];
            for ($j = 0; $j < rand(2, 5); $j++) {
                $menu = $menus->random();
                $qty = rand(1, 3);
                $subtotal = $menu->harga * $qty;
                $totalHarga += $subtotal;

                $items[] = [
                    'id_pesanan' => $pesananId,
                    'id_menu'    => $menu->id,
                    'jumlah'     => $qty,
                    'subtotal'   => $subtotal,
                    'catatan'    => rand(0, 1) ? $faker->sentence(3) : null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
            DB::table('detail_pesanan')->insert($items);
            DB::table('pesanan')->where('id', $pesananId)->update(['total_harga' => $totalHarga]);

            // Insert Pembayaran (Tunai atau other_qris)
            $isMidtrans = rand(0, 1) == 1;
            DB::table('pembayaran')->insert([
                'id_pesanan'        => $pesananId,
                'jenis'             => 'Full',
                'metode'            => $isMidtrans ? 'other_qris' : 'tunai',
                'nominal'           => $totalHarga,
                'snap_token'        => $isMidtrans ? 'snap-sod-' . Str::random(10) : null,
                'midtrans_order_id' => $isMidtrans ? 'ORDER-' . $pesananId . '-' . Str::random(5) : null,
                'status'            => $isCompleted ? 'Success' : 'Failed',
                'uang_diterima'     => null, // SelfOrder tunai langsung pas sesuai permintaan
                'uang_kembali'      => null,
                'created_at'        => $date,
                'updated_at'        => $date,
            ]);
        }

        // =====================================================================
        // 2. DATA HISTORIS RESERVASI (3 BULAN TERAKHIR)
        // =====================================================================
        $this->command->info('Membuat Data Historis Reservasi (3 Bulan Terakhir)...');
        for ($i = 0; $i < 60; $i++) {
            $createdDate = Carbon::now()->subDays(rand(1, 90))->subHours(rand(1, 24));
            $resDate = $createdDate->copy()->addDays(rand(1, 14));
            $jamMulai = Carbon::createFromTime(rand(15, 21), rand(0, 1) * 30);
            $jamSelesai = $jamMulai->copy()->addHours(2);
            
            $isCompleted = rand(1, 100) <= 80;
            $status = $isCompleted ? 'Completed' : 'Cancelled';
            $kode = 'RSV-' . $createdDate->format('md') . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            
            $totalHarga = 0;
            $items = [];
            for ($j = 0; $j < rand(3, 8); $j++) {
                $menu = $menus->random();
                $qty = rand(1, 4);
                $sub = $menu->harga * $qty;
                $totalHarga += $sub;
                $items[] = [
                    'id_menu'    => $menu->id,
                    'jumlah'     => $qty,
                    'subtotal'   => $sub,
                    'catatan'    => rand(0, 1) ? 'Notes: ' . $faker->word : null,
                    'created_at' => $createdDate,
                    'updated_at' => $createdDate,
                ];
            }

            // Pastikan total pesanan reservasi >= 50k
            if ($totalHarga < 50000) {
                $menuTambahan = $menus->where('harga', '>=', 50000 - $totalHarga)->first() ?? $menus->first();
                $items[] = [
                    'id_menu'    => $menuTambahan->id,
                    'jumlah'     => 1,
                    'subtotal'   => $menuTambahan->harga,
                    'catatan'    => 'Menu syarat DP',
                    'created_at' => $createdDate,
                    'updated_at' => $createdDate,
                ];
                $totalHarga += $menuTambahan->harga;
            }

            $pesananId = DB::table('pesanan')->insertGetId([
                'kode_pesanan' => $kode,
                'id_user'      => 2,
                'id_meja'      => null, // Diatur di detail_reservasi
                'total_harga'  => $totalHarga,
                'status'       => $status,
                'created_at'   => $createdDate,
                'completed_at' => $isCompleted ? $resDate->copy()->setTime($jamSelesai->hour, $jamSelesai->minute) : null,
                'updated_at'   => $createdDate,
            ]);

            foreach ($items as &$it) { $it['id_pesanan'] = $pesananId; }
            DB::table('detail_pesanan')->insert($items);

            // Logika Alokasi Meja (Min 4 org, Max 6 org per meja)
            $jmlOrg = rand(4, 12);
            $kebutuhanMeja = (int) ceil($jmlOrg / 6); 

            $reservasiId = DB::table('reservasi')->insertGetId([
                'id_customer'    => $customers->random()->id,
                'id_pesanan'     => $pesananId,
                'tanggal'        => $resDate->format('Y-m-d'),
                'jam_mulai'      => $jamMulai->format('H:i'),
                'jam_selesai'    => $jamSelesai->format('H:i'),
                'jml_org'        => $jmlOrg,
                'keterangan'     => $faker->sentence(4),
                'created_at'     => $createdDate,
                'updated_at'     => $createdDate,
            ]);

            if ($isCompleted) {
                DB::table('notifikasi_wa')->insert([
                    [
                        'id_reservasi'     => $reservasiId,
                        'jenis_notifikasi' => 'dp_customer',
                        'status_kirim'     => 'sent',
                        'waktu_kirim'      => $createdDate,
                        'created_at'       => $createdDate,
                        'updated_at'       => $createdDate,
                    ],
                    [
                        'id_reservasi'     => $reservasiId,
                        'jenis_notifikasi' => 'dp_admin',
                        'status_kirim'     => 'sent',
                        'waktu_kirim'      => $createdDate,
                        'created_at'       => $createdDate,
                        'updated_at'       => $createdDate,
                    ],
                ]);
            }

            // Alokasi meja ke detail_reservasi jika Completed
            if ($isCompleted) {
                $assignedMejas = $mejas->random($kebutuhanMeja);
                foreach ($assignedMejas as $am) {
                    DB::table('detail_reservasi')->insert([
                        'id_meja'      => $am->id,
                        'id_reservasi' => $reservasiId,
                        'created_at'   => $createdDate,
                        'updated_at'   => $createdDate,
                    ]);
                }
            }

            // Pembayaran DP (Midtrans)
            $dpOptions = ['gopay', 'shopeepay', 'bca_va', 'other_qris'];
            $dpNominal = max(50000, round(rand(50000, $totalHarga) / 1000) * 1000);
            if ($dpNominal > $totalHarga) $dpNominal = $totalHarga;

            DB::table('pembayaran')->insert([
                'id_pesanan'        => $pesananId,
                'jenis'             => 'DP',
                'metode'            => $dpOptions[array_rand($dpOptions)],
                'nominal'           => $dpNominal,
                'snap_token'        => 'snap-dp-' . Str::random(10),
                'midtrans_order_id' => 'RESERV-' . $reservasiId . '-' . Str::random(5),
                'status'            => $isCompleted ? 'Success' : 'Failed',
                'created_at'        => $createdDate,
                'updated_at'        => $createdDate,
            ]);

            // Pembayaran Pelunasan
            if ($isCompleted && $dpNominal < $totalHarga) {
                $pelunasanOptions = ['tunai', 'qris', 'transfer_bank', 'debit'];
                $pelunasanMethod = $pelunasanOptions[array_rand($pelunasanOptions)];
                $sisa = $totalHarga - $dpNominal;
                
                $uangDiterima = null;
                $uangKembali = null;
                if ($pelunasanMethod === 'tunai') {
                    $uangDiterima = $sisa + (rand(0, 2) * 50000); // Simulasi uang lebih
                    $uangDiterima = round($uangDiterima / 1000) * 1000;
                    if ($uangDiterima < $sisa) $uangDiterima = $sisa;
                    $uangKembali = $uangDiterima - $sisa;
                }

                DB::table('pembayaran')->insert([
                    'id_pesanan'    => $pesananId,
                    'jenis'         => 'Pelunasan',
                    'metode'        => $pelunasanMethod,
                    'nominal'       => $sisa,
                    'uang_diterima' => $uangDiterima,
                    'uang_kembali'  => $uangKembali,
                    'status'        => 'Success',
                    'created_at'    => $resDate->copy()->setTime($jamSelesai->hour, $jamSelesai->minute),
                    'updated_at'    => $resDate->copy()->setTime($jamSelesai->hour, $jamSelesai->minute),
                ]);
            }
        }

        // =====================================================================
        // 3. DATA FUTURE RESERVASI (TEST KAPASITAS)
        // =====================================================================
        $this->command->info('Membuat Data Reservasi Test Kapasitas...');
        
        $dpOptions = ['gopay', 'shopeepay', 'bca_va', 'other_qris'];
        
        // --- SKENARIO A: 16 Desember 2026 (Total Orang < 88) ---
        $dateA = '2026-12-16';
        $jadwalA = [
            ['jam_mulai' => '15:00', 'jam_selesai' => '17:00', 'jml_org' => 15],
            ['jam_mulai' => '16:00', 'jam_selesai' => '18:00', 'jml_org' => 20],
            ['jam_mulai' => '16:30', 'jam_selesai' => '18:30', 'jml_org' => 10],
            // Total beririsan maksimal (jam 16:30 - 17:00): 15 + 20 + 10 = 45 orang (< 88)
        ];

        // --- SKENARIO B: 17 Desember 2026 (Total Orang = 88) ---
        $dateB = '2026-12-17';
        $jadwalB = [
            ['jam_mulai' => '19:00', 'jam_selesai' => '21:00', 'jml_org' => 30],
            ['jam_mulai' => '19:30', 'jam_selesai' => '21:30', 'jml_org' => 40],
            ['jam_mulai' => '19:45', 'jam_selesai' => '22:00', 'jml_org' => 18],
            // Total beririsan maksimal (jam 19:45 - 21:00): 30 + 40 + 18 = 88 orang (== 88)
        ];

        $semuaJadwal = array_merge(
            array_map(fn($j) => array_merge($j, ['tanggal' => $dateA]), $jadwalA),
            array_map(fn($j) => array_merge($j, ['tanggal' => $dateB]), $jadwalB)
        );

        $customerTerpakai = [];

        foreach ($semuaJadwal as $idx => $jadwal) {
            $createdDate = Carbon::now()->subDays(rand(1, 3));
            $kode = 'RSV-' . $createdDate->format('md') . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            
            // Generate items untuk mencapai min 50k
            $totalHarga = 0;
            $items = [];
            
            // Loop sampai total menu >= 50.000
            while ($totalHarga < 50000) {
                $menu = $menus->random();
                $qty = rand(1, 3);
                $sub = $menu->harga * $qty;
                $totalHarga += $sub;
                $items[] = [
                    'id_menu'    => $menu->id,
                    'jumlah'     => $qty,
                    'subtotal'   => $sub,
                    'catatan'    => 'Test Kapasitas',
                    'created_at' => $createdDate,
                    'updated_at' => $createdDate,
                ];
            }

            // Pastikan customer unik untuk tiap reservasi pending/confirmed
            $customerId = $customers->reject(fn($c) => in_array($c->id, $customerTerpakai))->random()->id;
            $customerTerpakai[] = $customerId;

            $pesananId = DB::table('pesanan')->insertGetId([
                'kode_pesanan' => $kode,
                'id_user'      => 2, // Kasir/Admin
                'id_meja'      => null, // Alokasi dikosongkan
                'total_harga'  => $totalHarga,
                'status'       => 'Confirmed', // Status Confirmed
                'created_at'   => $createdDate,
                'updated_at'   => $createdDate,
                'completed_at' => null,
            ]);

            foreach ($items as &$it) { $it['id_pesanan'] = $pesananId; }
            DB::table('detail_pesanan')->insert($items);

            $reservasiId = DB::table('reservasi')->insertGetId([
                'id_customer'    => $customerId,
                'id_pesanan'     => $pesananId,
                'tanggal'        => $jadwal['tanggal'],
                'jam_mulai'      => $jadwal['jam_mulai'],
                'jam_selesai'    => $jadwal['jam_selesai'],
                'jml_org'        => $jadwal['jml_org'],
                'keterangan'     => 'Test kapasitas rombongan ' . ($idx + 1),
                'created_at'     => $createdDate,
                'updated_at'     => $createdDate,
            ]);

            DB::table('notifikasi_wa')->insert([
                [
                    'id_reservasi'     => $reservasiId,
                    'jenis_notifikasi' => 'dp_customer',
                    'status_kirim'     => 'sent',
                    'waktu_kirim'      => $createdDate,
                    'created_at'       => $createdDate,
                    'updated_at'       => $createdDate,
                ],
                [
                    'id_reservasi'     => $reservasiId,
                    'jenis_notifikasi' => 'dp_admin',
                    'status_kirim'     => 'sent',
                    'waktu_kirim'      => $createdDate,
                    'created_at'       => $createdDate,
                    'updated_at'       => $createdDate,
                ],
            ]);

            // DP dibayar sukses (Nominal >= 50.000, <= Total Harga)
            $minDp = 50000;
            $dpNominal = max($minDp, round(rand($minDp, $totalHarga) / 1000) * 1000);
            if ($dpNominal > $totalHarga) $dpNominal = $totalHarga;

            DB::table('pembayaran')->insert([
                'id_pesanan'        => $pesananId,
                'jenis'             => 'DP',
                'metode'            => $dpOptions[array_rand($dpOptions)],
                'nominal'           => $dpNominal,
                'snap_token'        => 'snap-dp-' . Str::random(10),
                'midtrans_order_id' => 'RESERV-' . $reservasiId . '-' . Str::random(5),
                'status'            => 'Success', // Pembayaran berhasil
                'uang_diterima'     => null,
                'uang_kembali'      => null,
                'created_at'        => $createdDate,
                'updated_at'        => $createdDate,
            ]);
        }

        $this->command->info('Seeding Transaksi Berhasil!');
    }
}