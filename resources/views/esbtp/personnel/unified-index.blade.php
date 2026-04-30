@extends('layouts.app')

@section('title', 'Gestion du Personnel - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════
   PERSONNEL UNIFIED — Premium Design (pu- namespace)
   ═══════════════════════════════════════════════════ */

/* ─── Hero ─── */
.pu-hero {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    border-radius: 18px;
    padding: 2rem 2.25rem;
    color: #fff;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.pu-hero::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 340px; height: 340px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.pu-hero::after {
    content: '';
    position: absolute;
    bottom: -30%; left: -5%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.pu-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 2;
}
.pu-hero-title {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.pu-hero-title i { font-size: 1.25rem; opacity: 0.85; }
.pu-hero-subtitle {
    font-size: 0.88rem;
    opacity: 0.75;
    margin-top: 0.35rem;
}
.pu-hero-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
    position: relative;
    z-index: 1050;
}
.pu-hero-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    padding: 0.55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    white-space: nowrap;
}
.pu-hero-btn:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
.pu-hero-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}
.pu-hero-kpi {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: background 0.2s;
}
.pu-hero-kpi:hover { background: rgba(255,255,255,0.18); }
.pu-hero-kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
}
.pu-hero-kpi-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.8;
    margin-top: 0.15rem;
}

/* ─── Dropdown override inside hero ─── */
.pu-hero .dropdown-menu {
    z-index: 1051 !important;
    background: #fff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
    padding: 0.5rem !important;
    min-width: 200px !important;
}
.pu-hero .dropdown-item {
    padding: 0.6rem 0.85rem !important;
    color: #374151 !important;
    border-radius: 8px !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.15s !important;
}
.pu-hero .dropdown-item:hover {
    background: rgba(4,83,203,0.06) !important;
    color: #0453cb !important;
}
.pu-hero .dropdown-item i {
    width: 18px;
    text-align: center;
    font-size: 0.8rem;
    color: #94a3b8;
}
.pu-hero .dropdown-item:hover i { color: #0453cb; }

/* ─── Alerts ─── */
.pu-alert {
    border-radius: 12px;
    padding: 0.85rem 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.88rem;
    font-weight: 500;
    border: none;
}
.pu-alert-success {
    background: rgba(16,185,129,0.1);
    color: #065f46;
    border-left: 4px solid #10b981;
}
.pu-alert-danger {
    background: rgba(239,68,68,0.1);
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* ─── Tabs ─── */
.pu-tabs-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: visible;
    margin-bottom: 1.5rem;
}
.pu-tabs-bar {
    display: flex;
    border-bottom: 2px solid #f3f4f6;
    padding: 0 0.5rem;
    overflow-x: auto;
}
.pu-tab {
    flex: 1;
    min-width: 140px;
    padding: 1rem 0.75rem;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: center;
    position: relative;
    transition: all 0.2s;
    color: #6b7280;
}
.pu-tab::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 15%; right: 15%;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: transparent;
    transition: all 0.2s;
}
.pu-tab.active {
    color: #0453cb;
}
.pu-tab.active::after {
    background: #0453cb;
    left: 10%; right: 10%;
}
.pu-tab:hover:not(.active) {
    color: #374151;
    background: rgba(4,83,203,0.02);
}
.pu-tab-icon {
    display: block;
    font-size: 1.25rem;
    margin-bottom: 0.3rem;
}
.pu-tab.active .pu-tab-icon { color: #0453cb; }
.pu-tab-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
}
.pu-tab-count {
    display: block;
    font-size: 0.68rem;
    color: #94a3b8;
    margin-top: 0.15rem;
    font-weight: 600;
}
.pu-tab.active .pu-tab-count { color: #5e91de; }

/* ─── Panel ─── */
.pu-panel-content {
    padding: 1.5rem;
    min-height: 400px;
}
.pu-panel {
    display: none;
}
.pu-panel.active {
    display: block;
    animation: pu-slide 0.3s ease;
}
@keyframes pu-slide {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Panel Header (search + actions) ─── */
.pu-panel-header {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}
.pu-search {
    flex: 1;
    min-width: 220px;
    position: relative;
}
.pu-search input {
    width: 100%;
    padding: 0.6rem 0.75rem 0.6rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #1e293b;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.pu-search input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
    background: #fff;
}
.pu-search::before {
    content: '\f002';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.78rem;
    pointer-events: none;
}
.pu-panel-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.6rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}
.pu-panel-btn-primary {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
}
.pu-panel-btn-primary:hover {
    opacity: 0.9;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
.pu-panel-btn-warning {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #fff;
}
.pu-panel-btn-warning:hover {
    opacity: 0.9;
    color: #fff;
    text-decoration: none;
}

/* ─── Filters ─── */
.pu-filters {
    display: flex;
    gap: 0.6rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}
.pu-filters label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
}
.pu-filter-select {
    padding: 0.45rem 0.65rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.82rem;
    color: #1e293b;
    background: #fff;
    min-width: 140px;
    transition: border-color 0.2s;
}
.pu-filter-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
}

/* ─── Tip box ─── */
.pu-tip {
    background: rgba(4,83,203,0.04);
    border: 1px solid rgba(4,83,203,0.1);
    border-left: 4px solid #0453cb;
    border-radius: 10px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
}
.pu-tip i { color: #0453cb; margin-top: 2px; font-size: 0.9rem; }
.pu-tip-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #0453cb;
    margin-bottom: 0.15rem;
}
.pu-tip-text {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
}

/* ─── Personnel Card ─── */
.pu-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    margin-bottom: 0.75rem;
    border: 1px solid #f3f4f6;
    border-left: 3px solid transparent;
    transition: all 0.2s;
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.pu-card:hover {
    border-left-color: #0453cb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}
.pu-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.pu-avatar-green { background: linear-gradient(135deg, #10b981, #34d399); }
.pu-info {
    flex: 1;
    min-width: 0;
}
.pu-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #111827;
    line-height: 1.3;
}
.pu-meta {
    display: flex;
    gap: 0.85rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}
.pu-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    color: #6b7280;
}
.pu-meta-item i { font-size: 0.65rem; color: #94a3b8; }
.pu-status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    flex-shrink: 0;
}
.pu-status-active {
    background: rgba(16,185,129,0.1);
    color: #065f46;
}
.pu-status-active::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
}
.pu-status-inactive {
    background: rgba(239,68,68,0.08);
    color: #991b1b;
}
.pu-status-inactive::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #ef4444;
}
.pu-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}
.pu-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.pu-action-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4,83,203,0.04);
    text-decoration: none;
}
.pu-action-btn.pu-act-edit:hover {
    border-color: #f59e0b;
    color: #d97706;
    background: rgba(245,158,11,0.04);
}
.pu-action-btn.pu-act-danger:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239,68,68,0.04);
}
.pu-action-btn.pu-act-success:hover {
    border-color: #10b981;
    color: #10b981;
    background: rgba(16,185,129,0.04);
}

