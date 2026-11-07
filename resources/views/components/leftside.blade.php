@props([
    'items' => [],        
    'selectedId' => null,
    'detailRoute' => null, 
    'type' => 'pesanan',   
])

<div class="space-y-3">
    @forelse($items as $item)
        @php
            $isSelected = !is_null($selectedId) && (string)$selectedId === (string)$item->id;
            $routeParams = array_merge(request()->query(), ['id' => $item->id]);
            $waktu = ($item->created_at ? \Carbon\Carbon::parse($item->created_at)->diffForHumans() : '-');
            
            $mejaLabel = $type === 'pesanan' 
                ? ($item->id_meja ?? '-') 
                : ($item->customer->nama ?? 'Unknown Customer'); 
                
            $namaLabel = $type === 'reservasi'
                ? ($item->jam_mulai ? \Carbon\Carbon::parse($item->tanggal)->format('d M Y') . ' (' . \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') . ')' : '-')
                : 'Pesanan #' . $item->id;

            $statusRaw = $type === 'pesanan' ? ($item->status ?? '') : ($item->pesanan->status ?? '');
            // $isUnseen = is_null($item->admin_seen_at)
            //     && (
            //         ($type === 'pesanan'
            //             && (
            //                 $statusLower === 'paid'
            //                 || ($statusLower === 'pending'
            //                     && strtolower($item->metode_pembayaran ?? '') === 'tunai')
            //             ))
            //         || ($type === 'reservasi' && $statusLower === 'reserved')
            //     );
            $statusLower = strtolower(trim((string) $statusRaw));
            $loginAt = session('admin_logged_in_at');
            $loginTimestamp = $loginAt
                ? (is_numeric($loginAt)
                    ? (int) $loginAt
                    : \Carbon\Carbon::parse($loginAt)->timestamp)
                : 0;
            $seenKey = $type . '-' . $item->id;

            $activityAt = $type === 'reservasi'
                ? $item->pesanan?->updated_at
                : (in_array($statusLower, ['paid', 'pending'], true)
                    ? $item->updated_at
                    : $item->created_at);

            $isNewSinceLogin = $loginTimestamp > 0
                && $activityAt
                && $activityAt->timestamp >= $loginTimestamp
                && (
                    (
                        $type === 'pesanan'
                        && (
                            (
                                $statusLower === 'pending'
                                && $item->pembayaran?->contains(fn ($p) =>
                                    strtolower(trim((string) $p->jenis)) === 'full'
                                    && strtolower(trim((string) $p->metode)) === 'tunai'
                                    && strtolower(trim((string) $p->status)) === 'pending'
                                )
                            )
                            || (
                                $statusLower === 'paid'
                                && $item->pembayaran?->contains(fn ($p) =>
                                    strtolower(trim((string) $p->jenis)) === 'full'
                                    && strtolower(trim((string) $p->metode)) === 'other_qris'
                                    && strtolower(trim((string) $p->status)) === 'success'
                                )
                            )
                        )
                    )
                    || ($type === 'reservasi' && $statusLower === 'reserved')
                );

            $showNewDot = $isNewSinceLogin && !$isSelected;
        @endphp

        <div
            class="relative {{ $showNewDot ? 'ml-5' : '' }}"
            data-admin-card-wrapper="{{ $seenKey }}"
        >
            @if($showNewDot)
                <span
                    data-admin-new-dot="{{ $seenKey }}"
                    class="absolute -left-4 top-1/2 z-10 h-2.5 w-2.5 -translate-y-1/2 rounded-full bg-red-600"
                    aria-label="Pesanan baru"
                ></span>
            @endif

            <a
                href="{{ $detailRoute ? route($detailRoute, $routeParams) : '#' }}"
                data-admin-seen-key="{{ $seenKey }}"
                class="admin-leftside-card block min-w-0 rounded-lg border-2 p-3 transition {{ $isSelected ? 'bg-[#4A2311] text-white border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,0.3)]' : 'bg-white text-stone-700 border-stone-300 hover:border-[#4A2311] shadow-[2px_2px_0_0_rgba(0,0,0,0.1)]' }}"
            >
                <div class="flex items-center justify-between mb-1">
                    {{-- @if($isUnseen)
                        <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">
                            ●
                        </span>
                    @endif --}}
                    <span class="font-bold">
                        {{ $type === 'pesanan' ? 'Meja: ' . $mejaLabel : $mejaLabel }}
                    </span>
                    <span class="text-xs font-bold {{ $isSelected ? 'text-white' : 'text-stone-500' }}">{{ $waktu }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm truncate pr-2">{{ $namaLabel }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold whitespace-nowrap capitalize
                        {{ in_array($statusLower, ['paid', 'reserved']) ? 'bg-green-100 text-green-700' : '' }}
                        {{ $statusLower === 'processing' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $statusLower === 'confirmed' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $statusLower === 'completed' ? 'bg-blue-200 text-blue-700' : '' }}
                        {{ $statusLower === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $isSelected ? '!bg-white !text-stone-900' : '' }}">
                        {{ $statusRaw }}
                    </span>
                </div>
            </a>
        </div>
    @empty
        <div class="text-center py-8 text-stone-500 font-bold bg-stone-50 rounded-lg border-2 border-dashed border-stone-300">
            Tidak ada data
        </div>
    @endforelse
</div>
@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-admin-seen-key]').forEach((card) => {
        const key = `admin-seen-${card.dataset.adminSeenKey}`;
        const wrapper = card.closest('[data-admin-card-wrapper]');
        const dot = wrapper?.querySelector('[data-admin-new-dot]');

        if (sessionStorage.getItem(key) === '1') {
            dot?.remove();
            wrapper?.classList.remove('ml-5');
        }

        card.addEventListener('click', () => {
            sessionStorage.setItem(key, '1');
        });
    });
});
</script>
@endonce