@extends('layouts.app')

@section('title', 'Profil Secretaire — ' . ($secretaire->first_name ?? $secretaire->name ?? 'Secretaire') . ' ' . ($secretaire->last_name ?? '') . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   SECRETAIRE SHOW — Premium Design — KLASSCI Design System
   Namespace: ss- (secretaire-show)
=================================================================== */

/* -- Tokens -------------------------------------------------------- */
:root {
    --ss-blue:      #0453cb;
    --ss-blue-2:    #5e91de;
    --ss-surface:   #f4f7fb;
    --ss-card:      #ffffff;
    --ss-border:    #e2e8f0;
    --ss-text:      #1e293b;
    --ss-muted:     #64748b;
    --ss-success:   #10b981;
    --ss-danger:    #dc2626;
    --ss-radius:    12px;
    --ss-radius-lg: 20px;
    --ss-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --ss-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* -- Page shell ---------------------------------------------------- */
.ss-page { background: var(--ss-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.ss-hero {
    position: relative;
    background: linear-gradient(135deg, var(--ss-blue) 0%, var(--ss-blue-2) 100%);
    padding: 0;
}
.ss-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
}
.ss-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--ss-surface) 0%, transparent 100%);
}

.ss-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar */
.ss-hero-avatar {
    position: relative; flex-shrink: 0;
}
.ss-hero-avatar-circle {
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
.ss-hero-avatar-circle img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.ss-hero-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.ss-hero-status.active   { background: #10b981; }
.ss-hero-status.inactive { background: #94a3b8; }

/* Text block */
.ss-hero-text { flex: 1; min-width: 200px; color: #fff; }
.ss-hero-name {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em;
    margin: 0 0 3px; line-height: 1.2;
}
.ss-hero-sub { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.ss-hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.ss-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.ss-hero-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.ss-hero-pill.muted { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); }

/* Actions in hero */
.ss-hero-actions {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; margin-left: auto;
}
.ss-hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.ss-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.ss-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--ss-blue); }
.ss-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.ss-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.ss-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- KPI Strip (inside hero) --------------------------------------- */
.ss-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 8px;
}
.ss-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.ss-kpi:last-child { border-right: none; }
.ss-kpi-icon { font-size: 1rem; opacity: .7; }
.ss-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.ss-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* -- Tab Bar ------------------------------------------------------- */
.ss-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--ss-card);
    box-shadow: 0 1px 0 var(--ss-border);
}
.ss-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.ss-tabs::-webkit-scrollbar { display: none; }
.ss-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--ss-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.ss-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--ss-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.ss-tab:hover { color: var(--ss-blue); }
.ss-tab.active { color: var(--ss-blue); }
.ss-tab.active::after { transform: scaleX(1); }

/* -- Content area -------------------------------------------------- */
.ss-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.ss-panel { display: none; animation: ssFadeUp .24s ease; }
.ss-panel.active { display: block; }
@keyframes ssFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* -- Section card -------------------------------------------------- */
.ss-card {
    background: var(--ss-card); border: 1px solid var(--ss-border);
    border-radius: var(--ss-radius-lg); padding: 24px;
    box-shadow: var(--ss-shadow); margin-bottom: 20px;
}
.ss-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.ss-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--ss-text);
}
.ss-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--ss-blue) 0%, var(--ss-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* -- Info rows ----------------------------------------------------- */
.ss-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--ss-border);
}
.ss-info-row:last-child { border-bottom: none; }
.ss-info-label {
    font-size: .85rem; font-weight: 500; color: var(--ss-muted);
    display: flex; align-items: center; gap: 8px;
}
.ss-info-label i { font-size: .75rem; width: 16px; text-align: center; color: var(--ss-blue); opacity: .6; }
.ss-info-value { font-size: .9rem; font-weight: 600; color: var(--ss-text); }

