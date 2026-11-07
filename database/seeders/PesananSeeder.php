<?php

namespace Database\Seeders;

use App\Models\Meja;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PesananSeeder extends Seeder
{
    private int $sequence = 1;

    public function run(): void
    {
        $mejaIds = Meja::query()->pluck('id');

        if ($mejaIds->isEmpty()) {
            throw new \RuntimeException('PesananSeeder memerlukan data meja. Jalankan MejaSeeder terlebih dahulu.');
        }

        // Pesanan self-order historis H-90 sampai kemarin.
        for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
            $date = Carbon::today()->subDays($daysAgo);

            for ($i = 0, $dailyCount = random_int(5, 12); $i < $dailyCount; $i++) {
                $time = $date->copy()->setTime(
                    random_int(15, 23),
                    random_int(0, 59)
                );

                $isCompleted = random_int(1, 100) <= 95;
                $status = $isCompleted ? 'Completed' : 'Cancelled';
                $method = random_int(0, 1) === 1 ? 'midtrans' : 'tunai';

                $this->createSelfOrder([
                    'id_meja' => $mejaIds->random(),
                    'total_harga' => random_int(5, 25) * 10000,
                    'status' => $status,
                    'metode' => $method,
                    'created_at' => $time,
                    'updated_at' => $time,
                    'completed_at' => $isCompleted ? $time : null,
                ]);
            }
        }

        $today = Carbon::today();

        // Skenario hari ini: Paid.
        $this->createSelfOrder([
            'id_meja' => $mejaIds->random(),
            'total_harga' => 85000,
            'status' => 'Paid',
            'metode' => random_int(0, 1) ? 'midtrans' : 'tunai',
            'created_at' => $today->copy()->setTime(15, 15),
            'updated_at' => $today->copy()->setTime(15, 15),
        ]);

        // Skenario hari ini: Processing.
        $this->createSelfOrder([
            'id_meja' => $mejaIds->random(),
            'total_harga' => 120000,
            'status' => 'Processing',
            'metode' => random_int(0, 1) ? 'midtrans' : 'tunai',
            'created_at' => $today->copy()->setTime(16, 0),
            'updated_at' => $today->copy()->setTime(16, 5),
        ]);

        // Skenario hari ini: Completed.
        $completedAt = $today->copy()->setTime(18, 0);
        $this->createSelfOrder([
            'id_meja' => $mejaIds->random(),
            'total_harga' => 150000,
            'status' => 'Completed',
            'metode' => random_int(0, 1) ? 'midtrans' : 'tunai',
            'created_at' => $today->copy()->setTime(17, 30),
            'updated_at' => $completedAt,
            'completed_at' => $completedAt,
        ]);

        // Skenario hari ini: Cancelled.
        $this->createSelfOrder([
            'id_meja' => $mejaIds->random(),
            'total_harga' => 45000,
            'status' => 'Cancelled',
            'metode' => random_int(0, 1) ? 'midtrans' : 'tunai',
            'created_at' => $today->copy()->setTime(18, 10),
            'updated_at' => $today->copy()->setTime(18, 15),
        ]);

        // Skenario untuk tombol "Tandai Sudah Dibayar".
        $this->createSelfOrder([
            'id_meja' => $mejaIds->random(),
            'total_harga' => 65000,
            'status' => 'Pending',
            'metode' => 'tunai',
            'created_at' => $today->copy()->setTime(19, 0),
            'updated_at' => $today->copy()->setTime(19, 0),
        ]);
    }

    private function createSelfOrder(array $data): Pesanan
    {
        $totalHarga = (float) $data['total_harga'];
        $status = strtolower((string) $data['status']);
        $metode = strtolower((string) $data['metode']);

        $statusPembayaran = match ($status) {
            'paid', 'processing', 'completed' => 'Success',
            'cancelled' => 'Failed',
            default => 'Pending',
        };

        $createdAt = $data['created_at'];
        $updatedAt = $data['updated_at'] ?? $createdAt;

        return DB::transaction(function () use (
            $data,
            $totalHarga,
            $status,
            $metode,
            $statusPembayaran,
            $createdAt,
            $updatedAt
        ) {
            $pesanan = Pesanan::create([
                'kode_pesanan' => $this->generateCode($createdAt),
                'id_user' => null,
                'id_meja' => $data['id_meja'],
                'total_harga' => $totalHarga,
                'status' => ucfirst($status),
                'completed_at' => $data['completed_at'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            Pembayaran::create([
                'id_pesanan' => $pesanan->id,
                'jenis' => 'Full',
                'metode' => $metode,
                // Pembayaran full self-order harus selalu sama dengan total pesanan.
                'nominal' => $totalHarga,
                'status' => $statusPembayaran,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            return $pesanan;
        });
    }

    private function generateCode(Carbon $time): string
    {
        do {
            $code = 'SOD-'
                . $time->format('mdHi')
                . '-'
                . sprintf('%04d', $this->sequence++)
                . '-'
                . random_int(1000, 9999);
        } while (Pesanan::where('kode_pesanan', $code)->exists());

        return $code;
    }
}