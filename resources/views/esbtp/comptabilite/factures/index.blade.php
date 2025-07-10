@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Gestion des factures')

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
        <h1><i class="fas fa-file-invoice color-primary"></i> Gestion des factures</h1>
        <p class="header-subtitle">Suivi et gestion des factures de l'établissement</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.factures.create') }}" class="btn-acasi primary">
            <i class="fas fa-plus-circle"></i> Nouvelle facture
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.rapports') }}" class="btn-acasi secondary">
            <i class="fas fa-file-export"></i> Export Rapport
        </a>
        <a href="{{ route('esbtp.comptabilite.dashboard-avance') }}" class="btn-acasi secondary">
            <i class="fas fa-chart-line"></i> Voir Dashboard Avancé
        </a>
    </div>
@endsection

@section('content-block')
    {{-- Tableau des factures --}}
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
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
    {{-- Pagination --}}
    <div class="mt-3">
        @if(method_exists($factures, 'links'))
            {{ $factures->links() }}
        @endif
    </div>
@endsection
