@extends('layouts.app')

@section('title', 'Dashboard Comptabilité')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>

/* ── Typographie financière ── */
body, .filters-bar, .kpi-label, .filter-label, .filter-select {
    font-family: 'DM Sans', sans-serif;
}
.kpi-value,
#kpi-total-due, #kpi-total-paid, #kpi-overdue, #kpi-pending, #kpi-taux {
    font-family: 'JetBrains Mono', monospace !important;
    letter-spacing: -.02em;
}

/* ── Animations d'entrée réduites (dc-*) ── */
@keyframes dc-fade-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.dash-hero   { animation: dc-fade-in .2s ease both; }
.filters-bar { animation: dc-fade-in .2s ease .05s both; }
.kpi-strip .kpi-card { animation: dc-fade-in .2s ease .1s both; }

/* Respect motion preferences (WCAG 2.2) */
@media (prefers-reduced-motion: reduce) {
    .dash-hero, .filters-bar, .kpi-strip .kpi-card,
    .chart-card, .bottom-row {
        animation: none !important;
    }
    .aging-seg { transition: none !important; }
}

/* ── KPI cards — precision touch ── */
.kpi-card {
    border-top: 3px solid transparent;
    transition: box-shadow .2s, transform .2s, border-color .2s;
}
.kpi-card:nth-child(1) { border-top-color: #0453cb; }
.kpi-card:nth-child(2) { border-top-color: #10b981; }
.kpi-card:nth-child(3) { border-top-color: #ef4444; }
.kpi-card:nth-child(4) { border-top-color: #3b82f6; }
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.10); }

/* ── Chart cards ── */
.chart-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 1px 12px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.05);
    animation: dc-fade-in .5s ease .4s both;
}

