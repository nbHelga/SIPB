@extends('layouts.customer')

@section('title', 'Status Pesanan - Big Bang Eatery')

@section('content')
<div class="max-w-[95%] md:max-w-7xl mx-auto px-4 py-3 md:py-6 md:pb-24 pb-16 mt-1 space-y-5 md:space-y-8 min-h-[calc(100vh-140px)] flex flex-col justify-start">
    <x-alert type="confirm-action" />

    @if($st === 'pending' && !empty($snapToken) && !$isCashPayment)
        <script src="{{ $midtransProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ config('midtrans.client_key', env('MIDTRANS_CLIENT_KEY')) }}"></script>

        <script>
            function openOrderSnap() {
                if (typeof window.snap === 'undefined') {
                    alert('Midtrans belum siap. Muat ulang halaman lalu coba kembali.');
                    return;
                }

                window.snap.pay(@json($snapToken), {
                    onSuccess: () => window.location.reload(),
                    onPending: () => window.location.reload(),
                    onError: () => window.location.reload()
                });
            }

            @if(session('open_midtrans'))
                document.addEventListener('DOMContentLoaded', openOrderSnap);
            @endif
        </script>
    @endif

    {{-- =========================================================================
         UNIFIED ACTION BANNER (CARD FULL WIDTH SEJAJAR DENGAN KONTEN BAWAH)
         ========================================================================= --}}
    @if($st === 'pending')
        <div class="w-full rounded-2xl border-4 {{ $statusPembayaran === 'failed' ? 'border-red-600 bg-red-50' : 'border-amber-500 bg-amber-50' }} p-6 md:p-8 md:px-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col items-center text-center transition-all">
            @if($orderExpiresAt)
                <div id="order-countdown" data-expires-at="{{ $orderExpiresAt }}" class="mb-4 rounded-lg border-2 border-amber-600 bg-amber-200 px-4 py-2 text-base font-black tracking-wider text-amber-900" aria-label="Sisa waktu pembayaran">00:00:00</div>
                <p id="order-expired-message" class="hidden text-sm font-bold text-red-700" role="status">Waktu pembayaran habis. Halaman sedang diperbarui.</p>
            @endif
            
            <div class="flex items-center justify-center gap-3 font-black">
                @if($statusPembayaran === 'failed')
                    <span class="text-3xl" aria-hidden="true">⚠️</span>
                    <h2 class="text-xl md:text-2xl text-red-700">Pembayaran Gagal</h2>
                @else
                    <svg class="h-8 w-8 animate-spin text-amber-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <h2 class="text-xl md:text-2xl text-amber-800">Menunggu Pembayaran</h2>
                @endif
            </div>

            <p class="mt-4 text-sm md:text-base font-bold {{ $statusPembayaran === 'failed' ? 'text-red-900' : 'text-amber-950' }} max-w-xl leading-relaxed">
                @if($metodePembayaran === 'tunai')
                    Selesaikan pembayaran pesanan Anda secara langsung di Kasir agar pesanan dapat segera diproses ke dapur. Jika Anda berubah pikiran, silakan batalkan pesanan.
                @elseif($statusPembayaran === 'failed')
                    Waktu pembayaran QRIS telah kedaluwarsa atau transaksi gagal diproses. Silakan ulangi pembayaran untuk melanjutkan pesanan ini.
                @else
                    Segera selesaikan pembayaran QRIS Anda. Setelah sukses, pesanan akan otomatis diteruskan ke dapur. Silahkan muat ulang halaman jika perlu.
                @endif
            </p>

            @if(!($statusPembayaran === 'pending' && !empty($snapToken)) && !$canRetryPayment)
                <p class="mt-4 text-sm font-bold text-amber-900">
                    Status pembayaran sedang diperbarui. Muat ulang halaman beberapa saat lagi.
                </p>
            @endif

            <div class="mt-7 flex w-full flex-col items-center justify-center gap-3 sm:flex-row">
                @if($statusPembayaran === 'pending' && !empty($snapToken))
                    <button type="button" onclick="openOrderSnap()"
                        class="w-full sm:w-auto whitespace-nowrap rounded-xl border-2 border-stone-900 bg-[#4A2311] px-8 py-3.5 font-black text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-[#8B5A2B] active:translate-y-1 active:shadow-none">
                        💳 Lanjutkan Pembayaran
                    </button>
                @elseif($canRetryPayment)
                    <form action="{{ route('customer.payment.ulangi', $pesanan->kode_pesanan) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full whitespace-nowrap rounded-xl border-2 border-stone-900 bg-emerald-500 px-8 py-3.5 font-black text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-emerald-600 active:translate-y-1 active:shadow-none">
                            🔄 Ulangi Pembayaran
                        </button>
                    </form>
                @endif

                @if($statusPembayaran !== 'success' && $orderExpiresAt && now()->timestamp * 1000 < $orderExpiresAt)
                    <form action="{{ route('customer.payment.change-method', $pesanan->kode_pesanan) }}" method="POST" class="flex flex-wrap items-center justify-center gap-2">
                        @csrf
                        <label for="order-payment-method" class="sr-only">Metode pembayaran baru</label>
                        <select id="order-payment-method" name="metode_pembayaran" class="rounded-lg border-2 border-stone-400 bg-white px-3 py-2 font-bold">
                            <option value="{{ $isCashPayment ? 'Midtrans' : 'Tunai' }}">{{ $isCashPayment ? 'Midtrans / QRIS' : 'Tunai' }}</option>
                        </select>
                        <button class="rounded-lg border-2 border-stone-900 bg-white px-4 py-2 font-bold text-stone-900">Ubah Metode Pembayaran</button>
                    </form>
                @endif

                <form id="cancel-order-form" action="{{ route('customer.payment.batal', $pesanan->kode_pesanan) }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('component-confirm', { detail: { formId: 'cancel-order-form', message: 'Yakin ingin membatalkan pesanan ini?' }}))" class="w-full sm:w-auto whitespace-nowrap rounded-xl border-2 border-red-600 bg-white px-8 py-3.5 font-black text-red-600 shadow-[3px_3px_0_0_rgba(220,38,38,0.2)] transition-all hover:bg-red-50 active:translate-y-1 active:shadow-none">
                        ❌ Batalkan Pesanan
                    </button>
                </form>
            </div>
        </div>

    {{-- UNIFIED BANNER CANCELLED --}}
    @elseif($st === 'cancelled')
        <div class="w-full rounded-2xl border-4 border-stone-400 bg-stone-100 p-6 md:p-8 md:px-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col items-center text-center">
            <div class="flex items-center justify-center gap-3 font-black">
                <span class="text-3xl" aria-hidden="true">❌</span>
                <h2 class="text-xl md:text-2xl text-stone-700">Pesanan Dibatalkan</h2>
            </div>
            <p class="mt-3 text-sm md:text-base font-bold text-stone-600 max-w-xl">
                Pesanan ini telah dibatalkan secara manual atau batas waktu pembayarannya telah habis.
            </p>
            <div class="mt-7 flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('customer.selforder', ['tableNumber' => $pesanan->meja->nomor_meja ?? '']) }}" class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-white px-8 py-3.5 font-black text-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-stone-50 active:translate-y-1 active:shadow-none">
                    🛒 Buat Pesanan Baru
                </a>
            </div>
        </div>

    {{-- STEPPER UNTUK PESANAN BERJALAN (PAID, PROCESSING, COMPLETED) --}}
    @else
        <div class="p-2 md:p-4">
            <div class="relative mx-auto flex w-full max-w-2xl items-start">
                <div class="absolute left-[16.66%] right-[16.66%] top-4 h-1 -translate-y-1/2 {{ in_array($st, ['processing', 'completed'], true) ? 'bg-[#660b0b]' : 'bg-stone-200' }} md:top-5"></div>
                <div class="absolute left-1/2 right-[16.66%] top-4 h-1 -translate-y-1/2 {{ $st === 'completed' ? 'bg-[#660b0b]' : 'bg-stone-200' }} md:top-5"></div>

                @php
                    $orderSteps = [
                        ['label' => 'Pembayaran Berhasil', 'icon' => '💳', 'active' => in_array($st, ['paid', 'processing', 'completed'], true)],
                        ['label' => 'Pesanan Diproses', 'icon' => '🍳', 'active' => in_array($st, ['processing', 'completed'], true)],
                        ['label' => 'Pesanan Selesai', 'icon' => '✓', 'active' => $st === 'completed'],
                    ];
                    $activeStep = match ($st) { 'paid' => 1, 'processing' => 2, 'completed' => 3, default => 0 };
                @endphp

                @foreach($orderSteps as $index => $step)
                    @php
                        $stepNumber = $index + 1;
                        $isCurrentStep = $stepNumber === $activeStep;
                    @endphp
                    <div class="z-10 flex flex-1 flex-col items-center text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-stone-900 text-lg font-black md:h-12 md:w-12 {{ $step['active'] ? 'bg-[#660b0b] text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)]' : 'bg-stone-200 text-stone-400' }} transition-all">
                            <span aria-hidden="true">{{ $step['icon'] }}</span>
                        </div>
                        <span class="mt-3 max-w-[95px] text-[11px] leading-tight md:max-w-none md:text-xs {{ $isCurrentStep ? 'font-black text-stone-900' : 'font-bold text-stone-400' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-col items-center justify-center gap-2 text-center sm:flex-row">
        <span class="text-xs text-stone-500">Status pesanan diperiksa otomatis. Anda juga dapat memuat ulang secara manual.</span>
        <button type="button" onclick="window.location.reload()" class="shrink-0 rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs font-bold text-stone-700 transition hover:bg-stone-100">
            Refresh status
        </button>
    </div>

    {{-- MAIN CONTENT (HANYA FOKUS PADA RINCIAN DAN TOTAL BIAYA) --}}
    <div class="rounded-2xl border-4 border-stone-900 bg-white p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] md:p-6 md:shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
        <div class="mb-3 flex items-center justify-between gap-3 border-b-2 border-stone-900 pb-3">
            <h2 class="font-black text-base text-stone-900 md:text-xl">Rincian Menu</h2>
            <div class="flex shrink-0 items-center gap-2">
                <span class="text-xs font-bold text-stone-500">Meja {{ $pesanan->meja->nomor_meja ?? '-' }}</span>
                <span class="rounded-lg border-2 border-stone-900 bg-amber-100 px-2 py-0.5 text-xs font-black text-stone-900">{{ $pesanan->kode_pesanan }}</span>
            </div>
        </div>

        <div class="max-h-[480px] space-y-2.5 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-stone-400">
            @foreach($pesanan->detailPesanan as $detail)
                <div class="flex items-center justify-between rounded-xl border-2 border-stone-900 bg-stone-50 p-2.5 shadow-[2px_2px_0_0_rgba(0,0,0,1)] md:p-3">
                    <div class="flex min-w-0 items-center gap-2.5 pr-2 md:gap-3">
                        <img src="{{ $detail->menu->gambar ? asset('storage/'.$detail->menu->gambar) : 'https://via.placeholder.com/100' }}" alt="{{ $detail->menu->nama ?? 'Menu' }}" class="h-10 w-10 shrink-0 rounded-full border-2 border-stone-900 object-cover md:h-12 md:w-12">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-black text-stone-900 md:text-sm">{{ $detail->menu->nama ?? 'Menu Terhapus' }}</p>
                            <p class="text-[10px] font-bold text-stone-500 md:text-xs">{{ $detail->jumlah }}x @ Rp {{ number_format($detail->subtotal / max(1, $detail->jumlah), 0, ',', '.') }}</p>
                            @if($detail->catatan)
                                <p class="truncate text-[10px] font-medium italic text-amber-800">💬 {{ $detail->catatan }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-black text-stone-900 md:text-sm">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t-2 border-dashed border-stone-300 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center justify-between sm:justify-start gap-4 text-sm font-bold">
                <span class="text-stone-500">Metode Pembayaran</span>
                <span class="rounded-md border border-stone-300 bg-stone-100 px-2.5 py-1 text-xs uppercase tracking-wider text-stone-800">
                    {{ str_replace('_', ' ', $metodePembayaran) }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-stone-200 pt-3 font-black sm:border-0 sm:pt-0">
                <span class="text-stone-900 text-sm sm:text-base">Total Bayar</span>
                <span class="text-xl sm:text-2xl text-[#8B5A2B]">Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>

        @if(in_array($st, ['paid', 'processing', 'completed']))
            <div class="mt-4 border-t-2 border-dashed border-stone-300 pt-4">
                <a href="{{ route('customer.selforder', ['tableNumber' => $pesanan->meja->nomor_meja ?? '']) }}" class="block w-full rounded-xl border-2 border-stone-900 bg-[#4A2311] py-3 text-center text-sm font-black text-white shadow-[4px_4px_0_0_rgba(0,0,0,1)] transition-all hover:bg-[#8B5A2B] active:translate-x-1 active:translate-y-1 active:shadow-none">
                    🍽️ Pesan Menu Tambahan
                </a>
            </div>
        @endif
    </div>
    
</div>

@if($st === 'pending')
    <script>
        (() => {
            const initialStatus = @json($st);
            const statusUrl = @json(route('customer.order.status', $pesanan->kode_pesanan));
            const countdown = document.getElementById('order-countdown');
            let expiryHandled = false;

            const expireAndReload = async () => {
                if (expiryHandled) return;
                expiryHandled = true;
                document.querySelectorAll('button, form, a').forEach((element) => {
                    if (element.closest('#order-countdown')) return;
                    if (element.tagName === 'A') element.setAttribute('aria-disabled', 'true');
                    else element.disabled = true;
                });
                document.getElementById('order-expired-message')?.classList.remove('hidden');
                try {
                    await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                } catch (_) {}
                window.location.reload();
            };

            if (countdown) {
                const expiresAt = Number(countdown.dataset.expiresAt);
                const updateCountdown = () => {
                    const remaining = expiresAt - Date.now();
                    if (remaining <= 0) {
                        countdown.textContent = '00:00:00';
                        countdown.classList.add('border-red-600', 'bg-red-100', 'text-red-800');
                        expireAndReload();
                        return;
                    }
                    const totalSeconds = Math.floor(remaining / 1000);
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;
                    countdown.textContent = [hours, minutes, seconds]
                        .map(value => String(value).padStart(2, '0'))
                        .join(':');
                };
                updateCountdown();
                window.setInterval(updateCountdown, 1000);
            }

            window.setInterval(async () => {
                if (expiryHandled) return;
                try {
                    const response = await fetch(statusUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });
                    if (!response.ok) return;

                    const data = await response.json();
                    if (data.status !== initialStatus) {
                        expiryHandled = true;
                        window.location.reload();
                    }
                } catch (_) {
                    // Keep polling; a temporary network error should not stop status updates.
                }
            }, {{ app(\App\Services\SystemSettings::class)->get('payment_status_poll_seconds') * 1000 }});
        })();
    </script>
@endif
@endsection
