<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
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
