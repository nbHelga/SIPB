<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIPB - BigBang Eatery')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-stone-100 text-stone-900 font-sans min-h-screen flex flex-col">
    <!-- HEADER FIXED -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#291010] text-white border-b-4 border-orange-500 shadow-[0_4px_0_0_rgba(0,0,0,0.3)]">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg border-2 border-white shadow-[2px_2px_0_0_rgba(0,0,0,0.3)]">B</div>
                <span class="text-lg font-black tracking-tight">SIPB - <span class="text-orange-400">BIGBANG</span></span>
            </div>
            <div class="flex items-center gap-4">
                @if(auth()->check())
                    <span class="text-sm font-bold">{{ auth()->user()->username }}</span>
                    <a href="{{ route('login.logout') }}" class="text-sm bg-red-600 px-3 py-1 rounded font-bold hover:bg-red-700 transition">Logout</a>
                @endif
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="flex-1 pt-14">
        @yield('content')
    </main>

    @yield('modals')
</body>
</html>
