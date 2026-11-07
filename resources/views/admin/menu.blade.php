@extends('layouts.admin')

@section('title', 'Kelola Menu - SIPB')

@section('content')
<div class="flex gap-6">
    @php
        $isKasir = auth()->user()?->status === 'kasir';

        $menuHeaders = [
            ['label' => 'Nama', 'value' => 'nama', 'render' => function ($row) {
                return '<button type="button" onclick="openMenuDrawer(' . $row->id . ')" class="text-[#8B5A2B] font-bold hover:underline text-left">' . e($row->nama) . '</button>';
            }],
            ['label' => 'Kategori', 'value' => 'kategori', 'render' => function ($row) {
                return '<span class="px-2 py-0.5 bg-amber-100 rounded-full text-xs font-bold">' . e($row->kategori) . '</span>';
            }],
            ['label' => 'Harga', 'value' => 'harga', 'render' => function ($row) {
                return 'Rp ' . number_format($row->harga, 0, ',', '.');
            }],
            ['label' => 'Status', 'value' => 'status', 'render' => function ($row) {
                $color = $row->status === 'open'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-stone-100 text-stone-500';

                return '<span class="px-2 py-0.5 rounded-full text-xs font-bold ' . $color . '">' . e($row->status) . '</span>';
            }],
        ];

        if (!$isKasir) {
            $menuHeaders[] = [
                'label' => 'Aksi',
                'value' => 'id',
                'class' => 'w-24 text-center',
                'render' => function ($row) {
                    $url = route('admin.menu.delete', $row->id);

                    return '<button type="button" onclick="event.stopPropagation();" @click="deleteActionUrl = \'' . $url . '\'; showDeleteModal = true" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white font-black text-xs rounded-lg border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition">🗑️ Hapus</button>';
                },
            ];
        }
    @endphp

    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif
    
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-black">📋 Kelola Menu</h1>
            @if(!$isKasir)
                <button onclick="openMenuDrawer(null)" class="px-4 py-2 bg-[#4A2311] text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all">
                    + Tambah Menu
                </button>
            @endif
        </div>

        <div class="flex gap-3 mb-4">
            <div class="flex-1 max-w-sm">
                <x-search action="{{ route('admin.menu') }}" placeholder="Cari menu..." />
            </div>
            <x-filter :filters="[
                ['key' => 'kategori', 'label' => 'Kategori', 'options' => $kategoriList],
            ]" action="{{ route('admin.menu') }}" />
        </div>

        <div x-data="{ showDeleteModal: false, deleteActionUrl: '' }" @close-modal.window="showDeleteModal = false">
            <x-tableData 
                :headers="$menuHeaders"
                :data="$menus"
                emptyMessage="Menu tidak ditemukan" />

            <div x-show="showDeleteModal" style="display: none;">
                <x-alert 
                    type="confirm" 
                    title="HAPUS MENU" 
                    message="Apakah Anda yakin ingin menghapus menu ini dari daftar?" 
                    alpineAction="deleteActionUrl" 
                />
            </div>
        </div>
            
        <div class="mt-4">
            <x-pagination :paginator="$menus" />
        </div>
    </div>

   <x-drawer id="menuDrawer" title="Form Menu" :open="$errors->any()">
        @php
            $isOldEdit = old('_form_mode') === 'edit' && old('_menu_id');
            $initialAction = $isOldEdit
                ? route('admin.menu.update', old('_menu_id'))
                : route('admin.menu.store');
        @endphp

        <form
            id="menuForm"
            data-is-kasir="{{ $isKasir ? '1' : '0' }}"
            data-availability-url-template="{{ route('admin.menu.availability', ['id' => '__ID__']) }}"
            method="POST"
            action="{{ $initialAction }}"
            enctype="multipart/form-data"
            x-data="{ confirmOpen: false }"
            x-ref="menuForm"
            class="space-y-4"
        >
            @csrf
            <input
                id="menuMethod"
                type="hidden"
                name="_method"
                value="{{ $isOldEdit ? 'PUT' : '' }}"
            >
            <input
                id="menuFormMode"
                type="hidden"
                name="_form_mode"
                value="{{ old('_form_mode', 'create') }}"
            >
            <input
                id="menuId"
                type="hidden"
                name="_menu_id"
                value="{{ old('_menu_id') }}"
            >

            @if($errors->any())
                <div class="rounded-lg border-2 border-red-500 bg-red-50 p-3 text-sm font-semibold text-red-700">
                    Periksa kembali isian yang ditandai.
                </div>
            @endif

            <div>
                <label class="mb-2 block text-sm font-bold">Gambar menu</label>

                <div class="rounded-xl border-2 border-dashed border-stone-400 bg-stone-50 p-3">
                    <div class="mx-auto w-full max-w-xs">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-lg border border-stone-300 bg-stone-200">
                            <img
                                id="menuImagePreview"
                                src=""
                                alt="Preview gambar menu"
                                class="hidden h-full w-full object-cover"
                            >
                            <div
                                id="menuImageEmpty"
                                class="absolute inset-0 flex items-center justify-center text-center text-sm text-stone-500"
                            >
                                Belum ada gambar dipilih
                            </div>
                        </div>

                        <input
                            id="menuImageInput"
                            type="file"
                            name="gambar"
                            accept="image/jpeg,image/png,image/webp"
                            @disabled($isKasir)
                            class="mt-3 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#4A2311] file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-[#8B5A2B] disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100"
                        >
                        <p class="mt-2 text-xs text-stone-500">
                            JPG, PNG, atau WEBP; maksimal 2 MB.
                        </p>
                    </div>
                </div>

                @error('gambar')
                    <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">
                    Nama <span class="text-red-600">*</span>
                </label>
                <input
                    name="nama"
                    value="{{ old('nama') }}"
                    required minlength="3" maxlength="100"
                    class="w-full rounded-lg border-2 border-stone-300 px-3 py-2 @error('nama') border-red-500 @enderror disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100"
                    @disabled($isKasir)
                >
                @error('nama') <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">
                    Harga <span class="text-red-600">*</span>
                </label>
                <input
                    type="number"
                    name="harga"
                    value="{{ old('harga') }}"
                    required min="0" max="10000000" step="0.01"
                    class="w-full rounded-lg border-2 border-stone-300 px-3 py-2 @error('harga') border-red-500 @enderror disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100"
                    @disabled($isKasir)
                >
                @error('harga') <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>
            @php
                $oldKategori = old('kategori', '');
                $kategoriBaru = $oldKategori !== '' && !$kategoriList->contains($oldKategori);
            @endphp
            <div>
                <label class="mb-1 block text-sm font-bold">
                    Kategori <span class="text-red-600">*</span>
                </label>
                <select
                    id="kategoriSelect"
                    name="kategori"
                    required
                    onchange="toggleKategoriBaru()"
                    class="w-full rounded-lg border-2 border-stone-300 px-3 py-2 @error('kategori') border-red-500 @enderror disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100"
                    @disabled($isKasir)
                >
                    <option value="">Pilih kategori</option>
                    @foreach($kategoriList as $kategori)
                        <option value="{{ $kategori }}"
                            @selected(!$kategoriBaru && $oldKategori === $kategori)>
                            {{ $kategori }}
                        </option>
                    @endforeach
                    <option value="__kategori_baru__" @selected($kategoriBaru)>
                        + Masukkan kategori baru
                    </option>
                </select>

                <input
                    id="kategoriBaru"
                    type="text"
                    value="{{ $kategoriBaru ? $oldKategori : '' }}"
                    maxlength="50"
                    placeholder="Ketik kategori baru"
                    disabled
                    class="mt-2 hidden w-full rounded-lg border-2 border-stone-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100 @error('kategori') border-red-500 @enderror"
                >

                @error('kategori')
                    <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">Deskripsi</label>
                <textarea
                    name="deskripsi"
                    rows="3"
                    maxlength="1000"
                    class="w-full rounded-lg border-2 border-stone-300 px-3 py-2 @error('deskripsi') border-red-500 @enderror disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500 disabled:opacity-100"
                    @disabled($isKasir)
                >{{ old('deskripsi') }}</textarea>
                @error('deskripsi') <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            @php
            $statusAktif = old('status', 'close') === 'open';
            $bestsellerAktif = (bool) old('is_bestseller', false);
            $reservableAktif = (bool) old('is_reservable', false);
            @endphp

            <div class="space-y-5">
                
                <!-- Status Tampil -->
                <div class="flex items-center justify-between gap-3">
                    <label class="block font-bold text-sm text-stone-800">Status Tampil</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="status" value="close">
                        <input id="menuStatus" type="checkbox" name="status" value="open" class="sr-only peer" @checked(old('status', 'close') === 'open')>
                        <div class="w-11 h-6 bg-stone-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4A2311]"></div>
                    </label>
                </div>

                <!-- Best Seller -->
                <div class="flex items-center justify-between gap-3">
                    <label class="block font-bold text-sm text-stone-800">Best seller</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_bestseller" value="0">
                        <input id="menuBestseller" type="checkbox" name="is_bestseller" value="1" class="sr-only peer" @checked((bool) old('is_bestseller', false)) @disabled($isKasir)>
                        <div class="w-11 h-6 bg-stone-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4A2311] peer-disabled:cursor-not-allowed peer-disabled:bg-stone-200"></div>
                    </label>
                </div>

                <!-- Bisa Dipesan (Reservable) -->
                <div class="flex items-center justify-between gap-3">
                    <label class="block font-bold text-sm text-stone-800">Bisa dipesan untuk reservasi</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_reservable" value="0">
                        <input id="menuReservable" type="checkbox" name="is_reservable" value="1" class="sr-only peer" @checked((bool) old('is_reservable', false))>
                        <div class="w-11 h-6 bg-stone-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4A2311]"></div>
                    </label>
                </div>

            </div>

            <button
                type="button"
                @click="if ($refs.menuForm.reportValidity()) confirmOpen = true"
                class="w-full rounded-lg border-2 border-stone-900 bg-[#4A2311] py-3 font-bold text-white hover:bg-[#8B5A2B]"
            >
                Simpan
            </button>

            {{-- Konfirmasi sebelum form dikirim --}}
            <div
                x-cloak
                x-show="confirmOpen"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-stone-950/60 p-4"
            >
                <div class="w-full max-w-sm rounded-xl border-4 border-stone-900 bg-white p-5 shadow-xl">
                    <h3 class="text-lg font-black">Konfirmasi penyimpanan</h3>
                    <p class="my-3 text-sm">Simpan perubahan menu ini?</p>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="confirmOpen = false"
                            class="rounded-lg border-2 border-stone-900 px-4 py-2 font-bold"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmOpen = false; $refs.menuForm.requestSubmit()"
                            class="rounded-lg border-2 border-stone-900 bg-green-600 px-4 py-2 font-bold text-white"
                        >
                            Ya, simpan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </x-drawer>
