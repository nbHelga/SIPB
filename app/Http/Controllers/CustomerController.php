<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Reservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomerController extends Controller
{
    // LANDING PAGE
    public function landing()
    {
        $menuTerbaru = Menu::where('is_bestseller', true)->latest()->take(3)->get();
        $menus = Menu::where('status', 'open')
             ->orWhere('is_reservable', true)
             ->orderByDesc('is_bestseller')
             ->get();
        return view('landing_page', compact('menuTerbaru', 'menus'));
    }

    // METHOD BARU UNTUK PENGECEKAN KETERSEDIAAN
    public function cekKetersediaan(Request $request)
    {
        $minimumDate = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        // $totalOrangHariIni = Reservasi::whereDate('tanggal', $request->tanggal)->sum('jml_org');
        // if (($totalOrangHariIni + $request->jml_org) > 88) {
        //     return back()->withErrors(['Kapasitas restoran untuk tanggal tersebut sudah penuh.']);
        // }
        $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $minimumDate],
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'jml_org' => 'required|integer|min:1',
        ]);

        if ($request->jam_mulai < '15:00' || $request->jam_mulai > '23:59'
            || $request->jam_selesai > '23:59') {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservasi hanya dapat dibuat pada jam operasional 15:00–00:00.',
            ], 422);
        }

        $tanggal = $request->tanggal;
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;
        $jumlahOrang = (int) $request->jml_org;

        // 1. Hitung total kapasitas meja yang dimiliki restoran
        // Asumsi: Jika ada tabel 'tables', hitung total kapasitas / jumlah meja
        // Atau set kapasitas maksimum per sesi jam (contoh: total 10 meja / 40 kursi)
        // $totalKapasitasKursi = 88; // Sesuaikan logika meja Anda

        // 2. Hitung jumlah orang/meja yang sudah ter-reservasi di tanggal & rentang jam tersebut
        $overlappingOrang = Reservasi::whereDate('tanggal', $tanggal)
            ->where('jam_mulai', '<', $jamSelesai) // Jam mulai baru < Jam selesai existing
            ->where('jam_selesai', '>', $jamMulai) // Jam selesai baru > Jam mulai existing
            ->whereHas('pesanan', function($q) {
                // Jangan hitung reservasi yang sudah dibatalkan
                $q->whereNotIn('status', ['Cancelled']); 
            })
            ->sum('jml_org');

        if (($overlappingOrang + $jumlahOrang) > 88) {
            $sisa = max(0, 88 - $overlappingOrang);
            return response()->json([
                'status' => 'error',
                'message' => 'Maaf, meja/kapasitas untuk jam dan tanggal tersebut sudah penuh atau tidak mencukupi (Sisa kapasitas: ' . max(0, $sisa) . ' orang).'
            ], 422);
        }
        // $terpakai = Reservasi::where('tanggal', $tanggal)
        //     ->whereHas('pesanan', function($qp) {
        //                 $qp->whereIn('status', ['Pending', 'Reserved', 'Confirmed']);
        //             }) // Sesuaikan status reservasi aktif
        //     ->where(function ($query) use ($jamMulai, $jamSelesai) {
        //         // Pengecekan bentrok jam (overlapping time)
        //         $query->whereBetween('jam_mulai', [$jamMulai, $jamSelesai])
        //               ->orWhereBetween('jam_selesai', [$jamMulai, $jamSelesai])
        //               ->orWhere(function ($q) use ($jamMulai, $jamSelesai) {
        //                   $q->where('jam_mulai', '<=', $jamMulai)
        //                     ->where('jam_selesai', '>=', $jamSelesai);
        //               });
        //     })
        //     ->sum('jml_org');

        // $sisaKapasitas = $totalKapasitasKursi - $terpakai;
        // if (($totalOrangHariIni + $request->jml_org) > 88) {
        //     return back()->withErrors(['Kapasitas restoran untuk tanggal tersebut sudah penuh.']);
        // }

        // 3. Evaluasi
        // if ($sisaKapasitas < $jumlahOrang) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Maaf, meja/kapasitas untuk jam dan tanggal tersebut sudah penuh atau tidak mencukupi (Sisa kapasitas: ' . max(0, $sisaKapasitas) . ' orang).'
        //     ], 422);
        // }

        // Jika lolos, kirim URL redirect ke form reservasi
        $redirectUrl = route('customer.reservasi.form', [
            'tanggal' => $tanggal,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'jml_org' => $jumlahOrang,
        ]);

        return response()->json([
            'status' => 'success',
            'redirect_url' => $redirectUrl
        ]);
    }


    // public function midtransNotification(Request $request)
    // {
    //     MidtransConfig::$serverKey = env('MIDTRANS_SERVER_KEY');
    //     MidtransConfig::$isProduction = env('MIDTRANS_IS_PRODUCTION') === 'true';

    //     try {
    //         // Tangkap notifikasi resmi dari Midtrans
    //         $notif = new \Midtrans\Notification();
    //         $transactionStatus = $notif->transaction_status ?? null;
    //         $orderIdRaw = $notif->order_id ?? null;
    //         $grossAmount = $notif->gross_amount ?? 0;
    //     } catch (\Exception $e) {
    //         // Jika dipanggil oleh tombol "Test" Midtrans (payload kosong),
    //         // kembalikan HTTP 200 OK agar tes Midtrans BERHASIL (Hijau)
    //         return response()->json([
    //             'status' => 'ok',
    //             'message' => 'Notification endpoint is active (Test ping received).'
    //         ], 200);
    //     }

    //     if (!$orderIdRaw) {
    //         return response()->json(['status' => 'ok', 'message' => 'No order ID provided.'], 200);
    //     }

    //     // ====================================================
    //     // SKENARIO 1: PEMBAYARAN SELF-ORDER (Format: ORDER-{id}-XXX)
    //     // ====================================================
    //     if (str_starts_with($orderIdRaw, 'ORDER-')) {
    //         if (preg_match('/ORDER\-(\d+)\-/', $orderIdRaw, $matches)) {
    //             $pesananId = (int) $matches[1];
    //             $pesanan = Pesanan::find($pesananId);

    //             if ($pesanan) {
    //                 if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
    //                     $pesanan->update(['status' => 'Paid']);
    //                 } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
    //                     $pesanan->update(['status' => 'Cancelled']);
    //                 }
    //             }
    //         }
    //     } 
        
    //     // ====================================================
    //     // SKENARIO 2: PEMBAYARAN DP RESERVASI (Format: RESERV-{id}-XXX)
    //     // ====================================================
    //     elseif (str_starts_with($orderIdRaw, 'RESERV-')) {
    //         if (preg_match('/RESERV\-(\d+)\-/', $orderIdRaw, $matches)) {
    //             $reservasiId = (int) $matches[1];
    //             $reservasi = Reservasi::find($reservasiId);

    //             if ($reservasi) {
    //                 $pesanan = Pesanan::where('id_reservasi', $reservasi->id)->first();

    //                 if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
    //                     $reservasi->update([
    //                         'dp_bayar' => $grossAmount,
    //                         'status'   => 'confirmed'
    //                     ]);
                        
    //                     if ($pesanan) {
    //                         $pesanan->update(['status' => 'Paid']);
    //                     }
    //                 } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
    //                     $reservasi->update(['status' => 'cancelled']);
    //                     if ($pesanan) {
    //                         $pesanan->update(['status' => 'Cancelled']);
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json(['status' => 'ok', 'message' => 'Notification processed successfully'], 200);
    // }

