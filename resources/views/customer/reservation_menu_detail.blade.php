@extends('layouts.landing')

@section('title', $menu->nama . ' - BigBang Eatery')

@section('header_right')
    <a href="{{ route('customer.reservation.menu') }}" class="text-2xl font-bold hover:text-orange-400 transition">✕</a>
@endsection

@section('content')
<div class="pt-24 pb-10 max-w-4xl mx-auto px-4 py-8">
    <div class="bg-[#F4EAD4] rounded-xl border-4 border-[#4A2311] shadow-[8px_8px_0_0_rgba(74,35,17,0.3)] overflow-hidden flex flex-col md:flex-row">
        
        {{-- Sisi Gambar --}}
        <div class="md:w-1/2 bg-stone-100 flex items-center justify-center p-4">
            @if($menu->gambar)
                <img src="{{ asset('storage/' . $menu->gambar) }}" alt="{{ $menu->nama }}" class="w-full h-auto object-cover rounded-lg border-2 border-[#4A2311]">
            @else
                <div class="text-8xl py-12">🍽️</div>
            @endif
        </div>

        {{-- Sisi Detail Informasi --}}
        <div class="md:w-1/2 p-6 flex flex-col justify-between bg-[#E6D5BC] border-t-4 md:border-t-0 md:border-l-4 border-[#4A2311]">
            <div>
                <span class="px-3 py-1 bg-[#4A2311] text-white text-xs font-bold rounded-full uppercase tracking-wider">
                    {{ $menu->kategori }}
                </span>
                <h1 class="text-3xl font-black text-[#4A2311] mt-3 mb-1">{{ $menu->nama }}</h1>
                <p class="text-2xl font-black text-orange-600 mb-4">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                
                <div class="border-t-2 border-[#4A2311] opacity-20 my-3"></div>
                
                <p class="text-stone-700 font-medium leading-relaxed mb-4">
                    {{ $menu->deskripsi ?? 'Tidak ada deskripsi untuk menu ini.' }}
                </p>
            </div>

            <div class="space-y-2">
                <div class="text-sm font-bold text-stone-600">
                    Status Menu: <span class="text-[#4A2311] font-black">Tersedia</span>
                </div>
                <a href="{{ route('customer.reservation.menu') }}" 
                   class="block text-center w-full py-3 bg-[#4A2311] text-white font-black rounded-lg border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,0.25)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[4px] active:translate-y-[4px] transition-all">
                    ⬅️ Kembali ke Daftar Menu
                </a>
            </div>
        </div>

    </div>
</div>
@endsection