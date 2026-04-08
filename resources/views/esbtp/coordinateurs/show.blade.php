@extends('layouts.app')

@section('title', 'Profil Coordinateur — ' . $coordinateur->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   COORDINATEUR SHOW — Premium Design — KLASSCI Design System
   Namespace: cs- (coordinateur-show)
=================================================================== */

/* -- Tokens -------------------------------------------------------- */
:root {
    --cs-blue:      #0453cb;
    --cs-blue-2:    #5e91de;
    --cs-surface:   #f4f7fb;
    --cs-card:      #ffffff;
    --cs-border:    #e2e8f0;
    --cs-text:      #1e293b;
    --cs-muted:     #64748b;
    --cs-success:   #10b981;
    --cs-danger:    #dc2626;
    --cs-radius:    12px;
    --cs-radius-lg: 20px;
    --cs-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --cs-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* -- Page shell ---------------------------------------------------- */
.cs-page { background: var(--cs-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.cs-hero {
    position: relative;
    background: linear-gradient(135deg, var(--cs-blue) 0%, var(--cs-blue-2) 100%);
    padding: 0;
}
.cs-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
}
.cs-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--cs-surface) 0%, transparent 100%);
}

.cs-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar */
.cs-hero-avatar {
    position: relative; flex-shrink: 0;
}
.cs-hero-avatar-circle {
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
.cs-hero-avatar-circle img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.cs-hero-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.cs-hero-status.active   { background: #10b981; }
.cs-hero-status.inactive { background: #94a3b8; }

/* Text block */
.cs-hero-text { flex: 1; min-width: 200px; color: #fff; }
.cs-hero-name {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em;
    margin: 0 0 3px; line-height: 1.2;
}
.cs-hero-sub { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.cs-hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.cs-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.cs-hero-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.cs-hero-pill.muted { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); }

/* Actions in hero */
.cs-hero-actions {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; margin-left: auto;
}
.cs-hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.cs-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.cs-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--cs-blue); }
.cs-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.cs-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.cs-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- KPI Strip (inside hero) --------------------------------------- */
.cs-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 8px;
}
.cs-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.cs-kpi:last-child { border-right: none; }
.cs-kpi-icon { font-size: 1rem; opacity: .7; }
.cs-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.cs-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* -- Tab Bar ------------------------------------------------------- */
.cs-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--cs-card);
    box-shadow: 0 1px 0 var(--cs-border);
}
.cs-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.cs-tabs::-webkit-scrollbar { display: none; }
.cs-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--cs-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.cs-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--cs-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.cs-tab:hover { color: var(--cs-blue); }
.cs-tab.active { color: var(--cs-blue); }
.cs-tab.active::after { transform: scaleX(1); }

/* -- Content area -------------------------------------------------- */
.cs-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.cs-panel { display: none; animation: csFadeUp .24s ease; }
.cs-panel.active { display: block; }
@keyframes csFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* -- Section card -------------------------------------------------- */
.cs-card {
    background: var(--cs-card); border: 1px solid var(--cs-border);
    border-radius: var(--cs-radius-lg); padding: 24px;
    box-shadow: var(--cs-shadow); margin-bottom: 20px;
}
.cs-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.cs-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--cs-text);
}
.cs-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--cs-blue) 0%, var(--cs-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* -- Info rows ----------------------------------------------------- */
.cs-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--cs-border);
}
.cs-info-row:last-child { border-bottom: none; }
.cs-info-label {
    font-size: .85rem; font-weight: 500; color: var(--cs-muted);
    display: flex; align-items: center; gap: 8px;
}
.cs-info-label i { font-size: .75rem; width: 16px; text-align: center; color: var(--cs-blue); opacity: .6; }
.cs-info-value { font-size: .9rem; font-weight: 600; color: var(--cs-text); }

