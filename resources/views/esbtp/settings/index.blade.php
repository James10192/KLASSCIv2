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

                </div>
                <!-- End Tab 2: Configuration PDF -->

                <!-- Tab 3: Configuration Bulletin -->
                <div class="tab-pane fade" id="bulletin" role="tabpanel">

            <!-- Section: En-tête du Bulletin -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon bulletin">
                        <i class="fas fa-heading"></i>
                    </div>
                    <div>
                        <h3 class="section-title">En-tête du Bulletin</h3>
                        <p class="section-description">Configuration des informations en haut du bulletin</p>
                    </div>
                </div>

                <div class="settings-grid-3">
                    <div class="form-group">
                        <label class="form-label-modern">Afficher en-tête</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_header" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_header', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Info République</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_republic_info" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Info Ministère</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_ministry_info" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Info École</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_school_info" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_school_info', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Info Cycle</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_cycle_info" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_cycle_info', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label-modern">Texte République</label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_republic_text"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire') }}"
                               placeholder="République de Côte d'Ivoire">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Devise Union</label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_union_text"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail') }}"
                               placeholder="Union-Discipline-Travail">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Texte Ministère</label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_ministry_text"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur') }}"
                               placeholder="Ministère de l'Enseignement Supérieur">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Texte Cycle</label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_cycle_text"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur') }}"
                               placeholder="Brevet de Technicien Supérieur">
                    </div>
                </div>
            </div>

            <!-- Section: Affichage du Contenu -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon display">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Affichage du Contenu</h3>
                        <p class="section-description">Contrôle de l'affichage des différentes sections</p>
                    </div>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="toggleSectionCheckboxes('content-section', true)">
                            <i class="fas fa-check-double"></i> Tout cocher
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleSectionCheckboxes('content-section', false)">
                            <i class="fas fa-times"></i> Tout décocher
                        </button>
                    </div>
                </div>

                <div class="settings-grid-3" id="content-section">
                    <div class="form-group">
                        <label class="form-label-modern">Info Étudiant</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_student_info" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_student_info', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Matricule</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_matricule" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_matricule', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Date Naissance</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_birth_date" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_birth_date', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Redoublant</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_redoublant" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_redoublant', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Tableau Matières</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_subjects_table" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_subjects_table', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Professeurs</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_teachers" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_teachers', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Absences</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_absences" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_absences', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Statistiques</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_statistics" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Signature</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_signature" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_signature', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Note d'assiduité</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_attendance_note" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Décision du conseil</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_council_decision" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_council_decision', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section: Statistiques de classe -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon stats">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Statistiques de Classe</h3>
                        <p class="section-description">Configuration de l'affichage des statistiques</p>
                    </div>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="toggleSectionCheckboxes('stats-section', true)">
                            <i class="fas fa-check-double"></i> Tout cocher
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleSectionCheckboxes('stats-section', false)">
                            <i class="fas fa-times"></i> Tout décocher
                        </button>
                    </div>
                </div>

                <div class="settings-grid-3" id="stats-section">
                    <div class="form-group">
                        <label class="form-label-modern">Plus forte moyenne</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_highest_average" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Plus faible moyenne</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_lowest_average" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Moyenne de classe</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_class_average" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section: Pondération des semestres -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon stats">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Pondération des semestres</h3>
                        <p class="section-description">Coefficients utilisés pour calculer la moyenne générale annuelle (M.G.A)</p>
                    </div>
                </div>

                <div class="settings-grid-2">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-scale-balanced text-primary"></i>
                            Coefficient Semestre 1
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="setting_bulletin_semester1_weight"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_semester1_weight', '1') }}"
                               min="0" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-scale-balanced text-success"></i>
                            Coefficient Semestre 2
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="setting_bulletin_semester2_weight"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_semester2_weight', '1') }}"
                               min="0" step="0.1">
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Exemple: S1 = 1, S2 = 2 ⟶ (S1*1 + S2*2) / 3. La somme est normalisee automatiquement.
                </small>
            </div>

            <!-- Section: Mentions et Seuils -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon mentions">
                        <i class="fas fa-medal"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Mentions et Seuils</h3>
                        <p class="section-description">Configuration des mentions automatiques</p>
                    </div>
                </div>

                <div class="settings-grid-3">
                    <div class="form-group">
                        <label class="form-label-modern">Calcul Auto</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_auto_calculate_mention" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Félicitations</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_felicitation" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_felicitation', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Encouragements</label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_encouragement" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_encouragement', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-star text-warning"></i>
                            Seuil Félicitations (/20)
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="bulletin_felicitation_threshold"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_felicitation_threshold', '16') }}"
                               min="0" max="20" step="0.5">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-thumbs-up text-success"></i>
                            Seuil Encouragements (/20)
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="bulletin_encouragement_threshold"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_encouragement_threshold', '14') }}"
                               min="0" max="20" step="0.5">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-award text-info"></i>
                            Seuil Tableau d'honneur (/20)
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="bulletin_honor_roll_threshold"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_honor_roll_threshold', '12') }}"
                               min="0" max="20" step="0.5">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            Seuil Avertissement (/20)
                        </label>
                        <input type="number" class="form-control form-control-modern threshold-input"
                               name="bulletin_work_warning_threshold"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_work_warning_threshold', '8') }}"
                               min="0" max="20" step="0.5">
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
</script>
@endpush
