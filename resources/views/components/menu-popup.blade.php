@props([
    'menus' => [],
    'isEdit' => false,
    'pesananTerkait' => null,
    'actionUrl' => '',
    'minimumTotal' => 50000,
])

@php
    $modalId = $isEdit ? 'editMenuModal' : 'multiMenuModal';
    $inputId = $isEdit ? 'editMenuSearchInput' : 'menuSearchInput';
    $containerId = $isEdit ? 'editMenuItemsContainer' : 'menuItemsContainer';
    $totalDisplayId = $isEdit ? 'editModalTotalDisplay' : 'modalTotalDisplay';
    $btnId = $isEdit ? 'btnConfirmEditMenu' : 'btnConfirmMenu';
    $warningId = $isEdit ? 'editMinOrderWarning' : 'minOrderWarning';
    $minimumTotal = max(50000, (int) $minimumTotal);
    $minimumWarning = $isEdit
        ? 'Total menu tidak boleh kurang dari DP yang dibayar: Rp ' . number_format($minimumTotal, 0, ',', '.') . '.'
        : 'Minimal pemesanan menu adalah Rp 50.000.';
@endphp


<div id="{{ $modalId }}" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white w-full max-w-xl rounded-2xl border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col max-h-[85vh] overflow-hidden">
        
        <div class="bg-[#4A2311] text-white p-4 flex justify-between items-center border-b-4 border-stone-900 flex-shrink-0">
            <div>
                <h3 class="text-xl font-black tracking-wide">{{ $isEdit ? 'Sesuaikan Menu Reservasi' : 'Pilih Menu Reservasi' }}</h3>
                <p class="text-xs text-amber-200 font-medium">Silakan tentukan pesanan Anda</p>
            </div>
            <button type="button" onclick="toggleModal('{{ $modalId }}', false)" class="w-8 h-8 bg-red-500 rounded-full border-2 border-stone-900 flex items-center justify-center font-black hover:bg-red-600">✕</button>
        </div>

        {{-- PERBAIKAN STRUKTUR: Memisahkan form dengan div agar flexbox tidak rusak --}}
        @if($isEdit)
        <form action="{{ $actionUrl }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
        @else
        <div class="flex flex-col flex-1 overflow-hidden">
        @endif

            <div class="p-4 bg-stone-50 border-b-2 border-stone-200 flex-shrink-0">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-stone-400">🔍</span>
                    {{-- Cegah enter mensubmit form secara tidak sengaja --}}
                    <input type="text" id="{{ $inputId }}" onkeyup="filterMenuSearch('{{ $inputId }}', '#{{ $containerId }} .menu-item-card')" onkeydown="return event.key !== 'Enter';" placeholder="Cari menu favorit kamu di sini..." 
                        class="w-full border-2 border-stone-900 rounded-xl pl-10 pr-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#4A2311]">
                </div>
            </div>

            <div
                class="p-4 overflow-y-auto space-y-4 flex-1 bg-stone-100"
                id="{{ $containerId }}"
                data-minimum-total="{{ $minimumTotal }}"
            >
                @foreach($menus as $menu)
                @php
                    $oldQty = 0;
                    $oldNote = '';
                    if ($isEdit && $pesananTerkait) {
                        // Mencari data menu pada relasi pesanan
                        $checkedItem = $pesananTerkait->detailPesanan->where('id_menu', $menu->id)->first();
                        $oldQty = $checkedItem ? $checkedItem->jumlah : 0;
                        $oldNote = $checkedItem ? $checkedItem->catatan : '';
                    } else {
                        // Jika form baru / gagal validasi, ambil dari old()
                        $oldQty = old('menu_qty.'.$menu->id, 0);
                        $oldNote = old('menu_notes.'.$menu->id, '');
                    }
                @endphp
                <div class="menu-item-card bg-white border-2 border-stone-900 rounded-xl p-4 shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-all {{ $oldQty > 0 ? 'border-amber-700 bg-amber-50/20' : '' }}" data-nama="{{ strtolower($menu->nama) }}" data-harga="{{ $menu->harga }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-16 h-16 bg-stone-200 rounded-lg border-2 border-stone-300 overflow-hidden flex-shrink-0">
                                @if($menu->gambar)
                                    <img src="{{ asset('storage/' . $menu->gambar) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-stone-400 text-2xl">🍽️</div>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-black text-stone-900 text-sm menu-item-title">{{ $menu->nama }}</h4>
                                <p class="text-xs font-bold text-[#8B5A2B]">Rp {{ number_format($menu->harga, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="flex items-center border border-stone-900 rounded overflow-hidden bg-stone-50">
                                <button type="button" onclick="updateQty(this, -1, '{{ $containerId }}', '{{ $totalDisplayId }}', '{{ $btnId }}', '{{ $warningId }}')" class="btn-minus {{ $oldQty > 0 ? '' : 'hidden' }} px-2 py-0.5 bg-red-100 text-red-700 font-black text-xs border-r border-stone-900">-</button>
                                <input type="number" name="menu_qty[{{ $menu->id }}]" value="{{ $oldQty }}" min="0" readonly class="qty-input w-8 text-center font-extrabold text-xs bg-transparent focus:outline-none">
                                <button type="button" onclick="updateQty(this, 1, '{{ $containerId }}', '{{ $totalDisplayId }}', '{{ $btnId }}', '{{ $warningId }}')" class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-black text-xs border-l border-stone-900">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="note-dropdown {{ $oldQty > 0 ? '' : 'hidden' }} mt-3 pt-3 border-t border-dashed border-stone-300">
                        <label class="block text-[11px] font-black text-stone-600 uppercase mb-1">✍️ Catatan Khusus</label>
                        <textarea name="menu_notes[{{ $menu->id }}]" class="w-full text-xs border border-stone-400 rounded-lg p-2 focus:outline-none focus:border-[#4A2311] font-medium">{{ $oldNote }}</textarea>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="p-4 bg-white border-t-4 border-stone-900 space-y-3 flex-shrink-0">
                <div class="flex justify-between items-center text-xs lg:text-sm font-bold">
                    <span class="text-stone-600">Total Pemesanan Menu:</span>
                    <span id="{{ $totalDisplayId }}" class="text-base font-black text-[#4A2311]">Rp 0</span>
                </div>
                
                <div
                    id="{{ $warningId }}"
                    class="hidden text-xs font-bold text-red-600 bg-red-50 p-2 rounded-lg border border-red-200 text-center"
                >
                    ⚠️ {{ $minimumWarning }}
                </div>
                
                @if($isEdit)
                    <button
                        type="submit"
                        id="{{ $btnId }}"
                        disabled
                        class="w-full py-3 bg-stone-400 text-stone-200 font-black rounded-xl border-2 border-stone-900 cursor-not-allowed uppercase tracking-wider text-xs"
                    >
                        Simpan Perubahan Menu
                    </button>
                @else
                    <button type="button" id="{{ $btnId }}" onclick="submitSelectedMenus('{{ $containerId }}', '{{ $modalId }}')" class="w-full py-3 bg-[#4A2311] text-white font-black rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:bg-[#5c2d17] cursor-pointer transition-all uppercase tracking-wider text-sm">Konfirmasi Pilihan Menu</button>
                @endif
            </div>

        @if($isEdit)
        </form>
        @else
        </div>
        @endif
    </div>
</div>

<script>
function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (show) {
        modal.classList.remove('hidden');
        calculateModalTotal('{{ $containerId }}', '{{ $totalDisplayId }}', '{{ $btnId }}', '{{ $warningId }}');
    } else {
        modal.classList.add('hidden');
    }
}

function updateQty(button, amount, containerId, displayId, btnId, warningId) {
    const card = button.closest('.menu-item-card');
    const input = card.querySelector('.qty-input');
    const btnMinus = card.querySelector('.btn-minus');
    const noteDropdown = card.querySelector('.note-dropdown');
    
    let currentVal = parseInt(input.value) || 0;
    currentVal += amount;
    if (currentVal < 0) currentVal = 0;
    input.value = currentVal;

    if (currentVal > 0) {
        btnMinus.classList.remove('hidden');
        noteDropdown.classList.remove('hidden');
        card.classList.add('border-amber-700', 'bg-amber-50/20');
    } else {
        btnMinus.classList.add('hidden');
        noteDropdown.classList.add('hidden');
        card.classList.remove('border-amber-700', 'bg-amber-50/20');
    }
    calculateModalTotal(containerId, displayId, btnId, warningId);
}

function calculateModalTotal(containerId, displayId, btnId, warningId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let total = 0;
    container.querySelectorAll('.menu-item-card').forEach(card => {
        const qty = parseInt(card.querySelector('.qty-input').value) || 0;
        const harga = parseFloat(card.getAttribute('data-harga')) || 0;
        total += (qty * harga);
    });
    
    document.getElementById(displayId).innerText = 'Rp ' + total.toLocaleString('id-ID');
    
    const warning = document.getElementById(warningId);
    const button = document.getElementById(btnId);

    // =========================================================================
    // LOGIKA KONDISI 2 (AKTIF): WAJIB LEBIH DARI DP
    // =========================================================================
    const minimumTotal = Number(container.dataset.minimumTotal) || 50000;
    const meetsMinimum = total >= minimumTotal;

    if (warning) {
        warning.classList.toggle('hidden', meetsMinimum);
    }

    if (button) {
        button.disabled = !meetsMinimum;
        button.className = meetsMinimum
            ? "w-full py-3 bg-[#4A2311] text-white font-black rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:bg-[#5c2d17] cursor-pointer transition-all uppercase tracking-wider text-xs"
            : "w-full py-3 bg-stone-400 text-stone-200 font-black rounded-xl border-2 border-stone-900 cursor-not-allowed uppercase tracking-wider text-xs";
    }

    /*
    // =========================================================================
    // LOGIKA KONDISI 1 (KOMENTAR): TIDAK WAJIB PESAN MENU
    // =========================================================================
    // Jika ingin merubah ke kondisi 1, hapus/komentar blok kondisi 2 di atas, 
    // lalu aktifkan komentar blok di bawah ini:
    
    // if (warning) warning.classList.add('hidden');
    // if (button) {
    //     button.disabled = false;
    //     button.className = "w-full py-3 bg-[#4A2311] text-white font-black rounded-xl border-2 border-stone-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:bg-[#5c2d17] cursor-pointer transition-all uppercase tracking-wider text-xs";
    // }
    */
}

function filterMenuSearch(inputId, cardSelector) {
    const query = document.getElementById(inputId).value.toLowerCase();
    document.querySelectorAll(cardSelector).forEach(card => {
        const name = card.getAttribute('data-nama');
        card.style.display = name.includes(query) ? "block" : "none";
    });
}

function submitSelectedMenus(containerId, modalId) {
    const selectedItems = [];
    document.querySelectorAll('#' + containerId + ' .menu-item-card').forEach(card => {
        const qty = parseInt(card.querySelector('.qty-input').value) || 0;
        if (qty > 0) {
            selectedItems.push({
                nama: card.querySelector('.menu-item-title').innerText.trim(),
                harga: parseFloat(card.getAttribute('data-harga')) || 0,
                qty: qty,
                notes: (card.querySelector('textarea[name^="menu_notes"]') || {}).value || ''
            });
        }
    });
    
    if (typeof updateSelectedMenuSummary === "function") {
        updateSelectedMenuSummary(selectedItems);
    }
    toggleModal(modalId, false);
}
</script>