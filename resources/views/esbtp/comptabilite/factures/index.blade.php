@extends('layouts.app')

@section('title', 'Liste des factures')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold mb-0">Liste des factures</h2>
            <a href="{{ route('esbtp.comptabilite.factures.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nouvelle facture
            </a>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Numéro</th>
                            <th>Étudiant</th>
                            <th>Date émission</th>
                            <th>Montant total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($factures as $facture)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $facture->numero }}</td>
                                <td>
                                    @if($facture->etudiant)
                                        <span class="fw-semibold">{{ $facture->etudiant->prenoms }} {{ $facture->etudiant->nom }}</span><br>
                                        <span class="text-muted small">{{ $facture->etudiant->matricule }}</span>
                                    @else
                                        <span class="text-danger">-</span>
                                    @endif
                                </td>
                                <td>{{ $facture->date_emission ? $facture->date_emission->format('d/m/Y') : '-' }}</td>
                                <td class="fw-bold">{{ number_format($facture->montant_total, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    @php
                                        $badge = 'secondary';
                                        if ($facture->statut === 'émise') $badge = 'info';
                                        elseif ($facture->statut === 'payée') $badge = 'success';
                                        elseif ($facture->statut === 'partiellement payée') $badge = 'warning';
                                        elseif ($facture->statut === 'annulée') $badge = 'danger';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($facture->statut) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('esbtp.comptabilite.factures.show', $facture->id) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.comptabilite.factures.pdf', $facture->id) }}" class="btn btn-sm btn-outline-secondary" title="PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <a href="{{ route('esbtp.comptabilite.factures.edit', $facture->id) }}" class="btn btn-sm btn-outline-info" title="Éditer">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('esbtp.comptabilite.factures.destroy', $facture->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer cette facture ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Aucune facture trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($factures, 'links'))
            <div class="card-footer">
                {{ $factures->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
