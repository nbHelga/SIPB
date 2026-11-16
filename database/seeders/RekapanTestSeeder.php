<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RekapanTestSeeder extends Seeder
{
    public function run(): void
    {
        $menus = DB::table('menu')->where('status', 'open')->get();
        if ($menus->isEmpty()) {
            $this->command->error('Jalankan MenuSeeder terlebih dahulu.');
            return;
        }

        $userId = DB::table('users')->value('id');
        $tableId = DB::table('meja')->value('id');
        $day = Carbon::today();
        $prefix = 'REKAP-TEST-' . $day->format('Ymd') . '-';

        // Idempoten: menjalankan seeder ulang mengganti 20 transaksi test hari ini.
        $oldIds = DB::table('pesanan')->where('kode_pesanan', 'like', $prefix . '%')->pluck('id');
        if ($oldIds->isNotEmpty()) {
            DB::table('pesanan')->whereIn('id', $oldIds)->delete();
        }

        $menuCount = $menus->count();
        for ($i = 1; $i <= 20; $i++) {
            $createdAt = $day->copy()->setTime(9 + intdiv($i, 3), ($i * 7) % 60, 0);
            $orderId = DB::table('pesanan')->insertGetId([
                'kode_pesanan' => $prefix . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'id_user' => $userId,
                'id_meja' => $tableId,
                'total_harga' => 0,
                'status' => 'Completed',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'completed_at' => $createdAt->copy()->addMinutes(35),
            ]);

            $total = 0;
            $lineCount = 2 + ($i % 3);
            for ($line = 0; $line < $lineCount; $line++) {
                $menu = $menus[($i + ($line * 2)) % $menuCount];
                $quantity = 1 + (($i + $line) % 3);
                $subtotal = (int) $menu->harga * $quantity;
                $total += $subtotal;
                DB::table('detail_pesanan')->insert([
                    'id_pesanan' => $orderId,
                    'id_menu' => $menu->id,
                    'jumlah' => $quantity,
                    'subtotal' => $subtotal,
                    'catatan' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            DB::table('pesanan')->where('id', $orderId)->update(['total_harga' => $total]);
            DB::table('pembayaran')->insert([
                'id_pesanan' => $orderId,
                'jenis' => 'Full',
                'metode' => $i % 2 === 0 ? 'tunai' : 'other_qris',
                'nominal' => $total,
                'uang_diterima' => $i % 2 === 0 ? $total + 10000 : null,
                'uang_kembali' => $i % 2 === 0 ? 10000 : null,
                'snap_token' => $i % 2 === 0 ? null : 'rekap-test-snap-' . $i,
                'midtrans_order_id' => $i % 2 === 0 ? null : 'REKAP-TEST-MT-' . $day->format('Ymd') . '-' . $i,
                'status' => 'Success',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('20 transaksi test rekapan dibuat untuk ' . $day->toDateString() . '.');
    }
}
