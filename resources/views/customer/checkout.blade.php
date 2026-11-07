@php
    $nomorMeja = session('nomor_meja');
@endphp
@extends('layouts.customer')

@section('title', 'Checkout - BigBang Eatery')

@section('header_right')
    <span class="text-sm font-bold bg-[#4A2311] px-3 py-2 text-white rounded-lg">Nomor Meja : {{ $nomorMeja ?? '-' }} (Lantai {{ $tables->lantai ?? '-'}})</span>
    <a href="{{ route('customer.cart',['kode' => session('selforder_code'),'tableNumber' => $tables->nomor_meja]) }}" class="text-2xl font-bold hover:text-[#8B5A2B] transition">✕</a>
@endsection

@section('content')
    {{-- Container Utama: Gunakan min-h-screen agar fleksibel saat disorot/inspek --}}
    <div class="max-w-7xl mx-auto px-4 py-6 min-h-[calc(100vh-128px)] flex flex-col lg:flex-row gap-6 box-border">
        
        {{-- Content A: Daftar Menu --}}
        <div class="flex-1 flex flex-col bg-white rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,0.3)] p-4 lg:p-6">
            <h1 class="text-xl lg:text-2xl font-black border-b-2 border-stone-200 pb-3">
                📋 Konfirmasi Pesanan
            </h1>

            {{-- ALERT PERUBAHAN MENU --}}
            @if(isset($hasMenuChanges) && $hasMenuChanges)
                <div class="mb-4 bg-red-100 border-2 border-red-500 text-red-900 p-4 rounded-xl font-bold text-sm shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                    <p class="mb-2">⚠️ PERHATIAN: Terdapat perubahan pada menu Anda.</p>
                    <ul class="list-disc ml-5 mb-3 font-medium">
                        @foreach($menuChangeMessages as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                    {{-- <a href="{{ route('customer.cart') }}" class="inline-block px-4 py-2 bg-red-600 text-white rounded-lg shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:bg-red-700 transition">
                        🔄 Kembali ke Keranjang
                    </a> --}}
                </div>
            @endif

            {{-- Inner List Container --}}
            <div class="my-3 space-y-3 max-h-[450px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-stone-400 scrollbar-track-stone-100">
                @foreach($cartItems as $key => $item)
                    @php $menu = $item['menu']; @endphp
                    <div class="bg-stone-50 rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] p-3 flex items-center gap-3">
                        <div class="w-16 h-16 bg-stone-200 rounded-lg border-2 border-stone-900 overflow-hidden flex-shrink-0">
                            @if($menu['gambar'])
                                <img src="{{ asset('storage/' . $menu['gambar']) }}" alt="{{ $menu['nama'] }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-stone-400 text-2xl">🍽️</div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm lg:text-base text-stone-900 truncate">{{ $menu['nama'] }}</h3>
                            <p class="text-xs text-stone-500">{{ $item['jumlah'] }}x Rp {{ number_format($menu['harga'], 0, ',', '.') }}</p>
                            @if($item['catatan'] ?? null)
                                <p class="text-xs text-[#4A2311] italic font-medium">Catatan: {{ $item['catatan'] }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-black text-sm lg:text-base text-[#4A2311]">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Content B: Detail Pembayaran --}}
        <div class="lg:w-96 xl:w-[420px] flex-shrink-0">
            <div class="bg-white rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,0.3)] p-6 space-y-5 flex flex-col justify-between">
                <div class="space-y-4">
                    {{-- <h2 class="font-black text-lg lg:text-xl border-b-2 border-[#4A2311] pb-2 text-stone-900">
                        🧾 Detail Pesanan
                    </h2> --}}

                    <div class="space-y-2.5 text-sm">
                        {{-- <div class="flex justify-between text-stone-600">
                            <span>Total harga produk</span>
                            <span class="font-bold text-stone-900">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                        </div> --}}
                        {{-- <div class="flex justify-between text-stone-600">
                            <span>PB (10%)</span>
                            <span class="font-bold text-stone-900">Rp {{ number_format($pb, 0, ',', '.') }}</span>
                        </div> --}}
                        <div class="flex justify-between font-black text-lg border-t-2 border-stone-900 pt-3">
                            <span>Total Harga</span>
                            <span class="text-[#4A2311]">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-stone-500 italic leading-relaxed">
                        Dengan membayar, Anda menyetujui kebijakan dan ketentuan yang berlaku di BigBang Eatery.
                    </p>
                </div>

                {{-- <form method="POST" action="{{ route('customer.checkout.create', request()->query()) }}">
                    @csrf
                    <button type="submit" name="metode_pembayaran" value="Tunai"
                            class="rounded-lg border-2 border-stone-900 bg-amber-300 px-5 py-3 font-bold">
                        Tunai
                    </button>
                    <button type="submit" name="metode_pembayaran" value="Midtrans"
                            class="rounded-lg border-2 border-stone-900 bg-blue-500 px-5 py-3 font-bold text-white">
                        QRIS
                    </button>
                </form> --}}

                <form id="checkout-payment-form" method="POST" action="{{ route('customer.checkout.create', request()->query()) }}" class="flex flex-col gap-3">
                    @csrf
                    <button type="submit" name="metode_pembayaran" value="Tunai"
                            class="rounded-lg border-2 border-stone-900 bg-amber-300 px-5 py-3 font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(isset($hasMenuChanges) && $hasMenuChanges) disabled @endif>
                        Tunai
                    </button>
                    <button type="submit" name="metode_pembayaran" value="Midtrans"
                            class="rounded-lg border-2 border-stone-900 bg-blue-500 px-5 py-3 font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-stone-400"
                            @if(isset($hasMenuChanges) && $hasMenuChanges) disabled @endif>
                        QRIS
                    </button>
                </form>

                <div class="mb-6 rounded-xl border-2 border-stone-900 bg-white p-5">
                    <h2 class="mb-3 text-lg font-black">Tata Cara Pembayaran</h2>

                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="metode_pilihan" value="qris" checked
                            onchange="setInstruksiPembayaran('qris')">
                        <span class="font-bold">QRIS</span>
                    </label>

                    <div id="instruksiQris" class="mt-2 rounded-lg bg-blue-50 p-3 text-sm">
                        Setelah memilih QRIS, lanjutkan pembayaran pada halaman QRIS yang ditampilkan.
                    </div>

                    <label class="mt-4 flex cursor-pointer items-center gap-2">
                        <input type="radio" name="metode_pilihan" value="tunai"
                            onchange="setInstruksiPembayaran('tunai')">
                        <span class="font-bold">Tunai</span>
                    </label>
                    <div id="instruksiTunai" class="mt-2 hidden rounded-lg bg-amber-50 p-3 text-sm">
                        Pesanan dibuat terlebih dahulu. Silakan melanjutkan pembbayaran langsung ke kasir.
                    </div>
                </div>
            </div>
        </div>

    </div>
<script>
function setInstruksiPembayaran(metode) {
    document.getElementById('instruksiQris')
        ?.classList.toggle('hidden', metode !== 'qris');

    document.getElementById('instruksiTunai')
        ?.classList.toggle('hidden', metode !== 'tunai');
}

document.addEventListener('DOMContentLoaded', () => {
    const selected = document.querySelector('input[name="metode_pilihan"]:checked');
    if (selected) setInstruksiPembayaran(selected.value);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkout-payment-form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        const submitter = event.submitter;

        if (submitter && submitter.name) {
            const proxy = document.createElement('input');
            proxy.type = 'hidden';
            proxy.name = submitter.name;
            proxy.value = submitter.value;
            form.appendChild(proxy);

            // Hindari mengirim nilai tombol dua kali.
            submitter.removeAttribute('name');
        }

        form.dataset.submitting = 'true';

        form.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
        });

        if (submitter) {
            submitter.textContent = '⏳ Memproses pembayaran...';
        }
    });
});
</script>
@endsection