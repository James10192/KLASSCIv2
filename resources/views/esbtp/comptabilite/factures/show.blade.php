@extends('layouts.app')

@section('title', 'Détail facture ' . $facture->numero)

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Facture <span class="text-primary">#{{ $facture->numero }}</span></h2>
                <span class="badge bg-{{ $facture->statut === 'émise' ? 'info' : ($facture->statut === 'payée' ? 'success' : ($facture->statut === 'partiellement payée' ? 'warning' : 'secondary')) }}">
                    {{ ucfirst($facture->statut) }}
                </span>
            </div>
            <div>
                <a href="{{ route('esbtp.comptabilite.factures.pdf', $facture->id) }}" class="btn btn-outline-secondary me-2" target="_blank">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('esbtp.comptabilite.factures') }}" class="btn btn-outline-dark">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
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
        <a href="{{ route('esbtp.comptabilite.factures.edit', $facture->id) }}" class="btn btn-info">
            <i class="fas fa-edit"></i> Éditer
        </a>
        <form action="{{ route('esbtp.comptabilite.factures.destroy', $facture->id) }}" method="POST" onsubmit="return confirm('Supprimer cette facture ?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Supprimer</button>
        </form>
    </div>
</div>
@endsection
