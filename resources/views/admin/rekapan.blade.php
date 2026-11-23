@extends('layouts.admin')

@section('title', 'Rekapan Menu Terjual - SIPB')

@section('content')
<div class="rekapan-content space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-stone-900">Rekapan Menu Terjual</h1>
            <p class="mt-1 text-sm text-stone-500">Pesanan selesai pada {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d M Y') }}</p>
        </div>
        <a class="rounded-lg border-2 border-stone-900 bg-emerald-700 px-4 py-2 font-bold text-white hover:bg-emerald-800"
           href="{{ route('admin.cetak', ['tanggal' => $tanggal]) }}" target="_blank" rel="noopener">
            Cetak Rekap
        </a>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <article class="rounded-xl border-2 border-stone-900 bg-white p-4 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
            <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Total porsi menu terjual</p>
            <p class="mt-1 text-3xl font-black text-[#4A2311]">{{ number_format((int) $summary->total_porsi, 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border-2 border-stone-900 bg-white p-4 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
            <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Porsi Self-Order</p>
            <p class="mt-1 text-3xl font-black text-[#4A2311]">{{ number_format((int) $summary->porsi_selforder, 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border-2 border-stone-900 bg-white p-4 shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
            <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Porsi Reservasi</p>
            <p class="mt-1 text-3xl font-black text-[#4A2311]">{{ number_format((int) $summary->porsi_reservasi, 0, ',', '.') }}</p>
        </article>
    </div>

    <form method="GET" action="{{ route('admin.rekapan') }}" class="flex flex-col gap-3 rounded-xl border-2 border-stone-900 bg-white p-4 sm:flex-row sm:items-end">
        <label class="block text-sm font-bold">Tanggal selesai
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="mt-1 block rounded-lg border-2 border-stone-300 px-3 py-2">
        </label>
        <label class="block flex-1 text-sm font-bold">Cari kode pesanan, menu, atau pengelola
            <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Ketik kata kunci..." class="mt-1 block w-full rounded-lg border-2 border-stone-300 px-3 py-2">
        </label>
        <button type="submit" class="rounded-lg border-2 border-stone-900 bg-[#4A2311] px-5 py-2 font-bold text-white">Terapkan</button>
    </form>

    <div class="overflow-hidden rounded-xl border-2 border-stone-900 bg-white shadow-[4px_4px_0_0_rgba(0,0,0,0.15)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-[#4A2311] text-white">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Nomor Pesanan</th>
                        <th class="p-3">Nama Menu</th>
                        <th class="p-3 text-center">Jumlah</th>
                        <th class="p-3">Dikelola</th>
                        <th class="p-3 text-right">Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapan as $row)
                        <tr class="border-t border-stone-200 odd:bg-white even:bg-stone-50">
                            <td class="whitespace-nowrap p-3">{{ \Carbon\Carbon::parse($row->created_at)->timezone('Asia/Jakarta')->format('H.i') }}</td>
                            <td class="p-3 font-bold">{{ $row->kode_pesanan }}</td>
                            <td class="p-3">{{ $row->nama_menu }}</td>
                            <td class="p-3 text-center font-bold">{{ $row->jumlah }}</td>
                            <td class="p-3">{{ $row->pengelola ?: 'Belum dikelola' }}</td>
                            <td class="whitespace-nowrap p-3 text-right">Rp {{ number_format((float) $row->total_harga, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center font-bold text-stone-500">Belum ada menu terjual pada tanggal dan pencarian ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($rekapan instanceof \Illuminate\Pagination\AbstractPaginator)
        <div>{{ $rekapan->links() }}</div>
    @endif
</div>

@endsection
