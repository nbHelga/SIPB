@props([
    'item' => null,   
    'type' => 'pesanan', 
    'actions' => [],  
])

@if(!$item)
    <div class="flex items-center justify-center h-full text-stone-400 font-bold text-lg">
        Pilih data dari daftar di samping
    </div>
@else
    <div class="space-y-6">
        @if(count($actions) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($actions as $action)
                    {!! $action !!}
                @endforeach
            </div>
        @endif

        @if($type === 'pesanan')
            {{-- DETAIL PESANAN SELF ORDER --}}
            <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Detail Pesanan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-stone-500">KODE PESANAN</span>
                        <span class="break-all text-right font-bold">{{ $item->kode_pesanan ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">DIBUAT</span>
                        <span class="text-right font-bold">{{ $item->created_at ? $item->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">STATUS</span>
                        <span class="font-bold px-2 py-0.5 rounded-full text-xs capitalize
                            {{ strtolower($item->status) === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                            {{ strtolower($item->status) === 'processing' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ strtolower($item->status) === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ strtolower($item->status) === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                            {{ $item->status }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">MANAGED BY</span>
                        <span class="font-bold">{{ $item->user->username ?? 'Kasir' }}</span> 
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Produk</h3>
                <div class="space-y-3">
                    @foreach($item->detailPesanan as $detail)
                        @php $menu = $detail->menu; @endphp
                        <div class="flex flex-col border-b border-stone-100 pb-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-stone-500">{{ $menu->kategori ?? '-' }}</span>
                                    <span class="block font-bold">{{ $menu->nama ?? '-' }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold">{{ $detail->jumlah }}x</span>
                                    <span class="text-sm">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            @if(!empty($detail->catatan))
                                <div class="mt-1 bg-amber-50 border-l-2 border-[#8B5A2B] px-2 py-1 text-xs text-amber-800 rounded font-medium">
                                    📝 Catatan: "{{ $detail->catatan }}"
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Catatan Pesanan</h3>
                {{-- @php
                    $totalMenu = $item->total_harga;
                    $pb = $totalMenu * 0.1;
                    $totalHarga = $totalMenu + $pb;
                @endphp --}}
                <div class="space-y-2 text-sm">
                    {{-- <div class="flex justify-between">
                        <span class="text-stone-500">Total harga menu</span>
                        <span>Rp {{ number_format($item->total_harga, 0, ',', '.') }}</span>
                    </div> --}}
                    {{-- <div class="flex justify-between">
                        <span class="text-stone-500">PB 10%</span>
                        <span>Rp {{ number_format($item->pb, 0, ',', '.') }}</span>
                    </div> --}}
                    <div class="flex justify-between font-bold text-lg pt-2">
                        <span>Total Harga</span>
                        <span class="text-[#4A2311]">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        @else
            {{-- DETAIL RESERVASI --}}
            <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Detail Pesanan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-stone-500">KODE PESANAN</span>
                        <span class="break-all text-right font-bold">{{ $item->pesanan->kode_pesanan ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">DIBUAT</span>
                        <span class="text-right font-bold">{{ optional($item->pesanan)->created_at ? $item->pesanan->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">STATUS</span>
                        <span class="font-bold px-2 py-0.5 rounded-full text-xs capitalize
                            {{ strtolower(optional($item->pesanan)->status) === 'confirmed' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{-- {{ strtolower(optional($item->pesanan)->status) === 'processing' ? 'bg-yellow-100 text-yellow-700' : '' }} --}}
                            {{ strtolower(optional($item->pesanan)->status) === 'completed' ? 'bg-blue-200 text-blue-700' : '' }}
                            {{ strtolower(optional($item->pesanan)->status) === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                            {{ strtolower(optional($item->pesanan)->status) === 'reserved' ? 'bg-green-100 text-green-700' : '' }}">
                            {{ optional($item->pesanan)->status ?? '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">MANAGED BY</span>
                        <span class="font-bold">{{ optional(optional($item->pesanan)->user)->username ?? 'Kasir' }}</span> 
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Detail Reservasi</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between border-b border-stone-100 pb-1">
                        <span class="text-stone-500">Customer</span>
                        <span class="font-bold">{{ $item->customer->nama ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-100 pb-1">
                        <span class="text-stone-500">No. Telp</span>
                        <span class="font-bold">{{ $item->customer->nomor_telp ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-100 pb-1">
                        <span class="text-stone-500">Tanggal & Waktu</span>
                        <span class="font-bold">
                            {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d M Y') : '-' }} |
                            {{ $item->jam_mulai ? \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') : '-' }}–{{ $item->jam_selesai ? \Carbon\Carbon::parse($item->jam_selesai)->format('H:i') : '-' }} WIB
                        </span>
                    </div>
                    <div class="flex justify-between border-b border-stone-100 pb-1">
                        <span class="text-stone-500">Kapasitas</span>
                        <span class="font-bold">{{ $item->jml_org }} orang</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-100 pb-1">
                        <span class="text-stone-500">Meja Diplot</span>
                        <span class="font-bold text-[#4A2311]">
                            {{ $item->detailReservasi->pluck('meja.nomor_meja')->filter()->implode(', ') ?: 'Belum Di-assign' }}
                        </span>
                    </div>
                </div>

                @if(!empty($item->keterangan))
                    <div class="mt-4 bg-amber-50 border-l-4 border-amber-600 p-3 text-sm rounded">
                        <span class="block font-bold text-amber-800 mb-1">📋 Catatan Acara:</span>
                        <p class="text-amber-900 italic">"{{ $item->keterangan }}"</p>
                    </div>
                @endif
            </div>

            {{-- PRE-ORDER MENU RESERVASI --}}
            @if(optional($item->pesanan)->detailPesanan && $item->pesanan->detailPesanan->count() > 0)
                <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                    <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Pre-order Menu</h3>
                    {{-- @php
                        $menuDetails = $item->pesanan->detailPesanan;
                        $totalSub = $item->pesanan->total_harga;
                        $pb = $totalSub * 0.1;
                        $totalHarga = $totalSub + $pb;
                    @endphp --}}

                    <div class="space-y-3 mb-4">
                        @foreach($item->pesanan->detailPesanan as $detail)
                            @php
                                $menu = $detail->menu;
                            @endphp
                            <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                                <div>
                                    <span class="text-xs text-stone-500">{{ $menu->kategori ?? '-' }}</span>
                                    <span class="block font-bold">{{ $menu->nama ?? '-' }}</span>
                                    @if(!empty($detail->catatan))
                                        <div class="mt-1 text-xs italic text-amber-700">💬 {{ $detail->catatan }}</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold">{{ $detail->jumlah }}x</span>
                                    <span class="text-sm">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 text-sm border-t border-stone-200 pt-3">
                        {{-- <div class="flex justify-between">
                            <span class="text-stone-500">Total harga menu</span>
                            <span>Rp {{ number_format($item->pesanan->total_harga, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500">PB 10%</span>
                            <span>Rp {{ number_format($item->pesanan->pb, 0, ',', '.') }}</span>
                        </div> --}}
                        <div class="flex justify-between font-bold text-lg border-t-2 border-stone-900 pt-2">
                            <span>Total Harga</span>
                            <span class="text-[#4A2311]">Rp {{ number_format($item->pesanan->total_harga, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg border-2 border-stone-300 shadow-[3px_3px_0_0_rgba(0,0,0,0.15)] p-4">
                    <h3 class="font-bold text-lg mb-3 text-stone-800 border-b-2 border-[#4A2311] pb-2">Pre-order Menu</h3>
                    <p class="text-sm text-stone-500 italic">Tidak ada pre-order menu untuk reservasi ini.</p>
                </div>
            @endif
        @endif
    </div>
@endif