@extends('layouts.app')

@section('title', 'Profil Enseignant — ' . ($teacher->user->name ?? 'N/A') . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   ENSEIGNANT SHOW — Premium Design — KLASSCI Design System
   Namespace: es- (enseignant-show)
=================================================================== */

/* -- Tokens -------------------------------------------------------- */
:root {
    --es-blue:      #0453cb;
    --es-blue-2:    #5e91de;
    --es-surface:   #f4f7fb;
    --es-card:      #ffffff;
    --es-border:    #e2e8f0;
    --es-text:      #1e293b;
    --es-muted:     #64748b;
    --es-success:   #10b981;
    --es-danger:    #dc2626;
    --es-radius:    12px;
    --es-radius-lg: 20px;
    --es-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --es-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* -- Page shell ---------------------------------------------------- */
.es-page { background: var(--es-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.es-hero {
    position: relative;
    background: linear-gradient(135deg, var(--es-blue) 0%, var(--es-blue-2) 100%);
    padding: 0;
}
.es-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
}
.es-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--es-surface) 0%, transparent 100%);
}

.es-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar */
.es-hero-avatar {
    position: relative; flex-shrink: 0;
}
.es-hero-avatar-circle {
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
.es-hero-avatar-circle img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.es-hero-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.es-hero-status.active   { background: #10b981; }
.es-hero-status.inactive { background: #94a3b8; }

/* Text block */
.es-hero-text { flex: 1; min-width: 200px; color: #fff; }
.es-hero-name {
    font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em;
    margin: 0 0 3px; line-height: 1.2;
}
.es-hero-sub { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.es-hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.es-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.es-hero-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.es-hero-pill.muted { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); }

/* Actions in hero */
.es-hero-actions {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; margin-left: auto;
}
.es-hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.es-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.es-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--es-blue); }
.es-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.es-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.es-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- KPI Strip (inside hero) --------------------------------------- */
.es-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 8px;
}
.es-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.es-kpi:last-child { border-right: none; }
.es-kpi-icon { font-size: 1rem; opacity: .7; }
.es-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.es-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* -- Tab Bar ------------------------------------------------------- */
.es-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--es-card);
    box-shadow: 0 1px 0 var(--es-border);
}
.es-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.es-tabs::-webkit-scrollbar { display: none; }
.es-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--es-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.es-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--es-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.es-tab:hover { color: var(--es-blue); }
.es-tab.active { color: var(--es-blue); }
.es-tab.active::after { transform: scaleX(1); }

/* -- Content area -------------------------------------------------- */
.es-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.es-panel { display: none; animation: esFadeUp .24s ease; }
.es-panel.active { display: block; }
@keyframes esFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* -- Section card -------------------------------------------------- */
.es-card {
    background: var(--es-card); border: 1px solid var(--es-border);
    border-radius: var(--es-radius-lg); padding: 24px;
    box-shadow: var(--es-shadow); margin-bottom: 20px;
}
.es-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.es-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--es-text);
}
.es-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--es-blue) 0%, var(--es-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* -- Info rows ----------------------------------------------------- */
.es-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--es-border);
}
.es-info-row:last-child { border-bottom: none; }
.es-info-label {
    font-size: .85rem; font-weight: 500; color: var(--es-muted);
    display: flex; align-items: center; gap: 8px;
}
.es-info-label i { font-size: .75rem; width: 16px; text-align: center; color: var(--es-blue); opacity: .6; }
.es-info-value { font-size: .9rem; font-weight: 600; color: var(--es-text); }

/* -- Status badge -------------------------------------------------- */
.es-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .03em;
}
.es-badge.success { background: rgba(16,185,129,.1); color: #059669; }
.es-badge.danger  { background: rgba(220,38,38,.1);  color: #dc2626; }
.es-badge.muted   { background: rgba(100,116,139,.1); color: #64748b; }

/* -- Grid layout for two-column panels ----------------------------- */
.es-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}

/* -- Action buttons inside cards ----------------------------------- */
.es-action-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 14px; border-radius: 10px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid var(--es-border);
    background: var(--es-card); color: var(--es-text);
    transition: all .18s;
}
.es-action-btn:hover {
    border-color: var(--es-blue); color: var(--es-blue);
    box-shadow: 0 2px 8px rgba(4,83,203,.1);
    transform: translateY(-1px);
}
.es-action-btn i { width: 18px; text-align: center; }
.es-action-btn.danger-action { color: var(--es-danger); border-color: rgba(220,38,38,.2); }
.es-action-btn.danger-action:hover {
    border-color: var(--es-danger); background: rgba(220,38,38,.03);
    box-shadow: 0 2px 8px rgba(220,38,38,.1);
}
.es-action-btn.warning-action { color: #d97706; border-color: rgba(217,119,6,.2); }
.es-action-btn.warning-action:hover {
    border-color: #d97706; background: rgba(217,119,6,.03);
    box-shadow: 0 2px 8px rgba(217,119,6,.1);
}
.es-action-btn.success-action { color: #059669; border-color: rgba(5,150,105,.2); }
.es-action-btn.success-action:hover {
    border-color: #059669; background: rgba(5,150,105,.03);
    box-shadow: 0 2px 8px rgba(5,150,105,.1);
}

/* -- Empty state --------------------------------------------------- */
.es-empty {
    text-align: center; padding: 40px 20px; color: var(--es-muted);
}
.es-empty-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: var(--es-surface);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: var(--es-muted); margin-bottom: 12px;
}
.es-empty p { font-size: .9rem; margin: 0; }

/* -- Contact card items -------------------------------------------- */
.es-contact-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--es-border);
}
.es-contact-item:last-child { border-bottom: none; }
.es-contact-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(4,83,203,.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--es-blue); font-size: .85rem; flex-shrink: 0;
}
.es-contact-item a {
    color: var(--es-text); text-decoration: none; font-weight: 500; font-size: .88rem;
    transition: color .15s;
}
.es-contact-item a:hover { color: var(--es-blue); }

