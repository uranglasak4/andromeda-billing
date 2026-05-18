@extends('layouts.nav')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Manajemen Harga & Minimum Charge</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
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
                        @foreach($rules as $rule)
                        <tr>
                            <td class="font-weight-bold text-dark">{{ $rule->name }}</td>
                            <td class="text-muted small">
                                @php
                                    $days = explode(',', $rule->active_days);
                                    $dayNames = [1=>'Sen', 2=>'Sel', 3=>'Rab', 4=>'Kam', 5=>'Jum', 6=>'Sab', 7=>'Min'];
                                    $displayDays = collect($days)->map(fn($d) => $dayNames[$d] ?? $d)->implode(', ');
                                @endphp
                                {{ $displayDays }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($rule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($rule->end_time)->format('H:i') }}</td>
                            <td>Rp {{ number_format($rule->price_per_hour, 0, ',', '.') }}</td>
                            <td class="text-primary font-weight-bold">Rp {{ number_format($rule->min_charge, 0, ',', '.') }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-{{ $rule->id }}">
                                    Edit
                                </button>
                            </td>
                        </tr>

                        <div class="modal modal-blur fade" id="modal-edit-{{ $rule->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('master.pricing.update', $rule->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Harga: {{ $rule->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Harga Per Jam (Rp)</label>
                                                <input type="number" name="price_per_hour" class="form-control" value="{{ $rule->price_per_hour }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Charge (Rp)</label>
                                                <input type="number" name="min_charge" class="form-control" value="{{ $rule->min_charge }}">
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Jam Mulai</label>
                                                        <input type="time" name="start_time" class="form-control" value="{{ $rule->start_time }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Jam Selesai</label>
                                                        <input type="time" name="end_time" class="form-control" value="{{ $rule->end_time }}">
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
                7 => 'Minggu'
            ];
        @endphp

        @foreach($days as $value => $label)
        <label class="form-selectgroup-item">
            <input type="checkbox" name="active_days[]" value="{{ $value }}" class="form-selectgroup-input"
                {{ in_array($value, $activeDaysArray) ? 'checked' : '' }}>
            <span class="form-selectgroup-label">{{ $label }}</span>
        </label>
        @endforeach
    </div>
    <small class="text-muted">Ceklis hari yang termasuk dalam kategori {{ $rule->name }}</small>
</div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
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
    </div>
</div>
@endsection
