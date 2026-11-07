{{-- @props([
    'filters' => [], // [['key' => 'kategori', 'label' => 'Kategori', 'options' => ['Makanan', 'Minuman']]]
    'action' => '',
])

<div x-data="{ open: false }" class="relative">
    <!-- Tombol Filter -->
    <button @click="open = !open"
            class="flex items-center gap-2 px-4 py-2 bg-[#4A2311] text-white font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] transition active:shadow-none active:translate-x-[3px] active:translate-y-[3px]">
        <span>🔽</span> Filter
    </button>

    <!-- Pop-up Filter -->
    <div x-show="open"
         @click.away="open = false"
         x-transition
         class="absolute top-full mt-2 left-0 z-50 bg-white border-2 border-stone-900 rounded-lg shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] p-4 min-w-[200px]">
        <form action="{{ $action }}" method="GET" class="space-y-3"
              x-data="{ changed() { this.$el.submit() } }">
            @foreach($filters as $filter)
                <div>
                    <label class="block text-sm font-bold mb-1">{{ $filter['label'] }}</label>
                    <select name="filter[{{ $filter['key'] }}]"
                            class="w-full border-2 border-stone-300 rounded px-3 py-2 text-sm focus:border-[#4A2311] focus:outline-none"
                            @change="$el.form.submit()">
                        <option value="">Semua</option>
                        @foreach($filter['options'] as $value => $label)
                            <option value="{{ is_string($value) ? $value : $label }}"
                                {{ request('filter.'.$filter['key']) == (is_string($value) ? $value : $label) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </form>
    </div>
</div> --}}
@props([
    'filters' => [],
    'action' => '',
    'variant' => 'primary' // 'primary' (Cokelat), 'secondary' (Kuning Pastel)
])

<div x-data="{ open: false }" class="relative">
    {{-- Tombol Filter Utama --}}
    <button @click="open = !open" type="button"
            class="flex items-center gap-2 font-bold border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] transition
            {{ $variant === 'primary' 
                ? 'px-4 py-2 bg-[#4A2311] text-white rounded-lg hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[3px] active:translate-y-[3px]' 
                : 'px-3 py-2 bg-amber-100 text-stone-700 rounded-lg hover:bg-amber-200 text-sm' }}">
        {!! $variant === 'primary' ? '<span>🔽</span> Filter' : '🔽 Filter' !!}
    </button>

    {{-- Pop-up Area --}}
    <div x-show="open"
         @click.away="open = false"
         x-transition
         style="display: none;"
         class="absolute top-full mt-2 left-0 z-50 bg-white border-2 border-stone-900 rounded-lg shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] p-4 
         {{ $variant === 'primary' ? 'min-w-[200px]' : 'min-w-[180px]' }}">
        
        <form action="{{ $action }}" method="GET" class="space-y-3">
            @foreach(request()->except(['filter', 'page']) as $key => $value)
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

            @foreach($filters as $filter)
                <div>
                    <label class="block font-bold mb-1 {{ $variant === 'primary' ? 'text-sm' : 'text-xs' }}">
                        {{ $filter['label'] }}
                    </label>
                    <select name="filter[{{ $filter['key'] }}]"
                        class="w-full border-2 border-stone-300 rounded focus:border-[#4A2311] focus:outline-none
                        {{ $variant === 'primary' ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-sm' }}"
                        onchange="this.form.submit()">
                        <option value="">Default</option>
                        @foreach($filter['options'] as $value => $label)
                            <option value="{{ is_string($value) ? $value : $label }}"
                                {{ request('filter.'.$filter['key']) == (is_string($value) ? $value : $label) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </form>
    </div>
</div>