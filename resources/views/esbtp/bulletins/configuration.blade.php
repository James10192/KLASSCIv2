@extends('layouts.app')

@section('title', 'Configuration des Bulletins - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .config-section {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .section-title {
        color: var(--primary);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-sm);
        border-bottom: 2px solid var(--accent-blue);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .form-check-input:checked {
        background-color: var(--success);
        border-color: var(--success);
    }
    
    .color-input {
        width: 50px;
        height: 40px;
        border: none;
        border-radius: var(--radius-medium);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-cogs me-2"></i>Configuration des Bulletins</h1>
                <p class="header-subtitle">Personnalisez l'apparence et le contenu des bulletins de notes</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux résultats
                </a>
            </div>
        </div>
        
        <!-- Messages Flash -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('esbtp.bulletins.save-configuration') }}">
            @csrf
            
            <!-- Informations de l'établissement -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-university"></i>
                    Informations de l'établissement
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="school_name" class="form-label">Nom de l'école</label>
                            <input type="text" class="form-control" id="school_name" name="school_name" 
                                   value="{{ $settings['school_name'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="bulletin_school_name_custom" class="form-label">Nom personnalisé pour bulletin</label>
                            <input type="text" class="form-control" id="bulletin_school_name_custom" name="bulletin_school_name_custom" 
                                   value="{{ $settings['bulletin_school_name_custom'] ?? '' }}"
                                   placeholder="Laissez vide pour utiliser le nom par défaut">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label for="school_address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="school_address" name="school_address" 
                                   value="{{ $settings['school_address'] ?? '' }}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="school_phone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="school_phone" name="school_phone" 
                                   value="{{ $settings['school_phone'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="school_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="school_email" name="school_email" 
                                   value="{{ $settings['school_email'] ?? '' }}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="director_name" class="form-label">Nom du directeur</label>
                            <input type="text" class="form-control" id="director_name" name="director_name" 
                                   value="{{ $settings['director_name'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="director_title" class="form-label">Titre du directeur</label>
                            <input type="text" class="form-control" id="director_title" name="director_title" 
                                   value="{{ $settings['director_title'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- En-tête et informations officielles -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-flag"></i>
                    En-tête et informations officielles
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_header" name="bulletin_show_header" value="1" 
                                   {{ ($settings['bulletin_show_header'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_header">
                                Afficher l'en-tête complet
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_logo" name="bulletin_show_logo" value="1" 
                                   {{ ($settings['bulletin_show_logo'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_logo">
                                Afficher le logo
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_republic_info" name="bulletin_show_republic_info" value="1" 
                                   {{ ($settings['bulletin_show_republic_info'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_republic_info">
                                Afficher les informations de la République
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_ministry_info" name="bulletin_show_ministry_info" value="1" 
                                   {{ ($settings['bulletin_show_ministry_info'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_ministry_info">
                                Afficher les informations du ministère
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="bulletin_republic_text" class="form-label">Texte République</label>
                            <input type="text" class="form-control" id="bulletin_republic_text" name="bulletin_republic_text" 
                                   value="{{ $settings['bulletin_republic_text'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="bulletin_union_text" class="form-label">Devise nationale</label>
                            <input type="text" class="form-control" id="bulletin_union_text" name="bulletin_union_text" 
                                   value="{{ $settings['bulletin_union_text'] ?? '' }}">
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="bulletin_ministry_text" class="form-label">Texte Ministère</label>
                    <input type="text" class="form-control" id="bulletin_ministry_text" name="bulletin_ministry_text" 
                           value="{{ $settings['bulletin_ministry_text'] ?? '' }}">
                </div>
            </div>

            <!-- Informations du cycle et de la formation -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-graduation-cap"></i>
                    Cycle et formation
                </h3>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="bulletin_show_cycle_info" name="bulletin_show_cycle_info" value="1" 
                           {{ ($settings['bulletin_show_cycle_info'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulletin_show_cycle_info">
                        Afficher les informations du cycle
                    </label>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label for="bulletin_cycle_text" class="form-label">Nom du cycle</label>
                            <input type="text" class="form-control" id="bulletin_cycle_text" name="bulletin_cycle_text" 
                                   value="{{ $settings['bulletin_cycle_text'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="bulletin_cycle_abbreviation" class="form-label">Abréviation</label>
                            <input type="text" class="form-control" id="bulletin_cycle_abbreviation" name="bulletin_cycle_abbreviation" 
                                   value="{{ $settings['bulletin_cycle_abbreviation'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des matières -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-table"></i>
                    Tableau des matières et notes
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subjects_table" name="bulletin_show_subjects_table" value="1" 
                                   {{ ($settings['bulletin_show_subjects_table'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_subjects_table">
                                Afficher le tableau des matières
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subject_average" name="bulletin_show_subject_average" value="1" 
                                   {{ ($settings['bulletin_show_subject_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_subject_average">
                                Afficher les moyennes par matière
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_coefficient" name="bulletin_show_coefficient" value="1" 
                                   {{ ($settings['bulletin_show_coefficient'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_coefficient">
                                Afficher les coefficients
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_teachers" name="bulletin_show_teachers" value="1" 
                                   {{ ($settings['bulletin_show_teachers'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_teachers">
                                Afficher les professeurs
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="bulletin_show_appreciations" name="bulletin_show_appreciations" value="1" 
                           {{ ($settings['bulletin_show_appreciations'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulletin_show_appreciations">
                        Afficher les appréciations
                    </label>
                </div>
            </div>

            <!-- Moyennes et statistiques -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Moyennes et statistiques
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_general_average" name="bulletin_show_general_average" value="1" 
                                   {{ ($settings['bulletin_show_general_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_general_average">
                                Afficher la moyenne générale
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_technical_average" name="bulletin_show_technical_average" value="1" 
                                   {{ ($settings['bulletin_show_technical_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_technical_average">
                                Afficher la moyenne technique
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_rank" name="bulletin_show_class_rank" value="1" 
                                   {{ ($settings['bulletin_show_class_rank'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_class_rank">
                                Afficher le rang de classe
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_size" name="bulletin_show_class_size" value="1" 
                                   {{ ($settings['bulletin_show_class_size'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_class_size">
                                Afficher l'effectif de classe
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance" name="bulletin_show_attendance" value="1" 
                                   {{ ($settings['bulletin_show_attendance'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_attendance">
                                Afficher les informations d'assiduité
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance_note" name="bulletin_show_attendance_note" value="1" 
                                   {{ ($settings['bulletin_show_attendance_note'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_attendance_note">
                                Afficher la note d'assiduité (bonus/malus)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_highest_average" name="bulletin_show_highest_average" value="1" 
                                   {{ ($settings['bulletin_show_highest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_highest_average">
                                Afficher la plus forte moyenne
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_lowest_average" name="bulletin_show_lowest_average" value="1" 
                                   {{ ($settings['bulletin_show_lowest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_lowest_average">
                                Afficher la plus faible moyenne
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_average" name="bulletin_show_class_average" value="1" 
                                   {{ ($settings['bulletin_show_class_average'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_class_average">
                                Afficher la moyenne de classe
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="bulletin_show_council_decision" name="bulletin_show_council_decision" value="1" 
                           {{ ($settings['bulletin_show_council_decision'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulletin_show_council_decision">
                        Afficher la décision du conseil de classe
                    </label>
                </div>
            </div>

            <!-- Signatures -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-signature"></i>
                    Signatures et validation
                </h3>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="bulletin_show_signatures" name="bulletin_show_signatures" value="1" 
                           {{ ($settings['bulletin_show_signatures'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulletin_show_signatures">
                        Afficher la section signatures
                    </label>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="bulletin_show_director_signature" name="bulletin_show_director_signature" value="1" 
                                   {{ ($settings['bulletin_show_director_signature'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_show_director_signature">
                                Signature du directeur
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Apparence et style -->
            <div class="config-section">
                <h3 class="section-title">
                    <i class="fas fa-palette"></i>
                    Apparence et style
                </h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="bulletin_font_size" class="form-label">Taille de police</label>
                            <select class="form-control" id="bulletin_font_size" name="bulletin_font_size">
                                <option value="9" {{ ($settings['bulletin_font_size'] ?? '11') == '9' ? 'selected' : '' }}>9pt</option>
                                <option value="10" {{ ($settings['bulletin_font_size'] ?? '11') == '10' ? 'selected' : '' }}>10pt</option>
                                <option value="11" {{ ($settings['bulletin_font_size'] ?? '11') == '11' ? 'selected' : '' }}>11pt</option>
                                <option value="12" {{ ($settings['bulletin_font_size'] ?? '11') == '12' ? 'selected' : '' }}>12pt</option>
                                <option value="13" {{ ($settings['bulletin_font_size'] ?? '11') == '13' ? 'selected' : '' }}>13pt</option>
                                <option value="14" {{ ($settings['bulletin_font_size'] ?? '11') == '14' ? 'selected' : '' }}>14pt</option>
                            </select>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Boutons d'action -->
            <div class="d-flex justify-content-end gap-3">
                <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-times"></i>Annuler
                </a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save"></i>Sauvegarder la configuration
                </button>
            </div>
        </form>
    </div>
</div>
@endsection