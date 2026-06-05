{{-- File: resources/views/customer/customer.blade.php --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>Live Monitor - Andromeda Billiard & Cafe</title>

    <!-- Tabler Core CSS -->
    <link href="{{ asset('dist/css/tabler.min.css') }}" rel="stylesheet" />
    <!-- Andromeda Custom CSS -->
    <link href="{{ asset('dist/css/andromeda.css') }}" rel="stylesheet">
    <!-- Font Digital Countdown -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">

    <style>
        /* Desain khusus font countdown agar mirip jam digital */
        .font-countdown {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
        }
        /* Animasi berkedip text TIMEOUT */
        @keyframes text-blink {
            0% { opacity: 1; }
            50% { opacity: 0.2; }
            100% { opacity: 1; }
        }
        .blink-text {
            animation: text-blink 1s infinite;
        }
    </style>
</head>
<body class="d-flex flex-column bg-white">
    <div class="page">
        <!-- Menggunakan container-fluid agar memanfaatkan seluruh lebar layar TV -->
        <div class="container-fluid py-3">
            @yield('content')
        </div>
    </div>

    <!-- Tabler Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('dist/js/tabler.min.js') }}"></script>
</body>
</html>
