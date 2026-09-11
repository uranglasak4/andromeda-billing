@extends('layouts.nav')

@section('content')
    <div class="container-fluid">
        <!-- HEADER PAGE (TANPA TOMBOL QUICK FILTER) -->
        <div class="d-flex justify-content-between align-items-center mb-4">

        </div>

        <!-- SUMMARY CARDS -->
        <div class="row mb-4">
            <!-- 1. TOTAL OMSET (DENGAN BREAKDOWN SEWA MEJA & FNB) -->
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Omset</div>
                        <div class="h5 mb-2 font-weight-bold text-gray-800">Rp {{ number_format($totalOmset, 0, ',', '.') }}
                        </div>

                        <!-- Breakdown Billing & FnB (Berurutan ke bawah) -->
                        <div class="border-top pt-2 mt-1 text-xs text-muted">
                            <div class="mb-1">Total Billing: <strong>Rp
                                    {{ number_format($totalBillPrice ?? 0, 0, ',', '.') }}</strong></div>
                            <div>Total FnB: <strong>Rp {{ number_format($totalFnbPrice ?? 0, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. UANG CASH (LACI KASIR) -->
            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Uang Cash (Laci Kasir)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalCash, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. NON-CASH (QRIS / TRANSFER) -->
            <div class="col-md-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Non-Cash (QRIS / Transfer)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp
                            {{ number_format($totalNonCash, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- 4. TOTAL TRANSAKSI -->
            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Transaksi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ method_exists($transactions, 'total') ? number_format($transactions->total()) : number_format($totalTransactions) }}
                            Transaksi
                        </div>
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

                <!-- FORM FILTER TANGGAL, KATEGORI, SEARCH, KASIR -->
                <form action="{{ route('master.reportmaster') }}" method="GET" class="d-flex align-items-center m-0"
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

                    <!-- Dropdown Kategori Transaksi -->
                    <select name="type" class="custom-select custom-select-sm" style="width: auto;">
                        <option value="all" {{ request('type') == 'all' || !request('type') ? 'selected' : '' }}>--
                            Semua Transaksi --</option>
                        <option value="blm" {{ request('type') == 'blm' ? 'selected' : '' }}>BLM (Meja Only)</option>
                        <option value="fnb" {{ request('type') == 'fnb' ? 'selected' : '' }}>FNB (FnB Only)</option>
                        <option value="billing_fnb" {{ request('type') == 'billing_fnb' ? 'selected' : '' }}>ALL (Meja +
                            FnB)</option>
                    </select>

                    <!-- Input Search (4 Digit Akhir Nota / Nama Cust / Meja) -->
                    <div class="input-group input-group-sm" style="width: 170px;">
                        <input type="text" name="search" class="form-control" placeholder="4 Digit/Cust/Meja..."
                            value="{{ request('search') }}" autocomplete="off">
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
                    <button type="button" class="btn btn-sm btn-success px-3 ml-2" data-bs-toggle="modal"
                        data-bs-target="#exportModal">
                        📥 Export To
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
                                    <td>
                                        @php
                                            if (!$item->pool_table_id) {
                                                $prefix = 'FNB';
                                            } elseif (($item->fnb_price ?? 0) > 0) {
                                                $prefix = 'ALL';
                                            } else {
                                                $prefix = 'BLM';
                                            }
                                        @endphp

                                        <span class="font-weight-bold" style="font-family: monospace;">
                                            {{ $prefix }}-{{ $item->created_at ? $item->created_at->format('ymd') : date('ymd') }}-{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>

                                    <!-- 2. Kasir Open -->
                                    <td>{{ $item->creator->name ?? 'Admin' }}</td>

                                    <!-- 3. Kasir Close -->
                                    <td>{{ $item->closer->name ?? 'Admin' }}</td>

                                    <!-- 4. Tipe Billing -->
                                    <td>
                                        @if ($item->pool_table_id)
                                            <span class="badge badge-secondary">
                                                {{ ucfirst($item->billing_type ?? 'Hourly') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <!-- 5. Nama Cust -->
                                    <td><strong>{{ strtoupper($item->customer_name ?? 'GUEST') }}</strong></td>

                                    <!-- 6. No Meja -->
                                    <td>
                                        @if ($item->pool_table_id && $item->poolTable)
                                            <span class="badge badge-primary">Meja
                                                {{ $item->poolTable->table_number }}</span>
                                        @else
                                            <span class="badge badge-info">Standalone</span>
                                        @endif
                                    </td>

                                    <!-- 7. Start Billing -->
                                    <td>
                                        {{ $item->pool_table_id && $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '-' }}
                                    </td>

                                    <!-- 8. Durasi -->
                                    <td>
                                        @if (!$item->pool_table_id)
                                            -
                                        @elseif ($item->billing_type === 'hourly')
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
                                        {{ $item->pool_table_id ? ($item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : \Carbon\Carbon::parse($item->updated_at)->format('H:i')) : '-' }}
                                    </td>

                                    <!-- 10. Sewa Meja -->
                                    <td>
                                        {{ $item->pool_table_id ? 'Rp ' . number_format($item->bill_price ?? 0, 0, ',', '.') : '-' }}
                                    </td>

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
                                        <a href="{{ route('master.receipt', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-secondary">
                                            🖨️ Struk
                                        </a>
                                        <form action="{{ route('master.reports.destroy', $item->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi #{{ $item->id }} ini? Data omset akan berkurang secara permanen.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger p-1 px-2"
                                                title="Hapus Transaksi">
                                                🗑️ Drop
                                            </button>
                                        </form>
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

                <!-- FOOTER PAGINATION (JIKA MENGGUNAKAN PAGINATE 15 DATA) -->
                @if (method_exists($transactions, 'links'))
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} dari
                            {{ $transactions->total() }} transaksi
                        </small>
                        <div>
                            {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <!-- MODAL POP-UP EXPORT TO -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('master.reports.export') }}" method="GET" target="_blank">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title font-weight-bold" id="exportModalLabel" style="font-size: 1rem;">
                            📥 Export Laporan Keuangan
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- 1. PILIH FORMAT FILE -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">Format File Output:</label>
                            <div class="d-flex" style="gap: 20px;">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="formatExcel" name="format" value="excel"
                                        class="custom-control-input" checked>
                                    <label class="custom-control-label text-success font-weight-bold" for="formatExcel">
                                        📊 Excel (.xlsx)
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="formatPdf" name="format" value="pdf"
                                        class="custom-control-input">
                                    <label class="custom-control-label text-danger font-weight-bold" for="formatPdf">
                                        📄 PDF Document (.pdf)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- 2. PILIH RENTANG WAKTU PRESET -->
                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-dark">Pilih Rentang Waktu Laporan:</label>
                            <select name="time_range" id="time_range" class="custom-select custom-select-sm"
                                onchange="toggleCustomDate(this.value)">
                                <option value="today">Hari Ini</option>
                                <option value="this_week">1 Minggu Ini (Senin - Minggu Ini)</option>
                                <option value="last_7_days">7 Hari Terakhir</option>
                                <option value="this_month" selected>1 Bulan Ini (Bulan Berjalan)</option>
                                <option value="last_month">1 Bulan Sebelumnya</option>
                                <option value="custom">Rentang Tanggal Custom...</option>
                            </select>
                        </div>

                        <!-- INPUT TANGGAL CUSTOM -->
                        <div id="custom_date_container" class="row mt-2" style="display: none;">
                            <div class="col-6">
                                <label class="small text-muted mb-1">Dari Tanggal:</label>
                                <input type="date" name="export_start_date" class="form-control form-control-sm"
                                    value="{{ $startDate }}">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Sampai Tanggal:</label>
                                <input type="date" name="export_end_date" class="form-control form-control-sm"
                                    value="{{ $endDate }}">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success px-4">Download Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDate(val) {
            const container = document.getElementById('custom_date_container');
            if (val === 'custom') {
                container.style.display = 'flex';
            } else {
                container.style.display = 'none';
            }
        }
    </script>
@endsection
