@extends('layouts.app')

@section('title', $etudiant->nom_complet . ' — Fiche étudiant — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   FICHE ÉTUDIANT PREMIUM — KLASSCI Design System 2025
   Tokens, hero glassmorphism, tabs animés, rings SVG, payment timeline
=================================================================== */

/* ── Tokens ──────────────────────────────────────────────────────── */
:root {
    --k-blue:      #0453cb;
    --k-blue-2:    #5e91de;
    --k-surface:   #f4f7fb;
    --k-card:      #ffffff;
    --k-border:    #e2e8f0;
    --k-text:      #1e293b;
    --k-muted:     #64748b;
    --k-success:   #10b981;
    --k-warning:   #f59e0b;
    --k-danger:    #ef4444;
    --k-radius:    12px;
    --k-radius-lg: 20px;
    --k-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --k-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* ── Page shell ──────────────────────────────────────────────────── */
.fiche-page { background: var(--k-surface); min-height: 100vh; }

/* ── HERO ────────────────────────────────────────────────────────── */
.fiche-hero {
    position: relative;
    background: linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%);
    padding: 0 0 0;
    overflow: hidden;
}
/* SVG dot-pattern texture */
.fiche-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.12)'/%3E%3C/svg%3E");
    pointer-events: none;
}
/* glass strip bottom */
.fiche-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--k-surface) 0%, transparent 100%);
}

.hero-inner {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 0;
    display: flex; align-items: flex-end; gap: 28px; flex-wrap: wrap;
}

/* Avatar */
.hero-avatar {
    width: 108px; height: 108px; flex-shrink: 0;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,.55);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.6rem; font-weight: 700; color: rgba(255,255,255,.9);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
    backdrop-filter: blur(4px);
    margin-bottom: -20px;
}
.hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Text block */
.hero-text { flex: 1; min-width: 200px; padding-bottom: 24px; color: #fff; }
.hero-name { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; margin: 0 0 4px; line-height: 1.2; }
.hero-sub  { font-size: .9rem; opacity: .82; margin: 0 0 12px; }
.hero-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff; font-size: .78rem; font-weight: 600;
    padding: 4px 12px; border-radius: 20px;
    white-space: nowrap;
}
.hero-pill.green  { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.hero-pill.amber  { background: rgba(245,158,11,.25); border-color: rgba(245,158,11,.4); }
.hero-pill.red    { background: rgba(239,68,68,.25);  border-color: rgba(239,68,68,.4); }

/* Actions in hero */
.hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-bottom: 24px; }
.hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.hero-btn.primary { background: rgba(255,255,255,.95); color: var(--k-blue); }
.hero-btn.primary:hover { background: #fff; color: var(--k-blue); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.hero-btn.ghost:hover { background: rgba(255,255,255,.25); }
.hero-btn.danger { background: rgba(239,68,68,.2); color: #fff; border: 1px solid rgba(239,68,68,.4); }
.hero-btn.danger:hover { background: rgba(239,68,68,.35); }

/* ── Mini KPI Strip (inside hero, above tabs) ────────────────────── */
.hero-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 4px;
}
.hero-kpi {
    flex: 1; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.hero-kpi:last-child { border-right: none; }
.hero-kpi-icon { font-size: 1rem; opacity: .7; }
.hero-kpi-val { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.hero-kpi-lbl { font-size: .7rem; opacity: .7; letter-spacing: .04em; text-transform: uppercase; margin-top: 2px; }

/* ── Tab Bar ─────────────────────────────────────────────────────── */
.fiche-tabs-wrap {
    position: sticky; top: 0; z-index: 100;
    background: var(--k-card);
    box-shadow: 0 1px 0 var(--k-border);
}
.fiche-tabs {
    max-width: 1280px; margin: 0 auto;
    display: flex; overflow-x: auto; gap: 0;
    scrollbar-width: none;
    padding: 0 24px;
}
.fiche-tabs::-webkit-scrollbar { display: none; }
.fiche-tab {
    flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 0 20px; height: 52px;
    font-size: .84rem; font-weight: 600; color: var(--k-muted);
    background: none; border: none; cursor: pointer;
    position: relative; transition: color .2s;
    text-decoration: none; white-space: nowrap;
}
.fiche-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 12px; right: 12px; height: 3px;
    background: var(--k-blue); border-radius: 3px 3px 0 0;
    transform: scaleX(0); transform-origin: center;
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
}
.fiche-tab:hover { color: var(--k-blue); }
.fiche-tab.active { color: var(--k-blue); }
.fiche-tab.active::after { transform: scaleX(1); }
.fiche-tab .tab-badge {
    background: var(--k-danger); color: #fff;
    font-size: .67rem; font-weight: 700; min-width: 18px; height: 18px;
    padding: 0 5px; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center;
}

/* ── Content area ────────────────────────────────────────────────── */
.fiche-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* Tab panels */
.tab-panel { display: none; animation: fadeUp .24s ease; }
.tab-panel.active { display: block; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Section card ────────────────────────────────────────────────── */
.s-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius-lg); padding: 24px;
    box-shadow: var(--k-shadow); margin-bottom: 20px;
}
.s-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.s-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--k-text);
}
.s-card-title-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}

/* ── KPI cards with SVG progress rings ──────────────────────────── */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.kpi-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); padding: 20px 18px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--k-shadow);
    transition: box-shadow .2s, transform .2s;
}
.kpi-card:hover { box-shadow: var(--k-shadow-lg); transform: translateY(-2px); }
.kpi-ring { flex-shrink: 0; position: relative; width: 52px; height: 52px; }
.kpi-ring svg { width: 52px; height: 52px; transform: rotate(-90deg); }
.kpi-ring .ring-bg { fill: none; stroke: var(--k-border); stroke-width: 4; }
.kpi-ring .ring-fg { fill: none; stroke-width: 4; stroke-linecap: round;
    transition: stroke-dashoffset .6s cubic-bezier(.4,0,.2,1); }
.kpi-ring .ring-icon {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
}
.kpi-body { flex: 1; min-width: 0; }
.kpi-val { font-size: 1.45rem; font-weight: 800; color: var(--k-text); line-height: 1; }
.kpi-lbl { font-size: .72rem; color: var(--k-muted); font-weight: 500; margin-top: 3px; text-transform: uppercase; letter-spacing: .04em; }

