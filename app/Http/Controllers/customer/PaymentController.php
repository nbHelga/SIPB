<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use Midtrans\Notification;
use App\Models\Pesanan;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Models\NotifikasiWa;
use App\Services\FonnteService;
use App\Services\MidtransPaymentMethod;
use App\Services\MidtransConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Transaction;
use Midtrans\Snap;
use Carbon\Carbon;

class PaymentController extends Controller
{    
    public function changeMethod(Request $request, string $kode_pesanan)
    {
        $data = $request->validate(['metode_pembayaran' => ['required', 'in:Tunai,Midtrans']]);
        $order = Pesanan::with(['detailPesanan.menu', 'meja', 'pembayaran', 'reservasi.customer'])
            ->where('kode_pesanan', $kode_pesanan)
            ->firstOrFail();

        if (strtolower(trim((string) $order->status)) !== 'pending') {
            return back()->with('error', 'Metode pembayaran hanya dapat diubah saat pesanan menunggu pembayaran.');
        }

        $isReservation = (bool) $order->reservasi;
        $detailRoute = $isReservation ? 'customer.reservasi.detail' : 'customer.order.detail';

        $deadline = $isReservation
            ? $order->reservasi->paymentDeadline()
            : $order->created_at->copy()->addMinutes(
                app(\App\Services\SystemSettings::class)->get(
                    strtolower(trim((string) $order->pembayaran()->where('jenis', 'Full')->oldest('id')->value('metode'))) === 'tunai'
                        ? 'selforder_cash_minutes'
                        : 'selforder_qris_minutes'
                )
            );

        if (now()->gte($deadline)) {
            return back()->with('error', 'Batas waktu pembayaran telah habis.');
        }

        $kind = $isReservation ? 'DP' : 'Full';
        $current = $order->pembayaran()->where('jenis', $kind)->latest('id')->first();
        $targetMethod = $isReservation ? 'midtrans' : ($data['metode_pembayaran'] === 'Tunai' ? 'tunai' : 'other_qris');

        if ($isReservation && $data['metode_pembayaran'] === 'Tunai') {
            return back()->with('error', 'Pembayaran reservasi hanya dapat dilakukan secara digital/online.');
        }
        if ($isReservation && in_array(strtolower(trim((string) $current?->metode)), ['', 'midtrans'], true)) {
            return back()->with('error', 'Anda belum memilih metode pembayaran. Silakan pilih metode pembayaran terlebih dahulu melalui tombol Lanjutkan Pembayaran.');
        }
        if (!$isReservation && strtolower(trim((string) $current?->metode)) === $targetMethod) {
            return back()->with('error', 'Metode tersebut sudah digunakan.');
        }
        if (strtolower(trim((string) $current?->status)) === 'success') {
            return back()->with('error', 'Pembayaran sudah berhasil.');
        }

        // 1. Cek status dan batalkan transaksi aktif di Midtrans terlebih dahulu (aman terhadap 404/412).
        $pendingMidtransPayments = $order->pembayaran()
            ->where('jenis', $kind)
            ->where('status', 'Pending')
            ->whereNotNull('midtrans_order_id')
            ->get();

        if ($pendingMidtransPayments->isNotEmpty()) {
            app(MidtransConfigService::class)->configure();
        }

        foreach ($pendingMidtransPayments as $old) {
            try {
                $remote = Transaction::status($old->midtrans_order_id);
                $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));
                $remoteFraud = strtolower((string) data_get($remote, 'fraud_status', ''));

                if ($remoteStatus === 'settlement' || ($remoteStatus === 'capture' && $remoteFraud === 'accept')) {
                    $old->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($remote) ?? $old->metode,
                    ]);
                    $order->update(['status' => $isReservation ? 'Reserved' : 'Paid']);

