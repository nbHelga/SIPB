<?php
// filepath: c:\laragon2\www\SIPB\app\Services\MidtransPaymentMethod.php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

class MidtransPaymentMethod
{
    public static function from(object|array $transaction): ?string
    {
        $type = strtolower(trim((string) data_get(
            $transaction,
            'payment_type',
            ''
        )));

        return match ($type) {
            'gopay' => 'gopay',
            'shopeepay' => 'shopeepay',
            'qris' => 'other_qris',
            'bank_transfer' => self::bankTransferMethod($transaction),
            'echannel' => 'mandiri_bill',
            '' => null,
            default => $type,
        };
    }

    private static function bankTransferMethod(object|array $transaction): string
    {
        $bank = strtolower((string) data_get(
            $transaction,
            'va_numbers.0.bank',
            ''
        ));

        if ($bank !== '') {
            return "{$bank}_va";
        }

        if (data_get($transaction, 'permata_va_number')) {
            return 'permata_va';
        }

        return 'bank_transfer';
    }

    public function syncSelfOrder(Pesanan $pesanan): void
    {
        $payment = $pesanan->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
            ->whereNotNull('midtrans_order_id')
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->latest('id')
            ->first();

        if (!$payment || !$pesanan->exists) {
            return;
        }

        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

        try {
            $remote = Transaction::status($payment->midtrans_order_id);
        } catch (\Throwable $e) {
            if ($this->isTransactionNotFound($e)) {
                // Token tanpa transaksi Midtrans utuh tetap pending selama masa 15 menit.
                if (!$payment->created_at || $payment->created_at->gt(now()->subMinutes(15))) {
                    return;
                }

                $this->markFailedIfStillPending($payment->id, $pesanan->id);
                return;
            }

            Log::warning('Gagal memeriksa status pembayaran self-order di Midtrans.', [
                'payment_id' => $payment->id,
                'midtrans_order_id' => $payment->midtrans_order_id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $status = strtolower((string) data_get($remote, 'transaction_status', ''));
        $fraudStatus = strtolower((string) data_get($remote, 'fraud_status', ''));
        $method = MidtransPaymentMethod::from($remote);

        $success = $status === 'settlement'
            || ($status === 'capture' && $fraudStatus === 'accept');

        $failed = in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)
            || ($status === 'capture' && $fraudStatus === 'deny');

        if (!$success && !$failed) {
            if ($method !== null) {
                Pembayaran::whereKey($payment->id)
                    ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                    ->update(['metode' => $method]);
            }

            return;
        }

        DB::transaction(function () use (
            $payment,
            $pesanan,
            $success,
            $method,
            $status
        ) {
            $lockedOrder = Pesanan::whereKey($pesanan->id)
                ->lockForUpdate()
                ->first();

            $lockedPayment = Pembayaran::whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder || !$lockedPayment) {
                return;
            }

            if (strtolower(trim((string) $lockedPayment->status)) !== 'pending') {
                return;
            }

            if (!$success) {
                $lockedPayment->update([
                    'status' => 'Failed',
                    'metode' => $method ?? $lockedPayment->metode,
                ]);

                return;
            }

            $lockedPayment->update([
                'status' => 'Success',
                'metode' => $method ?? $lockedPayment->metode,
            ]);

            $latestPaymentId = $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                ->whereNotNull('midtrans_order_id')
                ->latest('id')
                ->value('id');

            $orderStatus = strtolower(trim((string) $lockedOrder->status));

            if (
                $orderStatus === 'cancelled'
                || (int) $latestPaymentId !== (int) $lockedPayment->id
            ) {
                Log::critical('Pembayaran self-order berhasil, tetapi order tidak dapat diaktifkan kembali.', [
                    'pesanan_id' => $lockedOrder->id,
                    'payment_id' => $lockedPayment->id,
                    'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                    'midtrans_status' => $status,
                    'order_status' => $orderStatus,
                ]);

                return;
            }

            if ($orderStatus === 'pending') {
                $lockedOrder->update(['status' => 'Paid']);
            }
        }, 3);
    }

    private function markFailedIfStillPending(int $paymentId, int $orderId): void
    {
        DB::transaction(function () use ($paymentId, $orderId) {
            $order = Pesanan::whereKey($orderId)->lockForUpdate()->first();
            $payment = Pembayaran::whereKey($paymentId)->lockForUpdate()->first();

            if (
                $order
                && $payment
                && strtolower(trim((string) $order->status)) === 'pending'
                && strtolower(trim((string) $payment->status)) === 'pending'
            ) {
                $payment->update(['status' => 'Failed']);
            }
        }, 3);
    }

    private function isTransactionNotFound(\Throwable $e): bool
    {
        return (int) $e->getCode() === 404
            || str_contains(strtolower($e->getMessage()), 'transaction doesn\'t exist')
            || str_contains(strtolower($e->getMessage()), 'transaction not found');
    }
}