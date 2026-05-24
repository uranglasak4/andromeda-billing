@extends('layouts.nav')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <!-- Bagian Atas: Pendaftaran & Status Antrean -->
            <div class="row row-cards mb-4 d-flex align-items-stretch">
                <div class="col-md-8 d-flex">
                    <div class="card w-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Pendaftaran Waiting List</h3>
                            <button class="btn btn-primary btn-sm" onclick="toggleWaitingList()">
                                <i class="ti ti-layout-sidebar-right me-1"></i> Toggle List Antrean
                            </button>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.waiting-list.store') }}" method="POST">
                                @csrf
                                <div class="row g-2">
                                    <div class="col">
                                        <input type="text" name="nama_pelanggan" class="form-control"
                                            placeholder="Nama Customer" required>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">Tambah Antrean</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 d-flex">
                    <div class="card w-100 text-center bg-primary-lt">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="h3 mb-3">Waiting List Sekarang</div>
                            <div class="h1 mb-0 font-weight-bold" style="font-size: 3rem;">{{ $currentWaitingCount ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Monitoring Meja -->
            <div class="row">
                <div id="meja-section" class="col-md-12">
                    <div class="row">
                        <div id="grid-meja" class="col-md-12">
                            <div class="row row-cards">
                                @foreach ($tables as $table)
                                    @php
                                        // Logika penentuan class background
                                        $statusClass = 'bg-available';
                                        $statusLabel = 'AVAILABLE';

                                        if ($table->status === 'playing') {
                                            $statusClass = 'bg-playing';
                                            $statusLabel = 'PLAYING';
                                        } elseif ($table->status === 'nearly') {
                                            $statusClass = 'bg-nearly';
                                            $statusLabel = 'NEARLY';
                                        } elseif ($table->status === 'personal') {
                                            $statusClass = 'bg-personal';
                                            $statusLabel = 'PERSONAL';
                                        } elseif ($table->status === 'maintenance') {
                                            $statusClass = 'bg-maintenance';
                                            $statusLabel = 'MAINTENANCE';
                                        } elseif ($table->status === 'timeout') {
                                            $statusClass = 'bg-timeout-blink'; // Hijau Berkedip
                                            $statusLabel = 'TIMEOUT';
                                        }

                                        $activeTrans = $table->transactions->first();
                                    @endphp

                                    <div class="col-6 col-md-2 mb-3">
                                        <div class="card card-table-admin {{ $statusClass }}">
                                            <div class="card-body p-3 text-center d-flex flex-column">
                                                <div class="mb-auto">
                                                    <div class="text-uppercase fw-bold small opacity-75">MEJA</div>
                                                    <div class="h1 m-0 mb-1 font-weight-bold">{{ $table->table_number }}
                                                    </div>
                                                </div>

                                                <div class="my-3">
                                                    <div class="text-uppercase fw-bold mb-1 status-label"
                                                        style="font-size: 0.7rem; letter-spacing: 1px;">
                                                        {{ $statusLabel }}
                                                    </div>

                                                    @if ($activeTrans)
                                                        <div class="fw-bold countdown-timer"
                                                            style="font-size: 1.2rem; color: #fff;"
                                                            data-start="{{ \Carbon\Carbon::parse($activeTrans->start_time)->toIso8601String() }}"
                                                            /* Hapus pengecekan status timeout di sini agar JS selalu dapat
                                                            data waktu */
                                                            data-end="{{ $activeTrans->end_time ? \Carbon\Carbon::parse($activeTrans->end_time)->toIso8601String() : '' }}"
                                                            data-table-number="{{ $table->table_number }}">

                                                            @if ($table->status === 'timeout' || ($activeTrans->end_time && \Carbon\Carbon::parse($activeTrans->end_time)->isPast()))
                                                                00:00:00
                                                            @else
                                                                --:--:--
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="h4 mb-0 opacity-50">--:--:--</div>
                                                    @endif
                                                </div>

                                                <div class="mt-auto">
                                                    @if ($table->status === 'available')
                                                        <button class="btn btn-primary btn-sm w-100"
                                                            onclick="showOpenTableModal('{{ $table->id }}', '{{ $table->table_number }}')">Open</button>
                                                    @elseif($table->status === 'maintenance')
                                                        <button class="btn btn-secondary btn-sm w-100"
                                                            disabled>Repair</button>
                                                    @else
                                                        <button class="btn btn-warning btn-sm w-100"
                                                            onclick="showOptionModal('{{ $table->id }}', '{{ $table->table_number }}')">Option</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Area Antrean (Hidden by default) -->
                        <div id="list-antrean-section" class="col-md-4 d-none">
                            <div class="card" style="min-height: 400px; border: 2px solid #206bc4;">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="card-title text-uppercase">Urutan Antrean</h3>
                                </div>
                                <div class="list-group list-group-flush" style="overflow-y: auto; max-height: 600px;">
                                    @forelse($waitingCustomers as $index => $customer)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div><span
                                                    class="badge bg-primary-lt me-2">{{ $index + 1 }}</span><strong>{{ strtoupper($customer->customer_name) }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $customer->created_at->format('H:i') }}</small>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted small">Belum ada antrean</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Open Table -->
    <div class="modal modal-blur fade" id="modal-open-table" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="form-open-table" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="modal-title h3">Open Table Meja <span id="display-no-meja"></span></div>
                        <div class="mb-3">
                            <label class="form-label">Nama Customer</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Nama..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Billing</label>
                            <select id="billing-selector" name="duration" class="form-select"
                                onchange="handleBillingSelection()" required>
                                <option value="" disabled selected>-- Pilih Durasi/Paket --</option>
                                <optgroup label="Custom">
                                    <option value="manual" data-type="manual">Per Jam (Input Manual)</option>
                                    <option value="personal" data-type="personal">Personal (Open Time)</option>
                                </optgroup>
                                <optgroup label="Paket Promo">
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->duration_value }}" data-type="package"
                                            data-price="{{ $package->price }}">
                                            {{ $package->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <!-- Input tambahan yang hanya muncul jika pilih "Per Jam (Manual)" -->
                        <div id="manual-duration-container" class="mb-3 d-none">
                            <label class="form-label">Masukkan Durasi (Jam)</label>
                            <div class="input-group">
                                <input type="number" id="input-hours" name="manual_hours" class="form-control"
                                    value="1" min="1" oninput="calculatePrice()">
                                <span class="input-group-text">Jam</span>
                            </div>
                        </div>

                        <div class="card bg-primary-lt p-3 text-center">
                            <div class="text-uppercase small fw-bold">Estimasi Harga</div>
                            <div class="h2 m-0 font-weight-bold">Rp <span id="display-harga">0</span></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">Mulai Billing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Option (Untuk Pindah/Stop Meja) -->
    <div class="modal modal-blur fade" id="modal-option-table" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-title h3 text-center">Opsi Meja <span id="option-no-meja"></span></div>
                    <div class="row g-2">
                        <div class="col-12">
                            <button class="btn btn-info w-100 py-3" onclick="showMoveModal()">
                                <i class="ti ti-arrows-exchange me-2"></i> Pindah Meja
                            </button>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-danger w-100 py-3" onclick="stopBilling()">
                                <i class="ti ti-player-stop me-2"></i> Selesaikan Billing
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pindah Meja -->
    <div class="modal modal-blur fade" id="modal-move-table" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="form-move-table" action="{{ route('billing.move') }}" method="POST">
                    @csrf
                    <input type="hidden" name="from_table_id" id="from-table-id">
                    <div class="modal-body">
                        <div class="modal-title h3 text-center">Pindah Meja</div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Meja Tujuan</label>
                            <select name="to_table_id" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Meja Kosong --</option>
                                @foreach ($tables->where('status', 'available') as $t)
                                    <option value="{{ $t->id }}">Meja {{ $t->table_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">Konfirmasi Pindah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
        // Logic Realtime Timer
        // Simpan status meja sebelumnya di memori untuk deteksi perubahan
        let previousStatuses = {};

        function updateTimers() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const startTimeStr = timer.getAttribute('data-start');
                const endTimeStr = timer.getAttribute('data-end');
                const cardElement = timer.closest('.card-table-admin');
                const tableNumber = timer.getAttribute('data-table-number');
                const statusLabel = cardElement.querySelector('.status-label');

                if (!startTimeStr) return;
                const now = new Date().getTime();

                if (endTimeStr && endTimeStr !== "") {
                    const endTime = new Date(endTimeStr).getTime();
                    const distance = endTime - now;
                    const minutesLeft = Math.floor(distance / 60000);

                    // 1. LOGIKA TIMEOUT
                    if (distance <= 0) {
                        timer.innerHTML = "00:00:00";
                        cardElement.classList.remove('bg-playing', 'bg-nearly');
                        cardElement.classList.add('bg-timeout-blink');
                        if (statusLabel) statusLabel.innerText = 'TIMEOUT';

                        // CEK APAKAH HARUS BUNYI?
                        const storageKey = 'done_' + tableNumber + '_' + startTimeStr;
                        const isAlreadyDone = localStorage.getItem(storageKey);

                        // Bunyi HANYA JIKA:
                        // - Belum pernah bunyi untuk sesi ini (isAlreadyDone kosong)
                        // - DAN status sebelumnya adalah 'NEARLY' (artinya kita memang sedang nungguin dia habis)
                        if (!isAlreadyDone && previousStatuses[tableNumber] === 'NEARLY') {
                            const audio = document.getElementById('snd-timeout');
                            if (audio) {
                                audio.currentTime = 0;
                                audio.play().then(() => {
                                    localStorage.setItem(storageKey, 'true');
                                }).catch(e => console.log("Klik layar sekali"));
                            }
                        }

                        previousStatuses[tableNumber] = 'TIMEOUT';
                        return;
                    }

                    // 2. LOGIKA NEARLY (< 20 Menit)
                    if (minutesLeft < 20) {
                        cardElement.classList.remove('bg-playing', 'bg-timeout-blink');
                        cardElement.classList.add('bg-nearly');
                        if (statusLabel) statusLabel.innerText = 'NEARLY';
                        previousStatuses[tableNumber] = 'NEARLY'; // Simpan status ini
                    }
                    // 3. LOGIKA PLAYING
                    else {
                        cardElement.classList.remove('bg-nearly', 'bg-timeout-blink');
                        cardElement.classList.add('bg-playing');
                        if (statusLabel) statusLabel.innerText = 'PLAYING';
                        previousStatuses[tableNumber] = 'PLAYING';
                    }

                    const h = Math.floor(distance / 3600000);
                    const m = Math.floor((distance % 3600000) / 60000);
                    const s = Math.floor((distance % 60000) / 1000);
                    timer.innerHTML = String(h).padStart(2, '0') + ":" + String(m).padStart(2, '0') + ":" + String(
                        s).padStart(2, '0');
                } else {
                    // Logika Personal
                    const start = new Date(startTimeStr).getTime();
                    const diff = now - start;
                    cardElement.classList.remove('bg-playing', 'bg-nearly', 'bg-timeout-blink');
                    cardElement.classList.add('bg-personal');
                    if (statusLabel) statusLabel.innerText = 'PERSONAL';

                    const h = Math.floor(diff / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);
                    timer.innerHTML = String(h).padStart(2, '0') + ":" + String(m).padStart(2, '0') + ":" + String(
                        s).padStart(2, '0');
                }
            });
        }
        // Bersihkan catatan lama setiap 1 jam agar browser tidak berat
        if (Math.random() < 0.1) { // Berjalan jarang-jarang saja
            localStorage.clear();
        }

        // Jalankan timer setiap detik
        setInterval(updateTimers, 500);
        updateTimers();

        // Fungsi UI lainnya
        function toggleWaitingList() {
            const gridMeja = document.getElementById('grid-meja');
            const listSection = document.getElementById('list-antrean-section');
            const tableItems = document.querySelectorAll('.table-item');
            if (listSection.classList.contains('d-none')) {
                listSection.classList.remove('d-none');
                gridMeja.className = 'col-md-8';
                tableItems.forEach(item => item.classList.replace('col-md-2', 'col-md-3'));
            } else {
                listSection.classList.add('d-none');
                gridMeja.className = 'col-md-12';
                tableItems.forEach(item => item.classList.replace('col-md-3', 'col-md-2'));
            }
        }

        function showOpenTableModal(id, number) {
            document.getElementById('display-no-meja').innerText = number;
            const form = document.getElementById('form-open-table');
            form.action = `/admin/billing/open/${id}`;
            new bootstrap.Modal(document.getElementById('modal-open-table')).show();
        }

        function showOptionModal(id, number) {
            window.currentSelectedTableId = id;
            document.getElementById('option-no-meja').innerText = number;
            document.getElementById('from-table-id').value = id;
            new bootstrap.Modal(document.getElementById('modal-option-table')).show();
        }

        function showMoveModal() {
            bootstrap.Modal.getInstance(document.getElementById('modal-option-table')).hide();
            new bootstrap.Modal(document.getElementById('modal-move-table')).show();
        }

        function stopBilling() {
            if (confirm('Selesaikan billing meja ini?')) {
                window.location.href = `/admin/billing/stop/${window.currentSelectedTableId}`;
            }
        }

        function calculatePrice() {
            const selector = document.getElementById('billing-selector');
            const selectedOption = selector.options[selector.selectedIndex];
            const displayHarga = document.getElementById('display-harga');

            const type = selectedOption.getAttribute('data-type');
            const price = selectedOption.getAttribute('data-price');

            if (type === 'package') {
                displayHarga.innerText = parseInt(price).toLocaleString('id-ID');
            } else if (type === 'hourly') {
                const duration = parseInt(selectedOption.value) / 60;
                // Asumsi harga per jam 50.000, sesuaikan dengan PricingRule Anda
                const totalPrice = duration * 50000;
                displayHarga.innerText = totalPrice.toLocaleString('id-ID');
            }
        }

        function handleBillingSelection() {
            const selector = document.getElementById('billing-selector');
            const selectedOption = selector.options[selector.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            const manualContainer = document.getElementById('manual-duration-container');

            // Reset tampilan input manual
            manualContainer.classList.add('d-none');

            if (type === 'manual') {
                manualContainer.classList.remove('d-none');
                calculatePrice();
            } else if (type === 'personal') {
                document.getElementById('display-harga').innerText = "Berjalan...";
            } else if (type === 'package') {
                const price = selectedOption.getAttribute('data-price');
                document.getElementById('display-harga').innerText = parseInt(price).toLocaleString('id-ID');
            }
        }

        function calculatePrice() {
            const selector = document.getElementById('billing-selector');
            const selectedOption = selector.options[selector.selectedIndex];
            const type = selectedOption.getAttribute('data-type');

            if (type === 'manual') {
                const hours = document.getElementById('input-hours').value;
                const pricePerHour = 50000; // Contoh harga per jam
                document.getElementById('display-harga').innerText = (hours * pricePerHour).toLocaleString('id-ID');
            }
        }

        window.addEventListener('load', function() {
            @if (session('success'))
                const msg = "{{ session('success') }}".toLowerCase();

                // Meja dimulai -> billing.wav[cite: 23]
                if (msg.includes('dimulai')) {
                    const snd = document.getElementById('snd-billing');
                    if (snd) snd.play().catch(e => console.log("Audio blocked"));
                }
                // Meja selesai -> finished.wav[cite: 23]
                else if (msg.includes('selesai')) {
                    const snd = document.getElementById('snd-finished');
                    if (snd) snd.play().catch(e => console.log("Audio blocked"));
                }
            @endif
        });
    </script>

    <!-- sound -->

@endsection