                    return redirect()->route($detailRoute, ['kode_pesanan' => $kode_pesanan])
                        ->with('success', 'Pembayaran sebelumnya sudah berhasil dikonfirmasi.');
                }

                if (in_array($remoteStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $old->update([
                        'status' => 'Failed',
                        'snap_token' => null,
                        'metode' => MidtransPaymentMethod::from($remote) ?? $old->metode,
                    ]);
                    continue;
                }

                Transaction::cancel($old->midtrans_order_id);
                $verified = Transaction::status($old->midtrans_order_id);
                $verifiedStatus = strtolower((string) data_get($verified, 'transaction_status', ''));
                $verifiedFraud = strtolower((string) data_get($verified, 'fraud_status', ''));

                if ($verifiedStatus === 'settlement' || ($verifiedStatus === 'capture' && $verifiedFraud === 'accept')) {
                    $old->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($verified) ?? $old->metode,
                    ]);
                    $order->update(['status' => $isReservation ? 'Reserved' : 'Paid']);

                    return redirect()->route($detailRoute, ['kode_pesanan' => $kode_pesanan])
                        ->with('success', 'Pembayaran sebelumnya sudah berhasil dikonfirmasi.');
                }

                if (!in_array($verifiedStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    return back()->with('error', 'Pembayaran lama belum dapat ditutup di Midtrans. Coba kembali beberapa saat lagi.');
                }

                $old->update([
                    'status' => 'Failed',
                    'snap_token' => null,
                    'metode' => MidtransPaymentMethod::from($verified) ?? $old->metode,
                ]);
            } catch (\Throwable $e) {
                $notFound = (int) $e->getCode() === 404
                    || str_contains(strtolower($e->getMessage()), "transaction doesn't exist")
                    || str_contains(strtolower($e->getMessage()), 'transaction not found');

                if ($notFound) {
                    // Pelanggan belum memunculkan/memilih kanal di Snap: aman ditandai Failed secara lokal.
                    $old->update(['status' => 'Failed', 'snap_token' => null]);
                    continue;
                }

                Log::warning('Tidak dapat menutup pembayaran lama saat ganti metode.', [
                    'payment_id' => $old->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Pembayaran lama belum dapat ditutup. Coba kembali beberapa saat lagi.');
            }
        }

        // 2. Buat baris pembayaran baru secara atomik (lindungi dari double-click).
        try {
            $payment = DB::transaction(function () use ($order, $kind, $targetMethod, $deadline) {
                $lockedOrder = Pesanan::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (strtolower(trim((string) $lockedOrder->status)) !== 'pending' || now()->gte($deadline)) {
                    throw new \RuntimeException('Status pesanan berubah atau batas waktu pembayaran telah habis.');
                }

                $hasSuccess = $lockedOrder->pembayaran()
                    ->where('jenis', $kind)
                    ->where('status', 'Success')
                    ->exists();

                if ($hasSuccess) {
                    throw new \RuntimeException('Pembayaran sudah berhasil.');
                }

                $latestBefore = $lockedOrder->pembayaran()->where('jenis', $kind)->latest('id')->lockForUpdate()->first();
                $amount = (int) ($latestBefore?->nominal ?? $lockedOrder->total_harga);

                $lockedOrder->pembayaran()
                    ->where('jenis', $kind)
                    ->where('status', 'Pending')
                    ->update(['status' => 'Failed', 'snap_token' => null]);

                return Pembayaran::create([
                    'id_pesanan' => $lockedOrder->id,
                    'jenis' => $kind,
                    'metode' => $targetMethod,
                    'nominal' => $amount,
                    'status' => 'Pending',
                ]);
            }, 3);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        // 3. Jika beralih ke Midtrans/QRIS, buat Snap token baru dengan sisa waktu menuju deadline.
        if ($targetMethod !== 'tunai') {
            try {
                app(MidtransConfigService::class)->configure();
                $remainingMinutes = max(1, (int) ceil(now()->diffInSeconds($deadline) / 60));
                $midtransId = ($isReservation ? 'RESERV-' . $order->reservasi->id : 'ORDER-' . $order->id) . '-' . Str::random(8);
                $amount = (int) $payment->nominal;
                $items = $isReservation
                    ? [['id' => 'DP', 'price' => $amount, 'quantity' => 1, 'name' => 'DP Reservasi']]
                    : $order->detailPesanan->map(fn ($d) => [
                        'id' => (string) $d->id_menu,
                        'price' => (int) round($d->subtotal / max(1, $d->jumlah)),
                        'quantity' => $d->jumlah,
                        'name' => substr($d->menu?->nama ?? 'Menu', 0, 50),
                    ])->values()->all();

                $enabledPayments = $isReservation
                    ? ['other_qris', 'gopay', 'shopeepay', 'bca_va']
                    : ['other_qris'];

                $customerDetails = $isReservation
                    ? [
                        'first_name' => $order->reservasi->customer->nama,
                        'phone' => $order->reservasi->customer->nomor_telp,
                    ]
                    : [
                        'first_name' => 'Meja ' . ($order->meja->nomor_meja ?? 'Tamu'),
                    ];

                $returnUrl = route($detailRoute, ['kode_pesanan' => $kode_pesanan]);

                $token = Snap::getSnapToken([
                    'transaction_details' => ['order_id' => $midtransId, 'gross_amount' => $amount],
                    'item_details' => $items,
                    'customer_details' => $customerDetails,
                    'enabled_payments' => $enabledPayments,
                    'custom_expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit' => 'minute',
                        'duration' => $remainingMinutes,
                    ],
                    'callbacks' => [
                        'finish' => $returnUrl,
                        'unfinish' => $returnUrl,
                        'error' => $returnUrl,
                    ],
                ]);

                $payment->update([
                    'midtrans_order_id' => $midtransId,
                    'snap_token' => $token,
                ]);
            } catch (\Throwable $e) {
                $payment->update(['status' => 'Failed', 'snap_token' => null]);
                Log::error('Gagal menyiapkan token saat ganti metode pembayaran.', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Pembayaran digital gagal disiapkan. Coba kembali.');
            }
        }

        return redirect()->route($detailRoute, ['kode_pesanan' => $kode_pesanan])
            ->with('open_midtrans', $targetMethod !== 'tunai');
    }

    public function midtransNotification(Request $request)
    {
        app(MidtransConfigService::class)->configure();

        try {
            // Memvalidasi notifikasi Midtrans.
            $notification = new Notification();
            $orderId = (string) ($notification->order_id ?? '');

            if ($orderId === '') {
                return response()->json(['message' => 'Order ID tidak ditemukan'], 400);
            }

            $payment = Pembayaran::where('midtrans_order_id', $orderId)->first();

            if (!$payment) {
                Log::warning('Callback Midtrans tidak menemukan pembayaran.', [
                    'order_id' => $orderId,
                ]);

                return response()->json(['message' => 'Payment not found'], 200);
            }

            // Pakai status terbaru dari Midtrans, bukan callback yang mungkin terlambat.
            $latest = Transaction::status($orderId);

            $status = strtolower((string) ($latest->transaction_status ?? ''));
            $fraudStatus = strtolower((string) ($latest->fraud_status ?? ''));
            $grossAmount = (float) ($latest->gross_amount ?? $notification->gross_amount ?? 0);

            if (round($grossAmount, 2) !== round((float) $payment->nominal, 2)) {
                Log::error('Nominal callback Midtrans tidak cocok.', [
                    'order_id' => $orderId,
                    'midtrans_amount' => $grossAmount,
                    'database_amount' => $payment->nominal,
                ]);

                return response()->json(['message' => 'Amount mismatch'], 200);
            }

            $success = $status === 'settlement'
                || ($status === 'capture' && $fraudStatus === 'accept');

            $failed = in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)
                || ($status === 'capture' && $fraudStatus === 'deny');

            $reservasiToNotify = null;
            $paymentMethod = MidtransPaymentMethod::from($latest);
            Log::info('Detail kanal transaksi Midtrans.', [
                'order_id' => $orderId,
                'transaction_status' => data_get($latest, 'transaction_status'),
                'payment_type' => data_get($latest, 'payment_type'),
                'acquirer' => data_get($latest, 'acquirer'),
                'issuer' => data_get($latest, 'issuer'),
                'transaction_time' => data_get($latest, 'transaction_time'),
                'settlement_time' => data_get($latest, 'settlement_time'),
                'mapped_method' => $paymentMethod,
            ]);

            // Pending/challenge bukan bukti berhasil maupun gagal.
            if (!$success && !$failed) {
                if ($paymentMethod !== null) {
                    DB::transaction(function () use ($payment, $paymentMethod) {
                        $lockedPayment = Pembayaran::whereKey($payment->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            $lockedPayment
                            && strtolower(trim((string) $lockedPayment->status)) === 'pending'
                        ) {
                            $lockedPayment->update(['metode' => $paymentMethod]);
                        }
                    }, 3);
                }

                return response()->json(['message' => 'Notification received'], 200);
            }

            $midtransTransactionTime = data_get($latest, 'transaction_time');
            
            DB::transaction(function () use (
                $payment,
                $success,
                $paymentMethod,
                $midtransTransactionTime,
                &$reservasiToNotify
            ) {
                $lockedPayment = Pembayaran::whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $pesanan = Pesanan::whereKey($lockedPayment->id_pesanan)
                    ->lockForUpdate()
                    ->firstOrFail();

                $paymentWasAlreadySuccessful =
                    strtolower(trim((string) $lockedPayment->status)) === 'success';

                // Callback gagal yang datang terlambat tidak boleh menurunkan status Success.
                if (!$success && $paymentWasAlreadySuccessful) {
                    return;
                }

                $jenis = strtolower(trim((string) $lockedPayment->jenis));
                $reservasi = $jenis === 'dp'
                    ? Reservasi::where('id_pesanan', $pesanan->id)->first()
                    : null;

                if ($success) {
                    $hasOtherSuccessfulSameKind = $pesanan->pembayaran()
                        ->where('jenis', $lockedPayment->jenis)
                        ->where('status', 'Success')
                        ->whereKeyNot($lockedPayment->id)
                        ->exists();

                    $lockedPayment->forceFill([
                        'status' => 'Success',
                        'metode' => $paymentMethod ?? $lockedPayment->metode,
                    ])->save();

                    $orderStatus = strtolower(trim((string) $pesanan->status));
                    $orderCancelled = $orderStatus === 'cancelled';

                    // Jika pesanan sudah terlanjur dibatalkan (misal: akibat race condition dengan cron job
                    // auto-expire yang berjalan beberapa detik sebelum settlement Midtrans tiba):
                    if ($orderCancelled) {
                        $canRestore = false;

                        // Cek apakah kuota meja masih mencukupi untuk memulihkan pesanan yang sempat dibatalkan
                        if ($reservasi && (int) $reservasi->jml_org > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests')) {
                            $canRestore = true;
                        } elseif ($reservasi) {
                            $capacity = app(\App\Services\ReservationCapacityService::class)->check(
                                $reservasi->tanggal,
                                $reservasi->jam_mulai,
                                $reservasi->jam_selesai,
                                (int) $reservasi->jml_org
                            );
                            $canRestore = (bool) ($capacity['available'] ?? false);
                        }

                        if ($canRestore) {
                            $pesanan->forceFill(['status' => $jenis === 'dp' ? 'Reserved' : 'Paid'])->save();

                            Log::warning(
                                'Pembayaran Midtrans berhasil untuk reservasi yang sempat dibatalkan. Berhasil dipulihkan menjadi Reserved karena kuota masih ada.',
                                [
                                    'payment_id' => $lockedPayment->id,
                                    'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                                    'pesanan_id' => $pesanan->id,
                                    'transaction_time' => $midtransTransactionTime,
                                ]
                            );

                            if ($reservasi && $jenis === 'dp') {
                                $reservasiToNotify = Reservasi::with([
                                    'customer',
                                    'pesanan.pembayaran',
                                    'detailReservasi.meja',
                                ])->find($reservasi->id);
                            }

                            return;
                        }

                        // Jika kuota sudah benar-benar penuh/diambil orang lain:
                        Log::critical(
                            'Pembayaran Midtrans berhasil setelah reservasi dibatalkan dan kuota tidak mencukupi untuk pemulihan. Perlu refund manual oleh admin.',
                            [
                                'payment_id' => $lockedPayment->id,
                                'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                                'pesanan_id' => $pesanan->id,
                                'transaction_time' => $midtransTransactionTime,
                            ]
                        );

                        return;
                    }

                    // KASUS NORMAL / RESMI:
                    // Hanya ubah status pesanan jika masih menunggu (Pending/Waiting) dan belum ada pembayaran sukses lain
                    // pada jenis yang sama, agar tidak menurunkan status pesanan yang sudah Confirmed/Completed.
                    if (
                        !$hasOtherSuccessfulSameKind
                        && in_array($orderStatus, ['pending', 'waiting'], true)
                    ) {
                        $pesanan->forceFill([
                            'status' => $jenis === 'dp' ? 'Reserved' : 'Paid',
                        ])->save();

                        $pesanan->pembayaran()
                            ->where('jenis', $lockedPayment->jenis)
                            ->where('status', 'Pending')
                            ->whereKeyNot($lockedPayment->id)
                            ->update([
                                'status' => 'Failed',
                                'snap_token' => null,
                            ]);

                        if ($reservasi && $jenis === 'dp') {
                            $reservasiToNotify = Reservasi::with([
                                'customer',
                                'pesanan.pembayaran',
                                'detailReservasi.meja',
                            ])->find($reservasi->id);
                        }
                    } elseif ($hasOtherSuccessfulSameKind) {
                        Log::critical('Terdeteksi pembayaran Midtrans ganda yang berhasil untuk pesanan yang sama.', [
                            'payment_id' => $lockedPayment->id,
                            'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                            'pesanan_id' => $pesanan->id,
                            'order_status' => $orderStatus,
                        ]);
                    }

                    return;
                }

                // Webhook gagal (expire/deny/cancel/failure) hanya mengubah baris pembayaran spesifik
                // yang cocok dengan midtrans_order_id menjadi Failed. Jangan menyentuh baris pembayaran baru
                // ataupun membatalkan reservasi (pembatalan otomatis ditangani oleh scheduler).
                $lockedPayment->forceFill([
                    'status' => 'Failed',
                    'snap_token' => null,
                    'metode' => $paymentMethod ?? $lockedPayment->metode,
                ])->save();
            }, 3);

            // return response()->json(['message' => 'Notification processed'], 200);
            // Kirim notifikasi WA SETELAH DB::transaction selesai dengan aman
            if ($reservasiToNotify) {
                $customer = $reservasiToNotify->customer;
                $order = $reservasiToNotify->pesanan;
                $dp = $order?->pembayaran
                    ->filter(fn ($row) =>
                        strtolower(trim((string) $row->jenis)) === 'dp'
                        && strtolower(trim((string) $row->status)) === 'success'
                    )
                    ->sortByDesc('id')
                    ->first();

                if ($customer && $dp) {
                    $detailUrl = route('customer.reservasi.detail', [
                        'kode_pesanan' => $order->kode_pesanan,
                    ]);

                    $method = ucfirst((string) $dp->metode);
                    $dpAmount = (int) $dp->nominal;
                    $date = Carbon::parse($reservasiToNotify->tanggal, 'Asia/Jakarta')
                        ->locale('id')->translatedFormat('d M Y');

                    $customerMessage = "✅ *Reservasi Dikonfirmasi!*\n\n"
                        . "Halo *{$customer->nama}*, pembayaran DP kamu berhasil diterima.\n\n"
                        . "📅 Tgl: *{$date}*\n"
                        . "⏰ Jam: *" . date('H.i', strtotime($reservasiToNotify->jam_mulai)) . " - " . date('H.i', strtotime($reservasiToNotify->jam_selesai)) . " WIB*\n"
                        . "👥 Jml: *{$reservasiToNotify->jml_org} Orang*\n"
                        . "💳 DP Masuk: *Rp " . number_format($dpAmount, 0, ',', '.') . "* ({$method})\n\n"
                        . "_Catatan: Sisa tagihan dibayarkan di kasir._\n\n"
                        . "📌 Detail: {$detailUrl}";

                    $detailMeja = $reservasiToNotify->detailReservasi->first();
                    $tableNumber = $detailMeja?->meja?->nomor_meja ?? '-';

                    $adminMessage = "🔔 *Reservasi Baru (DP Lunas)*\n\n"
                        . "👤 *{$customer->nama}* ({$customer->nomor_telp})\n"
                        . "📅 *{$date}* | *" . date('H.i', strtotime($reservasiToNotify->jam_mulai)) . " WIB*\n"
                        . "👥 *{$reservasiToNotify->jml_org} Orang* | Meja: *{$tableNumber}*\n"
                        . "💰 DP: *Rp " . number_format($dpAmount, 0, ',', '.') . "* ({$method})\n\n";
                        // . "Cek admin: " . route('admin.dashboard');

                    $fonnte = app(FonnteService::class);

                    // Catat ke notifikasi_wa agar callback berulang tidak mengirim duplikat.
                    if (!NotifikasiWa::sudahTerkirim($reservasiToNotify->id, 'dp_customer') && !empty($customer->nomor_telp)) {
                        if ($fonnte->send($customer->nomor_telp, $customerMessage)) {
                            NotifikasiWa::catatTerkirim($reservasiToNotify->id, 'dp_customer');
                        } else {
                            NotifikasiWa::catatGagal($reservasiToNotify->id, 'dp_customer');
                            Log::warning('WhatsApp konfirmasi reservasi ke customer gagal.', [
                                'reservasi_id' => $reservasiToNotify->id,
                            ]);
                        }
                    }

                    $adminPhone = config('services.fonnte.phone'); // FIX SECURITY-04
                    if (!NotifikasiWa::sudahTerkirim($reservasiToNotify->id, 'dp_admin') && !empty($adminPhone)) {
                        if ($fonnte->send($adminPhone, $adminMessage)) {
                            NotifikasiWa::catatTerkirim($reservasiToNotify->id, 'dp_admin');
                        } else {
                            NotifikasiWa::catatGagal($reservasiToNotify->id, 'dp_admin');
                            Log::warning('WhatsApp konfirmasi reservasi ke admin gagal.', [
                                'reservasi_id' => $reservasiToNotify->id,
                            ]);
                        }
                    }
                }
            }

            return response()->json(['message' => 'Notification processed'], 200);
        } catch (\Throwable $e) {
            Log::error('Callback Midtrans gagal diproses.', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            // Error sementara/API Midtrans gagal: minta Midtrans mencoba callback lagi.
            return response()->json(['message' => 'Notification processing failed'], 500);
        }
    }

    public function batalkanPesanan($kode_pesanan)
    {
        $pesanan = Pesanan::with('pembayaran', 'meja')
            ->where('kode_pesanan', $kode_pesanan)
            ->firstOrFail();

        if (Reservasi::where('id_pesanan', $pesanan->id)->exists()) {
            return back()->with('error', 'Pembatalan reservasi tidak dapat dilakukan melalui halaman ini.');
        }

        if (strtolower(trim((string) $pesanan->status)) !== 'pending') {
            return back()->with('error', 'Pesanan tidak lagi menunggu pembayaran.');
        }

        app(MidtransConfigService::class)->configure();

        foreach (
            $pesanan->pembayaran()
                ->where('status', 'Pending')
                ->whereNotNull('midtrans_order_id')
                ->get() as $payment
        ) {
            try {
                $remote = Transaction::status($payment->midtrans_order_id);
                $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));

                if (
                    $remoteStatus === 'settlement'
                    || (
                        $remoteStatus === 'capture'
                        && strtolower((string) data_get($remote, 'fraud_status', '')) === 'accept'
                    )
                ) {
                    $payment->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($remote) ?? $payment->metode,
                    ]);

                    $pesanan->update(['status' => 'Paid']);

                    return redirect()
                        ->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                        ->with('error', 'Pembayaran sudah berhasil; pesanan tidak dapat dibatalkan.');
                }

                if (in_array($remoteStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => MidtransPaymentMethod::from($remote) ?? $payment->metode,
                    ]);

                    continue;
                }

                Transaction::cancel($payment->midtrans_order_id);

                $verified = Transaction::status($payment->midtrans_order_id);
                $verifiedStatus = strtolower((string) data_get($verified, 'transaction_status', ''));

                if (
                    $verifiedStatus === 'settlement'
                    || (
                        $verifiedStatus === 'capture'
                        && strtolower((string) data_get($verified, 'fraud_status', '')) === 'accept'
                    )
                ) {
                    $payment->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($verified) ?? $payment->metode,
                    ]);

                    $pesanan->update(['status' => 'Paid']);

                    return redirect()
                        ->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                        ->with('error', 'Pembayaran sudah berhasil; pesanan tidak dapat dibatalkan.');
                }

                if (in_array($verifiedStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => MidtransPaymentMethod::from($verified) ?? $payment->metode,
                    ]);

                    continue;
                }

                return back()->with('error', 'Midtrans belum mengonfirmasi pembatalan pembayaran. Coba kembali.');
            } catch (\Throwable $e) {
                $notFound = (int) $e->getCode() === 404
                    || str_contains(strtolower($e->getMessage()), "transaction doesn't exist")
                    || str_contains(strtolower($e->getMessage()), 'transaction not found');

                if ($notFound) {
                    continue;
                }

                Log::warning('Pembatalan transaksi Midtrans gagal.', [
                    'payment_id' => $payment->id,
                    'midtrans_order_id' => $payment->midtrans_order_id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Pembayaran belum dapat dibatalkan di Midtrans. Coba kembali.');
            }
        }

        DB::transaction(function () use ($pesanan) {
            $lockedOrder = Pesanan::whereKey($pesanan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower(trim((string) $lockedOrder->status)) !== 'pending') {
                return;
            }

            $hasSuccessfulPayment = $lockedOrder->pembayaran()
                ->where('status', 'Success')
                ->exists();

            if ($hasSuccessfulPayment) {
                return;
            }

            $lockedOrder->update(['status' => 'Cancelled']);

            $lockedOrder->pembayaran()
                ->where('status', 'Pending')
                ->update(['status' => 'Failed']);
        }, 3);

        return redirect()->route('customer.selforder', [
            'tableNumber' => $pesanan->meja->nomor_meja ?? '',
        ])->with('success', 'Pesanan berhasil dibatalkan.');
    }
    
    public function ulangiPembayaran($kode_pesanan)
    {
        $pesanan = Pesanan::with(['detailPesanan.menu', 'meja', 'pembayaran'])
            ->where('kode_pesanan', $kode_pesanan)
            ->firstOrFail();

        if (Reservasi::where('id_pesanan', $pesanan->id)->exists()) {
            return back()->with('error', 'Gunakan tombol pembayaran pada detail reservasi.');
        }

        app(\App\Services\PendingSelfOrderExpiryService::class)->expireIfDue($pesanan);
        $pesanan->refresh();

        if (strtolower(trim((string) $pesanan->status)) !== 'pending') {
            return redirect()->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('error', 'Pesanan tidak lagi menunggu pembayaran.');
        }

        $initialMethod = strtolower(trim((string) $pesanan->pembayaran()
            ->where('jenis', 'Full')
            ->oldest('id')
            ->value('metode')));
        $windowMinutes = app(\App\Services\SystemSettings::class)->get(
            $initialMethod === 'tunai' ? 'selforder_cash_minutes' : 'selforder_qris_minutes'
        );
        $deadline = $pesanan->created_at->copy()->addMinutes($windowMinutes);

        if (now()->gte($deadline)) {
            app(\App\Services\PendingSelfOrderExpiryService::class)->expireIfDue($pesanan);
            return redirect()->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('error', 'Batas waktu pembayaran pesanan telah habis.');
        }

        foreach ($pesanan->detailPesanan as $detail) {
            if (!$detail->menu || $detail->menu->status !== 'open') {
                $this->batalkanPesanan($kode_pesanan);

                return redirect()->route('customer.selforder', [
                    'tableNumber' => $pesanan->meja->nomor_meja ?? '',
                ])->with('error', 'Ada menu yang tidak tersedia. Pesanan dibatalkan.');
            }
        }

        app(MidtransPaymentMethod::class)->syncSelfOrder($pesanan);
        $pesanan->refresh();
        $refreshedStatus = strtolower(trim((string) $pesanan->status));

        if ($refreshedStatus === 'paid') {
            return redirect()->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('success', 'Pembayaran Anda sudah berhasil dikonfirmasi.');
        }

        if ($refreshedStatus !== 'pending') {
            return redirect()->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('error', 'Pesanan tidak lagi menunggu pembayaran.');
        }

        $latest = $pesanan->pembayaran()
            ->where('jenis', 'Full')
            ->latest('id')
            ->first();

        if ($latest && strtolower(trim((string) $latest->status)) === 'pending') {
            if ($latest->midtrans_order_id) {
                try {
                    app(MidtransConfigService::class)->configure();
                    Transaction::cancel($latest->midtrans_order_id);
                } catch (\Throwable $e) {
                    // Abaikan jika sudah cancel/expire/404 di Midtrans
                }
            }
            $latest->update(['status' => 'Failed', 'snap_token' => null]);
        }

        $nominal = (int) ($latest?->nominal ?? $pesanan->total_harga);
        $items = [];

        foreach ($pesanan->detailPesanan as $detail) {
            $items[] = [
                'id' => (string) $detail->id_menu,
                'price' => (int) ($detail->subtotal / max(1, $detail->jumlah)),
                'quantity' => (int) $detail->jumlah,
                'name' => substr($detail->menu->nama ?? 'Menu', 0, 50),
            ];
        }

        $newPayment = null;
        $midtransOrderId = 'ORDER-' . $pesanan->id . '-' . Str::random(8);

        try {
            $newPayment = DB::transaction(function () use ($pesanan, $nominal, $midtransOrderId, $deadline) {
                $lockedOrder = Pesanan::whereKey($pesanan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (strtolower(trim((string) $lockedOrder->status)) !== 'pending' || now()->gte($deadline)) {
                    throw new \RuntimeException('Status pesanan sudah berubah atau waktu habis.');
                }

                $latestPayment = $lockedOrder->pembayaran()
                    ->where('jenis', 'Full')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($latestPayment && strtolower(trim((string) $latestPayment->status)) === 'pending') {
                    throw new \RuntimeException('Masih ada pembayaran aktif.');
                }

                return Pembayaran::create([
                    'id_pesanan' => $lockedOrder->id,
                    'jenis' => 'Full',
                    'metode' => 'other_qris',
                    'nominal' => $nominal,
                    'midtrans_order_id' => $midtransOrderId,
                    'status' => 'Pending',
                ]);
            }, 3);

            app(MidtransConfigService::class)->configure();

            $returnUrl = route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan]);
            $remainingMinutes = max(1, (int) ceil(now()->diffInSeconds($deadline) / 60));

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => $nominal,
                ],
                'item_details' => $items,
                'customer_details' => [
                    'first_name' => 'Meja ' . ($pesanan->meja->nomor_meja ?? 'Tamu'),
                ],
                'enabled_payments' => ['other_qris'],
                'custom_expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'minute',
                    'duration' => $remainingMinutes,
                ],
                'callbacks' => [
                    'finish' => $returnUrl,
                    'unfinish' => $returnUrl,
                    'error' => $returnUrl,
                ],
            ]);

            $newPayment->forceFill(['snap_token' => $snapToken])->save();

            return redirect()
                ->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('open_midtrans', true);
        } catch (\Throwable $e) {
            if ($newPayment) {
                $newPayment->forceFill([
                    'status' => 'Failed',
                    'snap_token' => null,
                ])->save();
            }

            Log::error('Ulangi pembayaran self-order gagal.', [
                'kode_pesanan' => $kode_pesanan,
                'payment_id' => $newPayment?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('customer.order.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('error', 'Pembayaran baru gagal dibuat. Muat ulang halaman dan coba kembali.');
        }
    }

    

    

// Sembunyikan dari Kasir/Dapur: Pastikan query di AdminController atau OrderController tidak memunculkan pesanan berstatus Pending (via Midtrans) di daftar antrean dapur atau notifikasi kasir. Pesanan hanya boleh muncul setelah berstatus Paid.
// Auto-Cancel Harian: Anda tidak perlu pusing memikirkan data yang menggantung ini. Namun, disarankan membuat satu fungsi sederhana (bisa dieksekusi manual oleh admin saat tutup restoran, atau otomatis lewat Cron Job) yang mencari semua pesanan berstatus Pending lebih dari 2 jam, lalu otomatis mengubah statusnya menjadi Cancelled untuk membersihkan database. Mana yg lebih baik?
}
