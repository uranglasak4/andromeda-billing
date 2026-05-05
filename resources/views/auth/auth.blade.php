<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <title>Login - Andromeda Billiard</title>
    <link href="{{ asset('dist/css/tabler.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('dist/css/demo.min.css') }}" rel="stylesheet"/>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1 class="navbar-brand navbar-brand-autodark">*ANDROMEDA BILLIARD.png</h1>
        </div>
        
        <div class="card card-md">
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