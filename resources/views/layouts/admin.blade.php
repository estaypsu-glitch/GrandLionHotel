<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - {{ config('app.name', 'The Grand Lion Hotel') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('brand/lion_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('brand/lion_logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --theme-primary: #b89254;
            --theme-secondary: #92713c;
            --theme-ink: #1f2530;
            --theme-primary-rgb: 184, 146, 84;
            --theme-secondary-rgb: 146, 113, 60;
            --theme-ink-rgb: 31, 37, 48;

            --admin-bg: #f7f3ec;
            --admin-surface: #ffffff;
            --admin-surface-soft: rgba(var(--theme-primary-rgb), 0.08);
            --admin-line: rgba(var(--theme-primary-rgb), 0.34);
            --admin-line-strong: rgba(var(--theme-secondary-rgb), 0.42);
            --admin-brand: var(--theme-primary);
            --admin-brand-dark: var(--theme-secondary);
            --admin-ink: var(--theme-ink);
            --admin-muted: rgba(var(--theme-ink-rgb), 0.72);
            --admin-shadow: 0 8px 20px rgba(var(--theme-ink-rgb), 0.1);

            --bs-primary: var(--theme-primary);
            --bs-primary-rgb: var(--theme-primary-rgb);
            --bs-success: var(--theme-primary);
            --bs-success-rgb: var(--theme-primary-rgb);
            --bs-warning: var(--theme-primary);
            --bs-warning-rgb: var(--theme-primary-rgb);
            --bs-info: var(--theme-primary);
            --bs-info-rgb: var(--theme-primary-rgb);
            --bs-danger: var(--theme-secondary);
            --bs-danger-rgb: var(--theme-secondary-rgb);
            --bs-secondary: var(--theme-ink);
            --bs-secondary-rgb: var(--theme-ink-rgb);
            --bs-dark: var(--theme-ink);
            --bs-dark-rgb: var(--theme-ink-rgb);
        }
        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--admin-bg);
            background-image:
                radial-gradient(circle at 8% -2%, rgba(var(--theme-primary-rgb), 0.24), transparent 34%),
                radial-gradient(circle at 96% 0%, rgba(var(--theme-ink-rgb), 0.09), transparent 30%),
                linear-gradient(180deg, #fdfbf7 0%, #f7f3ec 100%);
            color: var(--admin-ink);
            min-height: 100vh;
        }
        h1, h2, h3, h4, h5 {
            font-family: 'Manrope', sans-serif;
            letter-spacing: -0.01em;
            font-weight: 800;
        }
        .soft-card {
            border: 1px solid var(--admin-line);
            border-radius: 14px;
            box-shadow: var(--admin-shadow);
            background: var(--admin-surface);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .table-shell {
            border: 1px solid var(--admin-line);
            border-radius: 14px;
            background: var(--admin-surface);
            box-shadow: var(--admin-shadow);
            overflow: hidden;
        }
        .table {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }
        .table thead th {
            border-bottom: 1px solid rgba(var(--theme-primary-rgb), 0.24);
            color: rgba(var(--theme-ink-rgb), 0.78);
            font-size: 0.72rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            font-weight: 700;
            white-space: nowrap;
            padding-top: 0.78rem;
            padding-bottom: 0.62rem;
        }
        .table tbody td {
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
            vertical-align: middle;
            border-color: rgba(var(--theme-primary-rgb), 0.2);
            font-size: 0.9rem;
        }
        .table tbody tr:hover {
            background: rgba(var(--theme-primary-rgb), 0.08);
        }
        .btn-ta {
            border-radius: 10px;
            border: 1px solid var(--admin-brand);
            background: var(--admin-brand);
            color: #fff;
            font-weight: 700;
            padding: 0.5rem 0.95rem;
            box-shadow: 0 6px 14px rgba(var(--theme-primary-rgb), 0.24);
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-ta:hover {
            border-color: var(--admin-brand-dark);
            background: var(--admin-brand-dark);
            color: #fff;
            box-shadow: 0 10px 18px rgba(var(--theme-secondary-rgb), 0.25);
        }
        .btn-ta-outline {
            border-radius: 10px;
            border: 1px solid rgba(var(--theme-primary-rgb), 0.42);
            background: #fff;
            color: var(--theme-ink);
            font-weight: 700;
            padding: 0.5rem 0.95rem;
        }
        .btn-ta-outline:hover {
            background: var(--theme-ink);
            color: #fff;
            border-color: var(--theme-ink);
        }
        .admin-action-col {
            min-width: 220px;
        }
        .admin-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.45rem;
            flex-wrap: nowrap;
        }
        .admin-action-group form {
            margin: 0;
        }
        .admin-action-group .btn {
            min-height: 35px;
            min-width: 84px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.34rem;
            padding: 0.4rem 0.78rem;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .btn-action-delete {
            border: 1px solid rgba(var(--theme-secondary-rgb), 0.48);
            color: var(--theme-secondary);
            background: rgba(var(--theme-secondary-rgb), 0.08);
        }
        .btn-action-delete:hover,
        .btn-action-delete:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        .btn-outline-danger {
            border-radius: 10px;
            font-weight: 700;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(var(--theme-primary-rgb), 0.3) !important;
            box-shadow: 0 8px 20px rgba(var(--theme-ink-rgb), 0.12);
        }
        .navbar-brand {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            letter-spacing: 0.01em;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 1px 2px rgba(17, 24, 39, 0.28)) contrast(1.08) saturate(1.05);
            display: block;
            transform: scale(1.35);
            transform-origin: center;
        }
        .brand-wordmark {
            font-size: 0.94rem;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .admin-brand-suffix {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            color: var(--theme-ink);
            border: 1px solid rgba(var(--theme-primary-rgb), 0.44);
            border-radius: 999px;
            padding: 0.14rem 0.45rem;
            background: rgba(var(--theme-primary-rgb), 0.16);
        }
        .nav-link {
            color: rgba(var(--theme-ink-rgb), 0.85);
            font-weight: 600;
            position: relative;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
        .nav-link.active {
            color: var(--theme-ink);
            font-weight: 700;
        }
        .nav-link.active::after {
            content: "";
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            bottom: 0.28rem;
            height: 2px;
            border-radius: 999px;
            background: var(--theme-primary);
        }
        .admin-cta-wrap {
            margin-left: 0.65rem;
            padding-left: 0.9rem;
            border-left: 1px solid rgba(var(--theme-ink-rgb), 0.2);
        }
        .admin-cta-group {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 0.45rem 0.8rem;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1;
            border: 1px solid transparent;
        }
        .admin-pill-user {
            border-color: rgba(var(--theme-primary-rgb), 0.34);
            background: rgba(var(--theme-primary-rgb), 0.12);
            color: var(--theme-ink);
        }
        .admin-pill-logout {
            border-color: var(--admin-brand);
            background: var(--admin-brand);
            color: #fff;
        }
        .admin-pill-logout:hover {
            border-color: var(--admin-brand-dark);
            background: var(--admin-brand-dark);
            color: #fff;
        }
        .form-control,
        .form-select {
            border-radius: 10px;
            border-color: rgba(var(--theme-primary-rgb), 0.42);
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(var(--theme-primary-rgb), 0.2);
            border-color: rgba(var(--theme-primary-rgb), 0.66);
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: rgba(var(--theme-ink-rgb), 0.82);
            margin-bottom: 0.32rem;
        }
        .badge {
            font-weight: 700;
            font-size: 0.72rem;
        }
        .flash-stack {
            display: grid;
            gap: 0.65rem;
            margin-bottom: 1rem;
        }
        .flash-card {
            border-radius: 12px;
            border: 1px solid rgba(var(--theme-primary-rgb), 0.34);
            background: #fff;
            box-shadow: 0 6px 16px rgba(var(--theme-ink-rgb), 0.08);
            padding: 0.72rem 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }
        .flash-card .flash-icon {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.76rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        .flash-card .flash-title {
            font-size: 0.68rem;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .flash-card .flash-body {
            flex: 1;
            font-size: 0.86rem;
        }
        .flash-card.success {
            border-color: rgba(var(--theme-primary-rgb), 0.4);
            background: rgba(var(--theme-primary-rgb), 0.12);
        }
        .flash-card.success .flash-icon {
            background: rgba(var(--theme-primary-rgb), 0.22);
            color: var(--theme-primary);
        }
        .flash-card.success .flash-title {
            color: var(--theme-primary);
        }
        .flash-card.error {
            border-color: rgba(var(--theme-secondary-rgb), 0.42);
            background: rgba(var(--theme-secondary-rgb), 0.12);
        }
        .flash-card.error .flash-icon {
            background: rgba(var(--theme-secondary-rgb), 0.2);
            color: var(--theme-secondary);
        }
        .flash-card.error .flash-title {
            color: var(--theme-secondary);
        }
        .flash-close {
            opacity: 0.5;
            margin-top: 0.1rem;
        }
        .flash-close:hover {
            opacity: 1;
        }
        .text-success,
        .text-primary,
        .text-info,
        .text-warning {
            color: var(--theme-primary) !important;
        }
        .text-danger {
            color: var(--theme-secondary) !important;
        }
        .text-secondary,
        .text-dark,
        .text-muted {
            color: rgba(var(--theme-ink-rgb), 0.8) !important;
        }
        .btn-outline-danger {
            border-color: var(--theme-secondary);
            color: var(--theme-secondary);
        }
        .btn-outline-danger:hover,
        .btn-outline-danger:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        .alert-success,
        .alert-primary,
        .alert-info,
        .alert-warning {
            border-color: rgba(var(--theme-primary-rgb), 0.42);
            background: rgba(var(--theme-primary-rgb), 0.14);
            color: var(--theme-ink);
        }
        .alert-danger {
            border-color: rgba(var(--theme-secondary-rgb), 0.42);
            background: rgba(var(--theme-secondary-rgb), 0.14);
            color: var(--theme-ink);
        }
        .text-bg-success,
        .text-bg-primary,
        .text-bg-info,
        .text-bg-warning {
            background-color: var(--theme-primary) !important;
            color: #fff !important;
        }
        .text-bg-danger {
            background-color: var(--theme-secondary) !important;
            color: #fff !important;
        }
        .text-bg-secondary,
        .text-bg-dark,
        .text-bg-light {
            background-color: var(--theme-ink) !important;
            color: #fff !important;
            border-color: transparent !important;
        }
        @media (max-width: 991.98px) {
            .admin-cta-wrap {
                margin-left: 0;
                padding-left: 0;
                border-left: 0;
                width: 100%;
            }
            .admin-action-col {
                min-width: 280px;
            }
            .admin-cta-group {
                width: 100%;
                margin-top: 0.45rem;
            }
            .admin-pill {
                flex: 1 1 calc(50% - 0.5rem);
                padding-top: 0.65rem;
                padding-bottom: 0.65rem;
            }
        }
        @media (max-width: 575.98px) {
            .brand-logo {
                width: 32px;
                height: 32px;
                transform: scale(1.22);
            }
            .brand-wordmark {
                font-size: 0.85rem;
            }
            .admin-brand-suffix {
                font-size: 0.68rem;
                padding: 0.14rem 0.42rem;
            }
        }
    </style>
    @stack('head')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container-xl py-2">
            <a class="navbar-brand text-dark" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="brand-logo">
                <span class="brand-wordmark">THE GRAND LION HOTEL</span>
                <span class="admin-brand-suffix">Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.sales-report') ? 'active fw-semibold' : '' }}" href="{{ route('admin.sales-report') }}">Sales Report</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.rooms.index') }}">Rooms</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.users.index') }}">Customers</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.staff.index') }}">Staff</a></li>
                    <li class="nav-item admin-cta-wrap">
                        <div class="admin-cta-group">
                            <span class="admin-pill admin-pill-user">{{ \Illuminate\Support\Str::limit(auth()->user()->name ?? '', 18) }}</span>
                            <form method="POST" action="{{ route('logout') }}" data-confirm="Are you sure you want to log out?">
                                @csrf
                                <button class="admin-pill admin-pill-logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-xl py-4">
        @if(session('status') || $errors->any())
            <div class="flash-stack">
                @if(session('status'))
                    <div class="flash-card success alert alert-dismissible fade show mb-0" role="alert">
                        <span class="flash-icon" aria-hidden="true">&#10003;</span>
                        <div class="flash-body">
                            <p class="flash-title mb-1">Success</p>
                            <p class="mb-0">{{ session('status') }}</p>
                        </div>
                        <button type="button" class="btn-close flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="flash-card error alert alert-dismissible fade show mb-0" role="alert">
                        <span class="flash-icon" aria-hidden="true">!</span>
                        <div class="flash-body">
                            <p class="flash-title mb-1">Action Needed</p>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        @endif
        @yield('content')
    </main>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (() => {
            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                const message = form.getAttribute('data-confirm');
                if (!message) {
                    return;
                }

                if (form.dataset.confirmed === '1') {
                    return;
                }

                if (!window.confirm(message)) {
                    event.preventDefault();
                    return;
                }

                form.dataset.confirmed = '1';
            });
        })();
    </script>
    @include('layouts.partials.history-refresh')
    @stack('scripts')
</body>
</html>
