@extends('layouts.app')

@section('title', 'Suivi des Paiements par Catégorie - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
    
    /* Styles pour les cartes d'étudiants */
    .student-card {
        padding: var(--space-md);
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .student-card.success {
        border-left-color: var(--success);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, rgba(16, 185, 129, 0.08) 100%);
        border: 1px solid rgba(16, 185, 129, 0.1);
    }
    .student-card.warning {
        border-left-color: var(--warning);
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.03) 0%, rgba(245, 158, 11, 0.08) 100%);
        border: 1px solid rgba(245, 158, 11, 0.1);
    }
    .student-card.danger {
        border-left-color: var(--danger);
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.03) 0%, rgba(239, 68, 68, 0.08) 100%);
        border: 1px solid rgba(239, 68, 68, 0.1);
    }
    
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-sm);
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    .student-details h6 {
        font-weight: 600;
        margin: 0 0 var(--space-xs) 0;
        color: var(--text-primary);
    }
    
    .student-details p {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin: 0;
    }
    
    .payment-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: var(--text-small);
    }
    
    .amount-info {
        text-align: right;
    }
    
    .amount-paid {
        font-weight: 600;
        color: #059669;
        font-size: 14px;
    }

    .amount-due {
        color: #374151;
        font-weight: 500;
        font-size: 14px;
    }
    
    .percentage-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: var(--text-small);
        font-weight: 600;
    }
    
    .percentage-badge.success { 
        background: rgba(16, 185, 129, 0.1); 
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .percentage-badge.warning { 
        background: rgba(245, 158, 11, 0.1); 
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .percentage-badge.danger { 
        background: rgba(239, 68, 68, 0.1); 
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .students-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-md);
    }
    
    /* Styles pour les catégories - ancien style visuel */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: var(--space-xl);
        margin-bottom: var(--space-xl);
    }
    
    .category-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        background: linear-gradient(135deg, var(--surface) 0%, rgba(255, 255, 255, 0.95) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }
    
    .category-card:hover {
        transform: translateY(-6px) rotate(0.5deg);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .category-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    
    .category-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .mini-stat {
        text-align: center;
        padding: var(--space-md);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.4));
        border-radius: var(--radius-medium);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .mini-stat:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
    }
    
    .mini-stat-value {
        font-size: var(--amount-medium);
        font-weight: 800;
        display: block;
        margin-bottom: var(--space-xs);
    }
    
    .mini-stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Barre de progression flat (WCAG 2.2 — icone + libelle obligatoires a cote) */
    .progress-bar-modern {
        height: 10px;
        background: #f1f5f9;
        border-radius: 8px;
        overflow: hidden;
        margin: var(--space-md) 0;
        position: relative;
    }

    .progress-fill-modern {
        height: 100%;
        border-radius: 8px;
        transition: width .6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .progress-fill-modern.success { background: #10b981; }
    .progress-fill-modern.warning { background: #f59e0b; }
    .progress-fill-modern.danger  { background: #dc2626; }

    /* Styles pour les onglets étudiants avec lazy loading */
    /* Tabs avec ombres et positionnement amélioré */
    .student-tabs-container {
        position: relative;
        margin-top: 2rem;
        margin-bottom: 0;
    }

    .student-tabs-container .nav-tabs {
        border: none;
        margin-bottom: 0;
        position: relative;
        z-index: 10;
        display: flex;
        gap: 8px;
        padding-left: 20px;
    }

    .student-tabs-container .nav-item {
        border: none;
        margin-bottom: 0;
    }

    .student-tabs-container .nav-link {
        border: none !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 12px 20px 12px 20px !important;
        color: #6b7280 !important;
        background: #f8fafc !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        position: relative;
        margin-bottom: 0 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .student-tabs-container .nav-link:hover {
        background: #f1f5f9 !important;
        color: #374151 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
    }

    .student-tabs-container .nav-link.active {
        background: #ffffff !important;
        color: #1f2937 !important;
        font-weight: 600 !important;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.10), 2px 0 8px rgba(0, 0, 0, 0.10), -2px 0 8px rgba(0, 0, 0, 0.10) !important;
        transform: translateY(0px) !important;
        border-bottom: none !important;
        z-index: 15;
        position: relative;
    }

    .student-tabs-container .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background: #ffffff;
        z-index: 20;
    }

    .tab-content {
        position: relative;
        z-index: 5;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0 12px 12px 12px;
        margin-top: -1px;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        border-top: none;
    }

    .tab-pane {
        background: transparent;
        border: none;
        border-radius: 0;
        padding: 24px;
        margin-top: 0;
        box-shadow: none;
        min-height: 200px;
    }

    .loading-spinner {
        text-align: center;
        padding: 40px;
    }

    .spinner-border {
        width: 2rem;
        height: 2rem;
    }

    /* SPINNER ISOLÉ - Force tous les styles - COPIÉ DE RÉINSCRIPTIONS */
    .paiement-spinner {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        background: white !important;
        border: none !important;
        margin: 0 !important;
        padding: 40px !important;
    }

    .paiement-spinner.hidden {
        display: none !important;
    }

    .paiement-spinner-icon {
        display: block !important;
        margin-bottom: 20px !important;
        text-align: center !important;
    }

    .paiement-spinner-icon i {
        font-size: 48px !important;
        color: #3b82f6 !important;
        animation: paiement-spin 1s linear infinite !important;
        transform-origin: center center !important;
    }

    .paiement-spinner-text {
        display: block !important;
        position: static !important;
        animation: none !important;
        font-size: 16px !important;
        color: #374151 !important;
        font-weight: 500 !important;
        margin: 0 !important;
    }

    /* Animation spinner EXACTEMENT comme réinscriptions */
    @keyframes paiement-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ═══════════════════════════════════════════════
       Namespace sc-* (suivi-categories premium)
       ═══════════════════════════════════════════════ */
    .sc-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.75rem 2rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        position: relative;
        /* overflow visible : dropdowns ne sont pas clipes */
    }

    /* KPIs categorie selectionnee (suivi-content partial) — premium cards avec top-border accent */
    .sc-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .sc-stat {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.1rem;
        position: relative;
        overflow: hidden;
        transition: box-shadow .2s, transform .15s;
    }
    .sc-stat:hover {
        box-shadow: 0 4px 16px rgba(15,23,42,.06);
        transform: translateY(-2px);
    }
    .sc-stat::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 12px 12px 0 0;
    }
    .sc-stat.is-success::before { background: #10b981; }
    .sc-stat.is-warning::before { background: #f59e0b; }
    .sc-stat.is-danger::before  { background: #dc2626; }
    .sc-stat.is-primary::before { background: #0453cb; }
    .sc-stat-head {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: .3rem;
        color: #64748b;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .sc-stat.is-success .sc-stat-head i { color: #10b981; }
    .sc-stat.is-warning .sc-stat-head i { color: #f59e0b; }
    .sc-stat.is-danger  .sc-stat-head i { color: #dc2626; }
    .sc-stat.is-primary .sc-stat-head i { color: #0453cb; }
    .sc-stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
        color: #0f172a;
        letter-spacing: -.01em;
    }
    .sc-stat.is-success .sc-stat-value { color: #047857; }
    .sc-stat.is-warning .sc-stat-value { color: #b45309; }
    .sc-stat.is-danger  .sc-stat-value { color: #b91c1c; }
    .sc-stat.is-primary .sc-stat-value { color: #0453cb; }
    .sc-stat-label {
        margin-top: .2rem;
        font-size: .75rem;
        color: #64748b;
        font-weight: 500;
    }
    @media (max-width: 992px) {
        .sc-stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .sc-stats-grid { grid-template-columns: 1fr; gap: .5rem; }
    }

    /* Hero KPIs (row 2 glass) */
    .sc-hero-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .75rem;
        margin-top: 1.5rem;
    }
    .sc-hero-kpi {
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 12px;
        padding: .9rem 1rem;
        color: #fff;
        transition: background .2s, border-color .2s;
    }
    .sc-hero-kpi:hover {
        background: rgba(255,255,255,.14);
        border-color: rgba(255,255,255,.24);
    }
    .sc-hero-kpi-head {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: .35rem;
        color: rgba(255,255,255,.8);
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .sc-hero-kpi-head i { font-size: .72rem; }
    .sc-hero-kpi-label { flex: 1; }
    .sc-hero-kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.01em;
        line-height: 1.1;
    }
    .sc-hero-kpi-unit {
        font-size: .72rem;
        font-weight: 600;
        color: rgba(255,255,255,.65);
        margin-left: .25rem;
    }
    .sc-hero-kpi-meta {
        margin-top: .2rem;
        font-size: .72rem;
        color: rgba(255,255,255,.65);
        font-weight: 500;
    }
    .sc-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .sc-hero-left {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        flex: 1;
        min-width: 0;
    }
    .sc-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .sc-hero h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #fff;
        margin: 0 0 .2rem;
        letter-spacing: -.01em;
    }
    .sc-hero p {
        color: rgba(255,255,255,.72);
        font-size: .88rem;
        margin: 0 0 .55rem;
    }
    .sc-hero-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .sc-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem .65rem;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 99px;
        font-size: .74rem;
        font-weight: 600;
        color: rgba(255,255,255,.94);
    }
    .sc-chip i { font-size: .7rem; }
    .sc-chip .sc-chip-btn {
        background: none; border: none; color: inherit;
        padding: 0 0 0 .25rem; margin-left: .1rem;
        cursor: pointer; opacity: .75; transition: opacity .2s;
        font-size: .72rem; text-decoration: none;
    }
    .sc-chip .sc-chip-btn:hover { opacity: 1; }
    .sc-chip--filter {
        background: rgba(251,191,36,.2);
        border-color: rgba(251,191,36,.5);
        color: #fef3c7;
    }
    .sc-chip--filter .sc-chip-btn { color: #fef3c7; opacity: .85; }

    .sc-hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .sc-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .5rem 1rem;
        font-size: .82rem;
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
        text-decoration: none;
        white-space: nowrap;
        font-family: inherit;
    }
    .sc-btn--glass {
        background: rgba(255,255,255,.15);
        color: #fff;
        border-color: rgba(255,255,255,.2);
    }
    .sc-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
    .sc-btn--white {
        background: #fff;
        color: #0453cb;
        border-color: transparent;
    }
    .sc-btn--white:hover { background: #f8fafc; color: #0453cb; transform: translateY(-1px); }
    .sc-btn i { font-size: .78rem; }

    /* Filter bar groupee */
    .sc-filters {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 1.25rem 1.1rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .sc-filters-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .85rem;
        flex-wrap: wrap;
    }
    .sc-filters-title {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: #0f172a;
        font-weight: 700;
        font-size: .88rem;
    }
    .sc-filters-title i {
        width: 28px; height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }
    .sc-filters-active {
        display: none;
        align-items: center;
        gap: .5rem;
    }
    .sc-filters-active.is-visible { display: inline-flex; }
    .sc-filter-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .65rem;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        border: 1px solid rgba(4,83,203,.18);
        border-radius: 99px;
        font-size: .72rem;
        font-weight: 600;
    }
    .sc-filter-clear {
        background: none; border: none;
        color: #94a3b8;
        font-size: .76rem;
        font-weight: 600;
        cursor: pointer;
        padding: .25rem .55rem;
        border-radius: 6px;
        transition: all .2s;
    }
    .sc-filter-clear:hover { background: #fee2e2; color: #dc2626; }
    .sc-filters-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1.3fr auto;
        gap: .75rem;
        align-items: end;
    }
    .sc-field label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: .3rem;
    }
    .sc-field .form-control,
    .sc-field .form-select {
        font-size: .88rem;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: .5rem .75rem;
        background-color: #fff;
        transition: border-color .2s, box-shadow .2s;
    }
    .sc-field .form-control:focus,
    .sc-field .form-select:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .sc-filter-submit {
        padding: .55rem 1rem;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
        font-size: .85rem;
        white-space: nowrap;
        height: 41px;
    }
    .sc-filter-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.25); }
    .sc-filter-submit i { margin-right: .3rem; }

    /* Toast (reuse same styles as pi-*) */
    .sc-toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: .5rem;
        max-width: 360px;
        pointer-events: none;
    }
    .sc-toast {
        background: #fff;
        border-radius: 12px;
        padding: .85rem 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.15);
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        border-left: 4px solid #10b981;
        pointer-events: auto;
        animation: sc-toast-in .25s ease forwards;
        font-size: .86rem;
    }
    .sc-toast.is-leaving { animation: sc-toast-out .2s ease forwards; }
    .sc-toast--error { border-left-color: #dc2626; }
    .sc-toast--warning { border-left-color: #f59e0b; }
    .sc-toast--info { border-left-color: #0453cb; }
    .sc-toast-icon {
        font-size: 1rem;
        color: #10b981;
        flex-shrink: 0;
        margin-top: .15rem;
    }
    .sc-toast--error .sc-toast-icon { color: #dc2626; }
    .sc-toast--warning .sc-toast-icon { color: #f59e0b; }
    .sc-toast--info .sc-toast-icon { color: #0453cb; }
    .sc-toast-body { flex: 1; color: #1e293b; line-height: 1.4; }
    .sc-toast-title { font-weight: 700; color: #0f172a; margin-bottom: .1rem; font-size: .88rem; }
    .sc-toast-close {
        background: none; border: none;
        color: #94a3b8;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0 .15rem;
        line-height: 1;
        margin-left: .15rem;
        transition: color .2s;
    }
    .sc-toast-close:hover { color: #475569; }

    @keyframes sc-toast-in  { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes sc-toast-out { to { opacity: 0; transform: translateX(20px); } }

    /* Responsive */
    @media (max-width: 992px) {
        .sc-hero-top { flex-direction: column; align-items: stretch; }
        .sc-hero-actions { justify-content: flex-start; }
        .sc-filters-row { grid-template-columns: 1fr 1fr; }
        .sc-filters-row .sc-field:nth-child(3) { grid-column: span 2; }
        .sc-filter-submit { grid-column: span 2; }
    }
    @media (max-width: 576px) {
        .sc-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
        .sc-hero h1 { font-size: 1.2rem; }
        .sc-hero p { font-size: .82rem; }
        .sc-filters-row { grid-template-columns: 1fr; }
        .sc-filters-row .sc-field:nth-child(3),
        .sc-filters-row .sc-filter-submit { grid-column: span 1; }
    }
</style>
@endsection

@section('content')
@php
    $anneeNom = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('name') ?? (date('Y').'-'.(date('Y')+1));
    $activeFilters = collect(['filiere_id', 'niveau_id', 'category_id'])
        ->filter(fn($k) => filled(request($k)))
        ->count();

    // Labels for breadcrumb chips
    $selectedFiliere = $filiereId ? $filieres->firstWhere('id', $filiereId) : null;
    $selectedNiveau = $niveauId ? $niveaux->firstWhere('id', $niveauId) : null;
    $selectedCategory = $categoryId ? $categories->firstWhere('id', $categoryId) : null;

    $removeParamUrl = function ($key) {
        $params = request()->query();
        unset($params[$key]);
        return route('esbtp.paiements.suivi-categories') . (!empty($params) ? '?' . http_build_query($params) : '');
    };
@endphp
<div class="dashboard-acasi">
    <div class="main-content">
        {{-- Hero premium sc-* --}}
        <div class="sc-hero">
            <div class="sc-hero-top">
                <div class="sc-hero-left">
                    <span class="sc-hero-icon"><i class="fas fa-chart-bar"></i></span>
                    <div>
                        <h1>Suivi des Paiements par Catégorie</h1>
                        <p>Vue d'ensemble des frais et de leur recouvrement</p>
                        <div class="sc-hero-chips">
                            <span class="sc-chip">
                                <i class="fas fa-calendar"></i>
                                {{ $anneeNom }}
                                <button type="button" class="sc-chip-btn" onclick="showYearChangeInfo()" title="Comment changer d'année ?" aria-label="Information changement d'année">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </span>
                            @if($selectedFiliere)
                                <span class="sc-chip sc-chip--filter" role="status">
                                    <i class="fas fa-filter"></i>
                                    Filière : {{ $selectedFiliere->name }}
                                    <a href="{{ $removeParamUrl('filiere_id') }}" class="sc-chip-btn" title="Retirer ce filtre" aria-label="Retirer le filtre filière">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            @endif
                            @if($selectedNiveau)
                                <span class="sc-chip sc-chip--filter" role="status">
                                    <i class="fas fa-filter"></i>
                                    Niveau : {{ $selectedNiveau->name }}
                                    <a href="{{ $removeParamUrl('niveau_id') }}" class="sc-chip-btn" title="Retirer ce filtre" aria-label="Retirer le filtre niveau">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            @endif
                            @if($selectedCategory)
                                <span class="sc-chip sc-chip--filter" role="status">
                                    <i class="fas fa-filter"></i>
                                    Catégorie : {{ $selectedCategory->name }}
                                    <a href="{{ $removeParamUrl('category_id') }}" class="sc-chip-btn" title="Retirer ce filtre" aria-label="Retirer le filtre catégorie">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="sc-hero-actions">
                    <a href="{{ route('esbtp.paiements.index') }}" class="sc-btn sc-btn--glass">
                        <i class="fas fa-list"></i>
                        <span>Liste des paiements</span>
                    </a>
                    @can('paiements.create')
                    <a href="{{ route('esbtp.paiements.create') }}" class="sc-btn sc-btn--white">
                        <i class="fas fa-plus"></i>
                        <span>Nouveau paiement</span>
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Row 2 : KPIs glass (AJAX-refreshable via #suivi-metrics-container) --}}
            <div id="suivi-metrics-container">
                @include('esbtp.paiements.partials.suivi-metrics')
            </div>
        </div>

        {{-- Filter bar groupee --}}
        <div class="sc-filters">
            <div class="sc-filters-head">
                <div class="sc-filters-title">
                    <i class="fas fa-filter"></i>
                    <span>Filtres</span>
                </div>
                <div class="sc-filters-active {{ $activeFilters > 0 ? 'is-visible' : '' }}">
                    <span class="sc-filter-badge">
                        <i class="fas fa-check-circle"></i>
                        {{ $activeFilters }} filtre{{ $activeFilters > 1 ? 's' : '' }} actif{{ $activeFilters > 1 ? 's' : '' }}
                    </span>
                    <button type="button" class="sc-filter-clear" onclick="scClearAllFilters()">
                        <i class="fas fa-times me-1"></i>Effacer tout
                    </button>
                </div>
            </div>
            <form id="suivi-filter-form" method="GET">
                <div class="sc-filters-row">
                    <div class="sc-field">
                        <label for="filiere_id">Filière</label>
                        <select name="filiere_id" id="filiere_id" class="form-select">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-field">
                        <label for="niveau_id">Niveau d'étude</label>
                        <select name="niveau_id" id="niveau_id" class="form-select">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-field">
                        <label for="category_id">Catégorie détaillée</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Vue d'ensemble</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="sc-filter-submit">
                        <i class="fas fa-filter"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        {{-- Content Section (AJAX-refreshable) --}}
        <div id="suivi-content-container">
            @include('esbtp.paiements.partials.suivi-content')
        </div>
    </div>
</div>

{{-- Toast container (feedback post-action) --}}
<div id="sc-toast-container" class="sc-toast-container" aria-live="polite" aria-atomic="true"></div>
@endsection

@push('scripts')
<script>
// ===== TOAST HELPER (sc-*) =====
window.showToast = window.showToast || function(message, type = 'success', title = null) {
    const container = document.getElementById('sc-toast-container');
    if (!container) return;

    const ICONS = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    const TITLES = { success: 'Succès', error: 'Erreur', warning: 'Attention', info: 'Information' };

    const toast = document.createElement('div');
    toast.className = 'sc-toast sc-toast--' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const icon = document.createElement('i');
    icon.className = 'fas fa-' + (ICONS[type] || 'info-circle') + ' sc-toast-icon';

    const body = document.createElement('div');
    body.className = 'sc-toast-body';
    const titleEl = document.createElement('div');
    titleEl.className = 'sc-toast-title';
    titleEl.textContent = title || TITLES[type] || '';
    const msgEl = document.createElement('div');
    msgEl.textContent = message;
    body.appendChild(titleEl);
    body.appendChild(msgEl);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'sc-toast-close';
    closeBtn.setAttribute('aria-label', 'Fermer');
    closeBtn.innerHTML = '&times;';

    toast.appendChild(icon);
    toast.appendChild(body);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    let dismissTimer = null;
    const removeToast = () => {
        if (dismissTimer) clearTimeout(dismissTimer);
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 220);
    };
    closeBtn.addEventListener('click', removeToast);
    dismissTimer = setTimeout(removeToast, 5000);
};

// ===== CLEAR ALL FILTERS =====
window.scClearAllFilters = function() {
    const form = document.getElementById('suivi-filter-form');
    if (!form) return;
    form.querySelectorAll('select').forEach(s => s.value = '');
    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
};

// ===== AJAX FILTERING SYSTEM =====
(function() {
    'use strict';

    // Build refresh URL with current filters
    function buildRefreshUrl() {
        const form = document.getElementById('suivi-filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        return '{{ route("esbtp.paiements.suivi-categories.refresh") }}?' + params.toString();
    }

    // Build index URL with current filters
    function buildIndexUrl() {
        const form = document.getElementById('suivi-filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        return '{{ route("esbtp.paiements.suivi-categories") }}?' + params.toString();
    }

    // Fetch and update content via AJAX
    function fetchSuiviData(url, { pushState = true } = {}) {
        const metricsContainer = document.getElementById('suivi-metrics-container');
        const contentContainer = document.getElementById('suivi-content-container');

        if (!metricsContainer || !contentContainer) {
            return;
        }

        // Show loading state - griser les sections
        metricsContainer.style.opacity = '0.5';
        metricsContainer.style.pointerEvents = 'none';
        metricsContainer.style.transition = 'opacity 0.2s ease';
        contentContainer.style.opacity = '0.5';
        contentContainer.style.pointerEvents = 'none';
        contentContainer.style.transition = 'opacity 0.2s ease';

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des données.');
            }
            return response.json();
        })
        .then(data => {
            // Update metrics
            if (data.metrics && metricsContainer) {
                metricsContainer.innerHTML = data.metrics;
            }

            // Update content
            if (data.content && contentContainer) {
                contentContainer.innerHTML = data.content;

                // Re-bind category card clicks after content update
                bindCategoryCardClicks();
                bindExportButtons();
            }

            // Update URL — push INDEX url (not /refresh AJAX endpoint)
            // so browser back button loads the real page, not the JSON endpoint
            if (pushState) {
                const indexUrl = buildIndexUrl();
                window.history.pushState({ url: indexUrl }, '', indexUrl);
            }

            // Reinitialize tooltips if needed
            if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        })
        .catch(error => {
            debugError(error);
            window.showToast(error.message || 'Impossible de charger les données pour le moment.', 'error');
        })
        .finally(() => {
            // Remove loading state
            metricsContainer.style.opacity = '1';
            metricsContainer.style.pointerEvents = 'auto';
            contentContainer.style.opacity = '1';
            contentContainer.style.pointerEvents = 'auto';
        });
    }

    // Bind export buttons (Excel + PDF) for each tab
    function bindExportButtons() {
        const form = document.getElementById('suivi-filter-form');

        function getFormFilters() {
            if (!form) return {};
            const data = new FormData(form);
            const params = {};
            for (const [key, value] of data.entries()) {
                if (value) params[key] = value;
            }
            return params;
        }

        // Excel export buttons
        document.querySelectorAll('.btn-suivi-export').forEach(btn => {
            // Remove old listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const statut = this.dataset.statut;
                const categoryId = this.dataset.categoryId;
                const filters = getFormFilters();
                const params = new URLSearchParams({ ...filters, category_id: categoryId });
                window.location.href = '{{ route("esbtp.paiements.suivi-categories.export.excel", ["statut" => "__STATUT__"]) }}'.replace('__STATUT__', statut) + '?' + params.toString();
            });
        });

        // PDF export buttons
        document.querySelectorAll('.btn-suivi-export-pdf').forEach(btn => {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const statut = this.dataset.statut;
                const categoryId = this.dataset.categoryId;
                const filters = getFormFilters();
                const params = new URLSearchParams({ ...filters, category_id: categoryId });
                window.location.href = '{{ route("esbtp.paiements.suivi-categories.export.pdf", ["statut" => "__STATUT__"]) }}'.replace('__STATUT__', statut) + '?' + params.toString();
            });
        });
    }

    // Bind clicks on category cards to filter by category via AJAX
    function bindCategoryCardClicks() {
        const categoryCards = document.querySelectorAll('.category-card-ajax');
        categoryCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryId = this.dataset.categoryId;

                // Update the category_id select
                const categorySelect = document.getElementById('category_id');
                if (categorySelect) {
                    categorySelect.value = categoryId;
                }

                // Trigger form submission via AJAX
                document.getElementById('suivi-filter-form').dispatchEvent(new Event('submit'));
            });
        });
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('suivi-filter-form');

        if (!form) {
            return;
        }

        // Bind form submission
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const url = buildRefreshUrl();
            fetchSuiviData(url, { pushState: true });
        });

        // Bind filter selects to auto-submit
        const filterSelects = form.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                form.dispatchEvent(new Event('submit'));
            });
        });

        // Initial binding of category cards and export buttons
        bindCategoryCardClicks();
        bindExportButtons();

        // Handle browser back/forward buttons
        // event.state.url is an INDEX url — convert to /refresh AJAX url
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.url) {
                var refreshUrl = event.state.url.replace(
                    '{{ route("esbtp.paiements.suivi-categories") }}',
                    '{{ route("esbtp.paiements.suivi-categories.refresh") }}'
                );
                fetchSuiviData(refreshUrl, { pushState: false });
            }
        });

        // Set initial history state
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        // ===== LAZY LOADING DES ONGLETS =====
        function initStudentTabsLazyLoading() {
            const tabLinks = document.querySelectorAll('.students-tabs a[data-statut]');

            tabLinks.forEach(tabLink => {
                tabLink.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('href');
                    const targetPane = document.querySelector(targetId);

                    // Vérifier si déjà chargé
                    if (targetPane && targetPane.getAttribute('data-loaded') === 'false') {
                        const statut = event.target.getAttribute('data-statut');
                        const categoryId = event.target.getAttribute('data-category-id');
                        const count = parseInt(event.target.getAttribute('data-count'));

                        // Si count = 0, afficher message vide directement
                        if (count === 0) {
                            const container = targetPane.querySelector('.students-list-container');
                            container.innerHTML = `
                                <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                    <p style="font-size: 16px; font-weight: 500;">Aucun étudiant dans cette catégorie</p>
                                </div>
                            `;
                            targetPane.setAttribute('data-loaded', 'true');
                            return;
                        }

                        // Charger les étudiants via AJAX
                        loadStudentsByStatut(statut, categoryId, targetPane);
                    }
                });
            });

            // Charger automatiquement le premier onglet actif
            const firstActiveTab = document.querySelector('.students-tabs a.active[data-statut]');
            if (firstActiveTab) {
                const targetId = firstActiveTab.getAttribute('href');
                const targetPane = document.querySelector(targetId);
                const statut = firstActiveTab.getAttribute('data-statut');
                const categoryId = firstActiveTab.getAttribute('data-category-id');
                const count = parseInt(firstActiveTab.getAttribute('data-count'));

                if (targetPane && targetPane.getAttribute('data-loaded') === 'false') {
                    if (count === 0) {
                        const container = targetPane.querySelector('.students-list-container');
                        container.innerHTML = `
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                <p style="font-size: 16px; font-weight: 500;">Aucun étudiant dans cette catégorie</p>
                            </div>
                        `;
                        targetPane.setAttribute('data-loaded', 'true');
                    } else {
                        loadStudentsByStatut(statut, categoryId, targetPane);
                    }
                }
            }
        }

        // Variable globale pour la recherche active
        let currentSearchQuery = '';

        function loadStudentsByStatut(statut, categoryId, targetPane, resetPage) {
            const container = targetPane.querySelector('.students-list-container');

            // Si resetPage demandé (ex: nouvelle recherche), repartir de 0
            if (resetPage) {
                targetPane.setAttribute('data-current-page', '0');
                targetPane.setAttribute('data-loaded', 'false');
            }

            const currentPage = parseInt(targetPane.getAttribute('data-current-page') || '0');
            const nextPage = currentPage + 1;

            // Construire l'URL avec les filtres actuels + recherche
            const urlParams = new URLSearchParams(window.location.search);
            const params = {
                category_id: categoryId,
                page: nextPage,
                per_page: 20,
                filiere_id: urlParams.get('filiere_id') || '',
                niveau_id: urlParams.get('niveau_id') || '',
                annee_id: urlParams.get('annee_id') || ''
            };

            // Ajouter le paramètre de recherche si présent
            if (currentSearchQuery) {
                params.search = currentSearchQuery;
            }

            const url = `/esbtp/paiements/suivi-categories/load/${statut}?` + new URLSearchParams(params).toString();

            // Afficher un indicateur de chargement si première page
            if (nextPage === 1) {
                container.innerHTML = `
                    <div class="text-center" style="padding: 40px 0;">
                        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p style="margin-top: 12px; color: #6b7280; font-size: 13px;">Chargement...</p>
                    </div>
                `;
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (nextPage === 1) {
                    // Première page : remplacer tout le contenu
                    container.innerHTML = data.html;
                } else {
                    // Pages suivantes : ajouter à la fin
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    const list = container.querySelector('.students-list');
                    if (list) {
                        list.appendChild(...tempDiv.children);
                    }
                }

                // Mettre à jour l'état
                targetPane.setAttribute('data-loaded', 'true');
                targetPane.setAttribute('data-current-page', data.current_page);
                targetPane.setAttribute('data-has-more', data.has_more);

                // Mettre à jour le compteur de recherche
                const searchCountEl = document.getElementById('suivi-search-count');
                const searchCountValueEl = document.getElementById('suivi-search-count-value');
                if (currentSearchQuery && searchCountEl && searchCountValueEl) {
                    searchCountValueEl.textContent = data.total;
                    searchCountEl.style.display = 'inline';
                } else if (searchCountEl) {
                    searchCountEl.style.display = 'none';
                }

                // Mettre à jour le nombre dans l'onglet correspondant au statut chargé
                const loadedTab = document.querySelector(`.students-tabs a[data-statut="${statut}"]`);
                if (loadedTab) {
                    const countSpan = loadedTab.querySelector('.student-count');
                    if (countSpan) {
                        countSpan.textContent = data.total;
                    }
                }

                // Ajouter bouton "Charger plus" si nécessaire
                if (data.has_more) {
                    addLoadMoreButton(container, statut, categoryId, targetPane);
                }
            })
            .catch(error => {
                debugError('Erreur lors du chargement des étudiants:', error);
                container.innerHTML = `
                    <div class="alert alert-danger" style="margin: 20px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des étudiants. Veuillez réessayer.
                    </div>
                `;
            });
        }

        function addLoadMoreButton(container, statut, categoryId, targetPane) {
            // Supprimer l'ancien bouton s'il existe
            const oldBtn = container.querySelector('.load-more-btn');
            if (oldBtn) oldBtn.remove();

            const btn = document.createElement('div');
            btn.className = 'load-more-btn';
            btn.style.cssText = 'text-align: center; margin-top: 24px; padding: 20px;';
            btn.innerHTML = `
                <button class="btn btn-primary" style="padding: 12px 32px; font-weight: 600;">
                    <i class="fas fa-chevron-down me-2"></i>
                    Charger plus d'étudiants
                </button>
            `;

            btn.querySelector('button').addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Chargement...';
                loadStudentsByStatut(statut, categoryId, targetPane);
            });

            container.appendChild(btn);
        }

        // Initialiser après le premier chargement
        initStudentTabsLazyLoading();

        // ===== BARRE DE RECHERCHE — debounce 300ms =====
        function initSearchHandler() {
            const searchInput = document.getElementById('suivi-search-input');
            const searchClear = document.getElementById('suivi-search-clear');
            const searchCount = document.getElementById('suivi-search-count');

            if (!searchInput) return;

            let searchTimeout = null;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();

                // Afficher/masquer le bouton clear
                if (searchClear) {
                    searchClear.style.display = query ? 'block' : 'none';
                }

                // Debounce 300ms
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearchQuery = query;

                    // Recharger l'onglet actif avec le filtre de recherche
                    const activeTab = document.querySelector('.students-tabs a.active[data-statut]');
                    if (activeTab) {
                        const statut = activeTab.getAttribute('data-statut');
                        const categoryId = activeTab.getAttribute('data-category-id');
                        const targetId = activeTab.getAttribute('href');
                        const targetPane = document.querySelector(targetId);

                        if (targetPane) {
                            loadStudentsByStatut(statut, categoryId, targetPane, true);
                        }
                    }

                    // Marquer les autres onglets comme non chargés pour forcer le rechargement
                    document.querySelectorAll('.students-tabs a[data-statut]:not(.active)').forEach(tab => {
                        const tabTargetId = tab.getAttribute('href');
                        const tabPane = document.querySelector(tabTargetId);
                        if (tabPane) {
                            tabPane.setAttribute('data-loaded', 'false');
                            tabPane.setAttribute('data-current-page', '0');
                        }
                    });
                }, 300);
            });

            // Bouton clear
            if (searchClear) {
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.focus();
                });
            }
        }
        initSearchHandler();

        // Réinitialiser après chaque refresh AJAX
        const originalFetchSuiviData = fetchSuiviData;
        fetchSuiviData = function(url, options = {}) {
            return originalFetchSuiviData(url, options).then(() => {
                // Attendre que le DOM soit mis à jour
                setTimeout(() => {
                    initStudentTabsLazyLoading();
                    initSearchHandler();
                    // Reset la recherche lors d'un changement de catégorie
                    currentSearchQuery = '';
                }, 100);
            });
        };
    });
})();

