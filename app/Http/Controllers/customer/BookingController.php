<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Services\MenuQuoteService;
use App\Services\MidtransPaymentMethod;
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
use Midtrans\Config as MidtransConfig;
use Carbon\Carbon;

class BookingController extends Controller
{
    // private const RESERVATION_PAYMENT_WINDOW_MINUTES = 1;
    // private const RESERVATION_PAYMENT_WINDOW_MINUTES = 60;
    private const RESERVATION_CAPACITY = 88;

    private function reservationCapacityUsed(
        string $date,
        string $start,
        string $end
    ): int {
        $pendingExpiresAt = now()->subMinutes($this->reservationPaymentWindowMinutes());

        return (int) Reservasi::query()
            ->whereDate('tanggal', $date)
            ->where('jam_mulai', '<', $end)
            ->where('jam_selesai', '>', $start)
            ->where(function ($reservations) use ($pendingExpiresAt) {
                $reservations
                    ->whereHas('pesanan', function ($order) {
                        $order->whereRaw(
                            "LOWER(TRIM(status)) IN ('reserved', 'confirmed', 'processing')"
                        );
                    })
                    ->orWhere(function ($pending) use ($pendingExpiresAt) {
                        $pending
                            ->where('created_at', '>=', $pendingExpiresAt)
                            ->whereHas('pesanan', function ($order) {
                                $order->whereRaw("LOWER(TRIM(status)) = 'pending'");
                            });
                    });
            })
            ->sum('jml_org');
    }

    private function isMidtransTransactionNotFound(\Throwable $e): bool
    {
        return (int) $e->getCode() === 404
            || str_contains(strtolower($e->getMessage()), 'transaction doesn\'t exist')
            || str_contains(strtolower($e->getMessage()), 'transaction not found');
    }

    private function findPendingReservationForPhone(string $phone): ?Reservasi
    {
        $customer = Customer::where('nomor_telp', $phone)->first();

        if (!$customer) {
            return null;
        }

        return Reservasi::query()
            ->where('id_customer', $customer->id)
            ->whereHas('pesanan', function ($query) {
                $query->whereRaw("LOWER(TRIM(status)) = 'pending'")
                    ->whereHas('pembayaran', function ($payment) {
                        $payment->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                            ->whereRaw("LOWER(TRIM(status)) IN ('pending', 'failed')");
                    });
            })
            ->latest('id')
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

        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

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
        return max(1, (int) config('services.reservation.payment_window_minutes', 60));
    }

