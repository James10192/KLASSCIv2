@extends('layouts.app')

@section('title', 'Paramètres du Système - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .settings-section {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 2px solid var(--border);
    }
    
    .section-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-md);
        font-size: 1.25rem;
        color: white;
    }
    
    .section-icon.school,
    .section-icon.pdf,
    .section-icon.bulletin,
    .section-icon.display,
    .section-icon.mentions,
    .section-icon.stats { background: linear-gradient(135deg, #0453cb, #3b7ddb); }

    /* ── Zone upload logo premium ──────────────────────────── */
    .pdf-logo-zone {
        display: flex; gap: 24px; align-items: flex-start;
        background: #f8fafc; border: 1px solid #e5e7eb;
        border-radius: 14px; padding: 20px;
    }
    .pdf-logo-current {
        position: relative; flex-shrink: 0;
        width: 120px; height: 120px;
        border-radius: 12px; overflow: hidden;
        background: #fff; border: 2px dashed #d1d5db;
        display: flex; align-items: center; justify-content: center;
    }
    .pdf-logo-current img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .pdf-logo-placeholder { color: #9ca3af; text-align: center; font-size: .78rem; }
    .pdf-logo-placeholder i { display: block; font-size: 2rem; margin-bottom: 4px; }
    .pdf-logo-badge {
        position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%);
        background: #10b981; color: #fff; font-size: .65rem; font-weight: 700;
        padding: 2px 7px; border-radius: 20px; white-space: nowrap;
    }
    .pdf-logo-upload-side { flex: 1; display: flex; flex-direction: column; gap: 14px; }
    .pdf-logo-drop {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px 16px;
        cursor: pointer; transition: all .25s; background: #fff; text-align: center;
    }
    .pdf-logo-drop:hover { border-color: var(--primary); background: #f0f7ff; }
    .pdf-logo-drop-icon { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; transition: color .2s; }
    .pdf-logo-drop:hover .pdf-logo-drop-icon { color: var(--primary); }
    .pdf-logo-drop-title { font-weight: 700; color: #374151; font-size: .9rem; }
    .pdf-logo-drop-sub { color: #6b7280; font-size: .82rem; }
    .pdf-logo-drop-hint { color: #9ca3af; font-size: .73rem; margin-top: 4px; }
    .pdf-logo-toggles { display: flex; gap: 20px; flex-wrap: wrap; align-items: center; }

    /* ── Layout couleurs : 2 colonnes ─────────────────────── */
    .pdf-color-layout {
        display: grid; grid-template-columns: 1fr 300px; gap: 28px; align-items: start;
    }
    @media (max-width: 900px) { .pdf-color-layout { grid-template-columns: 1fr; } }

    /* ── Lignes de picker ──────────────────────────────────── */
    .pdf-color-pickers { display: flex; flex-direction: column; gap: 12px; }
    .pdf-picker-row {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 14px 16px; border-radius: 10px; background: #f8fafc;
        border: 1px solid #e5e7eb; cursor: pointer;
        transition: border-color .2s, box-shadow .2s;
    }
    .pdf-picker-row:hover { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(4,83,203,.07); }
    .pdf-picker-swatch-wrap {
        position: relative; flex-shrink: 0; width: 48px; height: 48px; cursor: pointer;
    }
    .pdf-color-input {
        position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%;
        cursor: pointer; border: none; padding: 0;
    }
    .pdf-picker-swatch {
        display: block; width: 48px; height: 48px;
        border-radius: 10px; border: 2px solid rgba(0,0,0,.12);
        pointer-events: none; transition: background .15s;
        box-shadow: 0 2px 6px rgba(0,0,0,.12);
    }
    .pdf-picker-meta { flex: 1; }
    .pdf-picker-label { font-weight: 700; color: #1e293b; font-size: .88rem; margin-bottom: 3px; }
    .pdf-picker-desc { color: #6b7280; font-size: .78rem; line-height: 1.4; }
    .pdf-contrast-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: .7rem; font-weight: 700; padding: 2px 8px;
        border-radius: 20px; margin-top: 6px;
    }
    .pdf-contrast-badge.ok { background: #d1fae5; color: #065f46; }
    .pdf-contrast-badge.warn { background: #fef3c7; color: #92400e; }
    .pdf-contrast-badge.bad { background: #fee2e2; color: #991b1b; }

    /* ── Prévisualisation mini-document ───────────────────── */
    .pdf-color-preview {
        border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.07); background: #fff; font-family: serif;
        position: sticky; top: 100px;
    }
    .prev-header {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; transition: background .2s;
    }
    .prev-logo-placeholder {
        width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.25); border-radius: 6px; font-size: .9rem; color: rgba(255,255,255,.7);
        flex-shrink: 0;
    }
    .prev-school-name { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .prev-school-meta { font-size: .6rem; opacity: .82; }
    .prev-divider { height: 3px; transition: background .2s; }
    .prev-doc-title {
        text-align: center; font-size: .7rem; font-weight: 800; letter-spacing: .1em;
        padding: 10px 14px 6px; text-transform: uppercase; transition: color .2s, border-color .2s;
        border-bottom-width: 2px; border-bottom-style: solid; display: inline-block;
        margin: 0 auto; display: block;
    }
    .prev-body { padding: 10px 14px; }
    .prev-line {
        height: 6px; background: #e2e8f0; border-radius: 3px; margin-bottom: 6px;
    }
    .prev-line-lg { width: 90%; }
    .prev-line-md { width: 70%; }
    .prev-line-sm { width: 50%; }
    .prev-hl-block {
        padding: 8px 10px; border-radius: 6px; margin: 8px 0;
        transition: border-color .2s, background .2s;
    }
    .prev-table { border-collapse: collapse; width: 100%; font-size: .65rem; }
    .prev-table-head {
        display: flex; transition: background .2s, color .2s;
        padding: 5px 14px;
    }
    .prev-table-head span, .prev-table-row span { flex: 1; }
    .prev-table-row {
        display: flex; padding: 4px 14px;
        border-bottom: 1px solid #e5e7eb; transition: color .2s;
    }
    .prev-legend {
        text-align: center; font-size: .58rem; color: #9ca3af; font-style: italic;
        padding: 6px; border-top: 1px solid #f1f5f9;
    }

    /* ── Avertissement contraste global ──────────────────── */
    .pdf-contrast-warning {
        display: flex; align-items: center; gap: 8px;
        background: #fef3c7; border-left: 4px solid #f59e0b;
        border-radius: 8px; padding: 10px 14px; margin-top: 16px;
        font-size: .83rem; color: #92400e;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    
    .section-description {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin: 0;
    }
    
    .form-group {
        margin-bottom: var(--space-lg);
    }
    
    .form-label-modern {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .form-control-modern {
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-sm) var(--space-md);
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .form-control-modern:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    .form-switch-modern {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }
    
    .form-switch-modern input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: var(--primary);
    }
    
    input:checked + .slider:before {
        transform: translateX(24px);
    }
    
    .threshold-input {
        width: 80px;
        text-align: center;
    }
    
    .btn-save {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none;
        color: white;
        padding: var(--space-md) var(--space-xl);
        border-radius: var(--radius-large);
        font-weight: 600;
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }
    
    .alert-modern {
        border: none;
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
    }
    
    .settings-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
    }
    
    .settings-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: var(--space-md);
    }
    
    .section-actions {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        margin-left: auto;
    }
    
    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 2px solid var(--border);
    }
    
    .section-header .section-actions {
        margin-left: auto;
    }
    
    .file-upload-area {
        border: 2px dashed var(--border);
        border-radius: var(--border-radius);
        padding: var(--space-md);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .file-upload-area:hover {
        border-color: var(--primary);
        background-color: var(--light);
    }
    
    .current-logo {
        text-align: center;
    }

    /* Tabs styles */
    .nav-tabs-modern {
        border-bottom: 2px solid var(--border);
        margin-bottom: var(--space-xl);
    }

    .nav-tabs-modern .nav-link {
        border: none;
        color: var(--text-secondary);
        padding: var(--space-md) var(--space-lg);
        font-weight: 600;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }

    .nav-tabs-modern .nav-link:hover {
        color: var(--primary);
        background-color: transparent;
    }

    .nav-tabs-modern .nav-link.active {
        color: var(--primary);
        background-color: transparent;
        border-bottom-color: var(--primary);
    }

    .nav-tabs-modern .nav-link i {
        margin-right: var(--space-xs);
    }

    .section-icon.notifications,
    .section-icon.conduite,
    .section-icon.ponderation,
    .section-icon.tronc { background: linear-gradient(135deg, #0453cb, #3b7ddb); }

    /* ── Bulletin Config Premium Cards ────────────────────── */
    .bc-grid { display: grid; gap: 12px; }
    .bc-grid-2 { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
    .bc-grid-3 { grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); }

    .bc-card {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 16px; border-radius: 12px;
        background: #f8fafc; border: 1px solid #e5e7eb;
        transition: border-color .2s, box-shadow .2s;
    }
    .bc-card:hover {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .06);
    }

    .bc-icon {
        flex-shrink: 0; width: 40px; height: 40px;
        border-radius: 10px; display: flex;
        align-items: center; justify-content: center;
        font-size: .95rem; color: #fff;
    }
    .bc-icon { background: linear-gradient(135deg, var(--primary), var(--secondary, #5e91de)); }

    .bc-body { flex: 1; min-width: 0; }
    .bc-label { font-weight: 600; color: #1e293b; font-size: .88rem; margin-bottom: 2px; }
    .bc-desc { color: #64748b; font-size: .78rem; line-height: 1.4; margin-top: 4px; }

    .bc-toggle { flex-shrink: 0; margin-left: auto; padding-top: 2px; }

    .bc-input-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; border-radius: 12px;
        background: #f8fafc; border: 1px solid #e5e7eb;
    }
    .bc-input-row .bc-icon { width: 36px; height: 36px; font-size: .85rem; }
    .bc-input-row .form-control-modern { max-width: 100px; }

    .bc-info-box {
        padding: 16px; border-radius: 12px;
        background: #f0f9ff; border: 1px solid #bae6fd;
        margin-top: 16px; font-size: .84rem; color: #0c4a6e;
    }
    .bc-info-box strong { color: #075985; }
    .bc-info-box ul { margin: 6px 0 0 16px; padding: 0; }
    .bc-info-box li { margin-bottom: 3px; }

    .bc-hint {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 16px; border-radius: 10px;
        background: #f8fafc; border: 1px solid #e5e7eb;
        font-size: .82rem; color: #64748b; margin-top: 12px;
    }
    .bc-hint i { color: var(--primary); }


    .mga-rows, .council-editor { display: grid; gap: 12px; }
    .mga-row, .council-card {
        display: grid;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }
    .mga-row { grid-template-columns: 140px 140px 140px 1fr auto; align-items: end; }
    .council-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
    .mga-row label, .council-card label { font-size: .78rem; color: #475569; font-weight: 600; display: grid; gap: 6px; }
    .mga-preview { font-size: .78rem; color: #334155; padding-bottom: 8px; }
    @media (max-width: 900px) {
        .mga-row { grid-template-columns: 1fr; }
    }

    .app-scale-editor {
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 16px;
        margin-top: 14px;
    }
    .app-scale-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .app-scale-title {
        margin: 0;
        color: #1e293b;
        font-size: .98rem;
        font-weight: 700;
    }
    .app-scale-desc {
        margin: 3px 0 0;
        color: #64748b;
        font-size: .82rem;
        line-height: 1.4;
    }
    .app-scale-table-wrap {
        overflow-x: auto;
    }
    .app-scale-table {
        width: 100%;
        min-width: 620px;
        border-collapse: collapse;
    }
    .app-scale-table th {
        color: #475569;
        font-size: .76rem;
        font-weight: 700;
        padding: 8px;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }
    .app-scale-table td {
        padding: 8px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    .app-scale-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .app-scale-table input[type="number"] {
        max-width: 120px;
    }

    .mailpulse-panel {
        font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .mailpulse-brand-card {
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        padding: 18px 20px; border-radius: 14px;
        background: #fff; border: 1px solid #e5e7eb; color: #09090b;
        margin-bottom: 20px;
    }
    .mailpulse-logo-mark {
        position: relative; display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 36px; flex-shrink: 0;
    }
    .mailpulse-logo-mark img { width: 48px; height: auto; object-fit: contain; }
    .mailpulse-brand-copy { min-width: 220px; }
    .mailpulse-wordmark {
        font-size: 1.35rem; font-weight: 750; color: #09090b; line-height: 1;
    }
    .mailpulse-wordmark span { color: var(--mailpulse-signal); }
    .mailpulse-brand-subtitle { margin-top: 6px; color: #a1a1aa; font-size: .86rem; }
    .mailpulse-status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px; border-radius: 999px;
        background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa;
        font-size: .78rem; font-weight: 700;
    }
    .mailpulse-status-badge.configured {
        background: #ecfdf5; color: #047857; border-color: #a7f3d0;
    }
    .mailpulse-field-card {
        padding: 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff;
    }
    .mailpulse-toggle {
        display: flex; align-items: center; gap: 10px; min-height: 44px; color: #334155; font-weight: 600;
    }
    .mailpulse-toggle input { position: absolute; opacity: 0; pointer-events: none; }
    .mailpulse-toggle-slider {
        width: 42px; height: 24px; border-radius: 999px; background: #cbd5e1; position: relative; flex: 0 0 auto;
        transition: background .2s ease;
    }
    .mailpulse-toggle-slider::after {
        content: ''; width: 18px; height: 18px; border-radius: 50%; background: #fff; position: absolute; top: 3px; left: 3px;
        box-shadow: 0 1px 3px rgba(15,23,42,.24); transition: transform .2s ease;
    }
    .mailpulse-toggle input:checked + .mailpulse-toggle-slider { background: #0453cb; }
    .mailpulse-toggle input:checked + .mailpulse-toggle-slider::after { transform: translateX(18px); }
    .mailpulse-recipient-list {
        display: grid; gap: 8px; margin-bottom: 10px;
    }
    .mailpulse-recipient-row {
        display: grid; grid-template-columns: 42px minmax(0, 1fr) 38px; gap: 8px; align-items: center;
    }
    .mailpulse-mini-toggle {
        width: 42px; height: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;
    }
    .mailpulse-mini-toggle input { position: absolute; opacity: 0; pointer-events: none; }
    .mailpulse-mini-toggle span {
        width: 34px; height: 20px; border-radius: 999px; background: #cbd5e1; position: relative; transition: background .2s ease;
    }
    .mailpulse-mini-toggle span::after {
        content: ''; width: 14px; height: 14px; border-radius: 50%; background: #fff; position: absolute; top: 3px; left: 3px;
        box-shadow: 0 1px 3px rgba(15,23,42,.22); transition: transform .2s ease;
    }
    .mailpulse-mini-toggle input:checked + span { background: #0453cb; }
    .mailpulse-mini-toggle input:checked + span::after { transform: translateX(14px); }
    .mailpulse-icon-button,
    .mailpulse-add-button {
        border: 1px solid #dbe4f0; background: #fff; color: #0453cb; min-height: 38px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700;
    }
    .mailpulse-icon-button { width: 38px; color: #64748b; }
    .mailpulse-add-button { padding: 0 12px; }
    .mailpulse-icon-button:hover,
    .mailpulse-add-button:hover { border-color: #0453cb; background: #f8fbff; }
    .mailpulse-code-input {
        font-family: "Space Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .84rem;
    }
    .mailpulse-info-grid {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;
        margin-top: 16px;
    }
    .mailpulse-info-card {
        padding: 14px; border-radius: 12px; background: #fafafa;
        border: 1px solid #e5e7eb; color: #3f3f46; font-size: .82rem;
    }
    .mailpulse-info-card strong { color: #09090b; display: block; margin-bottom: 4px; }
    .mailpulse-info-card i { color: var(--mailpulse-signal); margin-right: 6px; }
    .mailpulse-test-grid {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end;
    }
    .mailpulse-test-result {
        margin-top: 14px; border-radius: 12px; border: 1px solid #dbe4f0;
        background: #f8fafc; padding: 14px; color: #334155; font-size: .86rem;
    }
    .mailpulse-test-result.is-success {
        border-color: #a7f3d0; background: #ecfdf5; color: #065f46;
    }
    .mailpulse-test-result.is-error {
        border-color: #fecaca; background: #fef2f2; color: #991b1b;
    }
    .mailpulse-test-kv {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 10px;
    }
    .mailpulse-test-kv span {
        display: block; padding: 8px 10px; border-radius: 8px; background: rgba(255,255,255,.75);
        border: 1px solid rgba(148,163,184,.24);
    }
    .mailpulse-test-kv strong { display: block; color: inherit; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
    .mailpulse-save-row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        padding: 14px 16px; margin-top: 16px; border-radius: 12px; border: 1px solid #dbe4f0; background: #f8fafc;
    }
    .mailpulse-save-row strong { color: #0f172a; display: block; margin-bottom: 2px; }
    .mailpulse-save-status { font-size: .84rem; color: #64748b; }
    .mailpulse-save-status.is-success { color: #047857; }
    .mailpulse-save-status.is-error { color: #b91c1c; }
    .section-icon.mailpulse { background: #09090b; color: var(--mailpulse-signal); }
    @media (max-width: 992px) {
        .mailpulse-info-grid { grid-template-columns: 1fr; }
        .mailpulse-test-grid,
        .mailpulse-test-kv { grid-template-columns: 1fr; }
    }

    .settings-page-premium {
        --mailpulse-signal: #ff5a1f;
        --sp-ink: #0f172a;
        --sp-muted: #64748b;
        --sp-line: #e2e8f0;
        --sp-soft: #f8fafc;
        --sp-primary: #0453cb;
        --sp-primary-2: #5e91de;
    }
    .settings-page-premium .main-content {
        background:
            linear-gradient(180deg, rgba(4,83,203,.045), rgba(255,255,255,0) 360px);
        border-radius: 0;
    }
    .settings-page-premium .dashboard-header {
        display: none;
    }
    .settings-page-premium .settings-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        margin-bottom: 1.25rem;
        color: #fff;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
    }
    .settings-page-premium .settings-hero-content {
        position: relative;
        z-index: 1;
    }
    .settings-page-premium .settings-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .settings-page-premium .settings-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .settings-page-premium .settings-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        color: #fff;
    }
    .settings-page-premium .settings-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 11px;
        border-radius: 999px;
        background: rgba(255,255,255,.11);
        border: 1px solid rgba(255,255,255,.18);
        color: rgba(255,255,255,.84);
        font-size: .78rem;
        font-weight: 700;
        margin-bottom: .65rem;
    }
    .settings-page-premium .settings-hero-title {
        margin: 0;
        color: #fff;
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: 0;
    }
    .settings-page-premium .settings-hero-subtitle {
        max-width: 720px;
        margin: .35rem 0 0;
        color: rgba(255,255,255,.72);
        font-size: .88rem;
        line-height: 1.5;
    }
    .settings-page-premium .settings-hero-metrics {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    .settings-page-premium .settings-hero-metric {
        flex: 1;
        min-width: 140px;
        padding: .9rem 1rem;
        border-radius: 12px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .settings-page-premium .settings-hero-metric i {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.12);
        color: #fff;
        flex-shrink: 0;
    }
    .settings-page-premium .settings-hero-metric span {
        display: block;
        color: rgba(255,255,255,.62);
        font-size: .72rem;
        font-weight: 700;
        margin-top: .15rem;
    }
    .settings-page-premium .settings-hero-metric strong {
        display: block;
        margin-top: 0;
        color: #fff;
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .settings-page-premium .settings-hero-metric.is-ready strong { color: #86efac; }
    .settings-page-premium .settings-hero-metric.is-warning strong { color: #fde68a; }

    .settings-page-premium .alert-modern {
        border-radius: 14px;
        border: 1px solid transparent;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
    }
    .settings-page-premium .nav-tabs-modern {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 8px;
        margin: 0 0 20px;
        border: 1px solid var(--sp-line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15,23,42,.05);
        scrollbar-width: thin;
    }
    .settings-page-premium .nav-tabs-modern .nav-item { flex: 0 0 auto; }
    .settings-page-premium .nav-tabs-modern .nav-link {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
        border-radius: 12px;
        padding: 10px 13px;
        margin: 0;
        color: #475569;
        background: transparent;
        font-size: .88rem;
        white-space: nowrap;
    }
    .settings-page-premium .nav-tabs-modern .nav-link i { margin-right: 0; }
    .settings-page-premium .nav-tabs-modern .nav-link:hover {
        color: var(--sp-primary);
        background: #f8fafc;
        border-color: #e2e8f0;
    }
    .settings-page-premium .nav-tabs-modern .nav-link.active {
        color: #fff;
        background: var(--sp-primary);
        border-color: var(--sp-primary);
        box-shadow: 0 10px 24px rgba(4,83,203,.22);
    }

    .settings-page-premium .settings-section {
        border-radius: 18px;
        border: 1px solid rgba(226,232,240,.95);
        background: rgba(255,255,255,.96);
        box-shadow: 0 14px 34px rgba(15,23,42,.055);
        padding: clamp(18px, 2.2vw, 26px);
    }
    .settings-page-premium .settings-section:hover {
        box-shadow: 0 18px 42px rgba(15,23,42,.075);
    }
    .settings-page-premium .section-header {
        align-items: flex-start;
        gap: 14px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 16px;
    }
    .settings-page-premium .section-icon {
        width: 42px;
        height: 42px;
        margin-right: 0;
        border-radius: 12px;
        background: #0453cb !important;
        color: #fff;
        box-shadow: 0 10px 22px rgba(4,83,203,.2);
    }
    .settings-page-premium .section-icon.mailpulse {
        background: #09090b !important;
        color: var(--mailpulse-signal);
        box-shadow: 0 10px 24px rgba(9,9,11,.18);
    }
    .settings-page-premium .section-title {
        margin: 0;
        color: var(--sp-ink);
        font-size: 1.08rem;
        font-weight: 800;
    }
    .settings-page-premium .section-description {
        margin: 4px 0 0;
        color: var(--sp-muted);
        font-size: .86rem;
        line-height: 1.5;
    }
    .settings-page-premium .form-group {
        min-width: 0;
    }
    .settings-page-premium .form-label-modern {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #334155;
        font-size: .83rem;
        font-weight: 750;
        margin-bottom: 8px;
    }
    .settings-page-premium .form-control-modern,
    .settings-page-premium .ls-input {
        min-height: 44px;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        background: #fff;
        color: var(--sp-ink);
        box-shadow: 0 1px 0 rgba(15,23,42,.02);
    }
    .settings-page-premium .form-control-modern:focus,
    .settings-page-premium .ls-input:focus {
        border-color: var(--sp-primary);
        box-shadow: 0 0 0 4px rgba(4,83,203,.09);
    }
    .settings-page-premium .text-muted,
    .settings-page-premium small {
        color: #64748b !important;
    }
    .settings-page-premium .settings-actions-bar {
        position: sticky;
        bottom: 12px;
        z-index: 25;
        display: flex;
        justify-content: center;
        padding: 12px;
        margin-top: 22px;
        border: 1px solid rgba(226,232,240,.9);
        border-radius: 18px;
        background: rgba(255,255,255,.9);
        backdrop-filter: blur(12px);
        box-shadow: 0 18px 44px rgba(15,23,42,.14);
    }
    .settings-page-premium .btn-save {
        min-height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 22px;
        border-radius: 13px;
        background: #0453cb;
        color: #fff;
        box-shadow: 0 12px 24px rgba(4,83,203,.24);
    }
    .settings-page-premium .btn-save:hover {
        transform: translateY(-1px);
        background: #0345aa;
        box-shadow: 0 15px 30px rgba(4,83,203,.3);
    }
    @media (max-width: 992px) {
        .settings-page-premium .settings-hero-metrics { flex-direction: column; }
    }
    @media (max-width: 576px) {
        .settings-page-premium .settings-hero { padding: 1.25rem; border-radius: 18px; }
        .settings-page-premium .settings-hero-left { align-items: flex-start; }
        .settings-page-premium .settings-hero-icon { width: 46px; height: 46px; }
        .settings-page-premium .settings-section { border-radius: 16px; }
        .settings-page-premium .section-header { flex-direction: column; }
        .settings-page-premium .settings-actions-bar { bottom: 8px; }
        .settings-page-premium .btn-save { width: 100%; }
    }

    /* ── Éditeur barème d'assiduité (tranches configurables) ── */
    .att-editor { display: flex; flex-direction: column; gap: 16px; }
    .att-zero { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
    .att-zero-label { font-weight: 700; color: #1e293b; font-size: .9rem; margin-bottom: 6px; }
    .att-zero-input { max-width: 160px; }
    .att-scale { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; background: #fff; }
    .att-scale-head { margin-bottom: 8px; }
    .att-scale-title { font-weight: 800; color: #0453cb; font-size: .92rem; }
    .att-scale-sub { color: #64748b; font-size: .76rem; margin-top: 2px; }
    .att-table { width: 100%; border-collapse: collapse; }
    .att-table th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; padding: 4px 8px; }
    .att-table td { padding: 4px 8px; vertical-align: middle; }
    .att-from { font-weight: 700; color: #1e293b; }
    .att-inf { color: #64748b; font-style: italic; font-size: .82rem; }
    .att-num { max-width: 120px; }
    .att-del { border: 1px solid rgba(220,38,38,.25); background: rgba(220,38,38,.06); color: #dc2626; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; }
    .att-del:hover { background: #dc2626; color: #fff; }
    .att-add { margin-top: 8px; border: 1px dashed rgba(4,83,203,.35); background: rgba(4,83,203,.04); color: #0453cb; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: .8rem; cursor: pointer; }
    .att-add:hover { background: rgba(4,83,203,.1); }
    .att-sim { background: linear-gradient(135deg, rgba(4,83,203,.05), rgba(59,125,219,.06)); border: 1px solid rgba(4,83,203,.18); border-radius: 12px; padding: 12px 14px; }
    .att-sim-title { font-weight: 800; color: #0453cb; font-size: .88rem; margin-bottom: 8px; }
    .att-sim-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
    .att-sim-row label { display: flex; flex-direction: column; gap: 4px; font-size: .78rem; color: #475569; font-weight: 600; }
    .att-sim-out { font-size: .9rem; color: #1e293b; }
    .att-sim-out strong { color: #0453cb; }
    .att-warn { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.28); border-radius: 10px; padding: 10px 12px; color: #92400e; font-size: .82rem; display: flex; gap: 8px; }
    .att-warn ul { margin: 0; padding-left: 18px; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi settings-page-premium">
    <div class="main-content">
        @php
            $settingsTotal = isset($flatSettings) ? $flatSettings->count() : 0;
            $settingsGroups = isset($settings) ? $settings->count() : 0;
            $mailpulseApiKeyConfiguredForHero = \App\Models\Setting::where('key', 'mailpulse_api_key')
                ->where('is_active', true)
                ->whereNotNull('value')
                ->where('value', '<>', '')
                ->exists() || trim((string) config('services.mailpulse.api_key', '')) !== '';
            $mailpulseReady = $mailpulseApiKeyConfiguredForHero
                && \App\Helpers\SettingsHelper::get('mailpulse_enabled', '0') == '1';
        @endphp

        <div class="settings-hero">
            <div class="settings-hero-content">
                <div class="settings-hero-top">
                    <div class="settings-hero-left">
                        <div class="settings-hero-icon">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div>
                            <div class="settings-eyebrow">
                                <i class="fas fa-shield-alt"></i>
                                Configuration établissement
                            </div>
                            <h1 class="settings-hero-title">Paramètres du système</h1>
                            <p class="settings-hero-subtitle">
                                Pilotez les informations de l'établissement, les documents, les bulletins, la comptabilité et les canaux MailPulse depuis un centre de contrôle unique.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="settings-hero-metrics" aria-label="Synthèse des paramètres">
                    <div class="settings-hero-metric">
                        <i class="fas fa-list-check"></i>
                        <div>
                            <strong>{{ $settingsTotal }}</strong>
                            <span>Paramètres</span>
                        </div>
                    </div>
                    <div class="settings-hero-metric">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <strong>{{ $settingsGroups }}</strong>
                            <span>Groupes</span>
                        </div>
                    </div>
                    <div class="settings-hero-metric {{ $mailpulseReady ? 'is-ready' : 'is-warning' }}">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>{{ $mailpulseReady ? 'Prêt' : 'À configurer' }}</strong>
                            <span>MailPulse</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- En-tête moderne -->
        <div class="dashboard-header mb-lg">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="dashboard-title">Paramètres du Système</h1>
                    <p class="dashboard-subtitle">Configuration de l'établissement et des bulletins PDF</p>
                </div>
                <div class="header-right">
                    <div class="header-stats">
                        <div class="stat-item">
                            <span class="stat-number">{{ collect(request()->all())->count() }}</span>
                            <span class="stat-label">Paramètres</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertes -->
        @if(session('success'))
            <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                {{-- Sans cette liste, « Certaines configurations contiennent des
                     erreurs » restait la seule information : les champs fautifs
                     vivent souvent dans un AUTRE onglet, et l'utilisateur voyait
                     sa page revenir identique, comme si le bouton etait mort. --}}
                @if($errors->any())
                    <ul class="mb-0 mt-2" style="padding-left: 1.1rem;">
                        @foreach($errors->all() as $messageErreur)
                            <li>{{ $messageErreur }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs nav-tabs-modern" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                    <i class="fas fa-university"></i> Général
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pdf-tab" data-bs-toggle="tab" data-bs-target="#pdf" type="button" role="tab">
                    <i class="fas fa-file-pdf"></i> Configuration PDF
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bulletin-tab" data-bs-toggle="tab" data-bs-target="#bulletin" type="button" role="tab">
                    <i class="fas fa-clipboard-list"></i> Configuration Bulletin
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                    <i class="fas fa-file-alt"></i> Documents
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                    <i class="fas fa-bell"></i> Notifications et Rappels
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mailpulse-tab" data-bs-toggle="tab" data-bs-target="#mailpulse" type="button" role="tab">
                    <i class="fas fa-envelope"></i> MailPulse
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="lmd-tab" data-bs-toggle="tab" data-bs-target="#lmd" type="button" role="tab">
                    <i class="fas fa-graduation-cap"></i> Système LMD
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="compta-tab" data-bs-toggle="tab" data-bs-target="#compta" type="button" role="tab">
                    <i class="fas fa-calculator"></i> Comptabilité / SAARI
                </button>
            </li>
        </ul>

        {{-- novalidate : la validation vit cote serveur, qui liste ses erreurs
             dans l'alerte. La validation navigateur, elle, bloque la soumission
             sur un champ `min` invalide situe dans un onglet CACHE -- « not
             focusable », aucun message, bouton apparemment mort. C'est le piege
             classique des formulaires a onglets. --}}
        <form action="{{ route('esbtp.settings.update') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="settings_save_display" value="1">

            <div class="tab-content" id="settingsTabContent">
                <!-- Tab 1: Général -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">

            <!-- Section 1: Informations de l'École -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon school">
                        <i class="fas fa-university"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Informations de l'Établissement</h3>
                        <p class="section-description">Configuration des informations principales de l'école</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-building text-primary"></i>
                            Nom de l'établissement <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_name') is-invalid @enderror"
                               name="setting_school_name"
                               value="{{ old('setting_school_name', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI')) }}"
                               placeholder="Nom complet de votre établissement"
                               required>
                        @error('school_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-tag text-primary"></i>
                            Sigle
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_acronym') is-invalid @enderror"
                               name="setting_school_acronym"
                               value="{{ old('setting_school_acronym', \App\Helpers\SettingsHelper::get('school_acronym', '')) }}"
                               placeholder="Ex: ESBTP">
                        <small class="text-muted d-block mt-1">Forme courte du nom, utilisée là où la place manque.</small>
                        @error('school_acronym')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            Adresse
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_address') is-invalid @enderror"
                               name="setting_school_address"
                               value="{{ old('setting_school_address', \App\Helpers\SettingsHelper::get('school_address', '')) }}"
                               placeholder="Ex: BP 04 BP 1234 Abidjan 04">
                        @error('school_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-phone text-primary"></i>
                            Téléphone
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_phone') is-invalid @enderror"
                               name="setting_school_phone"
                               value="{{ old('setting_school_phone', \App\Helpers\SettingsHelper::get('school_phone', '')) }}"
                               placeholder="Ex: +225 00 00 00 00">
                        @error('school_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-globe-africa text-primary"></i>
                            Indicatif pays des numéros
                        </label>
                        <input type="text" class="form-control form-control-modern @error('telephone_indicatif_pays') is-invalid @enderror"
                               name="setting_telephone_indicatif_pays"
                               value="{{ old('setting_telephone_indicatif_pays', \App\Helpers\SettingsHelper::get('telephone_indicatif_pays', '')) }}"
                               placeholder="Ex: 225">
                        <small class="text-muted d-block mt-1">Apposé aux numéros saisis sans indicatif. 225 pour la Côte d'Ivoire, 229 pour le Bénin. Un numéro écrit en entier (+229…) garde le sien.</small>
                        @error('telephone_indicatif_pays')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-mobile-screen text-primary"></i>
                            Préfixes des numéros locaux
                        </label>
                        <input type="text" class="form-control form-control-modern @error('telephone_prefixes_mobiles') is-invalid @enderror"
                               name="setting_telephone_prefixes_mobiles"
                               value="{{ old('setting_telephone_prefixes_mobiles', \App\Helpers\SettingsHelper::get('telephone_prefixes_mobiles', '')) }}"
                               placeholder="Ex: 01,02,03,05,06,07,08,09">
                        <small class="text-muted d-block mt-1">Séparés par des virgules. Côte d'Ivoire : 01,02,03,05,06,07,08,09 — Bénin : 01 seul. Un numéro qui ne commence par aucun d'eux est refusé à la saisie.</small>
                        @error('telephone_prefixes_mobiles')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-envelope text-primary"></i>
                            Email
                        </label>
                        <input type="email" class="form-control form-control-modern @error('school_email') is-invalid @enderror"
                               name="setting_school_email"
                               value="{{ old('setting_school_email', \App\Helpers\SettingsHelper::get('school_email', '')) }}"
                               placeholder="Ex: contact@votre-ecole.com">
                        @error('school_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-mobile-screen text-primary"></i>
                            Mobile
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_mobile') is-invalid @enderror"
                               name="setting_school_mobile"
                               value="{{ old('setting_school_mobile', \App\Helpers\SettingsHelper::get('school_mobile', '')) }}"
                               placeholder="Ex: +225 07 00 00 00 00">
                        @error('school_mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-envelope-open-text text-primary"></i>
                            Code postal
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_postal_code') is-invalid @enderror"
                               name="setting_school_postal_code"
                               value="{{ old('setting_school_postal_code', \App\Helpers\SettingsHelper::get('school_postal_code', '')) }}"
                               placeholder="Ex: 01 BP 1234">
                        @error('school_postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-globe text-primary"></i>
                            Site web
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_website') is-invalid @enderror"
                               name="setting_school_website"
                               value="{{ old('setting_school_website', \App\Helpers\SettingsHelper::get('school_website', '')) }}"
                               placeholder="Ex: https://www.mon-ecole.ci">
                        @error('school_website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-city text-primary"></i>
                            Ville
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_city') is-invalid @enderror"
                               name="setting_school_city"
                               value="{{ old('setting_school_city', \App\Helpers\SettingsHelper::get('school_city', '')) }}"
                               placeholder="Ex: Yamoussoukro">
                        @error('school_city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-flag text-primary"></i>
                            Pays
                        </label>
                        <input type="text" class="form-control form-control-modern @error('school_country') is-invalid @enderror"
                               name="setting_school_country"
                               value="{{ old('setting_school_country', \App\Helpers\SettingsHelper::get('school_country', '')) }}"
                               placeholder="Ex: Côte d'Ivoire">
                        @error('school_country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-user-tie text-primary"></i>
                            Nom du directeur
                        </label>
                        <input type="text" class="form-control form-control-modern @error('director_name') is-invalid @enderror"
                               name="setting_director_name"
                               value="{{ old('setting_director_name', \App\Helpers\SettingsHelper::get('director_name', '')) }}"
                               placeholder="Ex: N'GUESSAN Marcel">
                        @error('director_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-briefcase text-primary"></i>
                            Titre du directeur
                        </label>
                        <input type="text" class="form-control form-control-modern @error('director_title') is-invalid @enderror"
                               name="setting_director_title"
                               value="{{ old('setting_director_title', \App\Helpers\SettingsHelper::get('director_title', 'Directeur Général')) }}"
                               placeholder="Ex: Directeur Général">
                        @error('director_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

                </div>
                <!-- End Tab 1: Général -->

                <!-- Tab 2: Configuration PDF -->
                <div class="tab-pane fade" id="pdf" role="tabpanel">

            <!-- Section 2: Logo & Identité Visuelle -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-image"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Logo & Identité Visuelle</h3>
                        <p class="section-description">Logo affiché en en-tête de tous vos documents (bulletins, certificats, attestations…)</p>
                    </div>
                </div>

                <!-- Zone upload logo premium -->
                <div class="pdf-logo-zone">
                    <div class="pdf-logo-current" id="logoCurrentWrap">
                        @if(\App\Helpers\SettingsHelper::get('school_logo', ''))
                            <img id="logoPreviewImg"
                                 src="{{ asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo', '')) }}"
                                 alt="Logo actuel">
                            <span class="pdf-logo-badge"><i class="fas fa-check-circle"></i> Logo actuel</span>
                        @else
                            <div class="pdf-logo-placeholder" id="logoPlaceholder">
                                <i class="fas fa-image"></i>
                                <span>Aucun logo</span>
                            </div>
                            <img id="logoPreviewImg" src="" alt="" style="display:none;">
                        @endif
                    </div>
                    <div class="pdf-logo-upload-side">
                        <label class="pdf-logo-drop" id="logoDrop" for="logoFileInput">
                            <i class="fas fa-cloud-upload-alt pdf-logo-drop-icon"></i>
                            <div class="pdf-logo-drop-title">Déposer votre logo ici</div>
                            <div class="pdf-logo-drop-sub">ou cliquez pour choisir un fichier</div>
                            <div class="pdf-logo-drop-hint">PNG, JPG, SVG — max 2 Mo · Recommandé : fond transparent</div>
                        </label>
                        <input type="file" id="logoFileInput" name="setting_school_logo"
                               accept="image/*" style="display:none" onchange="handleLogoUpload(this)">
                        <div class="pdf-logo-toggles">
                            <div class="form-group mb-0">
                                <label class="form-label-modern mb-1" style="font-size:.82rem;">
                                    <i class="fas fa-eye text-primary"></i>
                                    Afficher le logo sur les documents
                                </label>
                                <label class="form-switch-modern">
                                    <input type="checkbox" name="bulletin_show_logo" value="1"
                                           {{ \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1') == '1' ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-grid-2" style="margin-top:20px;">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-university text-primary"></i>
                            Nom affiché sur les bulletins
                        </label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_school_name_custom"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', '') }}"
                               placeholder="Laissez vide pour utiliser le nom de l'établissement">
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Si vide, utilise le nom défini dans l'onglet Général.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-font text-primary"></i>
                            Taille de police (px)
                        </label>
                        <input type="number" class="form-control form-control-modern"
                               name="bulletin_font_size"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_font_size', '13') }}"
                               min="9" max="16" step="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-calendar text-primary"></i>
                            Afficher date d'édition
                        </label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_edition_date" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_edition_date', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section: Couleurs des Documents -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Couleurs des Documents PDF</h3>
                        <p class="section-description">Chaque couleur cible un élément précis — le prévisionnement ci-contre se met à jour en temps réel</p>
                    </div>
                </div>

                <!-- Layout 2 colonnes : pickers gauche | preview droite -->
                <div class="pdf-color-layout">

                    <!-- Colonne pickers -->
                    <div class="pdf-color-pickers">

                        <!-- Picker 1 : Fond en-tête -->
                        <div class="pdf-picker-row" data-target="prev-header-bg">
                            <div class="pdf-picker-swatch-wrap">
                                <input type="color" id="colorHeaderBg" class="pdf-color-input"
                                       name="setting_pdf_header_bg_color"
                                       value="{{ \App\Helpers\SettingsHelper::get('pdf_header_bg_color', '#0453cb') }}"
                                       oninput="updatePreview()">
                                <span class="pdf-picker-swatch" id="swatchHeaderBg"
                                      style="background:{{ \App\Helpers\SettingsHelper::get('pdf_header_bg_color', '#0453cb') }}"></span>
                            </div>
                            <div class="pdf-picker-meta">
                                <div class="pdf-picker-label">Fond de l'en-tête établissement</div>
                                <div class="pdf-picker-desc">Bannière colorée en haut de chaque document (bulletins, certificats, attestations)</div>
                                <div class="pdf-contrast-badge" id="contrastHeaderBg"></div>
                            </div>
                        </div>

                        <!-- Picker 2 : Texte en-tête -->
                        <div class="pdf-picker-row" data-target="prev-header-text">
                            <div class="pdf-picker-swatch-wrap">
                                <input type="color" id="colorHeaderText" class="pdf-color-input"
                                       name="setting_pdf_header_text_color"
                                       value="{{ \App\Helpers\SettingsHelper::get('pdf_header_text_color', '#ffffff') }}"
                                       oninput="updatePreview()">
                                <span class="pdf-picker-swatch" id="swatchHeaderText"
                                      style="background:{{ \App\Helpers\SettingsHelper::get('pdf_header_text_color', '#ffffff') }}"></span>
                            </div>
                            <div class="pdf-picker-meta">
                                <div class="pdf-picker-label">Texte dans l'en-tête établissement</div>
                                <div class="pdf-picker-desc">Nom de l'école sur la bannière. Si le contraste est trop faible, les PDF basculent automatiquement sur une couleur lisible.</div>
                                <div class="pdf-contrast-badge" id="contrastHeaderText"></div>
                            </div>
                        </div>

                        <!-- Picker 3 : Couleur accent / titres -->
                        <div class="pdf-picker-row" data-target="prev-accent">
                            <div class="pdf-picker-swatch-wrap">
                                <input type="color" id="colorAccent" class="pdf-color-input"
                                       name="setting_pdf_primary_color"
                                       value="{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}"
                                       oninput="updatePreview()">
                                <span class="pdf-picker-swatch" id="swatchAccent"
                                      style="background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}"></span>
                            </div>
                            <div class="pdf-picker-meta">
                                <div class="pdf-picker-label">Couleur d'accent, titres et en-tetes de tableaux</div>
                                <div class="pdf-picker-desc">Titres, separateurs et fond des en-tetes de tableaux. Le texte de ces en-tetes reste blanc pour rester lisible.</div>
                                <div class="pdf-contrast-badge" id="contrastAccent"></div>
                            </div>
                        </div>

                        <!-- Picker 4 : Texte principal -->
                        <div class="pdf-picker-row" data-target="prev-body">
                            <div class="pdf-picker-swatch-wrap">
                                <input type="color" id="colorBody" class="pdf-color-input"
                                       name="setting_pdf_text_color"
                                       value="{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}"
                                       oninput="updatePreview()">
                                <span class="pdf-picker-swatch" id="swatchBody"
                                      style="background:{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}"></span>
                            </div>
                            <div class="pdf-picker-meta">
                                <div class="pdf-picker-label">Texte principal du corps du document</div>
                                <div class="pdf-picker-desc">Paragraphes, informations étudiant, notes de bas de page</div>
                                <div class="pdf-contrast-badge" id="contrastBody"></div>
                            </div>
                        </div>

                    </div><!-- /pdf-color-pickers -->

                    <!-- Colonne preview -->
                    <div class="pdf-color-preview" id="docPreview">
                        <!-- En-tête établissement -->
                        <div class="prev-header" id="prev-header-bg" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_header_bg_color', '#0453cb') }}">
                            <div class="prev-logo-placeholder"><i class="fas fa-university"></i></div>
                            <div id="prev-header-text" style="color:{{ \App\Helpers\SettingsHelper::get('pdf_header_text_color', '#ffffff') }}">
                                <div class="prev-school-name">NOM DE L'ÉTABLISSEMENT</div>
                                <div class="prev-school-meta">Adresse · Tél · Email</div>
                            </div>
                        </div>
                        <!-- Séparateur accent -->
                        <div class="prev-divider" id="prev-accent" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}"></div>
                        <!-- Titre document -->
                        <div class="prev-doc-title" style="color:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}; border-bottom:2px solid {{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}">
                            CERTIFICAT DE SCOLARITÉ
                        </div>
                        <!-- Corps -->
                        <div class="prev-body" id="prev-body" style="color:{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}">
                            <div class="prev-line prev-line-lg"></div>
                            <div class="prev-line"></div>
                            <div class="prev-hl-block" style="border-left:3px solid {{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}; background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}12">
                                <div class="prev-line prev-line-sm" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}55"></div>
                                <div class="prev-line prev-line-md" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}33"></div>
                            </div>
                            <div class="prev-line prev-line-md"></div>
                        </div>
                        <!-- Tableau -->
                        <div class="prev-table">
                            <div class="prev-table-head" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}; color:#ffffff">
                                <span>Année</span><span>Classe</span><span>Filière</span>
                            </div>
                            <div class="prev-table-row" style="color:{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}">
                                <span>2024-2025</span><span>BTS2</span><span>GC</span>
                            </div>
                        </div>
                        <!-- Légende -->
                        <div class="prev-legend">Aperçu non contractuel · Données fictives</div>
                    </div><!-- /pdf-color-preview -->

                </div><!-- /pdf-color-layout -->

                <!-- Avertissement contraste global -->
                <div class="pdf-contrast-warning" id="globalContrastWarning" style="display:none">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="globalContrastMsg"></span>
                </div>

            </div>

            {{-- ========================================================== --}}
            {{-- Section : Mise en page & Marges (Phase 9) --}}
            {{-- ========================================================== --}}
            <div class="settings-section" x-data="pdfAdvancedSection()">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Mise en page & Marges</h3>
                        <p class="section-description">Marges, taille du logo et formatage. Affectent tous vos exports PDF.</p>
                    </div>
                </div>

                <div class="settings-grid-2" style="margin-top: 20px;">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-image text-primary"></i>
                            Hauteur max du logo (px)
                        </label>
                        <div style="display: flex; gap: .75rem; align-items: center;">
                            <input type="range" min="20" max="120" step="5"
                                   x-model="settings.pdf_logo_size"
                                   style="flex: 1;">
                            <input type="number" min="20" max="120" step="5"
                                   class="form-control form-control-modern"
                                   name="setting_pdf_logo_size"
                                   x-model="settings.pdf_logo_size"
                                   style="width: 80px;">
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Recommandé : 50-70 px pour un en-tête équilibré.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-text-height text-primary"></i>
                            Taille de police du corps (px)
                        </label>
                        <input type="number" min="8" max="16" step="1"
                               class="form-control form-control-modern"
                               name="setting_pdf_font_size"
                               x-model="settings.pdf_font_size">
                        <small class="text-muted">Taille du texte des paragraphes (entre 8 et 16).</small>
                    </div>
                </div>

                <h4 style="margin-top: 24px; font-size: 0.95rem; color: #64748b; font-weight: 600;">
                    <i class="fas fa-arrows-alt text-primary"></i> Marges (mm)
                </h4>
                <div class="settings-grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 8px;">
                    <div class="form-group">
                        <label class="form-label-modern" style="font-size: .8rem;">Haut</label>
                        <input type="number" min="0" max="50" step="1"
                               class="form-control form-control-modern"
                               name="setting_pdf_margin_top"
                               x-model="settings.pdf_margin_top">
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern" style="font-size: .8rem;">Bas</label>
                        <input type="number" min="0" max="50" step="1"
                               class="form-control form-control-modern"
                               name="setting_pdf_margin_bottom"
                               x-model="settings.pdf_margin_bottom">
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern" style="font-size: .8rem;">Gauche</label>
                        <input type="number" min="0" max="50" step="1"
                               class="form-control form-control-modern"
                               name="setting_pdf_margin_left"
                               x-model="settings.pdf_margin_left">
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern" style="font-size: .8rem;">Droite</label>
                        <input type="number" min="0" max="50" step="1"
                               class="form-control form-control-modern"
                               name="setting_pdf_margin_right"
                               x-model="settings.pdf_margin_right">
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- Section : Footer & Mentions (Phase 9) --}}
            {{-- ========================================================== --}}
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-shoe-prints"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Pied de page & Mentions</h3>
                        <p class="section-description">Texte personnalisé, pagination et signature directeur affichés en bas de chaque page.</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label-modern">
                        <i class="fas fa-quote-right text-primary"></i>
                        Texte personnalisé du footer
                    </label>
                    <input type="text" class="form-control form-control-modern"
                           name="setting_pdf_footer_custom_text"
                           value="{{ \App\Helpers\SettingsHelper::get('pdf_footer_custom_text', '') }}"
                           placeholder="Laissez vide pour afficher le nom de l'établissement"
                           maxlength="200">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Si vide, le nom de l'école est affiché. Max 200 caractères.</small>
                </div>

                <div class="settings-grid-2" style="margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-list-ol text-primary"></i>
                            Afficher la pagination ("Page 1 / 5")
                        </label>
                        <label class="form-switch-modern">
                            <input type="hidden" name="setting_pdf_show_pagination" value="0">
                            <input type="checkbox" name="setting_pdf_show_pagination" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('pdf_show_pagination', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-qrcode text-primary"></i>
                            Imprimer un code QR sur la fiche d'inscription
                        </label>
                        <small class="d-block text-muted" style="margin:-.35rem 0 .5rem;font-size:.78rem;">
                            Scanne, il ouvre le dossier de l'étudiant. Utile quand la fiche revient signée.
                            Il n'affiche rien à qui n'est pas connecté.
                        </small>
                        <label class="form-switch-modern">
                            {{-- Une case décochée n'est pas envoyée : sans ce champ caché,
                                 le réglage ne pourrait jamais repasser à « non ». --}}
                            <input type="hidden" name="setting_documents_code_qr_actif" value="0">
                            <input type="checkbox" name="setting_documents_code_qr_actif" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('documents_code_qr_actif', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-user-tie text-primary"></i>
                            Afficher "Directeur : [Nom]"
                        </label>
                        <label class="form-switch-modern">
                            <input type="hidden" name="setting_pdf_show_director_signature" value="0">
                            <input type="checkbox" name="setting_pdf_show_director_signature" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('pdf_show_director_signature', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-user-edit text-primary"></i>
                            Afficher "Généré par [Nom]"
                        </label>
                        <label class="form-switch-modern">
                            <input type="hidden" name="setting_pdf_show_generator_name" value="0">
                            <input type="checkbox" name="setting_pdf_show_generator_name" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('pdf_show_generator_name', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Affiche le nom de l'utilisateur qui a cliqué sur "Télécharger PDF" dans l'en-tête de chaque document.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-signature text-primary"></i>
                            Hauteur des signatures (px) :
                            <span style="color: #0453cb; font-weight: 700;">{{ \App\Helpers\SettingsHelper::get('pdf_signature_height', '80') }} px</span>
                        </label>
                        <div style="display: flex; gap: .75rem; align-items: center;">
                            <input type="range" min="40" max="200" step="10"
                                   value="{{ \App\Helpers\SettingsHelper::get('pdf_signature_height', '80') }}"
                                   oninput="this.nextElementSibling.value = this.value; this.previousElementSibling.querySelector('span').textContent = this.value + ' px';"
                                   style="flex: 1;">
                            <input type="number" min="40" max="200" step="10"
                                   class="form-control form-control-modern"
                                   name="setting_pdf_signature_height"
                                   value="{{ \App\Helpers\SettingsHelper::get('pdf_signature_height', '80') }}"
                                   style="width: 80px;">
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Hauteur max des images signature directeur/secrétaire dans les bulletins/certificats. Défaut : 80 px.</small>
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- Section : Filigrane / Watermark (Phase 9) --}}
            {{-- ========================================================== --}}
            <div class="settings-section" x-data="watermarkSection()">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-stamp"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Filigrane (watermark)</h3>
                        <p class="section-description">Texte affiché en arrière-plan diagonal de chaque page (ex: "CONFIDENTIEL", "BROUILLON", "COPIE").</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label-modern">
                        <i class="fas fa-pen text-primary"></i>
                        Texte du filigrane
                    </label>
                    <input type="text" class="form-control form-control-modern"
                           name="setting_pdf_watermark"
                           x-model="watermark"
                           placeholder="Laissez vide pour désactiver le filigrane"
                           maxlength="50">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Visible uniquement si rempli. Max 50 caractères.</small>
                </div>

                <div class="settings-grid-2" style="margin-top: 16px;" x-show="watermark.trim() !== ''" x-cloak>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-adjust text-primary"></i>
                            Opacité (<span x-text="(opacity * 100).toFixed(0) + ' %'"></span>)
                        </label>
                        <input type="range" min="0.02" max="0.30" step="0.01"
                               x-model.number="opacity"
                               style="width: 100%;">
                        <input type="hidden" name="setting_pdf_watermark_opacity" :value="opacity">
                        <small class="text-muted">Plus l'opacité est faible, plus le filigrane est discret.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-sync-alt text-primary"></i>
                            Rotation (<span x-text="rotation + '°'"></span>)
                        </label>
                        <input type="range" min="-90" max="90" step="5"
                               x-model.number="rotation"
                               style="width: 100%;">
                        <input type="hidden" name="setting_pdf_watermark_rotation" :value="rotation">
                        <small class="text-muted">-30° est l'inclinaison standard.</small>
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- Section : Aperçu PDF (Phase 9) --}}
            {{-- ========================================================== --}}
            <div class="settings-section" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe;">
                <div class="section-header">
                    <div class="section-icon pdf" style="background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="section-title" style="color: #033a8e;">Prévisualiser un PDF avec ces paramètres</h3>
                        <p class="section-description" style="color: #1e40af;">
                            Génère un document de démonstration avec vos paramètres en cours d'édition (sans les sauvegarder).
                            Le PDF s'ouvre dans une nouvelle tab du navigateur.
                        </p>
                    </div>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    @can('settings.pdf.manage')
                    <button type="submit"
                            formaction="{{ route('esbtp.settings.pdf-preview') }}"
                            formmethod="POST"
                            formtarget="_blank"
                            class="btn-acasi primary"
                            style="padding: .7rem 1.4rem; font-size: .9rem;">
                        <i class="fas fa-file-pdf"></i> Aperçu PDF (nouvelle tab)
                    </button>
                    @endcan
                    <small class="text-muted" style="flex: 1; min-width: 200px;">
                        <i class="fas fa-info-circle"></i>
                        Le bouton utilise vos modifications en cours. Vous devrez ensuite cliquer sur "Enregistrer" pour les rendre permanentes.
                    </small>
                </div>
            </div>

                </div>
                <!-- End Tab 2: Configuration PDF -->

                <!-- Tab 3: Configuration Bulletin -->
                <div class="tab-pane fade" id="bulletin" role="tabpanel">

            <div class="settings-section" id="bulletin-style">
                <div class="section-header">
                    <div class="section-icon bulletin"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <h3 class="section-title">Modèle de bulletin</h3>
                        <p class="section-description">Le gabarit Yakro ou Abidjan vient de ce réglage, jamais du nom du tenant. Le titre 1 BTS semestre 1 se règle à part.</p>
                    </div>
                </div>
                <div class="bc-grid bc-grid-2">
                    @php $currentBulletinStyle = \App\Helpers\SettingsHelper::get('bulletin_style', 'yakro'); @endphp
                    <label class="bc-card" style="cursor:pointer; align-items:flex-start;">
                        <input type="radio" name="setting_bulletin_style" value="yakro" {{ $currentBulletinStyle === 'yakro' ? 'checked' : '' }} style="margin-top:6px;">
                        <div class="bc-body">
                            <div class="bc-label">Modèle Yakro</div>
                            <div class="bc-desc">Mise en page ESBTP Yamoussoukro. Les couleurs restent celles de l'onglet Documents.</div>
                        </div>
                    </label>
                    <label class="bc-card" style="cursor:pointer; align-items:flex-start;">
                        <input type="radio" name="setting_bulletin_style" value="abidjan" {{ $currentBulletinStyle === 'abidjan' ? 'checked' : '' }} style="margin-top:6px;">
                        <div class="bc-body">
                            <div class="bc-label">Modèle Abidjan / Plateau</div>
                            <div class="bc-desc">Conseil au-dessus de la signature. Les couleurs restent celles de l'onglet Documents. Le titre 1 BTS semestre 1 se règle séparément.</div>
                        </div>
                    </label>
                </div>
                <div class="form-group" style="margin-top:1.25rem;">
                    <label class="form-label-modern">
                        <i class="fas fa-gavel text-primary"></i>
                        Titre du conseil 1 BTS semestre 1
                    </label>
                    <input type="text" class="form-control form-control-modern"
                           name="setting_bulletin_bts1_s1_council_title"
                           value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts1_s1_council_title', 'Décision du conseil de classe') }}"
                           maxlength="191">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Indépendant du gabarit. Plateau : Appréciation du Conseil de Classe. Yakro : Décision du conseil de classe.</small>
                </div>
            </div>

            <!-- Section 1: En-tete du Bulletin -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon bulletin"><i class="fas fa-heading"></i></div>
                    <div>
                        <h3 class="section-title">En-tete du Bulletin</h3>
                        <p class="section-description">Elements affiches dans l'en-tete du document</p>
                    </div>
                </div>

                <div class="bc-grid bc-grid-3">
                    @php $headerToggles = [
                        ['name' => 'bulletin_show_header', 'label' => 'Afficher en-tete', 'icon' => 'fa-file-alt', 'color' => '', 'default' => '1'],
                        ['name' => 'bulletin_show_republic_info', 'label' => 'Info Republique', 'icon' => 'fa-flag', 'color' => '', 'default' => '1'],
                        ['name' => 'bulletin_show_ministry_info', 'label' => 'Info Ministere', 'icon' => 'fa-landmark', 'color' => '', 'default' => '1'],
                        ['name' => 'bulletin_show_school_info', 'label' => 'Info Ecole', 'icon' => 'fa-school', 'color' => '', 'default' => '1'],
                        ['name' => 'bulletin_show_cycle_info', 'label' => 'Info Cycle', 'icon' => 'fa-graduation-cap', 'color' => '', 'default' => '1'],
                    ]; @endphp
                    @foreach($headerToggles as $t)
                    <div class="bc-card">
                        <div class="bc-icon {{ $t['color'] }}"><i class="fas {{ $t['icon'] }}"></i></div>
                        <div class="bc-body"><div class="bc-label">{{ $t['label'] }}</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="{{ $t['name'] }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get($t['name'], $t['default']) == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="bc-grid bc-grid-2" style="margin-top: 16px;">
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-flag"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Texte Republique</div>
                            <input type="text" class="form-control form-control-modern" name="bulletin_republic_text"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire') }}"
                                   placeholder="Republique de Cote d'Ivoire">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-hands-holding"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Devise Union</div>
                            <input type="text" class="form-control form-control-modern" name="bulletin_union_text"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail') }}"
                                   placeholder="Union-Discipline-Travail">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-landmark"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Texte Ministere</div>
                            <input type="text" class="form-control form-control-modern" name="bulletin_ministry_text"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur') }}"
                                   placeholder="Ministere de l'Enseignement Superieur">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Texte Cycle</div>
                            <input type="text" class="form-control form-control-modern" name="bulletin_cycle_text"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur') }}"
                                   placeholder="Brevet de Technicien Superieur">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Affichage du Contenu -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon display"><i class="fas fa-eye"></i></div>
                    <div>
                        <h3 class="section-title">Affichage du Contenu</h3>
                        <p class="section-description">Sections visibles sur le bulletin</p>
                    </div>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="toggleSectionCheckboxes('content-section', true)">
                            <i class="fas fa-check-double"></i> Tout cocher
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleSectionCheckboxes('content-section', false)">
                            <i class="fas fa-times"></i> Tout decocher
                        </button>
                    </div>
                </div>

                <div class="bc-grid bc-grid-3" id="content-section">
                    @php $contentToggles = [
                        ['name' => 'bulletin_show_student_info', 'label' => 'Info Etudiant', 'icon' => 'fa-user', 'color' => 'blue'],
                        ['name' => 'bulletin_show_matricule', 'label' => 'Matricule', 'icon' => 'fa-id-badge', 'color' => 'slate'],
                        ['name' => 'bulletin_show_birth_date', 'label' => 'Date Naissance', 'icon' => 'fa-calendar-day', 'color' => 'cyan'],
                        ['name' => 'bulletin_show_redoublant', 'label' => 'Redoublant', 'icon' => 'fa-redo-alt', 'color' => 'amber'],
                        ['name' => 'bulletin_show_subjects_table', 'label' => 'Tableau Matieres', 'icon' => 'fa-table', 'color' => 'blue'],
                        ['name' => 'bulletin_show_teachers', 'label' => 'Professeurs', 'icon' => 'fa-chalkboard-teacher', 'color' => 'green'],
                        ['name' => 'bulletin_show_absences', 'label' => 'Absences', 'icon' => 'fa-user-clock', 'color' => 'red'],
                        ['name' => 'bulletin_show_statistics', 'label' => 'Statistiques', 'icon' => 'fa-chart-bar', 'color' => 'purple'],
                        ['name' => 'bulletin_show_signature', 'label' => 'Signature', 'icon' => 'fa-signature', 'color' => 'slate'],
                        ['name' => 'bulletin_show_attendance_note', 'label' => 'Note d\'assiduite', 'icon' => 'fa-clipboard-check', 'color' => 'green'],
                        ['name' => 'bulletin_show_council_decision', 'label' => 'Decision du conseil', 'icon' => 'fa-gavel', 'color' => 'amber'],
                    ]; @endphp
                    @foreach($contentToggles as $t)
                    <div class="bc-card">
                        <div class="bc-icon {{ $t['color'] }}"><i class="fas {{ $t['icon'] }}"></i></div>
                        <div class="bc-body"><div class="bc-label">{{ $t['label'] }}</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="{{ $t['name'] }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get($t['name'], '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>

                @php
                    $_attendanceRule = app(\App\Services\BulletinService::class)->getAttendanceNoteRule()->toArray();
                @endphp
                <div class="att-editor" x-data="attendanceBaremeEditor()" x-init="init(@js($_attendanceRule))" style="margin-top:16px;">
                    <input type="hidden" name="setting_attendance_note_rules" :value="serialize()">

                    <div class="att-zero">
                        <div class="att-zero-label"><i class="fas fa-user-check"></i> Aucune absence (0h)</div>
                        <input type="number" class="form-control form-control-modern att-zero-input"
                               x-model.number="zeroBonus" step="0.01" min="-20" max="20">
                        <small class="text-muted">Note appliquée uniquement s'il n'y a AUCUNE heure d'absence (justifiée + non justifiée). Mettez <strong>0</strong> pour ni bonus ni malus.</small>
                    </div>

                    {{-- Deux barèmes : non justifiées puis justifiées --}}
                    <template x-for="scale in scaleDefs" :key="scale.key">
                        <div class="att-scale">
                            <div class="att-scale-head">
                                <div class="att-scale-title"><i class="fas" :class="scale.icon"></i> <span x-text="scale.title"></span></div>
                                <div class="att-scale-sub" x-text="scale.sub"></div>
                            </div>
                            <table class="att-table">
                                <thead>
                                    <tr><th>De (h)</th><th>À (h, exclu)</th><th>Note (/20)</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in rows(scale.key)" :key="scale.key + '-' + idx">
                                        <tr>
                                            <td><span class="att-from" x-text="fromHour(scale.key, idx)"></span></td>
                                            <td>
                                                <template x-if="idx === rows(scale.key).length - 1">
                                                    <span class="att-inf">∞ (et plus)</span>
                                                </template>
                                                <template x-if="idx !== rows(scale.key).length - 1">
                                                    <input type="number" class="form-control form-control-modern att-num"
                                                           x-model.number="row.max" :min="fromHour(scale.key, idx)" step="0.5" placeholder="ex. 10">
                                                </template>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-modern att-num"
                                                       x-model.number="row.note" step="0.01" min="-20" max="20" placeholder="0.00">
                                            </td>
                                            <td>
                                                <button type="button" class="att-del" x-show="rows(scale.key).length > 1"
                                                        @click="removeRow(scale.key, idx)" title="Supprimer la tranche">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <button type="button" class="att-add" @click="addRow(scale.key)">
                                <i class="fas fa-plus"></i> Ajouter une tranche
                            </button>
                        </div>
                    </template>

                    <div class="att-sim">
                        <div class="att-sim-title"><i class="fas fa-calculator"></i> Simulateur</div>
                        <div class="att-sim-row">
                            <label>Heures justifiées <input type="number" class="form-control form-control-modern att-num" x-model.number="simJust" min="0" step="0.5"></label>
                            <label>Heures non justifiées <input type="number" class="form-control form-control-modern att-num" x-model.number="simNonJust" min="0" step="0.5"></label>
                            <div class="att-sim-out">→ Note d'assiduité : <strong x-text="formatNote(simulate())"></strong></div>
                        </div>
                    </div>

                    <div class="att-warn" x-show="errorsList().length" x-cloak>
                        <i class="fas fa-triangle-exclamation"></i>
                        <ul><template x-for="e in errorsList()" :key="e"><li x-text="e"></li></template></ul>
                    </div>

                    <div class="bc-desc" style="margin-top:10px;">
                        Le toggle <strong>Note d'assiduité</strong> reste le commutateur unique : actif, ce barème s'applique au calcul et à l'affichage ; inactif, la note vaut 0 partout et reste masquée.
                        Les tranches sont contiguës depuis 0h ; la dernière va jusqu'à l'infini. Les contributions « non justifiées » et « justifiées » s'additionnent.
                    </div>
                </div>

                {{-- Une matiere faite seulement d'absences : lu par NoteCalculationService::moyenneSansNoteComptable(). --}}
                <div class="bc-grid" style="margin-top:16px;">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-user-slash"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Une matière notée seulement d'absences compte 0</div>
                            <div class="bc-desc">
                                Activé : la matière vaut 0/20 et pèse dans la moyenne générale.
                                Désactivé : elle n'a pas de moyenne (« — ») et sort du calcul, comme une matière jamais notée.
                                Une absence au milieu d'autres notes ne compte jamais.
                                Changer ce réglage vaut pour les notes saisies ensuite : les moyennes déjà enregistrées à 0
                                ne partent qu'au recalcul des moyennes de la classe (à demander au support KLASSCI), et les bulletins déjà générés qu'à leur régénération.
                            </div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="{{ \App\Services\NoteCalculationService::REGLAGE_ABSENCES_SEULES_COMPTENT_ZERO }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get(\App\Services\NoteCalculationService::REGLAGE_ABSENCES_SEULES_COMPTENT_ZERO, '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Statistiques de classe -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon stats"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <h3 class="section-title">Statistiques de Classe</h3>
                        <p class="section-description">Moyennes affichees en bas du bulletin</p>
                    </div>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="toggleSectionCheckboxes('stats-section', true)">
                            <i class="fas fa-check-double"></i> Tout cocher
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleSectionCheckboxes('stats-section', false)">
                            <i class="fas fa-times"></i> Tout decocher
                        </button>
                    </div>
                </div>

                <div class="bc-grid bc-grid-3" id="stats-section">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-arrow-up"></i></div>
                        <div class="bc-body"><div class="bc-label">Plus forte moyenne</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_highest_average" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-arrow-down"></i></div>
                        <div class="bc-body"><div class="bc-label">Plus faible moyenne</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_lowest_average" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-equals"></i></div>
                        <div class="bc-body"><div class="bc-label">Moyenne de classe</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_class_average" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Tronc Commun / Specialisation -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon tronc"><i class="fas fa-code-branch"></i></div>
                    <div>
                        <h3 class="section-title">Tronc Commun / Specialisation</h3>
                        <p class="section-description">Systeme de tronc commun avec specialisation en cours d'annee</p>
                    </div>
                </div>

                <div class="bc-grid bc-grid-2">
                    @php $tcToggles = [
                        ['key' => 'tronc_commun_enabled', 'label' => 'Activer le tronc commun', 'desc' => 'Permet aux filieres marquees "tronc commun" de proposer une specialisation en cours d\'annee', 'icon' => 'fa-toggle-on', 'color' => '', 'default' => '0'],
                        ['key' => 'tronc_commun_mga_include_s1', 'label' => 'Reporter les notes S1 dans la MGA', 'desc' => 'Inclure les notes du tronc commun (S1) dans le calcul de la Moyenne Generale Annuelle', 'icon' => 'fa-clipboard-check', 'color' => '', 'default' => '1'],
                        ['key' => 'tronc_commun_report_paiements', 'label' => 'Reporter les paiements', 'desc' => 'Reporter automatiquement les paiements du tronc commun sur la specialisation', 'icon' => 'fa-money-bill-transfer', 'color' => '', 'default' => '1'],
                        ['key' => 'tronc_commun_report_notes', 'label' => 'Reporter les notes', 'desc' => 'Conserver les notes du S1 (tronc commun) accessibles depuis la specialisation', 'icon' => 'fa-file-lines', 'color' => '', 'default' => '1'],
                        ['key' => 'tronc_commun_bulletin_show_origin', 'label' => 'Afficher la classe d\'origine', 'desc' => 'Mentionner la classe de tronc commun (S1) sur le bulletin de la specialisation (S2)', 'icon' => 'fa-id-card', 'color' => '', 'default' => '1'],
                        ['key' => 'tronc_commun_matieres_communes', 'label' => 'Matieres communes automatiques', 'desc' => 'Detecter les matieres partagees entre TC et specialisation, reporter les notes automatiquement', 'icon' => 'fa-link', 'color' => '', 'default' => '1'],
                        ['key' => 'tronc_commun_planning_semestre_strict', 'label' => 'Planning strict par semestre', 'desc' => 'Restreindre le planning general : matieres TC en S1 uniquement, matieres specialisation en S2 uniquement', 'icon' => 'fa-calendar-check', 'color' => '', 'default' => '0'],
                    ]; @endphp
                    @foreach($tcToggles as $t)
                    <div class="bc-card">
                        <div class="bc-icon {{ $t['color'] }}"><i class="fas {{ $t['icon'] }}"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">{{ $t['label'] }}</div>
                            <div class="bc-desc">{{ $t['desc'] }}</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="{{ $t['key'] }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get($t['key'], $t['default']) == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 5: Ponderation des semestres -->
            <div class="settings-section" id="bts-bulletin-policy">
                <div class="section-header">
                    <div class="section-icon ponderation"><i class="fas fa-balance-scale"></i></div>
                    <div>
                        <h3 class="section-title">Ponderation des semestres</h3>
                        <p class="section-description">Coefficients pour le calcul de la Moyenne Generale Annuelle (M.G.A)</p>
                    </div>
                </div>

                <div class="bc-grid bc-grid-2">
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-scale-balanced"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Coefficient Semestre 1</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="setting_bulletin_semester1_weight"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', '1') }}"
                                   min="0" step="0.1">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-scale-balanced"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Coefficient Semestre 2</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="setting_bulletin_semester2_weight"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', '1') }}"
                                   min="0" step="0.1">
                        </div>
                    </div>
                </div>
                <div class="bc-hint">
                    <i class="fas fa-info-circle"></i>
                    Fallback global. Les coefficients BTS 1 et BTS 2 ci-dessous priment : 1re année (S1 + 2 x S2) / 3, 2e année (S1 + S2) / 2.
                </div>

                <div class="mga-rows" style="margin-top: 20px;">
                    @foreach([1, 2] as $btsYear)
                    <div class="mga-row">
                        <label>Niveau
                            <input type="text" class="form-control form-control-modern" value="BTS {{ $btsYear }}" readonly>
                        </label>
                        <label>Coefficient S1
                            <input type="number" class="form-control form-control-modern"
                                   name="setting_bulletin_bts{{ $btsYear }}_semester1_weight"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts'.$btsYear.'_semester1_weight', '1') }}"
                                   min="0" step="0.1">
                        </label>
                        <label>Coefficient S2
                            <input type="number" class="form-control form-control-modern"
                                   name="setting_bulletin_bts{{ $btsYear }}_semester2_weight"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts'.$btsYear.'_semester2_weight', $btsYear === 1 ? '2' : '1') }}"
                                   min="0" step="0.1">
                        </label>
                        <div class="mga-preview">
                            @if($btsYear === 1)
                                Formule 1re année : (S1 + 2 x S2) / 3
                            @else
                                Formule 2e année : (S1 + S2) / 2
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon ponderation"><i class="fas fa-gavel"></i></div>
                    <div>
                        <h3 class="section-title">Décision du conseil de classe BTS</h3>
                        <p class="section-description">Règles propres à cet établissement. Le mode manuel laisse la zone vide pour la saisie du conseil.</p>
                    </div>
                </div>

                <div class="council-editor">
                    <div class="council-card">
                        <div class="bc-label">BTS 1, semestre 2</div>
                        <div class="council-grid">
                            <label>Mode
                                <select class="form-control form-control-modern" name="setting_bulletin_bts1_council_mode">
                                    @foreach(['manual' => 'Manuel', 'threshold' => 'Selon un seuil'] as $value => $label)
                                    <option value="{{ $value }}" {{ \App\Helpers\SettingsHelper::get('bulletin_bts1_council_mode', 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Moyenne de décision
                                @php $_sourceDecisionBts1 = \App\Helpers\SettingsHelper::get('bulletin_bts1_council_average_source', \App\Services\BtsBulletinPolicy::defaultFor('bulletin_bts1_council_average_source')); @endphp
                                <select class="form-control form-control-modern" name="setting_bulletin_bts1_council_average_source">
                                    <option value="annual" {{ $_sourceDecisionBts1 === 'annual' ? 'selected' : '' }}>Annuelle avec assiduité</option>
                                    <option value="semestre2" {{ $_sourceDecisionBts1 === 'semestre2' ? 'selected' : '' }}>Semestre 2 avec assiduité</option>
                                </select>
                            </label>
                            <label>Seuil
                                <input type="number" class="form-control form-control-modern" name="setting_bulletin_bts1_council_threshold" value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts1_council_threshold', '10') }}" min="0" max="20" step="0.01">
                            </label>
                            <label>Sous le seuil
                                <input type="text" class="form-control form-control-modern" name="setting_bulletin_bts1_council_below_text" value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts1_council_below_text', 'Redouble la classe') }}" placeholder="Sous le seuil">
                            </label>
                            <label>Au seuil ou au-dessus
                                <input type="text" class="form-control form-control-modern" name="setting_bulletin_bts1_council_at_or_above_text" value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts1_council_at_or_above_text', 'Admis(e) en 2e Année BTS') }}" placeholder="Au seuil ou au-dessus">
                            </label>
                        </div>
                    </div>
                    <div class="council-card">
                        <div class="bc-label">BTS 2, semestre 2</div>
                        <div class="council-grid">
                            <label>Mode
                                <select class="form-control form-control-modern" name="setting_bulletin_bts2_council_mode">
                                    @foreach(['manual' => 'Manuel', 'fixed' => 'Texte fixe'] as $value => $label)
                                    <option value="{{ $value }}" {{ \App\Helpers\SettingsHelper::get('bulletin_bts2_council_mode', 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Texte fixe
                                <input type="text" class="form-control form-control-modern" name="setting_bulletin_bts2_council_fixed_text" value="{{ \App\Helpers\SettingsHelper::get('bulletin_bts2_council_fixed_text', "Redouble en cas d'échec à l'examen du BTS") }}" placeholder="Texte de décision">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Note de Conduite -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon conduite"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <h3 class="section-title">Note de Conduite</h3>
                        <p class="section-description">Note basee sur les absences de l'etudiant</p>
                    </div>
                </div>

                <div class="bc-grid bc-grid-2">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-toggle-on"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Activer la note de conduite</div>
                            <div class="bc-desc">Calculer et afficher une note de conduite sur le bulletin</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_conduite_enabled" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_conduite_enabled', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-list-ol"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Absences par matiere sur bulletin</div>
                            <div class="bc-desc">Detailler les absences matiere par matiere</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_absences_par_matiere" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_absences_par_matiere', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bc-grid bc-grid-2" style="margin-top: 12px;">
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-star"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Note par defaut (/20)</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="setting_conduite_note_defaut"
                                   value="{{ \App\Helpers\SettingsHelper::get('conduite_note_defaut', '16') }}"
                                   min="0" max="20" step="0.5">
                            <div class="bc-desc">Note de depart avant deduction des absences</div>
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-clock"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Heures d'absence par point retire</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="setting_conduite_heures_par_point"
                                   value="{{ \App\Helpers\SettingsHelper::get('conduite_heures_par_point', '4') }}"
                                   min="1" max="20" step="1">
                            <div class="bc-desc">Chaque X heures d'absences = -1 point</div>
                        </div>
                    </div>
                </div>

                <div class="bc-info-box">
                    <strong><i class="fas fa-info-circle"></i> Bareme des mentions de conduite :</strong>
                    <ul>
                        <li><strong>0/20</strong> -- Blame</li>
                        <li><strong>05/20 a 10/20</strong> -- Avertissement</li>
                    </ul>
                </div>

                @include('esbtp.settings.partials.appreciation-scale-editor', [
                    'system' => 'bts',
                    'scale' => old('appreciation_scale_bts', $appreciationScales['bts'] ?? []),
                    'title' => 'Barème des appréciations BTS',
                    'description' => 'Libellés affichés sur les notes, résultats, bulletins et aperçus PDF BTS.',
                ])
            </div>

            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h3 class="section-title">Scolarite</h3>
                        <p class="section-description">Roles ISLG-USAT-Rostan et workflow d impression. Laissez desactive pour Yakro.</p>
                    </div>
                </div>
                <div class="bc-grid bc-grid-1">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-users"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Rôles responsable / service scolarité</div>
                            <div class="bc-desc">Affiche les onglets personnel et les dashboards dedies. Yakro reste sur secretaire tant que cette option est desactivee.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="scolarite.split_roles" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('scolarite.split_roles', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-university"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">LMD pour le service scolarité</div>
                            <div class="bc-desc">Le service scolarité voit notes, résultats, bulletins LMD, domaines, parcours, UE et ECUE. Sans planning ni volumes enseignants.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="scolarite.clerk_lmd_access" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('scolarite.clerk_lmd_access', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-chalkboard"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Pédagogie pour le service scolarité</div>
                            <div class="bc-desc">Créer / modifier / valider inscriptions et étudiants. Le planning, les volumes horaires et les enseignants restent au secrétariat général.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="scolarite.clerk_pedagogie" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('scolarite.clerk_pedagogie', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Enseignants pour la scolarité</div>
                            <div class="bc-desc">Le responsable et le service scolarité créent et modifient les professeurs (fiches enseignants). Pas la page personnel unifié.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="scolarite.manage_teachers" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('scolarite.manage_teachers', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-stamp"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Approbation avant impression</div>
                            <div class="bc-desc">Le responsable valide certificat, attestation et bulletin. Solde impayé d'abord, puis accord. Sans les deux, aucun PDF.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="documents.print_requires_approval" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('documents.print_requires_approval', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-user-check"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Confirmer nouveau / ancien de l'établissement</div>
                            <div class="bc-desc">À l'inscription, l'agent doit dire si l'étudiant était déjà scolarisé ici. Sert aux frais réservés aux nouveaux (tenue 2e année) quand KLASSCI n'a pas l'historique.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="inscriptions.confirmer_statut_etablissement" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('inscriptions.confirmer_statut_etablissement', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-cash-register"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Pre-inscription par la caisse</div>
                            <div class="bc-desc">Yakro et Abidjan: la caisse saisit la pre-inscription. ISLG et USAT: laissez desactive, la caisse encaisse seulement.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="caisse.pre_inscription.enabled" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('caisse.pre_inscription.enabled', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-globe"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Réinscription en ligne depuis klassci.com</div>
                            {{-- Même remède que la carte des candidatures ci-dessous : un lien,
                                 pas un chemin de menu. Celui-ci nommait « Scolarité › Demandes de
                                 réinscription » — une section qui n'existe pas, et un intitulé qui
                                 n'est pas celui du lien (« Demandes en ligne », sous Étudiants). --}}
                            <div class="bc-desc">Ouvre le portail public. Les demandes arrivent dans @can('reinscriptions.demandes.view')<a href="{{ route('esbtp.reinscription-demandes.index') }}">la liste des demandes en ligne</a>@else la liste des demandes en ligne @endcan et ne deviennent des inscriptions qu'une fois converties par vos soins. Désactivé par défaut.</div>
                            <div class="row g-2" style="margin-top:.6rem;max-width:420px;">
                                <div class="col-6">
                                    <label class="bc-desc" for="rd-ouverture" style="display:block;margin-bottom:.2rem;">Ouverture</label>
                                    <input type="date" class="form-control form-control-sm" id="rd-ouverture"
                                           name="reinscriptions.en_ligne.ouverture"
                                           value="{{ \App\Helpers\SettingsHelper::get('reinscriptions.en_ligne.ouverture', '') }}">
                                </div>
                                <div class="col-6">
                                    <label class="bc-desc" for="rd-fermeture" style="display:block;margin-bottom:.2rem;">Fermeture</label>
                                    <input type="date" class="form-control form-control-sm" id="rd-fermeture"
                                           name="reinscriptions.en_ligne.fermeture"
                                           value="{{ \App\Helpers\SettingsHelper::get('reinscriptions.en_ligne.fermeture', '') }}">
                                </div>
                                @php
                                    $_anneeCible = (string) \App\Helpers\SettingsHelper::get('inscriptions.annee_cible', '');
                                    $_annees = \App\Models\ESBTPAnneeUniversitaire::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
                                @endphp
                                <div class="col-12" style="margin-top:.4rem;">
                                    <label class="bc-desc" for="rd-annee-cible" style="display:block;margin-bottom:.2rem;">Année visée par les inscriptions</label>
                                    <select class="form-control form-control-sm" id="rd-annee-cible" name="inscriptions.annee_cible">
                                        <option value="">Non précisée</option>
                                        @foreach($_annees as $_a)
                                            <option value="{{ $_a->id }}" {{ $_anneeCible === (string) $_a->id ? 'selected' : '' }}>
                                                {{ $_a->name }}{{ $_a->is_current ? ' — année courante' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {{-- Les deux canaux ne lisent pas ce champ pareil, et l'écran
                                         doit le dire : la réinscription retombe sur l'année
                                         courante quand il est vide, la candidature refuse. Écrire
                                         « par défaut » ici laissait ouvrir les candidatures sur un
                                         champ vide et faisait répondre au portail, à chaque
                                         bachelier, que l'école n'avait pas fini son paramétrage. --}}
                                    <div class="bc-desc" style="margin-top:.25rem;">
                                        <strong>Obligatoire pour ouvrir les candidatures</strong> des nouveaux étudiants ci-dessous. Pour les réinscriptions seules, laissez « Non précisée » : elles visent alors l'année courante. Renseignez-la si vous ouvrez la rentrée avant d'avoir clos l'année précédente : la saisie des notes reste sur l'année courante pendant que les inscriptions visent la suivante.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="reinscriptions.en_ligne.enabled" value="1"
                                       {{ app(\App\Services\TenantScolariteSettings::class)->reinscriptionEnLigneEnabled() ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    {{-- Le canal des NOUVEAUX candidats. Distinct de la réinscription
                         juste au-dessus : une école peut vouloir réinscrire les siens
                         sans ouvrir aux extérieurs, ou l'inverse. La fenêtre de dates,
                         elle, est commune — c'est la même rentrée. --}}
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Inscription en ligne des nouveaux étudiants</div>
                            {{-- Un lien plutôt qu'un chemin de menu à suivre : le chemin exact
                                 dépend du rôle qui lit, et il a déjà été décrit faux une fois.
                                 Le lien, lui, mène au même endroit pour tout le monde — et n'est
                                 montré qu'à qui a le droit d'y aller. --}}
                            <div class="bc-desc">Ouvre les candidatures des nouveaux bacheliers depuis klassci.com. Elles arrivent dans @can('inscriptions.candidatures.view')<a href="{{ route('esbtp.candidatures.index') }}">la corbeille des candidatures</a>@else la corbeille des candidatures @endcan et ne deviennent des inscriptions qu'une fois acceptées puis créées par vos soins. Désactivé par défaut. La période d'ouverture est celle de la réinscription ci-dessus ; <strong>l'année visée doit y être renseignée</strong>, sans quoi ce canal refuse toutes les candidatures.</div>
                            <div class="row g-2" style="margin-top:.6rem;max-width:420px;">
                                <div class="col-12">
                                    <label class="bc-desc" for="ci-physiques" style="display:block;margin-bottom:.2rem;">Début des inscriptions sur place</label>
                                    {{-- Les clés viennent des constantes, jamais de chaînes
                                         écrites ici : un renommage laisserait sinon le
                                         formulaire poster une clé disparue, et la bascule
                                         retomberait à zéro en silence. C'est la leçon de
                                         la PR #591, que le contrôleur applique déjà. --}}
                                    @php $_clePhysiques = \App\Services\Inscription\PortailCandidaturePublication::REGLAGE_PHYSIQUES; @endphp
                                    <input type="date" class="form-control form-control-sm" id="ci-physiques"
                                           name="{{ $_clePhysiques }}"
                                           value="{{ \App\Helpers\SettingsHelper::get($_clePhysiques, '') }}">
                                    <div class="bc-desc" style="margin-top:.25rem;">
                                        Premier jour où vous recevez les candidats pour finaliser leur dossier (pièces et paiement). Le portail l'annonce à la fin du formulaire : avant cette date il indique quand venir, à partir de cette date il invite à se présenter. Laissez vide pour n'annoncer aucune date.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                @php $_cleCandidatures = \App\Services\Inscription\PortailCandidaturePublication::REGLAGE_ACTIF; @endphp
                                <input type="checkbox" name="{{ $_cleCandidatures }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get($_cleCandidatures, '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Rôle agent d'inscription</div>
                            <div class="bc-desc">Active l onglet personnel et le dashboard dedie. Additif: n enleve aucun droit existant. A cocher sur ISLG et USAT uniquement.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="inscriptions.split_role" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('inscriptions.split_role', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    @include('esbtp.settings.partials.rendez-vous-reglages')
                    @include('esbtp.settings.partials.pieces-dossier-reglages')
                </div>
            </div>

            <!-- Section 6a: Interface mobile -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-mobile-screen-button"></i></div>
                    <div>
                        <h3 class="section-title">Interface mobile</h3>
                        <p class="section-description">Sur telephone, une barre d'onglets adaptee au profil de la personne connectee (caisse, comptabilite, enseignant, etudiant) remplace le menu lateral.</p>
                    </div>
                </div>
                <div class="bc-grid bc-grid-1">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-mobile-screen-button"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Barre d'onglets sur telephone</div>
                            <div class="bc-desc">Desactivee, l'interface classique est servie sur tous les ecrans. Sans effet sur ordinateur.</div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                @php $_cleShellMobile = \App\Services\Mobile\MobileProfileResolver::REGLAGE_ACTIF; @endphp
                                <input type="checkbox" name="{{ $_cleShellMobile }}" value="1"
                                       {{ \App\Helpers\SettingsHelper::get($_cleShellMobile, '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6b: Assiduite / Saisie manuelle d'heures -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-list-check"></i></div>
                    <div>
                        <h3 class="section-title">Assiduite -- Saisie manuelle d'heures</h3>
                        <p class="section-description">Sources et options pour la saisie manuelle des heures de presence/absence (page Marquer les presences)</p>
                    </div>
                </div>

                <div class="bc-grid bc-grid-1">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-globe"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Mode global (saisie sans matiere)</div>
                            <div class="bc-desc">
                                Active l'option "Mode global" dans /esbtp/attendances/create onglet Saisie manuelle.
                                Permet d'enregistrer des heures d'absence/presence pour un etudiant sans les rattacher
                                a une matiere specifique (cas : signalement disciplinaire, dispense, retour maladie).
                                La regle de priorite bulletin reste : <strong>par matiere &gt; global &gt; seances</strong>.
                            </div>
                        </div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="attendance_manual_hours_global_enabled" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('attendance_manual_hours_global_enabled', '0') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 7: Mentions et Seuils -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon mentions"><i class="fas fa-medal"></i></div>
                    <div>
                        <h3 class="section-title">Mentions et Seuils</h3>
                        <p class="section-description">Seuils de declenchement des mentions automatiques</p>
                    </div>
                </div>

                @include('esbtp.settings.partials.mention-rules-editor')
            </div>

                </div>
                <!-- End Tab 3: Configuration Bulletin -->

                <!-- Tab 4: Documents (Certificats & Attestations) -->
                <div class="tab-pane fade" id="documents" role="tabpanel">

            <!-- Section: Colonnes du Certificat de Scolarité -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de);">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Certificat de Scolarité</h3>
                        <p class="section-description">Colonnes affichées dans le tableau du certificat de scolarité</p>
                    </div>
                </div>

                <div class="settings-grid-3">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-chalkboard-teacher text-primary me-1"></i>
                            Classe suivie
                        </label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="certificat_show_classe" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('certificat_show_classe', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <small class="text-muted d-block mt-1">Afficher la colonne "Classe suivie"</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-layer-group text-primary me-1"></i>
                            Niveau d'étude
                        </label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="certificat_show_niveau" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('certificat_show_niveau', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <small class="text-muted d-block mt-1">Afficher la colonne "Niveau d'étude"</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-code-branch text-primary me-1"></i>
                            Filière
                        </label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="certificat_show_filiere" value="1"
                                   {{ \App\Helpers\SettingsHelper::get('certificat_show_filiere', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <small class="text-muted d-block mt-1">Afficher la colonne "Filière"</small>
                    </div>
                </div>
            </div>

                </div>
                <!-- End Tab 4: Documents -->

                <!-- Tab 5: Notifications et Rappels -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">

                    <!-- Section: Rappels Inscriptions -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon notifications">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Rappels Inscriptions en Attente</h3>
                                <p class="section-description">Configuration des rappels automatiques pour les inscriptions non validées</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-modern">
                                <i class="fas fa-toggle-on text-primary"></i>
                                Activer les rappels automatiques
                            </label>
                            <label class="form-switch-modern">
                                <input type="checkbox" name="reminder_inscription_enabled" value="1"
                                       {{ \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_enabled', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Active ou désactive l'envoi automatique de rappels pour les inscriptions en attente
                            </small>
                        </div>

                        <div class="settings-grid-3">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-hourglass-start text-primary"></i>
                                    Délai avant 1er rappel (jours)
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_inscription_first_delay"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_first_delay', '3') }}"
                                       min="1" max="30" step="1">
                                <small class="text-muted">
                                    Nombre de jours avant le premier rappel
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-redo text-primary"></i>
                                    Fréquence entre rappels (jours)
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_inscription_frequency"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_frequency', '2') }}"
                                       min="1" max="14" step="1">
                                <small class="text-muted">
                                    Nombre de jours entre chaque rappel
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-list-ol text-primary"></i>
                                    Nombre maximum de rappels
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_inscription_max_count"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_max_count', '5') }}"
                                       min="0" max="20" step="1">
                                <small class="text-muted">
                                    0 = illimité
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Rappels Paiements -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon notifications">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Rappels Paiements en Attente</h3>
                                <p class="section-description">Configuration des rappels automatiques pour les paiements non validés</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-modern">
                                <i class="fas fa-toggle-on text-primary"></i>
                                Activer les rappels automatiques
                            </label>
                            <label class="form-switch-modern">
                                <input type="checkbox" name="reminder_paiement_enabled" value="1"
                                       {{ \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_enabled', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Active ou désactive l'envoi automatique de rappels pour les paiements en attente
                            </small>
                        </div>

                        <div class="settings-grid-3">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-hourglass-start text-primary"></i>
                                    Délai avant 1er rappel (jours)
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_paiement_first_delay"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_first_delay', '2') }}"
                                       min="1" max="30" step="1">
                                <small class="text-muted">
                                    Nombre de jours avant le premier rappel
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-redo text-primary"></i>
                                    Fréquence entre rappels (jours)
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_paiement_frequency"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_frequency', '1') }}"
                                       min="1" max="14" step="1">
                                <small class="text-muted">
                                    Nombre de jours entre chaque rappel
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-list-ol text-primary"></i>
                                    Nombre maximum de rappels
                                </label>
                                <input type="number" class="form-control form-control-modern threshold-input"
                                       name="reminder_paiement_max_count"
                                       value="{{ \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_max_count', '7') }}"
                                       min="0" max="20" step="1">
                                <small class="text-muted">
                                    0 = illimité
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Tester les rappels -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon notifications">
                                <i class="fas fa-vial"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Test et Diagnostics</h3>
                                <p class="section-description">Testez le système de rappels manuellement</p>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Planification automatique:</strong> Les rappels sont envoyés automatiquement chaque jour à 8h00 (heure d'Abidjan).
                        </div>

                        <div class="form-group">
                            <label class="form-label-modern">
                                <i class="fas fa-terminal text-primary"></i>
                                Exécuter les rappels manuellement
                            </label>
                            <p class="text-muted mb-3">
                                Cliquez sur le bouton ci-dessous pour tester l'envoi des rappels immédiatement (mode test, aucune notification ne sera envoyée).
                            </p>
                            <button type="button" class="btn btn-outline-primary" onclick="testReminders()">
                                <i class="fas fa-play-circle me-2"></i>
                                Tester les rappels (mode simulation)
                            </button>
                            <div id="test-results" class="mt-3"></div>
                        </div>
                    </div>

                </div>
                <!-- End Tab 3: Notifications et Rappels -->

                <!-- Tab 6: MailPulse -->
                <div class="tab-pane fade mailpulse-panel" id="mailpulse" role="tabpanel">
                    @php
                        $mailpulseEnabled = \App\Helpers\SettingsHelper::get('mailpulse_enabled', '0');
                        $mailpulseWorkflowsEnabled = \App\Helpers\SettingsHelper::get('mailpulse_real_workflows_enabled', '0');
                        $mailpulseApiKeyConfigured = \App\Models\Setting::where('key', 'mailpulse_api_key')
                            ->where('is_active', true)
                            ->whereNotNull('value')
                            ->where('value', '<>', '')
                            ->exists() || trim((string) config('services.mailpulse.api_key', '')) !== '';
                    @endphp

                    <div class="mailpulse-brand-card">
                        <div class="mailpulse-logo-mark" aria-hidden="true">
                            <img src="{{ asset('images/mailpulse/mailpulse-mark-light.png') }}" alt="">
                        </div>
                        <div class="mailpulse-brand-copy">
                            <div class="mailpulse-wordmark">Mail<span>Pulse</span></div>
                            <div class="mailpulse-brand-subtitle">Email, WhatsApp et automatisations transactionnelles</div>
                        </div>
                        <a class="btn btn-sm btn-dark ms-sm-auto" href="{{ route('esbtp.parent-chatbot-onboarding.index') }}">
                            <i class="fas fa-users me-1" aria-hidden="true"></i>Activation des parents
                        </a>
                        <span class="mailpulse-status-badge {{ $mailpulseApiKeyConfigured ? 'configured' : '' }}">
                            <i class="fas {{ $mailpulseApiKeyConfigured ? 'fa-lock' : 'fa-key' }}"></i>
                            {{ $mailpulseApiKeyConfigured ? 'Clé API configurée' : 'Clé API à configurer' }}
                        </span>
                        <span class="mailpulse-status-badge {{ $mailpulseWorkflowsEnabled == '1' ? 'configured' : '' }}">
                            <i class="fas {{ $mailpulseWorkflowsEnabled == '1' ? 'fa-bolt' : 'fa-pause' }}"></i>
                            {{ $mailpulseWorkflowsEnabled == '1' ? 'Workflows parents actifs' : 'Workflows parents inactifs' }}
                        </span>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon mailpulse">
                                <i class="fas fa-plug"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Accès MailPulse</h3>
                                <p class="section-description">Configuration serveur des accès MailPulse pour les envois email et WhatsApp de cette instance</p>
                            </div>
                        </div>

                        <input type="hidden" name="setting_mailpulse_enabled" value="0">
                        <div class="mailpulse-field-card mb-3">
                            <label class="form-label-modern">
                                <i class="fas fa-toggle-on text-primary"></i>
                                Activer MailPulse pour cette instance
                            </label>
                            <label class="form-switch-modern">
                                <input type="checkbox" name="setting_mailpulse_enabled" value="1"
                                       {{ $mailpulseEnabled == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <small class="text-muted d-block mt-2">Les tests restent limités aux destinataires configurés ci-dessous.</small>
                        </div>

                        <input type="hidden" name="setting_mailpulse_real_workflows_enabled" value="0">
                        <div class="mailpulse-field-card mb-3">
                            <label class="form-label-modern">
                                <i class="fas fa-sitemap text-primary"></i>
                                Activer les workflows parents réels
                            </label>
                            <label class="form-switch-modern">
                                <input type="checkbox" name="setting_mailpulse_real_workflows_enabled" value="1"
                                       {{ $mailpulseWorkflowsEnabled == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <small class="text-muted d-block mt-2">Sans cette case, MailPulse envoie les tests mais pas les messages aux vrais parents (paiements, absences, notes, inscriptions).</small>
                        </div>

                        <div class="settings-grid">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-link text-primary"></i>
                                    URL de base MailPulse
                                </label>
                                <input type="url" class="form-control form-control-modern mailpulse-code-input"
                                       name="setting_mailpulse_base_url"
                                       value="{{ old('setting_mailpulse_base_url', \App\Helpers\SettingsHelper::get('mailpulse_base_url', 'https://mailpulse-two.vercel.app')) }}"
                                       placeholder="https://mailpulse-two.vercel.app">
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-key text-primary"></i>
                                    Clé API MailPulse
                                </label>
                                <input type="text" class="form-control form-control-modern mailpulse-code-input"
                                       name="setting_mailpulse_api_key"
                                       value=""
                                       autocomplete="off"
                                       autocapitalize="off"
                                       spellcheck="false"
                                       inputmode="text"
                                       pattern="^mp_(live|test)_[A-Za-z0-9_-]+$"
                                       maxlength="500"
                                       title="La clé doit commencer par mp_live_ ou mp_test_. Les caractères autorisés sont lettres, chiffres, tiret et underscore."
                                       placeholder="mp_live_... ou mp_test_...">
                                <small class="text-muted">Laissez vide pour conserver la clé actuelle.</small>
                            </div>
                        </div>

                        <div class="settings-grid">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-address-book text-primary"></i>
                                    Endpoint contacts
                                </label>
                                <input type="text" class="form-control form-control-modern mailpulse-code-input"
                                       name="setting_mailpulse_contacts_endpoint"
                                       value="{{ old('setting_mailpulse_contacts_endpoint', \App\Helpers\SettingsHelper::get('mailpulse_contacts_endpoint', '/api/v1/contacts')) }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-paper-plane text-primary"></i>
                                    Endpoint messages
                                </label>
                                <input type="text" class="form-control form-control-modern mailpulse-code-input"
                                       name="setting_mailpulse_messages_endpoint"
                                       value="{{ old('setting_mailpulse_messages_endpoint', \App\Helpers\SettingsHelper::get('mailpulse_messages_endpoint', '/api/v1/messages')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon mailpulse">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Paramètres d'envoi</h3>
                                <p class="section-description">Expéditeur, langue et timeout utilisés par les notifications KLASSCI</p>
                            </div>
                        </div>

                        <div class="settings-grid-3">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-at text-primary"></i>
                                    Email expéditeur
                                </label>
                                <input type="email" class="form-control form-control-modern"
                                       name="setting_mailpulse_sender_email"
                                       value="{{ old('setting_mailpulse_sender_email', \App\Helpers\SettingsHelper::get('mailpulse_sender_email', '')) }}"
                                       placeholder="notifications@etablissement.ci">
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-signature text-primary"></i>
                                    Nom expéditeur
                                </label>
                                <input type="text" class="form-control form-control-modern"
                                       name="setting_mailpulse_sender_name"
                                       value="{{ old('setting_mailpulse_sender_name', \App\Helpers\SettingsHelper::get('mailpulse_sender_name', 'KLASSCI')) }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-language text-primary"></i>
                                    Langue par défaut
                                </label>
                                <input type="text" class="form-control form-control-modern mailpulse-code-input"
                                       name="setting_mailpulse_default_language"
                                       value="{{ old('setting_mailpulse_default_language', \App\Helpers\SettingsHelper::get('mailpulse_default_language', 'fr')) }}"
                                       maxlength="8">
                            </div>
                        </div>

                        <div class="settings-grid-3">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-stopwatch text-primary"></i>
                                    Timeout API, secondes
                                </label>
                                <input type="number" class="form-control form-control-modern"
                                       name="setting_mailpulse_timeout"
                                       value="{{ old('setting_mailpulse_timeout', \App\Helpers\SettingsHelper::get('mailpulse_timeout', '20')) }}"
                                       min="5" max="120">
                            </div>

                        </div>

                        @php
                            $mailpulseDecodeRecipients = function ($value) {
                                $decoded = json_decode((string) $value, true);
                                return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
                            };
                            $mailpulseEmailRecipients = $mailpulseDecodeRecipients(old('setting_mailpulse_test_email_recipients', \App\Helpers\SettingsHelper::get('mailpulse_test_email_recipients', '')));
                            if ($mailpulseEmailRecipients === []) {
                                $legacyEmail = old('setting_mailpulse_test_email', \App\Helpers\SettingsHelper::get('mailpulse_test_email', ''));
                                $mailpulseEmailRecipients = $legacyEmail !== '' ? [['value' => $legacyEmail, 'enabled' => true]] : [['value' => '', 'enabled' => true]];
                            }
                            $mailpulsePhoneRecipients = $mailpulseDecodeRecipients(old('setting_mailpulse_test_phone_recipients', \App\Helpers\SettingsHelper::get('mailpulse_test_phone_recipients', '')));
                            if ($mailpulsePhoneRecipients === []) {
                                $legacyPhones = old('setting_mailpulse_test_phones', \App\Helpers\SettingsHelper::get('mailpulse_test_phones', \App\Helpers\SettingsHelper::get('mailpulse_test_phone', '')));
                                $items = preg_split('/[\r\n,;]+/', (string) $legacyPhones) ?: [];
                                $mailpulsePhoneRecipients = [];
                                foreach ($items as $item) {
                                    $item = trim($item);
                                    if ($item !== '') {
                                        $mailpulsePhoneRecipients[] = ['value' => $item, 'enabled' => true];
                                    }
                                }
                                if ($mailpulsePhoneRecipients === []) {
                                    $mailpulsePhoneRecipients = [['value' => '', 'enabled' => true]];
                                }
                            }
                        @endphp

                        <div class="settings-grid-2">
                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fas fa-envelope-open-text text-primary"></i>
                                    Emails de test
                                </label>
                                <input type="hidden"
                                       data-mailpulse-recipient-json="email"
                                       name="setting_mailpulse_test_email_recipients"
                                       value="{{ old('setting_mailpulse_test_email_recipients', \App\Helpers\SettingsHelper::get('mailpulse_test_email_recipients', '')) }}">
                                <input type="hidden"
                                       name="setting_mailpulse_test_email"
                                       value="{{ old('setting_mailpulse_test_email', \App\Helpers\SettingsHelper::get('mailpulse_test_email', '')) }}">
                                <div class="mailpulse-recipient-list" data-mailpulse-recipient-list="email">
                                    @foreach ($mailpulseEmailRecipients as $recipient)
                                        <div class="mailpulse-recipient-row" data-mailpulse-recipient-row>
                                            <label class="mailpulse-mini-toggle" title="Activer ce destinataire">
                                                <input type="checkbox" data-mailpulse-recipient-enabled {{ ($recipient['enabled'] ?? true) ? 'checked' : '' }}>
                                                <span></span>
                                            </label>
                                            <input type="email"
                                                   class="form-control form-control-modern"
                                                   data-mailpulse-recipient-value
                                                   value="{{ $recipient['value'] ?? '' }}"
                                                   placeholder="test@example.com">
                                            <button type="button" class="mailpulse-icon-button" data-mailpulse-recipient-remove title="Retirer">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="mailpulse-add-button" data-mailpulse-recipient-add="email">
                                    <i class="fas fa-plus"></i>
                                    Ajouter un email
                                </button>
                            </div>

                            <div class="form-group">
                                <label class="form-label-modern">
                                    <i class="fab fa-whatsapp text-primary"></i>
                                    T&eacute;l&eacute;phones WhatsApp de test
                                </label>
                                <input type="hidden"
                                       data-mailpulse-recipient-json="phone"
                                       name="setting_mailpulse_test_phone_recipients"
                                       value="{{ old('setting_mailpulse_test_phone_recipients', \App\Helpers\SettingsHelper::get('mailpulse_test_phone_recipients', '')) }}">
                                <input type="hidden"
                                       name="setting_mailpulse_test_phones"
                                       value="{{ old('setting_mailpulse_test_phones', \App\Helpers\SettingsHelper::get('mailpulse_test_phones', '')) }}">
                                <input type="hidden"
                                       name="setting_mailpulse_test_phone"
                                       value="{{ old('setting_mailpulse_test_phone', \App\Helpers\SettingsHelper::get('mailpulse_test_phone', '')) }}">
                                <div class="mailpulse-recipient-list" data-mailpulse-recipient-list="phone">
                                    @foreach ($mailpulsePhoneRecipients as $recipient)
                                        <div class="mailpulse-recipient-row" data-mailpulse-recipient-row>
                                            <label class="mailpulse-mini-toggle" title="Activer ce destinataire">
                                                <input type="checkbox" data-mailpulse-recipient-enabled {{ ($recipient['enabled'] ?? true) ? 'checked' : '' }}>
                                                <span></span>
                                            </label>
                                            <input type="tel"
                                                   class="form-control form-control-modern mailpulse-code-input"
                                                   data-mailpulse-recipient-value
                                                   value="{{ $recipient['value'] ?? '' }}"
                                                   placeholder="0544210112">
                                            <button type="button" class="mailpulse-icon-button" data-mailpulse-recipient-remove title="Retirer">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="mailpulse-add-button" data-mailpulse-recipient-add="phone">
                                    <i class="fas fa-plus"></i>
                                    Ajouter un num&eacute;ro
                                </button>
                            </div>
                        </div>

                        <div class="mailpulse-info-grid">
                            <div class="mailpulse-info-card">
                                <strong><i class="fas fa-address-card"></i>Contact parent test</strong>
                                KLASSCI crée ou met à jour le contact MailPulse avant chaque notification simulée.
                            </div>
                            <div class="mailpulse-info-card">
                                <strong><i class="fas fa-shield-alt"></i>Destinataires protégés</strong>
                                Les tests utilisent uniquement l'email et le téléphone de test configurés ici.
                            </div>
                            <div class="mailpulse-info-card">
                                <strong><i class="fab fa-whatsapp"></i>Limite WhatsApp</strong>
                                Meta peut exiger un template approuvé hors fenêtre 24h. Baileys peut aussi se déconnecter.
                            </div>
                        </div>
                    </div>
                    <div class="mailpulse-save-row">
                        <div>
                            <strong>Enregistrer les destinataires MailPulse</strong>
                            <span class="text-muted">Sauvegarde les emails, num&eacute;ros et options MailPulse sans recharger la page.</span>
                            <div class="mailpulse-save-status" data-mailpulse-save-status></div>
                        </div>
                        <button type="button" class="btn btn-acasi primary" data-mailpulse-save-submit>
                            <i class="fas fa-save me-2"></i>
                            Enregistrer MailPulse
                        </button>
                    </div>
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon mailpulse">
                                <i class="fas fa-vial"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Test direct MailPulse</h3>
                                <p class="section-description">Lancez le m&ecirc;me test que la commande CLI, sans quitter les param&egrave;tres.</p>
                            </div>
                        </div>

                        <div class="mailpulse-field-card">
                            <div class="mailpulse-test-grid">
                                <div class="form-group mb-0">
                                    <label class="form-label-modern">
                                        <i class="fas fa-calendar-check text-primary"></i>
                                        &Eacute;v&eacute;nement simul&eacute;
                                    </label>
                                    <select class="form-control form-control-modern" data-mailpulse-test-event>
                                        <option value="payment_received">Paiement re&ccedil;u</option>
                                        <option value="payment_submitted">Paiement en attente</option>
                                        <option value="payment_rejected">Paiement rejet&eacute;</option>
                                        <option value="absence_reported">Absence signal&eacute;e</option>
                                        <option value="grade_published">Note publi&eacute;e</option>
                                        <option value="fee_reminder">Rappel de frais</option>
                                        <option value="registration_confirmed">Inscription confirm&eacute;e</option>
                                        <option value="re_registration_confirmed">R&eacute;inscription confirm&eacute;e</option>
                                        <option value="bulletin_published">Bulletin disponible</option>
                                        <option value="low_grades_alert">Alerte notes faibles</option>
                                        <option value="low_attendance_alert">Alerte pr&eacute;sence faible</option>
                                    </select>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label-modern">
                                        <i class="fas fa-paper-plane text-primary"></i>
                                        Canal
                                    </label>
                                    <select class="form-control form-control-modern" data-mailpulse-test-channel>
                                        <option value="email">Email</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="both">Email et WhatsApp</option>
                                    </select>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label-modern">
                                        <i class="fas fa-shield-alt text-primary"></i>
                                        Mode
                                    </label>
                                    <label class="mailpulse-toggle mb-0">
                                        <input type="checkbox" data-mailpulse-test-dry-run>
                                        <span class="mailpulse-toggle-slider"></span>
                                        Simulation uniquement
                                    </label>
                                </div>

                                <button type="button" class="btn btn-acasi primary" data-mailpulse-test-submit>
                                    <i class="fas fa-play me-2"></i>
                                    Lancer le test
                                </button>
                            </div>

                            <small class="text-muted d-block mt-3">
                                Par d&eacute;faut, le test envoie r&eacute;ellement aux emails et num&eacute;ros de test actifs configur&eacute;s ci-dessus. Activez la simulation pour ne rien envoyer.
                            </small>
                            <div class="mailpulse-test-result d-none" data-mailpulse-test-result></div>
                        </div>
                    </div>
                </div>
                <!-- End Tab 6: MailPulse -->

                <!-- ══════════════════════════════════════════════ -->
                <!-- Tab 6: Système LMD — Premium Redesign -->
                <!-- ══════════════════════════════════════════════ -->
                <style>
                    /* ── LMD Settings Tab — Prefix: ls- ── */
                    .ls-section {
                        background: #fff;
                        border-radius: 14px;
                        border: 1px solid #e8ecf1;
                        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
                        padding: 1.5rem;
                        margin-bottom: 1.25rem;
                        border-left: 4px solid transparent;
                        transition: box-shadow .2s;
                    }
                    .ls-section:hover { box-shadow: 0 4px 16px rgba(4,83,203,.06); }
                    .ls-section--credits { border-left-color: #10b981; }
                    .ls-section--validation { border-left-color: #0453cb; }
                    .ls-section--evals { border-left-color: #0891b2; }
                    .ls-section--mentions { border-left-color: #3b7ddb; }
                    .ls-section--deliberation { border-left-color: #334155; }

                    .ls-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .4rem; }
                    .ls-icon {
                        width: 38px; height: 38px; border-radius: 10px;
                        display: flex; align-items: center; justify-content: center;
                        font-size: .95rem; color: #fff; flex-shrink: 0;
                    }
                    .ls-icon--credits { background: linear-gradient(135deg, #10b981, #059669); }
                    .ls-icon--validation { background: linear-gradient(135deg, #0453cb, #3b82f6); }
                    .ls-icon--evals { background: linear-gradient(135deg, #0891b2, #0e7490); }
                    .ls-icon--mentions { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
                    .ls-icon--deliberation { background: linear-gradient(135deg, #334155, #1e293b); }

                    .ls-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
                    .ls-desc { font-size: .82rem; color: #94a3b8; margin-bottom: 1.15rem; line-height: 1.5; }

                    .ls-field { margin-bottom: .15rem; }
                    .ls-label {
                        font-size: .72rem; font-weight: 700; color: #94a3b8;
                        text-transform: uppercase; letter-spacing: .06em; margin-bottom: .3rem;
                    }
                    .ls-input {
                        border: 1.5px solid #e2e8f0; border-radius: 9px;
                        padding: .5rem .75rem; font-size: .88rem; color: #1e293b;
                        background: #f8fafc; transition: all .2s; width: 100%;
                    }
                    .ls-input:focus {
                        outline: none; border-color: #0453cb; background: #fff;
                        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
                    }
                    .ls-hint { font-size: .72rem; color: #94a3b8; margin-top: .25rem; }

                    /* Toggle switches */
                    .ls-toggle {
                        display: flex; align-items: center; gap: .75rem;
                        padding: .85rem 1rem; border-radius: 10px;
                        background: #f8fafc; border: 1px solid #e8ecf1;
                        transition: all .2s; cursor: pointer;
                    }
                    .ls-toggle:hover { background: #f0f5ff; border-color: #c7d6f0; }
                    .ls-toggle-text { flex: 1; }
                    .ls-toggle-label { font-size: .88rem; font-weight: 600; color: #1e293b; }
                    .ls-toggle-hint { font-size: .72rem; color: #94a3b8; margin-top: .1rem; }

                    /* Mention cards */
                    .ls-mentions-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
                    .ls-mention-card {
                        border-radius: 12px; padding: 1rem; text-align: center;
                        border: 1.5px solid; transition: all .2s;
                    }
                    .ls-mention-card:hover { transform: translateY(-1px); }
                    .ls-mention-card--tb { background: #ecfdf5; border-color: #a7f3d0; }
                    .ls-mention-card--b  { background: #eff6ff; border-color: #bfdbfe; }
                    .ls-mention-card--ab { background: #fffbeb; border-color: #fde68a; }
                    .ls-mention-card--p  { background: #fef2f2; border-color: #fecaca; }
                    .ls-mention-badge {
                        display: inline-flex; align-items: center; justify-content: center;
                        width: 36px; height: 36px; border-radius: 50%;
                        font-size: .82rem; font-weight: 800; margin-bottom: .5rem;
                    }
                    .ls-mention-card--tb .ls-mention-badge { background: #059669; color: #fff; }
                    .ls-mention-card--b  .ls-mention-badge { background: #0453cb; color: #fff; }
                    .ls-mention-card--ab .ls-mention-badge { background: #d97706; color: #fff; }
                    .ls-mention-card--p  .ls-mention-badge { background: #dc2626; color: #fff; }
                    .ls-mention-name { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .5rem; }

                    /* Info banner */
                    .ls-info-banner {
                        display: flex; align-items: flex-start; gap: .85rem;
                        padding: 1.15rem 1.25rem; border-radius: 12px;
                        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                        border: 1px solid #bae6fd; margin-top: .25rem;
                    }
                    .ls-info-icon {
                        width: 36px; height: 36px; border-radius: 9px;
                        background: #0284c7; display: flex; align-items: center;
                        justify-content: center; color: #fff; font-size: .9rem; flex-shrink: 0;
                    }
                    .ls-info-title { font-size: .9rem; font-weight: 700; color: #0c4a6e; margin-bottom: .2rem; }
                    .ls-info-text { font-size: .8rem; color: #475569; line-height: 1.5; margin: 0; }

                    /* Alert */
                    .ls-alert {
                        display: flex; align-items: center; gap: .6rem;
                        padding: .75rem 1rem; border-radius: 9px;
                        background: #eff6ff; border: 1px solid #bfdbfe;
                        font-size: .82rem; color: #1e40af; margin-top: .85rem;
                    }
                    .ls-alert i { color: #3b82f6; flex-shrink: 0; }

                    @media (max-width: 768px) {
                        .ls-mentions-grid { grid-template-columns: 1fr 1fr; }
                    }
                </style>

                <div class="tab-pane fade" id="lmd" role="tabpanel">
                    @php
                        $lmdSettings = \App\Models\Setting::where('group', 'lmd')->get()->keyBy('key');
                        $lmdVal = fn($key, $default = '') => old("setting_{$key}", $lmdSettings[$key]->value ?? $default);
                    @endphp

                    {{-- Section 1: Crédits CECT --}}
                    <div class="ls-section ls-section--credits">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--credits"><i class="fas fa-award"></i></div>
                            <div class="ls-title">Crédits CECT</div>
                        </div>
                        <div class="ls-desc">
                            Configuration des crédits selon la norme UEMOA. Ces valeurs s'appliquent à tous les étudiants LMD.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="ls-field">
                                    <div class="ls-label">Crédits par semestre</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_per_semester"
                                           value="{{ $lmdVal('lmd_credits_per_semester', 30) }}" min="1" max="60">
                                    <div class="ls-hint">Standard UEMOA : 30</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ls-field">
                                    <div class="ls-label">Total Licence</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_licence_total"
                                           value="{{ $lmdVal('lmd_credits_licence_total', 180) }}" min="1">
                                    <div class="ls-hint">6 semestres x 30 = 180</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ls-field">
                                    <div class="ls-label">Total Master</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_master_total"
                                           value="{{ $lmdVal('lmd_credits_master_total', 120) }}" min="1">
                                    <div class="ls-hint">4 semestres x 30 = 120</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Validation & Compensation --}}
                    <div class="ls-section ls-section--validation">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--validation"><i class="fas fa-check-double"></i></div>
                            <div class="ls-title">Validation & Compensation</div>
                        </div>
                        <div class="ls-desc">
                            Règles de validation des UE et compensation. Conforme à la directive UEMOA par défaut.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Seuil validation UE (/20)</div>
                                    <input type="number" class="ls-input" name="setting_lmd_validation_threshold"
                                           value="{{ $lmdVal('lmd_validation_threshold', 10) }}" min="0" max="20" step="0.5">
                                    <div class="ls-hint">Standard : 10/20</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Note éliminatoire (/20)</div>
                                    <input type="number" class="ls-input" name="setting_lmd_note_eliminatoire"
                                           value="{{ $lmdVal('lmd_note_eliminatoire', 0) }}" min="0" max="10" step="0.5">
                                    <div class="ls-hint">0 = pas de note éliminatoire (UEMOA)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="ls-toggle" for="lmd_compensation_inter_ue">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Compensation inter-UE</div>
                                        <div class="ls-toggle-hint">APC : UE &lt; 10 compensée si moy. gén. &ge; 10</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_compensation_inter_ue"
                                               name="setting_lmd_compensation_inter_ue" value="1"
                                               {{ $lmdVal('lmd_compensation_inter_ue', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label class="ls-toggle" for="lmd_compensation_intra_ue">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Compensation intra-UE</div>
                                        <div class="ls-toggle-hint">ECUE se compensent dans la même UE</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_compensation_intra_ue"
                                               name="setting_lmd_compensation_intra_ue" value="1"
                                               {{ $lmdVal('lmd_compensation_intra_ue', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                        </div>
                        @include('esbtp.settings.partials.separation-des-devoirs')
                        <div class="row g-3" style="margin-top:.25rem;">
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_suppression_ue_libere_ecues_vers_bts">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Suppression d'une UE : rendre ses ECUE au BTS</div>
                                        <div class="ls-toggle-hint">Décochez dans une école tout-LMD : sinon les ECUE réapparaissent dans les écrans BTS</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        {{-- Une case décochée n'est pas envoyée : sans ce champ caché, le
                                             réglage ne pourrait jamais repasser à « non ». --}}
                                        <input type="hidden" name="setting_lmd_suppression_ue_libere_ecues_vers_bts" value="0">
                                        <input class="form-check-input" type="checkbox" id="lmd_suppression_ue_libere_ecues_vers_bts"
                                               name="setting_lmd_suppression_ue_libere_ecues_vers_bts" value="1"
                                               {{ $lmdVal('lmd_suppression_ue_libere_ecues_vers_bts', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Évaluations --}}
                    <div class="ls-section ls-section--evals">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--evals"><i class="fas fa-clipboard-check"></i></div>
                            <div class="ls-title">Évaluations</div>
                        </div>
                        <div class="ls-desc">
                            Pondération entre Contrôle Continu et Examen. Chaque établissement peut définir sa propre répartition.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Pondération CC (%)</div>
                                    <input type="number" class="ls-input" name="setting_lmd_cc_weight"
                                           value="{{ $lmdVal('lmd_cc_weight', 40) }}" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Pondération Examen (%)</div>
                                    <input type="number" class="ls-input" name="setting_lmd_exam_weight"
                                           value="{{ $lmdVal('lmd_exam_weight', 60) }}" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ls-field">
                                    <div class="ls-label">Portée du rattrapage</div>
                                    <select class="ls-input" name="setting_lmd_rattrapage_scope">
                                        <option value="ecue" {{ $lmdVal('lmd_rattrapage_scope', 'ecue') === 'ecue' ? 'selected' : '' }}>
                                            ECUE ratés uniquement
                                        </option>
                                        <option value="ue" {{ $lmdVal('lmd_rattrapage_scope', 'ecue') === 'ue' ? 'selected' : '' }}>
                                            Toute l'UE non acquise
                                        </option>
                                    </select>
                                    <div class="ls-hint">Standard UEMOA : ECUE des UE non acquises</div>
                                </div>
                            </div>
                        </div>
                        <div class="ls-alert">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                <strong>Note :</strong> CC + Examen doivent totaliser 100%. Si vous modifiez l'un, ajustez l'autre.
                                Ces deux pondérations ne sont pas encore appliquées au calcul des moyennes : elles sont
                                enregistrées pour l'établissement, mais la moyenne d'une matière reste calculée comme aujourd'hui.
                            </span>
                        </div>
                    </div>

                    {{-- Section 4: Mentions UE --}}
                    <div class="ls-section ls-section--mentions">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--mentions"><i class="fas fa-medal"></i></div>
                            <div class="ls-title">Mentions UE</div>
                        </div>
                        <div class="ls-desc">
                            Seuils de notes pour les mentions attribuées aux UE sur le bulletin semestriel.
                        </div>
                        <div class="ls-mentions-grid">
                            <div class="ls-mention-card ls-mention-card--tb">
                                <div class="ls-mention-badge">TB</div>
                                <div class="ls-mention-name">Très Bien (&ge;)</div>
                                <input type="number" class="ls-input" name="setting_lmd_mention_tb_threshold"
                                       value="{{ $lmdVal('lmd_mention_tb_threshold', 16) }}" min="0" max="20" step="0.5"
                                       style="text-align:center; font-weight:700; font-size:1rem;">
                            </div>
                            <div class="ls-mention-card ls-mention-card--b">
                                <div class="ls-mention-badge">B</div>
                                <div class="ls-mention-name">Bien (&ge;)</div>
                                <input type="number" class="ls-input" name="setting_lmd_mention_b_threshold"
                                       value="{{ $lmdVal('lmd_mention_b_threshold', 14) }}" min="0" max="20" step="0.5"
                                       style="text-align:center; font-weight:700; font-size:1rem;">
                            </div>
                            <div class="ls-mention-card ls-mention-card--ab">
                                <div class="ls-mention-badge">AB</div>
                                <div class="ls-mention-name">Assez Bien (&ge;)</div>
                                <input type="number" class="ls-input" name="setting_lmd_mention_ab_threshold"
                                       value="{{ $lmdVal('lmd_mention_ab_threshold', 12) }}" min="0" max="20" step="0.5"
                                       style="text-align:center; font-weight:700; font-size:1rem;">
                            </div>
                            <div class="ls-mention-card ls-mention-card--p">
                                <div class="ls-mention-badge">P</div>
                                <div class="ls-mention-name">Passable (&ge;)</div>
                                <input type="number" class="ls-input" name="setting_lmd_mention_p_threshold"
                                       value="{{ $lmdVal('lmd_mention_p_threshold', 10) }}" min="0" max="20" step="0.5"
                                       style="text-align:center; font-weight:700; font-size:1rem;">
                            </div>
                        </div>
                    </div>

                    {{-- Section 5: Délibération --}}
                    @include('esbtp.settings.partials.appreciation-scale-editor', [
                        'system' => 'lmd',
                        'scale' => old('appreciation_scale_lmd', $appreciationScales['lmd'] ?? []),
                        'title' => 'Barème des appréciations LMD',
                        'description' => 'Libellés complets utilisés dans les notes LMD, résultats LMD, bulletins LMD et dossier étudiant.',
                    ])

                    {{-- Section 6: Champs Bulletin LMD --}}
                    <div class="ls-section ls-section--bulletin-fields">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--mentions"><i class="fas fa-id-card"></i></div>
                            <div class="ls-title">Champs Bulletin LMD</div>
                        </div>
                        <div class="ls-desc">
                            Configurez quels champs afficher sur le bulletin semestriel et personnalisez leurs libellés.
                            Selon la hiérarchie UEMOA : Domaine → Mention → (Spécialité) → Parcours.
                        </div>

                        <div class="row g-3">
                            {{-- Domaine --}}
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_bulletin_show_domaine">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Domaine</div>
                                        <div class="ls-toggle-hint">Ex: Sciences et Technologies, Lettres et Sciences Humaines</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_domaine"
                                               name="setting_lmd_bulletin_show_domaine" value="1"
                                               {{ $lmdVal('lmd_bulletin_show_domaine', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <div class="ls-field">
                                    <div class="ls-label">Libellé "Domaine"</div>
                                    <input type="text" class="ls-input" name="setting_lmd_bulletin_label_domaine"
                                            value="{{ $lmdVal('lmd_bulletin_label_domaine') }}" placeholder="@rang('domaine')">
                                </div>
                            </div>

                            {{-- Mention --}}
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_bulletin_show_mention">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Mention</div>
                                        <div class="ls-toggle-hint">Ex: Génie Civil, Informatique</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_mention"
                                               name="setting_lmd_bulletin_show_mention" value="1"
                                               {{ $lmdVal('lmd_bulletin_show_mention', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <div class="ls-field">
                                    <div class="ls-label">Libellé "Mention"</div>
                                    <input type="text" class="ls-input" name="setting_lmd_bulletin_label_mention"
                                            value="{{ $lmdVal('lmd_bulletin_label_mention') }}" placeholder="@rang('mention')">
                                </div>
                            </div>

                            {{-- Spécialité --}}
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_bulletin_show_specialite">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Spécialité</div>
                                        <div class="ls-toggle-hint">Niveau intermédiaire entre Mention et Parcours. Aucune spécialité n'est encore enregistrée sur les étudiants : cette ligne reste vide sur le bulletin tant que la donnée n'existe pas.</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_specialite"
                                               name="setting_lmd_bulletin_show_specialite" value="1"
                                               {{ $lmdVal('lmd_bulletin_show_specialite', '0') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <div class="ls-field">
                                    <div class="ls-label">Libellé "Spécialité"</div>
                                    <input type="text" class="ls-input" name="setting_lmd_bulletin_label_specialite"
                                           value="{{ $lmdVal('lmd_bulletin_label_specialite', 'SPÉCIALITÉ') }}" placeholder="SPÉCIALITÉ">
                                </div>
                            </div>

                            {{-- Parcours --}}
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_bulletin_show_parcours">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Parcours</div>
                                        <div class="ls-toggle-hint">Ex: LICENCE 3 GCV BATIMENT & URBANISME</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_parcours"
                                               name="setting_lmd_bulletin_show_parcours" value="1"
                                               {{ $lmdVal('lmd_bulletin_show_parcours', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <div class="ls-field">
                                    <div class="ls-label">Libellé "Parcours"</div>
                                    <input type="text" class="ls-input" name="setting_lmd_bulletin_label_parcours"
                                            value="{{ $lmdVal('lmd_bulletin_label_parcours') }}" placeholder="@rang('parcours')">
                                </div>
                            </div>

                            {{-- Parcours auto --}}
                            <div class="col-md-12">
                                <label class="ls-toggle" for="lmd_bulletin_parcours_auto">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Parcours auto-généré</div>
                                        <div class="ls-toggle-hint">Compose automatiquement le parcours depuis Niveau + Filière (ex: "LICENCE 3 GCV BATIMENT & URBANISME")</div>
                                    </div>
                                    <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                                        <input class="form-check-input" type="checkbox" id="lmd_bulletin_parcours_auto"
                                               name="setting_lmd_bulletin_parcours_auto" value="1"
                                               {{ $lmdVal('lmd_bulletin_parcours_auto', '1') == '1' ? 'checked' : '' }}>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="ls-alert" style="margin-top: 1rem;">
                            <i class="fas fa-info-circle"></i>
                            <span><strong>Hiérarchie UEMOA :</strong> Domaine → Mention → Spécialité (optionnel) → Parcours. Activez uniquement les niveaux utilisés par votre établissement.</span>
                        </div>
                    </div>

                    {{-- Info UEMOA --}}
                    <div class="ls-info-banner">
                        <div class="ls-info-icon"><i class="fas fa-globe-africa"></i></div>
                        <div>
                            <div class="ls-info-title">Conformité UEMOA</div>
                            <p class="ls-info-text">
                                Les valeurs par défaut respectent la Directive 03/2007/CM/UEMOA portant adoption du système LMD
                                dans l'espace UEMOA : 30 crédits/semestre, validation à 10/20, compensation sans note éliminatoire,
                                crédits capitalisables et transférables.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- End Tab 6: Système LMD -->

                <!-- Tab 7: Comptabilité / SAARI -->
                <div class="tab-pane fade" id="compta" role="tabpanel">
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon" style="background:linear-gradient(135deg, #10b981, #0ea5e9); color:#fff;">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div>
                                <h3>Export comptable SAARI (Sage Ligne 100)</h3>
                                <p style="margin:0; font-size:.85rem; color:#64748b;">
                                    Configuration du mapping comptable pour l'export EXCEL SAARI des paiements.
                                    Permet à chaque école de définir son code journal, son compte par défaut et son mapping
                                    catégories de frais → numéros de compte SAARI.
                                </p>
                            </div>
                        </div>

                        <div class="settings-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-top:1rem;">
                            <div>
                                <label class="form-label" for="setting_saari_code_journal">
                                    <i class="fas fa-book-open me-1 text-primary"></i> Code journal SAARI
                                </label>
                                <input type="text" class="form-control form-control-modern @error('saari_code_journal') is-invalid @enderror"
                                       id="setting_saari_code_journal"
                                       name="setting_saari_code_journal"
                                       maxlength="10"
                                       value="{{ old('setting_saari_code_journal', \App\Helpers\SettingsHelper::get('saari_code_journal', 'JV')) }}"
                                       placeholder="JV">
                                @error('saari_code_journal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Ex: <code>JV</code> (Journal de Versement), <code>BK</code> (Banque), <code>CA</code> (Caisse).</small>
                            </div>

                            <div>
                                <label class="form-label" for="setting_saari_default_account">
                                    <i class="fas fa-coins me-1 text-success"></i> Compte SAARI par défaut
                                </label>
                                <input type="text" class="form-control form-control-modern @error('saari_default_account') is-invalid @enderror"
                                       id="setting_saari_default_account"
                                       name="setting_saari_default_account"
                                       maxlength="20"
                                       value="{{ old('setting_saari_default_account', \App\Helpers\SettingsHelper::get('saari_default_account', '')) }}"
                                       placeholder="411000">
                                @error('saari_default_account')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Numéro de compte utilisé si une catégorie de frais n'a pas de mapping spécifique. Ex: <code>411000</code> (clients).</small>
                            </div>
                        </div>

                        <div style="margin-top:1.25rem;">
                            <label class="form-label" for="setting_saari_account_mapping">
                                <i class="fas fa-link me-1 text-info"></i> Mapping catégories → comptes SAARI (JSON)
                            </label>
                            <textarea class="form-control form-control-modern @error('saari_account_mapping') is-invalid @enderror"
                                      id="setting_saari_account_mapping"
                                      name="setting_saari_account_mapping"
                                      rows="6"
                                      style="font-family:'Courier New', monospace; font-size:.85rem;"
                                      placeholder='{"1": "411000", "Inscription": "706100", "Scolarite": "706200"}'>{{ old('setting_saari_account_mapping', \App\Helpers\SettingsHelper::get('saari_account_mapping', '{}')) }}</textarea>
                            @error('saari_account_mapping')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div style="background:rgba(4,83,203,.04); border:1px solid rgba(4,83,203,.15); border-radius:8px; padding:.85rem 1rem; margin-top:.6rem; font-size:.82rem; color:#1e293b;">
                                <strong><i class="fas fa-info-circle"></i> Format du mapping</strong>
                                <ul style="margin:.4rem 0 0 1.25rem; padding:0; line-height:1.7;">
                                    <li><code>"id_categorie"</code> (numérique) → match exact sur l'ID de la catégorie de frais</li>
                                    <li><code>"mot_cle"</code> (chaîne) → match partiel insensible à la casse sur le nom de la catégorie</li>
                                </ul>
                                <strong>Exemple</strong> :
                                <pre style="margin:.4rem 0 0; padding:.5rem; background:#0f172a; color:#94a3b8; border-radius:6px; font-size:.78rem;">{
  "1": "411000",
  "2": "706000",
  "Inscription": "706100",
  "Scolarite": "706200",
  "Examen": "706300"
}</pre>
                            </div>
                        </div>

                        <div style="margin-top:1.25rem; padding:.85rem 1rem; background:linear-gradient(135deg, rgba(16,185,129,.06), rgba(14,165,233,.08)); border:1px solid rgba(16,185,129,.2); border-radius:10px;">
                            <i class="fas fa-file-excel" style="color:#10b981;"></i>
                            <strong>Utilisation</strong> : ces paramètres sont consommés par l'export EXCEL SAARI disponible
                            dans la page Paiements (menu Export → "Export EXCEL SAARI"). Format de sortie compatible Sage Ligne 100,
                            onglet <code>BNI BKE</code>.
                        </div>
                    </div>
                </div>
                <!-- End Tab 7: Comptabilité / SAARI -->

            </div>
            <!-- End Tab Content -->

            <!-- Bouton de sauvegarde -->
            <div class="settings-actions-bar">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save"></i>
                    Sauvegarder les Paramètres
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.settings-section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'all 0.6s ease';
        observer.observe(section);
    });
    
    // Validation en temps réel
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            const min = parseFloat(this.min);
            const max = parseFloat(this.max);
            const value = parseFloat(this.value);
            
            if (value < min || value > max) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#27ae60';
            }
        });
    });
    
    // Preview des changements
    document.querySelectorAll('.form-switch-modern input').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const section = this.closest('.settings-section');
            if (this.checked) {
                section.style.borderLeft = '4px solid var(--primary)';
            } else {
                section.style.borderLeft = 'none';
            }
        });
    });
});

// Fonction pour tout cocher/décocher dans une section
function toggleSectionCheckboxes(sectionId, state) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    
    const checkboxes = section.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = state;
        // Déclencher l'événement change pour les effets visuels
        checkbox.dispatchEvent(new Event('change'));
    });
    
    // Animation pour indiquer l'action
    section.style.transform = 'scale(1.02)';
    section.style.transition = 'transform 0.3s ease';
    setTimeout(() => {
        section.style.transform = 'scale(1)';
    }, 300);
}

// ── Upload logo avec preview ──────────────────────────────────
function handleLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert('Le fichier est trop volumineux (max 2 Mo)');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('logoPreviewImg');
        const placeholder = document.getElementById('logoPlaceholder');
        const badge = document.querySelector('.pdf-logo-badge');
        if (img) {
            img.src = e.target.result;
            img.style.display = '';
        }
        if (placeholder) placeholder.style.display = 'none';
        if (!badge) {
            const b = document.createElement('span');
            b.className = 'pdf-logo-badge';
            b.innerHTML = '<i class="fas fa-check-circle"></i> Nouveau logo';
            document.querySelector('.pdf-logo-current').appendChild(b);
        } else {
            badge.innerHTML = '<i class="fas fa-check-circle"></i> Nouveau logo';
        }
    };
    reader.readAsDataURL(file);
}

// Drag & drop sur la zone logo
document.addEventListener('DOMContentLoaded', function() {
    const drop = document.getElementById('logoDrop');
    if (drop) {
        drop.addEventListener('dragover', e => { e.preventDefault(); drop.style.borderColor = 'var(--primary)'; });
        drop.addEventListener('dragleave', () => { drop.style.borderColor = ''; });
        drop.addEventListener('drop', e => {
            e.preventDefault();
            drop.style.borderColor = '';
            const dt = e.dataTransfer;
            if (dt && dt.files.length) {
                const fi = document.getElementById('logoFileInput');
                // DataTransfer trick to set files on input
                try {
                    const dtt = new DataTransfer();
                    dtt.items.add(dt.files[0]);
                    fi.files = dtt.files;
                } catch(err) {}
                handleLogoUpload({ files: dt.files, value: '' });
            }
        });
    }
});

// ── Calcul ratio de contraste WCAG ───────────────────────────
function hexToRgb(hex) {
    const r = parseInt(hex.slice(1,3),16)/255;
    const g = parseInt(hex.slice(3,5),16)/255;
    const b = parseInt(hex.slice(5,7),16)/255;
    return [r,g,b];
}
function relativeLuminance(r,g,b) {
    const c = [r,g,b].map(v => v <= 0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055,2.4));
    return 0.2126*c[0] + 0.7152*c[1] + 0.0722*c[2];
}
function contrastRatio(hex1, hex2) {
    const [r1,g1,b1] = hexToRgb(hex1);
    const [r2,g2,b2] = hexToRgb(hex2);
    const L1 = relativeLuminance(r1,g1,b1);
    const L2 = relativeLuminance(r2,g2,b2);
    const lighter = Math.max(L1,L2);
    const darker  = Math.min(L1,L2);
    return (lighter + 0.05) / (darker + 0.05);
}
function setContrastBadge(badgeId, ratio, label) {
    const el = document.getElementById(badgeId);
    if (!el) return;
    if (ratio >= 4.5) {
        el.className = 'pdf-contrast-badge ok';
        el.innerHTML = `<i class="fas fa-check-circle"></i> Contraste ${ratio.toFixed(1)}:1 — lisible`;
    } else if (ratio >= 3) {
        el.className = 'pdf-contrast-badge warn';
        el.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Contraste ${ratio.toFixed(1)}:1 — passable`;
    } else {
        el.className = 'pdf-contrast-badge bad';
        el.innerHTML = `<i class="fas fa-times-circle"></i> Contraste ${ratio.toFixed(1)}:1 — difficile à lire`;
    }
}

// ── Mise à jour live preview ──────────────────────────────────
function updatePreview() {
    const bg    = document.getElementById('colorHeaderBg')?.value   || '#0453cb';
    const txt   = document.getElementById('colorHeaderText')?.value || '#ffffff';
    const acc   = document.getElementById('colorAccent')?.value     || '#0453cb';
    const body  = document.getElementById('colorBody')?.value       || '#1f2937';

    // Mettre à jour les swatches
    ['HeaderBg','HeaderText','Accent','Body'].forEach(k => {
        const s = document.getElementById('swatch'+k);
        const v = {HeaderBg:bg,HeaderText:txt,Accent:acc,Body:body}[k];
        if (s) s.style.background = v;
    });

    // Mettre à jour la preview
    const header = document.getElementById('prev-header-bg');
    const headerTxt = document.getElementById('prev-header-text');
    const divider = document.getElementById('prev-accent');
    const docTitle = document.querySelector('.prev-doc-title');
    const prevBody = document.getElementById('prev-body');
    const tableHead = document.querySelector('.prev-table-head');
    const tableRow  = document.querySelector('.prev-table-row');
    const hlBlock   = document.querySelector('.prev-hl-block');
    const hlLine1   = hlBlock ? hlBlock.querySelectorAll('.prev-line')[0] : null;
    const hlLine2   = hlBlock ? hlBlock.querySelectorAll('.prev-line')[1] : null;

    if (header)    header.style.background = bg;
    if (headerTxt) headerTxt.style.color = txt;
    if (divider)   divider.style.background = acc;
    if (docTitle)  { docTitle.style.color = acc; docTitle.style.borderBottomColor = acc; }
    if (prevBody)  prevBody.style.color = body;
    if (tableHead) { tableHead.style.background = acc; tableHead.style.color = '#ffffff'; }
    if (tableRow)  tableRow.style.color = body;
    if (hlBlock) {
        hlBlock.style.borderLeftColor = acc;
        hlBlock.style.background = acc + '12';
    }
    if (hlLine1)  hlLine1.style.background = acc + '55';
    if (hlLine2)  hlLine2.style.background = body + '33';

    // Badges de contraste
    setContrastBadge('contrastHeaderBg',   contrastRatio(bg,  txt), 'En-tête');
    setContrastBadge('contrastHeaderText', contrastRatio(txt, bg),  'Texte en-tête');
    setContrastBadge('contrastAccent',     contrastRatio(acc, '#ffffff'), 'Titre');
    setContrastBadge('contrastBody',       contrastRatio(body,'#ffffff'), 'Corps');

    // Avertissement global blanc-sur-blanc ou bleu-sur-bleu
    const warnings = [];
    if (contrastRatio(bg,txt) < 3)   warnings.push('Fond en-tête / Texte en-tête peu lisible');
    if (contrastRatio(acc,'#ffffff') < 3) warnings.push('Couleur accent trop claire pour titres blancs');
    const warn = document.getElementById('globalContrastWarning');
    const msg  = document.getElementById('globalContrastMsg');
    if (warn && msg) {
        if (warnings.length) {
            warn.style.display = 'flex';
            msg.textContent = '⚠ ' + warnings.join(' · ');
        } else {
            warn.style.display = 'none';
        }
    }
}

// Initialiser la preview et les badges au chargement
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});

// Fonction pour tester les rappels
function testReminders() {
    const button = event.target;
    const resultsDiv = document.getElementById('test-results');

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Test en cours...';

    resultsDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Exécution de la commande de test en cours...
        </div>
    `;

    // Appel AJAX pour exécuter la commande
    fetch('{{ route("esbtp.settings.test-reminders") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-play-circle me-2"></i> Tester les rappels (mode simulation)';

        if (data.success) {
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i> Test terminé avec succès</h5>
                    <ul class="mb-0">
                        <li><strong>Inscriptions en attente :</strong> ${data.data.inscriptions_found} trouvées, ${data.data.inscriptions_sent} rappels auraient été envoyés</li>
                        <li><strong>Paiements en attente :</strong> ${data.data.paiements_found} trouvés, ${data.data.paiements_sent} rappels auraient été envoyés</li>
                    </ul>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Mode simulation : Aucune notification n'a été réellement envoyée.
                    </small>
                </div>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.message || 'Erreur lors de l\'exécution du test'}
                </div>
            `;
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-play-circle me-2"></i> Tester les rappels (mode simulation)';
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Erreur: ${error.message}
            </div>
        `;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const placeholders = {
        email: 'test@example.com',
        phone: '0544210112',
    };

    const createRow = (type, value = '', enabled = true) => {
        const row = document.createElement('div');
        row.className = 'mailpulse-recipient-row';
        row.setAttribute('data-mailpulse-recipient-row', '');
        row.innerHTML = `
            <label class="mailpulse-mini-toggle" title="Activer ce destinataire">
                <input type="checkbox" data-mailpulse-recipient-enabled ${enabled ? 'checked' : ''}>
                <span></span>
            </label>
            <input type="${type === 'email' ? 'email' : 'tel'}"
                   class="form-control form-control-modern ${type === 'phone' ? 'mailpulse-code-input' : ''}"
                   data-mailpulse-recipient-value
                   value=""
                   placeholder="${placeholders[type]}">
            <button type="button" class="mailpulse-icon-button" data-mailpulse-recipient-remove title="Retirer">
                <i class="fas fa-times"></i>
            </button>
        `;
        row.querySelector('[data-mailpulse-recipient-value]').value = value;
        return row;
    };

    const syncRecipients = (type) => {
        const list = document.querySelector(`[data-mailpulse-recipient-list="${type}"]`);
        const hidden = document.querySelector(`[data-mailpulse-recipient-json="${type}"]`);
        if (!list || !hidden) {
            return;
        }

        const recipients = [...list.querySelectorAll('[data-mailpulse-recipient-row]')]
            .map((row) => ({
                value: row.querySelector('[data-mailpulse-recipient-value]')?.value.trim() || '',
                enabled: !!row.querySelector('[data-mailpulse-recipient-enabled]')?.checked,
            }))
            .filter((recipient) => recipient.value !== '');

        hidden.value = JSON.stringify(recipients);
    };

    const ensureOneRow = (type) => {
        const list = document.querySelector(`[data-mailpulse-recipient-list="${type}"]`);
        if (list && !list.querySelector('[data-mailpulse-recipient-row]')) {
            list.appendChild(createRow(type));
        }
    };

    document.querySelectorAll('[data-mailpulse-recipient-add]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.getAttribute('data-mailpulse-recipient-add');
            const list = document.querySelector(`[data-mailpulse-recipient-list="${type}"]`);
            if (list) {
                list.appendChild(createRow(type));
                syncRecipients(type);
            }
        });
    });

    document.querySelectorAll('[data-mailpulse-recipient-list]').forEach((list) => {
        const type = list.getAttribute('data-mailpulse-recipient-list');
        list.addEventListener('input', () => syncRecipients(type));
        list.addEventListener('change', () => syncRecipients(type));
        list.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-mailpulse-recipient-remove]');
            if (!remove) {
                return;
            }

            remove.closest('[data-mailpulse-recipient-row]')?.remove();
            ensureOneRow(type);
            syncRecipients(type);
        });
        syncRecipients(type);
    });

    const protectEmptyMailPulseApiKeySubmit = () => {
        const apiKeyInput = document.querySelector('[name="setting_mailpulse_api_key"]');
        if (!apiKeyInput || apiKeyInput.disabled) {
            return;
        }

        if (String(apiKeyInput.value ?? '').trim() !== '') {
            return;
        }

        apiKeyInput.disabled = true;
        window.requestAnimationFrame(() => {
            apiKeyInput.disabled = false;
        });
    };

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            syncRecipients('email');
            syncRecipients('phone');
            protectEmptyMailPulseApiKeySubmit();
        });
    });

    const testButton = document.querySelector('[data-mailpulse-test-submit]');
    const saveButton = document.querySelector('[data-mailpulse-save-submit]');
    const saveStatus = document.querySelector('[data-mailpulse-save-status]');
    const mailPulsePanel = document.querySelector('#mailpulse');
    const settingsForm = document.querySelector('form[action="{{ route('esbtp.settings.update') }}"]');
    const mailPulseSaveUrl = '{{ route('esbtp.settings.mailpulse.save') }}';
    const resultBox = document.querySelector('[data-mailpulse-test-result]');
    const eventSelect = document.querySelector('[data-mailpulse-test-event]');
    const channelSelect = document.querySelector('[data-mailpulse-test-channel]');
    const dryRunInput = document.querySelector('[data-mailpulse-test-dry-run]');

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderMailPulseResult = (payload, ok) => {
        if (!resultBox) {
            return;
        }

        const email = payload.email || {};
        const whatsapp = payload.whatsapp || {};
        const message = payload.message || (ok ? 'Test MailPulse terminé.' : 'Le test MailPulse a échoué.');
        const channelLabel = (channel) => {
            if (!channel.attempted) {
                return channel.message || channel.status || 'Ignoré';
            }

            if (channel.ok === false) {
                return channel.message || channel.action || channel.status || 'Erreur';
            }

            return channel.message || channel.status || 'Envoyé';
        };
        const channelActions = [email, whatsapp]
            .filter((channel) => channel.attempted && channel.ok === false && channel.action)
            .map((channel) => channel.action)
            .filter((value, index, values) => values.indexOf(value) === index);

        resultBox.classList.remove('d-none', 'is-success', 'is-error');
        resultBox.classList.add(ok ? 'is-success' : 'is-error');
        resultBox.innerHTML = `
            <div>
                <strong><i class="fas ${ok ? 'fa-check-circle' : 'fa-triangle-exclamation'} me-2"></i>${escapeHtml(message)}</strong>
            </div>
            <div class="mailpulse-test-kv">
                <span><strong>Contact</strong>${escapeHtml(payload.contactId || payload.contact?.status || 'Non créé')}</span>
                <span><strong>Email</strong>${escapeHtml(channelLabel(email))}</span>
                <span><strong>WhatsApp</strong>${escapeHtml(channelLabel(whatsapp))}</span>
            </div>
            ${email.attempted && email.ok === false && email.message ? `<div class="mt-3"><strong>Détail email</strong><br>${escapeHtml(email.message)}</div>` : ''}
            ${whatsapp.attempted && whatsapp.ok === false && whatsapp.message ? `<div class="mt-3"><strong>Détail WhatsApp</strong><br>${escapeHtml(whatsapp.message)}</div>` : ''}
            ${channelActions.length ? `<div class="mt-3"><strong>Action recommand&eacute;e</strong><br>${escapeHtml(channelActions.join(' '))}</div>` : ''}
            ${payload.errors ? `<pre class="mt-3 mb-0">${escapeHtml(JSON.stringify(payload.errors, null, 2))}</pre>` : ''}
        `;
    };

    const buildMailPulseFormData = () => {
        const source = mailPulsePanel || settingsForm;

        if (!source) {
            return null;
        }

        const formData = new FormData();
        source.querySelectorAll('[name^="setting_mailpulse_"]').forEach((field) => {
            if (!field.name || field.disabled) {
                return;
            }

            if (field.type === 'checkbox') {
                if (field.checked) {
                    formData.set(field.name, field.value || '1');
                } else if (!formData.has(field.name)) {
                    formData.set(field.name, '0');
                }
                return;
            }

            if (field.type === 'radio') {
                if (field.checked) {
                    formData.set(field.name, field.value || '');
                }
                return;
            }

            formData.set(field.name, field.value ?? '');
        });

        const apiKeyInput = document.querySelector('[name="setting_mailpulse_api_key"]');
        if (apiKeyInput && !apiKeyInput.disabled) {
            const apiKeyValue = String(apiKeyInput.value ?? '').trim();
            if (apiKeyValue !== '') {
                formData.set('setting_mailpulse_api_key', apiKeyValue);
            } else {
                formData.delete('setting_mailpulse_api_key');
            }
        }

        return formData;
    };

    const saveMailPulseBeforeTest = async () => {
        if (!mailPulsePanel && !settingsForm) {
            throw new Error('Formulaire MailPulse introuvable.');
        }

        syncRecipients('email');
        syncRecipients('phone');

        const formData = buildMailPulseFormData();
        if (!formData) {
            throw new Error('Formulaire MailPulse introuvable.');
        }

        const response = await fetch(mailPulseSaveUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: formData,
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: 'Réponse serveur illisible.',
        }));

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Enregistrement MailPulse impossible.');
        }

        const submittedApiKey = String(formData.get('setting_mailpulse_api_key') || '').trim();
        if (submittedApiKey !== '' && payload.api_key_received === false) {
            throw new Error("La clé API saisie n'a pas été reçue par le serveur. Rechargez la page puis réessayez.");
        }

        return payload;
    };

    if (saveButton && saveStatus && (mailPulsePanel || settingsForm)) {
        saveButton.addEventListener('click', async () => {
            syncRecipients('email');
            syncRecipients('phone');

            const originalLabel = saveButton.innerHTML;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement';
            saveStatus.className = 'mailpulse-save-status';
            saveStatus.textContent = 'Enregistrement des paramètres MailPulse...';

            try {
                const payload = await saveMailPulseBeforeTest();

                saveStatus.className = 'mailpulse-save-status is-success';
                saveStatus.textContent = payload.message || (
                    payload.api_key_configured
                        ? 'Paramètres MailPulse enregistrés. Clé API active.'
                        : "Paramètres MailPulse enregistrés, mais aucune clé API active n'est stockée."
                );
            } catch (error) {
                saveStatus.className = 'mailpulse-save-status is-error';
                saveStatus.textContent = error.message || "Erreur pendant l'enregistrement MailPulse.";
            } finally {
                saveButton.disabled = false;
                saveButton.innerHTML = originalLabel;
            }
        });
    }

    if (testButton && resultBox && eventSelect && channelSelect && dryRunInput) {
        testButton.addEventListener('click', async () => {
            syncRecipients('email');
            syncRecipients('phone');

            const originalLabel = testButton.innerHTML;
            testButton.disabled = true;
            testButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours';
            resultBox.classList.remove('d-none', 'is-success', 'is-error');
            resultBox.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Préparation du scénario de test MailPulse...';

            try {
                resultBox.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Enregistrement des destinataires de test...';
                const savePayload = await saveMailPulseBeforeTest();
                if (!dryRunInput.checked && savePayload.api_key_configured === false) {
                    throw new Error(savePayload.message || "La clé API MailPulse n'est pas enregistrée côté serveur.");
                }
                resultBox.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Envoi du test MailPulse...';

                const response = await fetch('{{ route('esbtp.settings.mailpulse.test-notification') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        event: eventSelect.value,
                        channel: channelSelect.value,
                        dryRun: dryRunInput.checked,
                    }),
                });
                const payload = await response.json().catch(() => ({
                    ok: false,
                    message: 'Réponse MailPulse illisible.',
                }));
                renderMailPulseResult(payload, response.ok && payload.ok !== false);
            } catch (error) {
                renderMailPulseResult({
                    ok: false,
                    message: error.message || 'Erreur réseau pendant le test MailPulse.',
                }, false);
            } finally {
                testButton.disabled = false;
                testButton.innerHTML = originalLabel;
            }
        });
    }
});

// ====================================================================
// Phase 9 — Sections avancées PDF (mise en page, footer, watermark)
// ====================================================================


window.pdfAdvancedSection = function () {
    return {
        settings: {
            pdf_logo_size: '{{ \App\Helpers\SettingsHelper::get("pdf_logo_size", "60") }}',
            pdf_font_size: '{{ \App\Helpers\SettingsHelper::get("pdf_font_size", "12") }}',
            pdf_margin_top: '{{ \App\Helpers\SettingsHelper::get("pdf_margin_top", "20") }}',
            pdf_margin_bottom: '{{ \App\Helpers\SettingsHelper::get("pdf_margin_bottom", "20") }}',
            pdf_margin_left: '{{ \App\Helpers\SettingsHelper::get("pdf_margin_left", "15") }}',
            pdf_margin_right: '{{ \App\Helpers\SettingsHelper::get("pdf_margin_right", "15") }}',
        },
    };
};

window.watermarkSection = function () {
    return {
        watermark: '{{ addslashes(\App\Helpers\SettingsHelper::get("pdf_watermark", "")) }}',
        opacity: parseFloat('{{ \App\Helpers\SettingsHelper::get("pdf_watermark_opacity", "0.05") }}') || 0.05,
        rotation: parseInt('{{ \App\Helpers\SettingsHelper::get("pdf_watermark_rotation", "-30") }}') || -30,
    };
};

// Éditeur du barème d'assiduité à tranches configurables.
if (typeof window.attendanceBaremeEditor !== 'function') {
window.attendanceBaremeEditor = function () {
    return {
        zeroBonus: 0,
        unjustified: [],
        justified: [],
        simJust: 0,
        simNonJust: 0,
        scaleDefs: [
            { key: 'unjustified', title: 'Absences NON justifiées', icon: 'fa-user-times', sub: "Barème appliqué selon le total d'heures d'absence non justifiées." },
            { key: 'justified', title: 'Absences justifiées', icon: 'fa-user-clock', sub: "Barème additionnel selon les heures justifiées (laisser une seule tranche à 0 pour aucun effet)." },
        ],

        init(rule) {
            rule = rule || {};
            this.zeroBonus = Number(rule.zero_bonus ?? 0);
            this.unjustified = this.normalize(rule.unjustified);
            this.justified = this.normalize(rule.justified);
        },

        normalize(list) {
            if (!Array.isArray(list) || !list.length) {
                return [{ max: null, note: 0 }];
            }
            return list.map((b, i) => ({
                max: (i === list.length - 1) ? null : Number(b.max),
                note: Number(b.note ?? 0),
            }));
        },

        rows(scale) { return this[scale]; },

        fromHour(scale, idx) {
            if (idx === 0) return 0;
            const prev = this[scale][idx - 1];
            return (prev && prev.max != null) ? prev.max : 0;
        },

        addRow(scale) {
            const arr = this[scale];
            const last = arr[arr.length - 1];
            // La dernière (ouverte) devient bornée, on ajoute une nouvelle dernière ouverte.
            const base = this.fromHour(scale, arr.length - 1);
            if (last.max == null) { last.max = base + 1; }
            arr.push({ max: null, note: 0 });
        },

        removeRow(scale, idx) {
            const arr = this[scale];
            if (arr.length <= 1) return;
            arr.splice(idx, 1);
            // La nouvelle dernière tranche doit rester ouverte.
            arr[arr.length - 1].max = null;
        },

        build() {
            const scale = (arr) => {
                let min = 0;
                return arr.map((row, i) => {
                    const isLast = i === arr.length - 1;
                    const entry = { min: min, max: isLast ? null : Number(row.max), note: Number(row.note || 0) };
                    min = isLast ? min : Number(row.max);
                    return entry;
                });
            };
            return {
                zero_bonus: Number(this.zeroBonus || 0),
                unjustified: scale(this.unjustified),
                justified: scale(this.justified),
            };
        },

        serialize() { return JSON.stringify(this.build()); },

        noteFor(brackets, hours) {
            for (const b of brackets) {
                if (b.max == null || hours < b.max) return Number(b.note || 0);
            }
            return 0;
        },

        simulate() {
            const j = Math.max(0, Number(this.simJust) || 0);
            const nj = Math.max(0, Number(this.simNonJust) || 0);
            if (j + nj === 0) return Number(this.zeroBonus || 0);
            const rule = this.build();
            return this.noteFor(rule.unjustified, nj) + this.noteFor(rule.justified, j);
        },

        formatNote(v) {
            const n = Number(v || 0);
            return (n >= 0 ? '+' : '') + n.toFixed(2);
        },

        // Validation miroir (contiguïté + dernière ouverte + bornes) pour un feedback live.
        errorsList() {
            const errs = [];
            if (this.zeroBonus < -20 || this.zeroBonus > 20) errs.push('La note « aucune absence » doit être entre -20 et 20.');
            for (const def of this.scaleDefs) {
                const arr = this[def.key];
                arr.forEach((row, i) => {
                    const isLast = i === arr.length - 1;
                    if (!isLast) {
                        const from = this.fromHour(def.key, i);
                        if (row.max == null || Number(row.max) <= from) {
                            errs.push(`${def.title} : la tranche ${i + 1} doit finir après ${from}h.`);
                        }
                    }
                    if (row.note < -20 || row.note > 20) errs.push(`${def.title} : note de la tranche ${i + 1} hors bornes.`);
                });
            }
            return errs;
        },
    };
};
}
</script>
@endpush
