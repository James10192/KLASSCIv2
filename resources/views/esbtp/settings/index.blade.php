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
    
    .section-icon.school { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
    .section-icon.pdf { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .section-icon.bulletin { background: linear-gradient(135deg, #f39c12, #e67e22); }
    .section-icon.display { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
    .section-icon.mentions { background: linear-gradient(135deg, #1abc9c, #16a085); }
    .section-icon.stats { background: linear-gradient(135deg, #34495e, #2c3e50); }

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

    .section-icon.notifications { background: linear-gradient(135deg, #3498db, #2980b9); }
    .section-icon.conduite { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .section-icon.ponderation { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .section-icon.tronc { background: linear-gradient(135deg, #7c3aed, #6d28d9); }

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
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
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
                <button class="nav-link" id="lmd-tab" data-bs-toggle="tab" data-bs-target="#lmd" type="button" role="tab">
                    <i class="fas fa-graduation-cap"></i> Système LMD
                </button>
            </li>
        </ul>

        <form action="{{ route('esbtp.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                        <input type="text" class="form-control form-control-modern @error('setting_school_name') is-invalid @enderror"
                               name="setting_school_name"
                               value="{{ old('setting_school_name', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI')) }}"
                               placeholder="Ex: École Spéciale du Bâtiment et des Travaux Publics"
                               required>
                        @error('setting_school_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            Adresse
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_school_address') is-invalid @enderror"
                               name="setting_school_address"
                               value="{{ old('setting_school_address', \App\Helpers\SettingsHelper::get('school_address', '')) }}"
                               placeholder="Ex: BP 04 BP 1234 Abidjan 04">
                        @error('setting_school_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-phone text-primary"></i>
                            Téléphone
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_school_phone') is-invalid @enderror"
                               name="setting_school_phone"
                               value="{{ old('setting_school_phone', \App\Helpers\SettingsHelper::get('school_phone', '')) }}"
                               placeholder="Ex: +225 00 00 00 00">
                        @error('setting_school_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-envelope text-primary"></i>
                            Email
                        </label>
                        <input type="email" class="form-control form-control-modern @error('setting_school_email') is-invalid @enderror"
                               name="setting_school_email"
                               value="{{ old('setting_school_email', \App\Helpers\SettingsHelper::get('school_email', '')) }}"
                               placeholder="Ex: contact@esbtp-yakro.com">
                        @error('setting_school_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-city text-primary"></i>
                            Ville
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_school_city') is-invalid @enderror"
                               name="setting_school_city"
                               value="{{ old('setting_school_city', \App\Helpers\SettingsHelper::get('school_city', '')) }}"
                               placeholder="Ex: Yamoussoukro">
                        @error('setting_school_city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-flag text-primary"></i>
                            Pays
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_school_country') is-invalid @enderror"
                               name="setting_school_country"
                               value="{{ old('setting_school_country', \App\Helpers\SettingsHelper::get('school_country', '')) }}"
                               placeholder="Ex: Côte d'Ivoire">
                        @error('setting_school_country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-user-tie text-primary"></i>
                            Nom du directeur
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_director_name') is-invalid @enderror"
                               name="setting_director_name"
                               value="{{ old('setting_director_name', \App\Helpers\SettingsHelper::get('director_name', '')) }}"
                               placeholder="Ex: N'GUESSAN Marcel">
                        @error('setting_director_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-briefcase text-primary"></i>
                            Titre du directeur
                        </label>
                        <input type="text" class="form-control form-control-modern @error('setting_director_title') is-invalid @enderror"
                               name="setting_director_title"
                               value="{{ old('setting_director_title', \App\Helpers\SettingsHelper::get('director_title', 'Directeur Général')) }}"
                               placeholder="Ex: Directeur Général">
                        @error('setting_director_title')
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
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_font_size', '11') }}"
                               min="8" max="16" step="1">
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
                                <div class="pdf-picker-desc">Nom de l'école, adresse, téléphone affichés sur la bannière colorée</div>
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
                                <div class="pdf-picker-label">Couleur d'accent — titres & soulignements</div>
                                <div class="pdf-picker-desc">Titre du document (ex: « CERTIFICAT DE SCOLARITÉ »), en-têtes de tableaux, séparateurs colorés</div>
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
                            <div class="prev-table-head" style="background:{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}; color:{{ \App\Helpers\SettingsHelper::get('pdf_header_text_color', '#ffffff') }}">
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
            <div class="settings-section">
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
                    Exemple : S1 = 1, S2 = 2 -- (S1*1 + S2*2) / 3. La somme est normalisee automatiquement.
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
                    <strong><i class="fas fa-book"></i> Appreciations des notes :</strong>
                    <ul>
                        <li>00/20 = Nul -- 01-06 = Mediocre -- 07-09.98 = Insuffisant -- 9.99-11.99 = Passable</li>
                        <li>12-13.99 = Assez-bien -- 14-15.99 = Bien -- 16-17.99 = Tres Bien -- 18-20 = Excellent</li>
                    </ul>
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

                <div class="bc-grid bc-grid-3">
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-calculator"></i></div>
                        <div class="bc-body"><div class="bc-label">Calcul Auto</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_auto_calculate_mention" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-trophy"></i></div>
                        <div class="bc-body"><div class="bc-label">Felicitations</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_felicitation" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_felicitation', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-icon"><i class="fas fa-thumbs-up"></i></div>
                        <div class="bc-body"><div class="bc-label">Encouragements</div></div>
                        <div class="bc-toggle">
                            <label class="form-switch-modern">
                                <input type="checkbox" name="bulletin_show_encouragement" value="1"
                                       {{ \App\Helpers\SettingsHelper::get('bulletin_show_encouragement', '1') == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bc-grid bc-grid-2" style="margin-top: 12px;">
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-star"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Seuil Felicitations (/20)</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="bulletin_felicitation_threshold"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_felicitation_threshold', '16') }}"
                                   min="0" max="20" step="0.5">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-thumbs-up"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Seuil Encouragements (/20)</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="bulletin_encouragement_threshold"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_encouragement_threshold', '14') }}"
                                   min="0" max="20" step="0.5">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-award"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Seuil Tableau d'honneur (/20)</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="bulletin_honor_roll_threshold"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_honor_roll_threshold', '12') }}"
                                   min="0" max="20" step="0.5">
                        </div>
                    </div>
                    <div class="bc-input-row">
                        <div class="bc-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="bc-body">
                            <div class="bc-label">Seuil Avertissement (/20)</div>
                            <input type="number" class="form-control form-control-modern" style="max-width: 100px;"
                                   name="bulletin_work_warning_threshold"
                                   value="{{ \App\Helpers\SettingsHelper::get('bulletin_work_warning_threshold', '8') }}"
                                   min="0" max="20" step="0.5">
                        </div>
                    </div>
                </div>
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
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Crédits par semestre</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_per_semester"
                                           value="{{ $lmdVal('lmd_credits_per_semester', 30) }}" min="1" max="60">
                                    <div class="ls-hint">Standard UEMOA : 30</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Total Licence</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_licence_total"
                                           value="{{ $lmdVal('lmd_credits_licence_total', 180) }}" min="1">
                                    <div class="ls-hint">6 semestres x 30 = 180</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Total Master</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_master_total"
                                           value="{{ $lmdVal('lmd_credits_master_total', 120) }}" min="1">
                                    <div class="ls-hint">4 semestres x 30 = 120</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ls-field">
                                    <div class="ls-label">Total Doctorat</div>
                                    <input type="number" class="ls-input" name="setting_lmd_credits_doctorat_total"
                                           value="{{ $lmdVal('lmd_credits_doctorat_total', 180) }}" min="1">
                                    <div class="ls-hint">6 semestres x 30 = 180</div>
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
                            <span><strong>Note :</strong> CC + Examen doivent totaliser 100%. Si vous modifiez l'un, ajustez l'autre.</span>
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
                    <div class="ls-section ls-section--deliberation">
                        <div class="ls-head">
                            <div class="ls-icon ls-icon--deliberation"><i class="fas fa-gavel"></i></div>
                            <div class="ls-title">Décisions de Délibération</div>
                        </div>
                        <div class="ls-desc">
                            Liste des décisions possibles lors du conseil de délibération. Séparez chaque décision par une virgule.
                        </div>
                        @php
                            $decisions = json_decode($lmdVal('lmd_deliberation_decisions', '[]'), true) ?? [];
                            $decisionsText = implode(', ', $decisions);
                        @endphp
                        <textarea class="ls-input" name="setting_lmd_deliberation_decisions" rows="3"
                                  placeholder="Félicitations du jury, Tableau d'honneur, Encouragement, Passage, Ajourné(e), Exclusion"
                                  style="resize:vertical;">{{ $decisionsText }}</textarea>
                        <div class="ls-hint">
                            Séparez par des virgules. Ces décisions apparaîtront dans le menu déroulant lors de la génération des bulletins.
                        </div>
                    </div>

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
                                           value="{{ $lmdVal('lmd_bulletin_label_domaine', 'DOMAINE') }}" placeholder="DOMAINE">
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
                                           value="{{ $lmdVal('lmd_bulletin_label_mention', 'MENTION') }}" placeholder="MENTION">
                                </div>
                            </div>

                            {{-- Spécialité --}}
                            <div class="col-md-6">
                                <label class="ls-toggle" for="lmd_bulletin_show_specialite">
                                    <div class="ls-toggle-text">
                                        <div class="ls-toggle-label">Spécialité</div>
                                        <div class="ls-toggle-hint">Optionnel — niveau intermédiaire entre Mention et Parcours</div>
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
                                           value="{{ $lmdVal('lmd_bulletin_label_parcours', 'PARCOURS') }}" placeholder="PARCOURS">
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

            </div>
            <!-- End Tab Content -->

            <!-- Bouton de sauvegarde -->
            <div class="text-center mt-xl">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save me-2"></i>
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
    if (tableHead) { tableHead.style.background = acc; tableHead.style.color = txt; }
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
</script>
@endpush
