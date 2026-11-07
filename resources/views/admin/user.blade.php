@extends('layouts.admin')

@section('title', 'Kelola User - SIPB')

@section('content')
{{-- Flash Session Alert (Notifikasi dari Controller) --}}
{{-- @if(session('success'))
    <x-alert type="success" title="Sukses" :message="session('success')" />
@endif

@if(session('error'))
    <x-alert type="error" title="Terjadi Kesalahan" :message="session('error')" />
@endif

{{-- Validation Error Alert --}}
{{-- @if($errors->any())
    <x-alert type="error" title="Gagal Menyimpan" :message="$errors->first()" />
@endif  --}}

<div class="flex gap-6">
    
    {{-- Toast Flash Messages --}}
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-black">👥 Kelola Akun Pengguna</h1>
            <button onclick="openUserDrawer(null)"
                    class="px-4 py-2 bg-[#4A2311] text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[3px] active:translate-y-[3px] transition-all">
                + Tambah Pengguna
            </button>
        </div>

        <div class="flex gap-3 mb-4">
            <div class="flex-1 max-w-sm">
                <x-search action="{{ route('admin.user') }}" placeholder="Cari user..." />
            </div>
            <x-filter :filters="[
                ['key' => 'status', 'label' => 'Status', 'options' => ['kasir' => 'Kasir', 'admin' => 'Admin']],
            ]" action="{{ route('admin.user') }}" />
        </div>
        
        @php
            $userHeaders = [
                ['label' => 'No', 'value' => 'id', 'class' => 'w-12 text-center'],
                ['label' => 'Username', 'value' => 'username', 
                    'render' => function($row) {
                        // Menggunakan kutip ganda asli (") di dalam string kutip tunggal (')
                        return '<button type="button" onclick="openUserDrawer(' . $row->id . ')" class="text-[#8B5A2B] font-bold hover:underline text-left">' . e($row->username) . '</button>';
                    }
                ],
                ['label' => 'Status', 'value' => 'status', 'render' => function($row) {
                    $color = $row->status === 'admin' ? 'bg-white text-stone-900' : 'bg-blue-500 text-white';
                    return '<span class="px-3 py-1 rounded-full text-xs font-bold border-2 border-stone-900 ' . $color . '">' . e($row->status) . '</span>';
                }],
                ['label' => 'Aksi', 'value' => 'id', 'class' => 'w-24 text-center', 'render' => function($row) {
                    $url = route('admin.user.delete', $row->id);
                    return '<button type="button" onclick="event.stopPropagation();" @click="deleteActionUrl = \'' . $url . '\'; showDeleteModal = true" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white font-black text-xs rounded-lg border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition">🗑️</button>';
                }],
            ];
        @endphp
        
        <div id="deleteConfirmWrapper" class="hidden">
            <x-alert 
                type="confirm" 
                title="KONFIRMASI HAPUS" 
                message="Apakah Anda yakin ingin menghapus user ini? Data yang dihapus tidak dapat dikembalikan." 
            />
        </div>

        <div x-data="{ showDeleteModal: false, deleteActionUrl: '' }" @close-modal.window="showDeleteModal = false">           
            <x-tabledata 
                :headers="$userHeaders"
                :data="$users"
                emptyMessage="User tidak ditemukan" />

            <div x-show="showDeleteModal" style="display: none;">
                <x-alert 
                    type="confirm" 
                    title="KONFIRMASI HAPUS" 
                    message="Apakah Anda yakin ingin menghapus user ini? Data yang dihapus tidak dapat dikembalikan." 
                    alpineAction="deleteActionUrl" 
                />
            </div>
        </div>

        <div class="mt-4">
            <x-pagination :paginator="$users" />
        </div>
    </div>

    <!-- DRAWER -->  
    <x-drawer id="userDrawer" title="Form Pengguna" :open="$errors->any()">
        @php
            $isOldEdit = old('_form_mode') === 'edit' && old('_user_id');
            $initialAction = $isOldEdit
                ? url('/admin/user/' . old('_user_id') . '/update')
                : route('admin.user.store');
        @endphp

        <form id="userForm" method="POST" action="{{ $initialAction }}" class="space-y-4">
            @csrf
            <input id="userMethod" type="hidden" name="_method" value="{{ $isOldEdit ? 'PUT' : '' }}">
            <input id="userFormMode" type="hidden" name="_form_mode" value="{{ old('_form_mode', 'create') }}">
            <input id="userId" type="hidden" name="_user_id" value="{{ old('_user_id') }}">

            @if($errors->any())
                <div class="rounded-lg border-2 border-red-500 bg-red-50 p-3 text-sm font-semibold text-red-700">
                    Periksa kembali isian yang ditandai.
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-bold">
                    Username <span class="text-red-600">*</span>
                </label>
                <input
                    id="userUsername"
                    name="username"
                    value="{{ old('username') }}"
                    required maxlength="50"
                    class="w-full rounded-lg border-2 border-stone-300 px-4 py-3 @error('username') border-red-500 @enderror"
                >
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">
                    Password <span id="passwordRequiredMark" class="text-red-600">*</span>
                </label>
                <input
                    id="userPassword"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border-2 border-stone-300 px-4 py-3 @error('password') border-red-500 @enderror"
                >
                <p id="passwordHint" class="mt-1 hidden text-xs text-stone-500">
                    Kosongkan jika tidak ingin mengubah password.
                </p>
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">
                    Status <span class="text-red-600">*</span>
                </label>
                <select
                    id="userStatus"
                    name="status"
                    required
                    class="w-full rounded-lg border-2 border-stone-300 px-4 py-3 @error('status') border-red-500 @enderror"
                >
                    <option value="kasir" @selected(old('status', 'kasir') === 'kasir')>Kasir</option>
                    <option value="admin" @selected(old('status') === 'admin')>Admin</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-lg border-2 border-stone-900 bg-[#4A2311] py-3 font-bold text-white hover:bg-[#8B5A2B]"
            >
                Simpan
            </button>
        </form>
    </x-drawer>
</div>

<script>
const userStoreUrl = @json(route('admin.user.store'));
const userJsonUrl = @json(url('/admin/user'));
const userUpdateUrl = @json(url('/admin/user'));

function openUserDrawerEvent() {
    window.dispatchEvent(new CustomEvent('open-drawer', { detail: 'userDrawer' }));
}

function openUserDrawer(userId = null) {
    const form = document.getElementById('userForm');
    const method = document.getElementById('userMethod');
    const password = document.getElementById('userPassword');

    if (userId === null) {
        form.reset();
        form.action = userStoreUrl;
        method.value = '';
        document.getElementById('userFormMode').value = 'create';
        document.getElementById('userId').value = '';
        password.required = true;
        document.getElementById('passwordRequiredMark').classList.remove('hidden');
        document.getElementById('passwordHint').classList.add('hidden');
        openUserDrawerEvent();
        return;
    }

    fetch(`${userJsonUrl}/${encodeURIComponent(userId)}/json`, {
        headers: { Accept: 'application/json' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil data user.');
            return response.json();
        })
        .then(user => {
            form.reset();
            form.action = `${userUpdateUrl}/${encodeURIComponent(user.id)}/update`;
            method.value = 'PUT';
            document.getElementById('userFormMode').value = 'edit';
            document.getElementById('userId').value = user.id;
            document.getElementById('userUsername').value = user.username ?? '';
            document.getElementById('userStatus').value = user.status ?? 'kasir';

            password.value = '';
            password.required = false;
            document.getElementById('passwordRequiredMark').classList.add('hidden');
            document.getElementById('passwordHint').classList.remove('hidden');

            openUserDrawerEvent();
        })
        .catch(error => {
            console.error(error);
            alert('Gagal memuat data pengguna.');
        });
}

function closeUserDrawer() {
    document.getElementById('userDrawer').classList.add('translate-x-full');
}

// function deleteUser(userId) {
//     // 1. Ambil URL endpoint hapus dari Laravel Route
//     const baseUrl = "{{ route('admin.user.delete', ':id') }}";
//     const deleteUrl = baseUrl.replace(':id', userId);

//     // 2. Ambil elemen wrapper modal dan form-nya
//     const modalWrapper = document.getElementById('deleteConfirmWrapper');
//     const form = modalWrapper.querySelector('form');

//     // 3. Set URL ke form action dan tampilkan modal pop-up
//     if (form) {
//         form.action = deleteUrl;
//     }
//     modalWrapper.classList.remove('hidden');
//     // if (!confirm('Yakin ingin menghapus user ini?')) return;
//     // const form = document.createElement('form');
//     // form.method = 'POST';
//     // form.action = `/admin/user/${id}/delete`;
//     // form.innerHTML = `@csrf @method('DELETE')`;
//     // document.body.appendChild(form);
//     // form.submit();
//     // window.location.href = `{{ route('admin.user') }}?action=delete&user_id=${id}`;
// }
// function closeConfirmModal() {
//     const modalWrapper = document.getElementById('deleteConfirmWrapper');
//     if (modalWrapper) {
//         modalWrapper.classList.add('hidden');
//     }
// }

// @if(request()->route('id'))
//     document.addEventListener('DOMContentLoaded', function() {
//         openUserDrawer({{ request()->route('id') }});
//     });
// @endif
</script>
@endsection