/* ─── Empty State ─── */
.pu-empty {
    padding: 3rem 2rem;
    text-align: center;
}
.pu-empty-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: #f1f5f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}
.pu-empty-icon i { font-size: 1.75rem; color: #cbd5e1; }
.pu-empty h3 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 0.4rem; }
.pu-empty p { font-size: 0.85rem; color: #6b7280; margin: 0 0 1rem; }
.pu-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.82rem;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}
.pu-empty-btn:hover { opacity: 0.9; color: #fff; text-decoration: none; }

/* ─── Animations ─── */
@keyframes pu-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.pu-animate { animation: pu-fade-up 0.5s ease-out both; }
.pu-delay-1 { animation-delay: 0.1s; }
.pu-delay-2 { animation-delay: 0.2s; }
.pu-delay-3 { animation-delay: 0.3s; }

/* ─── Overflow fix for dropdown ─── */
.dashboard-acasi,
.main-content { overflow: visible !important; }

/* ─── Responsive ─── */
@media (max-width: 992px) {
    .pu-hero-top { flex-direction: column; }
    .pu-hero-actions { width: 100%; }
    .pu-hero-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .pu-hero { padding: 1.5rem; border-radius: 14px; }
    .pu-hero-title { font-size: 1.25rem; }
    .pu-hero-kpis { grid-template-columns: 1fr 1fr; }
    .pu-tabs-bar { flex-direction: column; padding: 0; }
    .pu-tab { text-align: left; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem; }
    .pu-tab-icon { display: inline; margin-bottom: 0; font-size: 1rem; }
    .pu-tab-count { display: none; }
    .pu-tab::after { left: 0; right: auto; width: 3px; top: 15%; bottom: 15%; height: auto; border-radius: 0 3px 3px 0; }
    .pu-panel-header { flex-direction: column; }
    .pu-search { min-width: 100%; }
    .pu-filters { flex-direction: column; align-items: stretch; }
    .pu-card { flex-wrap: wrap; }
    .pu-actions { width: 100%; justify-content: flex-end; margin-top: 0.5rem; }
    .pu-hero-actions { flex-direction: column; }
}
@media (max-width: 576px) {
    .pu-hero-kpis { grid-template-columns: 1fr; }
    .pu-meta { flex-direction: column; gap: 0.25rem; }
}

/* ═══════════════════════════════════════════════════
   CUSTOM ROLES (cr-* namespace) — Lot 8
   ═══════════════════════════════════════════════════ */

.cr-section-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
    transition: box-shadow .2s ease;
}
.cr-section-bar:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.06), 0 2px 8px rgba(15,23,42,.04);
}
.cr-section-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.5rem;
    cursor: pointer;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}
.cr-section-toggle-left { display: flex; align-items: center; gap: .85rem; }
.cr-section-toggle-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
    flex-shrink: 0;
}
.cr-section-toggle-text h3 {
    font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;
}
.cr-section-toggle-text p {
    font-size: .8rem; color: #64748b; margin: 0;
}
.cr-section-toggle-right {
    display: flex; align-items: center; gap: .75rem;
}
.cr-section-toggle-badge {
    background: rgba(4,83,203,.08);
    color: #0453cb;
    font-size: .72rem;
    font-weight: 700;
    padding: .2rem .55rem;
    border-radius: 8px;
}
.cr-section-toggle-chev {
    color: #64748b;
    font-size: .85rem;
    transition: transform .2s ease;
}
.cr-section-bar.cr-open .cr-section-toggle-chev {
    transform: rotate(180deg);
}
.cr-section-content {
    display: none;
    padding: 0 1.5rem 1.25rem;
    border-top: 1px solid #f1f5f9;
}
.cr-section-bar.cr-open .cr-section-content {
    display: block;
}
.cr-section-actions {
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    padding: 1rem 0 0;
}

/* ── Lot 16 — Encadré informatif système vs custom ── */
.cr-info-note {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(94,145,222,.06));
    border: 1px solid rgba(4,83,203,.15);
    border-left: 3px solid #0453cb;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin: 1rem 0 .25rem;
}
.cr-info-note-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(4,83,203,.1);
    color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
}
.cr-info-note-body { flex: 1; min-width: 0; }
.cr-info-note-title {
    font-size: .82rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 .2rem;
    line-height: 1.3;
}
.cr-info-note-text {
    font-size: .78rem;
    color: #475569;
    margin: 0;
    line-height: 1.5;
}
.cr-info-note-text strong { color: #1e293b; font-weight: 600; }

/* Lot 17c — Variant warning (utilisé dans modal édition rôle système) */
.cr-info-note--warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.06), rgba(245, 158, 11, 0.02));
    border-left-color: #f59e0b;
}
.cr-info-note--warning .cr-info-note-icon { color: #b45309; }

/* Lot 17c — Grid pour les rôles standards (cards row) */
.cr-roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    padding: 12px 16px;
}
.cr-role-card--standard {
    border-left: 3px solid #0453cb;
    background: #fff;
}
.cr-role-card--standard .cr-role-card-name {
    background: rgba(4, 83, 203, 0.06);
    color: #033a8e;
}

/* Lot 17c — Tag « immuable » sur le label name readonly */
.cr-form-tag-immutable {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    letter-spacing: 0.3px;
}
.cr-form-hint {
    display: block;
    margin-top: 4px;
    font-size: 0.75rem;
    color: #64748b;
    font-style: italic;
}

/* ── Liste des rôles ── */
.cr-roles-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: .85rem;
    margin-top: 1rem;
}
.cr-role-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    transition: all .2s ease;
}
.cr-role-card:hover {
    border-color: #c7dafd;
    box-shadow: 0 4px 16px rgba(4,83,203,.06);
    transform: translateY(-2px);
}
.cr-role-card-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(4,83,203,.1), rgba(94,145,222,.15));
    color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.cr-role-card-body { flex: 1; min-width: 0; }
.cr-role-card-head {
    display: flex; align-items: center; gap: .55rem;
    flex-wrap: wrap;
}
.cr-role-card-label {
    font-size: .95rem; font-weight: 700;
    color: #1e293b; margin: 0;
    line-height: 1.3;
}
.cr-role-card-name {
    font-family: ui-monospace, monospace;
    font-size: .68rem;
    color: #64748b;
    background: #f1f5f9;
    padding: .15rem .45rem;
    border-radius: 6px;
}
.cr-role-card-desc {
    font-size: .8rem;
    color: #64748b;
    margin: .35rem 0 .55rem;
    line-height: 1.4;
}
.cr-role-card-stats {
    display: flex; gap: .5rem;
}
.cr-role-card-stat {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .72rem; font-weight: 600;
    color: #475569;
    background: #f8fafc;
    padding: .25rem .55rem;
    border-radius: 6px;
}
.cr-role-card-stat i { font-size: .72rem; color: #0453cb; }
.cr-role-card-actions {
    display: flex; flex-direction: column; gap: .35rem;
    flex-shrink: 0;
}
.cr-role-action {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
}
.cr-role-action:hover {
    background: #f8fafc;
    color: #0453cb;
    border-color: #c7dafd;
}
.cr-role-action.cr-role-action-users:hover {
    background: rgba(4,83,203,.08);
    color: #0453cb;
}
.cr-role-action.cr-role-action-danger:hover {
    background: rgba(220,38,38,.08);
    color: #dc2626;
    border-color: rgba(220,38,38,.2);
}

/* ── Empty state ── */
.cr-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #64748b;
}
.cr-empty-icon {
    font-size: 2rem;
    color: #cbd5e1;
    margin-bottom: .5rem;
}
.cr-empty h3 {
    font-size: 1rem; font-weight: 700;
    color: #1e293b;
    margin: 0 0 .25rem;
}
.cr-empty p {
    font-size: .85rem; margin: 0 0 1rem;
}
.cr-empty-btn {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    border: none;
    padding: .55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: .85rem;
    display: inline-flex; align-items: center; gap: .45rem;
    cursor: pointer;
}
.cr-empty-btn:hover { opacity: .9; }

