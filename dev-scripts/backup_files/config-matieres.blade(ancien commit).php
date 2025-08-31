@extends('layouts.app')

@section('title', 'Configuration des matières par type de formation')

@section('styles')
<style>
    .container-config {
        padding: 20px;
    }
    .matiere-box {
        border: 1px solid #ccc;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    .matiere-item {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        margin: 5px;
        padding: 10px;
        border-radius: 4px;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
    }
    .matiere-item:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 3px 5px rgba(0,0,0,0.1);
    }
    .matiere-item.general {
        border-left: 4px solid #0d6efd;
    }
    .matiere-item.technique {
        border-left: 4px solid #dc3545;
    }
    .matiere-item.non-classe {
        border-left: 4px solid #6c757d;
    }
    .matiere-item label {
        margin-bottom: 0;
        cursor: pointer;
        width: 100%;
        display: flex;
        align-items: center;
    }
    .matiere-item .checkbox-container {
        display: flex;
        align-items: center;
        margin-right: 10px;
    }
    .dropzone {
        min-height: 200px;
        border: 2px dashed #ccc;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .section-title {
        margin-bottom: 15px;
        font-weight: bold;
        color: #333;
    }
    .dragndrop-info {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f0f7ff;
        border-left: 4px solid #0d6efd;
        border-radius: 4px;
    }
    .info-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }
    .empty-message {
        text-align: center;
        padding: 30px;
        color: #6c757d;
        font-style: italic;
        background-color: #f9f9f9;
        border-radius: 5px;
        border: 1px dashed #dee2e6;
    }
    .debug-info {
        margin-top: 20px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .matiere-icon {
        margin-right: 8px;
        color: #6c757d;
    }
    .alert-guide {
        background-color: #f8f9fa;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .selection-action {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }
    .matiere-controls {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        align-items: center;
    }
    .search-matiere {
        width: 100%;
        max-width: 300px;
    }
</style>
@endsection

@section('content')
<div class="container-config">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Configuration des matières par type de formation</h2>

            <!-- Toast Notification Container -->
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080">
                <div id="configToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header" id="toast-header">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong class="me-auto" id="toast-title">Notification</strong>
                        <small>À l'instant</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body" id="toast-message">
                        Message de notification
                    </div>
                </div>
            </div>

            <!-- Session messages displayed as toasts -->
            @if(session('success') || session('error'))
            <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                <i class="fas fa-{{ session('success') ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                {{ session('success') ?? session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="info-header">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Classe:</strong> {{ $classe->libelle ?? $classe->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Filière:</strong> {{ $classe->filiere->nom ?? $classe->filiere->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Niveau:</strong> {{ $classe->niveau->libelle ?? $classe->niveau->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Période:</strong>
                            @if($periode == 'semestre1')
                                Premier Semestre
                            @elseif($periode == 'semestre2')
                                Deuxième Semestre
                            @else
                                Annuel
                            @endif
                        </p>
                        <p class="mb-1"><strong>Année:</strong> {{ $anneeUniversitaire->libelle ?? $anneeUniversitaire->name ?? 'N/A' }}</p>
                        @if(isset($etudiant))
                        <p class="mb-1"><strong>Étudiant:</strong> {{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dragndrop-info">
        <h5><i class="fas fa-exclamation-circle me-2"></i>Instructions pour la configuration des matières</h5>
        <p class="mb-0"><i class="fas fa-check-square me-2"></i>Sélectionnez les matières et choisissez leur type d'enseignement pour configurer le bulletin.</p>
        <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Cette configuration détermine comment les matières seront organisées dans le bulletin de l'étudiant.</p>
        <p class="mb-0"><i class="fas fa-save me-2"></i>Les modifications ne seront appliquées qu'après avoir cliqué sur le bouton "Enregistrer la configuration".</p>
    </div>

    <div class="alert-guide">
        <h5><i class="fas fa-lightbulb me-2 text-warning"></i>Guide d'utilisation</h5>
        <ol>
            <li>Toutes les matières disponibles sont listées ci-dessous</li>
            <li>Cochez les matières à inclure dans le bulletin</li>
            <li>Sélectionnez le type d'enseignement pour chaque matière (général ou technique)</li>
            <li>Les matières non sélectionnées ne seront pas incluses dans le bulletin</li>
            <li>Après avoir configuré toutes les matières, cliquez sur "Enregistrer la configuration"</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <h4>Configuration des matières pour le bulletin de {{ $etudiant->nom_prenom ?? $etudiant->nom . ' ' . $etudiant->prenoms }}</h4>
            <div class="alert alert-info">
                <p>Sélectionnez les matières à inclure dans le bulletin et classez-les par type d'enseignement.</p>
                <p>Les matières d'enseignement général apparaîtront dans la première partie du bulletin, tandis que les matières d'enseignement technique apparaîtront dans la seconde partie.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('esbtp.bulletins.save-config-matieres') }}" method="POST" id="configMatieresForm">
        @csrf
        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
        <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
        <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">
        <input type="hidden" name="periode" value="{{ $periode }}">
        @if(isset($bulletin))
            <input type="hidden" name="bulletin" value="{{ $bulletin }}">
        @endif

                <div class="card">
            <div class="card-header">
                <h3>Liste des matières</h3>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" id="toutes-generales">Toutes générales</button>
                    <button type="button" class="btn btn-secondary" id="toutes-techniques">Toutes techniques</button>
                    <button type="button" class="btn btn-warning" id="aucune">Aucune</button>
                        </div>
                    </div>

                    <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Type d'enseignement</th>
                                <th>Ne pas inclure</th>
                            </tr>
                        </thead>
                        <tbody>
                                @foreach($matieres as $matiere)
                            <tr>
                                <td>{{ $matiere->nom ?? $matiere->name }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio"
                                               class="btn-check matiere-type"
                                               name="matiere_type[{{ $matiere->id }}]"
                                               id="general_{{ $matiere->id }}"
                                               value="general"
                                               data-matiere-id="{{ $matiere->id }}"
                                               {{ in_array($matiere->id, $general ?? []) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="general_{{ $matiere->id }}">
                                            Enseignement général
                                        </label>

                                        <input type="radio"
                                               class="btn-check matiere-type"
                                               name="matiere_type[{{ $matiere->id }}]"
                                               id="technique_{{ $matiere->id }}"
                                               value="technique"
                                               data-matiere-id="{{ $matiere->id }}"
                                               {{ in_array($matiere->id, $technique ?? []) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="technique_{{ $matiere->id }}">
                                            Enseignement technique
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="radio"
                                           class="btn-check matiere-type"
                                           name="matiere_type[{{ $matiere->id }}]"
                                           id="none_{{ $matiere->id }}"
                                           value="none"
                                           data-matiere-id="{{ $matiere->id }}"
                                           {{ !in_array($matiere->id, array_merge($general ?? [], $technique ?? [])) ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger" for="none_{{ $matiere->id }}">
                                        Ne pas inclure
                                    </label>
                                </td>
                            </tr>
                                @endforeach
                        </tbody>
                    </table>
                                    </div>
                                </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="/esbtp/resultats/etudiant/{{ $etudiant->id }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-2"></i>Annuler les modifications
                        </a>
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer la configuration
                        </button>
                        </div>
                    <div class="btn-group">
                        <button type="submit" name="action" value="save_and_edit_profs" class="btn btn-success">
                            <i class="fas fa-user-edit me-2"></i>Enregistrer et éditer les professeurs
                        </button>
                        <button type="submit" name="action" value="save_and_return" class="btn btn-info">
                            <i class="fas fa-arrow-left me-2"></i>Enregistrer et retourner aux résultats
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="debug-info mt-4">
            <h4>Informations de débogage</h4>
            <div id="debug-content">
                <p>Nombre de matières: <span id="total-count">0</span></p>
                <p>Matières générales: <span id="general-count">0</span></p>
                <p>Matières techniques: <span id="technique-count">0</span></p>
                <p>Changements en cours: <span id="changes-count">0</span></p>
            </div>
        </div>
    </form>
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
            const totalCount = generalCount + techniqueCount;

            document.getElementById('total-count').textContent = totalCount;
            document.getElementById('general-count').textContent = generalCount;
            document.getElementById('technique-count').textContent = techniqueCount;
            document.getElementById('changes-count').textContent = '0';

            console.log('Compteurs mis à jour:', {
                total: totalCount,
                general: generalCount,
                technique: techniqueCount
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

        // Boutons de sélection rapide
        document.getElementById('toutes-generales').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="general"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            console.log('Toutes les matières marquées comme générales');
        });

        document.getElementById('toutes-techniques').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="technique"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            console.log('Toutes les matières marquées comme techniques');
        });

        document.getElementById('aucune').addEventListener('click', function() {
            document.querySelectorAll('.matiere-type[value="none"]').forEach(input => {
                input.checked = true;
            });
            updateCounters();
            console.log('Aucune matière sélectionnée');
        });

        // Gestion du formulaire
        const form = document.getElementById('configMatieresForm');
        const submitButtons = form.querySelectorAll('button[type="submit"]');

        submitButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Empêcher la soumission par défaut

                // Stocker l'action dans un champ caché
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = this.value;
                form.appendChild(actionInput);

                // Vérifier si au moins une matière est sélectionnée
                const hasSelectedMatieres = updateCounters();

                if (!hasSelectedMatieres) {
                    showToast('Veuillez sélectionner au moins une matière et son type d\'enseignement.', 'warning');
                    return;
                }

                // Désactiver tous les boutons et montrer l'indicateur de chargement
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.dataset.originalText = originalText;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
                });

                // Ajouter une notification pour l'utilisateur
                showToast('Sauvegarde de la configuration en cours...', 'info');

                console.log('Soumission du formulaire avec action:', actionInput.value);
                console.log('Données du formulaire:', new FormData(form));

                // Soumettre le formulaire explicitement
                form.submit();
            });
        });
    });
</script>
@endsection
