@props([
    'headers' => [],       // [['label' => 'Nama', 'value' => 'nama', 'detail' => true, 'render' => null], ...]
    'data' => [],          // collection or array
    'detailRoute' => null, // route name for detail link
    'detailParam' => 'id', // parameter name for detail route
    'emptyMessage' => 'Data tidak ditemukan',
])

<div class="overflow-x-auto rounded-lg border-2 border-stone-300 shadow-[4px_4px_0_0_rgba(0,0,0,0.2)] bg-white">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-amber-100 border-b-2 border-stone-300">
                @foreach($headers as $header)
                    <th class="px-4 py-3 text-left font-bold text-stone-700 {{ $header['class'] ?? '' }}">
                        {{ $header['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr class="border-b border-stone-200 hover:bg-stone-50 transition cursor-pointer">
                    @foreach($headers as $header)
                        <td class="px-4 py-3 {{ $header['class'] ?? '' }}">
                            @if(isset($header['detail']) && $header['detail'] && $detailRoute)
                                <a href="{{ route($detailRoute, [$detailParam => $row->{$header['value']} ?? $row[$header['value']]]) }}"
                                   class="text-[#8B5A2B] font-bold hover:underline">
                                    {{ $row->{$header['value']} ?? $row[$header['value']] ?? '-' }}
                                </a>
                            @elseif(isset($header['render']))
                                {!! $header['render']($row) !!}
                            @else
                                {{ $row->{$header['value']} ?? $row[$header['value']] ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-stone-500 font-bold">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
