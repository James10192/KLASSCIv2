@extends('layouts.app')

@section('title', 'Configuration des matières - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.stat-item {
    padding: 1rem;
    border-radius: 8px;
    background: rgba(0,0,0,0.02);
    border: 1px solid rgba(0,0,0,0.05);
}
.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}
.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}
.btn-group .btn-check:checked + .btn {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-cog me-2"></i>Configuration des matières</h1>
                <p class="header-subtitle">Configurer les matières par type d'enseignement pour le bulletin</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe['id'] ?? $classe->id]) }}" class="btn btn-outline-primary me-2" title="Gérer les matières de cette classe">
                    <i class="fas fa-sliders-h me-1"></i>Gérer les matières de la classe
                </a>
                @endif
                @role('superAdmin')
                <a href="{{ route('esbtp.matieres.index') }}" class="btn btn-outline-info me-2" title="Gestion globale des matières">
                    <i class="fas fa-cog me-1"></i>Gestion globale
                </a>
                @endrole
                <span class="badge bg-primary fs-6">
                    <i class="fas fa-graduation-cap me-1"></i>
                    {{ $etudiant['nom'] ?? '' }} {{ $etudiant['prenoms'] ?? '' }}
                </span>
            </div>
        </div>

        <!-- Informations contextuelles -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe['libelle'] ?? $classe['name'] ?? 'N/A' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $classe['filiere']['nom'] ?? $classe['filiere']['name'] ?? 'N/A' }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Période</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">
                    @if($periode == 'semestre1')
                        S1
                    @elseif($periode == 'semestre2')
                        S2
                    @else
                        Annuel
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-alt"></i>
                    {{ $anneeUniversitaire['libelle'] ?? $anneeUniversitaire['name'] ?? 'N/A' }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières disponibles</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ count($matieres) }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-book"></i>
                    À configurer
                </div>
            </div>
        </div>

        <!-- Alertes de session -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                    <ol class="mb-0">
                        <li>Sélectionnez le type d'enseignement pour chaque matière (général ou technique)</li>
                        <li>Les matières générales apparaîtront en premier sur le bulletin</li>
                        <li>Les matières techniques apparaîtront en second sur le bulletin</li>
                        <li>Cliquez sur "Ne pas inclure" pour exclure une matière du bulletin</li>
                        <li>Utilisez les boutons rapides pour configurer toutes les matières d'un coup</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Configuration des matières -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Configuration des matières
                </div>
                <div class="main-card-subtitle">Sélectionnez le type d'enseignement pour chaque matière</div>
            </div>

            <div class="main-card-body">
                <form action="{{ route('esbtp.bulletins.save-config-matieres') }}" method="POST" id="configMatieresForm">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $classe['id'] }}">
                    <input type="hidden" name="etudiant_id" value="{{ $etudiant['id'] }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire['id'] }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    @if(isset($bulletin))
                        <input type="hidden" name="bulletin" value="{{ $bulletin }}">
                    @endif

                    <!-- Boutons d'action rapide -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn-acasi primary" id="toutes-generales">
                                    <i class="fas fa-book"></i>Toutes générales
                                </button>
                                <button type="button" class="btn-acasi secondary" id="toutes-techniques">
                                    <i class="fas fa-cog"></i>Toutes techniques
                                </button>
                                <button type="button" class="btn-acasi warning" id="aucune">
                                    <i class="fas fa-times"></i>Aucune
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table des matières -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40%">Matière</th>
                                    <th width="35%" class="text-center">Type d'enseignement</th>
                                    <th width="25%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($matieres as $matiere)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-book text-primary me-3"></i>
                                            <span class="fw-medium">{{ $matiere->nom ?? $matiere->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <input type="radio"
                                                   class="btn-check matiere-type"
                                                   name="matiere_type[{{ $matiere->id }}]"
                                                   id="general_{{ $matiere->id }}"
                                                   value="general"
                                                   data-matiere-id="{{ $matiere->id }}"
                                                   {{ in_array($matiere->id, $general ?? []) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary btn-sm" for="general_{{ $matiere->id }}">
                                                <i class="fas fa-graduation-cap me-1"></i>Général
                                            </label>

                                            <input type="radio"
                                                   class="btn-check matiere-type"
                                                   name="matiere_type[{{ $matiere->id }}]"
                                                   id="technique_{{ $matiere->id }}"
                                                   value="technique"
                                                   data-matiere-id="{{ $matiere->id }}"
                                                   {{ in_array($matiere->id, $technique ?? []) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success btn-sm" for="technique_{{ $matiere->id }}">
                                                <i class="fas fa-tools me-1"></i>Technique
                                            </label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio"
                                               class="btn-check matiere-type"
                                               name="matiere_type[{{ $matiere->id }}]"
                                               id="none_{{ $matiere->id }}"
                                               value="none"
                                               data-matiere-id="{{ $matiere->id }}"
                                               {{ !in_array($matiere->id, array_merge($general ?? [], $technique ?? [])) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger btn-sm" for="none_{{ $matiere->id }}">
                                            <i class="fas fa-eye-slash me-1"></i>Exclure
                                        </label>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                        Aucune matière trouvée
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <a href="/esbtp/resultats/etudiant/{{ $etudiant->id }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="save" class="btn-acasi primary">
                                <i class="fas fa-save"></i>Enregistrer
                            </button>
                            <button type="submit" name="action" value="save_and_edit_profs" class="btn-acasi success">
                                <i class="fas fa-user-edit"></i>Éditer les professeurs
                            </button>
                            <button type="submit" name="action" value="save_and_return" class="btn-acasi info">
                                <i class="fas fa-arrow-left"></i>Retour résultats
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques en temps réel -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistiques de configuration
                </div>
            </div>
            <div class="main-card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-primary" id="total-count">0</div>
                            <div class="stat-label">Matières incluses</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-success" id="general-count">0</div>
                            <div class="stat-label">Générales</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-info" id="technique-count">0</div>
                            <div class="stat-label">Techniques</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-secondary" id="excluded-count">0</div>
                            <div class="stat-label">Exclues</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toast notifications setup
        const toast = new bootstrap.Toast(document.getElementById('configToast'));

        function showToast(message, type = 'info') {
            const toastEl = document.getElementById('configToast');
            const toastHeader = document.getElementById('toast-header');
            const toastTitle = document.getElementById('toast-title');
            const toastMessage = document.getElementById('toast-message');

            // Set content based on type
            if (type === 'success') {
                toastHeader.classList.add('bg-success', 'text-white');
                toastHeader.classList.remove('bg-danger', 'bg-warning', 'bg-info');
                toastTitle.innerText = 'Succès';
            } else if (type === 'error') {
                toastHeader.classList.add('bg-danger', 'text-white');
                toastHeader.classList.remove('bg-success', 'bg-warning', 'bg-info');
                toastTitle.innerText = 'Erreur';
            } else if (type === 'warning') {
                toastHeader.classList.add('bg-warning');
                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-info');
                toastTitle.innerText = 'Attention';
                } else {
                toastHeader.classList.add('bg-info', 'text-white');
                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning');
                toastTitle.innerText = 'Information';
            }

            toastMessage.innerText = message;
            toast.show();
        }

        // Show flash messages if they exist
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif

        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif

        // Fonction pour mettre à jour les compteurs
        function updateCounters() {
            const generalInputs = document.querySelectorAll('.matiere-type[value="general"]:checked');
            const techniqueInputs = document.querySelectorAll('.matiere-type[value="technique"]:checked');
            const noneInputs = document.querySelectorAll('.matiere-type[value="none"]:checked');

            const generalCount = generalInputs.length;
            const techniqueCount = techniqueInputs.length;
            const excludedCount = noneInputs.length;
            const totalCount = generalCount + techniqueCount;

            document.getElementById('total-count').textContent = totalCount;
            document.getElementById('general-count').textContent = generalCount;
            document.getElementById('technique-count').textContent = techniqueCount;
            document.getElementById('excluded-count').textContent = excludedCount;

            console.log('Compteurs mis à jour:', {
                total: totalCount,
                general: generalCount,
                technique: techniqueCount,
                excluded: excludedCount
            });

            return totalCount > 0; // Retourne true si au moins une matière est sélectionnée
        }

        // Mettre à jour les compteurs au chargement
        updateCounters();

        // Écouter les changements de type
        document.querySelectorAll('.matiere-type').forEach(input => {
            input.addEventListener('change', function() {
                updateCounters();
            });
        });

        // Configuration du formulaire
        const form = document.getElementById('configMatieresForm');
        form.addEventListener('submit', function(e) {
            const hasSelectedMatieres = updateCounters();

            if (!hasSelectedMatieres) {
                e.preventDefault();
                showToast('Veuillez sélectionner au moins une matière pour continuer.', 'error');
            }
        });

        // Boutons de sélection rapide
        document.getElementById('toutes-generales').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="general"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            showToast('Toutes les matières ont été définies comme générales.', 'success');
        });

        document.getElementById('toutes-techniques').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="technique"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            showToast('Toutes les matières ont été définies comme techniques.', 'success');
        });

        document.getElementById('aucune').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="none"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            showToast('Aucune matière n\'est sélectionnée.', 'warning');
        });
    });
</script>
@endsection
