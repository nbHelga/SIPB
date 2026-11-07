<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard - BigBang Eatery')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        [x-cloak] { display: none !important; }
        @media (min-width: 1280px) {
            .selforder-sidebar {
                position: static !important;
                inset: auto !important;
                z-index: auto !important;
                display: flex !important;
                flex: 0 0 22rem !important;
                width: 22rem !important;
                max-width: none !important;
                height: 100% !important;
                transform: none !important;
                translate: none !important;
                visibility: visible !important;
            }
            // tutup tombol x versi 1280px
            .selforder-close-button {
                display: none !important;
            }

            body main .selforder-open-button,
            body main .selforder-overlay {
                display: none !important;
            }

            .reservation-sidebar {
                position: static !important;
                inset: auto !important;
                z-index: auto !important;
                display: flex !important;
                flex: 0 0 20rem !important;
                width: 20rem !important;
                max-width: none !important;
                height: 100% !important;
                transform: none !important;
                translate: none !important;
                visibility: visible !important;
            }

            .reservation-open-button,
            .reservation-overlay,
            .reservation-close-button {
                display: none !important;
            }
        }
    </style>
</head>
<body
    class="bg-stone-100 text-stone-900 font-sans min-h-screen flex flex-col"
    x-data="{
        mobileSidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('admin-sidebar-collapsed') === '1',
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem(
                'admin-sidebar-collapsed',
                this.sidebarCollapsed ? '1' : '0'
            );
        }
    }"
