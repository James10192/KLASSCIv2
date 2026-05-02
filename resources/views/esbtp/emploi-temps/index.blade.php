@extends('layouts.app')

@section('title', 'Emplois du temps - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ─── Scoped Premium — Emploi du Temps Index ─────────────────── */

    /* ── Header ──────────────────────────────────────────────────── */
    .emploi-temps-header {
        background: linear-gradient(135deg, var(--primary) 0%, #5e91de 100%);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        color: white;
        position: relative;
        overflow: hidden;
    }
    .emploi-temps-header::before {
        content: '';
        position: absolute;
        top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .et-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-md);
        position: relative;
        z-index: 1;
    }
    .et-header-left { display: flex; align-items: center; gap: var(--space-lg); }
    .et-header-icon {
        width: 64px; height: 64px;
        border-radius: var(--radius-medium);
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: #fff;
        flex-shrink: 0;
    }
    .emploi-temps-header h1 { color: #fff; margin: 0; font-size: 1.3rem; font-weight: 700; }
    .emploi-temps-header .et-subtitle { color: rgba(255,255,255,0.8); margin: 3px 0 0; font-size: 0.84rem; }
    .et-header-actions { display: flex; align-items: center; gap: var(--space-sm); flex-wrap: wrap; }
    .et-header-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: var(--radius-small);
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(4px);
        cursor: pointer;
    }
    .et-header-btn:hover { background: rgba(255,255,255,0.25); color: #fff; }
    .et-header-btn--primary { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.4); }
    .et-header-btn--primary:hover { background: rgba(255,255,255,0.35); }

    /* ── KPI Grid ─────────────────────────────────────────────────── */
    .et-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    .et-kpi { min-width: 0; overflow: hidden; }
    .et-kpi .et-kpi-value,
    .et-kpi .et-kpi-label { overflow: hidden; text-overflow: ellipsis; }
    .et-kpi {
        border-radius: var(--radius-medium);
        padding: var(--space-lg) var(--space-md);
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    }
    .et-kpi:hover { transform: translateY(-4px); }
    .et-kpi::after {
        content: '';
        position: absolute;
        top: -30%; right: -20%;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
        pointer-events: none;
    }
    .et-kpi-icon { font-size: 1.5rem; margin-bottom: var(--space-sm); opacity: 0.9; }
    .et-kpi-value { font-size: 1.8rem; font-weight: 800; line-height: 1.1; margin-bottom: 4px; }
    .et-kpi-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.85;
        font-weight: 600;
    }
    .et-kpi--primary { background: linear-gradient(135deg, var(--primary), #3b7ddb); box-shadow: 0 4px 16px rgba(4,83,203,0.25); }
    .et-kpi--primary:hover { box-shadow: 0 8px 28px rgba(4,83,203,0.35); }
    .et-kpi--success { background: linear-gradient(135deg, var(--success), #34d399); box-shadow: 0 4px 16px rgba(16,185,129,0.25); }
    .et-kpi--success:hover { box-shadow: 0 8px 28px rgba(16,185,129,0.35); }
    .et-kpi--cyan { background: linear-gradient(135deg, #0891b2, var(--accent-blue)); box-shadow: 0 4px 16px rgba(6,182,212,0.25); }
    .et-kpi--cyan:hover { box-shadow: 0 8px 28px rgba(6,182,212,0.35); }
    .et-kpi--warning { background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 4px 16px rgba(245,158,11,0.25); }
    .et-kpi--warning:hover { box-shadow: 0 8px 28px rgba(245,158,11,0.35); }

    /* ── Filter Card ──────────────────────────────────────────────── */
    .emploi-filter-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .emploi-filter-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .emploi-filter-card .card-header {
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid rgba(0,0,0,0.06);
        background: transparent;
    }
    .emploi-filter-card .card-header h6 {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .emploi-filter-card .card-header h6 i { color: var(--primary); }
    .emploi-filter-card .card-body { padding: var(--space-lg); }
    .emploi-filter-card .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }
    .emploi-filter-card .form-select,
    .emploi-filter-card .form-control {
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small);
        padding: 8px 12px;
        font-size: 0.84rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .emploi-filter-card .form-select:focus,
    .emploi-filter-card .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }

    /* ── Main Card (list) ─────────────────────────────────────────── */
    .et-list-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .et-list-card > .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid rgba(0,0,0,0.06);
        background: transparent;
    }
    .et-list-card > .card-header h5 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .et-list-card > .card-header h5 i { color: var(--primary); }

    /* ── EDT Cards (keep existing but enhance) ────────────────────── */
    .emploi-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid rgba(0,0,0,0.06);
        margin-bottom: var(--space-md);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .emploi-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .emploi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        background: var(--primary);
    }
    .emploi-card.active::before { background: var(--success); }
    .emploi-card.expired::before { background: #dc2626; }
    .emploi-card.upcoming::before { background: var(--accent-blue); }
    .emploi-card.current-period::before { background: #d97706; }

    .timetable-tips-btn {
        background: rgba(255,255,255,0.12);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 8px 16px;
        border-radius: var(--radius-small);
        font-weight: 600;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        cursor: pointer;
        backdrop-filter: blur(4px);
    }
    .timetable-tips-btn:hover {
        background: rgba(255,255,255,0.25);
        color: #fff;
    }

    .tips-modal .modal-content {
        border-radius: 18px;
        border: none;
        overflow: hidden;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
    }

    .tips-modal .modal-header {
        background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%);
        color: #ffffff;
        border-bottom: none;
    }

    .tips-modal .modal-title {
        font-weight: 700;
    }

    .tips-modal .modal-body {
        background: #f8fafc;
    }

    .tips-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .tips-step {
        background: #ffffff;
        border-radius: 18px;
        padding: 1.4rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1);
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        position: relative;
        overflow: hidden;
    }

    .tips-step::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.06), rgba(255, 255, 255, 0));
        opacity: 0.6;
        pointer-events: none;
    }

    .tips-step-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        color: #0f172a;
        z-index: 1;
    }

    .tips-step-title span {
        background: rgba(4, 83, 203, 0.12);
        color: #1d4ed8;
        border-radius: 999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .tips-step img {
        width: 100%;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.12);
        height: 220px;
        object-fit: cover;
        object-position: top;
        z-index: 1;
    }

    @media (min-width: 1200px) {
        .tips-steps {
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        }

        .tips-step img {
            height: 260px;
        }
    }

    .tips-step-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        border-radius: 10px;
        background: rgba(4, 83, 203, 0.1);
        color: #1d4ed8;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(4, 83, 203, 0.2);
        transition: all 0.2s ease;
        z-index: 1;
        width: fit-content;
    }

    .tips-step-action:hover {
        background: rgba(4, 83, 203, 0.18);
        transform: translateY(-1px);
        color: #1d4ed8;
    }

    .tips-step p {
        color: #475569;
        font-size: 0.9rem;
        z-index: 1;
    }

    .tips-note {
        margin-top: 1rem;
        background: rgba(14, 165, 233, 0.12);
        border: 1px solid rgba(14, 165, 233, 0.25);
        padding: 0.9rem 1rem;
        border-radius: 12px;
        color: #0369a1;
        font-weight: 600;
    }

    .emploi-shortcut-card {
        border: 1px dashed rgba(245, 158, 11, 0.6);
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
    }

    .emploi-shortcut-title {
        font-weight: 700;
        color: var(--warning);
        margin-bottom: var(--space-xs);
    }

    .emploi-shortcut-meta {
        color: var(--text-secondary);
        font-size: var(--text-small);
        margin-bottom: var(--space-sm);
    }

    .emploi-shortcut-stats {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }

    .emploi-shortcut-chip {
        background: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .emploi-card-header {
        padding: var(--space-md);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .emploi-card-title {
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--primary);
        margin: 0;
    }
    .emploi-card-body {
        padding: var(--space-md);
    }
    
    .emploi-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
    }

    .emploi-info-list {
        display: grid;
        gap: 8px;
        margin-bottom: var(--space-md);
    }

    .emploi-info-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .emploi-info-row i {
        color: var(--primary);
        font-size: 13px;
    }

    .emploi-info-key {
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-size: 11px;
    }

    .emploi-info-val {
        font-weight: 600;
        color: var(--text-primary);
    }

    .emploi-info-pills {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
        margin-bottom: var(--space-md);
    }

    .emploi-info-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid transparent;
        color: var(--text-primary);
        background: rgba(59, 130, 246, 0.08);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .emploi-info-pill i {
        font-size: 12px;
        opacity: 0.8;
    }

    .emploi-info-pill.primary {
        background: rgba(37, 99, 235, 0.12);
        border-color: rgba(37, 99, 235, 0.22);
        color: #1d4ed8;
    }

    .emploi-info-pill.info {
        background: rgba(14, 165, 233, 0.12);
        border-color: rgba(14, 165, 233, 0.22);
        color: #0369a1;
    }

    .emploi-info-pill.success {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.22);
        color: #047857;
    }

    .emploi-info-pill.warning {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.28);
        color: #b45309;
    }
    
    .emploi-info-item {
        text-align: left;
    }
    
    .emploi-info-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-bottom: 2px;
        font-weight: 500;
    }
    
    .emploi-info-value {
        font-size: var(--text-normal);
        color: var(--text-primary);
        font-weight: 600;
    }
    
    .emploi-actions {
        display: flex;
        gap: var(--space-xs);
        justify-content: flex-end;
        padding-top: var(--space-sm);
        border-top: 1px solid #f3f4f6;
        margin-top: var(--space-sm);
    }

    .table-container {
        overflow-x: auto;
        border-radius: var(--radius-medium);
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Conteneur des cards — redesign v2 : stack 1 colonne (cards horizontales), 2 colonnes ≥ 1600px */
    .emplois-cards-container {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
        margin-bottom: var(--space-lg);
        width: 100%;
    }
    @media (min-width: 1600px) {
        .emplois-cards-container {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-md);
        }
    }

    .emploi-card {
        min-width: 0;
    }
    
    /* Header du toggle vue */
    .emploi-view-toggle {
        display: flex;
        align-items: center;
    }
    
    /* État empty pour les cards */
    .empty-state-card {
        grid-column: 1 / -1;
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 2px dashed #e5e7eb;
    }
    
    /* Badges dans les cards */
    .emploi-status-badges {
        display: flex;
        gap: var(--space-xs);
        flex-wrap: wrap;
    }
    
    .emploi-status-badges .badge-moderne {
        font-size: 11px;
        padding: 3px 8px;
    }
    
    /* Animation de transition */
    .emploi-card, .table-container {
        transition: all 0.3s ease;
    }

    /* Responsive pour les cards (redesign v2 stack — les rules historiques neutralisées) */
    @media (max-width: 991px) {
        .emplois-cards-container {
            display: flex;
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        .emploi-view-toggle {
            order: -1;
            margin-bottom: var(--space-sm);
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-sm);
        }
    }
    
    .table-moderne {
        margin-bottom: 0;
        border-collapse: collapse;
        min-width: 960px;
        width: 100%;
    }
    .table-moderne thead tr {
        background: linear-gradient(135deg, var(--primary), #3b7ddb);
    }
    .table-moderne th {
        color: #fff;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        border: none;
        padding: 12px 16px;
        white-space: nowrap;
    }
    .table-moderne td {
        padding: 10px 16px;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        vertical-align: middle;
        font-size: 0.84rem;
        white-space: nowrap;
    }
    .table-moderne tbody tr {
        background: var(--surface);
        transition: background 0.15s ease;
    }
    .table-moderne tbody tr:hover {
        background: rgba(4,83,203,0.03);
    }

    .table-shortcut-row td {
        background: rgba(245, 158, 11, 0.06);
        border-bottom: 1px dashed rgba(245, 158, 11, 0.3);
    }
    .table-moderne .col-classe { min-width: 120px; font-weight: 600; }
    .table-moderne .col-filiere { min-width: 140px; }
    .table-moderne .col-niveau { min-width: 100px; }
    
    .table-moderne .col-annee {
        min-width: 150px;
    }
    
    .table-moderne .col-periode {
        min-width: 120px;
    }
    
    .table-moderne .col-statut {
        min-width: 180px;
        white-space: nowrap;
    }
    
    .table-moderne .col-statut .badge-moderne {
        display: inline-block;
        margin-right: 4px;
        margin-bottom: 2px;
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .table-moderne .col-actions {
        min-width: 120px;
        text-align: center;
    }
    
    .badge-moderne {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 500;
    }
    
    .badge-moderne.primary {
        background-color: rgba(30, 58, 138, 0.1);
        color: var(--primary);
    }
    
    .badge-moderne.success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    .badge-moderne.secondary {
        background-color: rgba(107, 114, 128, 0.1);
        color: var(--neutral);
    }
    
    .badge-moderne.info {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
    }

    .badge-moderne.warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .badge-moderne.danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
    
    .btn-group-moderne {
        display: flex;
        gap: var(--space-xs);
    }
    
    .btn-moderne {
        padding: var(--space-xs) var(--space-sm);
        border: none;
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    
    .btn-moderne.info {
        background-color: var(--accent-blue);
        color: white;
    }
    
    .btn-moderne.warning {
        background-color: var(--warning);
        color: white;
    }
    
    .btn-moderne.danger {
        background-color: var(--danger);
        color: white;
    }
    
    .btn-moderne:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }

    /* ── Responsive ────────────────────────────────────────────────── */
    @media (max-width: 992px) {
        .et-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .et-header-inner { flex-direction: column; text-align: center; }
        .et-header-left { flex-direction: column; }
        .et-header-actions { justify-content: center; }
    }
    @media (max-width: 480px) {
        .et-kpi-grid { grid-template-columns: 1fr; }
    }

    /* ══════════════════════════════════════════════
       Width containment — prevent horizontal overflow
       (nextadmin-main/content are flex: 1 with default min-width: auto,
        which lets intrinsically wide children overflow the viewport)
       ══════════════════════════════════════════════ */
    .nextadmin-main,
    .nextadmin-content { min-width: 0; }
    .nextadmin-content > * { max-width: 100%; }

    /* ══════════════════════════════════════════════
       KPI refresh — monochrome bleu (premium redesign)
       ══════════════════════════════════════════════ */
    .et-kpi { background: #fff; color: #1e293b; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .et-kpi::after { display: none; }
    .et-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04); }
    .et-kpi .et-kpi-icon { color: #0453cb; background: linear-gradient(135deg, rgba(4,83,203,.1), rgba(59,125,219,.15)); width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm); }
    .et-kpi .et-kpi-value { color: #0f172a; }
    .et-kpi .et-kpi-value--sm { font-size: 1.05rem; line-height: 1.25; }
    .et-kpi .et-kpi-label { color: #64748b; font-weight: 600; }
    .et-kpi--primary { box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .et-kpi--primary .et-kpi-icon { color: #fff; background: linear-gradient(135deg, #0453cb, #3b7ddb); }
    .et-kpi--success .et-kpi-icon { color: #059669; background: rgba(16,185,129,.12); }
    .et-kpi--accent .et-kpi-icon { color: #0453cb; background: rgba(4,83,203,.1); }
    .et-kpi--soft .et-kpi-icon { color: #475569; background: rgba(148,163,184,.14); }

    /* ══════════════════════════════════════════════
       Week navigator (et-week-*)
       ══════════════════════════════════════════════ */
    .et-week-nav {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: .9rem 1.1rem;
        margin-bottom: .75rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .et-week-nav-left { display: flex; align-items: center; gap: .65rem; flex: 1 1 280px; min-width: 0; }
    .et-week-nav-right { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; min-width: 0; }

    .et-week-current {
        flex: 1 1 0;
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 0;
        padding: .2rem .5rem;
        overflow: hidden;
    }
    .et-week-current-top span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }
    .et-week-current-top {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .et-week-current-top i { color: #0453cb; }
    .et-week-current-meta {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .et-week-meta-item {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .78rem;
        color: #64748b;
        font-weight: 500;
    }
    .et-week-meta-item i { font-size: .75rem; color: #94a3b8; }

    .et-week-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .9rem;
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #1e293b;
        cursor: pointer;
        transition: all .2s ease;
    }
    .et-week-btn:hover:not(:disabled) {
        border-color: #0453cb;
        color: #0453cb;
        background: rgba(4,83,203,.04);
    }
    .et-week-btn:disabled { opacity: .45; cursor: not-allowed; }
    .et-week-btn--icon { width: 38px; height: 38px; padding: 0; justify-content: center; }
    .et-week-btn--primary {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(4,83,203,.2);
    }
    .et-week-btn--primary:hover:not(:disabled) { background: linear-gradient(135deg, #033a8e, #0453cb); color: #fff; }
    .et-week-btn--ghost { background: transparent; border-color: #e2e8f0; }

    .et-week-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .2px;
    }
    .et-week-chip--current { background: rgba(16,185,129,.14); color: #047857; }
    .et-week-chip--past    { background: rgba(148,163,184,.16); color: #475569; }
    .et-week-chip--upcoming{ background: rgba(4,83,203,.12); color: #0453cb; }

    /* Rail horizontal des semaines */
    .et-week-rail {
        display: flex;
        gap: .5rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding: .25rem .2rem .75rem;
        margin-bottom: 1rem;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        min-width: 0;
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
    }
    .et-week-rail::-webkit-scrollbar { height: 6px; }
    .et-week-rail::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .et-week-pill {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .15rem;
        padding: .4rem .7rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: .76rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all .18s ease;
        flex-shrink: 0;
        min-width: 108px;
    }
    .et-week-pill:hover { border-color: #0453cb; color: #0453cb; }
    .et-week-pill-range { white-space: nowrap; }
    .et-week-pill-count {
        font-size: .66rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .et-week-pill.is-active {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 12px rgba(4,83,203,.22);
    }
    .et-week-pill.is-active .et-week-pill-count { color: rgba(255,255,255,.75); }
    .et-week-pill--current:not(.is-active) { border-color: rgba(16,185,129,.45); }
    .et-week-pill--current:not(.is-active) .et-week-pill-range::after {
        content: '•';
        color: #10b981;
        margin-left: 4px;
    }

    /* Modal Week picker */
    .et-week-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(15,23,42,.22);
    }
    .et-week-modal .modal-header {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        color: #fff;
        border: none;
    }
    .et-week-modal .modal-body { background: #f8fafc; padding: 1.25rem 1.5rem 1.5rem; }
    .et-week-modal-toolbar {
        display: flex;
        gap: .6rem;
        margin-bottom: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .et-week-modal-toolbar .form-control { flex: 1; min-width: 220px; border-radius: 10px; border: 1px solid #e2e8f0; }
    .et-week-modal-toolbar .form-control:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .et-week-modal-list { display: flex; flex-direction: column; gap: 1.25rem; }
    .et-week-modal-group-title {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #64748b;
        margin-bottom: .5rem;
        padding-left: .25rem;
    }
    .et-week-modal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: .6rem;
    }
    .et-week-modal-item {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        padding: .75rem .9rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        text-align: left;
        cursor: pointer;
        transition: all .18s ease;
    }
    .et-week-modal-item:hover {
        border-color: #0453cb;
        box-shadow: 0 4px 16px rgba(4,83,203,.1);
        transform: translateY(-1px);
    }
    .et-week-modal-item.is-active {
        border-color: transparent;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        box-shadow: 0 6px 18px rgba(4,83,203,.25);
    }
    .et-week-modal-item.is-active .et-week-chip { background: rgba(255,255,255,.22); color: #fff; }
    .et-week-modal-item-range { font-weight: 700; font-size: .9rem; color: inherit; }
    .et-week-modal-item.is-active .et-week-modal-item-range { color: #fff; }
    .et-week-modal-item-meta {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .75rem;
        color: #64748b;
        flex-wrap: wrap;
    }
    .et-week-modal-item.is-active .et-week-modal-item-meta { color: rgba(255,255,255,.85); }

    /* ══════════════════════════════════════════════
       Cards refresh (monochrome)
       ══════════════════════════════════════════════ */
    .emploi-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        transition: all .22s ease;
    }
    .emploi-card:hover {
        box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
        transform: translateY(-2px);
    }
    .emploi-card::before { background: #0453cb; }
    .emploi-card.active::before   { background: #10b981; }
    .emploi-card.expired::before  { background: #94a3b8; }
    .emploi-card.upcoming::before { background: #3b7ddb; }
    .emploi-card-title { color: #0f172a; }

    .emploi-info-pill { background: rgba(4,83,203,.08); border-color: rgba(4,83,203,.18); color: #0453cb; }
    .emploi-info-pill.primary { background: rgba(4,83,203,.1); border-color: rgba(4,83,203,.22); color: #0453cb; }
    .emploi-info-pill.info    { background: rgba(59,125,219,.1); border-color: rgba(59,125,219,.22); color: #1e40af; }
    .emploi-info-pill.success { background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.22); color: #047857; }

    .emploi-actions .btn-moderne.primary,
    .emploi-actions .btn-pdf {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #0453cb;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        transition: all .18s ease;
        text-decoration: none;
    }
    .emploi-actions .btn-pdf:hover {
        background: rgba(4,83,203,.08);
        border-color: #0453cb;
        color: #0453cb;
    }

    @media (max-width: 768px) {
        .et-week-nav { padding: .75rem; }
        .et-week-nav-left { min-width: 100%; }
        .et-week-nav-right { width: 100%; justify-content: stretch; }
        .et-week-nav-right .et-week-btn { flex: 1; justify-content: center; }
    }

    /* ══════════════════════════════════════════════
       Redesign v2 — alert strip, chips, search, sticky
       ══════════════════════════════════════════════ */

    /* Alert strip (conditionnelle, visible uniquement si expirés > 0) */
    .et-alert-strip {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .7rem 1rem;
        margin-bottom: .85rem;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        color: #991b1b;
        max-height: 48px;
        font-size: .86rem;
    }
    .et-alert-strip__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border-radius: 8px;
        background: rgba(220, 38, 38, .12);
        color: #dc2626;
        flex-shrink: 0;
    }
    .et-alert-strip__body { flex: 1; min-width: 0; }
    .et-alert-strip__body strong { color: #b91c1c; font-weight: 700; }
    .et-alert-strip__hint { color: #7f1d1d; opacity: .75; }
    .et-alert-strip__action {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .85rem;
        border-radius: 8px;
        border: 1px solid #dc2626;
        background: #fff;
        color: #b91c1c;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .18s ease;
        flex-shrink: 0;
    }
    .et-alert-strip__action:hover { background: #dc2626; color: #fff; }

    /* Chips filtrantes */
    .et-chips-bar {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .et-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem .85rem;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .18s ease;
        text-decoration: none;
        line-height: 1.2;
    }
    .et-chip:hover { border-color: #0453cb; color: #0453cb; background: rgba(4,83,203,.03); }
    .et-chip.is-active {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 12px rgba(4,83,203,.22);
    }
    .et-chip__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        padding: 1px 6px;
        border-radius: 999px;
        background: rgba(15,23,42,.06);
        color: #475569;
        font-size: .72rem;
        font-weight: 700;
    }
    .et-chip.is-active .et-chip__count { background: rgba(255,255,255,.22); color: #fff; }
    .et-chip__dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .et-chip__dot--success { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.18); }
    .et-chip__dot--danger  { background: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.18); }
    .et-chip__dot--muted   { background: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,.18); }
    .et-chips-separator {
        width: 1px;
        height: 24px;
        background: #e2e8f0;
        margin: 0 .25rem;
    }
    .et-chip--link {
        color: #0453cb;
        border-style: dashed;
        border-color: #cbd5e1;
    }
    .et-chip--link:hover { border-style: solid; }
    .et-chip--link i { font-size: .7rem; opacity: .7; }

    /* Recherche inline dans le header de la liste */
    .et-list-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .et-list-card__tools {
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
    }
    .et-search-inline {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    .et-search-inline > i.fa-search {
        position: absolute;
        left: .75rem;
        color: #94a3b8;
        font-size: .8rem;
        pointer-events: none;
    }
    .et-search-inline__input {
        padding: .4rem .75rem .4rem 2rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: .82rem;
        width: 220px;
        background: #fff;
        transition: all .18s ease;
    }
    .et-search-inline__input:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
        width: 260px;
    }
    .et-search-inline__clear {
        position: absolute;
        right: .3rem;
        padding: .2rem .4rem;
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: .9rem;
    }
    .et-search-inline__clear:hover { color: #dc2626; }

    /* Sticky week-navigator au scroll */
    .et-week-nav {
        position: sticky;
        top: 0;
        z-index: 20;
        backdrop-filter: blur(6px);
        background: rgba(255,255,255,.96);
    }

    /* Current week pill : bordure dorée discrète */
    .et-week-pill--current:not(.is-active) {
        border-color: #facc15 !important;
        box-shadow: 0 0 0 2px rgba(250,204,21,.12);
    }
    .et-week-pill--current:not(.is-active) .et-week-pill-range::after {
        content: '•';
        color: #facc15;
        margin-left: 4px;
    }

    /* Empty-state quand le filtre masque toutes les cards */
    .et-empty-filter {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        font-size: .88rem;
    }
    .et-empty-filter i { font-size: 1.6rem; color: #94a3b8; margin-bottom: .5rem; display: block; }

    @media (max-width: 768px) {
        .et-alert-strip { max-height: none; flex-wrap: wrap; }
        .et-alert-strip__action { margin-left: auto; }
        .et-search-inline__input,
        .et-search-inline__input:focus { width: 100%; }
        .et-search-inline { flex: 1 1 100%; }
        .et-list-card__tools { width: 100%; }
    }

    /* ══════════════════════════════════════════════
       Card EDT v1 — redesign compact premium
       ══════════════════════════════════════════════ */
    .et-card {
        position: relative;
        display: flex;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: .85rem;
        /* overflow visible pour ne pas clipper le dropdown kebab */
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
    }
    .et-card:hover {
        box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
        transform: translateY(-1px);
        border-color: #cbd5e1;
    }
    .et-card__stripe {
        width: 4px;
        flex-shrink: 0;
        display: block;
        border-radius: 14px 0 0 14px;
    }
    /* Dropdown kebab Alpine (.et-card__menu-pop) doit passer au-dessus des cards
       voisines hovered — celles-ci créent un nouveau stacking context via transform.
       On bumpe le z-index de la card parent quand le menu Alpine est ouvert. */
    .et-card { z-index: 1; }
    .et-card:has(.et-card__menu--open) { z-index: 50; }
    .et-card__menu-pop { z-index: 1060; }
    .et-card__inner {
        flex: 1;
        padding: 1rem 1.1rem;
        display: flex;
        flex-direction: column;
        gap: .7rem;
        min-width: 0;
    }

    /* Head : titre + badges */
    .et-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .et-card__titles { flex: 1; min-width: 0; }
    .et-card__title {
        margin: 0 0 .15rem 0;
        font-size: .98rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }
    .et-card__subtitle {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-size: .78rem;
        color: #64748b;
        font-weight: 500;
        flex-wrap: wrap;
    }
    .et-card__sep { color: #cbd5e1; font-weight: 400; }
    .et-card__badges { display: flex; gap: .35rem; flex-wrap: wrap; flex-shrink: 0; }
    .et-card__badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.4;
    }
    .et-card__badge i { font-size: .65rem; }
    .et-card__badge--danger  { background: #fee2e2; color: #b91c1c; }
    .et-card__badge--success { background: rgba(16,185,129,.14); color: #047857; }
    .et-card__badge--warning { background: rgba(245,158,11,.15); color: #b45309; }
    .et-card__badge--info    { background: rgba(4,83,203,.1); color: #0453cb; }

    /* Meta row */
    .et-card__meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        font-size: .82rem;
        color: #475569;
    }
    .et-card__meta-item {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 500;
    }
    .et-card__meta-item i { color: #94a3b8; font-size: .78rem; }
    .et-card__meta-item--muted {
        padding: .1rem .5rem;
        border-radius: 6px;
        background: #f1f5f9;
        font-size: .72rem;
        font-weight: 700;
        color: #475569;
    }

    /* Footer fraîcheur */
    .et-card__footer {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .72rem;
        color: #94a3b8;
        padding-top: .5rem;
        border-top: 1px dashed #e2e8f0;
    }
    .et-card__footer i { font-size: .7rem; }

    /* Actions */
    .et-card__actions {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-top: .25rem;
    }
    .et-card-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .5rem .85rem;
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        cursor: pointer;
        transition: all .18s ease;
        white-space: nowrap;
    }
    .et-card-btn:hover { border-color: #0453cb; color: #0453cb; background: rgba(4,83,203,.04); }
    .et-card-btn--ghost { color: #b91c1c; }
    .et-card-btn--ghost:hover { border-color: #dc2626; color: #b91c1c; background: #fee2e2; }
    .et-card-btn--primary {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        border-color: transparent;
        color: #fff;
        margin-left: auto;
        box-shadow: 0 4px 12px rgba(4,83,203,.22);
    }
    .et-card-btn--primary:hover {
        background: linear-gradient(135deg, #033a8e, #0453cb);
        color: #fff;
        box-shadow: 0 6px 18px rgba(4,83,203,.3);
    }
    .et-card-btn--icon {
        width: 36px;
        height: 36px;
        padding: 0;
        justify-content: center;
        color: #64748b;
    }

    /* Menu popover (edit / delete) */
    .et-card__menu { position: relative; }
    .et-card__menu-pop {
        position: absolute;
        top: calc(100% + 4px);
        right: 0;
        min-width: 160px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(15,23,42,.12), 0 4px 12px rgba(15,23,42,.06);
        padding: .3rem;
        z-index: 50;
    }
    .et-card__menu-pop a,
    .et-card__menu-pop button,
    .et-card__menu-item {
        display: flex;
        align-items: center;
        gap: .55rem;
        width: 100%;
        padding: .5rem .75rem;
        border-radius: 7px;
        font-size: .82rem;
        color: #1e293b;
        text-decoration: none;
        background: transparent;
        border: none;
        text-align: left;
        cursor: pointer;
        transition: background .15s ease;
    }
    .et-card__menu-pop a:hover,
    .et-card__menu-item:hover { background: rgba(4,83,203,.08); color: #0453cb; }
    .et-card__menu-item--danger { color: #b91c1c; }
    .et-card__menu-item--danger:hover { background: #fee2e2; color: #b91c1c; }
    .et-card__menu-pop form { margin: 0; }
    .et-card__menu-pop i { width: 14px; text-align: center; color: #94a3b8; }
    .et-card__menu-pop a:hover i,
    .et-card__menu-item:hover i { color: inherit; }

    /* Décoration subtile selon statut (bordure gauche existante via stripe suffit, on garde) */
    .et-card--expired { background: #fffcfc; }
    .et-card--active { background: #fafffe; }

    [x-cloak] { display: none !important; }

    /* ══════════════════════════════════════════════
       Empty-state intelligent : "Dupliquer la semaine précédente"
       ══════════════════════════════════════════════ */
    .et-duplicate-empty {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.75rem 1.5rem;
        background: linear-gradient(135deg, #f0f7ff 0%, #fafcff 100%);
        border: 1px solid #cfe0f5;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .et-duplicate-empty__icon {
        flex-shrink: 0;
        width: 56px; height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        box-shadow: 0 6px 18px rgba(4,83,203,.22);
    }
    .et-duplicate-empty__body { flex: 1; min-width: 0; }
    .et-duplicate-empty__title {
        margin: 0 0 .3rem 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }
    .et-duplicate-empty__text {
        margin: 0 0 .85rem 0;
        font-size: .88rem;
        color: #475569;
        line-height: 1.5;
    }
    .et-duplicate-empty__text strong { color: #0f172a; font-weight: 700; }
    .et-duplicate-empty__form {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin: 0;
    }
    .et-duplicate-empty__btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .65rem 1.3rem;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(4,83,203,.24);
        transition: all .2s ease;
    }
    .et-duplicate-empty__btn:hover {
        background: linear-gradient(135deg, #033a8e, #0453cb);
        box-shadow: 0 6px 20px rgba(4,83,203,.32);
        transform: translateY(-1px);
    }
    .et-duplicate-empty__link {
        color: #0453cb;
        font-size: .82rem;
        text-decoration: none;
        font-weight: 500;
    }
    .et-duplicate-empty__link:hover { text-decoration: underline; }

    @media (max-width: 576px) {
        .et-duplicate-empty { flex-direction: column; align-items: flex-start; padding: 1.25rem; }
        .et-duplicate-empty__btn { width: 100%; justify-content: center; }
    }

    @media (max-width: 576px) {
        .et-card__head { flex-direction: column; }
        .et-card__badges { width: 100%; }
        .et-card-btn--primary { margin-left: 0; flex: 1; justify-content: center; }
        .et-card__actions { flex-wrap: wrap; }
    }

    /* ══════════════════════════════════════════════
       Vue Compact row-dense — pour tenants 30+ classes
       ══════════════════════════════════════════════ */
    .emplois-compact-container {
        flex-direction: column;
        gap: .4rem;
        width: 100%;
    }

    /* Mobile < 768px : masquer le toggle compact (fallback cards) */
    @media (max-width: 767.98px) {
        .emploi-view-toggle label.et-toggle-compact,
        .emploi-view-toggle #compactView { display: none; }
    }

    .et-row {
        display: flex;
        align-items: stretch;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        transition: box-shadow .18s ease, border-color .18s ease, transform .18s ease;
        min-height: 64px;
    }
    .et-row:hover {
        box-shadow: 0 4px 14px rgba(4,83,203,.08);
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .et-row__stripe {
        width: 4px;
        flex-shrink: 0;
    }
    .et-row__content {
        flex: 1;
        display: grid;
        grid-template-columns: 2fr 1.3fr auto auto;
        gap: 1rem;
        align-items: center;
        padding: .7rem 1rem;
        min-width: 0;
    }
    .et-row__titles { min-width: 0; }
    .et-row__title {
        margin: 0 0 .1rem 0;
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .et-row__subtitle {
        font-size: .72rem;
        color: #64748b;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .et-row__subtitle .et-card__sep { color: #cbd5e1; margin: 0 .2rem; }
    .et-row__meta {
        display: flex;
        flex-direction: column;
        gap: .1rem;
        font-size: .76rem;
        color: #475569;
        min-width: 0;
    }
    .et-row__meta-line {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .et-row__meta-line i { color: #94a3b8; font-size: .72rem; }
    .et-row__badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        line-height: 1.3;
        flex-shrink: 0;
    }
    .et-row__badge i { font-size: .62rem; }
    .et-row__badge--danger  { background: #fee2e2; color: #b91c1c; }
    .et-row__badge--success { background: rgba(16,185,129,.14); color: #047857; }
    .et-row__badge--warning { background: rgba(245,158,11,.15); color: #b45309; }
    .et-row__badge--info    { background: rgba(4,83,203,.1); color: #0453cb; }
    .et-row__actions {
        display: flex;
        align-items: center;
        gap: .3rem;
        flex-shrink: 0;
    }
    .et-row__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: all .15s ease;
        text-decoration: none;
    }
    .et-row__btn:hover { border-color: #0453cb; color: #0453cb; background: rgba(4,83,203,.04); }
    .et-row__btn--pdf { color: #b91c1c; }
    .et-row__btn--pdf:hover { border-color: #dc2626; color: #b91c1c; background: #fee2e2; }
    .et-row__btn--primary {
        width: auto;
        padding: 0 .85rem;
        gap: .35rem;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border-color: transparent;
        font-size: .78rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(4,83,203,.18);
    }
    .et-row__btn--primary:hover {
        background: linear-gradient(135deg, #033a8e, #0453cb);
        color: #fff;
        box-shadow: 0 4px 14px rgba(4,83,203,.25);
    }
    .et-row__fresh {
        font-size: .68rem;
        color: #94a3b8;
        margin-top: .1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Responsive compact : réduire à 2 colonnes en <1200px */
    @media (max-width: 1199.98px) {
        .et-row__content {
            grid-template-columns: 1fr auto auto;
        }
        .et-row__meta { display: none; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" x-data="{
        statusFilter: '{{ request('status_chip', 'all') }}',
        searchQuery: '',
        matchesCard(status, haystack) {
            if (this.statusFilter !== 'all' && this.statusFilter !== status) return false;
            const q = this.searchQuery.trim().toLowerCase();
            return q === '' || (haystack || '').includes(q);
        },
        get visibleCardsCount() {
            let n = 0;
            document.querySelectorAll('.et-card[data-status]').forEach(el => {
                if (this.matchesCard(el.dataset.status, el.dataset.search)) n++;
            });
            return n;
        },
        get hasActiveFilter() {
            return this.statusFilter !== 'all' || this.searchQuery.trim() !== '';
        }
    }">
        <!-- Header premium -->
        <div class="emploi-temps-header">
            <div class="et-header-inner">
                <div class="et-header-left">
                    <div class="et-header-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h1>Gestion des emplois du temps</h1>
                        <p class="et-subtitle">{{ $anneeUniversitaireCourante->name ?? 'Aucune année active' }} · {{ $totalClassesTenant ?? 0 }} classes au total</p>
                    </div>
                </div>
                <div class="et-header-actions">
                    <button type="button" class="timetable-tips-btn" data-bs-toggle="modal" data-bs-target="#timetableTipsModal">
                        <i class="fas fa-lightbulb"></i> Tips
                    </button>
                    @if(auth()->user()->can('timetables.edit'))
                        <button type="button" class="et-header-btn" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                            <i class="fas fa-layer-group"></i> Modifier rapidement
                        </button>
                    @endif
                    @can('timetables.create')
                        <a href="{{ route('esbtp.emploi-temps.create') }}" class="et-header-btn et-header-btn--primary">
                            <i class="fas fa-plus-circle"></i> Nouveau
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        @php
            $anneeCourante = $anneeUniversitaireCourante ?? null;
            $anneeEndDate = $anneeCourante?->end_date ? \Carbon\Carbon::parse($anneeCourante->end_date) : null;
        @endphp
        @if($anneeCourante && $anneeEndDate && $anneeEndDate->isPast())
            <div class="alert alert-warning d-flex align-items-start gap-2 mt-3" role="alert">
                <i class="fas fa-exclamation-triangle mt-1"></i>
                <div>
                    <strong>Année universitaire échue :</strong>
                    {{ $anneeCourante->name }} s'est terminée le {{ $anneeEndDate->format('d/m/Y') }}.
                    Pensez à activer la nouvelle année courante.
                </div>
            </div>
        @endif

        {{-- Alert strip conditionnelle : visible uniquement si des EDT sont expirés --}}
        @if(($edtExpiresCount ?? 0) > 0)
            <div class="et-alert-strip" role="alert">
                <div class="et-alert-strip__icon"><i class="fas fa-circle-exclamation"></i></div>
                <div class="et-alert-strip__body">
                    <strong>{{ $edtExpiresCount }}</strong> emploi{{ $edtExpiresCount > 1 ? 's' : '' }} du temps expiré{{ $edtExpiresCount > 1 ? 's' : '' }}
                    <span class="et-alert-strip__hint">— à renouveler pour éviter toute rupture de planning</span>
                </div>
                <button type="button" class="et-alert-strip__action" @click="statusFilter = 'expired'; document.getElementById('emploisListAnchor')?.scrollIntoView({behavior:'smooth', block:'start'})">
                    Voir les expirés
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        @endif

        {{-- Chips filtrantes (Alpine) : filtrage client-side des cards par statut --}}
        <div class="et-chips-bar">
            <button type="button" class="et-chip" :class="{ 'is-active': statusFilter === 'all' }" @click="statusFilter = 'all'">
                <span class="et-chip__label">Tout</span>
                <span class="et-chip__count">{{ $emploisTemps->count() }}</span>
            </button>
            <button type="button" class="et-chip" :class="{ 'is-active': statusFilter === 'active' }" @click="statusFilter = 'active'">
                <span class="et-chip__dot et-chip__dot--success"></span>
                <span class="et-chip__label">Actif</span>
                <span class="et-chip__count">{{ $emploisTempsActifsCount }}</span>
            </button>
            <button type="button" class="et-chip" :class="{ 'is-active': statusFilter === 'expired' }" @click="statusFilter = 'expired'">
                <span class="et-chip__dot et-chip__dot--danger"></span>
                <span class="et-chip__label">Expiré</span>
                <span class="et-chip__count">{{ $edtExpiresCount ?? 0 }}</span>
            </button>
            <div class="et-chips-separator"></div>
            @can('timetables.create')
            @if(($classesSansEdtCount ?? 0) > 0)
                <button type="button"
                        class="et-chip et-chip--link"
                        data-bs-toggle="modal"
                        data-bs-target="#quickGenerateModal">
                    <span class="et-chip__dot et-chip__dot--muted"></span>
                    <span class="et-chip__label">{{ $classesSansEdtCount }} classe{{ $classesSansEdtCount > 1 ? 's' : '' }} sans emploi du temps</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            @endif
            @endcan
        </div>

        <!-- Navigateur de semaine -->
        @php
            $semainesList = $semaines ?? collect();
            $semaineActive = $semainesList->firstWhere('value', request('semaine'))
                ?? $semainesList->firstWhere('value', $semaineCouranteValue ?? null);
            $semaineIndex = $semaineActive ? $semainesList->search(fn ($s) => $s['value'] === $semaineActive['value']) : null;
        @endphp
        @if($semainesList->isNotEmpty())
            <div class="et-week-nav">
                <div class="et-week-nav-left">
                    <button type="button" class="et-week-btn et-week-btn--icon" id="weekPrevBtn" aria-label="Semaine précédente" {{ $semaineIndex === 0 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="et-week-current">
                        <div class="et-week-current-top">
                            <i class="fas fa-calendar-week"></i>
                            <span id="weekCurrentLabel">
                                @if($semaineActive)
                                    Semaine du {{ $semaineActive['label_long'] }}
                                @else
                                    Toutes les semaines
                                @endif
                            </span>
                        </div>
                        <div class="et-week-current-meta" id="weekCurrentMeta">
                            @if($semaineActive)
                                <span class="et-week-chip et-week-chip--{{ $semaineActive['status'] }}">
                                    @if($semaineActive['status'] === 'current') Semaine en cours
                                    @elseif($semaineActive['status'] === 'past') Semaine passée
                                    @else À venir
                                    @endif
                                </span>
                                <span class="et-week-meta-item"><i class="fas fa-layer-group"></i>{{ $semaineActive['count'] }} planning(s)</span>
                            @else
                                <span class="et-week-meta-item text-muted">Aucune semaine sélectionnée</span>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="et-week-btn et-week-btn--icon" id="weekNextBtn" aria-label="Semaine suivante" {{ $semaineIndex === $semainesList->count() - 1 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="et-week-nav-right">
                    <button type="button" class="et-week-btn et-week-btn--ghost" id="weekTodayBtn" {{ ! $semaineCouranteValue ? 'disabled' : '' }}>
                        <i class="fas fa-crosshairs"></i>Semaine courante
                    </button>
                    <button type="button" class="et-week-btn et-week-btn--ghost" id="weekAllBtn">
                        <i class="fas fa-list"></i>Toutes
                    </button>
                    <button type="button" class="et-week-btn et-week-btn--primary" data-bs-toggle="modal" data-bs-target="#weekPickerModal">
                        <i class="fas fa-calendar-alt"></i>Choisir une semaine
                    </button>
                </div>
            </div>
            <div class="et-week-rail" id="weekRail">
                @foreach($semainesList as $s)
                    <button type="button"
                            class="et-week-pill {{ $semaineActive && $semaineActive['value'] === $s['value'] ? 'is-active' : '' }} et-week-pill--{{ $s['status'] }}"
                            data-week-value="{{ $s['value'] }}"
                            title="{{ $s['label_long'] }} · {{ $s['count'] }} planning(s)">
                        <span class="et-week-pill-range">{{ $s['label_short'] }}</span>
                        <span class="et-week-pill-count">{{ $s['count'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Main content -->
            <div class="col-lg-8" id="emploisListAnchor">
                <div class="et-list-card">
                    <div class="card-header et-list-card__header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Liste des emplois du temps
                        </h5>
                        <div class="et-list-card__tools">
                            <div class="et-search-inline">
                                <i class="fas fa-search"></i>
                                <input type="search"
                                       class="et-search-inline__input"
                                       placeholder="Filtrer par classe ou filière..."
                                       x-model.debounce.300ms="searchQuery"
                                       aria-label="Filtrer les emplois du temps">
                                <button type="button"
                                        class="et-search-inline__clear"
                                        x-show="searchQuery.length > 0"
                                        @click="searchQuery = ''"
                                        aria-label="Effacer la recherche">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            <div class="emploi-view-toggle">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="viewMode" id="cardView" checked>
                                    <label class="btn btn-outline-secondary" for="cardView">
                                        <i class="fas fa-th-large"></i> Cards
                                    </label>

                                    <input type="radio" class="btn-check" name="viewMode" id="compactView">
                                    <label class="btn btn-outline-secondary et-toggle-compact" for="compactView">
                                        <i class="fas fa-bars"></i> Compact
                                    </label>

                                    <input type="radio" class="btn-check" name="viewMode" id="tableView">
                                    <label class="btn btn-outline-secondary" for="tableView">
                                        <i class="fas fa-table"></i> Tableau
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Vue en cards (par défaut) -->
                        <div id="cardsContainer" class="emplois-cards-container">
                            @include('esbtp.emploi-temps.partials.cards', [
                                'emploisTemps' => $emploisTemps,
                                'timetableShortcut' => $timetableShortcut ?? null,
                                'previousWeekValue' => $previousWeekValue ?? null,
                                'previousWeekPlanningCount' => $previousWeekPlanningCount ?? 0,
                            ])
                        </div>

                        <!-- Vue compact row-dense (masquée par défaut) -->
                        <div id="compactContainer" class="emplois-compact-container" style="display: none;">
                            @include('esbtp.emploi-temps.partials.cards-compact', [
                                'emploisTemps' => $emploisTemps,
                            ])
                        </div>

                        <!-- Vue tableau (masquée par défaut) -->
                        <div id="tableContainer" class="table-container" style="display: none;">
                            <div class="table-responsive">
                            <table class="table table-moderne datatable" id="emploiTempsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="col-classe">Classe</th>
                                    <th class="col-filiere">Filière</th>
                                    <th class="col-niveau">Niveau</th>
                                    <th class="col-annee">Année universitaire</th>
                                    <th class="col-periode">Période</th>
                                    <th class="col-dates">Dates</th>
                                    <th class="col-statut">Statut</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                    @include('esbtp.emploi-temps.partials.table-rows', ['emploisTemps' => $emploisTemps, 'timetableShortcut' => $timetableShortcut ?? null])
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec filtres -->
            <div class="col-lg-4">
                <div class="emploi-filter-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtrer les emplois du temps
                        </h6>
                    </div>
                    <div class="card-body">
                    <form action="{{ route('esbtp.emploi-temps.index') }}" method="GET" id="filterForm">
                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select class="form-select select2" id="filiere_id" name="filiere_id">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="niveau_id" class="form-label">Niveau d'études</label>
                            <select class="form-select select2" id="niveau_id" name="niveau_id">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="classe_id" class="form-label">Classe</label>
                            <select class="form-select select2" id="classe_id" name="classe_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Semaine : piloté par le navigateur au-dessus, select caché pour compat AJAX --}}
                        <select class="d-none" id="semaine" name="semaine" aria-hidden="true">
                            <option value="">Toutes les semaines</option>
                            @foreach($semainesList as $s)
                                <option value="{{ $s['value'] }}" {{ request('semaine') == $s['value'] ? 'selected' : '' }}>
                                    {{ $s['label_long'] }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mb-3">
                            <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: 8px;">
                                <label for="annee_id" class="form-label mb-0" style="font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Universitaire Courante</label>
                                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#yearChangeModal" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-blue); border: 1px solid rgba(6, 182, 212, 0.35); border-radius: 999px; padding: 2px 10px; font-weight: 600;">
                                    <i class="fas fa-info-circle me-1"></i>Changer d'année
                                </button>
                            </div>
                            <select name="annee_id" id="annee_id" class="form-select" style="background-color: #f8f9fa; cursor: not-allowed;" disabled>
                                @if(isset($anneeUniversitaireCourante))
                                    <option value="{{ $anneeUniversitaireCourante->id }}" selected>
                                        {{ $anneeUniversitaireCourante->name }} (Année en cours)
                                    </option>
                                @else
                                    <option value="" selected>Aucune année active</option>
                                @endif
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Les emplois du temps sont filtrés par l'année courante.
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="is_current" class="form-label">Emploi du temps courant</label>
                            <select class="form-select" id="is_current" name="is_current">
                                <option value="">Tous</option>
                                <option value="1" {{ request('is_current') == '1' ? 'selected' : '' }}>Courant uniquement</option>
                                <option value="0" {{ request('is_current') == '0' ? 'selected' : '' }}>Non courant</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="period_status" class="form-label">Statut automatique</label>
                            <select class="form-select" id="period_status" name="period_status">
                                <option value="">Tous les statuts</option>
                                <option value="current" {{ request('period_status') == 'current' ? 'selected' : '' }}>Actifs (période en cours)</option>
                                <option value="upcoming" {{ request('period_status') == 'upcoming' ? 'selected' : '' }}>Inactifs (période à venir)</option>
                                <option value="expired" {{ request('period_status') == 'expired' ? 'selected' : '' }}>Expirés</option>
                            </select>
                        </div>

                            <div class="d-grid gap-2">
                                <button type="button" id="applyFiltersBtn" class="btn-acasi primary">
                                    <i class="fas fa-search me-2"></i>Appliquer les filtres
                                </button>
                                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi secondary">
                                    <i class="fas fa-redo-alt me-2"></i>Réinitialiser
                                </a>
                            </div>
                    </form>
                </div>
            </div>

                <!-- Actions rapides -->
                <div class="emploi-filter-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @can('timetables.create')
                            <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi success">
                                <i class="fas fa-plus-circle me-2"></i>Créer emploi du temps
                            </a>
                            @endcan
                            <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn-acasi info">
                                <i class="fas fa-chart-pie me-2"></i>Répartition matières
                            </a>
                            <a href="{{ route('esbtp.planning-general.annuel') }}" class="btn-acasi warning">
                                <i class="fas fa-calendar-alt me-2"></i>Planning annuel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tips-modal" id="timetableTipsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-route me-2"></i>Guide rapide pour créer un emploi du temps</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="tips-steps">
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 1</span> Créer l’enseignant
                        </div>
                        <p class="text-muted mb-0">Ajoutez l’enseignant et renseignez sa spécialité pour les associations.</p>
                        <img src="{{ asset('assets/guides/timetable/step-1-teacher.png') }}" alt="Créer un enseignant">
                        <a class="tips-step-action" href="{{ route('esbtp.enseignants.create') }}">
                            <i class="fas fa-user-plus"></i>Créer un enseignant
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 2</span> Définir la disponibilité
                        </div>
                        <p class="text-muted mb-0">Configurez les créneaux disponibles avant de planifier.</p>
                        <img src="{{ asset('assets/guides/timetable/step-2-availability.png') }}" alt="Disponibilités enseignant">
                        <a class="tips-step-action" href="{{ url('esbtp/personnel/unified') }}">
                            <i class="fas fa-calendar-check"></i>Gérer les disponibilités
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 3</span> Planning général & volumes
                        </div>
                        <p class="text-muted mb-0">Associez les enseignants aux filières/niveaux et aux volumes horaires.</p>
                        <img src="{{ asset('assets/guides/timetable/step-3-planning.png') }}" alt="Planning général">
                        <a class="tips-step-action" href="{{ route('esbtp.planning-general.index') }}">
                            <i class="fas fa-layer-group"></i>Configurer le planning
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 4</span> Générer l’emploi du temps
                        </div>
                        <p class="text-muted mb-0">Lancez la génération rapide ou créez manuellement la semaine.</p>
                        <img src="{{ asset('assets/guides/timetable/step-4-generate.png') }}" alt="Génération emploi du temps">
                        <a class="tips-step-action" href="{{ route('esbtp.emploi-temps.create') }}">
                            <i class="fas fa-calendar-plus"></i>Créer l’emploi du temps
                        </a>
                    </div>
                </div>
                <div class="tips-note">
                    <i class="fas fa-info-circle me-2"></i>
                    Astuce : sans disponibilité ou volume horaire configuré, la duplication bascule en mode vide.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('esbtp.emploi-temps.bulk-edit') }}" id="bulk-edit-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEditModalLabel">
                        <i class="fas fa-layer-group me-2"></i>Modifier rapidement les séances
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Sélectionnez les classes avec un emploi du temps actif à modifier.</p>

                    {{-- Barre de recherche --}}
                    <div class="mb-3">
                        <input type="text" id="bulk-edit-search" class="form-control" placeholder="Rechercher une classe...">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulk-edit-select-all">
                            <label class="form-check-label" for="bulk-edit-select-all">Tout sélectionner</label>
                        </div>
                        <span class="badge bg-light text-dark" id="bulk-edit-count">{{ $emploisTempsActifs->count() }} actif(s)</span>
                    </div>

                    @if($emploisTempsActifs->isEmpty())
                        <div class="alert alert-warning mb-0">
                            Aucun emploi du temps actif n'est disponible pour la modification rapide.
                        </div>
                    @else
                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                            @foreach($emploisTempsActifs as $emploiTemps)
                                <label class="list-group-item d-flex align-items-center gap-3 bulk-edit-item"
                                       data-name="{{ strtolower($emploiTemps->classe->name ?? '') }}"
                                       data-titre="{{ strtolower($emploiTemps->titre ?? '') }}">
                                    <input class="form-check-input bulk-edit-checkbox" type="checkbox" name="ids[]" value="{{ $emploiTemps->id }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $emploiTemps->classe->name ?? 'Classe non définie' }}</div>
                                        <div class="small text-muted">
                                            {{ $emploiTemps->titre ?? 'Emploi du temps' }}
                                            @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                                                · {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    @if($emploiTemps->is_current)
                                        <span class="badge bg-success">Actuel</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="bulk-edit-submit" {{ $emploisTempsActifs->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-arrow-right me-1"></i>Ouvrir les emplois du temps
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($semainesList->isNotEmpty())
<div class="modal fade et-week-modal" id="weekPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Choisir une semaine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="et-week-modal-toolbar">
                    <input type="text" class="form-control" id="weekPickerSearch" placeholder="Rechercher une date (ex: 14/04, avril)…">
                    <button type="button" class="et-week-btn et-week-btn--ghost" id="weekPickerClear">
                        <i class="fas fa-list"></i>Voir toutes les semaines
                    </button>
                </div>
                @php
                    $grouped = $semainesList->groupBy('month_key');
                @endphp
                <div class="et-week-modal-list">
                    @foreach($grouped as $monthKey => $weeks)
                        <div class="et-week-modal-group" data-month="{{ strtolower($monthKey) }}">
                            <div class="et-week-modal-group-title">{{ ucfirst($monthKey) }}</div>
                            <div class="et-week-modal-grid">
                                @foreach($weeks as $s)
                                    <button type="button"
                                            class="et-week-modal-item et-week-pill--{{ $s['status'] }} {{ $semaineActive && $semaineActive['value'] === $s['value'] ? 'is-active' : '' }}"
                                            data-week-value="{{ $s['value'] }}"
                                            data-week-search="{{ strtolower($s['label_long']) }}">
                                        <div class="et-week-modal-item-range">
                                            {{ $s['start']->isoFormat('DD MMM') }}
                                            <i class="fas fa-arrow-right mx-1"></i>
                                            {{ $s['end']->isoFormat('DD MMM YYYY') }}
                                        </div>
                                        <div class="et-week-modal-item-meta">
                                            <span class="et-week-chip et-week-chip--{{ $s['status'] }}">
                                                @if($s['status'] === 'current') En cours
                                                @elseif($s['status'] === 'past') Passée
                                                @else À venir
                                                @endif
                                            </span>
                                            <span><i class="fas fa-layer-group me-1"></i>{{ $s['count'] }} EDT</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année universitaire ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les emplois du temps affichés se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des emplois du temps dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les emplois du temps de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les emplois du temps de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                @can('annees.view')
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>

@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false) && (auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']) || auth()->user()->can('timetables.create')))
<div class="modal fade" id="quickGenerateModal" tabindex="-1" aria-labelledby="quickGenerateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('esbtp.emploi-temps.quick-generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quickGenerateModalLabel">
                        <i class="fas fa-bolt me-2 text-warning"></i>Génération rapide des emplois du temps
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Classes concernées :</strong>
                        <div class="mt-2">
                            @if($timetableShortcut['missing'] > 0)
                                <div>• {{ $timetableShortcut['missing'] }} classe(s) sans emploi du temps (semaine courante)</div>
                            @endif
                            @if($timetableShortcut['expired'] > 0)
                                <div>• {{ $timetableShortcut['expired'] }} emploi(s) expiré(s) (semaine courante)</div>
                            @endif
                            @if($timetableShortcut['expiring_soon'] > 0)
                                <div>• {{ $timetableShortcut['expiring_soon'] }} emploi(s) expirant sous 3 jours (semaine prochaine)</div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quick-generate-semestre" class="form-label fw-semibold">
                            Période (obligatoire)
                        </label>
                        <select id="quick-generate-semestre" name="semestre" class="form-select" required>
                            <option value="" selected disabled>Choisir la période actuelle</option>
                            <option value="Semestre 1">Semestre 1</option>
                            <option value="Semestre 2">Semestre 2</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choisir les classes et le mode</label>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input class="form-check-input" type="checkbox" id="quick-generate-select-all">
                                        </th>
                                        <th>Classe</th>
                                        <th>Statut</th>
                                        <th>Période cible</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($timetableShortcut['items'] as $item)
                                        @php
                                            $classe = $item['class'];
                                            $hasSource = !empty($item['source']);
                                            $statusLabel = $item['status'] === 'missing'
                                                ? 'Sans emploi'
                                                : ($item['status'] === 'expiring_soon' ? 'Expire bientôt' : 'Expiré');
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="classes[]" value="{{ $classe->id }}" checked>
                                            </td>
                                            <td>
                                                <strong>{{ $classe->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">{{ $statusLabel }}</span>
                                            </td>
                                            <td>
                                                {{ $item['target_start']->format('d/m') }} → {{ $item['target_end']->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                <select name="modes[{{ $classe->id }}]" class="form-select form-select-sm">
                                                    <option value="empty" {{ $hasSource ? '' : 'selected' }}>Vide</option>
                                                    <option value="duplicate" {{ $hasSource ? 'selected' : 'disabled' }}>Dupliquer</option>
                                                </select>
                                                @if(!$hasSource)
                                                    <small class="text-muted">Aucune base à dupliquer</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <small class="text-muted">
                        Le système évite de créer des doublons si un emploi du temps existe déjà pour la période cible.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link me-auto" data-bs-toggle="modal" data-bs-target="#quickGenerateHelpModal">
                        <i class="fas fa-info-circle me-1"></i>Comment ça marche ?
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-bolt me-1"></i>Générer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickGenerateConfirmModal" tabindex="-1" aria-labelledby="quickGenerateConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickGenerateConfirmModalLabel">
                    <i class="fas fa-triangle-exclamation text-warning me-2"></i>Confirmer les séances ignorées
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Certaines séances ne pourront pas être dupliquées pour cause d'indisponibilité ou de conflit d'emploi du temps.</p>
                <div id="quickGenerateConflictList" class="d-grid gap-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="quickGenerateConfirmBtn">
                    Continuer quand même
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quickGenerateModal = document.getElementById('quickGenerateModal');
    if (!quickGenerateModal) {
        return;
    }

    const quickGenerateForm = quickGenerateModal.querySelector('form');
    const submitButton = quickGenerateForm ? quickGenerateForm.querySelector('button[type="submit"]') : null;
    const confirmModalEl = document.getElementById('quickGenerateConfirmModal');
    const confirmList = document.getElementById('quickGenerateConflictList');
    const confirmBtn = document.getElementById('quickGenerateConfirmBtn');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    let quickGenerateConfirmed = false;

    const selectAllCheckbox = document.getElementById('quick-generate-select-all');
    const checkboxes = () => quickGenerateModal.querySelectorAll('input[name="classes[]"]');

    const updateHeaderState = () => {
        const boxes = Array.from(checkboxes());
        const checked = boxes.filter((box) => box.checked).length;
        if (!selectAllCheckbox) {
            return;
        }
        selectAllCheckbox.checked = checked > 0 && checked === boxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < boxes.length;
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            selectAllCheckbox.indeterminate = false;
        });
    }

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateHeaderState);
    });

    updateHeaderState();

    if (quickGenerateForm) {
        quickGenerateForm.addEventListener('submit', async (event) => {
            if (quickGenerateConfirmed) {
                return;
            }

            event.preventDefault();

            const formData = new FormData(quickGenerateForm);
            const actionUrl = "{{ route('esbtp.emploi-temps.quick-generate.preview') }}";
            const token = quickGenerateForm.querySelector('input[name="_token"]')?.value || '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyse...';
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Erreur lors de la prévisualisation.');
                }

                if (payload.total_conflicts > 0 && confirmModal && confirmList) {
                    confirmList.innerHTML = payload.conflicts.map((group) => {
                        const itemsHtml = (group.items || []).map((item) => {
                            const badgeClass = item.reason === 'occupied' ? 'bg-danger' : 'bg-warning text-dark';
                            const reasonLabel = item.reason === 'occupied' ? 'Déjà occupé' : 'Indisponible';
                            return `
                                <li class="d-flex flex-wrap align-items-center gap-2 py-1">
                                    <span class="badge ${badgeClass}">${reasonLabel}</span>
                                    <span class="fw-semibold">${item.matiere}</span>
                                    <span class="text-muted">${item.enseignant}</span>
                                    <span class="text-muted">${item.jour} ${item.heure_debut}–${item.heure_fin}</span>
                                </li>
                            `;
                        }).join('');

                        return `
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="fw-semibold mb-2">${group.class_name}</div>
                                <ul class="list-unstyled mb-0">
                                    ${itemsHtml}
                                </ul>
                            </div>
                        `;
                    }).join('');

                    confirmModal.show();
                } else {
                    quickGenerateConfirmed = true;
                    quickGenerateForm.submit();
                }
            } catch (error) {
                alert(error.message || 'Erreur lors de la prévisualisation.');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-bolt me-1"></i>Générer';
                }
            }
        });
    }

    if (confirmBtn && quickGenerateForm) {
        confirmBtn.addEventListener('click', () => {
            quickGenerateConfirmed = true;
            if (confirmModal) {
                confirmModal.hide();
            }
            quickGenerateForm.submit();
        });
    }
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkEditModal = document.getElementById('bulkEditModal');
    if (!bulkEditModal) {
        return;
    }

    const selectAll = document.getElementById('bulk-edit-select-all');
    const submitButton = document.getElementById('bulk-edit-submit');
    const searchInput = document.getElementById('bulk-edit-search');
    const items = bulkEditModal.querySelectorAll('.bulk-edit-item');
    const checkboxes = () => bulkEditModal.querySelectorAll('.bulk-edit-checkbox');

    const isVisible = item => !item.classList.contains('d-none');

    const updateState = () => {
        const boxes = Array.from(checkboxes());
        const visibleBoxes = boxes.filter(box => isVisible(box.closest('.bulk-edit-item')));
        const checkedCount = boxes.filter((box) => box.checked).length;
        const visibleCheckedCount = visibleBoxes.filter(box => box.checked).length;

        if (selectAll) {
            selectAll.checked = visibleBoxes.length > 0 && visibleCheckedCount === visibleBoxes.length;
            selectAll.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < visibleBoxes.length;
        }
        if (submitButton) {
            submitButton.disabled = checkedCount === 0;
        }
    };

    // Recherche en temps réel
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            items.forEach(item => {
                const name = item.dataset.name || '';
                const titre = item.dataset.titre || '';
                const matches = !query || name.includes(query) || titre.includes(query);
                item.classList.toggle('d-none', !matches);
            });
            updateState();
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            // Ne sélectionner que les items visibles
            items.forEach(item => {
                if (isVisible(item)) {
                    const checkbox = item.querySelector('.bulk-edit-checkbox');
                    if (checkbox) checkbox.checked = selectAll.checked;
                }
            });
            selectAll.indeterminate = false;
            updateState();
        });
    }

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateState);
    });

    updateState();
});
</script>
@endpush

@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
<div class="modal fade" id="quickGenerateHelpModal" tabindex="-1" aria-labelledby="quickGenerateHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickGenerateHelpModalLabel">
                    <i class="fas fa-info-circle me-2 text-warning"></i>À propos de la génération rapide
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Objectif :</strong> créer rapidement des emplois du temps pour les classes sans planning, expirées ou qui expirent bientôt.</p>
                <ul class="mb-3">
                    <li><strong>Mode Vide :</strong> crée un emploi du temps sans séances.</li>
                    <li><strong>Mode Dupliquer :</strong> copie le dernier emploi du temps de la classe (séances + horaires).</li>
                </ul>
                <p class="mb-0"><strong>Dates générées automatiquement :</strong></p>
                <ul>
                    <li>Sans emploi du temps → semaine courante.</li>
                    <li>Expiré ou expiring sous 3 jours → semaine prochaine.</li>
                    <li>Expiré sans emploi actif → semaine courante.</li>
                </ul>
                <small class="text-muted">Astuce : décoche les classes que tu veux gérer manuellement.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Basculer entre les vues (cards / compact / tableau)
        const cardView = document.getElementById('cardView');
        const compactView = document.getElementById('compactView');
        const tableView = document.getElementById('tableView');
        const cardsContainer = document.getElementById('cardsContainer');
        const compactContainer = document.getElementById('compactContainer');
        const tableContainer = document.getElementById('tableContainer');

        const MOBILE_BREAKPOINT = 768;
        const isMobile = () => window.innerWidth < MOBILE_BREAKPOINT;

        // Gérer le changement de vue
        function toggleView() {
            // Mobile < 768px : compact force fallback sur cards (UX row-dense impossible sur écran étroit)
            if (compactView.checked && isMobile()) {
                compactView.checked = false;
                cardView.checked = true;
            }

            if (cardView.checked) {
                cardsContainer.style.display = 'grid';
                compactContainer.style.display = 'none';
                tableContainer.style.display = 'none';
                localStorage.setItem('emploiTempsViewMode', 'cards');
            } else if (compactView.checked) {
                cardsContainer.style.display = 'none';
                compactContainer.style.display = 'flex';
                tableContainer.style.display = 'none';
                localStorage.setItem('emploiTempsViewMode', 'compact');
            } else if (tableView.checked) {
                cardsContainer.style.display = 'none';
                compactContainer.style.display = 'none';
                tableContainer.style.display = 'block';
                localStorage.setItem('emploiTempsViewMode', 'table');

                // Réinitialiser DataTable si pas encore initialisé
                // Vérifier que jQuery et DataTables sont chargés
                if (typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
                    if (!$.fn.dataTable.isDataTable('#emploiTempsTable')) {
                        initializeDataTable();
                    }
                }
            }
        }

        cardView.addEventListener('change', toggleView);
        compactView.addEventListener('change', toggleView);
        tableView.addEventListener('change', toggleView);

        // Si on redimensionne sous 768px en mode compact → fallback cards
        window.addEventListener('resize', () => {
            if (compactView.checked && isMobile()) {
                cardView.checked = true;
                toggleView();
            }
        });

        // Restaurer la vue préférée de l'utilisateur
        const savedViewMode = localStorage.getItem('emploiTempsViewMode');
        if (savedViewMode === 'table') {
            tableView.checked = true;
        } else if (savedViewMode === 'compact' && !isMobile()) {
            compactView.checked = true;
        } else {
            cardView.checked = true;
        }
        toggleView();
        
        // Initialize DataTable (seulement quand nécessaire)
        function initializeDataTable() {
            const table = $('#emploiTempsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[3, 'desc'], [1, 'asc']]
            });
            return table;
        }

        // AJAX Refresh Function
        function fetchEmploisTempsData() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            debugLog('🔄 Fetching emplois temps with params:', Object.fromEntries(params));

            // Show loading overlay
            showOverlay();

            const targetUrl = `{{ route("esbtp.emploi-temps.refresh") }}?${params.toString()}`;

            fetch(targetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des emplois du temps.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update cards container
                    document.getElementById('cardsContainer').innerHTML = data.html_cards;

                    // Update compact container (3e vue row-dense)
                    if (data.html_compact !== undefined) {
                        document.getElementById('compactContainer').innerHTML = data.html_compact;
                    }

                    // Update table body
                    document.getElementById('tableBody').innerHTML = data.html_table;

                    // Reinitialize DataTable if in table view
                    if (tableView.checked && typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
                        if ($.fn.dataTable.isDataTable('#emploiTempsTable')) {
                            $('#emploiTempsTable').DataTable().destroy();
                        }
                        initializeDataTable();
                    }

                    // Update URL without reload
                    const newUrl = new URL(window.location);
                    params.forEach((value, key) => {
                        if (value) {
                            newUrl.searchParams.set(key, value);
                        } else {
                            newUrl.searchParams.delete(key);
                        }
                    });
                    history.pushState({}, '', newUrl);

                    debugLog('✅ Refresh completed: ' + data.count + ' emplois temps');
                } else {
                    debugError('❌ Error:', data.message);
                    alert('Erreur lors du chargement des données');
                }
            })
            .catch(error => {
                debugError('❌ Fetch error:', error);
                alert('Erreur de connexion au serveur: ' + error.message);
            })
            .finally(() => {
                hideOverlay();
            });
        }

        // Helper functions for overlay
        function showOverlay() {
            if (document.getElementById('loadingOverlay')) return;
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);z-index:9999;display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = '<div style="background:white;padding:20px;border-radius:8px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            document.body.appendChild(overlay);
        }

        function hideOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.remove();
        }

        // Initialize Select2 AFTER defining event listeners
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });
        }

        // Get all filter elements (same pattern as classes.index)
        const form = document.getElementById('filterForm');
        const filterInputs = form.querySelectorAll('select');

        // Add change listeners to ALL select elements (Select2 will trigger 'change' event on original element)
        filterInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                debugLog('🔄 Filter changed:', this.id || this.name, '=', this.value);
                fetchEmploisTempsData();
            });
        });

        // Event listener for "Appliquer les filtres" button
        document.getElementById('applyFiltersBtn').addEventListener('click', function(e) {
            e.preventDefault();
            debugLog('🔄 Apply filters button clicked');
            fetchEmploisTempsData();
        });

        // Prevent default form submission (safety fallback)
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('🔄 Form submit prevented');
            fetchEmploisTempsData();
            return false;
        });

        const searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('quick_generate')) {
            const quickGenerateModal = document.getElementById('quickGenerateModal');
            if (quickGenerateModal && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(quickGenerateModal);
                modal.show();
            }
        }
    });
</script>
@endpush

@push('scripts')
<script>
(function () {
    const weekSelect = document.getElementById('semaine');
    if (!weekSelect) { return; }

    const prevBtn = document.getElementById('weekPrevBtn');
    const nextBtn = document.getElementById('weekNextBtn');
    const todayBtn = document.getElementById('weekTodayBtn');
    const allBtn = document.getElementById('weekAllBtn');
    const rail = document.getElementById('weekRail');
    const pickerSearch = document.getElementById('weekPickerSearch');
    const pickerClear = document.getElementById('weekPickerClear');
    const pickerModal = document.getElementById('weekPickerModal');

    const currentLabel = document.getElementById('weekCurrentLabel');
    const currentMeta = document.getElementById('weekCurrentMeta');

    const values = Array.from(weekSelect.options)
        .map(o => o.value)
        .filter(v => v !== '');

    function setWeek(value) {
        if (weekSelect.value === value) { return; }
        weekSelect.value = value;
        weekSelect.dispatchEvent(new Event('change', { bubbles: true }));
        syncActiveState(value);
    }

    function syncActiveState(value) {
        document.querySelectorAll('[data-week-value]').forEach(el => {
            el.classList.toggle('is-active', el.dataset.weekValue === value);
        });
        if (prevBtn && nextBtn) {
            const idx = values.indexOf(value);
            prevBtn.disabled = idx <= 0;
            nextBtn.disabled = idx === -1 || idx >= values.length - 1;
        }
        const activePill = rail ? rail.querySelector('.et-week-pill.is-active') : null;
        if (activePill) {
            activePill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        const activeLabel = weekSelect.options[weekSelect.selectedIndex]?.textContent?.trim() || '';
        if (currentLabel) {
            currentLabel.textContent = value ? 'Semaine du ' + activeLabel : 'Toutes les semaines';
        }
        if (currentMeta) {
            const pill = document.querySelector('.et-week-pill[data-week-value="' + value + '"]');
            const count = pill ? pill.querySelector('.et-week-pill-count')?.textContent?.trim() : null;
            const statusClass = pill ? Array.from(pill.classList).find(c => c.startsWith('et-week-pill--')) : null;
            const statusKey = statusClass ? statusClass.replace('et-week-pill--', '') : null;
            const statusLabel = { current: 'Semaine en cours', past: 'Semaine passée', upcoming: 'À venir' }[statusKey] || '';
            if (value && count !== null) {
                currentMeta.innerHTML = '<span class="et-week-chip et-week-chip--' + (statusKey || 'upcoming') + '">' + statusLabel + '</span>' +
                    '<span class="et-week-meta-item"><i class="fas fa-layer-group"></i>' + count + ' planning(s)</span>';
            } else {
                currentMeta.innerHTML = '<span class="et-week-meta-item text-muted">Affichage de toutes les semaines</span>';
            }
        }
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const idx = values.indexOf(weekSelect.value);
            if (idx > 0) { setWeek(values[idx - 1]); }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const idx = values.indexOf(weekSelect.value);
            if (idx !== -1 && idx < values.length - 1) { setWeek(values[idx + 1]); }
        });
    }
    if (todayBtn) {
        const todayValue = @json($semaineCouranteValue ?? null);
        todayBtn.addEventListener('click', () => { if (todayValue) { setWeek(todayValue); } });
    }
    if (allBtn) {
        allBtn.addEventListener('click', () => { setWeek(''); });
    }
    if (rail) {
        rail.querySelectorAll('.et-week-pill').forEach(pill => {
            pill.addEventListener('click', () => setWeek(pill.dataset.weekValue));
        });
    }
    if (pickerModal) {
        pickerModal.querySelectorAll('.et-week-modal-item').forEach(item => {
            item.addEventListener('click', () => {
                setWeek(item.dataset.weekValue);
                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(pickerModal)?.hide();
                }
            });
        });
    }
    if (pickerSearch) {
        pickerSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.et-week-modal-item').forEach(item => {
                const match = !q || (item.dataset.weekSearch || '').includes(q) || (item.closest('.et-week-modal-group')?.dataset.month || '').includes(q);
                item.classList.toggle('d-none', !match);
            });
            document.querySelectorAll('.et-week-modal-group').forEach(group => {
                const hasVisible = Array.from(group.querySelectorAll('.et-week-modal-item')).some(i => !i.classList.contains('d-none'));
                group.classList.toggle('d-none', !hasVisible);
            });
        });
    }
    if (pickerClear) {
        pickerClear.addEventListener('click', () => {
            setWeek('');
            if (typeof bootstrap !== 'undefined' && pickerModal) {
                bootstrap.Modal.getInstance(pickerModal)?.hide();
            }
            if (pickerSearch) { pickerSearch.value = ''; pickerSearch.dispatchEvent(new Event('input')); }
        });
    }

    syncActiveState(weekSelect.value);
})();
</script>
@endpush
