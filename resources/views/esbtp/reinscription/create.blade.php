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
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Finaliser la Réinscription</h1>
                <p class="header-subtitle">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }} - {{ $anneeAcademique }}</p>
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

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="reporter_reliquat" name="reporter_reliquat" value="1">
                        <label class="form-check-label" for="reporter_reliquat">
                            <strong>Confirmer le report du reliquat</strong>
                            ({{ number_format($analyse['etudiant']->reliquat_montant, 0, ',', ' ') }} FCFA)
                            <br><small class="text-muted">Le reliquat sera ajouté aux frais de la nouvelle inscription</small>
                        </label>
                    </div>
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

                    <!-- Sélection nouvelle classe -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="form-group-moderne">
                                <label for="nouvelle_classe_id" class="form-label-moderne">Nouvelle Classe pour {{ date('Y') + 1 }} *</label>
                                <select name="nouvelle_classe_id" id="nouvelle_classe_id" class="form-select-moderne" required>
                                    <option value="">Sélectionner une classe...</option>
                                    @foreach($classesProposees as $classe)
                                    <option value="{{ $classe->id }}">
                                        {{ $classe->name ?? $classe->nom }} - {{ $classe->niveau->name ?? 'N/A' }} {{ $classe->filiere->name ?? 'N/A' }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    @if($analyse['decision'] === 'passage')
                                        Classes de niveau supérieur proposées
                                    @elseif($analyse['decision'] === 'redoublement')
                                        Classes de même niveau proposées
                                    @else
                                        Classe actuelle (rattrapage)
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration des nouveaux frais -->
                    @if($analyse['etudiant']->peut_reinscrire)
                    <div class="card-moderne mb-lg">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-money-bill-wave"></i>
                                Configuration des Frais pour {{ date('Y') + 1 }}
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
                    <input type="hidden" name="decision" value="{{ $analyse['decision'] }}">
                    <input type="hidden" name="selected_optionals" id="selectedOptionals" value="{}">
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Script réinscription create chargé');

    const classeSelect = document.getElementById('nouvelle_classe_id');
    const decisionSelect = document.getElementById('decision');
    const fraisContainer = document.getElementById('fraisContainer');
    const btnConfirmer = document.getElementById('btnConfirmer');
    const reporterReliquat = document.getElementById('reporter_reliquat');

    // Validation du reliquat pour superadmin
    @if($isSuperAdmin && !empty($fraisNonSoldes))
    function validateReliquat() {
        const hasReliquat = {{ !empty($fraisNonSoldes) ? 'true' : 'false' }};
        if (hasReliquat && reporterReliquat && !reporterReliquat.checked) {
            alert('Vous devez confirmer le report du reliquat pour procéder à la réinscription.');
            return false;
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
        });
    }
    @endif

    // Recharger les classes quand la décision change
    if (decisionSelect) {
        decisionSelect.addEventListener('change', function() {
            const decision = this.value;
            const etudiantId = {{ $analyse['etudiant']->id }};

            console.log('🔄 Changement de décision:', decision);

            // Réinitialiser le select des classes
            classeSelect.innerHTML = '<option value="">Chargement...</option>';
            fraisContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">Sélectionnez une classe pour voir les frais applicables</p></div>';

            // Charger les nouvelles classes
            fetch(`{{ url('esbtp/reinscription') }}/${etudiantId}/classes-by-decision?decision=${decision}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.classes) {
                    let options = '<option value="">Sélectionner une classe...</option>';
                    data.classes.forEach(function(classe) {
                        const niveauName = classe.niveau ? classe.niveau.name : 'N/A';
                        const filiereName = classe.filiere ? classe.filiere.name : 'N/A';
                        options += `<option value="${classe.id}">${classe.name || classe.nom} - ${niveauName} ${filiereName}</option>`;
                    });
                    classeSelect.innerHTML = options;
                } else {
                    classeSelect.innerHTML = '<option value="">Aucune classe disponible</option>';
                }
            })
            .catch(error => {
                console.error('Erreur chargement classes:', error);
                classeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
        });
    }

    // Chargement des frais quand une classe est sélectionnée
    if (classeSelect && fraisContainer) {
        classeSelect.addEventListener('change', function() {
            if (this.value) {
                console.log('Chargement frais pour classe:', this.value);
                loadFraisForClasse(this.value);
            } else {
                fraisContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">Sélectionnez une classe pour voir les frais applicables</p></div>';
            }
        });
    }

    function loadFraisForClasse(classeId) {
        fraisContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Chargement des frais...</p>
            </div>
        `;

        fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}`, {
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
            console.error('Erreur:', error);
            fraisContainer.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement des frais</div>`;
        });
    }

    function displayFrais(fraisData) {
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

        fraisData.forEach(function(category) {
            const categoryName = category.name || category.libelle || category.title || 'Frais non défini';
            const categoryAmount = category.default_amount || category.amount || 0;

            html += `
                <div class="card-moderne kpi-card">
                    <div class="kpi-title">
                        <i class="${category.icon || 'fas fa-money-bill-wave'} me-2"></i>
                        ${categoryName}
                    </div>
                    <div class="kpi-value ${category.is_mandatory ? 'color-success' : 'color-primary'}">
                        ${Number(categoryAmount).toLocaleString('fr-FR')} FCFA
                    </div>
                    <div class="kpi-trend ${category.is_mandatory ? 'positive' : ''}">
                        <i class="fas ${category.is_mandatory ? 'fa-check-circle' : 'fa-plus-circle'}"></i>
                        <span>${category.is_mandatory ? 'Obligatoire' : 'Optionnel'}</span>
                    </div>
                    ${category.is_mandatory ?
                        `<input type="hidden" name="frais_obligatoire[]" value="${category.id}" data-amount="${categoryAmount}">` :
                        `<div class="mt-3">
                            <select class="form-select frais-optional" data-category-id="${category.id}" style="font-size: 12px;">
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