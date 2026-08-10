@extends('layouts.nav')

@section('content')
    <div class="container-fluid">
        <!-- HEADER PAGE (TANPA TOMBOL QUICK FILTER) -->
        <div class="d-flex justify-content-between align-items-center mb-4">

        </div>

        <!-- SUMMARY CARDS -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Omset</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalOmset, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Uang Cash (Laci Kasir)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalCash, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Non-Cash (QRIS / Transfer)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp
                            {{ number_format($totalNonCash, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Transaksi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalTransactions) }}
                            Transaksi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL LAPORAN KASIR -->
        <div class="card shadow mb-4">
            <!-- CARD HEADER: JUDUL DAN FILTER SEBARIS FIT (1 BARIS) -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    Daftar Transaksi Selesai ({{ $periodText }})
                </h6>

                <!-- FORM FILTER TANGGAL DAN KASIR (1 BARIS RAPAT) -->
                <form action="{{ route('admin.reportadmin') }}" method="GET" class="d-flex align-items-center m-0"
                    style="gap: 6px;">

                    <!-- Dari Tanggal -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white text-muted">Dari:</span>
                        </div>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}"
                            style="width: 130px;">
                    </div>

                    <!-- Sampai Tanggal -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white text-muted">Sampai:</span>
                        </div>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}"
                            style="width: 130px;">
                    </div>

                    <!-- Dropdown Filter Kasir -->
                    <select name="cashier_id" class="custom-select custom-select-sm" style="width: auto;">
                        <option value="all" {{ $cashierId === null ? 'selected' : '' }}>
                            -- Semua Kasir --
                        </option>
                        @foreach ($cashiers as $c)
                            <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->id == auth()->id() ? '(Saya)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Tombol Filter -->
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        🔍 Filter
                    </button>
                </form>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>No. Nota</th>
                                <th>Kasir Open</th>
                                <th>Kasir Close</th>
                                <th>Tipe Billing</th>
                                <th>Nama Cust</th>
                                <th>No. Meja</th>
                                <th>Start Billing</th>
                                <th>Durasi</th>
                                <th>Close Billing</th>
                                <th>Sewa Meja</th>
                                <th>Harga FnB</th>
                                <th>Grand Total</th>
                                <th>Metode</th>
                                <th>Bayar / Kembali</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $item)
                                <tr>
                                    <!-- 1. No Nota -->
                                    <td>#{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}</td>

                                    <!-- 2. Kasir Open -->
                                    <td>{{ $item->creator->name ?? 'Admin' }}</td>

                                    <!-- 3. Kasir Close -->
                                    <td>{{ $item->closer->name ?? 'Admin' }}</td>

                                    <!-- 4. Tipe Billing -->
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ ucfirst($item->billing_type ?? 'Hourly') }}
                                        </span>
                                    </td>

                                    <!-- 5. Nama Cust -->
                                    <td><strong>{{ strtoupper($item->customer_name ?? 'GUEST') }}</strong></td>

                                    <!-- 6. No Meja -->
                                    <td>
                                        @if ($item->poolTable)
                                            <span class="badge badge-primary">Meja
                                                {{ $item->poolTable->table_number }}</span>
                                        @else
                                            <span class="badge badge-info">Standalone</span>
                                        @endif
                                    </td>

                                    <!-- 7. Start Billing -->
                                    <td>
                                        {{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '-' }}
                                    </td>

                                    <!-- 8. Durasi -->
                                    <td>
                                        @if ($item->billing_type === 'hourly')
                                            <strong>{{ round(($item->duration ?? 60) / 60) }} Jam</strong>
                                        @elseif($item->billing_type === 'package')
                                            @php
                                                $packageHours =
                                                    $item->package->duration_hours ??
                                                    round(($item->duration ?? 60) / 60);
                                            @endphp
                                            <strong>{{ $packageHours }} Jam</strong>
                                        @elseif($item->billing_type === 'personal')
                                            @php
                                                $start = $item->start_time
                                                    ? \Carbon\Carbon::parse($item->start_time)
                                                    : null;
                                                $end = $item->end_time
                                                    ? \Carbon\Carbon::parse($item->end_time)
                                                    : \Carbon\Carbon::parse($item->updated_at);

                                                if ($start && $end) {
                                                    $totalSeconds = $start->diffInSeconds($end);
                                                    $hours = floor($totalSeconds / 3600);
                                                    $minutes = floor(($totalSeconds % 3600) / 60);
                                                    $seconds = $totalSeconds % 60;
                                                    $formattedDuration = sprintf(
                                                        '%02d:%02d:%02d',
                                                        $hours,
                                                        $minutes,
                                                        $seconds,
                                                    );
                                                } else {
                                                    $formattedDuration = '00:00:00';
                                                }
                                            @endphp
                                            <span class="badge badge-dark" style="font-family: monospace;">
                                                {{ $formattedDuration }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <!-- 9. Close Billing -->
                                    <td>
                                        {{ $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : \Carbon\Carbon::parse($item->updated_at)->format('H:i') }}
                                    </td>

                                    <!-- 10. Sewa Meja -->
                                    <td>Rp {{ number_format($item->bill_price ?? 0, 0, ',', '.') }}</td>

                                    <!-- 11. Harga FnB -->
                                    <td>Rp {{ number_format($item->fnb_price ?? 0, 0, ',', '.') }}</td>

                                    <!-- 12. Grand Total -->
                                    <td class="font-weight-bold text-success">
                                        Rp {{ number_format($item->grand_total, 0, ',', '.') }}
                                    </td>

                                    <!-- 13. Metode -->
                                    <td>
                                        <span
                                            class="badge badge-{{ $item->payment_method == 'cash' ? 'success' : 'warning' }}">
                                            {{ strtoupper($item->payment_method) }}
                                        </span>
                                    </td>

                                    <!-- 14. Bayar / Kembali -->
                                    <td>
                                        @if ($item->payment_method == 'cash')
                                            <small>B: {{ number_format($item->pay_amount, 0, ',', '.') }}<br>
                                                K: {{ number_format($item->change_amount, 0, ',', '.') }}</small>
                                        @else
                                            <small class="text-muted">Pas</small>
                                        @endif
                                    </td>

                                    <!-- 15. Aksi -->
                                    <td>
                                        <a href="{{ route('billing.receipt', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-secondary">
                                            🖨️ Struk
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center py-4 text-muted">
                                        Belum ada transaksi selesai pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
