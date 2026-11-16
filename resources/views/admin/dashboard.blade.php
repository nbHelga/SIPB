@extends('layouts.admin')

@section('title', 'Dashboard Analytics')

@section('content')
<div class="min-w-0 space-y-5 bg-stone-100 p-3 sm:p-4 lg:space-y-6 lg:p-6">
    
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-black text-stone-900 sm:text-2xl">
            Dashboard {{ $isKasir ? 'Kasir' : 'Admin' }}
        </h1>
    </div>

    <!-- 4 KOTAK METRIK -->
    <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
        <!-- Pendapatan Cash -->
        <div class="min-w-0 overflow-hidden rounded-xl border-4 border-stone-900 bg-white p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] sm:p-5">
            <h3 class="text-xs font-bold text-stone-500 uppercase">Pendapatan Cash</h3>
            <div class="text-2xl font-black text-stone-900 mt-1">Rp {{ number_format($metrik['cash']['selected'], 0, ',', '.') }}</div>
            <div class="text-xs font-bold mt-2 {{ $percentage['cash'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $percentage['cash'] >= 0 ? '▲' : '▼' }} {{ abs($percentage['cash']) }}% vs Hari Sebelumnya
            </div>
        </div>

        <!-- Pendapatan Non-Cash -->
        <div class="min-w-0 overflow-hidden rounded-xl border-4 border-stone-900 bg-white p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] sm:p-5">
            <h3 class="text-xs font-bold text-stone-500 uppercase">Pendapatan Non-Cash</h3>
            <div class="text-2xl font-black text-stone-900 mt-1">Rp {{ number_format($metrik['non_cash']['selected'], 0, ',', '.') }}</div>
            <div class="text-xs font-bold mt-2 {{ $percentage['non_cash'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $percentage['non_cash'] >= 0 ? '▲' : '▼' }} {{ abs($percentage['non_cash']) }}% vs Hari Sebelumnya
            </div>
        </div>

        <!-- Total Pesanan -->
        <div class="min-w-0 overflow-hidden rounded-xl border-4 border-stone-900 bg-white p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] sm:p-5">
            <h3 class="text-xs font-bold text-stone-500 uppercase">Pesanan Self-Order</h3>
            <div class="text-2xl font-black text-stone-900 mt-1">{{ $metrik['total_order']['selected'] }} <span class="text-sm">Transaksi</span></div>
            <div class="text-xs font-bold mt-2 {{ $percentage['total_order'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $percentage['total_order'] >= 0 ? '▲' : '▼' }} {{ abs($percentage['total_order']) }}% vs Hari Sebelumnya
            </div>
        </div>

        <!-- Total Kunjungan -->
        <div class="min-w-0 overflow-hidden rounded-xl border-4 border-stone-900 bg-white p-4 shadow-[6px_6px_0_0_rgba(0,0,0,1)] sm:p-5">
            <h3 class="text-xs font-bold text-stone-500 uppercase">Total Kunjungan</h3>
            <div class="text-2xl font-black text-stone-900 mt-1">{{ $metrik['visitors']['selected'] }} <span class="text-sm">Pax (Est)</span></div>
            <div class="text-xs font-bold mt-2 {{ $percentage['visitors'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $percentage['visitors'] >= 0 ? '▲' : '▼' }} {{ abs($percentage['visitors']) }}% vs Hari Sebelumnya
            </div>
        </div>
    </div>

    @if($isKasir)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('admin.selforder') }}" class="flex items-center justify-between rounded-xl border-4 border-stone-900 bg-[#4A2311] p-5 shadow-[6px_6px_0_0_rgba(0,0,0,1)] transition-transform hover:-translate-y-1 hover:shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div>
                    <h3 class="text-lg font-black text-white">Kelola Self-Order</h3>
                    <p class="text-xs font-medium text-stone-300 mt-1">Terima pembayaran & proses pesanan meja</p>
                </div>
                <span class="text-3xl">🛎️</span>
            </a>
            <a href="{{ route('admin.reservasi') }}" class="flex items-center justify-between rounded-xl border-4 border-stone-900 bg-white p-5 shadow-[6px_6px_0_0_rgba(0,0,0,1)] transition-transform hover:-translate-y-1 hover:shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div>
                    <h3 class="text-lg font-black text-stone-900">Daftar Reservasi</h3>
                    <p class="text-xs font-medium text-stone-500 mt-1">Pantau tamu reservasi hari ini</p>
                </div>
                <span class="text-3xl">📅</span>
            </a>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border-4 border-stone-900 bg-white shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
        <div class="border-b-4 border-stone-900 bg-[#4A2311] p-4 sm:p-5 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-white">Pesanan Terakhir Hari Ini</h2>
                <p class="mt-1 text-xs font-medium text-stone-300">10 transaksi terbaru (Real-time)</p>
            </div>
        </div>
        <div class="flex flex-col p-3 sm:p-4 gap-3 max-h-[400px] overflow-y-auto scrollbar-thin scrollbar-thumb-stone-400">
            @forelse($recentOrders as $order)
                @php 
                    $isReservation = (bool) $order->reservasi; 
                    $statusColor = match(strtolower($order->status)) {
                        'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                        'processing' => 'bg-blue-100 text-blue-800 border-blue-300',
                        'paid' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'pending' => 'bg-amber-100 text-amber-800 border-amber-300',
                        'cancelled' => 'bg-red-100 text-red-800 border-red-300',
                        default => 'bg-stone-100 text-stone-800 border-stone-300'
                    };
                @endphp
                <div class="flex items-center justify-between rounded-lg border-2 border-stone-200 bg-stone-50 p-3 transition-colors hover:border-stone-400">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-stone-900 bg-white text-lg">
                            {{ $isReservation ? '📅' : '🛎️' }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <span class="font-black text-stone-900">{{ $order->kode_pesanan }}</span>
                                <span class="rounded-md border border-stone-300 bg-stone-100 px-1.5 py-0.5 text-[10px] font-bold text-stone-600">
                                    {{ $isReservation ? 'Reservasi' : 'Self-Order' }}
                                </span>
                                <span class="rounded-md border {{ $statusColor }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                    {{ $order->status }}
                                </span>
                            </div>
                            <p class="text-xs font-bold text-stone-500 mt-0.5">
                                {{ $order->created_at?->copy()->timezone('Asia/Jakarta')->format('H.i') ?? '-' }} WIB • 
                                {{ $isReservation ? ($order->reservasi->customer->nama ?? 'Reservasi') : ('Meja ' . ($order->meja->nomor_meja ?? '-')) }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-sm font-black text-[#8B5A2B]">Rp {{ number_format((float) $order->total_harga, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <span class="text-4xl" aria-hidden="true">📭</span>
                    <p class="mt-3 text-sm font-bold text-stone-500">Belum ada transaksi hari ini.</p>
                </div>
            @endforelse
        </div>
    </section>


    @unless($isKasir)
        <div class="mt-5 grid min-w-0 grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4 lg:col-span-2">
                <h3 class="mb-2 font-bold">Tren Pendapatan</h3>
                <div id="chartRevenue" class="min-w-0"></div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4">
                <h3 class="mb-2 font-bold">Penjualan</h3>
                <div id="chartVolume" class="min-w-0"></div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4">
                <h3 class="mb-2 font-bold">Jam Sibuk (Peak Hours)</h3>
                <div id="chartPeak" class="min-w-0"></div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4">
                <h3 class="mb-2 font-bold">Top 5 Menu Bulan Ini</h3>
                @if($topMenus->isNotEmpty())
                    <div id="chartTopMenu" class="min-w-0"></div>
                @else
                    <div class="flex h-[300px] items-center justify-center rounded-lg bg-stone-50 p-4 text-center text-sm font-bold text-stone-500">
                        Belum ada menu terjual dari pesanan selesai bulan ini.
                    </div>
                @endif
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4">
                <h3 class="mb-2 font-bold">Metode Pembayaran</h3>
                <div id="chartPayment" class="min-w-0"></div>
            </div>
        </div>
    @endunless
    <!-- Kartu Pratinjau QR Code Meja di Admin -->
    {{-- <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
        @foreach($mejas as $meja)
            @php
                // Generate URL dinamis berbasis nama route Laravel
                $targetUrl = route('customer.selforder', ['tableNumber' => $meja->nomor_meja]);
            @endphp

            <div class="flex flex-col items-center p-4 bg-white border-2 border-stone-900 rounded-xl shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                <span class="font-black text-lg text-stone-900 mb-2">Meja {{ $meja->nomor_meja }}</span>
                
                <!-- Render QR Code -->
                <div class="p-2 bg-white rounded-lg border border-stone-300">
                    {!! QrCode::size(150)->generate($targetUrl) !!}
                </div>

                <p class="text-xs text-stone-500 mt-2 font-mono text-center break-all">
                    {{ $targetUrl }}
                </p>
            </div>
        @endforeach
    </div> --}}
</div>

@unless($isKasir)
<script>
    // Data dari Controller
    const revData = @json($revenueTrend);
    const peakData = @json($peakHours);
    const payData = @json($paymentMethods);
    const volData = @json($orderVolume);
    const topData = @json($topMenus);

    // 1. Stacked Bar: Revenue Cash vs Non-Cash
    new ApexCharts(document.querySelector("#chartVolume"), {
        chart: { type: 'line', height: 300, toolbar: { show: false } },
        series: [
            { name: 'Self Order', data: volData.map(v => v.self_order) },
            { name: 'Reservasi', data: volData.map(v => v.reservasi) }
        ],
        xaxis: { categories: volData.map(v => v.date) },
        colors: ['#8B5A2B', '#4A2311']
    }).render();

    // 2. Vertical Bar: Peak Hours
    new ApexCharts(document.querySelector("#chartPeak"), {
        chart: { type: 'line', height: 300, toolbar: { show: false } },
        series: [{ name: 'Total Order', data: peakData.map(p => p.total_order) }],
        xaxis: { categories: peakData.map(p => p.hour + ':00') },
        colors: ['#F59E0B']
    }).render();

    // 3. Donut Chart: Payment Methods
    new ApexCharts(document.querySelector("#chartPayment"), {
        chart: { type: 'donut', height: 300 },
        series: payData.map(p => Number(p.total)), // Memastikan nilai total dibaca sebagai angka
        labels: payData.map(p => p.metode ? p.metode.toUpperCase() : 'LAINNYA'), // Menggunakan field 'metode' dengan fallback
        colors: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5A2B', '#6B7280']
    }).render();

    // 4. Dual-Line: Order Volume
    new ApexCharts(document.querySelector("#chartRevenue"), {
        chart: { type: 'line', height: 300, toolbar: { show: false } },
        stroke: { curve: 'smooth', width: 6 },
        series: [
            { name: 'Tunai', data: revData.map(r => r.cash) },
            { name: 'Non-Tunai', data: revData.map(r => r.non_cash) }
        ],
        xaxis: { categories: revData.map(r => r.date) },
        colors: ['#10B981', '#3B82F6']
    }).render();

    // 5. Horizontal Bar: Top Menus
    const topMenuElement = document.querySelector("#chartTopMenu");
    if (topMenuElement && topData.length) new ApexCharts(topMenuElement, {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        series: [{ name: 'Porsi Terjual', data: topData.map(t => t.total_porsi) }],
        xaxis: { categories: topData.map(t => t.nama) },
        colors: ['#EF4444']
    }).render();
</script>
@endunless
{{-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}
{{-- <script>
    const topMenuLabels = {!! json_encode($topMenus->pluck('nama')) !!};
    const topMenuData = {!! json_encode($topMenus->pluck('total_porsi')) !!};
    new Chart(document.getElementById('topMenuChart'), {
        type: 'bar',
        data: {
            labels: topMenuLabels,
            datasets: [{
                label: 'Porsi Terjual',
                data: topMenuData,
                backgroundColor: '#059669', // Emerald
                borderColor: '#1c1917',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y', // Horizontal Bar Chart
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script> --}}
@endsection
