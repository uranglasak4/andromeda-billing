@extends('layouts.nav')
@section('title', 'Manajemen Meja')
@section('content')
<div class="page-body">
    <div class="container-xl">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible shadow-sm border-0 mb-3" role="alert">
                <div class="fw-bold">✅ {{ session('success') }}</div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible shadow-sm border-0 mb-3" role="alert">
                <div class="fw-bold">❌ {{ session('error') }}</div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        {{-- ✅ CARD SETTING NEARLY DI ATAS TABEL MEJA --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-3">
                <h3 class="card-title text-white fw-bold mb-0">⚡ KONFIGURASI PERINGATAN SISA WAKTU (NEARLY)</h3>
            </div>
            <div class="card-body py-4">
                <p class="text-muted small mb-3">
                    Atur berapa menit sebelum billing habis, lampu meja akan <strong>berkedip 5x</strong>
                    sebagai peringatan ke pemain bahwa waktu bermain hampir selesai.
                </p>
                <form action="{{ route('master.tables.nearly-setting') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                ⏱️ Sisa Waktu Trigger Nearly (Menit)
                            </label>
                            <div class="input-group">
                                <input type="number" name="nearly_warning_minutes"
                                    class="form-control form-control-lg fw-bold"
                                    value="{{ $nearlyWarningMinutes }}" min="1" max="60" required>
                                <span class="input-group-text bg-light fw-bold text-secondary">Menit</span>
                            </div>
                            <small class="form-hint text-muted mt-1 d-block">
                                Contoh: isi <strong>15</strong> → lampu berkedip 5x saat sisa waktu tinggal 15 menit.
                            </small>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                                💾 Simpan Konfigurasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL DAFTAR MEJA --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">🎱 Daftar Status Meja Biliar</h3>
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
                            <td class="fw-bold text-dark">{{ $table->name ?? 'Meja ' . $table->table_number }}</td>
                            <td>
                                @if($table->status == 'available')
                                    <span class="badge bg-success">AVAILABLE</span>
                                @elseif($table->status == 'maintenance')
                                    <span class="badge bg-danger">MAINTENANCE</span>
                                @elseif($table->status == 'nearly')
                                    <span class="badge bg-warning text-dark">⚡ NEARLY</span>
                                @elseif(in_array($table->status, ['playing', 'personal']))
                                    <span class="badge bg-blue">PLAYING (TERISI)</span>
                                @else
                                    <span class="badge bg-secondary">{{ strtoupper($table->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                @if($table->status == 'available')
                                    Meja kosong, siap menerima pemain baru.
                                @elseif($table->status == 'maintenance')
                                    <span class="text-danger">Meja dikunci. Kasir tidak bisa membuka billing.</span>
                                @elseif($table->status == 'nearly')
                                    <span class="text-warning fw-bold">⚠️ Waktu billing hampir habis. Lampu sudah berkedip 5x.</span>
                                @else
                                    <span class="text-muted">Sedang digunakan transaksi aktif.</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($table->status, ['available', 'maintenance']))
                                    <form action="{{ route('master.tables.maintenance', $table->id) }}" method="POST">
                                        @csrf
                                        @if($table->status == 'available')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Set meja ini ke MAINTENANCE?')">
                                                Set Maintenance
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Aktifkan Kembali
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        🔒 Meja Aktif
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
