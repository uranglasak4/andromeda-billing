@extends('customer.customer')

@section('content')
    <!-- NOTIFIKASI BERHASIL DAFTAR -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm border-0 mb-3" role="alert">
            <div class="d-flex">
                <div>
                    <h4 class="alert-title fw-bold">🎉 Berhasil!</h4>
                    <div class="text-secondary">{{ session('success') }}</div>
                </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
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
            <!-- TOMBOL DI ATAS KARTU UNTUK INPUT ANTRIAN -->
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

    <!-- ==================================================================== -->
    <!-- MODAL FORM INPUT WAITING LIST (POP UP) -->
    <!-- ==================================================================== -->
    <div class="modal modal-blur fade" id="modal-waiting-list" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">📝 Form Pendaftaran Antrean</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                {{-- Cari baris tag form modal ini: --}}
                <form action="{{ route('customer.waiting-list.store') }}" method="POST">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Nama Anda / Nama Tim</label>
                            <input type="text" class="form-control text-uppercase" name="nama_pelanggan"
                                placeholder="CONTOH: BUDI / FAJAR CS" required autocomplete="off">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">Nomor WhatsApp Aktif</label>
                            <div class="input-group input-group-flat">
                                <span class="input-group-text bg-light text-muted fw-bold">+62</span>
                                <input type="number" class="form-control" name="nomor_wa" placeholder="81234567xxx"
                                    required autocomplete="off">
                            </div>
                            <small class="form-hint text-danger mt-1 fw-bold">
                                *Pastikan nomor aktif. Sistem kami akan mengirimkan pesan otomatis jika meja biliar siap
                                digunakan.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link link-secondary fw-bold"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">Ambil Antrean Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-3 text-muted small border-top pt-2">
        <span>Andromeda Billiard & Cafe © {{ date('Y') }} — Realtime Self-Service Station</span>
    </div>

    <!-- ==================================================================== -->
    <!-- JAVASCRIPT AJAX FETCH ENGINE (FULL FIXED) -->
    <!-- ==================================================================== -->
    <script>
        // Live Clock Engine
        setInterval(() => {
            const now = new Date();
            document.getElementById('live-clock').innerText = now.toLocaleTimeString('id-ID');
        }, 1000);

        // Tambahkan variabel global untuk memantau status ketersediaan meja biliar
        let hasAvailableTable = false;

        function fetchLiveMonitorData() {
            fetch("{{ route('api.tables.status') }}")
                .then(response => response.json())
                .then(data => {
                    const tables = data.tables || [];
                    const waitingList = data.waiting_list || [];

                    let checkAvailable = false;
                    let tableGridHTML = '';

                    tables.forEach(table => {
                        let bgClass = 'bg-available';
                        let statusText = 'AVAILABLE';
                        let timerDisplay = 'READY';
                        let textClass = 'text-dark';

                        let currentStatus = table.status; // Simpan status asli meja dari DB

                        // AMBIL TRANSAKSI AKTIF YANG SEDANG RUNNING
                        const activeTx = (table.transactions && table.transactions.length > 0) ?
                            table.transactions[0] :
                            null;

                        // -----------------------------------------------------------------
                        // ENGINE 1: HITUNG REALTIME (MUNDUR UNTUK PAKET & MAJU UNTUK PERSONAL)
                        // -----------------------------------------------------------------
                        if ((currentStatus === 'playing' || currentStatus === 'nearly') && activeTx && activeTx.end_time) {
                            // A. LOGIKA HITUNG MUNDUR (Billing Berdurasi/Paket)
                            const endTimeMs = new Date(activeTx.end_time).getTime();
                            const nowMs = new Date().getTime();
                            let diff = endTimeMs - nowMs;

                            if (diff > 0) {
                                let hours = Math.floor(diff / 3600000).toString().padStart(2, '0');
                                let minutes = Math.floor((diff % 3600000) / 60000).toString().padStart(2, '0');
                                let seconds = Math.floor((diff % 60000) / 1000).toString().padStart(2, '0');

                                timerDisplay = `${hours}:${minutes}:${seconds}`;

                                // DETEKSI WARNA HIJAU: Jika sisa waktu <= 20 menit (1200000 ms)
                                if (diff <= 1200000) {
                                    currentStatus = 'nearly';
                                }
                            } else {
                                timerDisplay = '00:00:00';
                                currentStatus = 'timeout';
                            }
                        } else if (currentStatus === 'personal' && activeTx && activeTx.start_time) {
                            // B. LOGIKA HITUNG MAJU (Billing Open Play / Personal)
                            const startTimeMs = new Date(activeTx.start_time).getTime();
                            const nowMs = new Date().getTime();
                            let diffForward = nowMs - startTimeMs;

                            if (diffForward > 0) {
                                let hours = Math.floor(diffForward / 3600000).toString().padStart(2, '0');
                                let minutes = Math.floor((diffForward % 3600000) / 60000).toString().padStart(2, '0');
                                let seconds = Math.floor((diffForward % 60000) / 1000).toString().padStart(2, '0');

                                timerDisplay = `${hours}:${minutes}:${seconds}`;
                            } else {
                                timerDisplay = '00:00:00';
                            }
                        }

                        // -----------------------------------------------------------------
                        // ENGINE 2: MAP WARNA & TEKS BERDASARKAN STATUS TERBARU
                        // -----------------------------------------------------------------
                        if (currentStatus === 'playing') {
                            bgClass = 'bg-playing';
                            statusText = 'PLAYING';
                            textClass = 'text-white';
                        } else if (currentStatus === 'nearly') {
                            bgClass = 'bg-nearly'; // Mengaktifkan warna Hijau
                            statusText = 'NEARLY END';
                            textClass = 'text-white';
                        } else if (currentStatus === 'personal') {
                            bgClass = 'bg-personal'; // Mengaktifkan warna Jingga/Kuning
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

                        // Katup pengaman validasi tombol waiting list pelanggan
                        if (currentStatus === 'available' || currentStatus === 'timeout') {
                            checkAvailable = true;
                        }

                        tableGridHTML += `
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="card ${bgClass} ${textClass} card-table-admin border-0 shadow-sm" style="min-height: 140px;">
                                    <div class="card-body d-flex flex-column justify-content-between p-3 text-center">
                                        <div>
                                            <div class="small fw-bold text-uppercase opacity-75">MEJA</div>
                                            <div class="h1 m-0 fw-bold font-countdown" style="font-size: 38px; line-height:1;">${table.table_number}</div>
                                        </div>
                                        <div class="my-2">
                                            <div class="h3 font-countdown m-0 fw-bold" style="letter-spacing: 1px;">${timerDisplay}</div>
                                        </div>
                                        <div>
                                            <div class="small fw-bold text-uppercase" style="font-size: 11px; letter-spacing:1px;">${statusText}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('customer-table-grid').innerHTML = tableGridHTML;
                    hasAvailableTable = checkAvailable;

                    // --- 2. DISPLAY GENERATOR LIVE LIST ANTRIAN WAITING LIST ---
                    let waitingHTML = '';
                    if (waitingList.length === 0) {
                        waitingHTML = `
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4 small">
                                    🍃 Antrean kosong, meja siap dipesan.
                                </td>
                            </tr>
                        `;
                    } else {
                        waitingList.forEach((guest, index) => {
                            waitingHTML += `
                                <tr class="fw-bold text-dark">
                                    <td><span class="badge bg-secondary-lt">${index + 1}</span></td>
                                    <td class="text-uppercase" style="font-size: 13px;">${guest.customer_name}</td>
                                    <td class="text-end">
                                        <span class="badge bg-orange-lt px-2 py-1">WAITING</span>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('customer-waiting-grid').innerHTML = waitingHTML;
                })
                .catch(err => console.error("Gagal sinkronisasi data monitor:", err));
        }

        // FUNGSI CEK KETERSEDIAAN SEBELUM DAFTAR ANTRIAN (SWEETALERT VALVE)
        function checkTableAvailability() {
            if (hasAvailableTable) {
                Swal.fire({
                    title: 'Meja Masih Tersedia! 🎱',
                    text: 'Masih ada meja biliar yang kosong/ready. Kamu tidak perlu mengantri di waiting list. Silakan langsung pesan ke kasir Andromeda!',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Oke, Siap!'
                });
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('modal-waiting-list'));
                myModal.show();
            }
        }

        // Panggil fungsi perdana
        fetchLiveMonitorData();

        // Loop sinkronisasi sinkron per 1 detik
        setInterval(fetchLiveMonitorData, 1000);
    </script>
@endsection