/* ── Aging bar segments ── */
.aging-bar-track {
    display: flex;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin: 10px 0;
    background: #f1f5f9;
}
.aging-seg {
    height: 100%;
    transition: width .6s cubic-bezier(.22,.68,0,1.2);
}
.aging-seg-recent  { background: #10b981; }
.aging-seg-moderate{ background: #f59e0b; }
.aging-seg-serious { background: #f97316; }
.aging-seg-critical{ background: #ef4444; }

/* ── Bottom section fade-in ── */
.bottom-row { animation: dc-fade-in .25s ease .15s both; }

/* ── Hero premium (dc-*) — monochrome bleu, pas d'orbs décoratives ── */
.dash-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 16px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.dash-hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.dash-hero-subtitle {
    color: rgba(255,255,255,.6);
    font-size: .92rem;
    margin: 0;
}
.dash-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(16,185,129,.18);
    color: #10b981;
    border: 1px solid rgba(16,185,129,.35);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: .78rem;
    font-weight: 600;
    vertical-align: middle;
}
.dash-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .15s, transform .15s;
    white-space: nowrap;
}
.hero-btn:hover { opacity: .9; transform: translateY(-1px); }
.hero-btn-primary { background: #0453cb; color: #fff; }
.hero-btn-outline { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25); }

/* ── Filtres AJAX ── */
.filters-bar {
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 8px rgba(0,0,0,.05);
    border: 1px solid rgba(4,83,203,.07);
    display: flex;
    align-items: stretch;
    gap: 0;
}
.filters-bar-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: rgba(4,83,203,.08);
    color: #0453cb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
    align-self: flex-end;
    margin-bottom: 2px;
    margin-right: 16px;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1 1 0;
    min-width: 0;
    padding: 0 16px;
    border-right: 1px solid #eef2f7;
}
.filter-group:first-of-type { padding-left: 0; }
.filter-group:last-of-type { border-right: none; }
.filter-label {
    font-size: .67rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .06em;
    line-height: 1;
    white-space: nowrap;
}
.filter-select {
    appearance: none;
    -webkit-appearance: none;
    background-color: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 8px 32px 8px 12px;
    font-size: .875rem;
    font-weight: 500;
    color: #1e293b;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%234b5563' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    width: 100%;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}
.filter-select:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.08);
}
.filter-select:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1), 0 2px 4px rgba(15, 23, 42, 0.08);
    outline: none;
}
.filter-select.has-value {
    border-color: #0453cb;
    color: #0453cb;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(4, 83, 203, 0.08);
}
.filters-bar-actions {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
    padding-left: 20px;
    padding-bottom: 2px;
}
.filter-active-pill {
    display: none;
    align-items: center;
    gap: 5px;
    font-size: .72rem;
    font-weight: 700;
    color: #0453cb;
    padding: 5px 10px;
    background: rgba(4,83,203,.09);
    border-radius: 20px;
    border: 1px solid rgba(4,83,203,.15);
    white-space: nowrap;
    line-height: 1;
}
.filter-active-pill.active { display: inline-flex; }
.filter-active-pill i { font-size: .55rem; }
.filter-reset-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .78rem;
    font-weight: 600;
    color: #94a3b8;
    padding: 5px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    cursor: pointer;
    transition: background .12s, color .12s, border-color .12s;
    white-space: nowrap;
    line-height: 1;
}
.filter-reset-btn:hover {
    background: #fff0f0;
    color: #e53e3e;
    border-color: rgba(229,62,62,.25);
}
.filter-default-hint {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .67rem; font-weight: 600; color: #94a3b8;
    margin-top: 4px; line-height: 1;
    transition: opacity .15s;
}
.filter-default-hint i { font-size: .45rem; color: #10b981; }
.filter-default-hint.hidden { display: none; }
@media (max-width: 768px) {
    .filters-bar { flex-wrap: wrap; gap: 12px; padding: 14px 16px; }
    .filters-bar-icon { display: none; }
    .filter-group { flex: 1 1 40%; padding: 0; border-right: none; }
    .filters-bar-actions { padding-left: 0; width: 100%; justify-content: flex-end; }
    .filter-select { border-bottom: 1px solid #e2e8f0; border-radius: 8px; background-color: #f8fafc; padding: 7px 28px 7px 10px; }
    .filter-select.has-value { background-color: #f0f5ff; }
}

/* ── Action Feed (Aujourd'hui) ── */
.dc-feed {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 18px 16px;
    margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.dc-feed-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 12px; flex-wrap: wrap;
}
.dc-feed-title {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: .82rem; font-weight: 700; color: #1e293b;
    letter-spacing: -.01em;
}
.dc-feed-title i { color: #0453cb; font-size: .9rem; }
.dc-feed-date { font-weight: 500; color: #64748b; font-size: .78rem; }
.dc-feed-allclear {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,.10); color: #059669;
    padding: 4px 10px; border-radius: 999px;
    font-size: .72rem; font-weight: 600;
}
.dc-feed-cards {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
}
@media (max-width: 768px) { .dc-feed-cards { grid-template-columns: 1fr; } }
.dc-feed-card {
    display: flex; align-items: center; gap: 12px;
    background: #fff;
    border: 1px solid #e2e8f0; border-radius: 11px;
    padding: 10px 14px;
    text-decoration: none;
    transition: all .15s ease;
}
.dc-feed-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 4px 14px rgba(4,83,203,.08);
    transform: translateY(-1px);
    text-decoration: none;
}
.dc-feed-card.is-active {
    background: linear-gradient(135deg, #fff 0%, #fffbeb 100%);
    border-color: #fde68a;
}
.dc-feed-card.is-active#dc-feed-card-overdue {
    background: linear-gradient(135deg, #fff 0%, #fef2f2 100%);
    border-color: #fecaca;
}
.dc-feed-card-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0;
}
.dc-feed-card-body { flex: 1; min-width: 0; }
.dc-feed-card-value {
    font-size: 1.15rem; font-weight: 800; color: #0f172a;
    line-height: 1; letter-spacing: -.02em;
}
.dc-feed-card-label {
    font-size: .72rem; font-weight: 600; color: #64748b;
    margin-top: 2px;
}
.dc-feed-card-cta {
    color: #94a3b8; font-size: .8rem;
    transition: transform .15s ease, color .15s ease;
}
.dc-feed-card:hover .dc-feed-card-cta {
    color: #0453cb; transform: translateX(2px);
}

/* ── KPI Strip ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 992px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .kpi-strip { grid-template-columns: 1fr; } }

.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    gap: 0;
    transition: box-shadow .15s;
}
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.kpi-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex: 1;
}
.kpi-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
    flex-shrink: 0;
    margin-top: 2px;
}
.kpi-text { flex: 1; min-width: 0; }
.kpi-value {
    font-size: 1.22rem;
    font-weight: 700;
    line-height: 1.15;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.kpi-label {
    font-size: .80rem;
    color: #64748b;
    font-weight: 500;
    margin-top: 3px;
    line-height: 1.3;
}
.kpi-footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}
.kpi-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .72rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    width: fit-content;
}

/* ── Aging Card ── */
.aging-card {
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.aging-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 20px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.aging-title {
    font-size: .88rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: -.01em;
}
.aging-title i { color: #5e91de; font-size: .8rem; }

.aging-subtitle {
    margin-top: 3px;
    font-size: .78rem;
    color: #64748b;
}

.aging-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
    background: linear-gradient(135deg,#0453cb,#5e91de);
    color: #fff;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity .15s, transform .15s;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.aging-cta-btn:hover { opacity: .9; transform: translateY(-1px); color:#fff; }

.aging-rows {
    flex: 1;
    padding: 8px 0;
}

.aging-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    transition: background .12s;
    cursor: default;
}
.aging-row:hover { background: rgba(4,83,203,.03); }

.aging-row-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 165px;
    min-width: 0;
}

.aging-icon-badge {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
}

.aging-row-labels {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.aging-row-range {
    font-size: .80rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -.01em;
    line-height: 1.2;
}
.aging-row-sub {
    font-size: .70rem;
    font-weight: 600;
    line-height: 1.2;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.aging-row-bar-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.aging-bar-track {
    height: 6px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}
.aging-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .4s cubic-bezier(.4,0,.2,1);
    min-width: 3px;
}

.aging-amount {
    font-size: .79rem;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1;
}

.aging-count-bubble {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 46px;
    padding: 5px 4px;
    border-radius: 8px;
    text-align: center;
}
.aging-count {
    font-size: .95rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.02em;
}
.aging-count-label {
    font-size: .60rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    opacity: .75;
    line-height: 1;
}

.aging-footer {
    padding: 12px 20px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.aging-footer-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .78rem;
    font-weight: 600;
    color: #0453cb;
    text-decoration: none;
    transition: gap .15s;
}
.aging-footer-link:hover { gap: 8px; color: #0453cb; }

/* ── Chart ── */
.chart-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.06);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.chart-canvas-wrap { flex: 1; min-height: 180px; position: relative; }

/* ── Paiements en attente — redesign feed cards ── */
.pending-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.pending-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 14px;
    border-bottom: 1px solid #f1f5f9;
}
.pending-card-title {
    font-size: .88rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pending-count-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 11px;
    background: rgba(4,83,203,.1);
    color: #0453cb;
    font-size: .72rem;
    font-weight: 700;
}
.pending-see-all {
    font-size: .78rem;
    font-weight: 600;
    color: #5e91de;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid rgba(94,145,222,.25);
    transition: background .12s, color .12s;
}
.pending-see-all:hover { background: rgba(94,145,222,.08); color: #0453cb; }
.pending-feed { flex: 1; overflow-y: auto; max-height: 340px; }
.pending-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f8fafc;
    transition: background .1s;
}
.pending-item:last-child { border-bottom: none; }
.pending-item:hover { background: #fafbfd; }
.pending-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    letter-spacing: .5px;
}
.pending-info { flex: 1; min-width: 0; }
.pending-name {
    font-size: .84rem;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pending-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
    flex-wrap: wrap;
}
.pending-cat {
    font-size: .72rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
}
.pending-date-dot {
    width: 3px; height: 3px;
    border-radius: 50%;
    background: #cbd5e1;
    flex-shrink: 0;
}
.pending-date {
    font-size: .72rem;
    color: #94a3b8;
    white-space: nowrap;
}
.pending-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    flex-shrink: 0;
}
.pending-amount {
    font-size: .88rem;
    font-weight: 700;
    color: #0453cb;
    white-space: nowrap;
}
.pending-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .7rem;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 5px;
    background: rgba(4,83,203,.09);
    color: #0453cb;
    text-decoration: none;
    border: 1px solid rgba(4,83,203,.15);
    transition: background .12s;
}
.pending-action-btn:hover { background: rgba(4,83,203,.16); color: #0453cb; }
.pending-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    gap: 10px;
    color: #64748b;
}
.pending-empty-icon {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: rgba(16,185,129,.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #10b981;
}

/* ── Quick actions — redesign premium tiles ── */
.qa-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.qa-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 14px;
    border-radius: 10px;
    text-decoration: none;
    background: #fafbfc;
    border: 1px solid #f0f4f8;
    transition: background .12s, box-shadow .12s, transform .12s;
    position: relative;
    overflow: hidden;
}
.qa-tile::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 60%, rgba(4,83,203,.04) 100%);
    pointer-events: none;
}
.qa-tile:hover {
    background: #fff;
    box-shadow: 0 4px 16px rgba(4,83,203,.1);
    border-color: rgba(4,83,203,.18);
    transform: translateY(-2px);
}
.qa-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.qa-label {
    font-size: .8rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.2;
}
.qa-sublabel {
    font-size: .7rem;
    color: #94a3b8;
    font-weight: 400;
    margin-top: 1px;
}
.qa-arrow {
    margin-left: auto;
    font-size: .65rem;
    color: #cbd5e1;
    transition: color .12s, transform .12s;
}
.qa-tile:hover .qa-arrow { color: #0453cb; transform: translateX(2px); }

/* ── Loading overlay ── */
#dash-loading {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
#dash-loading.show { display: flex; }
.spinner-ring {
    width: 40px; height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #0453cb;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Taux de recouvrement bar ── */
.recovery-bar-wrap { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: 4px; }
.recovery-bar { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #10b981, #0453cb); transition: width .6s ease; }
</style>
@endpush

@push('styles')
<style>
/* Écran mobile du tableau de bord comptable (shell m-*, préfixe dm-). Le socle
   mobile-shell.css porte les classes m-* ; ici seulement ce qui est propre à l'écran. */
.dm-contenu { display: grid; gap: 14px; }
.dm-screen .m-kpi .d { min-height: 14px; }
.dm-screen .m-row .tt b { white-space: normal; line-height: 1.25; }
.dm-axe { display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; margin-top: -4px; }
.dm-vide, .dm-total { margin: 0; font-size: 12.5px; color: #64748b; }
.dm-total b { color: #0f172a; font-variant-numeric: tabular-nums; }
.dm-lbl { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
.dm-opt { max-height: 40vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.dm-opt label { position: relative; }
.dm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
</style>
@endpush

@section('content')
@php
    // Shell mobile (issue #963, maquette S['comptable:dash']) : le DOM de bureau
    // reste dans .m-only-desktop, l'écran m-* vit à côté, sous 992px.
    $dmShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $dmUser = auth()->user();

    // Santé réconciliation : un seul snapshot, partagé entre le widget de bureau
    // et la ligne « À faire » du mobile (le widget le recalcule s'il est absent).
    $dmRecMetrics = ($dmUser && $dmUser->can('comptabilite.reconciliation.view'))
        ? app(\App\Domain\Comptabilite\Reconciliation\Services\ReconciliationMetricsService::class)->snapshot()
        : null;
@endphp

{{-- Loading overlay --}}
<div id="dash-loading">
    <div class="spinner-ring"></div>
</div>

{{-- Enveloppe de bascule : .dashboard-acasi garde son display:flex, la bascule
     mobile/bureau se joue sur ce bloc parent (mobile-shell.css charge après dashboard-moderne.css). --}}
<div class="{{ $dmShell ? 'm-only-desktop' : '' }}" id="dm-bureau">
<div class="dashboard-acasi">
    <div class="main-content">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ── HERO ── --}}
        <div class="dash-hero">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <nav aria-label="breadcrumb" class="mb-2">
                        <ol class="breadcrumb mb-0" style="font-size:.78rem;opacity:.6;">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white text-decoration-none">Accueil</a></li>
                            <li class="breadcrumb-item text-white active">Dashboard Comptabilité</li>
                        </ol>
                    </nav>
                    <h1 class="dash-hero-title">
                        <i class="fas fa-chart-line" style="color:#5e91de;"></i>
                        Dashboard Comptabilité
                        @if($anneeActive)
                        <span class="dash-hero-pill">
                            <i class="fas fa-circle" style="font-size:.5rem;"></i>
                            {{ $anneeActive->name ?? $anneeActive->libelle }} — en cours
                        </span>
                        @endif
                    </h1>
                    <p class="dash-hero-subtitle">
                        Vue financière · Tous frais & paiements
                        @if($annee && $annee->id !== ($anneeActive->id ?? null))
                        <span style="color:#5e91de;"> · Filtré sur {{ $annee->name ?? $annee->libelle }}</span>
                        @endif
                    </p>
                </div>
                <div class="dash-hero-actions">
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="hero-btn hero-btn-outline">
                        <i class="fas fa-bell"></i>
                        <span class="d-none d-sm-inline">Relances</span>
                    </a>
                    <a href="{{ route('esbtp.paiements.index') }}" class="hero-btn hero-btn-primary">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="d-none d-sm-inline">Paiements</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- PR6 Widget santé Réconciliation Caisse --}}
        @include('esbtp.comptabilite.partials._reconciliation_health_widget', ['recMetrics' => $dmRecMetrics])

        {{-- ── FILTRES AJAX ── --}}
        <div class="filters-bar">
            <div class="filters-bar-icon">
                <i class="fas fa-sliders-h"></i>
            </div>

            <div class="filter-group">
                <label class="filter-label" for="f-annee">Année</label>
                <select id="f-annee" class="filter-select {{ ($annee) ? 'has-value' : '' }}">
                    <option value="">Toutes les années</option>
                    @foreach($annees as $a)
                    <option value="{{ $a->id }}"
                        {{ $annee && $annee->id == $a->id ? 'selected' : '' }}>
                        {{ $a->name ?? $a->libelle }}@if($a->is_current) ✦@endif
                    </option>
                    @endforeach
                </select>
                @if($anneeActive)
                <span class="filter-default-hint" id="filter-annee-hint">
                    <i class="fas fa-circle-dot"></i>
                    {{ $anneeActive->name ?? $anneeActive->libelle }} par défaut
                </span>
                @endif
            </div>

            <div class="filters-bar-sep"></div>

            <div class="filter-group">
                <label class="filter-label" for="f-filiere">Filière</label>
                <select id="f-filiere" class="filter-select {{ request('filiere') ? 'has-value' : '' }}">
                    <option value="">Toutes les filières</option>
                    @foreach($filieres as $f)
                    <option value="{{ $f->id }}" {{ request('filiere') == $f->id ? 'selected' : '' }}>
                        {{ $f->name ?? $f->nom }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="filters-bar-sep"></div>

            <div class="filter-group">
                <label class="filter-label" for="f-classe">Classe</label>
                <select id="f-classe" class="filter-select {{ request('classe') ? 'has-value' : '' }}">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('classe') == $c->id ? 'selected' : '' }}>
                        {{ $c->name ?? $c->nom }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="filters-bar-actions">
                <span class="filter-active-pill" id="filter-active-badge">
                    <i class="fas fa-circle"></i>
                    Filtres actifs
                </span>
                <button type="button" id="btn-reset-filters" class="filter-reset-btn">
                    <i class="fas fa-times"></i>Réinitialiser
                </button>
            </div>
        </div>

        {{-- ── ACTION FEED ── (Aujourd'hui : N à valider · M en retard · K validés) --}}
        @php
            $hasActions = ($countToValidate ?? 0) > 0 || ($countOverdueTotal ?? 0) > 0;
            $todayLabel = \Carbon\Carbon::today()->translatedFormat('l j F');
        @endphp
        <div class="dc-feed" id="dc-action-feed">
            <div class="dc-feed-head">
                <div class="dc-feed-title">
                    <i class="fas fa-bolt"></i>
                    <span>Aujourd'hui</span>
                    <span class="dc-feed-date">· {{ ucfirst($todayLabel) }}</span>
                </div>
                @if(!$hasActions)
                <span class="dc-feed-allclear">
                    <i class="fas fa-check-circle"></i>
                    Tout est à jour, rien d'urgent
                </span>
                @endif
            </div>
            <div class="dc-feed-cards">
                {{-- À VALIDER --}}
                @can('paiements.validate')
                <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}"
                   class="dc-feed-card {{ ($countToValidate ?? 0) > 0 ? 'is-active' : '' }}"
                   id="dc-feed-card-validate">
                    <div class="dc-feed-card-icon" style="background:rgba(245,158,11,.12);color:#d97706;">
                        <i class="fas fa-stamp"></i>
                    </div>
                    <div class="dc-feed-card-body">
                        <div class="dc-feed-card-value" id="dc-feed-validate-count">{{ $countToValidate ?? 0 }}</div>
                        <div class="dc-feed-card-label">à valider</div>
                    </div>
                    <div class="dc-feed-card-cta">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
                @endcan

                {{-- EN RETARD --}}
                @can('comptabilite.relances.send')
                <a href="{{ route('esbtp.comptabilite.relances.index') }}"
                   class="dc-feed-card {{ ($countOverdueTotal ?? 0) > 0 ? 'is-active' : '' }}"
                   id="dc-feed-card-overdue">
                    <div class="dc-feed-card-icon" style="background:rgba(220,38,38,.10);color:#dc2626;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="dc-feed-card-body">
                        <div class="dc-feed-card-value" id="dc-feed-overdue-count">{{ $countOverdueTotal ?? 0 }}</div>
                        <div class="dc-feed-card-label">étudiant{{ ($countOverdueTotal ?? 0) > 1 ? 's' : '' }} en retard</div>
                    </div>
                    <div class="dc-feed-card-cta">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
                @endcan

                {{-- VALIDÉS AUJOURD'HUI (rassurant) --}}
                @php $todayYmd = \Carbon\Carbon::today()->format('Y-m-d'); @endphp
                <a href="{{ route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $todayYmd, 'date_fin' => $todayYmd]) }}"
                   class="dc-feed-card dc-feed-card--positive"
                   id="dc-feed-card-validated">
                    <div class="dc-feed-card-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="dc-feed-card-body">
                        <div class="dc-feed-card-value" id="dc-feed-validated-count">{{ $countValidatedToday ?? 0 }}</div>
                        <div class="dc-feed-card-label">
                            validé{{ ($countValidatedToday ?? 0) > 1 ? 's' : '' }} aujourd'hui
                            @if(($totalValidatedToday ?? 0) > 0)
                            · <span id="dc-feed-validated-amount">{{ number_format($totalValidatedToday, 0, ',', ' ') }}</span>&thinsp;F
                            @endif
                        </div>
                    </div>
                    <div class="dc-feed-card-cta">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            </div>
        </div>

        {{-- ── KPI STRIP ── --}}
        <div class="kpi-strip" id="kpi-strip">
            @php
                $tauxRecouvrement = $totalDue > 0 ? min(100, round(($totalPaid / $totalDue) * 100, 1)) : 0;
            @endphp
            {{-- KPI 1 : Total frais dus --}}
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-icon" style="background:linear-gradient(135deg,#0453cb,#5e91de);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="kpi-text">
                        <div id="kpi-total-due" class="kpi-value" style="color:#0453cb;">{{ number_format($totalDue, 0, ',', ' ') }}</div>
                        <div class="kpi-label">FCFA · Total frais dus</div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-badge" style="background:rgba(4,83,203,.1);color:#0453cb;">
                        <i class="fas fa-users" style="font-size:.6rem;"></i>
                        <span id="kpi-subscriptions">{{ $countDue }} souscriptions</span>
                    </div>
                </div>
            </div>

            {{-- KPI 2 : Encaissé --}}
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="kpi-text">
                        <div id="kpi-total-paid" class="kpi-value" style="color:#10b981;">{{ number_format($totalPaid, 0, ',', ' ') }}</div>
                        <div class="kpi-label">FCFA · Encaissé</div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.72rem;color:#64748b;">
                        <span>Taux recouvrement</span>
                        <span id="kpi-taux" class="fw-bold" style="color:#10b981;">{{ $tauxRecouvrement }}%</span>
                    </div>
                    <div class="recovery-bar-wrap">
                        <div id="kpi-recovery-bar" class="recovery-bar" style="width:{{ min($tauxRecouvrement, 100) }}%;"></div>
                    </div>
                </div>
            </div>

            {{-- KPI 3 : Restant dû --}}
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-icon" style="background:linear-gradient(135deg,#1e293b,#334155);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-text">
                        <div id="kpi-overdue" class="kpi-value" style="color:#1e293b;">{{ number_format($totalOverdue, 0, ',', ' ') }}</div>
                        <div class="kpi-label">FCFA · Restant impayé</div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-badge" style="background:rgba(30,41,59,.1);color:#1e293b;">
                        <i class="fas fa-clock" style="font-size:.6rem;"></i>
                        <span id="kpi-count-overdue">{{ $countOverdue }} étudiants concernés</span>
                    </div>
                </div>
            </div>

            {{-- KPI 4 : En attente de validation --}}
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-icon" style="background:linear-gradient(135deg,#5e91de,#0453cb);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="kpi-text">
                        <div id="kpi-pending" class="kpi-value" style="color:#0453cb;">{{ $countPartiallyPaid }}</div>
                        <div class="kpi-label">Paiements en attente</div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-badge" style="background:rgba(94,145,222,.15);color:#0453cb;">
                        <i class="fas fa-check-circle" style="font-size:.6rem;"></i>
                        <span id="kpi-validated">{{ $countPaid }} déjà validés</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── AGING + GRAPHIQUE ── --}}
        <div class="row g-4 mb-4">

            {{-- Aging Buckets --}}
            <div class="col-12 col-lg-5">
                <div class="main-card h-100 aging-card" id="aging-section">

                    @php
                        $agingConfig = [
                            '0-30'  => ['label' => 'Récents (moins d\'1 mois)',     'sublabel' => 'À surveiller',           'color' => '#10b981', 'bg' => 'rgba(16,185,129,.10)',  'risk' => 'Faible',   'icon' => 'fa-circle-check',          'tooltip' => 'Retard inférieur à 30 jours. Surveillez sans alerter — un rappel doux suffit en général.'],
                            '31-60' => ['label' => 'À relancer (1 à 2 mois)',       'sublabel' => 'Relance recommandée',    'color' => '#5e91de', 'bg' => 'rgba(94,145,222,.12)',  'risk' => 'Modéré',   'icon' => 'fa-clock',                 'tooltip' => 'Retard entre 30 et 60 jours. Envoyez une relance écrite (SMS, email ou WhatsApp).'],
                            '61-90' => ['label' => 'Critiques (2 à 3 mois)',        'sublabel' => 'Action immédiate',       'color' => '#0453cb', 'bg' => 'rgba(4,83,203,.12)',    'risk' => 'Élevé',    'icon' => 'fa-triangle-exclamation',  'tooltip' => 'Retard entre 60 et 90 jours. Contactez l\'étudiant ou le parent par téléphone.'],
                            '90+'   => ['label' => 'Très critiques (plus de 3 mois)', 'sublabel' => 'Mise en demeure',     'color' => '#1e293b', 'bg' => 'rgba(30,41,59,.12)',    'risk' => 'Critique', 'icon' => 'fa-skull',                 'tooltip' => 'Retard supérieur à 90 jours. Considérez une mise en demeure formelle ou un agent de recouvrement.'],
                        ];
                        $agingTotalAmount = array_sum(array_column($agingBuckets, 'amount'));
                        $agingTotalCount  = array_sum(array_column($agingBuckets, 'count'));
                    @endphp

                    {{-- Header --}}
                    <div class="aging-header">
                        <div>
                            <div class="aging-title">
                                <i class="fas fa-hourglass-half"></i>
                                Retard de paiement
                            </div>
                            <div class="aging-subtitle">
                                <span id="aging-total-count">{{ $agingTotalCount }}</span> étudiant{{ $agingTotalCount > 1 ? 's' : '' }} en retard ·
                                <span id="aging-total-amount">{{ number_format($agingTotalAmount, 0, ',', ' ') }}</span>&nbsp;FCFA
                                <span title="Délai calculé depuis la date d'échéance de chaque catégorie de frais (configurable dans Frais → Configurer)" style="cursor:help;color:#94a3b8;font-size:.8em;"> <i class="fas fa-circle-info"></i></span>
                            </div>
                        </div>
                        <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="aging-cta-btn">
                            <i class="fas fa-paper-plane"></i>
                            Relancer
                        </a>
                    </div>

                    {{-- Bucket rows --}}
                    <div class="aging-rows">
                        @foreach($agingBuckets as $key => $bucket)
                        @php
                            $cfg = $agingConfig[$key] ?? ['label' => $key, 'sublabel' => '', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,.1)', 'risk' => '—', 'icon' => 'fa-circle'];
                            $pct = $agingTotalAmount > 0 ? round(($bucket['amount'] / $agingTotalAmount) * 100) : 0;
                        @endphp
                        <div class="aging-row" data-aging="{{ $key }}" title="{{ $cfg['tooltip'] ?? '' }}">
                            {{-- Left: icon badge + labels --}}
                            <div class="aging-row-left">
                                <div class="aging-icon-badge" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};">
                                    <i class="fas {{ $cfg['icon'] }}"></i>
                                </div>
                                <div class="aging-row-labels">
                                    <span class="aging-row-range">{{ $cfg['label'] }}</span>
                                    <span class="aging-row-sub" style="color:{{ $cfg['color'] }};">{{ $cfg['sublabel'] }}</span>
                                </div>
                            </div>

                            {{-- Center: bar + amount --}}
                            <div class="aging-row-bar-wrap">
                                <div class="aging-bar-track">
                                    <div class="aging-bar-fill" style="width:{{ $pct }}%;background:{{ $cfg['color'] }};"></div>
                                </div>
                                <span class="aging-amount" style="color:#1e293b;">
                                    {{ number_format($bucket['amount'], 0, ',', ' ') }}&thinsp;F
                                </span>
                            </div>

                            {{-- Right: count bubble --}}
                            <div class="aging-count-bubble" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};">
                                <span class="aging-count">{{ $bucket['count'] }}</span>
                                <span class="aging-count-label">étud.</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Footer actions --}}
                    <div class="aging-footer">
                        <a href="{{ route('esbtp.paiements.index') }}" class="aging-footer-link">
                            <i class="fas fa-arrow-right"></i>
                            Tous les paiements
                        </a>
                    </div>
                </div>
            </div>

            {{-- Graphique Encaissements --}}
            <div class="col-12 col-lg-7">
                <div class="chart-card" id="chart-section">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#1e293b;">
                            <i class="fas fa-chart-line me-2" style="color:#0453cb;"></i>Encaissements mensuels
                        </h6>
                        <span id="chart-annee-label" style="font-size:.78rem;color:#64748b;">{{ $annee ? ($annee->name ?? $annee->libelle) : '' }}</span>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="encaissementsChart"></canvas>
                        <div id="chart-empty" class="text-center text-muted py-5" style="display:none;">
                            <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0" style="font-size:.9rem;">Aucune donnée sur cette période</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PAIEMENTS EN ATTENTE + ACCÈS RAPIDES ── --}}
        <div class="row g-4">

            {{-- Paiements en attente --}}
            <div class="col-12 col-lg-8">
                <div class="pending-card" id="pending-section">
                    <div class="pending-card-header">
                        <div class="pending-card-title">
                            <i class="fas fa-hourglass-half" style="color:#5e91de;font-size:.9rem;"></i>
                            Paiements en attente
                            <span id="pending-count-badge" class="pending-count-pill" style="{{ $countPartiallyPaid > 0 ? '' : 'display:none;' }}">{{ $countPartiallyPaid }}</span>
                        </div>
                        <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}" class="pending-see-all">
                            Voir tout <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                        </a>
                    </div>

                    {{-- Empty state --}}
                    <div id="pending-empty" class="pending-empty-state" style="{{ $paiementsEnAttente->isEmpty() ? '' : 'display:none;' }}">
                        <div class="pending-empty-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-weight:600;color:#1e293b;font-size:.88rem;">Aucun paiement en attente</div>
                            <div style="font-size:.78rem;color:#94a3b8;margin-top:3px;">Tous les paiements ont été traités</div>
                        </div>
                    </div>

                    {{-- Feed cards --}}
                    <div class="pending-feed" id="pending-tbody" style="{{ $paiementsEnAttente->isEmpty() ? 'display:none;' : '' }}">
                        @foreach($paiementsEnAttente as $paiement)
                        @php
                            $etudiant = $paiement->inscription->etudiant ?? null;
                            $nom = $etudiant->nom ?? 'N/A';
                            $prenoms = $etudiant->prenoms ?? '';
                            $initials = mb_strtoupper(mb_substr($nom, 0, 1) . mb_substr($prenoms, 0, 1));
                            $categorie = $paiement->fraisCategory->name ?? $paiement->motif ?? '—';
                            $dateStr = \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y');
                        @endphp
                        <div class="pending-item">
                            <div class="pending-avatar">{{ $initials }}</div>
                            <div class="pending-info">
                                <div class="pending-name">{{ $nom }} {{ $prenoms }}</div>
                                <div class="pending-meta">
                                    <span class="pending-cat">{{ $categorie }}</span>
                                    <span class="pending-date-dot"></span>
                                    <span class="pending-date">{{ $dateStr }}</span>
                                </div>
                            </div>
                            <div class="pending-right">
                                <span class="pending-amount">{{ number_format($paiement->montant, 0, ',', ' ') }} F</span>
                                <a href="{{ route('esbtp.paiements.show', $paiement) }}" class="pending-action-btn">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Accès rapides --}}
            <div class="col-12 col-lg-4">
                <div class="main-card p-4 h-100">
                    <h6 class="fw-bold mb-3" style="color:#1e293b;font-size:.88rem;">
                        <i class="fas fa-bolt me-2" style="color:#0453cb;"></i>Accès rapides
                    </h6>
                    <div class="qa-grid">
                        @can('frais.view')
<a href="{{ route('esbtp.frais.index') }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(4,83,203,.1);">
                                <i class="fas fa-tags" style="color:#0453cb;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Frais</div>
                                <div class="qa-sublabel">Catégories</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcan
                        @canany(['paiements.view', 'paiements.view_own'])
<a href="{{ route('esbtp.paiements.index') }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(16,185,129,.1);">
                                <i class="fas fa-money-bill-wave" style="color:#10b981;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Paiements</div>
                                <div class="qa-sublabel">Historique</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcanany
                        @can('comptabilite.relances.send')
<a href="{{ route('esbtp.comptabilite.relances.index') }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(94,145,222,.1);">
                                <i class="fas fa-paper-plane" style="color:#5e91de;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Relances</div>
                                <div class="qa-sublabel">Impayés</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcan
                        @can('frais.configure')
                        <a href="{{ route('esbtp.frais.configure') }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(30,41,59,.07);">
                                <i class="fas fa-sliders-h" style="color:#475569;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Config</div>
                                <div class="qa-sublabel">Frais & Tarifs</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcan
                        @canany(['paiements.view', 'paiements.view_own'])
<a href="{{ route('esbtp.paiements.suivi-categories') }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(4,83,203,.1);">
                                <i class="fas fa-chart-pie" style="color:#0453cb;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Suivi</div>
                                <div class="qa-sublabel">Par catégorie</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcanany
                        @can('comptabilite.reports.export')
                        <a href="{{ route('esbtp.paiements.index', ['format' => 'export-excel']) }}" class="qa-tile">
                            <div class="qa-icon" style="background:rgba(16,185,129,.1);">
                                <i class="fas fa-file-excel" style="color:#10b981;"></i>
                            </div>
                            <div>
                                <div class="qa-label">Export</div>
                                <div class="qa-sublabel">Excel</div>
                            </div>
                            <i class="fas fa-chevron-right qa-arrow"></i>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

@if($dmShell)
@php
    // ── Écran mobile (maquette S['comptable:dash']) ──────────────────────────
    $dmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $dmEcoleNom = $dmEcole['name'] ?: ($dmEcole['acronym'] ?: config('app.name'));
    $dmAnneeLabel = $annee ? (string) ($annee->name ?? $annee->libelle ?? '') : '';
    $dmAnneeEnCours = $annee && $anneeActive && (int) $annee->id === (int) $anneeActive->id;
    $dmDateJour = now()->translatedFormat('j M');
    $dmTaux = $totalDue > 0 ? min(100, round(($totalPaid / $totalDue) * 100, 1)) : null;
    $dmAujourdhui = now()->format('Y-m-d');

    // Montant compact lisible : « 207,4 M », « 112 M », « 850 k », « 9 500 ».
    // Miroir exact côté navigateur : mCompact() dans la fabrique dashComptaMobile.
    $dmCompact = function (float $n): string {
        $abs = abs($n);
        if ($abs >= 1_000_000_000) {
            $s = number_format($n / 1_000_000_000, 1, ',', ' ') . ' Md';
        } elseif ($abs >= 1_000_000) {
            $s = number_format($n / 1_000_000, 1, ',', ' ') . ' M';
        } elseif ($abs >= 10_000) {
            $s = number_format($n / 1_000, 0, ',', ' ') . ' k';
        } else {
            return number_format($n, 0, ',', ' ');
        }

        return str_replace(',0 ', ' ', $s);
    };

    // Chaque KPI et chaque ligne « À faire » n'est un lien que si la personne a le
    // droit d'arriver sur la page visée (mêmes gardes que les routes).
    $dmPeutVoirPaiements = $dmUser->can('paiements.view') || $dmUser->can('paiements.view_own');
    $dmPeutRelancer = $dmUser->can('comptabilite.relances.send');
    $dmPeutRecouvrer = $dmUser->can('comptabilite.recouvrement.access');
    $dmPeutValider = $dmUser->can('paiements.validate');

    $dmHrefDu = $dmPeutVoirPaiements ? route('esbtp.paiements.suivi-categories') : null;
    $dmHrefImpaye = $dmPeutRelancer
        ? route('esbtp.comptabilite.relances.index')
        : ($dmPeutRecouvrer ? route('esbtp.comptabilite.recouvrement.index') : null);
    $dmHrefAValider = $dmPeutValider ? route('esbtp.paiements.index', ['status' => 'en_attente']) : null;
    $dmHrefValides = $dmPeutVoirPaiements
        ? route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $dmAujourdhui, 'date_fin' => $dmAujourdhui])
        : null;

    // Ligne réconciliation : l'état et le seuil viennent du snapshot du service,
    // jamais d'un nombre écrit ici. Absente sans la permission de voir la réconciliation.
    $dmRec = null;
    if ($dmRecMetrics !== null) {
        $dmRecEnRetard = (int) ($dmRecMetrics['overdue_draft_count'] ?? 0);
        $dmRecBrouillons = (int) ($dmRecMetrics['sessions_by_status']['draft'] ?? 0);
        $dmRecEnRevue = (int) ($dmRecMetrics['sessions_by_status']['review'] ?? 0);
        $dmRecDernier = $dmRecMetrics['days_since_last_close'] ?? null;
        if ($dmRecEnRetard > 0) {
            $dmRec = [
                'title' => $dmRecEnRetard . ' réconciliation' . ($dmRecEnRetard > 1 ? 's' : '') . ' en retard',
                'sub' => 'Ouverte' . ($dmRecEnRetard > 1 ? 's' : '') . ' depuis plus de ' . (int) ($dmRecMetrics['overdue_threshold_days'] ?? 0) . ' j sans clôture',
                'chip' => 'En attente',
                'tone' => 'warn',
                'href' => route('esbtp.comptabilite.reconciliation.index', ['status' => 'draft']),
            ];
        } elseif ($dmRecBrouillons + $dmRecEnRevue > 0) {
            $dmRecEnCours = $dmRecBrouillons + $dmRecEnRevue;
            $dmRec = [
                'title' => $dmRecEnCours . ' réconciliation' . ($dmRecEnCours > 1 ? 's' : '') . ' en cours',
                'sub' => $dmRecBrouillons . ' brouillon' . ($dmRecBrouillons > 1 ? 's' : '') . ' · ' . $dmRecEnRevue . ' en revue',
                'chip' => 'En cours',
                'tone' => 'info',
                'href' => route('esbtp.comptabilite.reconciliation.index'),
            ];
        } else {
            $dmRec = [
                'title' => 'Réconciliation caisse à jour',
                'sub' => $dmRecDernier !== null
                    ? 'Dernière clôture il y a ' . (int) $dmRecDernier . ' j' . (!empty($dmRecMetrics['last_close_code']) ? ' · ' . $dmRecMetrics['last_close_code'] : '')
                    : 'Aucune clôture enregistrée pour le moment',
                'chip' => 'OK',
                'tone' => 'ok',
                'href' => route('esbtp.comptabilite.reconciliation.index'),
            ];
        }
    }
    $dmRienAFaire = !$dmPeutRecouvrer && $dmRec === null && !$dmPeutValider;

    $dmSerie = $serieRecente ?? ['labels' => [], 'data' => [], 'total' => 0.0, 'jours' => 0];
    $dmConfig = [
        'dataUrl' => route('esbtp.comptabilite.dashboard.data'),
        'dateJour' => $dmDateJour,
        'filtres' => [
            'annee' => (string) request('annee', ''),
            'filiere' => (string) request('filiere', ''),
            'classe' => (string) request('classe', ''),
        ],
        'd' => [
            'totalDue' => (float) $totalDue,
            'totalPaid' => (float) $totalPaid,
            'totalOverdue' => (float) $totalOverdue,
            'countDue' => (int) $countDue,
            'countOverdue' => (int) $countOverdue,
            'countToValidate' => (int) ($countToValidate ?? 0),
            'countOverdueTotal' => (int) ($countOverdueTotal ?? 0),
            'countValidatedToday' => (int) ($countValidatedToday ?? 0),
            'totalValidatedToday' => (float) ($totalValidatedToday ?? 0),
            'anneeLabel' => $dmAnneeLabel,
            'serieRecente' => $dmSerie,
        ],
    ];
