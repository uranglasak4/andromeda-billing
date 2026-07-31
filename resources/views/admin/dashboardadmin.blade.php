@extends('layouts.nav')
@section('title', 'Dashboard Admin')
@section('content')
    <div class="page-body">
        <div class="container-xl">
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
                    <input type="hidden" name="package_id" id="input-package-id" value="">
                    <div class="modal-body">
                        <div class="modal-title h3">Open Table Meja <span id="display-no-meja"></span></div>
                        <div class="mb-3">
                            <label class="form-label">Nama Customer</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Nama..." required>
                        </div>
                        @php
                            use Carbon\Carbon;

                            // Ambil waktu & hari saat ini secara dinamis
                            $now = Carbon::now();
                            $currentTime = $now->format('H:i:s');
                            $todayISO = (string) $now->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu

                            // Ambil daftar active_days dari tabel pricing_rules berdasarkan day_type
                            $weekdayRule = \App\Models\PricingRule::where('day_type', 'weekday')->first();
                            $weekendRule = \App\Models\PricingRule::where('day_type', 'weekend')->first();

                            $weekdayDays =
                                $weekdayRule && $weekdayRule->active_days
                                    ? explode(',', $weekdayRule->active_days)
                                    : ['1', '2', '3', '4'];

                            $weekendDays =
                                $weekendRule && $weekendRule->active_days
                                    ? explode(',', $weekendRule->active_days)
                                    : ['5', '6', '7'];

                            // Cek apakah hari ini masuk kategori Weekday atau Weekend berdasarkan DB pricing_rules
                            $isTodayWeekday = in_array($todayISO, $weekdayDays);
                            $isTodayWeekend = in_array($todayISO, $weekendDays);
                        @endphp

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
                                        @php
                                            $isDisabled = false;

                                            // 1. Ambil jam dari database
                                            $startTime = Carbon::parse($package->active_from)->format('H:i');
                                            $endTime = Carbon::parse($package->active_to)->format('H:i');

                                            // --- DI SINI PERUBAHANNYA: Mengubah 'both' menjadi 'Everyday' ---
                                            $rawDayType = strtolower($package->day_type);
                                            if ($rawDayType === 'both') {
                                                $dayLabel = 'Everyday';
                                            } else {
                                                $dayLabel = ucfirst($package->day_type);
                                            }

                                            // Teks keterangan untuk dropdown
                                            $labelNote = " ({$dayLabel} {$startTime}-{$endTime})";

                                            // 2. Validasi Hari (Jika 'both' / 'Everyday', abaikan/loloskan validasi hari)
                                            if ($rawDayType === 'weekend' && !$isTodayWeekend) {
                                                $isDisabled = true;
                                            } elseif ($rawDayType === 'weekday' && !$isTodayWeekday) {
                                                $isDisabled = true;
                                            }

                                            // 3. Validasi Jam Aktif
                                            $pkgStart = Carbon::parse($package->active_from)->format('H:i:s');
                                            $pkgEnd = Carbon::parse($package->active_to)->format('H:i:s');

                                            $isTimeValid = false;
                                            if ($pkgStart <= $pkgEnd) {
                                                // Rentang normal (misal 11:00:00 s/d 15:00:00)
                                                $isTimeValid = $currentTime >= $pkgStart && $currentTime <= $pkgEnd;
                                            } else {
                                                // Rentang lewat tengah malam (misal 22:00:00 s/d 03:00:00)
                                                $isTimeValid = $currentTime >= $pkgStart || $currentTime <= $pkgEnd;
                                            }

                                            if (!$isTimeValid) {
                                                $isDisabled = true;
                                            }
                                        @endphp

                                        <option value="{{ $package->duration_value }}" data-type="package"
                                            data-price="{{ $package->price }}" data-package-id="{{ $package->id }}"
                                            {{ $isDisabled ? 'disabled' : '' }}>
                                            {{ $package->name }}{{ $labelNote }}
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

    <!-- Modal Option (Untuk Pindah/Stop/Detail Meja) -->
    <div class="modal modal-blur fade" id="modal-option-table" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-title h3 text-center mb-3">Opsi Meja <span id="option-no-meja"></span></div>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <button class="btn btn-info w-100 py-2 fw-bold" onclick="showMoveModal()">
                                <i class="ti ti-arrows-exchange me-2"></i> Pindah Meja
                            </button>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-danger w-100 py-2 fw-bold" onclick="stopBilling()">
                                <i class="ti ti-player-stop me-2"></i> Selesaikan Billing
                            </button>
                        </div>
                        <div class="col-12">
                            <a href="#" id="btn-link-fnb" class="btn btn-warning fw-bold w-100">
                                <i class="ti ti-plus me-1"></i> + FnB
                            </a>
                        </div>
                    </div>

                    <!-- 🛒 RINCIAN STRUK FNB & TOTAL BILLING MEJA (Dinamis via JS) -->
                    <div class="card shadow-sm border-1 mb-0">
                        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                            <h4 class="card-title text-white mb-0 fw-bold small">
                                <i class="ti ti-receipt me-1"></i> INVOICE
                            </h4>

                            <div class="d-flex align-items-center justify-content-end"
                                style="width: 70%; max-width: 300px;">
                                <span class="text-white-50 small me-2 d-none d-sm-inline"
                                    style="white-space: nowrap;">Cust:</span>

                                <div class="d-flex align-items-center bg-transparent w-100" style="position: relative;">
                                    <input type="text" id="option-customer-name-input"
                                        class="form-control text-end bg-transparent text-white fw-bold border-0 p-0 pe-1 shadow-none"
                                        style="font-size: 0.85rem; letter-spacing: 0.5px; border-bottom: 1px dashed rgba(255,255,255,0.4) !important; border-radius: 0; width: 100%; height: auto;"
                                        placeholder="Nama Pelanggan..."
                                        onkeydown="if(event.key === 'Enter') { event.preventDefault(); updateCustomerNameDirectly(); }">

                                    <svg xmlns="http://www.w3.org/2000/svg" onclick="updateCustomerNameDirectly()"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy text-warning ms-2"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; cursor: pointer; flex-shrink: 0;"
                                        title="Klik untuk Simpan">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                        <path d="M10 14a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                        <path d="M14 4l0 4l-6 0l0 -4" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-vcenter table-striped card-table mb-0 small">
                                    <thead>
                                        <tr class="text-muted bg-light" style="font-size: 0.75rem;">
                                            <th>Menu FnB</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fnb-table-body" style="font-size: 0.85rem;">
                                        <!-- Diisi oleh JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card-footer bg-light py-2 fw-bold text-dark" style="font-size: 0.85rem;">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Total Qty FnB:</span>
                                <span id="fnb-total-qty" class="text-azure">0 Pcs</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Total Bill FnB:</span>
                                <span id="fnb-total-price" class="text-monospace text-primary">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Biaya Billing Meja:</span>
                                <span id="billing-main-price" class="text-monospace text-secondary">Rp 0</span>
                            </div>

                            <hr class="my-1 border-secondary">

                            <div class="d-flex justify-content-between h4 mb-0 text-danger fw-bold pt-1">
                                <span>GRAND TOTAL:</span>
                                <span id="grand-total-bill" class="text-monospace">Rp 0</span>
                            </div>
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

    <!-- FLOATING ROCKET BUTTON (POJOK KIRI BAWAH LAYAR) -->
    <button class="btn btn-danger btn-pill shadow-lg"
        style="position: fixed; bottom: 20px; left: 20px; z-index: 1050; padding: 12px 20px; font-weight: bold; font-size: 1rem;"
        onclick="showRocketModal()">
        🚀 ROCKET BILLING
    </button>

    <!-- MODAL ROCKET POP-UP -->
    <div class="modal modal-blur fade" id="modal-rocket-billing" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content" style="border: 3px solid #d63939;">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold text-uppercase">🚀 Mass Rocket Billing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('billing.mass-open') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-important alert-warning small py-2 mb-3">
                            Fitur ini otomatis melewati meja yang sedang terisi (Playing/Maintenance).
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Jangkauan Nomor Meja</label>
                            <div class="row g-2">
                                <div class="col">
                                    <div class="input-group input-group-flat">
                                        <span class="input-group-text small">Dari</span>
                                        <input type="number" id="rocket-start-table" name="start_table"
                                            class="form-control text-center fw-bold" value="1" min="1"
                                            max="16" oninput="calculateRocketPrice()" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-flat">
                                        <span class="input-group-text small">Sampai</span>
                                        <input type="number" id="rocket-end-table" name="end_table"
                                            class="form-control text-center fw-bold" value="6" min="1"
                                            max="16" oninput="calculateRocketPrice()" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Utama Group Customer</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Nama..."
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Paket Billing</label>
                            <select id="rocket-billing-selector" name="duration" class="form-select text-dark fw-bold"
                                onchange="handleRocketBillingSelection()" required>
                                <option value="" disabled selected>-- Pilih Durasi/Paket --</option>
                                <optgroup label="Custom">
                                    <option value="manual" data-type="manual">Per Jam (Input Manual)</option>
                                    <option value="personal" data-type="personal">Personal (Open Time)</option>
                                </optgroup>
                                <optgroup label="Paket Promo Master">
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->duration_value }}" data-type="package"
                                            data-price="{{ $package->price }}">
                                            {{ $package->name }} (Rp {{ number_format($package->price, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <div id="rocket-manual-duration-container" class="mb-3 d-none">
                            <label class="form-label">Masukkan Durasi (Jam)</label>
                            <div class="input-group">
                                <input type="number" id="rocket-input-hours" name="manual_hours" class="form-control"
                                    value="1" min="1" oninput="calculateRocketPrice()">
                                <span class="input-group-text">Jam</span>
                            </div>
                        </div>

                        <div class="card bg-danger-lt p-3 text-center border-1 border-danger">
                            <div class="text-uppercase small fw-bold text-danger">Estimasi Tarif Billing</div>
                            <div class="h2 m-0 font-weight-bold text-danger">Rp <span id="rocket-display-harga">0</span>
                                <span class="h4 text-muted">/ Meja</span>
                            </div>
                            <small id="rocket-summary-meja" class="text-muted mt-1 fw-bold" style="font-size: 0.75rem;">
                                *Akan mengaktifkan 0 meja sekaligus secara mandiri.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2"
                            onclick="return confirm('Tembak billing massal sekarang? Periksa kembali rentang meja!')">
                            💥 TEMBAK BILLING ROKET
                        </button>
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
                    if (minutesLeft < nearlyThreshold) {
                        cardElement.classList.remove('bg-playing', 'bg-timeout-blink');
                        cardElement.classList.add('bg-nearly');
                        if (statusLabel) statusLabel.innerText = 'NEARLY';
                        previousStatuses[tableNumber] = 'NEARLY';
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
        // Tambahkan SEBELUM setInterval(updateTimers, 500):
        const nearlyThreshold = {{ \App\Models\Setting::where('key', 'nearly_warning_minutes')->value('value') ?? 20 }};
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

        // =========================================================================
        // PENGATURAN MODAL OPTION MEJA & EDIT NAMA CUSTOMER (UPDATED COMPLETE)
        // =========================================================================
        function showOptionModal(id, number) {
            window.currentSelectedTableId = id;
            document.getElementById('option-no-meja').innerText = number;
            document.getElementById('from-table-id').value = id;

            document.getElementById('option-customer-name-input').value = "";
            window.currentActiveTransactionId = null;

            const btnFnB = document.getElementById('btn-link-fnb');
            if (btnFnB) {
                btnFnB.href = `/admin/orderfnb?table_id=${id}`;
            }

            const tableBody = document.getElementById('fnb-table-body');
            const txtTotalQty = document.getElementById('fnb-total-qty');
            const txtTotalPrice = document.getElementById('fnb-total-price');
            const txtBillingPrice = document.getElementById('billing-main-price');
            const txtGrandTotal = document.getElementById('grand-total-bill');

            tableBody.innerHTML =
                `<tr><td colspan="3" class="text-center py-3 text-muted">Memuat rincian pesanan...</td></tr>`;
            txtTotalQty.innerText = "0 Pcs";
            txtTotalPrice.innerText = "Rp 0";
            txtBillingPrice.innerText = "Rp 0";
            txtGrandTotal.innerText = "Rp 0";

            fetch(`/admin/billing/active-detail/${id}`)
                .then(res => {
                    if (!res.ok) throw new Error('Server Return Error');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('option-customer-name-input').value = data.customer_name || 'GUEST';
                        window.currentActiveTransactionId = data.transaction_id;

                        // =========================================================================
                        // PERBAIKAN: HITUNG BIAYA BILLING MEJA (PERSONAL / HOURLY / PACKAGE)
                        // =========================================================================
                        let billingPrice = parseInt(data.billing_price) || 0;

                        // Jika billing_price dari backend 0/null, kalkulasi durasi berjalan secara real-time
                        if (billingPrice === 0 && data.start_time) {
                            const startTime = new Date(data.start_time).getTime();
                            const now = new Date().getTime();
                            const diffMinutes = Math.max(Math.ceil((now - startTime) / 60000), 1); // minimal 1 menit
                            const hourlyRate = parseInt(data.hourly_rate) || 29000; // default rate per jam
                            const minCharge = parseInt(data.min_charge) || 10000; // minimum charge personal

                            if (data.type === 'personal' || data.type === 'open') {
                                // Hitung per menit dari tarif jam
                                let calculated = Math.ceil((diffMinutes / 60) * hourlyRate);
                                billingPrice = Math.max(calculated, minCharge);
                            } else if (data.type === 'hourly' || data.type === 'manual') {
                                let calculated = Math.ceil((diffMinutes / 60) * hourlyRate);
                                billingPrice = calculated;
                            }
                        }

                        txtBillingPrice.innerText = `Rp ${billingPrice.toLocaleString('id-ID')}`;

                        let totalFnbPrice = 0;

                        if (data.fnb_orders && data.fnb_orders.length > 0) {
                            let htmlRows = '';
                            let totalQty = 0;

                            data.fnb_orders.forEach(item => {
                                let itemPrice = parseInt(item.price) || 0;
                                let itemSubtotal = parseInt(item.subtotal) || 0;
                                let itemQty = parseInt(item.stock) || 0;

                                totalQty += itemQty;
                                totalFnbPrice += itemSubtotal;

                                let priceDisplay = item.is_package_item ?
                                    `<span class="badge bg-green-lt">Include Paket (Rp 0)</span>` :
                                    `Rp ${itemPrice.toLocaleString('id-ID')} × ${itemQty}`;

                                let subtotalDisplay = item.is_package_item ?
                                    `<span class="text-success fw-bold">Rp 0</span>` :
                                    `Rp ${itemSubtotal.toLocaleString('id-ID')}`;

                                htmlRows += `
                                <tr class="fw-bold text-dark">
                                    <td>
                                        <div>${item.product_name}</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            ${priceDisplay}
                                        </div>
                                    </td>
                                    <td class="text-center valign-middle pt-2">
                                        <span class="badge bg-azure-lt fw-bold">${itemQty} Pcs</span>
                                    </td>
                                    <td class="text-end text-monospace valign-middle pt-2">
                                        ${subtotalDisplay}
                                    </td>
                                </tr>
                                `;
                            });

                            tableBody.innerHTML = htmlRows;
                            txtTotalQty.innerText = `${totalQty} Pcs`;
                            txtTotalPrice.innerText = `Rp ${totalFnbPrice.toLocaleString('id-ID')}`;

                        } else {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted italic">
                                        Tidak ada pesanan FnB di meja ini.
                                    </td>
                                </tr>
                            `;
                        }

                        // Grand Total = Total FnB + Biaya Billing Meja (Personal/Hourly/Package)
                        let grandTotal = totalFnbPrice + billingPrice;
                        txtGrandTotal.innerText = `Rp ${grandTotal.toLocaleString('id-ID')}`;
                    }
                })
                .catch(err => {
                    tableBody.innerHTML =
                        `<tr><td colspan="3" class="text-center text-danger py-3">Gagal memuat rincian data pesanan.</td></tr>`;
                    console.error("Terjadi error Fetch:", err);
                });

            new bootstrap.Modal(document.getElementById('modal-option-table')).show();
        }

        function updateCustomerNameDirectly() {
            const transactionId = window.currentActiveTransactionId;
            const newName = document.getElementById('option-customer-name-input').value.trim();

            if (!transactionId) {
                Swal.fire('Gagal!', 'Tidak ada transaksi aktif yang dapat diubah namanya pada meja ini.', 'error');
                return;
            }

            if (newName === "") {
                Swal.fire('Peringatan!', 'Nama customer tidak boleh dikosongkan.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Memperbarui Nama...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/admin/billing/update-customer-name`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        // Menggunakan cara alternatif penulisan CSRF Token bawaan Laravel yang jauh lebih aman
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId,
                        customer_name: newName
                    })
                })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Nama customer berhasil diubah.',
                            timer: 1000,
                            showConfirmButton: false
                        });

                        // Supaya grid warna/nama meja di dashboard berubah secara real-time
                        if (typeof getTablesStatus === "function") {
                            getTablesStatus();
                        }
                    } else {
                        Swal.fire('Gagal!', data.message || 'Gagal mengubah nama.', 'error');
                    }
                })
                .catch(err => {
                    Swal.close();
                    console.error(err);
                    Swal.fire('Sistem Error!', 'Gagal menghubungi server.', 'error');
                });
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
            const type = selectedOption.getAttribute('data-type');

            if (type === 'manual') {
                const hours = parseFloat(document.getElementById('input-hours').value) || 0;

                // 1. Ambil seluruh aturan harga dari database
                const pricingRules = @json($pricingRules);

                // 2. Ambil Waktu & Hari Kasir Aktif Saat Ini
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                const dayIndex = now.getDay(); // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu

                // Konversi index hari JS (0-6) ke standar ISO Laravel (1=Senin, 7=Minggu)
                const currentDayISO = dayIndex === 0 ? 7 : dayIndex;

                // Hitung total menit berjalan dari jam 00:00 pagi
                const totalCurrentMinutes = (currentHour * 60) + currentMinute;

                // Tentukan harga fallback utama (Rp 29.000 jika benar-benar tidak ada yang cocok)
                let pricePerHour = 29000;

                // 3. Iterasi pencocokan aturan harga
                for (let rule of pricingRules) {
                    if (!rule.active_days || !rule.start_time || !rule.end_time) continue;

                    // Bersihkan spasi dan jadikan array hari aktif
                    const activeDays = rule.active_days.toString().replace(/\s/g, '').split(',');

                    if (activeDays.includes(String(currentDayISO))) {
                        // Perbaikan: Pisahkan string waktu dan pastikan hanya mengambil Jam dan Menit (mengabaikan detik jika ada)
                        const startParts = rule.start_time.split(':');
                        const endParts = rule.end_time.split(':');

                        const startH = parseInt(startParts[0], 10);
                        const startM = parseInt(startParts[1], 10);
                        const endH = parseInt(endParts[0], 10);
                        const endM = parseInt(endParts[1], 10);

                        const startMinutes = (startH * 60) + startM;
                        const endMinutes = (endH * 60) + endM;

                        // Logika Aturan Waktu Melewati Tengah Malam (Contoh: 18:00:00 s.d 03:00:00)
                        if (endMinutes < startMinutes) {
                            if (totalCurrentMinutes >= startMinutes || totalCurrentMinutes < endMinutes) {
                                pricePerHour = parseFloat(rule.price_per_hour);
                                break;
                            }
                        }
                        // Logika Aturan Waktu Normal di Hari yang Sama (Contoh: 08:00:00 s.d 17:00:00)
                        else {
                            if (totalCurrentMinutes >= startMinutes && totalCurrentMinutes <= endMinutes) {
                                pricePerHour = parseFloat(rule.price_per_hour);
                                break;
                            }
                        }
                    }
                }

                // 4. Kalkulasi total akhir harga
                const totalPrice = hours * pricePerHour;
                document.getElementById('display-harga').innerText = totalPrice.toLocaleString('id-ID');
            }
        }

        function handleBillingSelection() {
            const selector = document.getElementById('billing-selector');
            const selectedOption = selector.options[selector.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            const manualContainer = document.getElementById('manual-duration-container');
            const inputPackageId = document.getElementById('input-package-id');

            // Reset default
            manualContainer.classList.add('d-none');
            if (inputPackageId) inputPackageId.value = '';

            if (type === 'manual') {
                manualContainer.classList.remove('d-none');
                calculatePrice();
            } else if (type === 'personal') {
                const minCharge = {{ $currentRule?->min_charge ?? 10000 }};
                document.getElementById('display-harga').innerText = minCharge.toLocaleString('id-ID');
            } else if (type === 'package') {
                const price = selectedOption.getAttribute('data-price');
                const pkgId = selectedOption.getAttribute('data-package-id');

                // 🚀 Set package_id ke input hidden
                if (inputPackageId) inputPackageId.value = pkgId;

                document.getElementById('display-harga').innerText = parseInt(price).toLocaleString('id-ID');
            }
        }

        function handleRocketBillingSelection() {
            const selector = document.getElementById('rocket-billing-selector');
            const selectedOption = selector.options[selector.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            const manualContainer = document.getElementById('rocket-manual-duration-container');

            // Reset tampilan input jam manual
            manualContainer.classList.add('d-none');

            // Ambil elemen display yang dibutuhkan
            const displayHarga = document.getElementById('rocket-display-harga');
            const summaryMeja = document.getElementById('rocket-summary-meja');

            if (type === 'manual') {
                manualContainer.classList.remove('d-none');
                calculateRocketPrice();
            } else if (type === 'personal') {
                const minCharge = {{ $currentRule?->min_charge ?? 10000 }};
                if (displayHarga) displayHarga.innerText = minCharge.toLocaleString('id-ID');
                if (summaryMeja) summaryMeja.innerText = summaryMeja.innerText; // no-op to avoid undefined usage
            } else if (type === 'package') {
                calculateRocketPrice();
            }
        }

        function calculateRocketPrice() {
            const selector = document.getElementById('rocket-billing-selector');
            if (!selector || selector.selectedIndex === -1) return;

            const selectedOption = selector.options[selector.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            const priceAttr = selectedOption.getAttribute('data-price');
            const displayHarga = document.getElementById('rocket-display-harga');
            const summaryMeja = document.getElementById('rocket-summary-meja');

            // Hitung jangkauan nomor meja untuk info kasir
            const startTable = parseInt(document.getElementById('rocket-start-table').value) || 1;
            const endTable = parseInt(document.getElementById('rocket-end-table').value) || 1;

            let totalMejaTerpilih = (endTable - startTable) + 1;
            if (totalMejaTerpilih <= 0) totalMejaTerpilih = 0;

            // Default per-meja dan total
            let hargaPerMeja = 0;
            let totalHarga = 0;

            if (type === 'package') {
                hargaPerMeja = parseInt(priceAttr) || 0;
            } else if (type === 'manual') {
                const hours = parseFloat(document.getElementById('rocket-input-hours').value) || 0;
                const pricingRules = @json($pricingRules);

                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                const dayIndex = now.getDay(); // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
                const currentDayISO = dayIndex === 0 ? 7 : dayIndex;
                const totalCurrentMinutes = (currentHour * 60) + currentMinute;

                let pricePerHour = 29000;
                for (let rule of pricingRules) {
                    if (!rule.active_days || !rule.start_time || !rule.end_time) continue;
                    const activeDays = rule.active_days.toString().replace(/\s/g, '').split(',');
                    if (activeDays.includes(String(currentDayISO))) {
                        const startParts = rule.start_time.split(':');
                        const endParts = rule.end_time.split(':');
                        const startMinutes = (parseInt(startParts[0], 10) * 60) + parseInt(startParts[1], 10);
                        const endMinutes = (parseInt(endParts[0], 10) * 60) + parseInt(endParts[1], 10);

                        if (endMinutes < startMinutes) {
                            if (totalCurrentMinutes >= startMinutes || totalCurrentMinutes < endMinutes) {
                                pricePerHour = parseFloat(rule.price_per_hour);
                                break;
                            }
                        } else {
                            if (totalCurrentMinutes >= startMinutes && totalCurrentMinutes <= endMinutes) {
                                pricePerHour = parseFloat(rule.price_per_hour);
                                break;
                            }
                        }
                    }
                }

                hargaPerMeja = hours * pricePerHour;

            } else if (type === 'personal') {
                // Ambil min_charge dari server (nilai saat halaman dirender)
                hargaPerMeja = {{ $currentRule?->min_charge ?? 10000 }};
            }

            // Hitung total untuk semua meja yang dipilih
            totalHarga = hargaPerMeja * totalMejaTerpilih;

            // Debug: log nilai penting ke console
            console.log('calculateRocketPrice', {
                type,
                startTable,
                endTable,
                totalMejaTerpilih,
                hargaPerMeja,
                totalHarga
            });

            // Tampilkan: per meja dan total di summary
            if (displayHarga) displayHarga.innerText = hargaPerMeja.toLocaleString('id-ID');
            if (summaryMeja) summaryMeja.innerText =
                `*Akan mengaktifkan ${totalMejaTerpilih} meja sekaligus secara mandiri. Total: Rp ${totalHarga.toLocaleString('id-ID')}`;
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

        function showRocketModal() {
            // Memastikan modal Bootstrap terpicu dengan aman
            const rocketModalEl = document.getElementById('modal-rocket-billing');
            if (rocketModalEl) {
                const modal = new bootstrap.Modal(rocketModalEl);
                modal.show();

                // Pastikan tampilan harga ter-update segera saat modal dibuka
                // (tidak perlu admin mengubah input meja dulu)
                setTimeout(() => {
                    try {
                        handleRocketBillingSelection();
                        calculateRocketPrice();
                    } catch (e) {
                        console.error('Error updating rocket prices on modal open', e);
                    }
                }, 50);

            } else {
                console.error("Elemen modal-rocket-billing tidak ditemukan!");
            }
        }
    </script>
@endsection
