@extends('layouts.app')

@section('title', 'Détails de la classe ' . $classe->name . ' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* =====================================================================
   CLASSES SHOW — namespace cs-*
   Design premium monochrome bleu KLASSCI
   ===================================================================== */
.dashboard-acasi {
    --cs-primary: #0453cb;
    --cs-primary-dark: #033a8e;
    --cs-accent: #3b7ddb;
    --cs-text: #1e293b;
    --cs-muted: #64748b;
    --cs-surface: #f8fafc;
    --cs-border: #e2e8f0;
    --cs-success: #10b981;
    --cs-warn: #f59e0b;
    --cs-danger: #ef4444;
}

/* ========================================= HERO compact ========================================= */
.cs-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2.25rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.cs-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -30px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.cs-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.cs-hero-left { flex: 1; min-width: 260px; }
.cs-breadcrumb {
    display: flex; align-items: center; gap: .4rem;
    font-size: .78rem; color: rgba(255,255,255,.7); margin-bottom: .5rem;
}
.cs-breadcrumb a { color: rgba(255,255,255,.85); text-decoration: none; transition: color .15s; }
.cs-breadcrumb a:hover { color: #fff; }
.cs-breadcrumb i { font-size: .6rem; opacity: .7; }
.cs-hero h1 {
    font-size: 1.6rem; font-weight: 700; color: #fff;
    margin: 0 0 .4rem; line-height: 1.2;
}
.cs-hero-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.cs-hero-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .6rem;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 99px;
    font-size: .75rem; font-weight: 500; color: #fff;
}
.cs-hero-chip--status-active { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.cs-hero-chip--status-inactive { background: rgba(148,163,184,.25); border-color: rgba(148,163,184,.4); }
.cs-hero-chip--warning { background: rgba(245,158,11,.22); border-color: rgba(254,215,170,.55); }
.cs-hero-chip--warning > i:first-child { color: #fbbf24; }
.cs-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: flex-start; }
.cs-btn--glass, .cs-btn--white {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600; text-decoration: none;
    cursor: pointer; transition: all .2s ease; border: 1px solid transparent;
}
.cs-btn--glass { background: rgba(255,255,255,.14); color: #fff; border-color: rgba(255,255,255,.18); }
.cs-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; transform: translateY(-1px); }
.cs-btn--white { background: #fff; color: var(--cs-primary); }
.cs-btn--white:hover { background: #f1f5f9; color: var(--cs-primary-dark); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.12); }

/* KPIs hero */
.cs-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .7rem; margin-top: 1.3rem;
    position: relative; z-index: 1;
}
.cs-kpi {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 11px; padding: .75rem .9rem;
    display: flex; align-items: center; gap: .6rem; transition: background .2s;
}
.cs-kpi:hover { background: rgba(255,255,255,.15); }
.cs-kpi-icon {
    width: 34px; height: 34px; border-radius: 9px;
    background: rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; color: #fff; flex-shrink: 0;
}
.cs-kpi-body { flex: 1; min-width: 0; }
.cs-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1; }
.cs-kpi-label {
    font-size: .65rem; color: rgba(255,255,255,.7);
    margin-top: .2rem; text-transform: uppercase;
    letter-spacing: .04em; font-weight: 500;
}
.cs-kpi-bar {
    height: 3px; background: rgba(255,255,255,.2);
    border-radius: 99px; margin-top: .4rem; overflow: hidden;
}
.cs-kpi-bar-fill { height: 100%; background: #fff; border-radius: 99px; transition: width .6s ease; }

/* ========================================= INFO GRID ========================================= */
.cs-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
.cs-info-card { background: #fff; border: 1px solid var(--cs-border); border-radius: 14px; padding: 1rem 1.25rem; }
.cs-info-card-header { display: flex; align-items: center; gap: .55rem; margin-bottom: .75rem; }
.cs-info-card-icon {
    width: 32px; height: 32px; border-radius: 9px;
    background: rgba(4,83,203,.1); color: var(--cs-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: .82rem; flex-shrink: 0;
}
.cs-info-card-title { font-size: .88rem; font-weight: 700; color: var(--cs-text); margin: 0; }
.cs-info-list { display: flex; flex-direction: column; gap: .4rem; font-size: .85rem; }
.cs-info-row {
    display: flex; justify-content: space-between; align-items: center;
    gap: 1rem; padding: .35rem 0; border-bottom: 1px dashed var(--cs-border);
}
.cs-info-row:last-child { border-bottom: none; }
.cs-info-label { color: var(--cs-muted); font-size: .78rem; font-weight: 500; }
/* Row pleine largeur (pour Hierarchie LMD tree, etc.) */
.cs-info-row--full { display: flex; flex-direction: column; align-items: stretch; gap: .65rem; padding: .85rem 0; }
.cs-info-full-header { display: flex; align-items: center; justify-content: space-between; gap: .65rem; }
/* Tree premium UEMOA — style IDE (indentation progressive + L-connectors VSCode-style) */
.cs-lmd-tree {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 12px;
    padding: .85rem;
}
.cs-lmd-tree-node {
    position: relative;
    display: flex; align-items: center;
    gap: .7rem;
    padding: 0 .65rem;
    border-radius: 7px;
    height: 44px;
    transition: background .15s;
}
.cs-lmd-tree-node + .cs-lmd-tree-node { margin-top: .25rem; }
.cs-lmd-tree-node:hover { background: rgba(4,83,203,.06); }
/* INDENTATION TREE - chaque enfant décalé à droite de son parent */
.cs-lmd-tree-node--mention  { margin-left: 1.6rem; }
.cs-lmd-tree-node--parcours { margin-left: 3.2rem; }
/* L-CONNECTOR : vertical aligné sur le CENTRE HORIZONTAL de l'icône parent, horizontal arrivant au bord gauche de l'icône enfant.
   Calcul : icône parent centrée à `1.65rem` du bord gauche du node parent (padding-left .65rem + 32px/2 = 1rem).
   Le node enfant est décalé de `margin-left: 1.6rem` à droite du parent. Donc le centre icône parent = 1.65 - 1.6 = .05rem du bord gauche du node enfant ≈ left:0 (~0.8px d'erreur, invisible). */
.cs-lmd-tree-node--mention::before,
.cs-lmd-tree-node--parcours::before {
    content: '';
    position: absolute;
    left: 0;                      /* trait vertical pile sous le centre horizontal icône parent */
    top: calc(-50% - .25rem);     /* part du milieu vertical du parent (height 44px + margin .25rem) */
    bottom: calc(50% - 1px);      /* arrive au milieu vertical de l'icône courante */
    width: .65rem;                /* segment horizontal jusqu'au bord gauche icône enfant (= padding-left .65rem) */
    border-left: 2px solid rgba(4,83,203,.42);
    border-bottom: 2px solid rgba(4,83,203,.42);
    border-bottom-left-radius: 7px;
    pointer-events: none;
}
.cs-lmd-tree-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .82rem; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
    position: relative; z-index: 1;
}
.cs-lmd-tree-node--domaine .cs-lmd-tree-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
.cs-lmd-tree-node--mention .cs-lmd-tree-icon { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
.cs-lmd-tree-node--parcours .cs-lmd-tree-icon { background: linear-gradient(135deg, #3b7ddb, #5e91de); }
.cs-lmd-tree-body {
    flex: 1; min-width: 0;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: .15rem .65rem;
}
.cs-lmd-tree-label { grid-column: 1; font-size: .62rem; color: var(--cs-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
.cs-lmd-tree-name { grid-column: 1; font-size: .92rem; font-weight: 700; color: var(--cs-text); line-height: 1.2; }
.cs-lmd-tree-code {
    grid-column: 2; grid-row: 1 / span 2;
    align-self: center;
    font-size: .64rem; color: #0453cb;
    background: rgba(4,83,203,.08);
    padding: .15rem .5rem; border-radius: 5px;
    font-weight: 700; letter-spacing: .3px;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}
.cs-lmd-tree-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    background: rgba(4,83,203,.12); color: #0453cb;
    border: 1px solid rgba(4,83,203,.25);
    padding: .2rem .55rem; border-radius: 6px;
    font-size: .68rem; font-weight: 700; letter-spacing: .4px;
}
.cs-info-value { color: var(--cs-text); font-weight: 600; text-align: right; }

/* Teachers chips */
.cs-teachers { display: flex; flex-wrap: wrap; gap: .4rem; padding-top: .4rem; }
.cs-teacher-chip {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .3rem .7rem .3rem .3rem;
    border-radius: 99px; border: 1px solid var(--cs-border);
    background: #fff; text-decoration: none; color: var(--cs-text);
    font-size: .78rem; font-weight: 500; transition: all .15s;
}
.cs-teacher-chip:hover { border-color: var(--cs-primary); background: rgba(4,83,203,.04); color: var(--cs-primary); }
.cs-teacher-avatar {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.cs-teacher-hours { font-size: .68rem; color: var(--cs-muted); font-weight: 400; }

/* ========================================= TABS (Alpine x-show) ========================================= */
.cs-tabs {
    background: #fff; border: 1px solid var(--cs-border);
    border-radius: 14px; margin-bottom: 1.25rem; overflow: hidden;
}
.cs-tabs-nav {
    display: flex; gap: .25rem; padding: .6rem .6rem 0;
    border-bottom: 1px solid var(--cs-border); background: var(--cs-surface);
    overflow-x: auto; scrollbar-width: none;
}
.cs-tabs-nav::-webkit-scrollbar { display: none; }
.cs-tab {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .7rem 1.1rem; font-size: .85rem; font-weight: 600;
    color: var(--cs-muted); background: transparent; border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer; transition: all .15s;
    white-space: nowrap; border-radius: 8px 8px 0 0;
}
.cs-tab:hover { color: var(--cs-primary); background: rgba(4,83,203,.04); }
.cs-tab.cs-tab--active {
    color: var(--cs-primary); border-bottom-color: var(--cs-primary); background: #fff;
}
.cs-tab-badge {
    background: rgba(4,83,203,.1); color: var(--cs-primary);
    font-size: .7rem; font-weight: 700; padding: .1rem .4rem; border-radius: 99px;
}
.cs-tab.cs-tab--active .cs-tab-badge { background: var(--cs-primary); color: #fff; }
.cs-tab-panel { padding: 1.25rem 1.5rem; }
[x-cloak] { display: none !important; }

/* ========================================= PANEL ÉTUDIANTS ========================================= */
.cs-students-actions {
    display: flex; justify-content: space-between; align-items: center;
    gap: .75rem; flex-wrap: wrap;
    margin-bottom: 1rem; padding-bottom: 1rem;
    border-bottom: 1px solid var(--cs-border);
}
.cs-students-count { font-size: .85rem; color: var(--cs-muted); }
.cs-students-count strong { color: var(--cs-text); }
.cs-students-btns { display: flex; gap: .5rem; flex-wrap: wrap; }
.cs-btn--primary, .cs-btn--ghost, .cs-btn--outline {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .5rem 1rem; border-radius: 10px;
    font-size: .83rem; font-weight: 600;
    cursor: pointer; transition: all .2s; text-decoration: none;
    border: 1px solid transparent;
}
.cs-btn--primary { background: var(--cs-primary); color: #fff; }
.cs-btn--primary:hover { background: var(--cs-primary-dark); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.25); }
.cs-btn--ghost { background: #fff; color: var(--cs-text); border-color: var(--cs-border); }
.cs-btn--ghost:hover { border-color: var(--cs-primary); color: var(--cs-primary); background: rgba(4,83,203,.04); }
.cs-btn--outline { background: #fff; color: var(--cs-primary); border-color: var(--cs-primary); }
.cs-btn--outline:hover { background: var(--cs-primary); color: #fff; }

.cs-table { border-collapse: separate; border-spacing: 0; width: 100%; }
.cs-table thead th {
    background: var(--cs-surface); color: var(--cs-muted);
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    padding: .75rem 1rem; border-bottom: 1px solid var(--cs-border);
    white-space: nowrap; text-align: left;
}
.cs-table tbody tr { transition: background .15s; }
.cs-table tbody tr:hover { background: rgba(4,83,203,.03); }
.cs-table tbody td {
    padding: .75rem 1rem; border-bottom: 1px solid var(--cs-surface);
    vertical-align: middle; color: var(--cs-text); font-size: .87rem;
}
.cs-etu-cell { display: flex; align-items: center; gap: .75rem; }
.cs-etu-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .78rem;
    flex-shrink: 0; overflow: hidden;
}
.cs-etu-name { font-weight: 600; color: var(--cs-text); line-height: 1.2; margin-bottom: .1rem; }
.cs-etu-prenom { font-weight: 400; color: #475569; }

.cs-matricule {
    display: inline-block; background: var(--cs-surface); color: var(--cs-muted);
    font-family: 'Courier New', monospace; font-size: .76rem; font-weight: 600;
    padding: .2rem .5rem; border-radius: 6px; letter-spacing: .03em;
}
.cs-gender { display: inline-flex; align-items: center; gap: .3rem; font-size: .82rem; color: var(--cs-muted); }
.cs-contact-line {
    display: flex; align-items: center; gap: .35rem;
    font-size: .8rem; color: var(--cs-muted);
}
.cs-contact-line i { width: 12px; text-align: center; color: #94a3b8; font-size: .72rem; }
.cs-btn-view {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--cs-border); background: #fff;
    color: var(--cs-primary); transition: all .15s; font-size: .82rem;
}
.cs-btn-view:hover {
    background: var(--cs-primary); color: #fff; border-color: var(--cs-primary);
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.cs-empty { text-align: center; padding: 3rem 1.5rem; }
.cs-empty-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--cs-surface);
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 1rem; color: var(--cs-muted); font-size: 1.4rem;
}
.cs-empty-title { font-weight: 600; color: var(--cs-text); margin-bottom: .3rem; }
.cs-empty-text { color: var(--cs-muted); font-size: .88rem; }

/* ========================================= PANEL MATIÈRES ========================================= */
.cs-matieres-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: .75rem; }
.cs-matiere-card {
    background: #fff; border: 1px solid var(--cs-border);
    border-radius: 12px; padding: .85rem 1rem; transition: all .2s;
}
.cs-matiere-card:hover { border-color: #c7d4e5; box-shadow: 0 4px 14px rgba(4,83,203,.06); transform: translateY(-1px); }
.cs-matiere-head { display: flex; justify-content: space-between; align-items: start; gap: .5rem; margin-bottom: .45rem; }
.cs-matiere-name { font-weight: 700; color: var(--cs-text); font-size: .92rem; line-height: 1.3; }
.cs-matiere-code {
    display: inline-block; background: var(--cs-surface); color: var(--cs-muted);
    font-family: 'Courier New', monospace; font-size: .7rem; font-weight: 600;
    padding: .15rem .45rem; border-radius: 5px; margin-top: .2rem;
}
.cs-matiere-coef {
    background: rgba(4,83,203,.1); color: var(--cs-primary);
    font-size: .78rem; font-weight: 700;
    padding: .25rem .55rem; border-radius: 7px; flex-shrink: 0;
}
.cs-matiere-desc { font-size: .78rem; color: var(--cs-muted); line-height: 1.4; margin: 0; }

/* ========================================= PANEL SUIVI HEURES ========================================= */
.cs-periode-toggle {
    display: flex; gap: .25rem;
    background: var(--cs-surface); border: 1px solid var(--cs-border);
    border-radius: 10px; padding: .25rem;
    margin-bottom: 1rem; width: fit-content;
}
.cs-periode-btn {
    padding: .4rem 1rem; border: none; background: transparent;
    color: var(--cs-muted); font-size: .8rem; font-weight: 600;
    border-radius: 8px; cursor: pointer; transition: all .15s;
}
.cs-periode-btn:hover { color: var(--cs-primary); }
.cs-periode-btn.active {
    background: #fff; color: var(--cs-primary);
    box-shadow: 0 1px 3px rgba(15,23,42,.1);
}
.cs-planning-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .75rem; margin-bottom: 1.25rem;
}
.cs-planning-kpi {
    background: #fff; border: 1px solid var(--cs-border);
    border-radius: 12px; padding: .85rem 1rem;
    position: relative; overflow: hidden;
}
.cs-planning-kpi::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-color, var(--cs-primary));
    border-radius: 12px 12px 0 0;
}
.cs-planning-kpi-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .88rem;
    background: color-mix(in srgb, var(--kpi-color, var(--cs-primary)) 12%, transparent);
    color: var(--kpi-color, var(--cs-primary));
    margin-bottom: .35rem;
}
.cs-planning-kpi-value { font-size: 1.4rem; font-weight: 800; color: var(--cs-text); line-height: 1; }
.cs-planning-kpi-label {
    font-size: .68rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .05em;
    color: var(--cs-muted); margin-top: .25rem;
}
.cs-subject-card {
    background: #fff; border-radius: 12px; border: 1px solid var(--cs-border);
    padding: 1rem 1.25rem; margin-bottom: .6rem;
    display: grid; grid-template-columns: 1fr 260px; gap: 1.25rem;
    align-items: start; transition: box-shadow .2s;
}
.cs-subject-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); }
.cs-subject-name { font-size: .95rem; font-weight: 700; color: var(--cs-text); }
.cs-subject-code {
    display: inline-block; background: var(--cs-surface); color: var(--cs-muted);
    font-family: 'Courier New', monospace; font-size: .68rem; font-weight: 600;
    padding: .15rem .45rem; border-radius: 5px; margin-left: .4rem;
}
.cs-progress-track {
    height: 9px; background: var(--cs-surface);
    border-radius: 99px; overflow: hidden;
    margin: .6rem 0 .35rem;
}
.cs-progress-fill { height: 100%; border-radius: 99px; transition: width .6s cubic-bezier(.4,0,.2,1); }
.cs-progress-fill--low { background: linear-gradient(90deg, #a7d4ff, var(--cs-primary)); }
.cs-progress-fill--mid { background: linear-gradient(90deg, #5e91de, var(--cs-primary)); }
.cs-progress-fill--good { background: linear-gradient(90deg, var(--cs-primary), var(--cs-primary-dark)); }
.cs-progress-fill--done { background: linear-gradient(90deg, #6ee7b7, var(--cs-success)); }
.cs-percent-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 48px; padding: .2rem .55rem;
    border-radius: 99px; font-size: .78rem; font-weight: 700; white-space: nowrap;
}

/* ========================================= MODALS ========================================= */
.cs-modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, var(--cs-primary) 100%);
    color: #fff; border-bottom: none;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}
.cs-modal-header .modal-title { color: #fff; font-weight: 700; font-size: 1rem; }
.cs-alert {
    display: flex; align-items: center; gap: .55rem;
    padding: .75rem 1rem; border-radius: 10px;
    margin-bottom: 1rem; font-size: .88rem; font-weight: 500;
}
.cs-alert--success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.cs-alert--danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.cs-info-box {
    display: flex; gap: .6rem;
    padding: .75rem 1rem; background: rgba(4,83,203,.06);
    border-left: 3px solid var(--cs-primary); border-radius: 8px;
    color: var(--cs-text); font-size: .84rem; line-height: 1.5; margin-top: .75rem;
}
.cs-info-box i { color: var(--cs-primary); font-size: .95rem; flex-shrink: 0; margin-top: .15rem; }
.cs-warn-box {
    display: flex; gap: .6rem;
    padding: .75rem 1rem; background: #fef3c7;
    border-left: 3px solid var(--cs-warn); border-radius: 8px;
    color: #92400e; font-size: .84rem; line-height: 1.5; margin-bottom: 1rem;
}
.cs-warn-box i { color: var(--cs-warn); font-size: .95rem; flex-shrink: 0; margin-top: .15rem; }
.cs-steps { padding-left: 1.2rem; line-height: 1.8; color: var(--cs-text); font-size: .88rem; }

/* ========================================= RESPONSIVE ========================================= */
@media (max-width: 992px) {
    .cs-hero { padding: 1.5rem 1.5rem 1.25rem; }
    .cs-hero h1 { font-size: 1.35rem; }
    .cs-info-grid { grid-template-columns: 1fr; }
    .cs-subject-card { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .cs-hero-top { flex-direction: column; align-items: stretch; }
    .cs-hero-actions { justify-content: flex-start; }
    .cs-kpis { grid-template-columns: repeat(2, 1fr); }
    .cs-tab-panel { padding: 1rem; }
    .cs-students-actions { flex-direction: column; align-items: stretch; }
}
@media (max-width: 480px) {
    .cs-hero { padding: 1.25rem; }
    .cs-hero h1 { font-size: 1.15rem; }
    .cs-kpis { grid-template-columns: 1fr; }
    .cs-matieres-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        @php
            $nombreEtudiants = $classe->etudiants->count();
            $pourcentage = $classe->places_totales > 0 ? round(($nombreEtudiants / $classe->places_totales) * 100, 1) : 0;
            $placesLibres = max(0, $classe->places_totales - $nombreEtudiants);
            // LMD : compter les ECUE depuis ESBTPPlanificationAcademique (source canonique)
            // BTS : compter via le pivot $combinationMatieres classique.
            $nbMatieres = (($classe->systeme_academique ?? '') === 'LMD' && isset($lmdMatieres))
                ? $lmdMatieres->count()
                : ($combinationMatieres ?? collect())->count();
            $kpiTaux = $planningMatiere['stats']['taux_realisation'] ?? 0;
            $nbEnseignants = isset($planningMatiere['enseignants']) ? $planningMatiere['enseignants']->count() : 0;
            $queryParams = request()->query();
            unset($queryParams['classe']);
            $returnToIndexUrl = route('esbtp.classes.index', $queryParams);
        @endphp

        {{-- HERO --}}
        <div class="cs-hero">
            <div class="cs-hero-top">
                <div class="cs-hero-left">
                    <div class="cs-breadcrumb">
                        <a href="{{ $returnToIndexUrl }}">Classes</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>{{ $classe->name }}</span>
                    </div>
                    <h1>{{ $classe->name }}</h1>
                    <div class="cs-hero-chips">
                        @php $isLmd = ($classe->systeme_academique ?? '') === 'LMD'; @endphp
                        @if($isLmd && $classe->parcours && optional($classe->parcours->mention)->domaine)
                            <span class="cs-hero-chip" title="Domaine UEMOA"><i class="fas fa-folder-open"></i>{{ $classe->parcours->mention->domaine->name }}</span>
                            <span class="cs-hero-chip" title="Mention"><i class="fas fa-graduation-cap"></i>{{ $classe->parcours->mention->name }}</span>
                            <span class="cs-hero-chip" title="Parcours"><i class="fas fa-route"></i>{{ $classe->parcours->name }}@if($classe->parcours->code) ({{ $classe->parcours->code }})@endif</span>
                            <span class="cs-hero-chip" style="background:rgba(255,255,255,.18);font-weight:700;letter-spacing:.5px;"><i class="fas fa-university"></i>LMD</span>
                        @elseif($isLmd && optional($classe->parcours)->mention)
                            <span class="cs-hero-chip"><i class="fas fa-graduation-cap"></i>{{ $classe->parcours->mention->name }}</span>
                            @if($classe->parcours->name)
                                <span class="cs-hero-chip"><i class="fas fa-route"></i>{{ $classe->parcours->name }}</span>
                            @endif
                            <span class="cs-hero-chip" style="background:rgba(255,255,255,.18);font-weight:700;"><i class="fas fa-university"></i>LMD</span>
                        @elseif($isLmd && $classe->filiere)
                            <span class="cs-hero-chip" title="Mention (filière)"><i class="fas fa-graduation-cap"></i>{{ $classe->filiere->name }}</span>
                            <span class="cs-hero-chip" style="background:rgba(255,255,255,.18);font-weight:700;"><i class="fas fa-university"></i>LMD tronc commun</span>
                        @elseif($classe->filiere)
                            <span class="cs-hero-chip"><i class="fas fa-layer-group"></i>{{ $classe->filiere->name }}</span>
                        @endif
                        @if($classe->niveau)
                            <span class="cs-hero-chip"><i class="fas fa-level-up-alt"></i>{{ $classe->niveau->name }}</span>
                        @endif
                        <span class="cs-hero-chip cs-hero-chip--status-{{ $classe->is_active ? 'active' : 'inactive' }}">
                            <i class="fas fa-{{ $classe->is_active ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $classe->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @if($anneeAcademique)
                            <span class="cs-hero-chip">
                                <i class="fas fa-calendar-alt"></i>{{ $anneeAcademique }}
                                <button type="button" onclick="showYearChangeInfo()" style="background:transparent;border:none;color:rgba(255,255,255,.7);padding:0;margin-left:.2rem;cursor:pointer;" title="Comment changer d'année ?">
                                    <i class="fas fa-info-circle" style="font-size:.72rem;"></i>
                                </button>
                            </span>
                        @else
                            <span class="cs-hero-chip cs-hero-chip--warning">
                                <i class="fas fa-exclamation-triangle"></i>Aucune année universitaire définie
                                <button type="button" onclick="showYearChangeInfo()" style="background:transparent;border:none;color:rgba(255,255,255,.7);padding:0;margin-left:.2rem;cursor:pointer;" title="Comment définir une année courante ?">
                                    <i class="fas fa-info-circle" style="font-size:.72rem;"></i>
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="cs-hero-actions">
                    @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.coordinate']))
                        @if(($classe->systeme_academique ?? '') === 'LMD')
                            <a href="{{ route('esbtp.lmd.ue.index', array_filter(['parcours_id' => $classe->parcours_id, 'niveau_id' => $classe->niveau_etude_id, 'filiere_id' => $classe->filiere_id])) }}" class="cs-btn--glass" title="Gérer les Unités d'Enseignement et leurs ECUEs pour cette classe">
                                <i class="fas fa-cubes"></i>Gérer UE / ECUE
                            </a>
                        @else
                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="cs-btn--glass">
                                <i class="fas fa-book"></i>Gérer matières
                            </a>
                        @endif
                    @endif
                    @can('classes.edit')
                        <a href="{{ route('esbtp.classes.edit', array_merge(['classe' => $classe->id], ['return_url' => request()->fullUrl()])) }}" class="cs-btn--white">
                            <i class="fas fa-edit"></i>Modifier
                        </a>
                    @endcan
                    <a href="{{ $returnToIndexUrl }}" class="cs-btn--glass" title="Retour à la liste">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <div class="cs-kpis">
                <div class="cs-kpi">
                    <div class="cs-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="cs-kpi-body">
                        <div class="cs-kpi-value">{{ $nombreEtudiants }}</div>
                        <div class="cs-kpi-label">Inscrits</div>
                    </div>
                </div>
                <div class="cs-kpi">
                    <div class="cs-kpi-icon"><i class="fas fa-chair"></i></div>
                    <div class="cs-kpi-body">
                        <div class="cs-kpi-value">{{ $classe->places_totales }}</div>
                        <div class="cs-kpi-label">Capacité ({{ $placesLibres }} libres)</div>
                        <div class="cs-kpi-bar"><div class="cs-kpi-bar-fill" style="width:{{ min(100, $pourcentage) }}%"></div></div>
                    </div>
                </div>
                <div class="cs-kpi">
                    <div class="cs-kpi-icon"><i class="fas fa-book"></i></div>
                    <div class="cs-kpi-body">
                        <div class="cs-kpi-value">{{ $nbMatieres }}</div>
                        <div class="cs-kpi-label">Matières</div>
                    </div>
                </div>
                <div class="cs-kpi">
                    <div class="cs-kpi-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="cs-kpi-body">
                        <div class="cs-kpi-value">{{ $kpiTaux }}%</div>
                        <div class="cs-kpi-label">Taux réalisation</div>
                    </div>
                </div>
                <div class="cs-kpi">
                    <div class="cs-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="cs-kpi-body">
                        <div class="cs-kpi-value">{{ $nbEnseignants }}</div>
                        <div class="cs-kpi-label">Enseignants</div>
                    </div>
                </div>
            </div>
        </div>

        @if(($classe->systeme_academique ?? '') === 'LMD' && !empty($lmdVolumeBudget))
            @php
                // Agreger CM/TD/TP/Projet/TPE (sommes par categorie sur toutes les ECUEs de la classe)
                $vbTotals = ['cm'=>['p'=>0,'r'=>0],'td'=>['p'=>0,'r'=>0],'tp'=>['p'=>0,'r'=>0]];
                foreach ($lmdVolumeBudget as $matiereId => $budget) {
                    foreach (['cm','td','tp'] as $k) {
                        $vbTotals[$k]['p'] += (float) ($budget[$k]['planifie'] ?? 0);
                        $vbTotals[$k]['r'] += (float) ($budget[$k]['realise'] ?? 0);
                    }
                }
                $vbLabels = ['cm'=>'Cours Magistral','td'=>'Travaux Dirigés','tp'=>'Travaux Pratiques'];
                $vbIcons  = ['cm'=>'fa-chalkboard-user','td'=>'fa-pen-ruler','tp'=>'fa-flask-vial'];
            @endphp
            <div class="cs-card" style="margin-top:1rem;">
                <div class="cs-card-head" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:.65rem;">
                        <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#0453cb,#3b7ddb);color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <div style="font-weight:700;color:#0f172a;font-size:1rem;">Volume horaire LMD — planifié vs réalisé</div>
                            <div style="font-size:.78rem;color:#64748b;">Suivi par catégorie pédagogique (UEMOA) sur toutes les ECUEs de la classe</div>
                        </div>
                    </div>
                    <a href="{{ route('esbtp.lmd.planning.index', array_filter(['parcours_id' => optional($classe->parcours)->id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => !empty($lmdSemestres) ? ($lmdSemestres[0] ?? null) : null])) }}" class="cs-btn--ghost" style="font-size:.78rem;">
                        <i class="fas fa-external-link-alt"></i> Voir maquette LMD
                    </a>
                </div>
                <div class="cs-card-body" style="padding:1.25rem;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                        @foreach(['cm','td','tp'] as $k)
                            @php
                                $p = $vbTotals[$k]['p']; $r = $vbTotals[$k]['r'];
                                $pct = $p > 0 ? min(100, round($r / $p * 100)) : ($r > 0 ? 100 : 0);
                                $tone = $pct >= 100 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#0453cb');
                            @endphp
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:.85rem 1rem;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                                    <div style="display:flex;align-items:center;gap:.5rem;">
                                        <i class="fas {{ $vbIcons[$k] }}" style="color:#0453cb;font-size:.85rem;"></i>
                                        <span style="font-weight:700;color:#0f172a;font-size:.85rem;">{{ $vbLabels[$k] }}</span>
                                    </div>
                                    <span style="font-size:.72rem;color:#64748b;font-weight:600;">{{ rtrim(rtrim(number_format($r,1,',','') ,'0'),',') ?: '0' }}h / {{ rtrim(rtrim(number_format($p,1,',',''),'0'),',') ?: '0' }}h</span>
                                </div>
                                <div style="background:rgba(4,83,203,.08);border-radius:6px;height:8px;overflow:hidden;">
                                    <div style="background:{{ $tone }};height:100%;width:{{ $pct }}%;transition:width .3s ease;"></div>
                                </div>
                                <div style="margin-top:.35rem;font-size:.7rem;color:{{ $tone }};font-weight:700;">{{ $pct }}%</div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($lmdVolumeBudget) === 0)
                        <div style="margin-top:1rem;padding:.75rem;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;">
                            <i class="fas fa-info-circle me-1"></i>Aucune planification académique pour cette classe LMD ({{ optional($classe->parcours)->name ?? 'parcours non défini' }} · S1). Configurez la maquette dans <a href="{{ route('esbtp.lmd.planning.index') }}" style="color:#92400e;text-decoration:underline;">Planning LMD</a>.
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="cs-alert cs-alert--success">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="cs-alert cs-alert--danger">
                <i class="fas fa-exclamation-triangle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- INFO GRID --}}
        <div class="cs-info-grid">
            <div class="cs-info-card">
                <div class="cs-info-card-header">
                    <div class="cs-info-card-icon"><i class="fas fa-info-circle"></i></div>
                    <h3 class="cs-info-card-title">Informations générales</h3>
                </div>
                <div class="cs-info-list">
                    <div class="cs-info-row">
                        <span class="cs-info-label">Code</span>
                        <span class="cs-info-value" style="font-family:'Courier New',monospace;">{{ $classe->code }}</span>
                    </div>
                    @php $isLmdInfo = ($classe->systeme_academique ?? '') === 'LMD'; @endphp
                    @if($isLmdInfo && optional($classe->parcours)->mention && optional($classe->parcours->mention)->domaine)
                        <div class="cs-info-row cs-info-row--full">
                            <div class="cs-info-full-header">
                                <span class="cs-info-label">Hiérarchie LMD</span>
                                <span class="cs-lmd-tree-badge"><i class="fas fa-university"></i>LMD</span>
                            </div>
                            <div class="cs-lmd-tree">
                                <div class="cs-lmd-tree-node cs-lmd-tree-node--domaine">
                                    <div class="cs-lmd-tree-icon"><i class="fas fa-folder-open"></i></div>
                                    <div class="cs-lmd-tree-body">
                                        <div class="cs-lmd-tree-label">Domaine</div>
                                        <div class="cs-lmd-tree-name">{{ $classe->parcours->mention->domaine->name }}</div>
                                        @if($classe->parcours->mention->domaine->code)
                                            <span class="cs-lmd-tree-code">{{ $classe->parcours->mention->domaine->code }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="cs-lmd-tree-node cs-lmd-tree-node--mention">
                                    <div class="cs-lmd-tree-icon"><i class="fas fa-graduation-cap"></i></div>
                                    <div class="cs-lmd-tree-body">
                                        <div class="cs-lmd-tree-label">Mention</div>
                                        <div class="cs-lmd-tree-name">{{ $classe->parcours->mention->name }}</div>
                                        @if($classe->parcours->mention->code)
                                            <span class="cs-lmd-tree-code">{{ $classe->parcours->mention->code }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="cs-lmd-tree-node cs-lmd-tree-node--parcours">
                                    <div class="cs-lmd-tree-icon"><i class="fas fa-route"></i></div>
                                    <div class="cs-lmd-tree-body">
                                        <div class="cs-lmd-tree-label">Parcours</div>
                                        <div class="cs-lmd-tree-name">{{ $classe->parcours->name }}</div>
                                        @if($classe->parcours->code)
                                            <span class="cs-lmd-tree-code">{{ $classe->parcours->code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($isLmdInfo)
                        <div class="cs-info-row">
                            <span class="cs-info-label">Mention</span>
                            <span class="cs-info-value">
                                @if($classe->filiere)
                                    {{ $classe->filiere->name }}
                                    <small style="font-weight:400;color:var(--cs-muted);display:block;">Tronc commun mention (sans parcours)</small>
                                @else
                                    <span style="color:var(--cs-muted);">Non assignée</span>
                                @endif
                            </span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Système</span>
                            <span class="cs-info-value">
                                <span style="display:inline-flex;align-items:center;gap:.3rem;background:rgba(4,83,203,.12);color:#0453cb;border:1px solid rgba(4,83,203,.25);padding:.15rem .5rem;border-radius:6px;font-size:.75rem;font-weight:700;letter-spacing:.4px;">
                                    <i class="fas fa-university"></i>LMD tronc commun
                                </span>
                            </span>
                        </div>
                    @else
                        <div class="cs-info-row">
                            <span class="cs-info-label">Filière</span>
                            <span class="cs-info-value">
                                @if($classe->filiere)
                                    {{ $classe->filiere->name }}
                                    @if($classe->filiere->parent)<br><small style="font-weight:400;color:var(--cs-muted);">Option de {{ $classe->filiere->parent->name }}</small>@endif
                                @else
                                    <span style="color:var(--cs-muted);">Non assignée</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    <div class="cs-info-row">
                        <span class="cs-info-label">Niveau</span>
                        <span class="cs-info-value">
                            @if($classe->niveau)
                                {{ $classe->niveau->name }}
                                <small style="font-weight:400;color:var(--cs-muted);display:block;">{{ $classe->niveau->type }} · Année {{ $classe->niveau->year }}</small>
                            @else
                                <span style="color:var(--cs-muted);">Non assigné</span>
                            @endif
                        </span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label">Description</span>
                        <span class="cs-info-value" style="font-weight:400;">{{ $classe->description ?: '—' }}</span>
                    </div>
                    <div class="cs-info-row">
                        <span class="cs-info-label">Créée le</span>
                        <span class="cs-info-value" style="font-weight:400;">{{ $classe->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="cs-info-card">
                <div class="cs-info-card-header">
                    <div class="cs-info-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <h3 class="cs-info-card-title">Enseignants ({{ $nbEnseignants }})</h3>
                        <p style="margin:.15rem 0 0;font-size:.72rem;color:var(--cs-muted);line-height:1.35;">
                            <i class="fas fa-circle-info" style="color:var(--cs-primary);font-size:.7rem;"></i>
                            Enseignants ayant tenu au moins une séance dans l'emploi du temps de cette classe (année courante)
                        </p>
                    </div>
                </div>
                <div class="cs-teachers">
                    @if(!empty($planningMatiere['enseignants']) && $planningMatiere['enseignants']->isNotEmpty())
                        @foreach($planningMatiere['enseignants'] as $enseignant)
                            @php
                                $hue = hexdec(substr(md5($enseignant['name'] ?? (string)$enseignant['id']), 0, 4)) % 360;
                            @endphp
                            <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant['id']]) }}" class="cs-teacher-chip">
                                <span class="cs-teacher-avatar" style="background: hsl({{ $hue }}, 55%, 45%);">{{ strtoupper(mb_substr($enseignant['name'], 0, 1)) }}</span>
                                <span>{{ $enseignant['name'] }}</span>
                                <span class="cs-teacher-hours">{{ number_format($enseignant['heures_realisees'], 1) }}h</span>
                            </a>
                        @endforeach
                    @else
                        <span style="color:var(--cs-muted);font-size:.85rem;">Aucun enseignant n'a encore tenu de séance dans l'emploi du temps de cette classe.</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- TABS Alpine --}}
        <div class="cs-tabs"
             x-data="{
                tab: (location.hash && ['etudiants','matieres','heures'].includes(location.hash.slice(1))) ? location.hash.slice(1) : 'etudiants',
                setTab(name) { this.tab = name; history.replaceState(null, '', '#' + name); }
             }"
             x-cloak>
            <div class="cs-tabs-nav">
                <button type="button" class="cs-tab" :class="{ 'cs-tab--active': tab === 'etudiants' }" @click="setTab('etudiants')">
                    <i class="fas fa-users"></i>Étudiants
                    <span class="cs-tab-badge">{{ $nombreEtudiants }}</span>
                </button>
                <button type="button" class="cs-tab" :class="{ 'cs-tab--active': tab === 'matieres' }" @click="setTab('matieres')">
                    <i class="fas fa-book"></i>Matières
                    <span class="cs-tab-badge">{{ $nbMatieres }}</span>
                </button>
                <button type="button" class="cs-tab" :class="{ 'cs-tab--active': tab === 'heures' }" @click="setTab('heures')">
                    <i class="fas fa-chart-line"></i>Suivi des heures
                </button>
            </div>

            {{-- TAB ÉTUDIANTS --}}
            <div class="cs-tab-panel" x-show="tab === 'etudiants'">
                <div class="cs-students-actions">
                    <div class="cs-students-count">
                        <strong id="studentCountSubtitle">{{ $nombreEtudiants }} étudiant(s)</strong> inscrit(s) dans cette classe pour l'année courante
                    </div>
                    <div class="cs-students-btns">
                        @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.coordinate']))
                            <button type="button" class="cs-btn--primary" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                                <i class="fas fa-user-plus"></i>Ajouter
                            </button>
                            <button type="button" class="cs-btn--ghost" data-bs-toggle="modal" data-bs-target="#removeStudentsModal">
                                <i class="fas fa-exchange-alt"></i>Retirer / Transférer
                            </button>
                        @endif
                        @if($nombreEtudiants > 0 && auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.teach', 'identity.coordinate']))
                            <div class="dropdown">
                                <button type="button" class="cs-btn--outline" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-download"></i>Exporter
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header" style="font-size:.72rem;text-transform:uppercase;color:var(--cs-muted);">Liste d'appel</h6></li>
                                    <li><a class="dropdown-item" href="{{ route('esbtp.classes.liste-appel', ['classe' => $classe->id]) }}" target="_blank"><i class="fas fa-eye me-2"></i>Aperçu</a></li>
                                    <li><a class="dropdown-item" href="{{ route('esbtp.classes.liste-appel.pdf', ['classe' => $classe->id]) }}"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header" style="font-size:.72rem;text-transform:uppercase;color:var(--cs-muted);">Liste complète</h6></li>
                                    <li><a class="dropdown-item" href="{{ route('esbtp.classes.liste-complete', ['classe' => $classe->id]) }}" target="_blank"><i class="fas fa-eye me-2"></i>Aperçu</a></li>
                                    <li><a class="dropdown-item" href="{{ route('esbtp.classes.liste-complete.pdf', ['classe' => $classe->id]) }}"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                    <li><a class="dropdown-item" href="{{ route('esbtp.classes.liste-complete.excel', ['classe' => $classe->id]) }}"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div id="studentTableContainer">
                    @include('esbtp.classes.partials.student-table-rows', ['classe' => $classe])
                </div>
            </div>

            {{-- TAB MATIÈRES --}}
            <div class="cs-tab-panel" x-show="tab === 'matieres'">
                @php $isLmdTab = ($classe->systeme_academique ?? '') === 'LMD'; @endphp

                @if($isLmdTab && ($lmdMatieres ?? collect())->isNotEmpty())
                    {{-- MODE LMD : ECUEs depuis ESBTPPlanificationAcademique (source canonique) --}}
                    @php
                        // Grouper par Unite d'Enseignement (UE) si possible
                        $ecuesByUe = $lmdMatieres->groupBy(function ($row) {
                            $ue = optional($row['matiere'])->uniteEnseignement;
                            return $ue ? $ue->id : '__nope__';
                        });
                        $sommeCredits = $lmdMatieres->sum('credits_ects');
                        $sommeHeures = $lmdMatieres->sum('volume_horaire_total');
                    @endphp

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding:.85rem 1.1rem;background:linear-gradient(135deg,rgba(4,83,203,.06),rgba(59,125,219,.08));border:1px solid rgba(4,83,203,.2);border-radius:12px;">
                        <div style="display:flex;align-items:center;gap:.6rem;">
                            <i class="fas fa-university" style="color:#0453cb;"></i>
                            <div>
                                <strong style="color:#0f172a;font-size:.9rem;">Maquette pédagogique LMD</strong>
                                <div style="font-size:.75rem;color:#64748b;">{{ $ecuesByUe->count() }} unité{{ $ecuesByUe->count() > 1 ? 's' : '' }} · {{ $lmdMatieres->count() }} ECUE · {{ $sommeCredits }} crédits ECTS · {{ rtrim(rtrim(number_format($sommeHeures,1,',',''),'0'),',') }}h totales</div>
                            </div>
                        </div>
                        <a href="{{ route('esbtp.lmd.planning.index', array_filter(['parcours_id' => optional($classe->parcours)->id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => !empty($lmdSemestres) ? ($lmdSemestres[0] ?? null) : null])) }}" style="display:inline-flex;align-items:center;gap:.35rem;background:#0453cb;color:#fff;padding:.45rem .85rem;border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:600;">
                            <i class="fas fa-edit"></i> Configurer dans Planning LMD
                        </a>
                    </div>

                    <div class="cs-matieres-grid">
                        @foreach($lmdMatieres as $row)
                            @php
                                $matiere = $row['matiere'];
                                $ue = optional($matiere)->uniteEnseignement;
                            @endphp
                            <div class="cs-matiere-card" style="border-left:3px solid #0453cb;">
                                <div class="cs-matiere-head">
                                    <div style="flex:1;min-width:0;">
                                        <div class="cs-matiere-name">{{ $matiere->name }}</div>
                                        <div style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin-top:.2rem;">
                                            @if($matiere->code)<span class="cs-matiere-code">{{ $matiere->code }}</span>@endif
                                            @if($ue)
                                                <span style="background:rgba(4,83,203,.1);color:#0453cb;padding:.1rem .4rem;border-radius:5px;font-size:.65rem;font-weight:700;letter-spacing:.3px;">UE · {{ $ue->name }}</span>
                                            @endif
                                            @foreach($row['semestres'] as $sem)
                                                <span style="background:rgba(148,163,184,.18);color:#475569;padding:.1rem .4rem;border-radius:5px;font-size:.65rem;font-weight:700;">S{{ $sem }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.2rem;">
                                        <span class="cs-matiere-coef" title="Coefficient">×{{ number_format($row['coefficient'], 2) }}</span>
                                        @if($row['credits_ects'] > 0)
                                            <span style="background:#10b981;color:#fff;padding:.1rem .45rem;border-radius:5px;font-size:.65rem;font-weight:700;">{{ $row['credits_ects'] }} ECTS</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.65rem;font-size:.72rem;">
                                    @if($row['cm'] > 0)<span style="background:rgba(4,83,203,.08);color:#033a8e;padding:.15rem .45rem;border-radius:5px;font-weight:600;"><i class="fas fa-chalkboard-user" style="font-size:.65rem;"></i> CM {{ rtrim(rtrim(number_format($row['cm'],1,',',''),'0'),',') }}h</span>@endif
                                    @if($row['td'] > 0)<span style="background:rgba(4,83,203,.08);color:#033a8e;padding:.15rem .45rem;border-radius:5px;font-weight:600;"><i class="fas fa-pen-ruler" style="font-size:.65rem;"></i> TD {{ rtrim(rtrim(number_format($row['td'],1,',',''),'0'),',') }}h</span>@endif
                                    @if($row['tp'] > 0)<span style="background:rgba(4,83,203,.08);color:#033a8e;padding:.15rem .45rem;border-radius:5px;font-weight:600;"><i class="fas fa-flask-vial" style="font-size:.65rem;"></i> TP {{ rtrim(rtrim(number_format($row['tp'],1,',',''),'0'),',') }}h</span>@endif
                                    <span style="margin-left:auto;color:#64748b;font-weight:600;">{{ rtrim(rtrim(number_format($row['volume_horaire_total'],1,',',''),'0'),',') }}h total</span>
                                </div>
                                @if($matiere->description)
                                    <p class="cs-matiere-desc" style="margin-top:.55rem;">{{ \Illuminate\Support\Str::limit($matiere->description, 110) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif($isLmdTab)
                    {{-- LMD mais aucune planification configuree --}}
                    <div class="cs-warn-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            Aucune planification académique LMD configurée pour {{ optional($classe->parcours)->name ?: 'cette classe' }} ({{ $classe->niveau->name ?? 'niveau non défini' }}).
                            La maquette pédagogique se configure dans <a href="{{ route('esbtp.lmd.planning.index', array_filter(['parcours_id' => optional($classe->parcours)->id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => !empty($lmdSemestres) ? ($lmdSemestres[0] ?? null) : null])) }}" style="color:#92400e;text-decoration:underline;">Planning LMD</a> en définissant les UE, ECUE et volumes par semestre.
                        </div>
                    </div>
                @elseif(($combinationMatieres ?? collect())->isNotEmpty())
                    {{-- MODE BTS : pivot classique inchange --}}
                    <div class="cs-info-box" style="margin-top:0;margin-bottom:1rem;">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Les matières affichées proviennent du catalogue configuré pour cette filière / ce niveau et sont automatiquement prises en compte pour {{ $classe->name }}.
                        </div>
                    </div>

                    <div class="cs-matieres-grid">
                        @foreach($combinationMatieres as $matiere)
                            <div class="cs-matiere-card">
                                <div class="cs-matiere-head">
                                    <div>
                                        <div class="cs-matiere-name">{{ $matiere->name }}</div>
                                        <span class="cs-matiere-code">{{ $matiere->code }}</span>
                                    </div>
                                    <span class="cs-matiere-coef" title="Coefficient">×{{ number_format($matiere->classe_coefficient ?? $matiere->coefficient ?? $matiere->coefficient_default ?? 1, 2) }}</span>
                                </div>
                                @if($matiere->description)
                                    <p class="cs-matiere-desc">{{ \Illuminate\Support\Str::limit($matiere->description, 110) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="cs-warn-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            Aucune matière n'est encore configurée dans le catalogue pour cette filière / ce niveau.
                            @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']))
                                <br><a href="{{ route('esbtp.matieres.index') }}" style="color:#92400e;text-decoration:underline;">Compléter le paramétrage global</a>
                                ·
                                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" style="color:#92400e;text-decoration:underline;">Ajuster pour {{ $classe->name }}</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- TAB HEURES --}}
            <div class="cs-tab-panel" x-show="tab === 'heures'">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <div>
                        <h4 style="margin:0;font-size:.95rem;font-weight:700;color:var(--cs-text);">Suivi des heures par matière</h4>
                        <p style="margin:.15rem 0 0;font-size:.78rem;color:var(--cs-muted);">Heures planifiées vs réalisées — année courante</p>
                    </div>
                    <div class="cs-periode-toggle" id="classe-periode-form" data-url="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}">
                        @php
                            $semLabelA = 'Semestre 1'; $semLabelB = 'Semestre 2';
                            if (($classe->systeme_academique ?? '') === 'LMD' && !empty($lmdSemestres) && count($lmdSemestres) >= 2) {
                                $semLabelA = 'Semestre '.$lmdSemestres[0];
                                $semLabelB = 'Semestre '.$lmdSemestres[1];
                            }
                        @endphp
                        <button type="button" class="cs-periode-btn periode-btn {{ ($periode ?? 'annee') === 'semestre1' ? 'active' : '' }}" data-periode="semestre1">{{ $semLabelA }}</button>
                        <button type="button" class="cs-periode-btn periode-btn {{ ($periode ?? 'annee') === 'semestre2' ? 'active' : '' }}" data-periode="semestre2">{{ $semLabelB }}</button>
                        <button type="button" class="cs-periode-btn periode-btn {{ ($periode ?? 'annee') === 'annee' ? 'active' : '' }}" data-periode="annee">Année</button>
                    </div>
                </div>

                <div id="classe-planning-content">
                    @if(($classe->systeme_academique ?? '') === 'LMD')
                        @include('esbtp.classes.partials._suivi_heures_lmd', [
                            'classe' => $classe,
                            'planningMatiere' => $planningMatiere,
                            'lmdVolumeBudget' => $lmdVolumeBudget,
                            'lmdUesAvecEcues' => $lmdUesAvecEcues,
                            'lmdSemestres' => $lmdSemestres,
                            'periode' => $periode,
                            'kpiTaux' => $kpiTaux,
                        ])
                    @else
                    @php
                        $kpiTauxColor = $kpiTaux >= 70 ? 'var(--cs-success)' : ($kpiTaux >= 30 ? 'var(--cs-warn)' : 'var(--cs-danger)');
                    @endphp

                    <div class="cs-planning-kpis">
                        <div class="cs-planning-kpi" style="--kpi-color: var(--cs-primary);">
                            <div class="cs-planning-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="cs-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
                            <div class="cs-planning-kpi-label">Planifiées</div>
                        </div>
                        <div class="cs-planning-kpi" style="--kpi-color: var(--cs-success);">
                            <div class="cs-planning-kpi-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="cs-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_realisees'] ?? 0, 1) }}h</div>
                            <div class="cs-planning-kpi-label">Réalisées</div>
                        </div>
                        <div class="cs-planning-kpi" style="--kpi-color: var(--cs-accent);">
                            <div class="cs-planning-kpi-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="cs-planning-kpi-value">{{ $planningMatiere['stats']['nb_seances'] ?? 0 }}</div>
                            <div class="cs-planning-kpi-label">Séances</div>
                        </div>
                        <div class="cs-planning-kpi" style="--kpi-color: {{ $kpiTauxColor }};">
                            <div class="cs-planning-kpi-icon"><i class="fas fa-chart-pie"></i></div>
                            <div class="cs-planning-kpi-value" style="color: {{ $kpiTauxColor }};">{{ $kpiTaux }}%</div>
                            <div class="cs-planning-kpi-label">Taux</div>
                        </div>
                    </div>

                    @if(!empty($planningMatiere['matieres']) && $planningMatiere['matieres']->isNotEmpty())
                        @foreach($planningMatiere['matieres'] as $item)
                            @php
                                $pct = min($item['pourcentage_realise'] ?? 0, 100);
                                $barLevel = $pct >= 100 ? 'done' : ($pct >= 70 ? 'good' : ($pct >= 30 ? 'mid' : 'low'));
                                $badgeBg = $pct >= 100 ? 'var(--cs-success)' : ($pct >= 70 ? 'var(--cs-primary)' : ($pct >= 30 ? 'var(--cs-warn)' : 'var(--cs-danger)'));
                            @endphp
                            <div class="cs-subject-card">
                                <div>
                                    <div>
                                        <span class="cs-subject-name">{{ $item['matiere']->name ?? 'Matière inconnue' }}</span>
                                        @if(!empty($item['matiere']->code))
                                            <span class="cs-subject-code">{{ $item['matiere']->code }}</span>
                                        @endif
                                        @if(!$item['est_configure'])
                                            <span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:700;padding:.15rem .4rem;border-radius:5px;margin-left:.4rem;">Non configuré</span>
                                        @endif
                                    </div>
                                    <div class="cs-teachers" style="margin-top:.6rem;">
                                        @if(isset($item['enseignants']) && $item['enseignants']->isNotEmpty())
                                            @foreach($item['enseignants'] as $ens)
                                                @php $ensHue = hexdec(substr(md5($ens['name'] ?? (string)$ens['id']), 0, 4)) % 360; @endphp
                                                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $ens['id']]) }}" class="cs-teacher-chip">
                                                    <span class="cs-teacher-avatar" style="background: hsl({{ $ensHue }}, 55%, 45%);">{{ strtoupper(mb_substr($ens['name'], 0, 1)) }}</span>
                                                    <span>{{ $ens['name'] }}</span>
                                                    <span class="cs-teacher-hours">{{ number_format($ens['heures_realisees'], 1) }}h</span>
                                                </a>
                                            @endforeach
                                        @else
                                            <span style="color:var(--cs-muted);font-size:.78rem;">Aucun enseignant assigné</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;align-items:baseline;gap:.4rem;flex-wrap:wrap;">
                                        <span style="font-size:1.35rem;font-weight:800;color:var(--cs-text);">{{ number_format($item['heures_realisees'], 1) }}h</span>
                                        <span style="font-size:.78rem;color:var(--cs-muted);">réalisées /</span>
                                        <span style="font-size:.92rem;font-weight:600;color:var(--cs-muted);">{{ number_format($item['heures_planifiees'], 1) }}h</span>
                                    </div>
                                    <div class="cs-progress-track">
                                        <div class="cs-progress-fill cs-progress-fill--{{ $barLevel }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.3rem;">
                                        <small style="color:var(--cs-muted);font-size:.72rem;">
                                            <i class="fas fa-clock"></i> {{ number_format($item['heures_restantes'], 1) }}h restantes · {{ $item['nb_seances'] }} séances
                                        </small>
                                        <span class="cs-percent-badge" style="background:color-mix(in srgb, {{ $badgeBg }} 18%, transparent);color:{{ $badgeBg }};border:1px solid color-mix(in srgb, {{ $badgeBg }} 40%, transparent);">{{ $pct }}%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="cs-empty">
                            <div class="cs-empty-icon"><i class="fas fa-calendar-times"></i></div>
                            <div class="cs-empty-title">Aucune donnée d'emploi du temps</div>
                            <div class="cs-empty-text">Aucune séance trouvée pour cette classe sur la période sélectionnée.</div>
                        </div>
                    @endif
                    @endif {{-- end if LMD/BTS --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL AJOUTER ÉTUDIANTS --}}
<div class="modal fade" id="addStudentsModal" tabindex="-1" aria-labelledby="addStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header cs-modal-header">
                <h5 class="modal-title" id="addStudentsModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Ajouter des étudiants à {{ $classe->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="addStudentSearchInput" class="form-control" placeholder="Rechercher par nom, matricule, téléphone...">
                    </div>
                    <small class="text-muted">Recherche parmi les étudiants inscrits cette année mais pas dans cette classe.</small>
                </div>
                <div class="mb-3 p-2" style="background:var(--cs-surface);border-radius:8px;min-height:36px;" id="addSelectedTags">
                    <span class="text-muted">Aucun étudiant sélectionné</span>
                </div>
                <div id="addSearchLoading" style="display:none;" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted">Recherche en cours...</span>
                </div>
                <div class="list-group" id="addSearchResults" style="max-height:400px;overflow-y:auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="addSubmitBtn" disabled>
                    <i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-primary" id="addSelectedCount">0</span> étudiant(s)
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RETIRER / TRANSFÉRER --}}
<div class="modal fade" id="removeStudentsModal" tabindex="-1" aria-labelledby="removeStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header cs-modal-header">
                <h5 class="modal-title" id="removeStudentsModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Retirer / Transférer des étudiants
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-3" style="background:var(--cs-surface);border:1px solid var(--cs-border);border-radius:10px;">
                    <label for="destinationClasseId" class="form-label fw-bold">
                        <i class="fas fa-exchange-alt me-1 text-primary"></i>Classe de destination
                    </label>
                    <select id="destinationClasseId" class="form-select">
                        <option value="">-- Non affecté (retirer sans transférer) --</option>
                        @isset($autresClasses)
                            @php
                                $grouped = $autresClasses->groupBy(function($c) {
                                    return optional($c->filiere)->name ?? 'Sans filière';
                                });
                            @endphp
                            @foreach($grouped as $filiereName => $classes)
                                <optgroup label="{{ $filiereName }}">
                                    @foreach($classes as $autreClasse)
                                        <option value="{{ $autreClasse->id }}">
                                            {{ $autreClasse->name }} ({{ optional($autreClasse->niveau)->name ?? '' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @endisset
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="fas fa-info-circle me-1"></i>Si aucune classe n'est sélectionnée, les étudiants seront marqués « non affectés ».
                    </small>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="removeSelectAll">
                        <label class="form-check-label fw-bold" for="removeSelectAll">Tout sélectionner</label>
                    </div>
                    <div style="width:250px;">
                        <input type="text" id="removeSearchInput" class="form-control form-control-sm" placeholder="Filtrer la liste...">
                    </div>
                </div>

                <div class="list-group" id="removeStudentsList" style="max-height:400px;overflow-y:auto;">
                    <div class="text-muted text-center py-3">Chargement...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="removeSubmitBtn" disabled>
                    <i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-primary" id="removeSelectedCount">0</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CONFIRMATION (bouton rouge outline — Q3a a11y) --}}
<div class="modal fade" id="removeConfirmModal" tabindex="-1" aria-labelledby="removeConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header cs-modal-header">
                <h5 class="modal-title" id="removeConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmation requise
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="removeConfirmBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-outline-danger" id="removeConfirmBtn">
                    <i class="fas fa-archive me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

@include('esbtp.classes.partials.year-change-modal')

@endsection

@push('scripts')
<script>
// Planning toggle S1/S2/Année
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('classe-periode-form');
    const content = document.getElementById('classe-planning-content');
    if (!container || !content) return;

    const fetchPlanning = (periode) => {
        const baseUrl = container.dataset.url;
        const url = new URL(baseUrl);
        url.searchParams.set('periode', periode);
        return fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextContent = doc.querySelector('#classe-planning-content');
                if (nextContent) content.innerHTML = nextContent.innerHTML;
                const urlObj = new URL(window.location.href);
                urlObj.searchParams.set('periode', periode);
                urlObj.hash = 'heures';
                window.history.replaceState({}, '', urlObj.toString());
            })
            .catch(() => { window.location.href = url.toString(); });
    };

    container.addEventListener('click', (event) => {
        const button = event.target.closest('.periode-btn');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        container.querySelectorAll('.periode-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        fetchPlanning(button.dataset.periode);
    });
});

function showYearChangeInfo() {
    const el = document.getElementById('yearChangeModal');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
}

function showNotification(type, message) {
    const cls = type === 'success' ? 'cs-alert--success' : 'cs-alert--danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    const el = document.createElement('div');
    el.className = 'cs-alert ' + cls;
    el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:500px;box-shadow:0 4px 12px rgba(0,0,0,.15);';
    el.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + message + '</span>';
    document.body.appendChild(el);
    setTimeout(() => { el.remove(); }, 5000);
}
</script>

<script>
// Gestion étudiants : add / remove / transfer
$(document).ready(function() {

    function initStudentDataTable() {
        if (typeof $.fn.DataTable === 'undefined') return;
        if ($.fn.DataTable.isDataTable('#studentsDataTable')) {
            $('#studentsDataTable').DataTable().destroy();
        }
        if (document.getElementById('studentsDataTable')) {
            $('#studentsDataTable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "pageLength": 25,
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json" }
            });
        }
    }
    initStudentDataTable();

    function refreshStudentTable() {
        const container = document.getElementById('studentTableContainer');
        if (!container) return;
        container.style.opacity = '0.5';

        fetch("{{ route('esbtp.classes.student-table-html', ['classe' => $classe->id]) }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = data.html;
                container.style.opacity = '1';
                const subtitleEl = document.getElementById('studentCountSubtitle');
                if (subtitleEl) subtitleEl.textContent = data.count + ' étudiant(s)';
                initStudentDataTable();
                refreshRemoveModalStudentList(data.html);
            }
        })
        .catch(() => { container.style.opacity = '1'; });
    }

    function refreshRemoveModalStudentList(tableHtml) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(tableHtml, 'text/html');
        const rows = doc.querySelectorAll('tbody tr[data-etudiant-id]');
        const listContainer = document.getElementById('removeStudentsList');
        if (!listContainer) return;

        if (rows.length === 0) {
            listContainer.innerHTML = '<div class="text-muted text-center py-3">Aucun étudiant dans cette classe.</div>';
            return;
        }

        let html = '';
        rows.forEach(row => {
            const id = row.getAttribute('data-etudiant-id');
            const matricule = row.getAttribute('data-matricule') || '';
            const nom = row.getAttribute('data-nom') || '';
            html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor:pointer;">' +
                '<input type="checkbox" class="form-check-input remove-student-checkbox" value="' + id + '" style="margin:0;">' +
                '<span class="cs-matricule">' + matricule + '</span>' +
                '<span class="fw-semibold">' + nom + '</span>' +
                '</label>';
        });
        listContainer.innerHTML = html;
        updateRemoveSelectedCount();
    }

    // AJOUTER ÉTUDIANTS
    let addSearchTimer = null;
    const addSelectedStudents = {};

    document.getElementById('addStudentSearchInput').addEventListener('input', function() {
        clearTimeout(addSearchTimer);
        const query = this.value;
        addSearchTimer = setTimeout(() => searchAvailableStudents(query), 300);
    });

    function searchAvailableStudents(query) {
        const resultsContainer = document.getElementById('addSearchResults');
        const loadingEl = document.getElementById('addSearchLoading');
        loadingEl.style.display = 'block';
        resultsContainer.innerHTML = '';

        fetch("{{ route('esbtp.classes.search-available-students', ['classe' => $classe->id]) }}?q=" + encodeURIComponent(query), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            loadingEl.style.display = 'none';
            if (!data.success) {
                resultsContainer.innerHTML = '<div class="text-danger p-3">' + (data.message || 'Erreur') + '</div>';
                return;
            }
            if (data.etudiants.length === 0) {
                resultsContainer.innerHTML = '<div class="text-muted text-center py-3"><i class="fas fa-search me-2"></i>Aucun étudiant trouvé.</div>';
                return;
            }
            let html = '';
            data.etudiants.forEach(etudiant => {
                const isChecked = addSelectedStudents[etudiant.id] ? 'checked' : '';
                html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor:pointer;">' +
                    '<input type="checkbox" class="form-check-input add-student-checkbox" value="' + etudiant.id + '" ' + isChecked + ' style="margin:0;">' +
                    '<div class="flex-grow-1">' +
                    '<div class="d-flex align-items-center gap-2">' +
                    '<span class="cs-matricule">' + (etudiant.matricule || 'N/A') + '</span>' +
                    '<strong>' + etudiant.nom_complet + '</strong>' +
                    '</div>' +
                    '<small class="text-muted">Classe actuelle : ' + etudiant.classe_actuelle + '</small>' +
                    '</div>' +
                    '</label>';
            });
            resultsContainer.innerHTML = html;
        })
        .catch(() => {
            loadingEl.style.display = 'none';
            resultsContainer.innerHTML = '<div class="text-danger p-3">Erreur de connexion.</div>';
        });
    }

    document.getElementById('addSearchResults').addEventListener('change', function(e) {
        if (e.target.classList.contains('add-student-checkbox')) {
            const id = e.target.value;
            const label = e.target.closest('label');
            const nameEl = label.querySelector('strong');
            if (e.target.checked) {
                addSelectedStudents[id] = nameEl ? nameEl.textContent : 'Étudiant ' + id;
            } else {
                delete addSelectedStudents[id];
            }
            updateAddSelectedCount();
        }
    });

    function updateAddSelectedCount() {
        const count = Object.keys(addSelectedStudents).length;
        const el = document.getElementById('addSelectedCount');
        if (el) el.textContent = count;
        document.getElementById('addSubmitBtn').disabled = count === 0;

        const tagsContainer = document.getElementById('addSelectedTags');
        if (count === 0) {
            tagsContainer.innerHTML = '<span class="text-muted">Aucun étudiant sélectionné</span>';
        } else {
            let html = '';
            for (const id in addSelectedStudents) {
                html += '<span class="badge bg-primary me-1 mb-1" style="font-size:.8rem;">' +
                    addSelectedStudents[id] +
                    ' <i class="fas fa-times ms-1" style="cursor:pointer;" data-remove-id="' + id + '"></i>' +
                    '</span>';
            }
            tagsContainer.innerHTML = html;
        }
    }

    document.getElementById('addSelectedTags').addEventListener('click', function(e) {
        const removeBtn = e.target.closest('[data-remove-id]');
        if (removeBtn) {
            const id = removeBtn.getAttribute('data-remove-id');
            delete addSelectedStudents[id];
            const checkbox = document.querySelector('.add-student-checkbox[value="' + id + '"]');
            if (checkbox) checkbox.checked = false;
            updateAddSelectedCount();
        }
    });

    document.getElementById('addSubmitBtn').addEventListener('click', function() {
        const ids = Object.keys(addSelectedStudents).map(Number);
        if (ids.length === 0) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ajout en cours...';

        fetch("{{ route('esbtp.classes.add-students', ['classe' => $classe->id]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ etudiant_ids: ids })
        })
        .then(r => r.ok ? r.json() : r.text().then(t => { try { return JSON.parse(t); } catch(e) { throw new Error('HTTP ' + r.status); } }))
        .then(data => {
            if (data.success) {
                const addModal = bootstrap.Modal.getInstance(document.getElementById('addStudentsModal'));
                if (addModal) addModal.hide();
                Object.keys(addSelectedStudents).forEach(k => delete addSelectedStudents[k]);
                updateAddSelectedCount();
                document.getElementById('addStudentSearchInput').value = '';
                document.getElementById('addSearchResults').innerHTML = '';
                refreshStudentTable();
                showNotification('success', data.message);
            } else {
                showNotification('danger', data.message || 'Erreur lors de l\'ajout.');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-primary" id="addSelectedCount">0</span> étudiant(s)';
        })
        .catch(err => {
            console.error('Erreur ajout étudiants:', err);
            showNotification('danger', err.message || 'Erreur de connexion.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-primary" id="addSelectedCount">0</span> étudiant(s)';
        });
    });

    document.getElementById('addStudentsModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('addStudentSearchInput').focus();
        if (document.getElementById('addSearchResults').innerHTML === '') {
            searchAvailableStudents('');
        }
    });

    // RETIRER / TRANSFÉRER
    document.getElementById('removeStudentsModal').addEventListener('shown.bs.modal', function() {
        populateRemoveStudentsList();
    });

    function populateRemoveStudentsList() {
        const tableRows = document.querySelectorAll('#studentTableContainer tr[data-etudiant-id]');
        const listContainer = document.getElementById('removeStudentsList');

        if (tableRows.length === 0) {
            listContainer.innerHTML = '<div class="text-muted text-center py-3">Aucun étudiant dans cette classe.</div>';
            return;
        }

        let html = '';
        tableRows.forEach(row => {
            const id = row.getAttribute('data-etudiant-id');
            const matricule = row.getAttribute('data-matricule') || '';
            const nom = row.getAttribute('data-nom') || '';
            html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor:pointer;">' +
                '<input type="checkbox" class="form-check-input remove-student-checkbox" value="' + id + '" style="margin:0;">' +
                '<span class="cs-matricule">' + matricule + '</span>' +
                '<span class="fw-semibold">' + nom + '</span>' +
                '</label>';
        });
        listContainer.innerHTML = html;
        updateRemoveSelectedCount();
    }

    document.getElementById('removeSelectAll').addEventListener('change', function() {
        document.querySelectorAll('.remove-student-checkbox').forEach(cb => cb.checked = this.checked);
        updateRemoveSelectedCount();
    });

    document.getElementById('removeSearchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#removeStudentsList .list-group-item').forEach(item => {
            item.classList.toggle('d-none', !item.textContent.toLowerCase().includes(query));
        });
    });

    document.getElementById('removeStudentsList').addEventListener('change', updateRemoveSelectedCount);

    function updateRemoveSelectedCount() {
        const count = document.querySelectorAll('.remove-student-checkbox:checked').length;
        const el = document.getElementById('removeSelectedCount');
        if (el) el.textContent = count;
        const btn = document.getElementById('removeSubmitBtn');
        if (btn) btn.disabled = count === 0;
    }

    document.getElementById('removeSubmitBtn').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.remove-student-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
        if (ids.length === 0) return;

        const destinationSelect = document.getElementById('destinationClasseId');
        const destinationClasseId = destinationSelect.value || null;
        const destinationName = destinationClasseId
            ? destinationSelect.options[destinationSelect.selectedIndex].text.trim()
            : null;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Vérification...';

        fetch("{{ route('esbtp.classes.check-student-data', ['classe' => $classe->id]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ etudiant_ids: ids })
        })
        .then(r => r.json())
        .then(checkData => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-primary" id="removeSelectedCount">' + ids.length + '</span>';
            showRemoveConfirmModal(checkData, ids, destinationClasseId, destinationName);
        })
        .catch(err => {
            console.error('Erreur vérification:', err);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-primary" id="removeSelectedCount">' + ids.length + '</span>';
            showNotification('danger', 'Impossible de vérifier les données.');
        });
    });

    function showRemoveConfirmModal(checkData, ids, destinationClasseId, destinationName) {
        const actionText = destinationName
            ? 'Transférer vers <strong>' + destinationName + '</strong>'
            : 'Retirer de la classe <strong>(non affectés)</strong>';

        let warningHtml = '';
        if (checkData.has_any_data) {
            warningHtml = '<div class="cs-warn-box" style="margin-bottom:1rem;">' +
                '<i class="fas fa-exclamation-triangle"></i>' +
                '<div><strong>Données académiques détectées.</strong> Les notes, résultats et bulletins de ces étudiants dans cette classe seront <strong>archivés</strong> et ne seront plus visibles (moyennes, classements, bulletins).</div>' +
                '</div>';
        }

        let tableHtml = '<table class="table table-sm mb-0" style="font-size:.85rem;">' +
            '<thead><tr><th>Étudiant</th><th class="text-center">Notes</th><th class="text-center">Résultats</th><th class="text-center">Bulletins</th></tr></thead><tbody>';

        checkData.students.forEach(s => {
            const rowClass = s.has_data ? 'table-warning' : '';
            tableHtml += '<tr class="' + rowClass + '">' +
                '<td>' + s.nom + '</td>' +
                '<td class="text-center">' + (s.notes_count > 0 ? '<span class="badge bg-danger">' + s.notes_count + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="text-center">' + (s.resultats_count > 0 ? '<span class="badge bg-danger">' + s.resultats_count + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="text-center">' + (s.bulletins_count > 0 ? '<span class="badge bg-danger">' + s.bulletins_count + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '</tr>';
        });
        tableHtml += '</tbody></table>';

        document.getElementById('removeConfirmBody').innerHTML =
            '<p class="mb-2">' + actionText + ' — <strong>' + ids.length + ' étudiant(s)</strong></p>' +
            warningHtml + tableHtml;

        const confirmBtn = document.getElementById('removeConfirmBtn');
        confirmBtn.dataset.ids = JSON.stringify(ids);
        confirmBtn.dataset.destinationClasseId = destinationClasseId || '';

        if (checkData.has_any_data) {
            confirmBtn.className = 'btn btn-outline-danger';
            confirmBtn.innerHTML = '<i class="fas fa-archive me-1"></i>Confirmer (archiver)';
        } else {
            confirmBtn.className = 'btn btn-primary';
            confirmBtn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Confirmer le transfert';
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('removeConfirmModal')).show();
    }

    document.getElementById('removeConfirmBtn').addEventListener('click', function() {
        const ids = JSON.parse(this.dataset.ids);
        const destinationClasseId = this.dataset.destinationClasseId || null;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';

        const body = { etudiant_ids: ids };
        if (destinationClasseId) body.destination_classe_id = parseInt(destinationClasseId);

        fetch("{{ route('esbtp.classes.remove-students', ['classe' => $classe->id]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        })
        .then(r => r.ok ? r.json() : r.text().then(t => { try { return JSON.parse(t); } catch(e) { throw new Error('HTTP ' + r.status); } }))
        .then(data => {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('removeConfirmModal'));
            if (confirmModal) confirmModal.hide();

            if (data.success) {
                const removeModal = bootstrap.Modal.getInstance(document.getElementById('removeStudentsModal'));
                if (removeModal) removeModal.hide();
                document.getElementById('removeSelectAll').checked = false;
                document.getElementById('removeSearchInput').value = '';
                refreshStudentTable();
                showNotification('success', data.message);
            } else {
                showNotification('danger', data.message || 'Erreur lors du retrait.');
            }
            btn.disabled = false;
        })
        .catch(err => {
            console.error('Erreur retrait étudiants:', err);
            showNotification('danger', err.message || 'Erreur de connexion.');
            btn.disabled = false;
        });
    });
});
</script>
@endpush
