<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran - #{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 300px; margin: auto; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; }
    </style>
</head>
<body>

    <div class="text-center">
        <strong>ANDROMEDA BILLIARD & CAFE</strong><br>
        Jl. Contoh Raya No. 123<br>
        Telp: 0812-3456-7890
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td>No. Nota:</td>
            <td class="text-right">#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td>Tanggal:</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($transaction->end_time ?? $transaction->updated_at)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td class="text-right">{{ strtoupper($transaction->customer_name ?? 'GUEST') }}</td>
        </tr>
        <tr>
            <td>Meja:</td>
            <td class="text-right">{{ $transaction->poolTable->name ?? 'MEJA ' . ($transaction->pool_table_id ?? '-') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <!-- TABEL ITEM PEMBAYARAN -->
    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. ITEM SEWA MEJA -->
            <tr>
                <td colspan="3">
                    <strong>SEWA MEJA ({{ strtoupper($transaction->billing_type ?? 'Hourly') }})</strong><br>
                    <small>
                        {{ $transaction->start_time ? \Carbon\Carbon::parse($transaction->start_time)->format('H:i') : '' }} -
                        {{ $transaction->end_time ? \Carbon\Carbon::parse($transaction->end_time)->format('H:i') : '' }}
                    </small>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="text-center">1</td>
                <!-- MENGAMBIL NILAIBILL_PRICE ATAU GRAND_TOTAL -->
                <td class="text-right">Rp {{ number_format($transaction->bill_price ?? $transaction->grand_total, 0, ',', '.') }}</td>
            </tr>

            <!-- 2. ITEM FnB (JIKA ADA) -->
            @if(!empty($transaction->fnb_price) && $transaction->fnb_price > 0)
            <tr>
                <td colspan="2">Pesanan FnB</td>
                <td class="text-right">Rp {{ number_format($transaction->fnb_price, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="line"></div>

    <!-- RINGKASAN TOTAL & METODE -->
    <table>
        <tr>
            <td><strong>GRAND TOTAL:</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td>Metode Bayar:</td>
            <td class="text-right">{{ strtoupper($transaction->payment_method ?? 'CASH') }}</td>
        </tr>

        @if(($transaction->payment_method ?? '') === 'cash')
        <tr>
            <td>Uang Bayar:</td>
            <td class="text-right">Rp {{ number_format($transaction->pay_amount ?? $transaction->grand_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali:</td>
            <td class="text-right">Rp {{ number_format($transaction->change_amount ?? 0, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <div class="line"></div>

    <div class="text-center">
        *** TERIMA KASIH ***<br>
        Selamat Datang Kembali!<br><br>
        <small>
            📱 WA: 0812-3456-7890<br>
            📷 IG: @andromeda.billiard<br>
            🎵 TikTok: @andromeda.billiard
        </small>
    </div>

    <!-- TOMBOL AKSIS (TIDAK IKUT TRICETAK) -->
    <div class="text-center" style="margin-top: 15px;" id="action-buttons">
        <button onclick="window.print()" class="btn btn-primary" style="padding: 5px 10px; cursor: pointer;">🖨️ Cetak / Save PDF</button>
        <button onclick="window.close()" class="btn btn-secondary" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
    </div>

</body>
</html>
