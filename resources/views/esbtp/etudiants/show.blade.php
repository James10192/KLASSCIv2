@extends('layouts.app')

@section('title', $etudiant->nom_complet . ' — Fiche étudiant — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   FICHE ÉTUDIANT PREMIUM — KLASSCI Design System 2025
   Tokens, hero glassmorphism, tabs animés, rings SVG, payment timeline
=================================================================== */

/* ── Toast succès paiement ────────────────────────────────────── */
.etd-toast-success {
    position: fixed; top: 24px; left: 50%; transform: translateX(-50%) translateY(-20px);
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff; padding: 14px 28px; border-radius: 12px;
    font-size: .9rem; font-weight: 600;
    box-shadow: 0 8px 32px rgba(16,185,129,.35);
    z-index: 9999; opacity: 0;
    transition: opacity .3s, transform .3s;
    display: flex; align-items: center; gap: 10px;
}
.etd-toast-success.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.etd-toast-success i { font-size: 1.1rem; }

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
    padding: 16px 22px 14px; /* compact : -50% vertical (32→16) pour voir info sans scroll */
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}

/* Avatar wrapper — permet le badge de statut */
.hero-avatar-wrap {
    position: relative; flex-shrink: 0;
}

/* Avatar — compact (96→64, gain -33%) */
.hero-avatar {
    width: 64px; height: 64px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.6);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 700; color: rgba(255,255,255,.9);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.18);
    backdrop-filter: blur(4px);
}
.hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Badge de statut — petit rond coloré en bas à droite de l'avatar */
.hero-avatar-status {
    position: absolute; bottom: 2px; right: 2px;
    width: 13px; height: 13px; border-radius: 50%;
    border: 2px solid rgba(4,83,203,.85);
    box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.hero-avatar-status.actif    { background: #10b981; }
.hero-avatar-status.inactif  { background: #94a3b8; }
.hero-avatar-status.abandon  { background: #ef4444; }

/* Bouton upload photo — overlay camera sur l'avatar (compact) */
.hero-avatar-upload {
    position: absolute; bottom: -2px; left: -2px;
    width: 22px; height: 22px; border-radius: 50%;
    background: rgba(4,83,203,.85);
    border: 2px solid white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: white; font-size: 0.55rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,.22);
    z-index: 2;
}
.hero-avatar-upload:hover {
    background: #0453cb;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(4,83,203,.4);
}
.hero-avatar-upload.uploading {
    pointer-events: none;
    background: rgba(4,83,203,.5);
}
.hero-avatar-upload .fa-spin { font-size: 0.65rem; }

/* Text block (compact) */
.hero-text { flex: 1; min-width: 180px; color: #fff; }
.hero-name { font-size: 1.15rem; font-weight: 700; letter-spacing: -.01em; margin: 0 0 2px; line-height: 1.18; }
.hero-sub  { font-size: .76rem; opacity: .78; margin: 0 0 6px; line-height: 1.3; }
.hero-pills { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
.hero-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .68rem; font-weight: 600;
    padding: 2px 9px; border-radius: 999px;
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

/* Actions in hero — poussées à droite (compact) */
.hero-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; margin-left: auto; }
.hero-btns { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.hero-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 7px; font-size: .74rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap; line-height: 1.2;
}
.hero-btn.primary { background: rgba(255,255,255,.95); color: var(--k-blue); }
.hero-btn.primary:hover { background: #fff; color: var(--k-blue); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.hero-btn.ghost:hover { background: rgba(255,255,255,.25); }
.hero-btn.danger { background: rgba(239,68,68,.2); color: #fff; border: 1px solid rgba(239,68,68,.4); }
.hero-btn.danger:hover { background: rgba(239,68,68,.35); }

/* ── Mini KPI Strip compact (inside hero, above tabs) ────────────────────── */
.hero-kpi-strip {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; gap: 0;
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 2px;
}
.hero-kpi {
    flex: 1; padding: 8px 14px;
    display: flex; align-items: center; gap: 9px;
    border-right: 1px solid rgba(255,255,255,.1);
    color: #fff;
}
.hero-kpi:last-child { border-right: none; }
.hero-kpi-icon { font-size: .85rem; opacity: .7; }
.hero-kpi-val { font-size: .95rem; font-weight: 700; line-height: 1; }
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
   CARD TPE — Travail Personnel Etudiant (lecture seule UEMOA)
═══════════════════════════════════════════════════════════════════ */
.et-tpe-card {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(94,145,222,.08));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 16px;
}
.et-tpe-header {
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.et-tpe-icon {
    width: 44px; height: 44px; border-radius: 11px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; box-shadow: 0 4px 12px rgba(4,83,203,.2);
}
.et-tpe-body {
    flex: 1; min-width: 0; display: flex; flex-direction: column;
    gap: .15rem;
}
.et-tpe-label {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b;
}
.et-tpe-value {
    font-size: 1.6rem; font-weight: 800; color: #0453cb; line-height: 1;
}
.et-tpe-hint {
    font-size: .76rem; color: #64748b; margin-top: .15rem;
}
.et-tpe-breakdown {
    display: flex; gap: .5rem; flex-wrap: wrap; flex-shrink: 0;
}
.et-tpe-sem {
    display: inline-flex; flex-direction: column; align-items: center;
    padding: .4rem .7rem; background: #fff;
    border: 1px solid rgba(4,83,203,.2); border-radius: 10px;
    min-width: 60px;
}
.et-tpe-sem-label {
    font-size: .68rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .4px;
}
.et-tpe-sem-value {
    font-size: .92rem; font-weight: 700; color: #0453cb;
    margin-top: .15rem;
}
@media (max-width: 600px) {
    .et-tpe-header { flex-direction: column; align-items: flex-start; }
    .et-tpe-breakdown { width: 100%; justify-content: flex-start; }
}

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
.acad-ring-wrap { position: relative; width: 90px; height: 90px; margin-bottom: 10px; }
.acad-ring-wrap svg { width: 90px; height: 90px; transform: rotate(-90deg); }
.acad-ring-wrap .acad-ring-bg { fill: none; stroke: rgba(255,255,255,.12); stroke-width: 4; }
.acad-ring-wrap .acad-ring-fg { fill: none; stroke-width: 4; stroke-linecap: round; transition: stroke-dashoffset .8s cubic-bezier(.4,0,.2,1); }
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
    width: 90px; height: 90px; margin-bottom: 10px;
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
    width: 90px; height: 90px; margin-bottom: 10px;
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

/* ══════════════════════════════════════════════════════════════════
   DOCUMENT UPLOAD MODAL — Premium Design
══════════════════════════════════════════════════════════════════ */
.doc-upload-modal .modal-dialog { max-width: 780px; }

.dum-content {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(4,83,203,.22), 0 4px 16px rgba(0,0,0,.10);
}

/* ── Header ── */
.dum-header {
    position: relative;
    background: linear-gradient(140deg, #0344a8 0%, #0453cb 45%, #5e91de 100%);
    padding: 22px 28px 20px;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 16px;
}
.dum-header-bg {
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='32' height='32' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='16' cy='16' r='1.5' fill='rgba(255,255,255,0.08)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.dum-close {
    position: absolute; top: 14px; right: 16px;
    width: 32px; height: 32px;
    background: rgba(255,255,255,.15); border: none; border-radius: 50%;
    color: #fff; font-size: .85rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s;
}
.dum-close:hover { background: rgba(255,255,255,.28); }

.dum-header-icon {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    background: rgba(255,255,255,.18);
    backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
    animation: dum-icon-in .4s cubic-bezier(.34,1.56,.64,1);
}
@keyframes dum-icon-in {
    from { transform: translateY(-12px) scale(.8); opacity: 0; }
    to   { transform: translateY(0) scale(1);   opacity: 1; }
}
.dum-header-texts { flex: 1; }
.dum-header-title {
    font-size: 1.05rem; font-weight: 700; color: #fff;
    margin: 0 0 3px; letter-spacing: -.01em;
}
.dum-header-sub {
    font-size: .78rem; color: rgba(255,255,255,.72);
    margin: 0; font-weight: 500;
}

/* ── Body — layout 2 colonnes paysage ── */
.dum-body {
    padding: 20px 24px 16px;
    background: #fff;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: stretch;
}
.dum-body-left { display: flex; flex-direction: column; }
.dum-body-right { display: flex; flex-direction: column; }

.dum-alert {
    padding: 10px 14px; border-radius: 10px;
    font-size: .84rem; margin-bottom: 16px;
    border: none;
}
.dum-alert.alert-warning { background: #fffbeb; color: #92400e; border-left: 3px solid #f59e0b; }
.dum-alert.alert-danger  { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }

.dum-field {
    display: flex; gap: 12px; align-items: flex-start;
    margin-bottom: 16px;
}
.dum-field-icon {
    width: 36px; height: 36px; flex-shrink: 0; margin-top: 2px;
    background: #f0f5ff; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--k-blue); font-size: .85rem;
}
.dum-field-inner { flex: 1; min-width: 0; }
.dum-label {
    display: block; font-size: .78rem; font-weight: 600;
    color: var(--k-muted); margin-bottom: 6px; letter-spacing: .03em; text-transform: uppercase;
}
.dum-required { color: var(--k-danger); }
.dum-optional { font-weight: 400; text-transform: none; letter-spacing: 0; color: #94a3b8; }
.dum-input {
    width: 100%; background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 10px; padding: 9px 13px;
    font-size: .9rem; color: var(--k-text);
    transition: border-color .2s, box-shadow .2s;
    outline: none; resize: none;
    font-family: inherit;
}
.dum-input:focus {
    border-color: var(--k-blue);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
    background: #fff;
}
.dum-textarea { min-height: 64px; }

/* ── Drop zone ── */
.dum-dropzone {
    position: relative;
    border: 2px dashed #cbd5e1; border-radius: 14px;
    background: #f8fafc;
    cursor: pointer; transition: border-color .2s, background .2s;
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.dum-dropzone:hover,
.dum-dropzone.drag-over {
    border-color: var(--k-blue);
    background: #eef3ff;
}
.dum-dropzone.drag-over { border-style: solid; }
.dum-file-input {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.dum-dz-idle {
    padding: 28px 20px;
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    pointer-events: none;
}
.dum-dz-icon {
    font-size: 2rem; color: #94a3b8;
    margin-bottom: 6px;
    transition: transform .3s, color .3s;
}
.dum-dropzone:hover .dum-dz-icon,
.dum-dropzone.drag-over .dum-dz-icon {
    transform: translateY(-4px); color: var(--k-blue);
}
.dum-dz-text { font-size: .9rem; font-weight: 600; color: var(--k-text); margin: 0; }
.dum-dz-sub  { font-size: .82rem; color: var(--k-muted); margin: 0; }
.dum-dz-browse { color: var(--k-blue); font-weight: 600; }
.dum-dz-formats { font-size: .74rem; color: #94a3b8; margin: 6px 0 0; letter-spacing: .02em; }

/* ── File preview ── */
.dum-dz-preview {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; pointer-events: none;
}
.dum-preview-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: #eef3ff; color: var(--k-blue);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.dum-preview-info {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 2px;
}
.dum-preview-name {
    font-size: .88rem; font-weight: 600; color: var(--k-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dum-preview-size { font-size: .76rem; color: var(--k-muted); }
.dum-preview-remove {
    pointer-events: auto;
    width: 28px; height: 28px; border-radius: 50%;
    background: #fee2e2; border: none; color: #ef4444;
    font-size: .75rem; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s;
}
.dum-preview-remove:hover { background: #fecaca; }

/* ── Footer ── */
.dum-footer {
    padding: 16px 24px 24px; background: #fff;
    display: flex; gap: 10px;
}
.dum-btn-cancel {
    flex: 0 0 auto; padding: 10px 20px;
    background: #f1f5f9; border: none; border-radius: 10px;
    font-size: .88rem; font-weight: 600; color: var(--k-muted);
    cursor: pointer; transition: background .2s;
}
.dum-btn-cancel:hover { background: #e2e8f0; }
.dum-btn-submit {
    flex: 1;
    padding: 11px 20px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    border: none; border-radius: 10px;
    font-size: .9rem; font-weight: 700; color: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: opacity .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 4px 14px rgba(4,83,203,.35);
}
.dum-btn-submit:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(4,83,203,.4); }
.dum-btn-submit:active { transform: translateY(0); }
.dum-btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

/* ══════════════════════════════════════════════════════════════════
   DOCUMENT PREVIEW MODAL
══════════════════════════════════════════════════════════════════ */
.dpm-dialog { max-width: 900px; }

.dpm-content {
    border: none; border-radius: 20px; overflow: hidden;
    box-shadow: 0 24px 64px rgba(4,83,203,.22), 0 4px 16px rgba(0,0,0,.10);
}

.dpm-header {
    position: relative;
    background: linear-gradient(140deg, #0344a8 0%, #0453cb 45%, #5e91de 100%);
    padding: 18px 24px;
    display: flex; align-items: center; gap: 14px;
    overflow: hidden;
}
.dpm-header-bg {
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='32' height='32' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='16' cy='16' r='1.5' fill='rgba(255,255,255,0.08)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.dpm-file-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.18); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.dpm-dl-btn {
    margin-left: auto; flex-shrink: 0;
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 10px;
    background: rgba(255,255,255,.18); border: 1.5px solid rgba(255,255,255,.3);
    color: #fff; font-size: .85rem; font-weight: 600;
    text-decoration: none; transition: background .2s;
    backdrop-filter: blur(4px);
    position: relative; z-index: 1;
}
.dpm-dl-btn:hover { background: rgba(255,255,255,.28); color: #fff; }

.dpm-body {
    background: #f1f5f9;
    min-height: 420px;
    display: flex; align-items: center; justify-content: center;
    padding: 0;
}
.dpm-body iframe {
    display: block; width: 100%; height: 70vh; border: none;
}
.dpm-body img {
    max-width: 100%; max-height: 70vh; display: block;
    margin: auto; border-radius: 0; object-fit: contain;
}
.dpm-placeholder {
    text-align: center; padding: 60px 32px; color: var(--k-muted);
}
.dpm-placeholder i { font-size: 3.5rem; color: #cbd5e1; margin-bottom: 16px; display: block; }
.dpm-placeholder p { margin: 0; font-size: .95rem; }
.dpm-placeholder small { font-size: .82rem; color: #94a3b8; }

/* ══════════════════════════════════════════════════════════════════
   DOCUMENT CARDS — Archive Raffiné
══════════════════════════════════════════════════════════════════ */
.doc-card {
    position: relative;
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8edf5;
    padding: 0;
    margin-bottom: 10px;
    transition: box-shadow .22s ease, border-color .22s ease, transform .18s ease;
    overflow: hidden;
}
.doc-card::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    background: linear-gradient(180deg, var(--k-blue), #5e91de);
    transform: scaleY(0);
    transform-origin: center;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1);
    border-radius: 3px 0 0 3px;
}
.doc-card:hover {
    box-shadow: 0 6px 24px rgba(4,83,203,.1), 0 2px 8px rgba(0,0,0,.05);
    border-color: #c7d7f5;
    transform: translateY(-2px);
}
.doc-card:hover::before { transform: scaleY(1); }

.doc-card-inner {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 16px 14px 20px;
}

/* ── Badge extension ── */
.doc-ext-badge {
    width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 1px;
}
.doc-ext-badge .ext-icon { font-size: .95rem; }
.doc-ext-badge .ext-label {
    font-size: .55rem; font-weight: 800; letter-spacing: .04em;
    line-height: 1;
}
.doc-ext-pdf  { background: #fee2e2; color: #dc2626; }
.doc-ext-doc,
.doc-ext-docx { background: #dbeafe; color: #2563eb; }
.doc-ext-xls,
.doc-ext-xlsx { background: #dcfce7; color: #16a34a; }
.doc-ext-png, .doc-ext-jpg,
.doc-ext-jpeg,.doc-ext-gif,
.doc-ext-webp { background: #ccfbf1; color: #0d9488; }
.doc-ext-zip,
.doc-ext-rar  { background: #fef9c3; color: #ca8a04; }
.doc-ext-txt  { background: #f1f5f9; color: #64748b; }
.doc-ext-default { background: #eef3ff; color: var(--k-blue); }

/* ── Body ── */
.doc-card-body { flex: 1; min-width: 0; }
.doc-card-title {
    font-size: .93rem; font-weight: 700; color: var(--k-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 2px; letter-spacing: -.01em;
}
.doc-card-desc {
    font-size: .8rem; color: var(--k-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 6px;
}
.doc-card-meta {
    display: flex; align-items: center; flex-wrap: wrap; gap: 0;
}
.doc-meta-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .73rem; color: #94a3b8;
    padding: 3px 8px; border-radius: 6px;
    background: #f8fafc; border: 1px solid #e9eff6;
    white-space: nowrap;
}
.doc-meta-chip i { font-size: .65rem; flex-shrink: 0; }
.doc-meta-sep {
    width: 1px; height: 12px; background: #e2e8f0;
    margin: 0 6px; flex-shrink: 0;
}

/* ── Action buttons ── */
.doc-card-actions { display: flex; gap: 6px; flex-shrink: 0; }
.doc-action-btn {
    width: 34px; height: 34px; border-radius: 9px;
    border: none; display: inline-flex; align-items: center; justify-content: center;
    font-size: .82rem; cursor: pointer; transition: background .18s, transform .12s;
    flex-shrink: 0;
}
.doc-action-btn:active { transform: scale(.92); }
.doc-action-btn.preview { background: #eef3ff; color: var(--k-blue); }
.doc-action-btn.preview:hover { background: #dce8ff; }
.doc-action-btn.delete { background: #fef2f2; color: #ef4444; }
.doc-action-btn.delete:hover { background: #fee2e2; }

/* ═══════════════════════════════════════════════════════════
   Saisie manuelle (heures par matière) — namespace mh-*
   Extrait du tab Présences : card claire, palette monochrome
   bleu KLASSCI + sémantiques (success/warning/danger) limitées
   aux indicateurs de statut (présence / abs. just / abs. non just).
   ═══════════════════════════════════════════════════════════ */
.mh-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.4rem;
    margin-top: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.mh-card[data-current="0"] {
    background: #f8fafc;
    margin-top: 1rem;
}

.mh-head {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}
.mh-head-title {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex: 1;
    min-width: 220px;
}
.mh-head-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
}
.mh-head-label { font-size: .92rem; font-weight: 700; color: #1e293b; line-height: 1.25; }
.mh-head-sub { font-size: .72rem; color: #64748b; margin-top: .1rem; }

.mh-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .7rem;
    font-weight: 600;
    padding: .22rem .65rem;
    border-radius: 999px;
    white-space: nowrap;
}
.mh-chip--priority { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.mh-chip--global   { background: #f0f9ff; color: #0c4a6e; border: 1px solid #bae6fd; }
.mh-chip--sm       { font-size: .65rem; padding: .15rem .5rem; }

.mh-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .75rem;
    margin-bottom: 1.15rem;
}
@media (max-width: 720px) {
    .mh-kpis { grid-template-columns: 1fr; }
}
.mh-kpi {
    display: flex;
    align-items: center;
    gap: .75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .75rem .9rem;
}
.mh-kpi-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .88rem;
}
.mh-kpi-icon--blue    { background: #dbeafe; color: #0453cb; }
.mh-kpi-icon--success { background: #dcfce7; color: #10b981; }
.mh-kpi-icon--warning { background: #fef3c7; color: #d97706; }
.mh-kpi-icon--danger  { background: #fee2e2; color: #dc2626; }
.mh-kpi-icon--muted   { background: #f1f5f9; color: #64748b; }
.mh-kpi-val {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    display: inline-flex;
    align-items: baseline;
    gap: .15rem;
}
.mh-kpi-unit { font-size: .78rem; font-weight: 600; color: #64748b; }
.mh-kpi-lbl { font-size: .72rem; color: #64748b; margin-top: .25rem; }

.mh-chart {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .9rem 1rem;
    margin-bottom: 1rem;
}
.mh-chart-legend {
    display: flex;
    gap: 1rem;
    margin-bottom: .75rem;
    flex-wrap: wrap;
    padding-bottom: .65rem;
    border-bottom: 1px dashed #e2e8f0;
}
.mh-legend-item { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; color: #64748b; font-weight: 500; }
.mh-legend-dot { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.mh-legend-dot--pres  { background: #10b981; }
.mh-legend-dot--absj  { background: #0453cb; }
.mh-legend-dot--absnj { background: #ef4444; }
.mh-chart-row {
    display: grid;
    grid-template-columns: minmax(120px, 1.2fr) minmax(0, 3fr) minmax(60px, auto);
    gap: .8rem;
    align-items: center;
    padding: .45rem 0;
}
.mh-chart-row + .mh-chart-row { border-top: 1px solid rgba(226,232,240,.6); }
.mh-chart-name {
    font-size: .82rem;
    color: #1e293b;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mh-chart-track {
    display: flex;
    height: 18px;
    background: #e2e8f0;
    border-radius: 5px;
    overflow: hidden;
    gap: 1px;
}
.mh-chart-seg { height: 100%; transition: width .25s ease; cursor: help; }
.mh-chart-seg--pres  { background: #10b981; }
.mh-chart-seg--absj  { background: #0453cb; }
.mh-chart-seg--absnj { background: #ef4444; }
.mh-chart-total {
    font-size: .82rem;
    font-weight: 700;
    color: #1e293b;
    text-align: right;
    display: inline-flex;
    align-items: baseline;
    gap: .1rem;
    justify-content: flex-end;
}
.mh-chart-unit { font-size: .68rem; color: #64748b; font-weight: 500; }

.mh-global {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
}
.mh-global-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: .7rem;
    flex-wrap: wrap;
}
.mh-global-periode { font-size: .78rem; color: #0c4a6e; font-weight: 600; }
.mh-global-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .75rem;
}
.mh-global-stat {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    background: rgba(255,255,255,.6);
    border-radius: 8px;
    padding: .55rem .7rem;
}
.mh-global-stat-lbl { font-size: .68rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.mh-global-stat-val { font-size: 1rem; font-weight: 800; }
.mh-global-stat-val--pres  { color: #10b981; }
.mh-global-stat-val--absj  { color: #0453cb; }
.mh-global-stat-val--absnj { color: #dc2626; }
.mh-global-note {
    font-size: .78rem;
    color: #0c4a6e;
    margin-top: .65rem;
    display: flex;
    align-items: flex-start;
    gap: .4rem;
}
.mh-global-note i { color: #0369a1; margin-top: .15rem; }

.mh-details { margin-top: 0; }
.mh-details-summary {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .6rem .85rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .8rem;
    font-weight: 600;
    color: #0453cb;
    cursor: pointer;
    list-style: none;
    transition: background .15s;
}
.mh-details-summary::-webkit-details-marker { display: none; }
.mh-details-summary:hover { background: #eff6ff; }
.mh-details-chevron { transition: transform .2s ease; }
.mh-details[open] .mh-details-chevron { transform: rotate(90deg); }
.mh-details-body { margin-top: .75rem; }

.mh-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.mh-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .8rem;
    background: #fff;
}
.mh-details-body .mh-table { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.mh-th {
    text-align: left;
    padding: .6rem .8rem;
    color: #64748b;
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 600;
    background: rgba(4,83,203,.04);
}
.mh-th--num { text-align: right; }
.mh-td {
    padding: .55rem .8rem;
    color: #1e293b;
    border-top: 1px solid #e2e8f0;
}
.mh-td--name   { font-weight: 500; }
.mh-td--muted  { color: #64748b; }
.mh-td--num    { text-align: right; font-weight: 600; }
.mh-td--pres   { color: #10b981; }
.mh-td--absj   { color: #0453cb; }
.mh-td--absnj  { color: #dc2626; }

.mh-footnote {
    font-size: .74rem;
    color: #64748b;
    margin: .8rem 0 0 0;
    font-style: italic;
    display: flex;
    align-items: flex-start;
    gap: .4rem;
}
.mh-footnote i { color: #0453cb; margin-top: .12rem; flex-shrink: 0; }

.presence-year-body .mh-card {
    background: #fff;
    margin-top: 1rem;
}

/* ═══════════════════════════════════════════════════════════
   mh-card "embedded" dans le dark hero fin-hero--pres
   (année courante avec seulement des heures manuelles) —
   reproduit le pattern de l'onglet Finances : UN SEUL dark hero
   qui contient badge année + KPIs + graphe, comme le widget finance.
   ═══════════════════════════════════════════════════════════ */
.fin-hero--pres {
    padding: 22px 24px 24px;
}
.mh-card--embedded {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
    margin-top: 18px;
}
.mh-card--embedded .mh-head { margin-bottom: 18px; align-items: center; }
.mh-card--embedded .mh-head-icon {
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff;
}
.mh-card--embedded .mh-head-label { color: #fff; font-size: .95rem; }
.mh-card--embedded .mh-head-sub   { color: rgba(255,255,255,.72); }
.mh-card--embedded .mh-chip--priority {
    background: rgba(255,255,255,.12);
    color: #fff;
    border-color: rgba(255,255,255,.2);
}
.mh-card--embedded .mh-chip--global {
    background: rgba(255,255,255,.12);
    color: #fff;
    border-color: rgba(255,255,255,.22);
}

/* KPIs dark */
.mh-card--embedded .mh-kpi {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.14);
}
.mh-card--embedded .mh-kpi-val { color: #fff; }
.mh-card--embedded .mh-kpi-unit { color: rgba(255,255,255,.65); }
.mh-card--embedded .mh-kpi-lbl { color: rgba(255,255,255,.7); }
.mh-card--embedded .mh-kpi-icon--blue    { background: rgba(147,197,253,.18); color: #bfdbfe; }
.mh-card--embedded .mh-kpi-icon--success { background: rgba(52,211,153,.18);  color: #a7f3d0; }
.mh-card--embedded .mh-kpi-icon--warning { background: rgba(251,191,36,.18);  color: #fde68a; }
.mh-card--embedded .mh-kpi-icon--danger  { background: rgba(248,113,113,.18); color: #fecaca; }
.mh-card--embedded .mh-kpi-icon--muted   { background: rgba(255,255,255,.1);  color: rgba(255,255,255,.7); }

/* Chart dark */
.mh-card--embedded .mh-chart {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
}
.mh-card--embedded .mh-chart-legend {
    border-bottom-color: rgba(255,255,255,.15);
}
.mh-card--embedded .mh-legend-item { color: rgba(255,255,255,.75); }
.mh-card--embedded .mh-chart-name  { color: #fff; }
.mh-card--embedded .mh-chart-track { background: rgba(255,255,255,.12); }
.mh-card--embedded .mh-chart-total { color: #fff; }
.mh-card--embedded .mh-chart-unit  { color: rgba(255,255,255,.65); }
.mh-card--embedded .mh-chart-row + .mh-chart-row { border-top-color: rgba(255,255,255,.08); }

/* Global row dark */
.mh-card--embedded .mh-global {
    background: rgba(147,197,253,.12);
    border-color: rgba(147,197,253,.28);
}
.mh-card--embedded .mh-global-periode { color: #bfdbfe; }
.mh-card--embedded .mh-global-stat { background: rgba(255,255,255,.08); }
.mh-card--embedded .mh-global-stat-lbl { color: rgba(255,255,255,.7); }
.mh-card--embedded .mh-global-stat-val--pres  { color: #34d399; }
.mh-card--embedded .mh-global-stat-val--absj  { color: #93c5fd; }
.mh-card--embedded .mh-global-stat-val--absnj { color: #fca5a5; }
.mh-card--embedded .mh-global-note { color: #bfdbfe; }
.mh-card--embedded .mh-global-note i { color: #93c5fd; }

/* Accordion détail dark-compatible */
.mh-card--embedded .mh-details-summary {
    background: rgba(255,255,255,.08);
    border-color: rgba(255,255,255,.15);
    color: #fff;
}
.mh-card--embedded .mh-details-summary:hover { background: rgba(255,255,255,.12); }
.mh-card--embedded .mh-details-body .mh-table { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.12); }
.mh-card--embedded .mh-th { background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); }
.mh-card--embedded .mh-td { color: #fff; border-top-color: rgba(255,255,255,.08); }
.mh-card--embedded .mh-td--muted  { color: rgba(255,255,255,.6); }
.mh-card--embedded .mh-td--pres   { color: #34d399; }
.mh-card--embedded .mh-td--absj   { color: #93c5fd; }
.mh-card--embedded .mh-td--absnj  { color: #fca5a5; }

.mh-card--embedded .mh-footnote { color: rgba(255,255,255,.7); }
.mh-card--embedded .mh-footnote i { color: #93c5fd; }
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
    // Inscription existe mais pas entièrement validée (workflow incomplet)
    $inscNonValidee = $inscCourante && (
        in_array($inscCourante->status, ['pending', 'en_attente'])
        || ($inscCourante->status === 'active' && $inscCourante->workflow_step !== 'etudiant_cree')
    );

    // Inscriptions futures : filtrage commun (année start_date > courante)
    $isFutureYear = fn($i) => optional($i->anneeUniversitaire)->start_date
        && optional($anneeCourante)->start_date
        && $i->anneeUniversitaire->start_date > $anneeCourante->start_date;

    $inscFutureSousReserve = $anneeCourante
        ? $etudiant->inscriptions->first(fn($i) => $i->is_sous_reserve && $isFutureYear($i))
        : null;

    $inscFutureNonReserve = $anneeCourante
        ? $etudiant->inscriptions->first(fn($i) => !$i->is_sous_reserve && $isFutureYear($i))
        : null;

    // Pré-inscription caissier : matricule PRE-* = infos à compléter
    $isPreInscription = $etudiant->matricule && str_starts_with($etudiant->matricule, 'PRE-') && $inscCourante;
    $preInscriptionMissing = [];
    if ($isPreInscription) {
        if (!$etudiant->date_naissance) $preInscriptionMissing[] = 'Date de naissance';
        if (!$etudiant->sexe) $preInscriptionMissing[] = 'Sexe';
        if (!$etudiant->lieu_naissance) $preInscriptionMissing[] = 'Lieu de naissance';
        if (!$etudiant->adresse) $preInscriptionMissing[] = 'Adresse';
        $preInscriptionCreator = $inscCourante->createdBy ?? null;
    }

    // Defaults LMD (safety fallback)
    $isLMD = $isLMD ?? false;
    $bulletinLMD = $bulletinLMD ?? null;
    $bulletinsLMD = $bulletinsLMD ?? collect();
    $lmdMoyenneAnnuelle = $lmdMoyenneAnnuelle ?? null;
    $parcours = $parcours ?? null;
    $lmdCredits = $lmdCredits ?? null;
@endphp
<div class="fiche-hero">
    <div class="hero-inner">
        {{-- Avatar avec badge statut --}}
        <div class="hero-avatar-wrap">
            <div class="hero-avatar" id="heroAvatarDisplay">
                @if($etudiant->photo && $etudiant->photo_url)
                    <img src="{{ $etudiant->photo_url }}"
                         alt="{{ $etudiant->nom_complet }}"
                         onerror="this.parentElement.innerHTML='<i class=\'fas fa-user-graduate\'></i>'">
                @else
                    {{ strtoupper(substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($etudiant->nom, 0, 1)) }}
                @endif
            </div>
            {{-- Badge rond : vert seulement si inscrit cette année, sinon gris --}}
            <span class="hero-avatar-status {{ $estInscritCetteAnnee ? 'actif' : 'inactif' }}"
                  title="{{ $estInscritCetteAnnee ? 'Inscrit ' . ($anneeCourante->name ?? '') : ($inscFutureSousReserve ? 'Pré-inscrit ' . ($inscFutureSousReserve->anneeUniversitaire->name ?? '') . ' (sous réserve)' : 'Non inscrit pour l\'année en cours') }}"></span>
            {{-- Bouton upload photo (superAdmin / secretaire) --}}
            @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']))
                <label class="hero-avatar-upload" id="heroPhotoUploadBtn" title="Modifier la photo">
                    <i class="fas fa-camera"></i>
                    <input type="file" accept="image/jpeg,image/png,image/jpg,image/gif"
                           style="display:none;" id="heroPhotoInput"
                           onchange="uploadEtudiantPhoto(this)">
                </label>
            @endif
        </div>

        {{-- Text --}}
        <div class="hero-text">
            <h1 class="hero-name">{{ strtoupper($etudiant->nom) }} {{ $etudiant->prenoms }}</h1>
            <p class="hero-sub">
                @if($inscCourante && $inscCourante->classe)
                    {{ $inscCourante->classe->name }}
                    @if($isLMD && $parcours)
                        · {{ $parcours->name }}
                        @if($inscCourante->classe->niveau) · {{ $inscCourante->classe->niveau->name ?? $inscCourante->classe->niveau->nom ?? '' }} @endif
                        @php $sems = $lmdCredits['semestres'] ?? []; @endphp
                        @if(count($sems) === 2) · <span style="opacity:.8">S{{ $sems[0] }}-S{{ $sems[1] }}</span> @endif
                    @else
                        @if($inscCourante->classe->filiere) · {{ $inscCourante->classe->filiere->name }} @endif
                        @if($inscCourante->classe->niveau) · {{ $inscCourante->classe->niveau->name ?? $inscCourante->classe->niveau->nom ?? '' }} @endif
                    @endif
                @elseif($inscFutureSousReserve)
                    {{ $inscFutureSousReserve->classe->name ?? '' }}
                    @if($inscFutureSousReserve->filiere) · {{ $inscFutureSousReserve->filiere->name }} @endif
                    <span style="color:rgba(255,255,255,0.75); font-style:italic;"> · {{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }} (sous réserve)</span>
                @elseif($anneeCourante)
                    <span style="color:rgba(255,255,255,0.75); font-style:italic;">Non réinscrit pour {{ $anneeCourante->name }}</span>
                @else
                    Étudiant
                @endif
            </p>
            @if($isLMD && $parcours && $parcours->mention && $parcours->mention->domaine)
                <p style="font-size:.78rem; color:rgba(255,255,255,.65); margin:-.1rem 0 .4rem; letter-spacing:.02em;">
                    <i class="fas fa-sitemap" style="font-size:.65rem; margin-right:.25rem;"></i>
                    {{ $parcours->mention->domaine->name }} <span style="opacity:.5">›</span> {{ $parcours->mention->name }}
                </p>
            @endif
            <div class="hero-pills">
                @include('esbtp.partials.bts-journey-badge', ['btsJourney' => $btsJourney ?? null, 'variant' => 'hero'])
                @if($isLMD || $lmdCredits)
                    <span class="hero-pill" style="background:rgba(16,185,129,.25); color:#6ee7b7; border-color:rgba(16,185,129,.4);"><i class="fas fa-graduation-cap" style="font-size:.65rem;"></i> LMD</span>
                @else
                    <span class="hero-pill" style="background:rgba(59,130,246,.25); color:#93c5fd; border-color:rgba(59,130,246,.4);"><i class="fas fa-graduation-cap" style="font-size:.65rem;"></i> BTS</span>
                @endif
                <span class="hero-pill"><i class="fas fa-id-card"></i> {{ $etudiant->matricule ?? 'Non attribué' }}</span>
                @if($estInscritCetteAnnee)
                    <span class="hero-pill green"><i class="fas fa-circle" style="font-size:.45rem"></i> Inscrit {{ $anneeCourante->name ?? '' }}</span>
                @elseif($inscFutureSousReserve)
                    <span class="hero-pill" style="background:rgba(245,158,11,.3); color:#fcd34d; border-color:rgba(245,158,11,.5);">
                        <i class="fas fa-clipboard-check" style="font-size:.6rem"></i> Pré-inscrit {{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }} (sous réserve)
                    </span>
                @else
                    <span class="hero-pill" style="background:rgba(255,255,255,0.15); color:#fff; border-color:rgba(255,255,255,0.4);">
                        <i class="fas fa-exclamation-circle" style="font-size:.7rem; color:#fbbf24;"></i> Non réinscrit {{ $anneeCourante ? $anneeCourante->name : '' }}
                    </span>
                @endif
                @if($inscNonValidee)
                    <span class="hero-pill" style="background:rgba(245,158,11,.3); color:#fcd34d; border-color:rgba(245,158,11,.5);">
                        <i class="fas fa-exclamation-triangle" style="font-size:.6rem"></i> Inscription non validée
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
                @can('students.edit')
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
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:250px;">
                        <li><h6 class="dropdown-header" style="font-size:.72rem; letter-spacing:.04em;">CERTIFICAT DE SCOLARITÉ</h6></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat.preview', $etudiant) }}" target="_blank">
                                <i class="fas fa-window-restore fa-fw me-2 text-muted"></i>Vue web (impression)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat.preview-pdf', $etudiant) }}" target="_blank">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Aperçu PDF
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
                                <i class="fas fa-window-restore fa-fw me-2 text-muted"></i>Vue web (impression)
                            </a>
                            @else
                            <a class="dropdown-item" href="#"
                               onclick="openAttestationConfirm(event, '{{ route('esbtp.etudiants.attestation-frequentation.preview', $etudiant) }}')">
                                <i class="fas fa-window-restore fa-fw me-2 text-muted"></i>Vue web (impression)
                            </a>
                            @endif
                        </li>
                        <li>
                            @if($estInscritCetteAnnee)
                            <a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation.preview-pdf', $etudiant) }}" target="_blank">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Aperçu PDF
                            </a>
                            @else
                            <a class="dropdown-item" href="#"
                               onclick="openAttestationConfirm(event, '{{ route('esbtp.etudiants.attestation-frequentation.preview-pdf', $etudiant) }}')">
                                <i class="fas fa-eye fa-fw me-2 text-muted"></i>Aperçu PDF
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
                @can('students.delete')
                <form id="es-form-delete-student" action="{{ route('esbtp.etudiants.destroy', $etudiant) }}" method="POST" style="margin:0">
                    @csrf @method('DELETE')
                    <button type="button" class="hero-btn danger"
                            data-ii-confirm-form="es-form-delete-student"
                            data-ii-confirm-title="Supprimer l'étudiant"
                            data-ii-confirm-message="Supprimer définitivement {{ $etudiant->nom_complet }} ? Cette action est irréversible et supprimera toutes les inscriptions, paiements, notes, bulletins, présences et documents liés."
                            data-ii-confirm-label="Supprimer"
                            data-ii-confirm-danger="1"
                            title="Supprimer l'étudiant">
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
        <button class="fiche-tab" data-tab="documents" role="tab">
            <i class="fas fa-folder-open"></i> Documents
            @if($etudiant->documents->count())
                <span class="tab-badge">{{ $etudiant->documents->count() }}</span>
            @endif
        </button>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     CONTENT
════════════════════════════════════════════════════════════════ --}}
<div class="fiche-content">

{{-- ─── TAB: VUE D'ENSEMBLE ─────────────────────────────────────── --}}
<div class="tab-panel active" id="tab-overview">
    @include('esbtp.partials.bts-journey', ['btsJourney' => $btsJourney ?? null])

    {{-- Bannière : étudiant non inscrit pour l'année courante (masquée si pré-inscrit sous réserve) --}}
    @if($anneeCourante && !$inscCourante && !$inscFutureSousReserve)
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

    {{-- Bannière : pré-inscription caissier à compléter --}}
    @if($isPreInscription && count($preInscriptionMissing) > 0)
    <div style="
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 1.5px solid #3b82f6;
        border-left: 5px solid #0453cb;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    ">
        <div style="flex-shrink:0; width:36px; height:36px; background:#0453cb; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-clipboard-list" style="color:#fff; font-size:.9rem;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:700; color:#1e3a5f; font-size:.95rem; margin-bottom:4px;">
                Pré-inscription — Informations à compléter
            </div>
            <div style="color:#1e40af; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
                Cette fiche a été créée par le caissier
                @if($preInscriptionCreator) <strong>{{ $preInscriptionCreator->name }}</strong> @endif
                le {{ $inscCourante->created_at->format('d/m/Y à H:i') }}.
                Veuillez compléter les informations manquantes avant de valider l'inscription.
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;">
                @foreach($preInscriptionMissing as $field)
                <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:rgba(4,83,203,.1); border:1px solid rgba(4,83,203,.2); border-radius:6px; font-size:.75rem; color:#0453cb; font-weight:600;">
                    <i class="fas fa-times-circle" style="font-size:.6rem;"></i> {{ $field }}
                </span>
                @endforeach
            </div>
            <a href="{{ route('esbtp.etudiants.edit', $etudiant->id) }}"
               style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #0453cb, #5e91de); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; text-decoration:none; cursor:pointer; box-shadow:0 2px 8px rgba(4,83,203,.25);">
                <i class="fas fa-edit"></i> Compléter les informations
            </a>
        </div>
    </div>
    @endif

    {{-- Bannière : inscription non validée (workflow incomplet) — masquée si pré-inscription incomplète --}}
    @if($inscNonValidee && (!$isPreInscription || count($preInscriptionMissing) === 0))
        @include('esbtp.partials.inscription-workflow-alert', [
            'inscriptionWorkflowAlert' => \App\Support\InscriptionWorkflowAlertPresenter::fromInscription($inscCourante, $anneeCourante),
            'redirectTo' => 'etudiant',
        ])
    @endif

    {{-- Bannière inscription future sous réserve (info) --}}
    @if($inscFutureSousReserve)
    <div style="background:linear-gradient(135deg,#dbeafe,#bfdbfe); border:1.5px solid #3b82f6; border-left:5px solid #0453cb; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:flex-start; gap:14px;">
        <div style="flex-shrink:0; width:36px; height:36px; background:#0453cb; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-clipboard-check" style="color:#fff; font-size:.9rem;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:700; color:#1e3a5f; font-size:.95rem; margin-bottom:4px;">
                Pré-inscrit pour {{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }}
            </div>
            <div style="color:#1e40af; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
                Cet étudiant est inscrit pour l'année <strong>{{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }}</strong>
                sous réserve de son <strong>{{ $inscFutureSousReserve->condition_reserve ?? 'diplôme' }}</strong>.
                L'inscription sera confirmée quand cette année deviendra l'année courante.
            </div>
            @if(Route::has('esbtp.inscriptions.sous-reserve'))
            <a href="{{ route('esbtp.inscriptions.sous-reserve') }}" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #0453cb, #5e91de); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; text-decoration:none; cursor:pointer; box-shadow:0 2px 8px rgba(4,83,203,.25);">
                <i class="fas fa-external-link-alt"></i> Gérer les réserves
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Bannière inscription future NON sous réserve (suggestion) --}}
    @if($inscFutureNonReserve && !$inscFutureSousReserve && Route::has('esbtp.inscriptions.marquer-sous-reserve'))
    <div style="background:linear-gradient(135deg,#fef3c7,#fde68a); border:1.5px solid #f59e0b; border-left:5px solid #d97706; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:flex-start; gap:14px;">
        <div style="flex-shrink:0; width:36px; height:36px; background:#d97706; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-exclamation-triangle" style="color:#fff; font-size:.9rem;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:700; color:#92400e; font-size:.95rem; margin-bottom:4px;">
                Inscription année future non marquée sous réserve
            </div>
            <div style="color:#78350f; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
                Cet étudiant a une inscription pour <strong>{{ $inscFutureNonReserve->anneeUniversitaire->name ?? '' }}</strong>
                qui n'est pas marquée sous réserve. Souhaitez-vous la marquer ?
            </div>
            @can('inscriptions.edit')
            <form method="POST" action="{{ route('esbtp.inscriptions.marquer-sous-reserve', $inscFutureNonReserve) }}" style="display:inline-flex; gap:8px; align-items:center;">
                @csrf
                <input type="text" name="condition_reserve" value="BACCALAURÉAT" class="form-control form-control-sm" style="width:180px; font-size:.82rem;" placeholder="Condition...">
                <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #d97706, #f59e0b); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(217,119,6,.3);">
                    <i class="fas fa-clipboard-check"></i> Marquer sous réserve
                </button>
            </form>
            @endcan
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

        // Inscription de référence : année courante, sinon future sous réserve
        $kpiInscActive = $inscCourante ?? ($inscFutureSousReserve ?? null);

        $kpiPaiTotal = $kpiInscActive
            ? $kpiInscActive->paiements->where('status', 'validé')->sum('montant')
            : null; // null = pas inscrit cette année
        // Total attendu via fraisSubscriptions (même logique que tab Finance L.3365)
        $kpiTotalAttendu = 0;
        if ($kpiInscActive) {
            try { $kpiTotalAttendu = $kpiInscActive->fraisSubscriptions->sum('amount'); } catch(\Exception $e) {}
        }
        $kpiPaiDu = max(0, $kpiTotalAttendu - ($kpiPaiTotal ?? 0));

        // Moyenne : LMD → depuis bulletinLMD, BTS → depuis ESBTPBulletin/ESBTPResultat
        if ($isLMD && $lmdMoyenneAnnuelle) {
            // ── LMD : moyenne annuelle pondérée (tous semestres) ──
            $kpiMoyenneGen    = $lmdMoyenneAnnuelle;
            $kpiMoyenneIsLive = false;
        } else if (!$isLMD) {
            if (($btsAnnualSnapshot['state'] ?? null) === 'annual_complete' && ($btsAnnualSnapshot['effective_total'] ?? null) !== null) {
                $kpiMoyenneGen = round((float) $btsAnnualSnapshot['effective_total'], 2);
                $kpiMoyenneIsLive = false;
            } elseif (($btsAnnualSnapshot['state'] ?? null) === 'annual_incomplete' && ($btsAnnualSnapshot['effective_total'] ?? null) !== null) {
                $kpiMoyenneGen = round((float) $btsAnnualSnapshot['effective_total'], 2);
                $kpiMoyenneIsLive = true;
            }
            // ── BTS : logique existante ──
            $kpiBulletins = $kpiInscActive
                ? \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
                    ->where('annee_universitaire_id', $kpiInscActive->annee_universitaire_id)
                    ->get()
                : collect();

            $kpiBulletinsCalcueles = $kpiBulletins->filter(fn($b) => $b->moyenne_generale !== null && $b->moyenne_generale > 0);
            if ($kpiMoyenneGen !== null) {
                // Le snapshot BTS phase-based est prioritaire quand il existe.
            } elseif ($kpiBulletinsCalcueles->count()) {
                $kpiMoyenneGen    = round($kpiBulletinsCalcueles->avg('moyenne_generale'), 2);
                $kpiMoyenneIsLive = false;
            } else {
                $kpiResultats = $kpiInscActive
                    ? \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                        ->where('annee_universitaire_id', $kpiInscActive->annee_universitaire_id)
                        ->whereNotNull('moyenne')
                        ->get()
                    : collect();

                if ($kpiResultats->count()) {
                    $_kpiPoidS1 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', 1));
                    $_kpiPoidS2 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', 1));
                    if ($_kpiPoidS1 + $_kpiPoidS2 <= 0) { $_kpiPoidS1 = 1; $_kpiPoidS2 = 1; }

                    $_kpiCalcSem = function($group) {
                        $sp = 0; $sc = 0;
                        foreach ($group as $_r) {
                            $c = $_r->coefficient ?? 1;
                            $sp += $_r->moyenne * $c;
                            $sc += $c;
                        }
                        return $sc > 0 ? $sp / $sc : null;
                    };

                    $_kpiS1 = $_kpiCalcSem($kpiResultats->where('periode', 'semestre1'));
                    $_kpiS2 = $_kpiCalcSem($kpiResultats->where('periode', 'semestre2'));

                    if ($_kpiS1 !== null && $_kpiS2 !== null) {
                        $_kpiTot = $_kpiPoidS1 + $_kpiPoidS2;
                        $kpiMoyenneGen = round(($_kpiS1 * $_kpiPoidS1 + $_kpiS2 * $_kpiPoidS2) / $_kpiTot, 2);
                    } elseif ($_kpiS1 !== null) {
                        $kpiMoyenneGen = round($_kpiS1, 2);
                    } elseif ($_kpiS2 !== null) {
                        $kpiMoyenneGen = round($_kpiS2, 2);
                    } else {
                        $kpiMoyenneGen = $_kpiCalcSem($kpiResultats) !== null ? round($_kpiCalcSem($kpiResultats), 2) : null;
                    }
                    $kpiMoyenneIsLive = true;
                }
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

        {{-- Absences / Crédits LMD (capitalisés à vie, même si pas inscrit cette année) --}}
        @if($lmdCredits)
            @php
                $credCap = $lmdCredits['capitalises'];
                $credTot = $lmdCredits['totaux'];
                $hasCredits = $credCap !== null && $credTot !== null;
                $credPct = $hasCredits && $credTot > 0 ? min(100, round($credCap / $credTot * 100)) : 0;
                $credColor = $hasCredits ? ($credPct >= 80 ? '#10b981' : ($credPct >= 50 ? '#f59e0b' : '#ef4444')) : '#94a3b8';
            @endphp
            <div class="kpi-card">
                <div class="kpi-ring">
                    <svg viewBox="0 0 52 52">
                        <circle class="ring-bg" cx="26" cy="26" r="22"/>
                        <circle class="ring-fg" cx="26" cy="26" r="22"
                            stroke="{{ $credColor }}"
                            stroke-dasharray="{{ round(2*3.14159*22,1) }}"
                            stroke-dashoffset="{{ round(2*3.14159*22 * (1 - $credPct/100),1) }}"/>
                    </svg>
                    <span class="ring-icon" style="color:{{ $credColor }}"><i class="fas fa-award" style="font-size:.75rem"></i></span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-val" style="color:{{ $credColor }}">{{ $hasCredits ? $credCap.'/'.$credTot : '—/—' }}</div>
                    <div class="kpi-lbl">Crédits CECT</div>
                </div>
            </div>
        @else
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
        @endif

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
            @if($parcours || $lmdCredits)
                @if($parcours && $parcours->mention && $parcours->mention->domaine)
                    <div class="info-row">
                        <span class="info-lbl">Domaine</span>
                        <span class="info-val">{{ $parcours->mention->domaine->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-lbl">Mention</span>
                        <span class="info-val">{{ $parcours->mention->name }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-lbl">Parcours</span>
                    @if($parcours && $parcours->name)
                        <span class="info-val" style="font-weight:600; color:#059669;">{{ $parcours->name }}</span>
                    @else
                        <span class="info-val" style="color:#94a3b8; font-style:italic; font-size:.82rem;"><i class="fas fa-unlink" style="font-size:.65rem; margin-right:4px;"></i>Classe non rattachée à un parcours</span>
                    @endif
                </div>
                <div class="info-row">
                    <span class="info-lbl">Crédits capitalisés</span>
                    <span class="info-val mono" style="font-weight:600; color:{{ $lmdCredits['capitalises'] !== null ? '#059669' : '#94a3b8' }};">{{ $lmdCredits['capitalises'] !== null ? $lmdCredits['capitalises'].' / '.$lmdCredits['totaux'] : '— / —' }} CECT</span>
                </div>
            @endif
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

                @if(!empty($insc->bts_journey_ui))
                    <div style="margin-bottom:.75rem;">
                        @include('esbtp.partials.bts-journey-badge', ['btsJourney' => $insc->bts_journey_ui])

                        @if(!empty($insc->bts_journey_ui['timeline']) && count($insc->bts_journey_ui['timeline']) > 1)
                            <div style="display:flex; flex-direction:column; gap:4px; margin-top:8px;">
                                @foreach($insc->bts_journey_ui['timeline'] as $phaseStep)
                                    @php
                                        $phaseSemestreDebut = $phaseStep['semestre_debut'] ?? null;
                                        $phaseSemestreFin = $phaseStep['semestre_fin'] ?? null;
                                        $phaseSemestreLabel = match (true) {
                                            empty($phaseSemestreDebut) => 'Semestre à définir',
                                            empty($phaseSemestreFin), (int) $phaseSemestreDebut === (int) $phaseSemestreFin => 'Semestre ' . $phaseSemestreDebut,
                                            default => 'Semestres ' . $phaseSemestreDebut . ' à ' . $phaseSemestreFin,
                                        };
                                    @endphp
                                    <div style="display:flex; align-items:center; gap:8px; font-size:.74rem; color:#475569; line-height:1.3;">
                                        <span style="width:7px; height:7px; border-radius:999px; flex-shrink:0; background:{{ !empty($phaseStep['is_active']) ? '#10b981' : '#94a3b8' }};"></span>
                                        <span>
                                            <strong style="color:#0f172a;">{{ $phaseStep['label'] }}</strong>
                                            @if(!empty($phaseStep['classe']))
                                                · {{ $phaseStep['classe'] }}
                                            @endif
                                            · {{ $phaseSemestreLabel }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @php
                    $inscIsLmd = ($insc->classe?->systeme_academique ?? '') === 'LMD';
                    $inscLmdParcours = $inscIsLmd && $insc->classe?->parcours
                        && $insc->classe->parcours->mention
                        && $insc->classe->parcours->mention->domaine
                        ? $insc->classe->parcours
                        : null;
                @endphp
                @if($inscLmdParcours)
                    {{-- Tree premium hiérarchique LMD compact (Domaine → Mention → Parcours → Classe) --}}
                    <div style="margin-bottom:.75rem;">
                        <x-lmd-hierarchy-tree :parcours="$inscLmdParcours" :classe="$insc->classe" compact />
                    </div>
                    <div class="insc-data-grid">
                        <div class="insc-data-row">
                            <span class="insc-data-lbl"><i class="fas fa-layer-group"></i> Niveau</span>
                            <span class="insc-data-val">{{ $insc->classe?->niveau?->name ?? '—' }}</span>
                        </div>
                        <div class="insc-data-row">
                            <span class="insc-data-lbl"><i class="fas fa-calendar-plus"></i> Date inscription</span>
                            <span class="insc-data-val">{{ $insc->created_at?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </div>
                @else
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
                @endif

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
        @if($anneeCourante && !$inscCourante && !$inscFutureSousReserve)
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

    /* Inscription de référence : la plus récente avec bulletins CALCULÉS (moyenne_generale > 0).
       FIX bug bulletin 179 : on filtre AUSSI par classe_id pour ne pas ramener les bulletins
       d'inscriptions précédentes ou d'autres classes de la même année. */
    $acadRef = null; $acadBuls = collect();
    foreach ($acadInscs as $_i) {
        $_b = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', optional($_i->anneeUniversitaire)->id)
            ->where('classe_id', $_i->classe_id)
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
        /* Si bulletins ont moyenne_generale=0/null, fallback aux résultats bruts avec pondération S1/S2 */
        if ($acadMg === null) {
            $acadAllNotesForKpi = $acadResultatsRef->whereNotNull('moyenne');
            if ($acadAllNotesForKpi->count()) {
                $_fbPoidS1 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', 1));
                $_fbPoidS2 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', 1));
                if ($_fbPoidS1 + $_fbPoidS2 <= 0) { $_fbPoidS1 = 1; $_fbPoidS2 = 1; }

                $_fbCalcSem = function($group) {
                    $sp = 0; $sc = 0;
                    foreach ($group as $_r) { $c = $_r->coefficient ?? 1; $sp += $_r->moyenne * $c; $sc += $c; }
                    return $sc > 0 ? $sp / $sc : null;
                };

                $_fbS1 = $_fbCalcSem($acadAllNotesForKpi->where('periode', 'semestre1'));
                $_fbS2 = $_fbCalcSem($acadAllNotesForKpi->where('periode', 'semestre2'));

                if ($_fbS1 !== null && $_fbS2 !== null) {
                    $_fbTot = $_fbPoidS1 + $_fbPoidS2;
                    $acadMg = round(($_fbS1 * $_fbPoidS1 + $_fbS2 * $_fbPoidS2) / $_fbTot, 2);
                } elseif ($_fbS1 !== null) {
                    $acadMg = round($_fbS1, 2);
                } elseif ($_fbS2 !== null) {
                    $acadMg = round($_fbS2, 2);
                } else {
                    $acadMg = $_fbCalcSem($acadAllNotesForKpi) !== null ? round($_fbCalcSem($acadAllNotesForKpi), 2) : null;
                }
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
        /* Cas sans bulletins : calculer moyenne pondérée depuis ESBTPResultat avec pondération S1/S2 */
        $acadAllNotesForKpi = $acadResultatsRef->whereNotNull('moyenne');
        if ($acadAllNotesForKpi->count()) {
            // Pondération S1/S2 depuis les settings (même logique que les bulletins)
            $_acadPoidS1 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', 1));
            $_acadPoidS2 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', 1));
            if ($_acadPoidS1 + $_acadPoidS2 <= 0) { $_acadPoidS1 = 1; $_acadPoidS2 = 1; }

            $_acadCalcSem = function($group) {
                $sp = 0; $sc = 0;
                foreach ($group as $_r) {
                    $c = $_r->coefficient ?? 1;
                    $sp += $_r->moyenne * $c;
                    $sc += $c;
                }
                return $sc > 0 ? $sp / $sc : null;
            };

            $_acadS1 = $_acadCalcSem($acadAllNotesForKpi->where('periode', 'semestre1'));
            $_acadS2 = $_acadCalcSem($acadAllNotesForKpi->where('periode', 'semestre2'));

            if ($_acadS1 !== null && $_acadS2 !== null) {
                $_acadTot = $_acadPoidS1 + $_acadPoidS2;
                $acadMg = round(($_acadS1 * $_acadPoidS1 + $_acadS2 * $_acadPoidS2) / $_acadTot, 2);
            } elseif ($_acadS1 !== null) {
                $acadMg = round($_acadS1, 2);
            } elseif ($_acadS2 !== null) {
                $acadMg = round($_acadS2, 2);
            } else {
                // Fallback toutes périodes confondues si periode non renseignée
                $acadMg = $_acadCalcSem($acadAllNotesForKpi) !== null ? round($_acadCalcSem($acadAllNotesForKpi), 2) : null;
            }

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

        /* Classement : calculer le rang parmi les co-inscrits de la même classe/année */
        $acadRang = null;
        if ($acadMg !== null && $acadRef) {
            // Récupérer tous les étudiants inscrits dans la même classe la même année
            $_classeId = $acadRef->classe_id;
            $_anneeId  = $acadRef->annee_universitaire_id;

            if ($_classeId && $_anneeId) {
                // Co-inscrits (même classe, même année, actifs, sauf cet étudiant)
                $_coInscrits = \App\Models\ESBTPInscription::where('classe_id', $_classeId)
                    ->where('annee_universitaire_id', $_anneeId)
                    ->pluck('etudiant_id')
                    ->unique();

                if ($_coInscrits->count() > 1) {
                    // Calculer la moyenne pondérée de chaque co-inscrit avec la même formule
                    $_moyennes = [];
                    $_resultatsClasse = \App\Models\ESBTPResultat::whereIn('etudiant_id', $_coInscrits)
                        ->where('annee_universitaire_id', $_anneeId)
                        ->whereNotNull('moyenne')
                        ->get()
                        ->groupBy('etudiant_id');

                    foreach ($_coInscrits as $_eid) {
                        $_rows = $_resultatsClasse->get($_eid, collect());
                        if ($_rows->isEmpty()) continue;

                        $_s1 = $_acadCalcSem($_rows->where('periode', 'semestre1'));
                        $_s2 = $_acadCalcSem($_rows->where('periode', 'semestre2'));

                        if ($_s1 !== null && $_s2 !== null) {
                            $_tot = $_acadPoidS1 + $_acadPoidS2;
                            $_moy = ($_s1 * $_acadPoidS1 + $_s2 * $_acadPoidS2) / $_tot;
                        } elseif ($_s1 !== null) {
                            $_moy = $_s1;
                        } elseif ($_s2 !== null) {
                            $_moy = $_s2;
                        } else {
                            $_moy = $_acadCalcSem($_rows);
                        }

                        if ($_moy !== null) {
                            $_moyennes[$_eid] = $_moy;
                        }
                    }

                    // Trier par moyenne décroissante et trouver la position de cet étudiant
                    arsort($_moyennes);
                    $_rang = 1;
                    foreach ($_moyennes as $_eid => $_moy) {
                        if ($_eid == $etudiant->id) {
                            $acadRang = $_rang . '/' . count($_moyennes);
                            break;
                        }
                        $_rang++;
                    }
                }
            }
        }
    }

    $acadAnnee  = optional($acadRef?->anneeUniversitaire)->name ?? '—';
    $acadClasse = optional($acadRef?->classe)->name ?? '—';

    /* RANG CANONIQUE : on délègue à RankingService pour cohérence avec /esbtp/resultats.
       Cohort : status='active' + workflow_step='etudiant_cree', source snapshot live,
       flag bulletin_show_attendance_note respecté, ex-aequo gérés.
       Si l'étudiant n'est pas dans la cohort canonique → on garde l'ad-hoc / bulletin snapshot. */
    if ($acadRef && $acadRef->classe_id && $acadRef->annee_universitaire_id) {
        try {
            $_rk = app(\App\Services\RankingService::class)->calculerRangPourEtudiant(
                (int) $etudiant->id,
                (int) $acadRef->classe_id,
                (int) $acadRef->annee_universitaire_id,
                'annuel'
            );
            if ($_rk['rang'] !== null && $_rk['total'] > 0) {
                $acadRang = $_rk['rang'] . '/' . $_rk['total'];
            }
        } catch (\Throwable $e) {
            // Silencieux : on garde le rang calculé en amont (bulletin snapshot ou ad-hoc)
        }
    }

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

    {{-- ══ BLOC TPE — Travail Personnel Etudiant attendu (lecture seule UEMOA) ══ --}}
    @if(($isLMD ?? false) && ($tpeAttendu ?? 0) > 0)
        @php
            $tpeFmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',') ?: '0';
            $tpeSemestres = collect($tpeParSemestre ?? [])->filter(fn ($v) => $v > 0)->sortKeys();
        @endphp
        <div class="et-tpe-card">
            <div class="et-tpe-header">
                <div class="et-tpe-icon"><i class="fas fa-user-clock"></i></div>
                <div class="et-tpe-body">
                    <div class="et-tpe-label">TPE attendu cette année</div>
                    <div class="et-tpe-value">{{ $tpeFmt($tpeAttendu) }}h</div>
                    <div class="et-tpe-hint">Travail personnel étudiant — heures autonomes hors séances (standards UEMOA).</div>
                </div>
                @if($tpeSemestres->count() > 1)
                    <div class="et-tpe-breakdown">
                        @foreach($tpeSemestres as $sem => $heures)
                            <div class="et-tpe-sem">
                                <span class="et-tpe-sem-label">S{{ $sem }}</span>
                                <span class="et-tpe-sem-value">{{ $tpeFmt($heures) }}h</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══ BLOC LMD — Résultats par UE/ECUE avec onglets semestres ══ --}}
    @if($isLMD && $bulletinsLMD->count())
        <div class="fin-hero" style="margin-bottom:16px;">
            <div class="fin-hero-year-badge">
                <i class="fas fa-calendar-check"></i>
                Année en cours : <strong>{{ $acadAnnee }}</strong>
                @if($acadClasse) &middot; {{ $acadClasse }} @endif
                @if($bulletinsLMD->count() === 1)
                    &middot; <span style="color:#6ee7b7;">Semestre {{ $bulletinsLMD->first()->semestre }}</span>
                @else
                    &middot; <span style="color:#6ee7b7;">{{ $bulletinsLMD->count() }} semestres</span>
                @endif
            </div>
        </div>

        {{-- Onglets semestres si plusieurs --}}
        @if($bulletinsLMD->count() > 1)
        <div style="display:flex; gap:6px; margin-bottom:12px;">
            @foreach($bulletinsLMD as $idx => $bul)
                <button class="lmd-sem-tab {{ $idx === $bulletinsLMD->count() - 1 ? 'active' : '' }}"
                        onclick="switchLmdSem({{ $bul->semestre }})"
                        data-sem="{{ $bul->semestre }}"
                        style="flex:1; padding:8px 12px; border:2px solid {{ $idx === $bulletinsLMD->count() - 1 ? '#059669' : '#e2e8f0' }}; background:{{ $idx === $bulletinsLMD->count() - 1 ? '#ecfdf5' : '#fff' }}; border-radius:10px; font-size:.82rem; font-weight:600; color:{{ $idx === $bulletinsLMD->count() - 1 ? '#059669' : '#64748b' }}; cursor:pointer; transition:all .2s;">
                    <i class="fas fa-book-open" style="margin-right:4px; font-size:.7rem;"></i>
                    S{{ $bul->semestre }}
                    @if($bul->moyenne_generale) — {{ number_format($bul->moyenne_generale, 2) }}/20 @endif
                </button>
            @endforeach
        </div>
        @endif

        {{-- Contenu de chaque semestre --}}
        @foreach($bulletinsLMD as $idx => $_bul)
        <div class="lmd-sem-panel" data-sem="{{ $_bul->semestre }}" style="{{ $idx !== $bulletinsLMD->count() - 1 ? 'display:none;' : '' }}">
        <div class="acad-hero">
            <div class="acad-hero-top">
                <div>
                    <div class="acad-hero-label"><i class="fas fa-graduation-cap" style="margin-right:5px;"></i>Bilan LMD</div>
                    <div class="acad-hero-title">{{ $acadAnnee }}</div>
                    <div class="acad-hero-subtitle">{{ $acadClasse }} · S{{ $_bul->semestre }}</div>
                </div>
            </div>
            <div class="acad-kpi-row">
                {{-- Moyenne --}}
                @php
                    $lmdMg = $_bul->moyenne_generale;
                    $lmdCirc = 2 * 3.14159 * 35;
                    $lmdMgPct = $lmdMg ? min(100, round($lmdMg / 20 * 100)) : 0;
                    $lmdMgOff = $lmdCirc - ($lmdMgPct / 100 * $lmdCirc);
                    $lmdMgStroke = !$lmdMg ? '#64748b' : ($lmdMg >= 14 ? '#10b981' : ($lmdMg >= 12 ? '#34d399' : ($lmdMg >= 10 ? '#f59e0b' : '#ef4444')));
                @endphp
                <div class="acad-kpi-block">
                    <div class="acad-kpi-label">Moyenne</div>
                    <div class="acad-ring-wrap">
                        <svg viewBox="0 0 80 80">
                            <circle class="acad-ring-bg" cx="40" cy="40" r="35"/>
                            <circle class="acad-ring-fg" cx="40" cy="40" r="35" stroke="{{ $lmdMgStroke }}" stroke-dasharray="{{ $lmdCirc }}" stroke-dashoffset="{{ $lmdMg ? $lmdMgOff : $lmdCirc }}"/>
                        </svg>
                        <div class="acad-ring-center">
                            <span class="acad-ring-val" style="color:{{ $lmdMgStroke }};">{{ $lmdMg ? number_format($lmdMg, 2) : '—' }}</span>
                            <span class="acad-ring-sub">/20</span>
                        </div>
                    </div>
                    @if($lmdMg) <div class="acad-kpi-mention">{{ $_bul->mention_generale }}</div> @endif
                </div>

                {{-- Crédits --}}
                @php
                    $lmdCred = $_bul->credits_capitalises ?? 0;
                    $lmdCredTot = $_bul->credits_totaux ?? 30;
                    $lmdCredPct = $lmdCredTot > 0 ? min(100, round($lmdCred / $lmdCredTot * 100)) : 0;
                    $lmdCredOff = $lmdCirc - ($lmdCredPct / 100 * $lmdCirc);
                    $lmdCredStroke = $lmdCredPct >= 80 ? '#10b981' : ($lmdCredPct >= 50 ? '#f59e0b' : '#ef4444');
                @endphp
                <div class="acad-kpi-block">
                    <div class="acad-kpi-label">Crédits</div>
                    <div class="acad-ring-wrap">
                        <svg viewBox="0 0 80 80">
                            <circle class="acad-ring-bg" cx="40" cy="40" r="35"/>
                            <circle class="acad-ring-fg" cx="40" cy="40" r="35" stroke="{{ $lmdCredStroke }}" stroke-dasharray="{{ $lmdCirc }}" stroke-dashoffset="{{ $lmdCredOff }}"/>
                        </svg>
                        <div class="acad-ring-center">
                            <span class="acad-ring-val" style="color:{{ $lmdCredStroke }};">{{ $lmdCred }}/{{ $lmdCredTot }}</span>
                            <span class="acad-ring-sub">CECT</span>
                        </div>
                    </div>
                    <div class="acad-kpi-mention" style="font-size:.72rem; color:rgba(255,255,255,.6);">{{ $lmdCredPct }}% capitalisés</div>
                </div>

                {{-- Classement --}}
                <div class="acad-kpi-block">
                    <div class="acad-kpi-label">Classement</div>
                    <div class="acad-ring-wrap">
                        <svg viewBox="0 0 80 80"><circle class="acad-ring-bg" cx="40" cy="40" r="35"/></svg>
                        <div class="acad-ring-center">
                            @if($_bul->rang)
                                <span class="acad-ring-val">{{ $_bul->rang }}<sup style="font-size:.5em;">e</sup></span>
                                <span class="acad-ring-sub">/ {{ $_bul->effectif ?? '—' }}</span>
                            @else
                                <span class="acad-ring-val" style="color:#64748b;">—</span>
                                <span class="acad-ring-sub">Non classé</span>
                            @endif
                        </div>
                    </div>
                    @if($_bul->decision_deliberation)
                        <div class="acad-kpi-mention" style="font-size:.72rem;">{{ $_bul->decision_deliberation }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tableau UE --}}
        @if($_bul->resultatsUEs && $_bul->resultatsUEs->count())
        <div class="s-card" style="margin-top:16px;">
            <div class="s-card-header">
                <div class="s-card-title">
                    <div class="s-card-title-icon"><i class="fas fa-layer-group"></i></div>
                    Résultats par Unité d'Enseignement — S{{ $_bul->semestre }}
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-modern" style="width:100%; font-size:.84rem;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:.6rem .75rem;">UE / ECUE</th>
                            <th style="text-align:center; width:80px;">Moyenne</th>
                            <th style="text-align:center; width:80px;">Crédits</th>
                            <th style="text-align:center; width:80px;">Statut</th>
                            <th style="text-align:center; width:120px;">Stats promo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($_bul->resultatsUEs->sortBy(fn($r) => $r->uniteEnseignement?->code ?? '') as $resUE)
                        @php
                            $ue = $resUE->uniteEnseignement;
                            $statutColor = match($resUE->statut) { 'AQ' => '#10b981', 'APC' => '#f59e0b', default => '#ef4444' };
                            $statutLabel = match($resUE->statut) { 'AQ' => 'Acquis', 'APC' => 'Compensé', 'NAQ' => 'Non acquis', default => $resUE->statut ?? '—' };
                        @endphp
                        <tr style="background:#f0fdf4; font-weight:600;">
                            <td style="padding:.55rem .75rem;">
                                <i class="fas fa-folder-open" style="color:#059669; font-size:.7rem; margin-right:.35rem;"></i>
                                {{ $ue?->code ?? '' }} — {{ $ue?->name ?? 'UE inconnue' }}
                            </td>
                            <td style="text-align:center; font-weight:700; color:{{ $resUE->moyenne >= 10 ? '#059669' : '#ef4444' }};">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '—' }}</td>
                            <td style="text-align:center; font-weight:700;">{{ $resUE->credit ?? '—' }}</td>
                            <td style="text-align:center;"><span style="display:inline-block; padding:2px 8px; border-radius:6px; font-size:.72rem; font-weight:600; color:#fff; background:{{ $statutColor }};">{{ $statutLabel }}</span></td>
                            <td style="text-align:center; font-size:.75rem; color:#64748b;">@if($resUE->stat_min !== null){{ number_format($resUE->stat_min, 1) }} / {{ number_format($resUE->stat_moy, 1) }} / {{ number_format($resUE->stat_max, 1) }}@else — @endif</td>
                        </tr>
                        @foreach($_bul->resultatsECUEs->where('resultat_ue_id', $resUE->id)->sortBy(fn($e) => $e->matiere?->code ?? '') as $resECUE)
                            <tr style="font-size:.8rem;">
                                <td style="padding:.4rem .75rem .4rem 2.5rem; color:#475569;"><i class="fas fa-file-alt" style="font-size:.6rem; color:#94a3b8; margin-right:.3rem;"></i>{{ $resECUE->matiere?->code ?? '' }} — {{ $resECUE->matiere?->name ?? '—' }}</td>
                                <td style="text-align:center; color:{{ ($resECUE->moyenne ?? 0) >= 10 ? '#059669' : '#ef4444' }}; font-weight:600;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '—' }}</td>
                                <td style="text-align:center;">{{ $resECUE->credit ?? '—' }}</td>
                                <td style="text-align:center; font-size:.72rem; color:#94a3b8;">—</td>
                                <td style="text-align:center; font-size:.72rem; color:#64748b;">@if($resECUE->stat_min !== null){{ number_format($resECUE->stat_min, 1) }} / {{ number_format($resECUE->stat_moy, 1) }} / {{ number_format($resECUE->stat_max, 1) }}@else — @endif</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="s-card" style="margin-top:16px; text-align:center; padding:2rem; color:#94a3b8;">
                <i class="fas fa-info-circle" style="font-size:1.5rem; margin-bottom:.5rem; display:block;"></i>
                Aucun résultat UE enregistré pour S{{ $_bul->semestre }}.
            </div>
        @endif
        </div>{{-- /lmd-sem-panel --}}
        @endforeach

    @elseif($isLMD && $bulletinsLMD->isEmpty())
        {{-- LMD mais pas encore de bulletin --}}
        <div class="fin-hero" style="margin-bottom:16px;">
            <div class="fin-hero-year-badge">
                <i class="fas fa-calendar-check"></i>
                Année en cours : <strong>{{ $acadAnnee }}</strong>
                @if($acadClasse) &middot; {{ $acadClasse }} @endif
            </div>
        </div>
        <div class="s-card" style="text-align:center; padding:2rem; color:#94a3b8;">
            <i class="fas fa-graduation-cap" style="font-size:2rem; margin-bottom:.75rem; display:block; color:#059669;"></i>
            <div style="font-weight:600; color:#334155; margin-bottom:.25rem;">Étudiant LMD</div>
            <div style="font-size:.84rem;">Aucun bulletin LMD n'a encore été généré pour cette année. Les résultats apparaîtront ici après la génération du bulletin.</div>
            @if($parcours)
                <div style="margin-top:.75rem; font-size:.8rem; color:#059669;">
                    <i class="fas fa-sitemap" style="margin-right:.25rem;"></i>
                    {{ $parcours->mention?->domaine?->name ?? '' }} › {{ $parcours->mention?->name ?? '' }} › {{ $parcours->name }}
                </div>
            @endif
        </div>
    @else

    {{-- ══ BLOC ANNÉE EN COURS (toujours plat, jamais collapsible) ══ --}}
    @if($acadIsNotCurrentYear && ($inscFutureSousReserve ?? null))
        {{-- Pré-inscrit sous réserve pour une année future --}}
        <div class="fin-hero" style="margin-bottom:16px;">
            <div class="fin-hero-year-badge" style="background:rgba(59,130,246,.12); border-color:rgba(59,130,246,.3);">
                <i class="fas fa-clipboard-check" style="color:#0453cb;"></i>
                <span style="color:#1e40af;">
                    Pré-inscrit <strong>{{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }}</strong>
                    sous réserve de son {{ $inscFutureSousReserve->condition_reserve ?? 'diplôme' }}
                    @if($inscFutureSousReserve->classe) &middot; {{ $inscFutureSousReserve->classe->name }} @endif
                </span>
            </div>
            <p style="margin:12px 0 0; font-size:.82rem; color:var(--k-muted); text-align:center;">
                <i class="fas fa-info-circle" style="margin-right:5px;"></i>Résultats académiques non encore disponibles pour cette année. Consultez les années précédentes ci-dessous.
            </p>
        </div>
    @elseif($acadIsNotCurrentYear)
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
            @php
                /* Bouton header : prioriser l'Annuel si dispo, sinon S1+S2 → pdf-params-preview annuel,
                   sinon le dernier semestre disponible. */
                $_acadHeroBulAnnuel = $acadBuls->firstWhere('periode', 'annuel');
                $_acadHeroBulS1 = $acadBuls->firstWhere('periode', 'semestre1');
                $_acadHeroBulS2 = $acadBuls->firstWhere('periode', 'semestre2');
                $_acadHeroLabel = 'Bulletin PDF';
                $_acadHeroPreviewUrl = null;
                $_acadHeroDownloadUrl = null;
                if ($_acadHeroBulAnnuel) {
                    $_acadHeroLabel = 'Bulletin Annuel';
                    $_acadHeroPreviewUrl = route('esbtp.bulletins.preview-pdf', $_acadHeroBulAnnuel);
                    $_acadHeroDownloadUrl = route('esbtp.bulletins.download', $_acadHeroBulAnnuel);
                } elseif ($_acadHeroBulS1 && $_acadHeroBulS2 && $acadRef) {
                    $_acadHeroLabel = 'Bulletin Annuel (live)';
                    $_acadHeroPreviewUrl = route('esbtp.bulletins.pdf-params-preview', [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $acadRef->classe_id,
                        'annee_universitaire_id' => $acadRef->annee_universitaire_id,
                        'periode' => 'annuel',
                    ]);
                    $_acadHeroDownloadUrl = route('esbtp.bulletins.pdf-params', [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $acadRef->classe_id,
                        'annee_universitaire_id' => $acadRef->annee_universitaire_id,
                        'periode' => 'annuel',
                    ]);
                } elseif ($_acadHeroBulS2) {
                    $_acadHeroLabel = 'Bulletin S2';
                    $_acadHeroPreviewUrl = route('esbtp.bulletins.preview-pdf', $_acadHeroBulS2);
                    $_acadHeroDownloadUrl = route('esbtp.bulletins.download', $_acadHeroBulS2);
                } elseif ($_acadHeroBulS1) {
                    $_acadHeroLabel = 'Bulletin S1';
                    $_acadHeroPreviewUrl = route('esbtp.bulletins.preview-pdf', $_acadHeroBulS1);
                    $_acadHeroDownloadUrl = route('esbtp.bulletins.download', $_acadHeroBulS1);
                } elseif ($acadLastBul) {
                    $_acadHeroPreviewUrl = route('esbtp.bulletins.preview-pdf', $acadLastBul);
                    $_acadHeroDownloadUrl = route('esbtp.bulletins.download', $acadLastBul);
                }
            @endphp
            @if($_acadHeroPreviewUrl)
            <span class="acad-hero-pdf-actions">
                <a href="{{ $_acadHeroPreviewUrl }}" class="acad-hero-pdf-btn acad-hero-pdf-btn--ghost" target="_blank" title="Aperçu PDF dans un nouvel onglet">
                    <i class="fas fa-eye"></i> Aperçu
                </a>
                <a href="{{ $_acadHeroDownloadUrl }}" class="acad-hero-pdf-btn" target="_blank" title="Télécharger {{ $_acadHeroLabel }}">
                    <i class="fas fa-file-pdf"></i> {{ $_acadHeroLabel }}
                </a>
            </span>
            @endif
        </div>

        <div class="acad-kpi-row">
            {{-- KPI 1 : Moyenne générale --}}
            @php
                $mgPct = $acadMg !== null ? min(100, round($acadMg / 20 * 100)) : 0;
                $circumference = 2 * 3.14159 * 35; // r=35
                $mgOffset = $circumference - ($mgPct / 100 * $circumference);
                $mgStroke = $acadMg === null ? '#64748b' : ($acadMg >= 14 ? '#10b981' : ($acadMg >= 12 ? '#34d399' : ($acadMg >= 10 ? '#f59e0b' : '#ef4444')));
            @endphp
            <div class="acad-kpi-block">
                <div class="acad-kpi-label">Moyenne</div>
                <div class="acad-ring-wrap">
                    <svg viewBox="0 0 80 80">
                        <circle class="acad-ring-bg" cx="40" cy="40" r="35"/>
                        <circle class="acad-ring-fg"
                            cx="40" cy="40" r="35"
                            stroke="{{ $mgStroke }}"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $acadMg !== null ? $mgOffset : $circumference }}"/>
                    </svg>
                    <div class="acad-ring-center">
                        <span class="acad-ring-val" style="color:{{ $mgStroke }};">
                            {{ $acadMg !== null ? number_format($acadMg, 2) : '—' }}
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
                <span class="acad-spark-val" style="color:{{ $barColor }};">{{ number_format($moy, 2) }}</span>
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
                    <a href="{{ route('esbtp.bulletins.preview-pdf', $bul) }}" class="acad-arch-pdf-link acad-arch-pdf-link--ghost" target="_blank" onclick="event.stopPropagation()" title="Aperçu PDF">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('esbtp.bulletins.download', $bul) }}" class="acad-arch-pdf-link" target="_blank" onclick="event.stopPropagation()" title="Télécharger PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <i class="fas fa-chevron-down acad-sem-chevron"></i>
                </div>
            </div>
            <div class="collapse {{ $loop->first ? 'show' : '' }}" id="{{ $semKey }}">
                @php
                    /* P-B : note d'assiduité par semestre, respectant le flag bulletin_show_attendance_note */
                    $_bulSvc = app(\App\Services\BulletinService::class);
                    $_assidEnabled = $_bulSvc->isAttendanceNoteEnabled();
                    $_semPeriode = strtolower($bul->periode ?? '');
                    $_assidPeriode = $_semPeriode === 'annuel' ? 'annuel' : ($_semPeriode === 'semestre2' ? 'semestre2' : 'semestre1');
                    $_noteAssid = null;
                    $_absJ = null; $_absNJ = null;
                    if ($_assidEnabled && $acadRef && $acadRef->classe_id && $acadRef->annee_universitaire_id) {
                        try {
                            $_noteAssid = $_bulSvc->calculateEffectiveAttendanceNoteForStudent(
                                (int) $etudiant->id,
                                (int) $acadRef->classe_id,
                                (int) $acadRef->annee_universitaire_id,
                                $_assidPeriode
                            );
                            $_anneeRef = $acadRef->anneeUniversitaire;
                            $_absDetail = app(\App\Services\ESBTP\ESBTPAbsenceService::class)->calculerDetailAbsences(
                                (int) $etudiant->id,
                                (int) $acadRef->classe_id,
                                $_anneeRef->date_debut ?? null,
                                $_anneeRef->date_fin ?? null,
                                (int) $acadRef->annee_universitaire_id,
                                $_assidPeriode
                            );
                            $_absJ = (int) ($_absDetail['justifiees'] ?? 0);
                            $_absNJ = (int) ($_absDetail['non_justifiees'] ?? 0);
                        } catch (\Throwable $e) {
                            $_noteAssid = null;
                        }
                    }
                @endphp
                @if($_assidEnabled && $_noteAssid !== null)
                    @php
                        $_assidColor = $_noteAssid > 0 ? '#10b981' : ($_noteAssid < 0 ? '#dc2626' : '#64748b');
                        $_assidIcon = $_noteAssid > 0 ? 'fa-arrow-up' : ($_noteAssid < 0 ? 'fa-arrow-down' : 'fa-equals');
                        $_assidSign = $_noteAssid > 0 ? '+' : '';
                    @endphp
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.6rem .85rem; background:rgba(4,83,203,.04); border:1px solid rgba(4,83,203,.10); border-radius:8px; margin:.5rem 0 .65rem;">
                        <div style="display:flex; align-items:center; gap:.55rem; font-size:.78rem; color:#334155;">
                            <i class="fas fa-user-clock" style="color:#0453cb;"></i>
                            <span style="font-weight:600;">Note d'assiduité</span>
                            <span style="color:#64748b;">·</span>
                            <span style="color:#64748b;">{{ $_absJ }} justifiée{{ $_absJ > 1 ? 's' : '' }}, {{ $_absNJ }} non justifiée{{ $_absNJ > 1 ? 's' : '' }}</span>
                        </div>
                        <span style="display:inline-flex; align-items:center; gap:.3rem; font-size:.8rem; font-weight:700; color:{{ $_assidColor }};">
                            <i class="fas {{ $_assidIcon }}" style="font-size:.65rem;"></i>
                            {{ $_assidSign }}{{ number_format($_noteAssid, 2) }} pt
                        </span>
                    </div>
                @endif
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
                    @elseif(strtolower($bul->periode ?? '') === 'annuel')
                        @php
                            /* P-C : Annuel n'est pas une période isolée mais la fusion S1+S2.
                               On affiche la formule de pondération + moyennes S1/S2/Annuelle. */
                            $_pondS1 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', 1));
                            $_pondS2 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', 1));
                            if ($_pondS1 + $_pondS2 <= 0) { $_pondS1 = 1; $_pondS2 = 1; }
                            $_bulS1 = $acadBuls->firstWhere('periode', 'semestre1');
                            $_bulS2 = $acadBuls->firstWhere('periode', 'semestre2');
                            $_mgS1 = $_bulS1 && $_bulS1->moyenne_generale > 0 ? (float) $_bulS1->moyenne_generale : null;
                            $_mgS2 = $_bulS2 && $_bulS2->moyenne_generale > 0 ? (float) $_bulS2->moyenne_generale : null;
                        @endphp
                        <div style="padding:12px 14px;">
                            <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:700; margin-bottom:.55rem;">
                                <i class="fas fa-balance-scale me-1" style="color:#0453cb;"></i>
                                Pondération annuelle
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.6rem;">
                                <div style="padding:.55rem .7rem; background:rgba(4,83,203,.05); border:1px solid rgba(4,83,203,.10); border-radius:8px;">
                                    <div style="font-size:.68rem; color:#64748b; font-weight:600;">Semestre 1</div>
                                    <div style="display:flex; align-items:baseline; gap:.4rem; margin-top:.15rem;">
                                        <span style="font-size:1.05rem; font-weight:700; color:#0453cb;">{{ $_mgS1 !== null ? number_format($_mgS1, 2) : '—' }}</span>
                                        <span style="font-size:.7rem; color:#64748b;">/ 20</span>
                                        <span style="margin-left:auto; font-size:.7rem; color:#94a3b8;">× {{ number_format($_pondS1, 2) }}</span>
                                    </div>
                                </div>
                                <div style="padding:.55rem .7rem; background:rgba(4,83,203,.05); border:1px solid rgba(4,83,203,.10); border-radius:8px;">
                                    <div style="font-size:.68rem; color:#64748b; font-weight:600;">Semestre 2</div>
                                    <div style="display:flex; align-items:baseline; gap:.4rem; margin-top:.15rem;">
                                        <span style="font-size:1.05rem; font-weight:700; color:#0453cb;">{{ $_mgS2 !== null ? number_format($_mgS2, 2) : '—' }}</span>
                                        <span style="font-size:.7rem; color:#64748b;">/ 20</span>
                                        <span style="margin-left:auto; font-size:.7rem; color:#94a3b8;">× {{ number_format($_pondS2, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($mg !== null && ($_mgS1 !== null || $_mgS2 !== null))
                                <div style="margin-top:.7rem; padding:.55rem .8rem; background:linear-gradient(135deg, rgba(4,83,203,.08), rgba(59,125,219,.10)); border:1px solid rgba(4,83,203,.20); border-radius:8px; display:flex; align-items:center; justify-content:space-between;">
                                    <span style="font-size:.78rem; color:#1e293b; font-weight:600;">
                                        <i class="fas fa-equals" style="font-size:.65rem; color:#0453cb; margin-right:.3rem;"></i>
                                        Moyenne annuelle pondérée
                                    </span>
                                    <span style="font-size:1.05rem; font-weight:700; color:#0453cb;">{{ number_format($mg, 2) }}<span style="font-size:.65em; color:#64748b; font-weight:500;">/20</span></span>
                                </div>
                            @endif
                            @php
                                /* P-E : fusion matières annuel (opt-in setting). Affiche les matières
                                   avec moyenne pondérée S1×w1 + S2×w2, badge si présent dans un seul semestre. */
                                $_fusionEnabled = \App\Helpers\SettingsHelper::get('bulletin_annuel_fusion_matieres', '0') === '1';
                                $_fusedRows = collect();
                                if ($_fusionEnabled && $acadRef) {
                                    $_anneeIdFuse = (int) $acadRef->annee_universitaire_id;
                                    $_rowsS1 = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                                        ->where('annee_universitaire_id', $_anneeIdFuse)
                                        ->where('periode', 'semestre1')
                                        ->with('matiere')->get()->keyBy('matiere_id');
                                    $_rowsS2 = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                                        ->where('annee_universitaire_id', $_anneeIdFuse)
                                        ->where('periode', 'semestre2')
                                        ->with('matiere')->get()->keyBy('matiere_id');
                                    $_matieresIds = $_rowsS1->keys()->merge($_rowsS2->keys())->unique();
                                    $_tot = $_pondS1 + $_pondS2;
                                    foreach ($_matieresIds as $_mid) {
                                        $_r1 = $_rowsS1->get($_mid);
                                        $_r2 = $_rowsS2->get($_mid);
                                        $_n1 = $_r1 ? $_r1->moyenne : null;
                                        $_n2 = $_r2 ? $_r2->moyenne : null;
                                        $_matiere = ($_r1 ?? $_r2)->matiere ?? null;
                                        $_coef = optional($_matiere)->coefficient ?? optional($_matiere)->coeff ?? null;
                                        $_only = null;
                                        if ($_n1 !== null && $_n2 !== null) {
                                            $_avg = ($_n1 * $_pondS1 + $_n2 * $_pondS2) / $_tot;
                                        } elseif ($_n1 !== null) {
                                            $_avg = $_n1; $_only = 'S1';
                                        } elseif ($_n2 !== null) {
                                            $_avg = $_n2; $_only = 'S2';
                                        } else {
                                            continue;
                                        }
                                        $_fusedRows->push([
                                            'name' => optional($_matiere)->name ?? '—',
                                            'coef' => $_coef,
                                            'avg' => $_avg,
                                            'only' => $_only,
                                        ]);
                                    }
                                    $_fusedRows = $_fusedRows->sortBy('name')->values();
                                }
                            @endphp
                            @if($_fusionEnabled && $_fusedRows->count())
                                <div style="margin-top:.85rem; padding-top:.65rem; border-top:1px dashed rgba(4,83,203,.18);">
                                    <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:700; margin-bottom:.45rem;">
                                        <i class="fas fa-layer-group me-1" style="color:#0453cb;"></i>
                                        Matières fusionnées (S1 + S2 pondérées)
                                    </div>
                                    <div class="acad-mat-list">
                                        @foreach($_fusedRows as $_fr)
                                            @php
                                                $_fcl = $_fr['avg'] >= 12 ? 'good' : ($_fr['avg'] >= 10 ? 'mid' : 'bad');
                                                $_fco = $_fr['avg'] >= 12 ? '#10b981' : ($_fr['avg'] >= 10 ? '#f59e0b' : '#ef4444');
                                                $_fpct = min(100, round($_fr['avg'] / 20 * 100));
                                            @endphp
                                            <div class="acad-mat-item">
                                                <div class="acad-mat-top">
                                                    <span class="acad-mat-name">{{ $_fr['name'] }}</span>
                                                    @if($_fr['coef']) <span class="acad-mat-coeff-pill">Coef. {{ $_fr['coef'] }}</span> @endif
                                                    @if($_fr['only'])
                                                        <span style="font-size:.62rem; padding:.1rem .35rem; background:rgba(245,158,11,.10); color:#b45309; border:1px solid rgba(245,158,11,.25); border-radius:5px; font-weight:700;">{{ $_fr['only'] }} seulement</span>
                                                    @endif
                                                    <span class="acad-mat-score {{ $_fcl }}">
                                                        {{ number_format($_fr['avg'], 2) }}<span style="font-size:.65em; font-weight:500; opacity:.6;">/20</span>
                                                    </span>
                                                </div>
                                                <div class="acad-mat-bar-wrap">
                                                    <div class="acad-mat-bar" style="width:{{ $_fpct }}%; background:{{ $_fco }};"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div style="margin-top:.55rem; font-size:.7rem; color:#94a3b8; text-align:center; line-height:1.5;">
                                <i class="fas fa-info-circle"></i>
                                Le détail des matières est consultable dans S1 et S2 ci-dessus.
                            </div>
                        </div>
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
    @endif {{-- fin @else isLMD --}}

    {{-- ══ AUTRES ANNÉES ══════════════════════════════════════ --}}
    @php
        /* Si l'année courante n'a pas de données, toutes les inscriptions vont ici
           mais on exclut l'inscription courante (sans données) et la future sous réserve (affichée dans le hero) */
        $acadExcludeIds = collect([$anneeCourante?->id])->filter();
        if ($inscFutureSousReserve ?? null) {
            $acadExcludeIds->push($inscFutureSousReserve->annee_universitaire_id);
        }
        $acadInscsPrec = $acadIsNotCurrentYear
            ? $acadInscs->filter(fn($i) => !$acadExcludeIds->contains($i->annee_universitaire_id))
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
        $autreIsLMD = optional($autreInsc->classe)->systeme_academique === 'LMD';

        // Charger les bulletins selon le système académique
        if ($autreIsLMD) {
            $autreBulsLMD = \App\Models\ESBTPLMDBulletin::where('etudiant_id', $etudiant->id)
                ->where('classe_id', optional($autreInsc->classe)->id)
                ->where('annee_universitaire_id', $autreInsc->annee_universitaire_id)
                ->with(['resultatsUEs.uniteEnseignement', 'resultatsECUEs.matiere'])
                ->orderBy('semestre')->get();
            $autreBuls = collect(); // pas de BTS bulletins
        } else {
            $autreBulsLMD = collect();
            $autreBuls = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', optional($autreInsc->anneeUniversitaire)->id)
                ->orderBy('periode')->get();
        }
        $autreMention = $autreIsLMD
            ? ($autreBulsLMD->last()?->mention_generale ?? null)
            : ($autreBuls->last()?->mention ?? null);
        $autreAnneeLabel = optional($autreInsc->anneeUniversitaire)->name ?? 'Année N/A';
        $autreClasseLabel = optional($autreInsc->classe)->name ?? '';
        $autreArchKey = 'acad-arch-' . $autreInsc->id;

        /* Calcul moyenne générale selon le système */
        if ($autreIsLMD) {
            // LMD : moyenne pondérée des bulletins LMD par crédits
            $autreBulsValides = $autreBulsLMD->filter(fn($b) => ($b->moyenne_generale ?? 0) > 0);
            if ($autreBulsValides->count() > 1) {
                $atc = $autreBulsValides->sum('credits_totaux');
                $autreMg = $atc > 0
                    ? round($autreBulsValides->sum(fn($b) => $b->moyenne_generale * $b->credits_totaux) / $atc, 2)
                    : round($autreBulsValides->avg('moyenne_generale'), 2);
            } elseif ($autreBulsValides->count() === 1) {
                $autreMg = round($autreBulsValides->first()->moyenne_generale, 2);
            } else {
                $autreMg = null;
            }
            $autreResultatsBruts = collect(); // pas de résultats BTS bruts pour LMD
        } else {
            // BTS : logique existante
            $autreResultatsBruts = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', optional($autreInsc->anneeUniversitaire)->id)
                ->with(['matiere'])->get();

            $autreBulsValides = $autreBuls->filter(fn($b) => ($b->moyenne_generale ?? 0) > 0);
        }

        if (!$autreIsLMD && $autreBulsValides->count()) {
            $autreMg = round($autreBulsValides->avg('moyenne_generale'), 2);
        } elseif ($autreResultatsBruts->whereNotNull('moyenne')->count()) {
            /* Pondération S1/S2 depuis settings (cohérence avec bulletins) */
            $_aPoidS1 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', 1));
            $_aPoidS2 = max(0, (float) \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', 1));
            if ($_aPoidS1 + $_aPoidS2 <= 0) { $_aPoidS1 = 1; $_aPoidS2 = 1; }

            $_aCalcSem = function($group) {
                $_sp = 0; $_sc = 0;
                foreach ($group as $_r) { $_c = $_r->coefficient ?? $_r->matiere?->coefficient ?? 1; $_sp += $_r->moyenne * $_c; $_sc += $_c; }
                return $_sc > 0 ? $_sp / $_sc : null;
            };
            $_aS1 = $_aCalcSem($autreResultatsBruts->whereNotNull('moyenne')->where('periode', 'semestre1'));
            $_aS2 = $_aCalcSem($autreResultatsBruts->whereNotNull('moyenne')->where('periode', 'semestre2'));

            if ($_aS1 !== null && $_aS2 !== null) {
                $autreMg = round(($_aS1 * $_aPoidS1 + $_aS2 * $_aPoidS2) / ($_aPoidS1 + $_aPoidS2), 2);
            } elseif ($_aS1 !== null) {
                $autreMg = round($_aS1, 2);
            } elseif ($_aS2 !== null) {
                $autreMg = round($_aS2, 2);
            } else {
                $autreMg = ($_r2 = $_aCalcSem($autreResultatsBruts->whereNotNull('moyenne'))) !== null ? round($_r2, 2) : null;
            }
        } else {
            $autreMg = null;
        }

        $autreMgColor = $autreMg === null ? 'var(--k-muted)' : ($autreMg >= 12 ? 'var(--k-success)' : ($autreMg >= 10 ? '#d97706' : 'var(--k-danger)'));

        /* Résultats groupés par période pour affichage dans le body */
        $autreResultats = (!$autreIsLMD && !$autreBuls->count())
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
                @if($autreIsLMD && $autreBulsLMD->count())
                    {{-- Rendu LMD pour années précédentes --}}
                    @foreach($autreBulsLMD as $abLmd)
                    <div class="acad-arch-sem-row">
                        <span class="acad-arch-sem-name">
                            <span style="display:inline-block; padding:1px 6px; border-radius:4px; font-size:.65rem; font-weight:600; color:#fff; background:#059669; margin-right:4px;">LMD</span>
                            Semestre {{ $abLmd->semestre }}
                        </span>
                        <div class="acad-arch-sem-info">
                            @if($abLmd->rang)
                                <span style="font-size:.75rem; color:var(--k-muted);"><i class="fas fa-trophy" style="color:#f59e0b; font-size:.65rem;"></i> {{ $abLmd->rang }}</span>
                            @endif
                            @if($abLmd->moyenne_generale)
                                @php $abLmdMgC = $abLmd->moyenne_generale >= 12 ? 'var(--k-success)' : ($abLmd->moyenne_generale >= 10 ? '#d97706' : 'var(--k-danger)'); @endphp
                                <span style="font-size:.8rem; font-weight:700; color:{{ $abLmdMgC }};">{{ number_format($abLmd->moyenne_generale, 2) }}/20</span>
                            @endif
                            <span style="font-size:.72rem; color:var(--k-muted);">{{ $abLmd->credits_capitalises ?? 0 }}/{{ $abLmd->credits_totaux ?? 30 }} CECT</span>
                        </div>
                    </div>
                    @endforeach
                @elseif($autreBuls->count())
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
                            @php
                                $_abClasseId = $autreInsc->classe_id;
                                $_abAnneeId = optional($autreInsc->anneeUniversitaire)->id;
                            @endphp
                            @php $_abPdfParams = ['bulletin' => $ab->id, 'classe_id' => $_abClasseId, 'periode' => $ab->periode, 'annee_universitaire_id' => $_abAnneeId]; @endphp
                            <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_abPdfParams) }}"
                               class="acad-arch-pdf-link acad-arch-pdf-link--ghost" target="_blank" title="Aperçu PDF">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('esbtp.bulletins.pdf-params', $_abPdfParams) }}"
                               class="acad-arch-pdf-link" target="_blank" title="Télécharger le bulletin PDF">
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
        $hasManualHoursCur = false;
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

            // Précharger la présence de saisie manuelle pour cette année :
            // ça évite d'afficher le message "Aucune séance enregistrée"
            // alors que la card manual-hours juste en-dessous contient des
            // données — la séparation visuelle paraissait incohérente.
            $hasManualHoursCur = \App\Models\ESBTPAttendanceManualHours::query()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneePresId)
                ->exists();
        }

        // Skip totalement le dark hero quand l'étudiant n'a QUE des heures
        // manuelles pour cette année (ni séances, ni absence d'inscription) —
        // la section manuelle ci-dessous porte toute la donnée et un dark
        // hero vide créait une bande inutile.
        $skipFinHeroPresCur = $presInscCourante
            && ($totalCur ?? 0) === 0
            && $hasManualHoursCur;
    @endphp
    @if($skipFinHeroPresCur)
        {{-- Cas "heures manuelles seules" : même pattern que l'onglet
             Finances — un unique dark hero qui contient le badge année,
             les KPIs et le graphe ; le détail/accordion reste dans une
             card claire en-dessous. --}}
        <div class="fin-hero fin-hero--pres" style="margin-bottom:16px;">
            <div class="fin-hero-year-badge">
                <i class="fas fa-calendar-check"></i>
                Année en cours&nbsp;:
                <strong>{{ $presAnneeCourante->name }}</strong>
                @if($presInscCourante->classe)
                    &middot; {{ $presInscCourante->classe->name }}
                @endif
            </div>
            @include('esbtp.etudiants.partials.presences-manual-hours', [
                'etudiantId' => $etudiant->id,
                'anneeId' => $presAnneeCourante->id,
                'isCurrentYear' => true,
                'embedded' => true,
            ])
        </div>
    @else
    <div class="fin-hero" style="margin-bottom:16px;">
        @if($presInscCourante)
        <div class="fin-hero-year-badge">
            <i class="fas fa-calendar-check"></i>
            Année en cours :
            <strong>{{ $presAnneeCourante->name }}</strong>
            @if($presInscCourante->classe)
                &middot; {{ $presInscCourante->classe->name }}
            @endif
        </div>
        @elseif($inscFutureSousReserve ?? null)
        <div class="fin-hero-year-badge" style="background:rgba(59,130,246,.12); border-color:rgba(59,130,246,.3);">
            <i class="fas fa-clipboard-check" style="color:#0453cb;"></i>
            <span style="color:#1e40af;">
                Pré-inscrit <strong>{{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }}</strong>
                sous réserve de son {{ $inscFutureSousReserve->condition_reserve ?? 'diplôme' }}
                @if($inscFutureSousReserve->classe) &middot; {{ $inscFutureSousReserve->classe->name }} @endif
            </span>
        </div>
        @else
        <div class="fin-hero-year-badge" style="background:rgba(239,68,68,.15); border-color:rgba(239,68,68,.3);">
            <i class="fas fa-calendar-times" style="color:#ef4444;"></i>
            <span style="color:#ef4444;">Aucune inscription pour {{ $presAnneeCourante->name }}</span>
        </div>
        @endif

        @if(!$presInscCourante)
            {{-- Pas d'inscription courante — message déjà affiché dans le hero badge ci-dessus --}}
        @elseif(($totalCur ?? 0) === 0)
            {{-- Cas "pas de séances, pas de manual" : empty state classique.
                 Le cas "pas de séances MAIS manual existe" ne rentre pas ici :
                 on a sauté le fin-hero entier via `$skipFinHeroPresCur`. --}}
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
    @endif {{-- /!$skipFinHeroPresCur --}}

    {{-- Section "Saisie manuelle" année courante — seulement si on N'A PAS
         déjà rendu la version embedded dans le dark hero ci-dessus
         ($skipFinHeroPresCur). --}}
    @if($presInscCourante && !$skipFinHeroPresCur)
        @include('esbtp.etudiants.partials.presences-manual-hours', [
            'etudiantId' => $etudiant->id,
            'anneeId' => $presAnneeCourante->id,
            'isCurrentYear' => true,
        ])
    @endif
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

            // Pré-vérification saisie manuelle pour cette année précédente :
            // évite l'affichage du message "Aucune séance enregistrée" en
            // doublon avec la card mh-card quand seules des heures manuelles
            // existent (même logique que l'année courante).
            $hasManualHoursPres = $anneePresId
                ? \App\Models\ESBTPAttendanceManualHours::query()
                    ->where('etudiant_id', $etudiant->id)
                    ->where('annee_universitaire_id', $anneePresId)
                    ->exists()
                : false;
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
                {{-- Saisie manuelle : mode compact pour les accordéons N-1 --}}
                @if($anneePresId)
                    @include('esbtp.etudiants.partials.presences-manual-hours', [
                        'etudiantId' => $etudiant->id,
                        'anneeId' => $anneePresId,
                        'isCurrentYear' => false,
                        'anneeLabel' => $anneePresLabel,
                    ])
                @endif
            </div>
            @else
            <div class="presence-year-body">
                @if(!$hasManualHoursPres)
                    {{-- Ni séances ni heures manuelles — on garde l'empty state. --}}
                    <div style="text-align:center; padding:28px 16px; color:var(--k-muted);">
                        <i class="fas fa-calendar-times" style="font-size:2rem; opacity:.25; display:block; margin-bottom:12px;"></i>
                        <p style="font-size:.84rem; margin:0; font-weight:500;">Aucune séance de présence enregistrée pour cette année.</p>
                    </div>
                @endif
                @if($anneePresId)
                    @include('esbtp.etudiants.partials.presences-manual-hours', [
                        'etudiantId' => $etudiant->id,
                        'anneeId' => $anneePresId,
                        'isCurrentYear' => false,
                        'anneeLabel' => $anneePresLabel,
                    ])
                @endif
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

        /* ── Fallback : inscription future sous réserve comme référence ── */
        $finUseFutureFallback = !$finInscActive && ($inscFutureSousReserve ?? null);
        $finInscRef = $finInscActive ?? ($finUseFutureFallback ? $inscFutureSousReserve : null);

        /* ── Autres inscriptions (exclure l'inscription de référence) ── */
        $finAutresInscs = $etudiant->inscriptions->filter(fn($i) => !$finInscRef || $i->id !== $finInscRef->id)->sortByDesc('created_at');

        /* ── Collect paiements de l'inscription de référence ── */
        $finPaiementsActive = collect();
        if($finInscRef) {
            foreach($finInscRef->paiements ?? [] as $pai) {
                $pai->_annee = $finInscRef->anneeUniversitaire?->name ?? 'N/A';
                $finPaiementsActive->push($pai);
            }
        }
        $finPaiementsActive = $finPaiementsActive->sortByDesc('date_paiement');

        /* ── Calculs pour inscription de référence ── */
        $finTotalPaye    = $finPaiementsActive->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'valid'))->sum('montant');
        $finEnAttente    = $finPaiementsActive->filter(fn($p) => str_contains(strtolower($p->status ?? $p->statut ?? ''), 'attente'))->sum('montant');
        $finNbPaiements  = $finPaiementsActive->count();
        $finReliquats    = $statistiques['total_reliquats_entrants'] ?? 0;

        $finTotalAttendu = 0;
        if($finInscRef) {
            try { $finTotalAttendu = $finInscRef->fraisSubscriptions->sum('amount'); } catch(\Exception $e) {}
        }

        // Reliquats entrants pour l'inscription de référence
        $finReliquatsActifs = isset($reliquatsEntrants) ? $reliquatsEntrants->filter(fn($r) =>
            $finInscRef && $r->inscription_destination_id == $finInscRef->id
        ) : collect();
        $finTotalReliquat = $finReliquatsActifs->sum('montant_reliquat');
        $finReliquatPaye  = $finReliquatsActifs->sum('montant_regle');
        $finReliquatSolde = $finReliquatsActifs->sum('solde_restant');

        // Total global = frais inscrits + reliquats
        $finTotalAttenduGlobal = $finTotalAttendu + $finTotalReliquat;
        $finTotalPayeGlobal    = $finTotalPaye + $finReliquatPaye;
        $finSolde  = $finTotalAttenduGlobal - $finTotalPayeGlobal;
        $finTaux   = $finTotalAttenduGlobal > 0 ? min(100, round($finTotalPayeGlobal / $finTotalAttenduGlobal * 100)) : 0;
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
        @elseif($finUseFutureFallback)
        <div class="fin-hero-year-badge" style="background:rgba(59,130,246,.12); border-color:rgba(59,130,246,.3);">
            <i class="fas fa-clipboard-check" style="color:#0453cb;"></i>
            <span style="color:#1e40af;">
                Pré-inscrit <strong>{{ $inscFutureSousReserve->anneeUniversitaire->name ?? '' }}</strong>
                sous réserve de son {{ $inscFutureSousReserve->condition_reserve ?? 'diplôme' }}
                @if($inscFutureSousReserve->classe) &middot; {{ $inscFutureSousReserve->classe->name }} @endif
            </span>
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
                    {{ number_format($finTotalAttenduGlobal, 0, ',', ' ') }}
                    <span class="fin-kpi-currency">FCFA</span>
                </div>
                <div class="fin-kpi-sub">
                    {{ $finNbPaiements }} transaction(s)
                    @if($finTotalReliquat > 0)
                        · <span style="color:#f59e0b;">Reliquat {{ number_format($finTotalReliquat, 0, ',', ' ') }}</span>
                    @endif
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
                    {{ number_format($finTotalPayeGlobal, 0, ',', ' ') }}
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
                    $finAttenteW = $finTotalAttenduGlobal > 0 ? min(100, round(($finTotalPayeGlobal + $finEnAttente) / $finTotalAttenduGlobal * 100)) : 0;
                @endphp
                @if($finEnAttente > 0 && $finTotalAttendu > 0)
                <div class="fin-progress-segment attente" style="width:{{ $finAttenteW }}%"></div>
                @endif
                {{-- Segment validé --}}
                <div class="fin-progress-segment valide" style="width:{{ $finTaux }}%"></div>
            </div>
            <div class="fin-progress-legend">
                <span><span class="fin-legend-dot valide"></span>Validé ({{ number_format($finTotalPayeGlobal, 0, ',', ' ') }} FCFA)</span>
                @if($finEnAttente > 0)<span><span class="fin-legend-dot attente"></span>En attente ({{ number_format($finEnAttente, 0, ',', ' ') }} FCFA)</span>@endif
                @if($finReliquats > 0)<span><span class="fin-legend-dot reliquat"></span>Reliquat ({{ number_format($finReliquats, 0, ',', ' ') }} FCFA)</span>@endif
            </div>
        </div>

        {{-- Boutons actions financières (inscription courante ou future sous réserve) --}}
        <div style="text-align:center; margin-top:16px; display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            @if($finInscRef)
            <a href="{{ route('esbtp.inscriptions.situation-financiere.preview', $finInscRef->id) }}"
               class="hero-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-size:.88rem; border-radius:10px; background:linear-gradient(135deg, #059669, #10b981); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 4px 12px rgba(5,150,105,.3); text-decoration:none;">
                <i class="fas fa-chart-line"></i> Situation Financière
            </a>
            <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $finInscRef->id) }}"
               class="hero-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-size:.88rem; border-radius:10px; background:linear-gradient(135deg, #dc2626, #ef4444); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 4px 12px rgba(220,38,38,.3); text-decoration:none;">
                <i class="fas fa-file-pdf"></i> PDF Situation
            </a>
            @endif
            @if($finInscRef && $finSolde > 0)
            @can('paiements.create')
            <button class="hero-btn primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-size:.88rem; border-radius:10px; background:linear-gradient(135deg, var(--k-blue), var(--k-blue-2)); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 4px 12px rgba(4,83,203,.3);"
                    data-bs-toggle="modal" data-bs-target="#etudiantPaymentModal"
                    onclick="prepareEtudiantPaymentModal({{ $finInscRef->id }})">
                <i class="fas fa-plus-circle"></i> Enregistrer un paiement
            </button>
            @endcan
            @endif
        </div>
    </div>

    {{-- ── BANDEAU PAIEMENTS EN ATTENTE ── --}}
    @php
        $finPaiementsEnAttente = $finPaiementsActive->filter(fn($p) =>
            str_contains(strtolower($p->status ?? $p->statut ?? ''), 'attente')
        );
    @endphp
    @if($finPaiementsEnAttente->count() > 0)
    <div style="
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border: 1.5px solid #fbbf24;
        border-left: 5px solid #f59e0b;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    ">
        <div style="flex-shrink:0; width:38px; height:38px; background:#f59e0b; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-hourglass-half" style="color:#fff; font-size:.85rem;"></i>
        </div>
        <div style="flex:1; min-width:180px;">
            <div style="font-weight:700; color:#92400e; font-size:.9rem;">
                {{ $finPaiementsEnAttente->count() }} paiement(s) en attente de validation
            </div>
            <div style="font-size:.82rem; color:#a16207; margin-top:2px;">
                Total : {{ number_format($finPaiementsEnAttente->sum('montant'), 0, ',', ' ') }} FCFA
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('esbtp.paiements.index') }}?search={{ urlencode($etudiant->nom . ' ' . $etudiant->prenoms) }}&status=en_attente"
               style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:#f59e0b; color:#fff; border-radius:8px; font-size:.8rem; font-weight:600; text-decoration:none; white-space:nowrap;">
                <i class="fas fa-clock"></i> Voir les paiements en attente
            </a>
            <a href="{{ route('esbtp.paiements.index') }}?search={{ urlencode($etudiant->nom . ' ' . $etudiant->prenoms) }}"
               style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:rgba(245,158,11,.15); color:#92400e; border:1.5px solid #fbbf24; border-radius:8px; font-size:.8rem; font-weight:600; text-decoration:none; white-space:nowrap;">
                <i class="fas fa-list"></i> Tous les paiements
            </a>
        </div>
    </div>
    @endif

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
                        <th style="text-align:center;">Action</th>
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
                        <td style="text-align:center;">
                            @if($subSolde > 0)
                            @can('paiements.create')
                            <button class="pmt-act-btn view" style="background:rgba(4,83,203,.08); color:var(--k-blue); border:none; border-radius:6px; padding:5px 10px; cursor:pointer; font-size:.78rem; font-weight:600;"
                                    data-bs-toggle="modal" data-bs-target="#etudiantPaymentModal"
                                    onclick="prepareEtudiantPaymentModalForCategory({{ $finInscActive->id }}, {{ $sub->frais_category_id }})">
                                <i class="fas fa-coins"></i> Payer
                            </button>
                            @endcan
                            @else
                            <span style="font-size:.75rem; color:#10b981; font-weight:600;"><i class="fas fa-check-circle"></i> Soldé</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                {{-- ── Lignes reliquats ── --}}
                @foreach($finReliquatsActifs as $reliquat)
                    @php
                        $relSolde = $reliquat->solde_restant ?? 0;
                        $relTaux  = $reliquat->montant_reliquat > 0 ? min(100, round(($reliquat->montant_regle ?? 0) / $reliquat->montant_reliquat * 100)) : 0;
                        $relAnnee = $reliquat->inscriptionSource?->anneeUniversitaire?->name ?? '—';
                        $relCatName = $reliquat->fraisSubscription?->fraisCategory?->name ?? 'Reliquat';
                    @endphp
                    <tr style="background:#fffbeb;">
                        <td>
                            <span class="fin-cat-name">{{ $relCatName }}</span>
                            <span style="display:inline-block; margin-left:6px; padding:1px 6px; border-radius:4px; font-size:.68rem; font-weight:600; color:#92400e; background:#fef3c7;">Reliquat {{ $relAnnee }}</span>
                        </td>
                        <td style="text-align:right; font-weight:600; color:var(--k-text);">{{ number_format($reliquat->montant_reliquat ?? 0, 0, ',', ' ') }}</td>
                        <td style="text-align:right; font-weight:700; color:#10b981;">{{ number_format($reliquat->montant_regle ?? 0, 0, ',', ' ') }}</td>
                        <td style="text-align:right; font-weight:700; color:{{ $relSolde > 0 ? '#f59e0b' : '#10b981' }};">
                            {{ $relSolde > 0 ? number_format($relSolde, 0, ',', ' ') : '✓' }}
                        </td>
                        <td style="text-align:center; min-width:100px;">
                            <div class="fin-mini-track">
                                <div class="fin-mini-fill" style="width:{{ $relTaux }}%; background:{{ $relTaux >= 100 ? '#10b981' : '#f59e0b' }};"></div>
                            </div>
                            <span style="font-size:.72rem; color:var(--k-muted);">{{ $relTaux }}%</span>
                        </td>
                        <td style="text-align:center;">
                            @if($relSolde > 0)
                                <span style="font-size:.72rem; color:#f59e0b; font-weight:600;"><i class="fas fa-clock"></i> En cours</span>
                            @else
                                <span style="font-size:.75rem; color:#10b981; font-weight:600;"><i class="fas fa-check-circle"></i> Soldé</span>
                            @endif
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

                {{-- Boutons actions financières (autres années) --}}
                <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid rgba(0,0,0,.06);">
                    <a href="{{ route('esbtp.inscriptions.situation-financiere.preview', $autreInsc->id) }}"
                       style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:.8rem; border-radius:8px; background:linear-gradient(135deg, #059669, #10b981); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 2px 8px rgba(5,150,105,.25); text-decoration:none;">
                        <i class="fas fa-chart-line"></i> Situation Financière
                    </a>
                    <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $autreInsc->id) }}"
                       style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:.8rem; border-radius:8px; background:linear-gradient(135deg, #dc2626, #ef4444); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 2px 8px rgba(220,38,38,.25); text-decoration:none;">
                        <i class="fas fa-file-pdf"></i> PDF Situation
                    </a>
                    @can('paiements.create')
                    <button {{ $autreSolde <= 0 ? 'disabled' : '' }}
                            style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:.8rem; border-radius:8px; background:linear-gradient(135deg, var(--k-blue, #0453cb), var(--k-blue-2, #5e91de)); color:#fff; border:none; cursor:pointer; font-weight:600; box-shadow:0 2px 8px rgba(4,83,203,.25);{{ $autreSolde <= 0 ? ' opacity:.5; cursor:not-allowed;' : '' }}"
                            @if($autreSolde > 0) data-bs-toggle="modal" data-bs-target="#etudiantPaymentModal" onclick="prepareEtudiantPaymentModal({{ $autreInsc->id }})" @endif>
                        <i class="fas fa-{{ $autreSolde <= 0 ? 'check-circle' : 'plus-circle' }}"></i>
                        {{ $autreSolde <= 0 ? 'Soldé' : 'Enregistrer un paiement' }}
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif

</div>{{-- /tab-finances --}}

{{-- ─── TAB: PROFIL ─────────────────────────────────────────────── --}}
<div class="tab-panel" id="tab-profil">

    {{-- Parcours LMD (visible même si pas inscrit cette année — crédits capitalisés à vie).
         Refondu en tree premium bleu KLASSCI (rule premium-redesign monochrome bleu).
         Avant : couleurs vertes #059669 hardcodées (violation palette). --}}
    @if($parcours)
    <div class="s-card" style="margin-bottom:16px;">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon" style="background:rgba(4,83,203,.08); color:#0453cb;"><i class="fas fa-sitemap"></i></div>
                Parcours académique LMD
            </div>
            <span style="display:inline-flex;align-items:center;gap:.3rem;background:rgba(4,83,203,.12);color:#0453cb;border:1px solid rgba(4,83,203,.25);padding:.2rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;letter-spacing:.4px;">
                <i class="fas fa-university"></i>LMD
            </span>
        </div>
        <div style="padding:0 16px 14px;">
            <x-lmd-hierarchy-tree :parcours="$parcours" />
        </div>
        <div class="info-grid">
            @if($parcours->filiere)
                <div class="info-row"><span class="info-lbl">Filière équivalente</span><span class="info-val">{{ $parcours->filiere->name }}</span></div>
            @endif
            @if($lmdCredits)
                <div class="info-row"><span class="info-lbl">Crédits capitalisés</span><span class="info-val mono" style="font-weight:600; color:#0453cb;">{{ $lmdCredits['capitalises'] }} / {{ $lmdCredits['totaux'] }} CECT</span></div>
                @if(count($lmdCredits['semestres']) === 2)
                    <div class="info-row"><span class="info-lbl">Semestres en cours</span><span class="info-val">S{{ $lmdCredits['semestres'][0] }} — S{{ $lmdCredits['semestres'][1] }}</span></div>
                @endif
            @endif
            @if($parcours->responsable)
                <div class="info-row"><span class="info-lbl">Responsable parcours</span><span class="info-val">{{ $parcours->responsable->name ?? '—' }}</span></div>
            @endif
        </div>
    </div>
    @endif

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
            <div class="info-row"><span class="info-lbl">Ville de résidence</span><span class="info-val">{{ $etudiant->ville ?? '—' }}</span></div>
            <div class="info-row"><span class="info-lbl">Commune de résidence</span><span class="info-val">{{ $etudiant->commune ?? '—' }}</span></div>
            @if($etudiant->adresse)
            <div class="info-row"><span class="info-lbl">Adresse complète</span><span class="info-val">{{ $etudiant->adresse }}</span></div>
            @endif
            @if($etudiant->statut === 'abandon')
            <div class="info-row"><span class="info-lbl">Date abandon</span><span class="info-val">{{ $etudiant->date_abandon ? \Carbon\Carbon::parse($etudiant->date_abandon)->format('d/m/Y') : '—' }}</span></div>
            <div class="info-row"><span class="info-lbl">Motif abandon</span><span class="info-val">{{ $etudiant->motif_abandon ?? '—' }}</span></div>
            @endif
        </div>
    </div>

    {{-- Compte utilisateur --}}
    @if($etudiant->user)
    <div class="s-card">
        <div class="s-card-header" style="display:flex; align-items:center; justify-content:space-between;">
            <div class="s-card-title">
                <div class="s-card-title-icon"><i class="fas fa-user-shield"></i></div>
                Compte utilisateur
            </div>
            @php
                $accountActive = $etudiant->user->is_active ?? true;
            @endphp
            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600;
                background:{{ $accountActive ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)' }};
                color:{{ $accountActive ? '#059669' : '#dc2626' }};
                border:1px solid {{ $accountActive ? 'rgba(16,185,129,.25)' : 'rgba(239,68,68,.25)' }};">
                <i class="fas fa-circle" style="font-size:.35rem;"></i>
                {{ $accountActive ? 'Actif' : 'Inactif' }}
            </span>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">Nom d'utilisateur</span>
                <span class="info-val" style="font-weight:600; font-family:monospace; background:rgba(0,0,0,.04); padding:2px 8px; border-radius:4px;">{{ $etudiant->user->username ?? $etudiant->user->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Email connexion</span>
                <span class="info-val"><a href="mailto:{{ $etudiant->user->email }}" style="color:#0453cb; text-decoration:none;">{{ $etudiant->user->email }}</a></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Dernière connexion</span>
                <span class="info-val">{{ $etudiant->user->last_login_at ? \Carbon\Carbon::parse($etudiant->user->last_login_at)->diffForHumans() : '— Jamais connecté' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Compte créé</span>
                <span class="info-val">{{ $etudiant->user->created_at?->format('d/m/Y à H:i') ?? '—' }}</span>
            </div>
        </div>
        @can('students.edit')
        <div style="padding:12px 16px 4px; border-top:1px solid rgba(0,0,0,.06);">
            <a href="{{ route('esbtp.etudiants.reset-password', $etudiant->id) }}"
               onclick="return confirm('Réinitialiser le mot de passe de cet étudiant ? Le nouveau mot de passe sera : Bonjour@2025')"
               style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color:#fff; border:none; border-radius:8px; font-size:.8rem; font-weight:600; text-decoration:none; cursor:pointer; box-shadow:0 2px 6px rgba(4,83,203,.25);">
                <i class="fas fa-key" style="font-size:.7rem;"></i> Réinitialiser le mot de passe
            </a>
        </div>
        @endcan
    </div>
    @endif

    {{-- Accessibilité --}}
    @can('students.accessibility.view')
    @php $accProfile = $etudiant->accessibilityProfile; @endphp
    @if($accProfile)
    <div class="s-card">
        <div class="s-card-header">
            <div class="s-card-title">
                <div class="s-card-title-icon" style="background: linear-gradient(135deg,#0453cb,#5e91de);"><i class="fas fa-universal-access"></i></div>
                Accessibilité &amp; aménagements
            </div>
            @can('students.accessibility.edit')
                <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}#accessibility-section" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                    <i class="fas fa-edit me-1"></i>Modifier
                </a>
            @endcan
        </div>
        <div style="padding: 18px 24px;">
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                @if($accProfile->has_official_recognition)
                    <span class="status-badge-success" style="font-size:.78rem;padding:5px 12px;border-radius:50px;">
                        <i class="fas fa-stamp me-1"></i>Reconnaissance officielle
                    </span>
                @endif
                @if($accProfile->requires_third_time)
                    <span style="background:rgba(4,83,203,.1);color:#0453cb;padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;">
                        <i class="fas fa-hourglass-half me-1"></i>Tiers-temps {{ $accProfile->third_time_percentage }}%
                    </span>
                @endif
                @if($accProfile->assistant_required)
                    <span style="background:rgba(4,83,203,.1);color:#0453cb;padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;">
                        <i class="fas fa-hands-helping me-1"></i>Assistant requis
                    </span>
                @endif
                @if(! $accProfile->isCurrentlyEffective())
                    <span style="background:#fef3c7;color:#78350f;padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;">
                        <i class="fas fa-exclamation-triangle me-1"></i>Hors période de validité
                    </span>
                @endif
            </div>

            @if($accProfile->short_description)
                <div class="info-row" style="margin-bottom:10px;">
                    <span class="info-lbl">Résumé</span>
                    <span class="info-val">{{ $accProfile->short_description }}</span>
                </div>
            @endif

            @if(! empty($accProfile->categoryLabels()))
                <div class="info-row" style="margin-bottom:10px;">
                    <span class="info-lbl">Catégories</span>
                    <span class="info-val">
                        @foreach($accProfile->categoryLabels() as $catLabel)
                            <span style="display:inline-block;background:#eff6ff;color:#0453cb;padding:3px 10px;border-radius:50px;font-size:.78rem;margin-right:4px;margin-bottom:3px;">{{ $catLabel }}</span>
                        @endforeach
                    </span>
                </div>
            @endif

            @if(! empty($accProfile->accommodationLabels()))
                <div class="info-row" style="margin-bottom:10px;">
                    <span class="info-lbl">Aménagements</span>
                    <span class="info-val">
                        @foreach($accProfile->accommodationLabels() as $accLabel)
                            <span style="display:inline-block;background:#f0fdf4;color:#065f46;padding:3px 10px;border-radius:50px;font-size:.78rem;margin-right:4px;margin-bottom:3px;">
                                <i class="fas fa-check me-1"></i>{{ $accLabel }}
                            </span>
                        @endforeach
                    </span>
                </div>
            @endif

            @can('students.accessibility.view_full')
                @if($accProfile->full_description)
                    <div class="info-row" style="margin-bottom:10px;">
                        <span class="info-lbl">Description médicale <i class="fas fa-lock" style="font-size:.7rem;color:#64748b;"></i></span>
                        <span class="info-val" style="white-space:pre-wrap;">{{ $accProfile->full_description }}</span>
                    </div>
                @endif
                @if($accProfile->accommodations_notes)
                    <div class="info-row" style="margin-bottom:10px;">
                        <span class="info-lbl">Notes aménagements <i class="fas fa-lock" style="font-size:.7rem;color:#64748b;"></i></span>
                        <span class="info-val" style="white-space:pre-wrap;">{{ $accProfile->accommodations_notes }}</span>
                    </div>
                @endif
            @endcan

            @if($accProfile->effective_from || $accProfile->effective_to)
                <div class="info-row" style="margin-bottom:10px;">
                    <span class="info-lbl">Validité</span>
                    <span class="info-val">
                        @if($accProfile->effective_from)Du {{ $accProfile->effective_from->format('d/m/Y') }}@endif
                        @if($accProfile->effective_to) au {{ $accProfile->effective_to->format('d/m/Y') }}@endif
                    </span>
                </div>
            @endif

            @if($accProfile->recognition_reference)
                <div class="info-row">
                    <span class="info-lbl">Référence officielle</span>
                    <span class="info-val">{{ $accProfile->recognition_reference }}</span>
                </div>
            @endif
        </div>
    </div>
    @endif
    @endcan

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

{{-- ───── Documents ───── --}}
<div class="tab-panel" id="tab-documents">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0 fw-semibold" style="color:var(--k-text);">
            <i class="fas fa-folder-open me-2" style="color:var(--k-blue);"></i>Documents de l'étudiant
        </h6>
        <button class="btn btn-acasi primary btn-sm" data-bs-toggle="modal" data-bs-target="#docUploadModal">
            <i class="fas fa-plus me-1"></i>Ajouter un document
        </button>
    </div>

    {{-- Liste --}}
    <div id="doc-list">
        @forelse($etudiant->documents as $doc)
        @php $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)); @endphp
        <div class="doc-card doc-item" id="doc-item-{{ $doc->id }}">
            <div class="doc-card-inner">
                {{-- Badge extension coloré --}}
                <div class="doc-ext-badge doc-ext-{{ in_array($ext, ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','gif','webp','zip','rar','txt']) ? $ext : 'default' }}">
                    <i class="fas {{ $doc->getFileIcon() }} ext-icon"></i>
                    <span class="ext-label">{{ strtoupper($ext) ?: 'DOC' }}</span>
                </div>
                {{-- Corps --}}
                <div class="doc-card-body">
                    <div class="doc-card-title" title="{{ $doc->titre }}">{{ $doc->titre }}</div>
                    @if($doc->description)
                        <div class="doc-card-desc">{{ $doc->description }}</div>
                    @endif
                    <div class="doc-card-meta">
                        <span class="doc-meta-chip" title="{{ $doc->file_name }}">
                            <i class="fas fa-paperclip"></i>{{ $doc->file_name }}
                        </span>
                        <span class="doc-meta-sep"></span>
                        <span class="doc-meta-chip">
                            <i class="fas fa-database"></i>{{ $doc->getFormattedFileSize() }}
                        </span>
                        <span class="doc-meta-sep"></span>
                        <span class="doc-meta-chip">
                            <i class="fas fa-calendar"></i>{{ $doc->created_at->format('d/m/Y') }}
                        </span>
                        @if($doc->uploadedBy)
                            <span class="doc-meta-sep"></span>
                            <span class="doc-meta-chip" title="{{ $doc->uploadedBy->name }}">
                                <i class="fas fa-user"></i>{{ $doc->uploadedBy->name }}
                            </span>
                        @endif
                    </div>
                </div>
                {{-- Actions --}}
                <div class="doc-card-actions">
                    <button class="doc-action-btn preview btn-preview-doc"
                        data-url="{{ $doc->getDownloadUrl() }}"
                        data-force-url="{{ $doc->getForceDownloadUrl() }}"
                        data-file-type="{{ $doc->file_type }}"
                        data-title="{{ $doc->titre }}"
                        data-filename="{{ $doc->file_name }}"
                        data-size="{{ $doc->getFormattedFileSize() }}"
                        title="Prévisualiser">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="doc-action-btn delete btn-delete-doc"
                        data-id="{{ $doc->id }}"
                        data-url="{{ route('esbtp.etudiants.documents.destroy', [$etudiant->id, $doc->id]) }}"
                        title="Supprimer">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div id="doc-empty" class="s-card text-center py-5" style="color:var(--k-muted);">
            <i class="fas fa-folder-open fa-2x mb-2 d-block" style="opacity:.4;"></i>
            Aucun document enregistré pour cet étudiant.
        </div>
        @endforelse
    </div>

</div>{{-- /tab-documents --}}

</div>{{-- /fiche-content --}}
</div>{{-- /fiche-page --}}

{{-- Modal upload document — Design premium --}}
<div class="modal fade doc-upload-modal" id="docUploadModal" tabindex="-1" aria-labelledby="docUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content dum-content">

            {{-- Header gradient horizontal --}}
            <div class="dum-header">
                <div class="dum-header-bg"></div>
                <button type="button" class="dum-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
                <div class="dum-header-icon">
                    <i class="fas fa-file-arrow-up"></i>
                </div>
                <div class="dum-header-texts">
                    <h4 class="dum-header-title" id="docUploadModalLabel">Ajouter un document</h4>
                    <p class="dum-header-sub">{{ $etudiant->nom_complet }}</p>
                </div>
            </div>

            {{-- Body — 2 colonnes paysage --}}
            <div class="dum-body">
                {{-- Colonne gauche : champs --}}
                <div class="dum-body-left">
                    <div id="doc-upload-alert" class="dum-alert d-none" role="alert"></div>

                    {{-- Titre --}}
                    <div class="dum-field">
                        <div class="dum-field-icon"><i class="fas fa-tag"></i></div>
                        <div class="dum-field-inner">
                            <label for="doc-titre" class="dum-label">Titre du document <span class="dum-required">*</span></label>
                            <input type="text" class="dum-input" id="doc-titre"
                                   placeholder="Ex : Baccalauréat, Extrait de naissance, CNI…"
                                   maxlength="255" autocomplete="off">
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="dum-field" style="flex: 1;">
                        <div class="dum-field-icon"><i class="fas fa-align-left"></i></div>
                        <div class="dum-field-inner" style="display:flex; flex-direction:column;">
                            <label for="doc-description" class="dum-label">Note <span class="dum-optional">— optionnel</span></label>
                            <textarea class="dum-input dum-textarea" id="doc-description"
                                      rows="4" maxlength="1000" style="flex:1; resize:none;"
                                      placeholder="Précision sur ce document…"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Colonne droite : drop zone --}}
                <div class="dum-body-right">
                    <div class="dum-dropzone" id="doc-dropzone" style="min-height: 200px;">
                        <input type="file" class="dum-file-input" id="doc-fichier"
                               accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
                        <div class="dum-dz-idle" id="doc-dz-idle">
                            <div class="dum-dz-icon">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <p class="dum-dz-text">Glissez un fichier ici</p>
                            <p class="dum-dz-sub">ou <span class="dum-dz-browse">parcourir</span></p>
                            <p class="dum-dz-formats">PDF · JPG · PNG · DOCX — max 10 Mo</p>
                        </div>
                        <div class="dum-dz-preview d-none" id="doc-dz-preview">
                            <div class="dum-preview-icon" id="doc-preview-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="dum-preview-info">
                                <span class="dum-preview-name" id="doc-preview-name">document.pdf</span>
                                <span class="dum-preview-size" id="doc-preview-size">2.4 Mo</span>
                            </div>
                            <button type="button" class="dum-preview-remove" id="doc-preview-remove" title="Retirer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="dum-footer">
                <button type="button" class="dum-btn-cancel" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="dum-btn-submit" id="doc-upload-btn">
                    <span id="doc-upload-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <i class="fas fa-arrow-up-from-bracket" id="doc-upload-icon"></i>
                    <span id="doc-upload-label">Importer</span>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Modal preview document --}}
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl dpm-dialog">
        <div class="modal-content dpm-content">

            <div class="dpm-header">
                <div class="dpm-header-bg"></div>
                <button type="button" class="dum-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
                <div class="dpm-file-icon" id="dpm-file-icon">
                    <i class="fas fa-file"></i>
                </div>
                <div class="dum-header-texts">
                    <h4 class="dum-header-title" id="docPreviewModalLabel"><span id="dpm-title">Document</span></h4>
                    <p class="dum-header-sub"><span id="dpm-filename"></span> · <span id="dpm-size"></span></p>
                </div>
                <a id="dpm-download-btn" href="#" class="dpm-dl-btn" title="Télécharger">
                    <i class="fas fa-download"></i>
                    <span>Télécharger</span>
                </a>
            </div>

            <div class="dpm-body" id="dpm-preview-area">
                {{-- Filled dynamically --}}
            </div>

        </div>
    </div>
</div>

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
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
// Switch LMD semester tabs
function switchLmdSem(sem) {
    document.querySelectorAll('.lmd-sem-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.lmd-sem-tab').forEach(t => {
        t.style.borderColor = '#e2e8f0';
        t.style.background = '#fff';
        t.style.color = '#64748b';
        t.classList.remove('active');
    });
    const panel = document.querySelector('.lmd-sem-panel[data-sem="' + sem + '"]');
    if (panel) panel.style.display = 'block';
    const tab = document.querySelector('.lmd-sem-tab[data-sem="' + sem + '"]');
    if (tab) {
        tab.style.borderColor = '#059669';
        tab.style.background = '#ecfdf5';
        tab.style.color = '#059669';
        tab.classList.add('active');
    }
}

// Upload photo étudiant via AJAX
function uploadEtudiantPhoto(input) {
    if (!input.files || !input.files[0]) return;

    var file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('La photo ne doit pas dépasser 5 Mo.');
        input.value = '';
        return;
    }

    var btn = document.getElementById('heroPhotoUploadBtn');
    var icon = btn.querySelector('i');
    btn.classList.add('uploading');
    icon.className = 'fas fa-spinner fa-spin';

    var formData = new FormData();
    formData.append('photo', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch("{{ route('esbtp.etudiants.update-photo', $etudiant) }}", {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.classList.remove('uploading');
        icon.className = 'fas fa-camera';

        if (data.success && data.photo_url) {
            var avatar = document.getElementById('heroAvatarDisplay');
            var img = document.createElement('img');
                    img.src = data.photo_url + '?' + Date.now();
                    img.alt = 'Photo';
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
                    avatar.innerHTML = '';
                    avatar.appendChild(img);
        } else {
            alert(data.message || 'Erreur lors de la mise à jour de la photo.');
        }
    })
    .catch(function() {
        btn.classList.remove('uploading');
        icon.className = 'fas fa-camera';
        alert('Erreur réseau lors de l\'upload.');
    });

    input.value = '';
}
</script>
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
<script>
(function () {
    const uploadUrl  = "{{ route('esbtp.etudiants.documents.store', $etudiant->id) }}";
    const deleteBase = "{{ url('esbtp/etudiants/' . $etudiant->id . '/documents') }}";
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ── Drag & Drop + file preview ──
    const dropzone   = document.getElementById('doc-dropzone');
    const fileInput  = document.getElementById('doc-fichier');
    const dzIdle     = document.getElementById('doc-dz-idle');
    const dzPreview  = document.getElementById('doc-dz-preview');

    const ICON_MAP = {
        'pdf': 'fa-file-pdf', 'jpg': 'fa-file-image', 'jpeg': 'fa-file-image',
        'png': 'fa-file-image', 'docx': 'fa-file-word', 'doc': 'fa-file-word',
    };

    function formatBytes(b) {
        if (b < 1024) return b + ' o';
        if (b < 1048576) return (b/1024).toFixed(0) + ' Ko';
        return (b/1048576).toFixed(1) + ' Mo';
    }

    function showFilePreview(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        document.getElementById('doc-preview-icon').innerHTML =
            `<i class="fas ${ICON_MAP[ext] || 'fa-file-alt'}"></i>`;
        document.getElementById('doc-preview-name').textContent = file.name;
        document.getElementById('doc-preview-size').textContent = formatBytes(file.size);
        dzIdle.classList.add('d-none');
        dzPreview.classList.remove('d-none');
    }

    function clearFilePreview() {
        fileInput.value = '';
        dzIdle.classList.remove('d-none');
        dzPreview.classList.add('d-none');
    }

    fileInput.addEventListener('change', function () {
        if (this.files[0]) showFilePreview(this.files[0]);
    });

    document.getElementById('doc-preview-remove').addEventListener('click', function (e) {
        e.stopPropagation();
        clearFilePreview();
    });

    ['dragover', 'dragenter'].forEach(ev => dropzone.addEventListener(ev, function (e) {
        e.preventDefault(); dropzone.classList.add('drag-over');
    }));
    ['dragleave', 'dragend', 'drop'].forEach(ev => dropzone.addEventListener(ev, function (e) {
        e.preventDefault(); dropzone.classList.remove('drag-over');
    }));
    dropzone.addEventListener('drop', function (e) {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFilePreview(file);
    });

    // Reset preview when modal closes
    document.getElementById('docUploadModal').addEventListener('hidden.bs.modal', resetForm);

    // ── Upload ──
    document.getElementById('doc-upload-btn').addEventListener('click', function () {
        const titre       = document.getElementById('doc-titre').value.trim();
        const description = document.getElementById('doc-description').value.trim();
        const fichier     = fileInput.files[0];

        hideAlert();
        if (!titre)   { showAlert('Veuillez saisir un titre.', 'warning'); return; }
        if (!fichier) { showAlert('Veuillez sélectionner un fichier.', 'warning'); return; }

        const btn     = this;
        const spinner = document.getElementById('doc-upload-spinner');
        const icon    = document.getElementById('doc-upload-icon');
        const label   = document.getElementById('doc-upload-label');
        btn.disabled  = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');
        label.textContent = 'Envoi…';

        const formData = new FormData();
        formData.append('titre', titre);
        formData.append('description', description);
        formData.append('fichier', fichier);
        formData.append('_token', csrfToken);

        fetch(uploadUrl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showAlert(data.message || "Erreur lors de l'upload.", 'danger'); return; }
                bootstrap.Modal.getInstance(document.getElementById('docUploadModal')).hide();
                prependDoc(data.document);
                updateBadge(1);
            })
            .catch(() => showAlert('Erreur réseau. Veuillez réessayer.', 'danger'))
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
                label.textContent = 'Importer';
            });
    });

    // ── Delete ──
    document.getElementById('doc-list').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-doc');
        if (!btn) return;
        if (!confirm('Supprimer ce document définitivement ?')) return;

        fetch(btn.dataset.url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Erreur suppression.'); return; }
            const item = document.getElementById('doc-item-' + btn.dataset.id);
            if (item) { item.style.opacity = '0'; item.style.transform = 'translateX(12px)'; item.style.transition = '.2s'; setTimeout(() => item.remove(), 200); }
            updateBadge(-1);
            if (!document.querySelector('#doc-list .doc-item')) showEmpty();
        })
        .catch(() => alert('Erreur réseau.'));
    });

    // ── Preview — ouvre dans un nouvel onglet ──
    document.getElementById('doc-list').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-doc');
        if (!btn) return;
        window.open(btn.dataset.url, '_blank');
    });

    function showAlert(msg, type) {
        const el = document.getElementById('doc-upload-alert');
        el.className = 'dum-alert alert-' + type;
        el.textContent = msg;
    }
    function hideAlert() {
        document.getElementById('doc-upload-alert').className = 'dum-alert d-none';
    }

    function resetForm() {
        document.getElementById('doc-titre').value = '';
        document.getElementById('doc-description').value = '';
        clearFilePreview();
        hideAlert();
    }

    function prependDoc(doc) {
        const empty = document.getElementById('doc-empty');
        if (empty) empty.remove();
        const ext = (doc.file_name.split('.').pop() || '').toLowerCase();
        const extClass = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','gif','webp','zip','rar','txt'].includes(ext) ? ext : 'default';
        const uploaderChip = doc.uploaded_by
            ? `<span class="doc-meta-sep"></span><span class="doc-meta-chip" title="${escHtml(doc.uploaded_by)}"><i class="fas fa-user"></i>${escHtml(doc.uploaded_by)}</span>` : '';
        const html = `<div class="doc-card doc-item" id="doc-item-${doc.id}" style="animation:fadeUp .2s ease;">
            <div class="doc-card-inner">
                <div class="doc-ext-badge doc-ext-${extClass}">
                    <i class="fas ${doc.file_icon} ext-icon"></i>
                    <span class="ext-label">${ext.toUpperCase() || 'DOC'}</span>
                </div>
                <div class="doc-card-body">
                    <div class="doc-card-title" title="${escHtml(doc.titre)}">${escHtml(doc.titre)}</div>
                    ${doc.description ? `<div class="doc-card-desc">${escHtml(doc.description)}</div>` : ''}
                    <div class="doc-card-meta">
                        <span class="doc-meta-chip" title="${escHtml(doc.file_name)}"><i class="fas fa-paperclip"></i>${escHtml(doc.file_name)}</span>
                        <span class="doc-meta-sep"></span>
                        <span class="doc-meta-chip"><i class="fas fa-database"></i>${doc.file_size}</span>
                        <span class="doc-meta-sep"></span>
                        <span class="doc-meta-chip"><i class="fas fa-calendar"></i>${doc.created_at}</span>
                        ${uploaderChip}
                    </div>
                </div>
                <div class="doc-card-actions">
                    <button class="doc-action-btn preview btn-preview-doc" data-url="${doc.download_url}" data-force-url="${doc.force_download_url}" data-file-type="${doc.file_type}" data-title="${escHtml(doc.titre)}" data-filename="${escHtml(doc.file_name)}" data-size="${doc.file_size}" title="Prévisualiser"><i class="fas fa-eye"></i></button>
                    <button class="doc-action-btn delete btn-delete-doc" data-id="${doc.id}" data-url="${deleteBase}/${doc.id}" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        </div>`;
        document.getElementById('doc-list').insertAdjacentHTML('afterbegin', html);
    }

    function showEmpty() {
        document.getElementById('doc-list').insertAdjacentHTML('beforeend',
            '<div id="doc-empty" class="s-card text-center py-5" style="color:var(--k-muted);"><i class="fas fa-folder-open fa-2x mb-2 d-block" style="opacity:.4;"></i>Aucun document enregistré pour cet étudiant.</div>');
    }

    function updateBadge(delta) {
        const tab = document.querySelector('[data-tab="documents"]');
        let badge = tab.querySelector('.tab-badge');
        const next = (badge ? parseInt(badge.textContent) : 0) + delta;
        if (next <= 0) { if (badge) badge.remove(); return; }
        if (!badge) { badge = document.createElement('span'); badge.className = 'tab-badge'; tab.appendChild(badge); }
        badge.textContent = next;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();

// ════════════════════════════════════════════════════════════════
// PAYMENT MODAL — Enregistrer un paiement depuis fiche étudiant
// ════════════════════════════════════════════════════════════════
// Move modal to <body> to escape stacking context (animation/transform)
(function() {
    var m = document.getElementById('etudiantPaymentModal');
    if (m) document.body.appendChild(m);
})();

// Skeleton helpers
function etdShowSkeleton(el, cls) {
    if (!el) return;
    el.style.display = 'none';
    if (!el.parentElement.querySelector('.' + cls)) {
        el.insertAdjacentHTML('afterend', '<div class="etd-skeleton ' + cls + '"></div>');
    }
}
function etdHideSkeleton(el, cls) {
    if (!el?.parentElement) return;
    el.style.display = '';
    el.parentElement.querySelector('.' + cls)?.remove();
}

function prepareEtudiantPaymentModal(inscriptionId) {
    const form = document.getElementById('etudiantPaymentForm');
    form.action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
    form.reset();
    const dateInput = form.querySelector('#etd_date_paiement');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
    const msgDiv = document.getElementById('etd-montant-validation-message');
    if (msgDiv) msgDiv.style.display = 'none';
    const montantInput = form.querySelector('#etd_montant');
    if (montantInput) { montantInput.setAttribute('disabled', 'disabled'); montantInput.value = ''; montantInput.removeAttribute('max'); }
    window._etdInscriptionId = inscriptionId;
    window._etdIsSubscribed = false;
    window._etdCategoryCache = {};

    const sel = document.getElementById('etd_fee_category_id');
    const montantWrap = document.getElementById('etd_montant')?.parentElement;
    etdShowSkeleton(sel, 'etd-skeleton-select');
    etdShowSkeleton(montantWrap, 'etd-skeleton-input');

    fetch(`/esbtp/inscriptions/${inscriptionId}/frais-restants`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !sel) return;
            const classeSpan = document.getElementById('etd-modal-classe-info');
            if (classeSpan) {
                let info = '';
                if (data.classe) info += ' · ' + data.classe;
                if (data.annee) info += ' · ' + data.annee;
                classeSpan.textContent = info;
            }
            // Cache category data to avoid 2nd AJAX call
            window._etdCategoryCache = {};
            data.categories.forEach(c => {
                window._etdCategoryCache[c.category_id] = c;
            });
            sel.innerHTML = '<option value="">Sélectionnez une catégorie</option>';
            data.categories.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.category_id;
                opt.textContent = c.name + ' — reste ' + Number(c.montant_restant).toLocaleString('fr-FR') + ' FCFA';
                sel.appendChild(opt);
            });
            sel.disabled = false;
        })
        .catch(() => {
            if (sel) { sel.innerHTML = '<option value="">Erreur de chargement</option>'; }
        })
        .finally(() => {
            etdHideSkeleton(sel, 'etd-skeleton-select');
            etdHideSkeleton(montantWrap, 'etd-skeleton-input');
        });
}

function prepareEtudiantPaymentModalForCategory(inscriptionId, categoryId) {
    prepareEtudiantPaymentModal(inscriptionId);
    setTimeout(() => {
        const sel = document.getElementById('etd_fee_category_id');
        if (sel) { sel.value = categoryId; etdUpdateMontantRestant(); }
    }, 100);
}

function etdUpdateMontantRestant() {
    const sel = document.getElementById('etd_fee_category_id');
    const montantInput = document.getElementById('etd_montant');
    const categoryId = sel ? sel.value : null;
    const md = document.getElementById('etd-montant-validation-message');

    if (!categoryId || !montantInput) {
        window._etdIsSubscribed = false;
        if (montantInput) { montantInput.setAttribute('disabled', 'disabled'); montantInput.value = ''; }
        if (md) md.style.display = 'none';
        return;
    }

    // Use cached data from first AJAX call — no 2nd request needed
    const cached = (window._etdCategoryCache || {})[categoryId];
    if (cached) {
        window._etdIsSubscribed = true;
        montantInput.removeAttribute('disabled');
        montantInput.value = cached.montant_restant;
        montantInput.setAttribute('max', cached.montant_restant);
        if (md) {
            md.style.display = 'block';
            md.innerHTML = `<div style="background:linear-gradient(135deg,#e7f3ff,#f0f8ff);border-left:4px solid var(--k-blue);border-radius:8px;padding:.75rem 1rem;">
                <div style="display:flex;align-items:start;gap:.5rem;">
                    <i class="fas fa-info-circle" style="color:var(--k-blue);margin-top:2px;"></i>
                    <div style="flex:1;">
                        <strong style="color:#084298;">Montant maximum :</strong>
                        <span style="color:#052c65;font-weight:600;">${Number(cached.montant_restant).toLocaleString('fr-FR')} FCFA</span><br>
                        <small style="color:var(--k-muted);">Total: ${Number(cached.montant_total).toLocaleString('fr-FR')} FCFA · Payé: ${Number(cached.montant_paye).toLocaleString('fr-FR')} FCFA</small>
                    </div>
                </div>
            </div>`;
        }
        montantInput.oninput = function() {
            const v = parseFloat(this.value) || 0;
            if (v > cached.montant_restant) { this.setCustomValidity(`Max ${Number(cached.montant_restant).toLocaleString('fr-FR')} FCFA`); this.reportValidity(); }
            else this.setCustomValidity('');
        };
        return;
    }

    // Fallback: fetch from server if not cached
    window._etdIsSubscribed = false;
    montantInput.setAttribute('disabled', 'disabled');
    montantInput.value = '';
    if (md) md.style.display = 'none';
}

// AJAX submission — reste sur etudiants.show, recharge tab finances
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('etudiantPaymentForm');
    if (!form) return;
    let submitted = false;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!window._etdIsSubscribed) {
            alert('❌ Veuillez sélectionner une catégorie de frais valide.');
            return;
        }
        const montant = parseFloat(form.querySelector('#etd_montant').value) || 0;
        const max = parseFloat(form.querySelector('#etd_montant').getAttribute('max')) || Infinity;
        if (montant > max) {
            alert(`❌ Le montant ne peut pas dépasser ${max.toLocaleString('fr-FR')} FCFA`);
            return;
        }
        if (submitted) return;
        submitted = true;

        const submitBtn = form.querySelector('button[type="submit"]');
        const origHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: new FormData(form)
        })
        .then(r => r.json().catch(() => ({ success: true })))
        .then(data => {
            // Fermer le modal
            const modalEl = document.getElementById('etudiantPaymentModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Afficher toast de succès
            const toast = document.createElement('div');
            toast.className = 'etd-toast-success';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> Paiement enregistré avec succès';
            document.body.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3500);

            // Spinner sur tab finances pendant le fetch
            const finTab = document.getElementById('tab-finances');
            if (finTab) {
                const overlay = document.createElement('div');
                overlay.id = 'fin-loading-overlay';
                overlay.style.cssText = 'position:absolute;inset:0;background:rgba(255,255,255,.75);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:12px;';
                overlay.innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--k-blue);"></i><div style="margin-top:8px;font-size:.85rem;color:var(--k-muted);font-weight:600;">Mise à jour...</div></div>';
                finTab.style.position = 'relative';
                finTab.appendChild(overlay);
            }

            // Fetch la page pour récupérer le HTML à jour
            fetch(window.location.pathname, {
                headers: { 'Accept': 'text/html' }
            })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 1. Remplacer #tab-finances
                const newFin = doc.getElementById('tab-finances');
                const oldFin = document.getElementById('tab-finances');
                if (newFin && oldFin) {
                    oldFin.innerHTML = newFin.innerHTML;
                    // Garder le tab actif
                    oldFin.classList.add('active');
                }

                // 2. Remplacer .hero-kpi-strip
                const newStrip = doc.querySelector('.hero-kpi-strip');
                const oldStrip = document.querySelector('.hero-kpi-strip');
                if (newStrip && oldStrip) oldStrip.innerHTML = newStrip.innerHTML;

                // 3. Remplacer .kpi-grid (KPI cards overview)
                const newGrid = doc.querySelector('.kpi-grid');
                const oldGrid = document.querySelector('.kpi-grid');
                if (newGrid && oldGrid) oldGrid.innerHTML = newGrid.innerHTML;

                // Retirer l'overlay
                const ov = document.getElementById('fin-loading-overlay');
                if (ov) ov.remove();

                // Re-déplacer le modal vers body (il a été recréé dans le nouveau HTML)
                const newModal = document.getElementById('etudiantPaymentModal');
                if (newModal && newModal.parentElement !== document.body) {
                    document.body.appendChild(newModal);
                }

                // Reset du form pour un prochain paiement
                submitted = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            })
            .catch(() => {
                // Fallback : recharger la page
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'finances');
                window.location.href = url.toString();
            });
        })
        .catch(err => {
            console.error('Erreur paiement:', err);
            alert('❌ Une erreur est survenue. Veuillez réessayer.');
            submitted = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
        });
    });
});
</script>

{{-- ═══ MODAL PAIEMENT ÉTUDIANT ═══ --}}
<style>
.etd-skeleton {
    position: relative;
    overflow: hidden;
    background: #e9ecef;
    border-radius: 8px;
    color: transparent !important;
    pointer-events: none;
    user-select: none;
}
.etd-skeleton::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.55) 50%, transparent 100%);
    animation: etd-shimmer 1.4s infinite;
}
@keyframes etd-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.etd-skeleton-select { height: 38px; width: 100%; }
.etd-skeleton-input { height: 38px; width: 100%; }
.etd-skeleton-msg { height: 52px; width: 100%; margin-bottom: 1rem; }
</style>
@if(isset($finInscRef) && $finInscRef)
<div class="modal fade" id="etudiantPaymentModal" tabindex="-1" aria-labelledby="etudiantPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow:0 10px 40px rgba(0,0,0,.2);">
            <div class="modal-header" style="background:linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%); color:#fff; border-radius:15px 15px 0 0; padding:1.5rem; border:none;">
                <h5 class="modal-title fw-bold" id="etudiantPaymentModalLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Enregistrer un paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="etudiantPaymentForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_action" value="valider-avec-paiement">
                <div class="modal-body" style="padding:2rem;">
                    <div style="background:linear-gradient(135deg,#e7f3ff,#f0f8ff);border-left:4px solid var(--k-blue);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--k-blue),var(--k-blue-2));display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div style="color:#084298;font-weight:500;margin-bottom:.25rem;">{{ $etudiant->nom_complet }}</div>
                                <div style="color:#052c65;font-size:.9rem;">
                                    Matricule : <strong>{{ $etudiant->matricule ?? 'N/A' }}</strong>
                                    <span id="etd-modal-classe-info">@if($finInscRef?->classe) · {{ $finInscRef->classe->name }} @endif</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="etd-montant-validation-message" style="display:none; margin-bottom:1rem;"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="etd_fee_category_id" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-tags me-1" style="color:var(--k-blue);"></i>Catégorie de frais <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="etd_fee_category_id" name="fee_category_id" required
                                    style="border:2px solid #dee2e6;border-radius:8px;font-weight:500;"
                                    onchange="etdUpdateMontantRestant()">
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="etd_montant" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-coins me-1" style="color:var(--k-blue);"></i>Montant <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="etd_montant" name="montant" min="0" step="1" required disabled
                                       style="border:2px solid #dee2e6;font-weight:600;">
                                <span class="input-group-text" style="background:linear-gradient(135deg,#f8f9fa,#e9ecef);border:2px solid #dee2e6;border-left:none;font-weight:600;">FCFA</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="etd_mode_paiement" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-credit-card me-1" style="color:var(--k-blue);"></i>Mode de paiement <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="etd_mode_paiement" name="mode_paiement" required
                                    style="border:2px solid #dee2e6;border-radius:8px;font-weight:500;">
                                <option value="">Sélectionnez un mode</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="etd_reference_paiement" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-hashtag me-1" style="color:#6c757d;"></i>Référence
                            </label>
                            <input type="text" class="form-control" id="etd_reference_paiement" name="reference_paiement"
                                   placeholder="N° chèque, réf. virement..."
                                   style="border:2px solid #dee2e6;border-radius:8px;">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="etd_date_paiement" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-calendar-alt me-1" style="color:var(--k-blue);"></i>Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="etd_date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required
                                   style="border:2px solid #dee2e6;border-radius:8px;font-weight:500;">
                        </div>
                        <div class="col-md-6">
                            <label for="etd_observations" class="form-label fw-semibold" style="color:#2d3748;font-size:.9rem;">
                                <i class="fas fa-comment-dots me-1" style="color:#6c757d;"></i>Observations
                            </label>
                            <textarea class="form-control" id="etd_observations" name="observations" rows="3"
                                      placeholder="Commentaires..."
                                      style="border:2px solid #dee2e6;border-radius:8px;resize:none;"></textarea>
                        </div>
                    </div>

                    {{-- Checkbox validation directe (réservée aux users avec self_override : créateur = validateur) --}}
                    @can('paiements.validate.self_override')
                    <div style="margin-top:1.25rem; padding:14px 16px; background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:10px;">
                        <div class="form-check" style="margin:0;">
                            <input class="form-check-input" type="checkbox" id="etd_validate_payment" name="validate_payment" value="1"
                                   style="width:18px; height:18px; margin-top:2px; cursor:pointer;">
                            <label class="form-check-label" for="etd_validate_payment" style="font-weight:600; color:#166534; font-size:.88rem; cursor:pointer; margin-left:4px;">
                                <i class="fas fa-shield-alt me-1"></i>
                                Valider le paiement immédiatement
                            </label>
                            <div style="font-size:.78rem; color:#15803d; margin-top:3px; margin-left:22px;">
                                Si décoché, le paiement sera enregistré en attente de validation.
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
                <div class="modal-footer" style="background:#f8f9fa;border-radius:0 0 15px 15px;padding:1.25rem 2rem;border:none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding:.65rem 1.5rem;border-radius:8px;font-weight:600;border:2px solid #6c757d;">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding:.65rem 1.5rem;border-radius:8px;font-weight:600;background:linear-gradient(135deg,var(--k-blue),var(--k-blue-2));border:none;box-shadow:0 4px 12px rgba(4,83,203,.3);">
                        <i class="fas fa-check-circle me-2"></i>Enregistrer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
// Intercept [data-ii-confirm-form] buttons — show iiConfirm modal, then submit
// the referenced form programmatically. Replaces native confirm() dialogs.
document.querySelectorAll('[data-ii-confirm-form]').forEach(btn => {
    btn.addEventListener('click', async function (e) {
        e.preventDefault();
        const formId = this.getAttribute('data-ii-confirm-form');
        const form = document.getElementById(formId);
        if (!form || typeof window.iiConfirm !== 'function') {
            return;
        }

        const confirmed = await window.iiConfirm({
            title: this.getAttribute('data-ii-confirm-title') || 'Confirmer',
            message: this.getAttribute('data-ii-confirm-message') || 'Voulez-vous continuer ?',
            confirmLabel: this.getAttribute('data-ii-confirm-label') || 'Confirmer',
            cancelLabel: this.getAttribute('data-ii-confirm-cancel') || 'Annuler',
            danger: this.getAttribute('data-ii-confirm-danger') === '1',
        });

        if (confirmed) {
            form.submit();
        }
    });
});
</script>
@endpush