</div>

<script>
const menuStoreUrl = @json(route('admin.menu.store'));
const menuJsonUrlTemplate = @json(route('admin.menu.json', ['id' => '__ID__']));
const menuUpdateUrlTemplate = @json(route('admin.menu.update', ['id' => '__ID__']));
const isKasir = document.getElementById('menuForm').dataset.isKasir === '1';
const availabilityUrlTemplate =
    document.getElementById('menuForm').dataset.availabilityUrlTemplate;

function openDrawer(id) {
    window.dispatchEvent(new CustomEvent('open-drawer', { detail: id }));
}

function openMenuDrawer(menuId = null) {
    if (isKasir && menuId === null) return;
    const form = document.getElementById('menuForm');
    const method = document.getElementById('menuMethod');

    if (menuId === null) {
        form.reset();
        setImagePreview('');
        form.action = menuStoreUrl;
        method.value = '';
        document.getElementById('menuFormMode').value = 'create';
        document.getElementById('menuId').value = '';
        openDrawer('menuDrawer');
        return;
    }

    fetch(menuJsonUrlTemplate.replace('__ID__', encodeURIComponent(menuId)), {
        headers: { 'Accept': 'application/json' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil data menu.');
            return response.json();
        })
        .then(menu => {
            form.reset();
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }

            setImagePreview(getMenuImageUrl(menu.gambar));

            if (isKasir) {
                form.action = availabilityUrlTemplate.replace(
                    '__ID__',
                    encodeURIComponent(menu.id)
                );
                method.value = 'PATCH';
            } else {
                form.action = menuUpdateUrlTemplate.replace(
                    '__ID__',
                    encodeURIComponent(menu.id)
                );
                method.value = 'PUT';
            }
            // form.action = menuUpdateUrlTemplate.replace('__ID__', encodeURIComponent(menu.id));
            // method.value = 'PUT';

            document.getElementById('menuFormMode').value = 'edit';
            document.getElementById('menuId').value = menu.id;
            form.elements.nama.value = menu.nama ?? '';
            form.elements.harga.value = menu.harga ?? '';
             form.elements.deskripsi.value = menu.deskripsi ?? '';

            setMenuCategory(menu.kategori ?? '');

            document.getElementById('menuStatus').checked =
                String(menu.status).toLowerCase() === 'open';
            document.getElementById('menuBestseller').checked = isEnabled(menu.is_bestseller);
            document.getElementById('menuReservable').checked = isEnabled(menu.is_reservable);

            openDrawer('menuDrawer');
        })
        .catch(error => {
            console.error(error);
            alert('Gagal memuat detail menu.');
        });
}

