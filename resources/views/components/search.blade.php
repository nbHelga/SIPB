{{-- @props([
'placeholder' => 'Cari data...',
'action' => '',
])

<form action="{{ $action }}" method="GET" class="relative">
<input type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="{{ $placeholder }}"
        class="w-full pl-4 pr-12 py-2 border-2 border-stone-300 rounded-full text-sm focus:border-[#4A2311] focus:outline-none shadow-[2px_2px_0_0_rgba(0,0,0,0.1)]">
<button type="submit"
        class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-bold border-2 border-stone-900 hover:bg-[#8B5A2B] transition">
    🔍
</button>
</form> --}}
@props([
    'placeholder' => 'Cari data...',
    'action' => '',
    'variant' => 'primary' // 'primary' untuk Menu/User, 'secondary' untuk Order/Reservasi
])

<form action="{{ $action }}" method="GET" class="relative">
    @foreach(request()->except(['search', 'page']) as $key => $value)
        @if(is_array($value))
            @foreach($value as $subKey => $subValue)
                @if(!is_array($subValue))
                    <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                @endif
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="{{ $placeholder }}"
        class="w-full border-2 border-stone-300 text-sm focus:border-[#4A2311] focus:outline-none shadow-[2px_2px_0_0_rgba(0,0,0,0.1)]
        {{ $variant === 'primary' ? 'pl-4 pr-12 py-2 rounded-full' : 'px-4 py-2 rounded-lg' }}">

    @if($variant === 'primary')
        <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-bold border-2 border-stone-900 hover:bg-[#8B5A2B] transition">
            🔍
        </button>
    @else
        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-[#4A2311] font-bold hover:text-[#8B5A2B]">
            🔍
        </button>
    @endif
</form>