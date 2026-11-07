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
        $filterTanggal = $request->validate([
            'tanggal' => ['nullable', 'date'],
        ])['tanggal'] ?? today()->toDateString();
        $selectedDate = Carbon::parse($filterTanggal);
        $previousDate = $selectedDate->copy()->subDay();

        // 1. MENGHITUNG METRIK 4 KOTAK (TANGGAL FILTER VS HARI SEBELUMNYA)
        $metrik = [
            'cash' => ['selected' => 0, 'previous' => 0],
            'non_cash' => ['selected' => 0, 'previous' => 0],
            'total_order' => ['selected' => 0, 'previous' => 0],
            'visitors' => ['selected' => 0, 'previous' => 0],
        ];

        foreach (['selected' => $selectedDate, 'previous' => $previousDate] as $key => $date) {
            $completed = Pesanan::with(['reservasi', 'pembayaran'])
            ->whereRaw('LOWER(status) = ?', ['completed'])
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

        // 5. TOP 5 MENU TERJUAL & TABEL PORSI (Hari Filter)
        $menuPortions = DB::table('menu as m')
            ->leftJoin('detail_pesanan as dp', 'dp.id_menu', '=', 'm.id')
            ->leftJoin('pesanan as p', function ($join) use ($filterTanggal) {
                $join->on('p.id', '=', 'dp.id_pesanan')
                    ->whereRaw('LOWER(p.status) = ?', ['completed'])
                    ->whereDate('p.completed_at', $filterTanggal);
            })
            ->select(
                'm.id',
                'm.nama',
                'm.kategori',
                DB::raw('COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN dp.jumlah ELSE 0 END), 0) as total_porsi')
            )
            ->groupBy('m.id', 'm.nama', 'm.kategori')
            ->orderByDesc('total_porsi')
            ->orderBy('m.nama')
            ->paginate(10)
            ->withQueryString();

        $top5Menus = $menuPortions->take(5);

        // return view('admin.dashboard', compact(
        //     'metrik', 'percentage', 'salesChart', 'paymentMethods', 'categorySales', 'menuPortions', 'top5Menus', 'filterTanggal'
        // ));
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Tren Pendapatan Harian (Cash vs Non-Cash)
        // Pembayaran self-order dihitung dari metode_pembayaran.
        // Reservasi: DP masuk non-cash; sisa masuk sesuai metode_pelunasan.
        $completedThisMonth = Pesanan::with('reservasi')
            ->where('status', 'Completed')
            ->where('completed_at', '>=', Carbon::now()->startOfMonth())
            ->get();

        $dailyRevenue = $completedThisMonth->groupBy(
            fn ($pesanan) => Carbon::parse($pesanan->completed_at)->toDateString()
        );

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
                $q->whereRaw('LOWER(status) = ?', ['completed'])
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
                $q->whereRaw('LOWER(status) = ?', ['completed'])
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


        // 5. Top 5 Menu Paling Laris
        $topMenus = DetailPesanan::select(
            'menu.nama',
            DB::raw('SUM(detail_pesanan.jumlah) as total_porsi')
        )
        ->join('menu', 'detail_pesanan.id_menu', '=', 'menu.id')
        ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id')
        ->whereRaw('LOWER(pesanan.status) = ?', ['completed'])
        ->whereDate('pesanan.completed_at', Carbon::today())
        ->groupBy('menu.nama')
        ->orderByDesc('total_porsi')
        ->limit(5)
        ->get();

        // Mengambil semua data meja
        $mejas = Meja::orderBy('nomor_meja', 'asc')->get();

        // Jika ingin me-generate QR Code untuk seluruh meja di Admin/Dashboard:
        $mejasWithQr = $mejas->map(function ($meja) {
            // Gunakan parameter 'tableNumber' agar sesuai dengan controller SelfOrderController
            $urlSelfOrder = route('customer.selforder', ['tableNumber' => $meja->nomor_meja]);
            
            // Atau jika menggunakan url():
            // $urlSelfOrder = url('/customer/selforder?tableNumber=' . $meja->nomor_meja);

            return [
                'meja' => $meja,
                'url' => $urlSelfOrder,
                'qr_code' => QrCode::size(200)->generate($urlSelfOrder),
            ];
        });

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
            'menuPortions',
            'filterTanggal',
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
            $chartLabels[] = $date->format('d/m');

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
                    $cash->whereRaw("LOWER(TRIM(pesanan.status)) = 'pending'")
                        ->where('pesanan.created_at', '>=', $cutoff)
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                                ->whereRaw("LOWER(TRIM(metode)) = 'tunai'")
                                ->whereRaw("LOWER(TRIM(status)) = 'pending'");
                        });
                })

                // Self-order Midtrans: baru dihitung setelah berstatus Paid.
                ->orWhere(function ($midtrans) use ($cutoff) {
                    $midtrans->whereRaw("LOWER(TRIM(pesanan.status)) = 'paid'")
                        ->where('pesanan.updated_at', '>=', $cutoff)
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->whereRaw("LOWER(TRIM(jenis)) = 'full'")
                                ->whereRaw("LOWER(TRIM(metode)) = 'other_qris'")
                                ->whereRaw("LOWER(TRIM(status)) = 'success'");
                        });
                });
            })
            ->pluck('pesanan.id')
            ->map(fn ($id) => "pesanan-{$id}")
            ->values();

        // Reservasi hanya masuk setelah status pesanan menjadi Reserved.
        $reservations = Reservasi::query()
            ->whereHas('pesanan', function ($order) use ($cutoff) {
                $order->whereRaw("LOWER(TRIM(status)) = 'reserved'")
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
                'pembayaran.metode',
                DB::raw('SUM(pembayaran.nominal) as total_nominal')
            )
            ->groupBy('pembayaran.metode')
            ->get();

        $totalPendapatan = $pendapatan->sum('total_nominal');
        $totalTunai = $pendapatan->where('metode', 'tunai')->sum('total_nominal');
        $totalNonTunai = $pendapatan->where('metode', '!=', 'tunai')->sum('total_nominal');

        return view('admin.daily_report', compact(
            'tanggal',
            'menuTerjual',
            'totalPenjualanMenu',
            'pendapatan',
            'totalPendapatan',
            'totalTunai',
            'totalNonTunai'
        ));
    }
}