function isEnabled(value) {
    return value === true || value === 1 || value === '1' ||
           String(value).toLowerCase() === 'true';
}

function toggleKategoriBaru() {
    const select = document.getElementById('kategoriSelect');
    const input = document.getElementById('kategoriBaru');
    const isNew = select.value === '__kategori_baru__';

    input.classList.toggle('hidden', !isNew);
    input.disabled = !isNew;
    input.required = isNew;
    input.name = isNew ? 'kategori' : '';
    select.disabled = isNew;
}

function setMenuCategory(value) {
    const select = document.getElementById('kategoriSelect');
    const input = document.getElementById('kategoriBaru');
    const exists = [...select.options].some(option => option.value === value);

    if (exists && value !== '__kategori_baru__') {
        select.value = value;
        input.value = '';
    } else {
        select.value = '__kategori_baru__';
        input.value = value || '';
    }

    toggleKategoriBaru();
}

function toggleKategoriBaru() {
    const select = document.getElementById('kategoriSelect');
    const input = document.getElementById('kategoriBaru');
    const isNew = select.value === '__kategori_baru__';

    input.classList.toggle('hidden', !isNew);
    input.required = isNew && !isKasir;
    input.name = isNew && !isKasir ? 'kategori' : '';

    // Kasir tidak boleh mengubah kategori, apa pun pilihan select.
    input.disabled = isKasir || !isNew;
    select.disabled = isKasir || isNew;
}

