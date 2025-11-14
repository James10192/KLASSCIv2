@extends('layouts.app')

@section('title', 'Détails de l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Style pour la carte d'ajout de réinscription */
    .add-reinscription-card {
        border: 2px dashed var(--primary);
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.02) 0%, rgba(94, 145, 222, 0.02) 100%);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .add-reinscription-card:hover {
        border-color: var(--secondary);
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.05) 0%, rgba(94, 145, 222, 0.05) 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(4, 83, 203, 0.15);
    }

    .add-reinscription-card i {
        transition: transform 0.3s ease;
    }

    .add-reinscription-card:hover i {
        transform: scale(1.1);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                <p class="header-subtitle">Détails de l'étudiant - Matricule: {{ $etudiant->matricule }}</p>
            </div>
            <div class="header-actions d-flex flex-wrap gap-2">
                @if(isset($etudiant) && $etudiant->id)
                <a href="{{ route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-edit"></i>
                    <span class="d-none d-sm-inline ms-1">Modifier</span>
                </a>
                @endif
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span class="d-none d-sm-inline ms-1">Retour</span>
                </a>
                <div class="dropdown">
                    <button class="btn-acasi info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-alt"></i>
                        <span class="d-none d-md-inline ms-1">Documents</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat.preview', ['etudiant' => $etudiant->id]) }}">
                            <i class="fas fa-eye me-1"></i>Prévisualiser Certificat
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat', ['etudiant' => $etudiant->id]) }}" target="_blank">
                            <i class="fas fa-download me-1"></i>Télécharger Certificat
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation.preview', ['etudiant' => $etudiant->id]) }}">
                            <i class="fas fa-eye me-1"></i>Prévisualiser Attestation
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation', ['etudiant' => $etudiant->id]) }}" target="_blank">
                            <i class="fas fa-download me-1"></i>Télécharger Attestation
                        </a></li>
                        @if($etudiant->paiements->where('status', 'validé')->count() > 0)
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.index') }}?etudiant={{ $etudiant->id }}">
                            <i class="fas fa-receipt me-1"></i>Historique des reçus
                        </a></li>
                        @endif
                    </ul>
                </div>
                <a href="{{ route('esbtp.paiements.create') }}?etudiant={{ $etudiant->id }}" class="btn-acasi success">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-lg-inline ms-1">Paiement</span>
                </a>

                <!-- Bouton suppression étudiant - Accès réservé aux superAdmin -->
                @can('delete_students')
                <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#deleteStudentModal" title="Supprimer l'étudiant et toutes ses données">
                    <i class="fas fa-trash"></i>
                    <span class="d-none d-lg-inline ms-1">Supprimer</span>
                </button>
                @endcan
            </div>
        </div>

        <div class="p-lg">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    {{ session('success') }}
                                    @if(session('new_password'))
                                        <hr class="my-2">
                                        <p class="mb-0">
                                            <strong>Nouveau mot de passe:</strong>
                                            <code class="text-dark bg-white px-2 py-1 rounded">{{ session('new_password') }}</code>
                                        </p>
                                        <p class="mb-0 mt-2 small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            L'étudiant devra changer ce mot de passe lors de sa première connexion.
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

            <div class="row">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="card-moderne">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-user"></i>Informations personnelles
                            </div>
                                    <div class="text-center mb-4">
                                        <div class="photo-container position-relative mx-auto" style="width: 150px; height: 150px; cursor: pointer;" onclick="document.getElementById('photo-upload').click()">
                                            @if($etudiant->photo_url)
                                                <img src="{{ $etudiant->photo_url }}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center rounded-circle" style="width: 150px; height: 150px;">
                                                    <i class="fas fa-user fa-5x text-secondary"></i>
                                                </div>
                                            @endif
                                            <!-- Overlay caméra -->
                                            <div class="photo-overlay position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border: 3px solid white;">
                                                <i class="fas fa-camera text-white"></i>
                                            </div>
                                        </div>
                                        <!-- Input caché pour upload -->
                                        <input type="file" id="photo-upload" accept="image/*" style="display: none;" onchange="uploadPhoto(this)">
                                        <h5 class="mt-3">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h5>
                                        <p class="text-muted">
                                            Matricule: <strong>{{ $etudiant->matricule }}</strong>
                                        </p>
                                        <div class="mb-2">
                                            @if($etudiant->statut == 'actif')
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-danger">Inactif</span>
                                            @endif
                                        </div>
                                    </div>

                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">Genre</th>
                                            <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date de naissance</th>
                                            <td>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Lieu de naissance</th>
                                            <td>{{ $etudiant->lieu_naissance ?: 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nationalité</th>
                                            <td>{{ $etudiant->nationalite ?: 'Non renseignée' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <td>{{ $etudiant->telephone }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $etudiant->email_personnel ?: 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Ville de résidence</th>
                                            <td>{{ $etudiant->ville ?: 'Non renseignée' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Commune de résidence</th>
                                            <td>{{ $etudiant->commune ?: 'Non renseignée' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date d'admission</th>
                                            <td>{{ $etudiant->created_at ? $etudiant->created_at->format('d/m/Y') : 'Non renseignée' }}</td>
                                        </tr>
                                    </table>
                        </div>
                    </div>

                    <div class="card-moderne mt-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-user-cog"></i>Compte utilisateur
                            </div>

                            @if(session('account_created'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Compte créé avec succès!</h5>
                                    <hr>
                                    <p class="mb-2"><strong>Nom d'utilisateur:</strong> <code class="text-dark">{{ session('new_username') }}</code></p>
                                    <p class="mb-0"><strong>Mot de passe:</strong> <code class="text-dark">{{ session('new_password') }}</code></p>
                                    <hr>
                                    <p class="mb-0 small"><i class="fas fa-info-circle me-1"></i>Veuillez communiquer ces identifiants à l'étudiant.</p>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if($etudiant->user)
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-success me-2">Actif</span>
                                    <span>{{ $etudiant->user->email }}</span>
                                </div>
                                <div>
                                    <p><strong>Nom d'utilisateur:</strong> {{ $etudiant->user->username ?: $etudiant->user->email }}</p>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('esbtp.etudiants.reset-password', ['etudiant' => $etudiant->id]) }}" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe de cet utilisateur ?')">
                                        <i class="fas fa-key me-1"></i>Réinitialiser le mot de passe
                                    </a>
                                </div>
                            @else
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Aucun compte utilisateur n'est associé à cet étudiant.
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                        <i class="fas fa-user-plus me-1"></i>Créer un compte
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal de création de compte -->
                    @if(!$etudiant->user)
                    <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="createAccountModalLabel">
                                        <i class="fas fa-user-plus me-2"></i>Créer un compte utilisateur
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Un compte sera créé avec les informations suivantes :
                                    </div>
                                    <ul class="list-unstyled mb-3">
                                        <li class="mb-2"><i class="fas fa-user text-primary me-2"></i><strong>Nom d'utilisateur:</strong> Basé sur le prénom et nom</li>
                                        <li class="mb-2"><i class="fas fa-envelope text-primary me-2"></i><strong>Email:</strong> Format @esbtp.edu</li>
                                        <li class="mb-2"><i class="fas fa-key text-primary me-2"></i><strong>Mot de passe:</strong> Généré automatiquement (6 caractères)</li>
                                        <li class="mb-2"><i class="fas fa-id-badge text-primary me-2"></i><strong>Rôle:</strong> Étudiant</li>
                                    </ul>
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <small>Le mot de passe sera affiché une seule fois. Notez-le pour le communiquer à l'étudiant.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </button>
                                    <form action="{{ route('esbtp.etudiants.create-account', $etudiant) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check me-1"></i>Créer le compte
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Statistiques rapides -->
                    @if(isset($statistiques))
                    <div class="card-moderne mt-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-chart-pie"></i>Résumé financier
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-12 mb-3">
                                    <div class="card border-success">
                                        <div class="card-body py-2">
                                            <small class="text-muted">Paiements validés</small>
                                            <div class="h5 mb-0 text-success">{{ number_format($statistiques['paiements_valides'], 0, ',', ' ') }} FCFA</div>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($statistiques['paiements_en_attente'] > 0)
                                <div class="col-12 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body py-2">
                                            <small class="text-muted">En attente validation</small>
                                            <div class="h6 mb-0 text-warning">{{ number_format($statistiques['paiements_en_attente'], 0, ',', ' ') }} FCFA</div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                
                                <div class="col-12 mb-3">
                                    <div class="card border-info">
                                        <div class="card-body py-2">
                                            <small class="text-muted">Total paiements</small>
                                            <div class="h6 mb-0 text-info">{{ $statistiques['nombre_paiements'] }} transaction(s)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($statistiques['inscription_active'])
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted d-block">Inscription active:</small>
                                <strong>{{ $statistiques['inscription_active']->filiere->name ?? 'N/A' }}</strong>
                                <br><small>{{ $statistiques['inscription_active']->niveauEtude->name ?? 'N/A' }}</small>
                            </div>
                            @endif

                            {{-- Section Reliquats --}}
                            @if($statistiques['total_reliquats_entrants'] > 0 || $statistiques['total_reliquats_sortants'] > 0)
                            <div class="mt-4">
                                <div class="section-title mb-md">
                                    <i class="fas fa-exchange-alt"></i>Reliquats
                                </div>

                                @if($statistiques['total_reliquats_entrants'] > 0)
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-arrow-right me-2"></i>Reliquats à payer</h6>
                                    <p class="mb-2">Montant dû des inscriptions précédentes: <strong>{{ number_format($statistiques['total_reliquats_entrants'], 0, ',', ' ') }} FCFA</strong></p>
                                    <small class="text-muted">{{ $statistiques['nombre_reliquats_actifs'] }} reliquat(s) actif(s)</small>
                                </div>
                                @endif

                                @if($statistiques['total_reliquats_sortants'] > 0)
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-arrow-left me-2"></i>Reliquats transférés</h6>
                                    <p class="mb-0">Montant transféré vers les inscriptions futures: <strong>{{ number_format($statistiques['total_reliquats_sortants'], 0, ',', ' ') }} FCFA</strong></p>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-12 col-md-7 col-lg-8">
                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-users"></i>Parents / Tuteurs
                            </div>
                                    @if($etudiant->parents->count() > 0)
                                        <div class="accordion" id="accordionParents">
                                            @foreach($etudiant->parents as $index => $parent)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading{{ $index }}">
                                                        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                                            {{ $parent->nom }} {{ $parent->prenoms }} - {{ $parent->pivot->relation }}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionParents">
                                                        <div class="accordion-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <table class="table table-bordered">
                                                                        <tr>
                                                                            <th style="width: 40%">Nom complet</th>
                                                                            <td>{{ $parent->nom }} {{ $parent->prenoms }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Relation</th>
                                                                            <td>{{ $parent->pivot->relation }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Téléphone</th>
                                                                            <td>{{ $parent->telephone }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Email</th>
                                                                            <td>{{ $parent->email ?: 'Non renseigné' }}</td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <table class="table table-bordered">
                                                                        <tr>
                                                                            <th style="width: 40%">Profession</th>
                                                                            <td>{{ $parent->profession ?: 'Non renseignée' }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Adresse</th>
                                                                            <td>{{ $parent->adresse ?: 'Non renseignée' }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Autres étudiants</th>
                                                                            <td>
                                                                                @php
                                                                                    $autresEtudiants = $parent->etudiants->where('id', '!=', $etudiant->id);
                                                                                @endphp
                                                                                @if($autresEtudiants->count() > 0)
                                                                                    <ul class="list-unstyled mb-0">
                                                                                        @foreach($autresEtudiants as $autreEtudiant)
                                                                                            <li>
                                                                                                <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $autreEtudiant->id]) }}">
                                                                                                    {{ $autreEtudiant->nom }} {{ $autreEtudiant->prenoms }}
                                                                                                </a>
                                                                                            </li>
                                                                                        @endforeach
                                                                                    </ul>
                                                                                @else
                                                                                    Aucun autre étudiant
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Aucun parent ou tuteur n'est associé à cet étudiant.
                                        </div>
                                    @endif
                        </div>
                    </div>

                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-graduation-cap"></i>Inscriptions
                            </div>
                            @if($etudiant->inscriptions->count() > 0)
                                <div class="row">
                                    @foreach($etudiant->inscriptions->sortByDesc('created_at') as $inscription)
                                        @php
                                            $rawAcademicYear = $inscription->anneeUniversitaire?->libelle
                                                ?? $inscription->anneeUniversitaire?->name
                                                ?? null;
                                            if ($rawAcademicYear) {
                                                $normalized = \Illuminate\Support\Str::of($rawAcademicYear)->lower();
                                                $anneeUniversitaireLabel = $normalized->contains('année')
                                                    ? $rawAcademicYear
                                                    : 'Année Universitaire ' . $rawAcademicYear;
                                            } else {
                                                $anneeUniversitaireLabel = 'Année Universitaire non renseignée';
                                            }
                                        @endphp
                                        <div class="col-md-6 mb-4">
                                            <div class="card-moderne">
                                                <div class="p-md">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h6 class="mb-0 font-semibold">{{ $anneeUniversitaireLabel }}</h6>
                                                        <div class="d-flex gap-2">
                                                            @switch($inscription->workflow_step ?? 'prospect')
                                                                @case('prospect')
                                                                    <span class="badge bg-secondary">
                                                                        <i class="fas fa-user-plus"></i>Prospect
                                                                    </span>
                                                                    @break
                                                                @case('documents_complets')
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-file-check"></i>Documents OK
                                                                    </span>
                                                                    @break
                                                                @case('en_validation')
                                                                    <span class="badge bg-warning">
                                                                        <i class="fas fa-hourglass-half"></i>En validation
                                                                    </span>
                                                                    @break
                                                                @case('valide')
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check"></i>Validé
                                                                    </span>
                                                                    @break
                                                                @case('etudiant_cree')
                                                                    <span class="badge bg-primary">
                                                                        <i class="fas fa-graduation-cap"></i>Étudiant créé
                                                                    </span>
                                                                    @break
                                                                @default
                                                                    <span class="badge bg-light text-dark">{{ $inscription->workflow_step ?? 'N/A' }}</span>
                                                            @endswitch
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6">
                                                            <strong>Filière:</strong><br>
                                                            <span class="text-secondary">{{ $inscription->filiere ? $inscription->filiere->name : 'N/A' }}</span>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Niveau:</strong><br>
                                                            <span class="text-secondary">{{ $inscription->niveauEtude ? $inscription->niveauEtude->name : 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6">
                                                            <strong>Classe:</strong><br>
                                                            <span class="text-secondary">{{ $inscription->classe ? $inscription->classe->name : 'Non assigné' }}</span>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Date d'inscription:</strong><br>
                                                            <span class="text-secondary">{{ $inscription->created_at->format('d/m/Y') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-sm-6">
                                                            <strong>Année universitaire:</strong><br>
                                                            <span class="text-secondary">
                                                                {{ $anneeUniversitaireLabel }}
                                                            </span>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            &nbsp;
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6">
                                                            <strong>Statut d'affectation:</strong><br>
                                                            @if($inscription->affectation_status)
                                                                @switch($inscription->affectation_status)
                                                                    @case('affecté')
                                                                        <span class="badge bg-success">
                                                                            <i class="fas fa-check-circle me-1"></i>Affecté
                                                                        </span>
                                                                        @break
                                                                    @case('réaffecté')
                                                                        <span class="badge bg-warning">
                                                                            <i class="fas fa-exchange-alt me-1"></i>Réaffecté
                                                                        </span>
                                                                        @break
                                                                    @case('non_affecté')
                                                                        <span class="badge bg-danger">
                                                                            <i class="fas fa-times-circle me-1"></i>Non affecté
                                                                        </span>
                                                                        @break
                                                                    @default
                                                                        <span class="badge bg-secondary">{{ $inscription->affectation_status }}</span>
                                                                @endswitch
                                                            @else
                                                                <span class="text-muted">Non renseigné</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-sm-6">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            @if($inscription->status == 'active')
                                                                <span class="badge bg-success">Active</span>
                                                            @elseif($inscription->status == 'pending' || $inscription->status == 'en_attente')
                                                                <span class="badge bg-warning">En attente</span>
                                                            @elseif($inscription->status == 'annulée')
                                                                <span class="badge bg-danger">Annulée</span>
                                                            @else
                                                                <span class="badge bg-secondary">{{ $inscription->status }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="btn-group">
                                                            <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @if($inscription->status == 'pending' || $inscription->status == 'en_attente')
                                                                @can('inscriptions.validate')
                                                                <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn btn-sm btn-success" title="Aller à l'administration">
                                                                    <i class="fas fa-cog"></i>
                                                                </a>
                                                                @endcan
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- Carte pour ajouter une nouvelle réinscription --}}
                                    @if($anneeCourante)
                                    <div class="col-md-6 mb-4">
                                        <a href="{{ route('esbtp.reinscription.show', $etudiant->id) }}?annee_academique={{ $anneeCourante->name }}"
                                           class="text-decoration-none">
                                            <div class="card-moderne add-reinscription-card h-100">
                                                <div class="p-md h-100 d-flex flex-column align-items-center justify-content-center text-center"
                                                     style="min-height: 300px;">
                                                    <div class="mb-3">
                                                        <i class="fas fa-plus-circle" style="font-size: 4rem; color: var(--primary);"></i>
                                                    </div>
                                                    <h5 class="mb-2 font-semibold" style="color: var(--primary);">
                                                        Nouvelle Réinscription
                                                    </h5>
                                                    <p class="text-secondary mb-0">
                                                        Réinscrire pour l'année<br>
                                                        <strong>{{ $anneeCourante->name }}</strong>
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune inscription n'est enregistrée pour cet étudiant.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-moderne">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-money-bill-wave"></i>Paiements
                            </div>
                                    @if($etudiant->paiements && $etudiant->paiements->count() > 0)
                                        <!-- Desktop : Table -->
                                        <div class="table-responsive d-none d-lg-block">
                                            <table class="table table-bordered table-striped">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Numéro Reçu</th>
                                                        <th>Référence</th>
                                                        <th>Motif</th>
                                                        <th>Montant</th>
                                                        <th>Date Paiement</th>
                                                        <th>Mode</th>
                                                        <th>Statut</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($etudiant->paiements->sortByDesc('date_paiement') as $paiement)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $paiement->numero_recu ?: 'N/A' }}</strong>
                                                            </td>
                                                            <td>{{ $paiement->reference_paiement ?: 'N/A' }}</td>
                                                            <td>
                                                                <span class="text-muted small">{{ Str::limit($paiement->motif ?: 'Non spécifié', 30) }}</span>
                                                            </td>
                                                            <td class="text-end">
                                                                <strong class="text-primary">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                                                            </td>
                                                            <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A' }}</td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">{{ $paiement->mode_paiement ?: 'N/A' }}</span>
                                                            </td>
                                                            <td>
                                                                @php
                                                                    $status = $paiement->status ?? 'en_attente';
                                                                @endphp
                                                                @if($status == 'validé')
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check me-1"></i>Validé
                                                                    </span>
                                                                @elseif($status == 'en_attente')
                                                                    <span class="badge bg-warning">
                                                                        <i class="fas fa-clock me-1"></i>En attente
                                                                    </span>
                                                                @elseif($status == 'rejeté')
                                                                    <span class="badge bg-danger">
                                                                        <i class="fas fa-times me-1"></i>Rejeté
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ $status }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}"
                                                                       class="btn btn-sm btn-outline-primary"
                                                                       title="Voir détails">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    @if($paiement->status == 'validé' && $paiement->numero_recu)
                                                                        <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}"
                                                                           class="btn btn-sm btn-outline-success"
                                                                           title="Télécharger reçu"
                                                                           target="_blank">
                                                                            <i class="fas fa-file-pdf"></i>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Mobile : Cards -->
                                        <div class="d-lg-none">
                                            @foreach($etudiant->paiements->sortByDesc('date_paiement') as $paiement)
                                                @php
                                                    $status = $paiement->status ?? 'en_attente';
                                                @endphp
                                                <div class="card mb-3 border-start border-4 border-{{ $status == 'validé' ? 'success' : ($status == 'en_attente' ? 'warning' : 'danger') }}">
                                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                                        <strong class="text-dark"><i class="fas fa-receipt me-1"></i>{{ $paiement->numero_recu ?: 'N/A' }}</strong>
                                                        @if($status == 'validé')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Validé
                                                            </span>
                                                        @elseif($status == 'en_attente')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock me-1"></i>En attente
                                                            </span>
                                                        @elseif($status == 'rejeté')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Rejeté
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $status }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="card-body p-3">
                                                        <div class="mb-3 text-center">
                                                            <div class="h4 mb-0 text-primary fw-bold">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                                                        </div>
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Référence</small>
                                                                <strong class="small">{{ $paiement->reference_paiement ?: 'N/A' }}</strong>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Date</small>
                                                                <strong class="small">{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A' }}</strong>
                                                            </div>
                                                            <div class="col-12">
                                                                <small class="text-muted d-block">Motif</small>
                                                                <span class="small">{{ $paiement->motif ?: 'Non spécifié' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Mode</small>
                                                                <span class="badge bg-light text-dark">{{ $paiement->mode_paiement ?: 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <a href="{{ route('esbtp.paiements.show', $paiement->id) }}"
                                                               class="btn btn-sm btn-outline-primary flex-fill">
                                                                <i class="fas fa-eye me-1"></i>Détails
                                                            </a>
                                                            @if($paiement->status == 'validé' && $paiement->numero_recu)
                                                                <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}"
                                                                   class="btn btn-sm btn-outline-success flex-fill"
                                                                   target="_blank">
                                                                    <i class="fas fa-file-pdf me-1"></i>Reçu
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        @php
                                            $totalPaiements = $etudiant->paiements->sum('montant');
                                            $paiementsValides = $etudiant->paiements->where('status', 'validé')->sum('montant');
                                            $paiementsEnAttente = $etudiant->paiements->where('status', 'en_attente')->sum('montant');
                                        @endphp
                                        <div class="row g-3 mt-3">
                                            <div class="col-12 col-sm-6 col-md-4">
                                                <div class="card border-primary">
                                                    <div class="card-body text-center p-2">
                                                        <small class="text-muted">Total Validé</small>
                                                        <div class="h6 mb-0 text-primary">{{ number_format($paiementsValides, 0, ',', ' ') }} FCFA</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6 col-md-4">
                                                <div class="card border-warning">
                                                    <div class="card-body text-center p-2">
                                                        <small class="text-muted">En Attente</small>
                                                        <div class="h6 mb-0 text-warning">{{ number_format($paiementsEnAttente, 0, ',', ' ') }} FCFA</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6 col-md-4">
                                                <div class="card border-info">
                                                    <div class="card-body text-center p-2">
                                                        <small class="text-muted">Total Général</small>
                                                        <div class="h6 mb-0 text-info">{{ number_format($totalPaiements, 0, ',', ' ') }} FCFA</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Aucun paiement n'est enregistré pour cet étudiant.
                                        </div>
                                    @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour suppression étudiant -->
@can('delete_students')
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStudentModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Suppression définitive de l'étudiant
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-0 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">⚠️ ATTENTION - Action irréversible</h6>
                            <p class="mb-0">Cette action supprimera définitivement toutes les données de l'étudiant. Cette opération ne peut pas être annulée.</p>
                        </div>
                    </div>
                </div>

                <div class="student-info mb-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-user me-2"></i>Étudiant à supprimer :</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong><br>
                            <small class="text-muted">Matricule: {{ $etudiant->matricule }}</small>
                        </div>
                        <div class="col-md-6">
                            @if($etudiant->email_personnel)
                            <small class="text-muted">Email: {{ $etudiant->email_personnel }}</small><br>
                            @endif
                            <small class="text-muted">ID: {{ $etudiant->id }}</small>
                        </div>
                    </div>
                </div>

                <div class="deletion-preview">
                    <h6><i class="fas fa-list me-2"></i>Données qui seront supprimées :</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-graduation-cap text-primary me-2"></i>Inscriptions ({{ $etudiant->inscriptions->count() }})</li>
                                <li><i class="fas fa-money-bill text-success me-2"></i>Paiements ({{ $etudiant->paiements->count() }})</li>
                                <li><i class="fas fa-file-alt text-info me-2"></i>Notes et bulletins</li>
                                <li><i class="fas fa-calendar-times text-warning me-2"></i>Absences et présences</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-receipt text-secondary me-2"></i>Frais et factures</li>
                                <li><i class="fas fa-users text-dark me-2"></i>Relations familiales ({{ $etudiant->parents->count() }})</li>
                                @if($etudiant->user_id)
                                <li><i class="fas fa-user-circle text-danger me-2"></i>Compte utilisateur</li>
                                @endif
                                <li><i class="fas fa-phone text-info me-2"></i>Historique de relances</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="confirmation-section mt-4 p-3 bg-warning bg-opacity-10 rounded">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmDeletion" required>
                        <label class="form-check-label fw-bold" for="confirmDeletion">
                            Je comprends que cette action est irréversible et supprimera définitivement toutes les données de cet étudiant.
                        </label>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="keepUserAccount">
                        <label class="form-check-label" for="keepUserAccount">
                            Conserver le compte utilisateur (optionnel)
                        </label>
                    </div>
                </div>

                <div class="loading-overlay d-none text-center py-4" id="deletionProgress">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Suppression en cours...</span>
                    </div>
                    <p class="mt-2 text-muted">Suppression en cours, veuillez patienter...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="deleteStudent()">
                    <i class="fas fa-trash me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

<!-- Script pour la suppression d'étudiant -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Script de suppression chargé');

    // Gérer l'activation du bouton de confirmation
    const confirmCheckbox = document.getElementById('confirmDeletion');
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    debugLog('Checkbox:', confirmCheckbox);
    debugLog('Button:', confirmBtn);

    if (confirmCheckbox && confirmBtn) {
        confirmCheckbox.addEventListener('change', function() {
            debugLog('Checkbox changé:', this.checked);
            confirmBtn.disabled = !this.checked;
        });
    } else {
        debugError('Éléments non trouvés:', {
            checkbox: !!confirmCheckbox,
            button: !!confirmBtn
        });
    }
});

async function deleteStudent() {
    debugLog('deleteStudent appelée');

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const loadingOverlay = document.getElementById('deletionProgress');
    const modalBody = document.querySelector('#deleteStudentModal .modal-body');
    const keepUser = document.getElementById('keepUserAccount')?.checked || false;

    if (!confirmBtn || !loadingOverlay || !modalBody) {
        debugError('Éléments requis non trouvés');
        return;
    }

    // Désactiver le bouton et afficher le loading
    confirmBtn.disabled = true;
    modalBody.classList.add('d-none');
    loadingOverlay.classList.remove('d-none');

    try {
        debugLog('Envoi de la requête de suppression...');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            throw new Error('Token CSRF non trouvé');
        }

        const response = await fetch('{{ route("esbtp.etudiants.destroy", $etudiant->id) }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                keep_user: keepUser
            })
        });

        debugLog('Réponse reçue:', response.status);

        if (response.ok) {
            const data = await response.json();
            debugLog('Succès:', data);

            // Succès - rediriger vers la liste des étudiants
            window.location.href = '{{ route("esbtp.etudiants.index") }}';
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Une erreur est survenue lors de la suppression');
        }

    } catch (error) {
        debugError('Erreur:', error);

        // Restaurer l'affichage
        loadingOverlay.classList.add('d-none');
        modalBody.classList.remove('d-none');
        confirmBtn.disabled = false;

        // Afficher un message d'erreur
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreur:</strong> ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        modalBody.insertBefore(alertDiv, modalBody.firstChild);
    }
}

// Fonction pour upload de photo
async function uploadPhoto(input) {
    const file = input.files[0];
    if (!file) return;

    // Vérifier le type de fichier
    if (!file.type.startsWith('image/')) {
        alert('Veuillez sélectionner un fichier image valide.');
        return;
    }

    // Vérifier la taille (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        alert('Le fichier est trop volumineux. Taille maximale: 5MB');
        return;
    }

    const formData = new FormData();
    formData.append('photo', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    try {
        // Afficher un indicateur de chargement
        const photoContainer = document.querySelector('.photo-container');
        photoContainer.style.opacity = '0.6';

        const response = await fetch('{{ route("esbtp.etudiants.update-photo", $etudiant->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (response.ok) {
            // Mettre à jour l'image
            const imgElement = photoContainer.querySelector('img');
            if (imgElement) {
                imgElement.src = data.photo_url + '?t=' + new Date().getTime();
            } else {
                // Créer l'image si elle n'existe pas
                photoContainer.innerHTML = `
                    <img src="${data.photo_url}?t=${new Date().getTime()}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                    <div class="photo-overlay position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border: 3px solid white;">
                        <i class="fas fa-camera text-white"></i>
                    </div>
                `;
            }

            // Afficher un message de succès
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Photo mise à jour avec succès!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.p-lg').insertBefore(alertDiv, document.querySelector('.p-lg').firstChild);

            // Supprimer l'alerte après 3 secondes
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);

        } else {
            throw new Error(data.message || 'Erreur lors de l\'upload');
        }

    } catch (error) {
        debugError('Erreur upload photo:', error);
        alert('Erreur lors de la mise à jour de la photo: ' + error.message);
    } finally {
        // Restaurer l'opacité
        document.querySelector('.photo-container').style.opacity = '1';
        // Réinitialiser l'input
        input.value = '';
    }
}
</script>

@endsection
