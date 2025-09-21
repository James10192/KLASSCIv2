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
                    <div class="row g-4">
                        @foreach($resultatsGeneraux as $resultat)
                        <div class="col-lg-6">
                            <div class="subject-card">
                                <div class="subject-header">
                                    <div class="subject-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="subject-info">
                                        <h6 class="subject-title">{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}</h6>
                                        <p class="subject-code">{{ $resultat->matiere->code ?? 'Code non défini' }}</p>
                                    </div>
                                </div>

                                @php
                                    $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect();
                                @endphp

                                @if($enseignantsMatiere->count() > 0)
                                <div class="quick-select-section">
                                    <label class="quick-select-label">
                                        <i class="fas fa-users"></i>
                                        Sélection rapide
                                    </label>
                                    <select class="form-select-modern">
                                        <option value="">Choisir un enseignant associé...</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                        <option value="{{ $enseignant->name }}">
                                            {{ $enseignant->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="teacher-input-section">
                                    <label for="professeur_{{ $resultat->matiere_id }}" class="teacher-input-label">
                                        <i class="fas fa-user-edit"></i>
                                        Nom de l'enseignant
                                    </label>
                                    <input type="text"
                                           class="form-control-modern"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="Nom qui apparaîtra sur le bulletin"
                                    />
                                </div>

                                @if($enseignantsMatiere->count() == 0)
                                <div class="no-teachers-notice">
                                    <i class="fas fa-info-circle"></i>
                                    Aucun enseignant associé à cette matière
                                </div>
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
                    <div class="row g-4">
                        @foreach($resultatsTechniques as $resultat)
                        <div class="col-lg-6">
                            <div class="subject-card">
                                <div class="subject-header">
                                    <div class="subject-icon technical">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="subject-info">
                                        <h6 class="subject-title">{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}</h6>
                                        <p class="subject-code">{{ $resultat->matiere->code ?? 'Code non défini' }}</p>
                                    </div>
                                </div>

                                @php
                                    $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect();
                                @endphp

                                @if($enseignantsMatiere->count() > 0)
                                <div class="quick-select-section">
                                    <label class="quick-select-label">
                                        <i class="fas fa-users"></i>
                                        Sélection rapide
                                    </label>
                                    <select class="form-select-modern">
                                        <option value="">Choisir un enseignant associé...</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                        <option value="{{ $enseignant->name }}">
                                            {{ $enseignant->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="teacher-input-section">
                                    <label for="professeur_{{ $resultat->matiere_id }}" class="teacher-input-label">
                                        <i class="fas fa-user-edit"></i>
                                        Nom de l'enseignant
                                    </label>
                                    <input type="text"
                                           class="form-control-modern"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="Nom qui apparaîtra sur le bulletin"
                                    />
                                </div>

                                @if($enseignantsMatiere->count() == 0)
                                <div class="no-teachers-notice">
                                    <i class="fas fa-info-circle"></i>
                                    Aucun enseignant associé à cette matière
                                </div>
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
            <div class="main-card">
                <div class="main-card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('esbtp.resultats.etudiant', [
                                'etudiant' => $etudiant->id,
                                'classe_id' => $classe->id,
                                'periode' => $periode,
                                'annee_universitaire_id' => $anneeUniversitaire->id
                            ]) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i> Retour aux résultats
                            </a>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn-acasi success btn-lg" name="action" value="save_and_return">
                                <i class="fas fa-save me-2"></i> Enregistrer et retourner
                            </button>
                            <button type="submit" class="btn-acasi primary btn-lg" name="action" value="generate">
                                <i class="fas fa-file-pdf me-2"></i> Enregistrer et générer bulletin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script de sélection d'enseignant directement dans le HTML -->
<script>
console.log('🔥 SCRIPT EDIT-PROFESSEURS CHARGÉ!');

// Fonction pour animer les changements de valeur
function animateValueChange(inputElement) {
    inputElement.classList.add('value-changed');
    setTimeout(() => {
        inputElement.classList.remove('value-changed');
    }, 1000);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 EDIT-PROFESSEURS: DOM chargé - Initialisation des event listeners');

    const form = document.getElementById('professeursForm');

    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Formulaire soumis !');
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
            console.log(`Bouton ${index + 1} cliqué:`, this.name, '=', this.value);
        });
    });

    // Ajouter des event listeners aux selects d'enseignants
    const teacherSelects = document.querySelectorAll('.form-select-modern');
    console.log('📝 EDIT-PROFESSEURS: Selects trouvés:', teacherSelects.length);

    // Test de diagnostic plus complet
    console.log('🔍 DIAGNOSTIC COMPLET:');
    console.log('- Tous les selects sur la page:', document.querySelectorAll('select').length);
    console.log('- Selects avec classe form-select-modern:', teacherSelects.length);
    console.log('- Inputs avec name professeurs:', document.querySelectorAll('input[name^="professeurs["]').length);
    console.log('- Subject cards:', document.querySelectorAll('.subject-card').length);

    teacherSelects.forEach((select, index) => {
        console.log(`📋 Configuration select ${index + 1}:`, select);

        select.setAttribute('title', 'Cliquez pour sélectionner un enseignant associé à cette matière');

        // Event listener pour la sélection d'enseignant
        select.addEventListener('change', function() {
            console.log('🔄 Select change détecté sur:', this);
            console.log('🔄 Valeur sélectionnée:', this.value);

            if (!this.value) {
                console.log('⚠️ Aucune valeur sélectionnée, abandon');
                return;
            }

            // Trouver l'input correspondant
            const parentCard = this.closest('.subject-card');
            console.log('🏠 Parent card:', parentCard);

            if (parentCard) {
                const targetInput = parentCard.querySelector('.form-control-modern');
                console.log('🎯 Input trouvé:', targetInput);
                console.log('🎯 Input classes:', targetInput ? targetInput.className : 'N/A');
                console.log('🎯 Input ID:', targetInput ? targetInput.id : 'N/A');

                if (targetInput) {
                    console.log('📝 Valeur avant:', targetInput.value);

                    // Essayer plusieurs méthodes d'assignation
                    targetInput.value = this.value;
                    targetInput.setAttribute('value', this.value);

                    console.log('📝 Valeur après:', targetInput.value);

                    // Déclencher l'événement input
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    targetInput.dispatchEvent(new Event('change', { bubbles: true }));

                    // Animation
                    animateValueChange(targetInput);

                    // Forcer le focus pour s'assurer que la valeur est visible
                    targetInput.focus();
                    targetInput.blur();

                    console.log('✅ Valeur assignée avec succès');

                    // Réinitialiser le select
                    this.value = '';
                } else {
                    console.error('❌ Input cible non trouvé dans la card');
                    console.log('🔍 Tous les inputs dans la card:', parentCard.querySelectorAll('input'));
                }
            } else {
                console.error('❌ Parent card non trouvé');
            }
        });
    });

    // Ajouter des gestionnaires d'événements pour les champs de saisie
    const teacherInputs = document.querySelectorAll('input[name^="professeurs["]');
    console.log('🎯 Inputs trouvés:', teacherInputs.length);

    teacherInputs.forEach(input => {
        // Animation lors de la saisie
        input.addEventListener('input', function() {
            // Optionnel: vous pouvez ajouter une légère animation lors de la saisie
            this.style.borderColor = 'var(--success)';
        });

        // Réinitialiser la couleur de bordure quand le champ perd le focus
        input.addEventListener('blur', function() {
            this.style.borderColor = '';
        });
    });
});
</script>

