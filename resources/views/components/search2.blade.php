@props([
    'placeholder' => 'Cari pesanan...',
    'action' => '',
])

<form action="{{ $action }}" method="GET" class="relative">
    <input type="text"
           name="search"
           value="{{ request('search') }}"
           placeholder="{{ $placeholder }}"
           class="w-full px-4 py-2 border-2 border-stone-300 rounded-lg text-sm focus:border-[#4A2311] focus:outline-none shadow-[2px_2px_0_0_rgba(0,0,0,0.1)]">
    <button type="submit"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-[#4A2311] font-bold hover:text-[#8B5A2B]">
        🔍
    </button>
</form>
