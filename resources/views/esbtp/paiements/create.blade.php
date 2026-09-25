@extends('layouts.app')

@section('title', 'Nouveau Paiement - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .pc-page {
        --pc-primary: #0453cb;
        --pc-primary-d: #033a8e;
        --pc-secondary: #5e91de;
        --pc-dark: #0f172a;
        --pc-text: #1e293b;
        --pc-muted: #64748b;
        --pc-border: #dbe4f0;
        --pc-surface: #f8fafc;
    }

    .pc-page .dashboard-header {
        border: 1px solid var(--pc-border);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.06);
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.05));
    }

    .pc-page .header-left h1 {
        color: var(--pc-primary);
    }

    .pc-header-shell {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .pc-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--pc-primary), var(--pc-secondary));
        color: #fff;
        font-size: 1rem;
        box-shadow: 0 10px 24px rgba(4, 83, 203, 0.2);
        flex-shrink: 0;
    }

    .pc-header-meta {
        margin-top: 0.4rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .pc-header-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #1e3a8a;
        border: 1px solid rgba(4, 83, 203, 0.22);
        background: rgba(255, 255, 255, 0.72);
    }

    .student-progress-card {
        background: #fff;
        color: var(--pc-text);
        border-radius: 12px;
        padding: 1.1rem 1.15rem 0.85rem;
        margin-bottom: 1rem;
        border: 1px solid var(--pc-border);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .pc-ledger-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        padding-bottom: 0.85rem;
        margin-bottom: 0.35rem;
        border-bottom: 1px solid #eef2f7;
    }

    .pc-ledger-label {
        margin: 0;
        color: var(--pc-muted);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .pc-ledger-amount {
        margin: 0.15rem 0 0;
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--pc-dark);
        font-variant-numeric: tabular-nums;
        line-height: 1.15;
    }

    .pc-ledger-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.2rem;
        color: var(--pc-muted);
        font-size: 0.8rem;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    .category-progress {
        padding: 0.7rem 0;
        border-bottom: 1px solid #eef2f7;
    }

    .category-progress:last-child {
        border-bottom: 0;
    }

    .pc-fee-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        margin-bottom: 0.4rem;
    }

    .pc-fee-name {
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--pc-dark);
    }

    .pc-fee-rest {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        color: var(--pc-dark);
        font-size: 0.88rem;
    }

    .pc-fee-sub {
        display: flex;
        justify-content: space-between;
        margin-top: 0.35rem;
        color: var(--pc-muted);
        font-size: 0.75rem;
        font-variant-numeric: tabular-nums;
    }

    .progress-bar-modern {
        height: 6px;
        border-radius: 999px;
        background: #e8eef6;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: inherit;
        background: #0453cb;
        transition: width 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @media (prefers-reduced-motion: reduce) {
        .progress-fill { transition: none; }
    }

    .payment-form-card {
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.06);
        border: 1px solid var(--pc-border);
        overflow: visible;
        position: relative;
        z-index: 1;
    }

    .payment-form-card:focus-within {
        z-index: 25;
    }

    .pc-page .section-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: var(--pc-dark);
        font-size: 0.92rem;
        margin-bottom: 1rem;
    }

    .category-selection {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 0.8rem;
        margin-bottom: 0;
    }

    .category-option {
        border: 1px solid var(--pc-border);
        border-radius: 12px;
        padding: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }

    .category-option:hover {
        border-color: var(--pc-primary);
        box-shadow: 0 8px 20px rgba(4, 83, 203, 0.08);
        transform: translateY(-1px);
    }

    .category-option.selected {
        border-color: var(--pc-primary);
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.1));
        color: var(--pc-dark);
    }

    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.55rem;
        font-size: 16px;
        background: linear-gradient(135deg, var(--pc-primary), var(--pc-secondary));
        color: #fff;
    }

    .form-floating-modern {
        position: relative;
        margin-bottom: 1rem;
    }

    .form-floating-modern:focus-within {
        z-index: 30;
    }

    .pc-field-label {
        display: block;
        color: var(--pc-muted);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 0.45rem;
    }

    .pc-field .au-select {
        width: 100%;
        max-width: 100%;
    }

    .pc-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(11rem, 1fr) minmax(11rem, 1fr);
        gap: 0.7rem;
        margin-bottom: 0.9rem;
    }
    .pc-search,
    .pc-filter {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 48px;
        background: #fff;
        border: 1px solid var(--pc-border);
        border-radius: 12px;
        padding: 0 0.85rem 0 2.6rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pc-search:focus-within,
    .pc-filter:focus-within {
        border-color: var(--pc-primary);
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .12);
    }
    .pc-search i,
    .pc-filter i {
        position: absolute;
        left: 0.9rem;
        color: var(--pc-muted);
        font-size: 0.82rem;
        pointer-events: none;
    }
    .pc-search input,
    .pc-filter select {
        width: 100%;
        border: 0;
        background: transparent;
        outline: none;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--pc-dark);
        min-height: 46px;
    }
    .pc-search input::placeholder { color: #94a3b8; font-weight: 500; }
    .pc-insc-list {
        border: 1px solid var(--pc-border);
        border-radius: 14px;
        overflow: auto;
        max-height: 320px;
        background: #fff;
    }
    .pc-insc-row {
        width: 100%;
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: center;
        text-align: left;
        padding: 0.75rem 0.95rem;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        cursor: pointer;
        color: var(--pc-text);
        transition: background .15s ease;
    }
    .pc-insc-row:last-child { border-bottom: 0; }
    .pc-insc-row:hover { background: #f8fafc; }
    .pc-insc-row.is-on {
        background: #eff6ff;
        border-bottom-color: #dbeafe;
    }
    .pc-avatar {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--pc-primary), var(--pc-secondary));
        color: #fff;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        flex-shrink: 0;
    }
    .pc-insc-nom { font-weight: 700; font-size: 0.9rem; color: var(--pc-dark); }
    .pc-insc-sub { font-size: 0.75rem; color: var(--pc-muted); margin-top: 0.12rem; }
    .pc-insc-track {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--pc-primary);
        background: rgba(4, 83, 203, 0.08);
        border-radius: 999px;
        padding: 0.28rem 0.65rem;
        white-space: nowrap;
    }
    .pc-insc-empty { padding: 1.4rem 1rem; text-align: center; color: var(--pc-muted); font-size: 0.86rem; }
    @media (max-width: 768px) {
        .pc-toolbar { grid-template-columns: 1fr; }
        .pc-insc-row { grid-template-columns: 40px minmax(0, 1fr); }
        .pc-insc-track { grid-column: 2; justify-self: start; }
    }

    .form-floating-modern input,
    .form-floating-modern select,
    .form-floating-modern textarea {
        width: 100%;
        padding: 0.75rem 0.95rem;
        border: 1px solid var(--pc-border);
        border-radius: 10px;
        font-size: 0.95rem;
        background: #fff;
        color: var(--pc-text);
        transition: all 0.2s ease;
    }

    .form-floating-modern input:focus,
    .form-floating-modern select:focus,
    .form-floating-modern textarea:focus {
        outline: none;
        border-color: var(--pc-primary);
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.12);
    }

    .form-floating-modern label {
        color: var(--pc-muted);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .amount-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .amount-input-group .fcfa-suffix {
        pointer-events: none;
        font-weight: 600;
    }

    .amount-input-group input[type="number"] {
        padding-right: 4.6rem;
        -moz-appearance: textfield;
    }

    .amount-input-group input[type="number"]::-webkit-outer-spin-button,
    .amount-input-group input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .amount-suggestions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.6rem;
        flex-wrap: wrap;
    }

    /* Repartition du versement — pc-rep-* */
    .pc-rep {
        margin-top: 1.25rem;
        padding: 1.1rem 1.2rem 1.15rem;
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        background: #fff;
    }

    .pc-rep-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }

    .pc-rep-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1e293b;
    }

    .pc-rep-sub {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 0.15rem;
    }

    .pc-rep-toggle {
        border: 1px solid #c7d4e5;
        background: #fff;
        color: #0453cb;
        border-radius: 10px;
        padding: 0.42rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .pc-rep-toggle:hover { background: rgba(4, 83, 203, 0.06); border-color: #0453cb; }

    .pc-rep-ligne {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.7rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .pc-rep-ligne + .pc-rep-ligne { margin-top: 0.4rem; }

    .pc-rep-nom { font-size: 0.85rem; font-weight: 600; color: #1e293b; }

    .pc-rep-reste { font-size: 0.72rem; color: #64748b; margin-top: 0.1rem; }

    .pc-rep-montant { font-size: 0.88rem; font-weight: 700; color: #0453cb; white-space: nowrap; }

    .pc-rep-input {
        width: 150px;
        text-align: right;
        border: 1px solid #d9e2ef;
        border-radius: 8px;
        padding: 0.35rem 0.55rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
    }

    .pc-rep-total {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        margin-top: 0.6rem;
        padding: 0 0.7rem;
    }

    .pc-rep-alert {
        margin-top: 0.7rem;
        padding: 0.65rem 0.8rem;
        border-radius: 10px;
        background: #fffbeb;
        border: 1px solid #f59e0b;
        color: #92400e;
        font-size: 0.8rem;
        line-height: 1.5;
    }

    .amount-suggestion {
        padding: 0.38rem 0.68rem;
        background: #f1f5f9;
        border: 1px solid #d9e2ef;
        border-radius: 999px;
        cursor: pointer;
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
        transition: all 0.2s ease;
    }

    .amount-suggestion:hover {
        background: rgba(4, 83, 203, 0.12);
        color: var(--pc-primary);
        border-color: rgba(4, 83, 203, 0.25);
    }

    .pc-page .btn-acasi.primary.large,
    .pc-page .btn-acasi.secondary.large {
        border-radius: 10px;
    }

    @media (max-width: 992px) {
        .student-progress-card {
            padding: 1rem;
        }
    }

    @media (max-width: 768px) {
        .pc-header-shell {
            align-items: flex-start;
        }

        .pc-page .main-content {
            padding: 1rem;
        }

        .pc-page .dashboard-header {
            padding: 1rem;
        }

        .pc-page .header-actions {
            width: 100%;
        }

        .pc-page .header-actions .btn-acasi {
            width: 100%;
            justify-content: center;
        }

        .pc-page #submit-section .btn-acasi {
            width: 100%;
            margin: 0.35rem 0 !important;
        }
    }

    /* ===== Refonte septembre 2026 : deux colonnes, libellés au-dessus ===== */
    .pc-page .dashboard-header { background: #fff; }
    .pc-page .header-left h1 { color: var(--pc-dark); font-size: 1.4rem; }
    .pc-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.25rem; align-items: start; }
    .pc-main { display: grid; gap: 1rem; min-width: 0; }
    .pc-main .pc-step + .pc-step { margin-top: 1rem; }
    /* Colonne étroite : la recherche sur sa ligne, les deux filtres dessous, en entier. */
    .pc-grid .pc-toolbar { grid-template-columns: 1fr 1fr; }
    .pc-grid .pc-toolbar .pc-search { grid-column: 1 / -1; }
    .pc-grid .pc-filter select { text-overflow: ellipsis; padding-right: 1.5rem; }
    .pc-insc-row { grid-template-columns: 40px minmax(0, 1fr) auto 20px; }
    .pc-step { background: #fff; border: 1px solid var(--pc-border); border-radius: 14px; padding: 1.25rem 1.5rem 1.5rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .pc-step-head { display: flex; align-items: flex-start; gap: .85rem; margin-bottom: 1.1rem; }
    .pc-step-head h2 { margin: 0; font-size: 1.02rem; font-weight: 700; color: var(--pc-dark); }
    .pc-step-head p { margin: .15rem 0 0; font-size: .84rem; color: var(--pc-muted); }
    .pc-step-num { width: 30px; height: 30px; border-radius: 9px; background: rgba(4,83,203,.1); color: var(--pc-primary); display: grid; place-items: center; font-weight: 800; font-size: .88rem; flex-shrink: 0; }
    .pc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem; }
    .pc-row + .pc-row { margin-top: 1rem; }
    .pc-field { display: flex; flex-direction: column; gap: .4rem; min-width: 0; }
    .pc-field > .au-select { display: flex; width: 100%; }
    .pc-field > .au-select .au-select-trigger { width: 100%; }
    .pc-label { font-size: .8rem; font-weight: 700; color: #334155; margin: 0; }
    .pc-req { color: #dc2626; }
    .pc-opt { font-weight: 500; color: #94a3b8; margin-left: .25rem; }
    .pc-page .pc-field .form-control { height: 46px; border: 1.5px solid var(--pc-border); border-radius: 10px; padding: 0 .9rem; font-size: .95rem; color: var(--pc-text); background: #fff; box-shadow: none; }
    .pc-page .pc-field textarea.form-control { height: auto; min-height: 46px; padding: .65rem .9rem; resize: vertical; }
    .pc-page .pc-field .form-control:focus { border-color: var(--pc-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.12); outline: none; }
    .pc-page .amount-input-group { position: relative; }
    .pc-page .amount-input-group .pc-amount { height: 56px; font-size: 1.45rem; font-weight: 800; padding-right: 4.5rem; font-variant-numeric: tabular-nums; }
    .pc-page .amount-input-group .pc-amount.is-unusual { border-color: #f59e0b; background: #fffbeb; }
    .pc-page .amount-input-group .fcfa-suffix { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); font-size: .8rem; font-weight: 700; color: var(--pc-muted); }
    .pc-page .amount-suggestions { display: flex; flex-wrap: wrap; gap: .5rem; margin: 0; }
    .pc-page .amount-suggestion { display: inline-flex; gap: .45rem; align-items: baseline; border: 1px solid var(--pc-border); background: var(--pc-surface); border-radius: 999px; padding: .35rem .8rem; font-size: .8rem; color: var(--pc-muted); cursor: pointer; }
    .pc-page .amount-suggestion b { color: var(--pc-primary); font-weight: 700; font-variant-numeric: tabular-nums; }
    .pc-page .amount-suggestion:hover { border-color: var(--pc-primary); background: #eef4ff; }
    .pc-unusual { display: flex; gap: .7rem; padding: .8rem .95rem; background: #fffbeb; border: 1.5px solid #f59e0b; border-radius: 10px; }
    .pc-unusual > i { color: #d97706; margin-top: .15rem; }
    .pc-unusual b { color: #92400e; font-size: .86rem; }
    .pc-unusual p { margin: .25rem 0 .5rem; font-size: .8rem; color: #7c2d12; line-height: 1.5; }
    .pc-unusual-ok { display: flex; gap: .5rem; align-items: center; cursor: pointer; font-size: .83rem; font-weight: 600; color: #92400e; }
    .pc-unusual-ok .form-check-input { margin: 0; }
    .pc-page .pc-rep { margin-top: 1.25rem; }
    .pc-picked { display: flex; align-items: center; gap: .9rem; padding: .85rem 1rem; border: 1px solid rgba(4,83,203,.25); background: #f5f8ff; border-radius: 12px; }
    .pc-picked-txt { display: grid; gap: .15rem; min-width: 0; }
    .pc-picked-txt b { color: var(--pc-dark); font-size: 1rem; }
    .pc-picked-txt span { color: var(--pc-muted); font-size: .84rem; }
    .pc-avatar--lg { width: 48px; height: 48px; font-size: 1rem; }
    .pc-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .pc-facts > div { background: var(--pc-surface); border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem .85rem; display: grid; gap: .2rem; }
    .pc-facts span { font-size: .75rem; color: var(--pc-muted); font-weight: 600; }
    .pc-facts b { font-size: .9rem; color: var(--pc-dark); }
    .pc-insc-txt { display: grid; gap: .1rem; min-width: 0; text-align: left; }
    .pc-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .92em; }
    .pc-insc-check { color: var(--pc-primary); opacity: 0; font-size: 1.05rem; }
    .pc-insc-row.is-on .pc-insc-check { opacity: 1; }
    .pc-aside { position: sticky; top: 96px; display: grid; gap: 1rem; }
    .pc-aside .student-progress-card { margin: 0; border-radius: 14px; }
    .pc-aside-vide { display: grid; justify-items: center; text-align: center; gap: .35rem; padding: 2rem 1.25rem; background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; color: var(--pc-muted); font-size: .85rem; }
    .pc-aside-vide i { font-size: 1.5rem; color: #94a3b8; margin-bottom: .25rem; }
    .pc-aside-vide b { color: var(--pc-dark); font-size: .95rem; }
    /* Le bloc « reste dû » suit l'étape 2 : caché tant qu'aucune inscription n'est chargée. */
    .pc-grid:has(#student-progress-section[style*="display: none"]) .pc-ledger { display: none; }
    .pc-grid:not(:has(#student-progress-section[style*="display: none"])) .pc-aside-vide { display: none; }
    .pc-submit { display: grid; gap: .5rem; }
    .pc-submit .btn-acasi { width: 100%; justify-content: center; margin: 0 !important; }
    @media (max-width: 1199.98px) {
        .pc-grid { grid-template-columns: 1fr; }
        .pc-aside { position: static; }
    }
    @media (max-width: 767.98px) {
        .pc-row, .pc-facts { grid-template-columns: 1fr; }
        .pc-insc-row { grid-template-columns: 40px minmax(0, 1fr) 20px; }
        .pc-insc-track { grid-column: 2; grid-row: 2; justify-self: start; }
        .pc-insc-check { grid-column: 3; grid-row: 1; }
        .pc-step { padding: 1rem; }
    }
</style>
@endpush

@section('content')
@php
    $studentOptions = [];
    $selectedStudentId = old('etudiant_id', request('etudiant_id', $etudiant->id ?? ''));
    if ($selectedStudentId) {
        $selectedStudent = $etudiant ?? \App\Models\ESBTPEtudiant::with('user')->find($selectedStudentId);
        if ($selectedStudent) {
            $studentOptions[(string) $selectedStudent->id] = ($selectedStudent->matricule ?? 'N/A') . ' - ' . ($selectedStudent->user->name ?? $selectedStudent->nom_complet ?? 'N/A');
        }
    }

    // Dérivé de l'enum, et non recopié : cette liste avait divergé — ni Djamo
    // ni Celtiis Cash n'y figuraient, alors que la réconciliation les connaît.
    // Un mode absent d'ici n'est JAMAIS proposé (la boucle plus bas ne montre
    // que ce qu'elle contient, `$allowedPaymentModes` ne fait que restreindre),
    // donc l'encaissement correspondant n'avait aucune façon d'être saisi.
    $allModeOptions = \App\Enums\ModePaiement::optionsDeGuichet();
    $allowedPaymentModes = $allowedPaymentModes ?? array_values($allModeOptions);
    $modeOptions = [];
    foreach ($allModeOptions as $label => $canonical) {
        if (in_array($canonical, $allowedPaymentModes, true) || in_array(strtolower($label), $allowedPaymentModes, true)) {
            $modeOptions[$label] = $label;
        }
    }

    $trancheOptions = [
        'Première tranche' => 'Première tranche',
        'Deuxième tranche' => 'Deuxième tranche',
        'Troisième tranche' => 'Troisième tranche',
        'Paiement intégral' => 'Paiement intégral',
    ];
    // Shell mobile actif : le formulaire de bureau reste tel quel mais se cache
    // sous 768px, remplace par le pas-a-pas plein ecran (partial mobile).
    $mabShell = (bool) (($mobileShellEnabled ?? false) && ($mobileProfile ?? null));
@endphp
<div class="{{ $mabShell ? 'm-only-desktop' : '' }}">
<div class="dashboard-acasi pc-page">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <div class="pc-header-shell">
                    <div class="pc-header-icon"><i class="fas fa-money-check-dollar"></i></div>
                    <div>
                        <h1>Encaisser un paiement</h1>
                        <p class="header-subtitle">Étudiant, montant, mode : le reste dû et la répartition se calculent au fur et à mesure.</p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                @canany(['cash_session.manage', 'module.caisse.access'])
                <a href="{{ route('esbtp.caisse.ma-caisse') }}" class="btn-acasi secondary">
                    <i class="fas fa-cash-register"></i>Ma caisse
                </a>
                @endcanany
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>
        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form action="{{ route('esbtp.paiements.store') }}" method="POST" id="payment-form">
            @csrf
        <div class="pc-grid">
        <div class="pc-main">

            {{-- 1. L'étudiant --}}
            <section class="pc-step">
                <header class="pc-step-head">
                    <span class="pc-step-num">1</span>
                    <div>
                        <h2>Étudiant</h2>
                        <p>Recherchez par nom, prénom ou matricule.</p>
                    </div>
                </header>

                    @if($etudiant)
                        <div class="pc-picked">
                            <span class="pc-avatar pc-avatar--lg">{{ mb_strtoupper(mb_substr($etudiant->user->name ?? $etudiant->nom_complet ?? 'NN', 0, 2, 'UTF-8'), 'UTF-8') }}</span>
                            <div class="pc-picked-txt">
                                <b>{{ $etudiant->user->name ?? $etudiant->nom_complet ?? 'N/A' }}</b>
                                <span>{{ $etudiant->matricule }}@if($etudiant->user?->email) · {{ $etudiant->user->email }}@endif</span>
                            </div>
                            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                        </div>
                    @else
                        <div x-data="caisseInscriptionPicker()" x-init="charger()">
                            <div class="pc-toolbar">
                                <label class="pc-search">
                                    <i class="fas fa-search"></i>
                                    <input type="search" x-model="q" @input.debounce.250ms="charger()" placeholder="Nom, prénom ou matricule" autocomplete="off">
                                </label>
                                <label class="pc-filter">
                                    <i class="fas fa-sitemap"></i>
                                    <select x-model="filiereId" @change="charger()">
                                        <option value="">Toutes les filières</option>
                                        @foreach(($filieres ?? []) as $filiere)
                                            <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="pc-filter">
                                    <i class="fas fa-layer-group"></i>
                                    <select x-model="niveauId" @change="charger()">
                                        <option value="">Tous les niveaux</option>
                                        @foreach(($niveaux ?? []) as $niveau)
                                            <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="pc-insc-list">
                                <template x-for="row in rows" :key="row.id">
                                    <button type="button" class="pc-insc-row" :class="{ 'is-on': selectedId === row.id }" @click="choisir(row)">
                                        <span class="pc-avatar" x-text="initiales(row.nom)"></span>
                                        <span class="pc-insc-txt">
                                            <span class="pc-insc-nom" x-text="row.nom"></span>
                                            <span class="pc-insc-sub"><span x-text="row.classe"></span> · <span class="pc-mono" x-text="row.matricule"></span></span>
                                        </span>
                                        <span class="pc-insc-track" x-text="[row.filiere, row.niveau].filter(Boolean).join(' · ')"></span>
                                        <i class="fas fa-check-circle pc-insc-check" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <div class="pc-insc-empty" x-show="rows.length === 0" x-cloak>Aucun dossier pour ces filtres.</div>
                            </div>
                            <input type="hidden" name="etudiant_id" id="etudiant_id" x-model="etudiantId" required>
                        </div>
                    @endif
            </section>

            {{-- 2. L'inscription (le bloc « reste dû » vit dans la colonne de droite) --}}
            <div id="student-progress-section" style="display: none;">
                <section class="pc-step">
                    <header class="pc-step-head">
                        <span class="pc-step-num">2</span>
                        <div>
                            <h2>Inscription</h2>
                            <p>Celle de l'année en cours est choisie d'office.</p>
                        </div>
                    </header>

                        @if($inscription)
                            <div class="pc-facts">
                                <div><span>Filière</span><b>{{ $inscription->filiere->name ?? 'N/A' }}</b></div>
                                <div><span>Niveau d'études</span><b>{{ $inscription->niveauEtude->name ?? 'N/A' }}</b></div>
                                <div><span>Année universitaire</span><b>{{ $inscription->anneeUniversitaire->libelle ?? $inscription->anneeUniversitaire->name ?? 'N/A' }}</b></div>
                                <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                            </div>
                        @else
                            <div class="pc-field">
                                <label for="inscription_id" class="pc-label">Inscription <span class="pc-req">*</span></label>
                                <x-au-select
                                    id="inscription_id"
                                    name="inscription_id"
                                    :value="(string) old('inscription_id', request('inscription_id', ''))"
                                    :options="[]"
                                    placeholder="Sélectionner une inscription"
                                    icon="fa-file-signature"
                                    required
                                    searchable />

                                <div id="inscription-auto-notice" class="alert alert-info mt-2 mb-0 py-2 px-3" style="display: none; font-size: 0.82rem;"></div>
                            </div>
                        @endif
                </section>
            </div>

            <div id="category-selection-section" style="display: none;">
                <input type="hidden" name="frais_category_id" id="selected_category_id" value="{{ old('frais_category_id') }}">
            </div>

            {{-- 3. Le versement --}}
            <div id="payment-details-section" style="display: none;">
                <section class="pc-step">
                    <header class="pc-step-head">
                        <span class="pc-step-num">3</span>
                        <div>
                            <h2>Versement</h2>
                            <p>Le montant se répartit tout seul sur les frais encore dus.</p>
                        </div>
                    </header>

                        <div class="pc-row" x-data="{
                                montant: {{ (int) old('montant', 0) }},
                                threshold: {{ (int) ($unusualAmountThreshold ?? 500000) }},
                                confirmed: false,
                                get isUnusual() { return this.montant > this.threshold; },
                                get formattedThreshold() { return new Intl.NumberFormat('fr-FR').format(this.threshold); },
                                get formattedMontant() { return new Intl.NumberFormat('fr-FR').format(this.montant); },
                            }">
                            <div class="pc-field pc-field--amount">
                                <label for="montant" class="pc-label">Montant <span class="pc-req">*</span></label>
                                <div class="amount-input-group">
                                    <input type="number" name="montant" id="montant" class="form-control pc-amount" min="0" step="1"
                                           value="{{ old('montant') }}" required placeholder="0"
                                           x-on:input="montant = parseInt($event.target.value || 0); confirmed = false"
                                           :class="isUnusual ? 'is-unusual' : ''">
                                    <span class="fcfa-suffix">FCFA</span>
                                </div>
                                <div class="amount-suggestions" id="amount-suggestions"></div>

                                {{-- Garde-fou montant inhabituel (QW3) --}}
                                <div x-show="isUnusual" x-cloak x-transition.opacity class="qw3-unusual-alert pc-unusual">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    <div>
                                        <b>Montant inhabituel : vérifiez avant d'enregistrer</b>
                                        <p>Le montant saisi (<strong x-text="formattedMontant + ' FCFA'"></strong>) dépasse le seuil habituel de <strong x-text="formattedThreshold + ' FCFA'"></strong> configuré pour cette école. Vérifiez qu'il ne s'agit pas d'une erreur de frappe (50&nbsp;000 au lieu de 5&nbsp;000).</p>
                                        <label class="pc-unusual-ok">
                                            <input type="checkbox" name="confirmed_unusual_amount" value="1" x-model="confirmed" class="form-check-input">
                                            <span>Je confirme que ce montant est correct</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="pc-field">
                                <label for="date_paiement" class="pc-label">Date de paiement <span class="pc-req">*</span></label>
                                <input type="date" name="date_paiement" id="date_paiement" class="form-control" value="{{ old('date_paiement', date('Y-m-d')) }}" required>
                            </div>
                        </div>

                        {{-- Ou cet argent va atterrir. Le serveur repond, cet ecran
                             se contente de montrer : la regle de repartition n'existe
                             qu'en un exemplaire, et une seconde ecriture ici finirait
                             par imputer autrement que l'enregistrement lui-meme. --}}
                        <div class="pc-rep" id="repartition-section" style="display:none;">
                            <div class="pc-rep-head">
                                <div>
                                    <div class="pc-rep-title"><i class="fas fa-code-branch me-2"></i>Répartition du versement</div>
                                    <div class="pc-rep-sub" id="repartition-sub">Sur quels frais cet argent sera imputé.</div>
                                </div>
                                <button type="button" class="pc-rep-toggle" id="repartition-toggle">
                                    <i class="fas fa-sliders-h me-1"></i><span id="repartition-toggle-label">Répartir moi-même</span>
                                </button>
                            </div>

                            <div id="repartition-lignes"></div>

                            <div class="pc-rep-alert" id="repartition-erreur" style="display:none;"></div>
                        </div>
                </section>

                {{-- 4. Le mode --}}
                <section class="pc-step">
                    <header class="pc-step-head">
                        <span class="pc-step-num">4</span>
                        <div>
                            <h2>Mode de paiement</h2>
                            <p>Pour un chèque, un virement ou un mobile money, notez la référence.</p>
                        </div>
                    </header>

                        <div class="pc-row">
                            <div class="pc-field">
                                <label for="mode_paiement" class="pc-label">Mode de paiement <span class="pc-req">*</span></label>
                                <x-au-select
                                    id="mode_paiement"
                                    name="mode_paiement"
                                    :value="(string) old('mode_paiement', '')"
                                    :options="$modeOptions"
                                    placeholder="Sélectionner un mode"
                                    icon="fa-wallet"
                                    required
                                    searchable />
                            </div>
                            <div class="pc-field">
                                <label for="reference_paiement" class="pc-label">Référence <span class="pc-opt">facultatif</span></label>
                                <input type="text" name="reference_paiement" id="reference_paiement" class="form-control" value="{{ old('reference_paiement') }}" placeholder="N° de chèque, de transaction…">
                            </div>
                        </div>

                        <div class="pc-row">
                            <div class="pc-field">
                                <label for="tranche" class="pc-label">Tranche <span class="pc-opt">facultatif</span></label>
                                <x-au-select
                                    id="tranche"
                                    name="tranche"
                                    :value="(string) old('tranche', '')"
                                    :options="$trancheOptions"
                                    placeholder="Sélectionner une tranche"
                                    icon="fa-list-check" />
                            </div>
                            <div class="pc-field">
                                <label for="commentaire" class="pc-label">Commentaire <span class="pc-opt">facultatif</span></label>
                                <textarea name="commentaire" id="commentaire" class="form-control" rows="2" placeholder="Visible sur la fiche du paiement">{{ old('commentaire') }}</textarea>
                            </div>
                        </div>
                </section>
            </div>
        </div>

        {{-- Colonne de droite : ce qu'il reste dû, et l'enregistrement. --}}
        <aside class="pc-aside">
            <div class="pc-aside-vide">
                <i class="fas fa-user-graduate"></i>
                <b>Choisissez un étudiant</b>
                <span>Ce qu'il reste à payer, frais par frais, s'affichera ici.</span>
            </div>

            <div class="student-progress-card pc-ledger">
                <div class="pc-ledger-head">
                    <div>
                        <p class="pc-ledger-label">Reste à encaisser</p>
                        <p class="pc-ledger-amount" id="total-remaining">0 F</p>
                    </div>
                    <div class="pc-ledger-meta">
                        <span id="total-progress">0 % payé</span>
                        <span id="total-paid">0 F payé</span>
                    </div>
                </div>
                <div id="categories-progress"></div>
            </div>

            <div class="pc-submit" id="submit-section" style="display: none;">
                <button type="submit" class="btn-acasi primary large">
                    <i class="fas fa-check me-2"></i>Enregistrer le paiement
                </button>
                <button type="button" class="btn-acasi secondary" onclick="window.history.back()">
                    Annuler
                </button>
            </div>
        </aside>
        </div>
        </form>
    </div>
</div>
</div>
@if($mabShell)
    @include('esbtp.paiements.partials._encaisser-mobile')
@endif
@endsection

@push('scripts')
<script>
window.caisseInscriptionPicker = function () {
    const url = @json(route('esbtp.api.caisse.inscriptions'));
    return {
        q: '',
        filiereId: '',
        niveauId: '',
        rows: [],
        selectedId: null,
        etudiantId: '',
        initiales(nom) {
            return String(nom || '')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(function (mot) { return mot.charAt(0); })
                .join('')
                .toUpperCase();
        },
        async charger() {
            const params = new URLSearchParams();
            if (this.q.trim()) params.set('q', this.q.trim());
            if (this.filiereId) params.set('filiere_id', this.filiereId);
            if (this.niveauId) params.set('niveau_id', this.niveauId);
            try {
                const response = await fetch(url + '?' + params.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                const payload = await response.json();
                this.rows = payload.results || [];
            } catch (error) {
                debugWarn('Liste inscriptions indisponible', error);
            }
        },
        choisir(row) {
            this.selectedId = row.id;
            this.etudiantId = String(row.etudiant_id);
            const input = document.getElementById('etudiant_id');
            if (input) {
                input.value = this.etudiantId;
                $(input).trigger('change');
            }
        }
    };
};

$(function() {
    debugLog('=== SCRIPT PRINCIPAL CHARGÉ ===');
    
    let currentStudent = null;
    let currentInscription = null;
    let studentBalance = null;
    let categories = [];
    let selectedCategory = null;

    // Repartition du versement : declare ici, avec les autres etats de l'ecran,
    // pour qu'aucune fonction appelee tot ne tombe sur une variable pas encore
    // initialisee.
    const apercuUrl = @json(route('esbtp.paiements.repartition.apercu'));
    let repartitionManuelle = false;
    let repartitionTimer = null;
    let repartitionRequete = null;
    
    // Le choix de l'étudiant passe par le sélecteur d'inscription de la caisse
    // (caisseInscriptionPicker), qui porte sa propre recherche. L'ancien
    // branchement sur un sélecteur générique était mort et levait « Illegal
    // invocation » à chaque ouverture de l'écran : retiré.

    // Gestion de la sélection d'étudiant
    $('#etudiant_id').on('change', function() {
        var etudiantId = $(this).val();
        currentStudent = etudiantId;
        
        debugLog('Étudiant sélectionné:', etudiantId);
        
        if (etudiantId) {
            debugLog('Chargement des données pour l\'étudiant:', etudiantId);
            loadStudentData(etudiantId);
        } else {
            debugLog('Réinitialisation du formulaire');
            resetForm();
        }
    });
    
    // Si un étudiant est déjà sélectionné (pré-rempli), charger ses données
    @if($etudiant)
        currentStudent = '{{ $etudiant->id }}';
        debugLog('Étudiant pré-sélectionné:', currentStudent);
        
        @if($inscription)
            // Inscription déjà sélectionnée, charger directement les catégories
            debugLog('Inscription pré-sélectionnée: {{ $inscription->id }}');
            currentInscription = '{{ $inscription->id }}';
            showInscriptionNotice('success', 'Inscription chargée automatiquement depuis le contexte courant.');
            $('#student-progress-section').show();
            loadStudentBalance(currentStudent, currentInscription);
            loadCategories(currentInscription);
        @else
            // Étudiant sélectionné mais pas d'inscription, charger normalement
            loadStudentData(currentStudent);
        @endif
    @endif
    
    // Fonction pour charger les données de l'étudiant
    function loadStudentData(etudiantId) {
        debugLog('=== loadStudentData appelée avec ID:', etudiantId);

        currentInscription = null;
        studentBalance = null;
        hideInscriptionNotice();
        resetCategorySelection();
        $('#student-progress-section').hide();
        $('#category-selection-section').hide();
        $('#payment-details-section').hide();
        $('#submit-section').hide();
        
        // Charger les inscriptions
        loadInscriptions(etudiantId);
    }
    
    // Charger les inscriptions de l'étudiant
    function loadInscriptions(etudiantId) {
        debugLog('=== Chargement des inscriptions pour étudiant:', etudiantId);
        debugLog('URL complète:', "{{ route('esbtp.api.etudiants.inscriptions') }}" + "?etudiant_id=" + etudiantId);
        
        $.ajax({
            url: "{{ route('esbtp.api.etudiants.inscriptions') }}",
            data: { etudiant_id: etudiantId },
            dataType: 'json',
            beforeSend: function(xhr, settings) {
                debugLog('Envoi de la requête AJAX...');
                debugLog('URL:', settings.url);
                debugLog('Data:', settings.data);
            },
            success: function(data) {
                debugLog('✅ Inscriptions reçues avec succès:', data);
                var inscriptions = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
                debugLog('Nombre d\'inscriptions:', inscriptions.length);

                var selectedInscriptionId = '';
                var noticeType = null;
                var noticeMessage = null;
                var inscriptionOptions = [];

                if (inscriptions.length === 0) {
                    noticeType = 'warning';
                    noticeMessage = 'Aucune inscription disponible pour cet étudiant.';
                } else {
                    inscriptions.forEach(function(inscription) {
                        if (!inscription || !inscription.id) {
                            return;
                        }

                        inscriptionOptions.push({
                            value: String(inscription.id),
                            label: (inscription.filiere || 'Filiere non definie') + ' - ' +
                                (inscription.niveau || 'Niveau non defini') +
                                ' (' + (inscription.annee || 'Annee non definie') + ')'
                        });
                    });

                    var preferredInscriptionId = @json($inscription->id ?? null);
                    var preferredExists = preferredInscriptionId && inscriptions.some(function(inscription) {
                        return String(inscription.id) === String(preferredInscriptionId);
                    });

                    if (preferredExists) {
                        selectedInscriptionId = String(preferredInscriptionId);
                        noticeType = 'success';
                        noticeMessage = 'Inscription chargee automatiquement depuis le contexte courant.';
                    } else {
                        var currentYearInscription = inscriptions.find(function(inscription) {
                            return Boolean(inscription && inscription.is_current_year);
                        });

                        if (currentYearInscription && currentYearInscription.id) {
                            selectedInscriptionId = String(currentYearInscription.id);
                            noticeType = 'info';
                            noticeMessage = "Inscription de l'année en cours sélectionnée automatiquement.";
                        } else if (inscriptions[0] && inscriptions[0].id) {
                            selectedInscriptionId = String(inscriptions[0].id);
                            noticeType = 'warning';
                            noticeMessage = "Aucune inscription pour l'année en cours : la plus récente a été sélectionnée.";
                        }
                    }
                }

                var inscriptionRoot = document.getElementById('inscription_id')?.closest('[x-data]');
                var inscriptionSelect = inscriptionRoot ? Alpine.$data(inscriptionRoot) : null;
                if (inscriptionSelect && typeof inscriptionSelect.setOptions === 'function') {
                    inscriptionSelect.setOptions(inscriptionOptions, selectedInscriptionId);
                }

                currentInscription = selectedInscriptionId || null;

                $('#student-progress-section').show();

                if (noticeMessage) {
                    showInscriptionNotice(noticeType, noticeMessage);
                } else {
                    hideInscriptionNotice();
                }

                if (selectedInscriptionId) {
                    $('#inscription_id').trigger('change');
                } else {
                    resetProgressDisplay();
                    $('#category-selection-section').hide();
                    $('#payment-details-section').hide();
                    $('#submit-section').hide();
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur chargement inscriptions:', {status, error, response: xhr.responseText});
                $('#student-progress-section').show();
                showInscriptionNotice('warning', 'Impossible de charger les inscriptions. Reessayez.');
            }
        });
    }
    
    // Charger les soldes de l'étudiant
    function loadStudentBalance(etudiantId, inscriptionId = null) {
        debugLog('=== Chargement des soldes pour étudiant:', etudiantId, 'inscription:', inscriptionId);
        
        $.ajax({
            url: "{{ route('esbtp.api.etudiants.soldes') }}",
            data: {
                etudiant_id: etudiantId,
                inscription_id: inscriptionId || undefined,
            },
            dataType: 'json',
            success: function(data) {
                debugLog('Soldes reçus:', data);
                studentBalance = data;
                updateProgressDisplay(data);
            },
            error: function(xhr, status, error) {
                studentBalance = null;
                resetProgressDisplay();
                debugWarn('Impossible de charger les soldes:', {status, error});
            }
        });
    }
    
    // Gestion du changement d'inscription
    $('#inscription_id').on('change', function() {
        var inscriptionId = $(this).val();
        debugLog('Inscription sélectionnée:', inscriptionId);
        currentInscription = inscriptionId || null;
        
        if (inscriptionId) {
            resetCategorySelection();
            $('#category-selection-section').hide();
            $('#student-progress-section').fadeIn();
            loadStudentBalance(currentStudent, inscriptionId);
            loadCategories(inscriptionId);
        } else {
            resetProgressDisplay();
            $('#student-progress-section').hide();
            resetCategorySelection();
        }
    });
    
    // Charger les catégories de frais disponibles
    function loadCategories(inscriptionId) {
        debugLog('=== Chargement des catégories pour inscription:', inscriptionId);
        
        $.ajax({
            url: "{{ route('esbtp.api.frais.categories') }}",
            data: { inscription_id: inscriptionId },
            dataType: 'json',
            success: function(data) {
                debugLog('Catégories reçues:', data);
                var categoriesData = Array.isArray(data) ? data : [];
                categories = categoriesData;
                if (categoriesData.length > 0) {
                    displayCategories(categoriesData);
                } else {
                    resetCategorySelection();
                    $('#category-selection-section').hide();
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur chargement catégories:', {status, error, response: xhr.responseText});
            }
        });
    }
    
    // Afficher les catégories de frais
    function displayCategories(liste) {
        debugLog('=== Affichage des catégories:', liste);
        categories = Array.isArray(liste) ? liste : [];

        var porteur = categories.find(function(categorie) {
            return !categorie.satisfied_in_kind;
        }) || categories[0];

        if (porteur) {
            selectedCategory = porteur;
            $('#selected_category_id').val(porteur.id);
        }

        $('#category-selection-section').hide();
        $('#payment-details-section').show();
        $('#submit-section').show();
        $('#repartition-section').show();
        $('#repartition-lignes').html('');
        repartitionManuelle = false;
        $('#repartition-toggle-label').text('Répartir moi-même');
        if (porteur) {
            loadPaymentDetails(porteur);
        }
        demanderRepartition();
    }
    
    // Sélectionner une catégorie
    function selectCategory($element) {
        $('.category-option').removeClass('selected');
        $element.addClass('selected');
        
        selectedCategory = JSON.parse($element.attr('data-category'));
        $('#selected_category_id').val(selectedCategory.id);
        
        debugLog('Catégorie sélectionnée:', selectedCategory);
        
        // Afficher la section de détails du paiement
        loadPaymentDetails(selectedCategory);
        $('#payment-details-section').fadeIn();
        $('#submit-section').fadeIn();
    }
    
    // Charger les détails du paiement pour la catégorie sélectionnée
    function loadPaymentDetails(category) {
        // Calculer les suggestions de montant
        var suggestions = calculateAmountSuggestions(category);
        displayAmountSuggestions(suggestions);
        rafraichirRepartition();
    }

    // ------------------------------------------------------------------
    // Répartition du versement
    //
    // Cet écran ne calcule RIEN : il demande au serveur où l'argent ira, et
    // affiche la réponse. La règle de répartition n'existe qu'en un seul
    // exemplaire côté serveur ; une seconde écriture ici finirait par diverger
    // (le frais servi en premier, le porteur de l'avance), et la même saisie
    // produirait deux écritures comptables selon la porte d'entrée.
    //
    // Le garde-fou visible ici est donc le VRAI garde-fou, rendu tôt. Il ne
    // remplace pas celui de l'enregistrement, qui reste seul à faire foi.
    // ------------------------------------------------------------------
    function montantSaisi() {
        const valeur = parseFloat($('#montant').val());
        return Number.isFinite(valeur) ? valeur : 0;
    }

    function partsSaisies() {
        const parts = {};
        $('.pc-rep-input').each(function() {
            const id = $(this).data('category-id');
            const valeur = parseFloat($(this).val());
            parts[id] = Number.isFinite(valeur) ? valeur : 0;
        });
        return parts;
    }

    function rafraichirRepartition() {
        clearTimeout(repartitionTimer);
        repartitionTimer = setTimeout(demanderRepartition, 350);
    }

    function demanderRepartition() {
        // Trois provenances pour l'inscription. Le dernier repli vise le champ
        // CACHE rendu quand on arrive depuis la fiche d'un etudiant : il porte
        // un `name` mais PAS d'attribut `id`, donc `$('#inscription_id')` ne le
        // trouve pas. Ce chemin passe aujourd'hui par `currentInscription`, mais
        // le repli evite que l'apercu devienne muet si cette variable change.
        const inscriptionId = currentInscription
            || $('#inscription_id').val()
            || $('[name="inscription_id"]').val();
        const categorieId = $('#selected_category_id').val();
        const montant = montantSaisi();

        if (!inscriptionId) {
            $('#repartition-section').hide();
            bloquerEnvoi(false);
            return;
        }

        if (!categorieId || montant <= 0) {
            $('#repartition-section').show();
            afficherDispatchRepos();
            bloquerEnvoi(false);
            return;
        }

        const charge = {
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val(),
            inscription_id: inscriptionId,
            frais_category_id: categorieId,
            montant: montant
        };

        if (repartitionManuelle) {
            charge.repartition = partsSaisies();
        }

        if (repartitionRequete) {
            repartitionRequete.abort();
        }

        repartitionRequete = $.ajax({
            url: apercuUrl,
            method: 'POST',
            data: charge,
            dataType: 'json'
        }).done(function(data) {
            $('#repartition-section').show();
            $('#repartition-erreur').hide().text('');
            afficherRepartition(data.allocations || [], data.reste || {});
            bloquerEnvoi(false);
        }).fail(function(xhr) {
            if (xhr.statusText === 'abort') {
                return;
            }
            const reponse = xhr.responseJSON || {};
            $('#repartition-section').show();
            $('#repartition-erreur')
                .text(reponse.message || "Impossible de vérifier la répartition de ce versement.")
                .show();
            // On empêche l'envoi tant que la caisse n'a pas corrigé, mais c'est
            // l'enregistrement qui refuse pour de bon.
            bloquerEnvoi(xhr.status === 422);
        });
    }

    function afficherDispatchRepos() {
        $('#repartition-sub').text("Saisissez le montant : il se répartit tout seul sur les frais encore dus.");
        let html = '';
        categories.forEach(function(categorie) {
            const restant = Math.max(0, Number(categorie.montant || 0) - getPaidAmountForCategory(categorie.id));
            const note = categorie.satisfied_in_kind
                ? 'déjà déposé en nature'
                : (restant > 0 ? formatAmount(restant) + ' FCFA restent dus' : 'soldé');
            html += '<div class="pc-rep-ligne">'
                + '<div><div class="pc-rep-nom">' + categorie.name + '</div>'
                + '<div class="pc-rep-reste">' + note + '</div></div>'
                + '<div class="pc-rep-montant">0 FCFA</div>'
                + '</div>';
        });
        if (!html) {
            html = '<div class="pc-rep-reste">Aucun frais à imputer pour cette inscription.</div>';
        }
        $('#repartition-lignes').html(html);
    }

    function afficherRepartition(allocations, reste) {
        if (!repartitionManuelle) {
            $('#repartition-sub').text("Sur quels frais cet argent sera imputé.");
            let html = '';
            allocations.forEach(function(ligne) {
                const restant = reste[ligne.frais_category_id];
                const detail = (restant === undefined)
                    ? "montant du frais non configuré"
                    : formatAmount(restant) + ' FCFA restaient dus';
                html += '<div class="pc-rep-ligne">'
                    + '<div><div class="pc-rep-nom">' + ligne.name + '</div>'
                    + '<div class="pc-rep-reste">' + detail + '</div></div>'
                    + '<div class="pc-rep-montant">' + formatAmount(ligne.montant) + ' FCFA</div>'
                    + '</div>';
            });
            $('#repartition-lignes').html(html);
            return;
        }

        $('#repartition-sub').text("Indiquez le montant imputé à chaque frais. Le total doit valoir le versement.");

        // En saisie manuelle, on ne réécrit les champs QUE la première fois :
        // les remplacer à chaque frappe ferait sauter le curseur du caissier.
        if ($('.pc-rep-input').length === 0) {
            let html = '';
            categories.forEach(function(categorie) {
                const propose = allocations.find(function(l) { return l.frais_category_id === categorie.id; });
                const restant = reste[categorie.id];
                const detail = (restant === undefined)
                    ? "montant du frais non configuré"
                    : formatAmount(restant) + ' FCFA restent dus';
                html += '<div class="pc-rep-ligne">'
                    + '<div><div class="pc-rep-nom">' + categorie.name + '</div>'
                    + '<div class="pc-rep-reste">' + detail + '</div></div>'
                    + '<input type="number" min="0" step="1" class="pc-rep-input" '
                    + 'name="repartition[' + categorie.id + ']" data-category-id="' + categorie.id + '" '
                    + 'value="' + (propose ? propose.montant : 0) + '">'
                    + '</div>';
            });
            html += '<div class="pc-rep-total"><span>Total réparti</span><span id="repartition-total">—</span></div>';
            $('#repartition-lignes').html(html);
            $('.pc-rep-input').on('input', function() {
                majTotalReparti();
                rafraichirRepartition();
            });
        }

        majTotalReparti();
    }

    function majTotalReparti() {
        const parts = partsSaisies();
        let total = 0;
        Object.keys(parts).forEach(function(id) { total += parts[id]; });
        $('#repartition-total').text(formatAmount(total) + ' FCFA');
    }

    function bloquerEnvoi(bloque) {
        $('#payment-form').find('button[type="submit"]').prop('disabled', bloque);
    }

    $('#repartition-toggle').on('click', function() {
        repartitionManuelle = !repartitionManuelle;
        $('#repartition-toggle-label').text(repartitionManuelle ? 'Répartition automatique' : 'Répartir moi-même');
        // Repartir des champs à neuf : passer d'un mode à l'autre change la
        // nature de ce qui est affiché.
        $('#repartition-lignes').html('');
        demanderRepartition();
    });

    $('#montant').on('input', rafraichirRepartition);

    function getPaidAmountForCategory(categoryId) {
        if (!studentBalance || !studentBalance.categories) {
            return 0;
        }

        var key = String(categoryId);
        var categoryBalance = studentBalance.categories[key] || studentBalance.categories[categoryId] || null;

        if (typeof categoryBalance === 'number') {
            return Number.isFinite(categoryBalance) ? categoryBalance : 0;
        }

        if (categoryBalance && typeof categoryBalance === 'object') {
            var paid = Number(categoryBalance.paid || 0);
            return Number.isFinite(paid) ? paid : 0;
        }

        return 0;
    }
    
    // Calculer les suggestions de montant
    function calculateAmountSuggestions(category) {
        var suggestions = [];
        var total = Number(category.montant || 0);
        var paid = Number.isFinite(Number(category.paid))
            ? Number(category.paid)
            : getPaidAmountForCategory(category.id);
        var remaining = Number.isFinite(Number(category.remaining))
            ? Math.max(0, Number(category.remaining))
            : Math.max(0, total - paid);
        
        // Suggestions intelligentes
        if (remaining > 0) {
            suggestions.push({
                label: "Tout le reste",
                amount: remaining
            });
            
            if (remaining >= 2) {
                suggestions.push({
                    label: "La moitié",
                    amount: Math.floor(remaining * 0.5)
                });
            }

        }
        
        return suggestions;
    }
    
    // Afficher les suggestions de montant
    function displayAmountSuggestions(suggestions) {
        var html = '';
        suggestions.forEach(function(suggestion) {
            html += `
                <button type="button" class="amount-suggestion" data-amount="${suggestion.amount}">
                    <span>${suggestion.label}</span><b>${formatAmount(suggestion.amount)} F</b>
                </button>
            `;
        });
        
        $('#amount-suggestions').html(html);
        
        // Ajouter les événements de clic
        $('.amount-suggestion').on('click', function() {
            var amount = $(this).attr('data-amount');
            $('#montant').val(amount).focus();
        });
    }
    
    // Calculer le progrès d'une catégorie
    function calculateCategoryProgress(category) {
        var paid = getPaidAmountForCategory(category.id);
        var total = Number(category.montant || 0);
        var percentage = total > 0 ? Math.round((paid / total) * 100) : 0;
        
        return {
            paid: paid,
            total: total,
            remaining: Math.max(0, total - paid),
            percentage: Math.min(percentage, 100)
        };
    }
    
    // Mettre à jour l'affichage de progression
    function updateProgressDisplay(balanceData) {
        if (!balanceData || !balanceData.categories) {
            debugLog('Pas de données de solde disponibles');
            resetProgressDisplay();
            return;
        }
        
        var totalPaid = 0;
        var totalDue = 0;
        var html = '';
        
        // Calculer les totaux et créer l'affichage pour chaque catégorie
        Object.keys(balanceData.categories).forEach(function(categoryId) {
            var categoryBalance = balanceData.categories[categoryId];
            var paid = Number(categoryBalance.paid || 0);
            var total = Number(categoryBalance.total || 0);
            var remaining = Math.max(0, total - paid);

            totalPaid += Number.isFinite(paid) ? paid : 0;
            totalDue += Number.isFinite(total) ? total : 0;
            
            var percentage = total > 0 ? Math.round((paid / total) * 100) : 0;
            
            html += `
                <div class="category-progress">
                    <div class="pc-fee-top">
                        <span class="pc-fee-name">${categoryBalance.name || 'Catégorie ' + categoryId}</span>
                        <span class="pc-fee-rest">${formatAmount(remaining)} F</span>
                    </div>
                    <div class="progress-bar-modern">
                        <div class="progress-fill" style="width: ${percentage}%; background: ${getProgressColor(percentage)}"></div>
                    </div>
                    <div class="pc-fee-sub">
                        <span>${formatAmount(paid)} F payé</span>
                        <span>${formatAmount(total)} F dû</span>
                    </div>
                </div>
            `;
        });

        $('#categories-progress').html(html);

        var totalRemaining = Math.max(0, totalDue - totalPaid);
        var totalPercentage = totalDue > 0 ? Math.round((totalPaid / totalDue) * 100) : 0;
        $('#total-remaining').text(formatAmount(totalRemaining) + ' F');
        $('#total-progress').text(totalPercentage + ' % payé');
        $('#total-paid').text(formatAmount(totalPaid) + ' F payé');
    }

    function resetProgressDisplay() {
        $('#categories-progress').html('');
        $('#total-remaining').text('0 F');
        $('#total-progress').text('0 % payé');
        $('#total-paid').text('0 F payé');
    }
    
    // Obtenir l'icône pour un type de catégorie
    function getCategoryIcon(type) {
        var icons = {
            'inscription': 'fas fa-user-plus',
            'scolarite': 'fas fa-graduation-cap',
            'examen': 'fas fa-clipboard-check',
            'diplome': 'fas fa-certificate',
            'divers': 'fas fa-ellipsis-h'
        };
        return icons[type] || 'fas fa-money-bill';
    }
    
    // Obtenir la couleur de progression
    function getProgressColor(percentage) {
        return percentage >= 100 ? '#10b981' : '#0453cb';
    }

    function showInscriptionNotice(type, message) {
        var $notice = $('#inscription-auto-notice');
        if ($notice.length === 0) {
            return;
        }

        var alertClass = 'alert-info';
        if (type === 'success') {
            alertClass = 'alert-success';
        } else if (type === 'warning') {
            alertClass = 'alert-warning';
        }

        $notice
            .removeClass('alert-info alert-success alert-warning')
            .addClass(alertClass)
            .text(message || '')
            .show();
    }

    function hideInscriptionNotice() {
        var $notice = $('#inscription-auto-notice');
        if ($notice.length === 0) {
            return;
        }

        $notice
            .hide()
            .text('')
            .removeClass('alert-info alert-success alert-warning')
            .addClass('alert-info');
    }
    
    // Formater un montant
    function formatAmount(amount) {
        var numericAmount = Number(amount || 0);
        if (!Number.isFinite(numericAmount)) {
            numericAmount = 0;
        }

        return new Intl.NumberFormat('fr-FR').format(numericAmount);
    }
    
    // Réinitialiser le formulaire
    function resetForm() {
        currentInscription = null;
        studentBalance = null;
        hideInscriptionNotice();
        resetProgressDisplay();
        $('#inscription_id').val('').trigger('input');
        $('#student-progress-section').hide();
        $('#category-selection-section').hide();
        $('#payment-details-section').hide();
        $('#submit-section').hide();
        resetCategorySelection();
    }
    
    // Réinitialiser la sélection de catégorie
    function resetCategorySelection() {
        $('#category-options').html('');
        $('#selected_category_id').val('');
        $('#amount-suggestions').html('');
        selectedCategory = null;
        $('#payment-details-section').hide();
        $('#submit-section').hide();

        // Les champs de repartition portent `name="repartition[...]"` : les
        // laisser en place les enverrait avec le versement suivant, sur un
        // etudiant qui n'a rien a voir.
        $('#repartition-lignes').html('');
        $('#repartition-erreur').hide().text('');
        $('#repartition-section').hide();
        repartitionManuelle = false;
        $('#repartition-toggle-label').text('Répartir moi-même');
        bloquerEnvoi(false);
    }

    let isSubmitting = false;

    $('#payment-form').on('submit', function(e) {
        const montantVal = parseInt($('#montant').val() || 0);
        const threshold = parseInt(@json((int) ($unusualAmountThreshold ?? 500000)));
        const $confirmCheckbox = $('input[name="confirmed_unusual_amount"]');
        if (montantVal > threshold && $confirmCheckbox.length > 0 && !$confirmCheckbox.is(':checked')) {
            e.preventDefault();
            const $alert = $('.qw3-unusual-alert');
            if ($alert.length) {
                $alert[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => $confirmCheckbox.trigger('focus'), 400);
            }
            return false;
        }

        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true;
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement en cours...');
        $submitBtn.addClass('disabled');
        return true;
    });
});
</script>
@endpush 
