@extends('layouts.app')

@section('title', 'Fiche Comptable — ' . $user->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   COMPTABLE SHOW — Premium Design — KLASSCI Design System
   Namespace: cs-comptable- (comptable-show)
=================================================================== */

/* -- Tokens -------------------------------------------------------- */
:root {
    --cs-comptable-blue:      #0453cb;
    --cs-comptable-blue-2:    #5e91de;
    --cs-comptable-surface:   #f4f7fb;
    --cs-comptable-card:      #ffffff;
    --cs-comptable-border:    #e2e8f0;
    --cs-comptable-text:      #1e293b;
    --cs-comptable-muted:     #64748b;
    --cs-comptable-success:   #10b981;
    --cs-comptable-danger:    #dc2626;
    --cs-comptable-radius:    12px;
    --cs-comptable-radius-lg: 20px;
    --cs-comptable-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --cs-comptable-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* -- Page shell ---------------------------------------------------- */
.cs-comptable-page { background: var(--cs-comptable-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.cs-comptable-hero {
    position: relative;
    background: linear-gradient(135deg, var(--cs-comptable-blue) 0%, var(--cs-comptable-blue-2) 100%);
    padding: 0;
}
.cs-comptable-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
}
.cs-comptable-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--cs-comptable-surface) 0%, transparent 100%);
}

.cs-comptable-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar */
.cs-comptable-hero-avatar {
    position: relative; flex-shrink: 0;
}
.cs-comptable-hero-avatar-circle {
    width: 96px; height: 96px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,.6);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; font-weight: 700; color: rgba(255,255,255,.9);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.22);
    backdrop-filter: blur(4px);
}
.cs-comptable-hero-avatar-circle img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.cs-comptable-hero-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.cs-comptable-hero-status.active   { background: #10b981; }
.cs-comptable-hero-status.inactive { background: #94a3b8; }

/* Text block */
.cs-comptable-hero-text { flex: 1; min-width: 200px; color: #fff; }
.cs-comptable-hero-name {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em;
    margin: 0 0 3px; line-height: 1.2;
}
.cs-comptable-hero-name span { display: inline; }
.cs-comptable-hero-sub { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.cs-comptable-hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.cs-comptable-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.cs-comptable-hero-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.cs-comptable-hero-pill.muted { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); }

/* Actions in hero */
.cs-comptable-hero-actions {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; margin-left: auto;
}
.cs-comptable-hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.cs-comptable-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.cs-comptable-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--cs-comptable-blue); }
.cs-comptable-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.cs-comptable-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.cs-comptable-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- KPI Strip (inside hero) --------------------------------------- */
.cs-comptable-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 8px;
}
.cs-comptable-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.cs-comptable-kpi:last-child { border-right: none; }
.cs-comptable-kpi-icon { font-size: 1rem; opacity: .7; }
.cs-comptable-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.cs-comptable-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* -- Tab Bar ------------------------------------------------------- */
.cs-comptable-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--cs-comptable-card);
    box-shadow: 0 1px 0 var(--cs-comptable-border);
}
.cs-comptable-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.cs-comptable-tabs::-webkit-scrollbar { display: none; }
.cs-comptable-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--cs-comptable-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.cs-comptable-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--cs-comptable-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.cs-comptable-tab:hover { color: var(--cs-comptable-blue); }
.cs-comptable-tab.active { color: var(--cs-comptable-blue); }
.cs-comptable-tab.active::after { transform: scaleX(1); }

/* -- Content area -------------------------------------------------- */
.cs-comptable-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.cs-comptable-panel { display: none; animation: csComptableFadeUp .24s ease; }
.cs-comptable-panel.active { display: block; }
@keyframes csComptableFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* -- Section card -------------------------------------------------- */
.cs-comptable-card {
    background: var(--cs-comptable-card); border: 1px solid var(--cs-comptable-border);
    border-radius: var(--cs-comptable-radius-lg); padding: 24px;
    box-shadow: var(--cs-comptable-shadow); margin-bottom: 20px;
}
.cs-comptable-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.cs-comptable-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--cs-comptable-text);
}
.cs-comptable-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--cs-comptable-blue) 0%, var(--cs-comptable-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* -- Info rows ----------------------------------------------------- */
.cs-comptable-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--cs-comptable-border);
}
.cs-comptable-info-row:last-child { border-bottom: none; }
.cs-comptable-info-label {
    font-size: .85rem; font-weight: 500; color: var(--cs-comptable-muted);
    display: flex; align-items: center; gap: 8px;
}
.cs-comptable-info-label i { font-size: .75rem; width: 16px; text-align: center; color: var(--cs-comptable-blue); opacity: .6; }
.cs-comptable-info-value { font-size: .9rem; font-weight: 600; color: var(--cs-comptable-text); }

