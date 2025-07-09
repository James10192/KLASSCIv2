@extends('layouts.app')

@section('title', 'Gestion des Bons de Sortie')

@section('content')
<div class="container-fluid">
    <!-- Header avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-file-export text-primary"></i>
                    Gestion des Bons de Sortie
                </h1>
                @can('comptabilite.bons.create')
                <a href="{{ route('esbtp.comptabilite.bons-sortie.create') }}"
                   class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau Bon
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistiques['total'] ?? 0 }}</h4>
                            <p class="mb-0">Total Bons</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistiques['en_attente'] ?? 0 }}</h4>
                            <p class="mb-0">En Attente</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistiques['approuve'] ?? 0 }}</h4>
                            <p class="mb-0">Approuvés</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistiques['rejete'] ?? 0 }}</h4>
                            <p class="mb-0">Rejetés</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> Filtres
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('esbtp.comptabilite.bons-sortie.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut" class="form-control">
                                <option value="">Tous les statuts</option>
                                <option value="brouillon" {{ request('statut') == 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                                <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En Attente</option>
                                <option value="approuve" {{ request('statut') == 'approuve' ? 'selected' : '' }}>Approuvé</option>
                                <option value="paye" {{ request('statut') == 'paye' ? 'selected' : '' }}>Payé</option>
                                <option value="rejete" {{ request('statut') == 'rejete' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date début</label>
                            <input type="date" name="date_debut" class="form-control"
                                   value="{{ request('date_debut') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date fin</label>
                            <input type="date" name="date_fin" class="form-control"
                                   value="{{ request('date_fin') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                                <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}"
                                   class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des bons -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Liste des Bons de Sortie
            </h5>
        </div>
        <div class="card-body">
            @if($bons->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Bon</th>
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
                        @foreach($bons as $bon)
                        <tr>
                            <td>
                                <strong class="text-primary">{{ $bon->numero_bon }}</strong>
                            </td>
                            <td>{{ $bon->libelle }}</td>
                            <td>
                                <span class="fw-bold text-success">
                                    {{ number_format($bon->montant, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($bon->date_depense)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $bon->categorie->nom ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statutClass = match($bon->statut_workflow) {
                                        'brouillon' => 'bg-secondary',
                                        'en_attente' => 'bg-warning',
                                        'approuve' => 'bg-success',
                                        'paye' => 'bg-primary',
                                        'rejete' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $statutText = match($bon->statut_workflow) {
                                        'brouillon' => 'Brouillon',
                                        'en_attente' => 'En Attente',
                                        'approuve' => 'Approuvé',
                                        'paye' => 'Payé',
                                        'rejete' => 'Rejeté',
                                        default => 'Inconnu'
                                    };
                                @endphp
                                <span class="badge {{ $statutClass }}">
                                    {{ $statutText }}
                                </span>
                            </td>
                            <td>{{ $bon->createur->name ?? 'N/A' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('esbtp.comptabilite.bons-sortie.show', $bon->id) }}"
                                       class="btn btn-outline-info" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if($bon->statut_workflow === 'brouillon')
                                        @can('comptabilite.bons.edit')
                                        <a href="{{ route('esbtp.comptabilite.bons-sortie.edit', $bon->id) }}"
                                           class="btn btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                    @endif

                                    @if($bon->statut_workflow === 'en_attente')
                                        @can('comptabilite.bons.approve')
                                        <button type="button"
                                                class="btn btn-outline-success"
                                                onclick="approuverBon({{ $bon->id }})"
                                                title="Approuver">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                onclick="rejeterBon({{ $bon->id }})"
                                                title="Rejeter">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @endcan
                                    @endif

                                    @if(in_array($bon->statut_workflow, ['approuve', 'paye']))
                                        <a href="{{ route('esbtp.comptabilite.bons-sortie.pdf', $bon->id) }}"
                                           class="btn btn-outline-primary"
                                           target="_blank"
                                           title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $bons->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-file-export fa-3x text-muted mb-3"></i>
                <h5>Aucun bon de sortie trouvé</h5>
                <p class="text-muted">Commencez par créer votre premier bon de sortie.</p>
                @can('comptabilite.bons.create')
                <a href="{{ route('esbtp.comptabilite.bons-sortie.create') }}"
                   class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer un Bon
                </a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modals pour approbation/rejet -->
<div class="modal fade" id="approuverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approuver le bon de sortie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approuverForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Commentaire (optionnel)</label>
                        <textarea name="commentaire" class="form-control" rows="3"
                                  placeholder="Commentaire sur l'approbation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approuver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejeterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeter le bon de sortie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejeterForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Motif de rejet <span class="text-danger">*</span></label>
                        <textarea name="motif" class="form-control" rows="3"
                                  placeholder="Précisez le motif du rejet..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approuverBon(bonId) {
    const modal = new bootstrap.Modal(document.getElementById('approuverModal'));
    const form = document.getElementById('approuverForm');
    form.action = `/esbtp/comptabilite/bons-sortie/${bonId}/approuver`;
    modal.show();
}

function rejeterBon(bonId) {
    const modal = new bootstrap.Modal(document.getElementById('rejeterModal'));
    const form = document.getElementById('rejeterForm');
    form.action = `/esbtp/comptabilite/bons-sortie/${bonId}/rejeter`;
    modal.show();
}

// Auto-refresh toutes les 30 secondes pour les statuts
setInterval(function() {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>
@endpush
