@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')

@section('content')
<main class="mx-auto max-w-4xl p-6">
    <h1 class="mb-2 text-2xl font-black">Pengaturan Aplikasi</h1>
    <p class="mb-6 text-sm text-stone-600">Atur batas waktu pembayaran dan batas operasional.</p>
    @if(session('success')) <div class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="mb-4 rounded bg-red-100 p-3 text-red-800">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4 rounded-xl border-2 border-stone-900 bg-white p-6">
        @csrf @method('PUT')
        @php($fields = [
            'selforder_cash_minutes' => 'Batas pembayaran tunai Self-Order (menit)',
            'selforder_qris_minutes' => 'Batas pembayaran QRIS Self-Order (menit)',
            'reservation_dp_minutes' => 'Batas pembayaran DP Reservasi (menit)',
            'reservation_minimum_dp' => 'Minimum DP Reservasi (rupiah)',
            'reservation_regular_max_guests' => 'Batas pax reservasi reguler',
            'reservation_max_guests' => 'Maksimum pax reservasi',
            'table_default_capacity' => 'Kapasitas default meja',
            'reservation_buffer_minutes' => 'Jeda aman antarreservasi meja (menit)',
            'payment_status_poll_seconds' => 'Interval pemeriksaan status pembayaran (detik)',
            'admin_notification_poll_seconds' => 'Interval pemeriksaan notifikasi admin (detik)',
        ])
        @foreach($fields as $key => $label)
            <label class="block text-sm font-bold">{{ $label }}
                <input type="number" min="1" required name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" class="mt-1 w-full rounded-lg border-2 border-stone-300 px-3 py-2">
            </label>
        @endforeach
        <button class="rounded-lg bg-[#4A2311] px-5 py-3 font-bold text-white">Simpan Pengaturan</button>
    </form>
</main>
@endsection
