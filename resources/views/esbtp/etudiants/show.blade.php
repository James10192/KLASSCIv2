@extends('layouts.app')

@section('title', 'Détails de l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de l'étudiant: {{ $etudiant->nom }} {{ $etudiant->prenoms }}</h5>
                    <div>
                        @if(isset($etudiant) && $etudiant->id)
                        <a href="{{ route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id]) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        @endif
                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                        <a href="{{ route('esbtp.etudiants.certificat', ['etudiant' => $etudiant->id]) }}" class="btn btn-success btn-sm" target="_blank">
                            <i class="fas fa-file-pdf"></i> Certificat de scolarité
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations personnelles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        @if($etudiant->photo)
                                            <img src="{{ asset('storage/'.$etudiant->photo) }}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
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
                                            <th>Ville/Commune de naissance</th>
                                            <td>
                                                @if($etudiant->ville_naissance || $etudiant->commune_naissance)
                                                    {{ $etudiant->ville_naissance }} {{ $etudiant->commune_naissance ? ', '.$etudiant->commune_naissance : '' }}
                                                @else
                                                    Non renseigné
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <td>{{ $etudiant->telephone }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $etudiant->email }}</td>
                                        </tr>
                                        <tr>
                                            <th>Adresse</th>
                                            <td>
                                                @if($etudiant->ville || $etudiant->commune)
                                                    {{ $etudiant->ville }} {{ $etudiant->commune ? ', '.$etudiant->commune : '' }}
                                                @else
                                                    Non renseignée
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Date d'admission</th>
                                            <td>{{ $etudiant->date_admission ? $etudiant->date_admission->format('d/m/Y') : 'Non renseignée' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Compte utilisateur</h6>
                                </div>
                                <div class="card-body">
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
                        </div>

                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Parents / Tuteurs</h6>
                                </div>
                                <div class="card-body">
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

                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Inscriptions</h6>
                                </div>
                                <div class="card-body">
                                    @if($etudiant->inscriptions->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Année universitaire</th>
                                                        <th>Filière</th>
                                                        <th>Niveau</th>
                                                        <th>Classe</th>
                                                        <th>Date d'inscription</th>
                                                        <th>Statut</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($etudiant->inscriptions->sortByDesc('created_at') as $inscription)
                                                        <tr>
                                                            <td>{{ $inscription->anneeUniversitaire ? $inscription->anneeUniversitaire->name : 'N/A' }}</td>
                                                            <td>{{ $inscription->filiere ? $inscription->filiere->name : 'N/A' }}</td>
                                                            <td>{{ $inscription->niveau ? $inscription->niveau->name : 'N/A' }}</td>
                                                            <td>{{ $inscription->classe ? $inscription->classe->name : 'Non assigné' }}</td>
                                                            <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                                                            <td>
                                                                @if($inscription->status == 'active')
                                                                    <span class="badge bg-success">Active</span>
                                                                @elseif($inscription->status == 'pending')
                                                                    <span class="badge bg-warning">En attente</span>
                                                                @elseif($inscription->status == 'annulée')
                                                                    <span class="badge bg-danger">Annulée</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ $inscription->status }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>

                                                                    @if($inscription->status == 'pending')
                                                                        @can('inscriptions.validate')
                                                                        <button type="button" class="btn btn-sm btn-success valider-btn" data-bs-toggle="modal" data-bs-target="#validationModal{{ $inscription->id }}" title="Valider l'inscription">
                                                                            <i class="fas fa-check"></i>
                                                                        </button>

                                                                        <!-- Modal de validation -->
                                                                        <div class="modal fade" id="validationModal{{ $inscription->id }}" tabindex="-1" aria-labelledby="validationModalLabel{{ $inscription->id }}" aria-hidden="true">
                                                                            <div class="modal-dialog">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="validationModalLabel{{ $inscription->id }}">Valider l'inscription</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                    </div>
                                                                                    <form action="{{ route('esbtp.inscriptions.valider', $inscription->id) }}" method="POST">
                                                                                        @csrf
                                                                                        @method('PUT')
                                                                                        <div class="modal-body">
                                                                                            <p>Êtes-vous sûr de vouloir valider cette inscription ?</p>
                                                                                            <p>L'étudiant sera automatiquement activé et pourra accéder à son compte.</p>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                                            <button type="submit" class="btn btn-success">Valider l'inscription</button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endcan
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Aucune inscription n'est enregistrée pour cet étudiant.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Paiements</h6>
                                </div>
                                <div class="card-body">
                                    @if($etudiant->paiements && $etudiant->paiements->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Référence</th>
                                                        <th>Type</th>
                                                        <th>Montant</th>
                                                        <th>Date</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($etudiant->paiements->sortByDesc('created_at') as $paiement)
                                                        <tr>
                                                            <td>{{ $paiement->reference }}</td>
                                                            <td>{{ $paiement->type }}</td>
                                                            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ $paiement->created_at->format('d/m/Y H:i') }}</td>
                                                            <td>
                                                                @if($paiement->statut == 'approuvé')
                                                                    <span class="badge bg-success">Approuvé</span>
                                                                @elseif($paiement->statut == 'en_attente')
                                                                    <span class="badge bg-warning">En attente</span>
                                                                @else
                                                                    <span class="badge bg-danger">Rejeté</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
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
    </div>
</div>
@endsection
