@extends('layouts.app')

@section('title', 'Édition des professeurs - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-edit me-2"></i>Édition des professeurs</h1>
                <p class="header-subtitle">Configurez les enseignants pour chaque matière du bulletin</p>
            </div>
            <div class="header-actions">
                <span class="badge bg-primary fs-6">
                    <i class="fas fa-graduation-cap me-1"></i>
                    {{ $etudiant->nom }} {{ $etudiant->prenom }}
                </span>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe->libelle ?? $classe->name ?? 'N/A' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $classe->filiere->nom ?? $classe->filiere->name ?? 'N/A' }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières générales</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $resultatsGeneraux->count() ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Enseignement général
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières techniques</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $resultatsTechniques->count() ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-tools"></i>
                    Enseignement technique
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Professeurs assignés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ !empty($professeurs) ? count($professeurs) : 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Configurés
                </div>
            </div>
        </div>

        <!-- Guide d'utilisation -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-lightbulb"></i>
                    Guide d'utilisation
                </div>
            </div>
            <div class="main-card-body">
                <div class="alert alert-info mb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-users me-2"></i><strong>Sélection rapide :</strong> Utilisez les listes déroulantes pour choisir un enseignant déjà associé à la matière</p>
                            <p><i class="fas fa-keyboard me-2"></i><strong>Saisie libre :</strong> Écrivez directement le nom dans le champ de texte</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-eye-slash me-2"></i>Les champs vides n'afficheront pas de nom d'enseignant sur le bulletin</p>
                            <p><i class="fas fa-save me-2"></i>N'oubliez pas d'enregistrer vos modifications avant de quitter</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur de validation -->
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Messages de succès -->
        @if(session('success'))
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Messages d'erreur -->
        @if(session('error'))
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form id="professeursForm" action="{{ route('esbtp.bulletins.save-professeurs') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-graduation-cap"></i>
                        Enseignement général
                    </div>
                    <div class="main-card-subtitle">{{ $resultatsGeneraux->count() }} matière(s) d'enseignement général</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($resultatsGeneraux as $resultat)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label fw-medium">
                                    <i class="fas fa-book text-primary me-2"></i>
                                    {{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}
                                </label>

                                @php
                                    $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect();
                                @endphp

                                @if($enseignantsMatiere->count() > 0)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <strong>Sélection rapide</strong> - Enseignants associés à cette matière :
                                    </small>
                                    <select class="form-select border-primary"
                                            style="background-color: #f8f9ff;"
                                            onchange="selectEnseignant(this, 'professeur_{{ $resultat->matiere_id }}')">
                                        <option value="">🔸 Cliquez pour sélectionner un enseignant...</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                        <option value="{{ $enseignant->name }}">
                                            👨‍🏫 {{ $enseignant->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label text-success fw-bold mt-2">
                                    <i class="fas fa-save me-1"></i>
                                    Nom qui sera sauvegardé :
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">
                                        <i class="fas fa-save"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control border-success"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="💾 Nom qui apparaîtra sur le bulletin (sélectionnez ci-dessus ou tapez ici)"
                                           oninput="updateSaveIndicator('{{ $resultat->matiere_id }}')"
                                </div>

                                <!-- Indicateur de sauvegarde -->
                                <div id="save_indicator_{{ $resultat->matiere_id }}" class="mt-2" style="display: none;">
                                    <div class="alert alert-success py-2 mb-0">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>Sera sauvegardé :</strong>
                                        <span id="save_value_{{ $resultat->matiere_id }}" class="fw-bold"></span>
                                    </div>
                                </div>

                                @if($enseignantsMatiere->count() == 0)
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Aucun enseignant associé à cette matière. Saisie libre uniquement.
                                </small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-tools"></i>
                        Enseignement technique
                    </div>
                    <div class="main-card-subtitle">{{ $resultatsTechniques->count() }} matière(s) d'enseignement technique</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($resultatsTechniques as $resultat)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label fw-medium">
                                    <i class="fas fa-cog text-success me-2"></i>
                                    {{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}
                                </label>

                                @php
                                    $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect();
                                @endphp

                                @if($enseignantsMatiere->count() > 0)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <strong>Sélection rapide</strong> - Enseignants associés à cette matière :
                                    </small>
                                    <select class="form-select border-primary"
                                            style="background-color: #f8f9ff;"
                                            onchange="selectEnseignant(this, 'professeur_{{ $resultat->matiere_id }}')">
                                        <option value="">🔸 Cliquez pour sélectionner un enseignant...</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                        <option value="{{ $enseignant->name }}">
                                            👨‍🏫 {{ $enseignant->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label text-success fw-bold mt-2">
                                    <i class="fas fa-save me-1"></i>
                                    Nom qui sera sauvegardé :
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">
                                        <i class="fas fa-save"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control border-success"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="💾 Nom qui apparaîtra sur le bulletin (sélectionnez ci-dessus ou tapez ici)"
                                           oninput="updateSaveIndicator('{{ $resultat->matiere_id }}')"
                                </div>

                                <!-- Indicateur de sauvegarde -->
                                <div id="save_indicator_{{ $resultat->matiere_id }}" class="mt-2" style="display: none;">
                                    <div class="alert alert-success py-2 mb-0">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>Sera sauvegardé :</strong>
                                        <span id="save_value_{{ $resultat->matiere_id }}" class="fw-bold"></span>
                                    </div>
                                </div>

                                @if($enseignantsMatiere->count() == 0)
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Aucun enseignant associé à cette matière. Saisie libre uniquement.
                                </small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if((!isset($resultatsGeneraux) || $resultatsGeneraux->isEmpty()) && (!isset($resultatsTechniques) || $resultatsTechniques->isEmpty()))
            <div class="main-card">
                <div class="main-card-body">
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                        <h5>Aucune matière configurée</h5>
                        <p>Veuillez d'abord configurer les matières pour cet étudiant.</p>
                        <a href="{{ route('esbtp.bulletins.config-matieres') }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}&bulletin={{ $etudiant->id }}" class="btn-acasi primary">
                            <i class="fas fa-cogs"></i> Configurer les matières
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <a href="{{ route('esbtp.resultats.etudiant', [
                        'etudiant' => $etudiant->id,
                        'classe_id' => $classe->id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $anneeUniversitaire->id
                    ]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour aux résultats
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-acasi success" name="action" value="edit">
                        <i class="fas fa-save"></i> Enregistrer et continuer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Fonction pour sélectionner un enseignant depuis le select
function selectEnseignant(selectElement, inputId) {
    const selectedValue = selectElement.value;
    const inputElement = document.getElementById(inputId);

    if (inputElement && selectedValue) {
        inputElement.value = selectedValue;

        // Mettre à jour l'indicateur de sauvegarde
        const matiereId = inputId.replace('professeur_', '');
        updateSaveIndicator(matiereId);

        // Optionnel : Réinitialiser le select après sélection
        selectElement.value = '';

        // Animation de feedback visuel
        inputElement.style.backgroundColor = '#d4edda';
        setTimeout(() => {
            inputElement.style.backgroundColor = '';
        }, 1000);
    }
}

// Fonction pour mettre à jour l'indicateur de sauvegarde
function updateSaveIndicator(matiereId) {
    const inputElement = document.getElementById('professeur_' + matiereId);
    const indicatorElement = document.getElementById('save_indicator_' + matiereId);
    const valueElement = document.getElementById('save_value_' + matiereId);

    if (inputElement && indicatorElement && valueElement) {
        const currentValue = inputElement.value.trim();

        if (currentValue) {
            valueElement.textContent = '"' + currentValue + '"';
            indicatorElement.style.display = 'block';
        } else {
            indicatorElement.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('professeursForm');

    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('🚀 Formulaire soumis !');
            console.log('Action:', this.action);
            console.log('Méthode:', this.method);

            // Collecter les données du formulaire
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            console.log('Données du formulaire:', data);

            // Ne pas empêcher la soumission par défaut
            // Juste logger pour débogage
        });
    }

    // Logger les boutons de soumission
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            console.log(`🖱️ Bouton ${index + 1} cliqué:`, this.name, '=', this.value);
        });
    });

    // Ajouter des tooltips aux selects d'enseignants
    const teacherSelects = document.querySelectorAll('select[onchange*="selectEnseignant"]');
    teacherSelects.forEach(select => {
        select.setAttribute('title', 'Cliquez pour sélectionner un enseignant associé à cette matière');
    });

    // Ajouter des gestionnaires d'événements pour les champs de saisie
    const teacherInputs = document.querySelectorAll('input[name^="professeurs["]');
    teacherInputs.forEach(input => {
        // Mettre à jour l'indicateur lors de la saisie
        input.addEventListener('input', function() {
            const matiereId = this.id.replace('professeur_', '');
            updateSaveIndicator(matiereId);
        });

        // Initialiser l'affichage au chargement de la page
        const matiereId = input.id.replace('professeur_', '');
        updateSaveIndicator(matiereId);
    });
});
</script>
@endsection