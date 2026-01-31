@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')

@section('title', 'Ajouter une évaluation - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle me-2"></i>Nouvelle Évaluation</h1>
                <p class="header-subtitle">Créer une nouvelle évaluation pour vos étudiants</p>
            </div>
            <div class="header-actions">
                @if(!empty($anneeUniversitaire))
                <span class="badge rounded-pill bg-light text-dark">
                    <i class="fas fa-calendar me-1"></i>
                    Année courante: {{ $anneeUniversitaire->name }}
                </span>
                @endif
                @if(auth()->check() && auth()->user() && !auth()->user()->hasRole(['teacher', 'enseignant']))
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
                @else
                <a href="{{ route('teacher.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au tableau de bord
                </a>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Erreur de validation</h5>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.evaluations.store') }}" method="POST" id="evaluationCreateForm">
            @csrf
            @if(request()->boolean('embed'))
                <input type="hidden" name="embed" value="1">
                @if(!empty($classe_id))
                    <input type="hidden" name="classe_id" value="{{ $classe_id }}">
                @endif
                @if(!empty($matiere_id))
                    <input type="hidden" name="matiere_id" value="{{ $matiere_id }}">
                @endif
            @endif

            <div class="form-sections">
                <!-- Section 1: Informations générales -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations générales
                        </div>
                        <div class="main-card-subtitle">Détails de base de l'évaluation</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="titre" class="form-label">Titre de l'évaluation <span class="text-danger">*</span></label>
                                <input type="text" class="form-input @error('titre') error @enderror" 
                                       id="titre" name="titre" value="{{ old('titre') }}"
                                       placeholder="Ex: Examen final de mathématiques" required>
                                @error('titre')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="type" class="form-label">Type d'évaluation <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') error @enderror" id="type" name="type" required>
                                    <option value="">-- Sélectionner un type --</option>
                                    @foreach($types as $typeKey => $typeValue)
                                        <option value="{{ $typeKey }}" {{ old('type') == $typeKey ? 'selected' : '' }}>
                                            {{ $typeValue }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="periode" class="form-label">Période <span class="text-danger">*</span></label>
                                <select class="form-select @error('periode') error @enderror" id="periode" name="periode" required>
                                    <option value="">-- Sélectionner une période --</option>
                                    <option value="semestre1" {{ old('periode') == 'semestre1' ? 'selected' : '' }}>Semestre 1</option>
                                    <option value="semestre2" {{ old('periode') == 'semestre2' ? 'selected' : '' }}>Semestre 2</option>
                                </select>
                                @error('periode')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="date_evaluation" class="form-label">Date d'évaluation <span class="text-danger">*</span></label>
                                <input type="date" class="form-input @error('date_evaluation') error @enderror" 
                                       id="date_evaluation" name="date_evaluation" value="{{ old('date_evaluation') }}" required>
                                @error('date_evaluation')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_debut" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" class="form-input @error('heure_debut') error @enderror"
                                       id="heure_debut" name="heure_debut"
                                       value="{{ old('heure_debut', '08:00') }}" required>
                                @error('heure_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_fin" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-input @error('heure_fin') error @enderror"
                                       id="heure_fin" name="heure_fin"
                                       value="{{ old('heure_fin', '10:00') }}" required>
                                @error('heure_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="duree_minutes" class="form-label">Durée (en minutes)</label>
                                <input type="number" class="form-input @error('duree_minutes') error @enderror" 
                                       id="duree_minutes" name="duree_minutes" value="{{ old('duree_minutes') }}" 
                                       min="15" max="720" placeholder="Ex: 120 (calculée automatiquement si vide)">
                                @error('duree_minutes')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                                <small class="form-hint">Laissez vide pour calculer automatiquement la durée à partir des horaires.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Classe et Matière -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-users"></i>
                            Classe et Matière
                        </div>
                        <div class="main-card-subtitle">Sélection de la classe et de la matière</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="classe_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <select class="form-select @error('classe_id') error @enderror" id="classe_id" name="classe_id" required @if(request()->boolean('embed') && !empty($classe_id)) disabled @endif>
                                    <option value="">-- Sélectionner une classe --</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ old('classe_id', $classe_id) == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }} ({{ $classe->filiere->name ?? '' }} - {{ $classe->niveau->name ?? '' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('classe_id')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="matiere_id" class="form-label">Matière <span class="text-danger">*</span></label>
                                <select id="matiere_id" name="matiere_id" class="form-select @error('matiere_id') error @enderror" required @if(request()->boolean('embed') && !empty($matiere_id)) disabled @endif>
                                    <option value="">-- Sélectionner une matière --</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" {{ (old('matiere_id', $matiere_id) == $matiere->id) ? 'selected' : '' }}>
                                            {{ $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('matiere_id')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                                <div class="form-hint mt-2" style="background: #f1f5f9; border-left: 3px solid var(--primary); padding: 10px 12px; border-radius: 6px;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('coordinateur') || auth()->user()->hasRole('secretaire'))
                                        Pour rattacher une matière à une classe (via filière + niveau),
                                        allez sur <a href="{{ route('esbtp.matieres.index') }}" class="text-decoration-underline">Matières</a>
                                        puis cliquez sur <strong>Configurer les liaisons</strong> (icône <i class="fas fa-link"></i>) sur la matière,
                                        ou sélectionnez plusieurs matières et utilisez <strong>Attacher aux combinaisons</strong> dans la barre d’actions.
                                    @else
                                        Si une matière manque, signalez-le à la direction afin d'ajouter ou retirer des matières pour la classe.
                                    @endif
                                </div>
                            </div>

<div class="form-group">
                                <label for="coefficient" class="form-label">Coefficient de l'évaluation <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="number" class="form-input flex-grow-1 @error('coefficient') error @enderror" 
                                           id="coefficient" name="coefficient" value="{{ old('coefficient', 1) }}" 
                                           step="0.1" min="0.1" max="10" required>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            id="btn-use-matiere-coefficient" 
                                            title="Utiliser le coefficient de la matière">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="form-hint mt-2" style="background: #f8f9fa; border-left: 3px solid var(--secondary); padding: 10px 12px; border-radius: 6px;">
                                    <i class="fas fa-lightbulb me-1 text-warning"></i>
                                    <strong>Logique :</strong> Le coefficient de l'évaluation peut être différent du coefficient de la matière.
                                    <br><small class="text-muted">
                                        • Ex: Matière coefficient 3, mais évaluation coefficient 1 (quiz)<br>
                                        • Ex: Matière coefficient 2, mais évaluation coefficient 2 (examen final)<br>
                                        • Utilisez le bouton <i class="fas fa-sync-alt"></i> pour copier le coefficient de la matière
                                    </small>
                                </div>
                                <div id="coeff-matiere-info" style="display: none;"></div>
                                @error('coefficient')
                                    <div class="form-error mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="bareme" class="form-label">Barème <span class="text-danger">*</span></label>
                                <input type="number" class="form-input @error('bareme') error @enderror" 
                                       id="bareme" name="bareme" value="{{ old('bareme', 20) }}" 
                                       step="0.1" min="1" required>
                                @error('bareme')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Description -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-align-left"></i>
                            Description et options
                        </div>
                        <div class="main-card-subtitle">Informations complémentaires</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-group">
                            <label for="description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-textarea @error('description') error @enderror" 
                                      id="description" name="description" rows="4"
                                      placeholder="Décrivez le contenu de l'évaluation, les chapitres couverts, etc...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-toggle">
                            <input type="checkbox" id="is_published" name="is_published" value="1" 
                                   {{ old('is_published') ? 'checked' : '' }}>
                            <label for="is_published">
                                <span class="toggle-title">Publier immédiatement</span>
                                <span class="toggle-description">Une évaluation publiée est visible par les enseignants et permet la saisie des notes.</span>
                            </label>
                        </div>
                        <div class="info-box mt-3">
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>Astuce :</strong>
                                tant que l'évaluation n'est pas publiée, elle reste en brouillon (invisible aux étudiants) et la saisie des notes est bloquée.
                                Une fois publiée, le statut passe automatiquement à <strong>Planifiée</strong>, <strong>En cours</strong>, puis <strong>Terminée</strong> selon la date et la durée.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Assignation d'enseignant (non-enseignants uniquement) -->
                @if(auth()->check() && auth()->user() && !auth()->user()->hasRole(['teacher', 'enseignant']))
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-user-tie"></i>
                            Assignation d'enseignant
                        </div>
                        <div class="main-card-subtitle">Attribution de l'évaluation à un enseignant</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="enseignant_id" class="form-label">Enseignant de la plateforme</label>
                                <select class="form-select" id="enseignant_id" name="enseignant_id">
                                    <option value="">-- Sélectionner un enseignant --</option>
                                    @foreach($enseignants as $enseignant)
                                        <option value="{{ $enseignant->id }}" {{ old('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                                            {{ $enseignant->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-hint">L'enseignant pourra saisir les notes directement</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="enseignant_externe_nom" class="form-label">Enseignant externe</label>
                                <input type="text" class="form-input" id="enseignant_externe_nom" 
                                       name="enseignant_externe_nom" value="{{ old('enseignant_externe_nom') }}"
                                       placeholder="Nom de l'enseignant externe">
                                <small class="form-hint">Si l'enseignant n'a pas de compte</small>
                            </div>
                        </div>

                        <div class="form-toggle">
                            <input type="checkbox" id="generer_lien_externe" name="generer_lien_externe" value="1" 
                                   {{ old('generer_lien_externe') ? 'checked' : '' }}>
                            <label for="generer_lien_externe">
                                <span class="toggle-title">Générer un lien de saisie pour l'enseignant externe</span>
                                <span class="toggle-description">Un lien temporaire sera créé pour permettre la saisie des notes (valable 30 jours)</span>
                            </label>
                        </div>

                        <div class="info-box">
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>Options d'assignation :</strong>
                                <ul class="mt-2 mb-0">
                                    <li><strong>Enseignant de la plateforme :</strong> Peut se connecter et saisir les notes</li>
                                    <li><strong>Enseignant externe :</strong> Traçabilité du nom seulement</li>
                                    <li><strong>Lien externe :</strong> Envoyez le lien à l'enseignant pour qu'il saisisse les notes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Section 5: Actions -->
                <div class="form-actions">
                    <button type="reset" class="btn-acasi secondary">
                        <i class="fas fa-undo"></i>Réinitialiser
                    </button>
                    <button type="submit" class="btn-acasi primary" id="evaluation-submit">
                        <i class="fas fa-save"></i>Enregistrer l'évaluation
                    </button>
                </div>
            </div>
        </form>
    </div>
    </div>
</div>
@endsection

<div class="modal fade" id="coeffMissingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2"></i>Coefficient manquant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="coeffMissingModalBody"></div>
        </div>
    </div>
</div>

<style>
/* Formulaire moderne avec dashboard-moderne.css */
.form-sections {
    display: grid;
    gap: var(--space-xl);
    max-width: none;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: var(--text);
    margin-bottom: var(--space-sm);
    font-size: var(--text-small);
    line-height: 1.2;
}

.form-input, .form-select, .form-textarea {
    padding: var(--space-md);
    border: 1px solid var(--border);
    border-radius: var(--radius-small);
    background: var(--card-background);
    color: var(--text);
    font-size: var(--text-base);
    transition: all 0.2s ease;
    line-height: 1.5;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    background: white;
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(var(--danger-rgb), 0.1);
}

.form-error {
    color: var(--danger);
    font-size: var(--text-small);
    margin-top: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.form-error::before {
    content: "⚠";
    font-weight: bold;
}

.form-hint {
    color: var(--muted);
    font-size: var(--text-small);
    margin-top: var(--space-xs);
    font-style: italic;
}

.form-toggle {
    display: flex;
    gap: var(--space-md);
    align-items: flex-start;
    padding: var(--space-lg);
    border: 1px solid var(--border);
    border-radius: var(--radius-medium);
    background: rgba(var(--primary-rgb), 0.02);
    margin-top: var(--space-md);
}

.form-toggle input[type="checkbox"] {
    margin: 0;
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    margin-top: 2px;
}

.form-toggle input[type="checkbox"]:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.form-toggle label {
    display: flex;
    flex-direction: column;
    margin: 0;
    cursor: pointer;
}

.toggle-title {
    font-weight: 600;
    color: var(--text);
    margin-bottom: var(--space-xs);
    font-size: var(--text-base);
}

.toggle-description {
    color: var(--muted);
    font-size: var(--text-small);
    line-height: 1.4;
}

.info-box {
    display: flex;
    gap: var(--space-md);
    padding: var(--space-lg);
    background: rgba(var(--info-rgb), 0.08);
    border: 1px solid rgba(var(--info-rgb), 0.2);
    border-radius: var(--radius-medium);
    color: var(--text);
    margin-top: var(--space-lg);
}

.info-box i {
    flex-shrink: 0;
    margin-top: var(--space-xs);
    color: var(--info);
    font-size: 1.1rem;
}

.info-box ul {
    margin: var(--space-sm) 0 0 var(--space-md);
    padding: 0;
}

.info-box li {
    margin-bottom: var(--space-xs);
    line-height: 1.4;
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: flex-end;
    padding: var(--space-xl) 0;
    border-top: 1px solid var(--border);
    margin-top: var(--space-lg);
}

/* Amélioration des cards principales */
.main-card {
    background: var(--card-background);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(var(--border-rgb), 0.1);
    transition: all 0.2s ease;
}

.main-card:hover {
    box-shadow: var(--shadow-hover);
}

.main-card-header {
    padding: var(--space-lg);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.03), rgba(30, 64, 175, 0.01));
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: var(--radius-medium) var(--radius-medium) 0 0;
}

.main-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.main-card-subtitle {
    font-size: var(--text-small);
    color: var(--muted);
    margin: 0;
}

.main-card-body {
    padding: var(--space-xl);
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-toggle {
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .main-card-body {
        padding: var(--space-lg);
    }
}

/* Couleurs personnalisées */
:root {
    --primary: #01632f;
    --primary-rgb: 1, 99, 47;
    --danger: #dc3545;
    --danger-rgb: 220, 53, 69;
    --info: #0dcaf0;
    --info-rgb: 13, 202, 240;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pattern AJAX classe → matières (identique à attendances.create pour cohérence)
    const classeSelect = document.getElementById('classe_id');
    const matiereSelect = document.getElementById('matiere_id');
    const coeffInput = document.getElementById('coefficient');
    const submitBtn = document.getElementById('evaluation-submit');
    const coeffCheckUrl = '{{ route("esbtp.evaluations.coefficients.check") }}';
    const coeffMissingModal = document.getElementById('coeffMissingModal');
    const coeffMissingBody = document.getElementById('coeffMissingModalBody');

    if (classeSelect && matiereSelect) {
        classeSelect.addEventListener('change', function(e) {
            e.preventDefault();
            const classeId = this.value;

            debugLog('📚 [AJAX] Classe sélectionnée:', classeId);

            // Reset matière select
            matiereSelect.innerHTML = '<option value="">-- Sélectionner une matière --</option>';
            matiereSelect.disabled = true;

            if (classeId) {
                loadMatieres(classeId);
            }

            return false;
        });
    }

    /**
     * Charge les matières disponibles pour une classe via AJAX
     * Utilise les combinaisons globales (filière + niveau)
     */
    function loadMatieres(classeId) {
        debugLog('🔄 [AJAX] Chargement matières pour classe:', classeId);

        // Supprimer tous les spinners existants pour éviter les doublons
        const label = document.querySelector('label[for="matiere_id"]');
        const existingSpinners = label.querySelectorAll('.loading-spinner');
        existingSpinners.forEach(s => s.remove());

        // Créer un nouveau spinner
        const spinner = document.createElement('span');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = ' <i class="fas fa-spinner fa-spin text-primary"></i>';
        label.appendChild(spinner);

        const url = '{{ route("esbtp.evaluations.load-matieres") }}?classe_id=' + classeId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (spinner) spinner.remove();

            if (data.success) {
                debugLog('✅ [AJAX] Matières reçues:', data.count, 'pour', data.classe.nom);

                // Mettre à jour le select avec les options HTML
                matiereSelect.innerHTML = data.options;
                matiereSelect.disabled = false;

                if (matiereSelect.value) {
                    checkCombinationCoefficient();
                }

                // Message si aucune matière
                if (data.count === 0) {
                    matiereSelect.innerHTML = '<option value="">Aucune matière disponible pour cette classe</option>';

                    // Alert utilisateur
                    alert('Attention: Aucune matière n\'est configurée pour la combinaison ' +
                          data.classe.filiere + ' / ' + data.classe.niveau + '. ' +
                          'Veuillez d\'abord ajouter des matières via la page "Matières de classe".');
                }
            } else {
                debugError('❌ Erreur:', data.message);
                alert('Erreur: ' + data.message);
                matiereSelect.disabled = false;
            }
        })
        .catch(error => {
            if (spinner) spinner.remove();
            debugError('❌ Erreur AJAX:', error);
            alert('Une erreur est survenue lors du chargement des matières: ' + error.message);
            matiereSelect.disabled = false;
        });
    }

    function checkCombinationCoefficient() {
        if (!classeSelect || !matiereSelect) {
            return;
        }

        const classeId = classeSelect.value;
        const matiereId = matiereSelect.value;
        const coeffInfoDiv = document.getElementById('coeff-matiere-info');

        if (!classeId || !matiereId) {
            if (coeffInfoDiv) coeffInfoDiv.style.display = 'none';
            return;
        }

        fetch(`${coeffCheckUrl}?classe_id=${classeId}&matiere_id=${matiereId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (coeffInput) {
                        coeffInput.value = data.coefficient;
                    }
                    if (coeffInfoDiv) {
                        coeffInfoDiv.style.display = 'block';
                        coeffInfoDiv.className = 'mt-2';
                        coeffInfoDiv.innerHTML = `
                            <div style="background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); border-left: 3px solid #17a2b8; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
                                <i class="fas fa-info-circle text-info me-1"></i>
                                Coefficient matière : <strong>${data.coefficient}</strong> (pré-rempli, vous pouvez le modifier)
                            </div>
                        `;
                    }
                } else {
                    // Coefficient matière non trouvé : laisser la valeur actuelle, ne PAS bloquer
                    if (coeffInput && (!coeffInput.value || coeffInput.value <= 0)) {
                        coeffInput.value = 1;
                    }
                    if (coeffInfoDiv) {
                        coeffInfoDiv.style.display = 'block';
                        coeffInfoDiv.className = 'mt-2';
                        coeffInfoDiv.innerHTML = `
                            <div style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); border-left: 3px solid #ffc107; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                Aucun coefficient matière configuré pour cette combinaison. Vous pouvez saisir le coefficient manuellement.
                                ${data.config_url ? `<br><small><a href="${data.config_url}" class="text-primary">Configurer les coefficients matière</a></small>` : ''}
                            </div>
                        `;
                    }
                }
                // Le bouton submit reste TOUJOURS actif
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            })
            .catch(() => {
                // En cas d'erreur réseau, ne pas bloquer non plus
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                if (coeffInfoDiv) {
                    coeffInfoDiv.style.display = 'none';
                }
            });
    }

if (classeSelect && matiereSelect) {
        matiereSelect.addEventListener('change', checkCombinationCoefficient);
    }

    // Gérer le bouton de synchronisation du coefficient de la matière
    const btnUseMatiereCoefficient = document.getElementById('btn-use-matiere-coefficient');
    const coefficientInput = document.getElementById('coefficient');

    if (btnUseMatiereCoefficient && coefficientInput) {
        btnUseMatiereCoefficient.addEventListener('click', function() {
            const matiereSelect = document.getElementById('matiere_id');
            const classeSelect = document.getElementById('classe_id');
            
            if (!matiereSelect.value || !classeSelect.value) {
                alert('Veuillez d\'abord sélectionner une classe et une matière.');
                return;
            }

            // Récupérer les matières avec coefficients
            const matiere = matieresJson.find(m => m.id == matiereSelect.value);
            
            if (matiere && matiere.coefficient) {
                coefficientInput.value = matiere.coefficient;
                
                // Feedback visuel temporaire
                const originalText = btnUseMatiereCoefficient.innerHTML;
                btnUseMatiereCoefficient.innerHTML = '<i class="fas fa-check text-success"></i>';
                btnUseMatiereCoefficient.classList.add('btn-success');
                btnUseMatiereCoefficient.classList.remove('btn-outline-secondary');
                
                setTimeout(() => {
                    btnUseMatiereCoefficient.innerHTML = originalText;
                    btnUseMatiereCoefficient.classList.remove('btn-success');
                    btnUseMatiereCoefficient.classList.add('btn-outline-secondary');
                }, 1500);
                
                // Mettre à jour l'info-bulle
                btnUseMatiereCoefficient.title = `Coefficient ${matiere.coefficient} copié depuis la matière`;
            } else {
                alert('Cette matière n\'a pas de coefficient défini.');
            }
        });

        // Mettre à jour le titre du bouton selon la matière sélectionnée
        matiereSelect.addEventListener('change', function() {
            const matiere = matieresJson.find(m => m.id == this.value);
            if (matiere && matiere.coefficient) {
                btnUseMatiereCoefficient.title = `Utiliser le coefficient de la matière (${matiere.coefficient})`;
                btnUseMatiereCoefficient.disabled = false;
            } else {
                btnUseMatiereCoefficient.title = 'Aucun coefficient défini pour cette matière';
                btnUseMatiereCoefficient.disabled = true;
            }
        });
    }
});
</script>

<style>
.loading-spinner {
    margin-left: 8px;
    display: inline-block;
    animation: fadeIn 0.3s ease;
}

.loading-spinner i {
    font-size: 1rem;
    color: var(--primary, #01632f);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Désactiver visuellement le select pendant le chargement */
#matiere_id:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f5f5f5;
}
</style>
