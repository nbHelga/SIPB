<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\DetailPesanan;
use App\Models\Meja;
use App\Services\MidtransPaymentMethod;
use App\Services\PendingSelfOrderExpiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Midtrans\Snap;
use Midtrans\Config as MidtransConfig;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;


class SelfOrderController extends Controller
{
    // SELF ORDER — support QR scan via ?nomor_meja=XXX
    private function createSelfOrderCode(): string
    {
        do {
            // MMDD (4 digit tanggal) - HHMM (4 digit jam menit) - RAND (4 digit acak)
            $code = now()->format('md-Hi') . '-' . random_int(1000, 9999);
        } while (Pesanan::where('kode_pesanan', 'SOD-' . $code)->exists());

        return $code;
    }

    private function selfOrderParams(): array
    {
        return [
            'kode' => session('selforder_code'),
            'tableNumber' => session('nomor_meja'),
        ];
    }

    private function redirectToSelfOrder(string $message = '')
    {
        $response = redirect()->route('customer.selforder', $this->selfOrderParams());

        return $message !== '' ? $response->with('error', $message) : $response;
    }

    

    public function selfOrder(Request $request)
    {
        // 1. Ambil nomor meja dari URL atau Session
        $nomorMeja = $request->query('tableNumber')
            ?? $request->query('nomor_meja')
            ?? session('nomor_meja');

        $tables = Meja::where('nomor_meja', $nomorMeja)->first();

        if (!$tables) {
            return redirect()->route('home')->with('error', 'Meja tidak ditemukan.');
        }

        // 2. Jika berpindah meja fisik, bersihkan keranjang dan kode lama
        if (session('id_meja') && (string) session('id_meja') !== (string) $tables->id) {
            session()->forget(['cart', 'cart_count', 'selforder_code']);
        }

        $codeFromUrl = $request->query('kode');

        // Cek apakah kode (baik dari URL maupun Session) sudah pernah dipakai di database
        $isCodeUsedInDb = function ($c) {
            return is_string($c) && Pesanan::where('kode_pesanan', 'SOD-' . $c)->exists();
        };

        // 3. Validasi format kode dari URL (misal: 1011-1730-1234)
        $isValidUrlCode = is_string($codeFromUrl)
            && preg_match('/^\d{4}-\d{4}-\d{4}$/', $codeFromUrl)
            && !$isCodeUsedInDb($codeFromUrl);

        if ($isValidUrlCode) {
            // Jika kode dari URL valid dan belum dipakai di DB, gunakan kode tersebut
            session(['selforder_code' => $codeFromUrl]);
        } else {
            // Jika kode di session tidak ada ATAU sudah pernah dipakai transaksi di DB, generate KODE BARU
            $currentSessionCode = session('selforder_code');
            if (!$currentSessionCode || $isCodeUsedInDb($currentSessionCode)) {
                session(['selforder_code' => $this->createSelfOrderCode()]);
            }
        }

        // 4. Simpan info meja ke session
        session([
            'nomor_meja' => $tables->nomor_meja,
            'id_meja' => $tables->id,
        ]);

        // 5. Pastikan URL selalu rapi dan berisi kode sesi terbaru serta tableNumber
        if (
            $request->query('kode') !== session('selforder_code') ||
            $request->query('tableNumber') !== $tables->nomor_meja
        ) {
            return redirect()->route('customer.selforder', $this->selfOrderParams());
        }

        // 6. Ambil data menu & kategori
        $menus = Menu::where('status', 'open')
                ->orderByDesc('is_bestseller')
                ->orderBy('kategori')
                ->orderBy('nama', 'asc')
                ->get();
        $categories = Menu::where('status', 'open')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        $businessTime = now('Asia/Jakarta');
        $businessMinuteOfDay = ((int) $businessTime->format('G') * 60)
            + (int) $businessTime->format('i');
        $isWithinBusinessHours = $businessMinuteOfDay >= 0
        // $isWithinBusinessHours = $businessMinuteOfDay >= 900 #pengaruh dari buka tutup resto
            && $businessMinuteOfDay < 1440;
        // Local-only override: set SELFORDER_FORCE_OPEN=true to test the menu outside 15.00-00.00 WIB.
        $isRestaurantOpen = app()->environment('local')
            && filter_var(config('services.selforder_force_open'), FILTER_VALIDATE_BOOLEAN)
                ? true
                : $isWithinBusinessHours;
        $businessTimezoneLabel = 'WIB';
        $pendingOrders = Pesanan::query()
            ->where('id_meja', $tables->id)
            ->whereRaw("LOWER(TRIM(status)) = 'pending'")
            ->whereDoesntHave('reservasi')
            ->orderByDesc('created_at')
            ->get(['id', 'kode_pesanan', 'total_harga', 'created_at']);

        return view('customer.selforder', compact(
            'menus',
            'categories',
            'tables',
            'isRestaurantOpen',
            'businessTimezoneLabel',
            'pendingOrders'
        ));
    }

