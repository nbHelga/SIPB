<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Services\MenuQuoteService;
use App\Services\MidtransPaymentMethod;
use App\Services\ReservationCapacityService;
use App\Services\FonnteService;
use App\Services\MidtransConfigService;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\DetailPesanan;
use App\Models\Customer;
use App\Models\Reservasi;
use App\Models\Pembayaran;
use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Midtrans\Snap;
use Midtrans\Transaction;
use Carbon\Carbon;

class BookingController extends Controller
{
    // private const RESERVATION_PAYMENT_WINDOW_MINUTES = 1;
    // private const RESERVATION_PAYMENT_WINDOW_MINUTES = 60;
    private function isMidtransTransactionNotFound(\Throwable $e): bool
    {
        return (int) $e->getCode() === 404
            || str_contains(strtolower($e->getMessage()), 'transaction doesn\'t exist')
            || str_contains(strtolower($e->getMessage()), 'transaction not found');
    }

    private function findPendingReservationForPhone(string $phone): ?Reservasi
    {
        $customer = $this->findCustomerByPhone($phone);

        if (!$customer) {
            return null;
        }

        return Reservasi::query()
            ->where('id_customer', $customer->id)
            ->whereHas('pesanan', function ($query) {
                $query->whereRaw("LOWER(TRIM(status)) = 'waiting'")
                    ->orWhere(function ($q) {
                        $q->whereRaw("LOWER(TRIM(status)) = 'pending'")
                          ->whereHas('pembayaran', function ($payment) {
                              $payment->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                                  ->whereRaw("LOWER(TRIM(status)) = 'pending'");
                          });
                    });
            })
            ->latest('id')
            ->first();
    }

    private function findCustomerByPhone(string $phone): ?Customer
    {
        $normalized = preg_replace('/\D+/', '', $phone);

        return Customer::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nomor_telp, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?", [$normalized])
            ->first();
    }

    private function reservationQuoteLines(array $draft): array
    {
        $lines = [];
        $quantities = $draft['menu_qty'] ?? [];
        $snapshots = $draft['menu_snapshot'] ?? [];
        $notes = $draft['menu_notes'] ?? [];

        foreach ($quantities as $menuId => $qty) {
            $lines[] = [
                'id' => $menuId,
                'qty' => $qty,
                'notes' => $notes[$menuId] ?? null,
                'snapshot' => $snapshots[(string) $menuId] ?? null,
            ];
        }

        return $lines;
    }

    private function reservationDraftErrors(array $draft): array
    {
        $minimumDate = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $validator = Validator::make($draft, [
            'nama' => 'required|string|min:3|max:50',
            'nomor_telp' => 'required|string|regex:/^[0-9\+\-\s]{9,20}$/',
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $minimumDate],
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'jml_org' => 'required|integer|min:1', // FIX UX-02
            'keterangan' => 'nullable|string|max:200',
        ], [
            'tanggal.after_or_equal' => 'Reservasi hanya dapat dibuat mulai besok (H+1).',
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
        ]);
        $errors = $validator->errors()->all();

        if (!$validator->errors()->has('jam_mulai') && !$validator->errors()->has('jam_selesai')) {
            if (($draft['jam_mulai'] ?? '') < '15:00' || ($draft['jam_mulai'] ?? '') > '23:59'
                || ($draft['jam_selesai'] ?? '') < '15:00' || ($draft['jam_selesai'] ?? '') > '23:59') {
                $errors[] = 'Jam mulai dan selesai harus berada dalam jam operasional 15.00–23.59 WIB.';
            }
        }

