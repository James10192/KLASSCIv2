@extends('layouts.app')

@section('title', 'Profil Enseignant — ' . $teacher->user->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   TEACHER SHOW — Premium Design — KLASSCI Design System
   Namespace: ts- (teacher-show)
=================================================================== */

/* -- Tokens -------------------------------------------------------- */
:root {
    --ts-blue:      #0453cb;
    --ts-blue-2:    #5e91de;
    --ts-surface:   #f4f7fb;
    --ts-card:      #ffffff;
    --ts-border:    #e2e8f0;
    --ts-text:      #1e293b;
    --ts-muted:     #64748b;
    --ts-success:   #10b981;
    --ts-danger:    #dc2626;
    --ts-radius:    12px;
    --ts-radius-lg: 20px;
    --ts-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --ts-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* -- Page shell ---------------------------------------------------- */
.ts-page { background: var(--ts-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.ts-hero {
    position: relative;
    background: linear-gradient(135deg, var(--ts-blue) 0%, var(--ts-blue-2) 100%);
    padding: 0;
}
.ts-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
}
.ts-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--ts-surface) 0%, transparent 100%);
}

.ts-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar */
.ts-hero-avatar {
    position: relative; flex-shrink: 0;
}
.ts-hero-avatar-circle {
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
.ts-hero-avatar-circle img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.ts-hero-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.ts-hero-status.active   { background: #10b981; }
.ts-hero-status.inactive { background: #94a3b8; }

/* Text block */
.ts-hero-text { flex: 1; min-width: 200px; color: #fff; }
.ts-hero-name {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em;
    margin: 0 0 3px; line-height: 1.2;
}
.ts-hero-sub { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.ts-hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.ts-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.ts-hero-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.ts-hero-pill.muted { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); }

/* Actions in hero */
.ts-hero-actions {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; margin-left: auto;
}
.ts-hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.ts-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.ts-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--ts-blue); }
.ts-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.ts-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.ts-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- KPI Strip (inside hero) --------------------------------------- */
.ts-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 8px;
}
.ts-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.ts-kpi:last-child { border-right: none; }
.ts-kpi-icon { font-size: 1rem; opacity: .7; }
.ts-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.ts-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* Progress bar in KPI */
.ts-kpi-progress {
    width: 100%; height: 4px; background: rgba(255,255,255,.2);
    border-radius: 2px; margin-top: 6px; overflow: hidden;
}
.ts-kpi-progress-bar {
    height: 100%; border-radius: 2px;
    background: rgba(255,255,255,.85);
    transition: width .4s ease;
}

/* -- Tab Bar ------------------------------------------------------- */
.ts-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--ts-card);
    box-shadow: 0 1px 0 var(--ts-border);
}
.ts-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.ts-tabs::-webkit-scrollbar { display: none; }
.ts-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--ts-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.ts-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--ts-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.ts-tab:hover { color: var(--ts-blue); }
.ts-tab.active { color: var(--ts-blue); }
.ts-tab.active::after { transform: scaleX(1); }

/* -- Content area -------------------------------------------------- */
.ts-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.ts-panel { display: none; animation: tsFadeUp .24s ease; }
.ts-panel.active { display: block; }
@keyframes tsFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* -- Section card -------------------------------------------------- */
.ts-card {
    background: var(--ts-card); border: 1px solid var(--ts-border);
    border-radius: var(--ts-radius-lg); padding: 24px;
    box-shadow: var(--ts-shadow); margin-bottom: 20px;
}
.ts-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.ts-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--ts-text);
}
.ts-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--ts-blue) 0%, var(--ts-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* -- Info rows ----------------------------------------------------- */
.ts-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--ts-border);
}
.ts-info-row:last-child { border-bottom: none; }
.ts-info-label {
    font-size: .85rem; font-weight: 500; color: var(--ts-muted);
    display: flex; align-items: center; gap: 8px;
}
.ts-info-label i { font-size: .75rem; width: 16px; text-align: center; color: var(--ts-blue); opacity: .6; }
.ts-info-value { font-size: .9rem; font-weight: 600; color: var(--ts-text); }