/* ── Modal premium ── */
.cr-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(15,23,42,.2);
}
.cr-modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    color: #fff;
    border-bottom: none;
    padding: 1.25rem 1.5rem;
}
.cr-modal-header-left {
    display: flex; align-items: center; gap: .85rem;
}
.cr-modal-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #fff;
}
.cr-modal-title {
    font-size: 1.1rem; font-weight: 700; margin: 0; color: #fff;
}
.cr-modal-subtitle {
    font-size: .8rem; margin: .15rem 0 0;
    color: rgba(255,255,255,.75);
}
.cr-modal-name-tag {
    font-family: ui-monospace, monospace;
    font-size: .7rem;
    background: rgba(255,255,255,.15);
    padding: .1rem .4rem;
    border-radius: 5px;
    margin-left: .35rem;
}
.cr-modal-body {
    padding: 1.5rem;
    background: #fafbfc;
}
/* Fix Lot 17 : éviter espace blanc en bas du modal scrollable */
.cr-modal-body > :last-child { margin-bottom: 0 !important; }
.cr-modal-body .cr-error-zone:empty { display: none !important; }
.cr-modal-body .cr-section:last-child { margin-bottom: 0; }
.cr-modal-footer {
    background: #fff;
    border-top: 1px solid #f1f5f9;
    padding: 1rem 1.5rem;
}

/* ── Sections du modal ── */
.cr-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    margin-bottom: 1rem;
}
.cr-section-header {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: 1rem;
}
.cr-section-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
}
.cr-section-title {
    font-size: .95rem; font-weight: 700;
    color: #1e293b; margin: 0;
}
.cr-section-desc {
    font-size: .78rem; color: #64748b; margin: 0;
}

