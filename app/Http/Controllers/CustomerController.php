<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Meja;
use App\Models\Reservasi;
use App\Services\ReservationCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomerController extends Controller
{
    // LANDING PAGE
    public function landing()
    {
        $menuTerbaru = Menu::where('is_bestseller', true)->latest()->take(3)->get();
        $maxGuests = ReservationCapacityService::maxGuestsLimit();
        $menus = Menu::where('status', 'open')
             ->orWhere('is_reservable', true)
             ->orderByDesc('is_bestseller')
             ->get();
        return view('landing_page', compact('menuTerbaru', 'menus', 'maxGuests'));
    }

    // METHOD BARU UNTUK PENGECEKAN KETERSEDIAAN
    public function cekKetersediaan(Request $request, ReservationCapacityService $capacityService)
    {
        $minimumDate = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $maxGuests = ReservationCapacityService::maxGuestsLimit();

        $validator = Validator::make($request->all(), [
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $minimumDate],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'jml_org' => ['required', 'integer', 'min:1', 'max:' . $maxGuests],
        ], [
            'tanggal.after_or_equal' => 'Reservasi hanya dapat dibuat mulai besok (H+1).',
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
            'jml_org.max' => 'Jumlah orang maksimal adalah ' . $maxGuests . ' orang.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->jam_mulai < '15:00' || $request->jam_mulai > '23:59'
            || $request->jam_selesai < '15:00' || $request->jam_selesai > '23:59') {
            return response()->json([
                'status' => 'error',
                'message' => 'Jam mulai dan selesai harus berada dalam jam operasional 15.00–23.59 WIB.',
            ], 422);
        }

        $tanggal = $request->tanggal;
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;
        $jumlahOrang = (int) $request->jml_org;
        // $maxAutoBookingGuests = 10; // Set batas rombongan reguler
        // if ($jumlahOrang > $maxAutoBookingGuests) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Reservasi untuk lebih dari ' . $maxAutoBookingGuests . ' orang membutuhkan penyesuaian meja. Silakan hubungi Admin via WhatsApp: 08123456789.',
        //     ], 422);
        // }

        $largeReservation = $jumlahOrang > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests');
        $capacity = $largeReservation ? ['available' => true] : $capacityService->check($tanggal, $jamMulai, $jamSelesai, $jumlahOrang);

        if (!$capacity['available']) {
            return response()->json([
                'status' => 'error',
                'message' => $capacity['message'] ?? 'Kapasitas meja tidak mencukupi untuk jadwal tersebut. Silakan hubungi admin.',
            ], 422);
        }

        // Lolos semua validasi, arahkan ke form
        $redirectUrl = route('customer.reservasi.form', [
            'tanggal' => $tanggal,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'jml_org' => $jumlahOrang,
        ]);

        return response()->json([
            'status' => 'success',
            'redirect_url' => $redirectUrl,
            'large_reservation' => $largeReservation,
            'guest_count' => $jumlahOrang,
        ]);
    }
}
