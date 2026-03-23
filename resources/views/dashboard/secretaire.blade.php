@extends('layouts.app')

@section('title', 'Tableau de bord Secretaire')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ─── Scoped Premium — Secretaire Dashboard ─────────────────── */
    body { background-color: var(--background); }

    /* ── Header ──────────────────────────────────────────────────── */
    .sec-header {
        background: linear-gradient(135deg, var(--primary) 0%, #5e91de 100%);
        color: #fff;
        border-radius: var(--radius-medium);
        padding: var(--space-xl) var(--space-lg);
        margin-bottom: var(--space-lg);
        position: relative;
        overflow: hidden;
    }
    .sec-header::before {
        content: '';
        position: absolute;
        top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .sec-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-md);
        position: relative;
        z-index: 1;
    }
    .sec-header-left {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
    }
    .sec-avatar {
        width: 72px; height: 72px;
        border-radius: var(--radius-circle);
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        flex-shrink: 0;
    }
    .sec-header h1 {
        color: #fff;
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
    }
    .sec-header .header-sub {
        color: rgba(255,255,255,0.8);
        margin: 4px 0 0;
        font-size: 0.88rem;
    }
    .sec-header-actions {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }
    .sec-badge {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        backdrop-filter: blur(4px);
    }
    .sec-btn-refresh {
        width: 38px; height: 38px;
        border-radius: var(--radius-small);
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.1);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sec-btn-refresh:hover {
        background: rgba(255,255,255,0.25);
        transform: rotate(90deg);
    }
    .sec-quick-actions {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        border-radius: var(--radius-small);
        padding: 7px 14px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        backdrop-filter: blur(4px);
    }
    .sec-quick-actions:hover {
        background: rgba(255,255,255,0.3);
    }

    /* ── Alert Inscriptions ──────────────────────────────────────── */
    .sec-alert {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, rgba(245,158,11,0.06) 0%, rgba(245,158,11,0.02) 100%);
        border: 1px solid rgba(245,158,11,0.2);
        margin-bottom: var(--space-lg);
        animation: sec-pulse 3s ease-in-out infinite;
    }
    @keyframes sec-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
        50% { box-shadow: 0 0 0 4px rgba(245,158,11,0.08); }
    }
    .sec-alert-icon {
        width: 56px; height: 56px;
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(245,158,11,0.25);
    }
    .sec-alert-content { flex: 1; }
    .sec-alert-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #92400e;
        margin-bottom: 2px;
    }
    .sec-alert-text {
        font-size: 0.82rem;
        color: var(--text-secondary);
    }

    /* ── KPI Grid ────────────────────────────────────────────────── */
    .sec-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    .sec-kpi {
        border-radius: var(--radius-medium);
        padding: var(--space-lg) var(--space-md);
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
        cursor: default;
    }
    .sec-kpi:hover {
        transform: translateY(-4px);
    }
    .sec-kpi::after {
        content: '';
        position: absolute;
        top: -30%; right: -20%;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
        pointer-events: none;
    }
    .sec-kpi-icon {
        font-size: 1.6rem;
        margin-bottom: var(--space-sm);
        opacity: 0.9;
    }
    .sec-kpi-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.85;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .sec-kpi-value {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: var(--space-sm);
    }
    .sec-kpi-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.72rem;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        padding: 3px 10px;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.2);
        transition: all 0.2s ease;
        font-weight: 500;
    }
    .sec-kpi-link:hover {
        background: rgba(255,255,255,0.3);
        color: #fff;
    }

    /* KPI color variants */
    .sec-kpi--primary { background: linear-gradient(135deg, var(--primary), #3b7ddb); box-shadow: 0 4px 16px rgba(4,83,203,0.25); }
    .sec-kpi--primary:hover { box-shadow: 0 8px 28px rgba(4,83,203,0.35); }
    .sec-kpi--success { background: linear-gradient(135deg, var(--success), #34d399); box-shadow: 0 4px 16px rgba(16,185,129,0.25); }
    .sec-kpi--success:hover { box-shadow: 0 8px 28px rgba(16,185,129,0.35); }
    .sec-kpi--warning { background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 4px 16px rgba(245,158,11,0.25); }
    .sec-kpi--warning:hover { box-shadow: 0 8px 28px rgba(245,158,11,0.35); }
    .sec-kpi--cyan { background: linear-gradient(135deg, #0891b2, var(--accent-blue)); box-shadow: 0 4px 16px rgba(6,182,212,0.25); }
    .sec-kpi--cyan:hover { box-shadow: 0 8px 28px rgba(6,182,212,0.35); }
    .sec-kpi--secondary { background: linear-gradient(135deg, var(--secondary), #4a8fe8); box-shadow: 0 4px 16px rgba(27,100,212,0.25); }
    .sec-kpi--secondary:hover { box-shadow: 0 8px 28px rgba(27,100,212,0.35); }
    .sec-kpi--danger { background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 4px 16px rgba(239,68,68,0.25); }
    .sec-kpi--danger:hover { box-shadow: 0 8px 28px rgba(239,68,68,0.35); }
    .sec-kpi--neutral { background: linear-gradient(135deg, #4b5563, var(--neutral)); box-shadow: 0 4px 16px rgba(107,114,128,0.25); }
    .sec-kpi--neutral:hover { box-shadow: 0 8px 28px rgba(107,114,128,0.35); }

    /* ── Section card ────────────────────────────────────────────── */
    .sec-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .sec-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .sec-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-lg) var(--space-lg) var(--space-md);
    }
    .sec-card-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    .sec-card-title i {
        color: var(--primary);
        font-size: 1rem;
    }
    .sec-card-body {
        padding: 0 var(--space-lg) var(--space-lg);
    }

    /* ── Premium Table ───────────────────────────────────────────── */
    .sec-table {
        width: 100%;
        border-collapse: collapse;
    }
    .sec-table thead tr {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    .sec-table th {
        color: #fff;
        padding: 14px 16px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        border: none;
        white-space: nowrap;
    }
    .sec-table tbody tr {
        border-bottom: 1px solid rgba(0,0,0,0.04);
        transition: background 0.15s ease;
    }
    .sec-table tbody tr:last-child { border-bottom: none; }
    .sec-table tbody tr:hover {
        background: rgba(4,83,203,0.03);
    }
    .sec-table td {
        padding: 12px 16px;
        font-size: 0.84rem;
        color: var(--text-primary);
        vertical-align: middle;
    }
    .sec-student-avatar {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    .sec-student-avatar img {
        width: 38px; height: 38px;
        border-radius: 50%;
        object-fit: cover;
    }
    .sec-badge-status {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: var(--radius-small);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .sec-badge-status.pending {
        background: rgba(245,158,11,0.1);
        color: #92400e;
    }
    .sec-badge-status.outdated {
        background: rgba(107,114,128,0.1);
        color: var(--neutral);
    }
    .sec-btn-details {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 12px;
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary);
        background: rgba(4,83,203,0.06);
        border: 1px solid rgba(4,83,203,0.15);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .sec-btn-details:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    /* ── Message Feed ────────────────────────────────────────────── */
    .sec-msg-feed { display: flex; flex-direction: column; gap: 0; }
    .sec-msg-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.15s ease;
    }
    .sec-msg-item:last-child { border-bottom: none; }
    .sec-msg-item:hover { color: var(--primary); }
    .sec-msg-bar {
        width: 3px;
        min-height: 44px;
        border-radius: 2px;
        flex-shrink: 0;
        align-self: stretch;
        background: var(--primary);
        opacity: 0.4;
    }
    .sec-msg-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        flex-shrink: 0;
        background: rgba(4,83,203,0.1);
        color: var(--primary);
    }
    .sec-msg-content { flex: 1; min-width: 0; }
    .sec-msg-subject {
        font-size: 0.84rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }
    .sec-msg-preview {
        font-size: 0.78rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }
    .sec-msg-meta {
        font-size: 0.7rem;
        color: var(--text-muted);
        margin-top: 3px;
    }
    .sec-msg-time {
        font-size: 0.7rem;
        color: var(--text-muted);
        white-space: nowrap;
        flex-shrink: 0;
        align-self: flex-start;
        margin-top: 2px;
    }

    /* ── Messaging Form ──────────────────────────────────────────── */
    .sec-form-group {
        position: relative;
    }
    .sec-form-group label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
        display: block;
    }
    .sec-form-group .form-control,
    .sec-form-group .form-select {
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small);
        padding: 10px 14px;
        font-size: 0.88rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .sec-form-group .form-control:focus,
    .sec-form-group .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }
    .sec-btn-send {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: var(--radius-small);
        background: linear-gradient(135deg, var(--primary), #3b7ddb);
        color: #fff;
        font-size: 0.88rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(4,83,203,0.25);
    }
    .sec-btn-send:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(4,83,203,0.35);
    }

    /* ── Stagger animation ───────────────────────────────────────── */
    @keyframes sec-fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .sec-animate {
        animation: sec-fadeUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .sec-animate:nth-child(1) { animation-delay: 0s; }
    .sec-animate:nth-child(2) { animation-delay: 0.05s; }
    .sec-animate:nth-child(3) { animation-delay: 0.1s; }
    .sec-animate:nth-child(4) { animation-delay: 0.15s; }
    .sec-animate:nth-child(5) { animation-delay: 0.2s; }
    .sec-animate:nth-child(6) { animation-delay: 0.25s; }
    .sec-animate:nth-child(7) { animation-delay: 0.3s; }

    /* ── Responsive ──────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .sec-header-inner { flex-direction: column; text-align: center; }
        .sec-header-left { flex-direction: column; }
        .sec-header-actions { justify-content: center; }
        .sec-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .sec-kpi-value { font-size: 1.4rem; }
        .sec-alert { flex-direction: column; text-align: center; }
    }
    @media (max-width: 480px) {
        .sec-kpi-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

        {{-- ── Premium Header ──────────────────────────────────────── --}}
        <div class="sec-header sec-animate">
            <div class="sec-header-inner">
                <div class="sec-header-left">
                    <div class="sec-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h1>Tableau de bord Secretaire</h1>
                        <p class="header-sub">Bienvenue, <strong>{{ $user->name }}</strong> — gestion des operations</p>
                    </div>
                </div>
                <div class="sec-header-actions">
                    <span class="sec-badge">
                        <i class="fas fa-calendar me-1"></i>
                        {{ $anneeEnCours->name ?? 'Annee non definie' }}
                    </span>
                    <span class="sec-badge">
                        <i class="fas fa-clock me-1"></i>
                        {{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}
                    </span>
                    <button class="sec-btn-refresh" onclick="location.reload()" title="Actualiser">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="sec-quick-actions" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt me-1"></i> Actions rapides
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus me-2" style="color: var(--primary);"></i>Nouvelle inscription</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.etudiants-inscriptions.index') }}"><i class="fas fa-users me-2" style="color: var(--success);"></i>Liste etudiants</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.attendances.index') }}"><i class="fas fa-clipboard-check me-2" style="color: var(--accent-blue);"></i>Presences</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn me-2" style="color: #d97706;"></i>Publier annonce</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Alert inscriptions en attente ───────────────────────── --}}
        @if($pendingInscriptionsCount > 0)
            <div class="sec-alert sec-animate">
                <div class="sec-alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="sec-alert-content">
                    <div class="sec-alert-title">
                        {{ $pendingInscriptionsCount }} inscription(s) en attente de validation
                    </div>
                    <div class="sec-alert-text">
                        Ces dossiers requierent une verification pour finaliser l'admission.
                        @if(isset($pendingCurrentYearInscriptionsByStep))
                            @php
                                $prospect = $pendingCurrentYearInscriptionsByStep['prospect'] ?? 0;
                                $docs = $pendingCurrentYearInscriptionsByStep['documents_complets'] ?? 0;
                                $valid = $pendingCurrentYearInscriptionsByStep['en_validation'] ?? 0;
                            @endphp
                            @if($prospect + $docs + $valid > 0)
                                <span style="margin-left: 8px; font-weight: 600;">
                                    @if($prospect > 0) {{ $prospect }} prospect(s) @endif
                                    @if($docs > 0) &middot; {{ $docs }} docs complets @endif
                                    @if($valid > 0) &middot; {{ $valid }} en validation @endif
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="btn-acasi warning" style="flex-shrink: 0;">
                    <i class="fas fa-check-circle me-1"></i>Consulter
                </a>
            </div>
        @endif

        {{-- ── KPI Cards Grid ──────────────────────────────────────── --}}
        <div class="sec-kpi-grid">
            @if(isset($pendingInscriptionsCount))
                <div class="sec-kpi sec-kpi--warning sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="sec-kpi-label">Inscriptions en attente</div>
                    <div class="sec-kpi-value">{{ $pendingInscriptionsCount }}</div>
                    <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="sec-kpi-link">
                        Valider <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($totalStudents))
                <div class="sec-kpi sec-kpi--primary sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="sec-kpi-label">Etudiants inscrits</div>
                    <div class="sec-kpi-value">{{ $totalStudents }}</div>
                    <a href="{{ route('esbtp.etudiants.index') }}" class="sec-kpi-link">
                        Gerer <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($todayAttendances))
                <div class="sec-kpi sec-kpi--success sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="sec-kpi-label">Presences aujourd'hui</div>
                    <div class="sec-kpi-value">{{ $todayAttendances }}</div>
                    <a href="{{ route('esbtp.attendances.index') }}" class="sec-kpi-link">
                        Suivre <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($pendingJustifications))
                <div class="sec-kpi sec-kpi--warning sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="sec-kpi-label">Justifications en attente</div>
                    <div class="sec-kpi-value">{{ $pendingJustifications }}</div>
                    <a href="{{ route('esbtp.attendances.index') }}" class="sec-kpi-link">
                        Traiter <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($totalTimetables))
                <div class="sec-kpi sec-kpi--cyan sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="sec-kpi-label">Emplois du temps</div>
                    <div class="sec-kpi-value">{{ $totalTimetables }}</div>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="sec-kpi-link">
                        Gerer <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($todayClasses))
                <div class="sec-kpi sec-kpi--secondary sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-chalkboard"></i></div>
                    <div class="sec-kpi-label">Cours aujourd'hui</div>
                    <div class="sec-kpi-value">{{ $todayClasses }}</div>
                    <a href="{{ route('esbtp.timetables.today') }}" class="sec-kpi-link">
                        Voir <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif

            @if(isset($totalBulletins))
                <div class="sec-kpi sec-kpi--secondary sec-animate">
                    <div class="sec-kpi-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="sec-kpi-label">Bulletins generes</div>
                    <div class="sec-kpi-value">{{ $totalBulletins }}</div>
                    <a href="{{ route('esbtp.resultats.index') }}" class="sec-kpi-link">
                        Traiter <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- ── Two-column content ──────────────────────────────────── --}}
        <div class="row g-3 mb-4">
            {{-- Etudiants recemment inscrits --}}
            <div class="col-lg-7 col-12">
                <div class="sec-card">
                    <div class="sec-card-header">
                        <div class="sec-card-title">
                            <i class="fas fa-user-graduate"></i>
                            Etudiants recemment inscrits
                        </div>
                        <a href="{{ route('esbtp.etudiants-inscriptions.index') }}" class="sec-btn-details">
                            Voir tout <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="sec-card-body">
                        @if(isset($recentStudents) && $recentStudents->count() > 0)
                            <div class="table-responsive">
                                <table class="sec-table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i> Etudiant</th>
                                            <th><i class="fas fa-id-badge me-1"></i> Matricule</th>
                                            <th><i class="fas fa-school me-1"></i> Classe</th>
                                            <th><i class="fas fa-calendar me-1"></i> Date</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentStudents as $etudiant)
                                            @php
                                                $currentYearId = $anneeEnCours->id ?? null;
                                                $latestInscription = $etudiant->inscriptions
                                                    ->sortByDesc('created_at')
                                                    ->first();
                                                $currentInscription = $currentYearId
                                                    ? $etudiant->inscriptions
                                                        ->where('annee_universitaire_id', $currentYearId)
                                                        ->sortByDesc('created_at')
                                                        ->first()
                                                    : null;
                                                $displayInscription = $currentInscription ?: $latestInscription;
                                                $displayDate = $displayInscription?->date_inscription ?? $displayInscription?->created_at;
                                                $classeName = $displayInscription?->classe?->name ?? $displayInscription?->classe?->nom;

                                                $initials = strtoupper(
                                                    mb_substr($etudiant->nom ?? '', 0, 1) .
                                                    mb_substr($etudiant->prenoms ?? '', 0, 1)
                                                );
                                                $colors = ['#0453cb','#0891b2','#10b981','#1b64d4','#059669','#0e7490'];
                                                $bgColor = $colors[crc32($etudiant->id ?? '0') % count($colors)];
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        @if(!empty($etudiant->photo_url))
                                                            <div class="sec-student-avatar">
                                                                <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom }}">
                                                            </div>
                                                        @else
                                                            <div class="sec-student-avatar" style="background: {{ $bgColor }}15; color: {{ $bgColor }};">
                                                                {{ $initials }}
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div style="font-weight: 600; font-size: 0.84rem;">{{ $etudiant->nom }}</div>
                                                            <div style="font-size: 0.78rem; color: var(--text-secondary);">{{ $etudiant->prenoms }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-secondary);">{{ $etudiant->matricule }}</span>
                                                </td>
                                                <td>
                                                    @if($displayInscription)
                                                        <span style="font-weight: 500;">{{ $classeName ?? 'Non definie' }}</span>
                                                        @if($displayInscription->status === 'pending' || $displayInscription->status === 'en_attente')
                                                            <br><span class="sec-badge-status pending">En attente</span>
                                                        @endif
                                                        @if(!$currentInscription && $latestInscription)
                                                            <br><span class="sec-badge-status outdated">
                                                                Hors annee
                                                                @if(!empty($latestInscription->anneeUniversitaire?->name))
                                                                    ({{ $latestInscription->anneeUniversitaire->name }})
                                                                @endif
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="sec-badge-status outdated">Aucune</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-size: 0.82rem;">{{ $displayDate?->format('d/m/Y') ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="sec-btn-details">
                                                        <i class="fas fa-eye"></i> Details
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div style="text-align: center; padding: var(--space-xl) 0; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: var(--space-sm); opacity: 0.4;"></i>
                                <p style="margin: 0;">Aucun etudiant recent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Messages recents --}}
            <div class="col-lg-5 col-12">
                <div class="sec-card">
                    <div class="sec-card-header">
                        <div class="sec-card-title">
                            <i class="fas fa-envelope"></i>
                            Messages recents
                        </div>
                        @if(isset($recentMessages) && $recentMessages->count() > 0)
                            <a href="{{ route('messages.index') }}" class="sec-btn-details">
                                Tous <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                    <div class="sec-card-body">
                        @if(isset($recentMessages) && $recentMessages->count() > 0)
                            <div class="sec-msg-feed">
                                @foreach($recentMessages as $message)
                                    @php
                                        $senderName = $message->sender->name ?? 'Systeme';
                                        $senderInitials = collect(explode(' ', $senderName))
                                            ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                            ->take(2)
                                            ->join('');
                                    @endphp
                                    <a href="{{ route('messages.show', $message->id) }}" class="sec-msg-item">
                                        <div class="sec-msg-bar"></div>
                                        <div class="sec-msg-avatar">{{ $senderInitials }}</div>
                                        <div class="sec-msg-content">
                                            <div class="sec-msg-subject">{{ $message->subject }}</div>
                                            <div class="sec-msg-preview">{{ Str::limit($message->content, 80) }}</div>
                                            <div class="sec-msg-meta">De : {{ $senderName }}</div>
                                        </div>
                                        <div class="sec-msg-time">{{ $message->created_at->diffForHumans() }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: var(--space-xl) 0; color: var(--text-muted);">
                                <i class="fas fa-envelope-open" style="font-size: 2rem; margin-bottom: var(--space-sm); opacity: 0.4;"></i>
                                <p style="margin: 0;">Aucun message recent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Formulaire envoi message ────────────────────────────── --}}
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-title">
                    <i class="fas fa-paper-plane"></i>
                    Envoyer un message
                </div>
            </div>
            <div class="sec-card-body">
                <form action="{{ route('esbtp.annonces.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4 sec-form-group">
                            <label for="recipient_type">Destinataire</label>
                            <select class="form-select" id="recipient_type" name="recipient_type" required>
                                <option value="">Selectionner un destinataire</option>
                                <option value="all">Tous les utilisateurs</option>
                                <option value="etudiant">Tous les etudiants</option>
                                <option value="specific_user">Etudiant specifique</option>
                                <option value="specific_class">Classe specifique</option>
                            </select>
                        </div>

                        <div class="col-md-4 sec-form-group" id="specific_user_container" style="display: none;">
                            <label for="recipient_id">Selectionner un etudiant</label>
                            <select class="form-select" id="recipient_id" name="recipient_id">
                                <option value="">Choisir un etudiant</option>
                                @foreach(App\Models\ESBTPEtudiant::all() as $etudiant)
                                    <option value="{{ $etudiant->user_id }}">{{ $etudiant->nom }} {{ $etudiant->prenoms }} ({{ $etudiant->matricule }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 sec-form-group" id="specific_class_container" style="display: none;">
                            <label for="recipient_group">Selectionner une classe</label>
                            <select class="form-select" id="recipient_group" name="recipient_group">
                                <option value="">Choisir une classe</option>
                                @foreach(App\Models\ESBTPClasse::all() as $classe)
                                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8 sec-form-group">
                            <label for="subject">Sujet</label>
                            <input type="text" class="form-control" id="subject" name="subject" required placeholder="Objet du message...">
                        </div>

                        <div class="col-12 sec-form-group">
                            <label for="content">Message</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required placeholder="Redigez votre message ici..."></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="sec-btn-send">
                                <i class="fas fa-paper-plane"></i> Envoyer le message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recipientType = document.getElementById('recipient_type');
        const specificUserContainer = document.getElementById('specific_user_container');
        const specificClassContainer = document.getElementById('specific_class_container');

        recipientType.addEventListener('change', function() {
            specificUserContainer.style.display = this.value === 'specific_user' ? 'block' : 'none';
            specificClassContainer.style.display = this.value === 'specific_class' ? 'block' : 'none';
        });
    });
</script>
@endpush
@endsection
