@extends('layouts.nav')
@section('title', 'Manajemen Harga & Paket')
@section('content')
    <div class="page-body">
        <div class="container-xl">

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Aturan Harga Reguler & Minimum Charge</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Nama Aturan</th>
                                <th>Hari Aktif</th>
                                <th>Rentang Jam</th>
                                <th>Harga / Jam</th>
                                <th>Min. Charge</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rules as $rule)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $rule->name }}</td>
                                    <td class="text-muted small">
                                        @php
                                            $days = explode(',', $rule->active_days);
                                            $dayNames = [
                                                1 => 'Sen',
                                                2 => 'Sel',
                                                3 => 'Rab',
                                                4 => 'Kam',
                                                5 => 'Jum',
                                                6 => 'Sab',
                                                7 => 'Min',
                                            ];
                                            $displayDays = collect($days)
                                                ->map(fn($d) => $dayNames[$d] ?? $d)
                                                ->implode(', ');
                                        @endphp
                                        {{ $displayDays }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($rule->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($rule->end_time)->format('H:i') }}</td>
                                    <td>Rp {{ number_format($rule->price_per_hour, 0, ',', '.') }}</td>
                                    <td class="text-primary font-weight-bold">Rp
                                        {{ number_format($rule->min_charge, 0, ',', '.') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#modal-edit-{{ $rule->id }}">
                                            Edit
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal modal-blur fade" id="modal-edit-{{ $rule->id }}" tabindex="-1"
                                    role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <form action="{{ route('master.pricing.update', $rule->id) }}" method="POST"
                                                id="form-edit-rule-{{ $rule->id }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Harga: {{ $rule->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Harga Per Jam (Rp)</label>
                                                        <input type="number" name="price" class="form-control"
                                                            value="{{ $rule->price_per_hour }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Minimum Charge (Rp)</label>
                                                        <input type="number" name="min_charge" class="form-control"
                                                            value="{{ $rule->min_charge }}">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Jam Mulai</label>
                                                                <input type="time" name="start_time" class="form-control"
                                                                    value="{{ $rule->start_time }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Jam Selesai</label>
                                                                <input type="time" name="end_time" class="form-control"
                                                                    value="{{ $rule->end_time }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Hari Aktif</label>
                                                        <div class="form-selectgroup">
                                                            @php
                                                                $activeDaysArray = explode(',', $rule->active_days);
                                                                $days = [
                                                                    1 => 'Senin',
                                                                    2 => 'Selasa',
                                                                    3 => 'Rabu',
                                                                    4 => 'Kamis',
                                                                    5 => 'Jumat',
                                                                    6 => 'Sabtu',
                                                                    7 => 'Minggu',
                                                                ];
                                                            @endphp

                                                            @foreach ($days as $value => $label)
                                                                <label class="form-selectgroup-item">
                                                                    <input type="checkbox" name="active_days[]"
                                                                        value="{{ $value }}"
                                                                        class="form-selectgroup-input"
                                                                        {{ in_array($value, $activeDaysArray) ? 'checked' : '' }}>
                                                                    <span
                                                                        class="form-selectgroup-label">{{ $label }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        <small class="text-muted">Ceklis hari yang termasuk dalam kategori
                                                            {{ $rule->name }}</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-link link-secondary me-auto"
                                                        data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header justify-content-between">
                    <h3 class="card-title">Daftar Paket Promo (Packages)</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-package">+ Tambah
                        Paket</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Paket</th>
                                <th>Harga Paket</th>
                                <th>Tipe Hari</th>
                                <th>Jam Berlaku</th>
                                <th>Durasi Main</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $pkg)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $pkg->name }}</td>
                                    <td>Rp {{ number_format($pkg->price, 0, ',', '.') }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $pkg->day_type == 'both' ? 'bg-purple-lt' : ($pkg->day_type == 'weekend' ? 'bg-orange-lt' : 'bg-green-lt') }}">
                                            {{ strtoupper($pkg->day_type) }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($pkg->active_from)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($pkg->active_to)->format('H:i') }}</td>
                                    <td>
                                        @if ($pkg->duration_type == 'minutes')
                                            {{ $pkg->duration_value / 60 }} Jam ({{ $pkg->duration_value }} Menit)
                                        @else
                                            Selesai Jam {{ $pkg->duration_value }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#modal-edit-package" data-id="{{ $pkg->id }}"
                                                data-name="{{ $pkg->name }}" data-price="{{ $pkg->price }}"
                                                data-daytype="{{ $pkg->day_type }}" data-from="{{ $pkg->active_from }}"
                                                data-to="{{ $pkg->active_to }}" data-durtype="{{ $pkg->duration_type }}"
                                                data-durval="{{ $pkg->duration_value }}"
                                                data-fnbs="{{ json_encode($pkg->fnbProducts) }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('master.package.destroy', $pkg->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus paket ini?')">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-add-package" tabindex="-1">
        <div class="modal-dialog" role="document">
            <form action="{{ route('master.package.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Paket Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Paket</label>
                        <input type="text" name="name" class="form-control"
                            placeholder="Contoh: Paket 2 Jam + Minum" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Paket (Total)</label>
                                <input type="number" name="price" class="form-control" placeholder="60000" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Tipe Hari</label>
                                <select name="day_type" class="form-select" required>
                                    <option value="weekday">Weekday (Senin - Kamis)</option>
                                    <option value="weekend">Weekend (Jumat - Minggu)</option>
                                    <option value="both">Both (Setiap Hari)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Mulai Berlaku</label>
                                <input type="time" name="active_from" class="form-control" value="10:00" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Akhir Berlaku</label>
                                <input type="time" name="active_to" class="form-control" value="23:59" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Tipe Durasi</label>
                                <select name="duration_type" class="form-select" required>
                                    <option value="minutes">Berdasarkan Menit</option>
                                    <option value="fixed_end_time">Jam Selesai Tetap</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Nilai Durasi</label>
                                <input type="text" name="duration_value" class="form-control"
                                    placeholder="Misal: 120" required>
                                <small class="text-muted">Isi <b>120</b> untuk durasi main selama 2 jam.</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Include Produk FnB (Opsional)</label>
                        <div id="fnb-package-wrapper">
                            <div class="row g-2 mb-2 fnb-item-row">
                                <div class="col-7">
                                    <select name="fnb_products[]" class="form-select">
                                        <option value="">-- Pilih Produk FnB --</option>
                                        @foreach ($allFnbProducts as $fnb)
                                            <option value="{{ $fnb->id }}">{{ $fnb->name }} (Stok:
                                                {{ $fnb->stock }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="fnb_quantities[]" class="form-control" placeholder="Qty"
                                        min="1" value="1">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-outline-danger w-100 remove-fnb-row">✕</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="add-fnb-row">
                            ➕ Tambah FnB Lainnya
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Paket</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-edit-package" tabindex="-1">
        <div class="modal-dialog" role="document">
            <form id="form-edit-package" action="" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Paket Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Paket</label>
                        <input type="text" name="name" id="edit-pkg-name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Paket</label>
                                <input type="number" name="price" id="edit-pkg-price" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Tipe Hari</label>
                                <select name="day_type" id="edit-pkg-daytype" class="form-select" required>
                                    <option value="weekday">Weekday</option>
                                    <option value="weekend">Weekend</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="active_from" id="edit-pkg-from" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Akhir</label>
                                <input type="time" name="active_to" id="edit-pkg-to" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Tipe Durasi</label>
                                <select name="duration_type" id="edit-pkg-durtype" class="form-select" required>
                                    <option value="minutes">Berdasarkan Menit</option>
                                    <option value="fixed_end_time">Jam Selesai Tetap</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Nilai Durasi</label>
                                <input type="text" name="duration_value" id="edit-pkg-durval" class="form-control"
                                    required>
                            </div>
                        </div>
                    </div>
                    <!-- Sisipkan di dalam modal-body Edit Paket (di bawah Nilai Durasi) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Include Produk FnB</label>
                        <div id="edit-fnb-package-wrapper">
                            <!-- Input FnB dinamis akan di-inject oleh JS saat tombol Edit diklik -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="add-edit-fnb-row">
                            ➕ Tambah FnB Lainnya
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ==========================================================
            // 1. LOGIKA TAMBAH & HAPUS ROW FNB (MODAL TAMBAH PAKET BARU)
            // ==========================================================
            const fnbWrapper = document.getElementById('fnb-package-wrapper');
            const addFnbBtn = document.getElementById('add-fnb-row');

            if (addFnbBtn && fnbWrapper) {
                addFnbBtn.addEventListener('click', function() {
                    const firstRow = fnbWrapper.querySelector('.fnb-item-row');
                    if (!firstRow) return;

                    const newRow = firstRow.cloneNode(true);
                    const select = newRow.querySelector('select');
                    const input = newRow.querySelector('input[type="number"]');
                    if (select) select.selectedIndex = 0;
                    if (input) input.value = 1;

                    fnbWrapper.appendChild(newRow);
                });

                fnbWrapper.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('remove-fnb-row')) {
                        const rows = fnbWrapper.querySelectorAll('.fnb-item-row');
                        if (rows.length > 1) {
                            e.target.closest('.fnb-item-row').remove();
                        } else {
                            const row = rows[0];
                            const select = row.querySelector('select');
                            const input = row.querySelector('input[type="number"]');
                            if (select) select.selectedIndex = 0;
                            if (input) input.value = 1;
                        }
                    }
                });
            }

            // ==========================================================
            // 2. LOGIKA EDIT HARGA REGULER
            // ==========================================================
            const editRuleModal = document.getElementById('modal-edit-rule');
            if (editRuleModal) {
                editRuleModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const price = button.getAttribute('data-price');
                    const min = button.getAttribute('data-min');
                    const from = button.getAttribute('data-from');
                    const to = button.getAttribute('data-to');

                    const form = document.getElementById('form-edit-rule');
                    if (form) {
                        form.action = `/master/pricing/update/${id}`;
                    }

                    if (document.getElementById('edit-rule-name')) document.getElementById('edit-rule-name')
                        .value = name;
                    if (document.getElementById('edit-rule-price')) document.getElementById(
                        'edit-rule-price').value = price;
                    if (document.getElementById('edit-rule-min')) document.getElementById('edit-rule-min')
                        .value = min;
                    if (document.getElementById('edit-rule-start')) document.getElementById(
                        'edit-rule-start').value = from ? from.substring(0, 5) : '';
                    if (document.getElementById('edit-rule-end')) document.getElementById('edit-rule-end')
                        .value = to ? to.substring(0, 5) : '';
                });
            }

            // ==========================================================
            // 3. LOGIKA UNTUK MODAL EDIT PAKET PROMO (LENGKAP DENGAN FNB)
            // ==========================================================
            // Template opsi dropdown produk FnB dari server
            const fnbOptionsHtml = `@foreach ($allFnbProducts as $fnb)
            <option value="{{ $fnb->id }}">{{ $fnb->name }} (Stok: {{ $fnb->stock }})</option>
        @endforeach`;

            const editPackageModal = document.getElementById('modal-edit-package');
            if (editPackageModal) {
                editPackageModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;

                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const price = button.getAttribute('data-price');
                    const dayType = button.getAttribute('data-daytype');
                    const from = button.getAttribute('data-from');
                    const to = button.getAttribute('data-to');
                    const durType = button.getAttribute('data-durtype');
                    const durVal = button.getAttribute('data-durval');
                    const fnbs = JSON.parse(button.getAttribute('data-fnbs') || '[]');

                    const form = document.getElementById('form-edit-package');
                    if (form) {
                        form.action = `/master/packages/update/${id}`;
                    }

                    document.getElementById('edit-pkg-name').value = name;
                    document.getElementById('edit-pkg-price').value = price;
                    document.getElementById('edit-pkg-daytype').value = dayType;
                    document.getElementById('edit-pkg-from').value = from ? from.substring(0, 5) : '';
                    document.getElementById('edit-pkg-to').value = to ? to.substring(0, 5) : '';
                    document.getElementById('edit-pkg-durtype').value = durType;
                    document.getElementById('edit-pkg-durval').value = durVal;

                    // Render otomatis daftar produk FnB yang ada pada paket ke dalam modal
                    const wrapper = document.getElementById('edit-fnb-package-wrapper');
                    if (wrapper) {
                        wrapper.innerHTML = ''; // Clear item lama
                        if (fnbs.length > 0) {
                            fnbs.forEach(item => {
                                appendFnbRowToEdit(wrapper, item.id, item.pivot.quantity);
                            });
                        } else {
                            appendFnbRowToEdit(wrapper, '', 1);
                        }
                    }
                });
            }

            // Fungsi pembantu untuk membuat baris FnB di modal edit
            function appendFnbRowToEdit(wrapper, selectedId = '', qty = 1) {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'row g-2 mb-2 edit-fnb-item-row';
                rowDiv.innerHTML = `
                <div class="col-7">
                    <select name="fnb_products[]" class="form-select">
                        <option value="">-- Pilih Produk FnB --</option>
                        ${fnbOptionsHtml}
                    </select>
                </div>
                <div class="col-3">
                    <input type="number" name="fnb_quantities[]" class="form-control" placeholder="Qty" min="1" value="${qty}">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-edit-fnb-row">✕</button>
                </div>
            `;

                if (selectedId) {
                    const select = rowDiv.querySelector('select');
                    select.value = selectedId;
                }

                wrapper.appendChild(rowDiv);
            }

            // Event listener klik tombol "+ Tambah FnB Lainnya" di modal edit
            const addEditFnbBtn = document.getElementById('add-edit-fnb-row');
            if (addEditFnbBtn) {
                addEditFnbBtn.addEventListener('click', function() {
                    const wrapper = document.getElementById('edit-fnb-package-wrapper');
                    if (wrapper) appendFnbRowToEdit(wrapper, '', 1);
                });
            }

            // Event listener tombol hapus (✕) di modal edit
            const editFnbWrapper = document.getElementById('edit-fnb-package-wrapper');
            if (editFnbWrapper) {
                editFnbWrapper.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('remove-edit-fnb-row')) {
                        const rows = editFnbWrapper.querySelectorAll('.edit-fnb-item-row');
                        if (rows.length > 1) {
                            e.target.closest('.edit-fnb-item-row').remove();
                        } else {
                            const row = rows[0];
                            row.querySelector('select').value = '';
                            row.querySelector('input').value = 1;
                        }
                    }
                });
            }

        });
    </script>
@endsection
