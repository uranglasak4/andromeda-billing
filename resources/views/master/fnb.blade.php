@extends('layouts.nav')
@section('title', 'Manejemen FnB')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manajemen FnB (Food & Beverage)
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

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Semua Produk FnB</h3>

                    <form action="{{ route('master.fnb') }}" method="GET" class="d-flex gap-2">
                        <select class="form-select w-auto" name="category_id" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            @foreach ($dropdownCategories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="text" class="form-control w-auto" name="search" value="{{ request('search') }}"
                            placeholder="Cari nama...">
                        <button type="submit" class="btn btn-icon btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-tabler icon-tabler-search">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modal-add-product">
                            + Tambah Produk
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable" id="table-products">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>HPP</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ $p->category->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($p->hpp, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $p->stock <= $p->min_stock ? 'bg-danger' : 'bg-success' }}">
                                            {{ $p->stock }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#modal-edit-product" data-id="{{ $p->id }}"
                                            data-name="{{ $p->name }}" data-hpp="{{ $p->hpp }}"
                                            data-price="{{ $p->price }}" data-stock="{{ $p->stock }}"
                                            data-minstock="{{ $p->min_stock }}" data-category="{{ $p->fnb_category_id }}">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data produk ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center gap-3">
                    <div>
                        {{ $products->links('pagination::bootstrap-4') }}
                    </div>
                    <p class="m-0 text-muted">
                        Showing <span>{{ $products->firstItem() ?? 0 }}</span> to
                        <span>{{ $products->lastItem() ?? 0 }}</span> of <span>{{ $products->total() }}</span> entries
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Kategori</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-category">
                        + Tambah Kategori
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Jumlah Produk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allCategories as $cat)
                                <tr>
                                    <td>{{ $cat->name }}</td>
                                    <td>{{ $cat->products_count ?? $cat->products()->count() }}</td>
                                    <td>
                                        <form action="{{ route('master.fnb.category.destroy', $cat->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus kategori ini? Produk di dalamnya akan kehilangan kategori.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center gap-3">
                    <div>
                        {{ $allCategories->links('pagination::bootstrap-4') }}
                    </div>
                    <p class="m-0 text-muted">
                        Showing <span>{{ $allCategories->firstItem() ?? 0 }}</span> to
                        <span>{{ $allCategories->lastItem() ?? 0 }}</span> of <span>{{ $allCategories->total() }}</span>
                        entries
                    </p>
                </div>
            </div>

        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-add-category" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('master.fnb.category.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kategori Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Kategori</label>
                            <input type="text" class="form-control" name="name" required
                                placeholder="Contoh: Snack, Coffee Base, Makanan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary ms-auto">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-add-product" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('master.fnb.product.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Produk FnB Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Produk</label>
                            <input type="text" class="form-control" name="name" required
                                placeholder="Masukkan nama produk/menu...">
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Kategori</label>
                                    <select class="form-select" name="fnb_category_id" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($dropdownCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Harga Jual (Rp)</label>
                                    <input type="number" class="form-control" name="price" required
                                        placeholder="Contoh: 25000">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">HPP (Modal Rp)</label>
                                    <input type="number" class="form-control" name="hpp" required
                                        placeholder="Contoh: 15000">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Stok Awal</label>
                                    <input type="number" class="form-control" name="stock" value="0" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Minimal Stok</label>
                                    <input type="number" class="form-control" name="min_stock" value="5" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary ms-auto">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-edit-product" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk FnB</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-edit-product" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Produk</label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Kategori</label>
                                    <select class="form-select" name="fnb_category_id" id="edit-category" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($dropdownCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Harga Jual (Rp)</label>
                                    <input type="number" class="form-control" name="price" id="edit-price" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">HPP (Modal Rp)</label>
                                    <input type="number" class="form-control" name="hpp" id="edit-hpp" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Stok Sekarang</label>
                                    <input type="number" class="form-control" name="stock" id="edit-stock" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Minimal Stok</label>
                                    <input type="number" class="form-control" name="min_stock" id="edit-minstock"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <form id="form-delete-product" method="POST" action="" class="d-none"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                    @csrf
                    @method('DELETE')
                </form>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="submit" form="form-delete-product"
                        class="btn btn-danger d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 7l16 0" />
                            <path d="M10 11l0 6" />
                            <path d="M14 11l0 6" />
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                        </svg>
                        Hapus Produk
                    </button>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="form-edit-product" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('modal-edit-product');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const hpp = button.getAttribute('data-hpp');
                const price = button.getAttribute('data-price');
                const stock = button.getAttribute('data-stock');
                const minstock = button.getAttribute('data-minstock');
                const category = button.getAttribute('data-category');

                // Atur Target URL Form Edit & Delete
                document.getElementById('form-edit-product').action = `/master/fnb/product/update/${id}`;
                document.getElementById('form-delete-product').action = `/master/fnb/product/delete/${id}`;

                // Inject Nilai Bawaan ke Input
                document.getElementById('edit-name').value = name;
                document.getElementById('edit-hpp').value = hpp;
                document.getElementById('edit-price').value = price;
                document.getElementById('edit-stock').value = stock;
                document.getElementById('edit-minstock').value = minstock;
                document.getElementById('edit-category').value = category;
            });
        });
    </script>
@endsection