/* -- Status badge -------------------------------------------------- */
.cs-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .03em;
}
.cs-badge.success { background: rgba(16,185,129,.1); color: #059669; }
.cs-badge.danger  { background: rgba(220,38,38,.1);  color: #dc2626; }
.cs-badge.muted   { background: rgba(100,116,139,.1); color: #64748b; }

/* -- Grid layout for two-column panels ----------------------------- */
.cs-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}

/* -- Action buttons inside cards ----------------------------------- */
.cs-action-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 14px; border-radius: 10px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid var(--cs-border);
    background: var(--cs-card); color: var(--cs-text);
    transition: all .18s;
}
.cs-action-btn:hover {
    border-color: var(--cs-blue); color: var(--cs-blue);
    box-shadow: 0 2px 8px rgba(4,83,203,.1);
    transform: translateY(-1px);
}
.cs-action-btn i { width: 18px; text-align: center; }
.cs-action-btn.danger-action { color: var(--cs-danger); border-color: rgba(220,38,38,.2); }
.cs-action-btn.danger-action:hover {
    border-color: var(--cs-danger); background: rgba(220,38,38,.03);
    box-shadow: 0 2px 8px rgba(220,38,38,.1);
}
.cs-action-btn.warning-action { color: #d97706; border-color: rgba(217,119,6,.2); }
.cs-action-btn.warning-action:hover {
    border-color: #d97706; background: rgba(217,119,6,.03);
    box-shadow: 0 2px 8px rgba(217,119,6,.1);
}
.cs-action-btn.success-action { color: #059669; border-color: rgba(5,150,105,.2); }
.cs-action-btn.success-action:hover {
    border-color: #059669; background: rgba(5,150,105,.03);
    box-shadow: 0 2px 8px rgba(5,150,105,.1);
}

/* -- Empty state --------------------------------------------------- */
.cs-empty {
    text-align: center; padding: 40px 20px; color: var(--cs-muted);
}
.cs-empty-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: var(--cs-surface);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: var(--cs-muted); margin-bottom: 12px;
}
.cs-empty p { font-size: .9rem; margin: 0; }

/* -- Activity timeline --------------------------------------------- */
.cs-timeline-item {
    display: flex; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid var(--cs-border);
}
.cs-timeline-item:last-child { border-bottom: none; }
.cs-timeline-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--cs-blue); margin-top: 6px; flex-shrink: 0;
}
.cs-timeline-date { font-size: .78rem; color: var(--cs-muted); font-weight: 500; }
.cs-timeline-title { font-size: .88rem; font-weight: 600; color: var(--cs-text); margin-top: 2px; }
.cs-timeline-desc { font-size: .82rem; color: var(--cs-muted); margin-top: 2px; }

/* -- Contact card items -------------------------------------------- */
.cs-contact-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--cs-border);
}
.cs-contact-item:last-child { border-bottom: none; }
.cs-contact-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(4,83,203,.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--cs-blue); font-size: .85rem; flex-shrink: 0;
}
.cs-contact-item a {
    color: var(--cs-text); text-decoration: none; font-weight: 500; font-size: .88rem;
    transition: color .15s;
}
.cs-contact-item a:hover { color: var(--cs-blue); }

