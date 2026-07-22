@extends('customer.customer')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white text-center py-3">
                <h4 class="modal-title fw-bold m-0">🔐 VERIFIKASI OTP WAITING LIST</h4>
            </div>
            <div class="card-body p-4 text-center">

                @if(session('otp_error'))
                    <div class="alert alert-danger fw-bold mb-3 small">{{ session('otp_error') }}</div>
                @endif

                <p class="text-muted small">
                    Kami telah mengirimkan kode OTP ke nomor WhatsApp Anda. <br>
                    Silakan masukkan kode tersebut sebelum waktu habis untuk menghindari <strong>Antrean Palsu</strong>.
                </p>

                <form action="{{ route('customer.waiting-list.check-otp') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ session('waiting_list_id') }}">

                    <div class="mb-4">
                        <input type="number" name="otp_code" class="form-control text-center fw-bold font-countdown"
                               placeholder="Masukkan Kode OTP" style="font-size: 24px; letter-spacing: 5px;" required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <div class="badge bg-danger-lt p-2 fw-bold" style="font-size: 14px;">
                            ⏳ Sisa Waktu Verifikasi: <span id="countdown-timer" class="font-countdown">60</span> Detik
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                        Verifikasi Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Timer 1 Menit (60 Detik)
    let timeLeft = 60;
    const timerElement = document.getElementById('countdown-timer');

    const interval = setInterval(() => {
        timeLeft--;
        timerElement.innerText = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(interval);

            // Trigger AJAX untuk ubah status ke 'failed' di database (TIDAK DIDELETE)
            fetch("{{ route('customer.waiting-list.timeout') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ id: "{{ session('waiting_list_id') }}" })
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    title: 'Waktu Habis! ❌',
                    text: 'Maaf, batas waktu verifikasi 1 menit telah habis. Antrean Anda otomatis dibatalkan.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Kembali Ke Menu Utama'
                }).then(() => {
                    window.location.href = "{{ route('customer.index') }}";
                });
            });
        }
    }, 1000);
</script>
@endsection
