@php
    $formatWib = function ($value) {
        if (!$value) {
            return '-';
        }

        $date = $value instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($value)
            : \Illuminate\Support\Carbon::parse($value, config('app.timezone', 'UTC'));

        return $date->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penjualan Harian - {{ $tanggal }}</title>
    <style>
        /* SETUP KHUSUS PRINTER THERMAL (58mm / 80mm) */
        @page { margin: 0; size: auto; }
        body {
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .receipt-container {
            width: 80mm;
            max-width: 300px; 
            margin: 0 auto;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-solid {
            border-bottom: 1px solid #000;
            margin: 6px 0;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        /* CSS Baru untuk Merapikan Informasi Header */
        .info-row {
            display: flex;
            margin-bottom: 2px;
        }
        .info-label {
            width: 95px; /* Mengunci lebar teks label */
        }
        .info-colon {
            width: 15px; /* Mengunci lebar titik dua */
        }
        .info-value {
            flex: 1;
        }

        @media print {
            .no-print { display: none !important; }
        }

        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #4A2311;
            color: white;
            border: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
        <!-- Tombol Cetak Manual -->
        <button class="btn-print no-print" onclick="window.print()">🖨️ CETAK SEKARANG</button>

        <!-- HEADER INFORMASI -->
        <div class="text-center">
            <h2 style="margin: 0; font-size: 16px;">BIGBANG EATERY</h2>
            <p style="margin: 0; font-size: 12px;">Jl. Mulawarman Raya No.3, Pedalangan<br>
            Banyumanik, Kota Semarang</p>
        </div>
        
        <div class="divider-solid"></div>
        <div class="text-center font-bold" style="font-size: 14px;">
            REKAP PENJUALAN HARIAN
        </div>
        <div class="divider-solid"></div>

        <!-- INFORMASI HEADER YANG DIRAPIKAN -->
        <div class="info-row">
            <span class="info-label">Tgl. Laporan</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Waktu Cetak</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ $formatWib(now()) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Kasir</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ auth()->user()->username ?? 'Admin' }}</span>
        </div>

        <div class="divider-solid"></div>

        <!-- 1. RINCIAN MENU TERJUAL -->
        <div class="font-bold" style="margin-bottom: 5px;">1. RINCIAN MENU TERJUAL</div>
        
        @forelse($menuTerjual as $menu)
            <div style="margin-bottom: 6px;">
                <div class="flex-between font-bold">
                    <span style="max-width: 65%; word-wrap: break-word;">{{ $menu->nama }}</span>
                    <span>{{ number_format($menu->total_subtotal, 0, ',', '.') }}</span>
                </div>
                <div>({{$menu->total_porsi }})</div>
            </div>
        @empty
            <div class="text-center" style="font-style: italic;">Tidak ada menu terjual</div>
        @endforelse

        <div class="divider"></div>
        <div class="flex-between font-bold" style="font-size: 13px;">
            <span>TOTAL PENJUALAN</span>
            <span>Rp {{ number_format($totalPenjualanMenu, 0, ',', '.') }}</span>
        </div>

        <div class="divider-solid" style="margin-top: 15px;"></div>

        <!-- 2. RINCIAN PENDAPATAN -->
        <div class="font-bold" style="margin-bottom: 5px;">2. RINCIAN PENDAPATAN</div>

        @forelse($pendapatan->reject(fn ($p) => strtolower(trim((string) $p->metode)) === 'tunai') as $p)
            <div class="flex-between" style="margin-bottom: 4px;">
                <span style="text-transform: uppercase;">{{ str_replace('_', ' ', $p->metode) }}</span>
                <span>{{ number_format($p->total_nominal, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="text-center" style="font-style: italic;">Tidak ada pendapatan non-tunai</div>
        @endforelse

        {{-- <div class="divider"></div> --}}
        

        <div class="divider" style="margin-top: 15px;"></div>

        <!-- RINGKASAN SETORAN KASIR -->
        <div class="flex-between">
            <span>NON-TUNAI (Sistem)</span>
            <span>Rp {{ number_format($totalNonTunai, 0, ',', '.') }}</span>
        </div>
        <div class="flex-between font-bold">
            <span>TUNAI</span>
            <span>Rp {{ number_format($totalTunai, 0, ',', '.') }}</span>
        </div>
        
        
        <div class="divider-solid" style="margin-bottom: 25px;"></div>

        <div class="flex-between font-bold" style="font-size: 13px;">
            <span>TOTAL PENDAPATAN</span>
            <span>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
        </div>

        <!-- 3. TANDA TANGAN -->
        <div class="flex-between text-center" style="margin-top: 30px;">
            <div style="width: 45%;">
                <p style="margin-bottom: 40px;">Kasir,</p>
                <p>-----------------</p>
            </div>
            <div style="width: 45%;">
                <p style="margin-bottom: 40px;">SPV / Owner,</p>
                <p>-----------------</p>
            </div>
        </div>

        <div class="divider" style="margin-top: 15px;"></div>
        <div class="text-center" style="font-size: 10px; margin-top: 5px;">
            -- Dicetak dari SIPB BigBang Eatery --
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>