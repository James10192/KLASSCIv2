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
                </div>
            </div>

                </div>
                <!-- End Tab 1: Général -->

                <!-- Tab 2: Configuration PDF -->
                <div class="tab-pane fade" id="pdf" role="tabpanel">

            <!-- Section 2: Configuration PDF de Base -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Configuration PDF de Base</h3>
                        <p class="section-description">Paramètres généraux des bulletins PDF</p>
                    </div>
                </div>

                <div class="settings-grid-2">
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
                            <i class="fas fa-image text-primary"></i>
                            Afficher le logo
                        </label>
                        <label class="form-switch-modern">
                            <input type="checkbox" name="bulletin_show_logo" value="1" 
                                   {{ \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-image text-primary"></i>
                            Logo de l'établissement
                        </label>
                        <div class="file-upload-area">
                            @if(\App\Helpers\SettingsHelper::get('school_logo', ''))
                                <div class="current-logo mb-2">
                                    <img src="{{ asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo', '')) }}" 
                                         alt="Logo actuel" style="max-height: 80px;" class="img-thumbnail">
                                    <p class="text-muted small">Logo actuel</p>
                                </div>
                            @endif
                            <input type="file" class="form-control form-control-modern"
                                   name="setting_school_logo"
                                   accept="image/*"
                                   onchange="previewLogo(this)">
                            <small class="text-muted">Formats acceptés: PNG, JPG, GIF (max 2MB)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-university text-primary"></i>
                            Nom pour les bulletins
                        </label>
                        <input type="text" class="form-control form-control-modern"
                               name="bulletin_school_name_custom"
                               value="{{ \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', '') }}"
                               placeholder="Laissez vide pour utiliser le nom de l'établissement">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Ce nom apparaîtra sur les bulletins. Si vide, utilisera le nom de l'établissement défini ci-dessus.
                        </small>
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

            <!-- Section: Couleurs PDF -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon pdf">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Couleurs des PDFs</h3>
                        <p class="section-description">Palette appliquée à tous les documents PDF</p>
                    </div>
                </div>

                <div class="settings-grid-3">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-fill-drip text-primary"></i>
                            Couleur principale
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_primary_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-fill-drip text-secondary"></i>
                            Couleur secondaire
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_secondary_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_secondary_color', '#64748b') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-highlighter text-warning"></i>
                            Couleur d'accent
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_accent_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_accent_color', '#f59e0b') }}">
                    </div>
                </div>

                <div class="settings-grid-3">
                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-font text-muted"></i>
                            Couleur du texte
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_text_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_text_color', '#1f2937') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-square text-primary"></i>
                            Fond des en-têtes
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_header_bg_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_header_bg_color', '#0453cb') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">
                            <i class="fas fa-font text-light"></i>
                            Texte des en-têtes
                        </label>
                        <input type="color" class="form-control form-control-modern"
                               name="setting_pdf_header_text_color"
                               value="{{ \App\Helpers\SettingsHelper::get('pdf_header_text_color', '#ffffff') }}">
                    </div>
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

                <!-- Tab 4: Notifications et Rappels -->
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

// Fonction pour prévisualiser le logo avant upload
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Vérifier la taille (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('Le fichier est trop volumineux (max 2MB)');
            input.value = '';
            return;
        }
        
        // Créer une prévisualisation
        const reader = new FileReader();
        reader.onload = function(e) {
            // Trouver ou créer la div de prévisualisation
            let previewDiv = input.parentNode.querySelector('.logo-preview');
            if (!previewDiv) {
                previewDiv = document.createElement('div');
                previewDiv.className = 'logo-preview mt-2';
                input.parentNode.insertBefore(previewDiv, input.nextSibling);
            }
            
            previewDiv.innerHTML = `
                <div class="text-center">
                    <img src="${e.target.result}" alt="Aperçu" style="max-height: 80px;" class="img-thumbnail">
                    <p class="text-success small"><i class="fas fa-eye"></i> Aperçu - Cliquez sur Sauvegarder pour confirmer</p>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

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
