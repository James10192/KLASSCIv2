@extends('layouts.app')

@section('title', 'Détails des frais de scolarité')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Détails des frais de scolarité</h5>
            <div>
                <a href="{{ route('esbtp.comptabilite.frais-scolarite.edit', $fraisScolarite->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
                <a href="{{ route('esbtp.comptabilite.frais-scolarite') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la liste
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
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Informations générales</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <p class="mb-1 fw-bold">Filière:</p>
                                    <p>{{ $fraisScolarite->filiere->name }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 fw-bold">Niveau d'études:</p>
                                    <p>{{ $fraisScolarite->niveau->name }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 fw-bold">Année universitaire:</p>
                                    <p>{{ $fraisScolarite->anneeUniversitaire->name }}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Montant total:</p>
                                    <p class="text-primary fs-5">{{ number_format($fraisScolarite->montant_total, 0, ',', ' ') }} FCFA</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Frais d'inscription:</p>
                                    <p class="text-primary fs-5">{{ number_format($fraisScolarite->frais_inscription, 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Nombre d'échéances:</p>
                                    <p>{{ $fraisScolarite->nombre_echeances }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Statut:</p>
                                    <p>
                                        @if($fraisScolarite->est_actif)
                                        <span class="badge bg-success">Actif</span>
                                        @else
                                        <span class="badge bg-danger">Inactif</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Décomposition des frais</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Frais mensuel:</p>
                                    <p>{{ number_format($fraisScolarite->frais_mensuel, 0, ',', ' ') ?? 'Non spécifié' }} FCFA</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Frais trimestriel:</p>
                                    <p>{{ number_format($fraisScolarite->frais_trimestriel, 0, ',', ' ') ?? 'Non spécifié' }} FCFA</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Frais semestriel:</p>
                                    <p>{{ number_format($fraisScolarite->frais_semestriel, 0, ',', ' ') ?? 'Non spécifié' }} FCFA</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Frais annuel:</p>
                                    <p>{{ number_format($fraisScolarite->frais_annuel, 0, ',', ' ') ?? 'Non spécifié' }} FCFA</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="mb-1 fw-bold">Échéance mensuelle calculée:</p>
                                    <p class="text-success fs-5">
                                        {{ number_format($fraisScolarite->getFraisMensuelParEcheanceAttribute(), 0, ',', ' ') }} FCFA
                                        <small class="text-muted">(Montant total - Frais d'inscription) / {{ $fraisScolarite->nombre_echeances }} échéances</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($fraisScolarite->details)
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Détails supplémentaires</h6>
                        </div>
                        <div class="card-body">
                            <p>{{ $fraisScolarite->details }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('esbtp.comptabilite.frais-scolarite.edit', $fraisScolarite->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                                
                                <!-- Formulaire pour activer/désactiver -->
                                <form action="{{ route('esbtp.comptabilite.frais-scolarite.update', $fraisScolarite->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="filiere_id" value="{{ $fraisScolarite->filiere_id }}">
                                    <input type="hidden" name="niveau_id" value="{{ $fraisScolarite->niveau_id }}">
                                    <input type="hidden" name="annee_universitaire_id" value="{{ $fraisScolarite->annee_universitaire_id }}">
                                    <input type="hidden" name="montant_total" value="{{ $fraisScolarite->montant_total }}">
                                    <input type="hidden" name="frais_inscription" value="{{ $fraisScolarite->frais_inscription }}">
                                    <input type="hidden" name="frais_mensuel" value="{{ $fraisScolarite->frais_mensuel }}">
                                    <input type="hidden" name="frais_trimestriel" value="{{ $fraisScolarite->frais_trimestriel }}">
                                    <input type="hidden" name="frais_semestriel" value="{{ $fraisScolarite->frais_semestriel }}">
                                    <input type="hidden" name="frais_annuel" value="{{ $fraisScolarite->frais_annuel }}">
                                    <input type="hidden" name="nombre_echeances" value="{{ $fraisScolarite->nombre_echeances }}">
                                    <input type="hidden" name="details" value="{{ $fraisScolarite->details }}">
                                    <input type="hidden" name="est_actif" value="{{ $fraisScolarite->est_actif ? 0 : 1 }}">
                                    
                                    <button type="submit" class="btn btn-{{ $fraisScolarite->est_actif ? 'warning' : 'success' }} w-100">
                                        <i class="fas fa-{{ $fraisScolarite->est_actif ? 'times-circle' : 'check-circle' }} me-1"></i> 
                                        {{ $fraisScolarite->est_actif ? 'Désactiver' : 'Activer' }} cette configuration
                                    </button>
                                </form>
                                
                                <!-- Formulaire de suppression -->
                                <form action="{{ route('esbtp.comptabilite.frais-scolarite.destroy', $fraisScolarite->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette configuration de frais de scolarité? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash-alt me-1"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">Informations système</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>ID:</strong> {{ $fraisScolarite->id }}</p>
                            <p><strong>Date de création:</strong> {{ $fraisScolarite->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Dernière modification:</strong> {{ $fraisScolarite->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 