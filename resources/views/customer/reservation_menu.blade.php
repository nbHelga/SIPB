@extends('layouts.landing')

@section('title', 'Daftar Menu - BigBang Eatery')

@section('header_right')
    <span class="text-sm font-bold uppercase tracking-wider text-white">Katalog Menu</span>
@endsection

@section('content')
    <div class="pt-24 pb-10 max-w-7xl mx-auto px-4 py-6">
        
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
                <article class="menu-item bg-[#F4EAD4] rounded-lg border-2 border-[#4A2311] shadow-[4px_4px_0_0_rgba(74,35,17,0.25)] overflow-hidden hover:shadow-[6px_6px_0_0_rgba(74,35,17,0.4)] hover:-translate-y-0.5 transition-all"
                    data-category="{{ $menu->kategori }}" data-menu-card tabindex="0" role="button" aria-expanded="false" aria-label="Lihat detail {{ $menu->nama }}">
                    
                    <div class="menu-image relative block aspect-[4/3] bg-stone-200 overflow-hidden">
                        
                        {{-- Highlight Badge Best Seller --}}
                        @if($menu->is_bestseller)
                            <div class="absolute top-2 left-2 z-10 flex items-center gap-1 bg-amber-400 text-[#4A2311] text-xs font-black px-2.5 py-1 rounded-full border-2 border-[#4A2311] shadow-[2px_2px_0_0_rgba(74,35,17,1)]">
                                ⭐
                            </div>
                        @endif

                        @if($menu->gambar)
                            <img src="{{ asset('storage/' . $menu->gambar) }}" alt="{{ $menu->nama }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-stone-400 text-4xl">🍽️</div>
                        @endif
                    </div>
                    
                    <div class="menu-summary p-3 bg-[#E6D5BC]">
                        <h3 class="font-bold text-sm truncate text-[#4A2311]">{{ $menu->nama }}</h3>
                        <p class="text-orange-600 font-black mt-1">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                    </div>

                    @php($descriptionWordCount = preg_match_all('/\S+/u', $menu->deskripsi ?? '') ?: 0)
                    <div class="menu-details" aria-hidden="true">
                        <span class="inline-block px-3 py-1 bg-[#4A2311] text-white text-xs font-bold rounded-full uppercase tracking-wider">{{ $menu->kategori }}</span>
                        <p class="menu-detail-name text-xl font-black text-[#4A2311] mt-3">{{ $menu->nama }}</p>
                        <p class="text-orange-600 font-black mt-1">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                        <div class="border-t-2 border-[#4A2311] opacity-20 my-3"></div>
                        <p class="menu-description text-stone-700 font-medium leading-relaxed {{ $descriptionWordCount > 30 ? 'is-scrollable' : '' }}">{{ $menu->deskripsi ?? 'Tidak ada deskripsi untuk menu ini.' }}</p>
                        <p class="text-sm font-bold text-stone-600 mt-4">Status Menu: <span class="text-[#4A2311] font-black">Tersedia</span></p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <style>
        .menu-item {
            cursor: pointer;
            transform-origin: top left;
        }

        .menu-item.is-expanded {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-rows: minmax(0, 1fr);
            transform: none;
        }

        .menu-item.is-expanded .menu-image {
            grid-column: 1;
            grid-row: 1;
            aspect-ratio: auto;
            min-height: 100%;
        }

        .menu-item.is-expanded .menu-summary {
            display: none;
        }

        .menu-details {
            display: none;
        }

        .menu-item.is-expanded .menu-details {
            display: block;
            grid-column: 2;
            grid-row: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 1rem;
            background: #E6D5BC;
            animation: menu-details-in 250ms ease both;
        }

        .menu-item.is-expanded .menu-detail-name {
            font-size: 1.125rem;
            margin-top: 0.5rem;
        }

        .menu-description.is-scrollable {
            max-height: 5rem;
            overflow-y: auto;
            padding-right: 0.35rem;
        }

        @keyframes menu-details-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .menu-item.is-expanded .menu-details { animation: none; }
        }
    </style>

    <script>
    const menuCards = [...document.querySelectorAll('[data-menu-card]')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function toggleMenuCard(card) {
        const cardHeight = card.getBoundingClientRect().height;
        const positions = reduceMotion.matches
            ? []
            : menuCards.filter(item => item.style.display !== 'none').map(item => [item, item.getBoundingClientRect()]);

        const willExpand = !card.classList.contains('is-expanded');
        menuCards.forEach(item => {
            const isExpanded = item === card && willExpand;
            item.classList.toggle('is-expanded', isExpanded);
            item.style.height = isExpanded ? `${cardHeight}px` : '';
            item.setAttribute('aria-expanded', String(isExpanded));
            item.querySelector('.menu-details').setAttribute('aria-hidden', String(!isExpanded));
        });

        positions.forEach(([item, before]) => {
            const after = item.getBoundingClientRect();
            const offsetX = before.left - after.left;
            const offsetY = before.top - after.top;
            const scaleX = before.width / after.width;
            const scaleY = before.height / after.height;

            item.style.transition = 'none';
            item.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scaleX}, ${scaleY})`;
            requestAnimationFrame(() => {
                item.style.transition = 'transform 350ms cubic-bezier(0.2, 0.75, 0.25, 1)';
                item.style.transform = '';
                item.addEventListener('transitionend', () => {
                    item.style.transition = '';
                }, { once: true });
            });
        });
    }

    menuCards.forEach(card => {
        card.addEventListener('click', () => toggleMenuCard(card));
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleMenuCard(card);
            }
        });
    });

    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.category-tab').forEach(t => {
                t.classList.remove('active', 'bg-[#4A2311]', 'text-white', 'border-stone-900');
                t.classList.add('bg-white', 'text-stone-700', 'border-stone-300');
            });
            this.classList.remove('bg-white', 'text-stone-700', 'border-stone-300');
            this.classList.add('active', 'bg-[#4A2311]', 'text-white', 'border-stone-900');

            const cat = this.dataset.category;
            menuCards.forEach(item => {
                const isVisible = cat === 'all' || item.dataset.category === cat;
                item.style.display = isVisible ? '' : 'none';
                if (!isVisible) {
                    item.classList.remove('is-expanded');
                    item.setAttribute('aria-expanded', 'false');
                    item.querySelector('.menu-details').setAttribute('aria-hidden', 'true');
                }
            });
        });
    });
    </script>
@endsection