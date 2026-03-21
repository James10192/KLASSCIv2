@extends('layouts.app')

@section('title', 'Codes d\'Émargement')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── Active Code Card ─────────────────────────────────── */
    .acode-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .acode-card-header {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        padding: 1.25rem 1.5rem;
        display: flex; align-items: center; justify-content: space-between;
    }

    .acode-card-header-left {
        display: flex; align-items: center; gap: 0.75rem;
    }

    .acode-card-header-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }

    .acode-card-title   { color: #fff; font-weight: 700; font-size: 0.95rem; margin: 0; }
    .acode-card-subtitle{ color: rgba(255,255,255,0.8); font-size: 0.8rem; margin: 0.1rem 0 0; }

    .acode-card-body { padding: 1.5rem; }

    /* ── Code Display ─────────────────────────────────────── */
    .acode-display-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 2rem;
        align-items: start;
    }

    .acode-main {
        display: flex; flex-direction: column; align-items: center; gap: 1rem;
    }

    .acode-value {
        font-family: 'Courier New', monospace;
        font-size: 3.2rem;
        font-weight: 900;
        color: #0453cb;
        text-align: center;
        padding: 1.5rem 2rem;
        border: 2.5px dashed rgba(4,83,203,0.35);
        border-radius: 16px;
        background: rgba(4,83,203,0.04);
        letter-spacing: 6px;
        min-width: 220px;
        transition: all 0.2s;
        cursor: pointer;
        user-select: all;
        position: relative;
    }

    .acode-value:hover {
        background: rgba(4,83,203,0.08);
        border-color: rgba(4,83,203,0.5);
        transform: scale(1.02);
    }

    .acode-value.copied {
        background: rgba(16,185,129,0.06) !important;
        border-color: rgba(16,185,129,0.4) !important;
        color: #065f46 !important;
    }

    .acode-active-badge {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.35rem 0.85rem;
        background: #d1fae5; color: #065f46;
        border-radius: 20px; font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    .acode-active-badge .pulse {
        width: 7px; height: 7px; border-radius: 50%;
        background: #10b981;
        animation: pulse-dot 1.5s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.4; transform: scale(0.75); }
    }

    .acode-countdown {
        font-family: 'Courier New', monospace;
        font-size: 0.82rem; font-weight: 700;
        color: #0453cb;
        display: flex; align-items: center; gap: 0.4rem;
    }

    .acode-countdown.warning { color: #d97706; }
    .acode-countdown.danger  { color: #dc2626; }

    /* ── Info Cards Grid ──────────────────────────────────── */
    .acode-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .acode-info-card {
        display: flex; gap: 0.75rem; align-items: flex-start;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: var(--radius-medium);
        transition: box-shadow 0.2s;
    }

    .acode-info-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

    .acode-info-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; flex-shrink: 0;
    }

    .acode-info-icon.blue   { background: rgba(4,83,203,0.1);  color: #0453cb; }
    .acode-info-icon.green  { background: rgba(16,185,129,0.1); color: #10b981; }
    .acode-info-icon.orange { background: rgba(234,88,12,0.1);  color: #ea580c; }
    .acode-info-icon.slate  { background: rgba(100,116,139,0.1); color: #64748b; }

    .acode-info-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-secondary); margin-bottom: 0.2rem; }
    .acode-info-value { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); }
    .acode-info-extra { font-size: 0.775rem; color: var(--text-secondary); margin-top: 0.15rem; line-height: 1.4; }

    /* ── KPI Strip ────────────────────────────────────────── */
    .acode-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        margin-top: 1.25rem;
    }

    .acode-kpi {
        text-align: center;
        padding: 0.85rem 0.5rem;
        background: #f8fafc;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: var(--radius-medium);
    }

    .acode-kpi-val  { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); line-height: 1; margin-bottom: 0.3rem; }
    .acode-kpi-lbl  { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: var(--text-secondary); }
    .acode-kpi.success .acode-kpi-val { color: #10b981; }
    .acode-kpi.danger  .acode-kpi-val { color: #dc2626; }
    .acode-kpi.info    .acode-kpi-val { color: #0453cb; }

    /* ── Action Buttons Strip ─────────────────────────────── */
    .acode-actions {
        display: flex; gap: 0.75rem; flex-wrap: wrap;
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(0,0,0,0.05);
    }

    /* ── Empty State ──────────────────────────────────────── */
    .acode-empty {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--text-secondary);
    }

    .acode-empty-icon {
        width: 80px; height: 80px; border-radius: 20px;
        background: rgba(4,83,203,0.06);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #0453cb; opacity: 0.6;
        margin: 0 auto 1.25rem;
    }

    .acode-empty h3 { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem; }
    .acode-empty p  { font-size: 0.85rem; margin-bottom: 1.5rem; }

    /* ── Table Card ───────────────────────────────────────── */
    .acode-table-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .acode-table-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: #f8fafc;
        display: flex; align-items: center; justify-content: space-between;
    }

    .acode-table-title {
        font-weight: 700; font-size: 0.95rem; color: var(--text-primary);
        display: flex; align-items: center; gap: 0.5rem;
    }

    .acode-table-title i { color: #0453cb; }

    /* ── Table ────────────────────────────────────────────── */
    .acode-table { width: 100%; border-collapse: collapse; }

    .acode-table thead th {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        color: rgba(255,255,255,0.92); font-weight: 600;
        font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 0.85rem 1.25rem;
        border: none; white-space: nowrap;
    }

    .acode-table tbody td {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        vertical-align: middle; font-size: 0.875rem;
    }

    .acode-table tbody tr:last-child td { border-bottom: none; }
    .acode-table tbody tr:hover { background: rgba(4,83,203,0.02); }
    .acode-table tbody tr.row-active { background: rgba(16,185,129,0.03); border-left: 3px solid #10b981; }

    /* Code badge in table */
    .acode-code-pill {
        display: inline-flex; align-items: center; gap: 0.5rem;
        font-family: 'Courier New', monospace;
        font-weight: 800; font-size: 0.95rem;
        letter-spacing: 2px; color: #0453cb;
        background: rgba(4,83,203,0.08);
        padding: 0.3rem 0.65rem; border-radius: 7px;
        border: 1px solid rgba(4,83,203,0.14);
        cursor: pointer; transition: all 0.2s;
    }

    .acode-code-pill:hover { background: rgba(4,83,203,0.14); border-color: rgba(4,83,203,0.28); }
    .acode-code-pill.copied { background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: #065f46; }

    /* Seance cell */
    .seance-name   { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
    .seance-meta   { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.15rem; }
    .seance-teacher{ font-size: 0.75rem; color: #0453cb; margin-top: 0.1rem; }

    /* Date cell */
    .date-main { font-weight: 600; font-size: 0.875rem; }
    .date-sub  { font-size: 0.73rem; color: var(--text-secondary); }

    /* User cell */
    .user-cell { display: flex; align-items: center; gap: 0.5rem; }
    .user-avatar {
        width: 30px; height: 30px; border-radius: 8px;
        background: rgba(4,83,203,0.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem; font-weight: 700; color: #0453cb; flex-shrink: 0;
    }

    /* Stats cell */
    .stats-val  { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
    .stats-rate { font-size: 0.75rem; color: var(--text-secondary); }

    /* Status pills */
    .acode-status {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.28rem 0.65rem; border-radius: 20px;
        font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    .acode-status.s-active  { background: #d1fae5; color: #065f46; }
    .acode-status.s-expired { background: #f1f5f9; color: var(--text-secondary); }
    .acode-status.s-invalid { background: #fee2e2; color: #991b1b; }

    /* Action icon button */
    .acode-btn-icon {
        width: 30px; height: 30px; border-radius: 7px;
        border: none; cursor: pointer; transition: all 0.2s;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.75rem;
        background: rgba(220,38,38,0.08); color: #dc2626;
    }

    .acode-btn-icon:hover { background: #dc2626; color: #fff; }

    /* ── Modal ────────────────────────────────────────────── */
    .acode-modal .modal-header {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        border-bottom: none; padding: 1.1rem 1.5rem;
    }

    .acode-modal .modal-title {
        color: #fff; font-weight: 700; font-size: 0.95rem;
        display: flex; align-items: center; gap: 0.5rem;
    }

    .acode-modal .modal-header .btn-close { filter: brightness(0) invert(1); opacity: 0.8; }

    .acode-modal .form-label {
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--text-secondary); margin-bottom: 0.3rem;
    }

    .acode-modal .form-control,
    .acode-modal .form-select {
        border: 1.5px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small); font-size: 0.875rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .acode-modal .form-control:focus,
    .acode-modal .form-select:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }

    /* ── Responsive ───────────────────────────────────────── */
    @media (max-width: 768px) {
        .acode-display-grid { grid-template-columns: 1fr; }
        .acode-main { margin-bottom: 0.5rem; }
        .acode-value { font-size: 2.2rem; letter-spacing: 4px; min-width: unset; width: 100%; }
        .acode-info-grid { grid-template-columns: 1fr; }
        .acode-kpi-strip { grid-template-columns: repeat(2, 1fr); }
        .acode-actions { flex-direction: column; }
        .acode-actions .btn-acasi { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── Header ──────────────────────────────────────────── --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-qrcode me-3"></i>Codes d'Émargement</h1>
                <p class="header-subtitle">Interface coordinateur — génération et gestion des codes quotidiens</p>
            </div>
            <div class="header-actions">
                <span style="background:rgba(255,255,255,0.15);color:#fff;padding:0.4rem 0.9rem;border-radius:var(--radius-medium);font-size:0.85rem;display:flex;align-items:center;gap:0.4rem;">
                    <i class="fas fa-calendar"></i>{{ now()->format('d/m/Y') }}
                </span>
            </div>
        </div>

        {{-- ── Alertes ──────────────────────────────────────────── --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ── Code Actif ───────────────────────────────────────── --}}
        <div class="acode-card">
            <div class="acode-card-header">
                <div class="acode-card-header-left">
                    <div class="acode-card-header-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <p class="acode-card-title">Code d'Émargement Actuel</p>
                        <p class="acode-card-subtitle">Code en cours de validité pour aujourd'hui</p>
                    </div>
                </div>
                @if($activeCode)
                <span style="background:rgba(255,255,255,0.2);color:#fff;padding:0.35rem 0.8rem;border-radius:20px;font-size:0.75rem;font-weight:700;">
                    <i class="fas fa-circle me-1" style="font-size:0.5rem;vertical-align:middle;"></i>EN LIGNE
                </span>
                @endif
            </div>

            <div class="acode-card-body">
                @if($activeCode)
                    <div class="acode-display-grid">
                        {{-- Code principal --}}
                        <div class="acode-main">
                            <div class="acode-value" id="activeCodeValue" title="Cliquer pour copier"
                                 onclick="copyCode('{{ $activeCode->code }}', this)">{{ $activeCode->code }}</div>
                            <div class="acode-active-badge">
                                <span class="pulse"></span>Code Actif
                            </div>
                            @if($activeCode->valid_until)
                            <div class="acode-countdown" id="remaining-time">
                                <i class="fas fa-clock"></i>
                                <span id="countdown-text">{{ $activeCode->getRemainingValidityInMinutes() }} min</span>
                            </div>
                            @endif
                        </div>

                        {{-- Info cards --}}
                        <div class="acode-info-grid">
                            @if($activeCode->valid_until)
                            <div class="acode-info-card">
                                <div class="acode-info-icon orange"><i class="fas fa-hourglass-half"></i></div>
                                <div>
                                    <div class="acode-info-label">Expiration</div>
                                    <div class="acode-info-value">{{ $activeCode->valid_until->format('H:i') }}</div>
                                    <div class="acode-info-extra">{{ $activeCode->valid_until->format('d/m/Y') }}</div>
                                </div>
                            </div>
                            @endif

                            @if($activeCode->seance)
                            <div class="acode-info-card">
                                <div class="acode-info-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div>
                                    <div class="acode-info-label">Séance</div>
                                    <div class="acode-info-value">{{ $activeCode->seance->matiere->name ?? 'Matière' }}</div>
                                    <div class="acode-info-extra">
                                        {{ $activeCode->seance->classe->name ?? 'Classe' }}
                                        @if($activeCode->seance->heure_debut && $activeCode->seance->heure_fin)
                                            · {{ $activeCode->seance->heure_debut }}–{{ $activeCode->seance->heure_fin }}
                                        @endif
                                        @if($activeCode->seance->teacher)
                                            <br>{{ $activeCode->seance->teacher->name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="acode-info-card">
                                <div class="acode-info-icon slate"><i class="fas fa-user-shield"></i></div>
                                <div>
                                    <div class="acode-info-label">Générateur</div>
                                    <div class="acode-info-value">{{ $activeCode->generator->name ?? 'Système' }}</div>
                                    <div class="acode-info-extra">{{ $activeCode->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>

                            <div class="acode-info-card">
                                <div class="acode-info-icon green"><i class="fas fa-tag"></i></div>
                                <div>
                                    <div class="acode-info-label">Type</div>
                                    <div class="acode-info-value">{{ ucfirst($activeCode->type) }}</div>
                                    <div class="acode-info-extra">{{ $activeCode->description ?? 'Code standard' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- KPI Strip --}}
                    <div class="acode-kpi-strip">
                        <div class="acode-kpi">
                            <div class="acode-kpi-val">{{ $activeCode->total_attempts }}</div>
                            <div class="acode-kpi-lbl">Tentatives</div>
                        </div>
                        <div class="acode-kpi success">
                            <div class="acode-kpi-val">{{ $activeCode->successful_attempts }}</div>
                            <div class="acode-kpi-lbl">Réussies</div>
                        </div>
                        <div class="acode-kpi danger">
                            <div class="acode-kpi-val">{{ $activeCode->failed_attempts }}</div>
                            <div class="acode-kpi-lbl">Échouées</div>
                        </div>
                        <div class="acode-kpi info">
                            <div class="acode-kpi-val">{{ $activeCode->getAttemptsStatistics()['success_rate'] }}%</div>
                            <div class="acode-kpi-lbl">Taux réussite</div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="acode-actions">
                        <form action="{{ route('esbtp.attendance-codes.invalidate', $activeCode->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-acasi danger"
                                    onclick="return confirm('Êtes-vous sûr de vouloir invalider ce code ?')">
                                <i class="fas fa-ban me-2"></i>Invalider
                            </button>
                        </form>
                        <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-acasi primary"
                                    onclick="return confirm('Générer un nouveau code invalidera l\'actuel. Continuer ?')">
                                <i class="fas fa-sync-alt me-2"></i>Renouveler
                            </button>
                        </form>
                        <button type="button" class="btn-acasi secondary"
                                data-bs-toggle="modal" data-bs-target="#customCodeModal">
                            <i class="fas fa-sliders-h me-2"></i>Code personnalisé
                        </button>
                    </div>

                @else
                    {{-- Empty state --}}
                    <div class="acode-empty">
                        <div class="acode-empty-icon"><i class="fas fa-key"></i></div>
                        <h3>Aucun code actif aujourd'hui</h3>
                        <p>Générez un code pour permettre aux enseignants de s'émarger.</p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-plus-circle me-2"></i>Générer le code du jour
                                </button>
                            </form>
                            <button type="button" class="btn-acasi secondary"
                                    data-bs-toggle="modal" data-bs-target="#customCodeModal">
                                <i class="fas fa-sliders-h me-2"></i>Code personnalisé
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Codes Récents ────────────────────────────────────── --}}
        <div class="acode-table-card">
            <div class="acode-table-card-header">
                <div class="acode-table-title">
                    <i class="fas fa-history"></i>Codes Récents
                </div>
                <span style="background:rgba(4,83,203,0.08);color:#0453cb;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.78rem;font-weight:700;">
                    {{ $recentCodes->count() }} codes
                </span>
            </div>

            @if($recentCodes->count() > 0)
            <div class="table-responsive">
                <table class="acode-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Séance</th>
                            <th>Création</th>
                            <th>Générateur</th>
                            <th class="text-center">Statistiques</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCodes as $code)
                        @php $isActive = $code->status === 'active' && $code->valid_until > now(); @endphp
                        <tr class="{{ $code->status === 'active' ? 'row-active' : '' }}">
                            <td>
                                <span class="acode-code-pill" title="Cliquer pour copier"
                                      onclick="copyCode('{{ $code->code }}', this)">
                                    {{ $code->code }}
                                    <i class="fas fa-copy" style="font-size:0.62rem;opacity:0.5;"></i>
                                </span>
                            </td>
                            <td>
                                @if($code->seance)
                                    <div class="seance-name">{{ $code->seance->matiere->name ?? 'Matière' }}</div>
                                    <div class="seance-meta">
                                        {{ $code->seance->classe->name ?? 'Classe' }}
                                        @if($code->seance->heure_debut && $code->seance->heure_fin)
                                            · {{ $code->seance->heure_debut }}–{{ $code->seance->heure_fin }}
                                        @endif
                                    </div>
                                    @if($code->seance->teacher)
                                    <div class="seance-teacher">{{ $code->seance->teacher->name }}</div>
                                    @endif
                                @else
                                    <span style="color:var(--text-secondary);font-size:0.82rem;">Code général</span>
                                @endif
                            </td>
                            <td>
                                <div class="date-main">{{ $code->created_at->format('d/m/Y') }}</div>
                                <div class="date-sub">{{ $code->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="user-cell">
                                    @php $genName = $code->generator->name ?? 'Système'; @endphp
                                    <div class="user-avatar">{{ strtoupper(substr($genName, 0, 1)) }}</div>
                                    <span style="font-weight:600;font-size:0.875rem;">{{ $genName }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="stats-val">{{ $code->successful_attempts }}/{{ $code->total_attempts }}</div>
                                <div class="stats-rate">
                                    {{ $code->getAttemptsStatistics()['success_rate'] }}% réussite
                                </div>
                            </td>
                            <td class="text-center">
                                @if($isActive)
                                    <span class="acode-status s-active">
                                        <i class="fas fa-check-circle"></i>Actif
                                    </span>
                                @elseif($code->status === 'expired' || !$isActive)
                                    <span class="acode-status s-expired">
                                        <i class="fas fa-clock"></i>Expiré
                                    </span>
                                @else
                                    <span class="acode-status s-invalid">
                                        <i class="fas fa-ban"></i>Annulé
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isActive)
                                <form action="{{ route('esbtp.attendance-codes.invalidate', $code->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="acode-btn-icon"
                                            onclick="return confirm('Invalider ce code ?')" title="Invalider">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                @else
                                <span style="color:var(--text-secondary);">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="acode-empty">
                <div class="acode-empty-icon"><i class="fas fa-history"></i></div>
                <h3>Aucun historique</h3>
                <p>Les codes générés apparaîtront ici</p>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ── Modal Code Personnalisé ──────────────────────────────── --}}
<div class="modal fade" id="customCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.15);">
            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb); border:none; padding:1.25rem 1.5rem;">
                    <div>
                        <h5 style="color:#fff; font-weight:700; font-size:1rem; margin:0; display:flex; align-items:center; gap:.5rem;">
                            <i class="fas fa-sliders-h" style="font-size:.85rem;"></i>Code Personnalisé
                        </h5>
                        <div style="font-size:.75rem; color:rgba(255,255,255,.7); margin-top:.15rem;">Paramètres de génération du code d'émargement</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:brightness(0) invert(1); opacity:.7;"></button>
                </div>

                <div class="modal-body" style="padding:1.25rem 1.5rem; background:#f8fafc;">
                    <div style="background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem; margin-bottom:.85rem;">
                        <label for="custom_description" style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">
                            Description
                        </label>
                        <input type="text" class="form-control" id="custom_description" name="description"
                               placeholder="Ex: Code pour cours de mathématiques" maxlength="255"
                               style="border-radius:9px; border:1px solid #e2e8f0; font-size:.88rem;">
                        <div style="font-size:.68rem; color:#94a3b8; margin-top:.25rem;">Optionnel — pour identifier ce code</div>
                    </div>

                    <div style="display:flex; gap:.75rem; margin-bottom:.85rem;">
                        <div style="flex:1; background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem;">
                            <label for="custom_duration" style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">
                                <i class="fas fa-clock" style="color:#0453cb; font-size:.65rem;"></i> Durée
                            </label>
                            <select class="form-select" id="custom_duration" name="duration_minutes"
                                    style="border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                                <option value="30">30 min</option>
                                <option value="60" selected>1 heure</option>
                                <option value="90">1h30</option>
                                <option value="120">2 heures</option>
                                <option value="180">3 heures</option>
                                <option value="240">4 heures</option>
                                <option value="480">Journée (8h)</option>
                            </select>
                        </div>
                    </div>

                    <div style="background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem;">
                        <label for="seance_select" style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">
                            <i class="fas fa-calendar-check" style="color:#0453cb; font-size:.65rem;"></i> Séance à venir
                            <span style="font-weight:400; text-transform:none; letter-spacing:0; color:#94a3b8;">(optionnel)</span>
                        </label>
                        <select class="form-select" id="seance_select" name="seance_id"
                                style="border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                            <option value="">Aucune séance spécifique</option>
                            @if(isset($seancesAVenir) && $seancesAVenir->count() > 0)
                                @foreach($seancesAVenir as $seance)
                                <option value="{{ $seance->id }}"
                                        data-duration="{{ $seance->getDureeEnMinutes() }}"
                                        data-description="{{ ($seance->matiere->name ?? 'Matière') }} - {{ ($seance->classe->name ?? 'Classe') }}">
                                    {{ $seance->matiere->name ?? 'Matière' }} — {{ $seance->classe->name ?? 'Classe' }}
                                    ({{ $seance->heure_debut->format('H:i') }}–{{ $seance->heure_fin->format('H:i') }})
                                    @if($seance->teacher) · {{ $seance->teacher->name }}@endif
                                </option>
                                @endforeach
                            @endif
                        </select>
                        <div style="font-size:.68rem; color:#94a3b8; margin-top:.25rem;">Remplit automatiquement la durée et la description</div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:.85rem 1.5rem; background:#fff; gap:.4rem;">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                        style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0;">
                        Annuler
                    </button>
                    <button type="submit" class="btn"
                        style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); border:none; box-shadow:0 2px 6px rgba(4,83,203,.25);">
                        <i class="fas fa-key me-1" style="font-size:.7rem;"></i>Générer le code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Auto-dismiss alerts after 5s
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(a => {
            a.classList.remove('show');
            setTimeout(() => a.remove(), 150);
        });
    }, 5000);

    let countdownTimer;
    let autoRefreshTimer;

    @if($activeCode && $activeCode->valid_until)
    // Countdown timer
    const expirationTime = new Date("{{ $activeCode->valid_until->format('Y-m-d\TH:i:sP') }}").getTime();
    const countdownEl    = document.getElementById('remaining-time');
    const countdownText  = document.getElementById('countdown-text');

    function updateCountdown() {
        const distance = expirationTime - Date.now();

        if (distance <= 0) {
            clearInterval(countdownTimer);
            clearTimeout(autoRefreshTimer);
            countdownText.textContent = 'Expiré';
            countdownEl.classList.add('danger');
            setTimeout(() => location.reload(), 2000);
            return;
        }

        const h = Math.floor(distance / 3600000);
        const m = Math.floor((distance % 3600000) / 60000);
        const s = Math.floor((distance % 60000) / 1000);

        let txt = '';
        if (h > 0)      txt = `${h}h ${m}min ${s}s`;
        else if (m > 0) txt = `${m}min ${s}s`;
        else            txt = `${s}s`;

        countdownText.textContent = txt;
        countdownEl.classList.remove('warning', 'danger');
        if (m < 2 && h === 0)      countdownEl.classList.add('danger');
        else if (m <= 5 && h === 0) countdownEl.classList.add('warning');
    }

    updateCountdown();
    countdownTimer = setInterval(updateCountdown, 1000);
    @endif

    // Auto-refresh every 10 minutes
    autoRefreshTimer = setTimeout(() => location.reload(), 600000);

    // Seance auto-fill in modal
    const seanceSelect   = document.getElementById('seance_select');
    const durationSelect = document.getElementById('custom_duration');
    const descInput      = document.getElementById('custom_description');

    if (seanceSelect) {
        seanceSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                const dur  = opt.getAttribute('data-duration');
                const desc = opt.getAttribute('data-description');
                if (dur)  durationSelect.value = dur;
                if (desc && !descInput.value) descInput.value = 'Code pour ' + desc;
            }
        });
    }
});

function copyCode(code, el) {
    navigator.clipboard.writeText(code).then(() => {
        el.classList.add('copied');
        setTimeout(() => el.classList.remove('copied'), 1400);
    }).catch(() => {});
}
</script>
@endpush
