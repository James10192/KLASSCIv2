@extends('layouts.app')

@section('title', 'Liste des factures')

@section('content')
<div class="container-fluid py-4">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-file-invoice-dollar fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Liste des factures</h1>
                <div class="text-white-50">Suivi et gestion des factures de l'établissement</div>
            </div>
        </div>
        <a href="{{ route('esbtp.comptabilite.factures.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Nouvelle facture
        </a>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle premium-table mb-0">
                                <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
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
                                                <span class="badge bg-{{ $badge }} px-3 py-2">{{ ucfirst($facture->statut) }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('esbtp.comptabilite.factures.show', $facture->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.comptabilite.factures.pdf', $facture->id) }}" class="btn btn-secondary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="PDF" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="{{ route('esbtp.comptabilite.factures.edit', $facture->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Éditer">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('esbtp.comptabilite.factures.destroy', $facture->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer cette facture ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Supprimer"><i class="fas fa-trash"></i></button>
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
                        <div class="card-footer bg-white border-0 rounded-bottom-4">
                            {{ $factures->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
