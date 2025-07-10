@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Détail facture ' . $facture->numero)

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="navigation-link">
            <i class="fas fa-home"></i> Accueil
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements') }}" class="navigation-link">
            <i class="fas fa-credit-card"></i> Paiements
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses') }}" class="navigation-link">
            <i class="fas fa-shopping-cart"></i> Dépenses
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link">
            <i class="fas fa-file-export"></i> Bons de Sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link active">
            <i class="fas fa-file-invoice"></i> Factures
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="navigation-link">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </li>
@endsection

@section('header')
    <div class="header-left">
        <h1><i class="fas fa-file-invoice color-primary"></i> Détail de la facture <span class="text-primary">#{{ $facture->numero }}</span></h1>
        <p class="header-subtitle">Informations détaillées de la facture</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.factures.pdf', $facture->id) }}" class="btn-acasi secondary" target="_blank">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.factures.edit', $facture->id) }}" class="btn-acasi secondary">
            <i class="fas fa-edit"></i> Éditer
        </a>
    </div>
@endsection

@section('content-block')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informations principales</h5>
                    <ul class="list-unstyled mb-0">
                        <li><strong>Étudiant :</strong> {{ $facture->etudiant ? $facture->etudiant->prenoms . ' ' . $facture->etudiant->nom : '-' }}</li>
                        <li><strong>Matricule :</strong> {{ $facture->etudiant ? $facture->etudiant->matricule : '-' }}</li>
                        <li><strong>Date émission :</strong> {{ $facture->date_emission ? $facture->date_emission->format('d/m/Y') : '-' }}</li>
                        <li><strong>Date échéance :</strong> {{ $facture->date_echeance ? $facture->date_echeance->format('d/m/Y') : '-' }}</li>
                        <li><strong>Année universitaire :</strong> {{ $facture->anneeUniversitaire->nom ?? '-' }}</li>
                        <li><strong>Créateur :</strong> {{ $facture->createur->name ?? '-' }}</li>
                        <li><strong>Fournisseur :</strong> {{ $facture->fournisseur->nom ?? '-' }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Montants</h5>
                    <ul class="list-unstyled mb-0">
                        <li><strong>Montant total :</strong> <span class="fw-bold text-primary">{{ number_format($facture->montant_total, 0, ',', ' ') }} FCFA</span></li>
                        <li><strong>Montant payé :</strong> <span class="fw-bold text-success">{{ number_format($facture->montant_paye, 0, ',', ' ') }} FCFA</span></li>
                        <li><strong>Montant restant :</strong> <span class="fw-bold text-danger">{{ number_format(max(0, $facture->montant_total - $facture->montant_paye), 0, ',', ' ') }} FCFA</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <h5 class="card-title px-4 pt-4">Détails de la facture</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facture->details as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $detail->description }}</td>
                                <td>{{ $detail->quantite }}</td>
                                <td>{{ number_format($detail->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                                <td class="fw-bold">{{ number_format($detail->montant, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aucun détail de facture.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('esbtp.comptabilite.factures.edit', $facture->id) }}" class="btn-acasi primary">
            <i class="fas fa-edit"></i> Éditer
        </a>
        <form action="{{ route('esbtp.comptabilite.factures.destroy', $facture->id) }}" method="POST" onsubmit="return confirm('Supprimer cette facture ?');">
            @csrf
            @method('DELETE')
            <button class="btn-acasi danger"><i class="fas fa-trash"></i> Supprimer</button>
        </form>
    </div>
</div>
@endsection
