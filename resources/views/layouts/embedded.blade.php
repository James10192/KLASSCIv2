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

        /* ─── Student Edit Form (se-*) ─── */
        .se-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .se-section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f0f0;
            background: #fafbfc;
        }

        .se-section-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(4, 83, 203, 0.08);
            color: var(--primary, #0453cb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .se-section-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--gray-800, #1e293b);
        }

        .se-section-desc {
            font-size: 0.78rem;
            color: var(--gray-500, #64748b);
            margin-top: 1px;
        }

        .se-section-body {
            padding: 1.25rem;
        }

        /* Form controls inside embedded */
        .se-section-body .form-label {
            font-size: 0.82rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.3rem;
        }

        .se-section-body .form-control,
        .se-section-body .form-select {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 0.88rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .se-section-body .form-control:focus,
        .se-section-body .form-select:focus {
            border-color: var(--primary, #0453cb);
            box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
        }

        .se-section-body .form-text {
            font-size: 0.72rem;
        }

        .se-section-body .input-group .btn {
            border-radius: 0 8px 8px 0;
            font-size: 0.82rem;
        }

        /* Submit button */
        .se-submit-wrap {
            padding: 1rem 0 0;
        }

        .se-submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            background: var(--primary, #0453cb);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .se-submit-btn:hover {
            background: var(--primary-dark, #0340a0);
        }

        /* Parent search overlay (replaces Bootstrap modal to avoid iframe z-index flash) */
        .parent-search-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-height: 100%;
            z-index: 9999;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .parent-search-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 900px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .parent-search-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: var(--primary, #0453cb);
            color: #fff;
        }

        .parent-search-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .parent-search-close:hover {
            color: #fff;
            background: rgba(255,255,255,0.15);
        }

        .parent-search-body {
            padding: 1.25rem;
            overflow-y: auto;
            flex: 1;
        }

        .parent-search-body .table tbody tr {
            transition: none !important;
        }

        /* Kill ALL hover effects inside the parent search panel */
        .parent-search-body .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-striped-bg: transparent;
        }

        .parent-search-body .table > tbody > tr:hover > * {
            --bs-table-hover-bg: transparent;
            --bs-table-accent-bg: transparent;
            background-color: transparent !important;
        }

        /* Lock body scroll when panel is open */
        body.parent-panel-open {
            overflow: hidden;
        }

        /* Badge overrides inside embedded */
        .se-section .badge {
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 5px;
            padding: 3px 8px;
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
