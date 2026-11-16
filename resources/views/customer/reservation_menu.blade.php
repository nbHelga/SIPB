@extends('layouts.landing')

@section('title', 'Katalog Menu Eksklusif - BigBang Eatery')

@section('header_right')
    <span class="text-sm font-black uppercase tracking-widest text-amber-200">📖 Menu Book</span>
@endsection

@section('content')
<style>
    /* 3D Perspective & Flip Animation Setup */
    .book-container {
        perspective: 2000px;
    }
    
    .book-wrapper {
        box-shadow: 0 20px 50px -10px rgba(0,0,0,0.5), 0 0 0 12px #2A1208, 0 0 0 15px #4A2311;
        transition: transform 0.5s ease;
    }

    /* Spine Shadow (Efek Lipatan Buku di Tengah) */
    .book-spine-shadow {
        background: linear-gradient(to right, 
            rgba(0,0,0,0.15) 0%, 
            rgba(0,0,0,0.35) 48%, 
            rgba(0,0,0,0.6) 50%, 
            rgba(0,0,0,0.35) 52%, 
            rgba(0,0,0,0.15) 100%);
    }

    /* Flip Page Layer Animation */
    .page-turn-layer {
        position: absolute;
        top: 0;
        right: 0;
        width: 50%;
        height: 100%;
        transform-origin: left center;
        transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.645, 0.045, 0.355, 1);
        z-index: 30;
        backface-visibility: hidden;
    }

    .page-turn-layer.turning-next {
        transform: rotateY(-180deg);
    }

    .page-turn-layer.turning-prev {
        transform-origin: right center;
        left: 0;
        right: auto;
        transform: rotateY(180deg);
    }

    /* Smooth Hover Zoom & Shadows */
    .menu-card-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .menu-card-item:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 20px -5px rgba(74, 35, 17, 0.3);
    }

    /* Texture Kertas Kuno Warm Parchment */
    .parchment-bg {
        background-color: #FAF4E8;
        background-image: radial-gradient(#E8D5B7 0.75px, transparent 0.75px);
        background-size: 16px 16px;
    }
</style>

<div class="pt-24 pb-16 max-w-7xl mx-auto px-3 sm:px-6">

    {{-- KATEGORI TABS (Bentuk Ribbon / Stamp) --}}
    <div class="flex items-center justify-center gap-2 overflow-x-auto pb-6 scrollbar-none" id="categoryTabs">
        <button class="category-tab active px-5 py-2.5 bg-[#4A2311] text-amber-200 font-black rounded-xl border-2 border-amber-900/40 shadow-[4px_4px_0_0_rgba(0,0,0,0.2)] text-xs md:text-sm uppercase tracking-wider whitespace-nowrap transition-all transform hover:-translate-y-0.5" data-category="all">
            ✨ Semua Menu
        </button>
        @foreach($categories as $cat)
            <button class="category-tab px-5 py-2.5 bg-[#F4EAD4] text-[#4A2311] font-bold rounded-xl border-2 border-[#8B5A2B]/30 shadow-[3px_3px_0_0_rgba(0,0,0,0.1)] text-xs md:text-sm uppercase tracking-wider whitespace-nowrap hover:bg-[#4A2311] hover:text-white transition-all transform hover:-translate-y-0.5" data-category="{{ $cat }}">
                {{ $cat }}
            </button>
        @endforeach
    </div>

    {{-- BUKU MENU 3D --}}
    <div class="book-container relative mx-auto my-4 max-w-6xl">
        <div class="book-wrapper relative min-h-[580px] md:min-h-[660px] rounded-2xl parchment-bg overflow-hidden border-4 border-[#3D1E0F] flex flex-col justify-between">
            
            {{-- Bayangan Sampul & Spine Tengah --}}
            <div class="absolute inset-y-0 left-1/2 hidden md:block w-12 -translate-x-1/2 book-spine-shadow z-20 pointer-events-none"></div>
            
            {{-- Halaman Kiri & Kanan Container --}}
            <div class="relative flex-1 p-4 md:p-8">
                
                {{-- Dynamic Animated Flip Sheet (Efek Kertas Membalik) --}}
                <div id="flipSheet" class="page-turn-layer hidden parchment-bg border-l border-amber-900/20 p-6 shadow-2xl">
                    <div class="h-full w-full opacity-40 bg-amber-900/10 rounded-lg flex items-center justify-center">
                        <span class="text-amber-900 font-black text-xl italic">BigBang Eatery</span>
                    </div>
                </div>

                {{-- Grid Konten Menu --}}
                <div id="menuGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach($menus as $menu)
                        <div class="menu-card-item cursor-pointer group relative flex flex-col justify-between rounded-xl border-2 border-[#8B5A2B]/40 bg-[#FFFDF9] p-3 shadow-md"
                             data-category="{{ $menu->kategori }}"
                             data-menu-card
                             onclick="openMenuDetail({{ json_encode([
                                 'id' => $menu->id,
                                 'nama' => $menu->nama,
                                 'harga' => $menu->harga,
                                 'kategori' => $menu->kategori,
                                 'is_bestseller' => (bool)$menu->is_bestseller,
                                 'gambar' => $menu->gambar ? asset('storage/' . $menu->gambar) : null,
                                 'deskripsi' => $menu->deskripsi ?? 'Hidangan lezat disajikan segar dengan racikan bumbu khas BigBang Eatery.'
                             ]) }})">
                            
                            {{-- Foto & Badges --}}
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-lg bg-stone-100 border border-amber-900/10">
                                @if($menu->is_bestseller)
                                    <div class="absolute top-2 left-2 z-10 flex items-center gap-1 bg-amber-400 text-[#4A2311] text-[10px] font-black px-2.5 py-1 rounded-full border border-[#4A2311] shadow-md">
                                        ⭐ BEST SELLER
                                    </div>
                                @endif

                                @if($menu->gambar)
                                    <img src="{{ asset('storage/' . $menu->gambar) }}" alt="{{ $menu->nama }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-stone-300 text-5xl">🍳</div>
                                @endif
                            </div>

                            {{-- Info Menu --}}
                            <div class="mt-3 flex flex-col justify-between flex-1">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800/70">{{ $menu->kategori }}</span>
                                    <h3 class="font-black text-sm text-[#3D1E0F] line-clamp-1 group-hover:text-amber-800 transition-colors">{{ $menu->nama }}</h3>
                                </div>
                                <div class="mt-3 pt-2 border-t border-dashed border-amber-900/20 flex items-center justify-between">
                                    <span class="text-xs font-bold text-stone-500">Harga</span>
                                    <span class="text-sm font-black text-[#8B5A2B]">Rp {{ number_format($menu->harga, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>

            {{-- Footer Kontrol Lembaran Buku --}}
            <div class="relative z-30 flex items-center justify-between border-t-2 border-[#8B5A2B]/30 bg-[#EFE3CF] px-4 py-3 md:px-8">
                <button type="button" id="bookPrev" class="flex items-center gap-2 rounded-xl border-2 border-[#4A2311] bg-[#4A2311] px-4 py-2 text-xs md:text-sm font-black text-amber-100 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] transition-all hover:bg-[#65321A] active:translate-y-0.5 active:shadow-none disabled:cursor-not-allowed disabled:opacity-40">
                    Sebelumnya
                </button>
                
                <div class="flex items-center gap-2">
                    <span id="bookPageLabel" class="text-xs md:text-sm font-black uppercase tracking-wider text-[#4A2311]">Halaman 1</span>
                </div>

                <button type="button" id="bookNext" class="flex items-center gap-2 rounded-xl border-2 border-[#4A2311] bg-[#4A2311] px-4 py-2 text-xs md:text-sm font-black text-amber-100 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] transition-all hover:bg-[#65321A] active:translate-y-0.5 active:shadow-none disabled:cursor-not-allowed disabled:opacity-40">
                    Selanjutnya
                </button>
            </div>
        </div>
    </div>
</div>

{{-- IN-PAGE DETAIL MODAL OVERLAY (TIDAK REDIRECT HALAMAN) --}}
<div id="menuDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="relative w-full max-w-lg scale-95 rounded-2xl border-4 border-[#4A2311] bg-[#FAF4E8] p-5 md:p-6 shadow-2xl transition-transform duration-300 transform" id="modalCard">
        
        {{-- Tombol Close --}}
        <button type="button" onclick="closeMenuDetail()" class="absolute -top-3 -right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-[#4A2311] bg-red-600 font-black text-white shadow-md hover:bg-red-700 transition">
            ✕
        </button>

        {{-- Gambar Utama --}}
        <div class="relative h-56 md:h-64 w-full overflow-hidden rounded-xl border-2 border-amber-900/20 bg-stone-200">
            <img id="modalGambar" src="" alt="Menu" class="h-full w-full object-cover">
            <div id="modalFallbackGambar" class="hidden h-full w-full items-center justify-center text-6xl text-stone-400">🍽️</div>
            
            <div id="modalBestSellerBadge" class="hidden absolute top-3 left-3 z-10 items-center gap-1 bg-amber-400 text-[#4A2311] text-xs font-black px-3 py-1 rounded-full border border-[#4A2311] shadow-md">
                ⭐ BEST SELLER
            </div>
        </div>

        {{-- Detail Konten --}}
        <div class="mt-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span id="modalKategori" class="text-xs font-bold uppercase tracking-wider text-amber-800">Kategori</span>
                    <h2 id="modalNama" class="text-xl md:text-2xl font-black text-[#3D1E0F]">Nama Menu</h2>
                </div>
                <span id="modalHarga" class="text-lg md:text-xl font-black text-orange-700 whitespace-nowrap bg-amber-100 px-3 py-1 rounded-lg border border-amber-300">Rp 0</span>
            </div>

            <p id="modalDeskripsi" class="text-sm font-medium leading-relaxed text-stone-700 bg-white/60 p-3 rounded-lg border border-amber-900/10 max-h-28 overflow-y-auto">
                Deskripsi menu makanan...
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuCards = [...document.querySelectorAll('[data-menu-card]')];
    const tabs = [...document.querySelectorAll('.category-tab')];
    const flipSheet = document.getElementById('flipSheet');
    
    // 8 item per halaman (cocok untuk tampilan buku dual-page / 4 col desktop)
    const pageSize = 8;
    let currentCategory = 'all';
    let page = 0;

    function renderBook(withFlipAnimation = false, direction = 'next') {
        if (withFlipAnimation) {
            flipSheet.classList.remove('hidden', 'turning-next', 'turning-prev');
            flipSheet.classList.add(direction === 'next' ? 'turning-next' : 'turning-prev');
            
            setTimeout(() => {
                applyFilterAndPagination();
                flipSheet.classList.add('hidden');
            }, 300);
        } else {
            applyFilterAndPagination();
        }
    }

    function applyFilterAndPagination() {
        const filtered = menuCards.filter(card => currentCategory === 'all' || card.dataset.category === currentCategory);
        const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
        page = Math.min(page, pages - 1);

        menuCards.forEach(card => { card.style.display = 'none'; });

        const currentPageItems = filtered.slice(page * pageSize, (page + 1) * pageSize);
        currentPageItems.forEach(card => { card.style.display = 'flex'; });

        document.getElementById('bookPageLabel').textContent = `Halaman ${page + 1} dari ${pages}`;
        document.getElementById('bookPrev').disabled = page === 0;
        document.getElementById('bookNext').disabled = page >= pages - 1;
    }

    document.getElementById('bookPrev').addEventListener('click', () => { 
        if (page > 0) {
            page--; 
            renderBook(true, 'prev'); 
        }
    });

    document.getElementById('bookNext').addEventListener('click', () => { 
        const filtered = menuCards.filter(card => currentCategory === 'all' || card.dataset.category === currentCategory);
        const pages = Math.ceil(filtered.length / pageSize);
        if (page < pages - 1) {
            page++; 
            renderBook(true, 'next'); 
        }
    });

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => {
                t.classList.remove('active', 'bg-[#4A2311]', 'text-amber-200', 'border-amber-900/40');
                t.classList.add('bg-[#F4EAD4]', 'text-[#4A2311]', 'border-[#8B5A2B]/30');
            });
            this.classList.remove('bg-[#F4EAD4]', 'text-[#4A2311]', 'border-[#8B5A2B]/30');
            this.classList.add('active', 'bg-[#4A2311]', 'text-amber-200', 'border-amber-900/40');

            currentCategory = this.dataset.category;
            page = 0;
            renderBook(true, 'next');
        });
    });

    renderBook();
});

