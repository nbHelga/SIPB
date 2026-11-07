@extends('layouts.landing')

@section('title', 'BigBang Eatery - Home')

@section('content')

{{-- SCRIPT & CSS KHUSUS LANDING PAGE --}}
<style>
    :root {
        --landing-accent: #660b0b;
        --landing-amber: #FBBF24;
    }

    /* Paksa header di landing page transparan secara default */
    header {
        background-color: transparent !important;
        border-bottom-color: transparent !important;
        box-shadow: none !important;
        transition: background-color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
    }
    header.header-scrolled {
        background-color: #291010 !important;
        border-bottom-color: #8B5A2B !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4) !important;
    }

    html {
        scroll-behavior: smooth;
    }

    #beranda .hero-bg {
        transform: scale(1.04);
        transition: opacity 1.2s ease, transform 8s ease;
    }

    #beranda .hero-bg.opacity-100 {
        transform: scale(1);
    }

    .hero-scrim {
        background: linear-gradient(90deg, rgba(0, 0, 0, 0.76), rgba(0, 0, 0, 0.35) 68%, rgba(0, 0, 0, 0.48));
    }

    .hero-copy h1 {
        animation: hero-rise 850ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
    }

    .hero-copy p {
        animation: hero-rise 850ms 160ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
    }

    .landing-menu-card {
        transition: transform 280ms ease, box-shadow 280ms ease;
    }

    .landing-menu-card img {
        transition: transform 450ms ease;
    }

    .landing-menu-card:hover {
        box-shadow: 0 14px 28px rgba(28, 25, 23, 0.2);
    }

    .landing-menu-card:hover img {
        transform: scale(1.08);
    }

    .reservation-panel {
        position: relative;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
    }

    .reservation-panel::before {
        position: absolute;
        top: 0;
        left: 10%;
        width: 80%;
        height: 2px;
        background: var(--landing-amber);
        content: '';
    }

    #formCekReservasi input {
        transition: border-color 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
    }

    #formCekReservasi input:focus {
        background-color: rgba(0, 0, 0, 0.72);
        box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.22);
    }

    #btnSubmitReservasi {
        box-shadow: 0 8px 18px rgba(127, 29, 29, 0.3);
    }

    #btnSubmitReservasi:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 12px 22px rgba(127, 29, 29, 0.38);
    }

    @keyframes hero-rise {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes section-rise {
        from { opacity: 0; transform: translateY(22px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: no-preference) {
        html.has-reveal [data-reveal]:not(.is-visible) {
            opacity: 0;
            transform: translateY(22px);
        }

        [data-reveal] {
            transition: opacity 650ms ease, transform 650ms cubic-bezier(0.2, 0.75, 0.25, 1);
            transition-delay: var(--reveal-delay, 0ms);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        #beranda .hero-bg,
        .hero-copy h1,
        .hero-copy p,
        .landing-menu-card,
        .landing-menu-card img,
        [data-reveal] {
            animation: none !important;
            transition: none !important;
        }
    }

    #formCekReservasi input[type="date"]::-webkit-calendar-picker-indicator,
    #formCekReservasi input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
    }
</style>

<!-- SECTION BERANDA (HERO SLIDESHOW) -->
<section id="beranda" class="relative min-h-[90vh] md:min-h-screen flex items-center justify-center text-white overflow-hidden bg-stone-900">
    
    {{-- Layer Background Slideshow --}}
    <div class="absolute inset-0 z-0">
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-100" style="background-image: url('{{ asset('landing/landing.jpg') }}');"></div>
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-0" style="background-image: url('{{ asset('landing/landing2.jpg') }}');"></div>
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-0" style="background-image: url('{{ asset('landing/landing3.jpg') }}');"></div>
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-0" style="background-image: url('{{ asset('landing/landing4.jpg') }}');"></div>
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-0" style="background-image: url('{{ asset('landing/landing5.jpg') }}');"></div>
        <div class="hero-bg absolute inset-0 bg-cover bg-center transition-opacity duration-1000 opacity-0" style="background-image: url('{{ asset('landing/landing6.jpg') }}');"></div>
    </div>

    {{-- Dark Overlay --}}
    <div class="hero-scrim absolute inset-0 z-10"></div>

    {{-- Content --}}
    <div class="hero-copy max-w-7xl mx-auto px-4 sm:px-6 pt-20 pb-12 text-center relative z-20">
        <h1 class="text-3xl sm:text-6xl md:text-7xl font-black mb-4 tracking-tight drop-shadow-md leading-tight">
            MAKAN MEWAH <span class="text-[#8B5A2B] block sm:inline">HARGA MURAH</span>
        </h1>
        <p class="text-sm sm:text-xl text-stone-200 max-w-2xl mx-auto font-medium drop-shadow leading-relaxed">
            “Makanannya memang enak tapi kehadiranmu membuat momen ini jauh lebih berarti”
        </p>
    </div>
</section>


<!-- SECTION MENU -->
<section id="menu" class="py-12 md:py-20 px-4 md:px-8 bg-stone-50 relative overflow-hidden scroll-mt-14">
    <div class="max-w-6xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        
        {{-- Daftar Card Menu --}}
        <div class="lg:col-span-7 relative order-2 lg:order-1">
            {{-- Background Aksen (Hanya Muncul di Desktop agar tidak menutupi kartu di mobile) --}}
            <div class="hidden lg:block absolute inset-y-0 left-0 w-3/4 rounded-3xl overflow-hidden shadow-md -z-0">
                <img src="{{ asset('landing/landing7.jpg') }}" class="w-full h-full object-cover filter brightness-75">
                <div class="absolute inset-0 bg-black/30"></div>
            </div>

            <div class="relative z-10 space-y-3 md:space-y-4 py-2 lg:py-6 lg:pl-6 lg:pr-4">
                @foreach($menuTerbaru as $index => $menu)
                    @php
                        $isRed = $index % 2 === 0;
                        $cardBg = $isRed ? 'bg-[#660b0b] text-white' : 'bg-[#1F2937] text-white';
                    @endphp
                    <div data-reveal style="--reveal-delay: {{ min($index * 90, 360) }}ms" class="landing-menu-card {{ $cardBg }} p-3 md:p-4 rounded-xl md:rounded-3xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,0.25)] md:shadow-[6px_6px_0_0_rgba(0,0,0,0.25)] flex items-center gap-3 md:gap-4 hover:-translate-y-1">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-full overflow-hidden border-2 border-white flex-shrink-0 bg-stone-200">
                            @if($menu->gambar)
                                <img src="{{ asset('storage/' . $menu->gambar) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xl md:text-2xl">🍽️</div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold text-sm sm:text-base md:text-lg truncate">{{ $menu->nama }}</h3>
                            <p class="text-[11px] sm:text-xs text-stone-200 line-clamp-2 mt-0.5">{{ $menu->deskripsi ?? 'Hidangan lezat racikan koki profesional.' }}</p>
                            <p class="font-black text-xs sm:text-sm mt-1 text-amber-300">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Deskripsi Section Menu --}}
        <div data-reveal class="lg:col-span-5 flex flex-col justify-center text-left lg:pl-6 order-1 lg:order-2">
            <span class="text-[#660b0b] font-bold text-xs sm:text-sm tracking-widest uppercase mb-1 sm:mb-2">Menu</span>
            <h2 class="text-2xl sm:text-4xl md:text-5xl font-black text-stone-900 leading-tight mb-3 md:mb-4">
                Beberapa <span class="text-[#660b0b]">Menu Andalan</span> Kami
            </h2>
            <p class="text-stone-600 text-xs sm:text-sm md:text-base leading-relaxed mb-5 md:mb-6">
                Pilihan hidangan terbaik yang disiapkan dengan bahan segar berkualitas tinggi untuk memberikan cita rasa luar biasa di setiap santapan Anda.
            </p>
            <div>
                <a href="{{ route('customer.reservation.menu') }}" class="inline-block px-6 py-2.5 sm:px-8 sm:py-3.5 bg-[#660b0b] text-white font-black text-xs sm:text-sm rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] hover:bg-[#991B1B] active:shadow-none transition-all">
                    Detail Menu
                </a>
            </div>
        </div>

        {{-- DEKORASI SISi KANAN DESKTOP --}}
        <div class="hidden lg:flex absolute right-0 top-0 bottom-0 flex-col items-center justify-between py-20 pointer-events-none">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full border-2 border-stone-800 flex items-center justify-center text-stone-800 font-bold text-xs mb-2">
                    🔔
                </div>
                <div class="w-[2px] h-12 bg-stone-800"></div>
            </div>

            <div class="rotate-90 whitespace-nowrap tracking-[0.3em] font-bold text-stone-800 text-xs uppercase my-auto">
                BigBang Eatery
            </div>

            <div class="flex flex-col items-center">
                <div class="w-[2px] h-12 bg-stone-800"></div>
            </div>
        </div>
    </div>
</section>


<!-- SECTION RESERVASI -->
<section id="reservasi" class="relative py-12 md:py-24 bg-cover bg-center bg-fixed scroll-mt-18" style="background-image: url('{{ asset('landing/landing8.jpg') }}');">
    {{-- Dark Overlay --}}
    <div class="absolute inset-0 bg-black/80"></div>

    <div data-reveal class="relative z-10 max-w-5xl mx-auto px-4 text-center text-white">
        
        {{-- Header Reservasi --}}
        <p class="font-serif italic text-amber-400 text-lg md:text-2xl mb-1">Jam Buka</p>
        <h2 class="text-2xl sm:text-4xl md:text-5xl font-extrabold tracking-widest uppercase mb-3">RESERVASI</h2>
        
        <div class="flex items-center justify-center gap-3 mb-4">
            <div class="w-8 sm:w-12 h-[1px] bg-[#291010]"></div>
            <span class="text-stone-300 text-base sm:text-lg">🍷</span>
            <div class="w-8 sm:w-12 h-[1px] bg-[#291010]"></div>
        </div>

        <div class="font-serif italic text-stone-300 text-xs sm:text-sm md:text-base mb-6 md:mb-8">
            <p>Setiap Hari &nbsp;&nbsp;<span class="not-italic text-amber-400 font-sans block sm:inline mt-1 sm:mt-0">15.00 - 00.00 WIB</span></p>
        </div>

        {{-- Form Pengecekan Reservasi --}}
        <form id="formCekReservasi" class="reservation-panel max-w-4xl mx-auto bg-stone-900/60 p-4 sm:p-6 rounded-2xl border border-stone-800 backdrop-blur-sm">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4 md:mb-6">
                
                {{-- Select Tanggal --}}
                <div class="text-left">
                    <label class="block text-[11px] sm:text-xs font-semibold text-stone-300 mb-1.5 uppercase tracking-wider">
                        Pilih Tanggal
                    </label>
                    <input type="date" name="tanggal" required
                        min="{{ \Illuminate\Support\Carbon::now('Asia/Jakarta')->addDay()->toDateString() }}"
                        class="w-full bg-black/50 border border-stone-600 text-white px-3 py-2.5 rounded focus:outline-none focus:border-red-500 text-xs sm:text-sm">
                </div>

                {{-- Select Jam --}}
                <div class="text-left">
                    <label class="block text-[11px] sm:text-xs font-semibold text-stone-300 mb-1.5 uppercase tracking-wider">
                        Jam (Mulai - Selesai)
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="time" name="jam_mulai" required min="15:00" max="23:59"
                            title="Jam mulai: 15.00–23.59"
                            class="w-full bg-black/50 border border-stone-600 text-white px-2 py-2.5 rounded focus:outline-none focus:border-red-500 text-xs text-center">
                        <input type="time" name="jam_selesai" required min="15:00" max="23:59"
                            title="Jam selesai: setelah jam mulai sampai 23.59 WIB"
                            class="w-full bg-black/50 border border-stone-600 text-white px-2 py-2.5 rounded focus:outline-none focus:border-red-500 text-xs text-center">
                    </div>
                </div>

                {{-- Select Jumlah Orang --}}
                <div class="text-left sm:col-span-2 md:col-span-1">
                    <label class="block text-[11px] sm:text-xs font-semibold text-stone-300 mb-1.5 uppercase tracking-wider">Jumlah Orang</label>
                    <input type="number" name="jml_org" min="1" max="88" placeholder="Contoh: 2" required
                           class="w-full bg-black/50 border border-stone-600 text-white px-3 py-2.5 rounded focus:outline-none focus:border-red-500 text-xs sm:text-sm">
                </div>

            </div>
            {{-- <p class="text-left text-xs leading-relaxed text-red-400">
                * Reservasi hanya dapat dibuat minimal H+1.
            </p> --}}
            {{-- Tombol Cek Meja --}}
            <div class="text-center mt-4">
                <button type="submit" id="btnSubmitReservasi"
                        class="w-full md:w-auto px-8 py-3 bg-[#660b0b] hover:bg-gray-700 text-white font-bold text-xs sm:text-sm tracking-widest uppercase rounded shadow-lg transition-all duration-200 border border-red-500">
                    Cek Meja
                </button>
            </div>

            {{-- Tempat Pesan Error --}}
            <div id="errorMessage" class="hidden mt-4 p-3 bg-red-900/80 border border-red-500 text-red-200 text-xs sm:text-sm rounded text-center font-medium animate-pulse">
            </div>
        </form>

    </div>
</section>


{{-- <!-- SECTION KONTAK -->
<section id="kontak" class="py-20 bg-stone-50">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-black mb-2">📍 Kontak</h2>
        <p class="text-stone-500 mb-10">Hubungi kami untuk informasi lebih lanjut</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="https://instagram.com/bigbangeatery" target="_blank" class="bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-6 hover:border-stone-900 hover:-translate-y-1 transition-all block group">
                <div class="w-16 h-16 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-black text-2xl border-2 border-stone-900 mx-auto mb-4 group-hover:bg-[#8B5A2B]">IG</div>
                <h3 class="font-bold text-stone-900">Instagram</h3>
                <p class="text-stone-500 group-hover:text-[#8B5A2B]">@bigbangeatery</p>
            </a>
            <a href="https://wa.me/6281234567890" target="_blank" class="bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-6 hover:border-stone-900 hover:-translate-y-1 transition-all block group">
                <div class="w-16 h-16 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-black text-2xl border-2 border-stone-900 mx-auto mb-4 group-hover:bg-[#8B5A2B]">WA</div>
                <h3 class="font-bold text-stone-900">WhatsApp</h3>
                <p class="text-stone-500 group-hover:text-[#8B5A2B]">+62 812-3456-7890</p>
            </a>
            <a href="https://maps.google.com/?q=Jl.+Contoh+No.+123,+Jakarta" target="_blank" class="bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-6 hover:border-stone-900 hover:-translate-y-1 transition-all block group">
                <div class="w-16 h-16 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-black text-2xl border-2 border-stone-900 mx-auto mb-4 group-hover:bg-[#8B5A2B]">📍</div>
                <h3 class="font-bold text-stone-900">Alamat</h3>
                <p class="text-stone-500 group-hover:text-[#8B5A2B]">Jl. Contoh No. 123, Jakarta</p>
            </a>
        </div>
    </div>
</section> --}}

{{-- JAVASCRIPT KHUSUS LANDING PAGE --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Reveal sections as they enter the viewport, with a no-observer fallback.
        const revealItems = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window) {
            document.documentElement.classList.add('has-reveal');
            const revealObserver = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
            revealItems.forEach(item => revealObserver.observe(item));
        } else {
            revealItems.forEach(item => item.classList.add('is-visible'));
        }
        
        // 1. HEADER TRANSPARAN KE SOLID ON SCROLL (Khusus Landing Page)
        const header = document.querySelector('header');
        if (header) {
            function checkScroll() {
                if (window.scrollY > 40) {
                    header.classList.add('header-scrolled');
                } else {
                    header.classList.remove('header-scrolled');
                }
            }
            checkScroll();
            window.addEventListener('scroll', checkScroll);
        }

        // 2. AUTOMATIC BACKGROUND SLIDESHOW (2 DETIK)
        const heroBgs = document.querySelectorAll('.hero-bg');
        let currentBgIndex = 0;
        if (heroBgs.length > 0) {
            setInterval(() => {
                heroBgs[currentBgIndex].classList.remove('opacity-100');
                heroBgs[currentBgIndex].classList.add('opacity-0');

                currentBgIndex = (currentBgIndex + 1) % heroBgs.length;

                heroBgs[currentBgIndex].classList.remove('opacity-0');
                heroBgs[currentBgIndex].classList.add('opacity-100');
            }, 2000);
        }

        // 3. PENGECEKAN AJAX KETERSEDIAAN MEJA RESERVASI
        const formCek = document.getElementById('formCekReservasi');
        const errorDiv = document.getElementById('errorMessage');
        const btnSubmit = document.getElementById('btnSubmitReservasi');

        formCek.addEventListener('submit', function (e) {
            e.preventDefault();
            
            // Reset state
            errorDiv.classList.add('hidden');
            errorDiv.innerText = '';
            btnSubmit.disabled = true;
            btnSubmit.innerText = 'MEMERIKSA MEJA...';

            const formData = new FormData(formCek);

            fetch("{{ route('reservasi.cek') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Terjadi kesalahan sistem.');
                }
                return data;
            })
            .then(data => {
                if (data.status === 'success') {
                    // Jika lolos pengecekan, arahkan ke halaman form reservasi
                    window.location.href = data.redirect_url;
                }
            })
            .catch(error => {
                // Jika gagal/kebobolan/kapasitas habis, tampilkan pesan merah
                errorDiv.innerText = error.message;
                errorDiv.classList.remove('hidden');
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'BOOK A TABLE';
            });
        });

    });
</script>

@endsection