@props([
    'filters' => [], // [['key' => 'status', 'label' => 'Status', 'options' => [...]], ...]
    'action' => '',
])

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open"
            class="flex items-center gap-2 px-3 py-2 bg-amber-100 text-stone-700 font-bold rounded-lg border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.2)] hover:bg-amber-200 transition text-sm">
        🔽 Filter
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition
         class="absolute top-full mt-2 left-0 z-50 bg-white border-2 border-stone-900 rounded-lg shadow-[4px_4px_0_0_rgba(0,0,0,0.3)] p-4 min-w-[180px]">
        <form action="{{ $action }}" method="GET" class="space-y-3">
            @foreach($filters as $filter)
                <div>
                    <label class="block text-xs font-bold mb-1">{{ $filter['label'] }}</label>
                    <select name="filter[{{ $filter['key'] }}]"
                            class="w-full border-2 border-stone-300 rounded px-2 py-1 text-sm focus:border-[#4A2311] focus:outline-none"
                            onchange="this.form.submit()">
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
</div>
