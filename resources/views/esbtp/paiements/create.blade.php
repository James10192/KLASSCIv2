@extends('layouts.app')

@section('title', 'Nouveau Paiement - KLASSCI')

@section('styles')
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
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.15rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 8px 30px rgba(4, 83, 203, 0.18), 0 2px 8px rgba(15, 23, 42, 0.08);
    }

    .student-progress-card h5,
    .student-progress-card small,
    .student-progress-card .text-muted {
        color: #fff !important;
    }

    .student-progress-card #total-progress {
        background: rgba(255, 255, 255, 0.16) !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .category-progress {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 0.8rem;
        margin-bottom: 0.6rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        transition: all 0.2s ease;
    }

    .category-progress:hover {
        background: rgba(255, 255, 255, 0.16);
    }

    .progress-bar-modern {
        height: 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: inherit;
        transition: width 0.25s ease;
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

    .pc-select-field .au-select {
        width: 100%;
        max-width: 100%;
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
</style>
@endsection

@section('content')
@php
    $studentOptions = \App\Models\ESBTPEtudiant::with('user')
        ->limit(10)
        ->get()
        ->mapWithKeys(function ($student) {
            $label = ($student->matricule ?? 'N/A') . ' - ' . ($student->user->name ?? $student->nom_complet ?? 'N/A');

            return [(string) $student->id => $label];
        })
        ->all();

    $modeOptions = [
        'Espèces' => 'Espèces',
        'Chèque' => 'Chèque',
        'Virement' => 'Virement bancaire',
        'Mobile Money' => 'Mobile Money',
        'Carte bancaire' => 'Carte bancaire',
    ];

    $trancheOptions = [
        'Première tranche' => 'Première tranche',
        'Deuxième tranche' => 'Deuxième tranche',
        'Troisième tranche' => 'Troisième tranche',
        'Paiement intégral' => 'Paiement intégral',
    ];
@endphp
<div class="dashboard-acasi pc-page">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <div class="pc-header-shell">
                    <div class="pc-header-icon"><i class="fas fa-money-check-dollar"></i></div>
                    <div>
                        <h1>Nouveau paiement</h1>
                        <p class="header-subtitle">Encaissement guidé et sécurisé avec suivi en temps réel</p>
                        <div class="pc-header-meta">
                            <span class="pc-header-pill"><i class="fas fa-shield-check"></i> Anti-erreur actif</span>
                            <span class="pc-header-pill"><i class="fas fa-bolt"></i> Flux rapide caissier</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
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
            
            <!-- Sélection de l'étudiant -->
            <div class="card-moderne payment-form-card mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-user-graduate me-2"></i>
                        Sélection de l'Étudiant
                    </div>
                    
                    @if($etudiant)
                        <div class="d-flex align-items-center p-3 bg-light rounded-3">
                            <div class="avatar-circle bg-primary me-3" style="width: 60px; height: 60px; font-size: 24px;">
                                {{ substr($etudiant->user->name ?? $etudiant->nom_complet ?? 'NN', 0, 2) }}
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $etudiant->user->name ?? $etudiant->nom_complet ?? 'N/A' }}</h5>
                                <p class="mb-1 text-muted">{{ $etudiant->matricule }}</p>
                                <small class="text-muted">{{ $etudiant->user->email ?? 'N/A' }}</small>
                            </div>
                            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                        </div>
                    @else
                        <div class="form-floating-modern pc-select-field">
                            <label for="etudiant_id" class="pc-field-label">Étudiant <span class="text-danger">*</span></label>
                            <x-au-select
                                id="etudiant_id"
                                name="etudiant_id"
                                :value="(string) old('etudiant_id', request('etudiant_id', ''))"
                                :options="$studentOptions"
                                placeholder="Rechercher et sélectionner un étudiant"
                                icon="fa-user-graduate"
                                required
                                searchable />
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Barre de progression et inscription -->
            <div id="student-progress-section" style="display: none;">
                <!-- Barre de progression des frais -->
                <div class="student-progress-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progression des Paiements</h5>
                        <span class="badge bg-white text-dark" id="total-progress">0% payé</span>
                    </div>
                    
                    <div id="categories-progress">
                        <!-- Les catégories seront chargées dynamiquement -->
                    </div>
                </div>
                
                <!-- Informations de l'inscription -->
                <div class="card-moderne payment-form-card mb-lg">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Informations de l'Inscription
                        </div>
                        
                        @if($inscription)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-floating-modern">
                                        <input type="text" class="form-control" value="{{ $inscription->filiere->name ?? 'N/A' }}" readonly>
                                        <label>Filière</label>
                                        <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating-modern">
                                        <input type="text" class="form-control" value="{{ $inscription->niveauEtude->name }}" readonly>
                                        <label>Niveau d'études</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating-modern">
                                        <input type="text" class="form-control" value="{{ $inscription->anneeUniversitaire->libelle }}" readonly>
                                        <label>Année universitaire</label>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="form-floating-modern pc-select-field">
                                <label for="inscription_id" class="pc-field-label">Inscription <span class="text-danger">*</span></label>
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
                    </div>
                </div>
            </div>
            
            <!-- Sélection de la catégorie de frais -->
            <div id="category-selection-section" style="display: none;">
                <div class="card-moderne payment-form-card mb-lg">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-tags me-2"></i>
                            Sélection de la Catégorie de Frais
                        </div>
                        
                        <div class="category-selection" id="category-options">
                            <!-- Les catégories seront chargées dynamiquement -->
                        </div>
                        
                        <input type="hidden" name="frais_category_id" id="selected_category_id" value="{{ old('frais_category_id') }}">
                    </div>
                </div>
            </div>
            
            <!-- Informations du paiement -->
            <div id="payment-details-section" style="display: none;">
                <div class="card-moderne payment-form-card mb-lg">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-money-check-alt me-2"></i>
                            Détails du Paiement
                        </div>
                        
                        <div class="row" x-data="{
                                montant: {{ (int) old('montant', 0) }},
                                threshold: {{ (int) ($unusualAmountThreshold ?? 500000) }},
                                confirmed: false,
                                get isUnusual() { return this.montant > this.threshold; },
                                get formattedThreshold() { return new Intl.NumberFormat('fr-FR').format(this.threshold); },
                                get formattedMontant() { return new Intl.NumberFormat('fr-FR').format(this.montant); },
                            }">
                            <div class="col-md-6">
                                <div class="form-floating-modern">
                                    <div class="amount-input-group">
                                        <input type="number" name="montant" id="montant" class="form-control" min="0" step="1"
                                               value="{{ old('montant') }}" required
                                               x-on:input="montant = parseInt($event.target.value || 0); confirmed = false"
                                               :style="isUnusual ? 'border-color:#f59e0b;background:#fffbeb;' : ''">
                                        <span class="fcfa-suffix position-absolute end-0 top-50 translate-middle-y me-3 text-muted">FCFA</span>
                                    </div>
                                    <label>Montant <span class="text-danger">*</span></label>
                                    <div class="amount-suggestions" id="amount-suggestions">
                                        <!-- Les suggestions de montant seront générées dynamiquement -->
                                    </div>

                                    {{-- Garde-fou montant inhabituel (QW3) --}}
                                    <div x-show="isUnusual" x-cloak x-transition.opacity
                                         class="qw3-unusual-alert" style="margin-top:12px;padding:12px 14px;background:#fffbeb;border:1.5px solid #f59e0b;border-radius:10px;">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <i class="fas fa-triangle-exclamation" style="color:#d97706;font-size:1.1rem;margin-top:2px;flex-shrink:0;"></i>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-weight:700;color:#92400e;font-size:.88rem;margin-bottom:4px;">
                                                    Montant inhabituel — vérifiez avant de valider
                                                </div>
                                                <div style="font-size:.82rem;color:#7c2d12;line-height:1.5;">
                                                    Le montant saisi (<strong x-text="formattedMontant + ' FCFA'"></strong>) dépasse le seuil habituel de <strong x-text="formattedThreshold + ' FCFA'"></strong> configuré pour cette école.
                                                    Vérifiez qu'il ne s'agit pas d'une erreur de frappe (ex: 50&nbsp;000 au lieu de 5&nbsp;000).
                                                </div>
                                                <label class="form-check" style="margin-top:8px;display:flex;gap:8px;align-items:center;cursor:pointer;">
                                                    <input type="checkbox" name="confirmed_unusual_amount" value="1"
                                                           x-model="confirmed" class="form-check-input" style="margin-top:0;">
                                                    <span style="font-size:.84rem;color:#92400e;font-weight:600;">
                                                        Je confirme que ce montant est correct
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating-modern">
                                    <input type="date" name="date_paiement" id="date_paiement" class="form-control" value="{{ old('date_paiement', date('Y-m-d')) }}" required>
                                    <label>Date de paiement <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating-modern pc-select-field">
                                    <label for="mode_paiement" class="pc-field-label">Mode de paiement <span class="text-danger">*</span></label>
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
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating-modern">
                                    <input type="text" name="reference_paiement" id="reference_paiement" class="form-control" value="{{ old('reference_paiement') }}" placeholder="N° de chèque, transaction, etc.">
                                    <label>Référence du paiement</label>
                                    <small class="form-text text-muted">Numéro de chèque, référence de transaction, etc.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating-modern pc-select-field">
                                    <label for="tranche" class="pc-field-label">Tranche de paiement</label>
                                    <x-au-select
                                        id="tranche"
                                        name="tranche"
                                        :value="(string) old('tranche', '')"
                                        :options="$trancheOptions"
                                        placeholder="Sélectionner une tranche"
                                        icon="fa-list-check" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating-modern">
                                    <textarea name="commentaire" id="commentaire" class="form-control" rows="3" style="height: auto; min-height: 60px;">{{ old('commentaire') }}</textarea>
                                    <label>Commentaire</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="text-center mb-lg" id="submit-section" style="display: none;">
                <button type="submit" class="btn-acasi primary large">
                    <i class="fas fa-save me-2"></i>Enregistrer le Paiement
                </button>
                <button type="button" class="btn-acasi secondary large ms-3" onclick="window.history.back()">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    debugLog('=== SCRIPT PRINCIPAL CHARGÉ ===');
    
    let currentStudent = null;
    let currentInscription = null;
    let studentBalance = null;
    let categories = [];
    let selectedCategory = null;
    
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

                var $inscriptionSelect = $('#inscription_id');
                $inscriptionSelect.empty();

                var placeholderOption = new Option('Sélectionner une inscription', '');
                placeholderOption.setAttribute('data-placeholder', '1');
                $inscriptionSelect.append(placeholderOption);

                var selectedInscriptionId = '';
                var noticeType = null;
                var noticeMessage = null;

                if (inscriptions.length === 0) {
                    $inscriptionSelect.append(new Option('Aucune inscription trouvée pour cet étudiant', ''));
                    noticeType = 'warning';
                    noticeMessage = 'Aucune inscription disponible pour cet étudiant.';
                } else {
                    $.each(inscriptions, function(index, inscription) {
                        if (!inscription || !inscription.id) {
                            return;
                        }

                        var label = (inscription.filiere || 'Filière non définie') + ' - ' +
                            (inscription.niveau || 'Niveau non défini') +
                            ' (' + (inscription.annee || 'Année non définie') + ')';

                        $inscriptionSelect.append(new Option(label, String(inscription.id)));
                    });

                    var preferredInscriptionId = @json($inscription->id ?? null);
                    var preferredExists = preferredInscriptionId && inscriptions.some(function(inscription) {
                        return String(inscription.id) === String(preferredInscriptionId);
                    });

                    if (preferredExists) {
                        selectedInscriptionId = String(preferredInscriptionId);
                        noticeType = 'success';
                        noticeMessage = 'Inscription chargée automatiquement depuis le contexte courant.';
                    } else {
                        var currentYearInscription = inscriptions.find(function(inscription) {
                            return Boolean(inscription && inscription.is_current_year);
                        });

                        if (currentYearInscription && currentYearInscription.id) {
                            selectedInscriptionId = String(currentYearInscription.id);
                            noticeType = 'info';
                            noticeMessage = 'Inscription de l\'année courante sélectionnée automatiquement.';
                        } else if (inscriptions[0] && inscriptions[0].id) {
                            selectedInscriptionId = String(inscriptions[0].id);
                            noticeType = 'warning';
                            noticeMessage = 'Aucune inscription de l\'année courante trouvée. La plus récente a été sélectionnée automatiquement.';
                        }
                    }
                }

                $inscriptionSelect.val(selectedInscriptionId);
                currentInscription = selectedInscriptionId || null;

                if (noticeMessage) {
                    showInscriptionNotice(noticeType, noticeMessage);
                } else {
                    hideInscriptionNotice();
                }

                debugLog('Select #inscription_id mis à jour');
                debugLog('Nouvelles options dans le select:', $inscriptionSelect.find('option').length);

                // Important: force le composant premium à relire les options dynamiques.
                $inscriptionSelect.trigger('input');

                if (selectedInscriptionId) {
                    $inscriptionSelect.trigger('change');
                } else {
                    resetProgressDisplay();
                    $('#student-progress-section').hide();
                    $('#category-selection-section').hide();
                    $('#payment-details-section').hide();
                    $('#submit-section').hide();
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur chargement inscriptions:', {status, error, response: xhr.responseText});
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
                displayCategories(categoriesData);

                if (categoriesData.length > 0) {
                    $('#category-selection-section').fadeIn();
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
    function displayCategories(categories) {
        debugLog('=== Affichage des catégories:', categories);
        
        var html = '';
        categories.forEach(function(category) {
            var progress = calculateCategoryProgress(category);
            var icon = getCategoryIcon(category.type);
            
            var configuredBadge = category.configured ? 
                '<span class="badge bg-success text-white small ms-2"><i class="fas fa-check"></i> Configuré</span>' : 
                '<span class="badge bg-secondary text-white small ms-2"><i class="fas fa-cog"></i> Défaut</span>';
            
            html += `
                <div class="category-option" data-category-id="${category.id}" data-category="${JSON.stringify(category).replace(/"/g, '&quot;')}">
                    <div class="category-icon bg-primary text-white">
                        <i class="${icon}"></i>
                    </div>
                    <h6 class="mb-1">${category.name}${configuredBadge}</h6>
                    <p class="text-muted small mb-2">${category.description || 'Frais scolaires'}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark">${formatAmount(category.montant)} FCFA</span>
                        <span class="text-primary small">${progress.percentage}% payé</span>
                    </div>
                    <div class="progress-bar-modern mt-2">
                        <div class="progress-fill bg-primary" style="width: ${progress.percentage}%"></div>
                    </div>
                </div>
            `;
        });
        
        $('#category-options').html(html);
        
        // Ajouter les événements de clic
        $('.category-option').on('click', function() {
            selectCategory($(this));
        });

        // Pré-sélection intelligente pour accélérer le flux d'encaissement.
        const $allOptions = $('#category-options .category-option');
        if ($allOptions.length > 0) {
            const previouslySelectedId = String($('#selected_category_id').val() || '');
            let $target = previouslySelectedId
                ? $allOptions.filter('[data-category-id="' + previouslySelectedId + '"]').first()
                : $();

            if ($target.length === 0) {
                $target = $allOptions.first();
            }

            selectCategory($target);
        }
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
    }

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
        var paid = getPaidAmountForCategory(category.id);
        var remaining = Math.max(0, total - paid);
        
        // Suggestions intelligentes
        if (remaining > 0) {
            suggestions.push({
                label: "Solde restant",
                amount: remaining
            });
            
            if (remaining >= 50000) {
                suggestions.push({
                    label: "50% du solde",
                    amount: Math.floor(remaining * 0.5)
                });
                suggestions.push({
                    label: "Tranche 25,000",
                    amount: 25000
                });
            }
            
            if (remaining >= 100000) {
                suggestions.push({
                    label: "Tranche 50,000",
                    amount: 50000
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
                    ${suggestion.label}: ${formatAmount(suggestion.amount)} FCFA
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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">${categoryBalance.name || 'Catégorie ' + categoryId}</h6>
                        <span class="badge bg-white bg-opacity-20">${percentage}%</span>
                    </div>
                    <div class="progress-bar-modern">
                        <div class="progress-fill" style="width: ${percentage}%; background: ${getProgressColor(percentage)}"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small>${formatAmount(paid)} FCFA payé</small>
                        <small>${formatAmount(remaining)} FCFA restant</small>
                    </div>
                </div>
            `;
        });
        
        $('#categories-progress').html(html);
        
        // Mettre à jour le progrès total
        var totalPercentage = totalDue > 0 ? Math.round((totalPaid / totalDue) * 100) : 0;
        $('#total-progress').text(totalPercentage + '% payé');
    }

    function resetProgressDisplay() {
        $('#categories-progress').html('');
        $('#total-progress').text('0% payé');
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
        if (percentage >= 80) return 'linear-gradient(90deg, #10b981, #059669)';
        if (percentage >= 50) return 'linear-gradient(90deg, #f59e0b, #d97706)';
        return 'linear-gradient(90deg, #ef4444, #dc2626)';
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
    }

    // ========================================
    // PROTECTION CONTRE LES DOUBLE-CLICS
    // ========================================
    let isSubmitting = false;
    let originalButtonText = '';

    // Handler sur le BOUTON SUBMIT - se déclenche IMMÉDIATEMENT au clic
    $('#payment-form').off('click', 'button[type="submit"]').on('click', 'button[type="submit"]', function(e) {
        const $submitBtn = $(this);

        // Si déjà en cours de soumission, bloquer immédiatement
        if (isSubmitting) {
            e.preventDefault();
            e.stopImmediatePropagation();
            debugWarn('⚠️ Clic bloqué, soumission déjà en cours');
            return false;
        }

        // QW3 : Garde-fou montant inhabituel — bloquer si > seuil sans confirmation cochée
        const montantVal = parseInt($('#montant').val() || 0);
        const threshold = parseInt(@json((int) ($unusualAmountThreshold ?? 500000)));
        const $confirmCheckbox = $('input[name="confirmed_unusual_amount"]');
        if (montantVal > threshold && $confirmCheckbox.length > 0 && !$confirmCheckbox.is(':checked')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            // Scroll smooth vers le warning + focus checkbox pour faciliter
            const $alert = $('.qw3-unusual-alert');
            if ($alert.length) {
                $alert[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => $confirmCheckbox.trigger('focus'), 400);
            }
            return false;
        }

        // Marquer comme en cours de soumission IMMÉDIATEMENT
        isSubmitting = true;
        debugLog('🔒 Bouton cliqué, verrouillage immédiat');

        // Sauvegarder le texte original
        originalButtonText = $submitBtn.html();

        // Désactiver le bouton IMMÉDIATEMENT (avant même le submit)
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement en cours...');
        $submitBtn.addClass('disabled');
    });

    // Handler de soumission (sécurité supplémentaire)
    $('#payment-form').on('submit', function(e) {
        // Si déjà en cours de soumission, bloquer (ne devrait jamais arriver grâce au click handler)
        if (isSubmitting) {
            const $submitBtn = $(this).find('button[type="submit"]');
            if (!$submitBtn.prop('disabled')) {
                $submitBtn.prop('disabled', true);
            }
        }

        // Laisser le formulaire se soumettre normalement
        return true;
    });
});
</script>
@endpush 
