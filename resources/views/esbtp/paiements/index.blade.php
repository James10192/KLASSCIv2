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

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}" selected>
                                {{ date('Y') . '-' . (date('Y') + 1) }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les paiements affichés correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

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

            @if($stats['reliquats_total'] > 0)
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Reliquats à Recouvrer</div>
                <div class="kpi-value color-danger">{{ number_format($stats['reliquats_total'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend">
                    <i class="fas fa-history"></i>
                    Années précédentes
                </div>
            </div>
            @endif
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

                @if($stats['reliquats_total'] > 0)
                <div class="mt-3">
                    <div class="alert alert-info alert-sm">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note :</strong> Les montants "En attente" incluent les reliquats des années précédentes ({{ number_format($stats['reliquats_total'], 0, ',', ' ') }} FCFA).
                    </div>
                </div>
                @endif
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
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all" class="form-check-input" title="Tout sélectionner">
                                </th>
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
                                        @if($paiement->status == 'en_attente' && auth()->user()->hasRole('superAdmin'))
                                            <input type="checkbox" class="form-check-input paiement-checkbox"
                                                   value="{{ $paiement->id }}"
                                                   data-status="{{ $paiement->status }}">
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="color-primary">{{ $paiement->numero_recu }}</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary me-2">
                                                {{ substr($paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet, 0, 2) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="text-decoration-none">
                                                    <strong>{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}</strong>
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
                                                
                                                @if(auth()->user()->hasRole('superAdmin'))
                                                <form action="{{ route('esbtp.paiements.valider', $paiement->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-outline-success"
                                                            title="Valider"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir valider ce paiement ?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>

                                                @if($paiement->status == 'en_attente')
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        title="Rejeter"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejetModal{{ $paiement->id }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @endif
                                                @endif
                                            @endif
                                            
                                            @if($paiement->status == 'validé')
                                                <div class="dropdown pdf-dropdown">
                                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                                            id="pdfDropdown{{ $paiement->id }}" data-bs-toggle="dropdown" 
                                                            aria-expanded="false" title="Options PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pdfDropdown{{ $paiement->id }}">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('esbtp.paiements.preview', $paiement->id) }}">
                                                                <i class="fas fa-eye me-1"></i>Prévisualiser
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}">
                                                                <i class="fas fa-download me-1"></i>Télécharger
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @endif

                                            @if(auth()->user()->hasRole('superAdmin'))
                                                <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}"
                                                   class="btn btn-outline-warning btn-sm"
                                                   title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <br><span class="text-muted">Aucun paiement trouvé</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Barre d'actions groupées -->
                @if(auth()->user()->hasRole('superAdmin'))
                <div class="bulk-actions-bar" id="bulk-actions-bar" style="display: none;">
                    <div class="bulk-actions-content">
                        <div class="bulk-actions-info">
                            <i class="fas fa-check-square me-2"></i>
                            <span id="selected-count">0</span> paiement(s) sélectionné(s)
                        </div>
                        <div class="bulk-actions-buttons">
                            <button type="button" class="btn btn-success" onclick="bulkValider()">
                                <i class="fas fa-check me-1"></i>Valider la sélection
                            </button>
                            <button type="button" class="btn btn-danger" onclick="openBulkRejetModal()">
                                <i class="fas fa-times me-1"></i>Rejeter la sélection
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                <i class="fas fa-times-circle me-1"></i>Annuler
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                
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

/* Styles pour les dropdowns PDF compacts */
.pdf-dropdown .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

.pdf-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item i {
    width: 14px;
    text-align: center;
}

/* Barre d'actions groupées */
.bulk-actions-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
    padding: 15px 30px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(4, 83, 203, 0.3);
    z-index: 1000;
    min-width: 600px;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateX(-50%) translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

.bulk-actions-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.bulk-actions-info {
    display: flex;
    align-items: center;
    font-weight: 600;
    font-size: 1rem;
}

.bulk-actions-buttons {
    display: flex;
    gap: 10px;
}

.bulk-actions-buttons .btn {
    padding: 8px 16px;
    font-weight: 600;
    border: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.2s;
}

.bulk-actions-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .bulk-actions-bar {
        min-width: 90%;
        left: 5%;
        transform: translateX(0);
        padding: 12px 15px;
    }

    .bulk-actions-content {
        flex-direction: column;
        gap: 10px;
    }

    .bulk-actions-buttons {
        width: 100%;
        flex-direction: column;
    }

    .bulk-actions-buttons .btn {
        width: 100%;
    }
}
</style>

@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les paiements affichés se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet groupé -->
@if(auth()->user()->hasRole('superAdmin'))
<div class="modal fade" id="bulkRejetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="bulk-rejet-form" method="POST" action="{{ route('esbtp.paiements.bulk-rejeter') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        Rejeter les paiements sélectionnés
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter <strong><span id="bulk-rejet-count">0</span> paiement(s)</strong>.
                    </div>

                    <div class="form-group">
                        <label for="bulk_motif_rejet">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk_motif_rejet" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ces paiements sont rejetés..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulk_confirmer_rejet" required>
                        <label class="form-check-label" for="bulk_confirmer_rejet">
                            Je confirme le rejet de ces paiements
                        </label>
                    </div>

                    <div id="bulk-selected-paiements"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>Rejeter les paiements
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modaux de rejet pour les paiements en attente -->
@foreach($paiements as $paiement)
    @if($paiement->status == 'en_attente')
        <div class="modal fade" id="rejetModal{{ $paiement->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                Rejeter le paiement
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Vous êtes sur le point de rejeter le paiement de <strong>{{ $paiement->etudiant->nom_complet ?? 'N/A' }}</strong>
                                d'un montant de <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>.
                            </div>

                            <div class="form-group">
                                <label for="motif_rejet_{{ $paiement->id }}">Motif du rejet <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="motif_rejet_{{ $paiement->id }}" name="motif_rejet" rows="4"
                                          placeholder="Expliquez pourquoi ce paiement est rejeté..." required></textarea>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmer_rejet_{{ $paiement->id }}" required>
                                <label class="form-check-label" for="confirmer_rejet_{{ $paiement->id }}">
                                    Je confirme le rejet de ce paiement
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times-circle me-1"></i>Rejeter le paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });

    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });

    // === GESTION DES ACTIONS GROUPÉES ===

    // Sélectionner/Désélectionner tous les paiements
    $('#select-all').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.paiement-checkbox').prop('checked', isChecked);
        updateBulkActionsBar();
    });

    // Gérer le changement sur les checkboxes individuelles
    $(document).on('change', '.paiement-checkbox', function() {
        updateBulkActionsBar();

        // Mettre à jour la checkbox "Tout sélectionner"
        const totalCheckboxes = $('.paiement-checkbox').length;
        const checkedCheckboxes = $('.paiement-checkbox:checked').length;
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
    });
});