/* -- Status badge -------------------------------------------------- */
.ss-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .03em;
}
.ss-badge.success { background: rgba(16,185,129,.1); color: #059669; }
.ss-badge.danger  { background: rgba(220,38,38,.1);  color: #dc2626; }
.ss-badge.muted   { background: rgba(100,116,139,.1); color: #64748b; }

/* -- Grid layout for two-column panels ----------------------------- */
.ss-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}

/* -- Action buttons inside cards ----------------------------------- */
.ss-action-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 14px; border-radius: 10px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid var(--ss-border);
    background: var(--ss-card); color: var(--ss-text);
    transition: all .18s;
}
.ss-action-btn:hover {
    border-color: var(--ss-blue); color: var(--ss-blue);
    box-shadow: 0 2px 8px rgba(4,83,203,.1);
    transform: translateY(-1px);
}
.ss-action-btn i { width: 18px; text-align: center; }
.ss-action-btn.danger-action { color: var(--ss-danger); border-color: rgba(220,38,38,.2); }
.ss-action-btn.danger-action:hover {
    border-color: var(--ss-danger); background: rgba(220,38,38,.03);
    box-shadow: 0 2px 8px rgba(220,38,38,.1);
}
.ss-action-btn.warning-action { color: #d97706; border-color: rgba(217,119,6,.2); }
.ss-action-btn.warning-action:hover {
    border-color: #d97706; background: rgba(217,119,6,.03);
    box-shadow: 0 2px 8px rgba(217,119,6,.1);
}
.ss-action-btn.success-action { color: #059669; border-color: rgba(5,150,105,.2); }
.ss-action-btn.success-action:hover {
    border-color: #059669; background: rgba(5,150,105,.03);
    box-shadow: 0 2px 8px rgba(5,150,105,.1);
}

/* -- Empty state --------------------------------------------------- */
.ss-empty {
    text-align: center; padding: 40px 20px; color: var(--ss-muted);
}
.ss-empty-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: var(--ss-surface);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: var(--ss-muted); margin-bottom: 12px;
}
.ss-empty p { font-size: .9rem; margin: 0; }

/* -- Activity timeline --------------------------------------------- */
.ss-timeline-item {
    display: flex; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid var(--ss-border);
}
.ss-timeline-item:last-child { border-bottom: none; }
.ss-timeline-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--ss-blue); margin-top: 6px; flex-shrink: 0;
}
.ss-timeline-date { font-size: .78rem; color: var(--ss-muted); font-weight: 500; }
.ss-timeline-title { font-size: .88rem; font-weight: 600; color: var(--ss-text); margin-top: 2px; }
.ss-timeline-desc { font-size: .82rem; color: var(--ss-muted); margin-top: 2px; }

/* -- Contact card items -------------------------------------------- */
.ss-contact-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--ss-border);
}
.ss-contact-item:last-child { border-bottom: none; }
.ss-contact-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(4,83,203,.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--ss-blue); font-size: .85rem; flex-shrink: 0;
}
.ss-contact-item a {
    color: var(--ss-text); text-decoration: none; font-weight: 500; font-size: .88rem;
    transition: color .15s;
}
.ss-contact-item a:hover { color: var(--ss-blue); }

