@extends('layouts.app')

@section('title', 'Suivi des Paiements - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/cursor-fix.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Suivi des Paiements</h1>
                <p class="header-subtitle">Monitoring des paiements étudiants et relances automatiques</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Suivi par Catégorie
                </a>
                @can('create-paiements')
                <a href="{{ route('esbtp.paiements.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau Paiement
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- KPI Cards Harmonisées avec le Système de Catégories -->
        <div class="kpi-grid">
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Frais Académiques Payés</div>
                <div class="kpi-value color-success">{{ number_format($stats['academic_paid'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-graduation-cap"></i>
                    @if($stats['academic_total'] > 0)
                        {{ number_format(($stats['academic_paid'] / $stats['academic_total']) * 100, 1) }}% payé
                    @else
                        Aucun frais
                    @endif
                </div>
            </div>
            
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Services Optionnels</div>
                <div class="kpi-value color-warning">{{ number_format($stats['service_paid'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend">
                    <i class="fas fa-cogs"></i>
                    @if($stats['service_total'] > 0)
                        {{ number_format(($stats['service_paid'] / $stats['service_total']) * 100, 1) }}% payé
                    @else
                        Aucun service
                    @endif
                </div>
            </div>
            
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Frais Administratifs</div>
                <div class="kpi-value color-info">{{ number_format($stats['administrative_paid'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend">
                    <i class="fas fa-file-alt"></i>
                    @if($stats['administrative_total'] > 0)
                        {{ number_format(($stats['administrative_paid'] / $stats['administrative_total']) * 100, 1) }}% payé
                    @else
                        Aucun frais
                    @endif
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Taux de Recouvrement Global</div>
                <div class="kpi-value color-primary">{{ $stats['recovery_rate'] }}%</div>
                <div class="kpi-trend {{ $stats['recovery_rate'] >= 75 ? 'positive' : ($stats['recovery_rate'] >= 50 ? '' : 'negative') }}">
                    <i class="fas fa-chart-line"></i>
                    {{ number_format($stats['montant_valide'], 0, ',', ' ') }} / {{ number_format($stats['montant_total'], 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>

        <!-- Statistiques Détaillées par Catégorie -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="d-flex justify-content-between align-items-center mb-md">
                    <div class="section-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Répartition des Paiements par Catégorie
                    </div>
                    <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="btn-acasi secondary small">
                        <i class="fas fa-chart-bar me-1"></i>Vue détaillée
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="resultat-card border-start border-success border-3">
                            <div class="resultat-title">Frais Académiques</div>
                            <div class="resultat-montant color-success">{{ number_format($stats['academic_paid'], 0, ',', ' ') }} FCFA</div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $stats['academic_total'] > 0 ? ($stats['academic_paid'] / $stats['academic_total']) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">
                                En attente: {{ number_format($stats['academic_pending'], 0, ',', ' ') }} FCFA
                            </small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="resultat-card border-start border-warning border-3">
                            <div class="resultat-title">Services Optionnels</div>
                            <div class="resultat-montant color-warning">{{ number_format($stats['service_paid'], 0, ',', ' ') }} FCFA</div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $stats['service_total'] > 0 ? ($stats['service_paid'] / $stats['service_total']) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">
                                En attente: {{ number_format($stats['service_pending'], 0, ',', ' ') }} FCFA
                            </small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="resultat-card border-start border-info border-3">
                            <div class="resultat-title">Frais Administratifs</div>
                            <div class="resultat-montant color-info">{{ number_format($stats['administrative_paid'], 0, ',', ' ') }} FCFA</div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: {{ $stats['administrative_total'] > 0 ? ($stats['administrative_paid'] / $stats['administrative_total']) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">
                                En attente: {{ number_format($stats['administrative_pending'], 0, ',', ' ') }} FCFA
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et Actions -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <form action="{{ route('esbtp.paiements.index') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Matricule, nom, n° reçu..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="validé" {{ request('status') == 'validé' ? 'selected' : '' }}>Validé</option>
                                <option value="rejeté" {{ request('status') == 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="annee_id" class="form-label">Année universitaire</label>
                            <select name="annee_id" id="annee_id" class="form-select">
                                <option value="">Toutes</option>
                                @foreach($annees ?? [] as $annee)
                                    <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des Paiements -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-list me-2"></i>
                    Liste des Paiements
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>N° Reçu</th>
                                <th>Étudiant</th>
                                <th>Catégorie</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paiements as $paiement)
                                <tr>
                                    <td>
                                        <strong class="color-primary">{{ $paiement->numero_recu }}</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary me-2">
                                                {{ substr($paiement->etudiant->user->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="text-decoration-none">
                                                    <strong>{{ $paiement->etudiant->user->name }}</strong>
                                                </a>
                                                <br><small class="text-muted">{{ $paiement->etudiant->matricule }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            // Logique harmonisée pour déterminer la catégorie
                                            $categoryInfo = null;
                                            $categoryColors = [
                                                'academic' => 'success',
                                                'service' => 'warning', 
                                                'administrative' => 'info'
                                            ];
                                            $categoryIcons = [
                                                'academic' => 'fas fa-graduation-cap',
                                                'service' => 'fas fa-cogs',
                                                'administrative' => 'fas fa-file-alt'
                                            ];
                                            
                                            // D'abord essayer avec le nouveau système
                                            if ($paiement->fraisCategory) {
                                                $categoryInfo = [
                                                    'name' => $paiement->fraisCategory->name,
                                                    'type' => $paiement->fraisCategory->category_type ?? 'academic',
                                                    'source' => 'Nouveau système'
                                                ];
                                            }
                                            // Fallback sur l'ancien système
                                            elseif ($paiement->categorie) {
                                                $categoryInfo = [
                                                    'name' => $paiement->categorie->nom ?? 'Catégorie ancienne',
                                                    'type' => $paiement->categorie->nom && str_contains(strtolower($paiement->categorie->nom), 'cantine') ? 'service' : 'academic',
                                                    'source' => 'Ancien système'
                                                ];
                                            }
                                            // Fallback sur le motif
                                            elseif ($paiement->motif) {
                                                $motifLower = strtolower($paiement->motif);
                                                $type = 'academic';
                                                if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
                                                    $type = 'service';
                                                } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
                                                    $type = 'administrative';
                                                }
                                                $categoryInfo = [
                                                    'name' => $paiement->motif,
                                                    'type' => $type,
                                                    'source' => 'Inféré du motif'
                                                ];
                                            }
                                            
                                            $color = $categoryColors[$categoryInfo['type'] ?? 'academic'] ?? 'secondary';
                                            $icon = $categoryIcons[$categoryInfo['type'] ?? 'academic'] ?? 'fas fa-money-bill';
                                        @endphp
                                        
                                        @if($categoryInfo)
                                            <div class="badge bg-{{ $color }} d-flex align-items-center" style="max-width: 150px;">
                                                <i class="{{ $icon }} me-1"></i>
                                                <span class="text-truncate">{{ $categoryInfo['name'] }}</span>
                                            </div>
                                            <small class="text-muted d-block">{{ ucfirst($categoryInfo['type']) }}</small>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-question me-1"></i>Non définie
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                    <td>
                                        <strong class="color-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $paiement->mode_paiement }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'validé' => 'success',
                                                'en_attente' => 'warning', 
                                                'rejeté' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$paiement->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}">
                                            {{ $paiement->status_formatte }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" 
                                               class="btn btn-outline-info" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($paiement->status != 'validé')
                                                @can('edit-paiements')
                                                <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}" 
                                                   class="btn btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endcan
                                                
                                                @can('validate-paiements')
                                                <a href="{{ route('esbtp.paiements.valider', $paiement->id) }}" 
                                                   class="btn btn-outline-success" 
                                                   title="Valider"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir valider ce paiement ?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                @endcan
                                            @endif
                                            
                                            @if($paiement->status == 'validé')
                                                <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" 
                                                   class="btn btn-outline-primary" title="Télécharger le reçu">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <br><span class="text-muted">Aucun paiement trouvé</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if($paiements->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-lg">
                    <div class="text-muted">
                        Affichage de {{ $paiements->firstItem() }} à {{ $paiements->lastItem() }} 
                        sur {{ $paiements->total() }} paiements
                    </div>
                    <div>
                        {{ $paiements->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.table th {
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

@endpush