@extends('layouts.admin')

@section('title', 'Kelola Self Order - SIPB')

@section('content')
<div x-data="selfOrderApp()" class="relative flex h-[calc(100vh-6rem)] gap-4 xl:gap-6">
    
    <!-- OVERLAY MOBILE -->
    <div
        x-show="sidebarOpen"
        x-cloak
        class="selforder-overlay fixed inset-0 z-40 bg-stone-950/60 backdrop-blur-sm xl:hidden"
        @click="sidebarOpen = false"
        x-transition.opacity
    ></div>

    <!-- LEFTSIDE (Drawer di Mobile, Sidebar Statis di Desktop) -->
    <aside
        :class="sidebarOpen
            ? 'translate-x-0 shadow-[8px_0_15px_-3px_rgba(0,0,0,0.5)]'
            : '-translate-x-full xl:translate-x-0 xl:shadow-none'"
        class="
            selforder-sidebar fixed inset-y-0 left-0 z-50
            h-full w-[85vw] max-w-[320px] flex-shrink-0
            transition-transform duration-300 ease-in-out
            xl:static xl:inset-auto xl:z-auto
            xl:h-full xl:w-80 xl:min-w-80 xl:max-w-none
            xl:basis-80 xl:flex-none
            xl:translate-x-0 xl:transform-none xl:shadow-none
        "
    >
        <div class="flex h-full flex-col overflow-hidden rounded-r-xl border-2 border-stone-300 bg-white shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] xl:rounded-xl">
            <div class="flex items-center justify-between border-b-4 border-stone-900 bg-[#4A2311] px-4 py-3 text-white">
                <span class="flex items-center gap-2 text-lg font-black">
                    📄 Daftar Pesanan
                </span>

                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="selforder-close-button"
                    aria-label="Tutup daftar pesanan"
                >
                    &times;
                </button>
            </div>

            <!-- Kontainer Isi Leftside -->
            <div class="flex flex-col flex-1 overflow-hidden p-4">
                <div class="mb-3">
                    <x-search variant="secondary" action="{{ route('admin.selforder') }}" placeholder="Cari meja, nomor pesanan..." />
                </div>

                <form id="selforder-date-filter"
                    method="GET"
                    action="{{ route('admin.selforder') }}"
                    class="mb-3 flex items-end gap-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif

                    @if(request('filter.status'))
                        <input type="hidden" name="filter[status]" value="{{ request('filter.status') }}">
                    @endif

                    <div class="min-w-0 flex-1">
                        <label for="selforder-tanggal" class="mb-1 block text-sm font-bold text-stone-700">
                            Tanggal pesanan
                        </label>
                        <input
                            id="selforder-tanggal"
                            type="date"
                            name="tanggal"
                            value="{{ $filterTanggal ?? request('tanggal', now()->toDateString()) }}"
                            class="w-full rounded-lg border-2 border-stone-300 px-2 py-2 text-sm"
                        >
                    </div>

                    <button
                        type="submit"
                        class="shrink-0 rounded-lg border-2 border-stone-900 bg-[#4A2311] px-3 py-2 text-sm font-bold text-white hover:bg-[#65351d]"
                    >
                        Terapkan
                    </button>
                </form>

                <div class="mb-3">
                    <x-filter variant="secondary" :filters="[
                        ['key' => 'status', 'label' => 'Status', 'options' => [
                            'pending' => 'Menunggu Pembayaran',
                            'paid' => 'Paid',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ]],
                    ]" action="{{ route('admin.selforder') }}" />
                </div>

                <div id="leftside-container-selforder" class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <x-leftside
                        :items="$pesananList"
                        :selectedId="$selectedId"
                        detailRoute="admin.selforder"
                        type="pesanan" />
                    <div class="pt-3">
                        {{ $pesananList->links('pagination::tailwind') }}
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- RIGHT DETAIL -->
    <main class="flex-1 min-w-0 flex flex-col h-full">
        <!-- Tombol Buka Drawer Mobile -->
        <button
            type="button"
            class="selforder-open-button mb-4 w-full flex-shrink-0 rounded-lg border-2 border-stone-900
                bg-[#4A2311] px-4 py-3 font-bold text-white xl:hidden"
            @click="sidebarOpen = true"
        >
            ☰ Buka Daftar Pesanan
        </button>

        <!-- Kotak Putih Konten Kanan -->
        <div class="flex-1 overflow-y-auto rounded-xl border-2 border-stone-300 bg-white p-4 xl:p-6 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
            @if($selectedPesanan)
                @php
                    $currentStatus = strtolower(trim((string) $selectedPesanan->status));

                    $pembayaranFull = $selectedPesanan->pembayaran->first(
                        fn ($p) => strtolower(trim((string) $p->jenis)) === 'full'
                    );

                    $metodePembayaran = strtolower(trim((string) ($pembayaranFull?->metode ?? '')));
                    $statusPembayaran = strtolower(trim((string) ($pembayaranFull?->status ?? '')));
                @endphp

                <!-- Z-index 20 untuk area Tombol Aksi agar 100% bebas dari overlap/hambatan klik -->
                <div class="flex items-center gap-2 mb-4 pb-4 border-b border-stone-200 flex-wrap relative z-20">
                    @if(
                        $currentStatus === 'pending'
                        && $metodePembayaran === 'tunai'
                        && $statusPembayaran === 'pending'
                    )
                        <div
                            x-data="{
                                bukaModal: false,
                                total: @js((int) $selectedPesanan->total_harga),
                                uang: '',
                                get kembalian() {
                                    return Math.max(0, (Number(this.uang) || 0) - this.total);
                                }
                            }"
                            @keydown.escape.window="bukaModal = false"
                        >
                            <button
                                type="button"
                                @click="bukaModal = true"
                                class="rounded-lg border-2 border-stone-900 bg-green-500 px-4 py-2 text-sm font-bold text-white shadow hover:bg-green-600"
                            >
                                💵 Terima Tunai
                            </button>

                            <div
                                x-show="bukaModal"
                                x-cloak
                                x-transition.opacity
                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
                                @click.self="bukaModal = false"
                            >
                                <section class="w-full max-w-sm rounded-xl border-2 border-stone-900 bg-white p-5 shadow-xl">
                                    <div class="mb-4 flex items-center justify-between border-b pb-3">
                                        <h2 class="text-lg font-black">Pembayaran Tunai</h2>
                                        <button type="button" @click="bukaModal = false" class="text-xl font-bold" aria-label="Tutup">
                                            &times;
                                        </button>
                                    </div>

                                    <p class="mb-1 text-sm text-stone-500">Total tagihan</p>
                                    <p class="mb-4 text-xl font-black">
                                        Rp {{ number_format($selectedPesanan->total_harga, 0, ',', '.') }}
                                    </p>

                                    <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="Paid">

                                        <label for="uang_diterima" class="mb-1 block text-sm font-bold">
                                            Uang diterima
                                        </label>
                                        <input
                                            id="uang_diterima"
                                            name="uang_diterima"
                                            type="number"
                                            min="{{ (int) $selectedPesanan->total_harga }}"
                                            step="1"
                                            x-model.number="uang"
                                            required
                                            class="mb-3 w-full rounded-lg border-2 border-stone-300 p-2 focus:border-stone-900 focus:outline-none"
                                            placeholder="Masukkan nominal"
                                        >

                                        <div class="mb-5 rounded-lg bg-stone-100 p-3">
                                            <p class="text-sm text-stone-500">Kembalian</p>
                                            <p class="font-black text-green-700">
                                                Rp <span x-text="kembalian.toLocaleString('id-ID')"></span>
                                            </p>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="bukaModal = false"
                                                class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-bold hover:bg-stone-100"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="submit"
                                                :disabled="Number(uang) < total"
                                                class="rounded-lg bg-[#4A2311] px-3 py-2 text-sm font-bold text-white hover:bg-[#65351d] disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Konfirmasi
                                            </button>
                                        </div>
                                    </form>
                                </section>
                            </div>
                        </div>
                    @elseif($currentStatus === 'paid')
                        <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline-block">
                            @csrf
                            <input type="hidden" name="status" value="Processing">
                            <button type="submit" class="px-4 py-2 bg-yellow-500 text-stone-900 font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-yellow-400 active:shadow-none active:translate-y-1 transition-all text-sm flex items-center gap-1">
                                ⏳ Proses
                            </button>
                        </form>
                    @elseif($currentStatus === 'processing')
                        <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline-block">
                            @csrf
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-green-700 active:shadow-none active:translate-y-1 transition-all text-sm flex items-center gap-1">
                                ✔ Selesai
                            </button>
                        </form>
                        {{-- jgn diubah krn utk menangani selforder midtrans yg lgsg paid --}}
                        <a target="_blank"
                            href="{{ route('admin.selforder.nota', ['id' => $selectedPesanan->id, 'jenis' => 'self_customer']) }}"
                            class="inline-block rounded-lg border-2 border-stone-900 bg-white px-3 py-2 font-bold shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] text-sm active:translate-y-1 active:shadow-none transition-all">
                                Cetak Nota Customer
                        </a>
                        <a target="_blank"
                            href="{{ route('admin.selforder.nota', ['id' => $selectedPesanan->id, 'jenis' => 'self_kitchen']) }}"
                            class="inline-block rounded-lg border-2 border-stone-900 bg-amber-200 px-3 py-2 font-bold shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] text-sm active:translate-y-1 active:shadow-none transition-all">
                                Cetak Nota Dapur
                        </a>
                    @else
                        <span class="text-xs px-3 py-1 rounded-full font-bold uppercase border border-stone-300
                            {{ $currentStatus === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-stone-100 text-stone-600' }}">
                            Status Saat Ini: {{ $currentStatus }}
                        </span>
                    @endif
                </div>

                <x-rightside 
                    :item="$selectedPesanan" 
                    type="pesanan" 
                />
            @else
                <div class="flex h-full items-center justify-center text-stone-400 font-bold text-lg">
                    Pilih pesanan dari daftar
                </div>
            @endif
        </div>
    </main>
</div>

{{-- SCRIPT MENJAGA POSISI SCROLL & STATE DRAWER --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('selfOrderApp', () => {
            const params = new URLSearchParams(window.location.search);
            // Cek apakah ada aksi pencarian/filter DAN belum memilih id pesanan
            const hasFilter = params.has('search') || Array.from(params.keys()).some(k => k.includes('filter'));
            const hasId = params.has('id');

            return {
                sidebarOpen: hasFilter && !hasId,
            };
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const leftside = document.getElementById('leftside-container-selforder');
        if (!leftside) return;

        const key = 'selforder_leftside_scroll';
        const saved = sessionStorage.getItem(key);
        const urlParams = new URLSearchParams(window.location.search);
        const active = leftside.querySelector('a.bg-\\[\\#4A2311\\]');

        if (urlParams.has('id') && active) {
            setTimeout(() => {
                active.scrollIntoView({ block: 'center', behavior: 'instant' });
            }, 50);
        } else if (saved !== null) {
            leftside.scrollTop = parseInt(saved, 10);
        }

        leftside.addEventListener('scroll', () => {
            sessionStorage.setItem(key, leftside.scrollTop);
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.selforder-sidebar');
        const dateInput = document.getElementById('selforder-tanggal');

        if (!sidebar || !dateInput) return;

        sidebar.querySelectorAll('form').forEach((form) => {
            if (form.id === 'selforder-date-filter') return;

            form.addEventListener('submit', () => {
                form.querySelectorAll('input[data-selforder-date]').forEach((input) => {
                    input.remove();
                });

                const date = document.createElement('input');
                date.type = 'hidden';
                date.name = 'tanggal';
                date.value = dateInput.value;
                date.dataset.selforderDate = 'true';
                form.appendChild(date);
            });
        });
    });
</script>
{{-- 
<style>
    @media (min-width: 1280px) {
        .selforder-sidebar {
            position: static !important;
            inset: auto !important;
            z-index: auto !important;
            display: block !important;
            width: 20rem !important;
            max-width: none !important;
            height: 100% !important;
            transform: none !important;
            translate: none !important;
            visibility: visible !important;
        }

        .selforder-open-button,
        .selforder-overlay {
            display: none !important;
        }
    }
</style> --}}
@endsection