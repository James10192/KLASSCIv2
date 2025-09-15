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
                                                <input type="text" class="form-control" id="matricule" name="matricule" value="{{ $etudiant->matricule }}" readonly>
                                                <small class="form-text text-muted">Le matricule ne peut pas être modifié.</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="nom" class="form-label">Nom</label>
                                                <input type="text" class="form-control" id="nom" name="nom" value="{{ old('nom', $etudiant->nom) }}" readonly>
                                                <small class="form-text text-muted">Le nom ne peut pas être modifié.</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="prenoms" class="form-label">Prénom(s)</label>
                                                <input type="text" class="form-control" id="prenoms" name="prenoms" value="{{ old('prenoms', $etudiant->prenoms) }}" readonly>
                                                <small class="form-text text-muted">Les prénoms ne peuvent pas être modifiés.</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="sexe" class="form-label">Genre</label>
                                                <select class="form-select" id="sexe" name="sexe" disabled>
                                                    <option value="M" {{ old('sexe', $etudiant->sexe) == 'M' ? 'selected' : '' }}>Masculin</option>
                                                    <option value="F" {{ old('sexe', $etudiant->sexe) == 'F' ? 'selected' : '' }}>Féminin</option>
                                                </select>
                                                <small class="form-text text-muted">Le genre ne peut pas être modifié.</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="{{ old('date_naissance', $etudiant->date_naissance ? $etudiant->date_naissance->format('Y-m-d') : '') }}" readonly>
                                                <small class="form-text text-muted">La date de naissance ne peut pas être modifiée.</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance', $etudiant->lieu_naissance) }}" readonly>
                                                <small class="form-text text-muted">Le lieu de naissance ne peut pas être modifié.</small>
                                            </div>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-4 mb-3">
                                                <label for="nationalite" class="form-label">Nationalité</label>
                                                <input type="text" class="form-control" id="nationalite" name="nationalite" value="{{ old('nationalite', $etudiant->nationalite) }}" readonly>
                                                <small class="form-text text-muted">La nationalité ne peut pas être modifiée.</small>
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
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Information(s) sur le(s) parent(s) / tuteur(s)</h6>
                                        @if($etudiant->parents->count() < 2)
                                            <button type="button" class="btn btn-sm btn-primary" id="add-parent">
                                                <i class="fas fa-plus me-1"></i>Ajouter un parent
                                            </button>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div id="parents-container">
                                            @forelse($etudiant->parents as $index => $parent)
                                                <div class="parent-item mb-4 p-3 border rounded">
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <h6>Parent / Tuteur #{{ $index + 1 }}</h6>
                                                        @if($index > 0 || $etudiant->parents->count() > 1)
                                                            <button type="button" class="btn btn-sm btn-outline-danger remove-parent" data-parent-id="{{ $parent->id }}">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <input type="hidden" name="parents[{{ $index }}][id]" value="{{ $parent->id }}">

                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_nom_{{ $index }}" class="form-label">Nom <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="parent_nom_{{ $index }}" name="parents[{{ $index }}][nom]" value="{{ old('parents.'.$index.'.nom', $parent->nom) }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_prenoms_{{ $index }}" class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="parent_prenoms_{{ $index }}" name="parents[{{ $index }}][prenoms]" value="{{ old('parents.'.$index.'.prenoms', $parent->prenoms) }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_relation_{{ $index }}" class="form-label">Relation <span class="text-danger">*</span></label>
                                                            <select class="form-control" id="parent_relation_{{ $index }}" name="parents[{{ $index }}][relation]" required>
                                                                <option value="Père" {{ old('parents.'.$index.'.relation', $parent->pivot->relation) == 'Père' ? 'selected' : '' }}>Père</option>
                                                                <option value="Mère" {{ old('parents.'.$index.'.relation', $parent->pivot->relation) == 'Mère' ? 'selected' : '' }}>Mère</option>
                                                                <option value="Tuteur" {{ old('parents.'.$index.'.relation', $parent->pivot->relation) == 'Tuteur' ? 'selected' : '' }}>Tuteur</option>
                                                                <option value="Autre" {{ old('parents.'.$index.'.relation', $parent->pivot->relation) == 'Autre' ? 'selected' : '' }}>Autre</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_telephone_{{ $index }}" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="parent_telephone_{{ $index }}" name="parents[{{ $index }}][telephone]" value="{{ old('parents.'.$index.'.telephone', $parent->telephone) }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_email_{{ $index }}" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="parent_email_{{ $index }}" name="parents[{{ $index }}][email]" value="{{ old('parents.'.$index.'.email', $parent->email) }}">
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="parent_profession_{{ $index }}" class="form-label">Profession</label>
                                                            <input type="text" class="form-control" id="parent_profession_{{ $index }}" name="parents[{{ $index }}][profession]" value="{{ old('parents.'.$index.'.profession', $parent->profession) }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12 mb-3">
                                                            <label for="parent_adresse_{{ $index }}" class="form-label">Adresse</label>
                                                            <textarea class="form-control" id="parent_adresse_{{ $index }}" name="parents[{{ $index }}][adresse]" rows="2">{{ old('parents.'.$index.'.adresse', $parent->adresse) }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="alert alert-info">
                                                    Aucun parent ou tuteur enregistré pour cet étudiant.
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

@section('scripts')
<script>
    $(document).ready(function() {
        // Prévention de double soumission
        let formSubmitted = false;
        $('#editEtudiantForm').on('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
            $(this).find('button[type="submit"]').prop('disabled', true);
        });

        // Validation de la taille de la photo avant soumission
        $('input[type="file"]').on('change', function() {
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (this.files[0] && this.files[0].size > maxSize) {
                alert('La taille de la photo ne doit pas dépasser 2MB');
                this.value = '';
            }
        });

        // Initialisation de Select2 pour les sélecteurs si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('#sexe, #statut').select2({
                theme: 'bootstrap4',
                minimumResultsForSearch: Infinity
            });
        }

        // Ajout de parents
        let parentCount = {{ $etudiant->parents->count() }};
        $('#add-parent').on('click', function() {
            if (parentCount >= 2) {
                alert('Vous ne pouvez ajouter que 2 parents maximum.');
                return;
            }

            const parentHtml = `
                <div class="parent-item mb-4 p-3 border rounded">
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Nouveau parent / tuteur</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-parent">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="parent_nom_new" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="parent_nom_new" name="new_parent[nom]" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parent_prenoms_new" class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="parent_prenoms_new" name="new_parent[prenoms]" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parent_relation_new" class="form-label">Relation <span class="text-danger">*</span></label>
                            <select class="form-control" id="parent_relation_new" name="new_parent[relation]" required>
                                <option value="">Sélectionner une relation</option>
                                <option value="Père">Père</option>
                                <option value="Mère">Mère</option>
                                <option value="Tuteur">Tuteur</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="parent_telephone_new" class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="parent_telephone_new" name="new_parent[telephone]" placeholder="+225 XX XX XXX XXX" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parent_email_new" class="form-label">Email</label>
                            <input type="email" class="form-control" id="parent_email_new" name="new_parent[email]">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parent_profession_new" class="form-label">Profession</label>
                            <input type="text" class="form-control" id="parent_profession_new" name="new_parent[profession]">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="parent_adresse_new" class="form-label">Adresse</label>
                            <textarea class="form-control" id="parent_adresse_new" name="new_parent[adresse]" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            `;

            $('#parents-container').append(parentHtml);
            parentCount++;

            // Masquer le bouton d'ajout si on a atteint 2 parents
            if (parentCount >= 2) {
                $('#add-parent').hide();
            }
        });

        // Suppression de parents
        $(document).on('click', '.remove-parent', function() {
            const parentItem = $(this).closest('.parent-item');
            const parentId = $(this).data('parent-id');

            if (parentId) {
                // Si c'est un parent existant, ajouter un champ hidden pour la suppression
                $('form').append(`<input type="hidden" name="delete_parents[]" value="${parentId}">`);
            }

            parentItem.remove();
            parentCount--;

            // Afficher le bouton d'ajout si on a moins de 2 parents
            if (parentCount < 2) {
                $('#add-parent').show();
            }
        });
    });
</script>
            </div>
        </div>
    </div>
</div>
@endsection