/* ── Info grid ───────────────────────────────────────────────────── */
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.info-row {
    display: flex; flex-direction: column; gap: 3px;
    padding: 14px 16px; background: var(--k-surface);
    border-radius: 10px; border: 1px solid var(--k-border);
}
.info-lbl { font-size: .71rem; font-weight: 600; color: var(--k-muted); text-transform: uppercase; letter-spacing: .05em; }
.info-val { font-size: .9rem; font-weight: 600; color: var(--k-text); word-break: break-word; }
.info-val a { color: var(--k-blue); text-decoration: none; }
.info-val a:hover { text-decoration: underline; }
.info-val.mono { font-family: 'Courier New', monospace; font-size: .88rem; letter-spacing: .03em; }
.info-val.empty { color: var(--k-muted); font-weight: 400; font-style: italic; }

/* ── Semestre cards ──────────────────────────────────────────────── */
.semestre-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.sem-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); overflow: hidden;
    box-shadow: var(--k-shadow);
}
.sem-card-head {
    padding: 16px 18px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--k-border);
    background: linear-gradient(135deg, rgba(4,83,203,.04) 0%, rgba(94,145,222,.04) 100%);
}
.sem-card-name { font-size: .88rem; font-weight: 700; color: var(--k-text); }
.sem-score {
    font-size: 1.4rem; font-weight: 800; line-height: 1;
    padding: 4px 12px; border-radius: 8px;
}
.sem-score.green { color: var(--k-success); background: rgba(16,185,129,.1); }
.sem-score.amber { color: #d97706; background: rgba(245,158,11,.1); }
.sem-score.red   { color: var(--k-danger); background: rgba(239,68,68,.1); }
.sem-card-body { padding: 14px 18px; }
.mat-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--k-border); }
.mat-row:last-child { border-bottom: none; }
.mat-name { font-size: .83rem; color: var(--k-text); font-weight: 500; flex: 1; min-width: 0; }
.mat-coeff { font-size: .72rem; color: var(--k-muted); margin-right: 10px; }
.mat-note { font-size: .88rem; font-weight: 700; min-width: 36px; text-align: right; }
.mat-note.good   { color: var(--k-success); }
.mat-note.mid    { color: #d97706; }
.mat-note.bad    { color: var(--k-danger); }

/* ── Presence year rows ──────────────────────────────────────────── */
.presence-year {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); margin-bottom: 16px;
    overflow: hidden; box-shadow: var(--k-shadow);
}
.presence-year-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; cursor: pointer;
    background: linear-gradient(135deg, rgba(4,83,203,.03) 0%, rgba(94,145,222,.03) 100%);
    border-bottom: 1px solid var(--k-border);
    transition: background .2s;
}
.presence-year-head:hover { background: rgba(4,83,203,.06); }
.py-label { font-size: .9rem; font-weight: 700; color: var(--k-text); display: flex; align-items: center; gap: 10px; }
.py-stats { display: flex; align-items: center; gap: 20px; }
.py-stat { text-align: center; }
.py-stat-val { font-size: 1rem; font-weight: 700; color: var(--k-text); line-height: 1; }
.py-stat-lbl { font-size: .67rem; color: var(--k-muted); text-transform: uppercase; letter-spacing: .04em; }
.py-stat-val.green { color: var(--k-success); }
.py-stat-val.amber { color: #d97706; }
.py-stat-val.red   { color: var(--k-danger); }
/* Inline donut SVG for presence year */
.py-donut { flex-shrink: 0; }

.presence-year-body { padding: 16px 20px; }
.presence-bars { display: flex; flex-direction: column; gap: 10px; }
.pbar-row { display: flex; align-items: center; gap: 12px; }
.pbar-lbl { font-size: .78rem; color: var(--k-muted); font-weight: 600; min-width: 80px; }
.pbar-track { flex: 1; height: 8px; background: var(--k-border); border-radius: 4px; overflow: hidden; }
.pbar-fill { height: 100%; border-radius: 4px; transition: width .6s cubic-bezier(.4,0,.2,1); }
.pbar-fill.green { background: var(--k-success); }
.pbar-fill.amber { background: var(--k-warning); }
.pbar-fill.red   { background: var(--k-danger); }
.pbar-fill.blue  { background: var(--k-blue-2); }
.pbar-val { font-size: .78rem; font-weight: 700; color: var(--k-text); min-width: 32px; text-align: right; }

/* ── Payment timeline ────────────────────────────────────────────── */
.pmt-timeline { display: flex; flex-direction: column; gap: 0; }
.pmt-item {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid var(--k-border);
}
.pmt-item:last-child { border-bottom: none; }
/* Dot */
.pmt-dot-wrap { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; padding-top: 2px; }
.pmt-dot {
    width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
    border: 2px solid;
}
.pmt-dot.valide  { background: var(--k-success); border-color: var(--k-success); }
.pmt-dot.attente { background: var(--k-warning); border-color: var(--k-warning); }
.pmt-dot.rejete  { background: var(--k-danger); border-color: var(--k-danger); }
.pmt-line { flex: 1; width: 2px; background: var(--k-border); min-height: 24px; margin-top: 4px; }
/* Body */
.pmt-body { flex: 1; min-width: 0; }
.pmt-motif { font-size: .88rem; font-weight: 700; color: var(--k-text); }
.pmt-meta  { font-size: .76rem; color: var(--k-muted); margin-top: 2px; display: flex; gap: 12px; flex-wrap: wrap; }
.pmt-meta span { display: inline-flex; align-items: center; gap: 4px; }
/* Right side */
.pmt-right { text-align: right; flex-shrink: 0; }
.pmt-amount { font-size: 1rem; font-weight: 800; color: var(--k-text); }
.pmt-badge {
    display: inline-block; margin-top: 4px;
    font-size: .69rem; font-weight: 700; padding: 2px 9px; border-radius: 10px; text-transform: uppercase; letter-spacing: .04em;
}
.pmt-badge.valide  { background: rgba(16,185,129,.12); color: #059669; }
.pmt-badge.attente { background: rgba(245,158,11,.12);  color: #d97706; }
.pmt-badge.rejete  { background: rgba(239,68,68,.12);   color: #dc2626; }
.pmt-actions { margin-top: 6px; display: flex; gap: 6px; justify-content: flex-end; }
.pmt-act-btn {
    font-size: .73rem; font-weight: 600; padding: 4px 10px; border-radius: 6px;
    text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 4px;
}
.pmt-act-btn.view  { background: rgba(4,83,203,.1); color: var(--k-blue); }
.pmt-act-btn.view:hover { background: var(--k-blue); color: #fff; }
.pmt-act-btn.del   { background: rgba(239,68,68,.1); color: var(--k-danger); }
.pmt-act-btn.del:hover { background: var(--k-danger); color: #fff; }

/* ── Parent accordion ────────────────────────────────────────────── */
.parent-item {
    border: 1px solid var(--k-border); border-radius: var(--k-radius);
    overflow: hidden; margin-bottom: 12px;
}
.parent-head {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px; cursor: pointer;
    background: var(--k-surface); transition: background .15s;
}
.parent-head:hover { background: rgba(4,83,203,.05); }
.parent-avatar-initials {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 700; color: #fff;
}
.parent-head-info { flex: 1; min-width: 0; }
.parent-name { font-size: .9rem; font-weight: 700; color: var(--k-text); }
.parent-lien { font-size: .76rem; color: var(--k-muted); }
.parent-toggle { color: var(--k-muted); transition: transform .2s; }
.parent-item.open .parent-toggle { transform: rotate(180deg); }
.parent-body { padding: 0 18px; max-height: 0; overflow: hidden; transition: max-height .3s ease, padding .3s ease; }
.parent-item.open .parent-body { max-height: 300px; padding: 14px 18px; }
.parent-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }

/* ── Status badge ────────────────────────────────────────────────── */
.status-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
}
.status-chip.actif    { background: rgba(16,185,129,.12);  color: #059669; }
.status-chip.inactif  { background: rgba(100,116,139,.12); color: var(--k-muted); }
.status-chip.abandon  { background: rgba(239,68,68,.12);   color: #dc2626; }
.status-chip::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ── Empty state ─────────────────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 48px 24px; color: var(--k-muted);
}
.empty-state i { font-size: 2.4rem; opacity: .35; margin-bottom: 12px; display: block; }
.empty-state p { font-size: .88rem; }

/* ── Inscription card ────────────────────────────────────────────── */
.insc-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-left: 4px solid var(--k-blue);
    border-radius: var(--k-radius); padding: 18px 20px;
    margin-bottom: 14px; box-shadow: var(--k-shadow);
}
.insc-card:hover { box-shadow: var(--k-shadow-lg); }
.insc-year { font-size: 1rem; font-weight: 800; color: var(--k-blue); }
.insc-meta { font-size: .82rem; color: var(--k-muted); margin: 6px 0 12px; display: flex; gap: 16px; flex-wrap: wrap; }
.insc-meta span { display: inline-flex; align-items: center; gap: 5px; }
.insc-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.insc-btn {
    font-size: .79rem; font-weight: 600; padding: 6px 14px; border-radius: 7px;
    text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer;
}
.insc-btn.view   { background: rgba(4,83,203,.08); color: var(--k-blue); }
.insc-btn.view:hover { background: var(--k-blue); color: #fff; }
.insc-btn.pdf    { background: rgba(239,68,68,.08); color: var(--k-danger); }
.insc-btn.pdf:hover  { background: var(--k-danger); color: #fff; }
.insc-btn.edit   { background: rgba(245,158,11,.08); color: #d97706; }
.insc-btn.edit:hover { background: var(--k-warning); color: #fff; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .hero-inner { padding: 24px 16px 0; gap: 16px; }
    .hero-name  { font-size: 1.35rem; }
    .hero-kpi   { padding: 10px 14px; }
    .hero-kpi-val { font-size: .95rem; }
    .hero-kpi-lbl { font-size: .62rem; }
    .fiche-content { padding: 20px 16px 48px; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .info-grid { grid-template-columns: 1fr; }
    .semestre-grid { grid-template-columns: 1fr; }
    .py-stats { gap: 12px; }
    .hero-actions { padding-bottom: 16px; }
}
@media (max-width: 480px) {
    .hero-kpi-strip { display: none; }
    .hero-avatar { width: 80px; height: 80px; font-size: 2rem; }
    .fiche-tab { padding: 0 14px; font-size: .78rem; }
    .kpi-grid { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
<div class="fiche-page">

{{-- ════════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════════ --}}
<div class="fiche-hero">
    <div class="hero-inner">
        {{-- Avatar --}}
        <div class="hero-avatar">
            @if($etudiant->photo)
                <img src="{{ asset('storage/photos/etudiants/' . $etudiant->photo) }}"
                     alt="{{ $etudiant->nom_complet }}"
                     onerror="this.parentElement.innerHTML='<i class=\'fas fa-user-graduate\'></i>'">
            @else
                {{ strtoupper(substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($etudiant->nom, 0, 1)) }}
            @endif
        </div>

        {{-- Text --}}
        <div class="hero-text">
            <h1 class="hero-name">{{ strtoupper($etudiant->nom) }} {{ $etudiant->prenoms }}</h1>
            <p class="hero-sub">
                @php $inscA = $etudiant->inscriptions->firstWhere('statut', 'actif') ?? $etudiant->inscriptions->first(); @endphp
                @if($inscA && $inscA->classe)
                    {{ $inscA->classe->nom }}
                    @if($inscA->classe->filiere) · {{ $inscA->classe->filiere->nom }} @endif
                    @if($inscA->classe->niveau) · {{ $inscA->classe->niveau->nom }} @endif
                @else
                    Étudiant
                @endif
            </p>
            <div class="hero-pills">
                <span class="hero-pill"><i class="fas fa-id-card"></i> {{ $etudiant->matricule ?? 'Non attribué' }}</span>
                @if($etudiant->statut === 'actif')
                    <span class="hero-pill green"><i class="fas fa-circle" style="font-size:.5rem"></i> Actif</span>
                @elseif($etudiant->statut === 'inactif')
                    <span class="hero-pill"><i class="fas fa-circle" style="font-size:.5rem"></i> Inactif</span>
                @elseif($etudiant->statut === 'abandon')
                    <span class="hero-pill red"><i class="fas fa-circle" style="font-size:.5rem"></i> Abandon</span>
                @endif
                @if($etudiant->nationalite)
                    <span class="hero-pill"><i class="fas fa-flag"></i> {{ $etudiant->nationalite }}</span>
                @endif
            </div>
        </div>

        {{-- Année courante — top-right --}}
        @if($inscA && $inscA->anneeUniversitaire)
        <div style="position:absolute; top:16px; right:16px; z-index:10">
            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.3); color:#fff; font-size:.78rem; font-weight:700; padding:6px 14px; border-radius:20px; letter-spacing:.02em;">
                <i class="fas fa-calendar-alt"></i>
                {{ $inscA->anneeUniversitaire->libelle ?? $inscA->anneeUniversitaire->name ?? 'N/A' }}
            </span>
        </div>
        @endif

        {{-- Actions --}}
        <div class="hero-actions">
            @can('update', $etudiant)
            <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}" class="hero-btn primary">
                <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Modifier</span>
            </a>
            @endcan
            <a href="{{ route('esbtp.etudiants.index') }}" class="hero-btn ghost">
                <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Retour</span>
            </a>
            @can('delete', $etudiant)
            <form action="{{ route('esbtp.etudiants.destroy', $etudiant) }}" method="POST" style="margin:0"
                  onsubmit="return confirm('Supprimer définitivement {{ addslashes($etudiant->nom_complet) }} ?')">
                @csrf @method('DELETE')
                <button type="submit" class="hero-btn danger">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- KPI Strip --}}
    @php
        $totalPaiements  = $etudiant->inscriptions->sum(fn($i) => $i->paiements->where('status','validé')->sum('montant'));
        $totalAbsences   = $etudiant->absences->count();
        $nbInscriptions  = $etudiant->inscriptions->count();

        // taux presence depuis les absences de l'étudiant
        $tauxPresence = null;
        $inscActive = $etudiant->inscriptions->firstWhere('statut', 'actif') ?? $etudiant->inscriptions->first();
        if ($inscActive && $inscActive->anneeUniversitaire) {
            $anneeId = $inscActive->anneeUniversitaire->id;
            $attStats = \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeId)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN statut='present' THEN 1 ELSE 0 END) as nb_pres, SUM(CASE WHEN statut IN ('retard','late') THEN 1 ELSE 0 END) as nb_ret")
                ->first();
            if ($attStats && $attStats->total > 0) {
                $tauxPresence = round((($attStats->nb_pres + $attStats->nb_ret) / $attStats->total) * 100, 1);
            }
        }
    @endphp
    <div class="hero-kpi-strip">
        <div class="hero-kpi">
            <i class="fas fa-file-alt hero-kpi-icon"></i>
            <div>
                <div class="hero-kpi-val">{{ $nbInscriptions }}</div>
                <div class="hero-kpi-lbl">Inscriptions</div>
            </div>
        </div>
        <div class="hero-kpi">
            <i class="fas fa-percentage hero-kpi-icon"></i>
            <div>
                <div class="hero-kpi-val">{{ $tauxPresence !== null ? $tauxPresence . '%' : '—' }}</div>
                <div class="hero-kpi-lbl">Présence</div>
            </div>
        </div>
        <div class="hero-kpi">
            <i class="fas fa-user-times hero-kpi-icon"></i>
            <div>
                <div class="hero-kpi-val">{{ $totalAbsences }}</div>
                <div class="hero-kpi-lbl">Absences</div>
            </div>
        </div>
        <div class="hero-kpi">
            <i class="fas fa-coins hero-kpi-icon"></i>
            <div>
                <div class="hero-kpi-val">{{ number_format($totalPaiements, 0, ',', ' ') }}</div>
                <div class="hero-kpi-lbl">Payé (FCFA)</div>
            </div>
        </div>
    </div>
</div>{{-- /hero --}}

{{-- ════════════════════════════════════════════════════════════════
     TAB BAR
════════════════════════════════════════════════════════════════ --}}
<div class="fiche-tabs-wrap">
    <div class="fiche-tabs" role="tablist">
        <button class="fiche-tab active" data-tab="overview"   role="tab">
            <i class="fas fa-th-large"></i> Vue d'ensemble
        </button>
        <button class="fiche-tab" data-tab="academique" role="tab">
            <i class="fas fa-graduation-cap"></i> Académique
        </button>
        <button class="fiche-tab" data-tab="presences"  role="tab">
            <i class="fas fa-calendar-check"></i> Présences
        </button>
        <button class="fiche-tab" data-tab="finances"   role="tab">
            <i class="fas fa-wallet"></i> Finances
        </button>
        <button class="fiche-tab" data-tab="profil"     role="tab">
            <i class="fas fa-user-circle"></i> Profil
        </button>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     CONTENT
════════════════════════════════════════════════════════════════ --}}
<div class="fiche-content">

{{-- ─── TAB: VUE D'ENSEMBLE ─────────────────────────────────────── --}}
<div class="tab-panel active" id="tab-overview">

    {{-- KPI Cards --}}
    @php
        // Calcul KPI globaux depuis les vraies variables
        $kpiMoyenneGen = null;
        $kpiTauxPres   = $tauxPresence;
        $kpiAbsTotal   = $etudiant->absences->count();
        $kpiPaiTotal   = $statistiques['paiements_valides'] ?? 0;
        $kpiPaiDu      = 0;

        // Moyenne depuis bulletins de l'inscription active
        $allMoys = [];
        foreach($etudiant->inscriptions as $inscIt) {
            foreach($inscIt->bulletins ?? [] as $bul) {
                if($bul->moyenne_generale !== null) $allMoys[] = $bul->moyenne_generale;
            }
        }
        if(count($allMoys)) $kpiMoyenneGen = round(array_sum($allMoys)/count($allMoys), 2);
    @endphp

    <div class="kpi-grid">
        {{-- Moyenne --}}
        @php $m = $kpiMoyenneGen; $mc = $m >= 12 ? '#10b981' : ($m >= 10 ? '#f59e0b' : '#ef4444'); $mpct = min(100, ($m/20)*100); @endphp
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22"
                        stroke="{{ $mc }}"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - $mpct/100),1) }}"/>
                </svg>
                <span class="ring-icon" style="color:{{ $mc }}"><i class="fas fa-star" style="font-size:.75rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:{{ $mc }}">{{ $m !== null ? number_format($m,2) : '—' }}<small style="font-size:.6em;font-weight:500">/20</small></div>
                <div class="kpi-lbl">Moy. générale</div>
            </div>
        </div>

        {{-- Présence --}}
        @php $p = $kpiTauxPres; $pc = $p >= 80 ? '#10b981' : ($p >= 60 ? '#f59e0b' : '#ef4444'); @endphp
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22"
                        stroke="{{ $pc }}"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - ($p ?? 0)/100),1) }}"/>
                </svg>
                <span class="ring-icon" style="color:{{ $pc }}"><i class="fas fa-user-check" style="font-size:.75rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:{{ $pc }}">{{ $p !== null ? $p . '%' : '—' }}</div>
                <div class="kpi-lbl">Taux présence</div>
            </div>
        </div>

        {{-- Absences --}}
        @php $ac = $kpiAbsTotal > 20 ? '#ef4444' : ($kpiAbsTotal > 10 ? '#f59e0b' : '#10b981'); $apct = min(100, $kpiAbsTotal * 2); @endphp
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22"
                        stroke="{{ $ac }}"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - $apct/100),1) }}"/>
                </svg>
                <span class="ring-icon" style="color:{{ $ac }}"><i class="fas fa-calendar-times" style="font-size:.75rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:{{ $ac }}">{{ $kpiAbsTotal }}</div>
                <div class="kpi-lbl">Absences totales</div>
            </div>
        </div>

        {{-- Paiements --}}
        @php
            $totalDu2 = $kpiPaiTotal + $kpiPaiDu;
            $ppct = $totalDu2 > 0 ? min(100, round($kpiPaiTotal/$totalDu2*100)) : 0;
            $payc = $ppct >= 80 ? '#10b981' : ($ppct >= 50 ? '#f59e0b' : '#ef4444');
        @endphp
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22"
                        stroke="{{ $payc }}"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - $ppct/100),1) }}"/>
                </svg>
                <span class="ring-icon" style="color:{{ $payc }}"><i class="fas fa-coins" style="font-size:.75rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:{{ $payc }}">{{ $ppct }}%</div>
                <div class="kpi-lbl">Paiements réglés</div>
            </div>
        </div>
    </div>

    {{-- Quick info --}}
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-info-circle"></i></div>
                Informations rapides
            </div>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">Matricule</span>
                <span class="info-val mono">{{ $etudiant->matricule ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Statut</span>
                <span class="info-val">
                    <span class="status-chip {{ $etudiant->statut }}">{{ ucfirst($etudiant->statut) }}</span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Classe actuelle</span>
                <span class="info-val">{{ $inscA?->classe?->nom ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Filière</span>
                <span class="info-val">{{ $inscA?->classe?->filiere?->nom ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Email</span>
                <span class="info-val">
                    @if($etudiant->email_personnel)
                        <a href="mailto:{{ $etudiant->email_personnel }}">{{ $etudiant->email_personnel }}</a>
                    @else
                        <span class="empty">Non renseigné</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Téléphone</span>
                <span class="info-val">
                    @if($etudiant->telephone)
                        <a href="tel:{{ $etudiant->telephone }}">{{ $etudiant->telephone }}</a>
                    @else
                        <span class="empty">Non renseigné</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Inscriptions rapides --}}
    @if($etudiant->inscriptions->count())
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-file-signature"></i></div>
                Inscriptions ({{ $etudiant->inscriptions->count() }})
            </div>
            <a href="{{ route('esbtp.inscriptions.create', ['etudiant_id' => $etudiant->id]) }}" class="insc-btn view">
                <i class="fas fa-plus"></i> Nouvelle
            </a>
        </div>
        @foreach($etudiant->inscriptions->sortByDesc(fn($i) => $i->annee_universitaire ?? '') as $insc)
        @php
            $statInsc = $insc->statut ?? 'pending';
            $anneeLabel = $insc->annee_universitaire ?? ($insc->anneeAcademique?->libelle ?? 'N/A');
        @endphp
        <div class="insc-card">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <div class="insc-year">{{ $anneeLabel }}</div>
                <span class="status-chip {{ $statInsc === 'actif' ? 'actif' : ($statInsc === 'validé' ? 'actif' : 'inactif') }}">
                    {{ ucfirst($statInsc) }}
                </span>
            </div>
            <div class="insc-meta">
                @if($insc->classe) <span><i class="fas fa-chalkboard"></i> {{ $insc->classe->nom }}</span> @endif
                @if($insc->classe?->filiere) <span><i class="fas fa-project-diagram"></i> {{ $insc->classe->filiere->nom }}</span> @endif
                <span><i class="fas fa-calendar-alt"></i> {{ $insc->created_at?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="insc-actions">
                <a href="{{ route('esbtp.inscriptions.show', $insc) }}" class="insc-btn view">
                    <i class="fas fa-eye"></i> Voir
                </a>
                @can('update', $insc)
                <a href="{{ route('esbtp.inscriptions.edit', $insc) }}" class="insc-btn edit">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>{{-- /tab-overview --}}

{{-- ─── TAB: ACADÉMIQUE ────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-academique">
    @php
        $inscriptionsTriees = $etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date);
    @endphp
    @if($inscriptionsTriees->count())
        @foreach($inscriptionsTriees as $inscAc)
        @php
            $anneeLabel = optional($inscAc->anneeUniversitaire)->libelle ?? optional($inscAc->anneeUniversitaire)->name ?? 'Année N/A';
            $classeLabel = optional($inscAc->classe)->nom;
            $bulletinsAc = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', optional($inscAc->anneeUniversitaire)->id)
                ->orderBy('periode')
                ->get();
        @endphp
        <div class="s-card">
            <div class="s-card-header">
                <div class="s-card-title">
                    <div class="s-card-title-icon"><i class="fas fa-calendar-alt"></i></div>
                    {{ $anneeLabel }}
                    @if($classeLabel)
                        <span style="font-size:.78rem; font-weight:500; color:var(--k-muted)">— {{ $classeLabel }}</span>
                    @endif
                </div>
                @if($bulletinsAc->avg('moyenne_generale') !== null)
                    @php $ma = round($bulletinsAc->avg('moyenne_generale'), 2); @endphp
                    <span class="sem-score {{ $ma >= 12 ? 'green' : ($ma >= 10 ? 'amber' : 'red') }}">
                        {{ number_format($ma, 2) }}/20
                    </span>
                @endif
            </div>

            @if($bulletinsAc->count())
                <div class="semestre-grid">
                @foreach($bulletinsAc as $bul)
                @php $mg = $bul->moyenne_generale; @endphp
                <div class="sem-card">
                    <div class="sem-card-head">
                        <span class="sem-card-name">{{ ucfirst(str_replace('semestre', 'Semestre ', $bul->periode)) }}</span>
                        @if($mg !== null)
                            <span class="sem-score {{ $mg >= 12 ? 'green' : ($mg >= 10 ? 'amber' : 'red') }}">
                                {{ number_format($mg, 2) }}
                            </span>
                        @endif
                    </div>
                    <div class="sem-card-body">
                        @if($bul->rang)
                            <div class="mat-row">
                                <span class="mat-name" style="font-weight:600">Rang</span>
                                <span class="mat-note good">{{ $bul->rang }}</span>
                            </div>
                        @endif
                        @if($bul->mention)
                            <div class="mat-row">
                                <span class="mat-name" style="font-weight:600">Mention</span>
                                <span class="mat-coeff">{{ $bul->mention }}</span>
                            </div>
                        @endif
                        <div class="mat-row" style="margin-top:8px">
                            <a href="{{ route('esbtp.bulletins.download', $bul) }}" class="insc-btn pdf" style="font-size:.75rem; padding:4px 10px" target="_blank">
                                <i class="fas fa-file-pdf"></i> Bulletin PDF
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
            @else
                {{-- Pas de bulletin calculé — afficher les résultats bruts si dispo --}}
                @php
                    $resultatsAc = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                        ->whereHas('evaluation', fn($q) => $q->where('annee_universitaire_id', optional($inscAc->anneeUniversitaire)->id))
                        ->with(['matiere', 'evaluation'])
                        ->get()
                        ->groupBy(fn($r) => $r->evaluation->semestre ?? 'N/A');
                @endphp
                @if($resultatsAc->count())
                    <div class="semestre-grid">
                    @foreach($resultatsAc as $semNum => $resultsSem)
                    <div class="sem-card">
                        <div class="sem-card-head">
                            <span class="sem-card-name">Semestre {{ $semNum }}</span>
                            @php $mgR = round($resultsSem->avg('moyenne'), 2); @endphp
                            @if($mgR)
                                <span class="sem-score {{ $mgR >= 12 ? 'green' : ($mgR >= 10 ? 'amber' : 'red') }}">{{ number_format($mgR, 2) }}</span>
                            @endif
                        </div>
                        <div class="sem-card-body">
                            @foreach($resultsSem as $res)
                            @php $n = $res->moyenne; @endphp
                            <div class="mat-row">
                                <span class="mat-name">{{ optional($res->matiere)->nom ?? '—' }}</span>
                                <span class="mat-note {{ ($n !== null && $n >= 12) ? 'good' : (($n !== null && $n >= 10) ? 'mid' : 'bad') }}">
                                    {{ $n !== null ? number_format($n, 2) : '—' }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    </div>
                @else
                    <p style="font-size:.82rem; color:var(--k-muted); text-align:center; padding:16px 0; margin:0">Aucune note enregistrée pour cette année</p>
                @endif
            @endif
        </div>
        @endforeach
    @else
        <div class="s-card">
            <div class="empty-state">
                <i class="fas fa-graduation-cap"></i>
                <p>Aucun résultat académique disponible</p>
            </div>
        </div>
    @endif
</div>{{-- /tab-academique --}}

{{-- ─── TAB: PRÉSENCES ─────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-presences">
    @php
        $inscriptionsPresences = $etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date);
    @endphp
    @if($inscriptionsPresences->count())
        @foreach($inscriptionsPresences as $inscPres)
        @php
            $anneePresId = optional($inscPres->anneeUniversitaire)->id;
            $anneePresLabel = optional($inscPres->anneeUniversitaire)->libelle ?? optional($inscPres->anneeUniversitaire)->name ?? 'Année N/A';
            $attRow = $anneePresId
                ? \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
                    ->where('annee_universitaire_id', $anneePresId)
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN statut='present' THEN 1 ELSE 0 END) as nb_presences, SUM(CASE WHEN statut='absent' THEN 1 ELSE 0 END) as nb_absences, SUM(CASE WHEN statut='excuse' THEN 1 ELSE 0 END) as nb_absences_just, SUM(CASE WHEN statut IN ('retard','late') THEN 1 ELSE 0 END) as nb_retards")
                    ->first()
                : null;
            $total  = (int)($attRow->total ?? 0);
            $pres   = (int)($attRow->nb_presences ?? 0);
            $retard = (int)($attRow->nb_retards ?? 0);
            $abs    = (int)($attRow->nb_absences ?? 0);
            $just   = (int)($attRow->nb_absences_just ?? 0);
            $taux   = $total > 0 ? round(($pres + $retard) / $total * 100, 1) : null;

            // Donut: rayon 18, circum = 2π*18 ≈ 113.1
            $r = 18; $circum = round(2*3.14159*$r, 1);
            $presRatio = $total > 0 ? ($pres+$retard)/$total : 0;
            $presOffset = round($circum * (1 - $presRatio), 1);
            $presColor = ($taux ?? 0) >= 80 ? '#10b981' : (($taux ?? 0) >= 60 ? '#f59e0b' : '#ef4444');
        @endphp
        <div class="presence-year">
            <div class="presence-year-head" onclick="this.closest('.presence-year').classList.toggle('open')">
                <span class="py-label">
                    <i class="fas fa-calendar-alt" style="color:var(--k-blue)"></i>
                    {{ $anneePresLabel }}
                </span>
                <div style="display:flex;align-items:center;gap:20px">
                    <div class="py-stats">
                        <div class="py-stat">
                            <div class="py-stat-val green">{{ $pres + $retard }}</div>
                            <div class="py-stat-lbl">Présents</div>
                        </div>
                        <div class="py-stat">
                            <div class="py-stat-val red">{{ $abs }}</div>
                            <div class="py-stat-lbl">Absences</div>
                        </div>
                        <div class="py-stat">
                            <div class="py-stat-val amber">{{ $just }}</div>
                            <div class="py-stat-lbl">Justifiés</div>
                        </div>
                        <div class="py-stat">
                            <div class="py-stat-val {{ ($taux??0) >= 80 ? 'green' : (($taux??0) >= 60 ? 'amber' : 'red') }}">
                                {{ $taux !== null ? $taux . '%' : '—' }}
                            </div>
                            <div class="py-stat-lbl">Taux</div>
                        </div>
                    </div>
                    {{-- Inline donut SVG --}}
                    <svg class="py-donut" width="44" height="44" viewBox="0 0 44 44" style="transform:rotate(-90deg)">
                        <circle cx="22" cy="22" r="{{ $r }}" fill="none" stroke="var(--k-border)" stroke-width="4"/>
                        <circle cx="22" cy="22" r="{{ $r }}" fill="none"
                            stroke="{{ $presColor }}" stroke-width="4" stroke-linecap="round"
                            stroke-dasharray="{{ $circum }}" stroke-dashoffset="{{ $presOffset }}"/>
                    </svg>
                    <i class="fas fa-chevron-down" style="color:var(--k-muted); font-size:.75rem"></i>
                </div>
            </div>
            @if($total > 0)
            <div class="presence-year-body">
                <div class="presence-bars">
                    <div class="pbar-row">
                        <span class="pbar-lbl">Présences</span>
                        <div class="pbar-track"><div class="pbar-fill green" style="width:{{ $total > 0 ? round($pres/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:var(--k-success)">{{ $pres }}</span>
                    </div>
                    <div class="pbar-row">
                        <span class="pbar-lbl">Retards</span>
                        <div class="pbar-track"><div class="pbar-fill amber" style="width:{{ $total > 0 ? round($retard/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:#d97706">{{ $retard }}</span>
                    </div>
                    <div class="pbar-row">
                        <span class="pbar-lbl">Absences</span>
                        <div class="pbar-track"><div class="pbar-fill red" style="width:{{ $total > 0 ? round($abs/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:var(--k-danger)">{{ $abs }}</span>
                    </div>
                    @if($just > 0)
                    <div class="pbar-row">
                        <span class="pbar-lbl">Justifiés</span>
                        <div class="pbar-track"><div class="pbar-fill blue" style="width:{{ $total > 0 ? round($just/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:var(--k-blue-2)">{{ $just }}</span>
                    </div>
                    @endif
                </div>
                <p style="font-size:.75rem; color:var(--k-muted); margin-top:12px; margin-bottom:0">
                    Total séances enregistrées : <strong>{{ $total }}</strong>
                </p>
            </div>
            @endif
        </div>
        @endforeach
    @else
        <div class="s-card">
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>Aucune donnée de présence disponible</p>
            </div>
        </div>
    @endif
</div>{{-- /tab-presences --}}

{{-- ─── TAB: FINANCES ──────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-finances">
    @php
        // Collect all paiements across inscriptions
        $allPaiements = collect();
        foreach($etudiant->inscriptions as $insc) {
            foreach($insc->paiements ?? [] as $pai) {
                $pai->_annee = $insc->annee_universitaire ?? ($insc->anneeAcademique?->libelle ?? 'N/A');
                $allPaiements->push($pai);
            }
        }
        $allPaiements = $allPaiements->sortByDesc('date_paiement');
    @endphp

    {{-- Financial summary --}}
    @php
        $finTotalPaye   = $statistiques['paiements_valides'] ?? 0;
        $finEnAttente   = $statistiques['paiements_en_attente'] ?? 0;
        $finNbPaiements = $statistiques['nombre_paiements'] ?? $allPaiements->count();
        $finReliquats   = $statistiques['total_reliquats_entrants'] ?? 0;
        $finTotal       = $finTotalPaye + $finEnAttente;
    @endphp
    <div class="kpi-grid" style="margin-bottom:20px">
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22" stroke="#10b981"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - min(1, $finTotalPaye / max(1, $finTotal))), 1) }}"/>
                </svg>
                <span class="ring-icon" style="color:#10b981"><i class="fas fa-check" style="font-size:.7rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:#10b981">{{ number_format($finTotalPaye, 0, ',', ' ') }}</div>
                <div class="kpi-lbl">Total validé (FCFA)</div>
            </div>
        </div>
        @if($finEnAttente > 0)
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22" stroke="#f59e0b"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}" stroke-dashoffset="{{ round(2*3.14159*22*.35,1) }}"/>
                </svg>
                <span class="ring-icon" style="color:#f59e0b"><i class="fas fa-clock" style="font-size:.7rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:#f59e0b">{{ number_format($finEnAttente, 0, ',', ' ') }}</div>
                <div class="kpi-lbl">En attente (FCFA)</div>
            </div>
        </div>
        @endif
        @if($finReliquats > 0)
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22" stroke="#ef4444"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}" stroke-dashoffset="{{ round(2*3.14159*22*.5,1) }}"/>
                </svg>
                <span class="ring-icon" style="color:#ef4444"><i class="fas fa-exclamation" style="font-size:.8rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:#ef4444">{{ number_format($finReliquats, 0, ',', ' ') }}</div>
                <div class="kpi-lbl">Reliquats (FCFA)</div>
            </div>
        </div>
        @endif
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22" stroke="var(--k-blue)"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}" stroke-dashoffset="{{ round(2*3.14159*22*.5,1) }}"/>
                </svg>
                <span class="ring-icon" style="color:var(--k-blue)"><i class="fas fa-list" style="font-size:.7rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:var(--k-blue)">{{ $finNbPaiements }}</div>
                <div class="kpi-lbl">Transactions</div>
            </div>
        </div>
    </div>

    {{-- Payment timeline --}}
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-history"></i></div>
                Historique des paiements
            </div>
        </div>

        @if($allPaiements->count())
        <div class="pmt-timeline">
            @foreach($allPaiements as $loop_idx => $pai)
            @php
                $status = $pai->status ?? $pai->statut ?? 'en_attente';
                $statusKey = match(true) {
                    str_contains(strtolower($status), 'valid') => 'valide',
                    str_contains(strtolower($status), 'attente') => 'attente',
                    str_contains(strtolower($status), 'rejet') => 'rejete',
                    default => 'attente'
                };
                $statusLabel = match($statusKey) {
                    'valide'  => 'Validé',
                    'attente' => 'En attente',
                    'rejete'  => 'Rejeté',
                    default   => ucfirst($status)
                };
                $isLast = $loop_idx === $allPaiements->count() - 1;
            @endphp
            <div class="pmt-item">
                <div class="pmt-dot-wrap">
                    <div class="pmt-dot {{ $statusKey }}"></div>
                    @if(!$isLast) <div class="pmt-line"></div> @endif
                </div>
                <div class="pmt-body">
                    <div class="pmt-motif">{{ $pai->motif ?? 'Paiement' }}</div>
                    <div class="pmt-meta">
                        @if($pai->date_paiement)
                        <span><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($pai->date_paiement)->format('d/m/Y') }}</span>
                        @endif
                        @if($pai->mode_paiement)
                        <span><i class="fas fa-credit-card"></i> {{ $pai->mode_paiement }}</span>
                        @endif
                        @if($pai->numero_recu)
                        <span><i class="fas fa-receipt"></i> {{ $pai->numero_recu }}</span>
                        @endif
                        <span><i class="fas fa-folder"></i> {{ $pai->_annee }}</span>
                    </div>
                </div>
                <div class="pmt-right">
                    <div class="pmt-amount">{{ number_format($pai->montant, 0, ',', ' ') }} <small style="font-size:.65em;font-weight:500">FCFA</small></div>
                    <span class="pmt-badge {{ $statusKey }}">{{ $statusLabel }}</span>
                    <div class="pmt-actions">
                        @if(isset($pai->inscription_id))
                        <a href="{{ route('esbtp.paiements.show', $pai) }}" class="pmt-act-btn view">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endif
                        @can('delete', $pai)
                        <form action="{{ route('esbtp.paiements.destroy', $pai) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Supprimer ce paiement ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="pmt-act-btn del"><i class="fas fa-trash"></i></button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <div class="empty-state">
                <i class="fas fa-wallet"></i>
                <p>Aucun paiement enregistré</p>
            </div>
        @endif
    </div>
</div>{{-- /tab-finances --}}

{{-- ─── TAB: PROFIL ─────────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-profil">

    {{-- Infos personnelles --}}
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-id-card"></i></div>
                Informations personnelles
            </div>
        </div>
        <div class="info-grid">
            <div class="info-row"><span class="info-lbl">Nom</span><span class="info-val">{{ strtoupper($etudiant->nom) }}</span></div>
            <div class="info-row"><span class="info-lbl">Prénoms</span><span class="info-val">{{ $etudiant->prenoms ?? '—' }}</span></div>
            <div class="info-row">
                <span class="info-lbl">Date de naissance</span>
                <span class="info-val">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : '—' }}{{ $etudiant->age ? ' (' . $etudiant->age . ' ans)' : '' }}</span>
            </div>
            <div class="info-row"><span class="info-lbl">Lieu de naissance</span><span class="info-val">{{ $etudiant->lieu_naissance ?? '—' }}</span></div>
            <div class="info-row"><span class="info-lbl">Nationalité</span><span class="info-val">{{ $etudiant->nationalite ?? '—' }}</span></div>
            <div class="info-row"><span class="info-lbl">Sexe</span><span class="info-val">{{ $etudiant->sexe === 'M' ? 'Masculin' : ($etudiant->sexe === 'F' ? 'Féminin' : ($etudiant->sexe ?? '—')) }}</span></div>
            <div class="info-row">
                <span class="info-lbl">Email personnel</span>
                <span class="info-val">
                    @if($etudiant->email_personnel)
                        <a href="mailto:{{ $etudiant->email_personnel }}">{{ $etudiant->email_personnel }}</a>
                    @else <span class="empty">Non renseigné</span> @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Téléphone</span>
                <span class="info-val">
                    @if($etudiant->telephone)
                        <a href="tel:{{ $etudiant->telephone }}">{{ $etudiant->telephone }}</a>
                    @else <span class="empty">Non renseigné</span> @endif
                </span>
            </div>
            <div class="info-row"><span class="info-lbl">Adresse</span><span class="info-val">{{ $etudiant->adresse ?? '—' }}</span></div>
            @if($etudiant->statut === 'abandon')
            <div class="info-row"><span class="info-lbl">Date abandon</span><span class="info-val">{{ $etudiant->date_abandon ? \Carbon\Carbon::parse($etudiant->date_abandon)->format('d/m/Y') : '—' }}</span></div>
            <div class="info-row"><span class="info-lbl">Motif abandon</span><span class="info-val">{{ $etudiant->motif_abandon ?? '—' }}</span></div>
            @endif
        </div>
    </div>

    {{-- Compte utilisateur --}}
    @if($etudiant->user)
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-user-lock"></i></div>
                Compte utilisateur
            </div>
        </div>
        <div class="info-grid">
            <div class="info-row"><span class="info-lbl">Email connexion</span><span class="info-val"><a href="mailto:{{ $etudiant->user->email }}">{{ $etudiant->user->email }}</a></span></div>
            <div class="info-row">
                <span class="info-lbl">Dernière connexion</span>
                <span class="info-val">{{ $etudiant->user->last_login_at ? \Carbon\Carbon::parse($etudiant->user->last_login_at)->diffForHumans() : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Compte créé</span>
                <span class="info-val">{{ $etudiant->user->created_at?->format('d/m/Y') ?? '—' }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Parents / Tuteurs --}}
    @if($etudiant->parents && $etudiant->parents->count())
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-users"></i></div>
                Parents / Tuteurs ({{ $etudiant->parents->count() }})
            </div>
        </div>
        @foreach($etudiant->parents as $parent)
        <div class="parent-item" id="parent-{{ $parent->id }}">
            <div class="parent-head" onclick="document.getElementById('parent-{{ $parent->id }}').classList.toggle('open')">
                <div class="parent-avatar-initials">
                    {{ strtoupper(substr($parent->prenoms ?? 'P', 0, 1)) }}{{ strtoupper(substr($parent->nom, 0, 1)) }}
                </div>
                <div class="parent-head-info">
                    <div class="parent-name">{{ strtoupper($parent->nom) }} {{ $parent->prenoms }}</div>
                    <div class="parent-lien">{{ $parent->lien_parente ?? 'Tuteur' }}{{ $parent->profession ? ' · ' . $parent->profession : '' }}</div>
                </div>
                <i class="fas fa-chevron-down parent-toggle"></i>
            </div>
            <div class="parent-body">
                <div class="parent-detail-grid">
                    @if($parent->telephone)
                    <div class="info-row">
                        <span class="info-lbl">Téléphone</span>
                        <span class="info-val"><a href="tel:{{ $parent->telephone }}">{{ $parent->telephone }}</a></span>
                    </div>
                    @endif
                    @if($parent->email)
                    <div class="info-row">
                        <span class="info-lbl">Email</span>
                        <span class="info-val"><a href="mailto:{{ $parent->email }}">{{ $parent->email }}</a></span>
                    </div>
                    @endif
                    @if($parent->adresse)
                    <div class="info-row">
                        <span class="info-lbl">Adresse</span>
                        <span class="info-val">{{ $parent->adresse }}</span>
                    </div>
                    @endif
                    @if($parent->profession)
                    <div class="info-row">
                        <span class="info-lbl">Profession</span>
                        <span class="info-val">{{ $parent->profession }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>{{-- /tab-profil --}}

</div>{{-- /fiche-content --}}
</div>{{-- /fiche-page --}}
@endsection

@section('scripts')
<script>
(function () {
    // Tab switching
    const tabs   = document.querySelectorAll('.fiche-tab');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = this.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('tab-' + target);
            if (panel) panel.classList.add('active');
        });
    });

    // Handle ?tab=xxx URL param
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    if (urlTab) {
        const t = document.querySelector('[data-tab="' + urlTab + '"]');
        if (t) t.click();
    }
})();
</script>
@endsection
