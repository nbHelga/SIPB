<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BigBang Eatery')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-stone-50 text-stone-900 font-sans antialiased">
    <!-- HEADER -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#291010] text-white border-b-4 border-[#8B5A2B] shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                {{-- <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center font-black text-xl border-2 border-white shadow-sm">B</div>
                <span class="text-xl font-black tracking-tight">BIGBANG <span class="text-orange-400">EATERY</span></span> --}}
                <img src="{{ asset('logo.png') }}" class="w-24 h-12 object-contain">
                <span class="text-lg font-black tracking-tight text-white">BIGBANG <span class="text-white">EATERY</span></span>
            </a>
            @if(request()->routeIs('home'))
                <nav class="hidden md:flex items-center gap-6 font-semibold text-sm">
                    <a href="#beranda" class="hover:text-{#4A2311} transition">Beranda</a>
                    <a href="#menu" class="hover:text-{#4A2311} transition">Menu</a>
                    <a href="#reservasi" class="hover:text-[#4A2311] transition">Reservasi</a>
                    <a href="#kontak" class="hover:text-[#4A2311] transition">Kontak</a>
                </nav>
            @endif
            <button id="hamburger" class="md:hidden text-2xl hover:text-{#4A2311}">☰</button>
        </div>
    </header>
    <main>@yield('content')</main>
    <!-- FOOTER -->
    <footer class="bg-[#291010] text-white border-t-4 border-stone-900 pt-12 pb-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start pb-8 border-b-2 border-stone-700">
                
                <!-- BAGIAN 1: Deskripsi & Jam Operasional -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-black tracking-wide text-amber-300">BIGBANG EATERY</h3>
                    <p class="text-stone-200 text-sm leading-relaxed italic">
                        “Makanannya memang enak tapi kehadiranmu membuat momen ini jauh lebih berarti”
                    </p>
                    <div class="pt-2">
                        <span class="inline-block px-3 py-1 bg-amber-400 text-stone-900 font-extrabold text-xs rounded-md border border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
                            ⏰ Jam Operasional
                        </span>
                        <p class="text-sm font-bold text-stone-100 mt-2">
                            Setiap Hari: <span class="text-amber-300">15.00 - 00.00 WIB</span>
                        </p>
                    </div>
                </div>

                <!-- BAGIAN 2: Maps & Alamat -->
                <div class="space-y-3 px-12">
                    <h4 class="text-lg font-black text-amber-300 uppercase tracking-wider">Lokasi kami</h4>
                    
                    <!-- Embed Google Maps -->
                    <div class="w-full h-36 bg-stone-800 rounded-xl border-2 border-stone-900 overflow-hidden shadow-[4px_4px_0_0_rgba(0,0,0,0.5)]">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.5003691044444!2d110.4320589!3d-7.067834800000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708f005e872e3f%3A0xb890f049db5768a2!2sBigbang%20eatery!5e0!3m2!1sid!2sid!4v1788718458235!5m2!1sid!2sid"  
                            class="w-full h-full border-0" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    
                    <a href="https://maps.app.goo.gl/zmuBQr8TDmFfoQpd9" target="_blank" class="block group">
                        <p class="text-xs text-stone-200 group-hover:text-amber-300 transition-colors leading-snug">
                            Jl. Mulawarman Raya No.3, Pedalangan, Kec. Banyumanik, Kota Semarang, Jawa Tengah 50268
                        </p>
                    </a>
                </div>

                <!-- BAGIAN 3: Kontak & Sosmed (Tanpa Kotak/Background) -->
                <div class="space-y-4 px-12">
                    <h4 class="text-lg font-black text-amber-300 uppercase tracking-wider">Hubungi Kami</h4>
                    <div class="space-y-3 text-sm font-medium">
                        
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/BIGBANG_EATERY" target="_blank" 
                        class="flex items-center gap-3 text-stone-200 hover:text-amber-300 transition-colors group">
                            <img src="{{ asset('landing/instagram.png') }}" alt="Instagram" class="w-6 h-6 object-contain group-hover:scale-110 transition-transform">
                            <span class="font-bold">IG @BIGBANG_EATERY</span>
                        </a>

                        <!-- TikTok -->
                        <a href="https://www.tiktok.com/@BIGBANG_EATERY" target="_blank" 
                        class="flex items-center gap-3 text-stone-200 hover:text-amber-300 transition-colors group">
                            <img src="{{ asset('landing/tik-tok.png') }}" alt="TikTok" class="w-6 h-6 object-contain group-hover:scale-110 transition-transform">
                            <span class="font-bold">TIKTOK @BIGBANG_EATERY</span>
                        </a>

                        <!-- WhatsApp / Reservasi -->
                        <a href="https://wa.me/6285168170080" target="_blank" 
                        class="flex items-center gap-3 text-stone-200 hover:text-amber-300 transition-colors group">
                            <img src="{{ asset('landing/whatsapp.png') }}" alt="WhatsApp" class="w-6 h-6 object-contain group-hover:scale-110 transition-transform">
                            <span class="font-bold">RESERVASI: 085168170080</span>
                        </a>
                        
                    </div>
                </div>

            </div>

            <div class="mt-6 flex flex-col items-center justify-between gap-3 text-center text-xs font-medium text-stone-400 sm:flex-row sm:text-left">
                <span>&copy; {{ \Carbon\Carbon::now('Asia/Jakarta')->year }} BigBang Eatery. All rights reserved.</span>
                <a href="{{ route('login') }}" class="underline-offset-2 hover:text-white hover:underline">Login Admin</a>
            </div>
        </div>
    </footer>
</body>
</html>
