@extends('layouts.nav')
@section('title', 'Manajemen Meja')
@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Daftar Status Meja Biliar</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table text-nowrap">
                    <thead>
                        <tr>
                            <th>Nama Meja</th>
                            <th>Status Saat Ini</th>
                            <th>Keterangan Sistem</th>
                            <th class="w-1">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tables as $table)
                        <tr>
                            <td class="font-weight-bold text-dark">{{ $table->name }}</td>
                            <td>
                                @if($table->status == 'available')
                                    <span class="badge bg-success">AVAILABLE</span>
                                @elseif($table->status == 'maintenance')
                                    <span class="badge bg-danger">MAINTENANCE</span>
                                @elseif(in_array($table->status, ['playing', 'personal']))
                                    <span class="badge bg-blue">PLAYING (TERISI)</span>
                                @else
                                    <span class="badge bg-warning">{{ strtoupper($table->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                @if($table->status == 'available')
                                    Meja kosong, siap menerima pemain baru atau perbaikan berkala.
                                @elseif($table->status == 'maintenance')
                                    <span class="text-danger">Meja dikunci. Kasir tidak bisa membuka billing di meja ini.</span>
                                @else
                                    <span class="text-muted">Sedang digunakan transaksi aktif. Sistem mengunci opsi maintenance.</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($table->status, ['available', 'maintenance']))
                                    <form action="{{ route('master.tables.maintenance', $table->id) }}" method="POST">
                                        @csrf
                                        @if($table->status == 'available')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Set meja ini ke status Maintenance? Kasir tidak akan bisa menggunakan meja ini.')">
                                                Set Maintenance
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Aktifkan Kembali
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="Meja sedang terpakai">
                                        Lock (Meja Aktif)
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
