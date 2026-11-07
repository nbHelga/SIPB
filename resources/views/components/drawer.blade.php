{{-- filepath: c:\laragon2\www\SIPB\resources\views\components\drawer.blade.php --}}
@props([
    'id',
    'title',
    'open' => false,
])

<div
    x-data="{ open: @js((bool) $open) }"
    @open-drawer.window="if ($event.detail === '{{ $id }}') open = true"
    @close-drawer.window="if ($event.detail === '{{ $id }}') open = false"
>
    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-stone-950/60"
        @click="open = false"
    ></div>

    <aside
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 w-full max-w-lg overflow-y-auto border-l-4 border-[#4A2311] bg-white shadow-[-8px_0_0_0_rgba(0,0,0,0.3)]"
    >
        <header class="sticky top-0 z-10 flex items-center justify-between border-b-2 border-stone-200 bg-white p-5">
            <h2 class="text-xl font-black">{{ $title }}</h2>
            <button
                type="button"
                @click="open = false"
                class="rounded-lg border-2 border-stone-900 bg-red-500 px-3 py-1 font-bold text-white hover:bg-red-600"
            >
                Tutup
            </button>
        </header>

        <div class="p-6">
            {{ $slot }}
        </div>
    </aside>
</div>