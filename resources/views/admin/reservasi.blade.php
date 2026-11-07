@extends('layouts.admin')

@section('title', 'Kelola Reservasi - SIPB')

@section('content')
<div
    x-data="reservasiApp()"
    class="relative flex h-[calc(100vh-6rem)] gap-4 xl:gap-6"
>
    <!-- OVERLAY MOBILE -->
    <div
        x-show="sidebarOpen"
        x-cloak
        class="reservation-overlay fixed inset-0 z-40 bg-stone-950/60 xl:hidden backdrop-blur-sm"
        @click="sidebarOpen = false"
        x-transition.opacity
    ></div>

    <!-- LEFTSIDE (Drawer di Mobile, Sidebar Statis di Desktop) -->
    <aside
        :class="sidebarOpen ? 'translate-x-0 shadow-[8px_0_15px_-3px_rgba(0,0,0,0.5)]' : '-translate-x-full xl:translate-x-0 xl:shadow-none'"
        class="reservation-sidebar fixed inset-y-0 left-0 z-50 h-full w-[85vw] max-w-[320px] flex-shrink-0 transition-transform duration-300 ease-in-out xl:static xl:inset-auto xl:z-auto xl:h-full xl:w-80 xl:max-w-none xl:translate-x-0 xl:transform-none xl:shadow-none"
    >
        <div class="flex h-full flex-col overflow-hidden rounded-r-xl border-2 border-stone-300 bg-white shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] xl:rounded-xl">
            <!-- Header Mobile dengan Background Cokelat -->
            <div class="flex items-center justify-between border-b-4 border-stone-900 bg-[#4A2311] px-4 py-3 text-white">
                <span class="flex items-center gap-2 text-lg font-black">📄 Daftar Reservasi</span>
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="reservation-close-button text-3xl font-black leading-none text-white hover:text-red-400 xl:hidden"
                    aria-label="Tutup daftar reservasi"
                >
                    &times;
                </button>
            </div>

            <div class="flex flex-col flex-1 overflow-hidden p-4">
                <form method="GET" action="{{ route('admin.reservasi') }}" class="mb-3 space-y-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('filter.status'))
                        <input type="hidden" name="filter[status]" value="{{ request('filter.status') }}">
                    @endif

                    <label class="block text-xs font-bold text-stone-700">Agenda reservasi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input
                            type="date"
                            name="waktu_mulai"
                            value="{{ $waktuMulai }}"
                            aria-label="Tanggal mulai"
                            class="min-w-0 w-full rounded-lg border-2 border-stone-300 px-2 py-2 text-sm"
                        >
                        <input
                            type="date"
                            name="waktu_selesai"
                            value="{{ $waktuSelesai }}"
                            aria-label="Tanggal sampai"
                            class="min-w-0 w-full rounded-lg border-2 border-stone-300 px-2 py-2 text-sm"
                        >
                    </div>
                    <button type="submit"
                        class="w-full rounded-lg border-2 border-stone-900 bg-[#4A2311] px-3 py-2 text-sm font-bold text-white">
                        Terapkan Waktu
                    </button>
                </form>

                <div class="mb-3">
                    <x-search variant="secondary" action="{{ route('admin.reservasi') }}"
                        placeholder="Cari nama atau nomor telepon..." />
                </div>

                <div class="mb-3">
                    <x-filter variant="secondary" :filters="[
                        ['key' => 'status', 'label' => 'Status', 'options' => [
                            'reserved' => 'Reserved',
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            'pending' => 'Pending',
                        ]],
                    ]" action="{{ route('admin.reservasi') }}" />
                </div>

                <div id="leftside-container" class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <x-leftside
                        :items="$reservasiList"
                        :selectedId="$selectedId"
                        detailRoute="admin.reservasi"
                        type="reservasi" />
                    <div class="pt-3">
                        {{ $reservasiList->links('pagination::tailwind') }}
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
            class="reservation-open-button mb-4 w-full flex-shrink-0 rounded-lg border-2 border-stone-900 bg-[#4A2311] px-4 py-3 font-bold text-white shadow-[3px_3px_0_0_rgba(0,0,0,1)] xl:hidden"
            @click="sidebarOpen = true"
        >
            ☰ Buka Daftar Reservasi
        </button>

        <div class="flex-1 overflow-y-auto rounded-xl border-2 border-stone-300 bg-white p-4 xl:p-6 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
            @if($selectedReservasi)
                @php
                    $currentStatus = strtolower($selectedReservasi->pesanan->status ?? '');
                @endphp

                <div class="flex items-center gap-2 mb-4 pb-4 border-b border-stone-200">
                    @if($currentStatus === 'reserved')
                        <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="px-4 py-2 bg-yellow-500 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-yellow-6 00 active:shadow-none active:translate-y-1 transition-all text-sm flex items-center gap-1">
                                ✔ Konfirmasi
                            </button>
                        </form>
                    @elseif($currentStatus === 'confirmed')
                        @php
                            $totalHarga = $selectedReservasi->pesanan->total_harga ?? 0;
                            $dp = $selectedReservasi->dp_bayar ?? 0; 
                            $sisaTagihan = max(0, $totalHarga - $dp);
                        @endphp

                        @if ($sisaTagihan > 0)
                        <button type="button" 
                                @click="openPelunasanModal({
                                    id: '{{ $selectedReservasi->id }}', 
                                    nama: '{{ addslashes($selectedReservasi->customer->nama ?? 'Guest') }}', 
                                    sisa_tagihan: {{ $sisaTagihan }}
                                })"
                                class="px-4 py-2 bg-blue-500 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-blue-600 active:shadow-none active:translate-y-1 transition-all text-sm flex items-center gap-1">
                            💰 Pelunasan & Selesai
                        </button>
                        @else
                            <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-emerald-700 active:shadow-none active:translate-y-1 transition-all text-sm flex items-center gap-1">
                                    ✔ Selesai (Sudah Lunas)
                                </button>
                            </form>
                        @endif

                        <a target="_blank"
                            href="{{ route('admin.reservasi.nota', ['id' => $selectedReservasi->id, 'jenis' => 'reservation_dp']) }}"
                            class="inline-block rounded-lg border-2 border-stone-900 bg-white px-3 py-2 font-bold shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] text-sm active:translate-y-1 active:shadow-none transition-all">
                                Cetak Nota DP
                        </a>

                        <a target="_blank"
                            href="{{ route('admin.reservasi.nota', ['id' => $selectedReservasi->id, 'jenis' => 'reservation_kitchen']) }}"
                            class="inline-block rounded-lg border-2 border-stone-900 bg-white px-3 py-2 font-bold shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] text-sm active:translate-y-1 active:shadow-none transition-all">
                                Cetak Nota Dapur
                        </a>
                    @else
                        <span class="text-xs px-3 py-1 rounded-full font-bold uppercase border border-stone-300
                            {{ $currentStatus === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-stone-100 text-stone-600' }}">
                            Status Saat Ini: {{ $currentStatus }}
                        </span>

                        <a target="_blank"
                            href="{{ route('admin.reservasi.nota', ['id' => $selectedReservasi->id, 'jenis' => 'reservation_customer']) }}"
                            class="inline-block rounded-lg border-2 border-stone-900 bg-white px-3 py-2 font-bold shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] text-sm active:translate-y-1 active:shadow-none transition-all">
                                Cetak Nota Customer
                        </a>
                    @endif
                </div>

                <x-rightside 
                    :item="$selectedReservasi" 
                    type="reservasi" 
                />
            @else
                <div class="flex h-full items-center justify-center text-stone-400 font-bold text-lg">
                    Pilih reservasi dari daftar
                </div>
            @endif
        </div>
    </main>

    <x-modal-pelunasan />
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reservasiApp', () => ({
            sidebarOpen: false, // Inisialisasi state drawer untuk mobile
            isPelunasanModalOpen: false,
            pelunasanData: {
                id: null,
                nama: '',
                sisa_tagihan: 0,
                metode_pelunasan: '',
                nominal_diterima: ''
            },

            openPelunasanModal(data) {
                this.pelunasanData = {
                    ...data,
                    metode_pelunasan: '',
                    nominal_diterima: ''
                };
                this.isPelunasanModalOpen = true;
            },

            closePelunasanModal() {
                this.isPelunasanModalOpen = false;
                setTimeout(() => {
                    this.pelunasanData = {
                        id: null,
                        nama: '',
                        sisa_tagihan: 0,
                        metode_pelunasan: '',
                        nominal_diterima: ''
                    };
                }, 300);
            },
            formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(angka);
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', () => {
        const leftside = document.getElementById('leftside-container');
        if (!leftside) return;

        const key = 'reservasi_leftside_scroll';
        const saved = sessionStorage.getItem(key);
        const urlParams = new URLSearchParams(window.location.search);
        const active = leftside.querySelector('.bg-\\[\\#4A2311\\]');

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
@endsection