// Setelah validasi gagal, Laravel mengembalikan old input dan error.
// Drawer dibuka kembali otomatis oleh prop :open="$errors->any()".
const imageInput = document.getElementById('menuImageInput');
const imagePreview = document.getElementById('menuImagePreview');
const imageEmpty = document.getElementById('menuImageEmpty');
const storageBase = @json(asset('storage'));
let previewObjectUrl = null;

function setImagePreview(src = '') {
    imagePreview.src = src;
    imagePreview.classList.toggle('hidden', !src);
    imageEmpty.classList.toggle('hidden', !!src);
}

function getMenuImageUrl(path) {
    if (!path) return '';

    // Mendukung path DB "menu/file.jpg", "storage/menu/file.jpg",
    // atau URL lengkap jika data lama menyimpan URL.
    if (/^https?:\/\//i.test(path)) return path;

    const cleanPath = String(path).replace(/^\/?(public\/)?storage\//, '').replace(/^\/+/, '');
    return `${storageBase}/${cleanPath}`;
}

imageInput.addEventListener('change', () => {
    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    }

    const file = imageInput.files?.[0];
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        imageInput.value = '';
        setImagePreview('');
        alert('Pilih gambar JPG, PNG, atau WEBP.');
        return;
    }

    previewObjectUrl = URL.createObjectURL(file);
    setImagePreview(previewObjectUrl);
});
</script>
@endsection