// ===== EXISTING SCRIPTS =====
$(function() {
    // Tooltip pour les pourcentages
    $('.percentage-badge').each(function() {
        const percentage = $(this).text();
        $(this).attr('title', `Taux de paiement: ${percentage}`);
    });

    // Animation simple au hover des cartes
    $('.card').hover(
        function() { $(this).addClass('shadow-lg'); },
        function() { $(this).removeClass('shadow-lg'); }
    );

    // ===== SYSTÈME LAZY LOADING - COPIÉ EXACT DES RÉINSCRIPTIONS =====
    @if($detailsCategorie)

    // Variables pour le système de lazy loading - EXACT COMME RÉINSCRIPTIONS
    let loadedTabs = {};
    let currentPage = {};

    const categoryId = {{ $categoryId ?? 'null' }};
    const baseParams = {
        @if(request('filiere_id'))
        filiere_id: {{ request('filiere_id') }},
        @endif
        @if(request('niveau_id'))
        niveau_id: {{ request('niveau_id') }},
        @endif
        @if(request('annee_id'))
        annee_id: {{ request('annee_id') }},
        @endif
        category_id: categoryId
    };

    debugLog("🚀 DEBUG PAIEMENTS: Page ready, initialisation du lazy loading");

    // Auto-charger le premier onglet avec des étudiants - EXACT COMME RÉINSCRIPTIONS
    const statistiques = {
        'non_payes': {{ $detailsCategorie['etudiants_non_payes']->count() }},
        'en_retard': {{ $detailsCategorie['etudiants_en_retard']->count() }},
        'a_jour': {{ $detailsCategorie['etudiants_a_jour']->count() }}
    };

    debugLog("📊 DEBUG PAIEMENTS: Statistiques disponibles:", statistiques);

    // Trouver la catégorie avec le plus d'étudiants
    let maxCategory = null;
    let maxCount = 0;

    Object.keys(statistiques).forEach(category => {
        if (statistiques[category] > maxCount) {
            maxCount = statistiques[category];
            maxCategory = category;
        }
    });

    debugLog(`🎯 DEBUG PAIEMENTS: Catégorie avec le plus d'étudiants: "${maxCategory}" (${maxCount} étudiants)`);

    if (maxCategory && maxCount > 0) {
        debugLog(`🚀 DEBUG PAIEMENTS: Activation de l'onglet "${maxCategory}"`);

        const tabLink = $(`.student-tab[data-statut="${maxCategory}"]`);
        const tabPane = $(`#${maxCategory}`);

        tabLink.addClass('active');
        tabPane.addClass('show active');

        // Cacher le spinner de cette catégorie car elle va être chargée
        const maxTabPane = $(`#${maxCategory}`);
        const maxSpinner = maxTabPane.find('.paiement-spinner');
        maxSpinner.addClass('hidden');

        debugLog(`📞 DEBUG PAIEMENTS: Appel loadTabContent("${maxCategory}")`);
        loadTabContent(maxCategory);
    }

    // Gestionnaire des clics sur les onglets - EXACT COMME RÉINSCRIPTIONS
    $('.student-tab').on('click', function(e) {
        e.preventDefault();

        const statut = $(this).data('statut');
        debugLog(`🖱️  DEBUG PAIEMENTS: Clic sur onglet "${statut}"`);

        // Gérer les onglets Bootstrap
        $('.student-tab').removeClass('active');
        $(this).addClass('active');

        $('.tab-pane').removeClass('show active');
        $(`#${statut}`).addClass('show active');

        // Charger le contenu si pas encore fait
        if (loadedTabs[statut]) {
            debugLog(`✅ DEBUG PAIEMENTS: Statut "${statut}" déjà en cache, pas de rechargement`);
        } else {
            debugLog(`🚀 DEBUG PAIEMENTS: Chargement par clic du statut "${statut}"`);
            loadTabContent(statut);
        }
    });

    // FONCTION PRINCIPALE - COPIE EXACTE DES RÉINSCRIPTIONS
    function loadTabContent(statut, page = 1) {
        debugLog(`🔥 DEBUG PAIEMENTS: loadTabContent("${statut}", ${page})`);

        const tabPane = $(`[data-statut="${statut}"]`);
        const loadingSpinner = tabPane.find('.paiement-spinner');
        const contentContainer = tabPane.find('.content-container');

        debugLog(`🔍 DEBUG PAIEMENTS: Éléments trouvés:`);
        debugLog(`  - tabPane:`, tabPane.length > 0, tabPane);
        debugLog(`  - loadingSpinner:`, loadingSpinner.length > 0, loadingSpinner);
        debugLog(`  - contentContainer:`, contentContainer.length > 0, contentContainer);

        // Afficher le spinner si c'est la première page
        if (page === 1) {
            debugLog(`🔄 DEBUG PAIEMENTS: Affichage du spinner pour page 1`);
            loadingSpinner.removeClass('hidden');
            contentContainer.hide();
        }

        const ajaxUrl = `{{ route('esbtp.paiements.suivi-categories.load', 'STATUT_PLACEHOLDER') }}`
            .replace('STATUT_PLACEHOLDER', statut);

        const params = {
            ...baseParams,
            page: page,
            per_page: 20
        };

        // Ajouter la recherche si active (variable partagée avec le système vanilla JS)
        if (typeof currentSearchQuery !== 'undefined' && currentSearchQuery) {
            params.search = currentSearchQuery;
        }

        debugLog(`📡 DEBUG PAIEMENTS: AJAX vers ${ajaxUrl} avec params:`, params);

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            data: params,
            success: function(response) {
                debugLog(`✅ DEBUG PAIEMENTS: AJAX Success pour "${statut}", page ${page}`);
                debugLog(`📊 DEBUG PAIEMENTS: Réponse:`, response);

                if (page === 1) {
                    debugLog(`🎯 DEBUG PAIEMENTS: Traitement première page`);
                    // Première page : remplacer le contenu
                    debugLog(`🚫 DEBUG PAIEMENTS: Masquage du spinner`);
                    loadingSpinner.addClass('hidden');

                    // Gérer les statuts vides
                    if (response.total === 0) {
                        debugLog(`🔍 DEBUG PAIEMENTS: Statut vide pour "${statut}"`);
                        contentContainer.html(`
                            <div style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <p>Aucun étudiant dans cette catégorie</p>
                            </div>
                        `);
                    } else {
                        contentContainer.html(response.html);
                    }

                    contentContainer.show();
                    loadedTabs[statut] = true;
                    currentPage[statut] = 1;

                } else {
                    debugLog(`➕ DEBUG PAIEMENTS: Ajout page ${page}`);
                    // Pages suivantes : ajouter le contenu directement
                    const studentsList = contentContainer.find('.students-list');
                    const newCards = $(response.html);
                    studentsList.append(newCards);
                    currentPage[statut] = page;
                    debugLog(`➕ ${newCards.length} nouvelles cartes ajoutées`);
                }

                // Mettre à jour le bouton "Charger plus"
                updateLoadMoreButton(statut, response);
            },
            error: function(xhr, status, error) {
                debugError(`❌ DEBUG PAIEMENTS: Erreur AJAX pour "${statut}":`, error);
                showErrorState(statut);
            }
        });
    }

    function loadMore(statut, nextPage) {
        debugLog(`🔄 DEBUG PAIEMENTS: loadMore("${statut}", ${nextPage})`);
        loadTabContent(statut, nextPage);
    }

    function updateLoadMoreButton(statut, response) {
        const container = $(`#${statut} .content-container`);
        container.find('.load-more-container').remove();

        if (response.has_more) {
            const nextPage = response.current_page + 1;
            const remaining = response.total - (response.current_page * 20);
            const loadMoreHtml = `
                <div class="load-more-container" style="text-align: center; margin: 30px 0 10px 0;">
                    <button class="btn btn-primary load-more-btn" data-statut="${statut}"
                            onclick="loadMore('${statut}', ${nextPage})"
                            style="padding: 12px 24px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #0453cb, #3b82f6); border: none; box-shadow: 0 4px 15px rgba(4, 83, 203, 0.3); transition: all 0.3s ease;">
                        <i class="fas fa-plus me-2"></i>Charger plus d'étudiants (${remaining} restants)
                    </button>
                </div>
            `;
            container.append(loadMoreHtml);
        }
    }

    function showErrorState(statut) {
        const tabPane = $(`[data-statut="${statut}"]`);
        const loadingSpinner = tabPane.find('.paiement-spinner');
        const contentContainer = tabPane.find('.content-container');

        debugLog(`🛑 DEBUG PAIEMENTS: Masquage spinner et affichage erreur`);
        loadingSpinner.addClass('hidden');
        contentContainer.html(`
            <div style="text-align: center; padding: 40px; color: #dc2626;">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Erreur lors du chargement des étudiants.</p>
                <button class="btn btn-outline-danger" onclick="loadTabContent('${statut}', 1)">
                    <i class="fas fa-redo me-2"></i>Réessayer
                </button>
            </div>
        `).show();
    }

    @endif
});

