@extends('layouts.app')

@section('title', 'Modifier un étudiant - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier l'étudiant</h1>
                <p class="header-subtitle">{{ $etudiant->nom }} {{ $etudiant->prenoms }} - Matricule: {{ $etudiant->matricule ?? 'N/A' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn-acasi info me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.etudiants.update', $etudiant) }}" method="POST" enctype="multipart/form-data" id="editEtudiantForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $etudiant->id }}">
                        <input type="hidden" name="form_submit_token" value="{{ md5(uniqid(mt_rand(), true)) }}">

                        <!-- Debugger temporaire pour vérifier les valeurs -->
                        <!--<div class="mb-4 p-3 bg-light">
                            <h6>Valeurs actuelles pour le debugging (À supprimer après résolution du problème)</h6>
                            <ul>
                                <li><strong>Email personnel (direct) :</strong> {{ $etudiant->email_personnel }}</li>
                                <li><strong>Email personnel (from array) :</strong> {{ $etudiant['email_personnel'] }}</li>
                                <li><strong>Genre/Sexe (direct) :</strong> {{ $etudiant->genre }} / {{ $etudiant->sexe }}</li>
                                <li><strong>Genre/Sexe (from array) :</strong> {{ $etudiant['genre'] ?? 'Non défini' }} / {{ $etudiant['sexe'] ?? 'Non défini' }}</li>
                                <li><strong>Toutes les propriétés :</strong> <pre>{{ print_r($etudiant->toArray(), true) }}</pre></li>
                            </ul>
                        </div>-->

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Informations personnelles</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="matricule" class="form-label">Matricule</label>
                                                <input type="text" class="form-control" id="matricule" name="matricule" value="{{ $etudiant->matricule }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">Le matricule ne peut pas être modifié.</small>
                                                @endif
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="nom" class="form-label">Nom</label>
                                                <input type="text" class="form-control" id="nom" name="nom" value="{{ old('nom', $etudiant->nom) }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">Le nom ne peut pas être modifié.</small>
                                                @endif
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="prenoms" class="form-label">Prénom(s)</label>
                                                <input type="text" class="form-control" id="prenoms" name="prenoms" value="{{ old('prenoms', $etudiant->prenoms) }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">Les prénoms ne peuvent pas être modifiés.</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="sexe" class="form-label">Genre</label>
                                                <select class="form-select" id="sexe" name="sexe" {{ auth()->user()->hasRole('superAdmin') ? '' : 'disabled' }}>
                                                    <option value="M" {{ old('sexe', $etudiant->sexe) == 'M' ? 'selected' : '' }}>Masculin</option>
                                                    <option value="F" {{ old('sexe', $etudiant->sexe) == 'F' ? 'selected' : '' }}>Féminin</option>
                                                </select>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">Le genre ne peut pas être modifié.</small>
                                                @endif
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="{{ old('date_naissance', $etudiant->date_naissance ? $etudiant->date_naissance->format('Y-m-d') : '') }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">La date de naissance ne peut pas être modifiée.</small>
                                                @endif
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance', $etudiant->lieu_naissance) }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                @if(!auth()->user()->hasRole('superAdmin'))
                                                    <small class="form-text text-muted">Le lieu de naissance ne peut pas être modifié.</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-4 mb-3">
                                                <label for="nationalite" class="form-label">Nationalité</label>
                                                <select class="form-select @error('nationalite') is-invalid @enderror" id="nationalite" name="nationalite" {{ auth()->user()->hasRole('superAdmin') ? '' : 'disabled' }}>
                                                    @include('esbtp.partials.nationality-options', ['selected' => old('nationalite', $etudiant->nationalite)])
                                                </select>
                                                @if(auth()->user()->hasRole('superAdmin'))
                                                    @error('nationalite')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                @else
                                                    <small class="form-text text-muted">La nationalité ne peut pas être modifiée.</small>
                                                @endif
                                                @unless(auth()->user()->hasRole('superAdmin'))
                                                    <input type="hidden" name="nationalite" value="{{ old('nationalite', $etudiant->nationalite) }}">
                                                @endunless
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone', $etudiant->telephone) }}" required>
                                                @error('telephone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="email_personnel" class="form-label">Email</label>
                                                <input type="email" class="form-control @error('email_personnel') is-invalid @enderror" id="email_personnel" name="email_personnel" value="{{ old('email_personnel', $etudiant->email_personnel) }}">
                                                @error('email_personnel')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="ville" class="form-label">Ville de résidence</label>
                                                <input type="text" class="form-control @error('ville') is-invalid @enderror" id="ville" name="ville" value="{{ old('ville', $etudiant->ville) }}">
                                                @error('ville')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <!-- Debug info: {{ $etudiant->ville ?? 'Non défini' }} -->
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="commune" class="form-label">Commune de résidence</label>
                                                <input type="text" class="form-control @error('commune') is-invalid @enderror" id="commune" name="commune" value="{{ old('commune', $etudiant->commune) }}">
                                                @error('commune')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <!-- Debug info: {{ $etudiant->commune ?? 'Non défini' }} -->
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="adresse" class="form-label">Adresse complète</label>
                                                <input type="text" class="form-control @error('adresse') is-invalid @enderror" id="adresse" name="adresse" value="{{ old('adresse', $etudiant->adresse) }}">
                                                @error('adresse')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="photo" class="form-label">Photo de profil</label>
                                                <input type="file" class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/*">
                                                @error('photo')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted">Laissez vide pour conserver la photo actuelle.</small>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                @if($etudiant->photo)
                                                    <div class="mt-2">
                                                        <label class="form-label">Photo actuelle</label>
                                                        <div>
                                                            <img src="{{ asset('storage/'.$etudiant->photo) }}" alt="Photo de profil" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                                <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                                                    <option value="actif" {{ old('statut', $etudiant->statut) == 'actif' ? 'selected' : '' }}>Actif</option>
                                                    <option value="inactif" {{ old('statut', $etudiant->statut) == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                                </select>
                                                @error('statut')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card-moderne">
                                    <div class="p-lg">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                                            <div>
                                                <h6 class="section-title mb-1">
                                                    <i class="fas fa-user-friends me-2"></i>Parents / Tuteurs
                                                </h6>
                                                <p class="text-muted mb-0 small">Gérez les représentants légaux de l'étudiant. Deux entrées maximum (parents ou tuteur).</p>
                                            </div>
                                            <div class="btn-group mt-3 mt-md-0" role="group" id="add-parent-group" {{ $etudiant->parents->count() >= 2 ? 'style="display:none;"' : '' }}>
                                                <button type="button" class="btn btn-sm btn-primary" id="add-new-parent">
                                                    <i class="fas fa-plus me-1"></i>Nouveau parent
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-existing-parent" data-bs-toggle="modal" data-bs-target="#searchParentModal">
                                                    <i class="fas fa-search me-1"></i>Parent existant
                                                </button>
                                            </div>
                                        </div>

                                        <div id="parents-container">
                                            @forelse($etudiant->parents as $index => $parent)
                                                @include('esbtp.etudiants.partials.parent-card', ['parent' => $parent, 'index' => $index])
                                            @empty
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>Aucun parent enregistré pour le moment. Ajoutez un parent en utilisant le bouton ci-dessus.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de recherche de parent existant -->
                        <div class="modal fade" id="searchParentModal" tabindex="-1" aria-labelledby="searchParentModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="searchParentModalLabel">
                                            <i class="fas fa-user-friends me-2"></i>Sélectionner un Parent Existant
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Filtres de recherche -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="parent_search_filter">Filtrer par :</label>
                                                <select class="form-control" id="parent_search_filter">
                                                    <option value="all">Tous les champs</option>
                                                    <option value="nom">Nom</option>
                                                    <option value="prenoms">Prénom(s)</option>
                                                    <option value="telephone">Téléphone</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="parent_search_query">Rechercher :</label>
                                                <input type="text" class="form-control" id="parent_search_query" placeholder="Nom, prénom, téléphone...">
                                            </div>
                                        </div>

                                        <!-- Tableau des parents -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th class="sortable-parent" data-column="nom" style="cursor: pointer;">
                                                            Nom <i class="fas fa-sort text-muted"></i>
                                                        </th>
                                                        <th class="sortable-parent" data-column="prenoms" style="cursor: pointer;">
                                                            Prénom(s) <i class="fas fa-sort text-muted"></i>
                                                        </th>
                                                        <th class="sortable-parent" data-column="telephone" style="cursor: pointer;">
                                                            Téléphone <i class="fas fa-sort text-muted"></i>
                                                        </th>
                                                        <th>Enfants</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="parents-table-body">
                                                    <tr><td colspan="5" class="text-center">Chargement...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Mettre à jour l'étudiant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let formSubmitted = false;
        const form = $('#editEtudiantForm');
        form.on('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
            $(this).find('button[type="submit"]').prop('disabled', true);
        });

        $('input[type="file"]').on('change', function() {
            const maxSize = 2 * 1024 * 1024;
            if (this.files[0] && this.files[0].size > maxSize) {
                alert('La taille de la photo ne doit pas dépasser 2MB');
                this.value = '';
            }
        });

        if (typeof $.fn.select2 !== 'undefined') {
            $('#sexe, #statut').select2({
                theme: 'bootstrap4',
                minimumResultsForSearch: Infinity
            });

            const nationaliteSelect = $('#nationalite');
            if (nationaliteSelect.length) {
                const wasDisabled = nationaliteSelect.prop('disabled');
                nationaliteSelect.prop('disabled', false);
                nationaliteSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Sélectionner une nationalité',
                    allowClear: true
                });
                if (wasDisabled) {
                    nationaliteSelect.prop('disabled', true);
                }
            }
        }

        const maxParents = 2;
        const parentsContainer = $('#parents-container');
        const addParentGroup = $('#add-parent-group');

        function ensureEmptyState() {
            if (parentsContainer.find('.parent-card').length === 0) {
                if (parentsContainer.find('.alert').length === 0) {
                    parentsContainer.html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun parent enregistré pour le moment. Ajoutez un parent en utilisant les boutons ci-dessus.
                        </div>
                    `);
                }
            } else {
                parentsContainer.find('.alert').remove();
            }
        }

        function recalculateParentCount() {
            const count = parentsContainer.find('.parent-card').length;
            if (count >= maxParents) {
                addParentGroup.hide();
            } else {
                addParentGroup.show();
            }
        }

        ensureEmptyState();
        recalculateParentCount();

        $('#add-new-parent').on('click', function() {
            if (parentsContainer.find('.parent-card').length >= maxParents) {
                alert('Vous ne pouvez ajouter que 2 parents maximum.');
                return;
            }

            parentsContainer.find('#new-parent-card').remove();
            parentsContainer.append(createNewParentCard());
            ensureEmptyState();
            recalculateParentCount();
        });

        $(document).on('click', '.remove-parent', function() {
            const parentId = $(this).data('parent-id');
            const card = $(this).closest('.parent-card');

            if (parentId) {
                $('<input>', {
                    type: 'hidden',
                    name: 'delete_parents[]',
                    value: parentId
                }).appendTo(form);
            }

            card.remove();
            ensureEmptyState();
            recalculateParentCount();
        });

        // Ajouter un parent existant
        $(document).on('click', '.add-existing-parent-btn', function() {
            if (parentsContainer.find('.parent-card').length >= maxParents) {
                alert('Vous ne pouvez ajouter que 2 parents maximum.');
                return;
            }

            const parentData = {
                id: $(this).data('parent-id'),
                nom: $(this).data('parent-nom'),
                prenoms: $(this).data('parent-prenoms'),
                telephone: $(this).data('parent-telephone'),
                email: $(this).data('parent-email'),
                profession: $(this).data('parent-profession'),
                adresse: $(this).data('parent-adresse')
            };

            // Vérifier si le parent n'est pas déjà ajouté
            if (parentsContainer.find(`input[value="${parentData.id}"]`).length > 0) {
                alert('Ce parent est déjà ajouté à cet étudiant.');
                return;
            }

            const newIndex = parentsContainer.find('.parent-card').length;

            const existingParentCard = `
<div class="parent-card mb-4" data-parent-index="${newIndex}" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1 text-primary" style="font-weight: 600;">
                <i class="fas fa-user-friends me-2"></i>Parent / Tuteur #${newIndex + 1}
            </h6>
            <small class="text-muted">${parentData.prenoms} ${parentData.nom}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">Existant</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent" data-parent-id="${parentData.id}">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="parent-card-body">
        <input type="hidden" name="existing_parents[]" value="${parentData.id}">
        <input type="hidden" name="existing_parents_relation[${parentData.id}]" value="Père" class="parent-relation-input">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom</label>
                <input type="text" class="form-control" value="${parentData.nom}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom(s)</label>
                <input type="text" class="form-control" value="${parentData.prenoms}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relation <span class="text-danger">*</span></label>
                <select class="form-select parent-relation-select" data-parent-id="${parentData.id}" required>
                    <option value="Père">Père</option>
                    <option value="Mère">Mère</option>
                    <option value="Tuteur">Tuteur</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone</label>
                <input type="text" class="form-control" value="${parentData.telephone}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="${parentData.email}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Profession</label>
                <input type="text" class="form-control" value="${parentData.profession}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Adresse</label>
                <textarea class="form-control" rows="1" readonly>${parentData.adresse}</textarea>
            </div>
        </div>
    </div>
</div>`;

            parentsContainer.append(existingParentCard);
            ensureEmptyState();
            recalculateParentCount();

            // Fermer la modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('searchParentModal'));
            if (modal) {
                modal.hide();
            }

            // Réinitialiser les champs de recherche
            document.getElementById('parent_search_query').value = '';
            document.getElementById('parent_search_filter').value = 'all';

            console.log('Parent existant ajouté:', parentData);
        });

        // Mettre à jour la relation dans le input hidden quand le select change
        $(document).on('change', '.parent-relation-select', function() {
            const parentId = $(this).data('parent-id');
            const relation = $(this).val();
            $(this).closest('.parent-card').find('.parent-relation-input').val(relation);
        });
    });

    function createNewParentCard() {
        return `
<div class="parent-card mb-4" id="new-parent-card" data-parent-index="new" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1 text-primary" style="font-weight: 600;">
                <i class="fas fa-user-friends me-2"></i>Nouveau parent / tuteur
            </h6>
            <small class="text-muted">Renseignez les informations du représentant.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="parent-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[nom]" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[prenoms]" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relation <span class="text-danger">*</span></label>
                <select class="form-select" name="new_parent[relation]" required>
                    <option value="">Sélectionner une relation</option>
                    <option value="Père">Père</option>
                    <option value="Mère">Mère</option>
                    <option value="Tuteur">Tuteur</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="new_parent[telephone]" placeholder="+225 XX XX XXX XXX" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="new_parent[email]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Profession</label>
                <input type="text" class="form-control" name="new_parent[profession]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Adresse</label>
                <textarea class="form-control" name="new_parent[adresse]" rows="1"></textarea>
            </div>
        </div>
    </div>
</div>`;
    }

    // === GESTION DES PARENTS EXISTANTS (Style class-selector) ===
    let allParents = [];
    let currentParentSort = { column: null, direction: 'asc' };

    // Fonction pour charger tous les parents
    function loadParents() {
        const tableBody = document.getElementById('parents-table-body');
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Chargement...</td></tr>';

        fetch('{{ route("esbtp.parents.search") }}?q=&etudiant_id={{ $etudiant->id }}')
            .then(response => response.json())
            .then(parents => {
                allParents = parents;
                displayParentsTable(allParents);
            })
            .catch(error => {
                console.error('Error loading parents:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur lors du chargement des parents.</td></tr>';
            });
    }

    // Fonction pour afficher les parents dans le tableau
    function displayParentsTable(parents) {
        const tableBody = document.getElementById('parents-table-body');
        tableBody.innerHTML = '';

        if (parents.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun parent trouvé</td></tr>';
            return;
        }

        parents.forEach(parent => {
            const enfants = parent.etudiants && parent.etudiants.length > 0
                ? parent.etudiants.map(e => `${e.prenoms} ${e.nom}`).join(', ')
                : '<em class="text-muted">Aucun</em>';

            tableBody.innerHTML += `<tr>
                <td>${parent.nom || ''}</td>
                <td>${parent.prenoms || ''}</td>
                <td>${parent.telephone || 'Non renseigné'}</td>
                <td>${enfants}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary add-existing-parent-btn"
                            data-parent-id="${parent.id}"
                            data-parent-nom="${parent.nom || ''}"
                            data-parent-prenoms="${parent.prenoms || ''}"
                            data-parent-telephone="${parent.telephone || ''}"
                            data-parent-email="${parent.email || ''}"
                            data-parent-profession="${parent.profession || ''}"
                            data-parent-adresse="${parent.adresse || ''}">
                        Sélectionner
                    </button>
                </td>
            </tr>`;
        });
    }

    // Fonction pour filtrer les parents
    function filterParents() {
        const filterType = document.getElementById('parent_search_filter').value;
        const query = document.getElementById('parent_search_query').value.toLowerCase();

        let filteredParents = allParents;

        if (query) {
            filteredParents = filteredParents.filter(parent => {
                switch (filterType) {
                    case 'nom':
                        return (parent.nom || '').toLowerCase().includes(query);
                    case 'prenoms':
                        return (parent.prenoms || '').toLowerCase().includes(query);
                    case 'telephone':
                        return (parent.telephone || '').toLowerCase().includes(query);
                    default: // 'all'
                        return (parent.nom || '').toLowerCase().includes(query) ||
                               (parent.prenoms || '').toLowerCase().includes(query) ||
                               (parent.telephone || '').toLowerCase().includes(query);
                }
            });
        }

        if (currentParentSort.column) {
            sortParents(filteredParents, currentParentSort.column, currentParentSort.direction);
        } else {
            displayParentsTable(filteredParents);
        }
    }

    // Fonction pour trier les parents
    function sortParents(parents, column, direction) {
        const sortedParents = [...parents].sort((a, b) => {
            let aValue = a[column] || '';
            let bValue = b[column] || '';

            if (direction === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });

        displayParentsTable(sortedParents);
        updateParentSortIcons(column, direction);
    }

    // Fonction pour mettre à jour les icônes de tri
    function updateParentSortIcons(activeColumn, direction) {
        document.querySelectorAll('.sortable-parent i').forEach(icon => {
            icon.className = 'fas fa-sort text-muted';
        });

        const activeHeader = document.querySelector(`[data-column="${activeColumn}"] i`);
        if (activeHeader) {
            if (direction === 'asc') {
                activeHeader.className = 'fas fa-sort-up text-primary';
            } else {
                activeHeader.className = 'fas fa-sort-down text-primary';
            }
        }
    }

    // Event listeners pour les filtres et la recherche
    document.getElementById('parent_search_query').addEventListener('input', filterParents);
    document.getElementById('parent_search_filter').addEventListener('change', filterParents);

    // Event listeners pour le tri
    document.querySelectorAll('.sortable-parent').forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            let direction = 'asc';

            if (currentParentSort.column === column) {
                direction = currentParentSort.direction === 'asc' ? 'desc' : 'asc';
            }

            currentParentSort = { column, direction };

            const filterType = document.getElementById('parent_search_filter').value;
            const query = document.getElementById('parent_search_query').value.toLowerCase();

            let filteredParents = allParents;

            if (query) {
                filteredParents = filteredParents.filter(parent => {
                    switch (filterType) {
                        case 'nom':
                            return (parent.nom || '').toLowerCase().includes(query);
                        case 'prenoms':
                            return (parent.prenoms || '').toLowerCase().includes(query);
                        case 'telephone':
                            return (parent.telephone || '').toLowerCase().includes(query);
                        default:
                            return (parent.nom || '').toLowerCase().includes(query) ||
                                   (parent.prenoms || '').toLowerCase().includes(query) ||
                                   (parent.telephone || '').toLowerCase().includes(query);
                    }
                });
            }

            sortParents(filteredParents, column, direction);
        });
    });

    // Charger les parents quand le modal s'ouvre
    document.getElementById('searchParentModal').addEventListener('show.bs.modal', function() {
        loadParents();
    });
</script>
@endpush
