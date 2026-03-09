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
    /* overflow: hidden retiré — coupait le dropdown Bootstrap */
}
/* SVG dot-pattern texture */
.fiche-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.12)'/%3E%3C/svg%3E");
    pointer-events: none;
    overflow: hidden;
    border-radius: inherit;
}
/* glass strip bottom */
.fiche-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--k-surface) 0%, transparent 100%);
}

.hero-inner {
    position: relative; z-index: 200; /* au-dessus de .fiche-tabs-wrap (z-index:100) pour que le dropdown ne soit pas masqué */
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 28px;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

/* Avatar wrapper — permet le badge de statut */
.hero-avatar-wrap {
    position: relative; flex-shrink: 0;
}

/* Avatar */
.hero-avatar {
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
.hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Badge de statut — petit rond coloré en bas à droite de l'avatar */
.hero-avatar-status {
    position: absolute; bottom: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 3px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.hero-avatar-status.actif    { background: #10b981; }
.hero-avatar-status.inactif  { background: #94a3b8; }
.hero-avatar-status.abandon  { background: #ef4444; }

/* Text block */
.hero-text { flex: 1; min-width: 200px; color: #fff; }
.hero-name { font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em; margin: 0 0 3px; line-height: 1.2; }
.hero-sub  { font-size: .88rem; opacity: .8; margin: 0 0 10px; }
.hero-pills { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .76rem; font-weight: 600;
    padding: 3px 11px; border-radius: 20px;
    white-space: nowrap;
}
.hero-pill.green  { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.hero-pill.amber  { background: rgba(245,158,11,.25); border-color: rgba(245,158,11,.4); }
.hero-pill.red    { background: rgba(239,68,68,.25);  border-color: rgba(239,68,68,.4); }
/* Badge année intégré dans les pills */
.hero-pill.year {
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.22);
    font-size: .74rem; letter-spacing: .02em;
}

/* Actions in hero — poussées à droite */
.hero-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; margin-left: auto; }
.hero-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
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

/* ── Semestre cards (legacy, kept for other tabs) ────────────────── */
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

/* ═══════════════════════════════════════════════════════════════════
   TAB ACADÉMIQUE — PREMIUM REDESIGN
═══════════════════════════════════════════════════════════════════ */

/* ── Hero bilan ──────────────────────────────────────────────────── */
.acad-hero {
    background: linear-gradient(150deg, #0d1b3e 0%, #0a2461 45%, #0453cb 100%);
    border-radius: var(--k-radius-lg);
    padding: 28px 28px 0;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.acad-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle at 15% 50%, rgba(94,145,222,.18) 0%, transparent 55%),
                      radial-gradient(circle at 85% 20%, rgba(255,255,255,.05) 0%, transparent 40%);
    pointer-events: none;
}
.acad-hero-top {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
    margin-bottom: 24px; position: relative; z-index: 1;
}
.acad-hero-label {
    font-size: .72rem; font-weight: 700; letter-spacing: .12em;
    color: rgba(255,255,255,.55); text-transform: uppercase;
}
.acad-hero-title {
    font-size: 1.05rem; font-weight: 800; color: #fff;
    margin-top: 2px;
}
.acad-hero-subtitle {
    font-size: .8rem; color: rgba(255,255,255,.6); margin-top: 2px;
}
.acad-hero-pdf-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    color: rgba(255,255,255,.88); font-size: .75rem; font-weight: 600;
    text-decoration: none; transition: background .2s;
    white-space: nowrap;
}
.acad-hero-pdf-btn:hover { background: rgba(255,255,255,.2); color: #fff; }

/* ── KPI 3 blocks ────────────────────────────────────────────────── */
.acad-kpi-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    position: relative; z-index: 1;
    padding-bottom: 28px;
}
@media (max-width: 600px) { .acad-kpi-row { grid-template-columns: 1fr 1fr; } }

.acad-kpi-block {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; padding: 18px 12px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 14px;
    backdrop-filter: blur(8px);
    transition: background .2s;
}
.acad-kpi-block:hover { background: rgba(255,255,255,.11); }

/* SVG ring */
.acad-ring-wrap { position: relative; width: 72px; height: 72px; margin-bottom: 10px; }
.acad-ring-wrap svg { width: 72px; height: 72px; transform: rotate(-90deg); }
.acad-ring-wrap .acad-ring-bg { fill: none; stroke: rgba(255,255,255,.12); stroke-width: 5; }
.acad-ring-wrap .acad-ring-fg { fill: none; stroke-width: 5; stroke-linecap: round; transition: stroke-dashoffset .8s cubic-bezier(.4,0,.2,1); }
.acad-ring-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.acad-ring-val {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1;
}
.acad-ring-denom { font-size: .6rem; color: rgba(255,255,255,.5); }

.acad-kpi-label {
    font-size: .68rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: rgba(255,255,255,.5);
    margin-bottom: 4px;
}
.acad-kpi-sub {
    font-size: .82rem; font-weight: 700; color: rgba(255,255,255,.9);
    line-height: 1.2;
}
.acad-kpi-sub.small { font-size: .72rem; }

/* Rang display (no ring) */
.acad-rang-display {
    width: 72px; height: 72px; margin-bottom: 10px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: rgba(255,255,255,.08); border-radius: 50%;
    border: 2px solid rgba(255,255,255,.15);
}
.acad-rang-num {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.4rem; font-weight: 700; color: #f59e0b; line-height: 1;
}
.acad-rang-icon { font-size: .7rem; color: rgba(245,158,11,.7); margin-top: 1px; }

/* Mention badge (no ring) */
.acad-mention-display {
    width: 72px; height: 72px; margin-bottom: 10px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
}
.acad-mention-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: .72rem; font-weight: 700; letter-spacing: .02em;
    white-space: nowrap;
}
.acad-mention-badge.excellent { background: rgba(16,185,129,.2); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
.acad-mention-badge.bien      { background: rgba(59,130,246,.2); color: #93c5fd; border: 1px solid rgba(59,130,246,.35); }
.acad-mention-badge.assez     { background: rgba(245,158,11,.2); color: #fcd34d; border: 1px solid rgba(245,158,11,.35); }
.acad-mention-badge.passable  { background: rgba(239,68,68,.2);  color: #fca5a5; border: 1px solid rgba(239,68,68,.35); }

/* ── Sparkline progression ───────────────────────────────────────── */
.acad-sparkline-section {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); padding: 16px 20px;
    margin-bottom: 16px;
}
.acad-sparkline-title {
    font-size: .72rem; font-weight: 700; color: var(--k-muted);
    text-transform: uppercase; letter-spacing: .08em; margin-bottom: 14px;
}
.acad-sparkline-bars {
    display: flex; align-items: flex-end; gap: 8px; height: 52px;
}
.acad-spark-bar-wrap {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.acad-spark-bar {
    width: 100%; border-radius: 4px 4px 0 0;
    min-height: 4px;
    transition: opacity .2s;
}
.acad-spark-bar:hover { opacity: .8; }
.acad-spark-label { font-size: .62rem; color: var(--k-muted); white-space: nowrap; }
.acad-spark-val   { font-size: .65rem; font-weight: 700; }

/* ── Top/Flop matières ───────────────────────────────────────────── */
.acad-topflop-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    margin-bottom: 16px;
}
@media (max-width: 600px) { .acad-topflop-row { grid-template-columns: 1fr; } }

.acad-topflop-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); padding: 14px 16px;
}
.acad-topflop-head {
    display: flex; align-items: center; gap: 6px;
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    color: var(--k-muted); margin-bottom: 10px;
}
.acad-topflop-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 5px 0; border-bottom: 1px solid var(--k-border);
}
.acad-topflop-item:last-child { border-bottom: none; }
.acad-topflop-name { font-size: .78rem; color: var(--k-text); font-weight: 500; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.acad-topflop-note { font-size: .78rem; font-weight: 800; min-width: 38px; text-align: right; }

/* ── Semestre accordion ──────────────────────────────────────────── */
.acad-sem-block {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); margin-bottom: 12px;
    overflow: hidden;
}
.acad-sem-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; cursor: pointer;
    background: linear-gradient(135deg, rgba(4,83,203,.04) 0%, rgba(94,145,222,.04) 100%);
    border-bottom: 1px solid var(--k-border);
    user-select: none;
    transition: background .15s;
}
.acad-sem-header:hover { background: linear-gradient(135deg, rgba(4,83,203,.07) 0%, rgba(94,145,222,.07) 100%); }
.acad-sem-header-left { display: flex; align-items: center; gap: 10px; }
.acad-sem-period {
    font-size: .88rem; font-weight: 700; color: var(--k-text);
}
.acad-sem-header-right { display: flex; align-items: center; gap: 12px; }
.acad-sem-badge {
    font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 8px;
}
.acad-sem-badge.green { color: var(--k-success); background: rgba(16,185,129,.1); }
.acad-sem-badge.amber { color: #d97706; background: rgba(245,158,11,.1); }
.acad-sem-badge.red   { color: var(--k-danger); background: rgba(239,68,68,.1); }
.acad-sem-chevron { color: var(--k-muted); font-size: .75rem; transition: transform .25s; }
.acad-sem-header[aria-expanded="true"] .acad-sem-chevron { transform: rotate(180deg); }

/* Per-matière rows */
.acad-mat-list { padding: 8px 18px 14px; }
.acad-mat-item {
    padding: 9px 0; border-bottom: 1px solid var(--k-border);
}
.acad-mat-item:last-child { border-bottom: none; }
.acad-mat-top {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 6px;
}
.acad-mat-name { font-size: .82rem; color: var(--k-text); font-weight: 600; flex: 1; min-width: 0; }
.acad-mat-coeff-pill {
    font-size: .64rem; font-weight: 700; color: var(--k-muted);
    background: var(--k-surface); border: 1px solid var(--k-border);
    border-radius: 10px; padding: 1px 7px; white-space: nowrap;
}
.acad-mat-score {
    font-size: .88rem; font-weight: 800; min-width: 44px; text-align: right;
}
.acad-mat-score.good { color: var(--k-success); }
.acad-mat-score.mid  { color: #d97706; }
.acad-mat-score.bad  { color: var(--k-danger); }
.acad-mat-bar-wrap { height: 5px; background: var(--k-border); border-radius: 3px; overflow: hidden; }
.acad-mat-bar { height: 100%; border-radius: 3px; transition: width .5s ease; }

/* ── Années précédentes (accordion) ─────────────────────────────── */
.acad-arch-sep {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0 14px;
}
.acad-arch-sep-line { flex: 1; height: 1px; background: var(--k-border); }
.acad-arch-sep-label {
    font-size: .7rem; font-weight: 700; color: var(--k-muted);
    text-transform: uppercase; letter-spacing: .1em; white-space: nowrap;
}

.acad-arch-card {
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius); margin-bottom: 10px; overflow: hidden;
}
.acad-arch-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 18px; cursor: pointer; width: 100%;
    background: none; border: none; text-align: left;
    transition: background .15s;
}
.acad-arch-toggle:hover { background: var(--k-surface); }
.acad-arch-toggle-left { display: flex; align-items: center; gap: 10px; }
.acad-arch-year { font-size: .88rem; font-weight: 700; color: var(--k-text); }
.acad-arch-class { font-size: .75rem; color: var(--k-muted); }
.acad-arch-toggle-right { display: flex; align-items: center; gap: 10px; }
.acad-arch-kpi { font-size: .9rem; font-weight: 800; }
.acad-arch-kpi-lbl { font-size: .72rem; color: var(--k-muted); }
.acad-arch-chevron { color: var(--k-muted); font-size: .7rem; transition: transform .25s; }
.acad-arch-toggle[aria-expanded="true"] .acad-arch-chevron { transform: rotate(180deg); }
.acad-arch-body { padding: 16px 18px; border-top: 1px solid var(--k-border); background: var(--k-surface); }
.acad-arch-sem-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 0; border-bottom: 1px dashed var(--k-border);
}
.acad-arch-sem-row:last-child { border-bottom: none; }
.acad-arch-sem-name { font-size: .82rem; font-weight: 600; color: var(--k-text); }
.acad-arch-sem-info { display: flex; align-items: center; gap: 10px; }
.acad-arch-pdf-link {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .72rem; color: var(--k-blue); font-weight: 600;
    text-decoration: none; padding: 2px 8px;
    border: 1px solid rgba(4,83,203,.2); border-radius: 6px;
    transition: background .15s;
}
.acad-arch-pdf-link:hover { background: rgba(4,83,203,.06); }

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

.presence-year-body { padding: 16px 20px; display: none; }
.presence-year.open .presence-year-body { display: block; }
.presence-year-head .fa-chevron-down { transition: transform .3s ease; }
.presence-year.open .presence-year-head .fa-chevron-down { transform: rotate(180deg); }
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

/* ── TAB FINANCES — Design Premium ──────────────────────────────── */

/* Hero financier avec 3 blocs KPI */
.fin-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0c1a3a 100%);
    border-radius: var(--k-radius-lg);
    padding: 28px 28px 20px;
    margin-bottom: 18px;
    box-shadow: 0 8px 40px rgba(4,83,203,.18), 0 2px 8px rgba(0,0,0,.12);
    position: relative;
    overflow: hidden;
}
/* Texture subtile */
.fin-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle at 80% 20%, rgba(94,145,222,.15) 0%, transparent 60%),
                      radial-gradient(circle at 10% 90%, rgba(4,83,203,.1) 0%, transparent 50%);
    pointer-events: none;
}

.fin-hero-grid {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto 1fr;
    gap: 0;
    position: relative;
    margin-bottom: 24px;
}
@media (max-width: 768px) {
    .fin-hero-grid { grid-template-columns: 1fr; gap: 20px; }
    .fin-sep { display: none !important; }
}

.fin-sep {
    width: 1px;
    background: rgba(255,255,255,.12);
    margin: 0 24px;
    align-self: stretch;
}

.fin-kpi-block { padding: 0 8px; }

