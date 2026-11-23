<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\DetailPesanan;
use App\Models\Reservasi;
use App\Models\Meja;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminController extends Controller
{
    // DASHBOARD
    public function dashboard(Request $request)
    {
        $selectedDate = Carbon::today();
        $previousDate = $selectedDate->copy()->subDay();
        $recentOrders = Pesanan::with(['user', 'reservasi.customer', 'meja'])
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // 1. MENGHITUNG METRIK 4 KOTAK (TANGGAL FILTER VS HARI SEBELUMNYA)
        $metrik = [
            'cash' => ['selected' => 0, 'previous' => 0],
            'non_cash' => ['selected' => 0, 'previous' => 0],
            'total_order' => ['selected' => 0, 'previous' => 0],
            'visitors' => ['selected' => 0, 'previous' => 0],
        ];

        foreach (['selected' => $selectedDate, 'previous' => $previousDate] as $key => $date) {
            $completed = Pesanan::with(['reservasi', 'pembayaran'])
            ->where('status', 'Completed')
            ->whereDate('completed_at', $date)
            ->get();

            foreach ($completed as $pesanan) {
                foreach ($pesanan->pembayaran->where('status', 'Success') as $payment) {
                    if (strtolower($payment->metode) === 'tunai') {
                        $metrik['cash'][$key] += (float) $payment->nominal;
                    } else {
                        $metrik['non_cash'][$key] += (float) $payment->nominal;
                    }
                }

                if ($pesanan->reservasi) {
                    $metrik['visitors'][$key] += (int) $pesanan->reservasi->jml_org;
                } else {
                    $metrik['total_order'][$key]++;
                    $metrik['visitors'][$key] += 2;
                }
            }
        }

        // Hitung Persentase Naik/Turun
        $percentage = [];
        foreach (['cash', 'non_cash', 'total_order', 'visitors'] as $key) {
            $selected = $metrik[$key]['selected'];
            $previous = $metrik[$key]['previous'];
            if ($previous > 0) {
                $percentage[$key] = round((($selected - $previous) / $previous) * 100, 1);
            } else {
                $percentage[$key] = $selected > 0 ? 100 : 0;
            }
        }

        // // 2. DATA GRAFIK PENJUALAN (7 Hari Terakhir)
        // $salesChart = ['labels' => [], 'data' => []];
        // for ($i = 6; $i >= 0; $i--) {
        //     $d = Carbon::today()->subDays($i);
        //     $salesChart['labels'][] = $d->format('d M');
            
        //     $revSelfOrder = Pesanan::whereNull('id_reservasi')->whereDate('created_at', $d)->whereNotIn('status', ['Pending', 'Cancelled'])->sum('total_harga');
        //     $revReservasi = Pesanan::whereHas('reservasi', function ($q) use ($d) {
        //         $q->whereDate('tanggal', $d);
        //     })->where('status', 'Completed')->sum('total_harga');
            
        //     $salesChart['data'][] = $revSelfOrder + $revReservasi;
        // }

        // // 3. GRAFIK METODE PEMBAYARAN (Hari Filter)
        // $paymentMethods = DB::table('pesanan')
        //     ->select('metode_pembayaran', DB::raw('SUM(total_harga) as total'))
        //     ->whereDate('created_at', $targetDate)
        //     ->whereNotIn('status', ['Pending', 'Cancelled'])
        //     ->whereNull('id_reservasi')
        //     ->groupBy('metode_pembayaran')
        //     ->pluck('total', 'metode_pembayaran')->toArray();

        // // Gabungkan dengan pelunasan reservasi
        // $pelunasanRes = Pesanan::whereHas('reservasi', function ($q) use ($targetDate) {
        //     $q->whereDate('tanggal', $targetDate);
        // })->where('status', 'Completed')->get();

        // foreach ($pelunasanRes as $pr) {
        //     $dp = (float) $pr->reservasi->dp_bayar;
        //     $sisa = (float) $pr->total_harga - $dp;
            
        //     $paymentMethods['Midtrans DP'] = ($paymentMethods['Midtrans DP'] ?? 0) + $dp;
        //     $method = $pr->metode_pelunasan ?? 'Lainnya';
        //     $paymentMethods[$method] = ($paymentMethods[$method] ?? 0) + $sisa;
        // }

        // // 4. GRAFIK PENJUALAN PER KATEGORI (Hari Filter)
        // $categorySales = DB::table('detail_pesanan')
        //     ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id')
        //     ->join('menu', 'detail_pesanan.id_menu', '=', 'menu.id')
        //     ->whereDate('pesanan.created_at', $targetDate) // Simplifikasi tanggal pesanan
        //     ->whereNotIn('pesanan.status', ['Pending', 'Cancelled'])
        //     ->select('menu.kategori', DB::raw('SUM(detail_pesanan.subtotal) as total'))
        //     ->groupBy('menu.kategori')
        //     ->pluck('total', 'menu.kategori')->toArray();

        // Admin trend charts cover this month; cashier dashboard stays focused on its four metrics.
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Tren Pendapatan Harian (Cash vs Non-Cash)
        // Pembayaran self-order dihitung dari metode_pembayaran.
        // Reservasi: DP masuk non-cash; sisa masuk sesuai metode_pelunasan.
        $completedThisMonth = Pesanan::with('reservasi')
            ->where('status', 'Completed')
            ->where('completed_at', '>=', Carbon::now()->startOfMonth())
            ->get();

        // $dailyRevenue = $completedThisMonth->groupBy(
        //     fn ($pesanan) => Carbon::parse($pesanan->completed_at)->toDateString()
        // );

        // Ganti revenueTrend:
        $revenueTrend = collect();

        // for ($date = Carbon::now()->startOfMonth(); $date->lte(Carbon::today()); $date->addDay()) {
        //     $payments = Pembayaran::where('status', 'Success')
        //         ->whereHas('pesanan', function ($q) use ($date) {
        //             $q->whereRaw('LOWER(status) = ?', ['completed'])
        //                 ->whereDate('completed_at', $date->toDateString());
        //         })->get();

        //     $revenueTrend->push((object) [
        //         'date' => $date->toDateString(),
        //         'cash' => $payments->filter(fn ($p) => strtolower($p->metode) === 'tunai')->sum('nominal'),
        //         'non_cash' => $payments->filter(fn ($p) => strtolower($p->metode) !== 'tunai')->sum('nominal'),
        //     ]);
        // }
        $paymentsData = Pembayaran::select(
                DB::raw('DATE(pembayaran.updated_at) as date'),
                DB::raw("SUM(CASE WHEN LOWER(TRIM(metode)) = 'tunai' THEN nominal ELSE 0 END) as cash"),
                DB::raw("SUM(CASE WHEN LOWER(TRIM(metode)) != 'tunai' THEN nominal ELSE 0 END) as non_cash")
            )
            ->where('pembayaran.status', 'Success')
            ->whereHas('pesanan', function ($q) use ($startOfMonth) {
                $q->where('status', 'Completed')
                ->where('completed_at', '>=', $startOfMonth);
            })
            ->groupBy('date')
            ->get()->keyBy('date');

        $revenueTrend = collect();
        for ($date = Carbon::now()->startOfMonth(); $date->lte(Carbon::today()); $date->addDay()) {
            $dateStr = $date->toDateString();
            $trend = $paymentsData->get($dateStr);
            
            $revenueTrend->push((object) [
                'date' => $dateStr,
                'cash' => $trend ? $trend->cash : 0,
                'non_cash' => $trend ? $trend->non_cash : 0,
            ]);
        }

        // 2. Jam Sibuk (Peak Dining Hours)
        $peakHours = Pesanan::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(id) as total_order')
        )->where('created_at', '>=', $startOfMonth)
        ->whereBetween(DB::raw('HOUR(created_at)'), [15, 23])
        ->groupBy('hour')->orderBy('hour')->get();

        // 3. Proporsi Metode Pembayaran
        // Query 1: Menghitung dari kolom metode_pembayaran (termasuk Self-Order & DP Reservasi)
        $queryPembayaran = DB::table('pesanan')
            ->select('metode_pembayaran as metode', DB::raw('COUNT(id) as total'))
            ->where('created_at', '>=', $startOfMonth)
            ->whereIn('status', ['Completed'])
            ->whereNotNull('metode_pembayaran')
            ->groupBy('metode_pembayaran');

        // Query 2: Menghitung dari kolom metode_pelunasan (khusus transaksi pelunasan reservasi)
        $queryPelunasan = DB::table('pesanan')
            ->select('metode_pelunasan as metode', DB::raw('COUNT(id) as total'))
            ->where('created_at', '>=', $startOfMonth)
            ->whereIn('status', ['Completed'])
            ->whereNotNull('metode_pelunasan')
            ->groupBy('metode_pelunasan');

        // Gabungkan kedua query dan jumlahkan total per nama metode
        $paymentMethods = Pembayaran::select('metode', DB::raw('SUM(nominal) as total'))
            ->where('status', 'Success')
            ->whereHas('pesanan', function ($q) use ($startOfMonth) {
                $q->where('status', 'Completed')
                    ->where('completed_at', '>=', $startOfMonth);
            })
            ->groupBy('metode')
            ->get();

        // 4. Volume Self-Order vs Reservasi
        $orderVolume = Pesanan::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw("SUM(CASE WHEN EXISTS (
                SELECT 1 FROM reservasi r WHERE r.id_pesanan = pesanan.id
            ) THEN 0 ELSE 1 END) as self_order"),
            DB::raw("SUM(CASE WHEN EXISTS (
                SELECT 1 FROM reservasi r WHERE r.id_pesanan = pesanan.id
            ) THEN 1 ELSE 0 END) as reservasi")
        )
        ->whereRaw('LOWER(status) = ?', ['completed'])
        ->where('completed_at', '>=', $startOfMonth)
        ->groupBy('date')
        ->orderBy('date')
        ->get();


        // 5. Top 5 Menu Paling Laris Bulan Ini
        $topMenus = DetailPesanan::select(
            'menu.nama',
            DB::raw('SUM(detail_pesanan.jumlah) as total_porsi')
        )
        ->join('menu', 'detail_pesanan.id_menu', '=', 'menu.id')
        ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id')
        ->whereRaw('LOWER(TRIM(pesanan.status)) = ?', ['completed'])
        ->where('pesanan.completed_at', '>=', $startOfMonth)
        ->where('pesanan.completed_at', '<', Carbon::today()->addDay())
        ->groupBy('menu.nama')
        ->orderByDesc('total_porsi')
        ->limit(5)
        ->get();

        // Mengambil semua data meja
        $mejas = Meja::orderBy('nomor_meja', 'asc')->get();

        // Jika ingin me-generate QR Code untuk seluruh meja di Admin/Dashboard:
        // $mejasWithQr = $mejas->map(function ($meja) {
        //     // Gunakan parameter 'tableNumber' agar sesuai dengan controller SelfOrderController
        //     $urlSelfOrder = route('customer.selforder', ['tableNumber' => $meja->nomor_meja]);
            
        //     // Atau jika menggunakan url():
        //     // $urlSelfOrder = url('/customer/selforder?tableNumber=' . $meja->nomor_meja);

        //     return [
        //         'meja' => $meja,
        //         'url' => $urlSelfOrder,
        //         'qr_code' => QrCode::size(200)->generate($urlSelfOrder),
        //     ];
        // });

        $isKasir = in_array(
            strtolower(trim((string) auth()->user()?->status)),
            ['kasir', 'cashier'],
            true
        );

        return view('admin.dashboard', compact(
            'mejas',
            'metrik',
            'percentage',
            'revenueTrend',
            'peakHours',
            'paymentMethods',
            'orderVolume',
            'topMenus',
            'recentOrders',
            'isKasir'
        ));
    }

    // Endpoint untuk fetch sales data dengan range yang berbeda
    public function salesData(Request $request)
    {
        $days = $request->get('days', 7);

        $chartLabels = [];
        $chartData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartLabels[] = $date->locale('id')->translatedFormat('d M');

            $sales = Pesanan::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total_harga');

            $chartData[] = $sales ?? 0;
        }

        return response()->json([
            'labels' => $chartLabels,
            'data' => $chartData
        ]);
    }
    public function notifications(Request $request): JsonResponse
    {
        $sessionKey = $request->session()->get('admin_notification_session');
        $loginAt = $request->session()->get('admin_logged_in_at');

        if (!$request->user() || !$sessionKey || !$loginAt) {
            return response()->json([
                'session_key' => null,
                'orders' => [],
                'reservations' => [],
            ]);
        }

        $cutoff = Carbon::parse($loginAt);

        $orders = Pesanan::query()
            ->whereDoesntHave('reservasi')
            ->where(function ($query) use ($cutoff) {
                // Self-order tunai: Pending dan dibuat selama sesi login ini.
                $query->where(function ($cash) use ($cutoff) {
                    $cash->where('pesanan.status', 'Pending')
                        ->where('pesanan.created_at', '>=', $cutoff)
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->where('jenis', 'Full')
                                ->where('metode', 'tunai')
                                ->where('status', 'Pending');
                        });
                })

                // Self-order Midtrans: baru dihitung setelah berstatus Paid.
                ->orWhere(function ($midtrans) use ($cutoff) {
                    $midtrans->where('pesanan.status', 'Paid')
                        ->where('pesanan.updated_at', '>=', $cutoff)
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->where('jenis', 'Full')
                                ->where('metode', 'other_qris')
                                ->where('status', 'Success');
                        });
                });
            })
            ->pluck('pesanan.id')
            ->map(fn ($id) => "pesanan-{$id}")
            ->values();

        // Reservasi baru yang menunggu approval dan reservasi yang DP-nya berhasil.
        $reservations = Reservasi::query()
            ->whereHas('pesanan', function ($order) use ($cutoff) {
                $order->whereIn('status', ['Waiting', 'Reserved'])
                    ->where('updated_at', '>=', $cutoff);
            })
            ->pluck('id')
            ->map(fn ($id) => "reservasi-{$id}")
            ->values();

        return response()->json([
            'session_key' => $sessionKey,
            'orders' => $orders,
            'reservations' => $reservations,
        ]);
    }

    public function dailyReport(Request $request)
    {
        $filters = $request->validate([
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $tanggal = $filters['tanggal'] ?? today()->toDateString();
        $search = trim((string) ($filters['search'] ?? ''));

        $summary = DB::table('detail_pesanan as dp')
            ->join('pesanan as p', 'p.id', '=', 'dp.id_pesanan')
            ->where('p.status', 'Completed')
            ->whereDate('p.completed_at', $tanggal)
            ->selectRaw('COALESCE(SUM(dp.jumlah), 0) as total_porsi')
            ->selectRaw("COALESCE(SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM reservasi r WHERE r.id_pesanan = p.id) THEN dp.jumlah ELSE 0 END), 0) as porsi_selforder")
            ->selectRaw("COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM reservasi r WHERE r.id_pesanan = p.id) THEN dp.jumlah ELSE 0 END), 0) as porsi_reservasi")
            ->first();

        $rowsQuery = DB::table('detail_pesanan as dp')
            ->join('pesanan as p', 'p.id', '=', 'dp.id_pesanan')
            ->join('menu as m', 'm.id', '=', 'dp.id_menu')
            ->leftJoin('users as u', 'u.id', '=', 'p.id_user')
            ->where('p.status', 'Completed')
            ->whereDate('p.completed_at', $tanggal)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($where) use ($search) {
                    $where->where('p.kode_pesanan', 'like', "%{$search}%")
                        ->orWhere('m.nama', 'like', "%{$search}%")
                        ->orWhere('u.username', 'like', "%{$search}%");
                });
            })
            ->select([
                'p.created_at',
                'p.kode_pesanan',
                'm.nama as nama_menu',
                'dp.jumlah',
                'dp.subtotal as total_harga',
                'u.username as pengelola',
            ])
            ->orderByDesc('p.created_at')
            ->orderByDesc('dp.id');

        $rekapan = $rowsQuery->paginate(25)->withQueryString();

        return view('admin.rekapan', compact('tanggal', 'search', 'summary', 'rekapan'));
    }

    public function cetak(Request $request)
    {
        // Tanggal laporan (berdasarkan yang difilter di dashboard)
        $tanggal = $request->query('tanggal', now()->format('Y-m-d'));

        // ====================================================================
        // 1. RINCIAN MENU TERJUAL (Berdasarkan pesanan yang sudah Selesai/Completed)
        // ====================================================================
        $menuTerjual = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id')
            ->join('menu', 'detail_pesanan.id_menu', '=', 'menu.id')
            ->whereDate('pesanan.completed_at', $tanggal)
            ->where('pesanan.status', 'Completed') // Hanya yang sudah selesai
            ->select(
                'menu.nama',
                DB::raw('SUM(detail_pesanan.jumlah) as total_porsi'),
                DB::raw('SUM(detail_pesanan.subtotal) as total_subtotal')
            )
            ->groupBy('menu.id', 'menu.nama')
            ->get();

        $totalPenjualanMenu = $menuTerjual->sum('total_subtotal');

        // ====================================================================
        // 2. RINCIAN PENDAPATAN (Berdasarkan pesanan yang sudah Selesai/Completed)
        // ====================================================================
        $pendapatan = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.id_pesanan', '=', 'pesanan.id')
            ->whereDate('pesanan.completed_at', $tanggal)
            ->where('pesanan.status', 'Completed')
            ->where('pembayaran.status', 'Success') // Hanya pembayaran sukses
            ->select(
                'pembayaran.jenis',
                'pembayaran.metode',
                'pembayaran.midtrans_order_id',
                DB::raw('SUM(pembayaran.nominal) as total_nominal')
            )
            ->groupBy('pembayaran.jenis', 'pembayaran.metode', 'pembayaran.midtrans_order_id')
            ->get();

        $totalPendapatan = $pendapatan->sum('total_nominal');
        $midtransPendapatan = $pendapatan
            ->filter(fn ($payment) => !empty($payment->midtrans_order_id))
            ->groupBy(fn ($payment) => strtolower(trim((string) $payment->metode)))
            ->map(fn ($payments) => $payments->sum('total_nominal'));
        $totalMidtrans = $midtransPendapatan->sum();
        $pelunasanNonTunai = $pendapatan
            ->filter(fn ($payment) =>
                strtolower(trim((string) $payment->jenis)) === 'pelunasan'
                && strtolower(trim((string) $payment->metode)) !== 'tunai'
                && empty($payment->midtrans_order_id)
            )
            ->groupBy(fn ($payment) => strtolower(trim((string) $payment->metode)))
            ->map(fn ($payments) => $payments->sum('total_nominal'));
        $totalTunai = $pendapatan
            ->filter(fn ($payment) => strtolower(trim((string) $payment->metode)) === 'tunai')
            ->sum('total_nominal');

        return view('admin.daily_report', compact(
            'tanggal',
            'menuTerjual',
            'totalPenjualanMenu',
            'pendapatan',
            'totalPendapatan',
            'totalTunai',
            'midtransPendapatan',
            'totalMidtrans',
            'pelunasanNonTunai'
        ));
    }
}