    public function menuJson($id)
    {
        $menu = Menu::findOrFail($id);
        return response()->json($menu);
    }

    // Halaman detail menu (halaman terpisah)
    public function menuDetail(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $menus = Menu::where('status', 'open')->get();
        $categories = Menu::where('status', 'open')->distinct()->orderBy('kategori')->pluck('kategori');
        // $nomorMeja = $request->query('nomor_meja')
        $nomorMeja = $request->query('tableNumber')
            ?? $request->query('nomor_meja')
            ?? session('nomor_meja');

        if ($nomorMeja) {
            session(['nomor_meja' => $nomorMeja]);
        }

        $tables = Meja::where('nomor_meja', $nomorMeja)->first();
        return view('customer.menu_detail', compact('menu', 'menus', 'categories', 'tables'));
    }

    // CART
    public function cart(Request $request)
    {
        $nomorMeja = $request->query('tableNumber')
            ?? $request->query('nomor_meja')
            ?? session('nomor_meja');

        $tables = Meja::where('nomor_meja', $nomorMeja)->first();

        if (!$tables || !$nomorMeja || !session('selforder_code')) {
            return redirect()->route('home')->with('error', 'Silakan scan QR meja terlebih dahulu.');
        }

        session([
            'nomor_meja' => $tables->nomor_meja,
            'id_meja' => $tables->id,
        ]);

        if ($request->query('kode') !== session('selforder_code') || $request->query('tableNumber') !== $tables->nomor_meja) {
            return redirect()->route('customer.cart', $this->selfOrderParams());
        }

        $cartItems = session('cart', []);
        // $selectedItems = array_keys($cartItems);
        $totalHarga = 0;
        foreach ($cartItems as $item) {
            $totalHarga += $item['subtotal'];
        }
        return view('customer.cart', compact('cartItems', 'totalHarga', 'tables'));
    }

