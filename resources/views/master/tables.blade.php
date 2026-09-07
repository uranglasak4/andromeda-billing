@extends('layouts.nav')
@section('title', 'Manajemen Meja')
@section('content')
    <div class="page-body">
        <div class="container-xl">

            {{-- 🔔 SWEETALERT FLASH NOTIFICATION --}}
            @if (session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: "{{ session('success') }}",
                            timer: 3000,
                            showConfirmButton: false
                        });
                    });
                </script>
            @endif

            @if (session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Akses Ditolak / Gagal!',
                            text: "{{ session('error') }}",
                            confirmButtonColor: '#d33'
                        });
                    });
                </script>
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
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1">
                                    ⏱️ Sisa Waktu Trigger Nearly (Menit)
                                </label>
                                <div class="input-group">
                                    <input type="number" name="nearly_warning_minutes"
                                        class="form-control form-control-lg fw-bold" value="{{ $nearlyWarningMinutes }}"
                                        min="1" max="60" required>
                                    <span class="input-group-text bg-light fw-bold text-secondary">Menit</span>
                                </div>
                                <small class="form-hint text-muted mt-1 d-block">
                                    Contoh: isi <strong>15</strong> → lampu berkedip 5x saat sisa waktu tinggal 15 menit.
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold mb-0">🎱 Daftar Status Meja Biliar</h3>

                    @php
                        $maxChannels = (int) env('MAX_RELAY_CHANNELS', 16);
                        $isMaxReached = $tables->count() >= $maxChannels;
                    @endphp

                    {{-- 🔴 TOMBOL SAMPAN (MODAL POPUP) & TAMBAH MEJA --}}
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger fw-bold position-relative"
                            data-bs-toggle="modal" data-bs-target="#modalTrashMeja">
                            🗑️ Sampah
                            @if($trashedTables->count() > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $trashedTables->count() }}
                                </span>
                            @endif
                        </button>

                        <button type="button" class="btn btn-primary fw-bold"
                            onclick="handleTambahMejaClick({{ $isMaxReached ? 'true' : 'false' }}, {{ $tables->count() }}, {{ $maxChannels }})">
                            ➕ Tambah Meja Baru
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Meja</th>
                                <th>Relay Channel</th>
                                <th>Status Saat Ini</th>
                                <th>Keterangan Sistem</th>
                                <th class="w-1 text-center">Aksi Kontrol</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tables as $table)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $table->name ?? 'Meja ' . $table->table_number }}</td>
                                    <td><span class="badge bg-outline-secondary">Channel
                                            {{ $table->relay_channel ?? $table->table_number }}</span></td>
                                    <td>
                                        @if ($table->status == 'available')
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
                                        @if ($table->status == 'available')
                                            Meja kosong, siap menerima pemain baru.
                                        @elseif($table->status == 'maintenance')
                                            <span class="text-danger">Meja dikunci. Kasir tidak bisa membuka billing.</span>
                                        @elseif($table->status == 'nearly')
                                            <span class="text-warning fw-bold">⚠️ Waktu billing hampir habis. Lampu sudah berkedip 5x.</span>
                                        @else
                                            <span class="text-muted">Sedang digunakan transaksi aktif.</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-list flex-nowrap justify-content-center">
                                            @if (in_array($table->status, ['available', 'maintenance']))
                                                {{-- ✏️ TOMBOL EDIT MEJA --}}
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditMeja{{ $table->id }}">
                                                    ✏️ Edit
                                                </button>

                                                {{-- TOMBOL SET MAINTENANCE --}}
                                                <form action="{{ route('master.tables.maintenance', $table->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @if ($table->status == 'available')
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                            onclick="return confirm('Set meja ini ke MAINTENANCE?')">
                                                            Maintenance
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            Aktifkan
                                                        </button>
                                                    @endif
                                                </form>

                                                {{-- 🗑️ TOMBOL HAPUS MEJA --}}
                                                <form id="form-delete-{{ $table->id }}"
                                                    action="{{ route('master.tables.destroy', $table->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmHapusMeja({{ $table->id }}, {{ $table->table_number }})">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-sm btn-secondary" disabled
                                                    title="Meja yang sedang terisi tidak bisa dihapus atau diedit">
                                                    🔒 Meja Aktif
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- 🪟 MODAL POPUP EDIT MEJA --}}
                                @if (in_array($table->status, ['available', 'maintenance']))
                                    <div class="modal fade" id="modalEditMeja{{ $table->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title fw-bold">✏️ Edit Meja Biliar #{{ $table->table_number }}</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('master.tables.update', $table->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Nomor Meja</label>
                                                            <input type="number" name="table_number" class="form-control"
                                                                value="{{ $table->table_number }}" min="1" required>
                                                            <small class="text-muted">Nomor ini yang akan tampil di aplikasi kasir.</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Relay Channel (Hardware)</label>
                                                            <input type="number" name="relay_channel" class="form-control"
                                                                value="{{ $table->relay_channel }}" min="1"
                                                                max="{{ env('MAX_RELAY_CHANNELS', 16) }}" required>
                                                            <small class="text-muted">Nomor pin relay pada modul hardware Arduino/Relay Board (Maks: {{ env('MAX_RELAY_CHANNELS', 16) }}).</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary fw-bold ms-auto">💾 Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada meja yang terdaftar.
                                        Silakan klik Tambah Meja Baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- 🪟 MODAL POPUP TAMBAH MEJA BARU --}}
    <div class="modal fade" id="modalTambahMeja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">➕ Tambah Meja Biliar Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('master.tables.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Meja</label>
                            <input type="number" name="table_number" class="form-control" placeholder="Contoh: 1, 2, 17"
                                min="1" required>
                            <small class="text-muted">Nomor ini yang akan tampil di aplikasi kasir.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Relay Channel (Hardware)</label>
                            <input type="number" name="relay_channel" class="form-control"
                                placeholder="Contoh: 1 - {{ env('MAX_RELAY_CHANNELS', 16) }}" min="1"
                                max="{{ env('MAX_RELAY_CHANNELS', 16) }}" required>
                            <small class="text-muted">Nomor pin relay pada modul hardware Arduino/Relay Board (Maks:
                                {{ env('MAX_RELAY_CHANNELS', 16) }}).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold ms-auto">💾 Simpan Meja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 🪟 MODAL POPUP SAMPAH / RECYCLE BIN --}}
    <div class="modal fade" id="modalTrashMeja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">🗑️ Recycle Bin (Daftar Meja Terhapus)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table text-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Meja</th>
                                    <th>Relay Channel</th>
                                    <th>Waktu Dihapus</th>
                                    <th class="w-1 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trashedTables as $trash)
                                    <tr>
                                        <td class="text-muted fw-bold">#{{ $trash->id }}</td>
                                        <td class="fw-bold text-dark">{{ $trash->name ?? 'Meja ' . $trash->table_number }}</td>
                                        <td><span class="badge bg-outline-secondary">Channel {{ $trash->relay_channel }}</span></td>
                                        <td class="text-muted small">{{ $trash->deleted_at->format('d M Y H:i') }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('master.tables.restore', $trash->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success fw-bold"
                                                    onclick="return confirm('Pulihkan meja ini kembali ke sistem aktif?')">
                                                    ♻️ Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Tidak ada data meja di Recycle Bin.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- 📜 SCRIPT SWEETALERT DAN MODAL HANDLER --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function handleTambahMejaClick(isMaxReached, currentTotal, maxLimit) {
            if (isMaxReached) {
                Swal.fire({
                    icon: 'error',
                    title: 'Batas Maksimum Tercapai!',
                    html: `Jumlah meja saat ini (<b>${currentTotal} meja</b>) sudah mencapai batas maksimum modul relay di file .env (<b>${maxLimit} Channel</b>).<br><br><small class="text-muted">Untuk menambah meja, silakan perbarui MAX_RELAY_CHANNELS di file .env dan firmware Arduino.</small>`,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Tutup'
                });
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('modalTambahMeja'));
                myModal.show();
            }
        }

        function confirmHapusMeja(id, tableNumber) {
            Swal.fire({
                title: `Hapus Meja ${tableNumber}?`,
                text: "Meja ini akan disembunyikan dari sistem (Soft Delete). Data transaksi lama tetap tersimpan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`form-delete-${id}`).submit();
                }
            });
        }
    </script>
@endsection