>
    
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#291010] text-white border-b-4 border-[#8B5A2B] shadow-[0_4px_0_0_rgba(0,0,0,0.3)]">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="text-white block md:hidden focus:outline-none mr-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <img src="{{ asset('logo.png') }}" class="w-24 h-12 object-contain">
                <span class="text-lg font-black tracking-tight text-white hidden sm:inline">BIGBANG EATERY</span>
            </div>
            <div class="flex items-center gap-4">
                @if(auth()->check())
                    <details class="relative">
                        <summary class="cursor-pointer list-none text-sm font-bold bg-[#4A2311] px-3 py-1 rounded-md border border-stone-700">
                            {{ auth()->user()->username }} ▾
                        </summary>
                        <div class="absolute right-0 mt-2 w-64 rounded-lg border-2 border-stone-900 bg-white p-4 text-stone-900 shadow-xl">
                            <p class="font-bold">{{ auth()->user()->username }}</p>
                            <p class="mt-2 text-sm">
                                Password:
                                <span aria-label="Password disembunyikan">••••••••</span>
                            </p>
                            <p class="mt-1 text-xs text-stone-600">
                                Password tersimpan dalam bentuk aman dan tidak dapat ditampilkan. Jika lupa, gunakan fitur ubah/reset password.
                            </p>
                        </div>
                    </details>

                    <form id="admin-logout-form" method="POST" action="{{ route('login.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm bg-red-600 px-3 py-1 rounded font-bold hover:bg-red-700 border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,0.3)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all">Logout</button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <div class="flex flex-1 pt-[68px] min-h-screen w-full relative">
        
        <aside
            :class="[
                mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
                sidebarCollapsed ? 'w-20' : 'w-64'
            ]"
            class="bg-stone-800 text-white border-r-4 border-[#8B5A2B] fixed md:sticky top-[68px] h-[calc(100vh-68px)] p-3 z-40 transition-all duration-300 ease-in-out overflow-y-auto"
        >
            <div class="mb-4 flex justify-start">
                <button
                    type="button"
                    @click="toggleSidebar()"
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#4A2311] hover:bg-[#67331a]"
                    :aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar'"
                    :title="sidebarCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar'"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <nav class="space-y-2">
                @php
                    $status = strtolower(trim((string) auth()->user()?->status));
                    $isAdmin = $status === 'admin';
                    $isKasir = in_array($status, ['kasir', 'cashier'], true);

                    $links = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => '📊', 'access' => ['admin', 'kasir']],
                        ['route' => 'admin.menu', 'label' => 'Menu', 'icon' => '🍔', 'access' => ['admin', 'kasir']],
                        ['route' => 'admin.user', 'label' => 'Akun Pengguna', 'icon' => '👥', 'access' => ['admin']],
                        ['route' => 'admin.selforder', 'label' => 'Self-Order', 'icon' => '🧾', 'access' => ['admin', 'kasir']],
                        ['route' => 'admin.meja', 'label' => 'Alokasi Meja', 'icon' => '🪑', 'access' => ['admin', 'kasir']],
                        ['route' => 'admin.reservasi', 'label' => 'Reservasi', 'icon' => '📅', 'access' => ['admin', 'kasir']],
                    ];
                @endphp

                @foreach($links as $link)
                    @if(
                        ($isAdmin && in_array('admin', $link['access'], true))
                        || ($isKasir && in_array('kasir', $link['access'], true))
                    )
                        <a
                            href="{{ route($link['route']) }}"
                            class="relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 font-bold transition hover:bg-[#4A2311] {{ request()->routeIs($link['route'] . '*') ? 'bg-[#4A2311]' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center' : ''"
                            title="{{ $link['label'] }}"
                        >
                            <span class="text-xl">{{ $link['icon'] }}</span>
                            <span x-show="!sidebarCollapsed" x-transition>{{ $link['label'] }}</span>

                            @if($link['route'] === 'admin.selforder')
                                <span
                                    x-show="!sidebarCollapsed"
                                    x-cloak
                                    data-admin-count="orders"
                                    class="ml-auto hidden min-w-6 rounded-full bg-red-600 px-2 py-0.5 text-center text-xs text-white"
                                ></span>
                                <span
                                    x-show="sidebarCollapsed"
                                    x-cloak
                                    data-admin-count="orders"
                                    class="absolute right-0 top-0 hidden min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] text-white"
                                ></span>
                            @elseif($link['route'] === 'admin.reservasi')
                                <span
                                    x-show="!sidebarCollapsed"
                                    x-cloak
                                    data-admin-count="reservations"
                                    class="ml-auto hidden min-w-6 rounded-full bg-red-600 px-2 py-0.5 text-center text-xs text-white"
                                ></span>
                                <span
                                    x-show="sidebarCollapsed"
                                    x-cloak
                                    data-admin-count="reservations"
                                    class="absolute right-0 top-0 hidden min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] text-white"
                                ></span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div x-show="mobileSidebarOpen" @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black/40 z-30 md:hidden" x-transition></div>

        <main class="flex-1 px-6 py-8 overflow-x-hidden min-w-0">
            @yield('content')
        </main>
    </div>

    @yield('modals')
    {{-- <div id="admin-new-alert"
     class="fixed right-4 top-20 z-[100] hidden rounded-xl border-2 border-stone-900 bg-amber-300 px-5 py-3 font-black shadow-lg"
     role="status"></div> --}}
    <x-alert type="admin-notification" />
    <script>
    (() => {
        const endpoint = @json(route('admin.notifications'));
        const seenPrefix = 'sipb:admin:seen:';
        const alertPrefix = 'sipb:admin:alert-shown:';
        let activeSessionKey = null;
        let announcedThisPage = new Set();

        const storageKey = (prefix, sessionKey, itemKey) =>
            `${prefix}${sessionKey}:${itemKey}`;

        function clearSessionNotifications(sessionKey) {
            if (!sessionKey) return;

            for (const prefix of [seenPrefix, alertPrefix]) {
                const fullPrefix = `${prefix}${sessionKey}:`;

                for (let i = localStorage.length - 1; i >= 0; i--) {
                    const key = localStorage.key(i);
                    if (key?.startsWith(fullPrefix)) {
                        localStorage.removeItem(key);
                    }
                }
            }
        }

        document.getElementById('admin-logout-form')?.addEventListener('submit', () => {
            clearSessionNotifications(activeSessionKey);
        });

        function updateBadge(selector, count) {
            document.querySelectorAll(selector).forEach(badge => {
                badge.textContent = count;
                badge.classList.toggle('hidden', count === 0);
            });
        }

        function updateDots(currentKeys) {
            document.querySelectorAll('[data-admin-new-dot]').forEach(dot => {
                const key = dot.dataset.adminNewDot;
                const seen = activeSessionKey
                    && localStorage.getItem(storageKey(seenPrefix, activeSessionKey, key)) === '1';

                dot.classList.toggle('hidden', !currentKeys.has(key) || seen);
            });
        }

        async function pollAdminNotifications() {
            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const data = await response.json();

                if (!data.session_key) {
                    activeSessionKey = null;
                    updateBadge('[data-admin-count="orders"]', 0);
                    updateBadge('[data-admin-count="reservations"]', 0);
                    updateDots(new Set());
                    return;
                }

                if (activeSessionKey !== data.session_key) {
                    activeSessionKey = data.session_key;
                    announcedThisPage = new Set();
                }

                const orders = data.orders || [];
                const reservations = data.reservations || [];
                const allKeys = [...orders, ...reservations];
                const currentKeys = new Set(allKeys);

                const isSeen = key =>
                    localStorage.getItem(storageKey(seenPrefix, activeSessionKey, key)) === '1';

                updateBadge('[data-admin-count="orders"]', orders.filter(key => !isSeen(key)).length);
                updateBadge('[data-admin-count="reservations"]', reservations.filter(key => !isSeen(key)).length);
                updateDots(currentKeys);

                const fresh = allKeys.filter(key =>
                    !announcedThisPage.has(key)
                    && localStorage.getItem(storageKey(alertPrefix, activeSessionKey, key)) !== '1'
                );

                if (fresh.length) {
                    fresh.forEach(key => {
                        announcedThisPage.add(key);
                        localStorage.setItem(storageKey(alertPrefix, activeSessionKey, key), '1');
                    });

                    window.dispatchEvent(new CustomEvent('admin-new-items', {
                        detail: { message: `${fresh.length} pesanan/reservasi baru masuk.` },
                    }));
                }
            } catch (error) {
                console.error('Gagal memeriksa notifikasi admin:', error);
            }
        }

        document.addEventListener('click', event => {
            const link = event.target.closest('[data-admin-seen-key]');
            if (!link || !activeSessionKey) return;

            const key = link.dataset.adminSeenKey;
            localStorage.setItem(storageKey(seenPrefix, activeSessionKey, key), '1');

            document.querySelectorAll('[data-admin-new-dot]').forEach(dot => {
                if (dot.dataset.adminNewDot === key) dot.classList.add('hidden');
            });

            pollAdminNotifications();
        });

        pollAdminNotifications();
        window.setInterval(pollAdminNotifications, 10000);
    })();
    </script>
</body>
</html>