// public function midtransNotification(Request $request)
//     {
//         MidtransConfig::$serverKey = env('MIDTRANS_SERVER_KEY');
//         MidtransConfig::$isProduction = env('MIDTRANS_IS_PRODUCTION') === 'true';

//         try {
//             $notif = new Notification();
//             $transactionStatus = $notif->transaction_status ?? null;
//             $orderIdRaw = $notif->order_id ?? null;
//         } catch (\Throwable $e) {
//             Log::error('Midtrans notification gagal diproses', [
//                 'message' => $e->getMessage(),
//                 'payload' => $request->all(),
//             ]);

//             return response()->json(['message' => 'Notification processing failed'], 500);
//         }

//         if (!$orderIdRaw) {
//             return response()->json(['status' => 'ok', 'message' => 'No order ID'], 200);
//         }

//         // ====================================================
//         // SKENARIO 1: SELF-ORDER (Format: ORDER-{id}-XXX)
//         // ====================================================
//         if (str_starts_with($orderIdRaw, 'ORDER-')) {
//             if (preg_match('/ORDER\-(\d+)\-/', $orderIdRaw, $matches)) {
//                 $pesananId = (int) $matches[1];
//                 $pesanan = Pesanan::find($pesananId);

//                 if ($pesanan) {
//                     if (in_array($transactionStatus, ['capture', 'settlement'])) {
//                         // Jika Lunas -> Update status ke Paid
//                         $pesanan->update(['status' => 'Paid']);
//                     } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
//                         // Jika Kedaluwarsa/Batal -> Hapus Detail & Pesanan dari Database
//                         $pesanan->detailPesanan()->delete(); // Hapus child record dulu (foreign key)
//                         $pesanan->delete();                  // Hapus parent record
//                     }
//                 }
//             }
//         } 
        
