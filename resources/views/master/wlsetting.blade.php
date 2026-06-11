@extends('layouts.nav')
@section('title', 'Pengaturan Waiting List')
@section('content')

<div class="container-xl mt-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm border-0 mb-3" role="alert">
            <div class="fw-bold">🎉 Sukses! {{ session('success') }}</div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    {{-- CARD FORM SETTING: 2 KOLOM SEJAJAR --}}
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h3 class="card-title text-white fw-bold mb-0">🛠️ KUSTOMISASI SISTEM</h3>
        </div>
        <div class="card-body py-4">
            <p class="text-muted small mb-4">
                Halaman ini khusus untuk Owner (Master) mengatur jalannya regulasi antrean secara dinamis tanpa mengubah kodingan program.
            </p>

            <form action="{{ route('master.waitinglist.update') }}" method="POST">
                @csrf
                <div class="row g-4 align-items-end">

                    {{-- KOLOM KIRI: Batas Waktu Verifikasi --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-1">⏱️ Batas Waktu Verifikasi (Menit)</label>
                        <div class="input-group">
                            <input type="number" name="verification_time"
                                class="form-control form-control-lg fw-bold"
                                value="{{ $verificationTime }}" min="1" required>
                            <span class="input-group-text bg-light fw-bold text-secondary">Menit</span>
                        </div>
                        <small class="form-hint text-muted mt-1 d-block">
                            Jika pelanggan mendaftar online via website dan tidak melapor ke kasir dalam waktu ini, namanya otomatis hangus dari antrean.
                        </small>
                    </div>

                    {{-- KOLOM KANAN: Batas Kuota Maksimal --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-1">📊 Batas Kuota Maksimal Antrean Online</label>
                        <div class="input-group">
                            <input type="number" name="max_online_queue"
                                class="form-control form-control-lg fw-bold"
                                value="{{ $maxOnlineQueue }}" min="1" required>
                            <span class="input-group-text bg-light fw-bold text-secondary">Customer</span>
                        </div>
                        <small class="form-hint text-muted mt-1 d-block">
                            Batas jumlah maksimal pengantre online yang terdaftar bersamaan di website monitor. Jika penuh, tombol daftar mandiri di website otomatis terkunci.
                        </small>
                    </div>

                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="font-size: 15px;">
                    💾 SIMPAN KONFIGURASI BARU
                </button>
            </form>
        </div>
    </div>

    {{-- LIST ANTREAN DENGAN TAB FILTER (READ ONLY UNTUK MASTER) --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-2">
                    <h3 class="card-title text-white fw-bold mb-0">📋 MONITORING ANTREAN HARI INI</h3>
                </div>

                <div class="card-header p-0 border-bottom">
                    <ul class="nav nav-tabs card-header-tabs m-0" data-bs-toggle="tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-semua" class="nav-link active fw-bold py-3 px-3 text-primary" data-bs-toggle="tab" role="tab">🌐 Semua Aktif</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-onsite" class="nav-link fw-bold py-3 px-3 text-success" data-bs-toggle="tab" role="tab">📍 On-Site (Kasir)</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-online-unverified" class="nav-link fw-bold py-3 px-3 text-warning" data-bs-toggle="tab" role="tab">⏳ Online (Belum Verifikasi)</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-online-verified" class="nav-link fw-bold py-3 px-3 text-azure" data-bs-toggle="tab" role="tab">✅ Online (Terverifikasi)</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-no-show" class="nav-link fw-bold py-3 px-3 text-purple" data-bs-toggle="tab" role="tab">❌ No-Show</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-expired" class="nav-link fw-bold py-3 px-3 text-danger" data-bs-toggle="tab" role="tab">💨 Expired</a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-semua" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])])
                        </div>
                        <div class="tab-pane fade" id="tab-onsite" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->where('tipe', 'onsite')->whereIn('status', ['waiting', 'call'])])
                        </div>
                        <div class="tab-pane fade" id="tab-online-unverified" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->where('tipe', 'online')->where('status', 'not_verified')])
                        </div>
                        <div class="tab-pane fade" id="tab-online-verified" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->where('tipe', 'online')->whereIn('status', ['verified', 'call'])])
                        </div>
                        <div class="tab-pane fade" id="tab-no-show" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->where('status', 'no_show')])
                        </div>
                        <div class="tab-pane fade" id="tab-expired" role="tabpanel">
                            @include('admin.table-loop', ['items' => $waitingLists->where('status', 'expired')])
                        </div>
                    </div>
                </div>

                <div class="card-footer text-muted small">
                    📊 Total antrean aktif hari ini:
                    <strong>{{ $waitingLists->whereIn('status', ['waiting','not_verified','verified','call'])->count() }} orang</strong>
                    — halaman ini <strong>read-only</strong>, pengelolaan antrean dilakukan oleh Kasir.
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
