@extends('layouts.nav')
@section('title', 'Dashboard Master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <!-- TOP STATS CARDS (4 CARDS SEJAJAR) -->
            <div class="row row-cards mb-4">
                <!-- Card 1: Omzet Hari Ini + Breakdown -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M16.7 8a3 3 0 1 0 -2.7 -2" />
                                            <path d="M12 8a3 3 0 1 0 -4.9 0" />
                                            <path d="M8 12a3 3 0 1 0 1.9 3" />
                                            <path d="M12 16a3 3 0 1 0 4.6 -1" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">Omzet Hari Ini</div>
                                    <div class="text-white h2 mb-0">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div>
                                    <div class="small text-white-50 mt-1">
                                        Billing: Rp {{ number_format($billingHariIni ?? 0, 0, ',', '.') }} | FnB: Rp
                                        {{ number_format($fnbHariIni ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Meja Terisi -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-success text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                            <path d="M13.45 11.55l2.05 -2.05" />
                                            <path d="M6.4 20a9 9 0 1 1 11.2 0z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">Meja Terisi</div>
                                    <div class="text-white h2 mb-0">{{ $mejaTerisi }} / {{ $tables->count() }}</div>
                                    <div class="small text-white-50 mt-1">
                                        {{ $tables->count() - $mejaTerisi }} Meja Kosong
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Waiting List -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-warning text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">Waiting List</div>
                                    <div class="text-white h2 mb-0">{{ $waitingLists->count() }}</div>
                                    <div class="small text-white-50 mt-1">Antrean Aktif</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Kasir Shift Aktif -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-dark text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">Kasir Shift Aktif</div>
                                    <div class="text-white h3 mb-0 text-truncate">
                                        {{ $activeCashier->name ?? 'Belum ada' }}
                                    </div>
                                    <div class="small text-white-50 mt-1">Status: On-Duty</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENT BODY -->
            <div class="row row-cards">
                <!-- KIRI: Performa Meja Hari Ini -->
                <div class="col-lg-7">
                    <div class="card" style="height: 100%;">
                        <div class="card-header">
                            <h3 class="card-title">Performa Meja Hari Ini</h3>
                        </div>
                        <!-- Ubah max-height menjadi 575px di bawah ini -->
                        <div class="table-responsive" style="max-height: 575px; overflow-y: auto;">
                            <table class="table table-vcenter card-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Meja</th>
                                        <th>Status</th>
                                        <th class="text-center">Trx</th>
                                        <th>Total Waktu</th>
                                        <th>Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tables as $table)
                                        <tr>
                                            <td class="font-weight-bold">Meja
                                                {{ str_pad($table->table_number, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>
                                                @if ($table->status == 'playing')
                                                    <span class="badge bg-success-lt">PLAYING</span>
                                                @elseif($table->status == 'personal')
                                                    <span class="badge bg-warning-lt">PERSONAL</span>
                                                @elseif($table->status == 'maintenance')
                                                    <span class="badge bg-danger-lt">MAINTENANCE</span>
                                                @else
                                                    <span class="badge bg-secondary-lt">KOSONG</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $table->total_transaksi }}</td>
                                            <td>{{ floor($table->total_waktu / 60) }}j {{ $table->total_waktu % 60 }}m
                                            </td>
                                            <td class="font-weight-bold">Rp
                                                {{ number_format($table->total_pendapatan, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- KANAN: Grafik Omzet di Atas, Live Transaksi & Waiting List 2 Kolom di Bawah -->
                <div class="col-lg-5">
                    <!-- 1. Tren Omzet 7 Hari Terakhir -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Tren Omzet 7 Hari Terakhir</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="chartOmzet" height="110"></canvas>
                        </div>
                    </div>

                    <!-- 2. Dua List Bersebelahan -->
                    <div class="row row-cards">
                        <!-- Live Transaction Details (Kolom Kiri) -->
                        <div class="col-6">
                            <div class="card" style="height: 260px;">
                                <div class="card-header bg-dark text-white p-2">
                                    <h4 class="card-title m-0" style="font-size: 0.85rem;">Live Transaction</h4>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 215px; overflow-y: auto;">
                                    @php $hasActive = false; @endphp
                                    @foreach ($tables as $table)
                                        @php
                                            $activeTrx = $table
                                                ->transactions()
                                                ->whereIn('status', ['running', 'active'])
                                                ->first();
                                        @endphp
                                        @if ($activeTrx)
                                            @php $hasActive = true; @endphp
                                            <div class="list-group-item p-2">
                                                <div class="font-weight-bold" style="font-size: 0.8rem;">
                                                    M-{{ $table->table_number }} {{ $activeTrx->customer_name }}</div>
                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <span class="text-muted"
                                                        style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($activeTrx->start_time)->format('H:i') }}</span>
                                                    <span
                                                        class="badge {{ $table->status == 'personal' ? 'bg-warning' : 'bg-success' }}"
                                                        style="font-size: 0.65rem;">
                                                        {{ strtoupper($activeTrx->billing_type) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if (!$hasActive)
                                        <div class="p-3 text-center text-muted small">Tidak ada transaksi meja berjalan
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Waiting List Aktif (Kolom Kanan) -->
                        <div class="col-6">
                            <div class="card" style="height: 260px;">
                                <div class="card-header bg-yellow-lt p-2">
                                    <h4 class="card-title text-dark m-0" style="font-size: 0.85rem;">Waiting List</h4>
                                </div>
                                <div class="table-responsive" style="max-height: 215px; overflow-y: auto;">
                                    <table class="table table-vcenter card-table" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th class="p-2">NAMA</th>
                                                <th class="p-2 text-end">STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($waitingLists as $wl)
                                                <tr>
                                                    <td class="p-2 font-weight-bold">{{ $wl->customer_name }}</td>
                                                    <td class="p-2 text-end">
                                                        <span class="badge bg-yellow text-black"
                                                            style="font-size: 0.65rem;">Waiting</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted p-3 small">Antrean
                                                        kosong</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('chartOmzet').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartDates ?? []) !!},
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: {!! json_encode($chartOmset ?? []) !!},
                        borderColor: '#206bc4',
                        backgroundColor: 'rgba(32, 107, 196, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: '#206bc4'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
