@extends('layouts.nav')
@section('title', 'Manajemen Akun')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manajemen Akun Pengguna (Staff / Kasir)
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Akun Sistem</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-user">
                        + Tambah Akun Baru
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Hak Akses (Role)</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>{{ $u->name }}</td>
                                    <td><span class="text-muted">{{ $u->username }}</span></td>
                                    <td>
                                        <span class="badge {{ $u->role === 'master' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                            {{ strtoupper($u->role) }}
                                        </span>
                                    </td>
                                    <td>{{ $u->created_at->format('d M Y, H:i') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#modal-edit-user"
                                            data-id="{{ $u->id }}"
                                            data-name="{{ $u->name }}"
                                            data-username="{{ $u->username }}"
                                            data-role="{{ $u->role }}">
                                            Edit / Reset
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Tidak ada data akun pengguna.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-add-user" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('master.users.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Akun Pengguna Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" required placeholder="Masukkan nama lengkap staff...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Username (Untuk Login)</label>
                            <input type="text" class="form-control" name="username" required placeholder="Contoh: kasir_andromeda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Password Awal</label>
                            <input type="password" class="form-control" name="password" required placeholder="Minimal 4 karakter...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Hak Akses (Role)</label>
                            <select class="form-select" name="role" required>
                                <option value="admin">Admin (Kasir Lapangan)</option>
                                <option value="master">Master (Owner/Manager)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary ms-auto">Simpan Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-edit-user" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Informasi Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-edit-user" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Username</label>
                            <input type="text" class="form-control" name="username" id="edit-username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ganti Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" class="form-control" name="password" placeholder="Isi password baru jika ingin diganti...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Hak Akses (Role)</label>
                            <select class="form-select" name="role" id="edit-role" required>
                                <option value="admin">Admin (Kasir Lapangan)</option>
                                <option value="master">Master (Owner/Manager)</option>
                            </select>
                        </div>
                    </div>
                </form>

                <form id="form-delete-user" method="POST" action="" class="d-none" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun staff ini?')">
                    @csrf
                    @method('DELETE')
                </form>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="submit" form="form-delete-user" class="btn btn-danger d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                        Hapus Akun
                    </button>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="form-edit-user" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('modal-edit-user');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const username = button.getAttribute('data-username');
                const role = button.getAttribute('data-role');

                // Atur Target URL Action Form
                document.getElementById('form-edit-user').action = `/master/users/update/${id}`;
                document.getElementById('form-delete-user').action = `/master/users/delete/${id}`;

                // Pasang data bawaan ke field input modal
                document.getElementById('edit-name').value = name;
                document.getElementById('edit-username').value = username;
                document.getElementById('edit-role').value = role;
            });
        });
    </script>
@endsection
