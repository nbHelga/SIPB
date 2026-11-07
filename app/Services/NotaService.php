<?php
// filepath: c:\laragon2\www\SIPB\app\Services\NotaService.php

namespace App\Services;

// use App\Models\NotaPrintLog;
use App\Models\Pesanan;
use App\Models\Reservasi;
// use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaService
{
    public function render(Pesanan $pesanan, string $jenis, ?Reservasi $reservasi = null)
    {
        $bolehCetak = match ($jenis) {
            'self_customer' => strtolower($pesanan->status) === 'processing',
            'self_kitchen' => strtolower($pesanan->status) === 'processing',
            'reservation_customer' => strtolower($pesanan->status) === 'completed',
            'reservation_kitchen' => strtolower($pesanan->status) === 'confirmed',
            'reservation_dp' => strtolower($pesanan->status) === 'confirmed',
            default => false,
        };

        if (!$bolehCetak) {
            throw ValidationException::withMessages([
                'nota' => 'Nota belum dapat dicetak untuk status pesanan ini.',
            ]);
        }
        $pesanan->loadMissing(['meja', 'user', 'detailPesanan.menu']);

        if ($reservasi) {
            $reservasi->loadMissing(['detailReservasi.meja', 'customer']);
        }

        return view('admin.nota', compact('pesanan', 'reservasi', 'jenis'));
    }
}