<style>
/* Cartes de matières modernes */
.subject-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.subject-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.subject-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary), #667eea);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.subject-icon.technical {
    background: linear-gradient(135deg, var(--success), #48bb78);
}

.subject-icon i {
    color: white;
    font-size: 1.2rem;
}

.subject-info {
    flex: 1;
}

.subject-title {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.subject-code {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Section de sélection rapide */
.quick-select-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid var(--info);
}

.quick-select-label {
    display: flex;
    align-items: center;
    margin: 0 0 0.75rem 0;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.quick-select-label i {
    margin-right: 0.5rem;
    color: var(--info);
}

.form-select-modern {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    background: white;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.form-select-modern:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

.form-select-modern:hover {
    border-color: var(--primary);
}

/* Section d'input enseignant */
.teacher-input-section {
    margin-bottom: 1rem;
}

.teacher-input-label {
    display: flex;
    align-items: center;
    margin: 0 0 0.75rem 0;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.teacher-input-label i {
    margin-right: 0.5rem;
    color: var(--success);
}

.form-control-modern {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    background: white;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    width: 100%;
}

.form-control-modern:focus {
    border-color: var(--success);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    outline: none;
}

.form-control-modern:hover {
    border-color: var(--success);
}

.form-control-modern::placeholder {
    color: #9ca3af;
    opacity: 1;
}

/* Notice d'absence d'enseignants */
.no-teachers-notice {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 6px;
    color: #92400e;
    font-size: 0.875rem;
    margin-top: 1rem;
}

.no-teachers-notice i {
    margin-right: 0.5rem;
    color: #f59e0b;
}

/* Amélioration des boutons */
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
}

.btn-outline-secondary {
    border: 2px solid #6b7280;
    color: #6b7280;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: #6b7280;
    color: white;
    transform: translateY(-1px);
}

/* Espacement responsive */
@media (max-width: 768px) {
    .subject-card {
        margin-bottom: 1rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .d-flex.gap-3 {
        justify-content: stretch;
    }

    .btn-lg {
        width: 100%;
        text-align: center;
    }

    .subject-header {
        flex-direction: column;
        text-align: center;
    }

    .subject-icon {
        margin: 0 0 1rem 0;
    }
}

/* Animation pour les changements de valeur */
.form-control-modern.value-changed {
    background-color: #d4edda;
    border-color: var(--success);
}

.form-control-modern.value-changed {
    animation: valueChange 1s ease-out;
}

@keyframes valueChange {
    0% {
        background-color: #d4edda;
        transform: scale(1.02);
    }
    100% {
        background-color: white;
        transform: scale(1);
    }
}
</style>

@endsection