//         // ====================================================
//         // SKENARIO 2: RESERVASI (Format: RESERV-{id}-XXX)
//         // ====================================================
//         elseif (str_starts_with($orderIdRaw, 'RESERV-')) {
//             if (preg_match('/RESERV\-(\d+)\-/', $orderIdRaw, $matches)) {
//                 $reservasiId = (int) $matches[1];
//                 $reservasi = Reservasi::find($reservasiId);

//                 if ($reservasi) {
//                     $pesanan = Pesanan::where('id_reservasi', $reservasi->id)->first();
//                     $pembayaran = Pembayaran::where('id_pesanan', $pesanan->id)->first();

//                     if (in_array($transactionStatus, ['capture', 'settlement'])) {
//                         $reservasi->update([
//                             'dp_bayar' => $notif->gross_amount ?? 0,
//                         ]);
//                         if ($pesanan) {
//                             $pesanan->update(['status' => 'Reserved']);
//                             $pembayaran->update(['status' => 'Success']);
//                         }
//                         $reservasi->refresh()->load('customer');

//                         $fonnte = app(FonnteService::class);
//                         $detailUrl = route('customer.reservasi.detail', ['id' => $reservasi->id]);

//                         if (!$reservasi->customer_notif) {
//                             // Tandai sebelum request agar callback berulang tidak mengirim duplikat.
//                             $reservasi->update(['customer_notif' => true]);

//                             $fonnte->send(
//                                 $reservasi->customer->nomor_telp ?? null,
//                                 "Pembayaran DP reservasi Anda telah diterima.\n"
//                                 . "Detail reservasi: {$detailUrl}"
//                             );
//                         }

//                         if (!$reservasi->admin_notif) {
//                             $reservasi->update(['admin_notif' => true]);

//                             $fonnte->send(
//                                 env('FONNTE_PHONE'),
//                                 "Reservasi baru telah membayar DP.\n"
//                                 . "Pelanggan: " . ($reservasi->customer->nama ?? 'Pelanggan') . "\n"
//                                 . "Tanggal: {$reservasi->tanggal}\n"
//                                 . "Jam: {$reservasi->jam_mulai}–{$reservasi->jam_selesai}\n"
//                                 . "Kelola reservasi: " . route('admin.reservasi', ['id' => $reservasi->id])
//                             );
//                         }
//                     } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
//                         // Untuk reservasi yang batal/expired, hapus dari database
//                         if ($pesanan) {
//                             $pesanan->detailPesanan()->delete();
//                             $pesanan->delete();
//                         }
//                         $reservasi->delete();
//                     }
//                 }
//             }
//         }

//         return response()->json(['status' => 'ok', 'message' => 'Processed successfully'], 200);
//     }
    // }
}
