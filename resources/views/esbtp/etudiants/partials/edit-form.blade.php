@php
    $isEmbedded = $isEmbedded ?? false;
    $formWrapperId = 'student-edit-form-wrapper-' . ($etudiant->id ?? uniqid());
    $successNoticeId = 'student-edit-success-' . ($etudiant->id ?? uniqid());
    $embeddedSuccessMessage = $isEmbedded ? session('embedded_success_student') : null;
@endphp

@if($isEmbedded && $embeddedSuccessMessage)
    <div class="alert alert-success d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3" id="{{ $successNoticeId }}">
        <div>
            <strong class="d-block mb-1">
                <i class="fas fa-check-circle me-1"></i>
                Fiche étudiant mise à jour
            </strong>
            <span>{{ $embeddedSuccessMessage }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-sm" data-embedded-toggle="form" data-target="{{ $formWrapperId }}" data-notice="{{ $successNoticeId }}">
                <i class="fas fa-edit me-1"></i>Modifier à nouveau
            </button>
            <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-external-link-alt me-1"></i>Voir la fiche
            </a>
        </div>
    </div>
@endif

<div id="{{ $formWrapperId }}" class="{{ $isEmbedded && $embeddedSuccessMessage ? 'd-none' : '' }}">
<form action="{{ route('esbtp.etudiants.update', $etudiant) }}" method="POST" enctype="multipart/form-data" id="editEtudiantForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="embedded_mode" value="{{ $isEmbedded ? 1 : 0 }}">
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
                                            <div class="col-md-4 mb-3" id="matriculeContainer">
                                                <label for="matricule" class="form-label">
                                                    Matricule
                                                    <span id="matriculeMode" class="badge bg-info ms-1"></span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="matriculeInput" name="matricule" value="{{ old('matricule', $etudiant->matricule) }}" {{ auth()->user()->hasRole('superAdmin') ? '' : 'readonly' }}>
                                                    @if(auth()->user()->hasRole('superAdmin'))
                                                        <button type="button" class="btn btn-outline-primary" id="generateMatriculeBtn" style="display: none;">
                                                            <i class="fas fa-magic"></i> Générer
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" id="checkMatriculeBtn" style="display: none;">
                                                            <i class="fas fa-search"></i> Vérifier
                                                        </button>
                                                    @endif
                                                </div>
                                                <small class="form-text text-muted" id="matriculeHelp">
                                                    @if(auth()->user()->hasRole('superAdmin'))
                                                        Matricule unique de l'étudiant
                                                    @else
                                                        Le matricule ne peut pas être modifié.
                                                    @endif
                                                </small>
                                                <div id="matriculeStatus" class="mt-1"></div>
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