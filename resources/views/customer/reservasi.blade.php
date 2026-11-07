@extends('layouts.landing')

@section('title', 'Reservasi - BigBang Eatery')

@section('content')
<style>
    #reservasiForm input[type="date"]::-webkit-calendar-picker-indicator,
    #reservasiForm input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
    }
</style>

<div class="pt-24 pb-10">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-black text-center mb-2">📅 Reservasi</h1>
        <p class="text-stone-500 text-center mb-8">Isi formulir di bawah untuk melakukan reservasi meja</p>

        <div class="bg-white rounded-xl border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,0.3)] p-6 md:p-8">
            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border-2 border-red-400 text-red-700 font-bold">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border-2 border-red-400 text-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $err)
                            <li class="font-medium">{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form id="reservasiForm" action="{{ route('customer.reservasi.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Nama Customer -->
                    <div>
                        <label class="block font-bold mb-1">Nama Pemesan <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama', $prefill['nama'] ?? '') }}" required
                               class="w-full border-2 @error('nama') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('nama')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- No WA -->
                    <div>
                        <label class="block font-bold mb-1">Nomor Telepon (WhatsApp Kalau Ada) <span class="text-red-500">*</span></label>
                        <input type="text" name="nomor_telp" value="{{ old('nomor_telp', $prefill['nomor_telp'] ?? '') }}" required placeholder="08xxxxxxxxxx"
                               class="w-full border-2 @error('nomor_telp') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('nomor_telp')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal -->
                    <div>
                        <label class="block font-bold mb-1">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" id="reservasiTanggal" required
                               value="{{ old('tanggal', $prefill['tanggal'] ?? '') }}"
                               min="{{ \Illuminate\Support\Carbon::now('Asia/Jakarta')->addDay()->toDateString() }}"
                               class="w-full border-2 @error('tanggal') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('tanggal')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @else
                            <p class="text-xs text-stone-500 mt-1">Pilih tanggal yang tersedia</p>
                        @enderror
                    </div>

                    <!-- Jam Mulai -->
                    <div>
                        <label class="block font-bold mb-1">Jam Mulai <span class="text-red-500">*</span></label>
                        <input type="time" name="jam_mulai" min="15:00" max="23:59" required
                               value="{{ old('jam_mulai', $prefill['jam_mulai'] ?? '') }}"
                               class="w-full border-2 @error('jam_mulai') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('jam_mulai')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jam Selesai -->
                    <div>
                        <label class="block font-bold mb-1">Jam Selesai <span class="text-red-500">*</span></label>
                        <input type="time" name="jam_selesai" min="15:00" max="23:59" required
                               value="{{ old('jam_selesai', $prefill['jam_selesai'] ?? '') }}"
                               class="w-full border-2 @error('jam_selesai') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('jam_selesai')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Orang -->
                    <div>
                        <label class="block font-bold mb-1">Jumlah Orang <span class="text-red-500">*</span></label>
                        <input type="number" name="jml_org" required min="1" 
                               value="{{ old('jml_org', $prefill['jml_org'] ?? '') }}"
                               class="w-full border-2 @error('jml_org') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">
                        @error('jml_org')
                            <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Keterangan -->
                <div>
                    <label class="block font-bold mb-1">Keterangan</label>
                    <textarea name="keterangan" rows="3" placeholder="Misal: meja dekat jendela, acara ulang tahun, dll..."
                              class="w-full border-2 @error('keterangan') border-red-500 bg-red-50 @else border-stone-300 @enderror rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none">{{ old('keterangan', $prefill['keterangan'] ?? '') }}</textarea>
                    @error('keterangan')
                        <p class="text-red-500 text-xs mt-1 font-bold">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <!-- Pilih Menu (Popup) -->
                <div>
                    <label class="block font-bold mb-3">Pilih Menu (Wajib Minimal Rp 50.000)</label>
                    <button type="button" onclick="toggleModal('multiMenuModal', true)"
                            class="px-4 py-2 bg-amber-100 text-stone-700 font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-amber-200 transition">
                        + Tambah Menu
                    </button>
                    
                    @error('menu')
                        <p class="text-red-500 text-xs mt-2 font-bold">⚠️ {{ $message }}</p>
                    @enderror
                    
                    <div id="selectedMenusSummary" class="mt-4 p-4 bg-stone-50 rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hidden">
                        <div class="flex items-center justify-between pb-3 border-b-2 border-stone-200 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📋</span>
                                <h4 class="font-black text-sm text-stone-900 uppercase tracking-wide">Ringkasan Pesanan Menu</h4>
                            </div>
                        </div>
                        
                        <div id="summaryList" class="space-y-2 max-h-48 overflow-y-auto pr-1"></div>

                        <div class="mt-3 pt-3 border-t-2 border-stone-200 flex justify-between items-center text-sm">
                            <span class="text-xs font-bold text-stone-500 uppercase">Total Menu</span>
                            <div class="text-right">
                                <span id="summaryTotalItems" class="font-bold text-stone-700">0 Item</span>
                                <span class="text-stone-300 mx-1">|</span>
                                <span id="summaryTotalPrice" class="font-black text-[#4A2311] text-base">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol -->
                <div class="flex gap-3 pt-3">
                    <button id="reservasiSubmitBtn" type="submit"
                            class="flex-1 py-4 bg-[#4A2311] text-white font-black text-lg rounded-lg border-2 border-stone-900 shadow-[5px_5px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[5px] active:translate-y-[5px] transition-all">
                        🔖 Lanjutkan Review
                    </button>
                    <a href="{{ url('/') }}"
                    class="px-6 py-4 bg-stone-200 text-stone-700 font-bold text-lg rounded-lg border-2 border-stone-900 shadow-[5px_5px_0_0_rgba(0,0,0,0.3)] hover:bg-stone-300 text-center flex items-center justify-center">
                        Batal
                    </a>
                </div>

                <!-- KOMPONEN POPUP MENU -->
                <x-menu-popup 
                    :menus="$menus" 
                    :isEdit="false" 
                />

            </form>
        </div>
    </div>
</div>

<script>
function updateSelectedMenuSummary(selectedItems) {
    const summaryContainer = document.getElementById('selectedMenusSummary');
    const summaryList = document.getElementById('summaryList');
    const totalItemsEl = document.getElementById('summaryTotalItems');
    const totalPriceEl = document.getElementById('summaryTotalPrice');

    if (!selectedItems || selectedItems.length === 0) {
        summaryContainer.classList.add('hidden');
        summaryList.innerHTML = '';
        return;
    }

    let totalQty = 0;
    let totalPrice = 0;
    let listHTML = '';

    selectedItems.forEach(item => {
        const itemTotal = item.harga * item.qty;
        totalQty += item.qty;
        totalPrice += itemTotal;

        listHTML += `
            <div class="flex items-center justify-between text-sm bg-white p-2.5 rounded-lg border border-stone-200">
                <div class="truncate pr-2">
                    <span class="font-bold text-stone-800 block truncate">${item.nama}</span>
                    ${item.notes ? `<p class="text-xs text-stone-500 italic">"${item.notes}"</p>` : ''}
                </div>
                <div class="text-right whitespace-nowrap">
                    <span class="font-black text-[#4A2311] bg-amber-100 px-2 py-0.5 rounded text-xs">${item.qty}x</span>
                    <span class="text-xs font-bold text-stone-600 ml-1">Rp ${itemTotal.toLocaleString('id-ID')}</span>
                </div>
                <!-- TAMBAHAN: Hidden input untuk mengirim harga yang dilihat pelanggan -->
                <input type="hidden" name="menu_price[${item.id}]" value="${item.harga}">
            </div>
        `;
    });

    summaryList.innerHTML = listHTML;
    totalItemsEl.textContent = `${totalQty} Porsi`;
    totalPriceEl.textContent = `Rp ${totalPrice.toLocaleString('id-ID')}`;
    
    summaryContainer.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservasiForm');
    const submitBtn = document.getElementById('reservasiSubmitBtn');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        try {
            const modal = document.getElementById('multiMenuModal');
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        } catch (err) {
            console.warn('[reservasiForm] error closing modal', err);
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerText = 'Memproses...';
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }
    });
});
</script>

@include('partials.floating_cs_wa')
@endsection