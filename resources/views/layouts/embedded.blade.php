<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KLASSCI')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="{{ asset('css/nextadmin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/navbar-enhancements.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sidebar-fixes.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
    <link href="{{ asset('css/modal-z-index-fix.css') }}" rel="stylesheet">
    <link href="{{ asset('css/form-interaction-fix.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Inter', sans-serif;
            padding: 1.5rem;
        }

        .embedded-shell {
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="embedded-shell">
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>
</html>