        return $errors;
    }

    private function syncReservationPayment(Reservasi $reservasi): void
    {
        $order = $reservasi->pesanan;

        if (!$order || strtolower(trim((string) $order->status)) !== 'pending') {
            return;
        }

        if (now()->greaterThanOrEqualTo($this->reservationDeadline($reservasi))) {
            $this->expireUnpaidReservation($reservasi);
            return;
        }

        $successfulDp = $order->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->whereRaw("LOWER(TRIM(status)) = 'success'")
            ->latest('id')
            ->first();

        if ($successfulDp) {
            if (now()->lt($this->reservationDeadline($reservasi))) {
                DB::transaction(function () use ($order) {
                    $lockedOrder = Pesanan::whereKey($order->id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        $lockedOrder
                        && strtolower(trim((string) $lockedOrder->status)) === 'pending'
                    ) {
                        $lockedOrder->update(['status' => 'Reserved']);
                    }
                }, 3);
            } else {
                $this->expireUnpaidReservation($reservasi);
            }

            return;
        }

        $payments = $order->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->latest('id')
            ->get();

        app(MidtransConfigService::class)->configure();

        foreach ($payments as $payment) {
            if (!$payment->midtrans_order_id) {
                continue;
            }

            try {
                $remote = Transaction::status($payment->midtrans_order_id);
                $status = strtolower((string) data_get($remote, 'transaction_status'));
                $method = MidtransPaymentMethod::from($remote);

                if ($method !== null) {
                    $payment->update(['metode' => $method]);
                }

                if ($this->midtransPaymentSucceeded($remote)) {
                    $this->recordReservationSuccess(
                        $reservasi,
                        $payment,
                        $remote,
                        $method
                    );

                    return;
                }

                if (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => $method ?? $payment->metode,
                    ]);
                }
            } catch (\Throwable $e) {
                $isTransactionNotFound =
                    (int) $e->getCode() === 404
                    || str_contains(
                        strtolower($e->getMessage()),
                        "transaction doesn't exist"
                    )
                    || str_contains(
                        strtolower($e->getMessage()),
                        'transaction not found'
                    );

                if ($isTransactionNotFound) {
                    // Normal sebelum customer memilih metode pembayaran di Snap.
                    // Jangan menganggap transaksi/pembayaran gagal dari respons 404 ini.
                    continue;
                }

                Log::warning('Gagal menyinkronkan status pembayaran reservasi.', [
                    'reservasi_id' => $reservasi->id,
                    'payment_id' => $payment->id,
                    'midtrans_order_id' => $payment->midtrans_order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->expireUnpaidReservation($reservasi);
    }

    private function midtransPaymentSucceeded(object|array $remote): bool
    {
        $status = strtolower((string) data_get($remote, 'transaction_status', ''));
        $fraud = strtolower((string) data_get($remote, 'fraud_status', ''));

        return $status === 'settlement'
            || ($status === 'capture' && $fraud === 'accept');
    }

    private function reservationPaymentWindowMinutes(): int
    {
        return max(1, (int) config('services.reservation.payment_window_minutes', 60)); // #live sesuaikan nilai default jika tanpa env
    }

    private function reservationDeadline(Reservasi $reservasi): Carbon
    {
        return $reservasi->paymentDeadline();
    }

    private function sendApprovalNotification(
        Reservasi $reservasi,
        string $flag,
        ?string $phone,
        string $message
    ): void {
        $claimed = Reservasi::query()
            ->whereKey($reservasi->id)
            ->where($flag, false)
            ->update([$flag => true]);

        if (!$claimed) {
            return;
        }

        if (!app(FonnteService::class)->send($phone, $message)) {
            Reservasi::whereKey($reservasi->id)->update([$flag => false]);
            Log::warning('WhatsApp notifikasi approval reservasi gagal.', [
                'reservasi_id' => $reservasi->id,
                'flag' => $flag,
            ]);
        }
    }

    private function notifyApprovalPending(Reservasi $reservasi): void
    {
        $reservasi->loadMissing(['customer', 'pesanan']);
        $detailUrl = route('customer.reservasi.detail', [
            'kode_pesanan' => $reservasi->pesanan->kode_pesanan,
        ]);
        $date = Carbon::parse($reservasi->tanggal, 'Asia/Jakarta')->format('d M Y');
        $schedule = "{$date}, {$reservasi->jam_mulai}–{$reservasi->jam_selesai} WIB";

        $customerMessage = "⏳ *Menunggu Persetujuan*\n\n"
            . "Halo *{$reservasi->customer->nama}*, permintaan reservasi Anda sedang kami tinjau.\n\n"
            . "📅 Tgl: *{$date}*\n"
            . "⏰ Jam: *" . date('H.i', strtotime($reservasi->jam_mulai)) . " - " . date('H.i', strtotime($reservasi->jam_selesai)) . " WIB*\n"
            . "👥 Jml: *{$reservasi->jml_org} Orang*\n\n"
            . "_DP belum ditagihkan hingga reservasi disetujui._\n\n"
            . "Cek status: <{$detailUrl}>";

        $adminMessage = "⚠️ *Persetujuan Diperlukan*\n\n"
            . "👤 *{$reservasi->customer->nama}* ({$reservasi->customer->nomor_telp})\n"
            . "📅 *{$date}* | *" . date('H.i', strtotime($reservasi->jam_mulai)) . " WIB*\n"
            . "👥 *{$reservasi->jml_org} Orang*\n"
            . "🏷️ Kode: *{$reservasi->pesanan->kode_pesanan}*\n\n"
            . "Tinjau: <" . route('admin.reservasi', ['id' => $reservasi->id]) . ">";

        $this->sendApprovalNotification(
            $reservasi,
            'approval_pending_customer_notif',
            $reservasi->customer->nomor_telp,
            $customerMessage
        );
        $this->sendApprovalNotification(
            $reservasi,
            'approval_pending_admin_notif',
            config('services.fonnte.phone'), // FIX SECURITY-04: gunakan config()
            $adminMessage
        );
    }

    public function notifyApprovalDecision(Reservasi $reservasi): void
    {
        $reservasi->loadMissing(['customer', 'pesanan']);
        $detailUrl = route('customer.reservasi.detail', [
            'kode_pesanan' => $reservasi->pesanan->kode_pesanan,
        ]);
        $date = Carbon::parse($reservasi->tanggal, 'Asia/Jakarta')->format('d M Y');

        if (strtolower(trim((string) $reservasi->pesanan?->status)) === 'pending') {
$message = "✅ *Reservasi Disetujui!*\n\n"
                . "Halo *{$reservasi->customer->nama}*, reservasi Anda telah disetujui.\n\n"
                . "📅 *{$date}* | *" . date('H.i', strtotime($reservasi->jam_mulai)) . " WIB*\n\n"
                . "Silakan buka detail reservasi dan tekan *Mulai Pembayaran DP*. Batas waktu pembayaran mulai dihitung setelah pembayaran dimulai.\n\n"
                . "Detail reservasi: <{$detailUrl}>";
        } else {
            $reason = $reservasi->rejection_reason ?: 'Silakan hubungi admin untuk informasi.';
            $message = "❌ *Reservasi Ditolak*\n\n"
                . "Halo *{$reservasi->customer->nama}*, mohon maaf reservasi Anda untuk tanggal *{$date}* belum dapat kami setujui.\n\n"
                . "📝 Alasan: _{$reason}_\n\n"
                . "Hubungi admin (085168170080) untuk info lebih lanjut.\n"
                . "Detail: <{$detailUrl}>";
        }

        $this->sendApprovalNotification(
            $reservasi,
            'approval_decision_customer_notif',
            $reservasi->customer->nomor_telp,
            $message
        );
    }

    private function reservationMidtransExpiry(Reservasi $reservasi): array
    {
        $remainingSeconds = $this->reservationDeadline($reservasi)->getTimestamp()
            - now()->getTimestamp();

        if ($remainingSeconds <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'Waktu pembayaran reservasi telah habis.',
            ]);
        }

        return [
            'start_time' => now()
                ->timezone(config('app.timezone'))
                ->format('Y-m-d H:i:s O'),
            'unit' => 'minute',
            'duration' => max(1, (int) ceil($remainingSeconds / 60)),
        ];
    }

    private function recordReservationSuccess(
        Reservasi $reservasi,
        Pembayaran $payment,
        object|array $remote,
        ?string $method
    ): bool {
        $transactionTime = data_get($remote, 'transaction_time');
        $deadline = $this->reservationDeadline($reservasi);

        $paidBeforeDeadline = now()->lt($deadline);

        return DB::transaction(function () use (
            $reservasi,
            $payment,
            $method,
            $paidBeforeDeadline,
            $transactionTime
        ) {
            $lockedReservation = Reservasi::whereKey($reservasi->id)
                ->lockForUpdate()
                ->first();

            $lockedOrder = $lockedReservation
                ? Pesanan::whereKey($lockedReservation->id_pesanan)
                    ->lockForUpdate()
                    ->first()
                : null;

            $lockedPayment = Pembayaran::whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedReservation || !$lockedOrder || !$lockedPayment) {
                return false;
            }

            $lockedPayment->update([
                'status' => 'Success',
                'metode' => $method ?? $lockedPayment->metode,
            ]);

            $latestDpId = $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                ->latest('id')
                ->value('id');

            $validOrder = strtolower(trim((string) $lockedOrder->status)) === 'pending'
                && (int) $latestDpId === (int) $lockedPayment->id;

            if (!$validOrder) {
                Log::critical('Pembayaran reservasi berhasil, tetapi pesanan sudah tidak berstatus pending.', [
                    'reservasi_id' => $lockedReservation->id,
                    'payment_id' => $lockedPayment->id,
                    'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                    'transaction_time' => $transactionTime,
                ]);

                return false;
            }

            if (!$paidBeforeDeadline) {
                // Midtrans sudah mengonfirmasi pembayaran. Jangan biarkan proses
                // kedaluwarsa mengubah pesanan yang sudah dibayar menjadi Cancelled.
                Log::warning('Pembayaran reservasi terkonfirmasi setelah batas waktu lokal.', [
                    'reservasi_id' => $lockedReservation->id,
                    'payment_id' => $lockedPayment->id,
                    'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                    'transaction_time' => $transactionTime,
                ]);
            }

            $lockedOrder->update(['status' => 'Reserved']);

            return true;
        }, 3);
    }

    private function expireUnpaidReservation(Reservasi $reservasi): void
    {
        if (strtolower(trim((string) $reservasi->pesanan?->status)) === 'waiting') {
            return;
        }

        if (now()->lt($this->reservationDeadline($reservasi))) {
            return;
        }

        $order = $reservasi->pesanan;

        if (!$order || strtolower(trim((string) $order->status)) !== 'pending') {
            return;
        }

        app(MidtransConfigService::class)->configure();

        $payments = $order->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->latest('id')
            ->get();
        $paymentStateUncertain = false;

        foreach ($payments as $payment) {
            if (!$payment->midtrans_order_id) {
                continue;
            }

            try {
                $remote = Transaction::status($payment->midtrans_order_id);
                $remoteStatus = strtolower((string) data_get(
                    $remote,
                    'transaction_status',
                    ''
                ));
                $method = MidtransPaymentMethod::from($remote);

                if ($this->midtransPaymentSucceeded($remote)) {
                    if ($this->recordReservationSuccess($reservasi, $payment, $remote, $method)) {
                        return;
                    }

                    continue;
                }

                if (in_array($remoteStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => $method ?? $payment->metode,
                    ]);

                    continue;
                }

                // Tutup transaksi yang masih aktif saat deadline reservasi tercapai.
                Transaction::cancel($payment->midtrans_order_id);

                $verified = Transaction::status($payment->midtrans_order_id);
                $verifiedStatus = strtolower((string) data_get(
                    $verified,
                    'transaction_status',
                    ''
                ));
                $verifiedMethod = MidtransPaymentMethod::from($verified);

                if ($this->midtransPaymentSucceeded($verified)) {
                    if ($this->recordReservationSuccess(
                        $reservasi,
                        $payment,
                        $verified,
                        $verifiedMethod
                    )) {
                        return;
                    }

                    continue;
                }

                if (in_array($verifiedStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $payment->update([
                        'status' => 'Failed',
                        'metode' => $verifiedMethod ?? $payment->metode,
                    ]);
                } else {
                    $paymentStateUncertain = true;
                    Log::warning('Status transaksi reservasi belum final setelah permintaan pembatalan.', [
                        'reservasi_id' => $reservasi->id,
                        'payment_id' => $payment->id,
                        'midtrans_order_id' => $payment->midtrans_order_id,
                        'status' => $verifiedStatus,
                    ]);
                }
            } catch (\Throwable $e) {
                if ($this->isMidtransTransactionNotFound($e)) {
                    // Belum memilih metode: belum ada transaksi Midtrans yang perlu dicancel.
                    $payment->update(['status' => 'Failed']);
                    continue;
                }

                Log::warning('Gagal menutup transaksi reservasi yang melewati deadline.', [
                    'reservasi_id' => $reservasi->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                $paymentStateUncertain = true;
            }
        }

        if ($paymentStateUncertain) {
            return;
        }

        DB::transaction(function () use ($reservasi) {
            $lockedReservation = Reservasi::whereKey($reservasi->id)
                ->lockForUpdate()
                ->first();

            $lockedOrder = $lockedReservation
                ? Pesanan::whereKey($lockedReservation->id_pesanan)
                    ->lockForUpdate()
                    ->first()
                : null;

            if (
                !$lockedReservation
                || !$lockedOrder
                || strtolower(trim((string) $lockedOrder->status)) !== 'pending'
                || now()->lt($this->reservationDeadline($lockedReservation))
            ) {
                return;
            }

            $lockedOrder->update(['status' => 'Cancelled']);

            $lockedOrder->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->update(['status' => 'Failed']);
        }, 3);
    }

    public function expirePendingReservations(): void
    {
        Reservasi::whereHas('pesanan', function ($query) {
                $query->whereRaw("LOWER(TRIM(status)) = 'pending'");
            })
        ->chunkById(100, function ($reservations) {
                foreach ($reservations as $reservation) {
                    /** @var Reservasi $reservation */
                    $this->expireUnpaidReservation($reservation);
                }
            });
    }

    // RESERVASI
    public function reservasiForm(Request $request)
    {
        // Menu open ATAU (menu closed tapi reservable)
        // $menus = Menu::where('status', 'open')
        //             ->orWhere('is_reservable', true)
        //             ->orderByDesc('is_bestseller')
        //             ->get();
        $menus = Menu::Where('is_reservable', true)
                    ->orderByDesc('is_bestseller')
                    ->get();

        session([
            'reservation_form_menu_quote' => app(MenuQuoteService::class)->capture($menus),
        ]);

        // Ambil data pre-fill dari query string (jika diisi dari beranda)
        // $prefill = [
        //     'tanggal'     => $request->query('tanggal'),
        //     'jam_mulai'   => $request->query('jam_mulai'),
        //     'jam_selesai' => $request->query('jam_selesai'),
        //     'jml_org'   => $request->query('jml_org'),
        // ];
        $draft = session('reservation_draft', []);
        $draft = is_object($draft) ? get_object_vars($draft) : (array) $draft;

        $prefill = array_merge([
            'nama' => '',
            'nomor_telp' => '',
            'tanggal' => '',
            'jam_mulai' => '',
            'jam_selesai' => '',
            'jml_org' => '',
            'keterangan' => '',
        ], array_intersect_key($draft, array_flip([
            'nama',
            'nomor_telp',
            'tanggal',
            'jam_mulai',
            'jam_selesai',
            'jml_org',
            'keterangan',
        ])));

        // Data pengecekan ketersediaan dari landing page mengalahkan jadwal draft lama.
        foreach (['tanggal', 'jam_mulai', 'jam_selesai', 'jml_org'] as $key) {
            if ($request->query->has($key)) {
                $prefill[$key] = $request->query($key);
            }
        }

        $requiresApproval = (int) ($prefill['jml_org'] ?? 0) > 10;

        return view('customer.reservasi', compact('menus', 'prefill', 'requiresApproval'));
    }

    public function reservationMenu()
    {
        // Menu open ATAU (menu closed tapi reservable)
        $menus = Menu::Where('is_reservable', true)
                    ->orderByDesc('is_bestseller')
                    ->get();
        $categories = Menu::where('status', 'open')->distinct()->orderBy('kategori')->pluck('kategori');

        return view('customer.reservation_menu', compact('menus', 'categories'));
    }

    public function reservationMenuDetail(int $id)
    {
        $menu = Menu::where('is_reservable', true)->findOrFail($id);
        return view('customer.reservation_menu_detail', compact('menu'));
    }

    // TAHAP 1: FORM KE SESSION DRAFT (BELUM MASUK DB)
    public function reservasiStore(Request $request)
    {
        $minimumReservationDate = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $validated = $request->validate([
            'nama'         => 'required|string|min:3|max:50',
            'nomor_telp'   => 'required|string|regex:/^[0-9\+\-\s]{9,20}$/',
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $minimumReservationDate],
            'jam_mulai'    => 'required|date_format:H:i',
            'jam_selesai'  => 'required|date_format:H:i|after:jam_mulai',
            'jml_org'      => 'required|integer|min:1', // FIX UX-02: batasan maksimum
            'keterangan'   => 'nullable|string|max:200',
            'menu_qty' => ['nullable', 'array'],
            'menu_qty.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'menu_price' => ['nullable', 'array'], 
            'menu_price.*' => ['nullable', 'integer'],
            'menu_notes' => ['nullable', 'array'],
            'menu_notes.*' => ['nullable', 'string', 'max:200'],
        ], ['jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.']);

        if ($validated['jam_mulai'] < '15:00' || $validated['jam_mulai'] > '23:59'
            || $validated['jam_selesai'] < '15:00' || $validated['jam_selesai'] > '23:59') {
            return back()
                ->withErrors(['jam_mulai' => 'Jam mulai dan selesai harus berada dalam jam operasional 15.00–23.59 WIB.'])
                ->withInput();
        }

        $capacity = (int) $validated['jml_org'] > 10 ? ['available' => true] : app(ReservationCapacityService::class)->check(
            $validated['tanggal'], $validated['jam_mulai'], $validated['jam_selesai'], (int) $validated['jml_org']
        );

        if (!$capacity['available']) {
            return back()
                ->withErrors([
                    // 'kapasitas' => 'Kapasitas tidak mencukupi. Tersedia '
                    'kapasitas' => 'Kapasitas tidak mencukupi.',
                    //     . $capacity['available_guests'] . ' kursi dan '
                    //     . $capacity['available_tables'] . ' meja aktif; reservasi ini memerlukan estimasi '
                    //     . $capacity['estimated_tables_needed'] . ' meja.',
                ])
                ->withInput();
        }

        // 4. Proses Menu & Harga
        $menuQty = collect($validated['menu_qty'] ?? [])
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn ($qty) => $qty > 0)
            ->all();

        $catalogQuote = session('reservation_form_menu_quote', []);
        $selectedMenuIds = array_map('strval', array_keys($menuQty));

        foreach ($selectedMenuIds as $menuId) {
            if (!isset($catalogQuote[$menuId])) {
                return back()
                    ->withErrors(['menu' => 'Pilihan menu sudah kedaluwarsa. Muat ulang form dan pilih menu kembali.'])
                    ->withInput();
            }
        }

        // Snapshot ini adalah harga saat menu ditampilkan, bukan harga terbaru dari DB.
        $menuSnapshot = [];
        $totalHargaMenu = 0;

        foreach ($menuQty as $menuId => $qty) {
            $snapshot = $catalogQuote[(string) $menuId];
            $menuSnapshot[(string) $menuId] = $snapshot;
            $totalHargaMenu += (int) $snapshot['harga'] * $qty;
        }

        // =========================================================================
        // KONDISI 2 (AKTIF): WAJIB PESAN MENU MINIMAL Rp 50.000 SAAT BOOKING
        // =========================================================================
        if ($totalHargaMenu < 50000) {
            return back()
                ->withErrors(['menu' => 'Minimal total pemesanan menu adalah Rp 50.000 untuk dapat melanjutkan reservasi.'])
                ->withInput();
        }

        /*
        // =========================================================================
        // KONDISI 1 (KOMENTAR): BEBAS PESAN MENU NANTI DI LOKASI, DP TETAP Rp 50.000
        // =========================================================================
        // Jika Anda ingin mengubah sistem ke Kondisi 1, berikan tanda komentar (//) 
        // pada 3 baris kode KONDISI 2 di atas. Sistem akan otomatis membiarkan 
        // form tersubmit meskipun $totalHargaMenu bernilai 0.
        */

        $menuQty = array_filter($validated['menu_qty'] ?? [], fn($qty) => (int) $qty > 0);

        session()->put('reservation_draft', [
            'nama'        => $validated['nama'],
            'nomor_telp'  => $validated['nomor_telp'],
            'tanggal'     => $validated['tanggal'],
            'jam_mulai'   => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'jml_org'     => $validated['jml_org'],
            'keterangan'  => $validated['keterangan'] ?? null,
            'menu_qty' => $menuQty,
            'menu_notes' => $validated['menu_notes'] ?? [],
            'menu_snapshot' => $menuSnapshot,
        ]);

        return redirect()->route('customer.reservasi.payment');
    }

    // TAHAP 2: REVIEW (CHECKOUT) SEBELUM BAYAR
    public function reservasiPayment()
    {
        $draft = session('reservation_draft');

        if (!$draft) {
            return redirect()->route('customer.reservasi.form')
                ->with('error', 'Silakan isi form reservasi terlebih dahulu.');
        }

        $draftErrors = $this->reservationDraftErrors((array) $draft);
        if ($draftErrors) {
            return redirect()->route('customer.reservasi.form')
                ->withErrors($draftErrors)->withInput((array) $draft);
        }

        // Konversi draft array ke object agar view bisa pakai $draft->nama, $draft->tanggal, dst.
        $draftObj = (object) $draft;

        // Data menu dari draft
        $menuItems = [];
        $totalHargaMenu = 0;
        $hasMenuChanges = false;
        $menuChangeMessages = [];

        $capacity = (int) $draft['jml_org'] > 10 ? ['available' => true] : app(ReservationCapacityService::class)->check(
            $draft['tanggal'],
            $draft['jam_mulai'],
            $draft['jam_selesai'],
            (int) $draft['jml_org']
        );

        if (!$capacity['available']) {
            $menuChangeMessages[] = 'Kapasitas jadwal tidak mencukupi. Silahkan hubungi kami via WhatsApp.';
            // $menuChangeMessages[] = 'Kapasitas jadwal tidak mencukupi: tersisa '
                // . $capacity['available_guests'] . ' kursi dan '
                // . $capacity['available_tables'] . ' meja aktif.';
        }

        $quoteReview = app(MenuQuoteService::class)->review(
            $this->reservationQuoteLines($draft),
            true
        );

        $menuItems = $quoteReview['items'];
        $totalHargaMenu = $quoteReview['total'];
        $menuChangeMessages = array_values(array_unique(array_merge(
            $menuChangeMessages,
            $quoteReview['messages']
        )));
        $hasMenuChanges = count($menuChangeMessages) > 0;

        if ($totalHargaMenu < 50000) {
            $hasMenuChanges = true;
            $menuChangeMessages[] = 'Total menu reservasi harus minimal Rp 50.000.';
        }

        // Cek apakah customer sudah pernah membuat reservasi yang sama dan masih Pending
        // $existingReservasiId = null;
        // $customer = Customer::where('nomor_telp', $draft['nomor_telp'])->first();
        // if ($customer) {
        //     $existing = Reservasi::where('id_customer', $customer->id)
        //         ->whereDate('tanggal', $draft['tanggal'])
        //         ->where('jam_mulai', $draft['jam_mulai'])
        //         ->whereHas('pesanan', function($q){
        //             $q->where('status', 'Pending');
        //         })
        //         ->where('created_at', '>=', now()->subMinutes(60)) // cari dalam 60 menit terakhir
        //         ->first();
        //     if ($existing) {
        //         $existingReservasiId = $existing->id;
        //     }
        // }

        $pendingReservasi = $this->findPendingReservationForPhone($draft['nomor_telp']);

        if ($pendingReservasi) {
            $this->expireUnpaidReservation($pendingReservasi);
            $pendingReservasi = $this->findPendingReservationForPhone($draft['nomor_telp']);
        }

        return view('customer.reservasi_payment', compact(
            'draftObj',
            'menuItems',
            'totalHargaMenu',
            // 'pb',
            // 'totalBayar',
            // 'totalDP',
            // 'sisaTagihan',
            // 'existingReservasiId', 
            'hasMenuChanges', 
            'menuChangeMessages',
            'pendingReservasi'
        ));
    }

    // TAHAP 3: EKSEKUSI (SIMPAN DB + DAPATKAN QRIS MIDTRANS)
    public function createReservationTransaction(Request $request)
    {
        $draft = session('reservation_draft');

        if (!$draft) {
            return redirect()->route('customer.reservasi.form')
                ->with('error', 'Data reservasi tidak ditemukan. Silakan ulangi form.');
        }

        $draftErrors = $this->reservationDraftErrors((array) $draft);
        if ($draftErrors) {
            Log::notice('Draft reservasi ditolak saat membuat transaksi karena validasi jadwal/data.', [
                'phone' => $draft['nomor_telp'] ?? null,
                'errors' => $draftErrors,
            ]);
            return redirect()->route('customer.reservasi.form')
                ->withErrors($draftErrors)->withInput((array) $draft);
        }

        $menuQty = collect($draft['menu_qty'] ?? [])
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn ($qty) => $qty > 0)
            ->all();

        $menuSnapshot = $draft['menu_snapshot'] ?? [];

        $quoteReview = app(MenuQuoteService::class)->review(
            $this->reservationQuoteLines($draft),
            true
        );

        if ($quoteReview['changed']) {
            Log::notice('Checkout reservasi dihentikan karena menu berubah.', [
                'phone' => $draft['nomor_telp'] ?? null,
                'messages' => $quoteReview['messages'],
            ]);
            return redirect()
                ->route('customer.reservasi.payment')
                ->with('error', 'Harga atau status menu berubah. Periksa perubahan sebelum melanjutkan.')
                ->with('menu_change_messages', $quoteReview['messages']);
        }

        $totalHargaMenu = (int) $quoteReview['total'];

        if ($totalHargaMenu < 50000) {
            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Total menu reservasi harus minimal Rp 50.000.');
        }

        $validator = Validator::make($request->all(), [
            'nominal_dp' => [
                'required',
                'integer',
                'min:50000',
                'max:' . $totalHargaMenu,
            ],
        ]);

        if ($validator->fails()) {
            Log::notice('Nominal DP reservasi tidak lolos validasi.', [
                'phone' => $draft['nomor_telp'] ?? null,
                'errors' => $validator->errors()->all(),
            ]);
            return redirect()->route('customer.reservasi.payment')
                ->withErrors($validator)
                ->withInput();
        }

        $payAmount = (int) $validator->validated()['nominal_dp'];
        $requiresApproval = (int) ($draft['jml_org'] ?? 0) > 10;

        if ($payAmount > $totalHargaMenu) {
            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Nominal DP tidak boleh melebihi total harga menu.');
        }

        $customer = $this->findCustomerByPhone($draft['nomor_telp']);
        if (!$customer) {
            $customer = Customer::create([
                'nomor_telp' => preg_replace('/\D+/', '', $draft['nomor_telp']),
                'nama' => $draft['nama'],
            ]);
        }

        try {
            $reservasi = DB::transaction(
                function () use ($draft, $customer, $menuQty, $menuSnapshot, $totalHargaMenu, $payAmount, $requiresApproval) {
                    return $this->persistReservation(
                        $draft,
                        (int) $customer->id,
                        $menuQty,
                        $menuSnapshot,
                        $totalHargaMenu,
                        $payAmount,
                        $requiresApproval
                    );
                },
                3
            );
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->all();
            Log::notice('Reservasi ditolak saat validasi akhir sebelum dibuat.', [
                'phone' => $draft['nomor_telp'] ?? null,
                'errors' => $messages,
            ]);

            return redirect()
                ->route('customer.reservasi.payment')
                ->with('error', 'Data reservasi berubah atau tidak dapat diproses. Periksa kembali.')
                ->with('menu_change_messages', $messages);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat data reservasi.', [
                'phone' => $draft['nomor_telp'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Reservasi gagal diproses. Silakan coba kembali.');
        }

        // Nomor telepon menjadi identitas customer; simpan nama terbaru dari reservasi ini.
        $customer->update(['nama' => trim((string) $draft['nama'])]);

        if ($requiresApproval) {
            $this->notifyApprovalPending($reservasi->fresh(['customer', 'pesanan']));
            $successMessage = 'Permintaan reservasi skala besar dikirim untuk persetujuan admin. DP belum ditagihkan.';
        } else {
            $successMessage = 'Reservasi berhasil dibuat. Batas waktu pembayaran sudah dimulai; tekan Mulai Pembayaran DP untuk membuka Midtrans.';
        }

        return redirect()
            ->route('customer.reservasi.detail', ['kode_pesanan' => $reservasi->pesanan->kode_pesanan])
            ->with('success', $successMessage);
    }

    private function persistReservation(
        array $draft,
        int $customerId,
        array $menuQty,
        array $menuSnapshot,
        int $totalHargaMenu,
        int $payAmount,
        bool $requiresApproval = false
    ): Reservasi {
        // Lock meja dalam urutan konsisten agar booking dan perubahan status meja terserialisasi.
        $lockedTables = Meja::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'is_active']);

        if ($lockedTables->where('is_active', true)->isEmpty()) {
            throw ValidationException::withMessages([
                'kapasitas' => 'Belum ada meja aktif untuk reservasi.',
            ]);
        }

        // Serialisasi pembuatan reservasi untuk nomor telepon yang sama.
        Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();

        $pendingReservation = Reservasi::query()
            ->where('id_customer', $customerId)
            ->whereHas('pesanan', function ($query) {
                $query->whereRaw("LOWER(TRIM(status)) = 'waiting'")
                    ->orWhere(function ($pending) {
                        $pending->whereRaw("LOWER(TRIM(status)) = 'pending'")
                            ->where('pesanan.created_at', '>=', now()->subMinutes($this->reservationPaymentWindowMinutes()))
                            ->whereHas('pembayaran', function ($payment) {
                                $payment->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                                    ->whereRaw("LOWER(TRIM(status)) = 'pending'");
                            });
                    });
            })
            ->lockForUpdate()
            ->first();

        if ($pendingReservation) {
            throw ValidationException::withMessages([
                'reservasi' => 'Nomor telepon ini masih memiliki reservasi yang menunggu persetujuan atau pembayaran. Lanjutkan atau batalkan reservasi lama terlebih dahulu.',
            ]);
        }

        $menusDb = Menu::whereIn('id', array_map('intval', array_keys($menuQty)))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $verifiedTotal = 0;

        foreach ($menuQty as $menuId => $qty) {
            $menu = $menusDb->get((int) $menuId);
            $snapshot = $menuSnapshot[(string) $menuId] ?? null;

            if (!$menu || !$menu->is_reservable) {
                throw ValidationException::withMessages([
                    'menu' => 'Ada menu yang baru saja tidak tersedia untuk reservasi.',
                ]);
            }

            if (
                !is_array($snapshot)
                || (int) round((float) $menu->harga) !== (int) ($snapshot['harga'] ?? -1)
            ) {
                throw ValidationException::withMessages([
                    'menu' => "Harga menu {$menu->nama} berubah. Periksa kembali sebelum membayar.",
                ]);
            }

            $verifiedTotal += (int) round((float) $menu->harga) * (int) $qty;
        }

        if ($verifiedTotal < 50000 || $verifiedTotal !== $totalHargaMenu) {
            throw ValidationException::withMessages([
                'menu' => 'Total menu berubah atau kurang dari minimum Rp 50.000.',
            ]);
        }

        if ($payAmount < 50000 || $payAmount > $verifiedTotal) {
            throw ValidationException::withMessages([
                'nominal_dp' => 'Nominal DP harus minimal Rp 50.000 dan tidak melebihi total menu.',
            ]);
        }

        $capacity = (int) $draft['jml_org'] > 10 ? ['available' => true] : app(ReservationCapacityService::class)->check(
            $draft['tanggal'],
            $draft['jam_mulai'],
            $draft['jam_selesai'],
            (int) $draft['jml_org']
        );

        if (!$capacity['available']) {
            throw ValidationException::withMessages([
                'kapasitas' => 'Kapasitas jadwal berubah. Silahkan pilih jadwal lain. ',
                // 'kapasitas' => 'Kapasitas jadwal berubah. Tersedia '
                //     . $capacity['available_guests'] . ' kursi dan '
                //     . $capacity['available_tables'] . ' meja aktif. Silakan pilih jadwal lain.',
            ]);
        }

        $pesanan = Pesanan::create([
            'kode_pesanan' => 'RSV-' . now()->format('md-Hi') . '-' . random_int(1000, 9999),
            'id_user' => null,
            'id_meja' => null,
            'total_harga' => $verifiedTotal,
            'status' => $requiresApproval ? 'Waiting' : 'Pending',
        ]);

        $reservasi = Reservasi::create([
            'id_customer' => $customerId,
            'id_pesanan' => $pesanan->id,
            'tanggal' => $draft['tanggal'],
            'jam_mulai' => $draft['jam_mulai'],
            'jam_selesai' => $draft['jam_selesai'],
            'jml_org' => $draft['jml_org'],
            'keterangan' => $draft['keterangan'] ?? null,
        ]);

        foreach ($menuQty as $menuId => $qty) {
            $menu = $menusDb->get((int) $menuId);

            DetailPesanan::create([
                'id_pesanan' => $pesanan->id,
                'id_menu' => $menu->id,
                'jumlah' => (int) $qty,
                'subtotal' => (int) round((float) $menu->harga) * (int) $qty,
                'catatan' => $draft['menu_notes'][$menuId] ?? null,
            ]);
        }

        // Simpan tagihan DP saat reservasi dibuat. Midtrans baru dibuat saat customer
        // menekan tombol mulai pembayaran, setelah reservasi besar disetujui.
        Pembayaran::create([
            'id_pesanan' => $pesanan->id,
            'jenis' => 'DP',
            'metode' => 'midtrans',
            'nominal' => $payAmount,
            'midtrans_order_id' => null,
            'snap_token' => null,
            'status' => 'Pending',
        ]);

        return $reservasi;
    }

    // Halaman Tujuan Setelah Pelanggan Menutup Pop-up Midtrans
    public function reservasiPaymentSuccess(Request $request)
    {
        $kodePesanan = $request->query('kode_pesanan');
        
        if ($kodePesanan) {
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $kodePesanan])
                ->with('success', 'Pembayaran sedang diproses. Silakan cek status reservasi Anda secara berkala.');
        }

        return redirect()->route('customer.reservasi.form');
    }

    public function reservasiDetail(string $kode_pesanan)
    {
        $reservasi = Reservasi::with([
            'customer',
            'detailReservasi.meja',
            'pesanan.detailPesanan.menu',
            'pesanan.pembayaran',
        ])->whereHas('pesanan', function ($query) use ($kode_pesanan) {
            $query->where('kode_pesanan', $kode_pesanan);
        })->firstOrFail();

        $this->syncReservationPayment($reservasi);
        // $this->expireUnpaidReservation($reservasi);

        $reservasi->refresh()->load(['pesanan.pembayaran']);

        $reservationExpiresAt = $this->reservationDeadline($reservasi)->timestamp * 1000;

        $pesanan = $reservasi->pesanan;

        $statusPesanan = strtolower(trim((string) ($pesanan?->status ?? 'pending')));

        $paymentDp = $pesanan?->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->latest('id')
            ->first();

        $statusPembayaranDp = strtolower(trim((string) ($paymentDp?->status ?? '')));
        $reservationFinished = in_array($statusPesanan, ['reserved', 'confirmed'], true);
        $reservationUrl = route('customer.reservasi.detail', ['kode_pesanan' => $pesanan->kode_pesanan]);

        $reservationSteps = [
            ['label' => 'Pembayaran Berhasil', 'icon' => '💳'],
            ['label' => 'Reservasi Diproses', 'icon' => '🗓️'],
            ['label' => 'Reservasi Selesai', 'icon' => '✓'],
        ];

        $stepAktif = match ($statusPesanan) {
            'reserved' => 1,
            'confirmed' => 2,
            'completed' => 3,
            default => 0,
        };

        $totalHarga = (float) ($pesanan?->total_harga ?? 0);

        // Ambil nominal DP yang ditetapkan untuk reservasi ini
        $nominalDp = (int) ($pesanan?->pembayaran
            ?->filter(fn ($p) => strtolower(trim((string) $p->jenis)) === 'dp')
            ->sortByDesc('id')
            ->first()?->nominal ?? 50000);

        // Hitung DP yang sudah sukses dibayar (jika ada)
        $dpSuccess = (float) ($pesanan?->pembayaran
            ?->filter(fn ($p) => strtolower(trim((string) $p->jenis)) === 'dp' && strtolower(trim((string) $p->status)) === 'success')
            ->sum('nominal') ?? 0);

        // Sisa pelunasan riil di resto:
        // Baik saat pembayaran DP masih pending (dalam proses) maupun sudah success (lunas),
        // sisa tagihan yang riil dilunasi di resto adalah Total Menu dikurangi DP.
        $pengurangDp = $dpSuccess > 0 ? $dpSuccess : $nominalDp;
        $sisaTagihan = max(0, $totalHarga - $pengurangDp);

        $paymentNotStarted = !$paymentDp
            || (!$paymentDp->midtrans_order_id && !$paymentDp->snap_token
                && strtolower(trim((string) $paymentDp->status)) === 'pending');
        $bisaUbahMenu = in_array($statusPesanan, ['pending', 'waiting'], true)
            && $paymentNotStarted;

        $menus = Menu::Where('is_reservable', true)
                    ->orderByDesc('is_bestseller')
                    ->get();

        $paymentStatus = strtolower(trim((string) ($paymentDp?->status ?? '')));
        $withinPaymentWindow = $paymentDp && now()->lt($this->reservationDeadline($reservasi));
        $snapToken = $statusPembayaranDp === 'pending'
            ? $paymentDp?->snap_token
            : null;

        $canRetryPayment = $statusPesanan === 'pending'
            && $statusPembayaranDp === 'failed'
            && $withinPaymentWindow;
        $canStartPayment = $statusPesanan === 'pending'
            && (!$paymentDp || (
                strtolower(trim((string) $paymentDp->status)) === 'pending'
                && !$paymentDp->snap_token
            ))
            && $withinPaymentWindow;

        $returnUrl = route('customer.reservasi.detail', ['kode_pesanan' => $pesanan->kode_pesanan]);

        return view('customer.reservasi_detail', compact(
            'reservasi',
            'pesanan',
            'snapToken',
            'returnUrl',
            'bisaUbahMenu',
            'sisaTagihan',
            'menus',
            'totalHarga',
            'nominalDp',
            'canRetryPayment',
            'canStartPayment',
            'reservationExpiresAt',
            'statusPesanan',
            'statusPembayaranDp',
            'paymentDp',
            'reservationFinished',
            'reservationUrl',
            'reservationSteps',
            'stepAktif'
        ));
    }

    public function reservasiUpdateMenu(Request $request, $id)
    {
        $initialReservation = Reservasi::with(['customer', 'pesanan'])->findOrFail($id);

        $this->syncReservationPayment($initialReservation);
        $initialReservation->refresh()->load('pesanan');

        $validated = $request->validate([
                'menu_qty' => ['nullable', 'array'],
                'menu_qty.*' => ['nullable', 'integer', 'min:0', 'max:100'],
                'menu_notes' => ['nullable', 'array'],
                'menu_notes.*' => ['nullable', 'string', 'max:200'],
            ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $reservasi = Reservasi::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $pesanan = Pesanan::whereKey($reservasi->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                now()->greaterThanOrEqualTo($this->reservationDeadline($reservasi))
                || !in_array(strtolower(trim((string) $pesanan->status)), ['pending', 'waiting'], true)
            ) {
                if (strtolower(trim((string) $pesanan->status)) === 'pending') {
                    $pesanan->update(['status' => 'Cancelled']);
                    $pesanan->pembayaran()
                        ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                        ->update(['status' => 'Failed']);
                }

                return ['error' => 'Waktu mengubah menu atau reservasi telah berakhir.', 'kode' => $pesanan->kode_pesanan];
            }

            $dpPayment = $pesanan->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                ->whereRaw("LOWER(TRIM(status)) IN ('pending', 'failed')")
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($dpPayment && (
                strtolower(trim((string) $dpPayment->status)) !== 'pending'
                || $dpPayment->midtrans_order_id
                || $dpPayment->snap_token
            )) {
                return ['error' => 'Pembayaran DP tidak dapat diedit; muat ulang halaman untuk melihat status terbaru.', 'kode' => $pesanan->kode_pesanan];
            }

            $activeMenuQty = collect($validated['menu_qty'] ?? [])
                ->map(fn ($qty) => (int) $qty)
                ->filter(fn ($qty) => $qty > 0)
                ->all();

            // FIX UX-04: tolak form jika tidak ada menu yang dipilih
            if (empty($activeMenuQty)) {
                return ['validation_error' => 'Pilih minimal satu menu untuk reservasi.', 'kode' => $pesanan->kode_pesanan];
            }

            $menuIds = array_map('intval', array_keys($activeMenuQty));
            $menus = Menu::whereIn('id', $menuIds)
                ->where('is_reservable', true)
                ->get()
                ->keyBy('id');

            if ($menus->count() !== count($menuIds)) {
                return ['validation_error' => 'Ada menu reservasi yang tidak valid.', 'kode' => $pesanan->kode_pesanan];
            }

            $totalHarga = 0;

            foreach ($activeMenuQty as $menuId => $qty) {
                $totalHarga += (int) round((float) $menus->get($menuId)->harga) * $qty;
            }

            $minimumTotal = max(50000, (int) ($dpPayment?->nominal ?? 50000));

            if ($totalHarga < $minimumTotal) {
                return ['validation_error' => 'Total menu tidak boleh kurang dari DP sebesar Rp '
                    . number_format($minimumTotal, 0, ',', '.') . '.', 'kode' => $pesanan->kode_pesanan];
            }

            DetailPesanan::where('id_pesanan', $pesanan->id)->delete();

            foreach ($activeMenuQty as $menuId => $qty) {
                $menu = $menus->get($menuId);

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id,
                    'id_menu' => $menuId,
                    'jumlah' => $qty,
                    'subtotal' => (int) round((float) $menu->harga) * $qty,
                    'catatan' => $validated['menu_notes'][$menuId] ?? null,
                ]);
            }

            $pesanan->update(['total_harga' => $totalHarga]);

            return ['success' => true, 'kode' => $pesanan->kode_pesanan];
        });

        $detailRoute = route('customer.reservasi.detail', ['kode_pesanan' => $result['kode']]);
        if (isset($result['validation_error'])) {
            return redirect()->back()->withErrors(['menu' => $result['validation_error']])->withInput();
        }
        if (isset($result['error'])) {
            return redirect()->to($detailRoute)->with('error', $result['error']);
        }

        return redirect()->to($detailRoute)->with('success', 'Menu pre-order berhasil disesuaikan.');
    }

    public function cancelPendingReservation(Request $request, int $id)
    {
        $draft = session('reservation_draft');

        if (!$draft || empty($draft['nomor_telp'])) {
            return redirect()->route('customer.reservasi.form')
                ->with('error', 'Sesi reservasi tidak ditemukan. Silakan mulai kembali.');
        }

        $reservasi = Reservasi::with(['customer', 'pesanan.pembayaran'])->findOrFail($id);

        if (
            !$reservasi->customer
            || preg_replace('/\D+/', '', (string) $reservasi->customer->nomor_telp)
                !== preg_replace('/\D+/', '', (string) $draft['nomor_telp'])
        ) {
            abort(403);
        }

        $this->expireUnpaidReservation($reservasi);
        $reservasi->refresh()->load('pesanan.pembayaran');

        if (!in_array(strtolower(trim((string) $reservasi->pesanan?->status)), ['pending', 'waiting'], true)) {
            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Reservasi tersebut tidak lagi berstatus menunggu persetujuan atau pembayaran.');
        }

        try {
            app(MidtransConfigService::class)->configure();

            foreach ($reservasi->pesanan->pembayaran as $payment) {
                if (
                    strtolower(trim((string) $payment->status)) !== 'pending'
                    || !$payment->midtrans_order_id
                ) {
                    continue;
                }

                try {
                    $remote = Transaction::status($payment->midtrans_order_id);
                    $remoteStatus = strtolower((string) data_get(
                        $remote,
                        'transaction_status',
                        ''
                    ));

                    if ($this->midtransPaymentSucceeded($remote)) {
                        $payment->update([
                            'status' => 'Success',
                            'metode' => MidtransPaymentMethod::from($remote)
                                ?? $payment->metode,
                        ]);

                        $reservasi->pesanan->update(['status' => 'Reserved']);

                        return redirect()->route('customer.reservasi.payment')
                            ->with('error', 'Pembayaran reservasi sudah berhasil; reservasi tidak dibatalkan.');
                    }

                    if (in_array($remoteStatus, ['expire', 'cancel', 'deny'], true)) {
                        $payment->update(['status' => 'Failed']);
                        continue;
                    }

                    Transaction::cancel($payment->midtrans_order_id);

                    $verify = Transaction::status($payment->midtrans_order_id);
                    $verifiedStatus = strtolower((string) data_get(
                        $verify,
                        'transaction_status',
                        ''
                    ));

                    if ($this->midtransPaymentSucceeded($verify)) {
                        $payment->update([
                            'status' => 'Success',
                            'metode' => MidtransPaymentMethod::from($verify)
                                ?? $payment->metode,
                        ]);

                        $reservasi->pesanan->update(['status' => 'Reserved']);

                        return redirect()->route('customer.reservasi.payment')
                            ->with('error', 'Pembayaran berhasil sebelum pembatalan diproses.');
                    }

                    if (in_array($verifiedStatus, ['expire', 'cancel', 'deny'], true)) {
                        $payment->update(['status' => 'Failed']);
                        continue;
                    }

                    throw new \RuntimeException(
                        'Midtrans belum mengonfirmasi pembatalan transaksi.'
                    );
                } catch (\Throwable $e) {
                    if ($this->isMidtransTransactionNotFound($e)) {
                        // Snap/token belum dipilih pelanggan: belum ada transaksi Midtrans utuh.
                        // Aman melanjutkan pembatalan lokal.
                        continue;
                    }

                    throw $e;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal membatalkan transaksi Midtrans reservasi.', [
                'reservasi_id' => $reservasi->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Pembayaran lama belum dapat dibatalkan. Buka detail reservasi lama untuk melanjutkan pembayaran.');
        }

        DB::transaction(function () use ($reservasi) {
            $lockedReservation = Reservasi::whereKey($reservasi->id)
                ->lockForUpdate()
                ->firstOrFail();

            $order = Pesanan::whereKey($lockedReservation->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            $hasSuccessfulDp = $order->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                ->whereRaw("LOWER(TRIM(status)) = 'success'")
                ->exists();

            if ($hasSuccessfulDp || !in_array(strtolower(trim((string) $order->status)), ['pending', 'waiting'], true)) {
                return;
            }

            $order->update(['status' => 'Cancelled']);
            $order->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->update(['status' => 'Failed']);
        }, 3);

        return redirect()->route('customer.reservasi.payment')
            ->with('success', 'Reservasi sebelumnya berhasil dibatalkan. Anda dapat melanjutkan reservasi baru.');
    }

    public function retryReservationPayment(int $id)
    {
        $reservasi = Reservasi::with(['customer', 'pesanan.pembayaran'])->findOrFail($id);
        $order = $reservasi->pesanan;
        if (!$order || strtolower(trim((string) $order->status)) !== 'pending') {
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order?->kode_pesanan])
                ->with('error', 'Reservasi tidak lagi menunggu pembayaran.');
        }

        $oldPayment = $order->pembayaran()->whereRaw("LOWER(TRIM(jenis)) = 'dp'")->latest('id')->first();
        if (now()->greaterThanOrEqualTo($this->reservationDeadline($reservasi))) {
            $this->expireUnpaidReservation($reservasi);
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Waktu pembayaran reservasi telah habis.');
        }
        $paymentStatus = strtolower(trim((string) ($oldPayment?->status ?? '')));
        $paymentIsUnstarted = $oldPayment
            && $paymentStatus === 'pending'
            && !$oldPayment->midtrans_order_id
            && !$oldPayment->snap_token;
        if ($oldPayment && !$paymentIsUnstarted && $paymentStatus !== 'failed') {
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Pembayaran sudah dimulai atau belum dapat diulangi. Muat ulang halaman.');
        }

        $newOrderId = 'RESERV-' . $reservasi->id . '-' . Str::random(8);
        $newPayment = null;
        try {
            $newPayment = DB::transaction(function () use ($reservasi, $order, $oldPayment, $newOrderId) {
                $lockedReservation = Reservasi::whereKey($reservasi->id)->lockForUpdate()->firstOrFail();
                $lockedOrder = Pesanan::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if (strtolower(trim((string) $lockedOrder->status)) !== 'pending') {
                    throw ValidationException::withMessages(['payment' => 'Status reservasi berubah. Muat ulang halaman.']);
                }
                $latest = $lockedOrder->pembayaran()->whereRaw("LOWER(TRIM(jenis)) = 'dp'")->latest('id')->first();
                if ($oldPayment && (!$latest || (int) $latest->id !== (int) $oldPayment->id)) {
                    throw ValidationException::withMessages(['payment' => 'Pembayaran berubah. Muat ulang halaman.']);
                }
                $latestStatus = strtolower(trim((string) ($latest?->status ?? '')));
                $stillUnstarted = $latest
                    && $latestStatus === 'pending'
                    && !$latest->midtrans_order_id
                    && !$latest->snap_token;
                if (($latest && !$stillUnstarted && $latestStatus !== 'failed')
                    || now()->greaterThanOrEqualTo($this->reservationDeadline($lockedReservation))) {
                    throw ValidationException::withMessages(['payment' => 'Status pembayaran berubah atau batas waktu telah habis.']);
                }
                $amount = (int) ($latest?->nominal ?? min(50000, (int) $lockedOrder->total_harga));
                if ($amount < 50000 || $amount > (int) $lockedOrder->total_harga) {
                    throw ValidationException::withMessages(['payment' => 'Nominal DP tidak valid. Hubungi admin untuk memperbarui reservasi.']);
                }
                if (!$latest) {
                    $latest = new Pembayaran([
                        'id_pesanan' => $lockedOrder->id,
                        'jenis' => 'DP',
                        'metode' => 'midtrans',
                        'nominal' => $amount,
                        'status' => 'Pending',
                    ]);
                }
                $latest->forceFill([
                    'midtrans_order_id' => $newOrderId,
                    'snap_token' => null,
                    'status' => 'Pending',
                ])->save();
                return $latest;
            }, 3);

            app(MidtransConfigService::class)->configure();
            $returnUrl = route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan]);
            $amount = (int) $newPayment->nominal;
            $snapToken = Snap::getSnapToken([
                'transaction_details' => ['order_id' => $newOrderId, 'gross_amount' => $amount],
                'item_details' => [['id' => 'dp-' . $reservasi->id, 'price' => $amount, 'quantity' => 1, 'name' => 'DP Reservasi']],
                'customer_details' => ['first_name' => $reservasi->customer->nama, 'phone' => $reservasi->customer->nomor_telp],
                'enabled_payments' => ['other_qris', 'gopay', 'shopeepay', 'bca_va'],
                'custom_expiry' => $this->reservationMidtransExpiry($reservasi),
                'callbacks' => ['finish' => $returnUrl, 'unfinish' => $returnUrl, 'error' => $returnUrl],
            ]);
            $newPayment->forceFill(['snap_token' => $snapToken])->save();
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])->with('open_midtrans', true);
        } catch (\Throwable $e) {
            if ($newPayment) $newPayment->forceFill(['status' => 'Failed', 'snap_token' => null])->save();
            Log::error('Gagal membuat pembayaran reservasi.', ['reservasi_id' => $id, 'payment_id' => $newPayment?->id, 'error' => $e->getMessage()]);
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Pembayaran gagal dibuat. Silakan muat ulang halaman dan coba kembali.');
        }
    }
    
}
