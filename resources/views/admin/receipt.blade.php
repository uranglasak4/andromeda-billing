<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>
        {{ $transaction->status === 'unpaid' ? 'Struk Tagihan' : 'Struk Pembayaran' }} - #{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}
    </title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 300px; margin: auto; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; }
        .status-badge {
            border: 1px solid #000;
            padding: 2px 6px;
            display: inline-block;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="text-center">
        <strong>ANDROMEDA BILLIARD & CAFE</strong><br>
        Jl. Contoh Raya No. 123<br>
        Telp: 0812-3456-7890<br>

        <!-- KETERANGAN STATUS STRUK -->
        <div class="status-badge">
            {{ $transaction->status === 'unpaid' ? '*** STRUK TAGIHAN (UNPAID) ***' : '*** STRUK PEMBAYARAN (LUNAS) ***' }}
        </div>
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td>No. Nota:</td>
            <td class="text-right">#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td>Tanggal:</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($transaction->end_time ?? $transaction->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td class="text-right">{{ strtoupper($transaction->customer_name ?? 'GUEST') }}</td>
        </tr>
        <tr>
            <td>Meja:</td>
            <td class="text-right">MEJA {{ $transaction->pool_table_id }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <!-- ITEM DETAIL -->
    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
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
                <td class="text-right">Rp {{ number_format($transaction->bill_price ?? $transaction->grand_total, 0, ',', '.') }}</td>
            </tr>

            @if(!empty($transaction->fnb_price) && $transaction->fnb_price > 0)
            <tr>
                <td colspan="2">Pesanan FnB</td>
                <td class="text-right">Rp {{ number_format($transaction->fnb_price, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="line"></div>

    <!-- RINGKASAN TOTAL -->
    <table>
        <tr>
            <td><strong>TOTAL TAGIHAN:</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong></td>
        </tr>

        <!-- JIKA SUDAH LUNAS, TAMPILKAN METODE DAN KEMBALIAN -->
        @if($transaction->status === 'finished')
            <tr>
                <td>Metode Bayar:</td>
                <td class="text-right">{{ strtoupper($transaction->payment_method ?? 'CASH') }}</td>
            </tr>
            @if(($transaction->payment_method ?? '') === 'cash')
            <tr>
                <td>Uang Bayar:</td>
                <td class="text-right">Rp {{ number_format($transaction->pay_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kembali:</td>
                <td class="text-right">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
        @else
            <!-- JIKA MASIH UNPAID/TAGIHAN -->
            <tr>
                <td colspan="2" class="text-center" style="padding-top: 5px;">
                    <em>-- BELUM DIBAYAR --</em>
                </td>
            </tr>
        @endif
    </table>

    <div class="line"></div>

    <div class="text-center">
        @if($transaction->status === 'unpaid')
            *** SILAHKAN BAYAR DI KASIR ***<br>
            Simpan struk ini sebagai bukti tagihan
        @else
            *** TERIMA KASIH ***<br>
            Selamat Datang Kembali!
        @endif
        <br><br>
        <small>
            📱 WA: 0812-3456-7890<br>
            📷 IG: @andromeda.billiard<br>
            🎵 TikTok: @andromeda.billiard
        </small>
    </div>

    <!-- TOMBOL CETAK -->
    <div class="text-center" style="margin-top: 15px;" id="action-buttons">
        <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">🖨️ Cetak Struk</button>
        <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
    </div>

</body>
</html>
