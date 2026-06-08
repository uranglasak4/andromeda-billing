@extends('customer.customer')

@section('content')
    <!-- Sesi Flash Message SweetAlert Handling -->
    @if (session('success_online'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Daftar Antrean Sukses! 🎉',
                    text: "{{ session('success_online') }}",
                    icon: 'success',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Sip, Paham!'
                });
            });
        </script>
    @endif

    @if (session('invalid_wa'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Nomor WA Tidak Valid! ❌',
                    text: "{{ session('invalid_wa') }}",
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Coba Nomor Lain'
                });
            });
        </script>
    @endif

    @if (session('duplicate_wa'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Nomor Sudah Terdaftar! ⚠️',
                    text: "{{ session('duplicate_wa') }}",
                    icon: 'warning',
                    confirmButtonColor: '#f0a30a',
                    confirmButtonText: 'Ganti Nomor Lain'
                });
            });
        </script>
    @endif

    <!-- HEADER MONITOR -->
    <div class="row align-items-center mb-3 border-bottom pb-2">
        <div class="col">
            <h1 class="text-dark fw-bold mb-0" style="font-size: 28px; letter-spacing: 2px;">ANDROMEDA BILLIARD</h1>
            <div class="text-muted small fw-bold">LIVE VISITOR MONITORING BOARD</div>
        </div>
        <div class="col-auto text-end">
            <div id="live-clock" class="h3 font-countdown text-dark mb-0">00:00:00</div>
            <span class="badge bg-green-lt fw-bold">REALTIME SYNC ACTIVE</span>
        </div>
    </div>

    <!-- BODY MONITOR -->
    <div class="row g-3">
        <!-- SISI KIRI: GRID MEJA BILIAR -->
        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-2">
                    <h3 class="card-title fw-bold">📌 STATUS MEJA BILIAR</h3>
                </div>
                <div class="card-body p-3">
                    <div id="customer-table-grid" class="row row-cards g-2">
                        <!-- Ditangani secara dinamis oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: LIST WAITING LIST + TOMBOL DAFTAR -->
        <div class="col-md-3">
            <button class="btn btn-danger w-100 mb-2 py-2 fw-bold shadow-sm" onclick="checkTableAvailability()">
                🚀 DAFTAR WAITING LIST DI SINI
            </button>

            <div class="card shadow-sm border-0" style="min-height: 70vh;">
                <div class="card-header bg-secondary text-white py-2">
                    <h3 class="card-title fw-bold">📋 LIST ANTRIAN</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped card-table mb-0">
                            <thead>
                                <tr class="bg-light text-muted small fw-bold">
                                    <th style="width: 15%">NO</th>
                                    <th>NAMA / TIM</th>
                                    <th class="text-end">STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="customer-waiting-grid">
                                <!-- Ditangani secara dinamis oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM INPUT WAITING LIST (POP UP) -->
    <div class="modal modal-blur fade" id="modal-waiting-list" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">📝 Form Pendaftaran Antrean</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('customer.waiting-list.store') }}" method="POST" id="form-waiting-list">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Nama Anda / Nama Tim</label>
                            <input type="text" class="form-control text-uppercase" name="nama_pelanggan"
                                placeholder="CONTOH: BUDI / FAJAR CS" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Nomor WhatsApp Aktif</label>
                            <div class="input-group input-group-flat">
                                <span class="input-group-text bg-light text-muted fw-bold">+62</span>
                                <input type="number" class="form-control" name="nomor_wa" placeholder="81234567xxx"
                                    required autocomplete="off">
                            </div>
                            <small class="form-hint text-muted mt-1 fw-bold">
                                *Sistem otomatis mengirimkan nomor urut & kode OTP verifikasi langsung ke nomor WhatsApp di
                                atas.
                            </small>
                        </div>

                        {{-- ✅ CLOUDFLARE TURNSTILE WIDGET --}}
                        <div class="mb-2 d-flex justify-content-center">
                            <div class="cf-turnstile" data-sitekey="{{ env('TURNSTILE_SITE_KEY') }}" data-theme="light"
                                data-language="id">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link link-secondary fw-bold"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" onclick="return validateTurnstile()">
                            Ambil Antrean Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-3 text-muted small border-top pt-2">
        <span>Andromeda Billiard & Cafe © {{ date('Y') }} — Realtime Self-Service Station</span>
    </div>

    <script>
        // Live Clock Engine
        setInterval(() => {
            const now = new Date();
            document.getElementById('live-clock').innerText = now.toLocaleTimeString('id-ID');
        }, 1000);

        let hasAvailableTable = false;
        let currentOnlineCount = 0;
        // 1. Definisikan variabel limit secara global, default awal kita set 0 dulu
        let maxOnlineLimit = 0;

        function fetchLiveMonitorData() {
            fetch("{{ route('api.tables.status') }}")
                .then(response => response.json())
                .then(data => {
                    const tables = data.tables || [];
                    const waitingList = data.waiting_list || [];

                    // 2. AMBIL LIMIT SECARA DINAMIS DARI API (Jika API belum melempar, kita backup ke data controller)
                    // Kita buat fleksibel agar membaca properti max_online_queue langsung dari respon json server
                    if (data.max_online_queue) {
                        maxOnlineLimit = parseInt(data.max_online_queue);
                    } else {
                        maxOnlineLimit =
                            {{ \App\Models\Setting::where('key', 'max_online_queue')->value('value') ?? 15 }};
                    }

                    let checkAvailable = false;
                    let tableGridHTML = '';

                    // Hitung jumlah antrean online aktif saat ini
                    currentOnlineCount = waitingList.filter(guest => guest.tipe === 'online' && (guest.status ===
                        'waiting' || guest.status === 'not_verified')).length;

                    tables.forEach(table => {
                        let bgClass = 'bg-available';
                        let statusText = 'AVAILABLE';
                        let timerDisplay = 'READY';
                        let textClass = 'text-dark';
                        let currentStatus = table.status;

                        const activeTx = (table.transactions && table.transactions.length > 0) ? table
                            .transactions[0] : null;

                        if ((currentStatus === 'playing' || currentStatus === 'nearly') && activeTx && activeTx
                            .end_time) {
                            const endTimeMs = new Date(activeTx.end_time).getTime();
                            const nowMs = new Date().getTime();
                            let diff = endTimeMs - nowMs;

                            if (diff > 0) {
                                let hours = Math.floor(diff / 3600000).toString().padStart(2, '0');
                                let minutes = Math.floor((diff % 3600000) / 60000).toString().padStart(2, '0');
                                let seconds = Math.floor((diff % 60000) / 1000).toString().padStart(2, '0');
                                timerDisplay = `${hours}:${minutes}:${seconds}`;
                                if (diff <= 1200000) currentStatus = 'nearly';
                            } else {
                                timerDisplay = '00:00:00';
                                currentStatus = 'timeout';
                            }
                        } else if (currentStatus === 'personal' && activeTx && activeTx.start_time) {
                            const startTimeMs = new Date(activeTx.start_time).getTime();
                            const nowMs = new Date().getTime();
                            let diffForward = nowMs - startTimeMs;
                            if (diffForward > 0) {
                                let hours = Math.floor(diffForward / 3600000).toString().padStart(2, '0');
                                let minutes = Math.floor((diffForward % 3600000) / 60000).toString().padStart(2,
                                    '0');
                                let seconds = Math.floor((diffForward % 60000) / 1000).toString().padStart(2,
                                    '0');
                                timerDisplay = `${hours}:${minutes}:${seconds}`;
                            } else {
                                timerDisplay = '00:00:00';
                            }
                        }

                        if (currentStatus === 'playing') {
                            bgClass = 'bg-playing';
                            statusText = 'PLAYING';
                            textClass = 'text-white';
                        } else if (currentStatus === 'nearly') {
                            bgClass = 'bg-nearly';
                            statusText = 'NEARLY END';
                            textClass = 'text-white';
                        } else if (currentStatus === 'personal') {
                            bgClass = 'bg-personal';
                            statusText = 'OPEN PLAY';
                            textClass = 'text-dark';
                        } else if (currentStatus === 'timeout') {
                            bgClass = 'bg-timeout-blink text-white';
                            statusText = '<span class="blink-text fw-bold">TIME OUT</span>';
                            timerDisplay = '00:00:00';
                            textClass = 'text-white';
                        } else if (currentStatus === 'maintenance') {
                            bgClass = 'bg-maintenance';
                            statusText = 'MAINTENANCE';
                            timerDisplay = 'OFF';
                            textClass = 'text-danger';
                        }

                        if (currentStatus === 'available' || currentStatus === 'timeout') checkAvailable = true;

                        tableGridHTML += `
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="card ${bgClass} ${textClass} card-table-admin border-0 shadow-sm" style="min-height: 140px;">
                                    <div class="card-body d-flex flex-column justify-content-between p-3 text-center">
                                        <div><div class="small fw-bold text-uppercase opacity-75">MEJA</div><div class="h1 m-0 fw-bold font-countdown" style="font-size: 38px; line-height:1;">${table.table_number}</div></div>
                                        <div class="my-2"><div class="h3 font-countdown m-0 fw-bold" style="letter-spacing: 1px;">${timerDisplay}</div></div>
                                        <div><div class="small fw-bold text-uppercase" style="font-size: 11px; letter-spacing:1px;">${statusText}</div></div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('customer-table-grid').innerHTML = tableGridHTML;
                    hasAvailableTable = checkAvailable;

                    let waitingHTML = '';
                    if (waitingList.length === 0) {
                        waitingHTML =
                            `<tr><td colspan="3" class="text-center text-muted py-4 small">🍃 Antrean kosong, meja siap dipesan.</td></tr>`;
                    } else {
                        waitingList.forEach((guest, index) => {
                            let statusBadgeHTML = '<span class="badge bg-orange-lt px-2 py-1">WAITING</span>';
                            if (guest.tipe === 'onsite') {
                                statusBadgeHTML =
                                    '<span class="badge bg-success-lt px-2 py-1">📍 ON-SITE KASIR</span>';
                            } else if (guest.tipe === 'online') {
                                statusBadgeHTML = (guest.status === 'verified' || guest.status === 'waiting') ?
                                    '<span class="badge bg-blue-lt px-2 py-1">🌐 ONLINE WEB VERIFIED</span>' :
                                    '<span class="badge bg-danger-lt px-2 py-1">⏳ ONLINE WEB UNVERIFIED</span>';
                            }
                            waitingHTML += `
                                <tr class="fw-bold text-dark">
                                    <td><span class="badge bg-secondary-lt">${index + 1}</span></td>
                                    <td class="text-uppercase text-truncate" style="max-width: 120px;" title="${guest.customer_name}">${guest.customer_name}</td>
                                    <td class="text-end">${statusBadgeHTML}</td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('customer-waiting-grid').innerHTML = waitingHTML;
                })
                .catch(err => console.error("Gagal sinkronisasi data monitor:", err));
        }

        // FUNGSI CEK KETERSEDIAAN DAN BATAS LIMIT KUOTA (FIXED & DINAMIS)
        function checkTableAvailability() {
            if (hasAvailableTable) {
                Swal.fire({
                    title: 'Meja Masih Tersedia! 🎱',
                    text: 'Masih ada meja biliar yang Ready. Kamu tidak perlu waiting list. Silakan langsung pesan ke kasir Andromeda!',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Oke, Siap!'
                });
            } else if (currentOnlineCount >= maxOnlineLimit) {
                // SEKARANG MENGIKUTI DATA INPUTAN MASTER SECARA REALTIME
                Swal.fire({
                    title: 'Kuota Online Penuh! 📋',
                    text: 'Maaf, kuota maksimal waiting list via website sudah mencapai batas (' + maxOnlineLimit +
                        ' Antrean). Silakan datang langsung untuk mengambil antrean offline di meja kasir.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Baik, Saya ke Lokasi'
                });
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('modal-waiting-list'));
                myModal.show();
            }
        }

        // ✅ VALIDASI TURNSTILE SEBELUM FORM SUBMIT
        function validateTurnstile() {
            const token = document.querySelector('[name="cf-turnstile-response"]');
            if (!token || !token.value) {
                Swal.fire({
                    title: 'Verifikasi Diperlukan!',
                    text: 'Mohon selesaikan verifikasi "Saya bukan robot" terlebih dahulu.',
                    icon: 'warning',
                    confirmButtonColor: '#f0a30a',
                    confirmButtonText: 'Oke'
                });
                return false;
            }
            return true;
        }

        // Reset Turnstile widget setiap kali modal dibuka agar tidak stale
        document.getElementById('modal-waiting-list').addEventListener('show.bs.modal', function() {
            if (typeof turnstile !== 'undefined') {
                turnstile.reset();
            }
        });

        fetchLiveMonitorData();
        setInterval(fetchLiveMonitorData, 1000);
    </script>
@endsection
