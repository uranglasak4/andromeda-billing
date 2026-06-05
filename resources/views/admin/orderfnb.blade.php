@extends('layouts.nav')
@section('title', 'FnB Order Management')
@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row g-3">

                <!-- SISI KIRI: GRID MENU MASAKAN & MINUMAN -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0"
                        style="height: calc(100vh - 130px); display: flex; flex-direction: column;">

                        <!-- CARD HEADER DENGAN FILTER KATEGORI -->
                        <div class="card-header bg-dark py-2 d-flex flex-column align-items-start gap-2">
                            <h3 class="card-title text-white h3 mb-1">
                                <i class="ti ti-apps me-2 text-warning"></i> Daftar Menu FnB
                            </h3>
                            <!-- Tombol Filter Kategori (UX Mulus) -->
                            <div class="d-flex flex-wrap gap-1 w-100" id="category-filter-container">
                                <button class="btn btn-sm btn-warning fw-bold btn-filter-cat"
                                    onclick="filterCategory('all', this)">
                                    🌟 Semua Menu
                                </button>
                                @foreach ($categories as $cat)
                                    <button class="btn btn-sm btn-outline-light btn-filter-cat"
                                        onclick="filterCategory('cat-{{ $cat->id }}', this)">
                                        {{ $cat->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Area Scrollable Grid Menu -->
                        <div class="card-body p-3" style="overflow-y: auto; flex: 1;">
                            <div class="row row-cards g-2" id="fnb-products-grid">
                                @foreach ($products as $product)
                                    <!-- Tambahkan class category-id dinamis untuk filter -->
                                    <div class="col-6 col-sm-4 col-md-3 fnb-item-card cat-{{ $product->fnb_category_id }}">
                                        <button class="card card-btn w-100 p-2 text-center border-2 btn-menu shadow-sm"
                                            onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }}, {{ $product->stock }})"
                                            style="border-color: #f1f5f9; transition: all 0.2s;">
                                            <div class="fw-bold text-dark text-truncate w-100 mb-1"
                                                style="font-size: 0.95rem;">
                                                {{ $product->name }}
                                            </div>
                                            <div class="text-primary fw-bold small mb-1">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </div>
                                            <div class="badge bg-muted-lt text-muted px-2 py-0.5"
                                                style="font-size: 0.75rem;">
                                                Stok: <span
                                                    id="stock-view-{{ $product->id }}">{{ $product->stock }}</span>
                                            </div>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SISI KANAN: KERANJANG BELANJA & CHECKOUT -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0"
                        style="height: calc(100vh - 130px); display: flex; flex-direction: column;">
                        <div class="card-header bg-primary text-white py-3">
                            <h3 class="card-title text-white h3 mb-0">
                                <i class="ti ti-shopping-cart-discount me-2"></i> Detail Keranjang
                            </h3>
                        </div>

                        <div class="p-3 border-bottom bg-light">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-check form-check-inline m-0 btn w-100 p-2 border active-type-btn"
                                        id="lbl-type-table" style="cursor:pointer;">
                                        <input class="form-check-input d-none" type="radio" name="order_type"
                                            value="table" checked onclick="toggleType('table')">
                                        <span class="form-check-label text-center d-block fw-bold">BILL MEJA</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="form-check form-check-inline m-0 btn w-100 p-2 border text-muted"
                                        id="lbl-type-standalone" style="cursor:pointer;">
                                        <input class="form-check-input d-none" type="radio" name="order_type"
                                            value="standalone" onclick="toggleType('standalone')">
                                        <span class="form-check-label text-center d-block fw-bold">STANDALONE</span>
                                    </label>
                                </div>
                            </div>

                            <div id="box-select-table">
                                <!-- KODE BARU -->
                                <select name="transaction_id" id="select-meja-fnb" class="form-select fw-bold">
                                    <option value="">-- Pilih Meja Aktif --</option>
                                    @foreach ($activeTransactions as $tx)
                                        <option value="{{ $tx->id }}" data-table-id="{{ $tx->pool_table_id }}"
                                            {{ request('table_id') == $tx->pool_table_id ? 'selected' : '' }}>
                                            MEJA {{ $tx->poolTable->table_number ?? '' }} ({{ $tx->customer_name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="box-input-name" class="d-none">
                                <input type="text" id="inp_customer_name" class="form-control form-control-lg fw-bold"
                                    placeholder="Nama Pembeli (Waiting List / Walk-In)">
                            </div>
                        </div>

                        <div class="p-3" style="overflow-y: auto; flex: 1;" id="cart-items-wrapper">
                            <div class="text-center text-muted py-5" id="cart-empty-state">
                                <i class="ti ti-basket fs-1 d-block mb-2 text-muted opacity-50"></i>
                                Keranjang kosong, silakan klik menu di sebelah kiri.
                            </div>
                            <div id="cart-table-list" class="d-none">
                                <table class="table table-vcenter card-table table-borderless">
                                    <tbody id="cart-table-body">
                                        <!-- Terisi otomatis -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="p-3 bg-dark text-white border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h3 mb-0 text-muted">Total Bayar:</span>
                                <span class="h1 mb-0 text-warning fw-bold" id="cart-total-text">Rp 0</span>
                            </div>
                            <button type="button" class="btn btn-warning w-100 py-2 h2 fw-bold mb-0 text-dark shadow"
                                onclick="checkoutOrder()">
                                <i class="ti ti-circle-check me-2 fs-2"></i> PROSES NOTA SEKARANG
                            </button>
                        </div>

                    </div>
                    <!-- TABEL RIWAYAT PESANAN TERAKHIR (Ditarik dari data $recentOrders di controller) -->
                    <div class="mt-3 card border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white py-2">
                            <h4 class="card-title text-white mb-0 small"><i class="ti ti-history me-1"></i> 5 Pesanan FnB
                                Terakhir</h4>
                        </div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-vcenter card-table table-striped table-sm text-center">
                                <thead>
                                    <tr class="small">
                                        <th>Menu</th>
                                        <th>Qty</th>
                                        <th>Tujuan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 0.85rem;">
                                    @forelse($recentOrders as $order)
                                        <tr>
                                            <td class="text-start fw-bold text-truncate" style="max-width: 120px;">
                                                {{ $order->fnbProduct->name ?? 'Menu Dihapus' }}
                                            </td>
                                            <td>{{ $order->qty }}</td>
                                            <td>
                                                @if ($order->transaction_id)
                                                    <span class="badge bg-indigo-lt">Meja</span>
                                                @else
                                                    <span class="badge bg-azure-lt">{{ $order->customer_name }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}-lt">
                                                    {{ strtoupper($order->payment_status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted small py-3">Belum ada riwayat pesanan
                                                hari ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        .card-btn:hover {
            border-color: #206bc4 !important;
            background-color: #f8fafc;
            transform: translateY(-2px);
        }

        .active-type-btn {
            background-color: #206bc4 !important;
            color: white !important;
            border-color: #206bc4 !important;
        }
    </style>

    <script>
        let cart = [];

        // =========================================================================
        // 1. LIVE FILTER KATEGORI (UX NYAMAN)
        // =========================================================================
        function filterCategory(className, btnElement) {
            const buttons = document.querySelectorAll('.btn-filter-cat');
            buttons.forEach(btn => {
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-outline-light');
            });
            btnElement.classList.remove('btn-outline-light');
            btnElement.classList.add('btn-warning');

            const items = document.querySelectorAll('.fnb-item-card');
            items.forEach(item => {
                if (className === 'all' || item.classList.contains(className)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        }

        // =========================================================================
        // 2. TOGGLE TIPE ORDERAN (BILL MEJA / STANDALONE)
        // =========================================================================
        function toggleType(type) {
            if (type === 'table') {
                document.getElementById('lbl-type-table').className =
                    'form-check form-check-inline m-0 btn w-100 p-2 border active-type-btn';
                document.getElementById('lbl-type-standalone').className =
                    'form-check form-check-inline m-0 btn w-100 p-2 border text-muted';
                document.getElementById('box-select-table').classList.remove('d-none');
                document.getElementById('box-input-name').classList.add('d-none');

                // PERBAIKAN: Saat balik ke Bill Meja, otomatis tarik data ulang dari database berdasarkan meja yang terpilih
                loadCartBySelectedMeja();
            } else {
                document.getElementById('lbl-type-standalone').className =
                    'form-check form-check-inline m-0 btn w-100 p-2 border active-type-btn';
                document.getElementById('lbl-type-table').className =
                    'form-check form-check-inline m-0 btn w-100 p-2 border text-muted';
                document.getElementById('box-select-table').classList.add('d-none');
                document.getElementById('box-input-name').classList.remove('d-none');

                // Kosongkan keranjang murni untuk transaksi Standalone / Walk-in baru
                cart = [];
                renderCart();
            }
        }

        // =========================================================================
        // 3. TAMBAH PRODUK KE KERANJANG (CREATE/UPDATE LOKAL)
        // =========================================================================
        function addToCart(id, name, price, maxStock) {
            let existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.qty >= maxStock) {
                    Swal.fire('Stok Habis!', 'Jumlah melebihi stok produk yang tersedia.', 'warning');
                    return;
                }
                existing.qty += 1;
            } else {
                if (maxStock < 1) {
                    Swal.fire('Stok Habis!', 'Produk tidak tersedia.', 'warning');
                    return;
                }
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    qty: 1,
                    maxStock: maxStock
                });
            }
            renderCart();
        }

        // =========================================================================
        // 4. UPDATE QUANTITY (TAMBAH / KURANG ITEM DI FORM)
        // =========================================================================
        function updateQty(id, delta) {
            let item = cart.find(i => i.id === id);
            if (item) {
                // Jika data lama dari DB dikurangi sampai 0, arahkan ke fungsi delete permanent
                if (item.qty + delta <= 0 && item.order_id) {
                    deleteExistingOrder(item.order_id);
                    return;
                }

                item.qty += delta;
                if (item.qty > item.maxStock) {
                    Swal.fire('Stok Batas!', 'Stok tidak mencukupi.', 'warning');
                    item.qty = item.maxStock;
                }
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            renderCart();
        }

        // =========================================================================
        // 5. RENDER CART KE SIDEBAR KANAN (TAMPILAN UTAMA POS)
        // =========================================================================
        function renderCart() {
            const emptyState = document.getElementById('cart-empty-state');
            const cartList = document.getElementById('cart-table-list');
            const body = document.getElementById('cart-table-body');
            const totalText = document.getElementById('cart-total-text');

            if (cart.length === 0) {
                emptyState.classList.remove('d-none');
                cartList.classList.add('d-none');
                totalText.innerText = 'Rp 0';
                return;
            }

            emptyState.classList.add('d-none');
            cartList.classList.remove('d-none');

            let html = '';
            let total = 0;

            cart.forEach(item => {
                let subtotal = item.price * item.qty;
                total += subtotal;

                // Cek apakah data ini pesanan tersimpan di DB atau menu baru di-klik kasir
                const isSaved = item.hasOwnProperty('order_id');

                html += `
            <tr class="border-bottom">
                <td class="p-2 text-start">
                    <div class="fw-bold text-dark">
                        ${item.name}
                        ${isSaved ? '<span class="badge bg-green-lt ms-1">Saved</span>' : '<span class="badge bg-blue-lt ms-1">Baru</span>'}
                    </div>
                    <div class="text-muted small">Rp ${item.price.toLocaleString('id-ID')}</div>
                </td>
                <td class="p-2">
                    <div class="input-group input-group-sm" style="width: 90px; margin: 0 auto;">
                        <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateQty(${item.id}, -1)">-</button>
                        <input type="text" class="form-control text-center bg-white p-0 fw-bold text-dark" value="${item.qty}" readonly>
                        <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                </td>
                <td class="p-2 text-end fw-bold text-primary">
                    Rp ${subtotal.toLocaleString('id-ID')}
                </td>
                <td class="p-2 text-center">
                    ${isSaved ?
                        `<button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="deleteExistingOrder(${item.order_id})">
                                <i class="ti ti-trash fs-2"></i>
                             </button>` :
                        `<button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="removeLocalItem(${item.id})">
                                <i class="ti ti-x fs-2"></i>
                             </button>`
                    }
                </td>
            </tr>
            `;
            });

            body.innerHTML = html;
            totalText.innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Fungsi menghapus item yang baru di-klik kasir (belum masuk DB)
        function removeLocalItem(id) {
            cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        // =========================================================================
        // 6. DELETE: HAPUS PERMANEN PESANAN DARI DATABASE DENGAN SWEETALERT
        // =========================================================================
        function deleteExistingOrder(orderId) {
            Swal.fire({
                title: 'Hapus Menu Pesanan?',
                text: "Item akan dihapus dari meja secara permanen dan stok akan dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/admin/orderfnb/delete-item/${orderId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire('Terhapus!', data.message, 'success');
                                // Ambil ulang data keranjang ter-update dari server
                                loadCartBySelectedMeja();
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                        });
                }
            });
        }

        // =========================================================================
        // 7. READ: AMBIL DATA ORDERAN MEJA SECARA REAL-TIME JIKA DROPDOWN / URL BERUBAH
        // =========================================================================
        function loadCartBySelectedMeja() {
    const selectMeja = document.getElementById('select-meja-fnb');
    if (!selectMeja) return;

    const transactionId = selectMeja.value;
    const selectedOption = selectMeja.options[selectMeja.selectedIndex];

    if (!transactionId || !selectedOption) {
        cart = [];
        renderCart();
        return;
    }

    // Ambil ID Meja murni dari data-attribute yang sudah kita buat di atas
    const poolTableId = selectedOption.getAttribute('data-table-id');

    if (!poolTableId) return;

    Swal.fire({
        title: 'Memuat Menu Meja...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(`/admin/orderfnb/current-cart/${poolTableId}`)
        .then(res => res.json())
        .then(data => {
            Swal.close();
            cart = data.map(item => {
                return {
                    id: item.id,
                    order_id: item.order_id,
                    name: item.name,
                    price: item.price,
                    qty: item.qty,
                    maxStock: 999
                };
            });
            renderCart();
        })
        .catch(err => {
            Swal.close();
            console.error("Gagal memuat item keranjang meja:", err);
        });
}

        // Hubungkan fungsi load data ke event change dropdown secara manual
        document.getElementById('select-meja-fnb').addEventListener('change', loadCartBySelectedMeja);

        // =========================================================================
        // 8. UPDATE / SUBMIT ORDER (CHECKOUT NOTA) DENGAN SWEETALERT
        // =========================================================================
        function checkoutOrder() {
            if (cart.length === 0) {
                Swal.fire('Keranjang Kosong!', 'Pilih menu makanan dulu sebelum proses.', 'info');
                return;
            }

            const type = document.querySelector('input[name="order_type"]:checked').value;
            let payload = {
                order_type: type,
                items: cart.map(i => ({
                    id: i.id,
                    qty: i.qty
                })),
                _token: "{{ csrf_token() }}"
            };

            if (type === 'table') {
                const txId = document.getElementById('select-meja-fnb').value;
                if (!txId) {
                    Swal.fire('Pilih Meja!', 'Tentukan nomor meja billing yang dituju.', 'warning');
                    return;
                }
                payload.transaction_id = txId;
            } else {
                const name = document.getElementById('inp_customer_name').value.trim();
                if (!name) {
                    Swal.fire('Nama Kosong!', 'Isi nama customer walk-in / waiting list.', 'warning');
                    return;
                }
                payload.customer_name = name;
            }

            Swal.fire({
                title: 'Memproses Nota...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("{{ route('admin.orderfnb.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            if (type === 'table') {
                                // Kuncinya disini: Refresh data ter-update saja tanpa me-reload paksa halaman!
                                loadCartBySelectedMeja();
                            } else {
                                cart = [];
                                renderCart();
                                document.getElementById('inp_customer_name').value = '';
                            }
                        });
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.close();
                    Swal.fire('Sistem Error!', 'Terjadi gangguan koneksi ke server.', 'error');
                });
        }

        // =========================================================================
        // 9. AUTOMATIC RUN ON LOAD (SAAT KLIK + FNB DARI MONITORING DASHBOARD)
        // =========================================================================
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            // Dashboard mengirim parameter '?table_id='
            const tableIdFromUrl = urlParams.get('table_id');

            if (tableIdFromUrl) {
                toggleType('table');

                const selectMeja = document.getElementById('select-meja-fnb');
                if (selectMeja) {
                    setTimeout(() => {
                        for (let i = 0; i < selectMeja.options.length; i++) {
                            let opt = selectMeja.options[i];
                            // COCOKKAN isi option dengan parameter URL pengirim
                            if (opt.text.toLowerCase().includes(`meja ${tableIdFromUrl}`)) {
                                selectMeja.value = opt.value;
                                // Trigger pemanggilan data otomatis
                                loadCartBySelectedMeja();
                                break;
                            }
                        }
                    }, 150);
                }
            }
        });
    </script>
@endsection
