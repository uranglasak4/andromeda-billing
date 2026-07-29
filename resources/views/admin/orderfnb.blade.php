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
    // HELPER FUNCTION: FORMAT CURRENCY / ANGKA
    // =========================================================================
    function numberFormat(number) {
        return new Intl.NumberFormat('id-ID').format(number || 0);
    }

    // =========================================================================
    // 1. LIVE FILTER KATEGORI
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

            loadCartBySelectedMeja();
        } else {
            document.getElementById('lbl-type-standalone').className =
                'form-check form-check-inline m-0 btn w-100 p-2 border active-type-btn';
            document.getElementById('lbl-type-table').className =
                'form-check form-check-inline m-0 btn w-100 p-2 border text-muted';
            document.getElementById('box-select-table').classList.add('d-none');
            document.getElementById('box-input-name').classList.remove('d-none');

            cart = [];
            renderCart();
        }
    }

    // =========================================================================
    // 3. TAMBAH PRODUK KE KERANJANG (DARI KLIK MENU SISI KIRI)
    // =========================================================================
    function addToCart(id, name, price, maxStock) {
        let existing = cart.find(item => item.id === id && (!item.is_package_include && !item.is_package_item));
        if (existing) {
            if (existing.qty >= maxStock) {
                Swal.fire('Stok Habis!', 'Jumlah melebihi stok produk yang tersedia.', 'warning');
                return;
            }
            existing.qty += 1;
            existing.subtotal = existing.qty * existing.price;
        } else {
            if (maxStock < 1) {
                Swal.fire('Stok Habis!', 'Produk tidak tersedia.', 'warning');
                return;
            }
            cart.push({
                id: id,
                name: name,
                product_name: name,
                price: parseFloat(price),
                qty: 1,
                subtotal: parseFloat(price),
                maxStock: maxStock,
                is_package_include: false,
                is_package_item: false
            });
        }
        renderCart();
    }

    // =========================================================================
    // 4. UPDATE QUANTITY (TAMBAH / KURANG ITEM)
    // =========================================================================
    function updateQty(id, delta) {
        let item = cart.find(i => i.id === id && (!i.is_package_include && !i.is_package_item));
        if (item) {
            if (item.qty + delta <= 0 && item.order_id) {
                deleteExistingOrder(item.order_id);
                return;
            }

            item.qty += delta;
            if (item.qty > item.maxStock) {
                Swal.fire('Stok Batas!', 'Stok tidak mencukupi.', 'warning');
                item.qty = item.maxStock;
            }
            item.subtotal = item.qty * item.price;

            if (item.qty <= 0) {
                cart = cart.filter(i => !(i.id === id && (!i.is_package_include && !i.is_package_item)));
            }
        }
        renderCart();
    }

    // =========================================================================
    // 5. RENDER CART KE SIDEBAR KANAN
    // =========================================================================
    function renderCart() {
        let html = '';
        let grandTotal = 0;

        const emptyState = document.getElementById('cart-empty-state');
        const tableList = document.getElementById('cart-table-list');
        const tbody = document.getElementById('cart-table-body');
        const totalText = document.getElementById('cart-total-text');

        if (cart.length === 0) {
            if(emptyState) emptyState.classList.remove('d-none');
            if(tableList) tableList.classList.add('d-none');
            if(totalText) totalText.innerText = 'Rp 0';
            return;
        }

        if(emptyState) emptyState.classList.add('d-none');
        if(tableList) tableList.classList.remove('d-none');

        cart.forEach(function(item) {
            let itemName = item.product_name || item.name;
            // Gunakan is_package_include dari Controller
            let isPackage = item.is_package_include || item.is_package_item || parseFloat(item.price) === 0;
            let itemSubtotal = item.subtotal !== undefined ? item.subtotal : (item.price * item.qty);

            // Format bentuk Table <tr> sesuai Blade kamu
            html += `
            <tr>
                <td class="text-start ps-0">
                    <div class="fw-bold text-dark">${itemName} ${isPackage ? '<span class="badge bg-success text-white small ms-1">Include Paket</span>' : ''}</div>
                    <div class="text-muted small">Rp ${numberFormat(item.price)}</div>
                </td>
                <td style="width: 110px;">
                    ${isPackage ? `
                        <div class="text-center fw-bold text-muted">${item.qty} Pcs</div>
                    ` : `
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary px-2" type="button" onclick="updateQty(${item.id}, -1)">-</button>
                            <input type="text" class="form-control text-center px-0 fw-bold" value="${item.qty}" readonly>
                            <button class="btn btn-outline-secondary px-2" type="button" onclick="updateQty(${item.id}, 1)">+</button>
                        </div>
                    `}
                </td>
                <td class="text-end fw-bold pe-0 text-dark">
                    Rp ${numberFormat(itemSubtotal)}
                </td>
            </tr>
            `;

            grandTotal += parseFloat(itemSubtotal);
        });

        if(tbody) tbody.innerHTML = html;
        if(totalText) totalText.innerText = 'Rp ' + numberFormat(grandTotal);
    }

    // =========================================================================
    // 6. DELETE EXISTING ORDER DARI DATABASE
    // =========================================================================
    function deleteExistingOrder(orderId) {
        Swal.fire({
            title: 'Hapus Menu Pesanan?',
            text: "Item akan dihapus dari meja secara permanen!",
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
                    didOpen: () => { Swal.showLoading(); }
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
    // 7. READ: AMBIL DATA ORDERAN MEJA REAL-TIME
    // =========================================================================
    function loadCartBySelectedMeja() {
        const selectMeja = document.getElementById('select-meja-fnb');
        if (!selectMeja) return;

        const selectedOption = selectMeja.options[selectMeja.selectedIndex];

        if (!selectMeja.value || !selectedOption) {
            cart = [];
            renderCart();
            return;
        }

        const poolTableId = selectedOption.getAttribute('data-table-id') || selectMeja.value;

        Swal.fire({
            title: 'Memuat Menu...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(`/admin/orderfnb/current-cart/${poolTableId}`)
            .then(res => res.json())
            .then(data => {
                Swal.close();
                cart = data.map(item => {
                    return {
                        id: item.id || item.product_id,
                        order_id: item.order_id,
                        name: item.name || item.product_name,
                        product_name: item.name || item.product_name,
                        price: parseFloat(item.price),
                        qty: item.qty,
                        subtotal: item.subtotal !== undefined ? parseFloat(item.subtotal) : (parseFloat(item.price) * item.qty),
                        is_package_include: item.is_package_include || parseFloat(item.price) === 0,
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

    // Event listener saat dropdown meja diganti
    const selectMejaEl = document.getElementById('select-meja-fnb');
    if (selectMejaEl) {
        selectMejaEl.addEventListener('change', loadCartBySelectedMeja);
    }

    // =========================================================================
    // 8. SUBMIT ORDER
    // =========================================================================
    function checkoutOrder() {
        if (cart.length === 0) {
            Swal.fire('Keranjang Kosong!', 'Pilih menu makanan dulu sebelum proses.', 'info');
            return;
        }

        const type = document.querySelector('input[name="order_type"]:checked').value;

        // Jangan proses item yang statusnya include paket
        let itemsToProcess = cart.filter(function(item) {
            return !item.is_package_include && !item.is_package_item && parseFloat(item.price) > 0;
        });

        if (type === 'table' && itemsToProcess.length === 0) {
            Swal.fire('Tidak Ada Pesanan Baru!', 'Belum ada item FnB berbayar baru yang ditambahkan.', 'info');
            return;
        }

        let payload = {
            order_type: type,
            items: itemsToProcess.map(i => ({
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
                Swal.fire('Nama Kosong!', 'Isi nama customer walk-in.', 'warning');
                return;
            }
            payload.customer_name = name;
        }

        Swal.fire({
            title: 'Memproses Nota...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
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
    // 9. AUTOMATIC RUN ON LOAD
    // =========================================================================
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tableIdFromUrl = urlParams.get('table_id');

        if (tableIdFromUrl) {
            toggleType('table');

            const selectMeja = document.getElementById('select-meja-fnb');
            if (selectMeja) {
                for (let i = 0; i < selectMeja.options.length; i++) {
                    let opt = selectMeja.options[i];
                    if (opt.getAttribute('data-table-id') == tableIdFromUrl || opt.text.toUpperCase().includes(`MEJA ${tableIdFromUrl}`)) {
                        selectMeja.selectedIndex = i;
                        break;
                    }
                }
                loadCartBySelectedMeja();
            }
        }
    });
</script>
@endsection