    public function cartAdd(Request $request)
    {
        $request->validate([
            'id_menu' => 'required|exists:menu,id',
            'jumlah' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        $menu = Menu::findOrFail($request->id_menu);
        $cart = session('cart', []);

        if (!session('id_meja') || !session('selforder_code')) {
            return redirect()->route('home')->with('error', 'Silakan scan QR meja terlebih dahulu.');
        }
        
        // FIX: Ambil dari $request->catatan, bukan $request->menu->catatan
        $catatan = trim($request->input('catatan', ''));

        // Hash key agar item menu sama dengan catatan beda tersimpan terpisah
        $key = 'menu_' . $menu->id . ($catatan !== '' ? '_' . md5($catatan) : '');

        if (isset($cart[$key])) {
            $cart[$key]['jumlah'] += $request->jumlah;
            $cart[$key]['subtotal'] = $cart[$key]['jumlah'] * $menu->harga;
            if (!empty($catatan)) {
                $cart[$key]['catatan'] = $catatan;
            }
        } else {
            $cart[$key] = [
                'id_menu'  => $menu->id,
                'menu'     => $menu,
                'jumlah'   => $request->jumlah,
                'subtotal' => $request->jumlah * $menu->harga,
                'catatan'  => $catatan, // Disimpan ke session cart
            ];
        }

        session(['cart' => $cart]);
        session(['cart_count' => count($cart)]);

        // $redirect = $request->input('redirect', route('customer.selforder', ['nomor_meja' => session('nomor_meja')]));
        // return redirect($redirect)->with('success', 'Menu berhasil ditambahkan ke keranjang!');
        return redirect()->route('customer.selforder', $this->selfOrderParams())->with('success', 'Menu berhasil ditambahkan ke keranjang!');
    }

    public function cartUpdate(Request $request)
    {
        $cart = session()->get('cart', []);
        $key = $request->key;

        if (isset($cart[$key])) {
            if ($request->action === 'update_note') {
                $cart[$key]['catatan'] = $request->catatan;
            } 
            elseif ($request->action === 'update_qty' || $request->has('jumlah')) {
                $jumlah = max(1, (int) $request->jumlah);
                $cart[$key]['jumlah'] = $jumlah;
                $cart[$key]['subtotal'] = $jumlah * $cart[$key]['menu']['harga'];
            }

            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Keranjang berhasil diperbarui!');
    }

    public function cartRemove(Request $request)
    {
        $key = $request->key;
        $cart = session('cart', []);
        unset($cart[$key]);
        session(['cart' => $cart]);
        session(['cart_count' => count($cart)]);

        // return redirect()->route('customer.cart')->with('success', 'Menu berhasil dihapus!');
        return redirect()->route('customer.cart', $this->selfOrderParams())->with('success', 'Menu berhasil dihapus!');
    }

    // CHECKOUT
    public function checkout(Request $request)
    {
        // 1. Ambil & Validasi Nomor Meja
        $nomorMeja = $request->query('nomor_meja') ?? session('nomor_meja');
        
        if (!$nomorMeja) {
            return redirect()->route('customer.selforder')
                ->withErrors(['Silakan scan QR code meja terlebih dahulu.']);
        }

        $tables = Meja::where('nomor_meja', $nomorMeja)->first();

        if (!$tables || !session('selforder_code')) {
            return redirect()->route('home')->with('error', 'Silakan scan QR meja terlebih dahulu.');
        }

        session([
            'nomor_meja' => $tables->nomor_meja,
            'id_meja' => $tables->id,
        ]);

        if (
            $request->query('kode') !== session('selforder_code') ||
            $request->query('tableNumber') !== $tables->nomor_meja
        ) {
            return redirect()->route('customer.checkout', $this->selfOrderParams());
        }

        // 2. Validasi Keranjang Belanja
        $cartItems = session('cart', []);

        // Untuk cart kosong:
        if (empty($cartItems)) {
            return $this->redirectToSelfOrder('Keranjang Anda kosong. Silakan pilih menu terlebih dahulu.');
        }

        // --- TAMBAHAN LOGIKA PENGECEKAN REAL-TIME ---
        $hasMenuChanges = false;
        $menuChangeMessages = [];
        $totalHarga = 0;

        foreach ($cartItems as $key => $item) {
            $menuDb = Menu::find($item['menu']['id'] ?? null);

            // Cek jika menu dihapus atau ditutup
            if (!$menuDb || $menuDb->status !== 'open') {
                $hasMenuChanges = true;
                $menuChangeMessages[] = "Menu '{$item['menu']['nama']}' saat ini sudah habis/ditutup.";
            } else {
                // Cek jika harga berubah di database
                if ((int)$menuDb->harga !== (int)($item['menu']['harga'] ?? 0)) {
                    $hasMenuChanges = true;
                    $menuChangeMessages[] = "Harga '{$menuDb->nama}' telah diperbarui menjadi Rp " . number_format($menuDb->harga, 0, ',', '.') . ". Silahkan kembali ke halaman sebelumnya";
                }
                // Selalu kalkulasi ulang berdasarkan harga DB agar aman
                $totalHarga += ((int)$menuDb->harga * $item['jumlah']);
            }
        }

        // 3. Kalkulasi Total Harga, PB1 (10%), dan Total Bayar
        $totalHarga = collect($cartItems)->sum('subtotal');
        // $pb         = round($totalHarga * 0.1);
        // $totalBayar = $totalHarga + $pb;

        return view('customer.checkout', 
            compact('cartItems', 
            'totalHarga', 
            // 'pb', 
            // 'totalBayar', 
            'tables',
            'hasMenuChanges', 
            'menuChangeMessages'));
    }

    // PAYMENT
    public function payment(Request $request)
    {
        $cartItems = session('cart', []);
        if (empty($cartItems)) {
            return redirect()->route('customer.cart');
        }

        $idMeja = session('id_meja');
        if (!$idMeja) {
            return redirect()->route('customer.cart')->withErrors(['Informasi meja hilang, silahkan scan kembali QR Code meja Anda.']);
        }
        return redirect()->route('customer.checkout.create');
    }

    public function createCheckoutTransaction(Request $request)
    {
        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'in:Tunai,Midtrans'],
        ]);

        $metode = strtolower($validated['metode_pembayaran']);
        $cart = session('cart', []);
        $idMeja = session('id_meja');
        $kodePesanan = session('selforder_code');

        if (empty($cart) || !$idMeja || !$kodePesanan) {
            return $this->redirectToSelfOrder(
                'Sesi pesanan tidak ditemukan. Silakan scan QR meja kembali.'
            );
        }

        // --- PENGECEKAN RACE CONDITION (DETIK TERAKHIR) ---
        $hasChanges = false;
        foreach ($cart as $key => $item) {
            $menu = Menu::find($item['menu']['id'] ?? null);
            // Jika menu dihapus, ditutup admin, atau harganya berubah
            if (!$menu || $menu->status !== 'open' || (int)$menu->harga !== (int)($item['menu']['harga'] ?? 0)) {
                $hasChanges = true;
                break;
            }
        }

        // Jika ada perubahan, kembalikan ke halaman Checkout agar Alert Merah muncul dan tombol mati
        if ($hasChanges) {
            return redirect()->route('customer.checkout', $this->selfOrderParams())
                ->with('error', 'Terjadi perubahan pada ketersediaan atau harga menu. Silakan periksa peringatan di layar.');
        }
        // ---------------------------------------------------

        $items = [];
        $totalHarga = 0;
        $detailItems = [];

        foreach ($cart as $key => $item) {
            $menu = Menu::find($item['menu']['id'] ?? null);

            $jumlah = (int) ($item['jumlah'] ?? 0);

            if ($jumlah < 1) {
                return $this->redirectToSelfOrder('Jumlah menu tidak valid.');
            }

            // Gunakan harga terbaru dari database, bukan harga dari session.
            $subtotal = (int) $menu->harga * $jumlah;
            $totalHarga += $subtotal;

            $items[] = [
                'id' => (string) $menu->id,
                'price' => (int) $menu->harga,
                'quantity' => $jumlah,
                'name' => substr($menu->nama, 0, 50),
            ];

            $detailItems[] = [
                'id_menu' => $menu->id,
                'jumlah' => $jumlah,
                'subtotal' => $subtotal,
                'catatan' => $item['catatan'] ?? null,
            ];
        }

        if ($totalHarga < 1) {
            return redirect()->route('customer.cart')->with('error', 'Keranjang Anda kosong.');
        }

        $storedMethod = $metode === 'midtrans' ? 'other_qris' : $metode;

        // Buat pesanan, detail, dan pembayaran bersama agar tidak ada data parsial.
        $pesanan = DB::transaction(function () use (
            $idMeja,
            $kodePesanan,
            $totalHarga,
            $detailItems,
            $metode,
            $storedMethod,
        ) {
            $order = Pesanan::create([
                'kode_pesanan' => 'SOD-' . $kodePesanan,
                'id_user' => null,
                'id_meja' => $idMeja,
                'total_harga' => $totalHarga,
                'status' => 'Pending',
            ]);

            foreach ($detailItems as $detail) {
                DetailPesanan::create([
                    'id_pesanan' => $order->id,
                    'id_menu' => $detail['id_menu'],
                    'jumlah' => $detail['jumlah'],
                    'subtotal' => $detail['subtotal'],
                    'catatan' => $detail['catatan'],
                ]);
            }

            $order->pembayaran()->create([
                'jenis' => 'Full',
                'metode' => $storedMethod,
                'nominal' => $totalHarga,
                'status' => 'Pending',
            ]);

            return $order;
        });

        // Hindari pembuatan pesanan duplikat jika pelanggan refresh halaman checkout.
        session()->forget(['cart', 'cart_count', 'selforder_code']);

        if ($metode === 'midtrans') {
            try {
                MidtransConfig::$serverKey = config(
                    'services.midtrans.server_key',
                    env('MIDTRANS_SERVER_KEY')
                );
                MidtransConfig::$isProduction = (bool) config(
                    'services.midtrans.is_production',
                    env('MIDTRANS_IS_PRODUCTION') === 'true'
                );
                MidtransConfig::$isSanitized = true;
                MidtransConfig::$is3ds = true;

                $pembayaran = $pesanan->pembayaran()
                    ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                    ->latest('id')
                    ->firstOrFail();

                $midtransOrderId = 'ORDER-' . $pesanan->id . '-' . Str::random(5);
                $pembayaran->update([
                    'midtrans_order_id' => $midtransOrderId,
                ]);

                $snapToken = Snap::getSnapToken([
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => $totalHarga,
                    ],
                    'item_details' => $items,

                    // Dipertahankan karena other_qris sudah berhasil pada konfigurasi sebelumnya.
                    'enabled_payments' => ['other_qris'],

                    'custom_expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit' => 'minute',
                        'duration' => 15,
                    ],
                    'callbacks' => [
                        'finish' => route('customer.order.detail', [
                            'kode_pesanan' => $pesanan->kode_pesanan,
                        ]),
                        'unfinish' => route('customer.order.detail', [
                            'kode_pesanan' => $pesanan->kode_pesanan,
                        ]),
                        'error' => route('customer.order.detail', [
                            'kode_pesanan' => $pesanan->kode_pesanan,
                        ]),
                    ],
                ]);

                $pembayaran->update([
                    'midtrans_order_id' => $midtransOrderId,
                    'snap_token' => $snapToken,
                ]);
            } catch (\Throwable $e) {
                $pembayaran->forceFill([
                    'status' => 'Failed',
                    'snap_token' => null,
                ])->save();
                report($e);

                // Jangan batalkan pesanan. Status pesanan dan pembayaran tetap Pending.
                return redirect()
                    ->route('customer.order.detail', [
                        'kode_pesanan' => $pesanan->kode_pesanan,
                    ])
                    ->with(
                        'error',
                        'QRIS belum dapat disiapkan. Pesanan tetap menunggu pembayaran; silakan coba lagi.'
                    );
            }
        }
        
        $response = redirect()->route('customer.order.detail', [
            'kode_pesanan' => $pesanan->kode_pesanan,
        ]);

        if ($metode === 'midtrans') {
            $response->with('open_midtrans', true);
        }

        return $response;

        // return redirect()
        //     ->route('customer.order.detail', [
        //         'kode_pesanan' => $pesanan->kode_pesanan,
        //     ])
        //     ->when($metode === 'midtrans', fn ($response) => $response->with('open_midtrans', true));
    }
    
    public function orderDetail($kode_pesanan, PendingSelfOrderExpiryService $expiryService)
    {
        $pesanan = Pesanan::with(['detailPesanan.menu', 'meja', 'pembayaran'])
            ->where('kode_pesanan', $kode_pesanan)
            ->firstOrFail();

        app(MidtransPaymentMethod::class)
            ->syncSelfOrder($pesanan);
        $expiryService->expireIfDue($pesanan);

        $pesanan->refresh()->load([
            'detailPesanan.menu',
            'meja',
            'pembayaran',
        ]);

        $fullPayment = $pesanan->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
            ->latest('id')
            ->first();

        $latestPayment = $pesanan->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'full'")
            ->whereNotNull('midtrans_order_id')
            ->latest('id')
            ->first();

        $st = strtolower(trim((string) $pesanan->status));
        $statusPembayaran = strtolower(trim((string) ($fullPayment?->status ?? '')));
        $metodePembayaran = strtolower(trim((string) ($fullPayment?->metode ?? '')));
        $isCashPayment = $metodePembayaran === 'tunai';

        $snapToken = strtolower(trim((string) ($latestPayment?->status ?? ''))) === 'pending'
            ? $latestPayment?->snap_token
            : null;

        $canRetryPayment = $st === 'pending'
            && !$isCashPayment
            && strtolower(trim((string) ($latestPayment?->status ?? ''))) === 'failed';

        $midtransProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );

        return view('customer.order_detail', compact(
            'pesanan',
            'snapToken',
            'canRetryPayment',
            'st',
            'statusPembayaran',
            'metodePembayaran',
            'isCashPayment',
            'midtransProduction'
        ));
    }

    public function orderStatus(
        string $kode_pesanan,
        PendingSelfOrderExpiryService $expiryService
    )
    {
        $pesanan = Pesanan::where('kode_pesanan', $kode_pesanan)->firstOrFail();
        $expiryService->expireIfDue($pesanan);
        $pesanan->refresh();

        return response()->json([
            'status' => strtolower($pesanan->status),
        ]);
    }

    
}
