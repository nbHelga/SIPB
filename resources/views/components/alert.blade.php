@props([
    'type' => 'info',
    'title' => null,
    'message' => '',
    'onConfirm' => '#',
    'alpineAction' => null, // 1. Tambahkan prop ini
    'onCancel' => '#',
    'autoClose' => true,
    'duration' => 3000
])

@php
    // Konfigurasi Warna Tema BigBang Eatery
    $theme = [
        'success' => [
            'bg' => 'bg-emerald-400',
            'icon' => '✅',
            'title' => 'BERHASIL!',
        ],
        'error' => [
            'bg' => 'bg-rose-500 text-white',
            'icon' => '💥',
            'title' => 'TERJADI KESALAHAN!',
        ],
        'warning' => [
            'bg' => 'bg-amber-400',
            'icon' => '⚠️',
            'title' => 'PERHATIAN!',
        ],
        'info' => [
            'bg' => 'bg-cyan-400',
            'icon' => '🔔',
            'title' => 'INFORMASI',
        ],
        'confirm' => [
            'bg' => 'bg-yellow-400',
            'icon' => '🍔',
            'title' => $title ?? 'KONFIRMASI PESANAN!',
        ],
        'success-toast' => [
            'bg' => 'bg-emerald-500 text-white',
            'icon' => '✅',
            'title' => 'BERHASIL!',
        ],
    ][$type] ?? [
        'bg' => 'bg-stone-200',
        'icon' => 'ℹ️',
        'title' => 'NOTIFIKASI',
    ];
@endphp

@if($type === 'success-toast')
    <div
        x-data="{ show: true, timer: null }"
        x-init="
            @if($autoClose)
                timer = setTimeout(() => show = false, {{ $duration }});
            @endif
        "
        x-show="show"
        x-cloak
        x-transition
        role="status"
        aria-live="polite"
        class="fixed right-4 top-[5.5rem] z-[120] w-[calc(100%-2rem)] max-w-sm"
    >
        <div class="{{ $theme['bg'] }} flex items-start gap-3 rounded-xl border-2 border-stone-900 p-4 shadow-lg">
            <span class="text-xl">{{ $theme['icon'] }}</span>
            <div class="flex-1">
                <div class="text-xs font-black uppercase">{{ $theme['title'] }}</div>
                <div class="text-sm font-bold">{{ $message }}</div>
            </div>
            <button type="button" @click="show = false" class="font-black" aria-label="Tutup">✕</button>
        </div>
    </div>
@elseif($type === 'admin-notification')
    <div
        x-data="{
            show: false,
            message: '',
            init() {
                window.addEventListener('admin-new-items', (event) => {
                    this.message = event.detail.message;
                    this.show = true;
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.show = false, 15000);
                });
            }
        }"
        x-cloak
        x-show="show"
        x-cloak
        x-transition
        class="fixed right-5 top-5 z-[100] max-w-sm w-full"
    >
        <div class="border-2 border-stone-900 rounded-xl bg-amber-400 p-4 shadow-lg flex gap-3">
            <span class="text-2xl">🔔</span>
            <div class="flex-1 font-bold text-xl" x-text="message"></div>
            <button type="button" @click="show = false" class="font-black">✕</button>
        </div>
    </div>
@elseif($type === 'confirm-action')
    <div
        x-data="{
            show: false,
            message: '',
            formId: '',
            init() {
                window.addEventListener('component-confirm', (event) => {
                    this.message = event.detail.message || 'Apakah Anda yakin?';
                    this.formId = event.detail.formId || '';
                    this.show = true;
                });
            },
            confirm() {
                const form = document.getElementById(this.formId);
                this.show = false;
                if (form) form.requestSubmit();
            }
        }"
        x-cloak
        x-show="show"
        class="fixed inset-0 z-[150] flex items-center justify-center bg-black/50 p-4"
        role="alertdialog"
        aria-modal="true"
    >
        <div class="w-full max-w-sm rounded-2xl border-4 border-stone-900 bg-white p-5 shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
            <h3 class="text-lg font-black text-stone-900">Konfirmasi pembatalan</h3>
            <p class="mt-2 text-sm text-stone-700" x-text="message"></p>

            <div class="mt-5 flex justify-end gap-3">
                <button
                    type="button"
                    @click="show = false"
                    class="rounded-lg border-2 border-stone-900 bg-white px-4 py-2 font-bold"
                >
                    Kembali
                </button>
                <button
                    type="button"
                    @click="confirm()"
                    class="rounded-lg border-2 border-red-900 bg-red-600 px-4 py-2 font-bold text-white"
                >
                    Ya, batalkan
                </button>
            </div>
        </div>
    </div>
{{-- ================================================= --}}
{{--                  ALERT ALOKASI MEJA               --}}
{{-- ================================================= --}}
@elseif($type === 'client-notification')
    <div
        x-data="{
            show: false,
            message: '',
            timer: null,
            init() {
                window.addEventListener('component-alert', (event) => {
                    this.message = event.detail.message || '';
                    this.show = true;
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.show = false, 5000);
                });
            }
        }"
        x-show="show"
        x-cloak
        x-transition
        class="fixed right-5 top-[5.5rem] z-[110] w-[calc(100%-2.5rem)] max-w-sm"
    >
        <div class="flex items-start gap-3 rounded-xl border-2 border-red-900 bg-red-600 p-4 text-white shadow-lg">
            <span class="text-xl" aria-hidden="true">⚠️</span>
            <p class="flex-1 font-bold" x-text="message"></p>
            <button
                type="button"
                @click="show = false"
                class="font-black text-white hover:text-red-200"
                aria-label="Tutup"
            >
                &times;
            </button>
        </div>
    </div> 
