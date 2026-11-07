@php
    $nomorMeja = session('nomor_meja');
@endphp
@extends('layouts.customer')

@section('title', 'Cart - BigBang Eatery')

@section('header_right')
    <span class="text-sm font-bold bg-[#4A2311] text-white px-3 py-2 rounded-lg border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
        Nomor Meja: {{ $nomorMeja ?? '-' }} (Lantai {{ $tables->lantai ?? '-' }})
    </span>
    <a href="{{ route('customer.selforder', ['kode' => session('selforder_code'),'tableNumber' => $tables->nomor_meja]) }}" class="text-2xl font-bold hover:text-[#8B5A2B] transition">✕</a>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6 pb-[180px]">
        {{-- Flash Session Success / Error --}}
        @if(session('success'))
            <x-alert type="success" title="Berhasil" :message="session('success')" />
        @endif

        @if(session('error'))
            <x-alert type="error" title="Gagal" :message="session('error')" />
        @endif

        <h1 class="text-2xl font-black mb-6">🛒 Keranjang Belanja</h1>
        
        @if(count($cartItems) > 0)
            <div class="space-y-4">
                @foreach($cartItems as $key => $item)
                    @php 
                        $menu = $item['menu'];
                        // Ambil catatan dari berbagai kemungkinan penamaan key di session
                        $catatan = $item['catatan'] ?? $item['catatan'] ?? null;
                    @endphp
                    
                    <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)] p-4 flex flex-col md:flex-row items-start md:items-center gap-4">
                        
                        {{-- Bagian Kiri: Gambar, Info Menu, dan Catatan --}}
                        <div class="flex items-center gap-4 w-full md:w-auto flex-1 min-w-0">
                            
                            {{-- Image Preview & Click to Zoom --}}
                            <div class="w-16 h-16 bg-stone-200 rounded-lg border-2 border-stone-300 overflow-hidden flex-shrink-0 cursor-pointer">
                                @if(!empty($menu['gambar']))
                                    <img src="{{ asset('storage/' . $menu['gambar']) }}" 
                                         alt="{{ $menu['nama'] }}" 
                                         class="w-full h-full object-cover hover:scale-105 transition-transform"
                                         onclick="openImageZoom('{{ asset('storage/' . $menu['gambar']) }}', '{{ addslashes($menu['nama']) }}')">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-stone-400 text-2xl">🍽️</div>
                                @endif
                            </div>

                            {{-- Info Menu & Manajamen Catatan --}}
                            <div class="flex-1 min-w-0" x-data="{ openNote: false }">
                                <h3 class="font-bold truncate text-stone-900">{{ $menu['nama'] }}</h3>
                                <p class="text-sm text-stone-500">Rp {{ number_format($menu['harga'], 0, ',', '.') }} / pcs</p>

                                {{-- Jika Catatan Ada --}}
                                @if(!empty($catatan))
                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs text-stone-700 bg-amber-50 p-1.5 rounded-md border border-amber-300">
                                        <span class="flex-shrink-0">📝</span>
                                        <span class="italic font-medium truncate flex-1 text-stone-800">
                                            {{ $catatan }}
                                        </span>
                                        <button type="button" @click="openNote = !openNote" class="text-xs font-bold text-[#4A2311] underline hover:text-stone-900 ml-1">
                                            Edit
                                        </button>
                                    </div>
                                @else
                                    {{-- Jika Catatan Belum Ada --}}
                                    <button type="button" @click="openNote = !openNote" class="mt-1.5 text-xs font-bold text-[#4A2311] hover:underline flex items-center gap-1">
                                        <span>✏️</span> Tambah Catatan
                                    </button>
                                @endif

                                {{-- Form Edit / Tambah Catatan --}}
                                <div x-show="openNote" x-transition class="mt-2">
                                    <form action="{{ route('customer.cart.update') }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <input type="hidden" name="action" value="update_note">
                                        
                                        <input type="text" name="catatan" 
                                               value="{{ $catatan }}" 
                                               placeholder="Contoh: Tanpa pedas, es sedikit..." 
                                               class="w-full text-xs px-2.5 py-1.5 bg-stone-50 rounded border-2 border-stone-900 focus:outline-none focus:ring-1 focus:ring-[#4A2311]">
                                               
                                        <button type="submit" class="px-3 py-1.5 bg-[#4A2311] text-white text-xs font-bold rounded border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:bg-[#8B5A2B] active:translate-x-[1px] active:translate-y-[1px] transition-all whitespace-nowrap">
                                            Simpan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Bagian Kanan: Qty, Subtotal, dan Hapus --}}
                        <div class="flex items-center justify-between w-full md:w-auto gap-4 pt-2 md:pt-0 border-t md:border-t-0 border-stone-200">
                            
                            {{-- Qty Controls --}}
                            <div class="flex items-center gap-2" x-data="{ qty: {{ $item['jumlah'] }} }">
                                <form action="{{ route('customer.cart.update') }}" method="POST" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <input type="hidden" name="action" value="update_qty">
                                    
                                    <button type="submit" name="jumlah" :value="qty - 1" x-show="qty > 1"
                                            class="w-8 h-8 bg-stone-200 hover:bg-stone-300 rounded-lg font-bold border-2 border-stone-400 active:translate-y-0.5 flex items-center justify-center">
                                        −
                                    </button>

                                    <input type="number" readonly :value="qty" class="w-12 text-center border-2 border-stone-400 rounded-lg px-2 py-1 font-bold bg-white">

                                    <button type="submit" name="jumlah" :value="qty + 1"
                                            class="w-8 h-8 bg-stone-200 hover:bg-stone-300 rounded-lg font-bold border-2 border-stone-400 active:translate-y-0.5 flex items-center justify-center">
                                        +
                                    </button>
                                </form>
                            </div>

                            {{-- Subtotal --}}
                            <div class="text-right min-w-[90px]">
                                <p class="font-bold text-[#4A2311]">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                            </div>

                            {{-- Delete Button --}}
                            <div x-data="{ showConfirm: false }" @close-modal.window="showConfirm = false">
                                <button type="button" 
                                        @click="showConfirm = true" 
                                        class="w-9 h-9 bg-red-500 text-white rounded-lg border-2 border-stone-900 flex items-center justify-center font-bold hover:bg-red-600 shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
                                    🗑️
                                </button>

                                <div x-show="showConfirm" style="display: none;">
                                    <x-alert 
                                        type="confirm" 
                                        title="HAPUS PESANAN" 
                                        message="Apakah Anda yakin ingin mengeluarkan item ini dari keranjang?" 
                                        :onConfirm="route('customer.cart.remove', ['key' => $key])" 
                                    />
                                </div>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Checkout Bar (Diatur bertengger tepat di atas Footer) --}}
            <div class="fixed bottom-[56px] left-0 right-0 bg-white border-t-4 border-[#8B5A2B] shadow-[0_-4px_0_0_rgba(0,0,0,0.3)] p-4 z-40">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div>
                        <p class="text-sm text-stone-500">Total Harga</p>
                        <p class="text-2xl font-black text-[#4A2311]">Rp {{ number_format($totalHarga, 0, ',', '.') }}</p>
                    </div>
                    <form action="{{ route('customer.checkout', ['kode' => session('selforder_code'),'tableNumber' => $tables->nomor_meja]) }}" method="GET">
                        <button type="submit" class="px-8 py-3 bg-[#4A2311] text-white font-black text-lg rounded-lg border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[4px] active:translate-y-[4px] transition-all">
                            Selanjutnya →
                        </button>
                    </form>
                </div>
            </div>

        @else
            <div class="text-center py-20">
                <div class="text-6xl mb-4">🛒</div>
                <p class="text-stone-500 font-bold text-lg">Keranjang masih kosong</p>
                <a href="{{ route('customer.selforder', ['kode' => session('selforder_code'), 'tableNumber' => $tables->nomor_meja]) }}" class="inline-block mt-4 px-6 py-3 bg-[#4A2311] text-white font-bold rounded-lg border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] transition">
                    Mulai Pesan
                </a>
            </div>
        @endif
    </div>
@endsection

<script>
// Image Zoom Modal
function openImageZoom(imageSrc, imageName) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/75 flex items-center justify-center z-50 p-4 transition-opacity';
    modal.id = 'zoom-modal';
    
    // Tutup saat area luar diklik
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };

    modal.innerHTML = `
        <div class="bg-white rounded-xl border-4 border-stone-900 max-w-2xl w-full overflow-hidden shadow-2xl animate-in zoom-in-95 duration-150">
            <div class="p-4 flex justify-between items-center border-b-2 border-stone-300 bg-stone-50">
                <h3 class="font-bold text-stone-900 text-lg">${imageName}</h3>
                <button onclick="document.getElementById('zoom-modal').remove()" class="w-8 h-8 rounded-lg bg-stone-200 hover:bg-red-500 hover:text-white font-bold text-xl flex items-center justify-center transition">
                    ✕
                </button>
            </div>
            <div class="p-4 bg-stone-100 flex items-center justify-center max-h-[75vh]">
                <img src="${imageSrc}" alt="${imageName}" class="max-w-full max-h-[70vh] object-contain rounded-lg border-2 border-stone-300 shadow-md">
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}
</script>