/* -- Password alert ------------------------------------------------ */
.cs-password-alert {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(16,185,129,.3);
    border-radius: var(--cs-radius); padding: 16px;
    margin-bottom: 16px;
}
.cs-password-alert h6 { color: #047857; font-size: .9rem; font-weight: 700; margin: 0 0 6px; }
.cs-password-alert code { background: rgba(0,0,0,.06); padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #065f46; }
.cs-password-alert .cs-password-hint { font-size: .8rem; color: #047857; margin: 8px 0 0; }

/* -- Responsive ---------------------------------------------------- */
@media (max-width: 1024px) {
    .cs-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .cs-hero-inner { padding: 24px 16px 0; flex-direction: column; text-align: center; }
    .cs-hero-pills { justify-content: center; }
    .cs-hero-actions { margin-left: 0; align-items: center; }
    .cs-kpi-strip { flex-wrap: wrap; }
    .cs-kpi { flex: 1 1 50%; border-bottom: 1px solid rgba(255,255,255,.1); }
    .cs-content { padding: 20px 16px 40px; }
    .cs-tabs { padding: 0 12px; }
    .cs-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>
@endsection

@section('content')
<div class="cs-page">

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="cs-hero">
        <div class="cs-hero-inner">
            {{-- Avatar --}}
            <div class="cs-hero-avatar">
                <div class="cs-hero-avatar-circle">
                    @if($coordinateur->photo_url ?? false)
                        <img src="{{ $coordinateur->photo_url }}" alt="{{ $coordinateur->name }}">
                    @else
                        {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                    @endif
                </div>
                <div class="cs-hero-status {{ $coordinateur->is_active ? 'active' : 'inactive' }}"></div>
            </div>

            {{-- Info --}}
            <div class="cs-hero-text">
                <h1 class="cs-hero-name">{{ $coordinateur->name }}</h1>
                <p class="cs-hero-sub">{{ $coordinateur->specialite ?? 'Coordinateur pedagogique' }}</p>
                <div class="cs-hero-pills">
                    <span class="cs-hero-pill">
                        <i class="fas fa-user-tie"></i> Coordinateur
                    </span>
                    <span class="cs-hero-pill {{ $coordinateur->is_active ? 'green' : '' }}">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="cs-hero-pill muted">
                        <i class="fas fa-calendar-alt"></i>
                        Depuis {{ $coordinateur->created_at->format('M Y') }}
                    </span>
                    @if($coordinateur->last_login_at)
                    <span class="cs-hero-pill muted">
                        <i class="fas fa-clock"></i>
                        {{ $coordinateur->last_login_at->diffForHumans() }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="cs-hero-actions">
                <div class="cs-hero-btns">
                    <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="cs-hero-btn primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="cs-hero-btn ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="cs-kpi-strip">
            <div class="cs-kpi">
                <i class="fas fa-chalkboard cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $statistiques['nb_classes_gerees'] ?? 0 }}</div>
                    <div class="cs-kpi-lbl">Classes</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-users cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $statistiques['nb_enseignants_supervises'] ?? 0 }}</div>
                    <div class="cs-kpi-lbl">Enseignants</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-calendar-check cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $coordinateur->created_at->diffInDays(now()) }}</div>
                    <div class="cs-kpi-lbl">Jours anciennete</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-sign-in-alt cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $coordinateur->last_login_at ? $coordinateur->last_login_at->format('d/m/Y') : '---' }}</div>
                    <div class="cs-kpi-lbl">Derniere connexion</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB BAR
    ============================================================= --}}
    <div class="cs-tabs-wrap">
        <div class="cs-tabs">
            <button class="cs-tab active" data-tab="info" type="button">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="cs-tab" data-tab="activity" type="button">
                <i class="fas fa-history"></i> Activite
            </button>
            <button class="cs-tab" data-tab="account" type="button">
                <i class="fas fa-user-cog"></i> Compte
            </button>
            <button class="cs-tab" data-tab="actions" type="button">
                <i class="fas fa-cog"></i> Actions
            </button>
        </div>
    </div>

    {{-- ============================================================
         CONTENT
    ============================================================= --}}
    <div class="cs-content">

        {{-- ---- TAB: Informations --------------------------------- --}}
        <div class="cs-panel active" id="cs-tab-info">
            <div class="cs-grid-2">
                {{-- Informations personnelles --}}
                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon"><i class="fas fa-id-card"></i></div>
                            Informations Personnelles
                        </div>
                    </div>

                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-user"></i> Nom complet</span>
                        <span class="cs-info-value">{{ $coordinateur->name }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="cs-info-value">{{ $coordinateur->email }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-phone"></i> Telephone</span>
                        <span class="cs-info-value">{{ $coordinateur->phone ?? $coordinateur->telephone ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="cs-info-value">{{ $coordinateur->username }}</span>
                    </div>
                    @if($coordinateur->specialite)
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-graduation-cap"></i> Specialite</span>
                        <span class="cs-info-value">{{ $coordinateur->specialite }}</span>
                    </div>
                    @endif
                    @if($coordinateur->date_naissance)
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-birthday-cake"></i> Date de naissance</span>
                        <span class="cs-info-value">{{ \Carbon\Carbon::parse($coordinateur->date_naissance)->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($coordinateur->adresse)
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-map-marker-alt"></i> Adresse</span>
                        <span class="cs-info-value">{{ $coordinateur->adresse }}</span>
                    </div>
                    @endif
                </div>

                {{-- Informations professionnelles --}}
                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon"><i class="fas fa-briefcase"></i></div>
                            Informations Professionnelles
                        </div>
                    </div>

                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-user-tie"></i> Role</span>
                        <span class="cs-info-value">Coordinateur pedagogique</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-toggle-on"></i> Statut du compte</span>
                        <span class="cs-info-value">
                            <span class="cs-badge {{ $coordinateur->is_active ? 'success' : 'danger' }}">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="cs-info-value">{{ $coordinateur->first_login_at ? $coordinateur->first_login_at->format('d/m/Y H:i') : 'Jamais connecte' }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="cs-info-value">{{ $coordinateur->last_login_at ? $coordinateur->last_login_at->format('d/m/Y H:i') : 'Jamais connecte' }}</span>
                    </div>

                    {{-- Contact rapide --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--cs-border);">
                        <div style="font-size: .85rem; font-weight: 700; color: var(--cs-text); margin-bottom: 12px;">
                            Contact rapide
                        </div>
                        <div class="cs-contact-item">
                            <div class="cs-contact-icon"><i class="fas fa-envelope"></i></div>
                            <a href="mailto:{{ $coordinateur->email }}">{{ $coordinateur->email }}</a>
                        </div>
                        @if($coordinateur->phone ?? $coordinateur->telephone ?? false)
                        <div class="cs-contact-item">
                            <div class="cs-contact-icon"><i class="fas fa-phone"></i></div>
                            <a href="tel:{{ $coordinateur->phone ?? $coordinateur->telephone }}">{{ $coordinateur->phone ?? $coordinateur->telephone }}</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Informations systeme --}}
            <div class="cs-card">
                <div class="cs-card-header">
                    <div class="cs-card-title">
                        <div class="cs-card-title-icon"><i class="fas fa-server"></i></div>
                        Informations Systeme
                    </div>
                </div>
                <div class="cs-grid-2">
                    <div>
                        <div class="cs-info-row">
                            <span class="cs-info-label"><i class="fas fa-hashtag"></i> ID</span>
                            <span class="cs-info-value">#{{ $coordinateur->id }}</span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label"><i class="fas fa-calendar-plus"></i> Cree le</span>
                            <span class="cs-info-value">{{ $coordinateur->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="cs-info-row">
                            <span class="cs-info-label"><i class="fas fa-edit"></i> Modifie le</span>
                            <span class="cs-info-value">{{ $coordinateur->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($coordinateur->created_by)
                        <div class="cs-info-row">
                            <span class="cs-info-label"><i class="fas fa-user-shield"></i> Cree par</span>
                            <span class="cs-info-value">{{ $coordinateur->createdBy->name ?? 'N/A' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Activite ------------------------------------- --}}
        <div class="cs-panel" id="cs-tab-activity">
            <div class="cs-card">
                <div class="cs-card-header">
                    <div class="cs-card-title">
                        <div class="cs-card-title-icon"><i class="fas fa-history"></i></div>
                        Activites Recentes
                    </div>
                </div>

                @if(isset($recentActivities) && $recentActivities->count() > 0)
                    @foreach($recentActivities as $activity)
                    <div class="cs-timeline-item">
                        <div class="cs-timeline-dot"></div>
                        <div>
                            <div class="cs-timeline-date">{{ $activity->created_at->format('d/m/Y') }}</div>
                            <div class="cs-timeline-title">{{ $activity->title }}</div>
                            <div class="cs-timeline-desc">{{ $activity->description }}</div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="cs-empty">
                        <div class="cs-empty-icon"><i class="fas fa-history"></i></div>
                        <p>Aucune activite recente enregistree</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ---- TAB: Compte --------------------------------------- --}}
        <div class="cs-panel" id="cs-tab-account">
            <div class="cs-grid-2">
                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon"><i class="fas fa-user-cog"></i></div>
                            Compte utilisateur
                        </div>
                    </div>

                    @if(session('new_password'))
                        <div class="cs-password-alert">
                            <h6><i class="fas fa-check-circle"></i> Mot de passe reinitialise</h6>
                            <p style="margin: 0;">Nouveau mot de passe : <code>{{ session('new_password') }}</code></p>
                            <p class="cs-password-hint"><i class="fas fa-info-circle"></i> Communiquez ces identifiants au coordinateur.</p>
                        </div>
                    @endif

                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="cs-info-value">{{ $coordinateur->username }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="cs-info-value">{{ $coordinateur->email }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                        <span class="cs-info-value">
                            <span class="cs-badge {{ $coordinateur->is_active ? 'success' : 'danger' }}">
                                {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="button" class="cs-action-btn" onclick="showResetPasswordModal()">
                            <i class="fas fa-key"></i>
                            <span>Reinitialiser le mot de passe</span>
                        </button>
                    </div>
                </div>

                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon"><i class="fas fa-shield-alt"></i></div>
                            Securite
                        </div>
                    </div>

                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="cs-info-value">{{ $coordinateur->first_login_at ? $coordinateur->first_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="cs-info-value">{{ $coordinateur->last_login_at ? $coordinateur->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label"><i class="fas fa-calendar"></i> Compte cree le</span>
                        <span class="cs-info-value">{{ $coordinateur->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Actions -------------------------------------- --}}
        <div class="cs-panel" id="cs-tab-actions">
            <div class="cs-grid-2">
                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon"><i class="fas fa-tools"></i></div>
                            Actions Rapides
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="cs-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Modifier le profil</span>
                        </a>

                        @if($coordinateur->is_active)
                        <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST"
                              onsubmit="return confirm('Desactiver ce coordinateur ?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="cs-action-btn warning-action">
                                <i class="fas fa-pause-circle"></i>
                                <span>Desactiver le compte</span>
                            </button>
                        </form>
                        @else
                        <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST"
                              onsubmit="return confirm('Activer ce coordinateur ?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="cs-action-btn success-action">
                                <i class="fas fa-play-circle"></i>
                                <span>Activer le compte</span>
                            </button>
                        </form>
                        @endif

                        <button type="button" class="cs-action-btn" onclick="showResetPasswordModal()">
                            <i class="fas fa-key"></i>
                            <span>Reinitialiser le mot de passe</span>
                        </button>
                    </div>
                </div>

                <div class="cs-card">
                    <div class="cs-card-header">
                        <div class="cs-card-title">
                            <div class="cs-card-title-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-exclamation-triangle"></i></div>
                            Zone de danger
                        </div>
                    </div>

                    <p style="font-size: .85rem; color: var(--cs-muted); margin: 0 0 16px;">
                        Cette action est irreversible. Le coordinateur et toutes ses donnees associees seront definitivement supprimes.
                    </p>

                    <form action="{{ route('esbtp.coordinateurs.destroy', $coordinateur) }}" method="POST"
                          onsubmit="return confirm('Supprimer definitivement ce coordinateur ? Cette action est irreversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cs-action-btn danger-action">
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
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--cs-blue) 0%, var(--cs-blue-2) 100%); color: white; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Reinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ route('esbtp.coordinateurs.reset-password', ['coordinateur' => $coordinateur->id]) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    {{-- Alert warning --}}
                    <div style="
                        background: rgba(4,83,203,.05);
                        border-left: 4px solid var(--cs-blue);
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px; height: 40px; border-radius: 50%;
                                background: linear-gradient(135deg, var(--cs-blue), var(--cs-blue-2));
                                display: flex; align-items: center; justify-content: center;
                                color: white; flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: var(--cs-text); font-weight: 600; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: var(--cs-muted); font-size: 0.9rem;">
                                    Cette action va reinitialiser le mot de passe a <strong>"Bonjour@2025"</strong> pour le coordinateur
                                    <strong>{{ $coordinateur->name }}</strong>. Le coordinateur devra changer son mot de passe a la premiere connexion.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Info coordinateur --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--cs-text); font-size: 0.9rem;">
                            <i class="fas fa-user me-1" style="color: var(--cs-blue);"></i>
                            Coordinateur concerne
                        </label>
                        <div style="
                            background: var(--cs-surface);
                            border: 2px solid var(--cs-border);
                            border-radius: 8px;
                            padding: 0.75rem;
                            font-weight: 500;
                        ">
                            {{ $coordinateur->name }} ({{ $coordinateur->email }})
                        </div>
                    </div>

                    {{-- Confirmation mot de passe --}}
                    <div id="newPasswordDisplay" style="display: none;" class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--cs-text); font-size: 0.9rem;">
                            <i class="fas fa-check-circle me-1" style="color: var(--cs-success);"></i>
                            Mot de passe reinitialise
                        </label>
                        <div style="
                            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                            border: 2px solid var(--cs-success);
                            border-radius: 8px;
                            padding: 1rem;
                            font-family: monospace;
                            font-size: 1.2rem;
                            font-weight: 700;
                            text-align: center;
                            color: #047857;
                            letter-spacing: 2px;
                        " id="newPasswordValue"></div>
                        <div class="form-text text-center mt-2" style="color: #047857;">
                            <i class="fas fa-info-circle me-1"></i>
                            Communiquez ce mot de passe au coordinateur. Il devra le changer a la premiere connexion.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: var(--cs-surface); padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 500;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn" id="resetPasswordBtn" style="
                        background: linear-gradient(135deg, var(--cs-blue) 0%, var(--cs-blue-2) 100%);
                        border: none; color: white;
                        padding: 0.65rem 1.5rem; border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(4,83,203,.3);
                    ">
                        <i class="fas fa-key me-1"></i>Reinitialiser a Bonjour@2025
                    </button>
                    <button type="button" class="btn" id="copyPasswordBtn" style="display: none; background: var(--cs-success); color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 600;" onclick="copyPassword()">
                        <i class="fas fa-copy me-1"></i>Copier le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// -- Tab switching -----------------------------------------------
document.querySelectorAll('.cs-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        // Remove active from all tabs and panels
        document.querySelectorAll('.cs-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.cs-panel').forEach(function(p) { p.classList.remove('active'); });
        // Activate clicked tab + matching panel
        this.classList.add('active');
        var panel = document.getElementById('cs-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});

// -- Reset Password Modal ----------------------------------------
function showResetPasswordModal() {
    var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    var submitBtn = document.getElementById('resetPasswordBtn');
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
            document.getElementById('newPasswordValue').textContent = data.password;
            document.getElementById('newPasswordDisplay').style.display = 'block';
            submitBtn.style.display = 'none';
            document.getElementById('copyPasswordBtn').style.display = 'inline-block';
            csNotify('Mot de passe reinitialise avec succes !', 'success');
        } else {
            csNotify('Erreur : ' + (data.message || 'Une erreur est survenue'), 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(function(error) {
        csNotify('Erreur de connexion', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function copyPassword() {
    var password = document.getElementById('newPasswordValue').textContent;
    navigator.clipboard.writeText(password).then(function() {
        csNotify('Mot de passe copie dans le presse-papiers !', 'success');
    }).catch(function() {
        csNotify('Erreur lors de la copie', 'danger');
    });
}

function csNotify(message, type) {
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
    }, 4000);
}

// Reset modal state on close
document.getElementById('resetPasswordModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('newPasswordDisplay').style.display = 'none';
    document.getElementById('newPasswordValue').textContent = '';
    document.getElementById('resetPasswordBtn').style.display = 'inline-block';
    document.getElementById('resetPasswordBtn').disabled = false;
    document.getElementById('resetPasswordBtn').innerHTML = '<i class="fas fa-key me-1"></i>Reinitialiser a Bonjour@2025';
    document.getElementById('copyPasswordBtn').style.display = 'none';
});
</script>
@endsection