@endphp
<div class="m-only-mobile m-screen dm-screen" x-data="dashComptaMobile({{ \Illuminate\Support\Js::from($dmConfig) }})">
    <x-m.appbar :title="$dmEcoleNom . ' · Finances'"
                :sub="$dmAnneeLabel !== '' ? 'Année ' . $dmAnneeLabel . ($dmAnneeEnCours ? ' · en cours' : '') : null"
                action="grid"
                action-label="Filtrer par année, filière ou classe"
                x-on:click="mOuvrirFiltres()">
        @if($dmPeutRelancer)
            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="m-ib ghost" aria-label="Relances des impayés">
                <x-m.icon name="bell" />
            </a>
        @endif
    </x-m.appbar>

    <div class="m-body" data-m-ptr="reload">
        <section class="m-hero" aria-live="polite">
            <span class="k" x-text="mHeroLabel()">Encaissé{{ $dmAnneeLabel !== '' ? ' · ' . $dmAnneeLabel : '' }} · au {{ $dmDateJour }}</span>
            <span class="v"><span x-text="mFmt(d.totalPaid)">{{ number_format((float) $totalPaid, 0, ',', ' ') }}</span><small>FCFA</small></span>
            <div class="row">
                <span class="pill" x-text="mPillTaux()">{{ $dmTaux !== null ? str_replace('.', ',', (string) $dmTaux) . ' % recouvré' : 'Aucun frais dû' }}</span>
                <span class="pill" x-text="mPluriel(d.countDue, 'souscription')">{{ $countDue }} souscription{{ $countDue > 1 ? 's' : '' }}</span>
                <span class="pill" x-show="mFiltreActif()" x-cloak>Filtré</span>
            </div>
        </section>

        {{-- Squelette pendant le rechargement des chiffres (filtres). --}}
        <div class="m-skel" x-show="loading" x-cloak aria-hidden="true"><i></i><i></i><i></i></div>

        <div class="dm-contenu" x-show="!loading">
            <div class="m-kpi">
                <a @if($dmHrefDu) href="{{ $dmHrefDu }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mCompact(d.totalDue)">{{ $dmCompact((float) $totalDue) }}</span>
                    <span class="l">Total frais dus</span>
                    <span class="d mute" x-text="'FCFA · ' + mPluriel(d.countDue, 'souscription')">FCFA · {{ $countDue }} souscription{{ $countDue > 1 ? 's' : '' }}</span>
                </a>
                <a @if($dmHrefImpaye) href="{{ $dmHrefImpaye }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mCompact(d.totalOverdue)">{{ $dmCompact((float) $totalOverdue) }}</span>
                    <span class="l">Reste impayé</span>
                    <span class="d {{ $countOverdue > 0 ? 'bad' : 'ok' }}"
                          x-bind:class="d.countOverdue > 0 ? 'bad' : 'ok'"
                          x-text="mPluriel(d.countOverdue, 'étudiant') + (d.countOverdue > 1 ? ' concernés' : ' concerné')">{{ $countOverdue }} étudiant{{ $countOverdue > 1 ? 's concernés' : ' concerné' }}</span>
                </a>
                <a @if($dmHrefAValider) href="{{ $dmHrefAValider }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mFmt(d.countToValidate)">{{ (int) ($countToValidate ?? 0) }}</span>
                    <span class="l">À valider</span>
                    <span class="d {{ ($countToValidate ?? 0) > 0 ? 'warn' : 'ok' }}"
                          x-bind:class="d.countToValidate > 0 ? 'warn' : 'ok'"
                          x-text="d.countToValidate > 0 ? 'En attente de validation' : 'Tout est validé'">{{ ($countToValidate ?? 0) > 0 ? 'En attente de validation' : 'Tout est validé' }}</span>
                </a>
                <a @if($dmHrefValides) href="{{ $dmHrefValides }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mFmt(d.countValidatedToday)">{{ (int) ($countValidatedToday ?? 0) }}</span>
                    <span class="l">Validés aujourd'hui</span>
                    <span class="d ok" x-text="mFmt(d.totalValidatedToday) + ' FCFA'">{{ number_format((float) ($totalValidatedToday ?? 0), 0, ',', ' ') }} FCFA</span>
                </a>
            </div>

            <div class="m-sec"><b>À faire aujourd'hui</b></div>
            @if($dmRienAFaire)
                <x-m.empty icon="check" title="Rien à faire ici" text="Les actions du jour (relances, réconciliation, validation) s'affichent selon vos droits." />
            @else
                <div class="m-list">
                    @if($dmPeutRecouvrer)
                        <a href="{{ route('esbtp.comptabilite.recouvrement.index') }}" class="m-row">
                            <div class="av ic" aria-hidden="true"><x-m.icon name="phone" /></div>
                            <div class="tt">
                                <b x-text="d.countOverdueTotal > 0 ? 'Relancer ' + mPluriel(d.countOverdueTotal, 'retard') + ' de paiement' : 'Aucun retard à relancer'">{{ ($countOverdueTotal ?? 0) > 0 ? 'Relancer ' . ($countOverdueTotal ?? 0) . ' retard' . (($countOverdueTotal ?? 0) > 1 ? 's' : '') . ' de paiement' : 'Aucun retard à relancer' }}</b>
                                <span>File de recouvrement du jour · WhatsApp</span>
                            </div>
                            <div class="tr">
                                <span class="m-chip {{ ($countOverdueTotal ?? 0) > 0 ? 'info' : 'ok' }}"
                                      x-bind:class="d.countOverdueTotal > 0 ? 'info' : 'ok'"
                                      x-text="d.countOverdueTotal > 0 ? 'Ouvrir' : 'À jour'">{{ ($countOverdueTotal ?? 0) > 0 ? 'Ouvrir' : 'À jour' }}</span>
                            </div>
                        </a>
                    @endif
                    @if($dmRec !== null)
                        <x-m.row :href="$dmRec['href']" icon="scale" :title="$dmRec['title']" :sub="$dmRec['sub']" :chip="$dmRec['chip']" :chip-type="$dmRec['tone']" />
                    @endif
                    @if($dmPeutValider)
                        <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}" class="m-row">
                            <div class="av ic" aria-hidden="true"><x-m.icon name="check" /></div>
                            <div class="tt">
                                <b x-text="d.countToValidate > 0 ? 'Valider ' + mPluriel(d.countToValidate, 'paiement') + ' en attente' : 'Aucun paiement à valider'">{{ ($countToValidate ?? 0) > 0 ? 'Valider ' . ($countToValidate ?? 0) . ' paiement' . (($countToValidate ?? 0) > 1 ? 's' : '') . ' en attente' : 'Aucun paiement à valider' }}</b>
                                <span>Passer en revue, puis valider ou rejeter</span>
                            </div>
                            <div class="tr">
                                <span class="m-chip {{ ($countToValidate ?? 0) > 0 ? 'warn' : 'ok' }}"
                                      x-bind:class="d.countToValidate > 0 ? 'warn' : 'ok'"
                                      x-text="d.countToValidate > 0 ? 'À faire' : 'À jour'">{{ ($countToValidate ?? 0) > 0 ? 'À faire' : 'À jour' }}</span>
                            </div>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Courbe légère en SVG : le tracé est calculé depuis les données, pas de Chart.js en mobile. --}}
            <div class="m-chart">
                <b x-text="'Encaissements · ' + d.serieRecente.jours + ' derniers jours'">Encaissements · {{ (int) ($dmSerie['jours'] ?? 0) }} derniers jours</b>
                <svg viewBox="0 0 300 110" role="img" x-bind:aria-label="mChartLabel()">
                    <defs>
                        <linearGradient id="dm-grad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#0453cb" stop-opacity=".28"/>
                            <stop offset="1" stop-color="#0453cb" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g stroke="#e9edf5"><line x1="0" y1="30" x2="300" y2="30"/><line x1="0" y1="60" x2="300" y2="60"/><line x1="0" y1="90" x2="300" y2="90"/></g>
                    <path x-bind:d="mAire()" fill="url(#dm-grad)"/>
                    <path x-bind:d="mLigne()" fill="none" stroke="#0453cb" stroke-width="2.5" stroke-linejoin="round"/>
                    <circle x-bind:cx="mDernierPoint().x" x-bind:cy="mDernierPoint().y" r="4" fill="#0453cb"/>
                    <text x="4" y="26" font-size="9" fill="#64748b" x-text="mEtiquette(30)"></text>
                    <text x="4" y="56" font-size="9" fill="#64748b" x-text="mEtiquette(60)"></text>
                </svg>
                <div class="dm-axe"><span x-text="mPremierJour()">{{ $dmSerie['labels'][0] ?? '' }}</span><span>Aujourd'hui</span></div>
                <p class="dm-vide" x-show="!(d.serieRecente.total > 0)" x-cloak>Aucun encaissement validé sur la période.</p>
                <p class="dm-total" x-show="d.serieRecente.total > 0">Total sur la période : <b x-text="mFmt(d.serieRecente.total) + ' FCFA'">{{ number_format((float) ($dmSerie['total'] ?? 0), 0, ',', ' ') }} FCFA</b></p>
            </div>
        </div>
    </div>

    {{-- Filtres en feuille : mêmes paramètres que le bureau (annee, filiere, classe), même endpoint JSON. --}}
    <x-m.sheet id="dm-filtres" title="Filtrer le tableau de bord" sub="Année, filière, classe">
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-annee-lbl">Année universitaire</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-annee-lbl">
                <label>
                    <input type="radio" name="dm_annee" value="" x-model="sheet.annee">
                    <span class="rd" aria-hidden="true"></span>
                    <b>{{ $anneeActive ? ($anneeActive->name ?? $anneeActive->libelle) : 'Année par défaut' }}</b>
                    @if($anneeActive)<span>En cours · par défaut</span>@endif
                </label>
                @foreach($annees as $a)
                    <label>
                        <input type="radio" name="dm_annee" value="{{ $a->id }}" x-model="sheet.annee">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $a->name ?? $a->libelle }}</b>
                        @if($a->is_current)<span>En cours</span>@endif
                    </label>
                @endforeach
            </div>
        </div>
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-filiere-lbl">Filière</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-filiere-lbl">
                <label>
                    <input type="radio" name="dm_filiere" value="" x-model="sheet.filiere" x-on:change="sheet.classe = ''">
                    <span class="rd" aria-hidden="true"></span>
                    <b>Toutes les filières</b>
                </label>
                @foreach($filieres as $f)
                    <label>
                        <input type="radio" name="dm_filiere" value="{{ $f->id }}" x-model="sheet.filiere" x-on:change="sheet.classe = ''">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $f->name ?? $f->nom }}</b>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-classe-lbl">Classe</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-classe-lbl">
                <label>
                    <input type="radio" name="dm_classe" value="" x-model="sheet.classe">
                    <span class="rd" aria-hidden="true"></span>
                    <b>Toutes les classes</b>
                </label>
                @foreach($classes as $c)
                    <label x-show="sheet.filiere === '' || String(sheet.filiere) === '{{ (int) $c->filiere_id }}'">
                        <input type="radio" name="dm_classe" value="{{ $c->id }}" x-model="sheet.classe">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $c->name ?? $c->nom }}</b>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="dm-actions">
            <button type="button" class="m-btn g" x-on:click="mReinitialiser()" x-bind:disabled="loading">Réinitialiser</button>
            <button type="button" class="m-btn p" x-bind:disabled="loading" x-on:click="mAppliquer().then(function (ok) { if (ok) { hide(); } })">
                <span x-show="!loading">Appliquer</span>
                <span x-show="loading" x-cloak>Chargement…</span>
            </button>
        </div>
    </x-m.sheet>
