@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Gestion des bons de sortie')

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
    <li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="navigation-link active">
            <i class="fas fa-file-export"></i> Bons de Sortie
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures') }}" class="navigation-link">
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
        <h1><i class="fas fa-file-export color-primary"></i> Gestion des bons de sortie</h1>
        <p class="header-subtitle">Suivi et gestion des bons de sortie de l'établissement</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.create') }}" class="btn-acasi primary">
            <i class="fas fa-plus-circle"></i> Nouveau bon
        </a>
    </div>
@endsection

@section('sidebarRight')
    <h3 class="sidebar-title">Actions Rapides</h3>
    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index', ['export' => 'pdf']) }}" class="btn-acasi secondary">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('esbtp.comptabilite.bons-sortie.index', ['export' => 'excel']) }}" class="btn-acasi secondary">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>
    <div class="mt-lg">
        <h3 class="sidebar-title">Statistiques</h3>
        <ul class="list-unstyled">
            <li><span class="badge bg-secondary me-2">Total</span> {{ $statistiques['total'] ?? 0 }}</li>
            <li><span class="badge bg-warning me-2">En Attente</span> {{ $statistiques['en_attente'] ?? 0 }}</li>
            <li><span class="badge bg-success me-2">Approuvés</span> {{ $statistiques['approuve'] ?? 0 }}</li>
            <li><span class="badge bg-danger me-2">Rejetés</span> {{ $statistiques['rejete'] ?? 0 }}</li>
        </ul>
    </div>
@endsection

@section('content-block')
    {{-- Filtres compacts --}}
    <form method="GET" action="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="row g-3 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="brouillon" {{ request('statut') == 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En Attente</option>
                <option value="approuve" {{ request('statut') == 'approuve' ? 'selected' : '' }}>Approuvé</option>
                <option value="paye" {{ request('statut') == 'paye' ? 'selected' : '' }}>Payé</option>
                <option value="rejete" {{ request('statut') == 'rejete' ? 'selected' : '' }}>Rejeté</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn-acasi primary flex-fill">
                <i class="fas fa-search"></i> Filtrer
            </button>
            <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}" class="btn-acasi secondary flex-fill">
                <i class="fas fa-times"></i> Reset
            </a>
        </div>
    </form>

    {{-- Tableau des bons de sortie --}}
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Numéro</th>
                    <th>Libellé</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Créateur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bons as $bon)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $bon->numero_bon }}</td>
                        <td>{{ $bon->libelle }}</td>
                        <td class="fw-bold">{{ number_format($bon->montant, 0, ',', ' ') }} FCFA</td>
                        <td>{{ \Carbon\Carbon::parse($bon->date_depense)->format('d/m/Y') }}</td>
                        <td><span class="badge bg-secondary px-3 py-2">{{ $bon->categorie->nom ?? 'N/A' }}</span></td>
                        <td>
                            @php
                                $badge = 'secondary';
                                if ($bon->statut_workflow === 'brouillon') $badge = 'secondary';
                                elseif ($bon->statut_workflow === 'en_attente') $badge = 'warning';
                                elseif ($bon->statut_workflow === 'approuve') $badge = 'success';
                                elseif ($bon->statut_workflow === 'paye') $badge = 'primary';
                                elseif ($bon->statut_workflow === 'rejete') $badge = 'danger';
                            @endphp
                            <span class="badge bg-{{ $badge }} px-3 py-2">{{ ucfirst($bon->statut_workflow) }}</span>
                        </td>
                        <td>{{ $bon->createur->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('esbtp.comptabilite.bons-sortie.show', $bon->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($bon->statut_workflow === 'brouillon')
                                @can('comptabilite.bons.edit')
                                <a href="{{ route('esbtp.comptabilite.bons-sortie.edit', $bon->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                            @endif
                            @if($bon->statut_workflow === 'en_attente')
                                @can('comptabilite.bons.approve')
                                <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" onclick="approuverBon({{ $bon->id }})" title="Approuver">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" onclick="rejeterBon({{ $bon->id }})" title="Rejeter">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endcan
                            @endif
                            @if(in_array($bon->statut_workflow, ['approuve', 'paye']))
                                <a href="{{ route('esbtp.comptabilite.bons-sortie.pdf', $bon->id) }}" class="btn btn-secondary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" target="_blank" title="PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Aucun bon de sortie trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Pagination --}}
    <div class="mt-3">
        @if(method_exists($bons, 'links'))
            {{ $bons->links() }}
        @endif
    </div>
@endsection

@push('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script>
function approuverBon(id) {
    if(confirm('Confirmer l\'approbation de ce bon ?')) {
        window.location.href = '{{ url('esbtp/comptabilite/bons-sortie') }}/' + id + '/approuver';
    }
}
function rejeterBon(id) {
    if(confirm('Confirmer le rejet de ce bon ?')) {
        window.location.href = '{{ url('esbtp/comptabilite/bons-sortie') }}/' + id + '/rejeter';
    }
}
</script>
@endpush
