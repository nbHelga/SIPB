<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification;
use App\Models\Pesanan;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Services\FonnteService;
use App\Services\MidtransPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Transaction;
use Midtrans\Snap;
use Carbon\Carbon;

class PaymentController extends Controller
{    
    public function midtransNotification(Request $request)
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');

        $production = config('services.midtrans.is_production');
        if ($production === null) {
            $production = env('MIDTRANS_IS_PRODUCTION', false);
        }

        MidtransConfig::$isProduction = filter_var($production, FILTER_VALIDATE_BOOLEAN);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

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
                    $lockedPayment->forceFill([
                        'status' => 'Success',
                        'metode' => $paymentMethod ?? $lockedPayment->metode,
                    ])->save();

                    $deadline = $reservasi
                        ? $reservasi->created_at->copy()->addMinutes(
                            max(
                                1,
                                (int) config(
                                    'services.reservation.payment_window_minutes',
                                    60
                                )
                            )
                        )
                        : null;

                    // $transactionTime = $midtransTransactionTime
                    //     ? Carbon::parse($midtransTransactionTime)
                    //     : null;

                    // // Callback boleh terlambat: transaksi yang benar-benar berhasil
                    // // sebelum deadline tetap diterima.
                    // $paidBeforeDeadline = !$reservasi
                    //     || ($transactionTime
                    //         ? $transactionTime->lte($deadline)
                    //         : now()->lt($deadline));

                    $orderCancelled =
                        strtolower(trim((string) $pesanan->status)) === 'cancelled';

                    // Deadline aplikasi menjadi acuan. Callback sukses yang diterima
                    // sebelum deadline tidak boleh tertahan karena parsing waktu Midtrans.
                    $deadlinePassed = $reservasi
                        && now()->greaterThanOrEqualTo($deadline);

                    if ($orderCancelled || $deadlinePassed) {
                        if ($reservasi && !$orderCancelled) {
                            $pesanan->forceFill(['status' => 'Cancelled'])->save();

                            $pesanan->pembayaran()
                                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                                ->update(['status' => 'Failed']);
                        }

                        Log::critical(
                            'Pembayaran Midtrans berhasil setelah reservasi dibatalkan atau melewati deadline.',
                            [
                                'payment_id' => $lockedPayment->id,
                                'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                                'pesanan_id' => $pesanan->id,
                                'transaction_time' => $midtransTransactionTime,
                            ]
                        );

                        return;
                    }

                    // Jangan menolak pembayaran sukses hanya karena ada retry
                    // yang membuat baris DP lebih baru.
                    $pesanan->forceFill([
                        'status' => $jenis === 'dp' ? 'Reserved' : 'Paid',
                    ])->save();

                    if ($reservasi && $jenis === 'dp') {
                        $reservasiToNotify = Reservasi::with([
                            'customer',
                            'pesanan.pembayaran',
                            'detailReservasi.meja',
                        ])->find($reservasi->id);
                    }

