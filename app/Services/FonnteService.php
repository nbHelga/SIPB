<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    public function send(?string $phone, string $message): bool
    {
        if (!$phone || !env('FONNTE_API_KEY')) {
            return false;
        }

        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        try {
            return Http::withHeaders([
                'Authorization' => trim(env('FONNTE_API_KEY')),
            ])->asForm()->post(
                env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
                ['target' => $phone, 'message' => $message]
            )->successful();
        } catch (\Throwable $e) {
            Log::warning('Pengiriman WhatsApp melalui Fonnte gagal.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}