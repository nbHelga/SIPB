<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

class PendingSelfOrderExpiryService
{
    public function expireDue(): void
    {
        $expiryMinutes = $this->expiryMinutes();
        $cutoff = $this->cutoff($expiryMinutes);

        Pesanan::query()
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('reservasi')
            ->chunkById(100, function ($orders) use ($expiryMinutes) {
                foreach ($orders as $order) {
                    /** @var Pesanan $order */
                    $this->expireDueOrder($order, $expiryMinutes);
                }
            });
    }

    public function expireIfDue(Pesanan $order): void
    {
        $this->expireDueOrder($order, $this->expiryMinutes());
    }

    private function expireDueOrder(Pesanan $order, int $expiryMinutes): void
    {
        $cutoff = $this->cutoff($expiryMinutes);
        $isDue = Pesanan::query()
            ->whereKey($order->id)
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('reservasi')
            ->exists();

        if (!$isDue) {
            return;
        }

        $pendingPayments = $order->pembayaran()
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->whereNotNull('midtrans_order_id')
            ->get();

        if ($pendingPayments->isNotEmpty()) {
            $this->configureMidtrans();
        }

        foreach ($pendingPayments as $payment) {
            try {
                $remote = Transaction::status($payment->midtrans_order_id);
                $status = strtolower((string) data_get($remote, 'transaction_status'));

                if ($this->isSuccessful($remote)) {
                    $this->markPaid($order->id, $payment->id, $remote);
                    return;
                }

                if (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => MidtransPaymentMethod::from($remote) ?? $payment->metode,
                    ]);
                    continue;
                }

                Transaction::cancel($payment->midtrans_order_id);
                $verified = Transaction::status($payment->midtrans_order_id);

                if ($this->isSuccessful($verified)) {
                    $this->markPaid($order->id, $payment->id, $verified);
                    return;
                }

                $verifiedStatus = strtolower((string) data_get($verified, 'transaction_status'));
                if (!in_array($verifiedStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    Log::warning('Self-order belum dapat dibatalkan di Midtrans; akan dicoba lagi.', [
                        'pesanan_id' => $order->id,
                        'payment_id' => $payment->id,
                        'midtrans_order_id' => $payment->midtrans_order_id,
                        'status' => $verifiedStatus,
                    ]);
                    return;
                }

                $payment->update([
                    'status' => 'Failed',
                    'metode' => MidtransPaymentMethod::from($verified) ?? $payment->metode,
                ]);
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    Log::warning('Gagal memeriksa/membatalkan pembayaran self-order; akan dicoba ulang.', [
                        'pesanan_id' => $order->id,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                    return;
                }
            }
        }

        DB::transaction(function () use ($order, $cutoff) {
            $lockedOrder = Pesanan::query()
                ->whereKey($order->id)
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->where('created_at', '<=', $cutoff)
                ->whereDoesntHave('reservasi')
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                return;
            }

            $hasSuccess = $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'success'")
                ->exists();

            if ($hasSuccess) {
                $lockedOrder->update(['status' => 'Paid']);
                return;
            }

            $lockedOrder->update(['status' => 'Cancelled']);
            $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->update(['status' => 'Failed']);
        }, 3);
    }

    private function expiryMinutes(): int
    {
        return max(1, (int) config('services.selforder.payment_window_minutes', 120));
    }

    private function cutoff(int $expiryMinutes)
    {
        return now('UTC')->subMinutes($expiryMinutes);
    }

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    private function markPaid(int $orderId, int $paymentId, object|array $remote): void
    {
        DB::transaction(function () use ($orderId, $paymentId, $remote) {
            $order = Pesanan::whereKey($orderId)->lockForUpdate()->first();
            $payment = Pembayaran::whereKey($paymentId)->lockForUpdate()->first();

            if (!$order || !$payment) {
                return;
            }

            $payment->update([
                'status' => 'Success',
                'metode' => MidtransPaymentMethod::from($remote) ?? $payment->metode,
            ]);

            if (strtolower(trim((string) $order->status)) === 'pending') {
                $order->update(['status' => 'Paid']);
            }
        }, 3);
    }

    private function isSuccessful(object|array $remote): bool
    {
        $status = strtolower((string) data_get($remote, 'transaction_status'));
        $fraud = strtolower((string) data_get($remote, 'fraud_status'));

        return $status === 'settlement'
            || ($status === 'capture' && $fraud === 'accept');
    }

    private function isNotFound(\Throwable $e): bool
    {
        return (int) $e->getCode() === 404
            || str_contains(strtolower($e->getMessage()), "transaction doesn't exist")
            || str_contains(strtolower($e->getMessage()), 'transaction not found');
    }
}