/* -- Status badge -------------------------------------------------- */
.ts-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .03em;
}
.ts-badge.success { background: rgba(16,185,129,.1); color: #059669; }
.ts-badge.danger  { background: rgba(220,38,38,.1);  color: #dc2626; }
.ts-badge.muted   { background: rgba(100,116,139,.1); color: #64748b; }

/* -- Grid layout for two-column panels ----------------------------- */
.ts-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}

/* -- Tag chips ----------------------------------------------------- */
.ts-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .78rem; font-weight: 600;
    background: rgba(4,83,203,.08); color: var(--ts-blue);
    border: 1px solid rgba(4,83,203,.12);
}
.ts-chip.teal {
    background: rgba(16,185,129,.08); color: #059669;
    border-color: rgba(16,185,129,.15);
}

/* -- Action buttons inside cards ----------------------------------- */
.ts-action-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 14px; border-radius: 10px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid var(--ts-border);
    background: var(--ts-card); color: var(--ts-text);
    transition: all .18s;
}
.ts-action-btn:hover {
    border-color: var(--ts-blue); color: var(--ts-blue);
    box-shadow: 0 2px 8px rgba(4,83,203,.1);
    transform: translateY(-1px);
}
.ts-action-btn i { width: 18px; text-align: center; }
.ts-action-btn.danger-action { color: var(--ts-danger); border-color: rgba(220,38,38,.2); }
.ts-action-btn.danger-action:hover {
    border-color: var(--ts-danger); background: rgba(220,38,38,.03);
    box-shadow: 0 2px 8px rgba(220,38,38,.1);
}

/* -- Empty state --------------------------------------------------- */
.ts-empty {
    text-align: center; padding: 40px 20px; color: var(--ts-muted);
}
.ts-empty-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: var(--ts-surface);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: var(--ts-muted); margin-bottom: 12px;
}
.ts-empty p { font-size: .9rem; margin: 0; }

/* -- Bio block ----------------------------------------------------- */
.ts-bio {
    font-size: .9rem; line-height: 1.7; color: var(--ts-text);
    padding: 16px 0 0;
}

/* -- Contact card items -------------------------------------------- */
.ts-contact-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--ts-border);
}
.ts-contact-item:last-child { border-bottom: none; }
.ts-contact-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(4,83,203,.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--ts-blue); font-size: .85rem; flex-shrink: 0;
}
.ts-contact-item a {
    color: var(--ts-text); text-decoration: none; font-weight: 500; font-size: .88rem;
    transition: color .15s;
}
.ts-contact-item a:hover { color: var(--ts-blue); }

