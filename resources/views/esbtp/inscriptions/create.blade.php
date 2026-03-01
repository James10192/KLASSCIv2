@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    /* ============================================
       VARIABLES KLASSCI OFFICIELLES
    ============================================ */
    :root {
        --kl-primary:        #0453cb;
        --kl-primary-dark:   #033a9e;
        --kl-primary-light:  #5e91de;
        --kl-primary-bg:     rgba(4, 83, 203, 0.06);
        --kl-primary-border: rgba(4, 83, 203, 0.18);
        --kl-success:        #10b981;
        --kl-success-bg:     rgba(16, 185, 129, 0.08);
        --kl-danger:         #ef4444;
        --kl-danger-bg:      rgba(239, 68, 68, 0.08);
        --kl-warning:        #f59e0b;
        --kl-warning-bg:     rgba(245, 158, 11, 0.08);
        --kl-info:           #0ea5e9;
        --kl-info-bg:        rgba(14, 165, 233, 0.08);
        --kl-surface:        #ffffff;
        --kl-bg:             #f1f5f9;
        --kl-text:           #111827;
        --kl-text-muted:     #6b7280;
        --kl-border:         #e2e8f0;
        --kl-radius:         12px;
        --kl-radius-lg:      16px;
        --kl-shadow:         0 1px 4px rgba(0,0,0,0.08), 0 4px 16px rgba(4,83,203,0.06);
        --kl-shadow-hover:   0 4px 16px rgba(4,83,203,0.16);
        --kl-transition:     all 0.22s ease;
    }

    /* ============================================
       STEPPER DE PROGRESSION
    ============================================ */
    .form-stepper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 32px;
        padding: 20px 16px;
        background: var(--kl-surface);
        border-radius: var(--kl-radius-lg);
        border: 1px solid var(--kl-border);
        box-shadow: var(--kl-shadow);
        flex-wrap: wrap;
    }

    .step-item {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }

    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--kl-bg);
        border: 2px solid var(--kl-border);
        color: var(--kl-text-muted);
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: var(--kl-transition);
    }

    .step-item.active .step-circle {
        background: var(--kl-primary);
        border-color: var(--kl-primary);
        color: white;
        box-shadow: 0 0 0 4px rgba(4,83,203,0.15);
    }

    .step-item.done .step-circle {
        background: var(--kl-success);
        border-color: var(--kl-success);
        color: white;
    }

    .step-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--kl-text-muted);
        margin-left: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .step-item.active .step-label { color: var(--kl-primary); }
    .step-item.done .step-label   { color: var(--kl-success); }

    .step-divider {
        flex: 1;
        height: 2px;
        background: var(--kl-border);
        margin: 0 8px;
        min-width: 20px;
        max-width: 60px;
    }

    @@media (max-width: 576px) {
        .step-label { display: none; }
        .step-divider { max-width: 20px; }
    }

    /* ============================================
       SECTIONS DU FORMULAIRE
    ============================================ */
    .form-section {
        background: var(--kl-surface);
        border: 1px solid var(--kl-border);
        border-radius: var(--kl-radius-lg);
        padding: 28px;
        margin-bottom: 24px;
        box-shadow: var(--kl-shadow);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--kl-bg);
    }

    .section-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--kl-primary);
        color: white;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .section-title-text {
        font-size: 16px;
        font-weight: 700;
        color: var(--kl-text);
        margin: 0;
    }

    .section-subtitle {
        font-size: 12px;
        color: var(--kl-text-muted);
        margin: 0;
    }

    .section-badge {
        margin-left: auto;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 50px;
    }

    .badge-optional {
        background: var(--kl-info-bg);
        color: var(--kl-info);
        border: 1px solid rgba(14,165,233,0.2);
    }

    /* ============================================
       CHAMPS DE FORMULAIRE
    ============================================ */
    .form-label {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-label .field-icon {
        color: var(--kl-primary-light);
        font-size: 11px;
    }

    .form-label .req { color: var(--kl-danger); margin-left: 2px; }
    .form-label .opt { color: var(--kl-text-muted); font-size: 10px; font-weight: 400; font-style: italic; }

    .form-control,
    .form-select {
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        padding: 10px 14px;
        font-size: 14px;
        color: var(--kl-text);
        background: var(--kl-surface);
        transition: var(--kl-transition);
        height: auto;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--kl-primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.12);
        background: var(--kl-surface);
        outline: none;
    }

    .form-control.is-invalid { border-color: var(--kl-danger); }
    .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }

    /* Champ en cours de vérification doublon */
    .form-control.checking-duplicate {
        border-color: var(--kl-warning);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23f59e0b' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpath d='M12 6v6l4 2'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 38px;
    }

    .form-control.duplicate-found {
        border-color: var(--kl-danger);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ef4444' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='15' y1='9' x2='9' y2='15'/%3E%3Cline x1='9' y1='9' x2='15' y2='15'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 38px;
    }

    .form-control.duplicate-ok {
        border-color: var(--kl-success);
    }

    /* ============================================
       ALERTE DOUBLON INLINE
    ============================================ */
    .duplicate-inline-alert {
        display: none;
        border-radius: var(--kl-radius);
        border: 1.5px solid var(--kl-danger);
        background: var(--kl-danger-bg);
        padding: 14px 16px;
        margin-top: 12px;
        margin-bottom: 4px;
    }

    .duplicate-inline-alert .alert-title {
        font-weight: 700;
        color: var(--kl-danger);
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .duplicate-inline-alert .alert-body {
        font-size: 13px;
        color: #7f1d1d;
    }

    .duplicate-inline-alert .btn-show-dupes {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        color: var(--kl-danger);
        background: white;
        border: 1.5px solid var(--kl-danger);
        border-radius: 50px;
        cursor: pointer;
        transition: var(--kl-transition);
    }

    .duplicate-inline-alert .btn-show-dupes:hover {
        background: var(--kl-danger);
        color: white;
    }

    /* ============================================
       MODAL DOUBLONS — DESIGN MODERNE
    ============================================ */
    #duplicateModal .modal-content {
        border: none;
        border-radius: var(--kl-radius-lg);
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    #duplicateModal .modal-header {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-bottom: 1px solid #fecaca;
        padding: 20px 24px;
    }

    #duplicateModal .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #duplicateModal .modal-body {
        padding: 24px;
        background: #fafafa;
    }

    #duplicateModal .modal-footer {
        border-top: 1px solid var(--kl-border);
        padding: 16px 24px;
        background: white;
    }

    .dupe-card {
        background: white;
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        transition: var(--kl-transition);
    }

    .dupe-card:hover {
        border-color: var(--kl-danger);
        box-shadow: 0 2px 8px rgba(239,68,68,0.1);
    }

    .dupe-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--kl-primary), var(--kl-primary-light));
        color: white;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dupe-info { flex: 1; min-width: 0; }

    .dupe-name {
        font-weight: 700;
        color: var(--kl-text);
        font-size: 15px;
    }

    .dupe-meta {
        font-size: 12px;
        color: var(--kl-text-muted);
        margin-top: 3px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .dupe-meta span { display: flex; align-items: center; gap: 4px; }

    .dupe-score-bar {
        height: 6px;
        border-radius: 3px;
        background: var(--kl-bg);
        margin-top: 8px;
        overflow: hidden;
    }

    .dupe-score-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease;
    }

    .score-high  .dupe-score-fill { background: var(--kl-danger); }
    .score-med   .dupe-score-fill { background: var(--kl-warning); }
    .score-low   .dupe-score-fill { background: var(--kl-text-muted); }

    .dupe-score-label {
        font-size: 11px;
        font-weight: 700;
        margin-top: 4px;
    }

    .score-high .dupe-score-label { color: var(--kl-danger); }
    .score-med  .dupe-score-label { color: var(--kl-warning); }
    .score-low  .dupe-score-label { color: var(--kl-text-muted); }

    .dupe-actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex-shrink: 0;
    }

    .btn-dupe-same {
        font-size: 11px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50px;
        background: var(--kl-primary);
        color: white;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        transition: var(--kl-transition);
    }

    .btn-dupe-same:hover { background: var(--kl-primary-dark); }

    .btn-dupe-view {
        font-size: 11px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50px;
        background: white;
        color: var(--kl-primary);
        border: 1.5px solid var(--kl-primary);
        cursor: pointer;
        white-space: nowrap;
        transition: var(--kl-transition);
    }

    .btn-dupe-view:hover { background: var(--kl-primary-bg); }

    #continue-with-duplicate {
        background: var(--kl-success);
        border-color: var(--kl-success);
        color: white;
        font-weight: 600;
        border-radius: var(--kl-radius);
        padding: 8px 20px;
    }

    #continue-with-duplicate:hover {
        background: #059669;
        border-color: #059669;
    }

    /* ============================================
       SECTION PARENTS — TOGGLE ACCORDÉON
    ============================================ */
    .parents-toggle-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        background: var(--kl-surface);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius-lg);
        cursor: pointer;
        transition: var(--kl-transition);
        user-select: none;
        margin-bottom: 0;
    }

    .parents-toggle-header:hover {
        border-color: var(--kl-primary);
        background: var(--kl-primary-bg);
    }

    .parents-toggle-header.open {
        border-color: var(--kl-primary);
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        background: var(--kl-primary-bg);
    }

    .parents-toggle-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .parents-toggle-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--kl-primary-bg);
        color: var(--kl-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .parents-toggle-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--kl-text);
    }

    .parents-toggle-sub {
        font-size: 12px;
        color: var(--kl-text-muted);
        margin-top: 2px;
    }

    .parents-toggle-chevron {
        color: var(--kl-primary);
        font-size: 14px;
        transition: transform 0.25s ease;
    }

    .parents-toggle-header.open .parents-toggle-chevron {
        transform: rotate(180deg);
    }

    .parents-body {
        display: none;
        background: var(--kl-surface);
        border: 1.5px solid var(--kl-primary);
        border-top: none;
        border-bottom-left-radius: var(--kl-radius-lg);
        border-bottom-right-radius: var(--kl-radius-lg);
        padding: 24px;
    }

    /* ============================================
       CARTES PARENTS
    ============================================ */
    .parent-item {
        background: var(--kl-bg);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        margin-bottom: 16px;
        overflow: hidden;
        transition: var(--kl-transition);
        animation: slideDown 0.25s ease;
    }

    @@keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .parent-item:hover {
        border-color: var(--kl-primary-border);
        box-shadow: var(--kl-shadow-hover);
    }

    .parent-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: white;
        border-bottom: 1px solid var(--kl-border);
    }

    .parent-card-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--kl-primary);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .parent-card-body { padding: 18px; }

    .btn-remove-parent {
        font-size: 11px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 50px;
        background: var(--kl-danger-bg);
        color: var(--kl-danger);
        border: 1px solid rgba(239,68,68,0.2);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: var(--kl-transition);
    }

    .btn-remove-parent:hover {
        background: var(--kl-danger);
        color: white;
        border-color: var(--kl-danger);
    }

    .btn-add-parent {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        background: var(--kl-primary-bg);
        color: var(--kl-primary);
        border: 1.5px dashed var(--kl-primary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--kl-transition);
        margin-top: 8px;
    }

    .btn-add-parent:hover {
        background: var(--kl-primary);
        color: white;
        border-style: solid;
    }

    /* Toggle existant/nouveau */
    .parent-type-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--kl-bg);
        border: 1px solid var(--kl-border);
        border-radius: 50px;
        padding: 4px 8px;
        margin-bottom: 16px;
        width: fit-content;
    }

    .parent-type-toggle .form-check-input {
        width: 14px;
        height: 14px;
        margin-top: 0;
        cursor: pointer;
    }

    .parent-type-toggle .form-check-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--kl-text-muted);
        cursor: pointer;
        margin: 0;
    }

    .parent-type-toggle:has(.form-check-input:checked) {
        background: var(--kl-primary-bg);
        border-color: var(--kl-primary-border);
    }

    /* ============================================
       CHOICES.JS — COULEURS KLASSCI
    ============================================ */
    .choices { margin-bottom: 0; font-size: 14px; }

    .choices__inner {
        background: var(--kl-surface);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        font-size: 14px;
        min-height: 44px;
        padding: 8px 14px 4px;
        transition: var(--kl-transition);
    }

    .choices__inner:focus-within {
        border-color: var(--kl-primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.12);
    }

    .choices__list--dropdown {
        background: var(--kl-surface);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        z-index: 1050;
        overflow: hidden;
    }

    .choices__item--selectable { padding: 10px 14px; font-size: 13px; }
    .choices__item--selectable:hover,
    .choices__item--selectable.is-highlighted {
        background: var(--kl-primary-bg);
        color: var(--kl-primary);
    }

    .choices__placeholder { color: #9ca3af; opacity: 1; }
    .choices__input { background: transparent; border: 0; font-size: 14px; }

    /* ============================================
       SECTION FRAIS
    ============================================ */
    .frais-card {
        background: white;
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        padding: 18px;
        margin-bottom: 16px;
        transition: var(--kl-transition);
    }

    .frais-card:hover { border-color: var(--kl-primary-border); }
    .frais-card.border-warning { border-color: var(--kl-warning) !important; }

    .resume-frais-card {
        background: linear-gradient(135deg, var(--kl-primary-bg), rgba(94,145,222,0.08));
        border: 1.5px solid var(--kl-primary-border);
        border-radius: var(--kl-radius);
        padding: 18px;
    }

    .resume-frais-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--kl-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ============================================
       STATUT AFFECTATION
    ============================================ */
    .affectation-info-card {
        border-radius: var(--kl-radius);
        padding: 16px;
        border: 1.5px solid var(--kl-border);
        background: var(--kl-bg);
        min-height: 110px;
        transition: var(--kl-transition);
    }

    /* ============================================
       BOUTONS PRINCIPAUX
    ============================================ */
    .btn-kl-primary {
        background: var(--kl-primary);
        color: white;
        border: none;
        border-radius: var(--kl-radius);
        padding: 12px 28px;
        font-weight: 700;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: var(--kl-transition);
        box-shadow: 0 2px 8px rgba(4,83,203,0.25);
    }

    .btn-kl-primary:hover {
        background: var(--kl-primary-dark);
        box-shadow: 0 4px 16px rgba(4,83,203,0.35);
        color: white;
        transform: translateY(-1px);
    }

    .btn-kl-secondary {
        background: white;
        color: var(--kl-text-muted);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius);
        padding: 12px 28px;
        font-weight: 600;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: var(--kl-transition);
        text-decoration: none;
    }

    .btn-kl-secondary:hover {
        border-color: var(--kl-primary);
        color: var(--kl-primary);
        text-decoration: none;
    }

    /* ============================================
       ALERTES
    ============================================ */
    .alert-kl {
        border-radius: var(--kl-radius);
        padding: 12px 16px;
        border: 1px solid transparent;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .alert-kl-info    { background: var(--kl-info-bg);    border-color: rgba(14,165,233,0.2);  color: #0c4a6e; }
    .alert-kl-warning { background: var(--kl-warning-bg); border-color: rgba(245,158,11,0.2);  color: #78350f; }
    .alert-kl-danger  { background: var(--kl-danger-bg);  border-color: rgba(239,68,68,0.2);   color: #7f1d1d; }
    .alert-kl-success { background: var(--kl-success-bg); border-color: rgba(16,185,129,0.2);  color: #064e3b; }

    /* ============================================
       DIVERS
    ============================================ */
    .choices.is-invalid .choices__inner { border-color: var(--kl-danger); }

    @@media (max-width: 768px) {
        .form-section { padding: 18px; }
        .parents-body  { padding: 16px; }
        .parent-card-body { padding: 14px; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Nouvelle Inscription</h1>
                <p class="header-subtitle">Enregistrement d'un nouvel étudiant</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Stepper de progression -->
        <div class="form-stepper" id="formStepper">
            <div class="step-item active" data-step="1">
                <div class="step-circle">1</div>
                <span class="step-label">Identité</span>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" data-step="2">
                <div class="step-circle">2</div>
                <span class="step-label">Académique</span>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" data-step="3">
                <div class="step-circle">3</div>
                <span class="step-label">Affectation</span>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" data-step="4">
                <div class="step-circle"><i class="fas fa-user-friends" style="font-size:12px"></i></div>
                <span class="step-label">Parents</span>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" data-step="5">
                <div class="step-circle">5</div>
                <span class="step-label">Frais</span>
            </div>
        </div>

        <form id="inscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="duplicate_override" id="duplicate_override" value="0">

            <!-- Erreurs globales -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- =============================================
                 SECTION 1 — INFORMATIONS PERSONNELLES
            ============================================== -->
            <div class="form-section" id="section-identite">
                <div class="section-header">
                    <div class="section-number">1</div>
                    <div>
                        <p class="section-title-text">Informations personnelles</p>
                        <p class="section-subtitle">Identité civile de l'étudiant</p>
                    </div>
                </div>

                <!-- Alerte doublon inline (affichée sous le nom/prénom) -->
                <div class="duplicate-inline-alert" id="duplicate-warning">
                    <div class="alert-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Doublon(s) potentiel(s) détecté(s)
                    </div>
                    <div class="alert-body" id="duplicate-warning-text">
                        Veuillez vérifier les informations avant de continuer.
                    </div>
                    <button type="button" class="btn-show-dupes" id="show-duplicates-modal">
                        <i class="fas fa-eye"></i> Voir les doublons
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-user field-icon"></i> Nom <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('nom') is-invalid @enderror"
                                   name="nom"
                                   id="nom-field"
                                   value="{{ old('nom') }}"
                                   required
                                   placeholder="Ex : KOUASSI">
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-user field-icon"></i> Prénom(s) <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('prenoms') is-invalid @enderror"
                                   name="prenoms"
                                   id="prenoms-field"
                                   value="{{ old('prenoms') }}"
                                   required
                                   placeholder="Ex : Jean-Marc">
                            @error('prenoms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-venus-mars field-icon"></i> Genre <span class="req">*</span>
                            </label>
                            <select class="form-control @error('sexe') is-invalid @enderror" name="sexe" required>
                                <option value="">Sélectionner</option>
                                <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                            @error('sexe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-calendar field-icon"></i> Date de naissance <span class="req">*</span>
                            </label>
                            <input type="date"
                                   class="form-control @error('date_naissance') is-invalid @enderror"
                                   name="date_naissance"
                                   value="{{ old('date_naissance') }}"
                                   required>
                            @error('date_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt field-icon"></i> Lieu de naissance <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('lieu_naissance') is-invalid @enderror"
                                   name="lieu_naissance"
                                   value="{{ old('lieu_naissance') }}"
                                   required
                                   placeholder="Ex : Abidjan">
                            @error('lieu_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-flag field-icon"></i> Nationalité <span class="req">*</span>
                            </label>
                            <select class="form-control @error('nationalite') is-invalid @enderror" name="nationalite" required>
                                @include('esbtp.partials.nationality-options', ['selected' => old('nationalite')])
                            </select>
                            @error('nationalite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-phone field-icon"></i> Téléphone <span class="req">*</span>
                            </label>
                            <input type="tel"
                                   class="form-control @error('telephone') is-invalid @enderror"
                                   name="telephone"
                                   value="{{ old('telephone') }}"
                                   required
                                   placeholder="+225 XX XX XXX XXX">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email_personnel') is-invalid @enderror"
                                   name="email_personnel"
                                   value="{{ old('email_personnel') }}"
                                   placeholder="exemple@email.com">
                            @error('email_personnel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4" id="matriculeContainer">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-id-card field-icon"></i>
                                Matricule <span class="req">*</span>
                                <span id="matriculeMode" class="badge bg-info ms-1" style="font-size:9px;"></span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control @error('matricule') is-invalid @enderror"
                                       name="matricule"
                                       id="matriculeInput"
                                       value="{{ old('matricule') }}"
                                       placeholder="Ex: MESBTP25-0001">
                                <button type="button" class="btn btn-outline-primary" id="generateMatriculeBtn" style="display:none;">
                                    <i class="fas fa-magic"></i> Générer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="checkMatriculeBtn" style="display:none;">
                                    <i class="fas fa-search"></i> Vérifier
                                </button>
                            </div>
                            <small class="text-muted" id="matriculeHelp">Matricule unique de l'étudiant</small>
                            <div id="matriculeStatus" class="mt-1"></div>
                            @error('matricule')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-camera field-icon"></i> Photo <span class="opt">(optionnel)</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('photo') is-invalid @enderror"
                                   name="photo"
                                   accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small class="text-muted">JPEG, PNG, JPG, GIF — max 2 Mo</small>
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-city field-icon"></i> Ville de résidence <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('ville') is-invalid @enderror"
                                   name="ville"
                                   value="{{ old('ville') }}"
                                   required
                                   placeholder="Ex : Abidjan">
                            @error('ville')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-map field-icon"></i> Commune <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('commune') is-invalid @enderror"
                                   name="commune"
                                   value="{{ old('commune') }}"
                                   required
                                   placeholder="Ex : Cocody">
                            @error('commune')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 2 — INFORMATIONS ACADÉMIQUES
            ============================================== -->
            <div class="form-section" id="section-academique">
                <div class="section-header">
                    <div class="section-number">2</div>
                    <div>
                        <p class="section-title-text">Informations académiques</p>
                        <p class="section-subtitle">Filière, niveau et année universitaire sont déduits de la classe</p>
                    </div>
                </div>

                <div class="alert-kl alert-kl-info mb-3">
                    <i class="fas fa-info-circle"></i>
                    <span>Sélectionnez une classe. La filière, le niveau et l'année universitaire seront automatiquement associés.</span>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        @include('components.forms.class-selector')
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 3 — STATUT D'AFFECTATION
            ============================================== -->
            <div class="form-section" id="section-affectation">
                <div class="section-header">
                    <div class="section-number">3</div>
                    <div>
                        <p class="section-title-text">Statut d'affectation gouvernementale</p>
                        <p class="section-subtitle">Détermine la prise en charge étatique et les frais applicables</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-university field-icon"></i> Statut d'affectation MESRS <span class="req">*</span>
                        </label>
                        <select class="form-select @error('affectation_status') is-invalid @enderror"
                                name="affectation_status"
                                id="affectation_status"
                                required
                                onchange="updateAffectationInfo()">
                            <option value="">Sélectionnez le statut d'affectation</option>
                            <option value="affecté"     {{ old('affectation_status') == 'affecté'     ? 'selected' : '' }}>Affecté</option>
                            <option value="réaffecté"   {{ old('affectation_status') == 'réaffecté'   ? 'selected' : '' }}>Réaffecté</option>
                            <option value="non_affecté" {{ old('affectation_status') == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                        </select>
                        @error('affectation_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-lightbulb me-1"></i>
                            Le statut influence les frais applicables selon la prise en charge étatique
                        </small>
                    </div>
                    <div class="col-md-6">
                        <div class="affectation-info-card" id="affectation-info">
                            <span class="text-muted" style="font-size:13px;">
                                <i class="fas fa-arrow-left me-2"></i>Sélectionnez un statut pour voir les détails
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 4 — PARENTS / TUTEURS (optionnel)
            ============================================== -->
            <div class="mb-4" id="section-parents">
                <!-- Toggle header cliquable -->
                <div class="parents-toggle-header" id="parents-toggle-btn" role="button" tabindex="0"
                     aria-expanded="false" aria-controls="parents-body">
                    <div class="parents-toggle-left">
                        <div class="parents-toggle-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="parents-toggle-title">
                                <i class="fas fa-user-friends me-2" style="color: var(--kl-primary);"></i>
                                Parents / Tuteurs
                                <span class="badge badge-optional ms-2">Optionnel</span>
                            </div>
                            <div class="parents-toggle-sub" id="parents-toggle-sub">
                                Cliquez pour ajouter les informations des parents ou tuteurs
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down parents-toggle-chevron"></i>
                </div>

                <!-- Corps de la section parents (masqué par défaut) -->
                <div class="parents-body" id="parents-body">
                    <div class="alert-kl alert-kl-info mb-4">
                        <i class="fas fa-info-circle"></i>
                        <span>Vous pouvez ajouter un ou plusieurs parents/tuteurs. Chaque section est optionnelle — si vous laissez les champs vides, aucun parent ne sera créé.</span>
                    </div>

                    <!-- Container des parents -->
                    <div id="parents-container">
                        <!-- Premier parent (index 0) — removable -->
                        <div class="parent-item" id="parent-0">
                            <div class="parent-card-header">
                                <h6 class="parent-card-title">
                                    <i class="fas fa-user-tie"></i> Parent / Tuteur #1
                                </h6>
                                <button type="button" class="btn-remove-parent remove-parent">
                                    <i class="fas fa-times"></i> Supprimer
                                </button>
                            </div>
                            <div class="parent-card-body">
                                <input type="hidden" name="parents[0][type]" value="nouveau">

                                <div class="parent-type-toggle mb-3">
                                    <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_0">
                                    <label class="form-check-label" for="parent_existant_0">
                                        <i class="fas fa-search me-1"></i> Sélectionner un parent existant
                                    </label>
                                </div>

                                <!-- Section parent existant -->
                                <div class="parent-existant-section" style="display:none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-search field-icon"></i> Rechercher un parent
                                            </label>
                                            <select class="form-control parent-select" id="parent_id_0" name="parents[0][parent_id]">
                                                <option value="">Sélectionner un parent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-link field-icon"></i> Relation avec l'étudiant
                                            </label>
                                            <select class="form-control" name="parents[0][relation]">
                                                <option value="">Sélectionner</option>
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section nouveau parent -->
                                <div class="parent-nouveau-section">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-user field-icon"></i> Nom</label>
                                            <input type="text" class="form-control" name="parents[0][nom]" placeholder="Nom du parent">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-user field-icon"></i> Prénom(s)</label>
                                            <input type="text" class="form-control" name="parents[0][prenoms]" placeholder="Prénom(s) du parent">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-phone field-icon"></i> Téléphone</label>
                                            <input type="tel" class="form-control" name="parents[0][telephone]" placeholder="+225 XX XX XXX XXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span></label>
                                            <input type="email" class="form-control" name="parents[0][email]" placeholder="email@exemple.com">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-briefcase field-icon"></i> Profession <span class="opt">(optionnel)</span></label>
                                            <input type="text" class="form-control" name="parents[0][profession]" placeholder="Ex : Ingénieur">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                            <select class="form-control" name="parents[0][relation]">
                                                <option value="">Sélectionner</option>
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label"><i class="fas fa-map-marker-alt field-icon"></i> Adresse <span class="opt">(optionnel)</span></label>
                                            <textarea class="form-control" name="parents[0][adresse]" rows="2" placeholder="Adresse complète du parent"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton ajouter un parent supplémentaire -->
                    <button type="button" id="add-parent-btn" class="btn-add-parent">
                        <i class="fas fa-plus"></i>
                        Ajouter un autre parent / tuteur
                    </button>
                </div>
            </div>

            <!-- Template caché pour clonage -->
            <div id="parent-template" style="display:none;">
                <div class="parent-item">
                    <div class="parent-card-header">
                        <h6 class="parent-card-title">
                            <i class="fas fa-user-tie"></i> Parent / Tuteur #<span class="parent-num"></span>
                        </h6>
                        <button type="button" class="btn-remove-parent remove-parent">
                            <i class="fas fa-times"></i> Supprimer
                        </button>
                    </div>
                    <div class="parent-card-body">
                        <input type="hidden" name="parents[template][type]" value="nouveau">

                        <div class="parent-type-toggle mb-3">
                            <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_template">
                            <label class="form-check-label" for="parent_existant_template">
                                <i class="fas fa-search me-1"></i> Sélectionner un parent existant
                            </label>
                        </div>

                        <div class="parent-existant-section" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-search field-icon"></i> Rechercher un parent</label>
                                    <select class="form-control parent-select" id="parent_id_template" name="parents[template][parent_id]">
                                        <option value="">Sélectionner un parent</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                    <select class="form-control" name="parents[template][relation]">
                                        <option value="">Sélectionner</option>
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="parent-nouveau-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-user field-icon"></i> Nom</label>
                                    <input type="text" class="form-control" name="parents[template][nom]" placeholder="Nom du parent">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-user field-icon"></i> Prénom(s)</label>
                                    <input type="text" class="form-control" name="parents[template][prenoms]" placeholder="Prénom(s) du parent">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-phone field-icon"></i> Téléphone</label>
                                    <input type="tel" class="form-control" name="parents[template][telephone]" placeholder="+225 XX XX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span></label>
                                    <input type="email" class="form-control" name="parents[template][email]" placeholder="email@exemple.com">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-briefcase field-icon"></i> Profession <span class="opt">(optionnel)</span></label>
                                    <input type="text" class="form-control" name="parents[template][profession]" placeholder="Ex : Ingénieur">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                    <select class="form-control" name="parents[template][relation]">
                                        <option value="">Sélectionner</option>
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-12">
                                    <label class="form-label"><i class="fas fa-map-marker-alt field-icon"></i> Adresse <span class="opt">(optionnel)</span></label>
                                    <textarea class="form-control" name="parents[template][adresse]" rows="2" placeholder="Adresse complète du parent"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 5 — FRAIS D'INSCRIPTION
            ============================================== -->
            <div class="form-section" id="section-frais">
                <div class="section-header">
                    <div class="section-number">5</div>
                    <div>
                        <p class="section-title-text">Frais d'inscription et options</p>
                        <p class="section-subtitle">Les frais obligatoires sont pré-sélectionnés selon la filière et le niveau</p>
                    </div>
                </div>

                <div class="alert-kl alert-kl-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Configuration des frais :</strong> Sélectionnez les options pour chaque catégorie. Sélectionnez d'abord une classe pour charger les frais applicables.</span>
                </div>

                <!-- Conteneur dynamique pour les frais -->
                <div id="fraisContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-muted" style="font-size:14px;">
                            <i class="fas fa-arrow-up me-2"></i>Sélectionnez d'abord une classe pour voir les frais applicables
                        </p>
                    </div>
                </div>

                <!-- Résumé des frais -->
                <div class="resume-frais-card mt-4">
                    <div class="resume-frais-title">
                        <i class="fas fa-calculator"></i>
                        Résumé des frais sélectionnés
                    </div>
                    <div id="resumeFrais">
                        <div class="text-center text-muted py-2" style="font-size:13px;">
                            Sélectionnez une classe et configurez les frais pour voir le résumé
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 BOUTONS DE SOUMISSION
            ============================================== -->
            <div class="d-flex justify-content-center gap-3 mt-2 mb-4">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-kl-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn-kl-primary">
                    <i class="fas fa-save"></i> Enregistrer l'inscription
                </button>
            </div>
        </form>

        <!-- =============================================
             MODAL DOUBLONS — dans le @section('content')
        ============================================== -->
        <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Doublons potentiels détectés
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3" style="font-size:13px;">
                            Les étudiants ci-dessous correspondent aux informations saisies.
                            Vérifiez qu'il ne s'agit pas de la même personne avant de continuer.
                        </p>
                        <div id="duplicate-modal-content">
                            <div class="alert-kl alert-kl-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Aucun doublon détecté.</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i> Corriger les informations
                        </button>
                        <button type="button" class="btn" id="continue-with-duplicate"
                                style="background:var(--kl-success);color:white;border:none;font-weight:600;border-radius:var(--kl-radius);padding:8px 20px;">
                            <i class="fas fa-check me-1"></i> Ce n'est pas un doublon — Continuer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.main-content -->
</div><!-- /.dashboard-acasi -->
@endsection

@push('scripts')
<!-- Choices.js -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let parentIndex = 1;
    let isLoadingFrais = false;

    // =============================================
    // REFS DOUBLON
    // =============================================
    const duplicateForm          = document.getElementById('inscriptionForm');
    const duplicateOverrideInput = document.getElementById('duplicate_override');
    const duplicateWarning       = document.getElementById('duplicate-warning');
    const duplicateWarningText   = document.getElementById('duplicate-warning-text');
    const duplicateModalElement  = document.getElementById('duplicateModal');
    const duplicateModalContent  = document.getElementById('duplicate-modal-content');
    const showDuplicatesBtn      = document.getElementById('show-duplicates-modal');
    const continueWithDuplicateBtn = document.getElementById('continue-with-duplicate');
    const duplicateCheckUrl      = "{{ route('esbtp.inscriptions.duplicates') }}";
    const nomField               = document.getElementById('nom-field');
    const prenomsField           = document.getElementById('prenoms-field');

    let duplicateModalInstance = null;
    if (duplicateModalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        duplicateModalInstance = new bootstrap.Modal(duplicateModalElement);
    }

    const duplicateState = { results: [], override: false };
    const duplicateInitialData = @json(session('duplicate_suggestions', []));
    const duplicateInitialOverride = {{ old('duplicate_override', '0') === '1' ? 'true' : 'false' }};

    if (Array.isArray(duplicateInitialData) && duplicateInitialData.length) {
        duplicateState.results = duplicateInitialData;
    }
    duplicateState.override = duplicateInitialOverride;
    if (duplicateState.override && duplicateOverrideInput) {
        duplicateOverrideInput.value = '1';
    }

    let duplicateTimer = null;

    function resetDuplicateOverride() {
        duplicateState.override = false;
        if (duplicateOverrideInput) duplicateOverrideInput.value = '0';
    }

    // =============================================
    // INDICATEURS VISUELS INLINE SUR LES CHAMPS
    // =============================================
    function setFieldState(state) {
        if (!nomField || !prenomsField) return;
        const fields = [nomField, prenomsField];
        fields.forEach(f => {
            f.classList.remove('checking-duplicate', 'duplicate-found', 'duplicate-ok');
            if (state) f.classList.add(state);
        });
    }

    // =============================================
    // DÉTECTION DOUBLONS
    // =============================================
    function scheduleDuplicateCheck() {
        if (!duplicateCheckUrl) return;
        if (duplicateTimer) clearTimeout(duplicateTimer);
        duplicateTimer = setTimeout(runDuplicateCheck, 600);
        resetDuplicateOverride();
        setFieldState('checking-duplicate');
    }

    function runDuplicateCheck() {
        if (!duplicateForm || !duplicateCheckUrl) return;

        const nomValue    = nomField ? nomField.value.trim() : '';
        const prenomsValue = prenomsField ? prenomsField.value.trim() : '';

        // Déclencher seulement si au moins l'un des champs a 2+ caractères
        if (nomValue.length < 2 && prenomsValue.length < 2) {
            duplicateState.results = [];
            setFieldState(null);
            updateDuplicateUI();
            return;
        }

        const dateField = duplicateForm.querySelector('input[name="date_naissance"]');
        const sexeField = duplicateForm.querySelector('select[name="sexe"]');

        const params = new URLSearchParams();
        params.append('nom', nomValue);
        params.append('prenoms', prenomsValue);
        if (dateField && dateField.value) params.append('date_naissance', dateField.value);
        if (sexeField && sexeField.value)  params.append('sexe', sexeField.value);

        fetch(`${duplicateCheckUrl}?${params.toString()}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            duplicateState.results = Array.isArray(data.duplicates) ? data.duplicates : [];
            resetDuplicateOverride();
            setFieldState(duplicateState.results.length > 0 ? 'duplicate-found' : 'duplicate-ok');
            updateDuplicateUI();
        })
        .catch(() => {
            duplicateState.results = [];
            setFieldState(null);
            updateDuplicateUI();
        });
    }

    function updateDuplicateUI() {
        if (!duplicateWarning || !duplicateWarningText) return;

        if (duplicateState.results.length > 0) {
            if (duplicateState.override) {
                duplicateWarning.style.display = 'none';
                if (duplicateOverrideInput) duplicateOverrideInput.value = '1';
                return;
            }
            duplicateWarning.style.display = 'block';
            const n = duplicateState.results.length;
            duplicateWarningText.textContent =
                `Nous avons trouvé ${n} étudiant${n > 1 ? 's' : ''} avec un profil similaire. Vérifiez avant de continuer.`;
            renderDuplicateModal();
        } else {
            duplicateWarning.style.display = 'none';
            if (duplicateOverrideInput) duplicateOverrideInput.value = '0';
            if (duplicateModalInstance) duplicateModalInstance.hide();
            if (duplicateModalContent) {
                duplicateModalContent.innerHTML = `
                    <div class="alert-kl alert-kl-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Aucun doublon détecté.</span>
                    </div>`;
            }
        }
    }

    function getInitials(fullName) {
        return (fullName || '?')
            .split(' ')
            .slice(0, 2)
            .map(p => p[0] || '')
            .join('')
            .toUpperCase();
    }

    function renderDuplicateModal() {
        if (!duplicateModalContent) return;

        if (duplicateState.results.length === 0) {
            duplicateModalContent.innerHTML = `
                <div class="alert-kl alert-kl-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Aucun doublon détecté.</span>
                </div>`;
            return;
        }

        const cards = duplicateState.results.map(item => {
            const score      = Number(item.score ?? 0);
            const scoreClass = score >= 80 ? 'score-high' : (score >= 60 ? 'score-med' : 'score-low');
            const initials   = getInitials(item.full_name);
            const matricule  = item.matricule  || 'N/A';
            const date       = item.date_naissance || 'N/A';
            const sexe       = item.sexe === 'M' ? 'Masculin' : (item.sexe === 'F' ? 'Féminin' : 'N/A');
            const tokens     = Array.isArray(item.matched_tokens) && item.matched_tokens.length
                ? `<span><i class="fas fa-tag"></i> ${item.matched_tokens.map(t => t.toUpperCase()).join(', ')}</span>`
                : '';
            const showUrl = item.show_url || '#';

            return `
                <div class="dupe-card ${scoreClass}">
                    <div class="dupe-avatar">${initials}</div>
                    <div class="dupe-info">
                        <div class="dupe-name">${item.full_name ?? ''}</div>
                        <div class="dupe-meta">
                            <span><i class="fas fa-id-card"></i> ${matricule}</span>
                            <span><i class="fas fa-calendar"></i> ${date}</span>
                            <span><i class="fas fa-venus-mars"></i> ${sexe}</span>
                            ${tokens}
                        </div>
                        <div class="dupe-score-bar mt-2">
                            <div class="dupe-score-fill" style="width:${Math.min(score, 100)}%"></div>
                        </div>
                        <div class="dupe-score-label">Similarité : ${Math.round(score)}%</div>
                    </div>
                    <div class="dupe-actions">
                        <button type="button" class="btn-dupe-same mark-duplicate" data-show-url="${showUrl}">
                            <i class="fas fa-user-check me-1"></i>C'est la même personne
                        </button>
                        <button type="button" class="btn-dupe-view view-duplicate" data-show-url="${showUrl}">
                            <i class="fas fa-external-link-alt me-1"></i>Voir la fiche
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        duplicateModalContent.innerHTML = cards;
    }

    // Init UI avec données de session (retour après erreur)
    updateDuplicateUI();

    // Listeners sur les champs déclencheurs
    if (nomField)    nomField.addEventListener('input', scheduleDuplicateCheck);
    if (prenomsField) prenomsField.addEventListener('input', scheduleDuplicateCheck);

    const dateInput = duplicateForm ? duplicateForm.querySelector('input[name="date_naissance"]') : null;
    const sexeSelect = duplicateForm ? duplicateForm.querySelector('select[name="sexe"]') : null;
    if (dateInput)  dateInput.addEventListener('change', scheduleDuplicateCheck);
    if (sexeSelect) sexeSelect.addEventListener('change', scheduleDuplicateCheck);

    // Bouton "Voir les doublons"
    if (showDuplicatesBtn) {
        showDuplicatesBtn.addEventListener('click', function() {
            renderDuplicateModal();
            if (duplicateModalInstance) duplicateModalInstance.show();
        });
    }

    // Bouton "Continuer — ce n'est pas un doublon"
    if (continueWithDuplicateBtn) {
        continueWithDuplicateBtn.addEventListener('click', function() {
            duplicateState.override = true;
            if (duplicateOverrideInput) duplicateOverrideInput.value = '1';
            if (duplicateModalInstance) duplicateModalInstance.hide();
            if (duplicateWarning) duplicateWarning.style.display = 'none';
            setFieldState(null);
        });
    }

    // Délégation click pour les boutons dans le modal
    document.addEventListener('click', function(e) {
        const markBtn = e.target.closest('.mark-duplicate');
        if (markBtn) {
            const url = markBtn.getAttribute('data-show-url');
            if (url && url !== '#') window.location.href = url;
            return;
        }
        const viewBtn = e.target.closest('.view-duplicate');
        if (viewBtn) {
            const url = viewBtn.getAttribute('data-show-url');
            if (url && url !== '#') window.open(url, '_blank');
        }
    });

    // Blocage submit si doublons non confirmés
    if (duplicateForm) {
        duplicateForm.addEventListener('submit', function(e) {
            if (duplicateState.results.length > 0 && !duplicateState.override) {
                e.preventDefault();
                renderDuplicateModal();
                if (duplicateModalInstance) duplicateModalInstance.show();
                else alert('Des doublons potentiels ont été détectés. Veuillez vérifier avant de continuer.');
            }
        });
    }

    // Check auto si champs déjà remplis (retour après erreur)
    if ((nomField && nomField.value.trim().length > 1) ||
        (prenomsField && prenomsField.value.trim().length > 1)) {
        scheduleDuplicateCheck();
    }

    // =============================================
    // TOGGLE SECTION PARENTS
    // =============================================
    const parentsToggleBtn = document.getElementById('parents-toggle-btn');
    const parentsBody      = document.getElementById('parents-body');
    const parentsToggleSub = document.getElementById('parents-toggle-sub');

    function updateParentToggleSub() {
        const count = document.querySelectorAll('#parents-container .parent-item').length;
        if (count === 0) {
            parentsToggleSub.textContent = 'Cliquez pour ajouter les informations des parents ou tuteurs';
        } else {
            parentsToggleSub.textContent = `${count} parent${count > 1 ? 's' : ''} ajouté${count > 1 ? 's' : ''}`;
        }
    }

    if (parentsToggleBtn && parentsBody) {
        // Restaurer état si erreur de validation et parents présents
        const hasParentData = document.querySelector('#parents-container input[name*="[nom]"]')?.value?.trim().length > 0
            || document.querySelector('#parents-container input[name*="[prenoms]"]')?.value?.trim().length > 0;

        if (hasParentData) {
            parentsToggleBtn.classList.add('open');
            parentsBody.style.display = 'block';
            parentsToggleBtn.setAttribute('aria-expanded', 'true');
        }

        parentsToggleBtn.addEventListener('click', function() {
            const isOpen = parentsBody.style.display === 'block';
            if (isOpen) {
                parentsBody.style.display = 'none';
                parentsToggleBtn.classList.remove('open');
                parentsToggleBtn.setAttribute('aria-expanded', 'false');
            } else {
                parentsBody.style.display = 'block';
                parentsToggleBtn.classList.add('open');
                parentsToggleBtn.setAttribute('aria-expanded', 'true');
            }
        });

        parentsToggleBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); parentsToggleBtn.click(); }
        });
    }

    // =============================================
    // GESTION PARENTS EXISTANTS
    // =============================================
    function loadParentsExistants(selectElement) {
        if (!selectElement) return;
        fetch('/esbtp/api/parents/search', {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.parents) {
                selectElement.innerHTML = '<option value="">Sélectionner un parent</option>';
                data.parents.forEach(parent => {
                    const opt = document.createElement('option');
                    opt.value = parent.id;
                    opt.textContent = `${parent.nom} ${parent.prenoms} - ${parent.telephone}`;
                    selectElement.appendChild(opt);
                });
            }
        })
        .catch(() => {});
    }

    // Checkbox toggle existant/nouveau
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('parent-existant-checkbox')) {
            const parentItem     = e.target.closest('.parent-item');
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection  = parentItem.querySelector('.parent-nouveau-section');
            const typeInput       = parentItem.querySelector('input[name*="[type]"]');

            if (e.target.checked) {
                if (existantSection) {
                    existantSection.style.display = 'block';
                    const sel = existantSection.querySelector('.parent-select');
                    if (sel) loadParentsExistants(sel);
                }
                if (nouveauSection) nouveauSection.style.display = 'none';
                if (typeInput) typeInput.value = 'existant';
            } else {
                if (existantSection) existantSection.style.display = 'none';
                if (nouveauSection) nouveauSection.style.display = 'block';
                if (typeInput) typeInput.value = 'nouveau';
            }
        }
    });

    // Supprimer un parent
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-parent') || e.target.closest('.remove-parent')) {
            e.preventDefault();
            const parentCard = e.target.closest('.parent-item');
            if (parentCard) {
                parentCard.remove();
                updateParentToggleSub();
                // Si plus de parents, fermer la section et ajouter un nouveau automatiquement
                if (document.querySelectorAll('#parents-container .parent-item').length === 0) {
                    // Ne pas fermer — l'utilisateur peut vouloir en rajouter
                }
            }
        }
    });

    // Ajouter un parent
    document.addEventListener('click', function(e) {
        if (e.target.id === 'add-parent-btn' || e.target.closest('#add-parent-btn')) {
            e.preventDefault();
            addNewParent();
        }
    });

    function addNewParent() {
        const template       = document.getElementById('parent-template');
        const parentsContainer = document.getElementById('parents-container');
        if (!template || !parentsContainer) return;

        const newParent = template.cloneNode(true);
        newParent.id = '';
        newParent.style.display = 'block';

        // Remplacer "template" par l'index courant
        newParent.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) el.name = el.name.replace('[template]', `[${parentIndex}]`);
            if (el.id)   el.id   = el.id.replace('_template', `_${parentIndex}`);
        });
        newParent.querySelectorAll('label[for]').forEach(l => {
            const f = l.getAttribute('for');
            if (f) l.setAttribute('for', f.replace('_template', `_${parentIndex}`));
        });

        // Mettre à jour le numéro dans le titre
        const numSpan = newParent.querySelector('.parent-num');
        if (numSpan) numSpan.textContent = parentIndex + 1;

        parentsContainer.appendChild(newParent);
        parentIndex++;
        updateParentToggleSub();
        newParent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // =============================================
    // SAUVEGARDE / RESTAURATION DONNÉES FORMULAIRE
    // =============================================
    function saveFormData() {
        const formData = {};
        const form = document.getElementById('inscriptionForm');
        form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select, textarea')
            .forEach(input => {
                if (input.name && input.value) formData[input.name] = input.value;
            });
        const photoInput = document.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files.length > 0) {
            formData['photo_filename'] = photoInput.files[0].name;
        }
        return formData;
    }

    function restoreFormData(formData) {
        Object.keys(formData).forEach(name => {
            const input = document.querySelector(`[name="${name}"]`);
            if (input && name !== 'photo_filename') input.value = formData[name];
        });
        if (formData['photo_filename']) {
            const photoInput = document.querySelector('input[name="photo"]');
            if (photoInput && photoInput.files.length === 0) {
                const infoDiv = document.createElement('div');
                infoDiv.className = 'alert-kl alert-kl-info mt-2';
                infoDiv.innerHTML = `<i class="fas fa-info-circle"></i><span>Photo précédemment sélectionnée : ${formData['photo_filename']}. Veuillez la resélectionner si nécessaire.</span>`;
                photoInput.parentNode.appendChild(infoDiv);
            }
        }
    }

    // =============================================
    // CHARGEMENT FRAIS PAR CLASSE
    // =============================================
    document.addEventListener('change', function(e) {
        if (e.target.id === 'classe_id') {
            if (isLoadingFrais) return;
            const classeId = e.target.value;
            const fraisContainer = document.getElementById('fraisContainer');
            if (classeId && fraisContainer) {
                isLoadingFrais = true;
                const savedData = saveFormData();
                fraisContainer.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
                            <span class="visually-hidden">Chargement des frais...</span>
                        </div>
                        <p class="mt-3 text-muted" style="font-size:14px;">Chargement des frais pour cette classe...</p>
                    </div>`;
                const affectationStatus = document.getElementById('affectation_status')?.value || 'affecté';
                fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}?affectation_status=${encodeURIComponent(affectationStatus)}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(data => {
                    if (data.success) {
                        updateFraisContainer(data.frais, data.has_unconfigured_fees, data.configure_url);
                        updateResumeFrais();
                        setTimeout(() => restoreFormData(savedData), 100);
                    } else {
                        fraisContainer.innerHTML = `<div class="alert-kl alert-kl-danger"><i class="fas fa-exclamation-triangle"></i><span>Erreur lors du chargement des frais : ${data.message}</span></div>`;
                    }
                    isLoadingFrais = false;
                })
                .catch(err => {
                    fraisContainer.innerHTML = `<div class="alert-kl alert-kl-danger"><i class="fas fa-exclamation-triangle"></i><span>Erreur lors du chargement des frais. Veuillez réessayer.</span></div>`;
                    setTimeout(() => restoreFormData(savedData), 100);
                    isLoadingFrais = false;
                });
            } else if (fraisContainer) {
                fraisContainer.innerHTML = `
                    <div class="text-center py-5">
                        <p class="text-muted" style="font-size:14px;">
                            <i class="fas fa-arrow-up me-2"></i>Sélectionnez d'abord une classe pour voir les frais applicables
                        </p>
                    </div>`;
                isLoadingFrais = false;
            }
        }
    });

    // Blocage submit pendant chargement frais
    document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
        if (isLoadingFrais) {
            e.preventDefault();
            alert('Veuillez attendre la fin du chargement des frais avant de soumettre le formulaire.');
            return false;
        }
        debugLog && debugLog('SUBMIT EVENT TRIGGERED!');
        const form = e.target;
        const formData = new FormData(form);
        debugLog && debugLog(`Nom: ${formData.get('nom') || 'VIDE'}`);
        debugLog && debugLog(`Classe: ${formData.get('classe_id') || 'VIDE'}`);
        debugLog && debugLog(`Matricule: ${formData.get('matricule') || 'VIDE'}`);
    });

    // =============================================
    // RENDU FRAIS
    // =============================================
    function updateFraisContainer(fraisData, hasUnconfiguredFees, configureUrl) {
        const fraisContainer = document.getElementById('fraisContainer');
        if (!fraisContainer) return;

        let html = '';

        if (hasUnconfiguredFees) {
            html += `
                <div class="alert-kl alert-kl-warning mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Configuration incomplète</strong><br>
                        Certaines catégories de frais pour cette classe n'ont pas de variantes configurées. Les montants par défaut seront utilisés.
                        <br><a href="${configureUrl}" target="_blank" class="btn btn-outline-warning btn-sm mt-2">
                            <i class="fas fa-cog me-1"></i>Configuration rapide
                        </a>
                    </div>
                </div>`;
        }

        const fraisObligatoires = fraisData.filter(f => f.is_mandatory);
        const fraisOptionnels   = fraisData.filter(f => !f.is_mandatory);

        if (fraisObligatoires.length > 0) {
            html += `<p class="fw-bold text-primary mb-3" style="font-size:13px;"><i class="fas fa-star me-2"></i>Frais obligatoires</p>`;
            fraisObligatoires.forEach(frais => { html += generateFraisHTML(frais); });
        }
        if (fraisOptionnels.length > 0) {
            html += `<p class="fw-bold mt-4 mb-3" style="font-size:13px;color:var(--kl-info);"><i class="fas fa-plus-circle me-2"></i>Frais optionnels</p>`;
            fraisOptionnels.forEach(frais => { html += generateFraisHTML(frais); });
        }
        if (fraisData.length === 0) {
            html += `
                <div class="alert-kl alert-kl-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Aucun frais configuré</strong><br>
                        Aucune catégorie de frais n'est configurée pour cette classe.
                        <a href="${configureUrl}" target="_blank" class="btn btn-outline-info btn-sm mt-2">
                            <i class="fas fa-cog me-1"></i>Configurer les frais
                        </a>
                    </div>
                </div>`;
        }

        fraisContainer.innerHTML = html;
    }

    function generateFraisHTML(frais) {
        const category          = frais.category;
        const options           = frais.options || frais.variants || [];
        const defaultAmount     = frais.default_amount;
        const isMandatory       = frais.is_mandatory;
        const isConfigured      = frais.is_configured;
        const configurationType = frais.configuration_type;
        const categoryType      = frais.category_type || 'academic';

        const typeIcons = { 'academic': 'graduation-cap', 'service': 'cogs', 'administrative': 'file-alt' };
        const icon = typeIcons[categoryType] || (isMandatory ? 'star' : 'plus-circle');

        let configBadge = '';
        if (configurationType === 'variant' || configurationType === 'configuration') {
            configBadge = `<div class="alert-kl alert-kl-success mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-check-circle"></i><span>Tarif configuré pour cette classe</span></div>`;
        } else if (configurationType === 'rule') {
            configBadge = `<div class="alert-kl alert-kl-info mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-cog"></i><span>Tarif configuré par règle de classe</span></div>`;
        } else if (configurationType === 'global_options') {
            configBadge = `<div class="alert-kl alert-kl-info mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-globe"></i><span>Options globales disponibles</span></div>`;
        } else {
            configBadge = `<div class="alert-kl alert-kl-warning mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-info-circle"></i><span>Montant par défaut utilisé (non configuré pour cette classe)</span></div>`;
        }

        let optionsHTML = '';

        if (isMandatory || configurationType === 'rule' || configurationType === 'variant' || configurationType === 'configuration') {
            optionsHTML += `
                <div class="form-check mb-2">
                    <input class="form-check-input frais-option" type="radio"
                           name="frais[${category.id}][variant_id]"
                           value="default"
                           id="frais_${category.id}_default"
                           ${isMandatory ? 'checked' : ''}>
                    <label class="form-check-label" for="frais_${category.id}_default">
                        ${configurationType === 'variant' ? 'Tarif configuré pour cette classe' :
                          configurationType === 'rule' ? 'Tarif configuré' :
                          configurationType === 'configuration' ? 'Tarif configuré pour cette classe' :
                          'Montant par défaut'} — <strong>${(parseFloat(defaultAmount) || 0).toLocaleString()} FCFA</strong>
                    </label>
                </div>`;
        }

        if (isConfigured && options.length > 0) {
            options.forEach(option => {
                const baseAmount       = parseFloat(defaultAmount) || 0;
                const additionalAmount = parseFloat(option.additional_amount) || parseFloat(option.amount) || 0;
                let totalAmount = configurationType === 'global_options'
                    ? baseAmount + additionalAmount
                    : (additionalAmount || baseAmount);
                if (isNaN(totalAmount) || totalAmount < 0) totalAmount = 0;

                optionsHTML += `
                    <div class="form-check mb-2">
                        <input class="form-check-input frais-option" type="radio"
                               name="frais[${category.id}][variant_id]"
                               value="${option.id}"
                               id="frais_${category.id}_${option.id}">
                        <label class="form-check-label" for="frais_${category.id}_${option.id}">
                            ${option.name} — <strong>${totalAmount.toLocaleString()} FCFA</strong>
                            ${option.description ? `<small class="text-muted d-block">${option.description}</small>` : ''}
                        </label>
                    </div>`;
            });
        }

        if (!isMandatory) {
            optionsHTML += `
                <div class="form-check mb-2">
                    <input class="form-check-input frais-option" type="radio"
                           name="frais[${category.id}][variant_id]"
                           value=""
                           id="frais_${category.id}_none"
                           checked>
                    <label class="form-check-label" for="frais_${category.id}_none">
                        <em>Ne pas souscrire à ce service</em>
                    </label>
                </div>`;
        }

        return `
            <div class="frais-card ${!isConfigured ? 'border-warning' : ''}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0" style="font-size:14px;">
                        <i class="fas fa-${icon} me-2" style="color:var(--kl-primary-light);"></i>
                        ${category.name}
                        ${!isConfigured ? '<i class="fas fa-exclamation-triangle text-warning ms-1" title="Pas d\'options configurées"></i>' : ''}
                    </h6>
                    <div class="d-flex gap-1">
                        ${isMandatory
                            ? '<span class="badge bg-danger" style="font-size:10px;">Obligatoire</span>'
                            : '<span class="badge bg-info" style="font-size:10px;">Optionnel</span>'}
                        <span class="badge bg-secondary" style="font-size:10px;">${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}</span>
                    </div>
                </div>
                ${category.description ? `<p class="text-muted mb-2" style="font-size:12px;">${category.description}</p>` : ''}
                ${configBadge}
                <div class="frais-options">${optionsHTML}</div>
            </div>`;
    }

    function updateResumeFrais() {
        const resumeContainer = document.getElementById('resumeFrais');
        if (!resumeContainer) return;

        const selectedOptions = document.querySelectorAll('.frais-option:checked');
        let totalAmount = 0;
        let resumeHTML  = '';

        selectedOptions.forEach(option => {
            if (!option.value || option.value === '') return;
            const fraisCard = option.closest('.frais-card');
            let categoryName = 'Frais';
            if (fraisCard) {
                const h6 = fraisCard.querySelector('h6');
                if (h6) {
                    let text = h6.textContent.trim().replace(/[\uF000-\uF8FF]/g, '').trim();
                    categoryName = text.split('\n')[0].trim() || categoryName;
                }
            }
            const label = option.closest('.form-check')?.querySelector('label')?.textContent || '';
            const match = label.match(/(\d+(?:[.,\s]\d{3})*)/);
            if (match) {
                const amount = parseInt(match[1].replace(/[.,\s]/g, '')) || 0;
                totalAmount += amount;
                resumeHTML += `<div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                    <span>${categoryName}</span>
                    <span class="fw-bold">${amount.toLocaleString()} FCFA</span>
                </div>`;
            }
        });

        if (resumeHTML) {
            resumeHTML += `<hr><div class="d-flex justify-content-between fw-bold" style="font-size:14px;">
                <span>Total</span>
                <span style="color:var(--kl-primary);">${(totalAmount || 0).toLocaleString()} FCFA</span>
            </div>`;
            resumeContainer.innerHTML = resumeHTML;
        } else {
            resumeContainer.innerHTML = '<div class="text-center text-muted py-2" style="font-size:13px;">Aucun frais sélectionné</div>';
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('frais-option')) updateResumeFrais();
    });

    // =============================================
    // STATUT AFFECTATION
    // =============================================
    window.updateAffectationInfo = function() {
        const select  = document.getElementById('affectation_status');
        const infoDiv = document.getElementById('affectation-info');
        const value   = select.value;
        let content   = '';

        switch (value) {
            case 'affecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-check-circle mt-1" style="color:var(--kl-success);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-success);font-size:13px;">Étudiant Affecté par l'État</div>
                            <div class="text-muted" style="font-size:12px;">Plateforme : bac.mesrs-ci.net</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Affectation officielle par le MESRS après le BAC</li>
                                <li>Éligible à une subvention étatique</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-success);font-weight:600;">
                                <i class="fas fa-coins me-1"></i> Frais réduits applicables
                            </div>
                        </div>
                    </div>`;
                break;
            case 'réaffecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-sync-alt mt-1" style="color:var(--kl-warning);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-warning);font-size:13px;">Étudiant Réaffecté par la DOB</div>
                            <div class="text-muted" style="font-size:12px;">Organisme : Direction de l'Orientation et des Bourses</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Initialement affecté dans un autre établissement</li>
                                <li>Réaffectation officielle après demande</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-warning);font-weight:600;">
                                <i class="fas fa-coins me-1"></i> Subvention étatique maintenue
                            </div>
                        </div>
                    </div>`;
                break;
            case 'non_affecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-times-circle mt-1" style="color:var(--kl-danger);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-danger);font-size:13px;">Étudiant Non Affecté</div>
                            <div class="text-muted" style="font-size:12px;">Statut : Inscription directe</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Non retenu dans le système public d'affectation</li>
                                <li>Inscription directe dans l'établissement</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-danger);font-weight:600;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Tarif complet sans subvention
                            </div>
                        </div>
                    </div>`;
                break;
            default:
                content = `<span class="text-muted" style="font-size:13px;"><i class="fas fa-arrow-left me-2"></i>Sélectionnez un statut pour voir les détails</span>`;
        }

        infoDiv.innerHTML = content;

        if (value && document.getElementById('classe_id')?.value) {
            loadFraisForClasse();
        }
    };

    window.loadFraisForClasse = function() {
        const classeSelect = document.getElementById('classe_id');
        if (!classeSelect || !classeSelect.value) return;
        const changeEvent = new Event('change', { bubbles: true });
        classeSelect.dispatchEvent(changeEvent);
    };

    function initClasseAffectationState() {
        const affectationSelect = document.getElementById('affectation_status');
        if (affectationSelect && affectationSelect.value) updateAffectationInfo();
        const classeSelect = document.getElementById('classe_id');
        if (classeSelect && classeSelect.value) loadFraisForClasse();
    }

    initClasseAffectationState();
    updateParentToggleSub();

    // =============================================
    // STEPPER VISUEL (scroll-based)
    // =============================================
    const stepSections = [
        { step: 1, el: document.getElementById('section-identite') },
        { step: 2, el: document.getElementById('section-academique') },
        { step: 3, el: document.getElementById('section-affectation') },
        { step: 4, el: document.getElementById('section-parents') },
        { step: 5, el: document.getElementById('section-frais') },
    ];

    function updateStepper() {
        const scrollMid = window.scrollY + window.innerHeight / 2;
        let active = 1;
        stepSections.forEach(({ step, el }) => {
            if (el && el.getBoundingClientRect().top + window.scrollY <= scrollMid) active = step;
        });
        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.dataset.step);
            item.classList.remove('active', 'done');
            if (s < active) item.classList.add('done');
            else if (s === active) item.classList.add('active');
        });
    }

    window.addEventListener('scroll', updateStepper, { passive: true });
    updateStepper();

    // =============================================
    // GESTION MATRICULES (inchangée)
    // =============================================
    const matriculeInput    = document.getElementById('matriculeInput');
    const matriculeContainer = document.getElementById('matriculeContainer');
    const generateBtn       = document.getElementById('generateMatriculeBtn');
    const checkBtn          = document.getElementById('checkMatriculeBtn');
    const matriculeStatus   = document.getElementById('matriculeStatus');
    const matriculeMode     = document.getElementById('matriculeMode');
    const matriculeHelp     = document.getElementById('matriculeHelp');
    const genreSelect       = document.querySelector('select[name="sexe"]');
    const classeSelect      = document.getElementById('classe_id');

    let currentMatriculeMode = 'automatique';
    let niveauConfig = null;

    fetch('/esbtp/matricule-config/mode-info', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => { currentMatriculeMode = data.mode || 'automatique'; updateMatriculeUI(); })
    .catch(() => updateMatriculeUI());

    function updateMatriculeUI() {
        if (currentMatriculeMode === 'automatique') {
            if (matriculeContainer) matriculeContainer.style.display = 'none';
            if (matriculeInput) matriculeInput.value = '';
        } else {
            if (matriculeContainer) matriculeContainer.style.display = 'block';
            if (matriculeMode)  { matriculeMode.textContent = 'MANUEL'; matriculeMode.className = 'badge bg-warning ms-1'; }
            if (matriculeHelp)  matriculeHelp.textContent = 'Saisissez manuellement le matricule (vérification anti-doublon)';
            if (generateBtn) generateBtn.style.display = 'none';
            if (checkBtn) checkBtn.style.display = 'block';
            if (matriculeInput) { matriculeInput.readOnly = false; matriculeInput.placeholder = 'Ex: MESBTP25-0001'; }
        }
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const genre = genreSelect ? genreSelect.value : null;
            if (!genre) { showMatriculeStatus('Veuillez d\'abord sélectionner le genre/sexe', 'warning'); return; }
            if (!niveauConfig) { showMatriculeStatus('Niveau d\'études non configuré. Contactez l\'équipe technique.', 'danger'); return; }
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
            fetch('/esbtp/matricule-config/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ niveau_etude_code: niveauConfig.code, genre, annee: new Date().getFullYear() })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { matriculeInput.value = data.matricule; showMatriculeStatus('Matricule généré avec succès', 'success'); }
                else showMatriculeStatus(data.message || 'Erreur lors de la génération', 'danger');
            })
            .catch(() => showMatriculeStatus('Erreur de connexion', 'danger'))
            .finally(() => { generateBtn.disabled = false; generateBtn.innerHTML = '<i class="fas fa-magic"></i> Générer'; });
        });
    }

    if (checkBtn) checkBtn.addEventListener('click', checkMatriculeManuel);

    if (currentMatriculeMode === 'manuel' && matriculeInput) {
        let checkTimeout;
        matriculeInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            if (this.value.length >= 3) checkTimeout = setTimeout(checkMatriculeManuel, 500);
        });
    }

    function checkMatriculeManuel() {
        const matricule = matriculeInput ? matriculeInput.value.trim() : '';
        if (!matricule) { showMatriculeStatus('', ''); return; }
        if (checkBtn) { checkBtn.disabled = true; checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        fetch('/esbtp/matricule-config/check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
            body: JSON.stringify({ matricule })
        })
        .then(r => r.json())
        .then(data => {
            if (data.exists) showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
            else showMatriculeStatus('✅ Matricule disponible', 'success');
        })
        .catch(() => showMatriculeStatus('Erreur de vérification', 'warning'))
        .finally(() => { if (checkBtn) { checkBtn.disabled = false; checkBtn.innerHTML = '<i class="fas fa-search"></i> Vérifier'; } });
    }

    function showMatriculeStatus(message, type) {
        if (!matriculeStatus) return;
        if (!message) { matriculeStatus.innerHTML = ''; return; }
        const cls = { success: 'alert-success', danger: 'alert-danger', warning: 'alert-warning', info: 'alert-info' }[type] || 'alert-info';
        matriculeStatus.innerHTML = `<small class="alert ${cls} p-1 m-0 d-inline-block">${message}</small>`;
    }

    async function generateMatriculeAuto() {
        const genre = genreSelect ? genreSelect.value : null;
        if (!genre || !niveauConfig) return null;
        try {
            const r = await fetch('/esbtp/matricule-config/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ niveau_etude_code: niveauConfig.code, genre, annee: new Date().getFullYear() })
            });
            const data = await r.json();
            return (data.success && data.matricule) ? data.matricule : null;
        } catch { return null; }
    }

    async function checkMatriculeDisponible() {
        const matricule = matriculeInput ? matriculeInput.value.trim() : '';
        if (!matricule) return false;
        try {
            const r = await fetch('/esbtp/matricule-config/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ matricule })
            });
            const data = await r.json();
            return !data.exists;
        } catch { return false; }
    }

    if (classeSelect) {
        classeSelect.addEventListener('change', function() {
            const classeId = this.value;
            if (classeId && currentMatriculeMode === 'automatique') {
                fetch(`/esbtp/api/classes/${classeId}/niveau-config`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    niveauConfig = data.niveau_config;
                    if (!niveauConfig && generateBtn) {
                        showMatriculeStatus('⚠️ Niveau non configuré pour génération automatique', 'warning');
                        generateBtn.disabled = true;
                    } else {
                        showMatriculeStatus('', '');
                        if (generateBtn) generateBtn.disabled = false;
                    }
                })
                .catch(() => { niveauConfig = null; });
            }
        });
    }

    const inscriptionForm2 = document.getElementById('inscriptionForm');
    if (inscriptionForm2) {
        inscriptionForm2.addEventListener('submit', async function(e) {
            if (currentMatriculeMode === 'automatique') {
                if (!genreSelect || !genreSelect.value) {
                    e.preventDefault();
                    alert('⚠️ Veuillez sélectionner le genre/sexe avant de soumettre le formulaire.');
                    if (genreSelect) genreSelect.focus();
                    return;
                }
                if (matriculeInput) matriculeInput.value = '';
            } else if (currentMatriculeMode === 'manuel') {
                e.preventDefault();
                const matricule = matriculeInput ? matriculeInput.value.trim() : '';
                if (!matricule) {
                    alert('⚠️ Le matricule est obligatoire.\n\nVeuillez saisir un matricule avant de soumettre le formulaire.');
                    if (matriculeInput) matriculeInput.focus();
                    return;
                }
                const isAvailable = await checkMatriculeDisponible();
                if (isAvailable) {
                    inscriptionForm2.submit();
                } else {
                    alert('❌ Ce matricule existe déjà dans la base de données.\n\nVeuillez en saisir un autre.\n\nMatricule saisi: ' + matricule);
                    if (matriculeInput) { matriculeInput.focus(); matriculeInput.select(); }
                    showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
                }
            }
        });
    }
});
</script>
@endpush
