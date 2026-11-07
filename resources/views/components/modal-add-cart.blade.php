{{-- resources/views/components/modal-add-cart.blade.php --}}
@props(['nomorMeja' => ''])

<div x-show="showModal" 
     style="display: none;" 
     class="fixed inset-0 bg-black/75 z-[100] p-2 sm:p-4 flex items-center justify-center transition-all">
    
    <div @click.outside="showModal = false" class="bg-white rounded-xl border-3 md:border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,0.3)] overflow-hidden w-full max-w-[92%] sm:max-w-lg md:max-w-4xl max-h-[95vh] flex flex-col md:flex-row relative">
        
        {{-- Tombol Close --}}
        <button @click="showModal = false" type="button" class="absolute top-2.5 right-2.5 z-10 w-8 h-8 md:w-9 md:h-9 bg-red-500 text-white rounded-full border-2 border-stone-900 font-bold hover:bg-red-600 shadow-[2px_2px_0_0_rgba(0,0,0,1)] flex items-center justify-center transition-colors">
            ✕
        </button>

        {{-- Area Gambar --}}
        <div class="md:w-1/2 bg-stone-200 h-52 sm:h-60 md:h-auto flex items-center justify-center relative overflow-hidden border-b-2 md:border-b-0 md:border-r-4 border-stone-900">
            <template x-if="selectedMenu.gambar">
                <img :src="selectedMenu.gambar" :alt="selectedMenu.nama" class="w-full h-full object-cover">
            </template>
            <template x-if="!selectedMenu.gambar">
                <div class="text-5xl md:text-7xl text-stone-400">🍽️</div>
            </template>
            <button type="button" x-show="selectedMenu.gambar" @click.stop="showImageZoom = true"
                class="absolute bottom-3 right-3 flex h-10 w-10 items-center justify-center rounded-full border-2 border-stone-900 bg-white/95 text-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] transition hover:bg-amber-300"
                aria-label="Tampilkan gambar menu ukuran penuh" title="Perbesar gambar">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M3 16v3a2 2 0 0 0 2 2h3m13-5v3a2 2 0 0 1-2 2h-3"/>
                </svg>
            </button>
        </div>

        {{-- Area Form Detail --}}
        <div class="md:w-1/2 p-3.5 md:p-5 flex flex-col justify-between overflow-y-auto">
            <div>
                <h1 class="text-lg md:text-2xl font-black text-stone-900 mb-0.5 leading-tight pr-6" x-text="selectedMenu.nama"></h1>
                <p class="text-[#8B5A2B] text-lg md:text-xl font-black mb-2" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedMenu.harga)"></p>
                
                {{-- deskripsi Menu dengan Logika Auto-Scroll --}}
                {{-- max-h-16 akan membatasi tinggi (sekitar 3-4 baris/50 kata). Jika lebih, scrollbar muncul --}}
                <template x-if="selectedMenu.deskripsi">
                    <div class="mb-3 text-xs text-stone-600 border-l-2 border-stone-300 pl-2 pr-1 italic max-h-16 overflow-y-auto scrollbar-thin" 
                         x-text="selectedMenu.deskripsi"></div>
                </template>
            </div>

            <form action="{{ route('customer.cart.add') }}" method="POST" class="space-y-2.5">
                @csrf
                <input type="hidden" name="id_menu" :value="selectedMenu.id">
                <input type="hidden" name="redirect" value="{{ route('customer.selforder', ['nomor_meja' => $nomorMeja]) }}">

                {{-- FIELD CATATAN KHUSUS --}}
                <div>
                    <label class="block text-[11px] font-bold text-stone-700 mb-1">Catatan Khusus (Opsional):</label>
                    <textarea name="catatan" 
                              placeholder="Contoh: Sangat pedas, es sedikit..." 
                              rows="2" 
                              class="w-full border-2 border-stone-300 rounded-lg p-1.5 text-xs focus:border-[#4A2311] focus:outline-none resize-none bg-stone-50 transition-colors"></textarea>
                </div>

                {{-- Pengaturan Jumlah --}}
                <div class="flex items-center justify-between bg-stone-100 p-2 rounded-lg border-2 border-stone-300">
                    <span class="font-bold text-xs text-stone-800">Jumlah:</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="if(qty > 1) qty--" x-show="qty > 1" class="w-7 h-7 bg-stone-200 hover:bg-stone-300 text-stone-900 rounded-lg font-bold border-2 border-stone-900 flex items-center justify-center text-sm transition-colors">
                            −
                        </button>
                        <input type="number" name="jumlah" x-model="qty" readonly class="w-10 text-center border-2 border-stone-400 rounded-lg py-0.5 font-bold text-xs bg-white">
                        <button type="button" @click="qty++" class="w-7 h-7 bg-stone-200 hover:bg-stone-300 text-stone-900 rounded-lg font-bold border-2 border-stone-900 flex items-center justify-center text-sm transition-colors">
                            +
                        </button>
                        {{-- <button type="button" @click="qty++" class="w-7 h-7 bg-stone-200 hover:bg-stone-300 text-stone-900 rounded-lg font-bold border-2 border-stone-900 flex items-center justify-center text-sm transition-colors">
                            +
                        </button> --}}
                    </div>
                </div>

                {{-- Total Subtotal --}}
                <div class="flex justify-between items-center text-xs md:text-sm font-bold text-stone-900 pt-0.5">
                    <span>Total:</span>
                    <span class="text-sm md:text-base text-[#4A2311]" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedMenu.harga * qty)"></span>
                </div>

                <button type="submit" class="w-full py-2.5 bg-[#4A2311] text-white font-black text-xs md:text-sm rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,1)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all">
                    🛒 + Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>

<div x-show="showImageZoom" x-transition.opacity @click.self="showImageZoom = false"
     @keydown.escape.window="showImageZoom = false" style="display: none;"
     class="fixed inset-0 z-[120] flex items-center justify-center bg-black/95 p-4" role="dialog" aria-modal="true" aria-label="Gambar menu ukuran penuh">
    <button type="button" @click="showImageZoom = false"
        class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full border-2 border-white bg-stone-900 text-2xl font-bold text-white transition hover:bg-red-700"
        aria-label="Tutup gambar">&times;</button>
    <img :src="selectedMenu.gambar" :alt="selectedMenu.nama" class="max-h-[90vh] max-w-full object-contain">
</div>