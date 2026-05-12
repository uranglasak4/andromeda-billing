<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>Admin Dashboard - Andromeda Billiard</title>

    <link href="{{ asset('dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dist/css/andromeda.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .navbar-brand-title {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
        }
    </style>
</head>

<body>
    <script src="{{ asset('dist/js/demo-theme.min.js') }}"></script>
    <div class="page">

        <body class="layout-fluid">
            <div class="page">
                <!-- Navbar Atas -->
                <header class="navbar navbar-expand-md navbar-light d-print-none">
                    <div class="container-fluid">
                        <h1 class="navbar-brand d-none-navbar-horizontal pe-0 pe-md-3">
                            <a href=".">ANDROMEDA</a>
                        </h1>
                        <div class="navbar-nav flex-row order-md-last">
                            <!-- Tombol Dark/Light Mode -->
                            <div class="nav-item d-none d-md-flex me-3">
                                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                                    </svg>
                                </a>
                                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                        <path
                                            d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                                    </svg>
                                </a>
                            </div>
                            <!-- Info Admin -->
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link d-flex lh-1 text-reset p-0">
                                    <span class="avatar avatar-sm" style="background-image: url(...)">@</span>
                                    <div class="d-none d-xl-block ps-2">
                                        <div>{{ auth()->user()->name }}</div>
                                        <div class="mt-1 small text-muted text-uppercase">{{ auth()->user()->role }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Menu Navigasi -->
                <header class="navbar-expand-md">
                    <div class="collapse navbar-collapse" id="navbar-menu">
                        <div class="navbar navbar-light">
                            <div class="container-fluid">
                                <ul class="navbar-nav">
                                    {{-- MENU UNTUK MASTER / OWNER --}}
                                    @if (auth()->user()->role == 'master')
                                        <li class="nav-item {{ Request::is('master/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('master.dashboard') }}">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                        width="24" height="24" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                        <path d="M13.45 11.55l2.05 -2.05" />
                                                        <path d="M6.4 20a9 9 0 1 1 11.2 0z" />
                                                    </svg>
                                                </span>
                                                <span class="nav-link-title">Dashboard Master</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">
                                                <span class="nav-link-title">Manajemen Meja</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">
                                                <span class="nav-link-title">Waiting List</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">
                                                <span class="nav-link-title">Laporan Keuangan</span>
                                            </a>
                                        </li>

                                        {{-- MENU UNTUK ADMIN / KASIR --}}
                                    @elseif(auth()->user()->role == 'admin')
                                        <li class="nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                        width="24" height="24" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                        <path d="M13.45 11.55l2.05 -2.05" />
                                                        <path d="M6.4 20a9 9 0 1 1 11.2 0z" />
                                                    </svg>
                                                </span>
                                                <span class="nav-link-title">Monitoring Meja</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">
                                                <span class="nav-link-title">Laporan Keuangan</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="page-wrapper">
                    @yield('content')
                </div>
            </div>
            <script src="{{ asset('dist/js/tabler.min.js') }}" defer></script>
        </body>

</html>
