@extends('layouts.admin')

@section('title', 'Dashboard Analytics')

@section('content')
<div class="min-w-0 space-y-5 bg-stone-100 p-3 sm:p-4 lg:space-y-6 lg:p-6">
    
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-black text-stone-900 sm:text-2xl">
            Dashboard {{ $isKasir ? 'Kasir' : 'Admin' }}
        </h1>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
                <label for="dashboard-tanggal" class="text-sm font-bold">Tanggal rekap</label>
                <input
                    id="dashboard-tanggal"
                    type="date"
                    name="tanggal"
                    value="{{ $filterTanggal }}"
                    class="rounded-lg border-2 border-stone-900 px-3 py-2 text-sm"
                >
                <button
                    type="submit"
                    class="rounded-lg border-2 border-stone-900 bg-[#4A2311] px-3 py-2 text-sm font-bold text-white"
                >
                    Terapkan
                </button>
            </form>

            <a
                href="{{ route('admin.dashboard.report', ['tanggal' => $filterTanggal]) }}"
                target="_blank"
                rel="noopener"
                class="rounded-lg border-2 border-stone-900 bg-emerald-700 px-3 py-2 text-sm font-bold text-white"
            >
                Cetak Rekap
            </a>
        </div>
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

    {{-- <!-- GRAFIK BARIS 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Grafik Penjualan (Daily) -->
        <div class="lg:col-span-2 bg-white p-5 rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
            <h3 class="font-black text-stone-900 mb-4">Tren Pendapatan Harian (7 Hari Terakhir)</h3>
            <canvas id="salesChart" height="100"></canvas>
        </div>

        <!-- Penjualan per Kategori -->
        <div class="bg-white p-5 rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)] flex flex-col">
            <h3 class="font-black text-stone-900 mb-4">Penjualan per Kategori ({{ \Carbon\Carbon::parse($filterTanggal)->format('d M') }})</h3>
            <div class="flex-1 relative w-full flex justify-center items-center">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- GRAFIK BARIS 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Metode Pembayaran -->
        <div class="bg-white p-5 rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
            <h3 class="font-black text-stone-900 mb-4">Cara Pembayaran ({{ \Carbon\Carbon::parse($filterTanggal)->format('d M') }})</h3>
            <canvas id="paymentChart"></canvas>
            
            <div class="mt-4 space-y-2">
                @foreach($paymentMethods as $method => $total)
                    <div class="flex justify-between text-xs font-bold">
                        <span class="capitalize">{{ str_replace('_', ' ', $method) }}</span>
                        <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top 5 Menus & Tabel Rincian -->
        <div class="lg:col-span-2 bg-white p-5 rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
            <h3 class="font-black text-stone-900 mb-4">5 Menu Terlaris ({{ \Carbon\Carbon::parse($filterTanggal)->format('d M') }})</h3>
            <canvas id="topMenuChart" height="80"></canvas>

            <h3 class="font-black text-stone-900 mt-8 mb-4 border-t-2 border-dashed border-stone-300 pt-4">Rincian Total Porsi Menu Terjual</h3>
            <div class="max-h-[300px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-stone-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#4A2311] text-white font-bold sticky top-0">
                        <tr>
                            <th class="p-3 rounded-tl-lg">Nama Menu</th>
                            <th class="p-3">Kategori</th>
                            <th class="p-3 text-center rounded-tr-lg">Porsi Terjual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menuPortions as $menu)
                        <tr class="border-b border-stone-200 hover:bg-amber-50 font-medium">
                            <td class="p-3">{{ $menu->nama }}</td>
                            <td class="p-3">{{ $menu->kategori }}</td>
                            <td class="p-3 text-center font-black text-[#4A2311] bg-stone-100">{{ $menu->total_porsi }}</td>
                        </tr>
                        @endforeach
                        @if($menuPortions->isEmpty())
                        <tr>
                            <td colspan="3" class="p-4 text-center text-stone-500 font-bold italic">Belum ada data penjualan pada tanggal ini.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- CHART.JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. CHART TREN PENJUALAN
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($salesChart['labels']) !!},
            datasets: [{
                label: 'Total Pendapatan (Rp)',
                data: {!! json_encode($salesChart['data']) !!},
                borderColor: '#4A2311',
                backgroundColor: 'rgba(74, 35, 17, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // 2. CHART KATEGORI
    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($categorySales)) !!},
            datasets: [{
                data: {!! json_encode(array_values($categorySales)) !!},
                backgroundColor: ['#e11d48', '#059669', '#d97706', '#2563eb', '#7c3aed'],
                borderWidth: 2,
                borderColor: '#1c1917' // stone-900
            }]
        },
        options: { responsive: true }
    });

    // 3. CHART METODE PEMBAYARAN
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($paymentMethods)) !!},
            datasets: [{
                data: {!! json_encode(array_values($paymentMethods)) !!},
                backgroundColor: ['#fbbf24', '#34d399', '#60a5fa', '#a78bfa', '#f472b6'],
                borderWidth: 2,
                borderColor: '#1c1917',
                cutout: '60%'
            }]
        },
        options: { responsive: true }
    });

    // 4. CHART TOP 5 MENU
    const topMenuLabels = {!! json_encode($top5Menus->pluck('nama')) !!};
    const topMenuData = {!! json_encode($top5Menus->pluck('total_porsi')) !!};
    
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
                <h3 class="mb-2 font-bold">Top 5 Menu</h3>
                <div id="chartTopMenu" class="min-w-0"></div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl border border-stone-300 bg-white p-3 shadow sm:p-4">
                <h3 class="mb-2 font-bold">Metode Pembayaran</h3>
                <div id="chartPayment" class="min-w-0"></div>
            </div>
        </div>
    @endunless
     <!-- Top 5 Menus & Tabel Rincian -->
    <div class="lg:col-span-2 bg-white p-5 rounded-xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
        {{-- <h3 class="font-black text-stone-900 mb-4">5 Menu Terlaris ({{ \Carbon\Carbon::parse($filterTanggal)->format('d M') }})</h3> --}}
        {{-- <canvas id="topMenuChart" height="80"></canvas> --}}

        {{-- <h3 class="font-black text-stone-900 mt-8 mb-4 border-t-2 border-dashed border-stone-300 pt-4">Rincian Total Porsi Menu Terjual</h3> --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-black text-stone-900">Rincian Total Porsi Menu Terjual</h3>

            {{-- <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <label for="tanggal" class="text-sm font-bold">Tanggal</label>
                <input
                    id="tanggal"
                    type="date"
                    name="tanggal"
                    value="{{ $filterTanggal }}"
                    class="rounded-lg border-2 border-stone-900 px-3 py-2 text-sm"
                >
                <button type="submit"
                        class="rounded-lg border-2 border-stone-900 bg-[#4A2311] px-3 py-2 text-sm font-bold text-white">
                    Filter
                </button>
            </form> --}}
        </div>
        <div class="max-h-[300px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-stone-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#4A2311] text-white font-bold sticky top-0">
                    <tr>
                        <th class="p-3 rounded-tl-lg">Nama Menu</th>
                        <th class="p-3">Kategori</th>
                        <th class="p-3 text-center rounded-tr-lg">Porsi Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($menuPortions as $menu)
                    <tr class="border-b border-stone-200 hover:bg-amber-50 font-medium">
                        <td class="p-3">{{ $menu->nama }}</td>
                        <td class="p-3">{{ $menu->kategori }}</td>
                        <td class="p-3 text-center font-black text-[#4A2311] bg-stone-100">{{ $menu->total_porsi }}</td>
                    </tr>
                    @endforeach
                    @if($menuPortions->isEmpty())
                    <tr>
                        <td colspan="3" class="p-4 text-center text-stone-500 font-bold italic">Belum ada data penjualan pada tanggal ini.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $menuPortions->links() }}
        </div>
    </div>
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
    new ApexCharts(document.querySelector("#chartTopMenu"), {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        series: [{ name: 'Porsi Terjual', data: topData.map(t => t.total_porsi) }],
        xaxis: { categories: topData.map(t => t.nama) },
        colors: ['#EF4444']
    }).render();
</script>
{{-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const topMenuLabels = {!! json_encode($top5Menus->pluck('nama')) !!};
    const topMenuData = {!! json_encode($top5Menus->pluck('total_porsi')) !!};
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
    }); --}}
</script>
@endsection