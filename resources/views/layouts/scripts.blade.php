{{-- File: resources/views/layouts/scripts.blade.php --}}

<audio id="snd-billing" src="{{ asset('sound/billing.wav') }}" preload="auto"></audio>
<audio id="snd-finished" src="{{ asset('sound/finished.wav') }}" preload="auto"></audio>
<audio id="snd-timeout" src="{{ asset('sound/timeout.wav') }}" preload="auto"></audio>

<script src="{{ asset('dist/js/tabler.min.js') }}"></script>

<script>
    // ========================================================
    // GLOBAL FUNCTIONS & AUTH LOGOUT (Berlaku untuk semua)
    // ========================================================
    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Apakah kamu yakin?',
            text: "Sesi kerja kamu di Andromeda Billiard akan diakhiri!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Keluar!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('logout') }}";
            }
        });
    }

    // ====================================================================
    // NOTE: SOLUSI A (BACKGROUND CHECK INTERVAL 10 DETIK) DIHAPUS
    // AGAR SUARA TIMEOUT TIDAK TERTRIGER BERULANG-ULANG SECARA BRUTEFORCE.
    // SUARA SKRG DIKENDALIKAN SEPENUHNYA OLEH TRANSMISI JS DI DASHBOARDADMIN.
    // ====================================================================
</script>