                    return;
                }

                $lockedPayment->forceFill([
                    'status' => 'Failed',
                    'metode' => $paymentMethod ?? $lockedPayment->metode,
                ])->save();

                // Pesanan tetap pending sebelum deadline satu jam.
                // Setelah deadline, batalkan pesanan dan semua tagihan pending.
                if (
                    $reservasi
                    && strtolower(trim((string) $pesanan->status)) === 'pending'
                    && now()->greaterThanOrEqualTo(
                        $reservasi->created_at->copy()->addMinutes(
                            max(1, (int) config('services.reservation.payment_window_minutes', 60))
                        )
                    )
                ) {
                    $hasSuccessfulDp = $pesanan->pembayaran()
                        ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                        ->whereRaw("LOWER(TRIM(status)) = 'success'")
                        ->exists();

                    if (!$hasSuccessfulDp) {
                        $pesanan->forceFill(['status' => 'Cancelled'])->save();

                        $pesanan->pembayaran()
                            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                            ->update(['status' => 'Failed']);
                    }
                }
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
                        ->format('d M Y');

                    // $customerMessage = "RESERVASI BIGBANG EATERY BERHASIL\n"
                    //     . "Halo {$customer->nama}, pembayaran DP Anda berhasil.\n\n"
                    //     . "Tanggal: {$date}\n"
                    //     . "Jam: {$reservasiToNotify->jam_mulai}–{$reservasiToNotify->jam_selesai} WIB\n"
                    //     . "Tamu: {$reservasiToNotify->jml_org} orang\n"
                    //     . "DP: Rp " . number_format($dpAmount, 0, ',', '.') . " ({$method})\n"
                    //     . "Sisa pembayaran dilakukan di restoran.\n\n"
                    //     . "Detail reservasi: {$detailUrl}";
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

                    // $adminMessage = "DP RESERVASI BERHASIL\n"
                    //     . "Pelanggan: {$customer->nama} ({$customer->nomor_telp})\n"
                    //     . "Jadwal: {$date}, {$reservasiToNotify->jam_mulai}–{$reservasiToNotify->jam_selesai} WIB\n"
                    //     . "Tamu: {$reservasiToNotify->jml_org} orang\n"
                    //     . "DP: Rp " . number_format($dpAmount, 0, ',', '.') . " ({$method})\n"
                    //     . "Meja: {$tableNumber}\n"
                    //     . "Kelola reservasi: " . route('admin.dashboard');
                    $adminMessage = "🔔 *Reservasi Baru (DP Lunas)*\n\n"
                        . "👤 *{$customer->nama}* ({$customer->nomor_telp})\n"
                        . "📅 *{$date}* | *" . date('H.i', strtotime($reservasiToNotify->jam_mulai)) . " WIB*\n"
                        . "👥 *{$reservasiToNotify->jml_org} Orang* | Meja: *{$tableNumber}*\n"
                        . "💰 DP: *Rp " . number_format($dpAmount, 0, ',', '.') . "* ({$method})\n\n";
                        // . "Cek admin: " . route('admin.dashboard');

                    $fonnte = app(FonnteService::class);

                    // Klaim flag sebelum mengirim agar callback berulang tidak mengirim duplikat.
                    $customerClaimed = Reservasi::whereKey($reservasiToNotify->id)
                        ->where(function ($query) {
                            $query->whereNull('customer_notif')
                                ->orWhere('customer_notif', false)
                                ->orWhere('customer_notif', 0);
                        })
                        ->update(['customer_notif' => true]);

                    if ($customerClaimed && !$fonnte->send($customer->nomor_telp, $customerMessage)) {
                        Reservasi::whereKey($reservasiToNotify->id)
                            ->update(['customer_notif' => false]);

                        Log::warning('WhatsApp konfirmasi reservasi ke customer gagal.', [
                            'reservasi_id' => $reservasiToNotify->id,
                        ]);
                    }

                    $adminClaimed = Reservasi::whereKey($reservasiToNotify->id)
                        ->where(function ($query) {
                            $query->whereNull('admin_notif')
                                ->orWhere('admin_notif', false)
                                ->orWhere('admin_notif', 0);
                        })
                        ->update(['admin_notif' => true]);

                    if ($adminClaimed && !$fonnte->send(env('FONNTE_PHONE'), $adminMessage)) {
                        Reservasi::whereKey($reservasiToNotify->id)
                            ->update(['admin_notif' => false]);

                        Log::warning('WhatsApp konfirmasi reservasi ke admin gagal.', [
                            'reservasi_id' => $reservasiToNotify->id,
                        ]);
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

        if (strtolower(trim((string) $pesanan->status)) !== 'pending') {
            return back()->with('error', 'Pesanan tidak lagi menunggu pembayaran.');
        }

        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

        foreach (
            $pesanan->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->whereNotNull('midtrans_order_id')
                ->get() as $payment
        ) {
            try {
                $remote = Transaction::status($payment->midtrans_order_id);
                $remoteStatus = strtolower((string) data_get(
                    $remote,
                    'transaction_status',
                    ''
                ));

                if (
                    $remoteStatus === 'settlement'
                    || (
                        $remoteStatus === 'capture'
                        && strtolower((string) data_get($remote, 'fraud_status', '')) === 'accept'
                    )
                ) {
                    $payment->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($remote)
                            ?? $payment->metode,
                    ]);

                    $pesanan->update(['status' => 'Paid']);

                    return redirect()
                        ->route('customer.order.detail', [
                            'kode_pesanan' => $pesanan->kode_pesanan,
                        ])
                        ->with('error', 'Pembayaran sudah berhasil; pesanan tidak dapat dibatalkan.');
                }

                if (in_array($remoteStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => MidtransPaymentMethod::from($remote)
                            ?? $payment->metode,
                    ]);

                    continue;
                }

                Transaction::cancel($payment->midtrans_order_id);

                $verified = Transaction::status($payment->midtrans_order_id);
                $verifiedStatus = strtolower((string) data_get(
                    $verified,
                    'transaction_status',
                    ''
                ));

                if (
                    $verifiedStatus === 'settlement'
                    || (
                        $verifiedStatus === 'capture'
                        && strtolower((string) data_get($verified, 'fraud_status', '')) === 'accept'
                    )
                ) {
                    $payment->update([
                        'status' => 'Success',
                        'metode' => MidtransPaymentMethod::from($verified)
                            ?? $payment->metode,
                    ]);

                    $pesanan->update(['status' => 'Paid']);

                    return redirect()
                        ->route('customer.order.detail', [
                            'kode_pesanan' => $pesanan->kode_pesanan,
                        ])
                        ->with('error', 'Pembayaran sudah berhasil; pesanan tidak dapat dibatalkan.');
                }

                if (in_array($verifiedStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => MidtransPaymentMethod::from($verified)
                            ?? $payment->metode,
                    ]);

                    continue;
                }

                return back()->with(
                    'error',
                    'Midtrans belum mengonfirmasi pembatalan pembayaran. Coba kembali.'
                );
            } catch (\Throwable $e) {
                $notFound = (int) $e->getCode() === 404
                    || str_contains(strtolower($e->getMessage()), 'transaction doesn\'t exist')
                    || str_contains(strtolower($e->getMessage()), 'transaction not found');

                if ($notFound) {
                    continue;
                }

                Log::warning('Pembatalan transaksi Midtrans gagal.', [
                    'payment_id' => $payment->id,
                    'midtrans_order_id' => $payment->midtrans_order_id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with(
                    'error',
                    'Pembayaran belum dapat dibatalkan di Midtrans. Coba kembali.'
                );
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
                ->whereRaw("LOWER(TRIM(status)) = 'success'")
                ->exists();

            if ($hasSuccessfulPayment) {
                return;
            }

            $lockedOrder->update(['status' => 'Cancelled']);

            $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->update(['status' => 'Failed']);
        }, 3);

        if (Reservasi::where('id_pesanan', $pesanan->id)->exists()) {
            return redirect()->route('home')
                ->with('success', 'Reservasi berhasil dibatalkan.');
        }

        return redirect()->route('customer.selforder', [
            'kode' => $pesanan->kode_pesanan,
            'tableNumber' => $pesanan->meja->nomor_meja ?? '',
        ])->with('success', 'Pesanan berhasil dibatalkan.');
    }
    
    public function ulangiPembayaran($kode_pesanan)
    {
        $pesanan = Pesanan::with(['detailPesanan.menu', 'meja', 'pembayaran'])
            ->where('kode_pesanan', $kode_pesanan)
            ->firstOrFail();

        if (strtolower(trim((string) $pesanan->status)) !== 'pending') {
            return back()->with('error', 'Pesanan tidak lagi menunggu pembayaran.');
        }

        if (Reservasi::where('id_pesanan', $pesanan->id)->exists()) {
            return back()->with('error', 'Gunakan tombol pembayaran pada detail reservasi.');
        }

        foreach ($pesanan->detailPesanan as $detail) {
            if (!$detail->menu || $detail->menu->status !== 'open') {
                $this->batalkanPesanan($kode_pesanan);

                return redirect()->route('customer.selforder', [
                    'tableNumber' => $pesanan->meja->nomor_meja ?? '',
                ])->with('error', 'Ada menu yang tidak tersedia. Pesanan dibatalkan.');
            }
        }

        // app(MidtransPaymentMethod::class)->syncSelfOrder($pesanan);

        $pesanan->refresh();

        $latest = $pesanan->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
            ->whereNotNull('midtrans_order_id')
            ->latest('id')
            ->first();

        if ($latest && strtolower(trim((string) $latest->status)) === 'pending') {
            if ($latest->snap_token) {
                return redirect()
                    ->route('customer.order.detail', [
                        'kode_pesanan' => $pesanan->kode_pesanan,
                    ])
                    ->with('open_midtrans', true);
            }

            return redirect()
                ->route('customer.order.detail', [
                    'kode_pesanan' => $pesanan->kode_pesanan,
                ])
                ->with('error', 'Pembayaran sedang disiapkan. Muat ulang halaman sebentar lagi.');
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
            $newPayment = DB::transaction(function () use (
                $pesanan,
                $nominal,
                $midtransOrderId
            ) {
                $lockedOrder = Pesanan::whereKey($pesanan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (strtolower(trim((string) $lockedOrder->status)) !== 'pending') {
                    throw new \RuntimeException('Status pesanan sudah berubah.');
                }

                $latestPayment = $lockedOrder->pembayaran()
                    ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                    // ->whereRaw("LOWER(TRIM(metode)) = 'midtrans'")
                    ->whereNotNull('midtrans_order_id')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                // Lindungi dari double-click/concurrent request.
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

            MidtransConfig::$serverKey = config('services.midtrans.server_key')
                ?: env('MIDTRANS_SERVER_KEY');
            MidtransConfig::$isProduction = filter_var(
                config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
                FILTER_VALIDATE_BOOLEAN
            );
            MidtransConfig::$isSanitized = true;
            MidtransConfig::$is3ds = true;

            $returnUrl = route('customer.order.detail', [
                'kode_pesanan' => $pesanan->kode_pesanan,
            ]);

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
                    'duration' => 15,
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
