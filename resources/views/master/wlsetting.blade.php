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
        <form action="{{ route('master.waitinglist.update') }}" method="POST">
            @csrf
            <div class="card shadow border-0 mb-4">
                {{-- Header Card --}}
                <div class="card-header bg-dark text-white py-3">
                    <h3 class="card-title text-white fw-bold mb-0">🛠️ KUSTOMISASI SISTEM</h3>
                </div>

                <div class="card-body py-4">
                    <div class="row g-3 align-items-center">

                        {{-- KOLOM 2: Batas Waktu Verifikasi --}}
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">⏱️ Batas Waktu Verifikasi (Menit)</label>
                            <div class="input-group">
                                <input type="number" name="verification_time" class="form-control form-control-lg fw-bold"
                                    value="{{ $verificationTime }}" min="1" required style="height: 48px;">
                                <span class="input-group-text bg-light fw-bold text-secondary">Menit</span>
                            </div>
                            <small class="form-hint text-muted mt-1 d-block">
                                Batas waktu verifikasi layer 2 ke kasir sebelum antrean hangus.
                            </small>
                        </div>

                        {{-- KOLOM 3: Batas Kuota Maksimal --}}
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">📊 Kuota Maksimal Pendaftaran Online (Website)</label>
                            <div class="input-group">
                                <input type="number" name="max_online_queue" class="form-control form-control-lg fw-bold"
                                    value="{{ $maxOnlineQueue }}" min="1" required style="height: 48px;">
                                <span class="input-group-text bg-light fw-bold text-secondary">Customer</span>
                            </div>
                            <small class="form-hint text-muted mt-1 d-block">
                                Batas jumlah maksimal pengantre online bersamaan.
                            </small>
                        </div>

                        {{-- KOLOM 1: Status Pendaftaran Online --}}
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">🌐 Pendaftaran Online (Website)</label>
                            <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between"
                                style="height: 48px;">
                                <span class="small font-weight-bold text-secondary">Status Sistem:</span>
                                <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                                    <input class="form-check-input ms-0" type="checkbox" role="switch" id="toggleRegistWl"
                                        name="regist_wl_website" value="1"
                                        style="width: 2.5em; height: 1.25em; cursor: pointer;"
                                        {{ ($registWlWebsite ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2 fw-bold text-warning" id="toggleLabel"
                                        for="toggleRegistWl" style="min-width: 30px;">
                                        {{ ($registWlWebsite ?? '1') == '1' ? 'ON' : 'OFF' }}
                                    </label>
                                </div>
                            </div>
                            <small class="form-hint text-muted mt-1 d-block">
                                Aktifkan pendaftaran mandiri via website.
                            </small>
                        </div>
                    </div>
                    <hr class="my-3">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="font-size: 15px;">
                        💾 SIMPAN KONFIGURASI BARU
                    </button>
                </div>
            </div>
        </form>

        {{-- LIST ANTREAN DENGAN TAB FILTER (READ ONLY UNTUK MASTER) --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-2">
                        <h3 class="card-title text-white fw-bold mb-0">📋 MONITORING ANTREAN HARI INI</h3>
                    </div>

                    <!-- HEADER NAV TAB UNTUK FILTER STATUS -->
                    <div class="card-header p-0 border-bottom">
                        <ul class="nav nav-tabs card-header-tabs m-0" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tab-semua" class="nav-link active fw-bold py-3 px-3 text-primary"
                                    data-bs-toggle="tab" role="tab">🌐 Semua Aktif</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-onsite" class="nav-link fw-bold py-3 px-3 text-success" data-bs-toggle="tab"
                                    role="tab">📍 On-Site (Kasir)</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-online-verified" class="nav-link fw-bold py-3 px-3 text-azure"
                                    data-bs-toggle="tab" role="tab">✅ Online (Terverifikasi)</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-online-unverified" class="nav-link fw-bold py-3 px-3 text-warning"
                                    data-bs-toggle="tab" role="tab">⏳ Online (Belum Verifikasi)</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-no-show" class="nav-link fw-bold py-3 px-3 text-purple" data-bs-toggle="tab"
                                    role="tab">❌ No-Show / Kabur</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-expired" class="nav-link fw-bold py-3 px-3 text-danger" data-bs-toggle="tab"
                                    role="tab">💨 Expired / Gagal Verifikasi L2</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-failed" class="nav-link fw-bold py-3 px-3 text-secondary"
                                    data-bs-toggle="tab" role="tab">❌ Gagal Verifikasi L1</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-done" class="nav-link fw-bold py-3 px-3 text-success" data-bs-toggle="tab"
                                    role="tab">✔️ Selesai</a>
                            </li>
                        </ul>
                    </div>

                    <!-- ISI KONTEN DATA MASING-MASING TAB -->
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab-semua" role="tabpanel">
                                @include('admin.table-loop', ['items' => $waitingLists])
                            </div>
                            <div class="tab-pane fade" id="tab-onsite" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabOnsite])
                            </div>
                            <div class="tab-pane fade" id="tab-online-verified" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabOnlineVerified])
                            </div>
                            <div class="tab-pane fade" id="tab-online-unverified" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabOnlineUnverified])
                            </div>
                            <div class="tab-pane fade" id="tab-no-show" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabNoShow])
                            </div>
                            <div class="tab-pane fade" id="tab-expired" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabExpired])
                            </div>
                            <div class="tab-pane fade" id="tab-failed" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabFailed])
                            </div>
                            <div class="tab-pane fade" id="tab-done" role="tabpanel">
                                @include('admin.table-loop', ['items' => $tabDone])
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-muted small">
                        📊 Total antrean aktif hari ini:
                        <strong>{{ $waitingLists->count() }} orang</strong>
                        — halaman ini <strong>read-only</strong>, pengelolaan antrean dilakukan oleh Kasir.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('toggleRegistWl')?.addEventListener('change', function() {
            const label = document.getElementById('toggleLabel');
            if (this.checked) {
                label.textContent = 'ON';
                label.classList.remove('text-secondary');
                label.classList.add('text-warning');
            } else {
                label.textContent = 'OFF';
                label.classList.remove('text-warning');
                label.classList.add('text-secondary');
            }
        });
    </script>
@endsection