/* IN-PAGE DETAIL MODAL FUNCTIONS */
function openMenuDetail(data) {
    const modal = document.getElementById('menuDetailModal');
    const modalCard = document.getElementById('modalCard');
    
    document.getElementById('modalNama').textContent = data.nama;
    document.getElementById('modalKategori').textContent = data.kategori;
    document.getElementById('modalHarga').textContent = 'Rp ' + Number(data.harga).toLocaleString('id-ID');
    document.getElementById('modalDeskripsi').textContent = data.deskripsi;

    const imgEl = document.getElementById('modalGambar');
    const fallbackEl = document.getElementById('modalFallbackGambar');
    
    if (data.gambar) {
        imgEl.src = data.gambar;
        imgEl.classList.remove('hidden');
        fallbackEl.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        fallbackEl.classList.remove('hidden');
        fallbackEl.classList.add('flex');
    }

    const badgeEl = document.getElementById('modalBestSellerBadge');
    if (data.is_bestseller) {
        badgeEl.classList.remove('hidden');
        badgeEl.classList.add('flex');
    } else {
        badgeEl.classList.add('hidden');
    }

    modal.classList.remove('pointer-events-none', 'opacity-0');
    modalCard.classList.remove('scale-95');
    modalCard.classList.add('scale-100');
}

function closeMenuDetail() {
    const modal = document.getElementById('menuDetailModal');
    const modalCard = document.getElementById('modalCard');

    modalCard.classList.remove('scale-100');
    modalCard.classList.add('scale-95');
    modal.classList.add('opacity-0', 'pointer-events-none');
}
</script>
@endsection
