<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            padding: 0;
            font-size: 18pt;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 10pt;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 7px 10px;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: #ffffff;
            font-size: 9pt;
            text-transform: uppercase;
        }
        td {
            font-size: 9pt;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #eaeded !important;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Transaksi Finansial</h2>
        <p>Periode: {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">ID Nota</th>
                <th>Customer</th>
                <th class="text-center">Meja</th>
                <th class="text-right">Billing</th>
                <th class="text-right">F&B</th>
                <th class="text-right">Grand Total</th>
                <th class="text-center">Metode</th>
                <th>Kasir</th>
                <th class="text-center">Waktu Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">#{{ sprintf('%04d', $item->id) }}</td>
                    <td>{{ $item->customer_name ?? '-' }}</td>
                    <td class="text-center">{{ $item->poolTable->table_number ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->bill_price ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->fnb_price ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right"><strong>Rp {{ number_format($item->grand_total ?? 0, 0, ',', '.') }}</strong></td>
                    <td class="text-center">{{ strtoupper($item->payment_method ?? '-') }}</td>
                    <td>{{ $item->closer->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->end_time ? $item->end_time->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($transactions->count() > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-center"><strong>TOTAL SELESAI</strong></td>
                <td class="text-right">Rp {{ number_format($transactions->sum('bill_price'), 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($transactions->sum('fnb_price'), 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($transactions->sum('grand_total'), 0, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
