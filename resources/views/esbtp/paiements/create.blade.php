@extends('layouts.app')

@section('title', 'Nouveau Paiement - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .student-progress-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .category-progress {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .category-progress:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }
    
    .progress-bar-modern {
        height: 8px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    
    .payment-form-card {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
    }
    
    .category-selection {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .category-option {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }
    
    .category-option:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .category-option.selected {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }
    
    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 18px;
    }
    
    .form-floating-modern {
        position: relative;
        margin-bottom: 20px;
    }
    
    .form-floating-modern input,
    .form-floating-modern select {
        width: 100%;
        padding: 15px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        transition: all 0.3s ease;
    }
    
    .form-floating-modern input:focus,
    .form-floating-modern select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .amount-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .amount-suggestions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    
    .amount-suggestion {
        padding: 8px 12px;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    
    .amount-suggestion:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Nouveau Paiement</h1>
                <p class="header-subtitle">Enregistrement intelligent des paiements par catégorie de frais</p>
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
                        <div class="form-floating-modern">
                            <select name="etudiant_id" id="etudiant_id" class="form-control select2" required>
                                <option value="">-- Rechercher et sélectionner un étudiant --</option>
                                @foreach(\App\Models\ESBTPEtudiant::with('user')->limit(10)->get() as $etudiant)
                                    <option value="{{ $etudiant->id }}">{{ $etudiant->matricule }} - {{ $etudiant->user->name ?? $etudiant->nom_complet ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                            <label>Étudiant <span class="text-danger">*</span></label>
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
                            <div class="form-floating-modern">
                                <select name="inscription_id" id="inscription_id" class="form-control" required>
                                    <option value="">-- Sélectionner d'abord un étudiant --</option>
                                </select>
                                <label>Inscription <span class="text-danger">*</span></label>
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
                        
                        <input type="hidden" name="frais_category_id" id="selected_category_id">
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating-modern">
                                    <div class="amount-input-group">
                                        <input type="number" name="montant" id="montant" class="form-control" min="0" step="1" value="{{ old('montant') }}" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted">FCFA</span>
                                    </div>
                                    <label>Montant <span class="text-danger">*</span></label>
                                    <div class="amount-suggestions" id="amount-suggestions">
                                        <!-- Les suggestions de montant seront générées dynamiquement -->
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
                                <div class="form-floating-modern">
                                    <select name="mode_paiement" id="mode_paiement" class="form-control" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="Espèces" {{ old('mode_paiement') == 'Espèces' ? 'selected' : '' }}>💵 Espèces</option>
                                        <option value="Chèque" {{ old('mode_paiement') == 'Chèque' ? 'selected' : '' }}>📄 Chèque</option>
                                        <option value="Virement" {{ old('mode_paiement') == 'Virement' ? 'selected' : '' }}>🏦 Virement bancaire</option>
                                        <option value="Mobile Money" {{ old('mode_paiement') == 'Mobile Money' ? 'selected' : '' }}>📱 Mobile Money</option>
                                        <option value="Carte bancaire" {{ old('mode_paiement') == 'Carte bancaire' ? 'selected' : '' }}>💳 Carte bancaire</option>
                                    </select>
                                    <label>Mode de paiement <span class="text-danger">*</span></label>
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
                                <div class="form-floating-modern">
                                    <select name="tranche" id="tranche" class="form-control">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="Première tranche" {{ old('tranche') == 'Première tranche' ? 'selected' : '' }}>Première tranche</option>
                                        <option value="Deuxième tranche" {{ old('tranche') == 'Deuxième tranche' ? 'selected' : '' }}>Deuxième tranche</option>
                                        <option value="Troisième tranche" {{ old('tranche') == 'Troisième tranche' ? 'selected' : '' }}>Troisième tranche</option>
                                        <option value="Paiement intégral" {{ old('tranche') == 'Paiement intégral' ? 'selected' : '' }}>Paiement intégral</option>
                                    </select>
                                    <label>Tranche de paiement</label>
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
            $('#student-progress-section').show();
            loadStudentBalance(currentStudent);
            loadCategories('{{ $inscription->id }}');
        @else
            // Étudiant sélectionné mais pas d'inscription, charger normalement
            loadStudentData(currentStudent);
        @endif
    @endif
    
    // Fonction pour charger les données de l'étudiant
    function loadStudentData(etudiantId) {
        debugLog('=== loadStudentData appelée avec ID:', etudiantId);
        
        // Charger les inscriptions
        loadInscriptions(etudiantId);
        
        // Charger les soldes et frais
        loadStudentBalance(etudiantId);
        
        // Afficher la section de progression
        $('#student-progress-section').fadeIn();
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
                debugLog('Nombre d\'inscriptions:', data.length);
                
                var options = '<option value="">-- Sélectionner une inscription --</option>';
                $.each(data, function(index, inscription) {
                    var selected = @if($inscription) (inscription.id == '{{ $inscription->id }}') ? 'selected' : '' @else '' @endif;
                    options += '<option value="' + inscription.id + '" ' + selected + '>' + 
                               inscription.filiere + ' - ' + inscription.niveau + 
                               ' (' + inscription.annee + ')</option>';
                });
                
                debugLog('Options HTML générées:', options);
                $('#inscription_id').html(options);
                debugLog('Select #inscription_id mis à jour');
                
                // Vérifier que le select existe et est mis à jour
                var selectElement = $('#inscription_id');
                debugLog('Select trouvé:', selectElement.length > 0);
                debugLog('Nouvelles options dans le select:', selectElement.find('option').length);
                
                // Si une inscription est sélectionnée, charger les catégories
                var selectedInscription = $('#inscription_id').val();
                debugLog('Inscription pré-sélectionnée:', selectedInscription);
                if (selectedInscription) {
                    loadCategories(selectedInscription);
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur chargement inscriptions:', {status, error, response: xhr.responseText});
            }
        });
    }
    
    // Charger les soldes de l'étudiant
    function loadStudentBalance(etudiantId) {
        debugLog('=== Chargement des soldes pour étudiant:', etudiantId);
        
        $.ajax({
            url: "{{ route('esbtp.api.etudiants.soldes') }}",
            data: { etudiant_id: etudiantId },
            dataType: 'json',
            success: function(data) {
                debugLog('Soldes reçus:', data);
                studentBalance = data;
                updateProgressDisplay(data);
            },
            error: function(xhr, status, error) {
                debugWarn('Impossible de charger les soldes:', {status, error});
            }
        });
    }
    
    // Gestion du changement d'inscription
    $('#inscription_id').on('change', function() {
        var inscriptionId = $(this).val();
        debugLog('Inscription sélectionnée:', inscriptionId);
        
        if (inscriptionId) {
            loadCategories(inscriptionId);
        } else {
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
                categories = data;
                displayCategories(data);
                $('#category-selection-section').fadeIn();
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
    
    // Calculer les suggestions de montant
    function calculateAmountSuggestions(category) {
        var suggestions = [];
        var remaining = category.montant;
        
        if (studentBalance && studentBalance.categories) {
            var paid = studentBalance.categories[category.id] || 0;
            remaining = category.montant - paid;
        }
        
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
        var paid = 0;
        var total = category.montant;
        
        if (studentBalance && studentBalance.categories) {
            paid = studentBalance.categories[category.id] || 0;
        }
        
        var percentage = total > 0 ? Math.round((paid / total) * 100) : 0;
        
        return {
            paid: paid,
            total: total,
            remaining: total - paid,
            percentage: Math.min(percentage, 100)
        };
    }
    
    // Mettre à jour l'affichage de progression
    function updateProgressDisplay(balanceData) {
        if (!balanceData || !balanceData.categories) {
            debugLog('Pas de données de solde disponibles');
            return;
        }
        
        var totalPaid = 0;
        var totalDue = 0;
        var html = '';
        
        // Calculer les totaux et créer l'affichage pour chaque catégorie
        Object.keys(balanceData.categories).forEach(function(categoryId) {
            var categoryBalance = balanceData.categories[categoryId];
            totalPaid += categoryBalance.paid || 0;
            totalDue += categoryBalance.total || 0;
            
            var percentage = categoryBalance.total > 0 ? 
                Math.round((categoryBalance.paid / categoryBalance.total) * 100) : 0;
            
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
                        <small>${formatAmount(categoryBalance.paid)} FCFA payé</small>
                        <small>${formatAmount(categoryBalance.total - categoryBalance.paid)} FCFA restant</small>
                    </div>
                </div>
            `;
        });
        
        $('#categories-progress').html(html);
        
        // Mettre à jour le progrès total
        var totalPercentage = totalDue > 0 ? Math.round((totalPaid / totalDue) * 100) : 0;
        $('#total-progress').text(totalPercentage + '% payé');
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
    
    // Formater un montant
    function formatAmount(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount);
    }
    
    // Réinitialiser le formulaire
    function resetForm() {
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