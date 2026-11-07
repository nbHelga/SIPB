@extends('layouts.landing')

@section('title', 'Pembayaran DP Reservasi - BigBang Eatery')

@section('content')
{{-- Gunakan min-h-screen dan biarkan overflow alami agar saat inspect/layar pendek bisa di-scroll --}}
<div class="pt-24 pb-12 min-h-screen max-w-6xl mx-auto px-4 flex flex-col justify-between box-border">
    <x-alert type="confirm-action" />
    {{-- @if(session('menu_change_messages'))
        <ul role="alert" class="mb-4 list-disc rounded-xl border-2 border-red-500 bg-red-100 p-4 pl-9 font-bold text-red-900">
            @foreach(session('menu_change_messages') as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif --}}
    @if(session('error'))
        <div role="alert" class="my-3 rounded-xl border-2 border-red-500 bg-red-100 p-3 font-bold text-red-900">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div role="status" class="my-3 rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3 font-bold text-emerald-900">
            {{ session('success') }}
        </div>
    @endif
    <div class="my-4 rounded-xl border-2 border-amber-500 bg-amber-50 p-4 text-sm text-amber-950">
        <h3 class="font-black">Sebelum melanjutkan, pastikan:</h3>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Menu pre-order dan jumlahnya sudah benar.</li>
            <li>Nominal DP yang dimasukkan sudah sesuai. DP minimal Rp 50.000 dan tidak melebihi total menu.</li>
            <li>Metode pembayaran yang dipilih pada popup Midtrans sudah benar.</li>
        </ul>
    </div>
    <!-- GRID CONTAINER -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start flex-1">
        
        <!-- KOLOM KIRI: RINGKASAN RESERVASI & PEMBAYARAN (7 Kolom) -->
        <div class="lg:col-span-7 lg:h-[720px] lg:overflow-y-auto bg-white rounded-2xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)] p-5 md:p-6 flex flex-col justify-between">

            @if($hasMenuChanges)
                <div role="alert" class="my-4 rounded-xl border-2 border-red-500 bg-red-100 p-4 font-bold text-red-900">
                    <p class="mb-2">⚠️ Harga atau ketersediaan menu berubah:</p>
                    <ul class="ml-5 list-disc">
                        @foreach($menuChangeMessages as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm font-medium">
                        Periksa harga terbaru. Tombol pembayaran aktif kembali setelah Anda kembali ke form dan mengirim pilihan menu dengan harga terbaru.
                    </p>
                    <a href="{{ route('customer.reservasi.form') }}"
                    class="mt-3 inline-block rounded-lg bg-red-700 px-4 py-2 text-white">
                        Periksa dan pilih menu kembali
                    </a>
                </div>
            @endif

            
            
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
                        {{-- <p class="text-stone-500 font-medium"><strong>Nama:</strong> {{ $draftObj['nama'] }}</p> --}}
                        <span class="font-black text-stone-900">{{ $draftObj->nama ?? 'Guest' }}</span>
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
                                {{ \Carbon\Carbon::parse($draftObj->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($draftObj->jam_selesai)->format('H:i') }} WIB
                            </span>
                            <span class="text-[11px] text-stone-400 block">
                                ({{ \Carbon\Carbon::parse($draftObj->tanggal)->translatedFormat('l') }})
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center py-1">
                        <span class="text-stone-500 font-medium">Jumlah Tamu</span>
                        <span class="font-bold text-stone-900 bg-stone-100 px-2.5 py-0.5 rounded-full border border-stone-300 text-xs">
                            👥 {{ $draftObj->jml_org }} Orang
                        </span>
                    </div>
                </div>

                <!-- Card DP Info (Kompak) -->
                <div class="bg-amber-50 border-2 border-stone-900 rounded-xl p-3.5 text-center shadow-[3px_3px_0_0_rgba(0,0,0,1)]">
                    <p class="text-[11px] font-black text-stone-600 uppercase tracking-wider mb-0.5">
                        Uang Muka (DP) Wajib Minimal
                    </p>
                    <p class="text-2xl lg:text-3xl font-black text-[#4A2311]">
                        Rp {{ number_format(50000, 0, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-stone-500 mt-1">
                        *Sisa tagihan menu dibayarkan langsung di kasir restoran.
                    </p>
                </div>
                
            </div>

            <!-- Form Tombol Bayar -->
            {{-- Ubah menjadi POST karena akan memproses pembuatan token transaksi --}}
            @if($pendingReservasi)
                <div class="mt-6 rounded-xl border-2 border-amber-500 bg-amber-50 p-4">
                    <h3 class="font-black text-amber-900">Masih ada reservasi menunggu pembayaran</h3>
                    <p class="mt-2 text-sm text-stone-700">
                        Reservasi #{{ $pendingReservasi->pesanan?->kode_pesanan }}
                        masih aktif. Batalkan reservasi lama jika ingin membuat reservasi baru,
                        atau buka detailnya untuk melanjutkan pembayaran.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a
                            href="{{ route('customer.reservasi.detail', ['kode_pesanan' => $pendingReservasi->pesanan?->kode_pesanan]) }}"
                            class="rounded-lg border-2 border-stone-900 bg-white px-4 py-3 text-center font-black hover:bg-stone-100"
                        >
                            Lanjutkan reservasi lama
                        </a>

                        <form
                            id="cancel-pending-reservation-form"
                            method="POST"
                            action="{{ route('customer.reservasi.cancel_pending', ['id' => $pendingReservasi->id]) }}"
                        >
                            @csrf
                            <button
                                type="button"
                                onclick="window.dispatchEvent(new CustomEvent('component-confirm', {
                                    detail: {
                                        message: 'Reservasi lama dan tagihan pending-nya akan dibatalkan. Lanjutkan membuat reservasi baru?',
                                        formId: 'cancel-pending-reservation-form'
                                    }
                                }))"
                                class="w-full rounded-lg border-2 border-stone-900 bg-red-600 px-4 py-3 font-black text-white hover:bg-red-700"
                            >
                                Batalkan & lanjutkan reservasi baru
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form action="{{ route('customer.reservasi.create_transaction') }}" method="POST" class="space-y-4 mt-6">
                    @csrf
                    {{-- <input type="hidden" name="id_reservasi" value="{{ $draftObj->id }}"> --}}
                    
                    {{-- Input Nominal Fleksibel --}}
                    <div class="bg-stone-50 p-4 rounded-xl border-2 border-stone-200">
                        {{-- <label class="block text-xs font-bold text-stone-700 mb-2">Nominal Pembayaran (Min. Rp 50.000)</label> --}}
                        <label for="nominal_dp" class="block text-xs font-bold text-stone-700 mb-2">
                            Nominal DP (minimal Rp 50.000)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-stone-600">Rp</span>
                            <input
                                id="nominal_dp"
                                type="number"
                                name="nominal_dp"
                                min="50000"
                                max="{{ (int) ($totalHargaMenu ?? 0) }}"
                                value="{{ old('nominal_dp', 50000) }}"
                                step="1000"
                                class="w-full pl-10 pr-4 py-3 border-2 border-stone-300 rounded-lg text-sm font-black text-[#4A2311] focus:border-[#4A2311] focus:outline-none"
                                required
                            >
                        </div>
                        @error('nominal_dp')
                            <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="text-[10px] text-stone-500 mt-1.5">
                            DP minimal Rp50.000. Anda dapat membayar lebih hingga sebesar total menu.
                        </p>
                    </div>

                    {{-- <button type="submit"
                        class="w-full py-3 bg-[#4A2311] text-white font-black text-sm lg:text-base rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:bg-[#5c2d17] active:shadow-none active:translate-x-0.5 active:translate-y-0.5 transition-all uppercase tracking-wider flex items-center justify-center gap-2">
                        💳 Bayar Sekarang via Midtrans
                    </button> --}}

                    <button
                        type="submit"
                        class="w-full py-3 bg-[#4A2311] text-white font-black rounded-xl disabled:cursor-not-allowed disabled:bg-stone-500 disabled:opacity-50"
                        @disabled($hasMenuChanges)
                    >
                        💳 Bayar Sekarang via Midtrans
                    </button>
                </form>
            @endif
        </div>

        <!-- KOLOM KANAN: DETAIL PESANAN MENU (5 Kolom) -->
        <div class="lg:col-span-5 lg:h-[720px] bg-white rounded-2xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)] p-5 md:p-6 flex min-h-0 flex-col">
            
            <div class="flex min-h-0 flex-1 flex-col">
                <div class="border-b-2 border-stone-200 pb-2 mb-3">
                    <h3 class="text-base font-black text-stone-900 flex items-center gap-2">
                        🍽️ Menu Yang Dipesan
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
                                {{-- <p class="text-[11px] text-stone-500">{{ $item->jumlah }}x @ Rp {{ number_format($menu->harga, 0, ',', '.') }}</p> --}}
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
                {{-- <div class="flex justify-between text-stone-600">
                    <span>Total Harga Menu</span>
                    <span class="font-bold">Rp {{ number_format($totalHargaMenu ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-stone-600">
                    <span>PB1 (10%)</span>
                    <span class="font-bold">Rp {{ number_format($pb ?? 0, 0, ',', '.') }}</span>
                </div> --}}
                <div class="flex justify-between text-stone-900 font-black text-sm pt-1.5 border-t border-stone-200">
                    <span>Total Estimasi</span>
                    <span class="text-[#4A2311]">Rp {{ number_format($totalHargaMenu ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="text-right text-m text-gray-350">
                    <em>Harga sudah termasuk pajak</em>
                </div>
            </div>
        </div>

    </div>
</div>


{{-- <script>
const returnUrl = @json($returnUrl);

snap.pay(@json($snapToken), {
    onSuccess: () => window.location.assign(returnUrl),
    onPending: () => window.location.assign(returnUrl),
    onError: () => window.location.assign(returnUrl),
    onClose: () => window.location.assign(returnUrl),
});
</script> --}}

@include('partials.floating_cs_wa')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('customer.reservasi.create_transaction') }}"]');
    const btnSubmit = form ? form.querySelector('button[type="submit"]') : null;
    
    if(form && btnSubmit) {
        form.addEventListener('submit', function() {
            // Langsung matikan tombol saat diklik pertama kali
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '⏳ Menyiapkan Pembayaran...';
            btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
        });
    }
});
</script>
@endsection