// Mettre à jour la barre d'actions groupées
function updateBulkActionsBar() {
    const selectedCheckboxes = $('.paiement-checkbox:checked');
    const count = selectedCheckboxes.length;

    if (count > 0) {
        $('#bulk-actions-bar').slideDown(200);
        $('#selected-count').text(count);
    } else {
        $('#bulk-actions-bar').slideUp(200);
    }
}

// Valider les paiements sélectionnés
function bulkValider() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    const confirmMessage = `Êtes-vous sûr de vouloir valider ${selectedIds.length} paiement(s) ?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    // Créer et soumettre le formulaire
    const form = $('<form>', {
        'method': 'POST',
        'action': "{{ route('esbtp.paiements.bulk-valider') }}"
    });

    // Ajouter le token CSRF
    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': "{{ csrf_token() }}"
    }));

    // Ajouter les IDs sélectionnés
    selectedIds.forEach(function(id) {
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'paiements[]',
            'value': id
        }));
    });

    // Ajouter au DOM et soumettre
    $('body').append(form);
    form.submit();
}

// Ouvrir le modal de rejet groupé
function openBulkRejetModal() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    // Mettre à jour le compteur dans le modal
    $('#bulk-rejet-count').text(selectedIds.length);

    // Ajouter les IDs sélectionnés comme champs cachés
    const container = $('#bulk-selected-paiements');
    container.empty();

    selectedIds.forEach(function(id) {
        container.append($('<input>', {
            'type': 'hidden',
            'name': 'paiements[]',
            'value': id
        }));
    });

    // Réinitialiser le formulaire
    $('#bulk_motif_rejet').val('');
    $('#bulk_confirmer_rejet').prop('checked', false);

    // Afficher le modal
    $('#bulkRejetModal').modal('show');
}

// Annuler la sélection
function clearSelection() {
    $('.paiement-checkbox').prop('checked', false);
    $('#select-all').prop('checked', false);
    updateBulkActionsBar();
}

// Obtenir les IDs des paiements sélectionnés
function getSelectedPaiementIds() {
    const ids = [];
    $('.paiement-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}
</script>