/* -- Password alert ------------------------------------------------ */
.es-password-alert {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(16,185,129,.3);
    border-radius: var(--es-radius); padding: 16px;
    margin-bottom: 16px;
}
.es-password-alert h6 { color: #047857; font-size: .9rem; font-weight: 700; margin: 0 0 6px; }
.es-password-alert code { background: rgba(0,0,0,.06); padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #065f46; }
.es-password-alert .es-password-hint { font-size: .8rem; color: #047857; margin: 8px 0 0; }

/* -- Bio text ------------------------------------------------------ */
.es-bio-text {
    background: var(--es-surface);
    border-radius: 10px;
    padding: 16px;
    color: var(--es-muted);
    font-style: italic;
    line-height: 1.6;
    border-left: 4px solid var(--es-blue);
    font-size: .9rem;
}

/* -- Teaching section (inside Enseignement tab) -------------------- */
.es-teaching-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
}
.es-teaching-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--es-text);
}
.es-periode-btns { display: flex; gap: 4px; }
.es-periode-btn {
    padding: 6px 16px; border-radius: 8px; font-size: .8rem; font-weight: 600;
    border: 1px solid var(--es-border); background: var(--es-card); color: var(--es-muted);
    cursor: pointer; transition: all .18s;
}
.es-periode-btn:hover { border-color: var(--es-blue); color: var(--es-blue); }
.es-periode-btn.active { background: var(--es-blue); color: #fff; border-color: var(--es-blue); }

/* Teaching KPI strip */
.es-teaching-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px; margin-bottom: 20px;
}
.es-teaching-kpi {
    background: var(--es-card); border: 1px solid var(--es-border);
    border-radius: 14px; padding: 16px 18px;
    position: relative; overflow: hidden;
}
.es-teaching-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--kpi-color, var(--es-blue));
    border-radius: 14px 14px 0 0;
}
.es-teaching-kpi:nth-child(1) { --kpi-color: #0453cb; }
.es-teaching-kpi:nth-child(2) { --kpi-color: #5e91de; }
.es-teaching-kpi:nth-child(3) { --kpi-color: #10b981; }
.es-teaching-kpi:nth-child(4) { --kpi-color: #0369a1; }
.es-teaching-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; margin-bottom: 6px;
    background: color-mix(in srgb, var(--kpi-color, var(--es-blue)) 12%, transparent);
    color: var(--kpi-color, var(--es-blue));
}
.es-teaching-kpi-val { font-size: 1.5rem; font-weight: 800; color: var(--es-text); line-height: 1; }
.es-teaching-kpi-lbl { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-top: 4px; }

/* Class cards */
.es-class-card {
    background: var(--es-card); border: 1px solid var(--es-border);
    border-radius: 14px; margin-bottom: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow: hidden;
}
.es-class-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9; background: #f8fafc;
}
.es-class-title a {
    color: var(--es-blue); font-weight: 700; text-decoration: none; font-size: 1rem;
}
.es-class-title a:hover { text-decoration: underline; }
.es-class-meta { font-size: .8rem; color: var(--es-muted); margin-top: 2px; }
.es-class-stats { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.es-class-stat { text-align: center; min-width: 70px; }
.es-class-stat .value { font-weight: 700; color: var(--es-text); font-size: 1rem; }
.es-class-stat .label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
.es-class-body { padding: 16px 20px; }

/* Matiere cards */
.es-matiere-card {
    background: var(--es-card); border: 1px solid var(--es-border);
    border-radius: 14px; padding: 16px 20px; margin-bottom: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    display: grid; grid-template-columns: 1fr 220px; gap: 20px; align-items: center;
    transition: box-shadow .2s;
}
.es-matiere-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.es-matiere-left { min-width: 0; }
.es-matiere-right { display: flex; flex-direction: column; gap: 8px; }
.es-matiere-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.es-matiere-title { font-weight: 700; color: var(--es-text); font-size: .95rem; }
.es-matiere-stats { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.es-matiere-stats .value { font-weight: 700; color: var(--es-text); font-size: 1rem; }
.es-matiere-stats .label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }

/* Progress bar */
.es-progress { height: 10px; background: #f1f5f9; border-radius: 99px; overflow: hidden; margin-top: 4px; }
.es-progress-fill { height: 100%; border-radius: 99px; transition: width .7s cubic-bezier(.4,0,.2,1); }
.es-progress-fill.level-low  { background: linear-gradient(90deg, #fca5a5, #ef4444); }
.es-progress-fill.level-mid  { background: linear-gradient(90deg, #fcd34d, #d97706); }
.es-progress-fill.level-good { background: linear-gradient(90deg, #6ee7b7, #10b981); }
.es-progress-fill.level-done { background: linear-gradient(90deg, #93c5fd, #0453cb); }

.es-percent-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 48px; padding: 3px 10px; border-radius: 99px;
    font-size: .82rem; font-weight: 700; white-space: nowrap;
}

/* -- Availability section ------------------------------------------ */
.es-availability-section {
    background: var(--es-card); border: 1px solid var(--es-border);
    border-radius: var(--es-radius-lg); padding: 24px;
    box-shadow: var(--es-shadow); margin-bottom: 20px;
}
.es-avail-grid {
    display: grid; grid-template-columns: 80px repeat(7, 1fr);
    gap: 6px; background: var(--es-surface); padding: 16px;
    border-radius: 12px; border: 1px solid var(--es-border);
}
.es-avail-time-header {
    font-weight: 600; text-align: center; color: var(--es-muted); font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
}
.es-avail-day-header {
    text-align: center; font-weight: 700; color: var(--es-blue);
    padding: 8px 4px; background: rgba(4,83,203,.08); border-radius: 8px; font-size: .8rem;
}
.es-avail-time-slot {
    text-align: center; padding: 6px; font-size: .78rem; color: var(--es-muted);
    display: flex; align-items: center; justify-content: center;
}
.es-avail-slot {
    padding: 6px; text-align: center; border-radius: 6px;
    font-size: .78rem; transition: all .2s; cursor: default;
    display: flex; align-items: center; justify-content: center;
}
.es-avail-slot.available { background: var(--es-success); color: white; font-weight: 600; }
.es-avail-slot.preferred { background: var(--es-blue); color: white; font-weight: 600; }
.es-avail-slot.unavailable { background: var(--es-border); color: var(--es-muted); }
.es-avail-slot.editable { cursor: pointer; border: 2px solid transparent; }
.es-avail-slot.editable:hover { border-color: var(--es-blue); transform: scale(1.05); }
.es-avail-slot.modified::after {
    content: ''; position: absolute; top: 2px; right: 2px;
    width: 6px; height: 6px; border-radius: 50%; background: #d97706;
}
.es-avail-legend {
    display: flex; justify-content: center; gap: 24px; margin-top: 16px; flex-wrap: wrap;
}
.es-avail-legend-item { display: flex; align-items: center; gap: 6px; font-size: .82rem; color: var(--es-muted); }
.es-avail-legend-color { width: 14px; height: 14px; border-radius: 4px; }

/* -- Skill tags ---------------------------------------------------- */
.es-skill-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.es-skill-tag {
    background: rgba(4,83,203,.08); color: var(--es-blue);
    padding: 4px 12px; border-radius: 20px; font-size: .8rem; font-weight: 500;
}

/* -- Responsive ---------------------------------------------------- */
@media (max-width: 1024px) {
    .es-grid-2 { grid-template-columns: 1fr; }
    .es-matiere-card { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .es-hero-inner { padding: 24px 16px 0; flex-direction: column; text-align: center; }
    .es-hero-pills { justify-content: center; }
    .es-hero-actions { margin-left: 0; align-items: center; }
    .es-kpi-strip { flex-wrap: wrap; }
    .es-kpi { flex: 1 1 50%; border-bottom: 1px solid rgba(255,255,255,.1); }
    .es-content { padding: 20px 16px 40px; }
    .es-tabs { padding: 0 12px; }
    .es-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
    .es-class-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .es-teaching-kpis { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endsection

@section('content')
<div class="es-page">

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="es-hero">
        <div class="es-hero-inner">
            {{-- Avatar --}}
            <div class="es-hero-avatar">
                <div class="es-hero-avatar-circle">
                    @if($teacher->user && $teacher->user->photo_url)
                        <img src="{{ $teacher->user->photo_url }}" alt="{{ $teacher->user->name }}">
                    @else
                        {{ $teacher->user ? strtoupper(substr($teacher->user->name, 0, 2)) : 'NN' }}
                    @endif
                </div>
                <div class="es-hero-status {{ $teacher->status === 'active' ? 'active' : 'inactive' }}"></div>
            </div>

            {{-- Info --}}
            <div class="es-hero-text">
                <h1 class="es-hero-name">{{ $teacher->user->name ?? 'Nom non disponible' }}</h1>
                <p class="es-hero-sub">{{ $teacher->specialization ?? 'Enseignant' }}</p>
                <div class="es-hero-pills">
                    <span class="es-hero-pill">
                        <i class="fas fa-id-card"></i> {{ $teacher->matricule }}
                    </span>
                    <span class="es-hero-pill {{ $teacher->status === 'active' ? 'green' : '' }}">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                    </span>
                    @if($teacher->department)
                    <span class="es-hero-pill muted">
                        <i class="fas fa-building"></i> {{ $teacher->department->name }}
                    </span>
                    @endif
                    <span class="es-hero-pill muted">
                        <i class="fas fa-calendar-alt"></i>
                        Depuis {{ $teacher->created_at ? $teacher->created_at->format('M Y') : 'N/A' }}
                    </span>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="es-hero-actions">
                <div class="es-hero-btns">
                    <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="es-hero-btn primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="es-hero-btn ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="es-kpi-strip">
            <div class="es-kpi">
                <i class="fas fa-clock es-kpi-icon"></i>
                <div>
                    <div class="es-kpi-val">{{ (int)($teacher->teaching_hours_due ?? 0) }}h</div>
                    <div class="es-kpi-lbl">Heures dues</div>
                </div>
            </div>
            <div class="es-kpi">
                <i class="fas fa-chalkboard-teacher es-kpi-icon"></i>
                <div>
                    <div class="es-kpi-val">{{ $teachingPlanning['stats']['classes'] ?? 0 }}</div>
                    <div class="es-kpi-lbl">Classes</div>
                </div>
            </div>
            <div class="es-kpi">
                <i class="fas fa-graduation-cap es-kpi-icon"></i>
                <div>
                    <div class="es-kpi-val">{{ $profileData->annees_experience_enseignement ?? 0 }} ans</div>
                    <div class="es-kpi-lbl">Experience</div>
                </div>
            </div>
            <div class="es-kpi">
                <i class="fas fa-chart-line es-kpi-icon"></i>
                <div>
                    <div class="es-kpi-val">{{ $teachingPlanning['stats']['taux_realisation'] ?? 0 }}%</div>
                    <div class="es-kpi-lbl">Realisation</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB BAR
    ============================================================= --}}
    <div class="es-tabs-wrap">
        <div class="es-tabs">
            <button class="es-tab active" data-tab="info" type="button">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="es-tab" data-tab="teaching" type="button">
                <i class="fas fa-chalkboard"></i> Enseignement
            </button>
            <button class="es-tab" data-tab="account" type="button">
                <i class="fas fa-user-cog"></i> Compte
            </button>
            <button class="es-tab" data-tab="actions" type="button">
                <i class="fas fa-cog"></i> Actions
            </button>
        </div>
    </div>

    {{-- ============================================================
         CONTENT
    ============================================================= --}}
    <div class="es-content">

        {{-- ---- TAB: Informations --------------------------------- --}}
        <div class="es-panel active" id="es-tab-info">
            <div class="es-grid-2">
                {{-- Informations personnelles --}}
                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon"><i class="fas fa-id-card"></i></div>
                            Informations Personnelles
                        </div>
                    </div>

                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-user"></i> Nom complet</span>
                        <span class="es-info-value">{{ $teacher->user->name ?? 'Non disponible' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="es-info-value">{{ $teacher->user->email ?? 'Non disponible' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-phone"></i> Telephone</span>
                        <span class="es-info-value">{{ $teacher->user->phone ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-award"></i> Titre academique</span>
                        <span class="es-info-value">{{ $profileData->titre_academique ?? 'Non renseigne' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-medal"></i> Grade academique</span>
                        <span class="es-info-value">{{ $profileData->grade_academique ?? 'Non renseigne' }}</span>
                    </div>
                </div>

                {{-- Informations professionnelles --}}
                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon"><i class="fas fa-briefcase"></i></div>
                            Informations Professionnelles
                        </div>
                    </div>

                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-star"></i> Specialisation</span>
                        <span class="es-info-value">{{ $teacher->specialization }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-building"></i> Departement</span>
                        <span class="es-info-value">{{ $teacher->department->name ?? 'Non assigne' }}</span>
                    </div>
                    @if($teacher->laboratory)
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-flask"></i> Laboratoire</span>
                        <span class="es-info-value">{{ $teacher->laboratory->name }}</span>
                    </div>
                    @endif
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-clock"></i> Heures dues</span>
                        <span class="es-info-value">{{ (int)($teacher->teaching_hours_due ?? 0) }}h/semaine</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                        <span class="es-info-value">
                            <span class="es-badge {{ $teacher->status === 'active' ? 'success' : 'danger' }}">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </span>
                    </div>
                    @if($profileData && $profileData->type_contrat)
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-file-contract"></i> Type de contrat</span>
                        <span class="es-info-value">{{ ucfirst($profileData->type_contrat) }}</span>
                    </div>
                    @endif
                    @if($profileData && $profileData->statut_emploi)
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-user-tie"></i> Statut d'emploi</span>
                        <span class="es-info-value">{{ str_replace('_', ' ', ucfirst($profileData->statut_emploi)) }}</span>
                    </div>
                    @endif

                    {{-- Contact rapide --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--es-border);">
                        <div style="font-size: .85rem; font-weight: 700; color: var(--es-text); margin-bottom: 12px;">
                            Contact rapide
                        </div>
                        <div class="es-contact-item">
                            <div class="es-contact-icon"><i class="fas fa-envelope"></i></div>
                            <a href="mailto:{{ $teacher->user->email ?? '#' }}">{{ $teacher->user->email ?? 'Non disponible' }}</a>
                        </div>
                        @if($teacher->user && $teacher->user->phone)
                        <div class="es-contact-item">
                            <div class="es-contact-icon"><i class="fas fa-phone"></i></div>
                            <a href="tel:{{ $teacher->user->phone }}">{{ $teacher->user->phone }}</a>
                        </div>
                        @endif
                        @if($teacher->website)
                        <div class="es-contact-item">
                            <div class="es-contact-icon"><i class="fas fa-globe"></i></div>
                            <a href="{{ $teacher->website }}" target="_blank">Site web</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Qualifications & Formation --}}
            @if($profileData)
            <div class="es-card">
                <div class="es-card-header">
                    <div class="es-card-title">
                        <div class="es-card-title-icon"><i class="fas fa-graduation-cap"></i></div>
                        Qualifications & Formation
                    </div>
                </div>
                <div class="es-grid-2">
                    <div>
                        @if($profileData->diplome_principal)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-scroll"></i> Diplome principal</span>
                            <span class="es-info-value">{{ $profileData->diplome_principal }}</span>
                        </div>
                        @endif
                        @if($profileData->universite_diplome)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-university"></i> Universite</span>
                            <span class="es-info-value">{{ $profileData->universite_diplome }}</span>
                        </div>
                        @endif
                        @if($profileData->annee_diplome)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-calendar"></i> Annee d'obtention</span>
                            <span class="es-info-value">{{ $profileData->annee_diplome }}</span>
                        </div>
                        @endif
                    </div>
                    <div>
                        @if($profileData->annees_experience_enseignement)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-chalkboard-teacher"></i> Experience enseignement</span>
                            <span class="es-info-value">{{ $profileData->annees_experience_enseignement }} annees</span>
                        </div>
                        @endif
                        @if($profileData->annees_experience_professionnelle)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-briefcase"></i> Experience professionnelle</span>
                            <span class="es-info-value">{{ $profileData->annees_experience_professionnelle }} annees</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Biographie --}}
            @if($teacher->bio)
            <div class="es-card">
                <div class="es-card-header">
                    <div class="es-card-title">
                        <div class="es-card-title-icon"><i class="fas fa-user-circle"></i></div>
                        A propos
                    </div>
                </div>
                <div class="es-bio-text">{{ $teacher->bio }}</div>
            </div>
            @endif

            {{-- Motivation & Objectifs --}}
            @if($profileData && ($profileData->motivation || $profileData->objectifs_pedagogiques))
            <div class="es-card">
                <div class="es-card-header">
                    <div class="es-card-title">
                        <div class="es-card-title-icon"><i class="fas fa-lightbulb"></i></div>
                        Motivation & Objectifs
                    </div>
                </div>
                @if($profileData->motivation)
                <div class="es-info-row">
                    <span class="es-info-label"><i class="fas fa-heart"></i> Motivation</span>
                    <span class="es-info-value">{{ $profileData->motivation }}</span>
                </div>
                @endif
                @if($profileData->objectifs_pedagogiques)
                <div class="es-info-row">
                    <span class="es-info-label"><i class="fas fa-bullseye"></i> Objectifs pedagogiques</span>
                    <span class="es-info-value">{{ $profileData->objectifs_pedagogiques }}</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Informations systeme --}}
            <div class="es-card">
                <div class="es-card-header">
                    <div class="es-card-title">
                        <div class="es-card-title-icon"><i class="fas fa-server"></i></div>
                        Informations Systeme
                    </div>
                </div>
                <div class="es-grid-2">
                    <div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-hashtag"></i> ID Enseignant</span>
                            <span class="es-info-value">#{{ $teacher->id }}</span>
                        </div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-id-badge"></i> Matricule</span>
                            <span class="es-info-value">{{ $teacher->matricule }}</span>
                        </div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-calendar-plus"></i> Cree le</span>
                            <span class="es-info-value">{{ $teacher->created_at ? $teacher->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-edit"></i> Modifie le</span>
                            <span class="es-info-value">{{ $teacher->updated_at ? $teacher->updated_at->format('d/m/Y H:i') : 'N/A' }}</span>
                        </div>
                        @if($teacher->createdBy)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-user-shield"></i> Cree par</span>
                            <span class="es-info-value">{{ $teacher->createdBy->name ?? 'N/A' }}</span>
                        </div>
                        @endif
                        @if($teacher->updatedBy)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-user-edit"></i> Modifie par</span>
                            <span class="es-info-value">{{ $teacher->updatedBy->name ?? 'N/A' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Enseignement --------------------------------- --}}
        <div class="es-panel" id="es-tab-teaching">

            {{-- Teaching overview --}}
            <div class="es-card" id="teaching-overview-content">
                <div class="es-teaching-header">
                    <div class="es-teaching-title">
                        <i class="fas fa-chalkboard" style="color: var(--es-blue);"></i>
                        Charge pedagogique par classe
                        <span style="font-size: .8rem; font-weight: 500; color: var(--es-muted); margin-left: 8px;">
                            {{ $anneeCourante->name ?? 'Annee en cours' }}
                        </span>
                    </div>
                    <form method="GET" action="{{ route('esbtp.enseignants.show', ['enseignant' => $teacher->id]) }}" class="es-periode-btns">
                        <button type="submit" name="periode" value="semestre1" class="es-periode-btn {{ ($periode ?? 'annee') === 'semestre1' ? 'active' : '' }}">S1</button>
                        <button type="submit" name="periode" value="semestre2" class="es-periode-btn {{ ($periode ?? 'annee') === 'semestre2' ? 'active' : '' }}">S2</button>
                        <button type="submit" name="periode" value="annee" class="es-periode-btn {{ ($periode ?? 'annee') === 'annee' ? 'active' : '' }}">Annee</button>
                    </form>
                </div>

                {{-- Teaching KPIs --}}
                <div class="es-teaching-kpis">
                    <div class="es-teaching-kpi">
                        <div class="es-teaching-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="es-teaching-kpi-val">{{ $teachingPlanning['stats']['classes'] ?? 0 }}</div>
                        <div class="es-teaching-kpi-lbl">Classes enseignees</div>
                    </div>
                    <div class="es-teaching-kpi">
                        <div class="es-teaching-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="es-teaching-kpi-val">{{ number_format($teachingPlanning['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
                        <div class="es-teaching-kpi-lbl">Heures planifiees</div>
                    </div>
                    <div class="es-teaching-kpi">
                        <div class="es-teaching-kpi-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="es-teaching-kpi-val">{{ number_format($teachingPlanning['stats']['heures_realisees'] ?? 0, 1) }}h</div>
                        <div class="es-teaching-kpi-lbl">Heures realisees</div>
                    </div>
                    <div class="es-teaching-kpi">
                        <div class="es-teaching-kpi-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="es-teaching-kpi-val">{{ $teachingPlanning['stats']['taux_realisation'] ?? 0 }}%</div>
                        <div class="es-teaching-kpi-lbl">Taux de realisation</div>
                    </div>
                </div>

                {{-- Class cards --}}
                @if(!empty($teachingPlanning['classes']) && $teachingPlanning['classes']->isNotEmpty())
                    @foreach($teachingPlanning['classes'] as $classeData)
                        @php
                            $classe = $classeData['classe'];
                            $collapseId = 'es-class-' . $classe->id;
                            $classTaux = $classeData['stats']['taux_realisation'] ?? 0;
                            $classBadgeBg = $classTaux >= 100 ? '#dbeafe' : ($classTaux >= 75 ? '#d1fae5' : ($classTaux >= 40 ? '#fef3c7' : '#fee2e2'));
                            $classBadgeTxt = $classTaux >= 100 ? '#1d4ed8' : ($classTaux >= 75 ? '#065f46' : ($classTaux >= 40 ? '#92400e' : '#991b1b'));
                        @endphp
                        <div class="es-class-card">
                            <div class="es-class-header">
                                <div>
                                    <div class="es-class-title">
                                        <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}">
                                            {{ $classe->name }}
                                        </a>
                                    </div>
                                    <div class="es-class-meta">
                                        <span class="es-badge muted" style="font-size:.72rem;">{{ $classe->filiere->name ?? 'N/A' }}</span>
                                        &middot; {{ $classe->niveau->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="es-class-stats">
                                    <div class="es-class-stat">
                                        <div class="value">{{ number_format($classeData['stats']['heures_planifiees'], 1) }}h</div>
                                        <div class="label">Planifiees</div>
                                    </div>
                                    <div class="es-class-stat">
                                        <div class="value">{{ number_format($classeData['stats']['heures_realisees'], 1) }}h</div>
                                        <div class="label">Realisees</div>
                                    </div>
                                    <div class="es-class-stat">
                                        <div class="value">
                                            <span class="es-percent-badge" style="background:{{ $classBadgeBg }};color:{{ $classBadgeTxt }};">{{ $classTaux }}%</span>
                                        </div>
                                        <div class="label">Realisation</div>
                                    </div>
                                </div>
                                <button class="es-hero-btn ghost" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="true" style="padding:6px 12px;font-size:.78rem;color:var(--es-muted);border-color:var(--es-border);background:var(--es-card);">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                            </div>
                            <div id="{{ $collapseId }}" class="collapse show">
                                <div class="es-class-body">
                                @if($classeData['matieres']->isNotEmpty())
                                    @foreach($classeData['matieres'] as $matiere)
                                        @php
                                            $mpct = $matiere['pourcentage_realise'] ?? 0;
                                            $mlevel = $mpct >= 100 ? 'level-done' : ($mpct >= 75 ? 'level-good' : ($mpct >= 40 ? 'level-mid' : 'level-low'));
                                            $mbg = $mpct >= 100 ? '#dbeafe' : ($mpct >= 75 ? '#d1fae5' : ($mpct >= 40 ? '#fef3c7' : '#fee2e2'));
                                            $mtxt = $mpct >= 100 ? '#1d4ed8' : ($mpct >= 75 ? '#065f46' : ($mpct >= 40 ? '#92400e' : '#991b1b'));
                                        @endphp
                                        <div class="es-matiere-card">
                                            <div class="es-matiere-left">
                                                <div class="es-matiere-header">
                                                    <div class="es-matiere-title">{{ $matiere['matiere']->name ?? 'Matiere inconnue' }}</div>
                                                    @if($matiere['est_configure'])
                                                        <span class="es-percent-badge" style="background:{{ $mbg }};color:{{ $mtxt }};">{{ $mpct }}%</span>
                                                    @else
                                                        <span class="es-badge" style="background:rgba(217,119,6,.1);color:#92400e;font-size:.72rem;">Non configure</span>
                                                    @endif
                                                </div>
                                                <div class="es-progress">
                                                    <div class="es-progress-fill {{ $mlevel }}" style="width: {{ min($mpct, 100) }}%"></div>
                                                </div>
                                                @if($matiere['est_configure'])
                                                    <div style="display:flex;justify-content:space-between;margin-top:4px;">
                                                        <small style="color:#10b981;font-size:.75rem;">{{ number_format($matiere['heures_realisees'], 1) }}h realisees</small>
                                                        @if($matiere['heures_restantes'] > 0)
                                                            <small style="color:#d97706;font-size:.75rem;">{{ number_format($matiere['heures_restantes'], 1) }}h restantes</small>
                                                        @else
                                                            <small style="color:#10b981;font-size:.75rem;">Objectif atteint</small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="es-matiere-right">
                                                <div class="es-matiere-stats">
                                                    <div>
                                                        <div class="value">{{ number_format($matiere['heures_realisees'], 1) }}h</div>
                                                        <div class="label">Realisees</div>
                                                    </div>
                                                    <div>
                                                        <div class="value">{{ number_format($matiere['heures_planifiees'], 1) }}h</div>
                                                        <div class="label">Planifiees</div>
                                                    </div>
                                                    <div>
                                                        <div class="value">{{ $matiere['nb_seances'] }}</div>
                                                        <div class="label">Seances</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="es-empty">
                                        <div class="es-empty-icon"><i class="fas fa-book-open"></i></div>
                                        <p>Aucune matiere trouvee pour cette classe</p>
                                    </div>
                                @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="es-empty">
                        <div class="es-empty-icon"><i class="fas fa-chalkboard"></i></div>
                        <p>Aucun cours trouve pour cet enseignant sur l'annee en cours</p>
                    </div>
                @endif
            </div>

            {{-- Disponibilites hebdomadaires --}}
            <div class="es-availability-section">
                <div class="es-card-header">
                    <div class="es-card-title">
                        <div class="es-card-title-icon"><i class="fas fa-calendar-alt"></i></div>
                        Disponibilites Hebdomadaires
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="es-badge {{ $teacher->status === 'active' ? 'success' : 'danger' }}">
                            {{ $teacher->status === 'active' ? 'Disponible' : 'Non disponible' }}
                        </span>
                        <button id="editAvailabilityBtn" class="es-hero-btn primary" onclick="toggleEditMode()" style="font-size:.78rem;padding:6px 14px;">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button id="saveAvailabilityBtn" class="es-hero-btn primary" onclick="saveAvailability()" style="display:none;font-size:.78rem;padding:6px 14px;background:var(--es-success);color:#fff;">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                        <button id="cancelAvailabilityBtn" class="es-hero-btn ghost" onclick="cancelEditMode()" style="display:none;font-size:.78rem;padding:6px 14px;color:var(--es-danger);border-color:rgba(220,38,38,.3);">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </div>

                @php
                    $hours = range(8, 18);
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    $dayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                    $availability = $realAvailability ?? [
                        'monday' => array_fill(0, 11, 'unavailable'),
                        'tuesday' => array_fill(0, 11, 'unavailable'),
                        'wednesday' => array_fill(0, 11, 'unavailable'),
                        'thursday' => array_fill(0, 11, 'unavailable'),
                        'friday' => array_fill(0, 11, 'unavailable'),
                        'saturday' => array_fill(0, 11, 'unavailable'),
                        'sunday' => array_fill(0, 11, 'unavailable')
                    ];
                @endphp

                <div class="es-avail-grid">
                    <div class="es-avail-time-header">Horaires</div>
                    @foreach($dayLabels as $dayLabel)
                        <div class="es-avail-day-header">{{ $dayLabel }}</div>
                    @endforeach

                    @foreach($hours as $index => $hour)
                        <div class="es-avail-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
                        @foreach($days as $dayIdx => $day)
                            @php
                                $status = $availability[$day][$index] ?? 'unavailable';
                                $icon = $status === 'preferred' ? '&#9733;' : ($status === 'available' ? '&#10003;' : '&#10007;');
                            @endphp
                            <div class="es-avail-slot {{ $status }}"
                                 id="slot-{{ $index }}-{{ $dayIdx }}"
                                 data-day="{{ $dayIdx }}"
                                 data-hour="{{ $hour }}"
                                 data-time-index="{{ $index }}"
                                 data-original-status="{{ $status }}"
                                 title="{{ ucfirst($day) }} {{ sprintf('%02d:00', $hour) }} - {{ ucfirst($status) }}">
                                {!! $icon !!}
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="es-avail-legend">
                    <div class="es-avail-legend-item">
                        <div class="es-avail-legend-color" style="background: var(--es-blue);"></div>
                        <span>Creneaux preferes</span>
                    </div>
                    <div class="es-avail-legend-item">
                        <div class="es-avail-legend-color" style="background: var(--es-success);"></div>
                        <span>Disponible</span>
                    </div>
                    <div class="es-avail-legend-item">
                        <div class="es-avail-legend-color" style="background: var(--es-border);"></div>
                        <span>Non disponible</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Compte --------------------------------------- --}}
        <div class="es-panel" id="es-tab-account">
            <div class="es-grid-2">
                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon"><i class="fas fa-user-cog"></i></div>
                            Compte utilisateur
                        </div>
                    </div>

                    @if(session('new_password'))
                        <div class="es-password-alert">
                            <h6><i class="fas fa-check-circle"></i> Mot de passe reinitialise</h6>
                            <p style="margin: 0;">Nouveau mot de passe : <code>{{ session('new_password') }}</code></p>
                            <p class="es-password-hint"><i class="fas fa-info-circle"></i> Communiquez ces identifiants a l'enseignant.</p>
                        </div>
                    @endif

                    @if($teacher->user)
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-at"></i> Nom d'utilisateur</span>
                            <span class="es-info-value">{{ $teacher->user->username ?: $teacher->user->email }}</span>
                        </div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-envelope"></i> Email</span>
                            <span class="es-info-value">{{ $teacher->user->email }}</span>
                        </div>
                        <div class="es-info-row">
                            <span class="es-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                            <span class="es-info-value">
                                <span class="es-badge {{ $teacher->status === 'active' ? 'success' : 'danger' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </span>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="button" class="es-action-btn" onclick="showResetPasswordModal()">
                                <i class="fas fa-key"></i>
                                <span>Reinitialiser le mot de passe</span>
                            </button>
                        </div>
                    @else
                        <div class="es-empty">
                            <div class="es-empty-icon"><i class="fas fa-user-slash"></i></div>
                            <p>Aucun compte utilisateur associe</p>
                        </div>
                    @endif
                </div>

                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon"><i class="fas fa-shield-alt"></i></div>
                            Securite
                        </div>
                    </div>

                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-sign-in-alt"></i> Premiere connexion</span>
                        <span class="es-info-value">{{ $teacher->user && $teacher->user->first_login_at ? $teacher->user->first_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-clock"></i> Derniere connexion</span>
                        <span class="es-info-value">{{ $teacher->user && $teacher->user->last_login_at ? $teacher->user->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span>
                    </div>
                    <div class="es-info-row">
                        <span class="es-info-label"><i class="fas fa-calendar"></i> Compte cree le</span>
                        <span class="es-info-value">{{ $teacher->created_at ? $teacher->created_at->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- TAB: Actions -------------------------------------- --}}
        <div class="es-panel" id="es-tab-actions">
            <div class="es-grid-2">
                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon"><i class="fas fa-tools"></i></div>
                            Actions Rapides
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="es-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Modifier le profil</span>
                        </a>

                        <a href="{{ route('esbtp.enseignants.matieres', ['teacher' => $teacher]) }}" class="es-action-btn">
                            <i class="fas fa-book"></i>
                            <span>Gerer les matieres</span>
                        </a>

                        @if($teacher->status === 'active')
                        <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST"
                              onsubmit="return confirm('Desactiver cet enseignant ?')">
                            @csrf
                            <button type="submit" class="es-action-btn warning-action">
                                <i class="fas fa-pause-circle"></i>
                                <span>Desactiver le compte</span>
                            </button>
                        </form>
                        @else
                        <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST"
                              onsubmit="return confirm('Activer cet enseignant ?')">
                            @csrf
                            <button type="submit" class="es-action-btn success-action">
                                <i class="fas fa-play-circle"></i>
                                <span>Activer le compte</span>
                            </button>
                        </form>
                        @endif

                        <button type="button" class="es-action-btn" onclick="showResetPasswordModal()">
                            <i class="fas fa-key"></i>
                            <span>Reinitialiser le mot de passe</span>
                        </button>
                    </div>
                </div>

                <div class="es-card">
                    <div class="es-card-header">
                        <div class="es-card-title">
                            <div class="es-card-title-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-exclamation-triangle"></i></div>
                            Zone de danger
                        </div>
                    </div>

                    <p style="font-size: .85rem; color: var(--es-muted); margin: 0 0 16px;">
                        Cette action est irreversible. L'enseignant et toutes ses donnees associees seront definitivement supprimes.
                    </p>

                    <form action="{{ route('esbtp.enseignants.destroy', ['enseignant' => $teacher]) }}" method="POST"
                          onsubmit="return confirm('Supprimer definitivement cet enseignant ? Cette action est irreversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="es-action-btn danger-action">
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
            <div class="modal-header" style="background: linear-gradient(135deg, var(--es-blue) 0%, var(--es-blue-2) 100%); color: white; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Reinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ route('esbtp.enseignants.reset-password', ['enseignant' => $teacher->id]) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    <div style="
                        background: rgba(4,83,203,.05);
                        border-left: 4px solid var(--es-blue);
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px; height: 40px; border-radius: 50%;
                                background: linear-gradient(135deg, var(--es-blue), var(--es-blue-2));
                                display: flex; align-items: center; justify-content: center;
                                color: white; flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: var(--es-text); font-weight: 600; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: var(--es-muted); font-size: 0.9rem;">
                                    Cette action va reinitialiser le mot de passe a <strong>"Bonjour@2025"</strong> pour l'enseignant
                                    <strong>{{ $teacher->user->name ?? 'l\'enseignant' }}</strong>. L'enseignant devra changer son mot de passe a la premiere connexion.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--es-text); font-size: 0.9rem;">
                            <i class="fas fa-user me-1" style="color: var(--es-blue);"></i>
                            Enseignant concerne
                        </label>
                        <div style="
                            background: var(--es-surface);
                            border: 2px solid var(--es-border);
                            border-radius: 8px;
                            padding: 0.75rem;
                            font-weight: 500;
                        ">
                            {{ $teacher->user->name ?? 'N/A' }} ({{ $teacher->user->email ?? 'N/A' }})
                        </div>
                    </div>

                    <div id="newPasswordDisplay" style="display: none;" class="mb-3">
                        <label class="form-label fw-semibold" style="color: var(--es-text); font-size: 0.9rem;">
                            <i class="fas fa-check-circle me-1" style="color: var(--es-success);"></i>
                            Mot de passe reinitialise
                        </label>
                        <div style="
                            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                            border: 2px solid var(--es-success);
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
                            Communiquez ce mot de passe a l'enseignant. Il devra le changer a la premiere connexion.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: var(--es-surface); padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 500;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn" id="resetPasswordBtn" style="
                        background: linear-gradient(135deg, var(--es-blue) 0%, var(--es-blue-2) 100%);
                        border: none; color: white;
                        padding: 0.65rem 1.5rem; border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(4,83,203,.3);
                    ">
                        <i class="fas fa-key me-1"></i>Reinitialiser a Bonjour@2025
                    </button>
                    <button type="button" class="btn" id="copyPasswordBtn" style="display: none; background: var(--es-success); color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 600;" onclick="copyPassword()">
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
document.querySelectorAll('.es-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.es-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.es-panel').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.getElementById('es-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});

// -- Periode AJAX fetch -------------------------------------------
(function() {
    var overviewContainer = document.getElementById('teaching-overview-content');
    if (!overviewContainer) return;

    overviewContainer.addEventListener('click', function(event) {
        var button = event.target.closest('button[name="periode"]');
        if (!button) return;
        event.preventDefault();

        var url = new URL(window.location.href);
        url.searchParams.set('periode', button.value);

        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(response) { return response.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var nextOverview = doc.querySelector('#teaching-overview-content');
                if (nextOverview) {
                    overviewContainer.innerHTML = nextOverview.innerHTML;
                }
                window.history.replaceState({}, '', url.toString());
            })
            .catch(function() {
                window.location.href = url.toString();
            });
    });
})();

// -- Availability editing -----------------------------------------
var isEditMode = false;
var originalData = {};
var modifiedSlots = new Set();

function toggleEditMode() {
    isEditMode = !isEditMode;
    var slots = document.querySelectorAll('.es-avail-slot');
    var editBtn = document.getElementById('editAvailabilityBtn');
    var saveBtn = document.getElementById('saveAvailabilityBtn');
    var cancelBtn = document.getElementById('cancelAvailabilityBtn');

    if (isEditMode) {
        slots.forEach(function(slot) {
            slot.classList.add('editable');
            slot.onclick = function() { toggleSlotStatus(slot); };
            originalData[slot.id] = slot.dataset.originalStatus;
        });
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-flex';
        cancelBtn.style.display = 'inline-flex';
        esNotify('Mode edition active. Cliquez sur les creneaux pour modifier.', 'info');
    } else {
        slots.forEach(function(slot) {
            slot.classList.remove('editable');
            slot.onclick = null;
        });
        editBtn.style.display = 'inline-flex';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
}

function toggleSlotStatus(slot) {
    if (!isEditMode) return;
    var statuses = ['unavailable', 'available', 'preferred'];
    var icons = ['\u2717', '\u2713', '\u2605'];
    var currentStatus = statuses.find(function(s) { return slot.classList.contains(s); }) || 'unavailable';
    var currentIndex = statuses.indexOf(currentStatus);
    var nextIndex = (currentIndex + 1) % statuses.length;
    var nextStatus = statuses[nextIndex];

    statuses.forEach(function(s) { slot.classList.remove(s); });
    slot.classList.add(nextStatus);
    slot.textContent = icons[nextIndex];

    if (nextStatus !== originalData[slot.id]) {
        slot.classList.add('modified');
        modifiedSlots.add(slot.id);
    } else {
        slot.classList.remove('modified');
        modifiedSlots.delete(slot.id);
    }

    var dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    var statusNames = { unavailable: 'Non disponible', available: 'Disponible', preferred: 'Prefere' };
    var dayIndex = parseInt(slot.dataset.day);
    slot.title = dayNames[dayIndex] + ' ' + String(parseInt(slot.dataset.hour)).padStart(2, '0') + ':00 - ' + statusNames[nextStatus];
}

function cancelEditMode() {
    modifiedSlots.forEach(function(slotId) {
        var slot = document.getElementById(slotId);
        var orig = originalData[slotId];
        var statuses = ['unavailable', 'available', 'preferred'];
        var icons = ['\u2717', '\u2713', '\u2605'];
        statuses.forEach(function(s) { slot.classList.remove(s); });
        slot.classList.add(orig);
        slot.textContent = icons[statuses.indexOf(orig)];
        slot.classList.remove('modified');
    });
    modifiedSlots.clear();
    toggleEditMode();
    esNotify('Modifications annulees', 'warning');
}

function saveAvailability() {
    if (modifiedSlots.size === 0) {
        esNotify('Aucune modification a sauvegarder', 'warning');
        return;
    }

    var changedSlots = [];
    modifiedSlots.forEach(function(slotId) {
        var slot = document.getElementById(slotId);
        var statuses = ['unavailable', 'available', 'preferred'];
        var currentStatus = statuses.find(function(s) { return slot.classList.contains(s); });
        var timeIndex = parseInt(slot.dataset.timeIndex);
        var startHour = 8 + timeIndex;
        var endHour = startHour + 1;
        changedSlots.push({
            day: parseInt(slot.dataset.day),
            startTime: String(startHour).padStart(2, '0') + ':00',
            endTime: String(endHour).padStart(2, '0') + ':00',
            status: currentStatus
        });
    });

    var teacherId = {{ $teacher->id }};
    fetch('/esbtp/enseignants/' + teacherId + '/update-availability', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ changes: changedSlots })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            esNotify('Disponibilites mises a jour avec succes !', 'success');
            modifiedSlots.forEach(function(slotId) {
                var slot = document.getElementById(slotId);
                var statuses = ['unavailable', 'available', 'preferred'];
                var currentStatus = statuses.find(function(s) { return slot.classList.contains(s); });
                originalData[slotId] = currentStatus;
                slot.dataset.originalStatus = currentStatus;
                slot.classList.remove('modified');
            });
            modifiedSlots.clear();
            toggleEditMode();
        } else {
            esNotify('Erreur : ' + (data.message || 'Erreur inconnue'), 'danger');
        }
    })
    .catch(function(error) {
        esNotify('Erreur de connexion : ' + error.message, 'danger');
    });
}

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
            esNotify('Mot de passe reinitialise avec succes !', 'success');
        } else {
            esNotify('Erreur : ' + (data.message || 'Une erreur est survenue'), 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(function(error) {
        esNotify('Erreur de connexion', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

document.getElementById('resetPasswordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newPasswordDisplay').style.display = 'none';
    document.getElementById('newPasswordValue').textContent = '';
    var btn = document.getElementById('resetPasswordBtn');
    btn.style.display = 'inline-block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-key me-1"></i>Reinitialiser a Bonjour@2025';
    document.getElementById('copyPasswordBtn').style.display = 'none';
});

function copyPassword() {
    var password = document.getElementById('newPasswordValue').textContent;
    navigator.clipboard.writeText(password).then(function() {
        esNotify('Mot de passe copie dans le presse-papiers !', 'success');
    }).catch(function() {
        esNotify('Erreur lors de la copie', 'danger');
    });
}

// -- Notification toast -------------------------------------------
function esNotify(message, type) {
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
</script>
@endpush