    private function reservationDeadline(Reservasi $reservasi): Carbon
    {
        return $reservasi->created_at->copy()->addMinutes(
            $this->reservationPaymentWindowMinutes()
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

            if (!$paidBeforeDeadline || !$validOrder) {
                Log::critical('Pembayaran reservasi berhasil setelah reservasi tidak aktif atau melewati deadline.', [
                    'reservasi_id' => $lockedReservation->id,
                    'payment_id' => $lockedPayment->id,
                    'midtrans_order_id' => $lockedPayment->midtrans_order_id,
                    'transaction_time' => $transactionTime,
                ]);

                return false;
            }

            $lockedOrder->update(['status' => 'Reserved']);

            return true;
        }, 3);
    }

    private function expireUnpaidReservation(Reservasi $reservasi): void
    {
        if (now()->lt($this->reservationDeadline($reservasi))) {
            return;
        }

        $order = $reservasi->pesanan;

        if (!$order || strtolower(trim((string) $order->status)) !== 'pending') {
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
        Reservasi::where(
            'created_at',
            '<=',
            now()->subMinutes($this->reservationPaymentWindowMinutes())
        )
        ->whereHas('pesanan', function ($query) {
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

        return view('customer.reservasi', compact('menus', 'prefill'));
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

    // TAHAP 1: FORM KE SESSION DRAFT (BELUM MASUK DB)
    public function reservasiStore(Request $request)
    {
        // $totalOrangHariIni = Reservasi::whereDate('tanggal', $request->tanggal)->sum('jml_org');
        // if (($totalOrangHariIni + $request->jml_org) > 88) {
        //     return back()->withErrors(['Kapasitas restoran untuk tanggal tersebut sudah penuh.']);
        // }

        $minimumReservationDate = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $validated = $request->validate([
            'nama'         => 'required|string|min:3|max:50',
            'nomor_telp'   => 'required|string|regex:/^[0-9\+\-\s]{9,20}$/',
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $minimumReservationDate],
            'jam_mulai'    => 'required|date_format:H:i',
            'jam_selesai'  => 'required|date_format:H:i|after:jam_mulai',
            'jml_org'      => 'required|integer|min:1',
            'keterangan'   => 'nullable|string|max:200',
            'menu_qty' => ['nullable', 'array'],
            'menu_qty.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'menu_price' => ['nullable', 'array'], 
            'menu_price.*' => ['nullable', 'integer'],
            'menu_notes' => ['nullable', 'array'],
            'menu_notes.*' => ['nullable', 'string', 'max:200'],
        ], ['jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.']);

        if ($validated['jam_mulai'] < '15:00' || $validated['jam_mulai'] > '23:59'
            || $validated['jam_selesai'] > '23:59') {
            return back()
                ->withErrors(['jam_mulai' => 'Reservasi hanya dapat dibuat pada jam operasional 15:00–00:00.'])
                ->withInput();
        }

        // $overlappingOrang = Reservasi::whereDate('tanggal', $request->tanggal)
        //     ->where('jam_mulai', '<', $request->jam_selesai) // Jam mulai baru < Jam selesai existing
        //     ->where('jam_selesai', '>', $request->jam_mulai) // Jam selesai baru > Jam mulai existing
        //     ->whereHas('pesanan', function($q) {
        //         // Jangan hitung reservasi yang sudah dibatalkan
        //         $q->whereNotIn('status', ['Cancelled']); 
        //     })
        //     ->sum('jml_org');

        // if (($overlappingOrang + $request->jml_org) > 88) {
        //     $sisa = max(0, 88 - $overlappingOrang);
        //     return back()
        //         ->withErrors(["Kapasitas restoran pada rentang jam tersebut sudah hampir penuh (Sisa kapasitas: {$sisa} orang)."])
        //         ->withInput();
        // }

        $overlappingOrang = $this->reservationCapacityUsed(
            $validated['tanggal'],
            $validated['jam_mulai'],
            $validated['jam_selesai']
        );

        if ($overlappingOrang + (int) $validated['jml_org'] > self::RESERVATION_CAPACITY) {
            $sisa = max(0, self::RESERVATION_CAPACITY - $overlappingOrang);

            return back()
                ->withErrors([
                    'kapasitas' => "Kapasitas jadwal tersebut tidak mencukupi. Sisa kapasitas: {$sisa} orang.",
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

        // Konversi draft array ke object agar view bisa pakai $draft->nama, $draft->tanggal, dst.
        $draftObj = (object) $draft;

        // Data menu dari draft
        $menuItems = [];
        $totalHargaMenu = 0;
        $hasMenuChanges = false;
        $menuChangeMessages = [];

        $overlappingOrang = $this->reservationCapacityUsed(
            $draft['tanggal'],
            $draft['jam_mulai'],
            $draft['jam_selesai']
        );

        if ($overlappingOrang + (int) $draft['jml_org'] > self::RESERVATION_CAPACITY) {
            $menuChangeMessages[] =
                'Kapasitas jadwal tersebut sudah tidak mencukupi. Silakan pilih jadwal lain.';
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
            $pendingReservasi->refresh();

            if (strtolower(trim((string) $pendingReservasi->pesanan?->status)) !== 'pending') {
                $pendingReservasi = null;
            }
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
            return redirect()->route('customer.reservasi.payment')
                ->withErrors($validator)
                ->withInput();
        }

        $payAmount = (int) $validator->validated()['nominal_dp'];

        if ($payAmount > $totalHargaMenu) {
            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Nominal DP tidak boleh melebihi total harga menu.');
        }

        $customer = Customer::firstOrCreate(
            ['nomor_telp' => $draft['nomor_telp']],
            ['nama' => $draft['nama']]
        );

        try {
            [$reservasi, $midtransOrderId] = DB::transaction(
                function () use ($draft, $customer, $menuQty, $menuSnapshot, $totalHargaMenu, $payAmount) {
                    return $this->persistReservation(
                        $draft,
                        (int) $customer->id,
                        $menuQty,
                        $menuSnapshot,
                        $totalHargaMenu,
                        $payAmount
                    );
                },
                3
            );
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->all();

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

        $payment = Pembayaran::where('midtrans_order_id', $midtransOrderId)
            ->firstOrFail();

        try {
            MidtransConfig::$serverKey = config('services.midtrans.server_key')
                ?: env('MIDTRANS_SERVER_KEY');

            $production = config('services.midtrans.is_production')
                ?? env('MIDTRANS_IS_PRODUCTION', false);

            MidtransConfig::$isProduction = filter_var($production, FILTER_VALIDATE_BOOLEAN);
            MidtransConfig::$isSanitized = true;
            MidtransConfig::$is3ds = true;

            $returnUrl = route('customer.reservasi.detail', [
                'kode_pesanan' => $reservasi->pesanan->kode_pesanan,
            ]);

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => $payAmount,
                ],
                'item_details' => [[
                    'id' => 'dp-' . $reservasi->id,
                    'price' => $payAmount,
                    'quantity' => 1,
                    'name' => 'DP Reservasi',
                ]],
                'customer_details' => [
                    'first_name' => $customer->nama,
                    'phone' => $customer->nomor_telp,
                ],
                // 'enabled_payments' => ['gopay', 'shopeepay', 'other_qris', 'bank_transfer'],
                'enabled_payments' => ['other_qris', 'gopay', 'shopeepay', 'bca_va'],
                'custom_expiry' => $this->reservationMidtransExpiry($reservasi),
                'callbacks' => [
                    'finish' => $returnUrl,
                    'unfinish' => $returnUrl,
                    'error' => $returnUrl,
                ],
            ]);

            $payment->forceFill(['snap_token' => $snapToken])->save();

            if (!$payment->fresh()?->snap_token) {
                throw new \RuntimeException('Snap token tidak tersimpan ke database.');
            }

            // session()->forget('reservation_draft');

            return redirect()
                ->route('customer.reservasi.detail', ['kode_pesanan' => $reservasi->pesanan->kode_pesanan])
                ->with('open_midtrans', true);
        } catch (\Throwable $e) {
            $payment->forceFill([
                'status' => 'Failed',
                'snap_token' => null,
            ])->save();

            Log::error('Gagal membuat transaksi Snap reservasi.', [
                'reservasi_id' => $reservasi->id,
                'payment_id' => $payment->id,
                'midtrans_order_id' => $midtransOrderId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return redirect()
                ->route('customer.reservasi.detail', ['kode_pesanan' => $reservasi->pesanan->kode_pesanan])
                ->with('error', 'Tagihan gagal dibuat. Silakan tekan Ulangi Pembayaran.');
        }
    }

    private function persistReservation(
        array $draft,
        int $customerId,
        array $menuQty,
        array $menuSnapshot,
        int $totalHargaMenu,
        int $payAmount
    ): array {
        // Mengunci mutex kapasitas yang sama untuk seluruh proses booking.
        Meja::query()->orderBy('id')->lockForUpdate()->firstOrFail();

        // Serialisasi pembuatan reservasi untuk nomor telepon yang sama.
        Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();

        $pendingReservation = Reservasi::query()
            ->where('id_customer', $customerId)
            ->where(
                'created_at',
                '>=',
                now()->subMinutes($this->reservationPaymentWindowMinutes())
            )
            ->whereHas('pesanan', function ($query) {
                $query->whereRaw("LOWER(TRIM(status)) = 'pending'");
            })
            ->lockForUpdate()
            ->first();

        if ($pendingReservation) {
            throw ValidationException::withMessages([
                'reservasi' => 'Nomor telepon ini masih memiliki reservasi yang menunggu pembayaran.',
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

        $usedCapacity = $this->reservationCapacityUsed(
            $draft['tanggal'],
            $draft['jam_mulai'],
            $draft['jam_selesai']
        );

        if ($usedCapacity + (int) $draft['jml_org'] > self::RESERVATION_CAPACITY) {
            throw ValidationException::withMessages([
                'kapasitas' => 'Kapasitas jadwal ini sudah penuh. Silakan pilih waktu lain.',
            ]);
        }

        $pesanan = Pesanan::create([
            'kode_pesanan' => 'RSV-' . now()->format('md-Hi') . '-' . random_int(1000, 9999),
            'id_user' => null,
            'id_meja' => null,
            'total_harga' => $verifiedTotal,
            'status' => 'Pending',
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

        $midtransOrderId = 'RESERV-' . $reservasi->id . '-' . Str::random(8);

        Pembayaran::create([
            'id_pesanan' => $pesanan->id,
            'jenis' => 'DP',
            'metode' => 'midtrans',
            'nominal' => $payAmount,
            'midtrans_order_id' => $midtransOrderId,
            'status' => 'Pending',
        ]);

        return [$reservasi, $midtransOrderId];
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
        $this->expireUnpaidReservation($reservasi);

        $reservasi->refresh()->load(['pesanan.pembayaran']);

        // Waktu ini selalu dari reservasi dibuat, bukan dari pembayaran/retry.
        $reservationExpiresAt = $this->reservationDeadline($reservasi)->timestamp * 1000;
        $withinPaymentWindow = Carbon::now('UTC')->lt($this->reservationDeadline($reservasi));

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
        $dpBayar = (float) ($pesanan?->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->whereRaw("LOWER(TRIM(status)) = 'success'")
            ->sum('nominal') ?? 0);
        $sisaTagihan = max(0, $totalHarga - $dpBayar);

        $bisaUbahMenu = $statusPesanan === 'pending' && $withinPaymentWindow;
        $nominalDp = (int) ($pesanan?->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->latest('id')
            ->value('nominal') ?? 50000);

        $menus = Menu::Where('is_reservable', true)
                    ->orderByDesc('is_bestseller')
                    ->get();

        $paymentDp = $pesanan?->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->latest('id')
            ->first();

        $paymentStatus = strtolower(trim((string) ($paymentDp?->status ?? '')));
        $snapToken = $statusPembayaranDp === 'pending'
            ? $paymentDp?->snap_token
            : null;

        $canRetryPayment = $statusPesanan === 'pending'
            && $statusPembayaranDp === 'failed'
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
            'reservationExpiresAt',
            'statusPesanan',
            'statusPembayaranDp',
            'reservationFinished',
            'reservationUrl',
            'reservationSteps',
            'stepAktif'
        ));
    }

    public function reservasiUpdateMenu(Request $request, $id)
    {
        $initialReservation = Reservasi::with('pesanan')->findOrFail($id);

        $this->syncReservationPayment($initialReservation);
        $this->expireUnpaidReservation($initialReservation);
        $initialReservation->refresh()->load('pesanan');

        return DB::transaction(function () use ($id, $request) {
            $validated = $request->validate([
                'menu_qty' => ['nullable', 'array'],
                'menu_qty.*' => ['nullable', 'integer', 'min:0', 'max:100'],
                'menu_notes' => ['nullable', 'array'],
                'menu_notes.*' => ['nullable', 'string', 'max:200'],
            ]);

            $reservasi = Reservasi::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $pesanan = Pesanan::whereKey($reservasi->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                now()->greaterThanOrEqualTo($this->reservationDeadline($reservasi))
                || strtolower(trim((string) $pesanan->status)) !== 'pending'
            ) {
                if (strtolower(trim((string) $pesanan->status)) === 'pending') {
                    $pesanan->update(['status' => 'Cancelled']);
                    $pesanan->pembayaran()
                        ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                        ->update(['status' => 'Failed']);
                }

                return redirect()
                    ->route('customer.reservasi.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                    ->with('error', 'Waktu mengubah menu atau reservasi telah berakhir.');
            }

            $dpPayment = $pesanan->pembayaran()
                ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                ->whereRaw("LOWER(TRIM(status)) IN ('pending', 'failed')")
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$dpPayment || !in_array(strtolower(trim((string) $dpPayment->status)), ['pending', 'failed'], true)) {
                return back()->with('error', 'Pembayaran DP tidak dapat diedit; muat ulang halaman untuk melihat status terbaru.');
            }

            $activeMenuQty = collect($validated['menu_qty'] ?? [])
                ->map(fn ($qty) => (int) $qty)
                ->filter(fn ($qty) => $qty > 0)
                ->all();

            $menuIds = array_map('intval', array_keys($activeMenuQty));
            $menus = Menu::whereIn('id', $menuIds)
                ->where('is_reservable', true)
                ->get()
                ->keyBy('id');

            if ($menus->count() !== count($menuIds)) {
                return back()->withErrors(['menu' => 'Ada menu reservasi yang tidak valid.'])->withInput();
            }

            $totalHarga = 0;

            foreach ($activeMenuQty as $menuId => $qty) {
                $totalHarga += (int) round((float) $menus->get($menuId)->harga) * $qty;
            }

            $minimumTotal = max(50000, (int) $dpPayment->nominal);

            if ($totalHarga < $minimumTotal) {
                return back()
                    ->withErrors([
                        'menu' => 'Total menu tidak boleh kurang dari DP sebesar Rp '
                            . number_format($minimumTotal, 0, ',', '.') . '.',
                    ])
                    ->withInput();
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

            return redirect()
                ->route('customer.reservasi.detail', ['kode_pesanan' => $pesanan->kode_pesanan])
                ->with('success', 'Menu pre-order berhasil disesuaikan.');
        });
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
            || (string) $reservasi->customer->nomor_telp !== (string) $draft['nomor_telp']
        ) {
            abort(403);
        }

        $this->expireUnpaidReservation($reservasi);
        $reservasi->refresh()->load('pesanan.pembayaran');

        if (strtolower(trim((string) $reservasi->pesanan?->status)) !== 'pending') {
            return redirect()->route('customer.reservasi.payment')
                ->with('error', 'Reservasi tersebut tidak lagi berstatus menunggu pembayaran.');
        }

        try {
            MidtransConfig::$serverKey = config('services.midtrans.server_key')
                ?: env('MIDTRANS_SERVER_KEY');

            $production = config('services.midtrans.is_production')
                ?? env('MIDTRANS_IS_PRODUCTION', false);

            MidtransConfig::$isProduction = filter_var($production, FILTER_VALIDATE_BOOLEAN);
            MidtransConfig::$isSanitized = true;
            MidtransConfig::$is3ds = true;

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

            if ($hasSuccessfulDp || strtolower(trim((string) $order->status)) !== 'pending') {
                return;
            }

            $order->update(['status' => 'Cancelled']);
            $order->pembayaran()
                ->whereRaw("LOWER(TRIM(status)) = 'pending'")
                ->update(['status' => 'Failed']);
        }, 3);

        return redirect()->route('customer.reservasi.payment')
            ->with('success', 'Reservasi lama berhasil dibatalkan. Anda dapat melanjutkan reservasi baru.');
    }

    public function retryReservationPayment(int $id)
    {
        $reservasi = Reservasi::with(['customer', 'pesanan.pembayaran'])
            ->findOrFail($id);

        $draft = session('reservation_draft');

        if (
            !$draft
            || empty($draft['nomor_telp'])
            || !$reservasi->customer
            || (string) $reservasi->customer->nomor_telp !== (string) $draft['nomor_telp']
        ) {
            abort(403);
        }

        $this->syncReservationPayment($reservasi);
        $reservasi->refresh()->load(['customer', 'pesanan.pembayaran']);

        if (now()->greaterThanOrEqualTo($this->reservationDeadline($reservasi))) {
            $this->expireUnpaidReservation($reservasi);

            return redirect()->route('customer.reservasi.detail', [
                'kode_pesanan' => $reservasi->pesanan?->kode_pesanan,
            ])
                ->with('error', 'Waktu pembayaran reservasi telah habis.');
        }

        $order = $reservasi->pesanan;

        if (!$order || strtolower(trim((string) $order->status)) !== 'pending') {
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Reservasi tidak lagi menunggu pembayaran.');
        }

        $oldPayment = $order->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->latest('id')
            ->first();

        if (!$oldPayment || strtolower(trim((string) $oldPayment->status)) !== 'failed') {
            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Pembayaran sebelumnya belum dinyatakan gagal atau expired oleh Midtrans.');
        }

        $newOrderId = 'RESERV-' . $reservasi->id . '-' . Str::random(8);
        $newPayment = null;

        try {
            $newPayment = DB::transaction(function () use (
                $reservasi,
                $order,
                $oldPayment,
                $newOrderId
            ) {
                $lockedReservation = Reservasi::whereKey($reservasi->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedOrder = Pesanan::whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedOldPayment = Pembayaran::whereKey($oldPayment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (now()->greaterThanOrEqualTo($this->reservationDeadline($lockedReservation))) {
                    throw ValidationException::withMessages([
                        'payment' => 'Waktu pembayaran reservasi telah habis.',
                    ]);
                }

                if (
                    strtolower(trim((string) $lockedOrder->status)) !== 'pending'
                    || strtolower(trim((string) $lockedOldPayment->status)) !== 'failed'
                ) {
                    throw ValidationException::withMessages([
                        'payment' => 'Status pembayaran berubah. Muat ulang halaman.',
                    ]);
                }

                $latestId = Pembayaran::where('id_pesanan', $lockedOrder->id)
                    ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                    ->latest('id')
                    ->value('id');

                if ((int) $latestId !== (int) $lockedOldPayment->id) {
                    throw ValidationException::withMessages([
                        'payment' => 'Pembayaran sudah diperbarui. Muat ulang halaman.',
                    ]);
                }

                return Pembayaran::create([
                    'id_pesanan' => $lockedOrder->id,
                    'jenis' => 'DP',
                    'metode' => 'midtrans',
                    'nominal' => (int) $lockedOldPayment->nominal,
                    'midtrans_order_id' => $newOrderId,
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

            $returnUrl = route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan]);
            $amount = (int) $newPayment->nominal;

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $newOrderId,
                    'gross_amount' => $amount,
                ],
                'item_details' => [[
                    'id' => 'dp-' . $reservasi->id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'DP Reservasi',
                ]],
                'customer_details' => [
                    'first_name' => $reservasi->customer->nama,
                    'phone' => $reservasi->customer->nomor_telp,
                ],
                'enabled_payments' => ['other_qris', 'gopay', 'shopeepay', 'bca_va'],
                'custom_expiry' => $this->reservationMidtransExpiry($reservasi),
                'callbacks' => [
                    'finish' => $returnUrl,
                    'unfinish' => $returnUrl,
                    'error' => $returnUrl,
                ],
            ]);

            $newPayment->forceFill(['snap_token' => $snapToken])->save();

            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('open_midtrans', true);
        } catch (\Throwable $e) {
            if ($newPayment) {
                $newPayment->forceFill([
                    'status' => 'Failed',
                    'snap_token' => null,
                ])->save();
            }

            Log::error('Gagal membuat pembayaran ulang reservasi.', [
                'reservasi_id' => $id,
                'payment_id' => $newPayment?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('customer.reservasi.detail', ['kode_pesanan' => $order->kode_pesanan])
                ->with('error', 'Pembayaran baru gagal dibuat. Silakan muat ulang halaman dan coba kembali.');
        }
    }
    
}
