<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Reservasi;
use App\Models\Pesanan;
use App\Models\DetailReservasi;
use App\Models\DetailPesanan;
use Carbon\Carbon;
class RestoranSkenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // 1. Ambil data master pendukung yang sudah ada (Master Data)
        $customerIds = DB::table('customer')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();
        $mejaIds = DB::table('meja')->pluck('id')->toArray();
        
        // Ambil 24 data menu yang sudah ter-seed sebelumnya
        $menus = DB::table('menu')->get();

        if ($menus->count() < 3 || empty($customerIds) || empty($mejaIds)) {
            $this->command->error('Pastikan Seeder Customer, Meja, dan 24 Menu sudah dijalankan terlebih dahulu!');
            return;
        }

        // =========================================================================
        // BAGIAN I: GENERATE 5 RESERVASI (Setiap reservasi punya 1 data pesanan terikat)
        // =========================================================================
        for ($i = 1; $i <= 5; $i++) {
            // Pilih acak customer dan meja
            $randomCustomerId = $customerIds[array_rand($customerIds)];
            $randomMejaId = $mejaIds[array_rand($mejaIds)];

            // A. Buat Data Reservasi
            $reservasi = Reservasi::create([
                'id_customer'       => $randomCustomerId,
                'tanggal'           => Carbon::now()->addDays($i)->toDateString(),
                'jam_mulai'         => '17:00:00',
                'jam_selesai'       => '19:00:00',
                'jml_org'           => rand(2, 6),
                'keterangan'        => "Booking Reservasi ke-$i via Aplikasi",
                'dp_bayar'          => 100000.00,
                'status'            => 'Confirmed',
                'status_notif'      => true,
                'metode_pembayaran' => 'Transfer Bank',
            ]);

            // B. Plotting Meja Langsung ke Detail Reservasi
            DetailReservasi::create([
                'id_meja'      => $randomMejaId,
                'id_reservasi' => $reservasi->id,
            ]);

            // C. Buat 1 Data Pesanan Terikat (Relasi 1-to-1 via FK id_reservasi di Pesanan)
            $pesananReservasi = Pesanan::create([
                'id_user'           => !empty($userIds) ? $userIds[array_rand($userIds)] : null,
                'nomor_meja'        => $randomMejaId,
                'id_reservasi'      => $reservasi->id, // Mengunci relasi ke reservasi
                // 'tanggal_pesanan'   => Carbon::now(),
                'total_harga'       => 0, // Akan dihitung otomatis dari subtotal detail_pesanan
                'nama_customer'     => 'Pelanggan Reservasi ' . $i,
                'status'    => 'Reserved',
                'metode_pembayaran' => 'Transfer Bank',
                'catatan'           => 'Pesanan pre-order via booking tempat.',
            ]);

            // D. Isi minimal 3 Menu Acak ke Detail Pesanan untuk Jalur Reservasi
            $randomMenus = $menus->random(rand(3, 5)); 
            $totalHargaPesanan = 0;

            foreach ($randomMenus as $menu) {
                $jumlah = rand(1, 3);
                $subtotal = $menu->harga * $jumlah;
                $totalHargaPesanan += $subtotal;

                DetailPesanan::create([
                    'id_pesanan'     => $pesananReservasi->id, // Primary Key 'id' dari tabel pesanan Anda
                    'id_menu'        => $menu->id,
                    'jumlah'         => $jumlah,
                    'subtotal'       => $subtotal,
                    'catatan' => rand(0, 1) ? 'Jangan terlalu pedas' : null,
                ]);
            }

            // Update kembali total harga pesanan setelah item detail selesai dihitung
            $pesananReservasi->update(['total_harga' => $totalHargaPesanan]);
        }


        // =========================================================================
        // BAGIAN II: GENERATE 10 PESANAN SELF-ORDER / WALK-IN (Tanpa Reservasi)
        // =========================================================================
        for ($j = 1; $j <= 10; $j++) {
            $randomMejaId = $mejaIds[array_rand($mejaIds)];

            // A. Buat Data Pesanan Walk-in (id_reservasi bernilai null)
            $pesananSelfOrder = Pesanan::create([
                'id_user'           => null, // Pelanggan self-order tidak wajib login
                'nomor_meja'        => $randomMejaId,
                'id_reservasi'      => null, // NULL karena murni self-order di tempat
                // 'tanggal_pesanan'   => Carbon::now(),
                'total_harga'       => 0, // Dihitung dinamis
                'nama_customer'     => 'Pelanggan Meja ' . $j,
                'status'    => 'Paid',
                'metode_pembayaran' => 'Qris',
                'catatan'           => "Self-order mandiri lewat QR Meja ke-$j.",
            ]);

            // B. Isi minimal 3 Menu Acak ke Detail Pesanan untuk Jalur Self-Order
            $randomMenusSelf = $menus->random(rand(3, 6));
            $totalHargaSelf = 0;

            foreach ($randomMenusSelf as $menu) {
                $jumlah = rand(1, 2);
                $subtotal = $menu->harga * $jumlah;
                $totalHargaSelf += $subtotal;

                DetailPesanan::create([
                    'id_pesanan'     => $pesananSelfOrder->id,
                    'id_menu'        => $menu->id,
                    'jumlah'         => $jumlah,
                    'subtotal'       => $subtotal,
                    'catatan' => null,
                ]);
            }

            // Update kembali total harga pesanan self-order
            $pesananSelfOrder->update(['total_harga' => $totalHargaSelf]);
        }
    }
}
