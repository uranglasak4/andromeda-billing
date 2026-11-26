<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <title>Login - Andromeda Billiard & Cafe</title>
    <link href="{{ asset('dist/css/tabler.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('dist/css/demo.min.css') }}" rel="stylesheet"/>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">

        <div class="card card-md shadow-sm overflow-hidden">
          <!-- Header Gelap untuk Logo Putih -->
          <div class="text-center py-4 px-3" style="background: radial-gradient(circle at center, #1b2234 0%, #0b0e14 100%);">
            <img src="{{ asset('images/andromeda-logo.png') }}"
                 alt="Andromeda Billiard & Cafe"
                 style="max-width: 230px; height: auto; mix-blend-mode: screen;">
          </div>

          <div class="card-body">
            <h2 class="h2 text-center mb-4">Login Akun</h2>

            @yield('content')

          </div>
        </div>

      </div>
    </div>
    <script src="{{ asset('dist/js/tabler.min.js') }}" defer></script>
  </body>
</html>