/* -- Responsive ---------------------------------------------------- */
@media (max-width: 1024px) {
    .ts-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .ts-hero-inner { padding: 24px 16px 0; flex-direction: column; text-align: center; }
    .ts-hero-pills { justify-content: center; }
    .ts-hero-actions { margin-left: 0; align-items: center; }
    .ts-kpi-strip { flex-wrap: wrap; }
    .ts-kpi { flex: 1 1 50%; border-bottom: 1px solid rgba(255,255,255,.1); }
    .ts-content { padding: 20px 16px 40px; }
    .ts-tabs { padding: 0 12px; }
    .ts-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>
@endsection

@section('content')
<div class="ts-page">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 16px 32px 0; border-radius: 12px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 16px 32px 0; border-radius: 12px;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="ts-hero">
        <div class="ts-hero-inner">
            {{-- Avatar --}}
            <div class="ts-hero-avatar">
                <div class="ts-hero-avatar-circle">
                    {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
                </div>
                <div class="ts-hero-status {{ $teacher->user->is_active ? 'active' : 'inactive' }}"></div>
            </div>

            {{-- Info --}}
            <div class="ts-hero-text">
                <h1 class="ts-hero-name">{{ $teacher->user->name }}</h1>
                <p class="ts-hero-sub">{{ $teacher->grade ?? $teacher->specialization ?? 'Enseignant' }}</p>
                <div class="ts-hero-pills">
                    <span class="ts-hero-pill">
                        <i class="fas fa-chalkboard-teacher"></i> Enseignant
                    </span>
                    @if($teacher->status)
                    <span class="ts-hero-pill muted">
                        <i class="fas fa-tag"></i>
                        {{ ucfirst($teacher->status) }}
                    </span>
                    @endif
                    <span class="ts-hero-pill {{ $teacher->user->is_active ? 'green' : '' }}">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        {{ $teacher->user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    @if($teacher->employee_id)
                    <span class="ts-hero-pill muted">
                        <i class="fas fa-id-badge"></i>
                        {{ $teacher->employee_id }}
                    </span>
                    @endif
                    <span class="ts-hero-pill muted">
                        <i class="fas fa-calendar-alt"></i>
                        Depuis {{ $teacher->created_at->format('M Y') }}
                    </span>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="ts-hero-actions">
                <div class="ts-hero-btns">
                    <a href="{{ route('esbtp.teachers.edit', $teacher->id) }}" class="ts-hero-btn primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="{{ route('esbtp.teachers.index') }}" class="ts-hero-btn ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Strip --}}
        @php
            $hoursDue = $teacher->teaching_hours_due ?? 0;
            $hoursDone = $teacher->teaching_hours_done ?? 0;
            $hoursPercent = $hoursDue > 0 ? min(100, round(($hoursDone / $hoursDue) * 100)) : 0;
        @endphp
        <div class="ts-kpi-strip">
            <div class="ts-kpi">
                <i class="fas fa-clock ts-kpi-icon"></i>
                <div style="flex:1">
                    <div class="ts-kpi-val">{{ $hoursDone }}h / {{ $hoursDue }}h</div>
                    <div class="ts-kpi-lbl">Heures enseignement ({{ $hoursPercent }}%)</div>
                    <div class="ts-kpi-progress">
                        <div class="ts-kpi-progress-bar" style="width: {{ $hoursPercent }}%"></div>
                    </div>
                </div>
            </div>
            <div class="ts-kpi">
                <i class="fas fa-building ts-kpi-icon"></i>
                <div>
                    <div class="ts-kpi-val">{{ $teacher->department->name ?? '---' }}</div>
                    <div class="ts-kpi-lbl">Departement</div>
                </div>
            </div>
            <div class="ts-kpi">
                <i class="fas fa-award ts-kpi-icon"></i>
                <div>
                    <div class="ts-kpi-val">{{ $teacher->grade ?? '---' }}</div>
                    <div class="ts-kpi-lbl">Grade</div>
                </div>
            </div>
            <div class="ts-kpi">
                <i class="fas fa-toggle-on ts-kpi-icon"></i>
                <div>
                    <div class="ts-kpi-val">
                        @if($teacher->user->is_active)
                            <span style="color: #10b981;">Actif</span>
                        @else
                            <span style="color: #94a3b8;">Inactif</span>
                        @endif
                    </div>
                    <div class="ts-kpi-lbl">Statut compte</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB BAR
    ============================================================= --}}
    <div class="ts-tabs-wrap">
        <div class="ts-tabs">
            <button class="ts-tab active" data-tab="info" type="button">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="ts-tab" data-tab="teaching" type="button">
                <i class="fas fa-chalkboard"></i> Enseignement
            </button>
            <button class="ts-tab" data-tab="system" type="button">
                <i class="fas fa-server"></i> Systeme
            </button>
            <button class="ts-tab" data-tab="actions" type="button">
                <i class="fas fa-cog"></i> Actions
            </button>
        </div>
    </div>

    {{-- ============================================================
         CONTENT
    ============================================================= --}}
    <div class="ts-content">

        {{-- ---- TAB: Informations --------------------------------- --}}
        <div class="ts-panel active" id="ts-tab-info">
            <div class="ts-grid-2">
                {{-- Informations personnelles --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon"><i class="fas fa-id-card"></i></div>
                            Informations Personnelles
                        </div>
                    </div>

                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-user"></i> Nom complet</span>
                        <span class="ts-info-value">{{ $teacher->user->name }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="ts-info-value">{{ $teacher->user->email }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                        <span class="ts-info-value">{{ $teacher->user->username }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-phone"></i> Telephone</span>
                        <span class="ts-info-value">{{ $teacher->user->phone ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-toggle-on"></i> Statut du compte</span>
                        <span class="ts-info-value">
                            <span class="ts-badge {{ $teacher->user->is_active ? 'success' : 'danger' }}">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                {{ $teacher->user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>

                    {{-- Contact rapide --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--ts-border);">
                        <div style="font-size: .85rem; font-weight: 700; color: var(--ts-text); margin-bottom: 12px;">
                            Contact rapide
                        </div>
                        <div class="ts-contact-item">
                            <div class="ts-contact-icon"><i class="fas fa-envelope"></i></div>
                            <a href="mailto:{{ $teacher->user->email }}">{{ $teacher->user->email }}</a>
                        </div>
                        @if($teacher->user->phone)
                        <div class="ts-contact-item">
                            <div class="ts-contact-icon"><i class="fas fa-phone"></i></div>
                            <a href="tel:{{ $teacher->user->phone }}">{{ $teacher->user->phone }}</a>
                        </div>
                        @endif
                        @if($teacher->website)
                        <div class="ts-contact-item">
                            <div class="ts-contact-icon"><i class="fas fa-globe"></i></div>
                            <a href="{{ $teacher->website }}" target="_blank">{{ $teacher->website }}</a>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Informations professionnelles --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon"><i class="fas fa-briefcase"></i></div>
                            Informations Professionnelles
                        </div>
                    </div>

                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-id-badge"></i> Numero d'employe</span>
                        <span class="ts-info-value">{{ $teacher->employee_id ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-building"></i> Departement</span>
                        <span class="ts-info-value">{{ $teacher->department->name ?? 'Non affecte' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-flask"></i> Laboratoire</span>
                        <span class="ts-info-value">{{ $teacher->laboratory->name ?? 'Non affecte' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-award"></i> Grade</span>
                        <span class="ts-info-value">{{ $teacher->grade ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-tag"></i> Statut</span>
                        <span class="ts-info-value">
                            @if($teacher->status)
                                <span class="ts-badge muted">{{ ucfirst($teacher->status) }}</span>
                            @else
                                Non renseigne
                            @endif
                        </span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-map-marker-alt"></i> Bureau</span>
                        <span class="ts-info-value">{{ $teacher->office_location ?? 'Non renseigne' }}</span>
                    </div>
                    @if($teacher->matricule)
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-barcode"></i> Matricule</span>
                        <span class="ts-info-value">{{ $teacher->matricule }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ---- TAB: Enseignement --------------------------------- --}}
        <div class="ts-panel" id="ts-tab-teaching">
            <div class="ts-grid-2">
                {{-- Heures d'enseignement --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon"><i class="fas fa-clock"></i></div>
                            Heures d'Enseignement
                        </div>
                    </div>

                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-hourglass-half"></i> Heures dues</span>
                        <span class="ts-info-value">{{ $teacher->teaching_hours_due ?? 0 }} h</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-check-circle"></i> Heures effectuees</span>
                        <span class="ts-info-value">{{ $teacher->teaching_hours_done ?? 0 }} h</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-percentage"></i> Progression</span>
                        <span class="ts-info-value">
                            <span class="ts-badge {{ $hoursPercent >= 100 ? 'success' : ($hoursPercent >= 50 ? 'muted' : 'danger') }}">
                                {{ $hoursPercent }}%
                            </span>
                        </span>
                    </div>

                    @if($hoursDue > 0)
                    <div style="margin-top: 16px;">
                        <div style="height: 8px; background: var(--ts-surface); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $hoursPercent }}%; background: linear-gradient(135deg, var(--ts-blue) 0%, var(--ts-blue-2) 100%); border-radius: 4px; transition: width .4s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: .75rem; color: var(--ts-muted);">
                            <span>0h</span>
                            <span>{{ $hoursDue }}h</span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Bureau & site web --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon"><i class="fas fa-info-circle"></i></div>
                            Details Enseignement
                        </div>
                    </div>

                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-door-open"></i> Bureau</span>
                        <span class="ts-info-value">{{ $teacher->office_location ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-globe"></i> Site web</span>
                        <span class="ts-info-value">
                            @if($teacher->website)
                                <a href="{{ $teacher->website }}" target="_blank" style="color: var(--ts-blue); text-decoration: none;">
                                    {{ $teacher->website }}
                                    <i class="fas fa-external-link-alt" style="font-size: .7rem; margin-left: 4px;"></i>
                                </a>
                            @else
                                Non renseigne
                            @endif
                        </span>
                    </div>
                    @if($teacher->specialization)
                    <div class="ts-info-row">
                        <span class="ts-info-label"><i class="fas fa-microscope"></i> Specialisation</span>
                        <span class="ts-info-value">{{ $teacher->specialization }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Specialites --}}
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-title">
                        <div class="ts-card-title-icon"><i class="fas fa-star"></i></div>
                        Specialites
                    </div>
                </div>

                @if(is_array($teacher->specialties) && count($teacher->specialties) > 0)
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($teacher->specialties as $specialty)
                            <span class="ts-chip">{{ $specialty }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="ts-empty">
                        <div class="ts-empty-icon"><i class="fas fa-star"></i></div>
                        <p>Aucune specialite renseignee</p>
                    </div>
                @endif
            </div>

            {{-- Interets de recherche --}}
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-title">
                        <div class="ts-card-title-icon"><i class="fas fa-lightbulb"></i></div>
                        Interets de Recherche
                    </div>
                </div>

                @if(is_array($teacher->research_interests) && count($teacher->research_interests) > 0)
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($teacher->research_interests as $interest)
                            <span class="ts-chip teal">{{ $interest }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="ts-empty">
                        <div class="ts-empty-icon"><i class="fas fa-lightbulb"></i></div>
                        <p>Aucun interet de recherche renseigne</p>
                    </div>
                @endif
            </div>

            {{-- Biographie --}}
            @if($teacher->bio)
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-title">
                        <div class="ts-card-title-icon"><i class="fas fa-book-open"></i></div>
                        Biographie
                    </div>
                </div>
                <div class="ts-bio">
                    {{ $teacher->bio }}
                </div>
            </div>
            @endif
        </div>

        {{-- ---- TAB: Systeme -------------------------------------- --}}
        <div class="ts-panel" id="ts-tab-system">
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-title">
                        <div class="ts-card-title-icon"><i class="fas fa-server"></i></div>
                        Informations Systeme
                    </div>
                </div>
                <div class="ts-grid-2">
                    <div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-hashtag"></i> ID Enseignant</span>
                            <span class="ts-info-value">#{{ $teacher->id }}</span>
                        </div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-user"></i> ID Utilisateur</span>
                            <span class="ts-info-value">#{{ $teacher->user_id }}</span>
                        </div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-calendar-plus"></i> Cree le</span>
                            <span class="ts-info-value">{{ $teacher->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-edit"></i> Modifie le</span>
                            <span class="ts-info-value">{{ $teacher->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-user-shield"></i> Cree par</span>
                            <span class="ts-info-value">{{ optional($teacher->createdBy)->name ?? 'Systeme' }}</span>
                        </div>
                        <div class="ts-info-row">
                            <span class="ts-info-label"><i class="fas fa-user-edit"></i> Mis a jour par</span>
                            <span class="ts-info-value">{{ optional($teacher->updatedBy)->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Actions -------------------------------------- --}}
        <div class="ts-panel" id="ts-tab-actions">
            <div class="ts-grid-2">
                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon"><i class="fas fa-tools"></i></div>
                            Actions Rapides
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="{{ route('esbtp.teachers.edit', $teacher->id) }}" class="ts-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Modifier le profil</span>
                        </a>
                        <a href="{{ route('esbtp.teachers.index') }}" class="ts-action-btn">
                            <i class="fas fa-list"></i>
                            <span>Retour a la liste</span>
                        </a>
                    </div>
                </div>

                <div class="ts-card">
                    <div class="ts-card-header">
                        <div class="ts-card-title">
                            <div class="ts-card-title-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-exclamation-triangle"></i></div>
                            Zone de danger
                        </div>
                    </div>

                    <p style="font-size: .85rem; color: var(--ts-muted); margin: 0 0 16px;">
                        Cette action est irreversible. L'enseignant et son compte utilisateur associe seront definitivement supprimes.
                    </p>

                    <button type="button" class="ts-action-btn danger-action" onclick="showDeleteModal()">
                        <i class="fas fa-trash-alt"></i>
                        <span>Supprimer definitivement</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ================================================================
     MODAL: Delete Confirmation
================================================================= --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); color: white; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div style="
                    background: rgba(220,38,38,.05);
                    border-left: 4px solid #dc2626;
                    border-radius: 10px;
                    padding: 1rem 1.25rem;
                    margin-bottom: 1.5rem;
                ">
                    <div class="d-flex align-items-start gap-3">
                        <div style="
                            width: 40px; height: 40px; border-radius: 50%;
                            background: linear-gradient(135deg, #dc2626, #ef4444);
                            display: flex; align-items: center; justify-content: center;
                            color: white; flex-shrink: 0;
                        ">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="color: var(--ts-text); font-weight: 600; margin-bottom: 0.25rem;">Attention</div>
                            <div style="color: var(--ts-muted); font-size: 0.9rem;">
                                Vous etes sur le point de supprimer l'enseignant <strong>{{ $teacher->user->name }}</strong>.
                                Cette action desactivera egalement le compte utilisateur associe et supprimera toutes les associations.
                            </div>
                        </div>
                    </div>
                </div>

                <div style="
                    background: var(--ts-surface);
                    border: 2px solid var(--ts-border);
                    border-radius: 8px;
                    padding: 0.75rem;
                    font-weight: 500;
                ">
                    {{ $teacher->user->name }} ({{ $teacher->user->email }})
                </div>
            </div>
            <div class="modal-footer" style="background: var(--ts-surface); padding: 1.25rem 2rem; border: none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                    padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 500;
                ">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form action="{{ route('esbtp.teachers.destroy', $teacher->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn" style="
                        background: linear-gradient(135deg, #dc2626, #ef4444);
                        border: none; color: white;
                        padding: 0.65rem 1.5rem; border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(220,38,38,.3);
                    ">
                        <i class="fas fa-trash-alt me-1"></i>Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// -- Tab switching -----------------------------------------------
document.querySelectorAll('.ts-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.ts-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ts-panel').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.getElementById('ts-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});

// -- Delete Modal ------------------------------------------------
function showDeleteModal() {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush
