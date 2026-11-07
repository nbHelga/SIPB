@php
    $nomorMeja = session('nomor_meja');
    // $lantai = session('lantai');
    $menuCollection = collect($menus);

    $menuSections = collect();

    $bestsellers = $menuCollection
        ->filter(fn ($menu) => (bool) $menu->is_bestseller)
        ->sortBy(fn ($menu) => mb_strtolower($menu->nama ?? ''), SORT_NATURAL)
        ->values();

    if ($bestsellers->isNotEmpty()) {
        $menuSections->push([
            'key' => '__bestseller__',
            'title' => '⭐ Bestseller',
            'menus' => $bestsellers,
        ]);
    }

    $categoryGroups = $menuCollection
        ->groupBy(fn ($menu) => mb_strtolower(trim($menu->kategori ?: 'Lainnya')))
        ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($categoryGroups as $categoryKey => $categoryMenus) {
        $menuSections->push([
            'key' => $categoryKey,
            'title' => $categoryMenus->first()->kategori ?: 'Lainnya',
            'menus' => $categoryMenus->sortBy(
                fn ($menu) => mb_strtolower($menu->nama ?? ''),
                SORT_NATURAL
            )->values(),
        ]);
    }
@endphp

@extends('layouts.customer')

@section('title', 'Menu - BigBang Eatery')

@section('header_right')
    @php $cartCount = count(Session::get('cart', [])); @endphp
    <span class="text-sm font-bold bg-[#4A2311] px-3 py-2 ">Nomor Meja : {{ $nomorMeja ?? '-' }} (Lantai {{ $tables->lantai ?? '-'}})</span>
    {{-- <h3 class="text-sm font-semibold ">{{ $tables->lantai ?? '-'}}</h3> --}}
    @if($isRestaurantOpen)
    <a href="{{ route('customer.cart', ['kode' => session('selforder_code'),'tableNumber' => $tables->nomor_meja,]) }}" class="relative p-2 hover:bg-stone-800 rounded-lg transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        @if($cartCount > 0)
            <span class="absolute -top-1 -right-1 bg-[#4A2311] text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $cartCount }}</span>
        @endif
    </a>
    @else
        <span class="relative cursor-not-allowed p-2 text-stone-500" aria-label="Keranjang tidak tersedia saat restoran tutup" aria-disabled="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            @if($cartCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-stone-500 text-xs font-bold text-white">{{ $cartCount }}</span>
            @endif
        </span>
    @endif
@endsection

