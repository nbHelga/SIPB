@extends('layouts.admin')

@section('title', 'Kelola Reservasi - SIPB')

@section('content')
<!-- BUNGKUS DENGAN ALPINE DATA UNTUK STATE MODAL -->
<div x-data="reservasiApp()" class="flex gap-6 h-[calc(100vh-6rem)] relative">
    
    <!-- LEFT SIDEBAR -->
    <div class="w-80 flex-shrink-0 bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-4 flex flex-col overflow-hidden">
        <div class="mb-3">
            <x-search variant="secondary" action="{{ route('admin.reservasi') }}" placeholder="Cari reservasi..." />
        </div>
        
        {{-- FILTER --}}
        <div class="mb-3">
            <x-filter variant="secondary" :filters="[
                ['key' => 'status', 'label' => 'Status', 'options' => [
                    'reserved' => 'Reserved',
                    'confirmed' => 'Confirmed',
                    // 'processing' => 'Processing',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]],
            ]" action="{{ route('admin.reservasi') }}" />
        </div>

        {{-- LIST RESERVASI --}}
        <div id="leftside-container" class="flex-1 overflow-y-auto space-y-2 pr-1">
            <x-leftside 
                :items="$reservasiList" 
                :selectedId="$selectedId"
                detailRoute="admin.reservasi"
                type="reservasi" />
        </div>
    </div>

    <!-- RIGHT DETAIL -->
    <div class="flex-1 bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-6 overflow-y-auto">
        @if($selectedReservasi)
            @php
                // Mengambil status dari relasi pesanan dan konversi ke lowercase
                $currentStatus = strtolower($selectedReservasi->pesanan->status ?? '');
            @endphp

            {{-- TOMBOL UPDATE STATUS BERDASARKAN KRONOLOGI ALUR RESERVASI --}}
            <div class="flex items-center gap-2 mb-4 pb-4 border-b border-stone-200">
                @if($currentStatus === 'reserved')
                    {{-- Reservasi Masuk -> Admin Verifikasi & Assign Meja --}}
                    <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-yellow-700 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ✔ Konfirmasi
                        </button>
                    </form>
                    <!-- Tombol Batal... -->

                @elseif($currentStatus === 'confirmed')
                    {{-- Meja Ter-assign -> Masuk Dapur / Hari H --}}
                    {{-- <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-stone-900 font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-yellow-400 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ⏳ Proses
                        </button>
                    </form>
                    <!-- Tombol Batal... -->

                @elseif($currentStatus === 'processing') --}}
                    {{-- Dapur Selesai / Tamu Sudah Makan -> Selesai & Pelunasan --}}
                    
                    @php
                        // Logika kalkulasi sisa tagihan: Total Harga - DP (Jika ada)
                        // Sesuaikan field ini dengan struktur tabel database Anda
                        $totalHarga = $selectedReservasi->pesanan->total_harga ?? 0;
                        $dp = $selectedReservasi->dp_bayar ?? 0; 
                        $sisaTagihan = max(0, $totalHarga - $dp);
                    @endphp

                    <!-- TOMBOL TRIGGER MODAL PELUNASAN -->
                    @if ($sisaTagihan > 0)
                    <button type="button" 
                            @click="openPelunasanModal({
                                id: '{{ $selectedReservasi->id }}', 
                                nama: '{{ addslashes($selectedReservasi->customer->nama ?? 'Guest') }}', 
                                sisa_tagihan: {{ $sisaTagihan }}
                            })"
                            class="px-4 py-2 bg-blue-500 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-blue-600 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                        💰 Pelunasan & Selesai
                    </button>
                    @else
                        <!-- JIKA SUDAH LUNAS (DP = Total Harga): Langsung Selesai Tanpa Modal -->
                        <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-emerald-700 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                                ✔ Selesai (Sudah Lunas)
                            </button>
                        </form>
                    @endif

                    {{-- <form action="{{ route('admin.reservasi.update', $selectedReservasi->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-red-600 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ✕ Batal
                        </button>
                    </form> --}}

                @else
                    <span class="text-xs px-3 py-1 rounded-full font-bold uppercase border border-stone-300
                        {{ $currentStatus === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-stone-100 text-stone-600' }}">
                        Status Saat Ini: {{ $currentStatus }}
                    </span>
                @endif
            </div>

            <x-rightside 
                :item="$selectedReservasi" 
                type="reservasi" 
            />
        @else
            <div class="flex items-center justify-center h-full text-stone-400 font-bold text-lg">
                Pilih reservasi dari daftar di samping
            </div>
        @endif
    </div>

    <!-- PANGGIL KOMPONEN MODAL DI DALAM WRAPPER x-data -->
    <x-modal-pelunasan />
    
</div>

{{-- SCRIPT ALPINE.JS & SCROLL --}}
<script>
    // Inisialisasi Alpine untuk Modal
    document.addEventListener('alpine:init', () => {
        Alpine.data('reservasiApp', () => ({
            isPelunasanModalOpen: false,
            pelunasanData: {
                id: null,
                nama: '',
                sisa_tagihan: 0
            },
            
            openPelunasanModal(data) {
                this.pelunasanData = data;
                this.isPelunasanModalOpen = true;
            },
            
            closePelunasanModal() {
                this.isPelunasanModalOpen = false;
                setTimeout(() => {
                    this.pelunasanData = { id: null, nama: '', sisa_tagihan: 0 };
                }, 300); // Tunggu transisi selesai
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

    // Script Scroll bawaan Anda tetap jalan di bawahnya
    document.addEventListener('DOMContentLoaded', () => {
        const leftside = document.getElementById('leftside-container');
        if (!leftside) return;

        const savedScroll = sessionStorage.getItem('reservasi_leftside_scroll');
        if (savedScroll !== null) {
            leftside.scrollTop = parseInt(savedScroll, 10);
        }

        leftside.addEventListener('scroll', () => {
            sessionStorage.setItem('reservasi_leftside_scroll', leftside.scrollTop);
        });

        const activeCard = leftside.querySelector('.bg-\\[\\#4A2311\\]');
        if (activeCard && savedScroll === null) {
            activeCard.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    });
</script>
@endsection