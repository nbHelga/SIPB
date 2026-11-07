@extends('layouts.admin')

@section('title', 'Kelola Self Order - SIPB')

@section('content')
<div class="flex gap-6 h-[calc(100vh-6rem)]">
    <!-- LEFT SIDEBAR -->
    <div class="w-80 flex-shrink-0 bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-4 flex flex-col overflow-hidden">
        <div class="mb-3">
            <x-search variant="secondary" action="{{ route('admin.selforder') }}" placeholder="Cari pesanan..." />
        </div>
        
        {{-- FILTER STATUS SELF ORDER --}}
        <div class="mb-3">
            <x-filter variant="secondary" :filters="[
                ['key' => 'status', 'label' => 'Status', 'options' => [
                    'pending' => 'Pending (Tunai)',
                    'paid' => 'Paid',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]],
            ]" action="{{ route('admin.selforder') }}" />
        </div>

        {{-- LIST PESANAN (Tambahkan ID unik id="leftside-container-selforder") --}}
        <div id="leftside-container-selforder" class="flex-1 overflow-y-auto space-y-2 pr-1">
            <x-leftside 
                :items="$pesananList" 
                :selectedId="$selectedId"
                detailRoute="admin.selforder"
                type="pesanan" />
        </div>
    </div>

    <!-- RIGHT DETAIL -->
    <div class="flex-1 bg-white rounded-xl border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-6 overflow-y-auto">
        @if($selectedPesanan)
            @php
                $currentStatus = strtolower($selectedPesanan->status ?? '');
            @endphp

            {{-- TOMBOL UPDATE STATUS BERDASARKAN ALUR SELF ORDER --}}
            <div class="flex items-center gap-2 mb-4 pb-4 border-b border-stone-200">
                @if($currentStatus === 'pending' && strtolower($selectedPesanan->metode_pembayaran ?? '') === 'tunai')
                    <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="paid">
                        <button type="submit"
                                class="rounded-lg border-2 border-stone-900 bg-green-500 px-4 py-2 font-bold text-white">
                            Tandai Sudah Dibayar
                        </button>
                    </form>
                @elseif($currentStatus === 'paid')
                    {{-- Customer Sudah Bayar -> Kirim ke Dapur --}}
                    <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="Processing">
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-stone-900 font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-yellow-400 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ⏳ Proses
                        </button>
                    </form>
                    <a target="_blank"
                        href="{{ route('admin.selforder.nota', ['id' => $selectedPesanan->id, 'jenis' => 'self_customer']) }}"
                        class="rounded-lg border-2 border-stone-900 bg-white px-3 py-2 font-bold">
                            Cetak Nota Customer
                    </a>
                    {{-- <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="Cancelled">
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-red-600 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ✕ Batal
                        </button>
                    </form> --}}

                @elseif($currentStatus === 'processing')
                    {{-- Dapur Selesai / Pesanan Diantar -> Selesai --}}
                    <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="Completed">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-green-700 active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all text-sm flex items-center gap-1">
                            ✔ Selesai
                        </button>
                    </form>
                    <a target="_blank"
                        href="{{ route('admin.selforder.nota', ['id' => $selectedPesanan->id, 'jenis' => 'self_kitchen']) }}"
                        class="rounded-lg border-2 border-stone-900 bg-amber-200 px-3 py-2 font-bold">
                            Cetak Nota Dapur
                    </a>
                    {{-- <form action="{{ route('admin.order.update', $selectedPesanan->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="Cancelled">
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
                :item="$selectedPesanan" 
                type="pesanan" 
            />
        @else
            <div class="flex items-center justify-center h-full text-stone-400 font-bold text-lg">
                Pilih pesanan dari daftar di samping
            </div>
        @endif
    </div>
</div>

{{-- SCRIPT MENJAGA POSISI SCROLL SELF ORDER --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const leftside = document.getElementById('leftside-container-selforder');
        if (!leftside) return;

        // Key unik khusus Self Order agar tidak bentrok dengan Reservasi
        const STORAGE_KEY = 'selforder_leftside_scroll';

        // 1. Restore posisi scroll dari session
        const savedScroll = sessionStorage.getItem(STORAGE_KEY);
        if (savedScroll !== null) {
            leftside.scrollTop = parseInt(savedScroll, 10);
        }

        // 2. Simpan posisi scroll setiap elemen diklik atau di-scroll
        leftside.addEventListener('scroll', () => {
            sessionStorage.setItem(STORAGE_KEY, leftside.scrollTop);
        });

        // 3. Fallback: Auto-scroll ke card aktif/ter-highlight jika scroll belum tersimpan
        const activeCard = leftside.querySelector('a.bg-\\[\\#4A2311\\]');
        if (activeCard && savedScroll === null) {
            activeCard.scrollIntoView({ block: 'nearest', behavior: 'instant' });
        }
    });
</script>
@endsection