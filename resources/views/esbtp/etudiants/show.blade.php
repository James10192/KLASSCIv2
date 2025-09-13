@extends('layouts.app')

@section('title', 'Détails de l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
            <div class="header-actions">
                @if(isset($etudiant) && $etudiant->id)
                <a href="{{ route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id]) }}" class="btn-acasi primary me-2">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                @endif
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
                <div class="dropdown me-2">
                    <button class="btn-acasi info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-alt"></i>Documents
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
                    <i class="fas fa-plus"></i>Nouveau paiement
                </a>
            </div>
        </div>

        <div class="p-lg">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
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
                <div class="col-md-4">
                    <div class="card-moderne">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-user"></i>Informations personnelles
                            </div>
                                    <div class="text-center mb-4">
                                        @if($etudiant->photo_url)
                                            <img src="{{ $etudiant->photo_url }}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 150px; height: 150px;">
                                                <i class="fas fa-user fa-5x text-secondary"></i>
                                            </div>
                                        @endif
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
                                            <th>Téléphone</th>
                                            <td>{{ $etudiant->telephone }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $etudiant->email ?: 'Non renseigné' }}</td>
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
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Aucun compte utilisateur n'est associé à cet étudiant.
                                            <div class="mt-2">
                                                <a href="{{ route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user-plus me-1"></i>Créer un compte
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                        </div>
                    </div>

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
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-md-8">
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
                                        <div class="col-md-6 mb-4">
                                            <div class="card-moderne">
                                                <div class="p-md">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h6 class="mb-0 font-semibold">{{ $inscription->anneeUniversitaire ? $inscription->anneeUniversitaire->libelle : 'N/A' }}</h6>
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
                                        <div class="table-responsive">
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
                                        
                                        @php
                                            $totalPaiements = $etudiant->paiements->sum('montant');
                                            $paiementsValides = $etudiant->paiements->where('status', 'validé')->sum('montant');
                                            $paiementsEnAttente = $etudiant->paiements->where('status', 'en_attente')->sum('montant');
                                        @endphp
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="card border-primary">
                                                    <div class="card-body text-center p-2">
                                                        <small class="text-muted">Total Validé</small>
                                                        <div class="h6 mb-0 text-primary">{{ number_format($paiementsValides, 0, ',', ' ') }} FCFA</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card border-warning">
                                                    <div class="card-body text-center p-2">
                                                        <small class="text-muted">En Attente</small>
                                                        <div class="h6 mb-0 text-warning">{{ number_format($paiementsEnAttente, 0, ',', ' ') }} FCFA</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
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
@endsection
