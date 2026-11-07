<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Pesanan;
use App\Models\Reservasi;
use App\Models\DetailPesanan;
use App\Models\DetailReservasi;
use App\Models\Menu;
use App\Models\Meja;
use App\Models\Pembayaran;
use Carbon\Carbon;

class ReservasiSeeder extends Seeder
{
    private function createSeedReservation(
        Customer $customer,
        array $reservationData,
        array $menuLines,
        int $dpNominal = 50000
    ): array {
        $total = collect($menuLines)->sum(
            fn ($line) => (float) $line['menu']->harga * (int) $line['jumlah']
        );

        while ($total < 50000) {
            $menu = Menu::query()->inRandomOrder()->firstOrFail();
            $jumlah = 1;
            $menuLines[] = ['menu' => $menu, 'jumlah' => $jumlah];
            $total += (float) $menu->harga;
        }

        $dpNominal = min($total, max(50000, $dpNominal));
        $status = $reservationData['status'] ?? 'Reserved';
        $completedAt = strtolower($status) === 'completed'
            ? ($reservationData['completed_at'] ?? now())
            : null;

        $createdAt = $reservationData['created_at'] ?? now();
        $updatedAt = $reservationData['updated_at'] ?? $createdAt;

        return DB::transaction(function () use (
            $customer,
            $reservationData,
            $menuLines,
            $total,
            $dpNominal,
            $status,
            $completedAt,
            $createdAt,
            $updatedAt
        ) {
            $pesanan = Pesanan::create([
                'kode_pesanan' => 'RSV-' . now()->format('mdHi') . '-' . Str::upper(Str::random(8)),
                'id_user' => $reservationData['id_user'] ?? 1,
                'id_meja' => null,
                'total_harga' => $total,
                'status' => $status,
                'completed_at' => $completedAt,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            $reservasi = Reservasi::create([
                'id' => $reservationData['id'] ?? null,
                'id_customer' => $customer->id,
                'id_pesanan' => $pesanan->id,
                'tanggal' => $reservationData['tanggal'],
                'jam_mulai' => $reservationData['jam_mulai'],
                'jam_selesai' => $reservationData['jam_selesai'],
                'jml_org' => $reservationData['jml_org'],
                'keterangan' => $reservationData['keterangan'] ?? null,
                'admin_notif' => $reservationData['admin_notif'] ?? false,
                'customer_notif' => $reservationData['customer_notif'] ?? false,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            foreach ($menuLines as $line) {
                $menu = $line['menu'];
                $jumlah = (int) $line['jumlah'];

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id,
                    'id_menu' => $menu->id,
                    'jumlah' => $jumlah,
                    'subtotal' => (float) $menu->harga * $jumlah,
                    'catatan' => $line['catatan'] ?? null,
                ]);
            }

            Pembayaran::create([
                'id_pesanan' => $pesanan->id,
                'jenis' => 'DP',
                'metode' => 'midtrans',
                'nominal' => $dpNominal,
                'status' => 'Success',
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            if (strtolower($status) === 'completed' && $total > $dpNominal) {
                Pembayaran::create([
                    'id_pesanan' => $pesanan->id,
                    'jenis' => 'Pelunasan',
                    'metode' => $reservationData['metode_pelunasan'] ?? 'tunai',
                    'nominal' => $total - $dpNominal,
                    'status' => 'Success',
                    'created_at' => $completedAt,
                    'updated_at' => $completedAt,
                ]);
            }

            return [$reservasi, $pesanan, $total];
        });
    }
    public function run(): void
    {
        $customers = Customer::all();
        $menus = Menu::all();
        $mejas = Meja::all();

        if ($customers->isEmpty() || $menus->isEmpty()) {
            return;
        }

        // -------------------------------------------------------------
        // A. DATA HISTORIS UNTUK DASHBOARD ANALYTICS (3 Bulan Terakhir)
        // -------------------------------------------------------------
        for ($i = 1; $i <= 20; $i++) {
            $date = Carbon::today()->subDays(rand(2, 90));
            $createdAt = $date->copy()->setTime(rand(9, 12), 0);
            
            // Jam operasional 15:00 - 00:00 (Mulai jam 15 s/d 21)
            $startHour = rand(15, 21);
            $duration = rand(1, 2);
            $customer = $customers->random();
            $jmlOrg = rand(2, 6);
            $menuLines = [];
            $estimatedTotal = 0;

            while ($estimatedTotal < 50000) {
                $menu = $menus->random();
                $qty = rand(1, 2);
                $menuLines[] = ['menu' => $menu, 'jumlah' => $qty];
                $estimatedTotal += (float) $menu->harga * $qty;
            }

            [$reservasi, $pesanan] = $this->createSeedReservation(
                $customer,
                [
                    'id' => 1000 + $i,
                    'tanggal' => $date->format('Y-m-d'),
                    'jam_mulai' => sprintf('%02d:00:00', $startHour),
                    'jam_selesai' => sprintf('%02d:00:00', min(23, $startHour + $duration)),
                    'jml_org' => rand(2, 6),
                    'keterangan' => 'Reservasi Historis',
                    'status' => 'Completed',
                    'completed_at' => $date->copy()->setTime(23, 0),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'metode_pelunasan' => ['tunai', 'qris', 'debit', 'transfer_bank'][array_rand(['tunai', 'qris', 'debit', 'transfer_bank'])],
                ],
                $menuLines
            );

            if ($mejas->isNotEmpty()) {
                DetailReservasi::create([
                    'id_reservasi' => $reservasi->id,
                    'id_meja' => $mejas->random()->id,
                ]);
            }
        }

        // -------------------------------------------------------------
        // B. SKENARIO KHUSUS ASSIGN MEJA & BENTROK A s/d K
        // Jam operasional: 15:00 - 00:00 | Beda Customer Setiap Skenario
        // -------------------------------------------------------------
        $today = Carbon::createFromDate(2026, 12, 1)->format('Y-m-d');    // Hasil: '2026-12-01'
        $tomorrow = Carbon::createFromDate(2026, 12, 2)->format('Y-m-d'); // Hasil: '2026-12-02'

        $skenarios = [
            // SKENARIO 1: Bentrok Sempurna (Jam Beririsan < 2 Jam, Hari Ini)
            [
                'id' => 1, 'code' => 'A', 'tanggal' => $today, 
                'jam_mulai' => '15:00:00', 'jam_selesai' => '17:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [2, 3] // Alokasi Meja ID #2 dan #3
            ],
            [
                'id' => 2, 'code' => 'B', 'tanggal' => $today, 
                'jam_mulai' => '15:30:00', 'jam_selesai' => '17:30:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [] // Belum dialokasi (Testing bentrok merah di Meja #2 & #3)
            ],

            // SKENARIO 2: Waktu Aman (Jeda > 2 Jam pada Meja Sama, Hari Ini)
            [
                'id' => 3, 'code' => 'C', 'tanggal' => $today, 
                'jam_mulai' => '15:00:00', 'jam_selesai' => '17:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [5]
            ],
            [
                'id' => 4, 'code' => 'D', 'tanggal' => $today, 
                'jam_mulai' => '20:00:00', 'jam_selesai' => '22:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [] // Boleh dipilih Meja #5 (Aman)
            ],

            // SKENARIO 3: Batas Toleransi 120 Menit (Hari Ini)
            [
                'id' => 5, 'code' => 'E', 'tanggal' => $today, 
                'jam_mulai' => '16:00:00', 'jam_selesai' => '18:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [8]
            ],
            [
                'id' => 6, 'code' => 'F', 'tanggal' => $today, 
                'jam_mulai' => '18:00:00', 'jam_selesai' => '20:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [] // Pas 120 menit -> Aman
            ],
            [
                'id' => 7, 'code' => 'G', 'tanggal' => $today, 
                'jam_mulai' => '17:59:00', 'jam_selesai' => '19:59:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [] // 119 menit -> Bentrok Merah
            ],

            // SKENARIO 4: Multi-Lantai, Beda Tanggal & Isolasi Konflik
            [
                'id' => 8, 'code' => 'H', 'tanggal' => $today, 
                'jam_mulai' => '19:00:00', 'jam_selesai' => '21:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [1, 2] // Lt 1
            ],
            [
                'id' => 9, 'code' => 'I', 'tanggal' => $today, 
                'jam_mulai' => '19:00:00', 'jam_selesai' => '21:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [17, 18] // Lt 2 (Jam sama tapi beda meja -> Aman)
            ],
            [
                'id' => 10, 'code' => 'J', 'tanggal' => $tomorrow, 
                'jam_mulai' => '19:00:00', 'jam_selesai' => '21:00:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [1] // Beda Tanggal -> Aman
            ],
            [
                'id' => 11, 'code' => 'K', 'tanggal' => $today, 
                'jam_mulai' => '19:30:00', 'jam_selesai' => '21:30:00', 
                'jml_org' => 4, 'dp_bayar' => 50000, 'status' => 'Confirmed',
                'plot_meja' => [] // Testing plot Meja #1 & #2 -> Bentrok Merah dari H
            ],
        ];

        foreach ($skenarios as $index => $s) {
            $cust = $customers[$index % $customers->count()];
            $menu = $menus->random();
            $qty = min(100, $s['jml_org']);

            [$reservasi, $pesanan] = $this->createSeedReservation(
                $cust,
                [
                    'id' => $s['id'],
                    'tanggal' => $s['tanggal'],
                    'jam_mulai' => $s['jam_mulai'],
                    'jam_selesai' => $s['jam_selesai'],
                    'jml_org' => $s['jml_org'],
                    'keterangan' => 'Testing Skenario ' . $s['code'],
                    'status' => $s['status'],
                    'admin_notif' => false,
                ],
                [[
                    'menu' => $menu,
                    'jumlah' => $qty,
                    'catatan' => 'Pre-order Skenario ' . $s['code'],
                ]]
            );
        }
    }
}