@extends('layouts.nav')
@section('title', 'Waiting List')
@section('content')
    <div class="container-xl mt-4">

        <!-- ALERT SINKRONISASI SISTEM -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible shadow-sm border-0 mb-3" role="alert">
                <div class="fw-bold">🎉 Sukses! {{ session('success') }}</div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible shadow-sm border-0 mb-3" role="alert">
                <div class="fw-bold">⚠️ Perhatian! {{ session('warning') }}</div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <div class="row g-3">
            <!-- ==================================================================== -->
            <!-- SISI ATAS: FORM INPUT PENDAFTARAN INTERNAL OLEH KASIR -->
            <!-- ==================================================================== -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title text-white fw-bold mb-0">📝 PENDAFTARAN WAITING LIST INTERNAL (KASIR)</h3>
                        <span class="badge {{ $canRegister ? 'bg-success' : 'bg-danger' }} fs-6">
                            {{ $canRegister ? 'STATUS: PENDAFTARAN DIBUKA' : 'STATUS: PENDAFTARAN DIKUNCI' }}
                        </span>
                    </div>
                    <div class="card-body bg-light-lt py-4">

                        {{-- Alert Notifikasi jika Pendaftaran Dikunci --}}
                        @if (!$canRegister)
                            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3"
                                role="alert">
                                <span class="fs-2 me-3">⚠️</span>
                                <div>
                                    <strong>Pendaftaran Waiting List Dikunci!</strong><br>
                                    Saat ini terdapat <b>{{ $availableTables }} meja bebas (Available/Timeout)</b> dan hanya
                                    <b>{{ $totalWaiting }} antrean aktif</b>. Pelanggan baru bisa langsung menggunakan meja
                                    tanpa perlu masuk antrean.
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('admin.waiting-list.store') }}" method="POST">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-dark">Nama Pelanggan / Tim</label>
                                    <input type="text" name="nama_pelanggan"
                                        class="form-control form-control-lg text-uppercase fw-bold"
                                        placeholder="CONTOH: BUDI / ALEX CS" {{ !$canRegister ? 'disabled' : '' }} required
                                        autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Nomor WhatsApp (Opsional)</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white fw-bold text-muted">+62</span>
                                        <input type="number" name="nomor_wa" class="form-control form-control-lg fw-bold"
                                            placeholder="81234567xxx" {{ !$canRegister ? 'disabled' : '' }}
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit"
                                        class="btn {{ $canRegister ? 'btn-primary' : 'btn-secondary' }} btn-lg w-100 fw-bold"
                                        {{ !$canRegister ? 'disabled' : '' }}>
                                        ➕ TAMBAH KE ANTREAN
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ==================================================================== -->
            <!-- SISI BAWAH: DATA TABEL FILTER WAITING LIST MANAGEMENT -->
            <!-- ==================================================================== -->
            <div class="col-12 mt-3">
                <div class="card shadow-sm border-0">

                    <!-- HEADER NAV TAB UNTUK FILTER (UPDATED) -->
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
                                <a href="#tab-online-unverified" class="nav-link fw-bold py-3 px-3 text-warning"
                                    data-bs-toggle="tab" role="tab">⏳ Online (Belum Verifikasi)</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-online-verified" class="nav-link fw-bold py-3 px-3 text-azure"
                                    data-bs-toggle="tab" role="tab">✅ Online (Terverifikasi)</a>
                            </li>
                            <!-- TAB TAMBAHAN BARU DARI GEMINI -->
                            <li class="nav-item" role="presentation">
                                <a href="#tab-no-show" class="nav-link fw-bold py-3 px-3 text-purple" data-bs-toggle="tab"
                                    role="tab">❌ No-Show / Kabur</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-expired" class="nav-link fw-bold py-3 px-3 text-danger" data-bs-toggle="tab"
                                    role="tab">💨 Expired / Gagal Verifikasi L2</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-failed" class="nav-link fw-bold py-3 px-3 text-secondary" data-bs-toggle="tab"
                                    role="tab">❌ Gagal Verifikasi L1</a>
                            </li>
                        </ul>
                    </div>

                    <!-- ISI KONTEN DATA MASING-MASING TAB -->
                    <div class="card-body p-0">
                        <div class="tab-content">

                            <!-- 1. TAB SEMUA ANTREAN YANG MASIH AKTIF -->
                            <div class="tab-pane fade show active" id="tab-semua" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->whereIn('status', [
                                        'waiting',
                                        'not_verified',
                                        'verified',
                                        'call',
                                    ]),
                                ])
                            </div>

                            <!-- 2. TAB ON-SITE ONLY -->
                            <div class="tab-pane fade" id="tab-onsite" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('tipe', 'onsite')->whereIn('status', ['waiting', 'call']),
                                ])
                            </div>

                            <!-- 3. TAB ONLINE BELUM VERIFIKASI -->
                            <div class="tab-pane fade" id="tab-online-unverified" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('tipe', 'online')->where('status', 'not_verified'),
                                ])
                            </div>

                            <!-- 4. TAB ONLINE TERVERIFIKASI -->
                            <div class="tab-pane fade" id="tab-online-verified" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('tipe', 'online')->whereIn('status', ['verified', 'call']),
                                ])
                            </div>

                            <!-- 5. TAB NO-SHOW (DIPANGGIL TAPI KABUR) -->
                            <div class="tab-pane fade" id="tab-no-show" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('status', 'no_show'),
                                ])
                            </div>

                            <!-- 6. TAB EXPIRED (ONLINE YANG TELAT VERIFIKASI OTP) -->
                            <div class="tab-pane fade" id="tab-expired" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('status', 'expired'),
                                ])
                            </div>

                            <!-- 7. TAB FAILED (GAGAL VERIFIKASI OTP) -->
                            <div class="tab-pane fade" id="tab-failed" role="tabpanel">
                                @include('admin.table-loop', [
                                    'items' => $waitingLists->where('status', 'failed'),
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
