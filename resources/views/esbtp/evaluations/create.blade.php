@extends('layouts.app')

@section('title', 'Ajouter une évaluation - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-plus-circle me-2"></i>Nouvelle Évaluation</h1>
            <p class="header-subtitle">Créer une nouvelle évaluation pour vos étudiants</p>
        </div>
        <div class="header-actions">
            @if(!auth()->user()->hasRole(['teacher', 'enseignant']))
            <a href="{{ route('esbtp.evaluations.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour à la liste
            </a>
            @else
            <a href="{{ route('teacher.dashboard') }}" class="btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour au tableau de bord
            </a>
            @endif
        </div>
    </div>

    <div class="main-content">
        <!-- Matières statiques (fallback) -->
        <div id="matiere-data" data-matieres="{{ json_encode($matieres) }}" style="display: none;"></div>

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

        <form action="{{ route('esbtp.evaluations.store') }}" method="POST">
            @csrf

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
                                <label for="duree_minutes" class="form-label">Durée (en minutes)</label>
                                <input type="number" class="form-input @error('duree_minutes') error @enderror" 
                                       id="duree_minutes" name="duree_minutes" value="{{ old('duree_minutes', 120) }}" 
                                       min="15" max="300" placeholder="Ex: 120">
                                @error('duree_minutes')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
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
                                <select class="form-select @error('classe_id') error @enderror" id="classe_id" name="classe_id" required>
                                    <option value="">-- Sélectionner une classe --</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
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
                                <select id="matiere_id" name="matiere_id" class="form-select @error('matiere_id') error @enderror" required>
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
                            </div>

                            <div class="form-group">
                                <label for="coefficient" class="form-label">Coefficient <span class="text-danger">*</span></label>
                                <input type="number" class="form-input @error('coefficient') error @enderror" 
                                       id="coefficient" name="coefficient" value="{{ old('coefficient', 1) }}" 
                                       step="0.1" min="0.1" required>
                                @error('coefficient')
                                    <div class="form-error">{{ $message }}</div>
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
                    </div>
                </div>

                <!-- Section 4: Assignation d'enseignant (non-enseignants uniquement) -->
                @if(!auth()->user()->hasRole(['teacher', 'enseignant']))
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
                    <button type="reset" class="btn-secondary">
                        <i class="fas fa-undo me-1"></i>Réinitialiser
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer l'évaluation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

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
    // Simple classe/matière interaction
    const classeSelect = document.getElementById('classe_id');
    const matiereSelect = document.getElementById('matiere_id');
    const staticMatieres = JSON.parse(document.getElementById('matiere-data').getAttribute('data-matieres') || '[]');

    if (classeSelect && matiereSelect) {
        classeSelect.addEventListener('change', function() {
            const classeId = this.value;
            
            // Reset matière select
            matiereSelect.innerHTML = '<option value="">-- Sélectionner une matière --</option>';
            
            if (classeId) {
                // Load matieres for selected class
                if (staticMatieres && staticMatieres.length > 0) {
                    staticMatieres.forEach(function(matiere) {
                        const option = document.createElement('option');
                        option.value = matiere.id;
                        option.textContent = matiere.nom || matiere.name || 'Matière ' + matiere.id;
                        matiereSelect.appendChild(option);
                    });
                }
            }
        });
    }
});
</script>