@extends('layouts.nav')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Manajemen FnB (Food & Beverage)</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <div class="card mb-4">
            <div class="card-header justify-content-between">
        <h3 class="card-title">Daftar Semua Produk FnB</h3>
        <div class="d-flex gap-2 align-items-center">
            <form action="{{ route('master.fnb') }}" method="GET" class="d-flex gap-2">
                <select name="category_id" class="form-select" style="min-width: 180px;" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($allCategories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <input type="text" name="search" class="form-control" style="min-width: 200px;" placeholder="Cari nama..." value="{{ request('search') }}">

                <button type="submit" class="btn btn-primary btn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                </button>
            </form>

            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-product">
                + Tambah Produk
            </button>
        </div>
    </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
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
                        @foreach($products as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td><span class="badge bg-blue-lt">{{ $p->category->name }}</span></td>
                            <td>Rp {{ number_format($p->hpp) }}</td>
                            <td>Rp {{ number_format($p->price) }}</td>
                            <td>
                                <span class="badge {{ $p->stock <= $p->min_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ $p->stock }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-product"
                                    data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-hpp="{{ $p->hpp }}"
                                    data-price="{{ $p->price }}" data-stock="{{ $p->stock }}" data-minstock="{{ $p->min_stock }}"
                                    data-category="{{ $p->fnb_category_id }}">Edit</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $products->appends(['search' => request('search'), 'category_id' => request('category_id')])->links() }}
            </div>
        </div>

        <div class="card">
            <div class="card-header justify-content-between">
        <h3 class="card-title">Daftar Kategori</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-category">
            + Tambah Kategori
        </button>
    </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                            <th>Jumlah Produk</th>
                            <th class="w-1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                        <tr>
                            <td>{{ $cat->name }}</td>
                            <td class="text-muted">{{ $cat->products->count() }} Item</td>
                            <td>
                                <form action="{{ route('master.fnb.category.destroy', $cat->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $categories->links() }}
            </div>
        </div>

    </div>
</div>

<div class="modal modal-blur fade" id="modal-category" tabindex="-1">
    <div class="modal-dialog modal-sm" role="document">
        <form action="{{ route('master.fnb.category.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
            </div>
            <div class="modal-body">
                <input type="text" name="name" class="form-control" placeholder="Contoh: Makanan" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-product" tabindex="-1">
    <div class="modal-dialog" role="document">
        <form action="{{ route('master.fnb.product.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk Baru</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
    <label class="form-label">Kategori</label>
    <select name="fnb_category_id" id="edit-category" class="form-select" required>
        @foreach($allCategories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
</div>
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Marlboro Merah" required>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">HPP (Modal)</label>
                            <input type="number" name="hpp" class="form-control" placeholder="Harga beli dari supplier" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Harga Jual</label>
                            <input type="number" name="price" class="form-control" placeholder="Harga jual ke pelanggan" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Stok Awal</label>
                            <input type="number" name="stock" class="form-control" value="0" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Minimal Stok (Alert)</label>
                            <input type="number" name="min_stock" class="form-control" value="5" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-edit-product" tabindex="-1">
    <div class="modal-dialog" role="document">
        <form id="form-edit-product" action="" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Edit Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="fnb_category_id" id="edit-category" class="form-select" required>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="row">
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">HPP</label>
            <input type="number" name="hpp" id="edit-hpp" class="form-control" required>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Harga Jual</label>
            <input type="number" name="price" id="edit-price" class="form-control" required>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Stok</label>
            <input type="number" name="stock" id="edit-stock" class="form-control" required>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Min. Stok</label>
            <input type="number" name="min_stock" id="edit-minstock" class="form-control" required>
        </div>
    </div>
</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('modal-edit-product');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const hpp = button.getAttribute('data-hpp');
            const price = button.getAttribute('data-price');
            const stock = button.getAttribute('data-stock');
            const minstock = button.getAttribute('data-minstock');
            const category = button.getAttribute('data-category');

            const form = document.getElementById('form-edit-product');
            form.action = `/master/fnb/product/update/${id}`;

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