@elseif($type === 'confirm')
    {{-- ================================================= --}}
    {{-- MODAL POP-UP CONFIRMATION (BIGBANG EATERY THEME)  --}}
    {{-- ================================================= --}}
    <div x-data="{ open: true }" 
         x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

        <div class="w-full max-w-md bg-stone-100 border-4 border-stone-900 rounded-2xl shadow-[8px_8px_0_0_rgba(0,0,0,1)] overflow-hidden">
            
            {{-- Header --}}
            <div class="{{ $theme['bg'] }} px-5 py-4 border-b-4 border-stone-900 flex items-center justify-between">
                <div class="flex items-center gap-2 font-black text-lg tracking-wider uppercase text-stone-900">
                    <span class="text-2xl">{{ $theme['icon'] }}</span>
                    <span>{{ $theme['title'] }}</span>
                </div>
                {{-- Mengirimkan event 'close-modal' saat ditutup --}}
                {{-- <button type="button" @click="$dispatch('close-modal')" class="w-8 h-8 rounded-lg bg-stone-900 text-white font-bold flex items-center justify-center hover:bg-stone-800 transition">
                    ✕
                </button> --}}
            </div>

            {{-- Body --}}
            <div class="p-6 text-stone-900 font-bold text-base leading-relaxed">
                <p>{{ $message }}</p>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-6 pt-2 flex gap-3 justify-end">
                <button type="button" @click="$dispatch('close-modal')" class="px-5 py-2.5 rounded-xl border-2 border-stone-900 bg-stone-200 text-stone-900 font-black hover:bg-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,1)] active:translate-x-0.5 active:translate-y-0.5 transition">
                    BATAL
                </button>

                {{-- 2. Modifikasi bagian tag <form> ini --}}
                <form 
                    @if($alpineAction) 
                        x-bind:action="{{ $alpineAction }}" 
                    @else 
                        action="{{ $onConfirm }}" 
                    @endif 
                    method="POST" class="inline"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-2.5 rounded-xl border-2 border-stone-900 bg-red-500 text-white font-black hover:bg-red-600 shadow-[3px_3px_0_0_rgba(0,0,0,1)] active:translate-x-0.5 active:translate-y-0.5 transition">
                        YA, HAPUS
                    </button>
                </form>
            </div>

        </div>
    </div>

@else

    {{-- ================================================= --}}
    {{-- FLOATING TOAST ALERT (FLASH MESSAGES / NOTIFIKASI) --}}
    {{-- ================================================= --}}
    <div x-data="{ 
            show: true,
            init() {
                @if($autoClose)
                    setTimeout(() => { this.show = false }, {{ $duration }});
                @endif
            }
         }" 
         x-show="show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-[-20px] scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed top-5 right-5 z-50 max-w-sm w-full pointer-events-auto">

        <div class="{{ $theme['bg'] }} border-3 border-stone-900 rounded-xl p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] flex items-start gap-3">
            {{-- Icon --}}
            <span class="text-2xl leading-none select-none">{{ $theme['icon'] }}</span>

            {{-- Pesan --}}
            <div class="flex-1 font-bold text-stone-900 text-sm leading-snug">
                <div class="font-black text-xs uppercase opacity-80 mb-0.5">{{ $theme['title'] }}</div>
                <div>{{ $message }}</div>
            </div>

            {{-- Tombol Tutup (X) --}}
            <button type="button" @click="show = false" class="text-stone-900 hover:opacity-70 font-black text-lg leading-none p-1">
                ✕
            </button>
        </div>

    </div>

@endif