/* -- Password alert ------------------------------------------------ */
.ss-password-alert {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(16,185,129,.3);
    border-radius: var(--ss-radius); padding: 16px;
    margin-bottom: 16px;
}
.ss-password-alert h6 { color: #047857; font-size: .9rem; font-weight: 700; margin: 0 0 6px; }
.ss-password-alert code { background: rgba(0,0,0,.06); padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #065f46; }
.ss-password-alert .ss-password-hint { font-size: .8rem; color: #047857; margin: 8px 0 0; }

/* -- Responsive ---------------------------------------------------- */
@media (max-width: 1024px) {
    .ss-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .ss-hero-inner { padding: 24px 16px 0; flex-direction: column; text-align: center; }
    .ss-hero-pills { justify-content: center; }
    .ss-hero-actions { margin-left: 0; align-items: center; }
    .ss-kpi-strip { flex-wrap: wrap; }
    .ss-kpi { flex: 1 1 50%; border-bottom: 1px solid rgba(255,255,255,.1); }
    .ss-content { padding: 20px 16px 40px; }
    .ss-tabs { padding: 0 12px; }
    .ss-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>
@endsection

@section('content')
<div class="ss-page">

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="ss-hero">
        <div class="ss-hero-inner">
            {{-- Avatar --}}
            <div class="ss-hero-avatar">
                <div class="ss-hero-avatar-circle">
                    @if($secretaire->photo_url ?? false)
                        <img src="{{ $secretaire->photo_url }}" alt="{{ $secretaire->name }}">
                    @else
                        {{ strtoupper(substr($secretaire->first_name ?? $secretaire->name ?? 'S', 0, 1) . substr($secretaire->last_name ?? '', 0, 1)) }}
                    @endif
                </div>
                <div class="ss-hero-status {{ $secretaire->is_active ? 'active' : 'inactive' }}"></div>
            </div>

            {{-- Info --}}
            <div class="ss-hero-text">
                <h1 class="ss-hero-name">{{ $secretaire->first_name ?? $secretaire->name ?? 'Secretaire' }} {{ $secretaire->last_name ?? '' }}</h1>
                <p class="ss-hero-sub">Secretaire academique</p>
                <div class="ss-hero-pills">
                    <span class="ss-hero-pill">
                        <i class="fas fa-user-shield"></i> Secretaire
                    </span>
                    <span class="ss-hero-pill {{ $secretaire->is_active ? 'green' : '' }}">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="ss-hero-pill muted">
                        <i class="fas fa-calendar-alt"></i>
                        Depuis {{ $secretaire->created_at->format('M Y') }}
                    </span>
                    @if($secretaire->last_login_at)
                    <span class="ss-hero-pill muted">
                        <i class="fas fa-clock"></i>
                        {{ \Carbon\Carbon::parse($secretaire->last_login_at)->diffForHumans() }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="ss-hero-actions">
                <div class="ss-hero-btns">
                    <a href="{{ route('esbtp.secretaires.edit', $secretaire->id) }}" class="ss-hero-btn primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="ss-hero-btn ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="ss-kpi-strip">
            <div class="ss-kpi">
                <i class="fas fa-calendar-check ss-kpi-icon"></i>
                <div>
                    <div class="ss-kpi-val">{{ $secretaire->created_at->diffInDays(now()) }}</div>
                    <div class="ss-kpi-lbl">Jours anciennete</div>
                </div>
            </div>
            <div class="ss-kpi">
                <i class="fas fa-sign-in-alt ss-kpi-icon"></i>
                <div>
                    <div class="ss-kpi-val">{{ $secretaire->last_login_at ? \Carbon\Carbon::parse($secretaire->last_login_at)->format('d/m/Y') : '---' }}</div>
                    <div class="ss-kpi-lbl">Derniere connexion</div>
                </div>
            </div>
            <div class="ss-kpi">
                <i class="fas fa-toggle-on ss-kpi-icon"></i>
                <div>
                    <div class="ss-kpi-val">
                        <span class="ss-badge {{ $secretaire->is_active ? 'success' : 'danger' }}" style="font-size:.72rem;">
                            {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <div class="ss-kpi-lbl">Statut</div>
                </div>
            </div>
            <div class="ss-kpi">
                <i class="fas fa-calendar-plus ss-kpi-icon"></i>
                <div>
                    <div class="ss-kpi-val">{{ $secretaire->created_at->format('d/m/Y') }}</div>
                    <div class="ss-kpi-lbl">Compte cree le</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB BAR
    ============================================================= --}}
    <div class="ss-tabs-wrap">
        <div class="ss-tabs">
            <button class="ss-tab active" data-tab="info" type="button">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="ss-tab" data-tab="activity" type="button">
                <i class="fas fa-history"></i> Activite
            </button>
            <button class="ss-tab" data-tab="account" type="button">
                <i class="fas fa-user-cog"></i> Compte
            </button>
            <button class="ss-tab" data-tab="actions" type="button">
                <i class="fas fa-cog"></i> Actions
            </button>
        </div>
    </div>

    {{-- ============================================================
         CONTENT
    ============================================================= --}}
    <div class="ss-content">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: var(--ss-radius); margin-bottom: 20px;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ---- TAB: Informations --------------------------------- --}}
        <div class="ss-panel active" id="ss-tab-info">
            <div class="ss-grid-2">
                {{-- Informations personnelles --}}
                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon"><i class="fas fa-id-card"></i></div>
                            Informations Personnelles
                        </div>
                    </div>

                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-user"></i> Nom complet</span>
                        <span class="ss-info-value">{{ $secretaire->first_name ?? '' }} {{ $secretaire->last_name ?? $secretaire->name ?? '' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="ss-info-value">{{ $secretaire->email }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-phone"></i> Telephone</span>
                        <span class="ss-info-value">{{ $secretaire->phone ?? $secretaire->telephone ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="ss-info-value">{{ $secretaire->username ?? 'Non defini' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-map-marker-alt"></i> Adresse</span>
                        <span class="ss-info-value">
                            @if($secretaire->address || $secretaire->city || $secretaire->adresse)
                                {{ $secretaire->address ?? $secretaire->adresse ?? '' }}@if(($secretaire->address || $secretaire->adresse) && $secretaire->city), @endif{{ $secretaire->city ?? '' }}
                            @else
                                Non renseignee
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Informations professionnelles --}}
                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon"><i class="fas fa-briefcase"></i></div>
                            Informations Professionnelles
                        </div>
                    </div>

                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-user-shield"></i> Role</span>
                        <span class="ss-info-value">Secretaire academique</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-toggle-on"></i> Statut du compte</span>
                        <span class="ss-info-value">
                            <span class="ss-badge {{ $secretaire->is_active ? 'success' : 'danger' }}">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="ss-info-value">{{ $secretaire->first_login_at ? \Carbon\Carbon::parse($secretaire->first_login_at)->format('d/m/Y H:i') : 'Jamais connecte' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="ss-info-value">{{ $secretaire->last_login_at ? \Carbon\Carbon::parse($secretaire->last_login_at)->format('d/m/Y H:i') : 'Jamais connecte' }}</span>
                    </div>

                    {{-- Contact rapide --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--ss-border);">
                        <div style="font-size: .85rem; font-weight: 700; color: var(--ss-text); margin-bottom: 12px;">
                            Contact rapide
                        </div>
                        <div class="ss-contact-item">
                            <div class="ss-contact-icon"><i class="fas fa-envelope"></i></div>
                            <a href="mailto:{{ $secretaire->email }}">{{ $secretaire->email }}</a>
                        </div>
                        @if($secretaire->phone ?? $secretaire->telephone ?? false)
                        <div class="ss-contact-item">
                            <div class="ss-contact-icon"><i class="fas fa-phone"></i></div>
                            <a href="tel:{{ $secretaire->phone ?? $secretaire->telephone }}">{{ $secretaire->phone ?? $secretaire->telephone }}</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Informations systeme --}}
            <div class="ss-card">
                <div class="ss-card-header">
                    <div class="ss-card-title">
                        <div class="ss-card-title-icon"><i class="fas fa-server"></i></div>
                        Informations Systeme
                    </div>
                </div>
                <div class="ss-grid-2">
                    <div>
                        <div class="ss-info-row">
                            <span class="ss-info-label"><i class="fas fa-hashtag"></i> ID</span>
                            <span class="ss-info-value">#{{ $secretaire->id }}</span>
                        </div>
                        <div class="ss-info-row">
                            <span class="ss-info-label"><i class="fas fa-calendar-plus"></i> Cree le</span>
                            <span class="ss-info-value">{{ $secretaire->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="ss-info-row">
                            <span class="ss-info-label"><i class="fas fa-edit"></i> Modifie le</span>
                            <span class="ss-info-value">{{ $secretaire->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Activite ------------------------------------- --}}
        <div class="ss-panel" id="ss-tab-activity">
            <div class="ss-card">
                <div class="ss-card-header">
                    <div class="ss-card-title">
                        <div class="ss-card-title-icon"><i class="fas fa-history"></i></div>
                        Activites Recentes
                    </div>
                </div>

                @if(isset($activites) && count($activites) > 0)
                    @foreach($activites as $activite)
                    <div class="ss-timeline-item">
                        <div class="ss-timeline-dot"></div>
                        <div>
                            <div class="ss-timeline-date">{{ $activite->created_at->format('d/m/Y') }}</div>
                            <div class="ss-timeline-title">{{ $activite->description }}</div>
                            <div class="ss-timeline-desc">{{ $activite->created_at->format('d/m/Y a H:i') }}</div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="ss-empty">
                        <div class="ss-empty-icon"><i class="fas fa-history"></i></div>
                        <p>Aucune activite recente enregistree</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ---- TAB: Compte --------------------------------------- --}}
        <div class="ss-panel" id="ss-tab-account">
            <div class="ss-grid-2">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon"><i class="fas fa-user-cog"></i></div>
                            Compte utilisateur
                        </div>
                    </div>

                    @if(session('new_password'))
                        <div class="ss-password-alert">
                            <h6><i class="fas fa-check-circle"></i> Mot de passe reinitialise</h6>
                            <p style="margin: 0;">Nouveau mot de passe : <code>{{ session('new_password') }}</code></p>
                            <p class="ss-password-hint"><i class="fas fa-info-circle"></i> Communiquez ces identifiants au secretaire.</p>
                        </div>
                    @endif

                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="ss-info-value">{{ $secretaire->username ?? $secretaire->email }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="ss-info-value">{{ $secretaire->email }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                        <span class="ss-info-value">
                            <span class="ss-badge {{ $secretaire->is_active ? 'success' : 'danger' }}">
                                {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="button" class="ss-action-btn" onclick="ssShowResetPasswordModal()">
                            <i class="fas fa-key"></i>
                            <span>Reinitialiser le mot de passe</span>
                        </button>
                    </div>
                </div>

                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon"><i class="fas fa-shield-alt"></i></div>
                            Securite
                        </div>
                    </div>

                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="ss-info-value">{{ $secretaire->first_login_at ? \Carbon\Carbon::parse($secretaire->first_login_at)->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="ss-info-value">{{ $secretaire->last_login_at ? \Carbon\Carbon::parse($secretaire->last_login_at)->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="ss-info-row">
                        <span class="ss-info-label"><i class="fas fa-calendar"></i> Compte cree le</span>
                        <span class="ss-info-value">{{ $secretaire->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Actions -------------------------------------- --}}
        <div class="ss-panel" id="ss-tab-actions">
            <div class="ss-grid-2">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon"><i class="fas fa-tools"></i></div>
                            Actions Rapides
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="{{ route('esbtp.secretaires.edit', $secretaire->id) }}" class="ss-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Modifier le profil</span>
                        </a>

                        @if($secretaire->is_active)
                        <form action="{{ route('secretaires.toggle-status', $secretaire->id) }}" method="POST"
                              onsubmit="return confirm('Desactiver ce secretaire ?')">
                            @csrf
                            <button type="submit" class="ss-action-btn warning-action">
                                <i class="fas fa-pause-circle"></i>
                                <span>Desactiver le compte</span>
                            </button>
                        </form>
                        @else
                        <form action="{{ route('secretaires.toggle-status', $secretaire->id) }}" method="POST"
                              onsubmit="return confirm('Activer ce secretaire ?')">
                            @csrf
                            <button type="submit" class="ss-action-btn success-action">
                                <i class="fas fa-play-circle"></i>
                                <span>Activer le compte</span>
                            </button>
                        </form>
                        @endif

                        <button type="button" class="ss-action-btn" onclick="ssShowResetPasswordModal()">
                            <i class="fas fa-key"></i>
                            <span>Reinitialiser le mot de passe</span>
                        </button>
                    </div>
                </div>

                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-title-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-exclamation-triangle"></i></div>
                            Zone de danger
                        </div>
                    </div>

                    <p style="font-size: .85rem; color: var(--ss-muted); margin: 0 0 16px;">
                        Cette action est irreversible. Le secretaire et toutes ses donnees associees seront definitivement supprimes.
                    </p>

                    <form action="{{ route('esbtp.secretaires.destroy', $secretaire->id) }}" method="POST"
                          onsubmit="return confirm('Supprimer definitivement ce secretaire ? Cette action est irreversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ss-action-btn danger-action">
                            <i class="fas fa-trash-alt"></i>
                            <span>Supprimer definitivement</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ================================================================
     MODAL: Reset Password
================================================================= --}}
<div class="modal fade" id="ssResetPasswordModal" tabindex="-1" aria-labelledby="ssResetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--ss-blue) 0%, var(--ss-blue-2) 100%); color: white; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="ssResetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Reinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ssResetPasswordForm" method="POST" action="{{ route('esbtp.secretaires.reset-password', ['secretaire' => $secretaire->id]) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    {{-- Alert warning --}}
                    <div style="
                        background: rgba(4,83,203,.05);
                        border-left: 4px solid var(--ss-blue);
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px; height: 40px; border-radius: 50%;
                                background: linear-gradient(135deg, var(--ss-blue), var(--ss-blue-2));
                                display: flex; align-items: center; justify-content: center;
                                color: white; flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: var(--ss-text); font-weight: 600; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: var(--ss-muted); font-size: 0.9rem;">
                                    Cette action va reinitialiser le mot de passe a <strong>"Bonjour@2025"</strong> pour le secretaire
                                    <strong>{{ $secretaire->first_name ?? $secretaire->name ?? '' }} {{ $secretaire->last_name ?? '' }}</strong>. Le secretaire devra changer son mot de passe a la premiere connexion.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Info secretaire --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--ss-text); font-size: 0.9rem;">
                            <i class="fas fa-user me-1" style="color: var(--ss-blue);"></i>
                            Secretaire concerne
                        </label>
                        <div style="
                            background: var(--ss-surface);
                            border: 2px solid var(--ss-border);
                            border-radius: 8px;
                            padding: 0.75rem;
                            font-weight: 500;
                        ">
                            {{ $secretaire->first_name ?? $secretaire->name ?? '' }} {{ $secretaire->last_name ?? '' }} ({{ $secretaire->email }})
                        </div>
                    </div>

                    {{-- Confirmation mot de passe --}}
                    <div id="ssNewPasswordDisplay" style="display: none;" class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--ss-text); font-size: 0.9rem;">
                            <i class="fas fa-check-circle me-1" style="color: var(--ss-success);"></i>
                            Mot de passe reinitialise
                        </label>
                        <div style="
                            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                            border: 2px solid var(--ss-success);
                            border-radius: 8px;
                            padding: 1rem;
                            font-family: monospace;
                            font-size: 1.2rem;
                            font-weight: 700;
                            text-align: center;
                            color: #047857;
                            letter-spacing: 2px;
                        " id="ssNewPasswordValue"></div>
                        <div class="form-text text-center mt-2" style="color: #047857;">
                            <i class="fas fa-info-circle me-1"></i>
                            Communiquez ce mot de passe au secretaire. Il devra le changer a la premiere connexion.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: var(--ss-surface); padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 500;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn" id="ssResetPasswordBtn" style="
                        background: linear-gradient(135deg, var(--ss-blue) 0%, var(--ss-blue-2) 100%);
                        border: none; color: white;
                        padding: 0.65rem 1.5rem; border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(4,83,203,.3);
                    ">
                        <i class="fas fa-key me-1"></i>Reinitialiser a Bonjour@2025
                    </button>
                    <button type="button" class="btn" id="ssCopyPasswordBtn" style="display: none; background: var(--ss-success); color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 600;" onclick="ssCopyPassword()">
                        <i class="fas fa-copy me-1"></i>Copier le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// -- Tab switching -----------------------------------------------
document.querySelectorAll('.ss-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.ss-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ss-panel').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.getElementById('ss-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});

// -- Reset Password Modal ----------------------------------------
function ssShowResetPasswordModal() {
    var modal = new bootstrap.Modal(document.getElementById('ssResetPasswordModal'));
    modal.show();
}

document.getElementById('ssResetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    var submitBtn = document.getElementById('ssResetPasswordBtn');
    var originalBtnText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Reinitialisation...';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('ssNewPasswordValue').textContent = data.password;
            document.getElementById('ssNewPasswordDisplay').style.display = 'block';
            submitBtn.style.display = 'none';
            document.getElementById('ssCopyPasswordBtn').style.display = 'inline-block';
            ssNotify('Mot de passe reinitialise avec succes !', 'success');
        } else {
            ssNotify('Erreur : ' + (data.message || 'Une erreur est survenue'), 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(function(error) {
        ssNotify('Erreur de connexion', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function ssCopyPassword() {
    var password = document.getElementById('ssNewPasswordValue').textContent;
    navigator.clipboard.writeText(password).then(function() {
        ssNotify('Mot de passe copie dans le presse-papiers !', 'success');
    }).catch(function() {
        ssNotify('Erreur lors de la copie', 'danger');
    });
}

function ssNotify(message, type) {
    var colors = { success: '#10b981', danger: '#dc2626', warning: '#d97706', info: '#0453cb' };
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:20px;right:20px;background:' + (colors[type] || colors.info) +
        ';color:white;padding:12px 20px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.15);z-index:9999;font-weight:600;font-size:.9rem;transform:translateX(120%);transition:transform .3s ease;';
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function() { el.style.transform = 'translateX(0)'; }, 50);
    setTimeout(function() {
        el.style.transform = 'translateX(120%)';
        setTimeout(function() { el.remove(); }, 400);
    }, 3000);
}
</script>
@endpush