</div>
@endif

<x-fab-encaisser />
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    let chartInstance = null;
    const AJAX_URL = '{{ route("esbtp.comptabilite.dashboard.data") }}';

    // Initial data from server (used for first render without AJAX)
    const initialData = {
        labels:   @json($labelsMois),
        datasets: @json($dataEncaissements),
    };

    // ── Chart bootstrap ────────────────────────────────────────────────────
    function renderChart(labels, data) {
        const ctx = document.getElementById('encaissementsChart');
        const emptyDiv = document.getElementById('chart-empty');

        if (!ctx) return;

        if (!labels || labels.length === 0) {
            ctx.style.display = 'none';
            emptyDiv.style.display = 'block';
            return;
        }

        ctx.style.display = 'block';
        emptyDiv.style.display = 'none';

        if (chartInstance) chartInstance.destroy();

        const canvasCtx = ctx.getContext('2d');
        const grad = canvasCtx.createLinearGradient(0, 0, 0, ctx.offsetHeight || 240);
        grad.addColorStop(0, 'rgba(4,83,203,0.22)');
        grad.addColorStop(0.55, 'rgba(4,83,203,0.07)');
        grad.addColorStop(1, 'rgba(4,83,203,0.00)');

        chartInstance = new Chart(canvasCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Encaissements (FCFA)',
                    data: data,
                    borderColor: '#0453cb',
                    backgroundColor: grad,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0453cb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + new Intl.NumberFormat('fr-FR').format(ctx.raw) + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8',
                            callback: function(val) {
                                return new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 0 }).format(val);
                            }
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // ── KPI helpers ────────────────────────────────────────────────────────
    function fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); }

    function updateKPIs(d) {
        const taux = d.totalDue > 0 ? Math.min(100, ((d.totalPaid / d.totalDue) * 100)) : 0;

        document.getElementById('kpi-total-due').textContent   = fmt(d.totalDue);
        document.getElementById('kpi-total-paid').textContent  = fmt(d.totalPaid);
        document.getElementById('kpi-overdue').textContent     = fmt(d.totalOverdue);
        document.getElementById('kpi-pending').textContent     = d.countPartiallyPaid;
        document.getElementById('kpi-subscriptions').textContent = d.countDue + ' souscriptions';
        document.getElementById('kpi-validated').textContent   = d.countPaid + ' déjà validés';
        document.getElementById('kpi-count-overdue').textContent = d.countOverdue + ' étudiants concernés';
        document.getElementById('kpi-taux').textContent        = taux.toFixed(1) + '%';
        document.getElementById('kpi-recovery-bar').style.width = taux.toFixed(1) + '%';

        updateActionFeed(d);
    }

    function updateActionFeed(d) {
        const validateEl = document.getElementById('dc-feed-validate-count');
        if (validateEl) {
            validateEl.textContent = d.countToValidate ?? 0;
            const card = document.getElementById('dc-feed-card-validate');
            if (card) card.classList.toggle('is-active', (d.countToValidate ?? 0) > 0);
        }

        const overdueEl = document.getElementById('dc-feed-overdue-count');
        if (overdueEl) {
            overdueEl.textContent = d.countOverdueTotal ?? 0;
            const card = document.getElementById('dc-feed-card-overdue');
            if (card) card.classList.toggle('is-active', (d.countOverdueTotal ?? 0) > 0);
        }

        const validatedEl = document.getElementById('dc-feed-validated-count');
        if (validatedEl) validatedEl.textContent = d.countValidatedToday ?? 0;
        const validatedAmount = document.getElementById('dc-feed-validated-amount');
        if (validatedAmount && (d.totalValidatedToday ?? 0) > 0) {
            validatedAmount.textContent = fmt(d.totalValidatedToday);
        }
    }

    function updateAging(buckets) {
        const colors = {
            '0-30':  { color: '#10b981', bg: 'rgba(16,185,129,.12)',  risk: 'Faible' },
            '31-60': { color: '#5e91de', bg: 'rgba(94,145,222,.12)',  risk: 'Modéré' },
            '61-90': { color: '#0453cb', bg: 'rgba(4,83,203,.12)',    risk: 'Élevé' },
            '90+':   { color: '#1e293b', bg: 'rgba(30,41,59,.12)',    risk: 'Critique' },
        };
        let totalCount = 0, totalAmount = 0;

        Object.keys(buckets).forEach(function(key) {
            const b = buckets[key];
            totalCount  += b.count;
            totalAmount += b.amount;
            const row = document.querySelector('[data-aging="' + key + '"]');
            if (!row) return;
            const cfg = colors[key] || { color: '#64748b', bg: 'rgba(0,0,0,.05)', risk: '—' };
            row.querySelector('.aging-count').textContent  = b.count;
            row.querySelector('.aging-amount').textContent = fmt(b.amount) + ' F';
        });

        const totalCountEl = document.getElementById('aging-total-count');
        const totalAmountEl = document.getElementById('aging-total-amount');
        if (totalCountEl) totalCountEl.textContent = totalCount;
        if (totalAmountEl) totalAmountEl.textContent = fmt(totalAmount) + ' F';
    }

    function updatePending(payments) {
        const feed = document.getElementById('pending-tbody');
        const emptyDiv = document.getElementById('pending-empty');
        const countBadge = document.getElementById('pending-count-badge');

        if (!payments || payments.length === 0) {
            if (feed) feed.style.display = 'none';
            if (emptyDiv) emptyDiv.style.display = 'flex';
            if (countBadge) countBadge.style.display = 'none';
            return;
        }

        if (emptyDiv) emptyDiv.style.display = 'none';
        if (feed) feed.style.display = 'block';
        if (countBadge) {
            countBadge.textContent = payments.length;
            countBadge.style.display = 'inline-flex';
        }

        if (!feed) return;
        feed.innerHTML = payments.map(function(p) {
            const nom = p.nom || 'N/A';
            const prenoms = p.prenoms || '';
            const initials = (nom.charAt(0) + prenoms.charAt(0)).toUpperCase();
            return '<div class="pending-item">'
                + '<div class="pending-avatar">' + initials + '</div>'
                + '<div class="pending-info">'
                +   '<div class="pending-name">' + nom + ' ' + prenoms + '</div>'
                +   '<div class="pending-meta">'
                +     '<span class="pending-cat">' + (p.categorie || '—') + '</span>'
                +     '<span class="pending-date-dot"></span>'
                +     '<span class="pending-date">' + (p.date || '') + '</span>'
                +   '</div>'
                + '</div>'
                + '<div class="pending-right">'
                +   '<span class="pending-amount">' + fmt(p.montant) + ' F</span>'
                +   '<a href="' + p.url + '" class="pending-action-btn"><i class="fas fa-eye"></i> Voir</a>'
                + '</div>'
                + '</div>';
        }).join('');
    }

    // ── AJAX load ──────────────────────────────────────────────────────────
    function loadData(annee, filiere, classe) {
        const loading = document.getElementById('dash-loading');
        if (loading) loading.classList.add('show');

        const params = new URLSearchParams();
        if (annee)   params.set('annee', annee);
        if (filiere) params.set('filiere', filiere);
        if (classe)  params.set('classe', classe);

        fetch(AJAX_URL + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            updateKPIs(data);
            updateAging(data.agingBuckets || {});
            updatePending(data.paiementsEnAttente || []);
            renderChart(data.labelsMois || [], data.dataEncaissements || []);

            // Update chart subtitle
            const subtitle = document.getElementById('chart-annee-label');
            if (subtitle) subtitle.textContent = data.anneeLabel || '';
        })
        .catch(function(err) {
            console.error('Dashboard AJAX error:', err);
        })
        .finally(function() {
            if (loading) loading.classList.remove('show');
        });
    }

    // ── Filter badge + has-value state ─────────────────────────────────────
    function updateFilterBadge() {
        const anneeEl   = document.getElementById('f-annee');
        const filiereEl = document.getElementById('f-filiere');
        const classeEl  = document.getElementById('f-classe');

        const hasFilter = (anneeEl && anneeEl.value)
            || (filiereEl && filiereEl.value)
            || (classeEl  && classeEl.value);

        const badge = document.getElementById('filter-active-badge');
        if (badge) badge.classList.toggle('active', !!hasFilter);

        // Update has-value class for styled selects
        [anneeEl, filiereEl, classeEl].forEach(function(el) {
            if (el) el.classList.toggle('has-value', !!el.value);
        });

        // Show "année en cours par défaut" hint only when no année is selected
        const anneeHint = document.getElementById('filter-annee-hint');
        if (anneeHint) anneeHint.classList.toggle('hidden', !!(anneeEl && anneeEl.value));
    }

    // ── Init ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Render initial chart with server data
        renderChart(initialData.labels, initialData.datasets);

        // Count-up animation supprimée : chiffres financiers s'affichent direct
        // (fade-in CSS géré par .dash-hero / .kpi-strip animations, respect
        // prefers-reduced-motion pour vestibular disorders).

        // Filter change → AJAX
        ['f-annee', 'f-filiere', 'f-classe'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', function () {
                updateFilterBadge();
                loadData(
                    document.getElementById('f-annee').value,
                    document.getElementById('f-filiere').value,
                    document.getElementById('f-classe').value
                );
            });
        });

        // Reset button
        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                document.getElementById('f-annee').value   = '';
                document.getElementById('f-filiere').value = '';
                document.getElementById('f-classe').value  = '';
                updateFilterBadge();
                loadData('', '', '');
            });
        }

        updateFilterBadge();
    });
})();
</script>
@endpush