.fin-kpi-label {
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.5);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.fin-kpi-amount {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -.02em;
    color: #fff;
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}
.fin-kpi-amount.paid   { color: #34d399; }
.fin-kpi-amount.due    { color: #fbbf24; }
.fin-kpi-amount.excess { color: #f87171; }
.fin-kpi-amount.zero   { color: #34d399; }
.fin-kpi-amount.neutral { color: #e2e8f0; }

.fin-kpi-currency {
    font-family: system-ui, sans-serif;
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .05em;
    opacity: .7;
    align-self: flex-end;
    margin-bottom: 3px;
}

.fin-kpi-sub {
    font-size: .75rem;
    color: rgba(255,255,255,.4);
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.fin-kpi-sub-warn  { color: #fbbf24 !important; opacity: .9; }
.fin-kpi-sub-danger { color: #f87171 !important; opacity: .9; }

/* Barre de progression dans le hero */
.fin-progress-wrap { position: relative; }

.fin-progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.fin-progress-lbl {
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: rgba(255,255,255,.45);
}
.fin-progress-pct {
    font-size: .9rem;
    font-weight: 800;
    font-family: Georgia, serif;
}

.fin-progress-track {
    height: 6px;
    background: rgba(255,255,255,.1);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
    margin-bottom: 10px;
}
.fin-progress-segment {
    position: absolute;
    top: 0; left: 0;
    height: 100%;
    border-radius: 3px;
    transition: width .8s cubic-bezier(.4,0,.2,1);
}
.fin-progress-segment.valide  { background: #34d399; }
.fin-progress-segment.attente { background: rgba(251,191,36,.45); }

.fin-progress-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.fin-progress-legend span {
    font-size: .72rem;
    color: rgba(255,255,255,.45);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.fin-legend-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.fin-legend-dot.valide   { background: #34d399; }
.fin-legend-dot.attente  { background: #fbbf24; }
.fin-legend-dot.reliquat { background: #f87171; }

/* Tableau récapitulatif par frais */
.fin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .84rem;
}
.fin-table th {
    padding: 10px 14px;
    text-align: left;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--k-muted);
    background: var(--k-surface);
    border-bottom: 2px solid var(--k-border);
}
.fin-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--k-border);
    vertical-align: middle;
    color: var(--k-text);
    font-size: .84rem;
}
.fin-table tbody tr:last-child td { border-bottom: none; }
.fin-table tbody tr:hover td { background: rgba(4,83,203,.03); }
.fin-cat-name { font-weight: 600; color: var(--k-text); }
.fin-year-badge {
    display: inline-block;
    font-size: .72rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    background: rgba(4,83,203,.08);
    color: var(--k-blue);
}
.fin-mini-track {
    height: 5px;
    background: var(--k-border);
    border-radius: 3px;
    overflow: hidden;
    margin: 0 auto 4px;
    max-width: 80px;
}
.fin-mini-fill {
    height: 100%;
    border-radius: 3px;
    transition: width .5s;
}

/* Historique paiements — card rows */
.fin-pmt-list { display: flex; flex-direction: column; gap: 0; }

.fin-pmt-row {
    display: flex;
    align-items: stretch;
    border-bottom: 1px solid var(--k-border);
    transition: background .15s;
}
.fin-pmt-row:last-child { border-bottom: none; }
.fin-pmt-row:hover { background: rgba(4,83,203,.02); }

.fin-pmt-accent-bar {
    width: 3px;
    flex-shrink: 0;
    background: var(--pmt-accent, var(--k-border));
    border-radius: 2px 0 0 2px;
    margin: 10px 0;
}

.fin-pmt-content {
    flex: 1;
    min-width: 0;
    padding: 14px 16px;
}

.fin-pmt-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}

.fin-pmt-motif {
    font-size: .88rem;
    font-weight: 700;
    color: var(--k-text);
    flex: 1;
    min-width: 0;
}

.fin-pmt-amount {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--k-text);
    white-space: nowrap;
    font-family: Georgia, serif;
    flex-shrink: 0;
}
.fin-pmt-amount small {
    font-family: system-ui, sans-serif;
    font-size: .6em;
    font-weight: 600;
    opacity: .6;
    margin-left: 2px;
}

.fin-pmt-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.fin-pmt-meta-list {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.fin-pmt-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .73rem;
    color: var(--k-muted);
    background: var(--k-surface);
    padding: 2px 8px;
    border-radius: 10px;
    border: 1px solid var(--k-border);
    white-space: nowrap;
}
.fin-pmt-chip.mono { font-family: 'Courier New', monospace; font-size: .7rem; }

.fin-pmt-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

/* ── Fin hero — badge année active ─────────────────────────────── */
.fin-hero-year-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.6);
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    padding: 4px 12px; border-radius: 20px; margin-bottom: 18px;
}
.fin-hero-year-badge strong { color: rgba(255,255,255,.9); font-weight: 700; }

/* ── Autres inscriptions — séparateur ──────────────────────────── */
.fin-autres-header {
    display: flex; align-items: center; gap: 8px;
    font-size: .76rem; font-weight: 700; color: var(--k-muted);
    text-transform: uppercase; letter-spacing: .08em;
    padding: 8px 0; margin: 4px 0 10px;
    border-top: 2px dashed var(--k-border);
}

/* ── Accordion archive inscription ─────────────────────────────── */
.fin-arch-card {
    border: 1px solid var(--k-border); border-radius: var(--k-radius);
    overflow: hidden; margin-bottom: 10px;
    background: var(--k-surface);
}
.fin-arch-toggle {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; background: none; border: none; cursor: pointer;
    text-align: left; gap: 12px; transition: background .15s;
}
.fin-arch-toggle:hover { background: rgba(4,83,203,.04); }
.fin-arch-toggle-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.fin-arch-year {
    font-size: .88rem; font-weight: 700; color: var(--k-text); white-space: nowrap;
}
.fin-arch-class {
    font-size: .78rem; color: var(--k-muted); white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}
.fin-arch-toggle-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.fin-arch-kpi { font-size: .92rem; font-weight: 800; font-family: Georgia, serif; }
.fin-arch-kpi-lbl { font-size: .74rem; color: var(--k-muted); }
.fin-arch-chevron {
    font-size: .75rem; color: var(--k-muted); transition: transform .25s;
}
.fin-arch-toggle[aria-expanded="true"] .fin-arch-chevron { transform: rotate(180deg); }
.fin-arch-body {
    padding: 0 18px 18px; border-top: 1px solid var(--k-border);
}
.fin-arch-stats {
    display: flex; flex-wrap: wrap; gap: 12px 20px;
    padding: 14px 0 12px;
}
.fin-arch-stat { min-width: 90px; }
.fin-arch-stat-lbl { font-size: .69rem; font-weight: 600; color: var(--k-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
.fin-arch-stat-val { font-size: 1rem; font-weight: 800; font-family: Georgia, serif; }
.fin-arch-stat-val.neutral { color: var(--k-text); }
.fin-arch-stat-val.paid    { color: #10b981; }
.fin-arch-stat-val.due     { color: #f59e0b; }
.fin-arch-stat-val.zero    { color: #10b981; }
.fin-arch-pmt-label {
    font-size: .72rem; font-weight: 700; color: var(--k-muted);
    text-transform: uppercase; letter-spacing: .06em;
    margin: 10px 0 6px; padding-top: 10px; border-top: 1px solid var(--k-border);
}

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
    position: relative;
    background: var(--k-card); border: 1px solid var(--k-border);
    border-radius: var(--k-radius);
    margin-bottom: 12px; box-shadow: var(--k-shadow);
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.insc-card:hover { box-shadow: var(--k-shadow-lg); transform: translateY(-1px); }
.insc-card-accent {
    position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
}
.insc-card-accent.actif  { background: linear-gradient(180deg, var(--k-success) 0%, #34d399 100%); }
.insc-card-accent.inactif { background: linear-gradient(180deg, #94a3b8 0%, #cbd5e1 100%); }
.insc-card-accent.abandon { background: linear-gradient(180deg, var(--k-danger) 0%, #f87171 100%); }
.insc-card-inner { padding: 16px 18px 14px 22px; }
.insc-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap; margin-bottom: 10px;
}
.insc-year-badge {
    display: flex; align-items: center; gap: 8px;
}
.insc-year-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(4,83,203,.1); display: flex; align-items: center; justify-content: center;
    color: var(--k-blue); font-size: .8rem; flex-shrink: 0;
}
.insc-year { font-size: .95rem; font-weight: 800; color: var(--k-text); }
.insc-meta {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;
}
.insc-meta-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .75rem; color: var(--k-muted); font-weight: 500;
    background: var(--k-surface); border: 1px solid var(--k-border);
    padding: 3px 10px; border-radius: 20px;
}
.insc-meta-chip i { font-size: .7rem; color: var(--k-blue); }
.insc-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 10px; border-top: 1px solid var(--k-border);
    flex-wrap: wrap; gap: 8px;
}
.insc-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.insc-btn {
    font-size: .77rem; font-weight: 600; padding: 5px 13px; border-radius: 7px;
    text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;
}
.insc-btn.view   { background: rgba(4,83,203,.08); color: var(--k-blue); }
.insc-btn.view:hover { background: var(--k-blue); color: #fff; }
.insc-btn.pdf    { background: rgba(239,68,68,.08); color: var(--k-danger); }
.insc-btn.pdf:hover  { background: var(--k-danger); color: #fff; }
.insc-btn.edit   { background: rgba(245,158,11,.08); color: #d97706; }
.insc-btn.edit:hover { background: var(--k-warning); color: #fff; }
/* workflow & affectation badges */
.insc-header-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.insc-wf-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .7rem; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: .03em;
}
.insc-wf-badge.prospect  { background: rgba(100,116,139,.1); color: #64748b; border: 1px solid rgba(100,116,139,.25); }
.insc-wf-badge.docs      { background: rgba(59,130,246,.1); color: #3b82f6; border: 1px solid rgba(59,130,246,.25); }
.insc-wf-badge.validation { background: rgba(245,158,11,.1); color: #d97706; border: 1px solid rgba(245,158,11,.3); }
.insc-wf-badge.valide    { background: rgba(16,185,129,.1); color: #059669; border: 1px solid rgba(16,185,129,.3); }
.insc-wf-badge.actif     { background: rgba(4,83,203,.1); color: var(--k-blue); border: 1px solid rgba(4,83,203,.2); }
/* data grid inside insc-card */
.insc-data-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 8px 16px; margin-bottom: 12px;
}
.insc-data-row { display: flex; flex-direction: column; gap: 2px; }
.insc-data-lbl {
    font-size: .68rem; color: var(--k-muted); font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    display: flex; align-items: center; gap: 4px;
}
.insc-data-lbl i { font-size: .62rem; color: var(--k-blue); opacity: .7; }
.insc-data-val { font-size: .82rem; font-weight: 600; color: var(--k-text); }
/* affectation status */
.insc-affectation { margin-bottom: 12px; }
.insc-aff-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .73rem; font-weight: 700; padding: 4px 11px; border-radius: 20px;
}
.insc-aff-badge.affecte    { background: rgba(16,185,129,.1); color: #059669; border: 1px solid rgba(16,185,129,.3); }
.insc-aff-badge.non-affecte { background: rgba(239,68,68,.08); color: var(--k-danger); border: 1px solid rgba(239,68,68,.2); }
/* reinscription CTA */
.insc-cta-card {
    border: 2px dashed var(--k-blue) !important;
    background: rgba(4,83,203,.03);
    text-decoration: none; transition: background .2s, border-color .2s;
}
.insc-cta-card:hover { background: rgba(4,83,203,.07); }
.insc-cta-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(4,83,203,.1); color: var(--k-blue);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.insc-cta-body { flex: 1; }
.insc-cta-title { font-size: .9rem; font-weight: 700; color: var(--k-blue); margin-bottom: 2px; }
.insc-cta-sub   { font-size: .75rem; color: var(--k-muted); }
/* inscriptions grid */
.insc-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
@media (max-width: 768px) { .insc-grid { grid-template-columns: 1fr; } }
/* presence constat */
.presence-constat {
    margin-top: 14px; padding: 10px 14px; border-radius: 8px;
    font-size: .78rem; font-weight: 500; line-height: 1.4;
    display: flex; align-items: flex-start; gap: 8px;
}
.presence-constat.good    { background: rgba(16,185,129,.08); color: #065f46; border-left: 3px solid var(--k-success); }
.presence-constat.warning { background: rgba(245,158,11,.08); color: #92400e; border-left: 3px solid var(--k-warning); }
.presence-constat.bad     { background: rgba(239,68,68,.08); color: #991b1b; border-left: 3px solid var(--k-danger); }
.presence-constat i { margin-top: 1px; flex-shrink: 0; }
/* presence stat rows */
.pres-stat-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 4px; }
.pres-stat-row {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 8px; background: var(--k-surface);
}
.pres-stat-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
}
.pres-stat-icon.green { background: rgba(16,185,129,.1); color: var(--k-success); }
.pres-stat-icon.amber { background: rgba(245,158,11,.1); color: #d97706; }
.pres-stat-icon.red   { background: rgba(239,68,68,.08); color: var(--k-danger); }
.pres-stat-icon.blue  { background: rgba(4,83,203,.08); color: var(--k-blue); }
.pres-stat-lbl { flex: 1; font-size: .8rem; color: var(--k-text); font-weight: 500; }
.pres-stat-val { font-size: .9rem; font-weight: 800; }
.pres-stat-val.green { color: var(--k-success); }
.pres-stat-val.amber { color: #d97706; }
.pres-stat-val.red   { color: var(--k-danger); }
.pres-stat-val.blue  { color: var(--k-blue); }
.pres-stat-pct { font-size: .72rem; color: var(--k-muted); font-weight: 500; margin-left: 4px; }

/* ── Overrides dark hero (fin-hero) pour contraste ──────────────── */
.fin-hero .py-stat-val { color: #fff; }
.fin-hero .py-stat-lbl { color: rgba(255,255,255,.65); }
.fin-hero .pres-stat-row {
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.08);
    border-top-color: rgba(255,255,255,.08) !important;
}
.fin-hero .pres-stat-lbl { color: rgba(255,255,255,.85); }
.fin-hero .pres-stat-pct { color: rgba(255,255,255,.5); }
.fin-hero .pres-stat-val { color: #fff; }
.fin-hero .pres-stat-val.green { color: #34d399; }
.fin-hero .pres-stat-val.amber { color: #fbbf24; }
.fin-hero .pres-stat-val.red   { color: #f87171; }
.fin-hero .pres-stat-val.blue  { color: #93c5fd; }
.fin-hero .pres-stat-icon { background: rgba(255,255,255,.1); color: rgba(255,255,255,.7); }
.fin-hero .pres-stat-icon.green { background: rgba(52,211,153,.15); color: #34d399; }
.fin-hero .pres-stat-icon.amber { background: rgba(251,191,36,.15); color: #fbbf24; }
.fin-hero .pres-stat-icon.red   { background: rgba(248,113,113,.12); color: #f87171; }
.fin-hero .pres-stat-icon.blue  { background: rgba(147,197,253,.12); color: #93c5fd; }
.fin-hero .pbar-lbl { color: rgba(255,255,255,.7); }
.fin-hero .pbar-track { background: rgba(255,255,255,.1); }
.fin-hero .presence-bars .pbar-val { color: rgba(255,255,255,.8) !important; }
.fin-hero .presence-constat { background: rgba(255,255,255,.06); color: rgba(255,255,255,.75); }
.fin-hero .presence-constat.good    { background: rgba(52,211,153,.12); color: #6ee7b7; border-left-color: #34d399; }
.fin-hero .presence-constat.warning { background: rgba(251,191,36,.12); color: #fde68a; border-left-color: #fbbf24; }
.fin-hero .presence-constat.bad     { background: rgba(248,113,113,.12); color: #fca5a5; border-left-color: #f87171; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .hero-inner { padding: 24px 16px 20px; gap: 16px; }
    .hero-name  { font-size: 1.3rem; }
    .hero-avatar { width: 80px; height: 80px; font-size: 1.9rem; }
    .hero-kpi   { padding: 10px 14px; }
    .hero-kpi-val { font-size: .95rem; }
    .hero-actions { margin-left: 0; }
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
@php
    $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
    $inscCourante = $anneeCourante
        ? $etudiant->inscriptions->first(fn($i) => $i->annee_universitaire_id === $anneeCourante->id)
        : null;
    // Vrai "actif" = statut actif ET inscrit pour l'année courante
    $estInscritCetteAnnee = $inscCourante !== null;
@endphp
<div class="fiche-hero">
    <div class="hero-inner">
        {{-- Avatar avec badge statut --}}
        <div class="hero-avatar-wrap">
            <div class="hero-avatar">
                @if($etudiant->photo)
                    <img src="{{ asset('storage/photos/etudiants/' . $etudiant->photo) }}"
                         alt="{{ $etudiant->nom_complet }}"
                         onerror="this.parentElement.innerHTML='<i class=\'fas fa-user-graduate\'></i>'">
                @else
                    {{ strtoupper(substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($etudiant->nom, 0, 1)) }}
                @endif
            </div>
            {{-- Badge rond : vert seulement si inscrit cette année, sinon gris --}}
            <span class="hero-avatar-status {{ $estInscritCetteAnnee ? 'actif' : 'inactif' }}"
                  title="{{ $estInscritCetteAnnee ? 'Inscrit ' . ($anneeCourante->name ?? '') : 'Non inscrit pour l\'année en cours' }}"></span>
        </div>

        {{-- Text --}}
        <div class="hero-text">
            <h1 class="hero-name">{{ strtoupper($etudiant->nom) }} {{ $etudiant->prenoms }}</h1>
            <p class="hero-sub">
                @if($inscCourante && $inscCourante->classe)
                    {{ $inscCourante->classe->name }}
                    @if($inscCourante->classe->filiere) · {{ $inscCourante->classe->filiere->name }} @endif
                    @if($inscCourante->classe->niveau) · {{ $inscCourante->classe->niveau->name ?? $inscCourante->classe->niveau->nom ?? '' }} @endif
                @elseif($anneeCourante)
                    <span style="color:rgba(255,255,255,0.75); font-style:italic;">Non réinscrit pour {{ $anneeCourante->name }}</span>
                @else
                    Étudiant
                @endif
            </p>
            <div class="hero-pills">
                <span class="hero-pill"><i class="fas fa-id-card"></i> {{ $etudiant->matricule ?? 'Non attribué' }}</span>
                @if($estInscritCetteAnnee)
                    <span class="hero-pill green"><i class="fas fa-circle" style="font-size:.45rem"></i> Inscrit {{ $anneeCourante->name ?? '' }}</span>
                @else
                    <span class="hero-pill" style="background:rgba(255,255,255,0.15); color:#fff; border-color:rgba(255,255,255,0.4);">
                        <i class="fas fa-exclamation-circle" style="font-size:.7rem; color:#fbbf24;"></i> Non réinscrit {{ $anneeCourante ? $anneeCourante->name : '' }}
                    </span>
                @endif
                @if($etudiant->statut === 'abandon')
                    <span class="hero-pill red"><i class="fas fa-circle" style="font-size:.45rem"></i> Abandon</span>
                @endif
                @if($etudiant->nationalite)
                    <span class="hero-pill"><i class="fas fa-flag"></i> {{ $etudiant->nationalite }}</span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="hero-actions">
            @if($anneeCourante)
                <span class="hero-pill year"><i class="fas fa-calendar-alt"></i> {{ $anneeCourante->name }}</span>
            @endif
            <div class="hero-btns">
                @can('update', $etudiant)
                <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}" class="hero-btn primary">
                    <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Modifier</span>
                </a>
                @endcan

                {{-- Dropdown Documents — visible dès qu'il y a au moins une inscription --}}
                @if($etudiant->inscriptions->isNotEmpty())
                <div class="dropdown">
                    <button class="hero-btn ghost dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            style="gap:6px;">
                        <i class="fas fa-file-alt"></i>
                        <span class="d-none d-sm-inline">Documents</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:230px;">
                        <li><h6 class="dropdown-header" style="font-size:.72rem; letter-spacing:.04em;">CERTIFICAT DE SCOLARITÉ</h6></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat.preview', $etudiant) }}" target="_blank">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Prévisualiser
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat', $etudiant) }}" target="_blank">
                                <i class="fas fa-download fa-fw me-2 text-muted"></i>Télécharger PDF
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header" style="font-size:.72rem; letter-spacing:.04em;">ATTESTATION DE FRÉQUENTATION</h6></li>
                        <li>
                            @if($estInscritCetteAnnee)
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation.preview', $etudiant) }}" target="_blank">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Prévisualiser
                            </a>
                            @else
                            <a class="dropdown-item" href="#"
                               onclick="openAttestationConfirm(event, '{{ route('esbtp.etudiants.attestation-frequentation.preview', $etudiant) }}')">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Prévisualiser
                            </a>
                            @endif
                        </li>
                        <li>
                            @if($estInscritCetteAnnee)
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation', $etudiant) }}" target="_blank">
                                <i class="fas fa-download fa-fw me-2 text-muted"></i>Télécharger PDF
                            </a>
                            @else
                            <a class="dropdown-item" href="#"
                               onclick="openAttestationConfirm(event, '{{ route('esbtp.etudiants.attestation-frequentation', $etudiant) }}')">
                                <i class="fas fa-download fa-fw me-2 text-muted"></i>Télécharger PDF
                            </a>
                            @endif
                        </li>
                    </ul>
                </div>
                @endif

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
    </div>

    {{-- KPI Strip --}}
    @php
        // KPI Hero : uniquement l'année courante — si pas inscrit, tout à zéro/nul
        $totalPaiements  = $inscCourante
            ? $inscCourante->paiements->where('status','validé')->sum('montant')
            : null; // null = non inscrit cette année
        $totalAbsences   = null; // calculé après $tauxPresence (dépend de $inscCourante)
        $nbInscriptions  = $etudiant->inscriptions->count();

        // taux presence depuis les absences de l'étudiant (année courante uniquement)
        $tauxPresence = null;
        if ($inscCourante && $inscCourante->anneeUniversitaire) {
            $anneeId = $inscCourante->anneeUniversitaire->id;
            $attStats = \App\Models\ESBTPAttendance::finalOnly()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeId)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN statut='present' THEN 1 ELSE 0 END) as nb_pres, SUM(CASE WHEN statut IN ('retard','late') THEN 1 ELSE 0 END) as nb_ret")
                ->first();
            if ($attStats && $attStats->total > 0) {
                $tauxPresence = round((($attStats->nb_pres + $attStats->nb_ret) / $attStats->total) * 100, 1);
            }
        }

        // Absences de l'année courante uniquement
        if ($inscCourante && $inscCourante->anneeUniversitaire) {
            $totalAbsences = \App\Models\ESBTPAttendance::finalOnly()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $inscCourante->anneeUniversitaire->id)
                ->where('statut', 'absent')
                ->count();
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
                <div class="hero-kpi-val">{{ $totalAbsences !== null ? $totalAbsences : '—' }}</div>
                <div class="hero-kpi-lbl">Absences</div>
            </div>
        </div>
        <div class="hero-kpi">
            <i class="fas fa-coins hero-kpi-icon"></i>
            <div>
                <div class="hero-kpi-val">{{ $totalPaiements !== null ? number_format($totalPaiements, 0, ',', ' ') : '—' }}</div>
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

    {{-- Bannière : étudiant non inscrit pour l'année courante --}}
    @if($anneeCourante && !$inscCourante)
    <div style="
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
        border: 1.5px solid #ffc107;
        border-left: 5px solid #e65100;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    ">
        <div style="flex-shrink:0; width:36px; height:36px; background:#e65100; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-exclamation-triangle" style="color:#fff; font-size:.9rem;"></i>
        </div>
        <div>
            <div style="font-weight:700; color:#b45309; font-size:.95rem; margin-bottom:4px;">
                Cet étudiant n'est pas réinscrit pour l'année {{ $anneeCourante->name }}
            </div>
            <div style="color:#92400e; font-size:.85rem; line-height:1.5;">
                Les indicateurs ci-dessous ne sont pas disponibles pour l'année en cours.
                Pour afficher les données académiques, financières et de présence, veuillez d'abord réinscrire cet étudiant.
            </div>
            <a href="{{ route('esbtp.reinscription.show', $etudiant) }}"
               style="display:inline-flex; align-items:center; gap:6px; margin-top:10px; padding:6px 14px; background:#e65100; color:#fff; border-radius:6px; font-size:.82rem; font-weight:600; text-decoration:none;">
                <i class="fas fa-redo"></i> Réinscrire pour {{ $anneeCourante->name }}
            </a>
        </div>
    </div>
    @endif

    {{-- KPI Cards --}}
    @php
        // Calcul KPI globaux : uniquement depuis l'inscription courante
        $kpiMoyenneGen     = null;
        $kpiMoyenneIsLive  = false;
        $kpiTauxPres       = $tauxPresence; // déjà filtré par $inscCourante
        // Absences : uniquement année courante
        $kpiAbsTotal       = $totalAbsences; // null si pas inscrit cette année

        // Inscription de référence : année courante uniquement
        $kpiInscActive = $inscCourante ?? null;

        $kpiPaiTotal = $kpiInscActive
            ? $kpiInscActive->paiements->where('status', 'validé')->sum('montant')
            : null; // null = pas inscrit cette année
        $kpiPaiDu = 0;

        // Moyenne : 1) chercher bulletins officiels de l'inscription de référence
        $kpiBulletins = $kpiInscActive
            ? \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $kpiInscActive->annee_universitaire_id)
                ->get()
            : collect();

        $kpiBulletinsCalcueles = $kpiBulletins->filter(fn($b) => $b->moyenne_generale !== null && $b->moyenne_generale > 0);
        if ($kpiBulletinsCalcueles->count()) {
            // Moyenne pondérée des bulletins officiels calculés
            $kpiMoyenneGen    = round($kpiBulletinsCalcueles->avg('moyenne_generale'), 2);
            $kpiMoyenneIsLive = false;
        } else {
            // Fallback : calculer depuis ESBTPResultat (résultats bruts, bulletin non généré)
            $kpiResultats = $kpiInscActive
                ? \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                    ->where('annee_universitaire_id', $kpiInscActive->annee_universitaire_id)
                    ->whereNotNull('moyenne')
                    ->get()
                : collect();

            if ($kpiResultats->count()) {
                $kpiSp = 0; $kpiSc = 0;
                foreach ($kpiResultats as $kr) {
                    $c = $kr->coefficient ?? 1;
                    $kpiSp += $kr->moyenne * $c;
                    $kpiSc += $c;
                }
                $kpiMoyenneGen    = $kpiSc > 0 ? round($kpiSp / $kpiSc, 2) : null;
                $kpiMoyenneIsLive = true; // Signaler que c'est provisoire
            }
        }
    @endphp

    <div class="kpi-grid">
        {{-- Moyenne --}}
        @php
            $m    = $kpiMoyenneGen;
            $mc   = $m !== null ? ($m >= 12 ? '#10b981' : ($m >= 10 ? '#f59e0b' : '#ef4444')) : '#94a3b8';
            $mpct = $m !== null ? min(100, ($m/20)*100) : 0;
        @endphp
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
                <div class="kpi-val" style="color:{{ $mc }}">
                    {{ $m !== null ? number_format($m,2) : '—' }}<small style="font-size:.6em;font-weight:500">/20</small>
                </div>
                <div class="kpi-lbl">Moy. générale</div>
                @if($kpiMoyenneIsLive && $m !== null)
                    <div style="font-size:.62rem; color:#f59e0b; margin-top:4px; display:flex; align-items:center; gap:3px; line-height:1.3;">
                        <i class="fas fa-clock" style="font-size:.58rem;"></i> Provisoire · Bulletin non généré
                    </div>
                @endif
            </div>
        </div>

        {{-- Présence --}}
        @php
            $p  = $kpiTauxPres;
            $pc = $p !== null ? ($p >= 80 ? '#10b981' : ($p >= 60 ? '#f59e0b' : '#ef4444')) : '#94a3b8';
        @endphp
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
        @php
            $ac   = $kpiAbsTotal !== null ? ($kpiAbsTotal > 20 ? '#ef4444' : ($kpiAbsTotal > 10 ? '#f59e0b' : '#10b981')) : '#94a3b8';
            $apct = $kpiAbsTotal !== null ? min(100, $kpiAbsTotal * 2) : 0;
        @endphp
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
                <div class="kpi-val" style="color:{{ $ac }}">{{ $kpiAbsTotal !== null ? $kpiAbsTotal : '—' }}</div>
                <div class="kpi-lbl">Absences totales</div>
            </div>
        </div>

        {{-- Paiements --}}
        @php
            $kpiPaiTotalSafe = $kpiPaiTotal ?? 0;
            $totalDu2 = $kpiPaiTotalSafe + ($kpiPaiDu ?? 0);
            $ppct  = ($kpiPaiTotal !== null && $totalDu2 > 0) ? min(100, round($kpiPaiTotalSafe / $totalDu2 * 100)) : null;
            $payc  = $ppct !== null ? ($ppct >= 80 ? '#10b981' : ($ppct >= 50 ? '#f59e0b' : '#ef4444')) : '#94a3b8';
            $ppctVal = $ppct ?? 0;
        @endphp
        <div class="kpi-card">
            <div class="kpi-ring">
                <svg viewBox="0 0 52 52">
                    <circle class="ring-bg" cx="26" cy="26" r="22"/>
                    <circle class="ring-fg" cx="26" cy="26" r="22"
                        stroke="{{ $payc }}"
                        stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                        stroke-dashoffset="{{ round(2*3.14159*22 * (1 - $ppctVal/100),1) }}"/>
                </svg>
                <span class="ring-icon" style="color:{{ $payc }}"><i class="fas fa-coins" style="font-size:.75rem"></i></span>
            </div>
            <div class="kpi-body">
                <div class="kpi-val" style="color:{{ $payc }}">{{ $ppct !== null ? $ppct . '%' : '—' }}</div>
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
                <span class="info-val">{{ $kpiInscActive?->classe?->name ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Filière</span>
                <span class="info-val">{{ $kpiInscActive?->classe?->filiere?->name ?? '—' }}</span>
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

    {{-- Toutes les inscriptions de l'étudiant --}}
    @php
        $toutesInscs = $etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date ?? $i->created_at);
    @endphp
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-file-signature"></i></div>
                Inscriptions
            </div>
        </div>
        <div class="insc-grid">
        @forelse($toutesInscs as $insc)
        @php
            $statInsc = $insc->status ?? 'pending';
            $anneeLabel = $insc->anneeUniversitaire?->name ?? 'N/A';
            $inscAccent = in_array($statInsc, ['active', 'actif', 'validé']) ? 'actif' : ($statInsc === 'abandon' ? 'abandon' : 'inactif');
            $wfStep = $insc->workflow_step ?? null;
            $affStatus = $insc->affectation_status ?? 'non_affecté';
        @endphp
        <div class="insc-card">
            <div class="insc-card-accent {{ $inscAccent }}"></div>
            <div class="insc-card-inner">
                <div class="insc-header">
                    <div class="insc-year-badge">
                        <div class="insc-year-icon"><i class="fas fa-graduation-cap"></i></div>
                        <span class="insc-year">{{ $anneeLabel }}</span>
                    </div>
                    <div class="insc-header-badges">
                        @if($wfStep)
                        @switch($wfStep)
                            @case('prospect')
                                <span class="insc-wf-badge prospect"><i class="fas fa-clock"></i> Prospect</span>
                                @break
                            @case('documents_complets')
                                <span class="insc-wf-badge docs"><i class="fas fa-folder-open"></i> Docs complets</span>
                                @break
                            @case('en_validation')
                                <span class="insc-wf-badge validation"><i class="fas fa-hourglass-half"></i> En validation</span>
                                @break
                            @case('valide')
                                <span class="insc-wf-badge valide"><i class="fas fa-check-circle"></i> Validé</span>
                                @break
                            @case('etudiant_cree')
                                <span class="insc-wf-badge actif"><i class="fas fa-user-graduate"></i> Étudiant créé</span>
                                @break
                        @endswitch
                        @endif
                        <span class="status-chip {{ $inscAccent }}">
                            {{ $statInsc === 'active' ? 'Active' : ucfirst($statInsc) }}
                        </span>
                    </div>
                </div>

                <div class="insc-data-grid">
                    <div class="insc-data-row">
                        <span class="insc-data-lbl"><i class="fas fa-project-diagram"></i> Filière</span>
                        <span class="insc-data-val">{{ $insc->classe?->filiere?->name ?? '—' }}</span>
                    </div>
                    <div class="insc-data-row">
                        <span class="insc-data-lbl"><i class="fas fa-layer-group"></i> Niveau</span>
                        <span class="insc-data-val">{{ $insc->classe?->niveau?->name ?? '—' }}</span>
                    </div>
                    <div class="insc-data-row">
                        <span class="insc-data-lbl"><i class="fas fa-chalkboard"></i> Classe</span>
                        <span class="insc-data-val">{{ $insc->classe?->name ?? '—' }}</span>
                    </div>
                    <div class="insc-data-row">
                        <span class="insc-data-lbl"><i class="fas fa-calendar-plus"></i> Date inscription</span>
                        <span class="insc-data-val">{{ $insc->created_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>

                <div class="insc-affectation">
                    @if(in_array($affStatus, ['affecté', 'réaffecté']))
                        <span class="insc-aff-badge affecte"><i class="fas fa-check"></i> {{ ucfirst($affStatus) }}</span>
                    @else
                        <span class="insc-aff-badge non-affecte"><i class="fas fa-times"></i> Non affecté</span>
                    @endif
                </div>

                <div class="insc-footer">
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
            </div>
        </div>
        @empty
        <div style="padding:24px;color:var(--k-gray);font-size:.9rem;">Aucune inscription enregistrée.</div>
        @endforelse
        {{-- CTA Réinscription si pas encore inscrit pour l'année courante --}}
        @if($anneeCourante && !$inscCourante)
        <a href="{{ route('esbtp.reinscription.show', $etudiant) }}" class="insc-card insc-cta-card">
            <div class="insc-card-accent inactif"></div>
            <div class="insc-card-inner" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;text-align:center;">
                <div class="insc-cta-icon"><i class="fas fa-plus"></i></div>
                <div>
                    <div class="insc-cta-title">Réinscrire pour {{ $anneeCourante->name }}</div>
                    <div class="insc-cta-sub">Cet étudiant n'est pas encore inscrit pour l'année en cours</div>
                </div>
                <i class="fas fa-arrow-right" style="color:var(--k-blue); opacity:.6;"></i>
            </div>
        </a>
        @endif
        </div>{{-- /grid --}}
    </div>

</div>{{-- /tab-overview --}}

{{-- ─── TAB: ACADÉMIQUE ────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-academique">
@php
    /* ── Données ─────────────────────────────────────────────────── */
    $acadInscs = $etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date);

    /* Inscription de référence : la plus récente avec bulletins CALCULÉS (moyenne_generale > 0) */
    $acadRef = null; $acadBuls = collect();
    foreach ($acadInscs as $_i) {
        $_b = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', optional($_i->anneeUniversitaire)->id)
            ->orderBy('periode')->get();
        /* Un bulletin n'est "calculé" que si au moins un a moyenne_generale > 0 */
        $_bCalcules = $_b->filter(fn($b) => $b->moyenne_generale !== null && $b->moyenne_generale > 0);
        if ($_bCalcules->count()) { $acadRef = $_i; $acadBuls = $_b; break; }
    }
    /* Fallback : inscription la plus récente avec resultats bruts */
    if (!$acadRef) {
        foreach ($acadInscs as $_i) {
            $_r = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', optional($_i->anneeUniversitaire)->id)
                ->whereNotNull('moyenne')->exists();
            if ($_r) { $acadRef = $_i; break; }
        }
    }
    if (!$acadRef && $acadInscs->count()) $acadRef = $acadInscs->first();

    /* Résultats bruts de l'année de référence (pour top/flop, sparkline et KPIs si pas de bulletins) */
    $acadResultatsRef = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
        ->where('annee_universitaire_id', optional($acadRef?->anneeUniversitaire)->id)
        ->with(['matiere'])->get();

    /* KPIs hero — depuis bulletins si dispo, sinon depuis résultats bruts */
    $acadLastBul = $acadBuls->last();
    if ($acadBuls->count()) {
        /* Cas avec bulletins : moyenne pondérée des bulletins (si > 0) */
        $acadMgFromBul = $acadBuls->filter(fn($b) => $b->moyenne_generale > 0)->avg('moyenne_generale');
        $acadMg      = $acadMgFromBul ? round($acadMgFromBul, 2) : null;
        $acadRang    = $acadLastBul->rang;
        $acadMention = $acadLastBul->mention;
        /* Si bulletins ont moyenne_generale=0/null, fallback aux résultats bruts */
        if ($acadMg === null) {
            $acadAllNotesForKpi = $acadResultatsRef->whereNotNull('moyenne');
            if ($acadAllNotesForKpi->count()) {
                $sommePoints = 0; $sommeCoeffs = 0;
                foreach ($acadAllNotesForKpi as $_r) {
                    $coef = $_r->coefficient ?? 1;
                    $sommePoints += $_r->moyenne * $coef;
                    $sommeCoeffs += $coef;
                }
                $acadMg = $sommeCoeffs > 0 ? round($sommePoints / $sommeCoeffs, 2) : null;
            }
            if (!$acadMention && $acadMg !== null) {
                $acadMention = match(true) {
                    $acadMg >= 16 => 'Excellent',
                    $acadMg >= 14 => 'Très Bien',
                    $acadMg >= 12 => 'Bien',
                    $acadMg >= 10 => 'Assez Bien',
                    default       => 'Passable',
                };
            }
        }
    } else {
        /* Cas sans bulletins : calculer moyenne pondérée depuis ESBTPResultat */
        $acadAllNotesForKpi = $acadResultatsRef->whereNotNull('moyenne');
        if ($acadAllNotesForKpi->count()) {
            $sommePoints = 0; $sommeCoeffs = 0;
            foreach ($acadAllNotesForKpi as $_r) {
                $coef = $_r->coefficient ?? 1;
                $sommePoints += $_r->moyenne * $coef;
                $sommeCoeffs += $coef;
            }
            $acadMg = $sommeCoeffs > 0 ? round($sommePoints / $sommeCoeffs, 2) : null;
            /* Mention calculée depuis la moyenne */
            $acadMention = match(true) {
                $acadMg === null => null,
                $acadMg >= 16   => 'Excellent',
                $acadMg >= 14   => 'Très Bien',
                $acadMg >= 12   => 'Bien',
                $acadMg >= 10   => 'Assez Bien',
                default         => 'Passable',
            };
        } else {
            $acadMg = null; $acadMention = null;
        }
        $acadRang = null; /* Rang non calculable sans bulletin officiel */
    }

    $acadAnnee  = optional($acadRef?->anneeUniversitaire)->name ?? '—';
    $acadClasse = optional($acadRef?->classe)->name ?? '—';

    /* Mention CSS class */
    $acadMentionCls = match(true) {
        !$acadMention => 'passable',
        str_contains(strtolower($acadMention), 'excellent') || str_contains(strtolower($acadMention), 'très bien') => 'excellent',
        str_contains(strtolower($acadMention), 'bien') && !str_contains(strtolower($acadMention), 'assez') => 'bien',
        str_contains(strtolower($acadMention), 'assez') => 'assez',
        default => 'passable',
    };

    $acadAllNotes = $acadResultatsRef->whereNotNull('moyenne');

    /* Top 3 / flop matières */
    $acadTop  = $acadAllNotes->sortByDesc('moyenne')->take(3);
    $acadFlop = $acadAllNotes->where('moyenne', '<', 10)->sortBy('moyenne')->take(3);

    /* Sparkline : moyennes pondérées par période */
    $acadSparkData = $acadResultatsRef->groupBy(fn($r) => $r->periode ?? 'N/A')
        ->map(function($g) {
            $sp = 0; $sc = 0;
            foreach ($g->whereNotNull('moyenne') as $r) {
                $c = $r->coefficient ?? 1;
                $sp += $r->moyenne * $c;
                $sc += $c;
            }
            return $sc > 0 ? round($sp / $sc, 2) : 0;
        })
        ->sortKeys();

    /* Résultats groupés par semestre pour l'affichage détail */
    $acadSemestres = $acadResultatsRef->groupBy(fn($r) => $r->periode ?? 'N/A')->sortKeys();

    /* Années précédentes */
    $acadAutres = $acadInscs->filter(fn($i) => !$acadRef || $i->id !== $acadRef->id);

    /* Notice : données d'une année différente de l'année courante */
    $acadAnneeCourante = $presAnneeCourante ?? \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
    $acadIsNotCurrentYear = $acadAnneeCourante && $acadRef
        && $acadRef->annee_universitaire_id !== $acadAnneeCourante->id;
@endphp

@if($acadInscs->count())

    {{-- ══ BLOC ANNÉE EN COURS (toujours plat, jamais collapsible) ══ --}}
    @if($acadIsNotCurrentYear)
        {{-- Pas de données pour l'année courante --}}
        <div class="fin-hero" style="margin-bottom:16px;">
            <div class="fin-hero-year-badge" style="background:rgba(239,68,68,.15); border-color:rgba(239,68,68,.3);">
                <i class="fas fa-calendar-times" style="color:#ef4444;"></i>
                <span style="color:#ef4444;">Aucune inscription pour {{ $acadAnneeCourante?->name ?? 'l\'année en cours' }}</span>
            </div>
            <p style="margin:12px 0 0; font-size:.82rem; color:var(--k-muted); text-align:center;">
                <i class="fas fa-info-circle" style="margin-right:5px;"></i>Pas encore de résultats académiques pour l'année en cours. Consultez les années précédentes ci-dessous.
            </p>
        </div>
    @else
    {{-- ══ HERO BILAN (année courante avec données) ══════════════════ --}}
    {{-- Badge plat "Année en cours" au-dessus du hero --}}
    <div class="fin-hero" style="margin-bottom:16px;">
        <div class="fin-hero-year-badge">
            <i class="fas fa-calendar-check"></i>
            Année en cours :
            <strong>{{ $acadAnnee }}</strong>
            @if($acadClasse)
                &middot; {{ $acadClasse }}
            @endif
        </div>
    </div>
    <div class="acad-hero">
        <div class="acad-hero-top">
            <div>
                <div class="acad-hero-label"><i class="fas fa-graduation-cap" style="margin-right:5px;"></i>Bilan académique</div>
                <div class="acad-hero-title">{{ $acadAnnee }}</div>
                <div class="acad-hero-subtitle">{{ $acadClasse }}</div>
            </div>
            @if($acadLastBul)
            <a href="{{ route('esbtp.bulletins.download', $acadLastBul) }}" class="acad-hero-pdf-btn" target="_blank">
                <i class="fas fa-file-pdf"></i> Bulletin PDF
            </a>
            @endif
        </div>

        <div class="acad-kpi-row">
            {{-- KPI 1 : Moyenne générale --}}
            @php
                $mgPct = $acadMg !== null ? min(100, round($acadMg / 20 * 100)) : 0;
                $circumference = 2 * 3.14159 * 28; // r=28
                $mgOffset = $circumference - ($mgPct / 100 * $circumference);
                $mgStroke = $acadMg === null ? '#64748b' : ($acadMg >= 14 ? '#10b981' : ($acadMg >= 12 ? '#34d399' : ($acadMg >= 10 ? '#f59e0b' : '#ef4444')));
            @endphp
            <div class="acad-kpi-block">
                <div class="acad-kpi-label">Moyenne</div>
                <div class="acad-ring-wrap">
                    <svg viewBox="0 0 64 64">
                        <circle class="acad-ring-bg" cx="32" cy="32" r="28"/>
                        <circle class="acad-ring-fg"
                            cx="32" cy="32" r="28"
                            stroke="{{ $mgStroke }}"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $acadMg !== null ? $mgOffset : $circumference }}"/>
                    </svg>
                    <div class="acad-ring-center">
                        <span class="acad-ring-val" style="color:{{ $mgStroke }};">
                            {{ $acadMg !== null ? number_format($acadMg, 1) : '—' }}
                        </span>
                        @if($acadMg !== null)<span class="acad-ring-denom">/20</span>@endif
                    </div>
                </div>
                <div class="acad-kpi-sub">
                    @if($acadMg !== null)
                        {{ $acadMg >= 14 ? 'Excellent' : ($acadMg >= 12 ? 'Bien' : ($acadMg >= 10 ? 'Passable' : 'Insuffisant')) }}
                    @else
                        Aucune moyenne
                    @endif
                </div>
            </div>

            {{-- KPI 2 : Rang --}}
            <div class="acad-kpi-block">
                <div class="acad-kpi-label">Classement</div>
                <div class="acad-rang-display">
                    @if($acadRang)
                        <span class="acad-rang-num">{{ $acadRang }}</span>
                        <span class="acad-rang-icon"><i class="fas fa-trophy"></i></span>
                    @else
                        <span style="font-size:1.5rem; color:rgba(255,255,255,.3);">—</span>
                    @endif
                </div>
                <div class="acad-kpi-sub">
                    @if($acadRang) Rang de classe @else Non classé @endif
                </div>
            </div>

            {{-- KPI 3 : Mention --}}
            <div class="acad-kpi-block">
                <div class="acad-kpi-label">Mention</div>
                <div class="acad-mention-display">
                    <i class="fas fa-award" style="font-size:1.8rem; color:{{ $acadMentionCls === 'excellent' ? '#34d399' : ($acadMentionCls === 'bien' ? '#93c5fd' : ($acadMentionCls === 'assez' ? '#fcd34d' : 'rgba(255,255,255,.3)')) }};"></i>
                </div>
                @if($acadMention)
                    <span class="acad-mention-badge {{ $acadMentionCls }}">{{ $acadMention }}</span>
                @else
                    <div class="acad-kpi-sub" style="color:rgba(255,255,255,.4);">Non évalué</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══ SPARKLINE : évolution par semestre ══════════════════════ --}}
    @if($acadSparkData->count() >= 2)
    @php
        $sparkMax = $acadSparkData->max() ?: 20;
    @endphp
    <div class="acad-sparkline-section">
        <div class="acad-sparkline-title"><i class="fas fa-chart-line" style="margin-right:5px;"></i>Progression par semestre</div>
        <div class="acad-sparkline-bars">
            @foreach($acadSparkData as $periode => $moy)
            @php
                $barH = $sparkMax > 0 ? max(8, round($moy / $sparkMax * 44)) : 8;
                $barColor = $moy >= 14 ? '#10b981' : ($moy >= 12 ? '#3b82f6' : ($moy >= 10 ? '#f59e0b' : '#ef4444'));
            @endphp
            <div class="acad-spark-bar-wrap">
                <span class="acad-spark-val" style="color:{{ $barColor }};">{{ number_format($moy, 1) }}</span>
                <div class="acad-spark-bar" style="height:{{ $barH }}px; background:{{ $barColor }};"></div>
                <span class="acad-spark-label">{{ ucfirst(str_replace(['semestre', '_', '-'], ['S', ' ', ' '], strtolower($periode))) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══ TOP / FLOP matières ══════════════════════════════════════ --}}
    @if($acadTop->count() || $acadFlop->count())
    <div class="acad-topflop-row">
        @if($acadTop->count())
        <div class="acad-topflop-card">
            <div class="acad-topflop-head" style="color:var(--k-success);">
                <i class="fas fa-arrow-trend-up"></i> Meilleures matières
            </div>
            @foreach($acadTop as $tm)
            @php $tn = $tm->moyenne; @endphp
            <div class="acad-topflop-item">
                <span class="acad-topflop-name" title="{{ optional($tm->matiere)->name }}">{{ optional($tm->matiere)->name ?? '—' }}</span>
                <span class="acad-topflop-note" style="color:var(--k-success);">{{ number_format($tn, 2) }}</span>
            </div>
            @endforeach
        </div>
        @endif
        @if($acadFlop->count())
        <div class="acad-topflop-card">
            <div class="acad-topflop-head" style="color:var(--k-danger);">
                <i class="fas fa-arrow-trend-down"></i> Matières en difficulté
            </div>
            @foreach($acadFlop as $fm)
            @php $fn = $fm->moyenne; @endphp
            <div class="acad-topflop-item">
                <span class="acad-topflop-name" title="{{ optional($fm->matiere)->name }}">{{ optional($fm->matiere)->name ?? '—' }}</span>
                <span class="acad-topflop-note" style="color:var(--k-danger);">{{ number_format($fn, 2) }}</span>
            </div>
            @endforeach
        </div>
        @elseif($acadTop->count())
        {{-- Pas de matières en difficulté = message positif --}}
        <div class="acad-topflop-card" style="display:flex; align-items:center; justify-content:center; gap:10px; color:var(--k-success);">
            <i class="fas fa-check-circle" style="font-size:1.4rem;"></i>
            <div>
                <div style="font-size:.82rem; font-weight:700;">Aucune difficulté</div>
                <div style="font-size:.72rem; color:var(--k-muted);">Toutes les notes ≥ 10</div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ══ DÉTAIL PAR SEMESTRE ══════════════════════════════════════ --}}
    @if($acadBuls->count())
        {{-- Avec bulletins officiels --}}
        @foreach($acadBuls as $bul)
        @php
            /* Résultats matières pour ce semestre */
            $semResultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', optional($acadRef->anneeUniversitaire)->id)
                ->where('periode', $bul->periode)
                ->with(['matiere'])->get()
                ->sortBy(fn($r) => optional($r->matiere)->name);

            /* Moyenne semestre : bulletin officiel, sinon calculée depuis résultats */
            $mg = ($bul->moyenne_generale > 0) ? $bul->moyenne_generale : null;
            if ($mg === null && $semResultats->count()) {
                $_sp = 0; $_sc = 0;
                foreach ($semResultats->whereNotNull('moyenne') as $_sr) {
                    $_coef = $_sr->coefficient ?? 1;
                    $_sp += $_sr->moyenne * $_coef;
                    $_sc += $_coef;
                }
                $mg = $_sc > 0 ? round($_sp / $_sc, 2) : null;
            }
            $semKey = 'acad-sem-' . $bul->id;
            $semBadgeCls = $mg === null ? '' : ($mg >= 12 ? 'green' : ($mg >= 10 ? 'amber' : 'red'));
            $semLabel = ucfirst(str_replace(['semestre', '_', '-'], ['Semestre ', ' ', ' '], strtolower($bul->periode ?? '')));
        @endphp
        <div class="acad-sem-block">
            <div class="acad-sem-header"
                 data-bs-toggle="collapse"
                 data-bs-target="#{{ $semKey }}"
                 aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                <div class="acad-sem-header-left">
                    <i class="fas fa-book-open" style="color:var(--k-blue); font-size:.8rem;"></i>
                    <span class="acad-sem-period">{{ $semLabel ?: 'Semestre' }}</span>
                    @if($bul->rang)
                        <span style="font-size:.72rem; color:var(--k-muted);">
                            <i class="fas fa-trophy" style="color:#f59e0b; font-size:.65rem;"></i> {{ $bul->rang }}
                        </span>
                    @endif
                </div>
                <div class="acad-sem-header-right">
                    @if($mg !== null)
                        <span class="acad-sem-badge {{ $semBadgeCls }}">{{ number_format($mg, 2) }}/20</span>
                    @endif
                    @php
                        $semMention = $bul->mention;
                        if (!$semMention && $mg !== null) {
                            $semMention = $mg >= 16 ? 'Excellent' : ($mg >= 14 ? 'Très Bien' : ($mg >= 12 ? 'Bien' : ($mg >= 10 ? 'Assez Bien' : 'Passable')));
                        }
                        $mc2 = !$semMention ? 'passable' : match(true) {
                            str_contains(strtolower($semMention), 'excellent') || str_contains(strtolower($semMention), 'très bien') => 'excellent',
                            str_contains(strtolower($semMention), 'bien') && !str_contains(strtolower($semMention), 'assez') => 'bien',
                            str_contains(strtolower($semMention), 'assez') => 'assez',
                            default => 'passable',
                        };
                    @endphp
                    @if($semMention)
                        <span class="acad-mention-badge {{ $mc2 }}" style="font-size:.65rem;">{{ $semMention }}</span>
                    @endif
                    <a href="{{ route('esbtp.bulletins.download', $bul) }}" class="acad-arch-pdf-link" target="_blank" onclick="event.stopPropagation()">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <i class="fas fa-chevron-down acad-sem-chevron"></i>
                </div>
            </div>
            <div class="collapse {{ $loop->first ? 'show' : '' }}" id="{{ $semKey }}">
                <div class="acad-mat-list">
                    @if($semResultats->count())
                        @foreach($semResultats as $res)
                        @php
                            $rn = $res->moyenne;
                            $rc = optional($res->matiere)->coefficient ?? optional($res->matiere)->coeff ?? null;
                            $rnom = optional($res->matiere)->name ?? '—';
                            $rclass = $rn === null ? '' : ($rn >= 12 ? 'good' : ($rn >= 10 ? 'mid' : 'bad'));
                            $rcolor = $rn === null ? '#94a3b8' : ($rn >= 12 ? '#10b981' : ($rn >= 10 ? '#f59e0b' : '#ef4444'));
                            $rpct = $rn !== null ? min(100, round($rn / 20 * 100)) : 0;
                        @endphp
                        <div class="acad-mat-item">
                            <div class="acad-mat-top">
                                <span class="acad-mat-name">{{ $rnom }}</span>
                                @if($rc) <span class="acad-mat-coeff-pill">Coef. {{ $rc }}</span> @endif
                                <span class="acad-mat-score {{ $rclass }}">
                                    {{ $rn !== null ? number_format($rn, 2) : '—' }}
                                    @if($rn !== null)<span style="font-size:.65em; font-weight:500; opacity:.6;">/20</span>@endif
                                </span>
                            </div>
                            @if($rn !== null)
                            <div class="acad-mat-bar-wrap">
                                <div class="acad-mat-bar" style="width:{{ $rpct }}%; background:{{ $rcolor }};"></div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <p style="font-size:.8rem; color:var(--k-muted); text-align:center; padding:12px 0; margin:0;">
                            Détail des matières non disponible
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

    @elseif($acadSemestres->count())
        {{-- Résultats bruts sans bulletin --}}
        @foreach($acadSemestres as $periodeKey => $semGroup)
        @php
            /* Moyenne pondérée par coefficient pour ce semestre */
            $_sp = 0; $_sc = 0;
            foreach ($semGroup->whereNotNull('moyenne') as $_sr) {
                $_coef = $_sr->coefficient ?? 1;
                $_sp += $_sr->moyenne * $_coef;
                $_sc += $_coef;
            }
            $mgBrut = $_sc > 0 ? round($_sp / $_sc, 2) : 0;
            $semKeyB = 'acad-sem-b-' . Str::slug($periodeKey);
            $semBadgeClsB = $mgBrut >= 12 ? 'green' : ($mgBrut >= 10 ? 'amber' : 'red');
            $semLabelB = ucfirst(str_replace(['semestre', '_', '-'], ['Semestre ', ' ', ' '], strtolower($periodeKey)));
        @endphp
        <div class="acad-sem-block">
            <div class="acad-sem-header"
                 data-bs-toggle="collapse"
                 data-bs-target="#{{ $semKeyB }}"
                 aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                <div class="acad-sem-header-left">
                    <i class="fas fa-book-open" style="color:var(--k-blue); font-size:.8rem;"></i>
                    <span class="acad-sem-period">{{ $semLabelB ?: 'Semestre' }}</span>
                </div>
                <div class="acad-sem-header-right">
                    @if($mgBrut)
                        <span class="acad-sem-badge {{ $semBadgeClsB }}">{{ number_format($mgBrut, 2) }}/20</span>
                    @endif
                    <i class="fas fa-chevron-down acad-sem-chevron"></i>
                </div>
            </div>
            <div class="collapse {{ $loop->first ? 'show' : '' }}" id="{{ $semKeyB }}">
                <div class="acad-mat-list">
                    @foreach($semGroup->sortBy(fn($r) => optional($r->matiere)->name) as $res)
                    @php
                        $rn = $res->moyenne;
                        $rc = optional($res->matiere)->coefficient ?? optional($res->matiere)->coeff ?? null;
                        $rnom = optional($res->matiere)->name ?? '—';
                        $rclass = $rn === null ? '' : ($rn >= 12 ? 'good' : ($rn >= 10 ? 'mid' : 'bad'));
                        $rcolor = $rn === null ? '#94a3b8' : ($rn >= 12 ? '#10b981' : ($rn >= 10 ? '#f59e0b' : '#ef4444'));
                        $rpct = $rn !== null ? min(100, round($rn / 20 * 100)) : 0;
                    @endphp
                    <div class="acad-mat-item">
                        <div class="acad-mat-top">
                            <span class="acad-mat-name">{{ $rnom }}</span>
                            @if($rc) <span class="acad-mat-coeff-pill">Coef. {{ $rc }}</span> @endif
                            <span class="acad-mat-score {{ $rclass }}">
                                {{ $rn !== null ? number_format($rn, 2) : '—' }}
                                @if($rn !== null)<span style="font-size:.65em; font-weight:500; opacity:.6;">/20</span>@endif
                            </span>
                        </div>
                        @if($rn !== null)
                        <div class="acad-mat-bar-wrap">
                            <div class="acad-mat-bar" style="width:{{ $rpct }}%; background:{{ $rcolor }};"></div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    @else
        <div class="s-card">
            <p style="text-align:center; color:var(--k-muted); font-size:.85rem; padding:16px 0; margin:0;">
                <i class="fas fa-info-circle" style="margin-right:6px;"></i>Aucune note enregistrée pour cette année
            </p>
        </div>
    @endif

    @endif {{-- fin @else acadIsNotCurrentYear --}}

    {{-- ══ AUTRES ANNÉES ══════════════════════════════════════ --}}
    @php
        /* Si l'année courante n'a pas de données, toutes les inscriptions vont ici
           mais on exclut quand même l'inscription de l'année courante (sans données) */
        $acadInscsPrec = $acadIsNotCurrentYear
            ? $acadInscs->filter(fn($i) => $anneeCourante ? $i->annee_universitaire_id !== $anneeCourante->id : true)
            : $acadAutres;
    @endphp
    @if($acadInscsPrec->count())
    <div class="acad-arch-sep">
        <div class="acad-arch-sep-line"></div>
        <span class="acad-arch-sep-label"><i class="fas fa-history" style="margin-right:4px;"></i>Autres années</span>
        <div class="acad-arch-sep-line"></div>
    </div>

    @foreach($acadInscsPrec as $autreInsc)
    @php
        $autreBuls = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', optional($autreInsc->anneeUniversitaire)->id)
            ->orderBy('periode')->get();
        $autreMention = $autreBuls->last()?->mention;
        $autreAnneeLabel = optional($autreInsc->anneeUniversitaire)->name ?? 'Année N/A';
        $autreClasseLabel = optional($autreInsc->classe)->name ?? '';
        $autreArchKey = 'acad-arch-' . $autreInsc->id;

        /* Résultats bruts pour calcul pondéré : utilisés si bulletins sans moyenne_generale */
        $autreResultatsBruts = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', optional($autreInsc->anneeUniversitaire)->id)
            ->with(['matiere'])->get();

        /* Calcul moyenne générale : bulletins officiels si disponibles, sinon pondéré depuis ESBTPResultat */
        $autreBulsValides = $autreBuls->filter(fn($b) => ($b->moyenne_generale ?? 0) > 0);
        if ($autreBulsValides->count()) {
            $autreMg = round($autreBulsValides->avg('moyenne_generale'), 2);
        } elseif ($autreResultatsBruts->whereNotNull('moyenne')->count()) {
            $_sp = 0; $_sc = 0;
            foreach ($autreResultatsBruts->whereNotNull('moyenne') as $_ar) {
                $_coef = $_ar->coefficient ?? $_ar->matiere?->coefficient ?? 1;
                $_sp += $_ar->moyenne * $_coef;
                $_sc += $_coef;
            }
            $autreMg = $_sc > 0 ? round($_sp / $_sc, 2) : null;
        } else {
            $autreMg = null;
        }

        $autreMgColor = $autreMg === null ? 'var(--k-muted)' : ($autreMg >= 12 ? 'var(--k-success)' : ($autreMg >= 10 ? '#d97706' : 'var(--k-danger)'));

        /* Résultats groupés par période pour affichage dans le body */
        $autreResultats = !$autreBuls->count()
            ? $autreResultatsBruts->groupBy(fn($r) => $r->periode ?? 'N/A')
            : collect();
    @endphp
    <div class="acad-arch-card">
        <button class="acad-arch-toggle"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $autreArchKey }}"
                aria-expanded="false">
            <div class="acad-arch-toggle-left">
                <span class="acad-arch-year">{{ $autreAnneeLabel }}</span>
                @if($autreClasseLabel)
                    <span class="acad-arch-class">{{ $autreClasseLabel }}</span>
                @endif
            </div>
            <div class="acad-arch-toggle-right">
                @if($autreMg !== null)
                    <span class="acad-arch-kpi" style="color:{{ $autreMgColor }};">{{ number_format($autreMg, 2) }}/20</span>
                @endif
                @if($autreMention)
                    @php
                        $amCls = match(true) {
                            str_contains(strtolower($autreMention), 'excellent') || str_contains(strtolower($autreMention), 'très bien') => 'excellent',
                            str_contains(strtolower($autreMention), 'bien') && !str_contains(strtolower($autreMention), 'assez') => 'bien',
                            str_contains(strtolower($autreMention), 'assez') => 'assez',
                            default => 'passable',
                        };
                    @endphp
                    <span class="acad-mention-badge {{ $amCls }}" style="font-size:.65rem;">{{ $autreMention }}</span>
                @endif
                <i class="fas fa-chevron-down acad-arch-chevron"></i>
            </div>
        </button>
        <div class="collapse" id="{{ $autreArchKey }}">
            <div class="acad-arch-body">
                @if($autreBuls->count())
                    @foreach($autreBuls as $ab)
                    @php
                        $abMgRaw = $ab->moyenne_generale;
                        /* Si le bulletin n'a pas de moyenne_generale, calculer depuis ESBTPResultat pondéré */
                        if (!$abMgRaw && $ab->periode) {
                            $_abResultats = $autreResultatsBruts->where('periode', $ab->periode)->whereNotNull('moyenne');
                            if ($_abResultats->count()) {
                                $_abSp = 0; $_abSc = 0;
                                foreach ($_abResultats as $_abR) {
                                    $_abCoef = $_abR->coefficient ?? $_abR->matiere?->coefficient ?? 1;
                                    $_abSp += $_abR->moyenne * $_abCoef;
                                    $_abSc += $_abCoef;
                                }
                                $abMg = $_abSc > 0 ? round($_abSp / $_abSc, 2) : null;
                            } else {
                                $abMg = null;
                            }
                        } else {
                            $abMg = $abMgRaw ?: null;
                        }
                        $abLabel = ucfirst(str_replace(['semestre', '_', '-'], ['Semestre ', ' ', ' '], strtolower($ab->periode ?? '')));
                        $abMgCls = $abMg === null ? 'var(--k-muted)' : ($abMg >= 12 ? 'var(--k-success)' : ($abMg >= 10 ? '#d97706' : 'var(--k-danger)'));
                    @endphp
                    <div class="acad-arch-sem-row">
                        <span class="acad-arch-sem-name">{{ $abLabel ?: 'Semestre' }}</span>
                        <div class="acad-arch-sem-info">
                            @if($ab->rang)
                                <span style="font-size:.75rem; color:var(--k-muted);">
                                    <i class="fas fa-trophy" style="color:#f59e0b; font-size:.65rem;"></i> {{ $ab->rang }}
                                </span>
                            @endif
                            @if($abMg !== null)
                                <span style="font-size:.8rem; font-weight:700; color:{{ $abMgCls }};">{{ number_format($abMg, 2) }}/20</span>
                            @endif
                            <a href="{{ route('esbtp.bulletins.download', $ab) }}" class="acad-arch-pdf-link" target="_blank">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                    @endforeach
                @elseif($autreResultats->count())
                    @foreach($autreResultats as $arPeriode => $arGroup)
                    @php
                        /* Calcul pondéré par coefficient */
                        $_arSp = 0; $_arSc = 0;
                        foreach ($arGroup->whereNotNull('moyenne') as $_arR) {
                            $_arCoef = $_arR->coefficient ?? $_arR->matiere?->coefficient ?? 1;
                            $_arSp += $_arR->moyenne * $_arCoef;
                            $_arSc += $_arCoef;
                        }
                        $arMg = $_arSc > 0 ? round($_arSp / $_arSc, 2) : 0;
                        $arLabel = ucfirst(str_replace(['semestre', '_', '-'], ['Semestre ', ' ', ' '], strtolower($arPeriode)));
                        $arColor = $arMg >= 12 ? 'var(--k-success)' : ($arMg >= 10 ? '#d97706' : 'var(--k-danger)');
                    @endphp
                    <div class="acad-arch-sem-row">
                        <span class="acad-arch-sem-name">{{ $arLabel ?: 'Semestre' }}</span>
                        <div class="acad-arch-sem-info">
                            @if($arMg)
                                <span style="font-size:.8rem; font-weight:700; color:{{ $arColor }};">{{ number_format($arMg, 2) }}/20</span>
                            @endif
                            <span style="font-size:.72rem; color:var(--k-muted);">{{ $arGroup->count() }} matière(s)</span>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p style="font-size:.8rem; color:var(--k-muted); margin:0;">Aucune donnée disponible</p>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    @endif

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
        $presAnneeCourante    = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $inscriptionsPresences = $etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date);
        $presInscCourante     = $presAnneeCourante
            ? $inscriptionsPresences->first(fn($i) => $i->annee_universitaire_id === $presAnneeCourante->id)
            : null;
        $presInscsPrecedentes = $presInscCourante
            ? $inscriptionsPresences->filter(fn($i) => $i->id !== $presInscCourante->id)
            : $inscriptionsPresences;
    @endphp

    @if($inscriptionsPresences->isEmpty())
        <div class="s-card">
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>Aucune donnée de présence disponible</p>
            </div>
        </div>
    @else

    {{-- ── Année en cours : bloc FLAT (toujours visible) ── --}}
    @if($presAnneeCourante)
    @php
        if ($presInscCourante) {
            $anneePresId    = $presAnneeCourante->id;
            $attRowCur = \App\Models\ESBTPAttendance::finalOnly()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneePresId)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN statut='present' THEN 1 ELSE 0 END) as nb_presences, SUM(CASE WHEN statut='absent' THEN 1 ELSE 0 END) as nb_absences, SUM(CASE WHEN statut='excuse' THEN 1 ELSE 0 END) as nb_absences_just, SUM(CASE WHEN statut IN ('retard','late') THEN 1 ELSE 0 END) as nb_retards")
                ->first();
            $totalCur  = (int)($attRowCur->total ?? 0);
            $presCur   = (int)($attRowCur->nb_presences ?? 0);
            $retardCur = (int)($attRowCur->nb_retards ?? 0);
            $absCur    = (int)($attRowCur->nb_absences ?? 0);
            $justCur   = (int)($attRowCur->nb_absences_just ?? 0);
            $tauxCur   = $totalCur > 0 ? round(($presCur + $retardCur) / $totalCur * 100, 1) : null;
        }
    @endphp
    <div class="fin-hero" style="margin-bottom:16px;">
        <div class="fin-hero-year-badge">
            <i class="fas fa-calendar-check"></i>
            Année en cours :
            <strong>{{ $presAnneeCourante->name }}</strong>
            @if($presInscCourante?->classe)
                &middot; {{ $presInscCourante->classe->name }}
            @endif
        </div>

        @if(!$presInscCourante)
            <div style="display:flex; align-items:center; gap:10px; padding:14px 16px; background:rgba(239,68,68,.06); border-radius:10px; border-left:3px solid #ef4444;">
                <i class="fas fa-exclamation-circle" style="color:#ef4444; font-size:1.1rem; flex-shrink:0;"></i>
                <span style="font-size:.84rem; color:#991b1b; font-weight:500;">Aucune inscription pour {{ $presAnneeCourante->name }} — données de présence indisponibles.</span>
            </div>
        @elseif($totalCur === 0)
            <div style="text-align:center; padding:28px 16px; color:var(--k-muted);">
                <i class="fas fa-calendar-times" style="font-size:2rem; opacity:.25; display:block; margin-bottom:12px;"></i>
                <p style="font-size:.84rem; margin:0; font-weight:500;">Aucune séance de présence enregistrée pour cette année.</p>
            </div>
        @else
            @php
                $presConstatClassCur = ($tauxCur ?? 0) >= 80 ? 'good' : (($tauxCur ?? 0) >= 60 ? 'warning' : 'bad');
                $presConstatIconCur  = ($tauxCur ?? 0) >= 80 ? 'fa-thumbs-up' : (($tauxCur ?? 0) >= 60 ? 'fa-exclamation-circle' : 'fa-times-circle');
                if (($tauxCur ?? 0) >= 90) {
                    $presConstatTextCur = "Excellent — taux de présence de {$tauxCur}%. L'étudiant est très assidu.";
                } elseif (($tauxCur ?? 0) >= 80) {
                    $presConstatTextCur = "Bon — taux de présence de {$tauxCur}%. Quelques absences ponctuelles sans impact notable.";
                } elseif (($tauxCur ?? 0) >= 70) {
                    $presConstatTextCur = "Acceptable — taux de {$tauxCur}%. Une vigilance est recommandée pour maintenir la régularité.";
                } elseif (($tauxCur ?? 0) >= 60) {
                    $presConstatTextCur = "Insuffisant — taux de {$tauxCur}%. Les absences fréquentes risquent d'affecter les résultats académiques.";
                } else {
                    $presConstatTextCur = "Critique — taux de " . ($tauxCur ?? 0) . "%. Une intervention urgente est nécessaire.";
                }
                $rCur = 18; $circumCur = round(2*3.14159*$rCur, 1);
                $presRatioCur   = $totalCur > 0 ? ($presCur+$retardCur)/$totalCur : 0;
                $presOffsetCur  = round($circumCur * (1 - $presRatioCur), 1);
                $presColorCur   = ($tauxCur ?? 0) >= 80 ? '#10b981' : (($tauxCur ?? 0) >= 60 ? '#f59e0b' : '#ef4444');
            @endphp
            <div style="display:flex; align-items:center; gap:20px; margin-bottom:16px; flex-wrap:wrap;">
                <div class="py-stats">
                    <div class="py-stat"><div class="py-stat-val green">{{ $presCur + $retardCur }}</div><div class="py-stat-lbl">Présents</div></div>
                    <div class="py-stat"><div class="py-stat-val red">{{ $absCur }}</div><div class="py-stat-lbl">Absences</div></div>
                    <div class="py-stat"><div class="py-stat-val amber">{{ $justCur }}</div><div class="py-stat-lbl">Justifiés</div></div>
                    <div class="py-stat">
                        <div class="py-stat-val {{ ($tauxCur??0) >= 80 ? 'green' : (($tauxCur??0) >= 60 ? 'amber' : 'red') }}">{{ $tauxCur !== null ? $tauxCur . '%' : '—' }}</div>
                        <div class="py-stat-lbl">Taux</div>
                    </div>
                </div>
                <svg width="44" height="44" viewBox="0 0 44 44" style="transform:rotate(-90deg); flex-shrink:0;">
                    <circle cx="22" cy="22" r="{{ $rCur }}" fill="none" stroke="var(--k-border)" stroke-width="4"/>
                    <circle cx="22" cy="22" r="{{ $rCur }}" fill="none" stroke="{{ $presColorCur }}" stroke-width="4" stroke-linecap="round" stroke-dasharray="{{ $circumCur }}" stroke-dashoffset="{{ $presOffsetCur }}"/>
                </svg>
            </div>
            <div class="pres-stat-list">
                <div class="pres-stat-row">
                    <div class="pres-stat-icon green"><i class="fas fa-check"></i></div>
                    <span class="pres-stat-lbl">Présences</span>
                    <span class="pres-stat-val green">{{ $presCur + $retardCur }}</span>
                    <span class="pres-stat-pct">{{ $totalCur > 0 ? round(($presCur+$retardCur)/$totalCur*100) : 0 }}%</span>
                </div>
                @if($retardCur > 0)
                <div class="pres-stat-row" style="padding-left:20px; opacity:.85;">
                    <div class="pres-stat-icon amber" style="width:24px;height:24px;font-size:.65rem;"><i class="fas fa-clock"></i></div>
                    <span class="pres-stat-lbl" style="font-size:.78rem;">dont {{ $retardCur }} retard(s)</span>
                    <span class="pres-stat-val amber">{{ $retardCur }}</span>
                    <span class="pres-stat-pct" style="font-size:.75rem;">{{ $totalCur > 0 ? round($retardCur/$totalCur*100) : 0 }}%</span>
                </div>
                @endif
                <div class="pres-stat-row">
                    <div class="pres-stat-icon red"><i class="fas fa-times"></i></div>
                    <span class="pres-stat-lbl">Absences injustifiées</span>
                    <span class="pres-stat-val red">{{ $absCur }}</span>
                    <span class="pres-stat-pct">{{ $totalCur > 0 ? round($absCur/$totalCur*100) : 0 }}%</span>
                </div>
                @if($justCur > 0)
                <div class="pres-stat-row">
                    <div class="pres-stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <span class="pres-stat-lbl">Absences justifiées</span>
                    <span class="pres-stat-val blue">{{ $justCur }}</span>
                    <span class="pres-stat-pct">{{ $totalCur > 0 ? round($justCur/$totalCur*100) : 0 }}%</span>
                </div>
                @endif
                <div class="pres-stat-row" style="border-top:1px solid rgba(255,255,255,.1); padding-top:10px; margin-top:2px;">
                    <div class="pres-stat-icon"><i class="fas fa-list"></i></div>
                    <span class="pres-stat-lbl">Total séances</span>
                    <span class="pres-stat-val">{{ $totalCur }}</span>
                </div>
            </div>
            <div class="presence-bars" style="margin-top:14px;">
                <div class="pbar-row">
                    <span class="pbar-lbl">Présent</span>
                    <div class="pbar-track"><div class="pbar-fill green" style="width:{{ $totalCur > 0 ? round(($presCur+$retardCur)/$totalCur*100) : 0 }}%"></div></div>
                    <span class="pbar-val" style="color:var(--k-success)">{{ $totalCur > 0 ? round(($presCur+$retardCur)/$totalCur*100) : 0 }}%</span>
                </div>
                <div class="pbar-row">
                    <span class="pbar-lbl">Absent</span>
                    <div class="pbar-track"><div class="pbar-fill red" style="width:{{ $totalCur > 0 ? round(($absCur+$justCur)/$totalCur*100) : 0 }}%"></div></div>
                    <span class="pbar-val" style="color:var(--k-danger)">{{ $totalCur > 0 ? round(($absCur+$justCur)/$totalCur*100) : 0 }}%</span>
                </div>
            </div>
            <div class="presence-constat {{ $presConstatClassCur }}" style="margin-top:14px;">
                <i class="fas {{ $presConstatIconCur }}"></i>
                <span>{{ $presConstatTextCur }}</span>
            </div>
        @endif
    </div>
    @endif

    {{-- ── Années précédentes : accordéons ── --}}
    @if($presInscsPrecedentes->count())
        @if($presInscCourante)
        <div style="display:flex; align-items:center; gap:10px; margin:8px 0 12px; opacity:.6;">
            <div style="flex:1; height:1px; background:var(--k-border);"></div>
            <span style="font-size:.72rem; font-weight:600; color:var(--k-muted); white-space:nowrap; letter-spacing:.04em;"><i class="fas fa-history" style="margin-right:4px;"></i>AUTRES ANNÉES</span>
            <div style="flex:1; height:1px; background:var(--k-border);"></div>
        </div>
        @endif
        @foreach($presInscsPrecedentes as $inscPres)
        @php
            $anneePresId = optional($inscPres->anneeUniversitaire)->id;
            $anneePresLabel = optional($inscPres->anneeUniversitaire)->name ?? 'Année N/A';
            $attRow = $anneePresId
                ? \App\Models\ESBTPAttendance::finalOnly()
                    ->where('etudiant_id', $etudiant->id)
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
            @php
                $presConstatClass = ($taux ?? 0) >= 80 ? 'good' : (($taux ?? 0) >= 60 ? 'warning' : 'bad');
                $presConstatIcon  = ($taux ?? 0) >= 80 ? 'fa-thumbs-up' : (($taux ?? 0) >= 60 ? 'fa-exclamation-circle' : 'fa-times-circle');
                if (($taux ?? 0) >= 90) {
                    $presConstatText = "Excellent — taux de présence de {$taux}%. L'étudiant est très assidu.";
                } elseif (($taux ?? 0) >= 80) {
                    $presConstatText = "Bon — taux de présence de {$taux}%. Quelques absences ponctuelles sans impact notable.";
                } elseif (($taux ?? 0) >= 70) {
                    $presConstatText = "Acceptable — taux de {$taux}%. Une vigilance est recommandée pour maintenir la régularité.";
                } elseif (($taux ?? 0) >= 60) {
                    $presConstatText = "Insuffisant — taux de {$taux}%. Les absences fréquentes risquent d'affecter les résultats académiques.";
                } else {
                    $presConstatText = "Critique — taux de " . ($taux ?? 0) . "%. Une intervention urgente est nécessaire.";
                }
            @endphp
            @if($total > 0)
            <div class="presence-year-body">
                <div class="pres-stat-list">
                    <div class="pres-stat-row">
                        <div class="pres-stat-icon green"><i class="fas fa-check"></i></div>
                        <span class="pres-stat-lbl">Présences</span>
                        <span class="pres-stat-val green">{{ $pres + $retard }}</span>
                        <span class="pres-stat-pct">{{ $total > 0 ? round(($pres+$retard)/$total*100) : 0 }}%</span>
                    </div>
                    @if($retard > 0)
                    <div class="pres-stat-row" style="padding-left:20px; opacity:.85;">
                        <div class="pres-stat-icon amber" style="width:24px;height:24px;font-size:.65rem;"><i class="fas fa-clock"></i></div>
                        <span class="pres-stat-lbl" style="font-size:.78rem;">dont {{ $retard }} retard(s)</span>
                        <span class="pres-stat-val amber">{{ $retard }}</span>
                        <span class="pres-stat-pct" style="font-size:.75rem;">{{ $total > 0 ? round($retard/$total*100) : 0 }}%</span>
                    </div>
                    @endif
                    <div class="pres-stat-row">
                        <div class="pres-stat-icon red"><i class="fas fa-times"></i></div>
                        <span class="pres-stat-lbl">Absences injustifiées</span>
                        <span class="pres-stat-val red">{{ $abs }}</span>
                        <span class="pres-stat-pct">{{ $total > 0 ? round($abs/$total*100) : 0 }}%</span>
                    </div>
                    @if($just > 0)
                    <div class="pres-stat-row">
                        <div class="pres-stat-icon blue"><i class="fas fa-file-alt"></i></div>
                        <span class="pres-stat-lbl">Absences justifiées</span>
                        <span class="pres-stat-val blue">{{ $just }}</span>
                        <span class="pres-stat-pct">{{ $total > 0 ? round($just/$total*100) : 0 }}%</span>
                    </div>
                    @endif
                    <div class="pres-stat-row" style="border-top:1px solid rgba(255,255,255,.1); padding-top:10px; margin-top:2px;">
                        <div class="pres-stat-icon"><i class="fas fa-list"></i></div>
                        <span class="pres-stat-lbl">Total séances</span>
                        <span class="pres-stat-val">{{ $total }}</span>
                    </div>
                </div>
                <div class="presence-bars" style="margin-top:14px;">
                    <div class="pbar-row">
                        <span class="pbar-lbl">Présent</span>
                        <div class="pbar-track"><div class="pbar-fill green" style="width:{{ $total > 0 ? round(($pres+$retard)/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:var(--k-success)">{{ $total > 0 ? round(($pres+$retard)/$total*100) : 0 }}%</span>
                    </div>
                    <div class="pbar-row">
                        <span class="pbar-lbl">Absent</span>
                        <div class="pbar-track"><div class="pbar-fill red" style="width:{{ $total > 0 ? round(($abs+$just)/$total*100) : 0 }}%"></div></div>
                        <span class="pbar-val" style="color:var(--k-danger)">{{ $total > 0 ? round(($abs+$just)/$total*100) : 0 }}%</span>
                    </div>
                </div>
                <div class="presence-constat {{ $presConstatClass }}">
                    <i class="fas {{ $presConstatIcon }}"></i>
                    <span>{{ $presConstatText }}</span>
                </div>
            </div>
            @else
            <div class="presence-year-body">
                <div style="text-align:center; padding:28px 16px; color:var(--k-muted);">
                    <i class="fas fa-calendar-times" style="font-size:2rem; opacity:.25; display:block; margin-bottom:12px;"></i>
                    <p style="font-size:.84rem; margin:0; font-weight:500;">Aucune séance de présence enregistrée pour cette année.</p>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    @endif {{-- /if presInscsPrecedentes->count() --}}
@endif {{-- /else: inscriptionsPresences not empty --}}
</div>{{-- /tab-presences --}}

{{-- ─── TAB: FINANCES ──────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-finances">
    @php
        /* ── Inscription de l'année courante (priorité absolue) ── */
        $finAnneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $finInscActive = $finAnneeCourante
            ? $etudiant->inscriptions->first(fn($i) => $i->annee_universitaire_id === $finAnneeCourante->id)
            : null;
        /* ── Autres inscriptions (toutes sauf l'active courante) ── */
        $finAutresInscs  = $etudiant->inscriptions->filter(fn($i) => !$finInscActive || $i->id !== $finInscActive->id)->sortByDesc('created_at');

        /* ── Collect paiements de l'inscription active ── */
        $finPaiementsActive = collect();
        if($finInscActive) {
            foreach($finInscActive->paiements ?? [] as $pai) {
                $pai->_annee = $finInscActive->anneeUniversitaire?->name ?? 'N/A';
                $finPaiementsActive->push($pai);
            }
        }
        $finPaiementsActive = $finPaiementsActive->sortByDesc('date_paiement');

        /* ── Calculs pour inscription active ── */
        $finTotalPaye    = $finPaiementsActive->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'valid'))->sum('montant');
        $finEnAttente    = $finPaiementsActive->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'attente'))->sum('montant');
        $finNbPaiements  = $finPaiementsActive->count();
        $finReliquats    = $statistiques['total_reliquats_entrants'] ?? 0;

        $finTotalAttendu = 0;
        if($finInscActive) {
            try { $finTotalAttendu = $finInscActive->fraisSubscriptions->sum('amount'); } catch(\Exception $e) {}
        }

        $finSolde  = $finTotalAttendu - $finTotalPaye;
        $finTaux   = $finTotalAttendu > 0 ? min(100, round($finTotalPaye / $finTotalAttendu * 100)) : 0;
    @endphp

    {{-- ── HERO FINANCIER ─────────────────────────────────────── --}}
    <div class="fin-hero">
        @if($finInscActive)
        <div class="fin-hero-year-badge">
            <i class="fas fa-calendar-check"></i>
            Année en cours :
            <strong>{{ $finInscActive->anneeUniversitaire?->name ?? 'N/A' }}</strong>
            @if($finInscActive->classe)
                &middot; {{ $finInscActive->classe->name }}
            @endif
        </div>
        @else
        <div class="fin-hero-year-badge" style="background:rgba(239,68,68,.15); border-color:rgba(239,68,68,.3);">
            <i class="fas fa-calendar-times" style="color:#ef4444;"></i>
            <span style="color:#ef4444;">Aucune inscription pour {{ $finAnneeCourante?->name ?? 'l\'année en cours' }}</span>
        </div>
        @endif
        <div class="fin-hero-grid">
            {{-- KPI 1 : Total attendu --}}
            <div class="fin-kpi-block">
                <div class="fin-kpi-label">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Total attendu
                </div>
                <div class="fin-kpi-amount neutral">
                    {{ number_format($finTotalAttendu, 0, ',', ' ') }}
                    <span class="fin-kpi-currency">FCFA</span>
                </div>
                <div class="fin-kpi-sub">
                    {{ $finNbPaiements }} transaction(s) enregistrée(s)
                </div>
            </div>

            {{-- Séparateur vertical --}}
            <div class="fin-sep"></div>

            {{-- KPI 2 : Total payé --}}
            <div class="fin-kpi-block">
                <div class="fin-kpi-label">
                    <i class="fas fa-check-circle"></i>
                    Total payé
                </div>
                <div class="fin-kpi-amount paid">
                    {{ number_format($finTotalPaye, 0, ',', ' ') }}
                    <span class="fin-kpi-currency">FCFA</span>
                </div>
                @if($finEnAttente > 0)
                <div class="fin-kpi-sub fin-kpi-sub-warn">
                    <i class="fas fa-clock"></i>
                    + {{ number_format($finEnAttente, 0, ',', ' ') }} FCFA en attente
                </div>
                @else
                <div class="fin-kpi-sub">
                    Paiements validés uniquement
                </div>
                @endif
            </div>

            {{-- Séparateur vertical --}}
            <div class="fin-sep"></div>

            {{-- KPI 3 : Solde --}}
            <div class="fin-kpi-block">
                <div class="fin-kpi-label">
                    <i class="fas fa-balance-scale"></i>
                    Solde restant
                </div>
                <div class="fin-kpi-amount {{ $finSolde > 0 ? 'due' : ($finSolde < 0 ? 'excess' : 'zero') }}">
                    {{ $finSolde > 0 ? '' : ($finSolde < 0 ? '-' : '') }}{{ number_format(abs($finSolde), 0, ',', ' ') }}
                    <span class="fin-kpi-currency">FCFA</span>
                </div>
                <div class="fin-kpi-sub {{ $finSolde > 0 ? 'fin-kpi-sub-danger' : '' }}">
                    @if($finSolde > 0)
                        <i class="fas fa-exclamation-circle"></i> Reste à régler
                    @elseif($finSolde < 0)
                        <i class="fas fa-info-circle"></i> Trop-perçu
                    @else
                        <i class="fas fa-check"></i> Situation apurée
                    @endif
                </div>
            </div>
        </div>

        {{-- Barre de progression globale --}}
        <div class="fin-progress-wrap">
            <div class="fin-progress-header">
                <span class="fin-progress-lbl">Progression du paiement</span>
                <span class="fin-progress-pct" style="color:{{ $finTaux >= 100 ? '#10b981' : ($finTaux >= 50 ? '#f59e0b' : '#ef4444') }}">{{ $finTaux }}%</span>
            </div>
            <div class="fin-progress-track">
                {{-- Segment en attente --}}
                @php
                    $finAttenteW = $finTotalAttendu > 0 ? min(100, round(($finTotalPaye + $finEnAttente) / $finTotalAttendu * 100)) : 0;
                @endphp
                @if($finEnAttente > 0 && $finTotalAttendu > 0)
                <div class="fin-progress-segment attente" style="width:{{ $finAttenteW }}%"></div>
                @endif
                {{-- Segment validé --}}
                <div class="fin-progress-segment valide" style="width:{{ $finTaux }}%"></div>
            </div>
            <div class="fin-progress-legend">
                <span><span class="fin-legend-dot valide"></span>Validé ({{ number_format($finTotalPaye, 0, ',', ' ') }} FCFA)</span>
                @if($finEnAttente > 0)<span><span class="fin-legend-dot attente"></span>En attente ({{ number_format($finEnAttente, 0, ',', ' ') }} FCFA)</span>@endif
                @if($finReliquats > 0)<span><span class="fin-legend-dot reliquat"></span>Reliquat ({{ number_format($finReliquats, 0, ',', ' ') }} FCFA)</span>@endif
            </div>
        </div>
    </div>

    {{-- ── TABLEAU RÉCAPITULATIF PAR INSCRIPTION (année active) ── --}}
    @php
        $finHasSubscriptions = false;
        if($finInscActive) {
            try {
                if($finInscActive->fraisSubscriptions->count() > 0) { $finHasSubscriptions = true; }
            } catch(\Exception $e) {}
        }
    @endphp

    @if($finHasSubscriptions)
    <div class="s-card" style="margin-bottom:16px;">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-table"></i></div>
                Détail par frais
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th style="text-align:right;">Attendu</th>
                        <th style="text-align:right;">Payé</th>
                        <th style="text-align:right;">Solde</th>
                        <th style="text-align:center;">Avancement</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($finInscActive->fraisSubscriptions as $sub)
                    @php
                        $subPaye  = $finPaiementsActive->filter(fn($p) =>
                            ($p->frais_category_id ?? null) == ($sub->frais_category_id ?? null) &&
                            str_contains(strtolower($p->status ?? $p->statut ?? ''), 'valid')
                        )->sum('montant');
                        $subSolde = $sub->amount - $subPaye;
                        $subTaux  = $sub->amount > 0 ? min(100, round($subPaye / $sub->amount * 100)) : 0;
                    @endphp
                    <tr>
                        <td>
                            <span class="fin-cat-name">{{ $sub->fraisCategory->name ?? '—' }}</span>
                        </td>
                        <td style="text-align:right; font-weight:600; color:var(--k-text);">{{ number_format($sub->amount, 0, ',', ' ') }}</td>
                        <td style="text-align:right; font-weight:700; color:#10b981;">{{ number_format($subPaye, 0, ',', ' ') }}</td>
                        <td style="text-align:right; font-weight:700; color:{{ $subSolde > 0 ? '#f59e0b' : '#10b981' }};">
                            {{ $subSolde > 0 ? number_format($subSolde, 0, ',', ' ') : '✓' }}
                        </td>
                        <td style="text-align:center; min-width:100px;">
                            <div class="fin-mini-track">
                                <div class="fin-mini-fill" style="width:{{ $subTaux }}%; background:{{ $subTaux >= 100 ? '#10b981' : ($subTaux >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
                            </div>
                            <span style="font-size:.72rem; color:var(--k-muted);">{{ $subTaux }}%</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── HISTORIQUE PAIEMENTS (année active) ────────────────── --}}
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-history"></i></div>
                Historique des paiements
            </div>
        </div>

        @if($finPaiementsActive->count())
        <div class="fin-pmt-list">
            @foreach($finPaiementsActive as $pai)
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
                $borderCol = match($statusKey) {
                    'valide'  => '#10b981',
                    'attente' => '#f59e0b',
                    'rejete'  => '#ef4444',
                    default   => '#94a3b8'
                };
            @endphp
            <div class="fin-pmt-row" style="--pmt-accent:{{ $borderCol }}">
                <div class="fin-pmt-accent-bar"></div>
                <div class="fin-pmt-content">
                    <div class="fin-pmt-top">
                        <div class="fin-pmt-motif">{{ $pai->motif ?? ($pai->fraisCategory->name ?? 'Paiement') }}</div>
                        <div class="fin-pmt-amount">
                            {{ number_format($pai->montant, 0, ',', ' ') }}
                            <small>FCFA</small>
                        </div>
                    </div>
                    <div class="fin-pmt-bottom">
                        <div class="fin-pmt-meta-list">
                            @if($pai->date_paiement)
                            <span class="fin-pmt-chip">
                                <i class="fas fa-calendar-day"></i>
                                {{ \Carbon\Carbon::parse($pai->date_paiement)->format('d/m/Y') }}
                            </span>
                            @endif
                            @if($pai->mode_paiement)
                            <span class="fin-pmt-chip">
                                <i class="fas fa-credit-card"></i>
                                {{ $pai->mode_paiement }}
                            </span>
                            @endif
                            @if($pai->numero_recu)
                            <span class="fin-pmt-chip mono">
                                <i class="fas fa-receipt"></i>
                                {{ $pai->numero_recu }}
                            </span>
                            @endif
                            @if($pai->_annee ?? null)
                            <span class="fin-pmt-chip">
                                <i class="fas fa-layer-group"></i>
                                {{ $pai->_annee }}
                            </span>
                            @endif
                        </div>
                        <div class="fin-pmt-right">
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
                </div>
            </div>
            @endforeach
        </div>
        @else
            <div class="empty-state">
                <i class="fas fa-wallet"></i>
                <p>Aucun paiement enregistré pour cette année</p>
            </div>
        @endif
    </div>

    {{-- ── AUTRES INSCRIPTIONS (années précédentes) ────────────── --}}
    @if($finAutresInscs->count())
    <div class="fin-autres-header">
        <i class="fas fa-archive"></i>
        Autres années ({{ $finAutresInscs->count() }})
    </div>

    @foreach($finAutresInscs as $autreInsc)
    @php
        /* calculs pour cette inscription archivée */
        $autrePaiements = collect();
        foreach($autreInsc->paiements ?? [] as $p) { $autrePaiements->push($p); }
        $autrePaye    = $autrePaiements->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'valid'))->sum('montant');
        $autreAttente = $autrePaiements->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'attente'))->sum('montant');
        $autreAttendu = 0;
        try { $autreAttendu = $autreInsc->fraisSubscriptions->sum('amount'); } catch(\Exception $e) {}
        $autreSolde   = $autreAttendu - $autrePaye;
        $autreTaux    = $autreAttendu > 0 ? min(100, round($autrePaye / $autreAttendu * 100)) : 0;
        $autreAccordionId = 'fin-arch-' . $autreInsc->id;
    @endphp
    <div class="fin-arch-card">
        <button class="fin-arch-toggle" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $autreAccordionId }}"
                aria-expanded="false">
            <div class="fin-arch-toggle-left">
                <span class="fin-arch-year">{{ $autreInsc->anneeUniversitaire?->name ?? 'N/A' }}</span>
                @if($autreInsc->classe)
                <span class="fin-arch-class">{{ $autreInsc->classe->name }}</span>
                @endif
            </div>
            <div class="fin-arch-toggle-right">
                <span class="fin-arch-kpi" style="color:{{ $autreTaux >= 100 ? '#10b981' : ($autreTaux >= 50 ? '#f59e0b' : '#ef4444') }}">
                    {{ $autreTaux }}%
                </span>
                <span class="fin-arch-kpi-lbl">{{ number_format($autrePaye, 0, ',', ' ') }} / {{ number_format($autreAttendu, 0, ',', ' ') }} FCFA</span>
                <i class="fas fa-chevron-down fin-arch-chevron"></i>
            </div>
        </button>

        <div class="collapse" id="{{ $autreAccordionId }}">
            <div class="fin-arch-body">
                {{-- Mini hero stats --}}
                <div class="fin-arch-stats">
                    <div class="fin-arch-stat">
                        <div class="fin-arch-stat-lbl">Attendu</div>
                        <div class="fin-arch-stat-val neutral">{{ number_format($autreAttendu, 0, ',', ' ') }}</div>
                    </div>
                    <div class="fin-arch-stat">
                        <div class="fin-arch-stat-lbl">Payé</div>
                        <div class="fin-arch-stat-val paid">{{ number_format($autrePaye, 0, ',', ' ') }}</div>
                    </div>
                    <div class="fin-arch-stat">
                        <div class="fin-arch-stat-lbl">Solde</div>
                        <div class="fin-arch-stat-val {{ $autreSolde > 0 ? 'due' : 'zero' }}">
                            {{ $autreSolde > 0 ? number_format($autreSolde, 0, ',', ' ') : '✓ Apuré' }}
                        </div>
                    </div>
                    @if($autreAttente > 0)
                    <div class="fin-arch-stat">
                        <div class="fin-arch-stat-lbl">En attente</div>
                        <div class="fin-arch-stat-val" style="color:#f59e0b;">{{ number_format($autreAttente, 0, ',', ' ') }}</div>
                    </div>
                    @endif
                </div>

                {{-- Détail frais --}}
                @if($autreInsc->fraisSubscriptions->count())
                <div style="overflow-x:auto; margin-bottom:12px;">
                    <table class="fin-table" style="font-size:.82rem;">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th style="text-align:right;">Attendu</th>
                                <th style="text-align:right;">Payé</th>
                                <th style="text-align:right;">Solde</th>
                                <th style="text-align:center;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($autreInsc->fraisSubscriptions as $sub)
                            @php
                                $aSubPaye  = $autrePaiements->filter(fn($p) =>
                                    ($p->frais_category_id ?? null) == ($sub->frais_category_id ?? null) &&
                                    str_contains(strtolower($p->status ?? $p->statut ?? ''), 'valid')
                                )->sum('montant');
                                $aSubSolde = $sub->amount - $aSubPaye;
                                $aSubTaux  = $sub->amount > 0 ? min(100, round($aSubPaye / $sub->amount * 100)) : 0;
                            @endphp
                            <tr>
                                <td><span class="fin-cat-name">{{ $sub->fraisCategory->name ?? '—' }}</span></td>
                                <td style="text-align:right; color:var(--k-text);">{{ number_format($sub->amount, 0, ',', ' ') }}</td>
                                <td style="text-align:right; color:#10b981; font-weight:700;">{{ number_format($aSubPaye, 0, ',', ' ') }}</td>
                                <td style="text-align:right; color:{{ $aSubSolde > 0 ? '#f59e0b' : '#10b981' }}; font-weight:700;">
                                    {{ $aSubSolde > 0 ? number_format($aSubSolde, 0, ',', ' ') : '✓' }}
                                </td>
                                <td style="text-align:center; color:var(--k-muted); font-size:.75rem;">{{ $aSubTaux }}%</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Paiements archivés --}}
                @if($autrePaiements->count())
                <div class="fin-arch-pmt-label">Paiements ({{ $autrePaiements->count() }})</div>
                <div class="fin-pmt-list" style="margin-top:0;">
                    @foreach($autrePaiements->sortByDesc('date_paiement') as $pai)
                    @php
                        $aStatus = $pai->status ?? $pai->statut ?? 'en_attente';
                        $aStatusKey = match(true) {
                            str_contains(strtolower($aStatus), 'valid') => 'valide',
                            str_contains(strtolower($aStatus), 'rejet') => 'rejete',
                            default => 'attente'
                        };
                        $aBorderCol = match($aStatusKey) { 'valide' => '#10b981', 'rejete' => '#ef4444', default => '#f59e0b' };
                        $aStatusLabel = match($aStatusKey) { 'valide' => 'Validé', 'rejete' => 'Rejeté', default => 'En attente' };
                    @endphp
                    <div class="fin-pmt-row" style="--pmt-accent:{{ $aBorderCol }}">
                        <div class="fin-pmt-accent-bar"></div>
                        <div class="fin-pmt-content">
                            <div class="fin-pmt-top">
                                <div class="fin-pmt-motif">{{ $pai->motif ?? ($pai->fraisCategory->name ?? 'Paiement') }}</div>
                                <div class="fin-pmt-amount">{{ number_format($pai->montant, 0, ',', ' ') }} <small>FCFA</small></div>
                            </div>
                            <div class="fin-pmt-bottom">
                                <div class="fin-pmt-meta-list">
                                    @if($pai->date_paiement)
                                    <span class="fin-pmt-chip"><i class="fas fa-calendar-day"></i> {{ \Carbon\Carbon::parse($pai->date_paiement)->format('d/m/Y') }}</span>
                                    @endif
                                    @if($pai->mode_paiement)
                                    <span class="fin-pmt-chip"><i class="fas fa-credit-card"></i> {{ $pai->mode_paiement }}</span>
                                    @endif
                                    @if($pai->numero_recu)
                                    <span class="fin-pmt-chip mono"><i class="fas fa-receipt"></i> {{ $pai->numero_recu }}</span>
                                    @endif
                                </div>
                                <div class="fin-pmt-right">
                                    <span class="pmt-badge {{ $aStatusKey }}">{{ $aStatusLabel }}</span>
                                    @if(isset($pai->inscription_id))
                                    <a href="{{ route('esbtp.paiements.show', $pai) }}" class="pmt-act-btn view"><i class="fas fa-eye"></i></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align:center; padding:16px; color:var(--k-muted); font-size:.83rem;">Aucun paiement</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    @endif

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

{{-- Modal confirmation attestation (étudiant non réinscrit cette année) --}}
<div class="modal fade" id="attestationConfirmModal" tabindex="-1" aria-labelledby="attestationConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center gap-2" id="attestationConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Étudiant non réinscrit cette année
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-1">
                    Cet étudiant ne s'est pas réinscrit pour
                    <strong>{{ $anneeCourante->name ?? "l'année en cours" }}</strong>.
                </p>
                <p class="text-muted" style="font-size:.9rem;">
                    La dernière inscription disponible sera utilisée pour générer ce document.
                    Voulez-vous continuer quand même ?
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="attestationConfirmBtn">
                    <i class="fas fa-check me-1"></i>Continuer quand même
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var _attestationUrl = null;
    window.openAttestationConfirm = function (e, url) {
        e.preventDefault();
        _attestationUrl = url;
        var modal = new bootstrap.Modal(document.getElementById('attestationConfirmModal'));
        modal.show();
    };
    document.getElementById('attestationConfirmBtn').addEventListener('click', function () {
        if (_attestationUrl) {
            window.open(_attestationUrl, '_blank');
        }
        bootstrap.Modal.getInstance(document.getElementById('attestationConfirmModal')).hide();
    });
})();
</script>

@endsection

@push('scripts')
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
@endpush
