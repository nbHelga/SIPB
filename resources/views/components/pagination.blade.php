@props([
    'paginator' => null,
    'maxVisible' => 5,
])

@if($paginator && $paginator->hasPages())
    <div class="flex items-center justify-center gap-2 mt-4">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="px-3 py-2 bg-stone-200 text-stone-400 rounded border-2 border-stone-300 font-bold text-sm cursor-not-allowed">◀</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="px-3 py-2 bg-white text-stone-700 rounded border-2 border-stone-900 font-bold text-sm hover:bg-[#4A2311] hover:text-white transition shadow-[2px_2px_0_0_rgba(0,0,0,0.2)]">◀</a>
        @endif

        {{-- Page Numbers --}}
        @php
            $total = $paginator->lastPage();
            $current = $paginator->currentPage();
            $start = max(1, $current - floor($maxVisible / 2));
            $end = min($total, $start + $maxVisible - 1);
            if ($end - $start + 1 < $maxVisible) {
                $start = max(1, $end - $maxVisible + 1);
            }
        @endphp

        @if($start > 1)
            <a href="{{ $paginator->url(1) }}" class="px-3 py-2 bg-white text-stone-700 rounded border-2 border-stone-300 font-bold text-sm hover:bg-[#4A2311] hover:text-white transition">1</a>
            @if($start > 2)
                <span class="px-2 text-stone-500 font-bold">...</span>
            @endif
        @endif

        @for($i = $start; $i <= $end; $i++)
            @if($i == $current)
                <span class="px-3 py-2 bg-[#4A2311] text-white rounded border-2 border-stone-900 font-bold text-sm shadow-[2px_2px_0_0_rgba(0,0,0,0.3)]">{{ $i }}</span>
            @else
                <a href="{{ $paginator->url($i) }}"
                   class="px-3 py-2 bg-white text-stone-700 rounded border-2 border-stone-300 font-bold text-sm hover:bg-[#4A2311] hover:text-white transition">{{ $i }}</a>
            @endif
        @endfor

        @if($end < $total)
            @if($end < $total - 1)
                <span class="px-2 text-stone-500 font-bold">...</span>
            @endif
            <a href="{{ $paginator->url($total) }}" class="px-3 py-2 bg-white text-stone-700 rounded border-2 border-stone-300 font-bold text-sm hover:bg-[#4A2311] hover:text-white transition">{{ $total }}</a>
        @endif

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="px-3 py-2 bg-white text-stone-700 rounded border-2 border-stone-900 font-bold text-sm hover:bg-[#4A2311] hover:text-white transition shadow-[2px_2px_0_0_rgba(0,0,0,0.2)]">▶</a>
        @else
            <span class="px-3 py-2 bg-stone-200 text-stone-400 rounded border-2 border-stone-300 font-bold text-sm cursor-not-allowed">▶</span>
        @endif
    </div>
@endif
