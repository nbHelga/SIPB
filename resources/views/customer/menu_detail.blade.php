@php
    $nomorMeja = session('nomor_meja');
@endphp
@extends('layouts.customer')

@section('title', $menu->nama . ' - BigBang Eatery')

@section('header_right')
    <span class="text-sm font-bold bg-[#4A2311] px-3 py-2 ">Nomor Meja : {{ $nomorMeja ?? '-' }} (Lantai {{ $tables->lantai }})</span>
    <a href="{{ route('customer.cart') }}" class="relative p-2 hover:bg-stone-800 rounded-lg transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        @php $cartCount = count(Session::get('cart', [])); @endphp
        @if($cartCount > 0)
            <span class="absolute -top-1 -right-1 bg-[#4A2311] text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $cartCount }}</span>
        @endif
    </a>
    <a href="{{ route('customer.selforder', ['nomor_meja' => $nomorMeja]) }}" class="text-2xl font-bold hover:text-[#8B5A2B] transition">✕</a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="bg-white rounded-xl border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,0.3)] overflow-hidden">
        <div class="flex flex-col md:flex-row">
            {{-- Gambar besar di kiri --}}
            <div class="md:w-1/2 bg-white aspect-square">
                @if($menu->gambar)
                    <img src="{{ asset('storage/' . $menu->gambar) }}" alt="{{ $menu->nama }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-6xl text-stone-400">🍽️</div>
                @endif
            </div>

            {{-- Detail di kanan --}}
            <div class="md:w-1/2 p-6 md:p-8 flex flex-col justify-between">
                <div>
                    <h1 class="text-3xl font-black mb-2">{{ $menu->nama }}</h1>
                    <p class="text-[#8B5A2B] text-3xl font-black mb-4">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>

                    @if($menu->deskripsi)
                        <p class="text-stone-600 mb-4 leading-relaxed">{{ $menu->deskripsi }}</p>
                    @endif

                    <div class="space-y-1 text-sm text-stone-500 mb-6">
                        <p>Kategori: <span class="font-bold text-stone-700">{{ $menu->kategori }}</span></p>
                        {{-- <p>Stok: <span class="font-bold text-stone-700">{{ $menu->stok }}</span></p> --}}
                    </div>
                </div>

                {{-- Tombol Add to Cart --}}
                <div x-data="{ jumlah: 1 }">
                    <form action="{{ route('customer.cart.add') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id_menu" value="{{ $menu->id }}">
                        <input type="hidden" name="redirect" value="{{ route('customer.menu.detail', $menu->id) . '?nomor_meja=' . $nomorMeja }}">

                        {{-- Qty controls --}}
                        <div class="flex items-center gap-3 mb-4">
                            <span class="font-bold text-sm">Jumlah:</span>
                            <button type="button" @click="jumlah = Math.max(1, jumlah - 1)"
                                    class="w-10 h-10 bg-stone-200 rounded-lg border-2 border-stone-900 font-bold text-xl hover:bg-stone-300">−</button>
                            <input type="number" name="jumlah" x-model="jumlah" min="1" max="100"
                                   class="w-16 text-center border-2 border-stone-300 rounded-lg py-2 font-bold text-lg">
                            <button type="button" @click="jumlah = Math.min(100, jumlah + 1)"
                                    class="w-10 h-10 bg-stone-200 rounded-lg border-2 border-stone-900 font-bold text-xl hover:bg-stone-300">+</button>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-bold text-stone-600 mb-1">Catatan Khusus (Opsional):</label>
                            <textarea name="catatan" placeholder="Contoh: Sangat pedas, pisah kuah..." rows="2" class="w-full border-2 border-stone-300 rounded-lg p-2 text-sm focus:border-[#4A2311] focus:outline-none"></textarea>
                        </div>

                        {{-- Total --}}
                        <p class="text-center font-bold text-lg mb-4">
                            Total: Rp <span x-text="new Intl.NumberFormat('id-ID').format(jumlah * {{ $menu->harga }})"></span>
                        </p>

                        <button type="submit"
                                class="w-full py-4 bg-[#4A2311] text-white font-black text-lg rounded-lg border-2 border-stone-900 shadow-[5px_5px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[5px] active:translate-y-[5px] transition-all">
                            🛒 + Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
