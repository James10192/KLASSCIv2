@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Détails du paiement')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-money-check-alt"></i> Paiements
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-receipt"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-truck-loading"></i> Bons de sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('sidebarRight')
    <div class="acasi-sidebar-right-block mb-4">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list"></i> Liste des paiements
        </a>
        <a href="{{ route('esbtp.comptabilite.paiements.create') }}" class="btn btn-primary w-100">
            <i class="fas fa-plus"></i> Nouveau paiement
        </a>
    </div>
@endsection

@section('content-block')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="acasi-card p-5 rounded-3 shadow mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>Détails du paiement : <span class="text-primary">{{ $paiement->reference_paiement }}</span></h4>
                <div class="d-flex gap-2">
                @if($paiement->statut != 'completé')
                    <a href="{{ route('esbtp.comptabilite.paiements.edit', $paiement->id) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
                @endif
                    <a href="{{ route('esbtp.comptabilite.paiements.recu', $paiement->id) }}" class="btn btn-success" target="_blank">
                    <i class="fas fa-file-invoice me-1"></i> Générer reçu
                </a>
                    <a href="{{ route('esbtp.comptabilite.paiements') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                </a>
            </div>
        </div>
            <div class="row g-4">
                                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="fw-medium">Référence :</span>
                        <span class="ms-2">{{ $paiement->reference_paiement }}</span>
                                </div>
                    <div class="mb-3">
                        <span class="fw-medium">Type :</span>
                        <span class="ms-2">{{ $paiement->type_paiement }}</span>
                                </div>
                    <div class="mb-3">
                        <span class="fw-medium">Montant :</span>
                        <span class="ms-2 fs-5 text-primary">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</span>
                            </div>
                    <div class="mb-3">
                        <span class="fw-medium">Date de paiement :</span>
                        <span class="ms-2">{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A' }}</span>
                                </div>
                    <div class="mb-3">
                        <span class="fw-medium">Mode de paiement :</span>
                        <span class="ms-2">{{ ucfirst($paiement->mode_paiement) }}</span>
                                </div>
                    <div class="mb-3">
                        <span class="fw-medium">Numéro de transaction :</span>
                        <span class="ms-2">{{ $paiement->numero_transaction ?? 'N/A' }}</span>
                                </div>
                            </div>
                                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="fw-medium">Statut :</span>
                        <span class="ms-2">
                                        @if($paiement->statut == 'completé')
                                        <span class="badge bg-success">Complété</span>
                                        @elseif($paiement->statut == 'en_attente')
                                        <span class="badge bg-warning">En attente</span>
                                        @elseif($paiement->statut == 'annulé')
                                        <span class="badge bg-danger">Annulé</span>
                                        @else
                                        <span class="badge bg-secondary">{{ $paiement->statut }}</span>
                                        @endif
                        </span>
                                </div>
                    <div class="mb-3">
                        <span class="fw-medium">Date d'échéance :</span>
                        <span class="ms-2">{{ $paiement->date_echeance ? $paiement->date_echeance->format('d/m/Y') : 'N/A' }}</span>
                            </div>
                            @if($paiement->description)
                    <div class="mb-3">
                        <span class="fw-medium">Description :</span>
                        <span class="ms-2">{{ $paiement->description }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                        </div>
        <div class="acasi-card p-4 rounded-3 shadow mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-user-graduate me-2"></i>Informations de l'étudiant</h5>
            <div class="row g-3">
                                <div class="col-md-6">
                    <span class="fw-medium">Nom complet :</span>
                    <span class="ms-2">{{ $paiement->etudiant->nom ?? '' }} {{ $paiement->etudiant->prenom ?? '' }}</span>
                                </div>
                                <div class="col-md-6">
                    <span class="fw-medium">Matricule :</span>
                    <span class="ms-2">{{ $paiement->etudiant->matricule ?? 'N/A' }}</span>
                            </div>
                                <div class="col-md-6">
                    <span class="fw-medium">Classe :</span>
                    <span class="ms-2">{{ $paiement->etudiant->classe->libelle ?? $paiement->etudiant->classe->name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-6">
                    <span class="fw-medium">Année universitaire :</span>
                    <span class="ms-2">{{ $paiement->anneeUniversitaire->nom ?? $paiement->anneeUniversitaire->name ?? 'N/A' }}</span>
                                </div>
                            </div>
            <div class="mt-4">
                                    <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user me-1"></i> Voir le profil de l'étudiant
                                    </a>
                                </div>
                            </div>
                        </div>
    <div class="col-lg-4">
        <div class="acasi-card p-4 rounded-3 shadow mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-cogs me-2"></i>Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="{{ route('esbtp.comptabilite.paiements.recu', $paiement->id) }}" class="btn btn-success" target="_blank">
                                    <i class="fas fa-file-invoice me-1"></i> Générer reçu
                                </a>
                                @if($paiement->statut != 'completé')
                <a href="{{ route('esbtp.comptabilite.paiements.edit', $paiement->id) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                                @endif
                                @if($paiement->statut == 'en_attente')
                                <form action="{{ route('esbtp.comptabilite.paiements.valider', $paiement->id) }}" method="POST">
                                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-check-circle me-1"></i> Valider ce paiement
                                    </button>
                                </form>
                                <form action="{{ route('esbtp.comptabilite.paiements.rejeter', $paiement->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter ce paiement?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-times-circle me-1"></i> Rejeter ce paiement
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
        <div class="acasi-card p-4 rounded-3 shadow mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Informations système</h5>
            <div class="mb-2"><strong>ID :</strong> {{ $paiement->id }}</div>
            <div class="mb-2"><strong>Créé par :</strong> {{ $paiement->createdBy->name ?? 'N/A' }}</div>
            <div class="mb-2"><strong>Date de création :</strong> {{ $paiement->created_at->format('d/m/Y H:i') }}</div>
                            @if($paiement->updated_at)
            <div class="mb-2"><strong>Dernière modification :</strong> {{ $paiement->updated_at->format('d/m/Y H:i') }}</div>
                            @endif
                            @if($paiement->date_validation)
            <div class="mb-2"><strong>Date de validation :</strong> {{ $paiement->date_validation->format('d/m/Y H:i') }}</div>
            <div class="mb-2"><strong>Validé par :</strong> {{ $paiement->validateur->name ?? 'N/A' }}</div>
                            @endif
        </div>
    </div>
</div>
@endsection 