@section('content')
    <div x-data="{ showModal: false, showImageZoom: false, showPendingOrders: false, selectedMenu: {}, qty: 1 }" class="max-w-7xl mx-auto px-4 py-6 {{ $pendingOrders->isNotEmpty() ? 'pb-28' : '' }}">
        {{-- Jika ada success --}}
        @if(session('success'))
            <x-alert
                type="success-toast"
                :message="session('success')"
                :duration="3500"
            />
        @endif

        <div class="mb-5 rounded-xl border-2 px-4 py-3 text-center font-black {{ $isRestaurantOpen ? 'border-emerald-700 bg-emerald-50 text-emerald-900' : 'border-stone-400 bg-stone-200 text-stone-700' }}" role="status">
            @if($isRestaurantOpen)
                Buka <span class="mx-1 opacity-50">•</span> Tutup pukul 00.00 {{ $businessTimezoneLabel }}
            @else
                Tutup <span class="mx-1 opacity-50">•</span> Buka kembali pukul 15.00 {{ $businessTimezoneLabel }}
            @endif
            </div>

        <div @if(!$isRestaurantOpen) inert aria-disabled="true" @endif class="{{ $isRestaurantOpen ? '' : 'grayscale opacity-60' }}">
        {{-- Category Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-thin" id="categoryTabs">
            <button class="category-tab active px-4 py-2 bg-[#4A2311] text-white font-bold rounded-full border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.3)] text-sm whitespace-nowrap" data-category="all">Semua</button>
            @foreach($categories as $cat)
                <button class="category-tab px-4 py-2 bg-white text-stone-700 font-bold rounded-full border-2 border-stone-300 shadow-[2px_2px_0_0_rgba(0,0,0,0.1)] text-sm whitespace-nowrap hover:border-[#8B5A2B] transition" data-category="{{ $cat }}">
                    {{ $cat }}
                </button>
            @endforeach
        </div>

        {{-- Menu Grid --}}
        <div id="menuGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($menus as $menu)
                <div class="menu-item bg-[#F4EAD4] rounded-lg border-2 border-[#4A2311] shadow-[4px_4px_0_0_rgba(74,35,17,0.25)] overflow-hidden hover:shadow-[6px_6px_0_0_rgba(74,35,17,0.4)] hover:-translate-y-0.5 transition-all"
                    data-category="{{ $menu->kategori }}">
                    
                    {{-- Bagian Gambar --}}
                    <div class="relative block aspect-[4/3] bg-stone-200 overflow-hidden">
                        @if($menu->is_bestseller)
                            <div class="absolute top-2 left-2 z-10 flex items-center gap-1 bg-amber-400 text-[#4A2311] text-xs font-black px-2.5 py-1 rounded-full border-2 border-[#4A2311] shadow-[2px_2px_0_0_rgba(74,35,17,1)]">
                                ⭐
                            </div>
                        @endif
                        @if(!empty($menu['gambar']))
                            <img src="{{ asset('storage/' . $menu['gambar']) }}" 
                                alt="{{ $menu['nama'] }}" 
                                class="w-full h-full object-cover hover:scale-105 transition-transform"
                                onclick="openImageZoom('{{ asset('storage/' . $menu['gambar']) }}', '{{ addslashes($menu['nama']) }}')">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-stone-400 text-4xl">🍽️</div>
                        @endif
                    </div>

                    {{-- Bagian Detail & Tombol Beli --}}
                    <div class="p-3 bg-[#E6D5BC]">
                        <h3 class="font-bold text-sm truncate text-[#4A2311]">{{ $menu->nama }}</h3>
                        <p class="text-orange font-black mt-1">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                        
                        {{-- INI TOMBOL BELI YANG BARU --}}
                        <button type="button" 
                                @click="
                                    selectedMenu = {
                                        id: '{{ $menu->id }}',
                                        nama: '{{ addslashes($menu->nama) }}',
                                        harga: {{ $menu->harga }},
                                        {{-- stok: {{ $menu->stok }}, --}}
                                        gambar: '{{ !empty($menu->gambar) ? asset('storage/' . $menu->gambar) : '' }}',
                                        deskripsi: '{{ addslashes($menu->deskripsi ?? '') }}'
                                    };
                                    qty = 1;
                                    showModal = true;
                                "
                                class="mt-2 w-full py-2 bg-[#4A2311] text-white font-bold text-sm rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.25)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all">
                            🛒 Beli
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Container Modal --}}
        <x-modal-add-cart :nomorMeja="$nomorMeja" />
        </div>

        @if($pendingOrders->count() === 1)
            @php($pendingOrder = $pendingOrders->first())
            <a href="{{ route('customer.order.detail', $pendingOrder->kode_pesanan) }}"
                class="fixed inset-x-0 bottom-[56px] z-40 mx-auto flex w-full max-w-none items-center justify-between gap-3 border-2 border-stone-900 bg-amber-300 px-4 py-3 font-bold text-stone-900 shadow-[0_-4px_16px_rgba(0,0,0,0.18)] transition hover:bg-amber-200">
                <span class="min-w-0 truncate">Pesanan sebelumnya · {{ $pendingOrder->kode_pesanan }}</span>
                <span class="shrink-0">Rp {{ number_format($pendingOrder->total_harga, 0, ',', '.') }}</span>
            </a>
        @elseif($pendingOrders->count() > 1)
            <button type="button" @click="showPendingOrders = true"
                class="fixed inset-x-0 bottom-[56px] z-40 mx-auto flex w-full max-w-none items-center gap-3 border-2 border-stone-900 bg-amber-300 px-4 py-3 text-left font-bold text-stone-900 shadow-[0_-4px_16px_rgba(0,0,0,0.18)] transition hover:bg-amber-200">
                <span class="min-w-0 flex-1 truncate">Pesanan sebelumnya · {{ $pendingOrders->first()->kode_pesanan }}</span>
                <span class="shrink-0">Rp {{ number_format($pendingOrders->first()->total_harga, 0, ',', '.') }}</span>
                <span class="shrink-0 rounded-full bg-stone-900 px-2.5 py-1 text-xs font-black text-white">+{{ $pendingOrders->count() - 1 }}</span>
            </button>

            <div x-show="showPendingOrders" x-transition.opacity @click.self="showPendingOrders = false"
                @keydown.escape.window="showPendingOrders = false" style="display: none;"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="pending-orders-title">
                <div class="w-full max-w-lg overflow-hidden rounded-xl border-2 border-stone-900 bg-white shadow-[6px_6px_0_0_rgba(0,0,0,0.35)]">
                    <div class="flex items-center justify-between border-b-2 border-stone-200 px-4 py-3">
                        <h2 id="pending-orders-title" class="font-black text-stone-900">Pesanan yang Menunggu Pembayaran</h2>
                        <button type="button" @click="showPendingOrders = false" class="flex h-9 w-9 items-center justify-center rounded-full text-xl font-bold text-stone-600 transition hover:bg-stone-100" aria-label="Tutup daftar">&times;</button>
                    </div>
                    <div class="max-h-[65vh] divide-y divide-stone-200 overflow-y-auto">
                        @foreach($pendingOrders as $pendingOrder)
                            <a href="{{ route('customer.order.detail', $pendingOrder->kode_pesanan) }}" class="flex items-center justify-between gap-4 px-4 py-4 transition hover:bg-amber-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-stone-900">{{ $pendingOrder->kode_pesanan }}</span>
                                    <span class="text-xs text-stone-500">{{ $pendingOrder->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                                </span>
                                <span class="shrink-0 font-black text-[#4A2311]">Rp {{ number_format($pendingOrder->total_harga, 0, ',', '.') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
<script>
(() => {
    const tabs = document.querySelectorAll('#categoryTabs .category-tab');
    const sections = document.querySelectorAll('#menuSections .menu-category-section');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const selected = (tab.dataset.category || 'all').trim().toLowerCase();

            tabs.forEach(button => {
                button.classList.toggle('active', button === tab);
                button.classList.toggle('bg-[#4A2311]', button === tab);
                button.classList.toggle('text-white', button === tab);
            });

            sections.forEach(section => {
                const category = (section.dataset.category || '').trim().toLowerCase();
                section.hidden = selected !== 'all' && category !== selected;
            });
        });
    });
})();
</script>
<script>
(() => {
    const scrollKey = 'selforder:scroll-before-add';
    const hasSuccess = @json(session()->has('success'));

    if (hasSuccess) {
        const savedY = sessionStorage.getItem(scrollKey);
        sessionStorage.removeItem(scrollKey);

        if (savedY !== null) {
            requestAnimationFrame(() => window.scrollTo(0, Number(savedY)));
        }
    } else {
        sessionStorage.removeItem(scrollKey);
    }

    // Simpan posisi saat form modal add-cart melakukan submit dan berpindah halaman.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const action = form.getAttribute('action') || '';
        if (action.toLowerCase().includes('cart')) {
            sessionStorage.setItem(scrollKey, String(window.scrollY));
        }
    }, true);
})();
</script>
@endsection
