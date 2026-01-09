@extends('layouts.app')

@section('title', 'Suivi des Paiements - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Suivi des Paiements des Frais</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('esbtp.frais.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-cogs me-1"></i>Configuration
            </a>
            <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-outline-secondary">
                <i class="fas fa-sliders-h me-1"></i>Règles
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques résumé -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Étudiants</h6>
                            <h4 class="mb-0">{{ $totalStudents }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-coins fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Encaissé</h6>
                            <h4 class="mb-0 text-success">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Attendu</h6>
                            <h4 class="mb-0 text-warning">{{ number_format($totalExpected, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Reste à Payer</h6>
                            <h4 class="mb-0 text-danger">{{ number_format($totalRemaining, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('esbtp.frais.payments') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select class="form-select" name="filiere_id" id="filiere_id">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="niveau_id" class="form-label">Niveau</label>
                            <select class="form-select" name="niveau_id" id="niveau_id">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="frais_category_id" class="form-label">Type de Frais</label>
                            <select class="form-select" name="frais_category_id" id="frais_category_id">
                                <option value="">Tous les types</option>
                                @foreach($fraisCategories as $category)
                                    <option value="{{ $category->id }}" {{ request('frais_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">Tous les statuts</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partiel</option>
                                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Non payé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('esbtp.frais.payments') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des paiements -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Détail des Paiements par Étudiant</h5>
        </div>
        <div class="card-body">
            @if($inscriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Filière/Niveau</th>
                                <th>Frais Obligatoires</th>
                                <th>Total Payé</th>
                                <th>Reste à Payer</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inscriptions as $inscription)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                {{ strtoupper(substr($inscription->etudiant->nom, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenom }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $inscription->etudiant->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $inscription->filiere->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $inscription->niveau->name }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $totalAttendu = collect($inscription->fraisDetails)->sum('montant_attendu');
                                        @endphp
                                        <span class="fw-bold">{{ number_format($totalAttendu, 0, ',', ' ') }} FCFA</span>
                                        <br>
                                        <small class="text-muted">{{ collect($inscription->fraisDetails)->count() }} frais</small>
                                    </td>
                                    <td>
                                        @php
                                            $totalPaye = collect($inscription->fraisDetails)->sum('total_paye');
                                        @endphp
                                        <span class="fw-bold text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</span>
                                        @if($totalAttendu > 0)
                                            <br>
                                            <small class="text-muted">{{ number_format(($totalPaye / $totalAttendu) * 100, 1) }}%</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $resteAPayer = $totalAttendu - $totalPaye;
                                        @endphp
                                        <span class="fw-bold {{ $resteAPayer > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $status = $resteAPayer <= 0 ? 'paid' : ($totalPaye > 0 ? 'partial' : 'unpaid');
                                        @endphp
                                        @if($status == 'paid')
                                            <span class="badge bg-success">Payé</span>
                                        @elseif($status == 'partial')
                                            <span class="badge bg-warning">Partiel</span>
                                        @else
                                            <span class="badge bg-danger">Non payé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Voir détail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="showPaymentDetails({{ $inscription->id }})"
                                                    title="Historique des paiements">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $inscriptions->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucun résultat trouvé</h5>
                    <p class="text-muted">Essayez de modifier vos filtres de recherche.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal pour l'historique des paiements -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">Historique des Paiements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentHistoryContent">
                <!-- Contenu chargé via AJAX -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showPaymentDetails(inscriptionId) {
    const modal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
    const content = document.getElementById('paymentHistoryContent');
    
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    modal.show();
    
    // Simuler le chargement des détails (à remplacer par un appel AJAX réel)
    setTimeout(() => {
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Cette fonctionnalité sera implémentée dans une future version.
            </div>
        `;
    }, 1000);
}
</script>
@endpush