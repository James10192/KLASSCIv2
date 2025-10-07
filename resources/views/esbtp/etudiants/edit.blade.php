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
                                            <button type="button" class="btn btn-sm btn-primary mt-3 mt-md-0" id="add-parent" {{ $etudiant->parents->count() >= 2 ? 'style="display:none;"' : '' }}>
                                                <i class="fas fa-plus me-1"></i>Ajouter un parent
                                            </button>
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

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Compte utilisateur</h6>
                                    </div>
                                    <div class="card-body">
                                        @if(session('new_password'))
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <strong>Mot de passe réinitialisé avec succès!</strong>
                                                <p>Le nouveau mot de passe est : <span class="font-weight-bold">{{ session('new_password') }}</span></p>
                                                <p>Veuillez communiquer ce mot de passe à l'étudiant.</p>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Compte utilisateur</label>
                                                @if($etudiant->user)
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-success me-2">Actif</span>
                                                        <span><strong>Nom d'utilisateur:</strong> {{ $etudiant->user->username ?: $etudiant->user->email }}</span>
                                                        <a href="{{ route('esbtp.etudiants.reset-password', $etudiant) }}" class="btn btn-sm btn-outline-secondary ms-2" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe de cet utilisateur ? Un nouveau mot de passe simple sera généré.')">
                                                            <i class="fas fa-key me-1"></i>Réinitialiser le mot de passe
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-warning me-2">Non créé</span>
                                                        <div class="form-check form-switch ms-2">
                                                            <input class="form-check-input" type="checkbox" id="create_account" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="create_account">
                                                                Créer un compte utilisateur pour cet étudiant
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Un compte sera créé avec un nom d'utilisateur basé sur le nom et prénom de l'étudiant. Un mot de passe temporaire sera généré.
                                                    </small>
                                                @endif
                                            </div>
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
        const addParentBtn = $('#add-parent');

        function ensureEmptyState() {
            if (parentsContainer.find('.parent-card').length === 0) {
                if (parentsContainer.find('.alert').length === 0) {
                    parentsContainer.html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun parent enregistré pour le moment. Ajoutez un parent en utilisant le bouton ci-dessus.
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
                addParentBtn.hide();
            } else {
                addParentBtn.show();
            }
        }

        ensureEmptyState();
        recalculateParentCount();

        addParentBtn.on('click', function() {
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
    });

    function createNewParentCard() {
        return `
<div class="parent-card card-moderne mb-4" id="new-parent-card" data-parent-index="new">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-1 text-primary">
                <i class="fas fa-user-friends me-2"></i>Nouveau parent / tuteur
            </h6>
            <small class="text-muted">Renseignez les informations du représentant.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="new_is_tuteur" name="new_parent[is_tuteur]">
                <label class="form-check-label small" for="new_is_tuteur">Tuteur</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="parent-card-body mt-3">
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
</script>
@endpush
