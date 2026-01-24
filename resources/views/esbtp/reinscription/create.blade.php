@extends('layouts.app')

@section('title', 'Finaliser la Réinscription - ' . $analyse['etudiant']->prenoms . ' ' . $analyse['etudiant']->nom)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-group-disabled {
        opacity: 0.7;
        pointer-events: none;
        background-color: #f8f9fa;
    }

    .reliquat-section {
        border: 2px solid #fbbf24;
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.05) 0%, rgba(245, 158, 11, 0.05) 100%);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .reliquat-detail {
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
    }

    /* Searchable Select Component CSS (from etudiants.index) */
    .searchable-select {
        position: relative;
        width: 100%;
    }

    .searchable-select-trigger {
        width: 100%;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 40px 10px 14px;
        font-size: 14px;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 42px;
    }

    .searchable-select-trigger:hover {
        border-color: #cbd5e1;
    }

    .searchable-select-trigger:focus,
    .searchable-select.active .searchable-select-trigger {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .searchable-select-trigger-text {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        cursor: pointer;
    }

    .searchable-select-trigger-text.placeholder {
        color: #64748b;
        font-style: italic;
    }

    .searchable-select-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        transition: transform 0.2s;
        color: #64748b;
        pointer-events: none;
    }

    .searchable-select.active .searchable-select-icon {
        transform: translateY(-50%) rotate(180deg);
    }

    .searchable-select-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12), 0 4px 10px rgba(15, 23, 42, 0.08);
        z-index: 9999;
        max-height: 320px;
        display: flex;
        flex-direction: column;
        animation: slideDown 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        isolation: isolate;
    }

    .searchable-select-search {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .searchable-select-search input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s;
    }

    .searchable-select-search input:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .searchable-select-search input::placeholder {
        color: #94a3b8;
    }

    .searchable-select-options {
        overflow-y: auto;
        max-height: 240px;
    }

    .searchable-select-option {
        padding: 10px 14px;
        cursor: pointer;
        transition: background-color 0.15s;
        font-size: 14px;
        color: #1e293b;
    }

    .searchable-select-option:hover {
        background-color: #f8fafc;
    }

    .searchable-select-option.selected {
        background-color: #eff6ff;
        color: #0453cb;
        font-weight: 500;
    }

    .searchable-select-option.highlighted {
        background-color: #0453cb;
        color: white;
    }

    .searchable-select-no-results {
        padding: 24px 14px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Scrollbar styling */
    .searchable-select-options::-webkit-scrollbar {
        width: 8px;
    }

    .searchable-select-options::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .searchable-select-options::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .searchable-select-options::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Alpine.js cloak */
    [x-cloak] {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Finaliser la Réinscription</h1>
                <p class="header-subtitle">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }} - De {{ $anneeEtudiantActuelle }} vers <span id="anneeDestinationDisplay">{{ $anneeDestinationName }}</span></p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.reinscription.show', $analyse['etudiant']->id) }}?annee_academique={{ $anneeAcademique }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--danger); background-color: rgba(239, 68, 68, 0.05);">
                <div class="p-lg">
                    <ul style="margin: 0; padding-left: 20px; color: var(--danger);">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Gestion des reliquats pour superadmin -->
        @if($isSuperAdmin && !empty($fraisNonSoldes))
        <div class="card-moderne mb-lg">
            <div class="main-card-header" style="background-color: rgba(251, 191, 36, 0.1);">
                <div class="main-card-title" style="color: var(--warning);">
                    <i class="fas fa-exclamation-triangle"></i>
                    Gestion des Reliquats (Superadministrateur)
                </div>
            </div>
            <div class="p-lg">
                <div class="reliquat-section">
                    <div class="alert alert-warning mb-lg">
                        <i class="fas fa-user-shield me-2"></i>
                        <strong>Privilège Superadmin :</strong> Vous pouvez créer une réinscription avec des frais impayés.
                        Ces montants seront reportés comme "reliquat année précédente" sur la nouvelle inscription.
                    </div>

                    <h6 class="mb-3">Détail des frais non soldés :</h6>

                    @foreach($fraisNonSoldes as $frais)
                    <div class="reliquat-detail">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ $frais['category_name'] }}</strong>
                            <span class="badge bg-warning">{{ number_format($frais['solde_restant'], 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="small text-muted">
                            Attendu: {{ number_format($frais['montant_attendu'], 0, ',', ' ') }} FCFA |
                            Payé: {{ number_format($frais['montant_paye'], 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                    @endforeach

                </div>
            </div>
        </div>
        @endif

        <!-- Formulaire de finalisation -->
        <div class="card-moderne">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-check-circle"></i>
                    Informations de Réinscription
                </div>
            </div>
            <div class="p-lg">
                <form action="{{ route('esbtp.reinscription.update', $analyse['etudiant']->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if($isSuperAdmin && !empty($fraisNonSoldes))
                    <!-- Section de gestion des reliquats -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Gestion des Reliquats</h6>
                                </div>
                                <div class="card-body">
                                    <h6 class="mb-3">Choix de gestion du reliquat :</h6>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" id="reporter_reliquat" name="action_reliquat" value="reporter">
                                        <label class="form-check-label" for="reporter_reliquat">
                                            <strong><i class="fas fa-arrow-right me-1"></i> Reporter le reliquat</strong>
                                            ({{ number_format($analyse['etudiant']->reliquat_montant, 0, ',', ' ') }} FCFA)
                                            <br><small class="text-muted">Le reliquat sera ajouté aux frais de la nouvelle inscription et devra être soldé ultérieurement</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" id="abandonner_reliquat" name="action_reliquat" value="abandonner">
                                        <label class="form-check-label" for="abandonner_reliquat">
                                            <strong><i class="fas fa-times me-1"></i> Abandonner les frais impayés</strong>
                                            <br><small class="text-muted">Les frais non soldés seront annulés définitivement (remise exceptionnelle)</small>
                                        </label>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Information :</strong> Vous devez choisir une action pour les frais impayés avant de pouvoir finaliser la réinscription.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Informations étudiant (grisées) -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group-moderne form-group-disabled">
                                <label class="form-label-moderne">Nom complet</label>
                                <input type="text" class="form-control-moderne" value="{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-moderne form-group-disabled">
                                <label class="form-label-moderne">Matricule</label>
                                <input type="text" class="form-control-moderne" value="{{ $analyse['etudiant']->matricule ?? 'N/A' }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group-moderne form-group-disabled">
                                <label class="form-label-moderne">Classe actuelle</label>
                                <input type="text" class="form-control-moderne" value="{{ $analyse['inscription']->classe->name ?? 'N/A' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-moderne">
                                <label for="decision" class="form-label-moderne">Décision académique *</label>
                                <select name="decision" id="decision" class="form-select-moderne" required>
                                    <option value="passage" {{ $analyse['decision'] === 'passage' ? 'selected' : '' }}>Passage</option>
                                    <option value="redoublement" {{ $analyse['decision'] === 'redoublement' ? 'selected' : '' }}>Redoublement</option>
                                    <option value="rattrapage" {{ $analyse['decision'] === 'rattrapage' ? 'selected' : '' }}>Rattrapage</option>
                                </select>
                                <small class="form-text text-muted">
                                    La décision détermine les classes proposées
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection année universitaire -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="form-group-moderne">
                                <label for="annee_universitaire_id" class="form-label-moderne">Année universitaire de destination *</label>
                                <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-select-moderne" required>
                                    <option value="">Sélectionner l'année universitaire</option>
                                    @foreach($anneeUniversitairesFutures as $annee)
                                        <option value="{{ $annee->id }}">{{ $annee->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Seules les années universitaires futures sont disponibles pour la réinscription
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection nouvelle classe et statut d'affectation -->
                    <div class="row mb-4" x-data="{ choixFiliere: 'meme' }">
                        <div class="col-md-6">
                            <div class="form-group-moderne">
                                <label for="affectation_status" class="form-label-moderne">Statut d'affectation *</label>
                                <select name="affectation_status" id="affectation_status" class="form-select-moderne" required>
                                    <option value="affecté">Affecté</option>
                                    <option value="réaffecté">Réaffecté</option>
                                    <option value="non_affecté">Non affecté</option>
                                </select>
                                <small class="form-text text-muted">
                                    Le statut influence les frais applicables
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Radio buttons pour choix de filière -->
                            <div class="form-group-moderne mb-3">
                                <label class="form-label-moderne">Choix de filière *</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="choix_filiere_radio" value="meme" id="meme_filiere"
                                               x-model="choixFiliere" checked>
                                        <label class="form-check-label" for="meme_filiere">Même filière</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="choix_filiere_radio" value="autre" id="autre_filiere"
                                               x-model="choixFiliere">
                                        <label class="form-check-label" for="autre_filiere">Autre filière</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Affichage conditionnel selon le choix -->

                            <!-- OPTION 1: Même filière (comportement par défaut) -->
                            <div x-show="choixFiliere === 'meme'" class="form-group-moderne">
                                <label class="form-label-moderne">Nouvelle Classe pour {{ $anneeDestinationName }} *</label>
                                <div x-data="window.searchableSelect({
                                    options: [],
                                    selected: '',
                                    name: 'nouvelle_classe',
                                    placeholder: 'Sélectionnez d\'abord une décision...'
                                })"
                                class="searchable-select"
                                :class="{ 'active': open }"
                                @click.away="open = false"
                                x-init="window.nouvelleClasseSelector = $data"
                                data-initial-value="{{ old('nouvelle_classe_id') }}">
                                    <input type="hidden" name="nouvelle_classe_id" :value="selectedValue" id="nouvelle_classe_id">
                                    <button type="button" class="searchable-select-trigger" @click="open = !open" :disabled="!choixFiliere || choixFiliere !== 'meme'">
                                        <span class="searchable-select-trigger-text" :class="{ 'placeholder': selectedValue === '' }" x-text="selectedLabel || placeholder"></span>
                                        <i class="fas fa-chevron-down searchable-select-icon"></i>
                                    </button>
                                    <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                        <div class="searchable-select-search">
                                            <input type="text"
                                                   x-model="search"
                                                   @input="filterOptions"
                                                   placeholder="Tapez pour rechercher..."
                                                   @click.stop
                                                   x-ref="searchInput">
                                        </div>
                                        <div class="searchable-select-options">
                                            <template x-if="filteredOptions.length === 0">
                                                <div class="searchable-select-no-results">
                                                    <i class="fas fa-search mb-2"></i>
                                                    <div>Aucune classe trouvée</div>
                                                </div>
                                            </template>
                                            <template x-for="option in filteredOptions" :key="option.value">
                                                <div class="searchable-select-option"
                                                     :class="{ 'selected': option.value === selectedValue }"
                                                     @click="selectOption(option)">
                                                    <span x-text="option.label"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div id="nouvelleClassePlacesInfo" class="mt-2"></div>
                                <small class="form-text text-muted" id="classes-help">
                                    Les classes dépendent de votre décision académique
                                </small>
                            </div>

                            <!-- OPTION 2: Autre filière (nouveaux selects en cascade) -->
                            <div x-show="choixFiliere === 'autre'" x-cloak>
                                <!-- Select Filière -->
                                <div class="form-group-moderne mb-3">
                                    <label for="autre_filiere_id" class="form-label-moderne">Filière *</label>
                                    <select id="autre_filiere_id" class="form-select-moderne"
                                            :required="choixFiliere === 'autre'"
                                            @change="handleFiliereChange">
                                        <option value="">Sélectionner une filière...</option>
                                        @foreach(\App\Models\ESBTPFiliere::active()->orderBy('name')->get() as $filiere)
                                            <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Select Niveau -->
                                <div class="form-group-moderne mb-3">
                                    <label for="autre_niveau_id" class="form-label-moderne">Niveau d'étude *</label>
                                    <select id="autre_niveau_id" class="form-select-moderne"
                                            :required="choixFiliere === 'autre'"
                                            @change="handleNiveauChange">
                                        <option value="">Sélectionner d'abord une filière...</option>
                                    </select>
                                </div>

                                <!-- Searchable Select Classe (Alpine.js) -->
                                <div class="form-group-moderne">
                                    <label class="form-label-moderne">Classe *</label>
                                    <div x-data="window.searchableSelect({
                                        options: [],
                                        selected: '',
                                        name: 'autre_classe',
                                        placeholder: '👉 Sélectionnez d\'abord une filière et un niveau'
                                    })"
                                    class="searchable-select"
                                    :class="{ 'active': open }"
                                    @click.away="open = false"
                                    x-init="window.autreClasseSelector = $data">
                                        <input type="hidden" name="autre_classe_id" :value="selectedValue" id="autre_classe_id">
                                        <button type="button" class="searchable-select-trigger" @click="open = !open">
                                            <span class="searchable-select-trigger-text" :class="{ 'placeholder': selectedValue === '' }" x-text="selectedLabel || placeholder"></span>
                                            <i class="fas fa-chevron-down searchable-select-icon"></i>
                                        </button>
                                        <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                            <div class="searchable-select-search">
                                                <input type="text"
                                                       x-model="search"
                                                       @input="filterOptions"
                                                       placeholder="Tapez pour rechercher..."
                                                       @click.stop
                                                       x-ref="searchInput">
                                            </div>
                                            <div class="searchable-select-options">
                                                <template x-if="filteredOptions.length === 0">
                                                    <div class="searchable-select-no-results">
                                                        <i class="fas fa-search mb-2"></i>
                                                        <div>Aucune classe trouvée</div>
                                                    </div>
                                                </template>
                                                <template x-for="option in filteredOptions" :key="option.value">
                                                    <div class="searchable-select-option"
                                                         :class="{ 'selected': option.value === selectedValue }"
                                                         @click="selectOption(option)">
                                                        <span x-text="option.label"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="autreClassePlacesInfo" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration des nouveaux frais -->
                    @if($analyse['etudiant']->peut_reinscrire)
                    <div class="card-moderne mb-lg">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-money-bill-wave"></i>
                                Configuration des Frais pour {{ $anneeDestinationName }}
                            </div>
                        </div>
                        <div class="p-lg">
                            <div id="fraisContainer">
                                <div class="text-center py-4">
                                    <p class="text-muted">Sélectionnez une classe pour voir les frais applicables</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Champs cachés -->
                    <input type="hidden" name="decision_finale" id="decisionFinale" value="{{ $analyse['decision'] }}">
                    <input type="hidden" name="affectation_status_final" id="affectationFinale" value="affecté">
                    <input type="hidden" name="selected_optionals" id="selectedOptionals" value="{}"
                    @if($isSuperAdmin)
                    <input type="hidden" name="has_reliquat" value="{{ !empty($fraisNonSoldes) ? '1' : '0' }}">
                    <input type="hidden" name="reliquat_montant" value="{{ $analyse['etudiant']->reliquat_montant }}">
                    @endif

                    <!-- Observations -->
                    <div class="form-group-moderne mb-lg">
                        <label for="observations" class="form-label-moderne">Observations</label>
                        <textarea name="observations" id="observations" class="form-textarea-moderne" rows="3"
                                  placeholder="Observations particulières concernant cette réinscription..."></textarea>
                    </div>

                    <!-- Boutons de validation -->
                    <div style="display: flex; justify-content: flex-end; gap: var(--space-md);">
                        <a href="{{ route('esbtp.reinscription.show', $analyse['etudiant']->id) }}?annee_academique={{ $anneeAcademique }}" class="btn-acasi secondary">
                            <i class="fas fa-times"></i>Annuler
                        </a>
                        <button type="submit" class="btn-acasi primary" id="btnConfirmer">
                            <i class="fas fa-check"></i>Confirmer la Réinscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ========================================
    // SEARCHABLE SELECT COMPONENT (from etudiants.index)
    // ========================================
    window.searchableSelect = function(config) {
        return {
            options: config.options || [],
            filteredOptions: [],
            search: '',
            open: false,
            selectedValue: config.selected || '',
            selectedLabel: '',
            placeholder: config.placeholder || 'Sélectionner...',

            init() {
                this.filteredOptions = this.options;
                this.updateSelectedLabel();

                // Watch for open changes to focus search input
                this.$watch('open', value => {
                    if (value) {
                        this.$nextTick(() => {
                            this.$refs.searchInput?.focus();
                        });
                    } else {
                        this.search = '';
                        this.filteredOptions = this.options;
                    }
                });
            },

            filterOptions() {
                const searchLower = this.search.toLowerCase();
                this.filteredOptions = this.options.filter(option =>
                    option.label.toLowerCase().includes(searchLower)
                );
            },

            selectOption(option) {
                this.selectedValue = option.value;
                this.selectedLabel = option.label;
                this.open = false;
                this.search = '';
                this.filteredOptions = this.options;

                // Déclencher les fonctions appropriées selon le type de select
                this.$nextTick(() => {
                    // Pour le select "Même filière"
                    if (config.name === 'nouvelle_classe') {
                        if (option.value) {
                            if (typeof window.fetchAvailablePlaces === 'function') {
                                window.fetchAvailablePlaces(option.value);
                            }
                            if (typeof window.loadFraisForClasse === 'function') {
                                window.loadFraisForClasse(option.value);
                            }
                        } else {
                            // Option vide sélectionnée - réinitialiser
                            const placesInfo = document.getElementById('nouvelleClassePlacesInfo');
                            if (placesInfo) {
                                placesInfo.innerHTML = '';
                            }
                            const fraisContainer = document.getElementById('fraisContainer');
                            if (fraisContainer) {
                                fraisContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">Sélectionnez une classe pour voir les frais applicables</p></div>';
                            }
                        }
                    }
                    // Pour le select "Autre filière"
                    else if (config.name === 'autre_classe') {
                        if (typeof window.checkAutrePlacesDisponibles === 'function') {
                            window.checkAutrePlacesDisponibles(option.value);
                        }
                        if (option.value && typeof window.loadAutreFrais === 'function') {
                            window.loadAutreFrais(option.value);
                        }
                    }
                });
            },

            updateSelectedLabel() {
                const selected = this.options.find(opt => opt.value === this.selectedValue);
                this.selectedLabel = selected ? selected.label : '';
            }
        }
    }

    // ========================================
    // LOGIQUE CASCADING SELECTS (Filière → Niveau → Classe)
    // ========================================
    window.handleFiliereChange = function(event) {
        const filiereId = event.target.value;
        const niveauSelect = document.getElementById('autre_niveau_id');

        // Réinitialiser le select niveau
        niveauSelect.innerHTML = '<option value="">⏳ Chargement...</option>';

        // Réinitialiser le searchable select classe
        if (window.autreClasseSelector) {
            window.autreClasseSelector.options = [];
            window.autreClasseSelector.filteredOptions = [];
            window.autreClasseSelector.selectedValue = '';
            window.autreClasseSelector.selectedLabel = '';
            window.autreClasseSelector.placeholder = '👉 Sélectionnez d\'abord un niveau';
        }

        // Réinitialiser l'info des places
        const placesInfo = document.getElementById('autreClassePlacesInfo');
        if (placesInfo) {
            placesInfo.innerHTML = '';
        }

        if (!filiereId) {
            niveauSelect.innerHTML = '<option value="">Sélectionner d\'abord une filière...</option>';
            return;
        }

        // Afficher un indicateur de chargement pendant le fetch AJAX
        niveauSelect.innerHTML = '<option value="">⏳ Chargement des niveaux...</option>';

        // Charger les niveaux pour cette filière via AJAX
        fetch(`/esbtp/reinscription/api/niveaux-by-filiere/${filiereId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.niveaux && data.niveaux.length > 0) {
                    let options = '<option value="">Sélectionner un niveau...</option>';
                    data.niveaux.forEach(niveau => {
                        options += `<option value="${niveau.id}">${niveau.name}</option>`;
                    });
                    niveauSelect.innerHTML = options;
                } else {
                    niveauSelect.innerHTML = '<option value="">Aucun niveau disponible pour cette filière</option>';
                }
            })
            .catch(error => {
                console.error('Erreur chargement niveaux:', error);
                niveauSelect.innerHTML = '<option value="">❌ Erreur de chargement</option>';
            });
    }

    window.handleNiveauChange = function(event) {
        const niveauId = event.target.value;
        const filiereId = document.getElementById('autre_filiere_id').value;

        // Réinitialiser l'info des places
        const placesInfo = document.getElementById('autreClassePlacesInfo');
        if (placesInfo) {
            placesInfo.innerHTML = '';
        }

        if (!niveauId || !filiereId) {
            if (window.autreClasseSelector) {
                window.autreClasseSelector.options = [];
                window.autreClasseSelector.filteredOptions = [];
                window.autreClasseSelector.selectedValue = '';
                window.autreClasseSelector.selectedLabel = '';
                window.autreClasseSelector.placeholder = '👉 Sélectionnez d\'abord une filière et un niveau';
            }
            return;
        }

        // Afficher un indicateur de chargement pendant le fetch AJAX
        if (window.autreClasseSelector) {
            window.autreClasseSelector.options = [];
            window.autreClasseSelector.filteredOptions = [];
            window.autreClasseSelector.selectedValue = '';
            window.autreClasseSelector.selectedLabel = '';
            window.autreClasseSelector.placeholder = '⏳ Chargement des classes...';
        }

        // Charger les classes pour cette filière + niveau via AJAX
        fetch(`/esbtp/reinscription/api/classes-by-filiere-niveau?filiere_id=${filiereId}&niveau_id=${niveauId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.classes && data.classes.length > 0) {
                    // Ajouter une option par défaut au début de la liste
                    const defaultOption = {
                        value: '',
                        label: 'Sélectionner une classe...'
                    };

                    const classesOptions = [
                        defaultOption,
                        ...data.classes.map(classe => ({
                            value: classe.id.toString(),
                            label: `${classe.name} - ${classe.filiere.name} (${classe.niveau.name})`
                        }))
                    ];

                    if (window.autreClasseSelector) {
                        window.autreClasseSelector.options = classesOptions;
                        window.autreClasseSelector.filteredOptions = classesOptions;
                        // Pré-sélectionner l'option par défaut
                        window.autreClasseSelector.selectedValue = '';
                        window.autreClasseSelector.selectedLabel = 'Sélectionner une classe...';
                        window.autreClasseSelector.placeholder = 'Sélectionner une classe...';
                    }
                } else {
                    if (window.autreClasseSelector) {
                        window.autreClasseSelector.options = [{
                            value: '',
                            label: 'Aucune classe disponible pour cette combinaison'
                        }];
                        window.autreClasseSelector.filteredOptions = [{
                            value: '',
                            label: 'Aucune classe disponible pour cette combinaison'
                        }];
                        window.autreClasseSelector.selectedValue = '';
                        window.autreClasseSelector.selectedLabel = 'Aucune classe disponible pour cette combinaison';
                        window.autreClasseSelector.placeholder = 'Aucune classe disponible';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur chargement classes:', error);
                if (window.autreClasseSelector) {
                    window.autreClasseSelector.options = [{
                        value: '',
                        label: '❌ Erreur de chargement'
                    }];
                    window.autreClasseSelector.filteredOptions = [{
                        value: '',
                        label: '❌ Erreur de chargement'
                    }];
                    window.autreClasseSelector.selectedValue = '';
                    window.autreClasseSelector.selectedLabel = '❌ Erreur de chargement';
                    window.autreClasseSelector.placeholder = '❌ Erreur de chargement';
                }
            });
    }

    // ========================================
    // VÉRIFICATION PLACES DISPONIBLES (pour "autre filière")
    // ========================================
    window.checkAutrePlacesDisponibles = function(classeId) {
        const placesInfo = document.getElementById('autreClassePlacesInfo');
        if (!placesInfo) {
            return;
        }

        // Si aucune classe sélectionnée (option vide), effacer l'info des places
        if (!classeId) {
            placesInfo.innerHTML = '';
            return;
        }

        placesInfo.innerHTML = `
            <div class="d-flex align-items-center text-muted small mt-2">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Vérification des places...
            </div>
        `;
        if (typeof window.setReinscriptionButtonState === 'function') {
            window.setReinscriptionButtonState(false, 'Vérification des places disponibles en cours...');
        }

        fetch(`/esbtp/classes/${classeId}/available-places`)
            .then(response => response.json())
            .then(data => {
                const available = Number(data.available_places);
                const capacity = data.capacity !== undefined ? Number(data.capacity) : null;

                const capacityText = capacity !== null && !isNaN(capacity) ? ` / ${capacity}` : '';
                let message = `Places disponibles: <strong>${Math.max(available, 0)}</strong>${capacityText}`;
                let alertClass = 'alert-success';
                let buttonMessage = '';

                if (available <= 5) {
                    alertClass = 'alert-warning';
                    buttonMessage = available > 0
                        ? `Il reste ${available} place(s).`
                        : buttonMessage;
                    if (available > 0) {
                        message += `<br><small class="text-warning">Il ne reste que ${available} place(s) disponibles.</small>`;
                    }
                }

                if (available <= 0) {
                    alertClass = 'alert-danger';
                    message = capacityText
                        ? `<strong>Aucune place disponible !</strong> (0${capacityText})`
                        : '<strong>Aucune place disponible !</strong>';
                    message += '<br><small class="text-danger">Veuillez sélectionner une autre classe avant de poursuivre.</small>';
                    if (typeof window.setReinscriptionButtonState === 'function') {
                        window.setReinscriptionButtonState(false, 'Classe complète. Choisissez une autre classe pour finaliser la réinscription.');
                    }
                } else {
                    // Places disponibles - activer le bouton
                    if (buttonMessage) {
                        if (typeof window.setReinscriptionButtonState === 'function') {
                            window.setReinscriptionButtonState(true, buttonMessage);
                        }
                    } else {
                        if (typeof window.setReinscriptionButtonState === 'function') {
                            window.setReinscriptionButtonState(true);
                        }
                    }
                }

                placesInfo.innerHTML = `<div class="alert ${alertClass} p-2 mt-2">${message}</div>`;

                // Charger les frais pour cette classe
                loadAutreFrais(classeId);
            })
            .catch(error => {
                console.error('Erreur vérification places:', error);
                placesInfo.innerHTML = '<div class="alert alert-danger p-2 mt-2">Erreur lors de la récupération des places.</div>';
                if (typeof window.setReinscriptionButtonState === 'function') {
                    window.setReinscriptionButtonState(false, 'Erreur lors de la récupération des places. Réessayez ou sélectionnez une autre classe.');
                }
            });
    }

    // ========================================
    // CHARGEMENT FRAIS POUR "AUTRE FILIÈRE"
    // ========================================
    function loadAutreFrais(classeId) {
        const fraisContainer = document.getElementById('fraisContainer');
        if (!fraisContainer) {
            return;
        }

        const affectationSelect = document.getElementById('affectation_status');
        const affectation = affectationSelect ? affectationSelect.value : 'affecté';

        fraisContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Chargement des frais...</p>
            </div>
        `;

        // Appeler l'endpoint AJAX pour récupérer les frais
        fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}?affectation_status=${encodeURIComponent(affectation)}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && typeof window.displayFrais === 'function') {
                window.displayFrais(data.frais);
            } else {
                fraisContainer.innerHTML = `<div class="alert alert-danger">Erreur: ${data.message || 'Impossible de charger les frais'}</div>`;
            }
        })
        .catch(error => {
            console.error('Erreur chargement frais:', error);
            fraisContainer.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement des frais</div>`;
        });
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classeSelect = document.getElementById('nouvelle_classe_id');
    const decisionSelect = document.getElementById('decision');
    const affectationSelect = document.getElementById('affectation_status');
    const fraisContainer = document.getElementById('fraisContainer');
    const btnConfirmer = document.getElementById('btnConfirmer');
    const placesInfo = document.getElementById('nouvelleClassePlacesInfo');
    // Variables pour la gestion des reliquats gérées directement dans validateReliquat()

    function setReinscriptionButtonState(enabled, message = '') {
        if (!btnConfirmer) {
            return;
        }
        btnConfirmer.disabled = !enabled;
        if (message) {
            btnConfirmer.setAttribute('title', message);
        } else {
            btnConfirmer.removeAttribute('title');
        }
    }

    window.setReinscriptionButtonState = setReinscriptionButtonState;

    // Champs cachés pour transmission finale
    const decisionFinale = document.getElementById('decisionFinale');
    const affectationFinale = document.getElementById('affectationFinale');

    // Données locales transmises depuis le contrôleur
    const classesParDecision = @json($classesParDecision ?? []);
    const fraisParClasse = @json($fraisParClasse ?? []);

    // Validation du reliquat pour superadmin
    @if($isSuperAdmin && !empty($fraisNonSoldes))
    function validateReliquat() {
        const hasReliquat = {{ !empty($fraisNonSoldes) ? 'true' : 'false' }};
        if (hasReliquat) {
            const actionReliquat = document.querySelector('input[name="action_reliquat"]:checked');
            if (!actionReliquat) {
                alert('Vous devez choisir une action pour les frais impayés (reporter ou abandonner) avant de procéder à la réinscription.');
                return false;
            }
        }
        return true;
    }

    // Validation avant soumission
    if (btnConfirmer) {
        btnConfirmer.addEventListener('click', function(e) {
            if (!validateReliquat()) {
                e.preventDefault();
                return false;
            }

            // Mettre à jour les champs cachés avec les valeurs finales
            decisionFinale.value = decisionSelect.value;
            affectationFinale.value = affectationSelect.value;
        });
    }
    @endif

    setReinscriptionButtonState(false, 'Sélectionnez une classe disponible pour finaliser la réinscription.');

    // Fonction pour mettre à jour les classes selon la décision
    function updateClassesParDecision() {
        const decision = decisionSelect.value;
        const affectation = affectationSelect.value;

        // Réinitialiser les infos
        fraisContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">Sélectionnez une classe pour voir les frais applicables</p></div>';
        if (placesInfo) {
            placesInfo.innerHTML = '';
        }
        setReinscriptionButtonState(false, 'Sélectionnez une classe disponible pour finaliser la réinscription.');

        if (decision && classesParDecision[decision]) {
            const classes = classesParDecision[decision];

            // Créer les options pour le searchable select
            const defaultOption = {
                value: '',
                label: 'Sélectionner une classe...'
            };

            const classesOptions = [
                defaultOption,
                ...classes.map(function(classe) {
                    const niveauName = classe.niveau ? classe.niveau.name : 'N/A';
                    const filiereName = classe.filiere ? classe.filiere.name : 'N/A';
                    return {
                        value: String(classe.id),
                        label: `${classe.name || classe.nom} - ${niveauName} ${filiereName}`
                    };
                })
            ];

            // Mettre à jour le searchable select
            if (window.nouvelleClasseSelector) {
                window.nouvelleClasseSelector.options = classesOptions;
                window.nouvelleClasseSelector.filteredOptions = classesOptions;
                window.nouvelleClasseSelector.selectedValue = '';
                window.nouvelleClasseSelector.selectedLabel = 'Sélectionner une classe...';
                window.nouvelleClasseSelector.placeholder = 'Sélectionner une classe...';

                // Restaurer la valeur initiale si présente
                const searchableSelectDiv = document.querySelector('[x-init="window.nouvelleClasseSelector = $data"]');
                const initialValue = searchableSelectDiv ? searchableSelectDiv.dataset.initialValue : '';

                if (initialValue) {
                    const hasMatch = classes.some(function(classe) {
                        return String(classe.id) === String(initialValue);
                    });

                    if (hasMatch) {
                        const matchingOption = classesOptions.find(opt => opt.value === String(initialValue));
                        if (matchingOption) {
                            window.nouvelleClasseSelector.selectedValue = matchingOption.value;
                            window.nouvelleClasseSelector.selectedLabel = matchingOption.label;
                            searchableSelectDiv.dataset.initialValue = '';
                            fetchAvailablePlaces(initialValue);
                            loadFraisForClasse(initialValue);
                        }
                    }
                }
            }

            document.getElementById('classes-help').textContent = `${classes.length} classe(s) disponible(s) pour ${decision}`;
        } else {
            // Aucune classe disponible
            if (window.nouvelleClasseSelector) {
                window.nouvelleClasseSelector.options = [{
                    value: '',
                    label: 'Aucune classe disponible'
                }];
                window.nouvelleClasseSelector.filteredOptions = [{
                    value: '',
                    label: 'Aucune classe disponible'
                }];
                window.nouvelleClasseSelector.selectedValue = '';
                window.nouvelleClasseSelector.selectedLabel = 'Aucune classe disponible';
                window.nouvelleClasseSelector.placeholder = 'Aucune classe disponible';
            }

            document.getElementById('classes-help').textContent = 'Aucune classe trouvée pour cette décision';
            setReinscriptionButtonState(false, 'Aucune classe disponible pour cette décision.');
        }
    }

    // Écouteurs d'événements pour décision et affectation
    if (decisionSelect) {
        decisionSelect.addEventListener('change', updateClassesParDecision);
    }

    if (affectationSelect) {
        affectationSelect.addEventListener('change', function() {
            document.getElementById('affectationFinale').value = this.value;

            // Recharger les classes (l'affectation peut influencer les classes disponibles)
            updateClassesParDecision();
            // Recharger les frais si une classe est sélectionnée
            if (window.nouvelleClasseSelector && window.nouvelleClasseSelector.selectedValue) {
                fetchAvailablePlaces(window.nouvelleClasseSelector.selectedValue);
                loadFraisForClasse(window.nouvelleClasseSelector.selectedValue);
            } else if (placesInfo) {
                placesInfo.innerHTML = '';
                setReinscriptionButtonState(false, 'Sélectionnez une classe disponible pour finaliser la réinscription.');
            }
        });
    }

    // Initialisation au chargement
    updateClassesParDecision();

    // Rendre les fonctions globales pour le composant Alpine
    window.fetchAvailablePlaces = function fetchAvailablePlaces(classeId) {
        if (!placesInfo) {
            return;
        }

        if (!classeId) {
            placesInfo.innerHTML = '';
            setReinscriptionButtonState(false, 'Sélectionnez une classe disponible pour finaliser la réinscription.');
            return;
        }

        placesInfo.innerHTML = `
            <div class="d-flex align-items-center text-muted small mt-2">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Vérification des places...
            </div>
        `;
        setReinscriptionButtonState(false, 'Vérification des places disponibles en cours...');

        fetch(`/esbtp/classes/${classeId}/available-places`)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (typeof data.available_places === 'undefined') {
                    placesInfo.innerHTML = '<div class="alert alert-danger p-2 mt-2">Réponse invalide du serveur.</div>';
                    setReinscriptionButtonState(false, 'Impossible de vérifier les places disponibles pour cette classe.');
                    return;
                }

                const available = Number(data.available_places);
                const capacity = data.capacity !== undefined ? Number(data.capacity) : null;

                if (Number.isNaN(available)) {
                    placesInfo.innerHTML = '<div class="alert alert-danger p-2 mt-2">Places disponibles non communiquées.</div>';
                    setReinscriptionButtonState(false, 'Impossible de vérifier les places disponibles pour cette classe.');
                    return;
                }

                const capacityText = capacity !== null && !Number.isNaN(capacity) ? ` / ${capacity}` : '';
                let message = `Places disponibles: <strong>${Math.max(available, 0)}</strong>${capacityText}`;
                let alertClass = 'alert-success';
                let buttonMessage = '';

                if (available <= 5) {
                    alertClass = 'alert-warning';
                    buttonMessage = available > 0
                        ? `Il reste ${available} place(s).`
                        : buttonMessage;
                    if (available > 0) {
                        message += `<br><small class="text-warning">Il ne reste que ${available} place(s) disponibles.</small>`;
                    }
                }

                if (available <= 0) {
                    alertClass = 'alert-danger';
                    message = capacityText
                        ? `<strong>Aucune place disponible !</strong> (0${capacityText})`
                        : '<strong>Aucune place disponible !</strong>';
                    message += '<br><small class="text-danger">Veuillez sélectionner une autre classe avant de poursuivre.</small>';
                    setReinscriptionButtonState(false, 'Classe complète. Choisissez une autre classe pour finaliser la réinscription.');
                } else {
                    if (buttonMessage) {
                        setReinscriptionButtonState(true, buttonMessage);
                    } else {
                        setReinscriptionButtonState(true);
                    }
                }

                placesInfo.innerHTML = `<div class="alert ${alertClass} p-2 mt-2">${message}</div>`;
            })
            .catch(function(error) {
                debugError('Erreur de vérification des places:', error);
                placesInfo.innerHTML = '<div class="alert alert-danger p-2 mt-2">Erreur lors de la récupération des places.</div>';
                setReinscriptionButtonState(false, 'Erreur lors de la récupération des places. Réessayez ou sélectionnez une autre classe.');
            });
    }

    window.loadFraisForClasse = function loadFraisForClasse(classeId) {
        const affectation = affectationSelect.value;

        fraisContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Chargement des frais...</p>
            </div>
        `;

        // Vérifier si nous avons les frais localement
        const classeKey = `${classeId}_${affectation || 'affecté'}`;

        if (fraisParClasse && fraisParClasse[classeKey]) {
            displayFrais(fraisParClasse[classeKey]);
        } else {
            // Fallback AJAX si les données locales ne sont pas disponibles
            fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}?affectation_status=${encodeURIComponent(affectation)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayFrais(data.frais);
                } else {
                    fraisContainer.innerHTML = `<div class="alert alert-danger">Erreur: ${data.message}</div>`;
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                fraisContainer.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement des frais</div>`;
            });
        }
    }

    // Exposer globalement pour être utilisée par loadAutreFrais()
    window.displayFrais = function displayFrais(fraisData) {
        if (!fraisData || fraisData.length === 0) {
            fraisContainer.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucun frais configuré pour cette classe. La réinscription se fera sans frais supplémentaires.
                </div>
            `;
            return;
        }

        let html = '<div class="kpi-grid">';

        fraisData.forEach(function(fraisItem) {
            const categoryName = fraisItem.category.name || fraisItem.category.libelle || fraisItem.category.title || 'Frais non défini';
            const categoryAmount = fraisItem.default_amount || fraisItem.configured_amount || fraisItem.amount || 0;

            html += `
                <div class="card-moderne kpi-card">
                    <div class="kpi-title">${categoryName}</div>
                    <div class="kpi-value ${fraisItem.is_mandatory ? 'color-success' : 'color-primary'}">
                        ${Number(categoryAmount).toLocaleString('fr-FR')} FCFA
                    </div>
                    <div class="kpi-trend ${fraisItem.is_mandatory ? 'positive' : ''}">
                        <i class="fas ${fraisItem.is_mandatory ? 'fa-check-circle' : 'fa-plus-circle'}"></i>
                        <span>${fraisItem.is_mandatory ? 'Obligatoire' : 'Optionnel'}</span>
                    </div>
                    ${fraisItem.is_mandatory ?
                        `<input type="hidden" name="frais_obligatoire[]" value="${fraisItem.category.id}" data-amount="${categoryAmount}">` :
                        `<div class="mt-3">
                            <select class="form-select frais-optional" data-category-id="${fraisItem.category.id}" style="font-size: 12px;">
                                <option value="none">Ne pas souscrire</option>
                                <option value="default" data-amount="${categoryAmount}">
                                    Souscrire ce frais
                                </option>
                            </select>
                        </div>`
                    }
                </div>
            `;
        });

        html += '</div>';
        fraisContainer.innerHTML = html;

        // Ajouter les event listeners pour les frais optionnels
        document.querySelectorAll('.frais-optional').forEach(function(select) {
            select.addEventListener('change', updateSelectedOptionals);
        });
    }

    function updateSelectedOptionals() {
        const selectedOptionals = {};

        document.querySelectorAll('.frais-optional').forEach(function(select) {
            const categoryId = select.dataset.categoryId;
            const selectedOption = select.options[select.selectedIndex];

            if (select.value !== 'none') {
                selectedOptionals[categoryId] = {
                    variant_id: select.value,
                    amount: parseFloat(selectedOption.dataset.amount) || 0,
                    name: selectedOption.text
                };
            }
        });

        document.getElementById('selectedOptionals').value = JSON.stringify(selectedOptionals);
    }
});
</script>
@endpush
