<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetailPesanan;
use App\Models\Pesanan;
use App\Models\Menu; 
// use Illuminate\Support\Facades\DB;

class DetailPesananSeeder extends Seeder
{
    public function run(): void
    {
        // Diasumsikan menu ID 1 - 24 sudah ada dari seeder Anda
        $menus = Menu::all();
        if ($menus->isEmpty()) {
            return;
        }

        $pesanans = Pesanan::with('reservasi')->get();

        foreach ($pesanans as $pesanan) {
            $totalHarga = 0;

            // Tentukan minimal qty pesanan berdasarkan jumlah orang jika ini reservasi
            if ($pesanan->id_reservasi && $pesanan->reservasi) {
                $minQty = $pesanan->reservasi->jml_org;
            } else {
                $minQty = rand(1, 4); // Self Order bawaan
            }

            $currentQtyCount = 0;

            // Loop menambah detail pesanan sampai batas minQty terpenuhi
            while ($currentQtyCount < $minQty) {
                $menu = $menus->random();
                $qty = rand(1, 3);
                $subtotal = $menu->harga * $qty;

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id,
                    'id_menu' => $menu->id,
                    'jumlah' => $qty,
                    'subtotal' => $subtotal,
                    'catatan' => rand(0, 1) ? 'Pedas / Normal' : null,
                ]);

                $totalHarga += $subtotal;
                $currentQtyCount += $qty;
            }

            // Tambahkan Pajak PB1 10% dan Update Total di tabel Pesanan
            // $pb1 = round($totalHarga * 0.1);
            $pesanan->update([
                // 'total_harga' => $totalHarga + $pb1
                'total_harga' => $totalHarga
            ]);
        }
    }
}
