<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BigBang Eatery - Customer')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
{{-- <body class="bg-stone-50 text-stone-900 font-sans antialiased flex flex-col min-h-screen"> --}}
<body class="bg-stone-50 text-[#4A2311] font-sans antialiased flex flex-col min-h-screen">
    <!-- HEADER -->
    {{-- <header class="sticky top-0 z-50 bg-stone-900 text-white border-b-4 border-orange-500 shadow-lg"> --}}
    <header class="sticky top-0 z-50 bg-[#291010] text-white border-b-4 border-[#8B5A2B] shadow-lg">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between gap-2">
            
            {{-- Sisi Kiri: Logo + Nama Resto --}}
            <a class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                {{-- Ukuran logo disesuaikan di mobile agar tidak terlalu memakan tempat --}}
                <img src="{{ asset('logo.png') }}" class="w-16 h-9 sm:w-24 sm:h-12 object-contain">
                
                {{-- Teks disembunyikan di mobile (hidden), baru muncul di layar sm ke atas (sm:inline-block) --}}
                <span class="hidden sm:inline-block text-base sm:text-lg font-black tracking-tight text-white">
                    BIGBANG <span class="text-white">EATERY</span>
                </span>
            </a>

            {{-- Sisi Kanan: @yield Header Right --}}
            <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0 text-xs sm:text-sm">
                @yield('header_right')
            </div>

        </div>
    </header>

    <!-- CONTENT -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="sticky bottom-0 z-10 left-0 w-full bg-[#291010] text-white border-t-4 border-[#8B5A2B]">
        <div class="max-w-7xl mx-auto px-4 py-4 text-center text-stone-400 text-sm">
            &copy; {{ date('Y') }} BigBang Eatery - Selamat menikmati hidangan!
        </div>
    </footer>
</body>
</html>
