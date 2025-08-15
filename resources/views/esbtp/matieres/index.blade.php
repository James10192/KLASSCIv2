@extends('layouts.app')

@section('title', 'Liste des matières')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
<style>
    .gap-1 {
        gap: 0.25rem !important;
    }
    
    /* Modal enhancements */
    .form-check:hover {
        background-color: rgba(var(--primary-rgb), 0.05) !important;
        border-radius: 6px;
    }
    
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    #combinations-preview .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .badge-link {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .badge-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
    
    .modal-xl {
        max-width: 1200px;
    }
    
    .combinations-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>
@endsection

@section('content')
<div class="main-content">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-book me-2"></i>Gestion des Matières</h1>
            <p class="header-subtitle">Liste des matières disponibles dans votre établissement</p>
        </div>
        <div class="header-actions">
            <input type="text" class="search-bar" placeholder="Rechercher une matière..." id="searchInput">
        </div>
    </div>

    <!-- Action Bar -->
    <div class="card-moderne mb-lg">
        <div class="p-lg">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2" id="bulk-actions" style="display: none;">
                    <button id="btn-attach-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-link"></i> Attacher
                    </button>
                    <button id="btn-edit-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button id="btn-delete-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('esbtp.matieres.attach-to-classes') }}" class="btn-acasi secondary">
                        <i class="fas fa-link"></i> Attacher aux classes
                    </a>
                    <a href="{{ route('esbtp.matieres.create') }}" class="btn-acasi primary">
                        <i class="fas fa-plus"></i> Ajouter une matière
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Success Alert -->
    @if(session('success'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle color-success me-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Matières Table -->
    <div class="card-moderne">
        <div class="main-card-header">
            <h3 class="main-card-title">
                <i class="fas fa-table"></i>Liste des Matières
            </h3>
            <p class="main-card-subtitle">{{ $matieres->count() }} matière(s) trouvée(s)</p>
        </div>
        <div class="main-card-body">
            <div class="table-responsive">
                <table class="table datatable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                    <label class="form-check-label" for="select-all"></label>
                                </div>
                            </th>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Unité d'enseignement</th>
                            <th>Coefficient</th>
                            <th>Total heures</th>
                            <th>Filières</th>
                            <th>Niveaux</th>
                            <th>Statut</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                            <tbody>
                                @foreach($matieres as $matiere)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input matiere-checkbox" type="checkbox" id="matiere-{{ $matiere->id }}" value="{{ $matiere->id }}">
                                                <label class="form-check-label" for="matiere-{{ $matiere->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge primary">{{ $matiere->code }}</span>
                                        </td>
                                        <td>
                                            <div class="font-semibold color-primary">{{ $matiere->name }}</div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $matiere->uniteEnseignement ? $matiere->uniteEnseignement->name : 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="font-bold color-accent">{{ $matiere->coefficient_default }}</span>
                                        </td>
                                        <td>
                                            <span class="font-bold color-primary">{{ $matiere->total_heures_default }}h</span>
                                        </td>
                                        <td>
                                            @if($matiere->filieres->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($matiere->filieres as $filiere)
                                                        <span class="badge bg-primary text-white" title="{{ $filiere->name }}">
                                                            {{ $filiere->code ?? Str::limit($filiere->name, 8) }}
                                                        </span>
                                                    @endforeach
                                                    @if($matiere->filieres->count() > 3)
                                                        <span class="badge bg-info text-white" title="{{ $matiere->filieres->count() }} filières au total">
                                                            +{{ $matiere->filieres->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-minus me-1"></i>Aucune
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($matiere->niveaux->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($matiere->niveaux as $niveau)
                                                        <span class="badge bg-info text-white" title="{{ $niveau->name }}">
                                                            {{ $niveau->code ?? Str::limit($niveau->name, 8) }}
                                                        </span>
                                                    @endforeach
                                                    @if($matiere->niveaux->count() > 3)
                                                        <span class="badge bg-warning text-dark" title="{{ $matiere->niveaux->count() }} niveaux au total">
                                                            +{{ $matiere->niveaux->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-link me-1"></i>
                                                        {{ $matiere->filieres->count() * $matiere->niveaux->count() }} combinaison(s)
                                                    </small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-minus me-1"></i>Aucun
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($matiere->is_active)
                                                <span class="badge success">
                                                    <i class="fas fa-check-circle me-1"></i>Actif
                                                </span>
                                            @else
                                                <span class="badge danger">
                                                    <i class="fas fa-times-circle me-1"></i>Inactif
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('esbtp.matieres.show', $matiere->id) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Voir" 
                                                   style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#configureModal"
                                                        data-matiere-id="{{ $matiere->id }}"
                                                        data-matiere-name="{{ $matiere->name }}"
                                                        title="Configurer liaisons"
                                                        style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                                <a href="{{ route('esbtp.matieres.edit', $matiere->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="Modifier"
                                                   style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        data-toggle="modal" 
                                                        data-target="#deleteModal{{ $matiere->id }}" 
                                                        title="Supprimer"
                                                        style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal de suppression -->
                                            <div class="modal fade" id="deleteModal{{ $matiere->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $matiere->id }}" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $matiere->id }}">Confirmation de suppression</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer la matière <strong>{{ $matiere->name }}</strong> ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('esbtp.matieres.destroy', $matiere->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de configuration des liaisons -->
<div class="modal fade" id="configureModal" tabindex="-1" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="configureModalLabel" style="font-weight: 600;">
                        <i class="fas fa-link me-2"></i>Configuration des liaisons
                    </h4>
                    <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">
                        Matière : <span id="modal-matiere-name" style="font-weight: 500;"></span>
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="configureLiaisonsForm">
                    @csrf
                    <input type="hidden" id="modal-matiere-id" name="matiere_id">
                    
                    <div class="row">
                        <!-- Filières disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-graduation-cap"></i>Filières
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les filières concernées</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="filieres-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPFiliere::where('is_active', true)->get() as $filiere)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input filiere-checkbox" type="checkbox" 
                                                       value="{{ $filiere->id }}" id="filiere-{{ $filiere->id }}" name="filieres[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="filiere-{{ $filiere->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $filiere->name }}</span>
                                                            @if($filiere->code)
                                                                <span class="badge secondary ms-2">{{ $filiere->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Niveaux disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-layer-group"></i>Niveaux d'étude
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les niveaux concernés</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="niveaux-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPNiveauEtude::where('is_active', true)->get() as $niveau)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input niveau-checkbox" type="checkbox" 
                                                       value="{{ $niveau->id }}" id="niveau-{{ $niveau->id }}" name="niveaux[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="niveau-{{ $niveau->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $niveau->name }}</span>
                                                            @if($niveau->code)
                                                                <span class="badge secondary ms-2">{{ $niveau->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aperçu des combinaisons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-eye"></i>Aperçu des combinaisons
                                    </h3>
                                    <p class="main-card-subtitle">Combinaisons filières/niveaux sélectionnées</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="combinations-preview" class="card-moderne" style="background: #e7f3ff; border: 1px solid #0ea5e9; padding: 1.5rem; border-radius: 8px;">
                                        <div class="d-flex align-items-center" style="color: #0369a1;">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>Sélectionnez des filières et des niveaux pour voir les combinaisons possibles.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-light); padding: 1.5rem 2rem; background: var(--bg-light); border-radius: 0 0 12px 12px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Les modifications seront sauvegardées immédiatement</small>
                    </div>
                    <div>
                        <button type="button" class="btn-acasi secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" class="btn-acasi primary" id="save-liaisons-btn">
                            <i class="fas fa-save me-1"></i>Enregistrer les liaisons
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialisation de DataTables
        var table = $('.datatable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            }
        });

        // Gestion de la barre de recherche
        $('#searchInput').on('input', function() {
            table.search(this.value).draw();
        });

        // Gestion de la sélection de toutes les cases à cocher
        $('#select-all').on('change', function() {
            $('.matiere-checkbox').prop('checked', $(this).prop('checked'));
            updateActionButtons();
        });

        // Gestion de la sélection individuelle
        $(document).on('change', '.matiere-checkbox', function() {
            updateActionButtons();

            // Si toutes les cases sont cochées, cocher "Sélectionner tout"
            if ($('.matiere-checkbox:checked').length === $('.matiere-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
        });

        // Mise à jour de l'affichage des boutons d'action
        function updateActionButtons() {
            var selectedCount = $('.matiere-checkbox:checked').length;
            const bulkActions = $('#bulk-actions');

            if (selectedCount > 0) {
                bulkActions.show();
                $('#btn-attach-selected').removeClass('d-none');
                $('#btn-delete-selected').removeClass('d-none');

                // Le bouton Modifier n'est visible que si une seule matière est sélectionnée
                if (selectedCount === 1) {
                    $('#btn-edit-selected').removeClass('d-none');
                } else {
                    $('#btn-edit-selected').addClass('d-none');
                }
            } else {
                bulkActions.hide();
            }
        }

        // Action du bouton Attacher
        $('#btn-attach-selected').on('click', function() {
            var selectedIds = [];
            $('.matiere-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                // Rediriger vers la page d'attachement avec les IDs sélectionnés
                window.location.href = "{{ route('esbtp.matieres.attach-to-classes') }}?matieres=" + selectedIds.join(',');
            }
        });

        // Action du bouton Modifier
        $('#btn-edit-selected').on('click', function() {
            var selectedId = $('.matiere-checkbox:checked').first().val();
            if (selectedId) {
                window.location.href = "{{ url('esbtp/matieres') }}/" + selectedId + "/edit";
            }
        });

        // Action du bouton Supprimer
        $('#btn-delete-selected').on('click', function() {
            var selectedIds = [];
            $('.matiere-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0 && confirm('Êtes-vous sûr de vouloir supprimer les matières sélectionnées ?')) {
                // Créer un formulaire pour soumettre la suppression
                var form = $('<form>', {
                    'method': 'POST',
                    'action': "{{ route('esbtp.matieres.bulk-delete') }}"
                });

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': "{{ csrf_token() }}"
                }));

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));

                // Ajouter les IDs des matières sélectionnées
                selectedIds.forEach(function(id) {
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'matieres[]',
                        'value': id
                    }));
                });

                // Ajouter le formulaire au document et le soumettre
                $('body').append(form);
                form.submit();
            }
        });

        // ===== GESTION DU MODAL DE CONFIGURATION DES LIAISONS =====
        
        // Ouvrir le modal et charger les données de la matière
        $('#configureModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const matiereId = button.getAttribute('data-matiere-id');
            const matiereName = button.getAttribute('data-matiere-name');
            
            // Mettre à jour le titre du modal
            document.getElementById('modal-matiere-name').textContent = matiereName;
            document.getElementById('modal-matiere-id').value = matiereId;
            
            // Réinitialiser les checkboxes
            $('.filiere-checkbox, .niveau-checkbox').prop('checked', false);
            updateCombinationsPreview();
            
            // Charger les liaisons existantes
            loadExistingLiaisons(matiereId);
        });

        // Fonction pour charger les liaisons existantes
        function loadExistingLiaisons(matiereId) {
            fetch(`/esbtp/matieres/${matiereId}/liaisons`)
                .then(response => response.json())
                .then(data => {
                    console.log('Liaisons existantes:', data);
                    
                    // Cocher les filières existantes
                    if (data.filieres) {
                        data.filieres.forEach(filiereId => {
                            $(`#filiere-${filiereId}`).prop('checked', true);
                        });
                    }
                    
                    // Cocher les niveaux existants
                    if (data.niveaux) {
                        data.niveaux.forEach(niveauId => {
                            $(`#niveau-${niveauId}`).prop('checked', true);
                        });
                    }
                    
                    updateCombinationsPreview();
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des liaisons:', error);
                });
        }

        // Mise à jour de l'aperçu des combinaisons
        function updateCombinationsPreview() {
            const selectedFilieres = [];
            const selectedNiveaux = [];
            
            $('.filiere-checkbox:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedFilieres.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            $('.niveau-checkbox:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedNiveaux.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            const previewDiv = $('#combinations-preview');
            
            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                previewDiv.html(`
                    <div class="d-flex align-items-center" style="color: #0369a1;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                    </div>
                `).css({
                    'background': '#e7f3ff',
                    'border': '1px solid #0ea5e9',
                    'padding': '1.5rem',
                    'border-radius': '8px'
                });
                return;
            }
            
            let combinationsHtml = `
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle me-2" style="color: #059669;"></i>
                    <strong style="color: #047857;">${selectedFilieres.length * selectedNiveaux.length} combinaison(s) sélectionnée(s)</strong>
                </div>
                <div class="d-flex flex-wrap gap-2">
            `;
            
            selectedFilieres.forEach(filiere => {
                selectedNiveaux.forEach(niveau => {
                    combinationsHtml += `
                        <span class="badge primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                            <i class="fas fa-link me-1"></i>
                            ${filiere.name} ↔ ${niveau.name}
                        </span>
                    `;
                });
            });
            
            combinationsHtml += '</div>';
            
            previewDiv.html(combinationsHtml).css({
                'background': '#d1fae5',
                'border': '1px solid #10b981',
                'padding': '1.5rem',
                'border-radius': '8px'
            });
        }

        // Écouter les changements dans les checkboxes
        $(document).on('change', '.filiere-checkbox, .niveau-checkbox', updateCombinationsPreview);

        // Sauvegarde des liaisons
        $('#save-liaisons-btn').on('click', function() {
            const matiereId = $('#modal-matiere-id').val();
            const selectedFilieres = $('.filiere-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            const selectedNiveaux = $('.niveau-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                alert('Veuillez sélectionner au moins une filière et un niveau.');
                return;
            }
            
            const saveBtn = $(this);
            const originalText = saveBtn.html();
            
            // Désactiver le bouton pendant la sauvegarde
            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...');
            
            fetch(`/esbtp/matieres/${matiereId}/update-liaisons`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    filieres: selectedFilieres,
                    niveaux: selectedNiveaux
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    $('#configureModal').modal('hide');
                    
                    // Afficher un message de succès
                    const alertDiv = $(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $('.card-body').prepend(alertDiv);
                    
                    // Recharger la page après 2 secondes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la sauvegarde');
            })
            .finally(() => {
                // Réactiver le bouton
                saveBtn.prop('disabled', false).html(originalText);
            });
        });
    });
</script>
@endsection