/* ── Form inputs ── */
.cr-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
.cr-form-grid:last-child { margin-bottom: 0; }
.cr-form-group { display: flex; flex-direction: column; }
.cr-form-label {
    font-size: .8rem; font-weight: 600;
    color: #1e293b;
    margin-bottom: .35rem;
}
.cr-req { color: #dc2626; }
.cr-form-input {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: .55rem .75rem;
    font-size: .85rem;
    background: #fff;
    color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.cr-form-input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.cr-form-input:disabled,
.cr-form-input[readonly] {
    background: #f8fafc;
    color: #64748b;
    cursor: not-allowed;
}
.cr-form-input-mono {
    font-family: ui-monospace, monospace;
    font-size: .82rem;
}
.cr-form-hint {
    font-size: .72rem;
    color: #94a3b8;
    margin-top: .3rem;
}

/* ── Icon picker ── */
.cr-icon-picker {
    display: flex; gap: .5rem; align-items: center;
}
.cr-icon-preview {
    width: 38px; height: 38px;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(94,145,222,.12));
    color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}
.cr-icon-picker .cr-form-input { flex: 1; }
.cr-icon-suggestions {
    display: flex; flex-wrap: wrap; gap: .35rem;
    margin-top: .5rem;
}
.cr-icon-chip {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
    font-size: .85rem;
}
.cr-icon-chip:hover {
    background: #f8fafc;
    color: #0453cb;
    border-color: #c7dafd;
}
.cr-icon-chip.active {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    border-color: transparent;
}

/* ── Picker permissions ── */
.cr-picker { background: #fff; }
.cr-picker-toolbar {
    display: flex; gap: .75rem;
    align-items: center;
    margin-bottom: .85rem;
    flex-wrap: wrap;
}
.cr-picker-search {
    position: relative; flex: 1; min-width: 220px;
}
.cr-picker-search i {
    position: absolute;
    left: .75rem; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .8rem;
}
.cr-picker-search-input {
    width: 100%;
    padding: .55rem .75rem .55rem 2.1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: .82rem;
    background: #f8fafc;
    transition: all .15s;
}
.cr-picker-search-input:focus {
    outline: none;
    border-color: #0453cb;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.cr-picker-counter {
    background: rgba(4,83,203,.06);
    border: 1px solid rgba(4,83,203,.15);
    border-radius: 8px;
    padding: .4rem .75rem;
    font-size: .8rem;
    color: #0453cb;
    font-weight: 600;
    display: flex; align-items: center; gap: .25rem;
    white-space: nowrap;
}
.cr-picker-counter-value { font-weight: 800; }
.cr-picker-counter-sep { color: #94a3b8; }
.cr-picker-counter-total { color: #475569; }
.cr-picker-counter-label { color: #64748b; font-weight: 500; margin-left: .15rem; }
.cr-picker-bulk { display: flex; gap: .35rem; }
.cr-picker-bulk-btn {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #475569;
    border-radius: 8px;
    padding: .4rem .65rem;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: .35rem;
    transition: all .15s;
}
.cr-picker-bulk-btn:hover { color: #0453cb; border-color: #c7dafd; }

.cr-picker-groups {
    max-height: 420px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
}
.cr-picker-group {
    border-bottom: 1px solid #f1f5f9;
}
.cr-picker-group:last-child { border-bottom: none; }
.cr-picker-group-header {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff;
    padding: 0;
}
.cr-picker-group-toggle {
    display: flex; align-items: center; gap: .55rem;
    background: transparent; border: none;
    padding: .65rem 1rem;
    flex: 1;
    cursor: pointer;
    text-align: left;
}
.cr-picker-group-chev {
    color: #94a3b8; font-size: .75rem;
    transition: transform .2s ease;
}
.cr-picker-group.cr-collapsed .cr-picker-group-chev {
    transform: rotate(-90deg);
}
.cr-picker-group-name {
    font-weight: 700; font-size: .85rem;
    color: #1e293b;
}
.cr-picker-group-count {
    font-size: .7rem; color: #64748b;
    background: #f1f5f9;
    padding: .15rem .45rem;
    border-radius: 6px;
    font-weight: 600;
}
.cr-picker-group-all {
    background: transparent;
    border: 1px solid transparent;
    color: #94a3b8;
    width: 32px; height: 32px;
    border-radius: 6px;
    cursor: pointer;
    margin-right: .5rem;
}
.cr-picker-group-all:hover {
    color: #0453cb;
    background: rgba(4,83,203,.06);
}
.cr-picker-group-body {
    padding: .35rem .6rem .65rem;
    background: #f8fafc;
}
.cr-picker-group.cr-collapsed .cr-picker-group-body { display: none; }

.cr-perm {
    display: flex; align-items: center; gap: .65rem;
    padding: .5rem .65rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color .12s;
    margin-bottom: .15rem;
}
.cr-perm:hover { background: #fff; }
.cr-perm input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.cr-perm-box {
    width: 18px; height: 18px;
    border: 1.5px solid #cbd5e1;
    border-radius: 5px;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    color: transparent;
    flex-shrink: 0;
    transition: all .12s;
}
.cr-perm-box i { font-size: .65rem; }
.cr-perm input:checked + .cr-perm-box {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    border-color: transparent;
    color: #fff;
}
.cr-perm-icon {
    color: #94a3b8;
    font-size: .8rem;
    width: 18px; text-align: center;
    flex-shrink: 0;
}
.cr-perm input:checked ~ .cr-perm-icon { color: #0453cb; }
.cr-perm-text {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: .1rem;
}
.cr-perm-label {
    font-size: .82rem;
    color: #1e293b;
    font-weight: 500;
    line-height: 1.3;
}
.cr-perm-name {
    font-family: ui-monospace, monospace;
    font-size: .68rem;
    color: #94a3b8;
}

.cr-picker-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    border-radius: 10px;
}
.cr-picker-empty-icon { font-size: 1.75rem; color: #cbd5e1; }
.cr-picker-empty h4 {
    font-size: .95rem;
    color: #1e293b;
    margin: .35rem 0 .15rem;
}
.cr-picker-empty p {
    font-size: .8rem; margin: 0;
}

/* ── Users list (assign modal) ── */
.cr-assign-info {
    background: rgba(4,83,203,.06);
    border: 1px solid rgba(4,83,203,.15);
    color: #1e3a8a;
    padding: .65rem .85rem;
    border-radius: 8px;
    font-size: .8rem;
    margin-bottom: 1rem;
    display: flex; align-items: center; gap: .5rem;
}
.cr-users-list {
    max-height: 420px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    padding: .35rem;
}
.cr-user-row {
    display: flex; align-items: center; gap: .65rem;
    padding: .65rem .75rem;
    background: #fff;
    border-radius: 8px;
    margin-bottom: .35rem;
    cursor: pointer;
    transition: all .15s;
}
.cr-user-row:hover {
    background: #f8fafc;
    box-shadow: 0 1px 3px rgba(15,23,42,.05);
}
.cr-user-row:last-child { margin-bottom: 0; }
.cr-user-row input[type="checkbox"] {
    position: absolute; opacity: 0; pointer-events: none;
}
.cr-user-box {
    width: 18px; height: 18px;
    border: 1.5px solid #cbd5e1;
    border-radius: 5px;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    color: transparent;
    flex-shrink: 0;
    transition: all .12s;
}
.cr-user-box i { font-size: .65rem; }
.cr-user-row input:checked + .cr-user-box {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    border-color: transparent;
    color: #fff;
}
.cr-user-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: .8rem;
    flex-shrink: 0;
}
.cr-user-info {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column;
}
.cr-user-name { font-size: .85rem; font-weight: 600; color: #1e293b; }
.cr-user-email { font-size: .75rem; color: #64748b; }
.cr-user-role-tag {
    background: #f1f5f9;
    color: #475569;
    font-size: .7rem;
    padding: .2rem .5rem;
    border-radius: 6px;
    font-weight: 600;
    display: inline-flex; align-items: center; gap: .3rem;
    flex-shrink: 0;
}
.cr-user-role-tag i { font-size: .65rem; color: #64748b; }

/* ── Buttons ── */
.cr-btn {
    border-radius: 9px;
    padding: .55rem 1.1rem;
    font-size: .82rem;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: .45rem;
    transition: all .15s ease;
}
.cr-btn-primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
}
.cr-btn-primary:hover { opacity: .92; }
.cr-btn-primary:disabled { opacity: .55; cursor: not-allowed; }
.cr-btn-ghost {
    background: #fff;
    border-color: #e2e8f0;
    color: #475569;
}
.cr-btn-ghost:hover { background: #f8fafc; color: #0453cb; }

/* ── Errors ── */
.cr-error-zone {
    background: rgba(220,38,38,.06);
    border: 1px solid rgba(220,38,38,.2);
    color: #b91c1c;
    padding: .65rem .85rem;
    border-radius: 8px;
    font-size: .82rem;
    margin-top: 1rem;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .cr-form-grid { grid-template-columns: 1fr; }
    .cr-roles-list { grid-template-columns: 1fr; }
    .cr-picker-toolbar { flex-direction: column; align-items: stretch; }
}
</style>
@endpush

@section('content')
@php
    // Tabs cachés selon le rôle de l'utilisateur connecté
    $hiddenTabs = [];
    $ur = $userRole ?? '';
    if ($ur === 'coordinateur') $hiddenTabs[] = 'coordinateurs';
    if ($ur === 'secretaire') $hiddenTabs[] = 'secretaires';
    // Premier tab visible = actif par défaut
    $tabOrder = ['coordinateurs', 'enseignants', 'secretaires', 'comptables', 'caissiers'];
    $firstVisibleTab = collect($tabOrder)->first(fn($t) => !in_array($t, $hiddenTabs));
@endphp

<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═══ Hero ═══ --}}
        <div class="pu-hero pu-animate">
            <div class="pu-hero-top">
                <div>
                    <div class="pu-hero-title">
                        <i class="fas fa-users-cog"></i>Gestion du Personnel
                    </div>
                    <div class="pu-hero-subtitle">Administration unifiée : coordinateurs, enseignants, secrétaires et comptables</div>
                </div>
                <div class="pu-hero-actions">
                    <div class="dropdown">
                        <button class="pu-hero-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus"></i>Nouveau Personnel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(($userRole ?? '') !== 'coordinateur')
                            <li><a class="dropdown-item" href="{{ route('esbtp.coordinateurs.create') }}">
                                <i class="fas fa-user-tie"></i>Coordinateur
                            </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('esbtp.enseignants.create') }}">
                                <i class="fas fa-chalkboard-teacher"></i>Enseignant
                            </a></li>
                            @if(($userRole ?? '') !== 'secretaire')
                            <li><a class="dropdown-item" href="{{ route('esbtp.secretaires.create') }}">
                                <i class="fas fa-user-shield"></i>Secrétaire
                            </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('esbtp.comptables.create') }}">
                                <i class="fas fa-calculator"></i>Comptable
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.caissiers.create') }}">
                                <i class="fas fa-cash-register"></i>Caissier
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="pu-hero-kpis">
                @if(!in_array('coordinateurs', $hiddenTabs))
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['coordinateurs'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Coordinateurs</div>
                </div>
                @endif
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['enseignants'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Enseignants</div>
                </div>
                @if(!in_array('secretaires', $hiddenTabs))
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['secretaires'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Secrétaires</div>
                </div>
                @endif
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['comptables'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Comptables</div>
                </div>
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['caissiers'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Caissiers</div>
                </div>
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Total</div>
                </div>
            </div>
        </div>

        {{-- ═══ Alerts ═══ --}}
        @if(session('success'))
            <div class="pu-alert pu-alert-success pu-animate pu-delay-1">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="pu-alert pu-alert-danger pu-animate pu-delay-1">
                <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- ═══ Lot 8 — Rôles personnalisés (collapsible) ═══ --}}
        @can('users.manage')
            @php $crCount = isset($customRoles) ? $customRoles->count() : 0; @endphp
            <div class="cr-section-bar pu-animate pu-delay-1 {{ $crCount > 0 ? 'cr-open' : '' }}" data-cr-section>
                <button type="button" class="cr-section-toggle" data-cr-section-toggle>
                    <div class="cr-section-toggle-left">
                        <div class="cr-section-toggle-icon"><i class="fas fa-id-badge"></i></div>
                        <div class="cr-section-toggle-text">
                            <h3>Rôles personnalisés</h3>
                            <p>Créez des rôles métiers sur mesure (Agent Inscriptions, Surveillant…)</p>
                        </div>
                    </div>
                    <div class="cr-section-toggle-right">
                        <span class="cr-section-toggle-badge">{{ $crCount }} rôle{{ $crCount > 1 ? 's' : '' }}</span>
                        <i class="fas fa-chevron-down cr-section-toggle-chev"></i>
                    </div>
                </button>
                <div class="cr-section-content">
                    {{-- Lot 16/17 — Encadré informatif : séparation système vs custom --}}
                    <div class="cr-info-note" role="note">
                        <div class="cr-info-note-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="cr-info-note-body">
                            <p class="cr-info-note-title">Rôles personnalisés (totalement libres)</p>
                            <p class="cr-info-note-text">
                                Créez, modifiez, supprimez et assignez des utilisateurs à des rôles métiers sur mesure.
                                Vous pouvez aussi modifier le label, l'icône, la description et les permissions des <strong>rôles standards</strong> ci-dessous.
                                Les rôles <strong>superAdmin</strong> et <strong>Service Technique</strong> restent gérés exclusivement par le Service Technique (page <code>/esbtp/roles-permissions</code>).
                            </p>
                        </div>
                    </div>
                    <div data-cr-list>
                        @include('esbtp.custom-roles._role-card', ['customRoles' => $customRoles ?? collect()])
                    </div>
                    <div class="cr-section-actions">
                        <button type="button" class="cr-btn cr-btn-primary" data-cr-create-trigger>
                            <i class="fas fa-plus"></i> Créer un rôle personnalisé
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal container (rempli en AJAX) --}}
            <div class="modal fade" id="crModal" tabindex="-1" aria-hidden="true">
                {{-- contenu chargé en AJAX --}}
            </div>

            {{-- ═══ Lot 17c — Rôles standards éditables (collapsible) ═══ --}}
            @if(isset($standardRoles) && $standardRoles->isNotEmpty())
                @php $stdCount = $standardRoles->count(); @endphp
                <div class="cr-section-bar pu-animate pu-delay-1" data-cr-section>
                    <button type="button" class="cr-section-toggle" data-cr-section-toggle>
                        <div class="cr-section-toggle-left">
                            <div class="cr-section-toggle-icon"><i class="fas fa-shield-halved"></i></div>
                            <div class="cr-section-toggle-text">
                                <h3>Rôles standards</h3>
                                <p>Modifiez le label, l'icône et les permissions des rôles système (sauf superAdmin / Service Technique)</p>
                            </div>
                        </div>
                        <div class="cr-section-toggle-right">
                            <span class="cr-section-toggle-badge">{{ $stdCount }} rôle{{ $stdCount > 1 ? 's' : '' }}</span>
                            <i class="fas fa-chevron-down cr-section-toggle-chev"></i>
                        </div>
                    </button>
                    <div class="cr-section-content">
                        <div class="cr-roles-list">
                            @foreach($standardRoles as $std)
                                <article class="cr-role-card cr-role-card--standard">
                                    <div class="cr-role-card-icon"><i class="fas {{ $std['icon'] }}"></i></div>
                                    <div class="cr-role-card-body">
                                        <div class="cr-role-card-head">
                                            <h4 class="cr-role-card-label">{{ $std['label'] }}</h4>
                                            <code class="cr-role-card-name">{{ $std['name'] }}</code>
                                        </div>
                                        @if(!empty($std['description']))
                                            <p class="cr-role-card-desc">{{ $std['description'] }}</p>
                                        @endif
                                        <div class="cr-role-card-stats">
                                            <span class="cr-role-card-stat"><i class="fas fa-users"></i> {{ $std['users_count'] }} user{{ $std['users_count'] > 1 ? 's' : '' }}</span>
                                            <span class="cr-role-card-stat"><i class="fas fa-key"></i> {{ $std['permissions_count'] }} perm{{ $std['permissions_count'] > 1 ? 's' : '' }}</span>
                                        </div>
                                    </div>
                                    <div class="cr-role-card-actions">
                                        <button type="button" class="cr-role-action"
                                                data-cr-edit-standard="{{ route('esbtp.custom-roles.standard.edit', $std['name']) }}"
                                                title="Modifier label, icône, permissions">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        {{-- ═══ Tabs Card ═══ --}}
        <div class="pu-tabs-card pu-animate pu-delay-2">
            <div class="pu-tabs-bar">
                @if(!in_array('coordinateurs', $hiddenTabs))
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'coordinateurs' ? 'active' : '' }}" data-tab="coordinateurs">
                    <span class="pu-tab-icon"><i class="fas fa-user-tie"></i></span>
                    <span class="pu-tab-label">Coordinateurs</span>
                    <span class="pu-tab-count">{{ $stats['coordinateurs'] ?? 0 }} personnes</span>
                </button>
                @endif
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'enseignants' ? 'active' : '' }}" data-tab="enseignants">
                    <span class="pu-tab-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                    <span class="pu-tab-label">Enseignants</span>
                    <span class="pu-tab-count">{{ $stats['enseignants'] ?? 0 }} personnes</span>
                </button>
                @if(!in_array('secretaires', $hiddenTabs))
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'secretaires' ? 'active' : '' }}" data-tab="secretaires">
                    <span class="pu-tab-icon"><i class="fas fa-user-shield"></i></span>
                    <span class="pu-tab-label">Secrétaires</span>
                    <span class="pu-tab-count">{{ $stats['secretaires'] ?? 0 }} personnes</span>
                </button>
                @endif
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'comptables' ? 'active' : '' }}" data-tab="comptables">
                    <span class="pu-tab-icon"><i class="fas fa-calculator"></i></span>
                    <span class="pu-tab-label">Comptables</span>
                    <span class="pu-tab-count">{{ $stats['comptables'] ?? 0 }} personnes</span>
                </button>
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'caissiers' ? 'active' : '' }}" data-tab="caissiers">
                    <span class="pu-tab-icon"><i class="fas fa-cash-register"></i></span>
                    <span class="pu-tab-label">Caissiers</span>
                    <span class="pu-tab-count">{{ $stats['caissiers'] ?? 0 }} personnes</span>
                </button>
            </div>

            <div class="pu-panel-content">

                {{-- ═══ Panel Coordinateurs ═══ --}}
                @if(!in_array('coordinateurs', $hiddenTabs))
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'coordinateurs' ? 'active' : '' }}" id="coordinateurs-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un coordinateur..." id="search-coordinateurs">
                        </div>
                        <a href="{{ route('esbtp.coordinateurs.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Coordinateur
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-coordinateurs-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="coordinateurs-list">
                        @if(isset($coordinateurs) && $coordinateurs->count() > 0)
                            @foreach($coordinateurs as $coordinateur)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $coordinateur->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $coordinateur->email }}</span>
                                        @if($coordinateur->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $coordinateur->telephone }}</span>
                                        @endif
                                        @if($coordinateur->specialite)
                                        <span class="pu-meta-item"><i class="fas fa-graduation-cap"></i>{{ $coordinateur->specialite }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $coordinateur->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $coordinateur->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($coordinateur->id !== auth()->id())
                                    <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="pu-action-btn {{ $coordinateur->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                                title="{{ $coordinateur->is_active ? 'Désactiver' : 'Activer' }}"
                                                onclick="return confirm('Êtes-vous sûr de vouloir {{ $coordinateur->is_active ? 'désactiver' : 'activer' }} ce coordinateur ?')">
                                            <i class="fas fa-{{ $coordinateur->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-user-tie"></i></div>
                                <h3>Aucun coordinateur</h3>
                                <p>Commencez par créer votre premier coordinateur.</p>
                                <a href="{{ route('esbtp.coordinateurs.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un coordinateur
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ═══ Panel Enseignants ═══ --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'enseignants' ? 'active' : '' }}" id="enseignants-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un enseignant..." id="search-enseignants">
                        </div>
                        <button type="button" class="pu-panel-btn pu-panel-btn-warning" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                            <i class="fas fa-calendar-check"></i>Disponibilités
                        </button>
                        <a href="{{ route('esbtp.enseignants.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouvel Enseignant
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-enseignants-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div class="pu-tip">
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <div class="pu-tip-title">Astuce</div>
                            <p class="pu-tip-text">Pour gérer la disponibilité d'un enseignant, consultez sa fiche détaillée en cliquant sur le bouton "Voir".</p>
                        </div>
                    </div>

                    <div id="enseignants-list">
                        @if(isset($enseignants) && $enseignants->count() > 0)
                            @foreach($enseignants as $teacher)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">
                                        @if($teacher->title)
                                            <span style="font-weight: 500; color: #64748b;">{{ $teacher->title }}</span>
                                        @endif
                                        {{ $teacher->user->name }}
                                    </div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $teacher->user->email }}</span>
                                        @if($teacher->user->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $teacher->user->telephone }}</span>
                                        @endif
                                        @if($teacher->specialization)
                                        <span class="pu-meta-item"><i class="fas fa-graduation-cap"></i>{{ $teacher->specialization }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $teacher->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $teacher->status === 'active' ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.enseignants.show', $teacher) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.enseignants.edit', $teacher) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($teacher->user->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $teacher->status === 'active' ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleTeacherStatus({{ $teacher->id }})">
                                        <i class="fas fa-{{ $teacher->status === 'active' ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <h3>Aucun enseignant</h3>
                                <p>Commencez par créer votre premier enseignant.</p>
                                <a href="{{ route('esbtp.enseignants.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un enseignant
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ═══ Panel Secrétaires ═══ --}}
                @if(!in_array('secretaires', $hiddenTabs))
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'secretaires' ? 'active' : '' }}" id="secretaires-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un secrétaire..." id="search-secretaires">
                        </div>
                        <a href="{{ route('esbtp.secretaires.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Secrétaire
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-secretaires-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="secretaires-list">
                        @if(isset($secretaires) && $secretaires->count() > 0)
                            @foreach($secretaires as $secretaire)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($secretaire->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $secretaire->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $secretaire->email }}</span>
                                        @if($secretaire->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $secretaire->telephone }}</span>
                                        @endif
                                        @if($secretaire->service)
                                        <span class="pu-meta-item"><i class="fas fa-briefcase"></i>{{ $secretaire->service }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $secretaire->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $secretaire->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.secretaires.show', $secretaire) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.secretaires.edit', $secretaire) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($secretaire->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $secretaire->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $secretaire->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleSecretaireStatus({{ $secretaire->id }})">
                                        <i class="fas fa-{{ $secretaire->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-user-shield"></i></div>
                                <h3>Aucun secrétaire</h3>
                                <p>Commencez par créer votre premier secrétaire.</p>
                                <a href="{{ route('esbtp.secretaires.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un secrétaire
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ═══ Panel Comptables ═══ --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'comptables' ? 'active' : '' }}" id="comptables-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un comptable..." id="search-comptables">
                        </div>
                        <a href="{{ route('esbtp.comptables.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Comptable
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-comptables-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="comptables-list">
                        @if(isset($comptables) && $comptables->count() > 0)
                            @foreach($comptables as $comptable)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar pu-avatar-green">
                                    {{ strtoupper(substr($comptable->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $comptable->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $comptable->email }}</span>
                                        @if($comptable->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $comptable->telephone }}</span>
                                        @endif
                                        @if($comptable->department)
                                        <span class="pu-meta-item"><i class="fas fa-building"></i>{{ $comptable->department }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $comptable->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $comptable->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $comptable->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.comptables.show', $comptable) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($comptable->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $comptable->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $comptable->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleComptableStatus({{ $comptable->id }})">
                                        <i class="fas fa-{{ $comptable->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-calculator"></i></div>
                                <h3>Aucun comptable</h3>
                                <p>Commencez par créer votre premier comptable.</p>
                                <a href="{{ route('esbtp.comptables.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un comptable
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ═══ Panel Caissiers (Lot 18e — aligne sur le pattern comptables) ═══ --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'caissiers' ? 'active' : '' }}" id="caissiers-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un caissier..." id="search-caissiers">
                        </div>
                        <a href="{{ route('esbtp.caissiers.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Caissier
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-caissiers-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="caissiers-list">
                        @if(isset($caissiers) && $caissiers->count() > 0)
                            @foreach($caissiers as $caissier)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar pu-avatar-blue">
                                    {{ strtoupper(substr($caissier->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $caissier->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $caissier->email ?: 'Sans email' }}</span>
                                        @if($caissier->phone ?? $caissier->telephone ?? null)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $caissier->phone ?? $caissier->telephone }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-cash-register"></i>Caissier</span>
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $caissier->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $caissier->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $caissier->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.caissiers.show', $caissier) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.caissiers.edit', $caissier) }}" class="pu-action-btn" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($caissier->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $caissier->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $caissier->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleCaissierStatus({{ $caissier->id }})">
                                        <i class="fas fa-{{ $caissier->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-cash-register"></i></div>
                                <h3>Aucun caissier</h3>
                                <p>Commencez par créer votre premier caissier.</p>
                                <a href="{{ route('esbtp.caissiers.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un caissier
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Modal pour afficher les credentials --}}
@include('partials.credentials-modal')

