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

    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h3 class="card-title text-white fw-bold mb-0">🛠️ KUSTOMISASI SISTEM (ROLE MASTER)</h3>
                </div>
                <div class="card-body py-4">
                    <p class="text-muted small">
                        Halaman ini khusus untuk Owner (Master) mengatur jalannya regulasi antrean secara dinamis tanpa mengubah kodingan program.
                    </p>

                    <form action="{{ route('master.waitinglist.update') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">⏱️ Batas Waktu Verifikasi (Menit)</label>
                            <div class="input-group">
                                <input type="number" name="verification_time" class="form-control form-control-lg fw-bold" value="{{ $verificationTime }}" min="1" required>
                                <span class="input-group-text bg-light fw-bold text-secondary">Menit</span>
                            </div>
                            <small class="form-hint text-muted mt-1">
                                Jika pelanggan mendaftar online via website dan tidak melapor ke kasir dalam waktu ini, namanya otomatis hangus dari antrean.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">📊 Batas Kuota Maksimal Antrean Online</label>
                            <div class="input-group">
                                <input type="number" name="max_online_queue" class="form-control form-control-lg fw-bold" value="{{ $maxOnlineQueue }}" min="1" required>
                                <span class="input-group-text bg-light fw-bold text-secondary">Customer</span>
                            </div>
                            <small class="form-hint text-muted mt-1">
                                Batas jumlah maksimal pengantre online yang terdaftar bersamaan di website monitor. Jika penuh, tombol daftar mandiri di website otomatis terkunci.
                            </small>
                        </div>

                        <hr class="my-3">

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="font-size: 15px;">
                            💾 SIMPAN KONFIGURASI BARU
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
