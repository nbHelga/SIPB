@extends('layouts.landing')

@section('title', 'Detail Reservasi - BigBang Eatery')

@section('content')
<div class="w-full max-w-[95%] md:max-w-7xl mx-auto px-2 md:px-4 pt-20 mt-4 md:pt-24 pb-5 md:pb-10 flex flex-col justify-start space-y-5 md:space-y-8">

    @if(session('success'))
        <div class="p-4 bg-emerald-100 border-2 border-emerald-900 text-emerald-900 font-bold rounded-xl shadow-[4px_4px_0_0_rgba(16,185,129,0.3)] text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- =========================================================================
         UNIFIED ACTION BANNER (CARD FULL WIDTH SEJAJAR DENGAN KONTEN BAWAH)
         ========================================================================= --}}
    @if($statusPesanan === 'pending')
        <div class="w-full rounded-2xl border-4 {{ $statusPembayaranDp === 'failed' ? 'border-red-600 bg-red-50' : 'border-amber-500 bg-amber-50' }} p-6 md:p-8 md:px-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col items-center text-center transition-all">
            
            <div class="flex flex-col items-center gap-3 text-center font-black md:flex-row md:justify-center md:gap-4">
                @if($statusPembayaranDp === 'failed')
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-3xl" aria-hidden="true">⚠️</span>
                        <h2 class="text-xl text-red-700 md:text-2xl">Pembayaran DP Gagal</h2>
                    </div>
                @else
                    <div class="flex items-center justify-center gap-3">
                        <svg class="h-8 w-8 animate-spin text-amber-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <h2 class="text-xl text-amber-800 md:text-2xl">Menunggu Pembayaran</h2>
                    </div>
                @endif

                @if($statusPembayaranDp !== 'failed' || $canRetryPayment)
                    <div
                        id="reservation-countdown"
                        data-expires-at="{{ $reservationExpiresAt }}"
                        class="rounded-lg border-2 border-amber-600 bg-amber-200 px-4 py-2 text-base font-black tracking-wider text-amber-900 shadow-[3px_3px_0_0_rgba(217,119,6,0.3)] md:text-lg"
                        aria-label="Sisa waktu pembayaran"
                    >
                        --:--:--
                    </div>
                @endif
            </div>

            <p class="mt-4 text-sm md:text-base font-bold {{ $statusPembayaranDp === 'failed' ? 'text-red-900' : 'text-amber-950' }} max-w-xl leading-relaxed">
                @if($statusPembayaranDp === 'failed')
                    Batas waktu pembayaran DP telah habis atau transaksi dibatalkan oleh sistem. Silakan tekan tombol <strong>Ulangi Pembayaran</strong> untuk mencoba kembali.
                @else
                    Segera selesaikan pembayaran DP Anda sebelum waktu pada layar habis. Setelah pembayaran berhasil, jadwal meja Anda akan otomatis dikonfirmasi.
                @endif
            </p>

            <p id="reservation-expired-message" class="mt-3 hidden text-sm font-bold text-red-700" role="status">
                Waktu pembayaran reservasi telah berakhir. Muat ulang halaman untuk melihat status terbaru.
            </p>

            <div class="mt-7 flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
                @if(!empty($snapToken))
                    <button type="button" onclick="openReservationSnap()" class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-[#4A2311] px-8 py-3.5 font-black text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-[#8B5A2B] active:translate-y-1 active:shadow-none">
                        💳 Lanjutkan Pembayaran
                    </button>
                @elseif($canRetryPayment)
                    <form action="{{ route('customer.reservasi.retry_payment', ['id' => $reservasi->id]) }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-emerald-500 px-8 py-3.5 font-black text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-emerald-600 active:translate-y-1 active:shadow-none">
                            🔄 Ulangi Pembayaran
                        </button>
                    </form>
                @endif
            </div>
        </div>

    {{-- UNIFIED BANNER CANCELLED --}}
    @elseif($statusPesanan === 'cancelled')
        <div class="w-full rounded-2xl border-4 border-stone-400 bg-stone-100 p-6 md:p-8 md:px-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col items-center text-center">
            <div class="flex items-center justify-center gap-3 font-black">
                <span class="text-3xl" aria-hidden="true">❌</span>
                <h2 class="text-xl md:text-2xl text-stone-700">Reservasi Dibatalkan</h2>
            </div>
            <p class="mt-3 text-sm md:text-base font-bold text-stone-600 max-w-xl">
                Reservasi ini telah dibatalkan secara manual atau batas waktu pembayaran DP telah berakhir.
            </p>
            <div class="mt-7 flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('customer.reservasi.form') }}" class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-white px-8 py-3.5 font-black text-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-stone-50 active:translate-y-1 active:shadow-none">
                    🗓️ Buat Reservasi Baru
                </a>
            </div>
        </div>

    {{-- STEPPER UNTUK RESERVASI BERJALAN --}}
    @else
        <div class="py-2">
            <div class="relative mx-auto flex w-full max-w-2xl items-start">
                <div class="absolute left-[16.66%] right-[16.66%] top-4 h-1 -translate-y-1/2 bg-stone-200 md:top-5"></div>
                <div class="absolute left-[16.66%] top-4 h-1 -translate-y-1/2 bg-[#660b0b] transition-all md:top-5" style="width: {{ $stepAktif > 1 ? '33.33%' : '0%' }}"></div>

                @foreach($reservationSteps as $index => $step)
                    @php
                        $stepNumber = $index + 1;
                        $sudahAktif = $stepNumber <= $stepAktif;
                        $stepSekarang = $stepNumber === $stepAktif;
                    @endphp
                    <div class="z-10 flex flex-1 flex-col items-center text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-stone-900 text-lg font-black md:h-12 md:w-12 {{ $sudahAktif ? 'bg-[#660b0b] text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)]' : 'bg-stone-200 text-stone-400' }} transition-all">
                            <span aria-hidden="true">{{ $step['icon'] }}</span>
                        </div>
                        <span class="mt-3 max-w-[100px] text-[11px] leading-tight md:max-w-none md:text-xs {{ $stepSekarang ? 'font-black text-stone-900' : 'font-bold text-stone-400' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-col items-center justify-center gap-2 text-center sm:flex-row">
        <p class="text-xs font-medium text-stone-500">Status reservasi tidak diperbarui otomatis. Muat ulang untuk melihat perubahan terbaru.</p>
        <button type="button" onclick="window.location.reload()" class="shrink-0 rounded-lg border border-stone-400 bg-white px-3 py-1.5 text-xs font-bold text-stone-700 transition hover:bg-stone-100">
            Muat ulang status
        </button>
    </div>

    {{-- =========================================================================
         KOTAK UTAMA DUAL-PANEL (MURNI UNTUK DATA, BEBAS DARI TOMBOL STATUS)
         ========================================================================= --}}
    <div class="bg-white border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,1)] rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12">
        
        {{-- PANEL KIRI (Data Customer & Sisa Pelunasan) --}}
        <div class="md:col-span-5 bg-[#4A2311] text-white p-5 md:p-7 flex flex-col justify-between space-y-6">
            <div>
                <div class="pb-3 border-b border-amber-900/60 mb-5 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-amber-300 font-bold">Kode Reservasi</span>
                        <h2 class="min-w-0 break-all text-xl md:text-2xl font-black text-white mt-1">
                            #{{ $reservasi->pesanan->kode_pesanan }}
                        </h2>
                    </div>
                </div>

                <div class="space-y-4 text-sm md:text-base">
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Tamu</span>
                        <span class="font-bold text-white text-right">{{ $reservasi->customer->nama }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">WhatsApp</span>
                        <span class="font-bold text-white text-right">{{ $reservasi->customer->nomor_telp }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Jadwal</span>
                        <span class="font-bold text-amber-200 text-right">
                            {{ \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('d M Y') }}
                            ({{ \Carbon\Carbon::parse($reservasi->jam_mulai)->format('H.i') }} - {{ \Carbon\Carbon::parse($reservasi->jam_selesai)->format('H.i') }} WIB)
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-amber-900/30 pb-2.5">
                        <span class="shrink-0 text-stone-300 font-medium">Catatan Khusus</span>
                        <div class="max-h-24 min-w-0 max-w-[65%] overflow-y-auto whitespace-pre-wrap break-words pr-1 text-right text-stone-200 italic">
                            {{ $reservasi->keterangan ?: '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-amber-900/60">
                <div class="bg-amber-500/10 p-4 rounded-xl border-2 border-amber-500/30 flex justify-between items-center shadow-[inset_0_0_10px_rgba(0,0,0,0.2)]">
                    <span class="text-sm text-amber-200 font-bold">Sisa Pelunasan (Di Resto)</span>
                    <span class="text-xl md:text-2xl font-black text-amber-300">
                        Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- PANEL KANAN (Rincian Menu Pre-Order) --}}
        <div class="md:col-span-7 p-5 md:p-7 flex flex-col justify-between bg-white space-y-6">
            <div>
                <div class="flex justify-between items-center pb-4 border-b-2 border-stone-200 mb-5">
                    <h3 class="font-black text-base md:text-lg text-stone-900">🍳 Menu Pre-Order</h3>
                    @if($bisaUbahMenu)
                        <button type="button" onclick="toggleModal('editMenuModal', true)" class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-stone-800 font-black text-xs border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] transition-all active:translate-y-[1px] active:shadow-[1px_1px_0_0_rgba(0,0,0,1)] rounded-lg">
                            ✏️ Ubah Menu
                        </button>
                    @endif
                </div>

                @if($pesanan && $pesanan->detailPesanan->count() > 0)
                    <div class="max-h-[240px] md:max-h-[300px] overflow-y-auto pr-1 space-y-3 scrollbar-thin scrollbar-thumb-stone-400">
                        @foreach($pesanan->detailPesanan as $detail)
                            <div class="flex items-center justify-between bg-stone-50 border-2 border-stone-900 p-3 rounded-xl shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <img src="{{ $detail->menu->gambar ? asset('storage/'.$detail->menu->gambar) : 'https://via.placeholder.com/100' }}" alt="{{ $detail->menu->nama }}" class="w-10 h-10 md:w-12 md:h-12 rounded-full border-2 border-stone-900 object-cover flex-shrink-0">
                                    <div class="min-w-0">
                                        <p class="font-black text-xs md:text-sm text-stone-900 truncate">{{ $detail->menu->nama }} <span class="text-stone-500 ml-1">({{ $detail->jumlah }}x)</span></p>
                                        @if($detail->catatan)
                                            <p class="text-[10px] md:text-xs text-amber-800 font-medium italic truncate mt-0.5">💬 {{ $detail->catatan }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="font-black text-sm md:text-base text-stone-900 flex-shrink-0">
                                    Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-stone-400 italic py-10 text-center font-medium">Belum ada menu makanan yang dipilih.</p>
                @endif
            </div>

            <div>
                <div class="bg-stone-50 border-2 border-stone-900 p-5 rounded-xl flex justify-between items-center shadow-[3px_3px_0_0_rgba(0,0,0,1)]">
                    <span class="font-black text-stone-900 text-base md:text-lg">Total Menu Pesanan</span>
                    <span class="text-[#8B5A2B] font-black text-lg md:text-xl">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                </div>

                @if(!$bisaUbahMenu)
                    <p class="text-xs text-red-600 font-bold italic text-center mt-4">
                        ⚠️ Pemilihan menu telah dikunci karena DP sudah dibayar/kedaluwarsa.
                    </p>
                @endif
            </div>
        </div>
    </div>

    @if($reservationFinished)
        <div class="flex flex-col gap-3 rounded-xl border-2 border-amber-500 bg-amber-50 text-amber-950 p-4 sm:flex-row sm:items-center sm:justify-between shadow-[3px_3px_0_0_rgba(245,158,11,0.2)]">
            <p class="text-sm font-bold leading-relaxed">
                Simpan atau <em>bookmark</em> tautan halaman ini sebagai bukti reservasi Anda.
                Tautan juga telah dikirimkan ke nomor WhatsApp Anda (jika terdaftar).
            </p>
            <button type="button" id="copy-reservation-link" data-url="{{ $reservationUrl }}" class="shrink-0 rounded-lg border-2 border-stone-900 bg-white px-5 py-2.5 text-sm font-black shadow-[2px_2px_0_0_rgba(0,0,0,1)] transition-all hover:bg-amber-100 active:translate-y-1 active:shadow-none">
                🔗 Salin Tautan
            </button>
        </div>
    @endif

    {{-- KOMPONEN POPUP MENU EDIT --}}
    @if($bisaUbahMenu)
        <x-menu-popup 
            :menus="$menus" 
            :is-edit="true" 
            :pesanan-terkait="$pesanan"
            :action-url="route('customer.reservasi.update_menu', $reservasi->id)"
            :minimum-total="$nominalDp"
        />
    @endif
</div>

@include('partials.floating_cs_wa')
<x-alert type="confirm-action" />

{{-- SCRIPT JAVASCRIPT UNTUK MIDTRANS, COUNTDOWN, DAN SALIN TAUTAN --}}
<script>
(() => {
    // 1. Logika Salin Tautan
    document.getElementById('copy-reservation-link')?.addEventListener('click', async function () {
        const button = this;
        const url = button.dataset.url;
        try {
            await navigator.clipboard.writeText(url);
            const originalText = button.innerHTML;
            button.textContent = '✅ Tautan Tersalin!';
            window.setTimeout(() => { button.innerHTML = originalText; }, 2000);
        } catch (error) { window.prompt('Salin tautan reservasi:', url); }
    });

    // 2. Logika Countdown Timer
    const timer = document.getElementById('reservation-countdown');
    if (timer) {
        const expiresAt = Number(timer.dataset.expiresAt);
        function updateCountdown() {
            const remaining = expiresAt - Date.now();
            if (remaining <= 0) {
                timer.textContent = '00:00:00';
                timer.classList.add('border-red-600', 'bg-red-100', 'text-red-800');
                document.getElementById('reservation-expired-message')?.classList.remove('hidden');
                document.querySelector('[onclick*="editMenuModal"]')?.setAttribute('disabled', 'disabled');
                document.getElementById('editMenuModal')?.classList.add('hidden');
                return;
            }
            const totalSeconds = Math.floor(remaining / 1000);
            timer.textContent = [
                Math.floor(totalSeconds / 3600),
                Math.floor((totalSeconds % 3600) / 60),
                totalSeconds % 60
            ].map(value => String(value).padStart(2, '0')).join(':');
        }

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    }
})();
</script>

{{-- SCRIPT MIDTRANS SNAP (Hanya dirender jika ada Token) --}}
@if($statusPesanan === 'pending' && !empty($snapToken))
    @php
        $midtransProduction = filter_var(config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)), FILTER_VALIDATE_BOOLEAN);
    @endphp
    <script src="{{ $midtransProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key', env('MIDTRANS_CLIENT_KEY')) }}"></script>
    <script>
        window.reservationSnapOpen = false;
        function openReservationSnap() {
            if (typeof window.snap === 'undefined') {
                window.alert('Midtrans belum siap. Muat ulang halaman lalu coba kembali.');
                return;
            }
            window.reservationSnapOpen = true;
            window.snap.pay(@json($snapToken), {
                onSuccess: function () { window.reservationSnapOpen = false; window.location.reload(); },
                onPending: function () { window.reservationSnapOpen = false; window.location.reload(); },
                onError: function () { window.reservationSnapOpen = false; window.location.reload(); },
                onClose: function () { window.reservationSnapOpen = false; }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            if (@json((bool) session('open_midtrans', false))) {
                window.setTimeout(openReservationSnap, 500);
            }
        });
    </script>
@endif
@endsection