/* Inline edit inputs */
.cs-comptable-edit-input {
    border: none; border-bottom: 2px solid var(--cs-comptable-blue); background: transparent;
    font-size: .9rem; font-weight: 600; color: var(--cs-comptable-text);
    padding: 2px 4px; outline: none; display: none; min-width: 180px;
    text-align: right;
}
.cs-comptable-edit-input:focus { border-bottom-color: var(--cs-comptable-blue-2); }
select.cs-comptable-edit-input { text-align: left; min-width: 160px; cursor: pointer; }

/* -- Status badge -------------------------------------------------- */
.cs-comptable-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .03em;
}
.cs-comptable-badge.success { background: rgba(16,185,129,.1); color: #059669; }
.cs-comptable-badge.danger  { background: rgba(220,38,38,.1);  color: #dc2626; }
.cs-comptable-badge.muted   { background: rgba(100,116,139,.1); color: #64748b; }

/* -- Grid layout for two-column panels ----------------------------- */
.cs-comptable-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}

/* -- Permission badges --------------------------------------------- */
.cs-comptable-perm-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(4,83,203,.06); color: var(--cs-comptable-blue);
    border-radius: 20px; padding: 4px 12px; font-size: .78rem; font-weight: 500; margin: 3px;
    border: 1px solid rgba(4,83,203,.12);
}

/* -- Quick access cards -------------------------------------------- */
.cs-comptable-quick-card {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    padding: 20px 12px; border-radius: var(--cs-comptable-radius);
    border: 1px solid var(--cs-comptable-border);
    background: var(--cs-comptable-card);
    text-decoration: none; color: var(--cs-comptable-text);
    transition: all .18s;
}
.cs-comptable-quick-card:hover {
    border-color: var(--cs-comptable-blue); color: var(--cs-comptable-blue);
    box-shadow: 0 4px 16px rgba(4,83,203,.1);
    transform: translateY(-2px);
}
.cs-comptable-quick-card i { font-size: 1.6rem; }
.cs-comptable-quick-card span { font-size: .82rem; font-weight: 600; text-align: center; }

/* -- Action buttons inside cards ----------------------------------- */
.cs-comptable-action-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 14px; border-radius: 10px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid var(--cs-comptable-border);
    background: var(--cs-comptable-card); color: var(--cs-comptable-text);
    transition: all .18s;
}
.cs-comptable-action-btn:hover {
    border-color: var(--cs-comptable-blue); color: var(--cs-comptable-blue);
    box-shadow: 0 2px 8px rgba(4,83,203,.1);
    transform: translateY(-1px);
}
.cs-comptable-action-btn i { width: 18px; text-align: center; }
.cs-comptable-action-btn.danger-action { color: var(--cs-comptable-danger); border-color: rgba(220,38,38,.2); }
.cs-comptable-action-btn.danger-action:hover {
    border-color: var(--cs-comptable-danger); background: rgba(220,38,38,.03);
    box-shadow: 0 2px 8px rgba(220,38,38,.1);
}
.cs-comptable-action-btn.warning-action { color: #d97706; border-color: rgba(217,119,6,.2); }
.cs-comptable-action-btn.warning-action:hover {
    border-color: #d97706; background: rgba(217,119,6,.03);
    box-shadow: 0 2px 8px rgba(217,119,6,.1);
}
.cs-comptable-action-btn.success-action { color: #059669; border-color: rgba(5,150,105,.2); }
.cs-comptable-action-btn.success-action:hover {
    border-color: #059669; background: rgba(5,150,105,.03);
    box-shadow: 0 2px 8px rgba(5,150,105,.1);
}

/* -- Responsive ---------------------------------------------------- */
@media (max-width: 1024px) {
    .cs-comptable-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .cs-comptable-hero-inner { padding: 24px 16px 0; flex-direction: column; text-align: center; }
    .cs-comptable-hero-pills { justify-content: center; }
    .cs-comptable-hero-actions { margin-left: 0; align-items: center; }
    .cs-comptable-kpi-strip { flex-wrap: wrap; }
    .cs-comptable-kpi { flex: 1 1 50%; border-bottom: 1px solid rgba(255,255,255,.1); }
    .cs-comptable-content { padding: 20px 16px 40px; }
    .cs-comptable-tabs { padding: 0 12px; }
    .cs-comptable-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
    .cs-comptable-edit-input { text-align: left; }
}
</style>
@endsection

@section('content')
<div class="cs-comptable-page">

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="cs-comptable-hero">
        <div class="cs-comptable-hero-inner">
            {{-- Avatar --}}
            <div class="cs-comptable-hero-avatar">
                <div class="cs-comptable-hero-avatar-circle">
                    @if($user->photo_url ?? false)
                        <img src="{{ $user->photo_url }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    @endif
                </div>
                <div class="cs-comptable-hero-status {{ $user->is_active ? 'active' : 'inactive' }}"></div>
            </div>

            {{-- Info --}}
            <div class="cs-comptable-hero-text">
                <h1 class="cs-comptable-hero-name">
                    <span id="cs-comptable-displayName">{{ $user->name }}</span>
                </h1>
                <p class="cs-comptable-hero-sub">{{ $user->department ?? 'Service Comptabilite' }}</p>
                <div class="cs-comptable-hero-pills">
                    <span class="cs-comptable-hero-pill">
                        <i class="fas fa-calculator"></i> Comptable
                    </span>
                    <span class="cs-comptable-hero-pill {{ $user->is_active ? 'green' : '' }}">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="cs-comptable-hero-pill muted">
                        <i class="fas fa-calendar-alt"></i>
                        Depuis {{ $user->created_at->format('M Y') }}
                    </span>
                    @if($user->last_login_at)
                    <span class="cs-comptable-hero-pill muted">
                        <i class="fas fa-clock"></i>
                        {{ $user->last_login_at->diffForHumans() }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="cs-comptable-hero-actions">
                <div class="cs-comptable-hero-btns">
                    <button type="button" class="cs-comptable-hero-btn primary" id="cs-comptable-editToggle" onclick="csComptableStartEdit()">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button type="button" class="cs-comptable-hero-btn primary" id="cs-comptable-saveBtn" style="display:none;" onclick="csComptableSaveChanges()">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                    <button type="button" class="cs-comptable-hero-btn ghost" id="cs-comptable-cancelBtn" style="display:none;" onclick="csComptableCancelEdit()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cs-comptable-hero-btn ghost">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="cs-comptable-hero-btn ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="cs-comptable-kpi-strip">
            <div class="cs-comptable-kpi">
                <i class="fas fa-calculator cs-comptable-kpi-icon"></i>
                <div>
                    <div class="cs-comptable-kpi-val">Comptable</div>
                    <div class="cs-comptable-kpi-lbl">Role</div>
                </div>
            </div>
            <div class="cs-comptable-kpi">
                <i class="fas fa-circle cs-comptable-kpi-icon" style="font-size:.6rem; color: {{ $user->is_active ? '#10b981' : '#94a3b8' }};"></i>
                <div>
                    <div class="cs-comptable-kpi-val">{{ $user->is_active ? 'Actif' : 'Inactif' }}</div>
                    <div class="cs-comptable-kpi-lbl">Statut</div>
                </div>
            </div>
            <div class="cs-comptable-kpi">
                <i class="fas fa-shield-alt cs-comptable-kpi-icon"></i>
                <div>
                    @php
                        $comptableRole = \Spatie\Permission\Models\Role::where('name', 'comptable')->first();
                        $comptablePerms = $comptableRole ? $comptableRole->permissions : collect();
                    @endphp
                    <div class="cs-comptable-kpi-val">{{ $comptablePerms->count() }}</div>
                    <div class="cs-comptable-kpi-lbl">Permissions</div>
                </div>
            </div>
            <div class="cs-comptable-kpi">
                <i class="fas fa-calendar-plus cs-comptable-kpi-icon"></i>
                <div>
                    <div class="cs-comptable-kpi-val">{{ $user->created_at->format('d/m/Y') }}</div>
                    <div class="cs-comptable-kpi-lbl">Date creation</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB BAR
    ============================================================= --}}
    <div class="cs-comptable-tabs-wrap">
        <div class="cs-comptable-tabs">
            <button class="cs-comptable-tab active" data-tab="info" type="button">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="cs-comptable-tab" data-tab="access" type="button">
                <i class="fas fa-rocket"></i> Acces Rapide
            </button>
            <button class="cs-comptable-tab" data-tab="account" type="button">
                <i class="fas fa-user-cog"></i> Compte
            </button>
            <button class="cs-comptable-tab" data-tab="actions" type="button">
                <i class="fas fa-cog"></i> Actions
            </button>
        </div>
    </div>

    {{-- ============================================================
         CONTENT
    ============================================================= --}}
    <div class="cs-comptable-content">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" style="border: none; box-shadow: var(--cs-comptable-shadow);">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" style="border: none; box-shadow: var(--cs-comptable-shadow);">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ---- TAB: Informations --------------------------------- --}}
        <div class="cs-comptable-panel active" id="cs-comptable-tab-info">
            <div class="cs-comptable-grid-2">
                {{-- Account info --}}
                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon"><i class="fas fa-id-card"></i></div>
                            Informations du compte
                        </div>
                    </div>

                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-user"></i> Nom complet</span>
                        <span class="cs-comptable-info-value" data-field="name">{{ $user->name }}</span>
                        <input class="cs-comptable-edit-input" type="text" data-field="name" value="{{ $user->name }}" placeholder="Nom complet">
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="cs-comptable-info-value" data-field="email">{{ $user->email }}</span>
                        <input class="cs-comptable-edit-input" type="email" data-field="email" value="{{ $user->email }}" placeholder="Email">
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-phone"></i> Telephone</span>
                        <span class="cs-comptable-info-value" data-field="telephone">{{ $user->telephone ?: 'Non renseigne' }}</span>
                        <input class="cs-comptable-edit-input" type="text" data-field="telephone" value="{{ $user->telephone }}" placeholder="Telephone">
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-building"></i> Departement</span>
                        <span class="cs-comptable-info-value" data-field="department">{{ $user->department ?: 'Non renseigne' }}</span>
                        <select class="cs-comptable-edit-input" data-field="department">
                            <option value="">-- Selectionner --</option>
                            <option value="Comptabilite" {{ $user->department === 'Comptabilité' ? 'selected' : '' }}>Comptabilite</option>
                            <option value="Finance" {{ $user->department === 'Finance' ? 'selected' : '' }}>Finance</option>
                            <option value="Audit" {{ $user->department === 'Audit' ? 'selected' : '' }}>Audit</option>
                        </select>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="cs-comptable-info-value">{{ $user->username }}</span>
                    </div>
                </div>

                {{-- Permissions --}}
                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon"><i class="fas fa-shield-alt"></i></div>
                            Permissions actives
                        </div>
                        <a href="{{ route('esbtp.roles-permissions.index', ['role' => 'comptable']) }}" class="cs-comptable-hero-btn primary" style="padding: 6px 14px; font-size: .78rem;">
                            <i class="fas fa-cog"></i> Gerer
                        </a>
                    </div>
                    <div>
                        @forelse($comptablePerms as $perm)
                            <span class="cs-comptable-perm-badge"><i class="fas fa-check" style="font-size:.6rem;"></i> {{ $perm->name }}</span>
                        @empty
                            <p style="font-size: .88rem; color: var(--cs-comptable-muted); margin: 0;">Aucune permission attribuee au role comptable.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- System info --}}
            <div class="cs-comptable-card">
                <div class="cs-comptable-card-header">
                    <div class="cs-comptable-card-title">
                        <div class="cs-comptable-card-title-icon"><i class="fas fa-server"></i></div>
                        Informations Systeme
                    </div>
                </div>
                <div class="cs-comptable-grid-2">
                    <div>
                        <div class="cs-comptable-info-row">
                            <span class="cs-comptable-info-label"><i class="fas fa-hashtag"></i> ID</span>
                            <span class="cs-comptable-info-value">#{{ $user->id }}</span>
                        </div>
                        <div class="cs-comptable-info-row">
                            <span class="cs-comptable-info-label"><i class="fas fa-calendar-plus"></i> Cree le</span>
                            <span class="cs-comptable-info-value">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="cs-comptable-info-row">
                            <span class="cs-comptable-info-label"><i class="fas fa-edit"></i> Modifie le</span>
                            <span class="cs-comptable-info-value">{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="cs-comptable-info-row">
                            <span class="cs-comptable-info-label"><i class="fas fa-sign-in-alt"></i> Derniere connexion</span>
                            <span class="cs-comptable-info-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Acces Rapide --------------------------------- --}}
        <div class="cs-comptable-panel" id="cs-comptable-tab-access">
            <div class="cs-comptable-card">
                <div class="cs-comptable-card-header">
                    <div class="cs-comptable-card-title">
                        <div class="cs-comptable-card-title-icon"><i class="fas fa-rocket"></i></div>
                        Acces rapides comptabilite
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
                    <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cs-comptable-quick-card">
                        <i class="fas fa-chart-line" style="color: var(--cs-comptable-blue);"></i>
                        <span>Dashboard Compta</span>
                    </a>
                    <a href="{{ route('esbtp.paiements.index') }}" class="cs-comptable-quick-card">
                        <i class="fas fa-money-bill-wave" style="color: var(--cs-comptable-success);"></i>
                        <span>Paiements</span>
                    </a>
                    <a href="{{ route('esbtp.frais.index') }}" class="cs-comptable-quick-card">
                        <i class="fas fa-tags" style="color: #d97706;"></i>
                        <span>Frais</span>
                    </a>
                    <a href="{{ route('esbtp.roles-permissions.index', ['role' => 'comptable']) }}" class="cs-comptable-quick-card">
                        <i class="fas fa-sliders-h" style="color: var(--cs-comptable-danger);"></i>
                        <span>Permissions</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Compte --------------------------------------- --}}
        <div class="cs-comptable-panel" id="cs-comptable-tab-account">
            <div class="cs-comptable-grid-2">
                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon"><i class="fas fa-user-cog"></i></div>
                            Compte utilisateur
                        </div>
                    </div>

                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="cs-comptable-info-value">{{ $user->username }}</span>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="cs-comptable-info-value">{{ $user->email }}</span>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                        <span class="cs-comptable-info-value">
                            <span class="cs-comptable-badge {{ $user->is_active ? 'success' : 'danger' }}">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-user-tag"></i> Role</span>
                        <span class="cs-comptable-info-value">Comptable</span>
                    </div>
                </div>

                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon"><i class="fas fa-shield-alt"></i></div>
                            Securite
                        </div>
                    </div>

                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="cs-comptable-info-value">{{ $user->first_login_at ? $user->first_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="cs-comptable-info-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="cs-comptable-info-row">
                        <span class="cs-comptable-info-label"><i class="fas fa-calendar"></i> Compte cree le</span>
                        <span class="cs-comptable-info-value">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>

                    {{-- Contact rapide --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--cs-comptable-border);">
                        <div style="font-size: .85rem; font-weight: 700; color: var(--cs-comptable-text); margin-bottom: 12px;">
                            Contact rapide
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 8px 0;">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(4,83,203,.08); display: flex; align-items: center; justify-content: center; color: var(--cs-comptable-blue); font-size: .85rem; flex-shrink: 0;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <a href="mailto:{{ $user->email }}" style="color: var(--cs-comptable-text); text-decoration: none; font-weight: 500; font-size: .88rem;">{{ $user->email }}</a>
                        </div>
                        @if($user->telephone)
                        <div style="display: flex; align-items: center; gap: 12px; padding: 8px 0;">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(4,83,203,.08); display: flex; align-items: center; justify-content: center; color: var(--cs-comptable-blue); font-size: .85rem; flex-shrink: 0;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <a href="tel:{{ $user->telephone }}" style="color: var(--cs-comptable-text); text-decoration: none; font-weight: 500; font-size: .88rem;">{{ $user->telephone }}</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Actions -------------------------------------- --}}
        <div class="cs-comptable-panel" id="cs-comptable-tab-actions">
            <div class="cs-comptable-grid-2">
                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon"><i class="fas fa-tools"></i></div>
                            Actions Rapides
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button type="button" class="cs-comptable-action-btn" onclick="csComptableStartEdit(); document.querySelector('.cs-comptable-tab[data-tab=info]').click();">
                            <i class="fas fa-edit"></i>
                            <span>Modifier les informations</span>
                        </button>

                        @if($user->is_active)
                        <form action="{{ route('esbtp.comptables.toggle-status', $user) }}" method="POST"
                              onsubmit="return confirm('Desactiver ce comptable ?')">
                            @csrf
                            <button type="submit" class="cs-comptable-action-btn warning-action">
                                <i class="fas fa-pause-circle"></i>
                                <span>Desactiver le compte</span>
                            </button>
                        </form>
                        @else
                        <form action="{{ route('esbtp.comptables.toggle-status', $user) }}" method="POST"
                              onsubmit="return confirm('Activer ce comptable ?')">
                            @csrf
                            <button type="submit" class="cs-comptable-action-btn success-action">
                                <i class="fas fa-play-circle"></i>
                                <span>Activer le compte</span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <div class="cs-comptable-card">
                    <div class="cs-comptable-card-header">
                        <div class="cs-comptable-card-title">
                            <div class="cs-comptable-card-title-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-exclamation-triangle"></i></div>
                            Zone de danger
                        </div>
                    </div>

                    <p style="font-size: .85rem; color: var(--cs-comptable-muted); margin: 0 0 16px;">
                        Cette action desactivera le comptable et retirera son role. Elle est irreversible.
                    </p>

                    <form action="{{ route('esbtp.comptables.destroy', $user) }}" method="POST"
                          onsubmit="return confirm('Supprimer definitivement ce comptable ? Cette action est irreversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cs-comptable-action-btn danger-action">
                            <i class="fas fa-trash-alt"></i>
                            <span>Supprimer definitivement</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// -- Tab switching -----------------------------------------------
document.querySelectorAll('.cs-comptable-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.cs-comptable-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.cs-comptable-panel').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.getElementById('cs-comptable-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});

// -- Inline editing ----------------------------------------------
var csComptableUpdateUrl = "{{ route('esbtp.comptables.update', $user) }}";
var csComptableCsrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function csComptableStartEdit() {
    document.querySelectorAll('.cs-comptable-info-value[data-field]').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.cs-comptable-edit-input').forEach(function(el) { el.style.display = ''; });
    document.getElementById('cs-comptable-saveBtn').style.display = 'inline-flex';
    document.getElementById('cs-comptable-cancelBtn').style.display = 'inline-flex';
    document.getElementById('cs-comptable-editToggle').style.display = 'none';
}

function csComptableCancelEdit() {
    document.querySelectorAll('.cs-comptable-info-value[data-field]').forEach(function(el) { el.style.display = ''; });
    document.querySelectorAll('.cs-comptable-edit-input').forEach(function(el) { el.style.display = 'none'; });
    document.getElementById('cs-comptable-saveBtn').style.display = 'none';
    document.getElementById('cs-comptable-cancelBtn').style.display = 'none';
    document.getElementById('cs-comptable-editToggle').style.display = 'inline-flex';
}

function csComptableSaveChanges() {
    var data = { _method: 'PUT' };
    document.querySelectorAll('.cs-comptable-edit-input').forEach(function(el) {
        var field = el.dataset.field;
        if (field) data[field] = el.value;
    });

    var saveBtn = document.getElementById('cs-comptable-saveBtn');
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
    saveBtn.disabled = true;

    fetch(csComptableUpdateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csComptableCsrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp.success) {
            document.querySelectorAll('.cs-comptable-info-value[data-field]').forEach(function(el) {
                var field = el.dataset.field;
                if (field) {
                    var input = document.querySelector('.cs-comptable-edit-input[data-field="' + field + '"]');
                    el.textContent = input.value || 'Non renseigne';
                }
            });
            if (data.name) {
                document.getElementById('cs-comptable-displayName').textContent = data.name;
            }
            csComptableCancelEdit();
            csComptableNotify('Informations mises a jour avec succes.', 'success');
        } else {
            csComptableNotify('Erreur lors de la sauvegarde.', 'danger');
        }
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
        saveBtn.disabled = false;
    })
    .catch(function() {
        csComptableNotify('Erreur reseau lors de la sauvegarde.', 'danger');
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
        saveBtn.disabled = false;
    });
}

// -- Notification toast -------------------------------------------
function csComptableNotify(message, type) {
    var colors = { success: '#10b981', danger: '#dc2626', warning: '#d97706', info: '#0453cb' };
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:20px;right:20px;background:' + (colors[type] || colors.info) +
        ';color:white;padding:12px 20px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.15);z-index:9999;font-weight:600;font-size:.9rem;transform:translateX(120%);transition:transform .3s ease;';
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function() { el.style.transform = 'translateX(0)'; }, 50);
    setTimeout(function() {
        el.style.transform = 'translateX(120%)';
        setTimeout(function() { document.body.removeChild(el); }, 300);
    }, 3000);
}
</script>
@endpush