@push('scripts')
<script>
/* Écran mobile du tableau de bord comptable : une fabrique Alpine exposée sous
   garde (patron du projet), branchée sur le même endpoint JSON que les filtres
   de bureau. La courbe est un SVG tracé depuis les données, pas Chart.js. */
if (typeof window.dashComptaMobile !== 'function') {
window.dashComptaMobile = function (config) {
    return {
        d: config.d,
        filtres: Object.assign({}, config.filtres),
        sheet: Object.assign({}, config.filtres),
        loading: false,
        dateJour: config.dateJour,

        /* ---------- formats ---------- */
        mFmt(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
        },
        // Miroir de $dmCompact côté Blade : « 207,4 M », « 112 M », « 850 k », « 9 500 ».
        mCompact(n) {
            n = Number(n) || 0;
            const abs = Math.abs(n);
            const avec = function (v, dec, suffixe) {
                return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: dec }).format(v) + suffixe;
            };
            if (abs >= 1e9) return avec(n / 1e9, 1, ' Md');
            if (abs >= 1e6) return avec(n / 1e6, 1, ' M');
            if (abs >= 1e4) return avec(n / 1e3, 0, ' k');
            return this.mFmt(n);
        },
        mPluriel(n, mot) {
            n = Number(n) || 0;
            return this.mFmt(n) + ' ' + mot + (n > 1 ? 's' : '');
        },

        /* ---------- héro ---------- */
        mHeroLabel() {
            return 'Encaissé' + (this.d.anneeLabel ? ' · ' + this.d.anneeLabel : '') + ' · au ' + this.dateJour;
        },
        mPillTaux() {
            if (!(Number(this.d.totalDue) > 0)) return 'Aucun frais dû';
            const taux = Math.min(100, (Number(this.d.totalPaid) / Number(this.d.totalDue)) * 100);
            return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(taux) + ' % recouvré';
        },
        mFiltreActif() {
            return !!(this.filtres.annee || this.filtres.filiere || this.filtres.classe);
        },

        /* ---------- courbe SVG (viewBox 300 × 110, tracé entre y = 8 et y = 100) ---------- */
        mValeurs() {
            const serie = this.d.serieRecente;
            return (serie && Array.isArray(serie.data)) ? serie.data.map(Number) : [];
        },
        mMax() {
            const v = this.mValeurs();
            return v.length ? Math.max(0, Math.max.apply(null, v)) : 0;
        },
        mPoints() {
            const v = this.mValeurs();
            const n = v.length;
            if (n === 0) return [];
            const max = this.mMax();
            const pas = n > 1 ? 300 / (n - 1) : 0;
            return v.map(function (val, i) {
                const y = max > 0 ? 100 - (val / max) * 92 : 100;
                return { x: Math.round(i * pas * 10) / 10, y: Math.round(y * 10) / 10 };
            });
        },
        mLigne() {
            const p = this.mPoints();
            if (!p.length) return '';
            return 'M' + p.map(function (q) { return q.x + ' ' + q.y; }).join(' L');
        },
        mAire() {
            const ligne = this.mLigne();
            return ligne ? ligne + ' L300 110 L0 110Z' : '';
        },
        mDernierPoint() {
            const p = this.mPoints();
            return p.length ? p[p.length - 1] : { x: -10, y: -10 };
        },
        // Valeur représentée par une ligne de grille (y = 30, 60 ou 90).
        mEtiquette(y) {
            const max = this.mMax();
            if (max <= 0) return '';
            return this.mCompact(max * (100 - y) / 92);
        },
        mPremierJour() {
            const l = this.d.serieRecente && this.d.serieRecente.labels;
            return (l && l.length) ? l[0] : '';
        },
        mChartLabel() {
            const s = this.d.serieRecente || {};
            return 'Courbe des encaissements validés sur ' + (s.jours || 0) + ' jours, total ' + this.mFmt(s.total) + ' FCFA';
        },

        /* ---------- filtres (feuille) ---------- */
        mOuvrirFiltres() {
            this.sheet = Object.assign({}, this.filtres);
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: 'dm-filtres' } }));
        },
        mReinitialiser() {
            this.sheet = { annee: '', filiere: '', classe: '' };
        },
        // Renvoie true si les chiffres ont été rechargés (la feuille se ferme alors).
        async mAppliquer() {
            const params = new URLSearchParams();
            ['annee', 'filiere', 'classe'].forEach((k) => { if (this.sheet[k]) params.set(k, this.sheet[k]); });
            this.loading = true;
            try {
                const url = config.dataUrl + (params.toString() ? '?' + params.toString() : '');
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('le serveur a répondu ' + res.status);
                const data = await res.json();
                this.d = Object.assign({}, this.d, data, {
                    serieRecente: data.serieRecente || this.d.serieRecente,
                });
                this.filtres = Object.assign({}, this.sheet);
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Tableau de bord mis à jour.' } }));
                return true;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Impossible de charger les chiffres : ' + e.message + '.' } }));
                return false;
            } finally {
                this.loading = false;
            }
        },
    };
};
}
</script>
@endpush