{{-- Modal pour sélection des enseignants pour modification groupée des disponibilités --}}
<div class="modal fade" id="bulkAvailabilityModal" tabindex="-1" aria-labelledby="bulkAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%); color: white; border-radius: 18px 18px 0 0; padding: 1.25rem 1.5rem;">
                <h5 class="modal-title fw-bold" id="bulkAvailabilityModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>Modification groupée des disponibilités
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités.
                </p>

                <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
                    <div class="flex-grow-1">
                        <input type="text" id="bulk-availability-search" class="form-control" placeholder="Rechercher un enseignant...">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="bulk-select-all-availability">
                        <i class="fas fa-check-double me-1"></i>Tout sélectionner
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-deselect-all-availability">
                        <i class="fas fa-times me-1"></i>Tout désélectionner
                    </button>
                </div>

                <div class="list-group" id="bulk-availability-list" style="max-height: 400px; overflow-y: auto;">
                    @if(isset($enseignants) && $enseignants->count() > 0)
                        @foreach($enseignants as $teacher)
                            <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 bulk-availability-item"
                                   data-name="{{ strtolower($teacher->user->name ?? '') }}"
                                   data-email="{{ strtolower($teacher->user->email ?? '') }}">
                                <input type="checkbox" class="form-check-input bulk-availability-checkbox" value="{{ $teacher->id }}" style="margin: 0;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        @if($teacher->title)
                                            <span class="text-muted">{{ $teacher->title }}</span>
                                        @endif
                                        {{ $teacher->user->name ?? 'N/A' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $teacher->user->email ?? '' }}
                                        @if($teacher->specialization)
                                            · {{ $teacher->specialization }}
                                        @endif
                                    </small>
                                </div>
                                <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </label>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <p>Aucun enseignant disponible</p>
                        </div>
                    @endif
                </div>

                <div class="mt-3 text-end">
                    <span class="badge bg-primary" id="bulk-availability-count">0 enseignant(s) sélectionné(s)</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="bulk-availability-submit" disabled>
                    <i class="fas fa-calendar-check me-1"></i>Modifier les disponibilités
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ═══ Tab switching ═══
    $('.slider-tab').click(function() {
        const tabName = $(this).data('tab');
        $('.slider-tab').removeClass('active');
        $(this).addClass('active');
        $('.slider-panel').removeClass('active');
        $('#' + tabName + '-panel').addClass('active');
    });

    // ═══ Search ═══
    ['coordinateurs', 'enseignants', 'secretaires', 'comptables'].forEach(function(type) {
        $('#search-' + type).on('input', function() {
            const q = $(this).val().toLowerCase();
            $('#' + type + '-list .personnel-card').each(function() {
                $(this).toggle($(this).text().toLowerCase().includes(q));
            });
        });
    });

    // ═══ Filters ═══
    $('.filter-select').change(function() {
        const panelType = $(this).attr('id').split('-')[1];
        const statusFilter = $('#filter-' + panelType + '-status').val();
        $('#' + panelType + '-list .personnel-card').each(function() {
            let show = true;
            if (statusFilter) {
                const hasActive = $(this).find('.pu-status-active').length > 0;
                const hasInactive = $(this).find('.pu-status-inactive').length > 0;
                if (statusFilter === 'active' && !hasActive) show = false;
                if (statusFilter === 'inactive' && !hasInactive) show = false;
            }
            $(this).toggle(show);
        });
    });
});

