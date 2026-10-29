@extends('layouts.app')

@section('title', 'Détails de la bourse')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Détails de la bourse</h5>
            <div>
                <a href="{{ route('esbtp.comptabilite.bourses.edit', $bourse->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
                <a href="{{ route('esbtp.comptabilite.bourses') }}" class="btn btn-secondary btn-sm">
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
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Étudiant:</p>
                                    <p>{{ $bourse->etudiant->nom_complet ?? $bourse->etudiant->user->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Matricule:</p>
                                    <p>{{ $bourse->etudiant->matricule ?? 'Non renseigné' }}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Année universitaire:</p>
                                    <p>{{ $bourse->anneeUniversitaire->nom ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Type de bourse:</p>
                                    <p>{{ ucfirst($bourse->type_bourse) }}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Organisme financeur:</p>
                                    <p>{{ $bourse->organisme_financeur ?? 'Non renseigné' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Statut:</p>
                                    <p>
                                        @if($bourse->statut == 'active')
                                        <span class="badge bg-success">Active</span>
                                        @elseif($bourse->statut == 'suspendue')
                                        <span class="badge bg-warning">Suspendue</span>
                                        @elseif($bourse->statut == 'terminée')
                                        <span class="badge bg-secondary">Terminée</span>
                                        @else
                                        <span class="badge bg-info">{{ $bourse->statut }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Détails financiers</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                @if($bourse->montant)
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Montant:</p>
                                    <p class="text-primary fs-5">{{ number_format($bourse->montant, 0, ',', ' ') }} FCFA</p>
                                </div>
                                @endif
                                
                                @if($bourse->pourcentage)
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Pourcentage:</p>
                                    <p class="text-primary fs-5">{{ $bourse->pourcentage }}%</p>
                                </div>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Date de début:</p>
                                    <p>{{ $bourse->date_debut ? $bourse->date_debut->format('d/m/Y') : 'Non renseignée' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 fw-bold">Date de fin:</p>
                                    <p>{{ $bourse->date_fin ? $bourse->date_fin->format('d/m/Y') : 'Non définie' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Conditions et commentaires</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="fw-bold">Conditions d'attribution:</p>
                                <div class="p-3 bg-light rounded">
                                    {!! nl2br(e($bourse->conditions)) ?? '<em class="text-muted">Aucune condition spécifiée</em>' !!}
                                </div>
                            </div>
                            <div>
                                <p class="fw-bold">Commentaires:</p>
                                <div class="p-3 bg-light rounded">
                                    {!! nl2br(e($bourse->commentaires)) ?? '<em class="text-muted">Aucun commentaire</em>' !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('esbtp.comptabilite.bourses.edit', $bourse->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                                
                                <!-- Changer le statut -->
                                @if($bourse->statut != 'active')
                                <form action="{{ route('esbtp.comptabilite.bourses.update', $bourse->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="statut" value="active">
                                    <input type="hidden" name="etudiant_id" value="{{ $bourse->etudiant_id }}">
                                    <input type="hidden" name="annee_universitaire_id" value="{{ $bourse->annee_universitaire_id }}">
                                    <input type="hidden" name="type_bourse" value="{{ $bourse->type_bourse }}">
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check-circle me-1"></i> Activer la bourse
                                    </button>
                                </form>
                                @endif
                                
                                @if($bourse->statut != 'suspendue')
                                <form action="{{ route('esbtp.comptabilite.bourses.update', $bourse->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="statut" value="suspendue">
                                    <input type="hidden" name="etudiant_id" value="{{ $bourse->etudiant_id }}">
                                    <input type="hidden" name="annee_universitaire_id" value="{{ $bourse->annee_universitaire_id }}">
                                    <input type="hidden" name="type_bourse" value="{{ $bourse->type_bourse }}">
                                    
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-pause-circle me-1"></i> Suspendre la bourse
                                    </button>
                                </form>
                                @endif
                                
                                @if($bourse->statut != 'terminée')
                                <form action="{{ route('esbtp.comptabilite.bourses.update', $bourse->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="statut" value="terminée">
                                    <input type="hidden" name="etudiant_id" value="{{ $bourse->etudiant_id }}">
                                    <input type="hidden" name="annee_universitaire_id" value="{{ $bourse->annee_universitaire_id }}">
                                    <input type="hidden" name="type_bourse" value="{{ $bourse->type_bourse }}">
                                    
                                    <button type="submit" class="btn btn-secondary w-100">
                                        <i class="fas fa-times-circle me-1"></i> Terminer la bourse
                                    </button>
                                </form>
                                @endif
                                
                                <!-- Formulaire de suppression -->
                                <form action="{{ route('esbtp.comptabilite.bourses.destroy', $bourse->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette bourse? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash-alt me-1"></i> Supprimer la bourse
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
                            <p><strong>ID:</strong> {{ $bourse->id }}</p>
                            <p><strong>Créé par:</strong> {{ $bourse->createur->name ?? 'N/A' }}</p>
                            <p><strong>Date de création:</strong> {{ $bourse->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Dernière modification:</strong> {{ $bourse->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 