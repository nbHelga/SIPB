<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Meja;
use App\Models\Reservasi;
use App\Models\DetailReservasi;
use App\Models\Pesanan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use Carbon\Carbon;

class ReservasiTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil atau Buat Data Customer
        $customer = Customer::firstOrCreate(
            ['nomor_telp' => '081233355772'],
            ['nama' => 'Budi Santoso']
        );

        // 2. Ambil Meja yang Tersedia
        $meja = Meja::first() ?? Meja::create([
            'nomor_meja' => 'Mtest',
            'lantai' => 1,
            'status' => 'available'
        ]);

        // 3. Tentukan Waktu Kedatangan (Besok Jam 22:50 WIB)
        $besok = Carbon::tomorrow(); // 5 Agustus 2026
        $jamMulai = '22:50:00';
        $jamSelesai = '23:50:00';

        // 4. Buat Record Reservasi (Status: Confirmed & DP 50rb)
        $reservasi = Reservasi::create([
            'id_customer' => $customer->id,
            'tanggal' => $besok->toDateString(),
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'jml_org' => 3,
            'keterangan' => 'Reservasi area non-smoking dekat jendela',
            'dp_bayar' => 50000,
            'status' => 'Confirmed',
            'status_notif' => true,
        ]);

        // 5. Mapping Meja ke Detail Reservasi
        DetailReservasi::create([
            'id_reservasi' => $reservasi->id,
            'id_meja' => $meja->id,
        ]);

        // 6. Buat Record Invoice Pesanan (Relasi 1-to-1 dengan Reservasi)
        $pesanan = Pesanan::create([
            'id_user' => null,
            'nomor_meja' => $meja->id,
            'id_reservasi' => $reservasi->id,
            // 'tanggal_pesanan' => now(),
            'total_harga' => 0, // Akan dihitung dari detail pesanan
            'nama_customer' => $customer->nama,
            'status' => 'Reserved',
            'metode_pembayaran' => 'Midtrans',
            'catatan' => 'Pre-order makanan untuk reservasi tempat',
        ]);

        // 7. Masukkan Sample Menu Pre-Order (Opsional)
        $menuSample = Menu::first();
        if ($menuSample) {
            $qty = 2;
            $subtotal = $menuSample->harga * $qty;

            DetailPesanan::create([
                'id_pesanan' => $pesanan->id,
                'id_menu' => $menuSample->id,
                'jumlah' => $qty,
                'subtotal' => $subtotal,
                'catatan' => 'Sedikit pedas',
            ]);

            // Update Total Belanja Pesanan
            $pesanan->update(['total_harga' => $subtotal]);
        }

        // Update status meja menjadi reserved
        $meja->update(['status' => 'reserved']);
    }
}