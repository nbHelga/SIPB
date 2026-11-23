@extends('layouts.landing')

@section('title', 'Pembayaran DP Reservasi - BigBang Eatery')

@section('content')
{{-- Gunakan min-h-screen dan biarkan overflow alami agar saat inspect/layar pendek bisa di-scroll --}}
<div class="pt-24 pb-12 min-h-screen max-w-6xl mx-auto px-4 flex flex-col justify-between box-border">
    <x-alert type="confirm-action" />
    @php
        $paymentErrorMessages = collect(session('menu_change_messages', []))
            ->merge($errors->all())->filter()->unique()->values();
    @endphp
    @if(session('error') || $paymentErrorMessages->isNotEmpty() || $hasMenuChanges)
        <div role="alert" class="my-3 rounded-xl border-2 border-red-500 bg-red-50 p-4 text-red-900">
            <p class="font-black">{{ session('error', 'Ada data reservasi yang perlu diperiksa sebelum melanjutkan.') }}</p>
            @if($paymentErrorMessages->isNotEmpty() || $hasMenuChanges)
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm font-semibold">
                    @foreach($paymentErrorMessages->merge($menuChangeMessages ?? [])->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
            @if($hasMenuChanges)
                <a href="{{ route('customer.reservasi.form') }}" class="mt-3 inline-flex rounded-lg bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800">Periksa kembali data dan menu</a>
            @endif
        </div>
    @endif

    @if(session('success'))
        <div role="status" class="my-3 rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3 font-bold text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    @if($pendingReservasi)
        <div class="mb-6 rounded-xl border-2 border-amber-500 bg-amber-50 p-4 md:p-5">
            <h3 class="font-black text-amber-900">Masih ada reservasi aktif untuk nomor ini</h3>
            <p class="mt-2 text-sm text-stone-700">
                Reservasi #{{ $pendingReservasi->pesanan?->kode_pesanan }}
                masih menunggu {{ strtolower((string) $pendingReservasi->pesanan?->status) === 'waiting' ? 'persetujuan admin' : 'pembayaran DP' }}. Lanjutkan reservasi tersebut atau batalkan dahulu jika ingin membuat reservasi baru.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <a href="{{ route('customer.reservasi.detail', ['kode_pesanan' => $pendingReservasi->pesanan?->kode_pesanan]) }}"
                    class="rounded-lg border-2 border-stone-900 bg-white px-4 py-3 text-center font-black hover:bg-stone-100">
                    Lanjutkan reservasi lama
                </a>

                <form id="cancel-pending-reservation-form" method="POST"
                    action="{{ route('customer.reservasi.cancel_pending', ['id' => $pendingReservasi->id]) }}">
                    @csrf
                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('component-confirm', { detail: { message: 'Reservasi lama dan tagihan pending-nya akan dibatalkan. Lanjutkan membuat reservasi baru?', formId: 'cancel-pending-reservation-form' } }))"
                        class="w-full rounded-lg border-2 border-stone-900 bg-red-600 px-4 py-3 font-black text-white hover:bg-red-700">
                        Batalkan & lanjutkan reservasi baru
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- GRID CONTAINER -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch flex-1">
        
        <!-- KOLOM KIRI: RINGKASAN RESERVASI & PEMBAYARAN (7 Kolom) -->
        <div class="lg:col-span-7 lg:min-h-[720px] bg-white rounded-2xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)] p-5 md:p-6 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="border-b-2 border-stone-200 pb-2">
                    <h2 class="text-lg font-black text-stone-900 flex items-center gap-2">
                        📋 Ringkasan Reservasi
                    </h2>
                    <p class="text-xs text-stone-500">Pastikan data jadwal dan pesanan Anda sudah benar.</p>
                </div>

                <!-- Detail Reservasi (Ringkas) -->
                <div class="space-y-2 text-xs lg:text-sm">
                    <div class="flex justify-between items-center py-1 border-b border-stone-100">
                        <span class="text-stone-500 font-medium">Nama Pemesan</span>
                        <span class="font-black text-stone-900">{{ $draftObj->nama ?? 'Guest' }}</span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-stone-100">
                        <span class="text-stone-500 font-medium">Nomor Telepon</span>
                        <span class="font-black text-stone-900">{{ $draftObj->nomor_telp ?? '-' }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-1 border-b border-stone-100">
                        <span class="text-stone-500 font-medium">Tanggal Kedatangan</span>
                        <span class="font-bold text-stone-900">
                            {{ \Carbon\Carbon::parse($draftObj->tanggal)->translatedFormat('d F Y') }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center py-1 border-b border-stone-100">
                        <span class="text-stone-500 font-medium">Waktu / Sesi</span>
                        <div class="text-right">
                            <span class="font-bold text-stone-900">
                                {{ \Carbon\Carbon::parse($draftObj->jam_mulai)->format('H.i') }} - {{ \Carbon\Carbon::parse($draftObj->jam_selesai)->format('H.i') }} WIB
                            </span>
                            <span class="text-[11px] text-stone-400 block">
                                ({{ \Carbon\Carbon::parse($draftObj->tanggal)->translatedFormat('l') }})
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center py-1 border-b border-stone-100">
                        <span class="text-stone-500 font-medium">Jumlah Tamu</span>
                        <span class="font-bold text-stone-900 bg-stone-100 px-2.5 py-0.5 rounded-full border border-stone-300 text-xs">
                            👥 {{ $draftObj->jml_org }} Orang
                        </span>
                    </div>
                    
                    <div class="flex items-start justify-between py-1.5 border-b border-stone-100">
                        <span class="text-stone-500 font-medium shrink-0">Keterangan Reservasi</span>
                        <div class="max-h-20 min-w-0 max-w-[65%] overflow-y-auto whitespace-pre-line break-words text-left font-medium text-stone-800 text-xs lg:text-sm italic">
                            {{ trim($draftObj->keterangan ?? '') ?: '-' }}
                        </div>
                    </div>
                </div>

                @if((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'))
                    <div class="rounded-lg border-2 border-amber-500 bg-amber-50 p-3 text-sm font-bold text-amber-950">
                        Reservasi lebih dari 10 orang perlu persetujuan admin. Masukkan nominal DP di bawah; pembayaran baru dimulai setelah reservasi disetujui.
                    </div>
                @endif

                <!-- Card DP Info (Kompak) -->
                <div class="bg-amber-50 border-2 border-stone-900 rounded-xl p-3.5 text-center shadow-[3px_3px_0_0_rgba(0,0,0,1)]">
                    <p class="text-[11px] font-black text-stone-600 uppercase tracking-wider mb-0.5">
                        @if((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'))
                            Uang Muka Setelah Persetujuan Admin
                        @else
                            Uang Muka (DP) Wajib Minimal
                        @endif
                    </p>
                    <p class="text-2xl lg:text-3xl font-black text-[#4A2311]">
                        Rp {{ number_format(app(\App\Services\SystemSettings::class)->get('reservation_minimum_dp'), 0, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-stone-500 mt-1">
                        @if((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'))
                            *Pembayaran DP dimulai setelah reservasi disetujui.
                        @else
                            *Sisa tagihan menu dibayarkan langsung di kasir restoran.
                        @endif
                    </p>
                </div>
                
            </div>

            <!-- Form Tombol Bayar -->
            @if(!$pendingReservasi)
                <form action="{{ route('customer.reservasi.create_transaction') }}" method="POST" class="space-y-4 mt-6">
                    @csrf
                    
                    {{-- Input Nominal Fleksibel --}}
                    <div class="bg-stone-50 p-4 rounded-xl border-2 border-stone-200">
                        <label for="nominal_dp" class="block text-xs font-bold text-stone-700 mb-2">
                            Nominal DP (minimal Rp 50.000)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-stone-600">Rp</span>
                            <input
                                id="nominal_dp"
                                type="text"
                                inputmode="numeric"
                                data-thousands
                                name="nominal_dp"
                                min="{{ app(\App\Services\SystemSettings::class)->get('reservation_minimum_dp') }}"
                                max="{{ (int) ($totalHargaMenu ?? 0) }}"
                                value="{{ old('nominal_dp', app(\App\Services\SystemSettings::class)->get('reservation_minimum_dp')) }}"
                                class="w-full pl-10 pr-4 py-3 border-2 border-stone-300 rounded-lg text-sm font-black text-[#4A2311] focus:border-[#4A2311] focus:outline-none"
                                required
                            >
                        </div>
                        <p class="text-[10px] text-stone-500 mt-1.5">
                            DP minimal Rp50.000. Anda dapat membayar lebih hingga sebesar total menu.
                        </p>
                    </div>

                    {{-- Checklist Catatan Sebelum Melanjutkan --}}
                    <div class="rounded-xl border border-amber-400 bg-amber-50 p-3 text-xs text-amber-950">
                        <span class="font-bold block mb-1">📌 Sebelum melanjutkan, pastikan:</span>
                        <ul class="list-disc space-y-0.5 pl-4 text-stone-700">
                            <li>Menu pre-order dan jumlahnya sudah benar.</li>
                            @if((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'))
                                <li>Nominal DP dicatat sekarang, pembayaran dimulai setelah disetujui admin.</li>
                            @else
                                <li>DP minimal Rp 50.000 dan tidak melebihi total pesanan.</li>
                            @endif
                            <li>Sisa pelunasan dibayar langsung di kasir restoran saat tiba.</li>
                        </ul>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 bg-[#4A2311] text-white font-black rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:bg-[#8B5A2B] transition-all disabled:cursor-not-allowed disabled:bg-stone-500 disabled:opacity-50"
                        @disabled($hasMenuChanges)
                    >
                            @if((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'))
                            Kirim permintaan reservasi
                        @else
                            💳 Bayar Sekarang via Midtrans
                        @endif
                    </button>
                </form>
            @endif
        </div>

        <!-- KOLOM KANAN: DETAIL PESANAN MENU (5 Kolom) -->
        <div class="lg:col-span-5 lg:min-h-[720px] bg-white rounded-2xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)] p-5 md:p-6 flex min-h-0 flex-col">
            
            <div class="flex min-h-0 flex-1 flex-col">
                <div class="border-b-2 border-stone-200 pb-2 mb-3">
                    <h3 class="text-base font-black text-stone-900 flex items-center gap-2">
                        Rincian Menu Yang Dipesan
                    </h3>
                </div>

                <!-- LIST ITEM MENU DENGAN SCROLLBAR INTERNAL JIKA TERLALU PANJANG -->
                <div class="min-h-0 flex-1 overflow-y-auto pr-1.5 space-y-2.5 my-3 scrollbar-thin scrollbar-thumb-stone-400 scrollbar-track-stone-100">
                    @forelse($menuItems ?? [] as $item)
                        @php $menu = $item->menu; @endphp
                        <div class="flex items-center gap-2.5 p-2 bg-stone-50 border-2 border-stone-900 rounded-xl shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
                            <div class="w-10 h-10 bg-stone-200 rounded-lg flex items-center justify-center flex-shrink-0 border border-stone-300 overflow-hidden">
                                @if($menu->gambar)
                                    <img src="{{ asset('storage/' . $menu->gambar) }}" alt="{{ $menu->nama }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-stone-400 text-xs">🍽️</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-stone-900 text-xs truncate">{{ $menu->nama ?? 'Menu' }}</h4>
                                <p class="text-[11px] text-stone-500">
                                    {{ $item->jumlah }}x @ Rp {{ number_format($item->menu->harga, 0, ',', '.') }}
                                </p>
                                @if($item->catatan)
                                    <p class="text-[10px] text-stone-400 italic truncate">"{{ $item->catatan }}"</p>
                                @endif
                            </div>
                            <div class="shrink-0 font-black text-stone-900 text-xs">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-stone-400 italic text-center py-6">Tidak ada pesanan menu tambahan.</p>
                    @endforelse
                </div>
            </div>

            <!-- RINGKASAN SUB TOTAL -->
            <div class="pt-3 border-t-2 border-dashed border-stone-300 space-y-1 text-xs mt-4">
                <div class="flex justify-between text-stone-900 font-black text-sm pt-1.5 border-t border-stone-200">
                    <span>Total Estimasi</span>
                    <span class="text-[#4A2311]">Rp {{ number_format($totalHargaMenu ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="text-right text-xs text-stone-400 italic">
                    <em>Harga sudah termasuk pajak</em>
                </div>
            </div>
        </div>

    </div>
</div>

@include('partials.floating_cs_wa')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('customer.reservasi.create_transaction') }}"]');
    const btnSubmit = form ? form.querySelector('button[type="submit"]') : null;
    const requiresApproval = @json((int) ($draftObj->jml_org ?? 0) > app(\App\Services\SystemSettings::class)->get('reservation_regular_max_guests'));
    
    if(form && btnSubmit) {
        form.addEventListener('submit', function() {
            // Langsung matikan tombol saat diklik pertama kali
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = requiresApproval ? 'Mengirim permintaan...' : '⏳ Menyiapkan Pembayaran...';
        });
    }
});
</script>
@endsection
