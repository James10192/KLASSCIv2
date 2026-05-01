@extends('layouts.app')

@section('title', 'Créer un emploi du temps - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* Animation pour la flèche */
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    /* Amélioration des gradients */
    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #0056b3) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #1e7e34) !important;
    }
    
    /* Amélioration des cartes */
    .card.border-primary {
        box-shadow: 0 0 20px rgba(0, 123, 255, 0.15) !important;
    }
    
    /* Amélioration des badges */
    .badge.fs-6 {
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
    }
    
    /* Style des progress bars */
    .progress {
        border-radius: 10px;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    /* Amélioration des alertes */
    .alert {
        border-radius: 15px;
    }
    
    /* Style pour les icônes circulaires */
    .rounded-circle {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Amélioration des tableaux */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
    
    /* Style pour les cards avec shadow */
    .shadow-lg {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
    }

    /* Variables CSS pour les formulaires */
    :root {
        --space-xs: 0.25rem;
        --space-sm: 0.5rem;
        --space-md: 0.75rem;
        --space-lg: 1rem;
        --space-xl: 1.25rem;
        --space-xxl: 1.5rem;
        --radius-small: 0.25rem;
        --radius-medium: 0.375rem;
        --text-small: 0.8rem;
        --text-base: 0.875rem;
        --text: #1f2937;
        --muted: #6b7280;
        --border: #d1d5db;
        --card-background: #ffffff;
        --primary: #3b82f6;
        --primary-rgb: 59, 130, 246;
        --danger: #ef4444;
        --danger-rgb: 239, 68, 68;
    }

    /* Styles des formulaires modernes */
    .form-sections {
        display: flex;
        flex-direction: column;
        gap: var(--space-xl);
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: var(--space-xl);
        margin-bottom: var(--space-lg);
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: var(--space-lg);
    }

    .form-label {
        font-weight: 600;
        color: var(--text);
        margin-bottom: var(--space-md);
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
        min-height: 40px;
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
        margin-top: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) 0;
    }

    .form-error::before {
        content: "⚠";
        font-weight: bold;
    }

    .form-help {
        color: var(--muted);
        font-size: var(--text-small);
        margin-top: var(--space-sm);
        font-style: italic;
        padding: var(--space-sm) 0;
    }

    .form-input-locked {
        padding: var(--space-md);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        background: #f8f9fa;
        color: var(--muted);
        font-size: var(--text-base);
        line-height: 1.5;
        display: flex;
        align-items: center;
        min-height: 40px;
    }

    .form-input-group {
        display: flex;
        gap: var(--space-sm);
        align-items: stretch;
    }

    .form-input-group .form-input {
        flex: 1;
    }

    .form-checkbox {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
        padding: var(--space-lg);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        background: rgba(var(--primary-rgb), 0.02);
        margin: var(--space-md) 0;
    }

    .form-checkbox input[type="checkbox"] {
        margin: 0;
        width: 20px;
        height: 20px;
    }

    .form-checkbox label {
        margin: 0;
        cursor: pointer;
        font-weight: 500;
    }

    .alert-info-modern {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-lg);
        border: 1px solid #3b82f6;
        border-radius: var(--radius-medium);
        background: rgba(59, 130, 246, 0.05);
        margin: var(--space-md) 0;
    }

    .alert-warning-modern {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-lg);
        border: 1px solid #f59e0b;
        border-radius: var(--radius-medium);
        background: rgba(245, 158, 11, 0.05);
        margin: var(--space-md) 0;
    }

    .alert-icon {
        flex-shrink: 0;
        font-size: 1.25rem;
        margin-top: 0.125rem;
    }

    .alert-info-modern .alert-icon {
        color: #3b82f6;
    }

    .alert-warning-modern .alert-icon {
        color: #f59e0b;
    }

    .alert-content h6 {
        margin: 0 0 var(--space-sm) 0;
        font-weight: 600;
        font-size: var(--text-base);
    }

    .alert-content p {
        margin: 0;
        font-size: var(--text-small);
        line-height: 1.6;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: var(--space-md);
        padding: var(--space-xl) var(--space-lg);
        border-top: 1px solid var(--border);
        margin-top: var(--space-xl);
        background: rgba(248, 249, 250, 0.5);
        border-radius: 0 0 var(--radius-medium) var(--radius-medium);
    }

    /* Améliorer l'espacement des main-cards */
    .main-card {
        margin-bottom: var(--space-xl) !important;
    }

    .main-card-body {
        padding: var(--space-xl) !important;
    }

    .main-card-header {
        padding: var(--space-lg) var(--space-xl) !important;
    }

    /* Améliorer l'espacement global */
    .dashboard-header {
        margin-bottom: var(--space-xl) !important;
    }

    .card-moderne {
        margin-bottom: var(--space-xl) !important;
    }

    .card-moderne .p-lg {
        padding: var(--space-xl) !important;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-alt me-2"></i>Nouvel Emploi du Temps</h1>
                <p class="header-subtitle">Créer un nouvel emploi du temps pour vos classes</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
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

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les emplois du temps sont créés pour l'année académique en cours uniquement.
                    </small>
                </div>
            </div>
        </div>

        <form action="{{ route('esbtp.emploi-temps.store') }}" method="POST">
            @csrf

            <div class="form-sections">
                <!-- Section 1: Informations générales -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations générales
                        </div>
                        <div class="main-card-subtitle">Détails de base de l'emploi du temps</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="titre" class="form-label">Titre de l'emploi du temps <span class="text-danger">*</span></label>
                                <input type="text" class="form-input @error('titre') error @enderror"
                                       id="titre" name="titre" value="{{ old('titre') }}"
                                       placeholder="Ex: Emploi du temps BTS 1ère année Génie Civil - Semestre 1" required>
                                @error('titre')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="classe_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <select class="form-select @error('classe_id') error @enderror" id="classe_id" name="classe_id" required>
                                    <option value="">-- Sélectionner une classe --</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }} - {{ $classe->niveau->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('classe_id')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($anneeCourante)
                            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeCourante->id }}">
                            <div class="form-group">
                                <label class="form-label">Année universitaire <span class="text-danger">*</span></label>
                                <div class="form-input-locked">
                                    <i class="fas fa-lock me-2"></i>
                                    {{ $anneeCourante->name }} (Année courante - Verrouillée)
                                </div>
                                <small class="form-help">L'année universitaire est automatiquement définie sur l'année en cours.</small>
                            </div>
                            @else
                            <div class="form-group">
                                <label class="form-label">Année universitaire <span class="text-danger">*</span></label>
                                <div class="form-input error">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Aucune année universitaire active trouvée
                                </div>
                                <div class="form-error">Veuillez activer une année universitaire dans les paramètres.</div>
                            </div>
                            @endif

                            <div class="form-group">
                                <label for="semestre" class="form-label">Période <span class="text-danger">*</span></label>
                                <select class="form-select @error('semestre') error @enderror" id="semestre" name="semestre" required>
                                    <option value="">-- Sélectionner une période --</option>
                                    <option value="Semestre 1" {{ old('semestre') == 'Semestre 1' ? 'selected' : '' }}>Semestre 1</option>
                                    <option value="Semestre 2" {{ old('semestre') == 'Semestre 2' ? 'selected' : '' }}>Semestre 2</option>
                                    <option value="Année complète" {{ old('semestre') == 'Année complète' ? 'selected' : '' }}>Année complète</option>
                                </select>
                                @error('semestre')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                                <div class="form-input-group">
                                    <input type="date" class="form-input @error('date_debut') error @enderror"
                                           id="date_debut" name="date_debut" value="{{ old('date_debut', $semaineCourante['date_debut'] ?? '') }}" required>
                                    <button type="button" class="btn-acasi secondary" id="btn-semaine-courante">
                                        <i class="fas fa-calendar-week"></i> Semaine courante
                                    </button>
                                </div>
                                @error('date_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                                <input type="date" class="form-input @error('date_fin') error @enderror"
                                       id="date_fin" name="date_fin" value="{{ old('date_fin', $semaineCourante['date_fin'] ?? '') }}" required>
                                @error('date_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                                <small class="form-help">
                                    <i class="fas fa-info-circle"></i> La période doit être de 6 jours maximum (du lundi au samedi).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Section 2: Information -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-lightbulb"></i>
                            Information importante
                        </div>
                        <div class="main-card-subtitle">À savoir après la création</div>
                    </div>
                    <div class="main-card-body">
                        <div class="alert-info-modern">
                            <div class="alert-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="alert-content">
                                <h6>Prochaines étapes</h6>
                                <p>Après avoir créé l'emploi du temps, vous pourrez consulter la planification académique et ajouter des séances de cours basées sur cette planification.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Options et paramètres -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-cog"></i>
                            Options et paramètres
                        </div>
                        <div class="main-card-subtitle">Configuration de l'emploi du temps</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="form-checkbox">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                    <label for="is_active">Emploi du temps actif</label>
                                </div>
                                <small class="form-help">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Un seul emploi du temps peut être actif par classe à la fois. Si vous activez cet emploi du temps,
                                    les autres seront automatiquement désactivés.
                                </small>
                            </div>
                        </div>

                        <div class="alert-warning-modern">
                            <div class="alert-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="alert-content">
                                <h6>Remarque importante</h6>
                                <p>Après avoir créé l'emploi du temps, vous pourrez y ajouter des séances de cours. Assurez-vous que la classe sélectionnée a des matières et des enseignants assignés.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="button" class="btn-acasi secondary" onclick="window.location.href='{{ route('esbtp.emploi-temps.index') }}'">
                        <i class="fas fa-times"></i>Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Créer l'emploi du temps
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Amélioration des listes déroulantes avec Select2
    $('#classe_id, #annee_universitaire_id, #semestre').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Sélectionnez un élément'
    });

    // Bouton semaine courante
    document.getElementById('btn-semaine-courante').addEventListener('click', function() {
        document.getElementById('date_debut').value = '{{ $semaineCourante['date_debut'] }}';
        document.getElementById('date_fin').value = '{{ $semaineCourante['date_fin'] }}';
    });

    // Validation période maximum 6 jours (lundi-samedi)
    document.getElementById('date_fin').addEventListener('change', function() {
        const dateDebut = new Date(document.getElementById('date_debut').value);
        const dateFin = new Date(this.value);

        if (dateDebut && dateFin) {
            const diffTime = Math.abs(dateFin - dateDebut);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 5) {
                alert('La période de l\'emploi du temps ne doit pas dépasser 6 jours (du lundi au samedi).');
                this.value = '';
            }
        }
    });
});
</script>
@endsection

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : L'emploi du temps sera créé pour la nouvelle année courante</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte la création des emplois du temps.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Créer des emplois du temps pour 2024-2025<br>
                    • Année courante = 2023-2024 → Créer des emplois du temps pour 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                @can('annees.view')
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });

    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>