// ═══ Toggle Status Functions ═══
function toggleTeacherStatus(teacherId) {
    if (confirm('Changer le statut de cet enseignant ?')) {
        fetch(`/esbtp/enseignants/${teacherId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

function toggleComptableStatus(comptableId) {
    if (confirm('Changer le statut de ce comptable ?')) {
        fetch(`/esbtp/comptables/${comptableId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

function toggleSecretaireStatus(secretaireId) {
    if (confirm('Changer le statut de ce secrétaire ?')) {
        fetch(`/esbtp/secretaires/${secretaireId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

// Lot 18e — toggle status caissier (route PATCH dediee)
function toggleCaissierStatus(caissierId) {
    if (confirm('Changer le statut de ce caissier ?')) {
        fetch(`/esbtp/caissiers/${caissierId}/toggle-status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

// ═══ Bulk Availability Modal ═══
(function() {
    const searchInput = document.getElementById('bulk-availability-search');
    const selectAllBtn = document.getElementById('bulk-select-all-availability');
    const deselectAllBtn = document.getElementById('bulk-deselect-all-availability');
    const submitBtn = document.getElementById('bulk-availability-submit');
    const countBadge = document.getElementById('bulk-availability-count');
    const checkboxes = document.querySelectorAll('.bulk-availability-checkbox');
    const items = document.querySelectorAll('.bulk-availability-item');

    function updateCount() {
        const checked = document.querySelectorAll('.bulk-availability-checkbox:checked').length;
        if (countBadge) countBadge.textContent = checked + ' enseignant(s) sélectionné(s)';
        if (submitBtn) submitBtn.disabled = checked === 0;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            items.forEach(item => {
                item.classList.toggle('d-none', q && !(item.dataset.name || '').includes(q) && !(item.dataset.email || '').includes(q));
            });
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            items.forEach(item => {
                if (!item.classList.contains('d-none')) {
                    const cb = item.querySelector('.bulk-availability-checkbox');
                    if (cb) cb.checked = true;
                }
            });
            updateCount();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateCount();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.bulk-availability-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) { alert('Veuillez sélectionner au moins un enseignant.'); return; }
            window.location.href = '{{ route("esbtp.enseignants.bulk-availability") }}?' + ids.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
        });
    }

    updateCount();
})();
</script>

@can('users.manage')
{{-- ═════════════════════════════════════════════════
     LOT 8 — JS pour les rôles custom
     ═════════════════════════════════════════════════ --}}
<script>
(function () {
    const modalEl = document.getElementById('crModal');
    if (!modalEl) return;

    // Lot 17 : il y a maintenant DEUX sections (Rôles personnalisés + Rôles standards),
    // querySelector() ne prend que le premier — il faut wirer chaque toggle individuellement.
    const sectionBars = document.querySelectorAll('[data-cr-section]');
    const sectionBar = sectionBars[0] || null;  // backward compat (badge update sur Rôles personnalisés)
    const listContainer = document.querySelector('[data-cr-list]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const ROUTES = {
        list: @json(route('esbtp.custom-roles.index')),
        create: @json(route('esbtp.custom-roles.create')),
        store: @json(route('esbtp.custom-roles.store')),
        editUrl: (n) => @json(url('/esbtp/custom-roles')) + '/' + n + '/edit',
        updateUrl: (n) => @json(url('/esbtp/custom-roles')) + '/' + n,
        destroyUrl: (n) => @json(url('/esbtp/custom-roles')) + '/' + n,
        assignFormUrl: (n) => @json(url('/esbtp/custom-roles')) + '/' + n + '/assign-users',
        assignUrl: (n) => @json(url('/esbtp/custom-roles')) + '/' + n + '/assign-users',
    };

    let bsModal = null;
    function getModal() {
        if (!bsModal && window.bootstrap) {
            bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
        }
        return bsModal;
    }

    /* ── Sections collapsibles (Rôles personnalisés + Rôles standards) ── */
    sectionBars.forEach((bar) => {
        const toggle = bar.querySelector('[data-cr-section-toggle]');
        if (!toggle) return;
        toggle.addEventListener('click', () => {
            bar.classList.toggle('cr-open');
        });
    });

    /* ── Helpers ── */
    function slugify(str) {
        return (str || '')
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9_]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .replace(/_+/g, '_')
            .substring(0, 64);
    }

    async function loadModal(url) {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } });
            if (!res.ok) {
                const t = await res.text();
                throw new Error(t || 'Erreur de chargement (' + res.status + ')');
            }
            const html = await res.text();
            modalEl.innerHTML = html;
            getModal().show();
            wireModalForm();
            wireModalIcons();
            wireModalPicker();
            wireModalUserPicker();
            wireNameAutogen();
        } catch (e) {
            alert('Erreur de chargement du modal : ' + e.message);
        }
    }

    function showError(msg) {
        const z = modalEl.querySelector('[data-cr-error]');
        if (!z) return;
        z.textContent = msg;
        z.style.display = 'block';
    }

    async function refreshList() {
        try {
            const res = await fetch(ROUTES.list, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return;
            // On reconstruit la liste côté client pour éviter un nouveau call HTML
            const html = renderList(data.roles || []);
            if (listContainer) listContainer.innerHTML = html;
            const badge = sectionBar?.querySelector('.cr-section-toggle-badge');
            if (badge) {
                const n = (data.roles || []).length;
                badge.textContent = n + ' rôle' + (n > 1 ? 's' : '');
            }
            wireRoleCardActions();
        } catch (e) {
            // silencieux
        }
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function renderList(roles) {
        if (!roles.length) {
            return `<div class="cr-empty">
                <div class="cr-empty-icon"><i class="fas fa-id-badge"></i></div>
                <h3>Aucun rôle personnalisé</h3>
                <p>Créez votre premier rôle métier sur mesure (ex: Agent Inscriptions, Surveillant)</p>
                <button type="button" class="cr-empty-btn" data-cr-create-trigger>
                    <i class="fas fa-plus"></i> Créer un rôle personnalisé
                </button>
            </div>`;
        }
        return `<div class="cr-roles-list">${roles.map(r => `
            <div class="cr-role-card" data-cr-role-card="${escapeHtml(r.name)}">
                <div class="cr-role-card-icon"><i class="fas ${escapeHtml(r.icon)}"></i></div>
                <div class="cr-role-card-body">
                    <div class="cr-role-card-head">
                        <h4 class="cr-role-card-label">${escapeHtml(r.label)}</h4>
                        <span class="cr-role-card-name">${escapeHtml(r.name)}</span>
                    </div>
                    ${r.description ? `<p class="cr-role-card-desc">${escapeHtml(r.description)}</p>` : ''}
                    <div class="cr-role-card-stats">
                        <span class="cr-role-card-stat"><i class="fas fa-users"></i>${r.users_count}</span>
                        <span class="cr-role-card-stat"><i class="fas fa-key"></i>${r.permissions_count}</span>
                    </div>
                </div>
                <div class="cr-role-card-actions">
                    <button type="button" class="cr-role-action" data-cr-edit="${escapeHtml(r.name)}" title="Modifier"><i class="fas fa-pen"></i></button>
                    <button type="button" class="cr-role-action cr-role-action-users" data-cr-assign="${escapeHtml(r.name)}" title="Assigner"><i class="fas fa-user-plus"></i></button>
                    <button type="button" class="cr-role-action cr-role-action-danger" data-cr-delete="${escapeHtml(r.name)}" data-cr-delete-label="${escapeHtml(r.label)}" data-cr-delete-users="${r.users_count}" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </div>`).join('')}</div>`;
    }

    /* ── Form submission générique ── */
    function wireModalForm() {
        const form = modalEl.querySelector('#cr-create-form, #cr-edit-form, #cr-assign-form');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[data-cr-submit]');
            if (submitBtn) submitBtn.disabled = true;
            const errorZone = form.querySelector('[data-cr-error]');
            if (errorZone) errorZone.style.display = 'none';

            const fd = new FormData(form);
            const method = form.querySelector('input[name=_method]')?.value || form.method || 'POST';

            try {
                const res = await fetch(form.action, {
                    method: method.toUpperCase() === 'GET' ? 'GET' : 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    let msg = data.message || 'Erreur de validation.';
                    if (data.errors) {
                        msg += '\n' + Object.values(data.errors).flat().join('\n');
                    }
                    showError(msg);
                    return;
                }
                // Succès
                getModal().hide();
                await refreshList();
            } catch (e2) {
                showError('Erreur réseau : ' + e2.message);
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    /* ── Auto-slug du nom à partir du label ── */
    function wireNameAutogen() {
        const labelInput = modalEl.querySelector('[data-cr-label-input]');
        const nameInput = modalEl.querySelector('[data-cr-name-input]');
        if (!labelInput || !nameInput) return;
        let manuallyEdited = false;
        nameInput.addEventListener('input', () => {
            manuallyEdited = nameInput.value.trim().length > 0;
        });
        labelInput.addEventListener('input', () => {
            if (!manuallyEdited) {
                nameInput.value = slugify(labelInput.value);
            }
        });
    }

    /* ── Icon picker ── */
    function wireModalIcons() {
        const preview = modalEl.querySelector('[data-cr-icon-preview]');
        const input = modalEl.querySelector('[data-cr-icon-input]');
        const chips = modalEl.querySelectorAll('[data-cr-icon-suggest]');
        if (!preview || !input) return;
        const updatePreview = () => {
            const v = (input.value || 'fa-user-tag').trim();
            preview.className = 'fas ' + v;
            chips.forEach(c => c.classList.toggle('active', c.dataset.crIconSuggest === v));
        };
        input.addEventListener('input', updatePreview);
        chips.forEach(c => {
            c.addEventListener('click', () => {
                input.value = c.dataset.crIconSuggest;
                updatePreview();
            });
        });
        updatePreview();
    }

    /* ── Picker permissions (search, group toggle, select-all) ── */
    function wireModalPicker() {
        const picker = modalEl.querySelector('.cr-picker');
        if (!picker) return;

        const search = picker.querySelector('[data-cr-search]');
        const checks = () => Array.from(picker.querySelectorAll('[data-cr-perm]'));
        const counterChecked = picker.querySelector('[data-cr-counter-checked]');

        const updateCounter = () => {
            if (counterChecked) {
                counterChecked.textContent = checks().filter(c => c.checked).length;
            }
            // par groupe
            picker.querySelectorAll('.cr-picker-group').forEach(group => {
                const items = group.querySelectorAll('[data-cr-perm]');
                const checkedCount = group.querySelectorAll('[data-cr-perm]:checked').length;
                const countEl = group.querySelector('[data-cr-group-count]');
                if (countEl) countEl.textContent = checkedCount + '/' + items.length;
            });
        };

        checks().forEach(c => c.addEventListener('change', updateCounter));

        if (search) {
            search.addEventListener('input', () => {
                const q = search.value.toLowerCase().trim();
                picker.querySelectorAll('[data-cr-perm-label]').forEach(label => {
                    const text = label.dataset.crPermLabel || '';
                    label.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
                });
                // Cacher groupes qui n'ont aucune match
                picker.querySelectorAll('.cr-picker-group').forEach(group => {
                    const visible = Array.from(group.querySelectorAll('[data-cr-perm-label]')).some(l => l.style.display !== 'none');
                    group.style.display = visible || q === '' ? '' : 'none';
                });
            });
        }

        // Group toggle (collapse)
        picker.querySelectorAll('.cr-picker-group-toggle').forEach(t => {
            t.addEventListener('click', () => t.closest('.cr-picker-group').classList.toggle('cr-collapsed'));
        });

        // Group "tout cocher"
        picker.querySelectorAll('[data-cr-group-all]').forEach(btn => {
            btn.addEventListener('click', () => {
                const group = btn.closest('.cr-picker-group');
                const items = group.querySelectorAll('[data-cr-perm]');
                const allChecked = Array.from(items).every(i => i.checked);
                items.forEach(i => i.checked = !allChecked);
                updateCounter();
            });
        });

        // Bulk
        picker.querySelector('[data-cr-select-all]')?.addEventListener('click', () => {
            checks().forEach(c => { if (c.closest('label').style.display !== 'none') c.checked = true; });
            updateCounter();
        });
        picker.querySelector('[data-cr-clear-all]')?.addEventListener('click', () => {
            checks().forEach(c => c.checked = false);
            updateCounter();
        });

        updateCounter();
    }

    /* ── User picker (assign modal) ── */
    function wireModalUserPicker() {
        const userSearch = modalEl.querySelector('[data-cr-user-search]');
        const counter = modalEl.querySelector('[data-cr-user-counter]');
        const checks = () => Array.from(modalEl.querySelectorAll('[data-cr-user-check]'));

        const updateCounter = () => {
            if (counter) counter.textContent = checks().filter(c => c.checked).length;
        };
        checks().forEach(c => c.addEventListener('change', updateCounter));

        if (userSearch) {
            userSearch.addEventListener('input', () => {
                const q = userSearch.value.toLowerCase().trim();
                modalEl.querySelectorAll('[data-cr-user-label]').forEach(row => {
                    const text = row.dataset.crUserLabel || '';
                    row.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
        updateCounter();
    }

    /* ── Bind sur les actions des cards (rebind après refresh) ── */
    function wireRoleCardActions() {
        document.querySelectorAll('[data-cr-edit]').forEach(btn => {
            btn.onclick = () => loadModal(ROUTES.editUrl(btn.dataset.crEdit));
        });
        // Lot 17c : édition rôles standards (URL absolue, pas via ROUTES.editUrl)
        document.querySelectorAll('[data-cr-edit-standard]').forEach(btn => {
            btn.onclick = () => loadModal(btn.dataset.crEditStandard);
        });
        document.querySelectorAll('[data-cr-assign]').forEach(btn => {
            btn.onclick = () => loadModal(ROUTES.assignFormUrl(btn.dataset.crAssign));
        });
        document.querySelectorAll('[data-cr-delete]').forEach(btn => {
            btn.onclick = async () => {
                const name = btn.dataset.crDelete;
                const label = btn.dataset.crDeleteLabel || name;
                const users = parseInt(btn.dataset.crDeleteUsers || '0', 10);
                if (users > 0) {
                    alert(`Ce rôle est attribué à ${users} utilisateur(s). Détachez-les d'abord (icône utilisateurs) avant de supprimer.`);
                    return;
                }
                if (!confirm(`Supprimer définitivement le rôle « ${label} » ?`)) return;
                try {
                    const res = await fetch(ROUTES.destroyUrl(name), {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: (() => { const f = new FormData(); f.append('_method', 'DELETE'); return f; })(),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.success === false) {
                        alert(data.message || 'Erreur lors de la suppression.');
                        return;
                    }
                    refreshList();
                } catch (e) {
                    alert('Erreur réseau : ' + e.message);
                }
            };
        });
        document.querySelectorAll('[data-cr-create-trigger]').forEach(btn => {
            btn.onclick = () => loadModal(ROUTES.create);
        });
    }

    // Bind initial
    wireRoleCardActions();
})();
</script>
@endcan
@endpush
