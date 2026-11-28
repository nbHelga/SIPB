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
    @if($statusPesanan === 'waiting')
        <div class="w-full rounded-2xl border-4 border-amber-500 bg-amber-50 p-6 md:p-8 md:px-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] text-center" role="status">
            <h2 class="text-xl font-black text-amber-900 md:text-2xl">Reservasi Menunggu Persetujuan</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm font-bold leading-relaxed text-amber-950">Reservasi skala besar sedang ditinjau oleh tim kami untuk penyesuaian meja. Kami akan memberi tahu Anda setelah disetujui melalui WhatsApp. Pembayaran DP belum dimulai.</p>
        </div>
    @elseif($statusPesanan === 'pending')
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
                    @if($statusPesanan === 'pending' && $withinPaymentWindow && $paymentStatus !== 'success')
                        @if(!empty($hasSelectedPaymentMethod))
                            <form id="form-change-payment-reservation" action="{{ route('customer.payment.change-method', $pesanan->kode_pesanan) }}" method="POST" class="hidden">
                                @csrf
                                <input type="hidden" name="metode_pembayaran" value="Midtrans">
                            </form>
                            <button
                                type="button"
                                onclick="window.dispatchEvent(new CustomEvent('component-confirm', {
                                    detail: {
                                        title: 'Ubah Metode Pembayaran',
                                        message: 'Pilihan metode pembayaran Anda sebelumnya akan dibatalkan, dan Anda dapat memilih kembali metode pembayaran lain di Midtrans (QRIS, GoPay, ShopeePay, atau BCA VA). Lanjutkan?',
                                        formId: 'form-change-payment-reservation',
                                        confirmText: 'Ya, Ubah Metode',
                                        confirmColor: 'bg-amber-600 border-amber-900'
                                    }
                                }))"
                                class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-amber-400 px-6 py-3.5 font-black text-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-amber-500 active:translate-y-1 active:shadow-none"
                            >
                                🔄 Ubah Metode Pembayaran
                            </button>
                        @else
                            <button
                                type="button"
                                disabled
                                title="Pilih metode pembayaran terlebih dahulu melalui tombol Lanjutkan Pembayaran"
                                class="w-full sm:w-auto rounded-xl border-2 border-stone-300 bg-stone-200 px-6 py-3.5 font-bold text-stone-400 cursor-not-allowed shadow-none"
                            >
                                🔄 Ubah Metode Pembayaran
                            </button>
                        @endif
                    @endif
                @elseif($canStartPayment)
                    <form action="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('customer.reservasi.retry_payment', now()->addHours(24), ['id' => $reservasi->id]) }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto rounded-xl border-2 border-stone-900 bg-emerald-600 px-8 py-3.5 font-black text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all hover:bg-emerald-700 active:translate-y-1 active:shadow-none">Mulai Pembayaran DP</button>
                    </form>
                @elseif($canRetryPayment)
                    <form action="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('customer.reservasi.retry_payment', now()->addHours(24), ['id' => $reservasi->id]) }}" method="POST" class="w-full sm:w-auto">
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
                @if($reservasi->rejection_reason)
                    <span class="mt-2 block">Alasan penolakan: {{ $reservasi->rejection_reason }}</span>
                @endif
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
                <div class="absolute left-[16.66%] right-[16.66%] top-4 z-0 h-1 -translate-y-1/2 bg-stone-200 md:top-5"></div>
                <div class="absolute left-[16.66%] top-4 z-[1] h-1 -translate-y-1/2 bg-[#660b0b] transition-all md:top-5" style="width: {{ $stepAktif > 1 ? (($stepAktif - 1) * 33.33) . '%' : '0%' }}"></div>

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

    {{-- =========================================================================
         KARTU TIKET (hanya muncul saat status confirmed)
         ========================================================================= --}}
    @if($statusPesanan === 'confirmed')
        <div class="w-full rounded-2xl border-4 border-emerald-600 bg-emerald-50 p-6 md:p-8 shadow-[8px_8px_0_0_rgba(0,0,0,1)] text-center" role="region" aria-label="Tiket Reservasi">
            <p class="text-xs font-black uppercase tracking-widest text-emerald-700 mb-2">🎟️ Tiket Reservasi</p>
            <p class="text-stone-500 text-sm font-bold mb-4">Tunjukkan tiket ini kepada staf saat Anda tiba di restoran</p>

            <p class="font-black text-3xl md:text-4xl text-emerald-900 tracking-wider mb-1">
                {{ $reservasi->pesanan->kode_pesanan }}
            </p>
            <p class="text-sm font-bold text-stone-700 mt-4">
                {{ $reservasi->customer->nama }}
                &nbsp;•&nbsp;
                {{ $reservasi->jml_org }} Tamu
            </p>
            <p class="text-sm font-bold text-emerald-800 mt-1">
                {{ \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('d M Y') }}
                &nbsp;|&nbsp;
                {{ \Carbon\Carbon::parse($reservasi->jam_mulai)->format('H.i') }} – {{ \Carbon\Carbon::parse($reservasi->jam_selesai)->format('H.i') }} WIB
            </p>
            @if($reservasi->detailReservasi->isNotEmpty())
                <p class="text-xs text-stone-500 mt-2">
                    Meja:
                    {{ $reservasi->detailReservasi->map(fn($d) => $d->meja?->nomor_meja ?? '-')->implode(', ') }}
                </p>
            @endif
        </div>
    @endif

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
                        <span class="text-stone-300 font-medium">Nama</span>
                        <span class="font-bold text-white text-right">{{ $reservasi->customer->nama }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Nomor Telp.</span>
                        <span class="font-bold text-white text-right">{{ $reservasi->customer->nomor_telp }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Jumlah Orang</span>
                        <span class="font-bold text-white text-right">{{ $reservasi->jml_org }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Jadwal</span>
                        <span class="font-bold text-amber-200 text-right">
                            {{ \Carbon\Carbon::parse($reservasi->tanggal)->translatedFormat('d M Y') }}
                            ({{ \Carbon\Carbon::parse($reservasi->jam_mulai)->format('H.i') }} - {{ \Carbon\Carbon::parse($reservasi->jam_selesai)->format('H.i') }} WIB)
                        </span>
                    </div>
                    <div class="space-y-1.5 border-b border-amber-900/30 pb-2.5">
                        <span class="block text-stone-300 font-medium">Catatan Khusus</span>
                        <div class="max-h-24 min-w-0 w-full overflow-y-auto whitespace-pre-line break-words text-left text-stone-200 italic text-xs md:text-sm bg-amber-950/40 p-2.5 rounded-lg border border-amber-900/50">
                            {{ trim($reservasi->keterangan ?? '') ?: '-' }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between pb-2.5 border-b border-amber-900/30">
                        <span class="text-stone-300 font-medium">Uang Muka (DP)</span>
                        <div class="text-right">
                            <span class="font-bold text-white">Rp {{ number_format($nominalDp, 0, ',', '.') }}</span>
                            @if($statusPembayaranDp === 'success')
                                <span class="ml-1.5 inline-block px-2 py-0.5 text-[10px] font-black bg-emerald-500/30 text-emerald-200 rounded border border-emerald-400/40">✓ Lunas ({{ match(strtolower($paymentDp?->metode ?? '')) { 'qris', 'other_qris' => 'QRIS', 'bca_va' => 'BCA VA', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', default => ucwords(str_replace('_', ' ', $paymentDp?->metode ?? 'Midtrans')) } }})</span>
                            @elseif($statusPembayaranDp === 'failed')
                                <span class="ml-1.5 inline-block px-2 py-0.5 text-[10px] font-black bg-red-500/30 text-red-200 rounded border border-red-400/40">Gagal</span>
                            @else
                                <span class="ml-1.5 inline-block px-2 py-0.5 text-[10px] font-black bg-amber-500/30 text-amber-200 rounded border border-amber-400/40">Pending</span>
                            @endif
                        </div>
                    </div>
                    @if($pelunasanPayment)
                        <div class="flex items-center justify-between pb-2.5 border-b border-amber-900/30">
                            <span class="text-stone-300 font-medium">Pelunasan (Di Resto)</span>
                            <div class="text-right">
                                <span class="font-bold text-white">Rp {{ number_format($pelunasanPayment->nominal, 0, ',', '.') }}</span>
                                <span class="ml-1.5 inline-block px-2 py-0.5 text-[10px] font-black bg-emerald-500/30 text-emerald-200 rounded border border-emerald-400/40">✓ Lunas ({{ match(strtolower($pelunasanPayment->metode ?? '')) { 'qris', 'other_qris' => 'QRIS', 'bca_va' => 'BCA VA', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'transfer_bank' => 'Transfer Bank', default => ucwords(str_replace('_', ' ', $pelunasanPayment->metode ?? '')) } }})</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-4 border-t border-amber-900/60">
                @if($pelunasanPayment)
                    <div class="bg-emerald-500/10 p-4 rounded-xl border-2 border-emerald-500/30 flex justify-between items-center shadow-[inset_0_0_10px_rgba(0,0,0,0.2)]">
                        <div>
                            <span class="text-xs md:text-sm text-emerald-200 font-bold block">Sisa Pelunasan (Di Resto)</span>
                            <span class="text-[10px] md:text-xs text-emerald-300/70">Lunas via {{ match(strtolower($pelunasanPayment->metode ?? '')) { 'qris', 'other_qris' => 'QRIS', 'bca_va' => 'BCA VA', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'transfer_bank' => 'Transfer Bank', default => ucwords(str_replace('_', ' ', $pelunasanPayment->metode ?? '')) } }}</span>
                        </div>
                        <span class="text-xl md:text-2xl font-black text-emerald-300">
                            Rp 0
                        </span>
                    </div>
                @elseif($statusPembayaranDp === 'success' && $sisaTagihan == 0)
                    <div class="bg-emerald-500/10 p-4 rounded-xl border-2 border-emerald-500/30 flex justify-between items-center shadow-[inset_0_0_10px_rgba(0,0,0,0.2)]">
                        <div>
                            <span class="text-xs md:text-sm text-emerald-200 font-bold block">Sisa Pelunasan (Di Resto)</span>
                            <span class="text-[10px] md:text-xs text-emerald-300/70">Lunas penuh saat pembayaran DP</span>
                        </div>
                        <span class="text-xl md:text-2xl font-black text-emerald-300">
                            Rp 0
                        </span>
                    </div>
                @else
                    <div class="bg-amber-500/10 p-4 rounded-xl border-2 border-amber-500/30 flex justify-between items-center shadow-[inset_0_0_10px_rgba(0,0,0,0.2)]">
                        <div>
                            <span class="text-xs md:text-sm text-amber-200 font-bold block">Sisa Pelunasan (Di Resto)</span>
                            <span class="text-[10px] md:text-xs text-amber-300/70">Total Menu dikurangi DP</span>
                        </div>
                        <span class="text-xl md:text-2xl font-black text-amber-300">
                            Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                        </span>
                    </div>
                @endif
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
                                            <div class="mt-0.5 max-h-14 overflow-y-auto overflow-x-hidden break-words whitespace-pre-line pr-1 text-[10px] md:text-xs text-amber-800 font-medium italic scrollbar-thin scrollbar-thumb-stone-300">💬 {{ trim($detail->catatan) }}</div>
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

    {{-- KOMPONEN POPUP MENU EDIT --}}
    @if($bisaUbahMenu)
        <x-menu-popup 
            :menus="$menus" 
            :is-edit="true" 
            :pesanan-terkait="$pesanan"
            :action-url="\Illuminate\Support\Facades\URL::temporarySignedRoute('customer.reservasi.update_menu', now()->addHours(24), ['id' => $reservasi->id])"
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
        let expiryHandled = false;
        const reloadOnceAtExpiry = () => {
            if (expiryHandled) return;
            expiryHandled = true;
            document.querySelectorAll('button, form, a').forEach((element) => {
                if (element.tagName === 'A') element.setAttribute('aria-disabled', 'true');
                else element.disabled = true;
            });
            document.getElementById('reservation-expired-message')?.classList.remove('hidden');
            window.location.reload();
        };
        function updateCountdown() {
            const remaining = expiresAt - Date.now();
            if (remaining <= 0) {
                timer.textContent = '00:00:00';
                timer.classList.add('border-red-600', 'bg-red-100', 'text-red-800');
                document.getElementById('reservation-expired-message')?.classList.remove('hidden');
                document.querySelector('[onclick*="editMenuModal"]')?.setAttribute('disabled', 'disabled');
                document.getElementById('editMenuModal')?.classList.add('hidden');
                reloadOnceAtExpiry();
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
    <script src="{{ $midtransProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
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
                onClose: function () { window.reservationSnapOpen = false; window.location.reload(); }
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
