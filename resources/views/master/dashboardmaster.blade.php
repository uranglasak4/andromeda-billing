@extends('layouts.nav')
@section('title', 'Dashboard Master')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Master Control Panel
                    </h2>
                    <p class="text-muted">Ringkasan operasional Andromeda Billiard hari ini.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-success text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm bg-warning text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-white-transparent text-white avatar"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
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
                                    <div class="font-weight-medium">Waiting List</div>
                                    <div class="text-white h2 mb-0">{{ $currentWaitingCount ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Performa Meja Hari Ini</h3></div>
            <div class="table-responsive">
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
                        @foreach($tables as $table)
                        <tr>
                            <td class="font-weight-bold">Meja {{ str_pad($table->table_number, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                @if($table->status == 'playing')
                                    <span class="badge bg-success-lt">PLAYING</span>
                                @elseif($table->status == 'personal')
                                    <span class="badge bg-warning-lt">PERSONAL</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $table->total_transaksi }}</td>
                            <td>{{ floor($table->total_waktu / 60) }}j {{ $table->total_waktu % 60 }}m</td>
                            <td class="font-weight-bold">Rp {{ number_format($table->total_pendapatan, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
    <div class="card mb-3" style="max-height: 300px; overflow-y: auto;">
        <div class="card-header bg-dark text-white">
            <h3 class="card-title">Live Transaction Details</h3>
        </div>
        <div class="list-group list-group-flush">
            @forelse($tables as $table)
                @php $activeTrx = $table->transactions()->whereIn('status', ['running', 'active'])->first(); @endphp
                @if($activeTrx)
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="font-weight-bold">Meja {{ $table->table_number }} - {{ $activeTrx->customer_name }}</div>
                                <div class="text-muted small">Mulai: {{ \Carbon\Carbon::parse($activeTrx->start_time)->format('H:i') }}</div>
                            </div>
                            <div class="col-auto">
                                <span class="badge {{ $table->status == 'personal' ? 'bg-warning' : 'bg-success' }}">
                                    {{ strtoupper($activeTrx->billing_type) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="p-3 text-center text-muted">Tidak ada meja aktif</div>
            @endforelse
        </div>
    </div>

    <div class="card" style="max-height: 250px; overflow-y: auto;">
        <div class="card-header bg-yellow-lt">
            <h3 class="card-title text-dark">Waiting List Aktif</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Meja</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waitingLists as $wl)
                    <tr>
                        <td>{{ $wl->customer_name }}</td>
                        <td>Meja {{ $wl->pool_table_id }}</td>
                        <td><span class="badge bg-yellow text-black">Waiting</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Antrean kosong</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