// Définition globale de la fonction loadMore pour les boutons onclick
@if($detailsCategorie)
window.loadMore = function(statut, nextPage) {
    debugLog(`🔄 DEBUG PAIEMENTS GLOBAL: loadMore("${statut}", ${nextPage})`);

    // Éviter les clics multiples
    const loadMoreBtn = event ? $(event.target) : $(`.load-more-btn[data-statut="${statut}"]`);
    if (loadMoreBtn.prop('disabled')) {
        debugLog('🚫 Bouton déjà en cours de chargement, abandon');
        return;
    }

    // Désactiver temporairement le bouton
    loadMoreBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Chargement...');

    const categoryId = {{ $categoryId ?? 'null' }};
    const baseParams = {
        @if(request('filiere_id'))
        filiere_id: {{ request('filiere_id') }},
        @endif
        @if(request('niveau_id'))
        niveau_id: {{ request('niveau_id') }},
        @endif
        @if(request('annee_id'))
        annee_id: {{ request('annee_id') }},
        @endif
        category_id: categoryId
    };

    const ajaxUrl = `{{ route('esbtp.paiements.suivi-categories.load', 'STATUT_PLACEHOLDER') }}`
        .replace('STATUT_PLACEHOLDER', statut);

    const params = {
        ...baseParams,
        page: nextPage,
        per_page: 20
    };

    debugLog(`📡 DEBUG PAIEMENTS GLOBAL: AJAX vers ${ajaxUrl}`);
    debugLog(`📋 DEBUG PAIEMENTS GLOBAL: Params:`, params);

    $.ajax({
        url: ajaxUrl,
        method: 'GET',
        data: params,
        success: function(response) {
            debugLog(`✅ SUCCESS: Page ${nextPage} chargée pour "${statut}"`);

            const contentContainer = $(`#${statut} .content-container`);
            const newStudents = $(response.html);

            // Ajouter les nouveaux étudiants à la liste existante
            const studentsList = contentContainer.find('.students-list');
            if (studentsList.length > 0) {
                // Les pages suivantes retournent directement les student-cards, pas dans un conteneur
                const newCards = $(response.html);
                studentsList.append(newCards);
                debugLog(`➕ ${newCards.length} nouvelles cartes ajoutées à .students-list`);
            } else {
                contentContainer.append(response.html);
                debugLog(`📄 Contenu ajouté directement au conteneur`);
            }

            // Supprimer l'ancien bouton charger plus
            contentContainer.find('.load-more-container').remove();

            // Ajouter le nouveau bouton si il y a plus de données
            if (response.has_more) {
                const nextPage = response.current_page + 1;
                const remaining = response.total - (response.current_page * 20);
                const loadMoreBtn = `
                    <div class="load-more-container" style="text-align: center; margin: 30px 0;">
                        <button class="btn btn-primary" onclick="loadMore('${statut}', ${nextPage})"
                                style="padding: 12px 24px; border-radius: 8px; font-weight: 500;">
                            <i class="fas fa-plus me-2"></i>Charger plus d'étudiants (${remaining} restants)
                        </button>
                    </div>
                `;
                contentContainer.append(loadMoreBtn);
            }
        },
        error: function(xhr, status, error) {
            debugError('❌ ERREUR AJAX:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            });

            // Réactiver le bouton en cas d'erreur
            loadMoreBtn.prop('disabled', false).html('<i class="fas fa-plus me-2"></i>Charger plus d\'étudiants');

            let errorMessage = 'Erreur lors du chargement';
            try {
                const errorData = JSON.parse(xhr.responseText);
                if (errorData.error) {
                    errorMessage = errorData.error;
                }
            } catch (e) {
                errorMessage = `Erreur ${xhr.status}: ${xhr.statusText}`;
            }

            window.showToast(errorMessage, 'error');
        }
    });
};
@endif

</script>
@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les paiements par catégorie se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
    
    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>