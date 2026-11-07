@php
    $pembayaranSukses = $pesanan->pembayaran
        ->where('status', 'Success');

    $dp = (float) $pembayaranSukses
        ->where('jenis', 'DP')
        ->sum('nominal');
    $total = (float) $pesanan->total_harga;
    $sisa = max(0, $total - $dp);

    $pembayaranTunai = $pembayaranSukses
        ->where('metode', 'tunai')
        ->sortByDesc('id')
        ->first();
    
    $status = strtolower((string) $pesanan->status);

    $bolehCetak = match ($jenis) {
        'self_customer' => $status === 'processing',
        'self_kitchen' => $status === 'processing',
        'reservation_customer' => $status === 'completed',
        'reservation_kitchen' => $status === 'confirmed',
        'reservation_dp' => $status === 'confirmed',
        default => false,
    };

    abort_unless($bolehCetak, 403, 'Nota belum dapat dicetak untuk status pesanan ini.');

    $isDp = $jenis === 'reservation_dp';
    $isKitchen = in_array($jenis, ['self_kitchen', 'reservation_kitchen'], true);

    $nomorMeja = $pesanan->meja?->nomor_meja
        ?? $reservasi?->detailReservasi?->first()?->meja?->nomor_meja
        ?? '-';

    
    $judul = match ($jenis) {
        'self_kitchen', 'reservation_kitchen' => 'NOTA DAPUR',
        'reservation_dp' => 'BUKTI RESERVASI (DP)',
        default => 'NOTA CUSTOMER',
    };

    $formatWib = function ($value) {
        if (!$value) {
            return '-';
        }

        $date = $value instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($value)
            : \Illuminate\Support\Carbon::parse($value, config('app.timezone', 'UTC'));

        return $date->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB';
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota #{{ $pesanan->kode_pesanan }}</title>
    <style>
        /* Standar 80mm Printer Thermal */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            /* Font monospace seringkali lebih rapi di printer thermal */
            font-family: 'Courier New', Courier, monospace; 
        }

        html, body {
            width: 80mm;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-size: 12px; /* Disesuaikan agar mudah dibaca */
            line-height: 1.2;
        }

        body {
            padding: 5mm; /* Jarak aman dari pinggir kertas */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
            padding: 2px 0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }
        .divider-thick {
            border-bottom: 2px solid #000;
            margin: 5px 0;
        }

        /* Khusus agar tidak terpotong saat ngeprint */
        tr { page-break-inside: avoid; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center bold" style="font-size: 18px; margin-bottom: 3px;">BIGBANG EATERY</div>
    
    @if(!$isKitchen)
        <div class="text-center" style="font-size: 10px; margin-bottom: 5px;">
            Jl. Mulawarman Raya No.3, Pedalangan<br>
            Banyumanik, Kota Semarang
        </div>
    @endif

    <div class="text-center bold" style="font-size: 14px; margin-bottom: 5px;">*** {{ $judul }} ***</div>

    <div class="divider"></div>
    
    <table>
        <tr>
            <td width="35%">No. Pesanan</td>
            <td width="5%">:</td>
            <td width="60%" class="bold text-right">{{ $pesanan->kode_pesanan }}</td>
        </tr>
        <tr>
            <td>Meja</td>
            <td>:</td>
            <td class="bold text-right" style="font-size: 14px;">{{ $nomorMeja }}</td>
        </tr>
        @if($reservasi)
            <tr>
                <td>Tamu</td>
                <td>:</td>
                <td class="text-right">{{ $reservasi->customer->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jadwal</td>
                <td>:</td>
                <td class="text-right">
                    {{ \Illuminate\Support\Carbon::parse($reservasi->tanggal)->format('d/m/Y') }}
                    {{ \Illuminate\Support\Carbon::parse($reservasi->jam_mulai)->format('H:i') }} WIB
                </td>
            </tr>
        @endif
        <tr>
            <td>Waktu Pesanan</td>
            <td>:</td>
            <td class="text-right">{{ $formatWib($pesanan->created_at) }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>:</td>
            <td class="text-right">{{ $pesanan->user?->username ?? 'Sistem' }}</td>
        </tr>
    </table>

    <div class="divider-thick"></div>

    <table>
        @foreach($pesanan->detailPesanan as $detail)
        <tr>
            <td colspan="3" class="bold">{{ $detail->menu->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td width="20%">{{ $detail->jumlah }}x</td>
            @if(!$isKitchen)
                <td width="40%">@ {{ number_format($detail->menu->harga, 0, ',', '.') }}</td>
                <td width="40%" class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            @else
                <td colspan="2"></td>
            @endif
        </tr>
        @if($detail->catatan)
        <tr>
            <td colspan="3" style="font-size: 11px; font-style: italic;">Catatan: {{ $detail->catatan }}</td>
        </tr>
        @endif
        @endforeach
    </table>

    <div class="divider-thick"></div>

    @if(!$isKitchen)
    <table>
        <tr class="bold" style="font-size: 14px;">
            <td>{{ $isDp ? 'TOTAL TAGIHAN' : 'TOTAL BAYAR' }}</td>
            <td class="text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
        </tr>

        @if($isDp)
            <tr>
                <td>DP Dibayar</td>
                <td class="text-right">Rp {{ number_format($dp, 0, ',', '.') }}</td>
            </tr>
            <tr class="bold">
                <td>Sisa Tagihan</td>
                <td class="text-right">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
            </tr>
        @endif
        
        {{-- Jika ada atribut uang diterima & kembalian (untuk self order tunai) --}}
        @if($pembayaranTunai)
            @if($pembayaranTunai->uang_diterima !== null)
                <tr>
                    <td>Tunai</td>
                    <td class="text-right">
                        Rp {{ number_format($pembayaranTunai->uang_diterima, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            @if($pembayaranTunai->uang_kembali !== null)
                <tr>
                    <td>Kembali</td>
                    <td class="text-right">
                        Rp {{ number_format($pembayaranTunai->uang_kembali, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        @endif
    </table>

    <div class="divider"></div>
    <div class="text-center" style="margin-top: 10px; font-size: 11px;">
        Terima kasih telah berkunjung!<br>
        IG: @bigbangeatery
    </div>
    @endif
</body>
</html>