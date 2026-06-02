@extends('layouts.app')

@section('title', 'Dashboard Coordinateur - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ─── Scoped Premium — Coordinateur Dashboard ────────────────── */
    body { background-color: var(--background); }

    /* ── Header ──────────────────────────────────────────────────── */
    .cd-header {
        background: linear-gradient(135deg, var(--primary) 0%, #5e91de 100%);
        color: #fff;
        border-radius: var(--radius-medium);
        padding: var(--space-xl) var(--space-lg);
        margin-bottom: var(--space-lg);
        position: relative;
        overflow: hidden;
    }
    .cd-header::before {
        content: '';
        position: absolute;
        top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .cd-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-md);
        position: relative;
        z-index: 1;
    }
    .cd-header-left {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
    }
    .cd-avatar {
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
    .cd-header h1 {
        color: #fff;
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
    }
    .cd-header .header-sub {
        color: rgba(255,255,255,0.8);
        margin: 4px 0 0;
        font-size: 0.88rem;
    }
    .cd-header-actions {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }
    .cd-badge {
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
    .cd-btn-refresh {
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
    .cd-btn-refresh:hover {
        background: rgba(255,255,255,0.25);
        transform: rotate(90deg);
    }
    .cd-btn-refresh.cd-spinning i { animation: cd-spin 0.8s linear infinite; }
    @keyframes cd-spin { to { transform: rotate(360deg); } }
    .cd-quick-actions {
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
    .cd-quick-actions:hover { background: rgba(255,255,255,0.3); }

    /* ── Alert Bar ────────────────────────────────────────────────── */
    .cd-alert {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-lg);
        animation: cd-pulse 3s ease-in-out infinite;
    }
    .cd-alert--warning {
        background: linear-gradient(135deg, rgba(245,158,11,0.06) 0%, rgba(245,158,11,0.02) 100%);
        border: 1px solid rgba(245,158,11,0.2);
    }
    .cd-alert--danger {
        background: linear-gradient(135deg, rgba(239,68,68,0.06) 0%, rgba(239,68,68,0.02) 100%);
        border: 1px solid rgba(239,68,68,0.2);
    }
    @keyframes cd-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
        50% { box-shadow: 0 0 0 4px rgba(245,158,11,0.08); }
    }
    .cd-alert-icon {
        width: 50px; height: 50px;
        border-radius: var(--radius-medium);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .cd-alert-icon--warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .cd-alert-icon--danger { background: linear-gradient(135deg, #dc2626, #ef4444); }
    .cd-alert-content { flex: 1; }
    .cd-alert-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; }
    .cd-alert--warning .cd-alert-title { color: #92400e; }
    .cd-alert--danger .cd-alert-title { color: #991b1b; }
    .cd-alert-text { font-size: 0.82rem; color: var(--text-secondary); }

    /* ── KPI Grid ─────────────────────────────────────────────────── */
    .cd-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    .cd-kpi {
        border-radius: var(--radius-medium);
        padding: var(--space-lg) var(--space-md);
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
        cursor: default;
    }
    .cd-kpi:hover { transform: translateY(-4px); }
    .cd-kpi::after {
        content: '';
        position: absolute;
        top: -30%; right: -20%;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
        pointer-events: none;
    }
    .cd-kpi-icon { font-size: 1.6rem; margin-bottom: var(--space-sm); opacity: 0.9; }
    .cd-kpi-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.85;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .cd-kpi-value {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: var(--space-sm);
    }
    .cd-kpi-sub {
        font-size: 0.68rem;
        opacity: 0.75;
        margin-bottom: var(--space-sm);
    }
    .cd-kpi-link {
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
    .cd-kpi-link:hover { background: rgba(255,255,255,0.3); color: #fff; }

    /* KPI color variants */
    .cd-kpi--primary { background: linear-gradient(135deg, var(--primary), #3b7ddb); box-shadow: 0 4px 16px rgba(4,83,203,0.25); }
    .cd-kpi--primary:hover { box-shadow: 0 8px 28px rgba(4,83,203,0.35); }
    .cd-kpi--success { background: linear-gradient(135deg, var(--success), #34d399); box-shadow: 0 4px 16px rgba(16,185,129,0.25); }
    .cd-kpi--success:hover { box-shadow: 0 8px 28px rgba(16,185,129,0.35); }
    .cd-kpi--warning { background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 4px 16px rgba(245,158,11,0.25); }
    .cd-kpi--warning:hover { box-shadow: 0 8px 28px rgba(245,158,11,0.35); }
    .cd-kpi--cyan { background: linear-gradient(135deg, #0891b2, var(--accent-blue)); box-shadow: 0 4px 16px rgba(6,182,212,0.25); }
    .cd-kpi--cyan:hover { box-shadow: 0 8px 28px rgba(6,182,212,0.35); }
    .cd-kpi--secondary { background: linear-gradient(135deg, var(--secondary, #1b64d4), #4a8fe8); box-shadow: 0 4px 16px rgba(27,100,212,0.25); }
    .cd-kpi--secondary:hover { box-shadow: 0 8px 28px rgba(27,100,212,0.35); }
    .cd-kpi--danger { background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 4px 16px rgba(239,68,68,0.25); }
    .cd-kpi--danger:hover { box-shadow: 0 8px 28px rgba(239,68,68,0.35); }

    /* ── Card ──────────────────────────────────────────────────────── */
    .cd-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .cd-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .cd-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-lg) var(--space-lg) var(--space-md);
    }
    .cd-card-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    .cd-card-title i { color: var(--primary); font-size: 1rem; }
    .cd-card-body { padding: 0 var(--space-lg) var(--space-lg); }
    .cd-card-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: var(--radius-small);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .cd-card-badge--warning { background: rgba(245,158,11,0.1); color: #92400e; }
    .cd-card-badge--danger { background: rgba(239,68,68,0.1); color: #991b1b; }
    .cd-card-badge--success { background: rgba(16,185,129,0.1); color: #065f46; }

    /* ── Premium Table ─────────────────────────────────────────────── */
    .cd-table { width: 100%; border-collapse: collapse; }
    .cd-table thead tr { background: linear-gradient(135deg, var(--primary), #3b7ddb); }
    .cd-table th {
        color: #fff;
        padding: 12px 16px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        border: none;
        white-space: nowrap;
    }
    .cd-table tbody tr {
        border-bottom: 1px solid rgba(0,0,0,0.04);
        transition: background 0.15s ease;
        cursor: pointer;
    }
    .cd-table tbody tr:last-child { border-bottom: none; }
    .cd-table tbody tr:hover { background: rgba(4,83,203,0.04); }
    .cd-table td {
        padding: 10px 16px;
        font-size: 0.84rem;
        color: var(--text-primary);
        vertical-align: middle;
    }
    .cd-avatar-sm {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        flex-shrink: 0;
        background: rgba(4,83,203,0.1);
        color: var(--primary);
    }
    .cd-avatar-sm img {
        width: 36px; height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }
    .cd-status {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: var(--radius-small);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .cd-status--active { background: rgba(16,185,129,0.1); color: #065f46; }
    .cd-status--pending { background: rgba(245,158,11,0.1); color: #92400e; }
    .cd-status--danger { background: rgba(239,68,68,0.1); color: #991b1b; }
    .cd-status--info { background: rgba(4,83,203,0.1); color: var(--primary); }
    .cd-btn-action {
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
    .cd-btn-action:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    /* ── Message Feed ──────────────────────────────────────────────── */
    .cd-msg-feed { display: flex; flex-direction: column; gap: 0; }
    .cd-msg-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.15s ease;
    }
    .cd-msg-item:last-child { border-bottom: none; }
    .cd-msg-item:hover { color: var(--primary); }
    .cd-msg-bar {
        width: 3px;
        min-height: 44px;
        border-radius: 2px;
        flex-shrink: 0;
        align-self: stretch;
        background: var(--primary);
        opacity: 0.4;
    }
    .cd-msg-avatar {
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
    .cd-msg-content { flex: 1; min-width: 0; }
    .cd-msg-subject {
        font-size: 0.84rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }
    .cd-msg-preview {
        font-size: 0.78rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }
    .cd-msg-time {
        font-size: 0.7rem;
        color: var(--text-muted);
        white-space: nowrap;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* ── Presence card ─────────────────────────────────────────────── */
    .cd-presence-ring {
        width: 120px; height: 120px;
        border-radius: 50%;
        background: conic-gradient(var(--success) calc(var(--rate) * 1%), rgba(239,68,68,0.15) 0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
    }
    .cd-presence-inner {
        width: 90px; height: 90px;
        border-radius: 50%;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .cd-presence-value { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .cd-presence-label { font-size: 0.68rem; color: var(--text-secondary); margin-top: 2px; }
    .cd-presence-details { display: flex; justify-content: center; gap: var(--space-lg); }
    .cd-presence-stat { text-align: center; }
    .cd-presence-stat-value { font-size: 1.1rem; font-weight: 700; }
    .cd-presence-stat-label { font-size: 0.7rem; color: var(--text-secondary); }

    /* ── EDT Summary ───────────────────────────────────────────────── */
    .cd-edt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md); }
    .cd-edt-item {
        text-align: center;
        padding: var(--space-md);
        border-radius: var(--radius-small);
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.06);
    }
    .cd-edt-value { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .cd-edt-label { font-size: 0.72rem; color: var(--text-secondary); margin-top: 4px; }

    /* ── Actions Grid ──────────────────────────────────────────────── */
    .cd-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: var(--space-sm);
    }
    .cd-action-btn {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: 10px 16px;
        border-radius: var(--radius-small);
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--primary);
        background: rgba(4,83,203,0.04);
        border: 1px solid rgba(4,83,203,0.12);
        transition: all 0.2s ease;
    }
    .cd-action-btn:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        transform: translateY(-1px);
    }
    .cd-action-btn i { font-size: 1rem; }

    /* ── Empty state ───────────────────────────────────────────────── */
    .cd-empty {
        text-align: center;
        padding: var(--space-xl) var(--space-md);
        color: var(--text-muted);
    }
    .cd-empty i { font-size: 2rem; margin-bottom: var(--space-sm); opacity: 0.4; }
    .cd-empty p { font-size: 0.84rem; margin: 0; }

    /* ── Stagger animation ─────────────────────────────────────────── */
    @keyframes cd-fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cd-animate { animation: cd-fadeUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .cd-animate:nth-child(1) { animation-delay: 0s; }
    .cd-animate:nth-child(2) { animation-delay: 0.05s; }
    .cd-animate:nth-child(3) { animation-delay: 0.1s; }
    .cd-animate:nth-child(4) { animation-delay: 0.15s; }
    .cd-animate:nth-child(5) { animation-delay: 0.2s; }
    .cd-animate:nth-child(6) { animation-delay: 0.25s; }
    .cd-animate:nth-child(7) { animation-delay: 0.3s; }

    /* ── Responsive ────────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .cd-header-inner { flex-direction: column; text-align: center; }
        .cd-header-left { flex-direction: column; }
        .cd-header-actions { justify-content: center; }
        .cd-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .cd-kpi-value { font-size: 1.4rem; }
        .cd-alert { flex-direction: column; text-align: center; }
        .cd-edt-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .cd-kpi-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

        {{-- ── Premium Header ──────────────────────────────────────── --}}
        <div class="cd-header cd-animate">
            <div class="cd-header-inner">
                <div class="cd-header-left">
                    <div class="cd-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h1>Dashboard Coordinateur</h1>
                        <p class="header-sub">Bienvenue, <strong>{{ $user->name }}</strong> — supervision academique et pedagogique</p>
                    </div>
                </div>
                <div class="cd-header-actions">
                    <span class="cd-badge">
                        <i class="fas fa-calendar me-1"></i>
                        {{ $anneeEnCours->name ?? 'Annee non definie' }}
                    </span>
                    <span class="cd-badge">
                        <i class="fas fa-clock me-1"></i>
                        {{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}
                    </span>
                    <button class="cd-btn-refresh" id="cd-refresh-btn" onclick="cdRefreshData()" title="Actualiser les donnees">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="cd-quick-actions" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt me-1"></i> Actions rapides
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('esbtp.planning-general.coordinateur') }}"><i class="fas fa-calendar-alt me-2" style="color: var(--primary);"></i>Planning General</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.emploi-temps.index') }}"><i class="fas fa-table me-2" style="color: var(--accent-blue);"></i>Emplois du temps</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.index') }}"><i class="fas fa-clipboard-list me-2" style="color: var(--success);"></i>Evaluations</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn me-2" style="color: #d97706;"></i>Publier annonce</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Alert inscriptions en attente ───────────────────────── --}}
        @if(($pendingInscriptionsCount ?? 0) > 0)
            <div class="cd-alert cd-alert--warning cd-animate">
                <div class="cd-alert-icon cd-alert-icon--warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="cd-alert-content">
                    <div class="cd-alert-title">{{ $pendingInscriptionsCount }} inscription(s) en attente de validation</div>
                    <div class="cd-alert-text">Ces dossiers requierent une verification pour finaliser l'admission.</div>
                </div>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="btn-acasi warning" style="flex-shrink: 0;">
                    <i class="fas fa-check-circle me-1"></i>Consulter
                </a>
            </div>
        @endif

        {{-- ── Alert evaluations sans notes ────────────────────────── --}}
        @if(($evaluationsSansNotesCount ?? 0) > 0)
            <div class="cd-alert cd-alert--danger cd-animate">
                <div class="cd-alert-icon cd-alert-icon--danger">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="cd-alert-content">
                    <div class="cd-alert-title">{{ $evaluationsSansNotesCount }} evaluation(s) passee(s) sans notes saisies</div>
                    <div class="cd-alert-text">Des evaluations deja passees n'ont toujours aucune note enregistree.</div>
                </div>
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi primary" style="flex-shrink: 0;">
                    <i class="fas fa-edit me-1"></i>Saisir
                </a>
            </div>
        @endif

        {{-- ── KPI Cards Grid ──────────────────────────────────────── --}}
        <div class="cd-kpi-grid">
            <div class="cd-kpi cd-kpi--warning cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-user-clock"></i></div>
                <div class="cd-kpi-label">Inscriptions en attente</div>
                <div class="cd-kpi-value" data-kpi="pendingInscriptionsCount">{{ $pendingInscriptionsCount ?? 0 }}</div>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="cd-kpi-link">
                    Valider <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="cd-kpi cd-kpi--primary cd-animate"
                 title="Étudiants distincts avec inscription active validée sur l'année {{ $anneeLabel ?? 'courante' }}">
                <div class="cd-kpi-icon"><i class="fas fa-user-check"></i></div>
                <div class="cd-kpi-label">Inscrits {{ $anneeLabel ?? 'année courante' }}</div>
                <div class="cd-kpi-value" data-kpi="totalStudents">{{ $totalStudents ?? 0 }}</div>
                <a href="{{ route('esbtp.etudiants.index') }}" class="cd-kpi-link">
                    Gerer <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            @if(isset($totalStudentsBase))
            <div class="cd-kpi cd-kpi--cyan cd-animate"
                 title="Toutes les fiches étudiants dans la base (toutes années / statuts confondus, y compris diplômés et non-réinscrits)">
                <div class="cd-kpi-icon"><i class="fas fa-users"></i></div>
                <div class="cd-kpi-label">Étudiants en base</div>
                <div class="cd-kpi-value" data-kpi="totalStudentsBase">{{ $totalStudentsBase ?? 0 }}</div>
                <a href="{{ route('esbtp.etudiants.index') }}" class="cd-kpi-link">
                    Consulter <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            @endif

            <div class="cd-kpi cd-kpi--success cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-users"></i></div>
                <div class="cd-kpi-label">Classes actives</div>
                <div class="cd-kpi-value" data-kpi="totalClasses">{{ $totalClasses ?? 0 }}</div>
                <a href="{{ route('esbtp.classes.index') }}" class="cd-kpi-link">
                    Voir <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="cd-kpi cd-kpi--cyan cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="cd-kpi-label">Enseignants</div>
                <div class="cd-kpi-value" data-kpi="totalTeachers">{{ $totalTeachers ?? 0 }}</div>
                <a href="{{ route('esbtp.enseignants.index') }}" class="cd-kpi-link">
                    Gerer <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="cd-kpi cd-kpi--secondary cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="cd-kpi-label">Evaluations</div>
                <div class="cd-kpi-value" data-kpi="totalExamens">{{ $totalExamens ?? 0 }}</div>
                @if(($evaluationsSansNotesCount ?? 0) > 0)
                    <div class="cd-kpi-sub"><i class="fas fa-exclamation-triangle"></i> {{ $evaluationsSansNotesCount }} sans notes</div>
                @endif
                <a href="{{ route('esbtp.evaluations.index') }}" class="cd-kpi-link">
                    Voir <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="cd-kpi cd-kpi--primary cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="cd-kpi-label">Emplois du temps</div>
                <div class="cd-kpi-value" data-kpi="totalEmploiTemps">{{ $totalEmploiTemps ?? 0 }}</div>
                @if(($expiredEmploiTemps ?? 0) > 0)
                    <div class="cd-kpi-sub"><i class="fas fa-clock"></i> {{ $expiredEmploiTemps }} expires</div>
                @endif
                <a href="{{ route('esbtp.emploi-temps.index') }}" class="cd-kpi-link">
                    Gerer <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            @php $rate = $attendanceStats['attendance_rate'] ?? 0; @endphp
            <div class="cd-kpi {{ $rate >= 70 ? 'cd-kpi--success' : ($rate >= 50 ? 'cd-kpi--warning' : 'cd-kpi--danger') }} cd-animate">
                <div class="cd-kpi-icon"><i class="fas fa-user-check"></i></div>
                <div class="cd-kpi-label">Taux de presence</div>
                <div class="cd-kpi-value" data-kpi="attendanceRate">{{ $rate }}%</div>
                <a href="{{ route('esbtp.attendances.index') }}" class="cd-kpi-link">
                    Suivre <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {{-- ── Emploi du temps + Presence row ──────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-7 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-calendar-alt"></i> Emplois du temps — Vue d'ensemble</div>
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="cd-btn-action">Tout voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="cd-card-body">
                        <div class="cd-edt-grid">
                            <div class="cd-edt-item">
                                <div class="cd-edt-value" style="color: var(--success);">{{ $activeEmploiTemps ?? 0 }}</div>
                                <div class="cd-edt-label">Actifs (semaine en cours)</div>
                            </div>
                            <div class="cd-edt-item">
                                <div class="cd-edt-value" style="color: #d97706;">{{ $expiredEmploiTemps ?? 0 }}</div>
                                <div class="cd-edt-label">Expires</div>
                            </div>
                            <div class="cd-edt-item">
                                <div class="cd-edt-value" style="color: #dc2626;">{{ $classesWithoutTimetable ?? 0 }}</div>
                                <div class="cd-edt-label">Classes sans EDT</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-chart-pie"></i> Presence aujourd'hui</div>
                    </div>
                    <div class="cd-card-body">
                        <div class="cd-presence-ring" style="--rate: {{ $rate }};">
                            <div class="cd-presence-inner">
                                <div class="cd-presence-value">{{ $rate }}%</div>
                                <div class="cd-presence-label">presence</div>
                            </div>
                        </div>
                        <div class="cd-presence-details">
                            <div class="cd-presence-stat">
                                <div class="cd-presence-stat-value" style="color: var(--success);">{{ $attendanceStats['total_present'] ?? 0 }}</div>
                                <div class="cd-presence-stat-label">Presents</div>
                            </div>
                            <div class="cd-presence-stat">
                                <div class="cd-presence-stat-value" style="color: #dc2626;">{{ $attendanceStats['total_absent'] ?? 0 }}</div>
                                <div class="cd-presence-stat-label">Absents</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Inscriptions + Evaluations tables ───────────────────── --}}
        <div class="row g-3 mb-4">
            {{-- Inscriptions recentes --}}
            <div class="col-lg-6 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-user-plus"></i> Inscriptions recentes</div>
                        @if(($pendingInscriptionsCount ?? 0) > 0)
                            <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="cd-card-badge cd-card-badge--warning">
                                {{ $pendingInscriptionsCount }} en attente
                            </a>
                        @endif
                    </div>
                    <div class="cd-card-body">
                        @if(isset($recentInscriptions) && $recentInscriptions->count() > 0)
                            <div class="table-responsive">
                                <table class="cd-table">
                                    <thead><tr><th>Etudiant</th><th>Filiere</th><th>Date</th><th>Statut</th></tr></thead>
                                    <tbody>
                                        @foreach($recentInscriptions as $inscription)
                                            <tr onclick="window.location.href='{{ $inscription->etudiant ? route('esbtp.etudiants.show', $inscription->etudiant->id) : '#' }}'">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="cd-avatar-sm">
                                                            @if($inscription->etudiant && $inscription->etudiant->photo_url)
                                                                <img src="{{ $inscription->etudiant->photo_url }}" alt="">
                                                            @else
                                                                {{ $inscription->etudiant ? mb_substr($inscription->etudiant->prenoms ?? '', 0, 1) . mb_substr($inscription->etudiant->nom ?? '', 0, 1) : 'NN' }}
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium" style="color: var(--primary); font-size: 0.84rem;">
                                                                {{ $inscription->etudiant->nom_complet ?? $inscription->etudiant->nom ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $inscription->classe->filiere->name ?? $inscription->classe->filiere->nom ?? '-' }}</td>
                                                <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if(in_array($inscription->status, ['en_attente', 'pending']))
                                                        <span class="cd-status cd-status--pending">En attente</span>
                                                    @else
                                                        <span class="cd-status cd-status--active">{{ ucfirst($inscription->status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cd-empty">
                                <i class="fas fa-user-plus"></i>
                                <p>Aucune inscription recente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Evaluations recentes --}}
            <div class="col-lg-6 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-clipboard-list"></i> Evaluations recentes</div>
                        @if(($evaluationsSansNotesCount ?? 0) > 0)
                            <span class="cd-card-badge cd-card-badge--danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ $evaluationsSansNotesCount }} sans notes
                            </span>
                        @endif
                    </div>
                    <div class="cd-card-body">
                        @if(isset($recentExamens) && $recentExamens->count() > 0)
                            <div class="table-responsive">
                                <table class="cd-table">
                                    <thead><tr><th>Matiere</th><th>Classe</th><th>Type</th><th>Date</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        @foreach($recentExamens as $examen)
                                            <tr onclick="window.location.href='{{ route('esbtp.notes.saisie-rapide', $examen->id) }}'">
                                                <td class="fw-medium">{{ $examen->matiere->name ?? $examen->matiere->nom ?? '-' }}</td>
                                                <td>{{ $examen->classe->name ?? $examen->classe->nom ?? '-' }}</td>
                                                <td><span class="cd-status cd-status--info">{{ ucfirst($examen->type ?? 'Examen') }}</span></td>
                                                <td>{{ \Carbon\Carbon::parse($examen->date_evaluation)->format('d/m/Y') }}</td>
                                                <td>
                                                    @if(($examen->notes_count ?? 0) > 0)
                                                        <span class="cd-status cd-status--active">{{ $examen->notes_count }} saisies</span>
                                                    @elseif(\Carbon\Carbon::parse($examen->date_evaluation)->lt(today()))
                                                        <span class="cd-status cd-status--danger">Sans notes</span>
                                                    @else
                                                        <span class="cd-status cd-status--pending">A venir</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cd-empty">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Aucune evaluation recente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Messages + Actions rapides ──────────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-6 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-comments"></i> Messages recents</div>
                    </div>
                    <div class="cd-card-body">
                        @if(isset($recentMessages) && $recentMessages->count() > 0)
                            <div class="cd-msg-feed">
                                @foreach($recentMessages as $message)
                                    <div class="cd-msg-item">
                                        <div class="cd-msg-bar"></div>
                                        <div class="cd-msg-avatar">
                                            {{ mb_substr($message->sender->name ?? 'U', 0, 2) }}
                                        </div>
                                        <div class="cd-msg-content">
                                            <div class="cd-msg-subject">{{ $message->subject ?? $message->sender->name ?? 'Message' }}</div>
                                            <div class="cd-msg-preview">{{ Str::limit($message->content ?? '', 60) }}</div>
                                        </div>
                                        <div class="cd-msg-time">{{ $message->created_at->diffForHumans() }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="cd-empty">
                                <i class="fas fa-comments"></i>
                                <p>Aucun message recent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-12">
                <div class="cd-card cd-animate">
                    <div class="cd-card-header">
                        <div class="cd-card-title"><i class="fas fa-bolt"></i> Actions rapides</div>
                    </div>
                    <div class="cd-card-body">
                        <div class="cd-actions-grid">
                            <a href="{{ route('esbtp.planning-general.coordinateur') }}" class="cd-action-btn"><i class="fas fa-calendar-alt"></i> Planning</a>
                            <a href="{{ route('esbtp.emploi-temps.index') }}" class="cd-action-btn"><i class="fas fa-table"></i> Emplois du temps</a>
                            <a href="{{ route('esbtp.evaluations.index') }}" class="cd-action-btn"><i class="fas fa-clipboard-list"></i> Evaluations</a>
                            <a href="{{ route('esbtp.notes.index') }}" class="cd-action-btn"><i class="fas fa-edit"></i> Notes</a>
                            <a href="{{ route('esbtp.etudiants.index') }}" class="cd-action-btn"><i class="fas fa-user-graduate"></i> Etudiants</a>
                            <a href="{{ route('esbtp.enseignants.index') }}" class="cd-action-btn"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a>
                            <a href="{{ route('esbtp.annonces.index') }}" class="cd-action-btn"><i class="fas fa-bullhorn"></i> Annonces</a>
                            <a href="{{ route('esbtp.attendances.index') }}" class="cd-action-btn"><i class="fas fa-user-check"></i> Presences</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function cdRefreshData() {
    var btn = document.getElementById('cd-refresh-btn');
    btn.classList.add('cd-spinning');

    fetch('{{ route("coordinateur.dashboard-data") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Update KPI values
        var kpiMap = {
            'pendingInscriptionsCount': data.pendingInscriptionsCount,
            'totalStudents': data.totalStudents,
            'totalStudentsBase': data.totalStudentsBase,
            'totalClasses': data.totalClasses,
            'totalTeachers': data.totalTeachers,
            'totalExamens': data.totalExamens,
            'totalEmploiTemps': data.totalEmploiTemps,
            'attendanceRate': data.attendanceStats.attendance_rate + '%'
        };
        for (var key in kpiMap) {
            var el = document.querySelector('[data-kpi="' + key + '"]');
            if (el) el.textContent = kpiMap[key];
        }
        btn.classList.remove('cd-spinning');
    })
    .catch(function() {
        btn.classList.remove('cd-spinning');
    });
}
</script>
@endpush
