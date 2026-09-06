@extends('layouts.nav')
@section('title', 'Manajemen Akun')
@section('content')
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
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>{{ $u->name }}</td>
                                    <td><span class="text-muted">{{ $u->username }}</span></td>
                                    <td>
                                        @if ($u->id === 1)
                                            <span class="badge text-dark font-weight-bold" style="background-color: #ffd700;">
                                                OWNER
                                            </span>
                                        @else
                                            <span class="badge {{ $u->role === 'master' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                                {{ strtoupper($u->role) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($u->is_active)
                                            <span class="badge bg-success-lt">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-lt">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($u->last_login_at)
                                            <span class="text-muted">{{ $u->last_login_at->format('d M Y, H:i') }}</span>
                                        @else
                                            <span class="text-muted small">Belum pernah login</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#modal-edit-user" data-id="{{ $u->id }}"
                                            data-name="{{ $u->name }}" data-username="{{ $u->username }}"
                                            data-role="{{ $u->role }}" data-active="{{ $u->is_active ? 1 : 0 }}">
                                            Edit / Reset
                                        </button>

                                        @if ($u->id !== 1 && (auth()->id() === 1 || $u->role !== 'master'))
                                            <form action="{{ route('master.users.toggle-status', $u->id) }}" method="POST" class="d-inline ms-1">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    onclick="return confirm('Ubah status akun {{ $u->name }}?')">
                                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data akun pengguna.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH USER --}}
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
                            <input type="text" class="form-control" name="name" required
                                placeholder="Masukkan nama lengkap staff...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Username (Untuk Login)</label>
                            <input type="text" class="form-control" name="username" required
                                placeholder="Contoh: kasir_andromeda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Password Awal</label>
                            <input type="password" class="form-control" name="password" required
                                placeholder="Minimal 4 karakter...">
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

    {{-- MODAL EDIT USER --}}
    <div class="modal modal-blur fade" id="modal-edit-user" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Informasi Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-edit-user" method="POST" action="">
                    @csrf
                    @method('PUT')
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
                            <input type="password" class="form-control" name="password"
                                placeholder="Isi password baru jika ingin diganti...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Hak Akses (Role)</label>
                            <select class="form-select" name="role" id="edit-role" required>
                                <option value="admin">Admin (Kasir Lapangan)</option>
                                <option value="master">Master (Owner/Manager)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Status Akun</label>
                            <select class="form-select" name="is_active" id="edit-active" required>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </form>

                <form id="form-delete-user" method="POST" action="" class="d-none"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun staff ini?')">
                    @csrf
                    @method('DELETE')
                </form>

                <div class="modal-footer d-flex justify-content-between">
                    <div id="wrapper-btn-delete">
                        <button type="submit" form="form-delete-user" id="btn-delete-user"
                            class="btn btn-danger d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-tabler icon-tabler-trash">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 7l16 0" />
                                <path d="M10 11l0 6" />
                                <path d="M14 11l0 6" />
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            </svg>
                            Hapus Akun
                        </button>
                    </div>

                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="form-edit-user" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('modal-edit-user');
            const createModal = document.getElementById('modal-add-user');

            // ID User Login
            const currentUserId = Number("{{ auth()->id() }}");
            const isOwner = (currentUserId === 1); // Hanya Owner Utama (ID 1)

            // Helper function agar nilai input disabled tetap terkirim saat submit form
            function ensureHiddenInput(form, name, value, shouldAdd) {
                let hiddenInput = form.querySelector(`input[type="hidden"][name="${name}"]`);
                if (shouldAdd) {
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = name;
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = value;
                } else if (hiddenInput) {
                    hiddenInput.remove();
                }
            }

            // ==========================================
            // 1. LOGIKA MODAL TAMBAH AKUN (CREATE)
            // ==========================================
            if (createModal) {
                createModal.addEventListener('show.bs.modal', function() {
                    const addRoleSelect = createModal.querySelector('select[name="role"]');
                    const form = createModal.querySelector('form');

                    if (addRoleSelect) {
                        if (!isOwner) {
                            addRoleSelect.value = 'admin';
                            addRoleSelect.disabled = true;
                            ensureHiddenInput(form, 'role', 'admin', true);
                        } else {
                            addRoleSelect.disabled = false;
                            ensureHiddenInput(form, 'role', '', false);
                        }
                    }
                });
            }

            // ==========================================
            // 2. LOGIKA MODAL EDIT AKUN (UPDATE)
            // ==========================================
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;

                    const id = Number(button.getAttribute('data-id'));
                    const name = button.getAttribute('data-name');
                    const username = button.getAttribute('data-username');
                    const role = button.getAttribute('data-role');
                    const active = button.getAttribute('data-active');

                    // Set Action Form
                    document.getElementById('form-edit-user').action = `/master/users/update/${id}`;
                    document.getElementById('form-delete-user').action = `/master/users/delete/${id}`;

                    // Set Value Form Input
                    document.getElementById('edit-name').value = name;
                    document.getElementById('edit-username').value = username;
                    document.getElementById('edit-role').value = role;
                    document.getElementById('edit-active').value = active;

                    const btnDelete = document.getElementById('btn-delete-user');
                    const wrapperBtnDelete = document.getElementById('wrapper-btn-delete');
                    const selectRole = document.getElementById('edit-role');
                    const selectActive = document.getElementById('edit-active');
                    const inputName = document.getElementById('edit-name');
                    const inputUsername = document.getElementById('edit-username');
                    const inputPassword = editModal.querySelector('input[name="password"]');
                    const btnSubmitEdit = editModal.querySelector('button[type="submit"][form="form-edit-user"]');
                    const editForm = document.getElementById('form-edit-user');

                    const isSelf = (id === currentUserId);
                    const isTargetMaster = (role === 'master');

                    // A. KONTROL TOMBOL HAPUS
                    if (isSelf || (isTargetMaster && !isOwner)) {
                        if (btnDelete) {
                            btnDelete.disabled = true;
                            btnDelete.classList.add('d-none');
                        }
                        if (wrapperBtnDelete) wrapperBtnDelete.classList.add('d-none');
                    } else {
                        if (btnDelete) {
                            btnDelete.disabled = false;
                            btnDelete.classList.remove('d-none');
                        }
                        if (wrapperBtnDelete) wrapperBtnDelete.classList.remove('d-none');
                    }

                    // B. KONTROL INPUT FORM & DROPDOWN ROLE/STATUS
                    if (!isOwner && isTargetMaster && !isSelf) {
                        // Jika Manager buka profil Master lain -> Kunci Semua Form
                        inputName.disabled = true;
                        inputUsername.disabled = true;
                        if (inputPassword) inputPassword.disabled = true;
                        selectRole.disabled = true;
                        selectActive.disabled = true;
                        if (btnSubmitEdit) btnSubmitEdit.disabled = true;

                        ensureHiddenInput(editForm, 'role', '', false);
                        ensureHiddenInput(editForm, 'is_active', '', false);
                    } else {
                        // Buka input umum
                        inputName.disabled = false;
                        inputUsername.disabled = false;
                        if (inputPassword) inputPassword.disabled = false;
                        if (btnSubmitEdit) btnSubmitEdit.disabled = false;

                        // Kontrol Dropdown Status (Owner ID 1 tidak bisa dinonaktifkan)
                        if (id === 1) {
                            selectActive.disabled = true;
                            ensureHiddenInput(editForm, 'is_active', '1', true);
                        } else {
                            selectActive.disabled = false;
                            ensureHiddenInput(editForm, 'is_active', '', false);
                        }

                        // Kontrol Dropdown Role (Hanya Owner ID 1 yang bisa ubah role)
                        if (!isOwner || isSelf) {
                            selectRole.disabled = true;
                            ensureHiddenInput(editForm, 'role', role, true);
                        } else {
                            selectRole.disabled = false;
                            ensureHiddenInput(editForm, 'role', '', false);
                        }
                    }
                });
            }
        });
    </script>
@endsection
