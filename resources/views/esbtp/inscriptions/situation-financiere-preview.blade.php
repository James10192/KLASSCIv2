@extends('layouts.app')

@section('title', 'Situation Financière - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .info-group {
        margin-bottom: 1rem;
    }

    .info-label {
        font-weight: 600;
        color: #374151;
        margin-right: 0.5rem;
    }

    .info-value {
        color: #6b7280;
    }

    .financial-table {
        width: 100%;
    }

    .section-title {
        background: #f3f4f6;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        color: #374151;
        margin: 1.5rem 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge.soldé { background: #d1fae5; color: #065f46; }
    .status-badge.partiel { background: #fef3c7; color: #92400e; }
    .status-badge.impayé { background: #fee2e2; color: #991b1b; }
    .status-badge.reliquat { background: #fef3c7; color: #d97706; }

    @media print {
        .dashboard-header, .btn-acasi, .no-print {
            display: none !important;
        }

        .main-content {
            padding: 0 !important;
        }

        .main-card {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
            margin-bottom: 20px !important;
        }

        .kpi-grid {
            display: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-line me-2"></i>Situation Financière</h1>
                <p class="header-subtitle">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }} - {{ $inscription->etudiant->matricule }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription->id) }}" class="btn-acasi danger">
                    <i class="fas fa-file-pdf"></i>Télécharger PDF
                </a>
                <button onclick="window.print()" class="btn-acasi primary">
                    <i class="fas fa-print"></i>Imprimer
                </button>
                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Attendu</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-money-bill-wave"></i>
                    Frais année + reliquats
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Payé</div>
                <div class="kpi-value" style="color: var(--success); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Tous paiements validés
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Solde</div>
                <div class="kpi-value" style="color: {{ $statistiques['solde_restant'] > 0 ? 'var(--danger)' : 'var(--success)' }}; font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-balance-scale"></i>
                    {{ $statistiques['solde_restant'] > 0 ? 'Restant à payer' : 'Soldé' }}
                </div>
            </div>

            @if($statistiques['total_reliquats'] > 0)
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Reliquats</div>
                <div class="kpi-value" style="color: var(--warning); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-history"></i>
                    Années précédentes
                </div>
            </div>
            @endif
        </div>

        <!-- Informations Étudiant -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-user"></i>
                    Informations Étudiant
                </div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Matricule:</label>
                            <span class="info-value">{{ $inscription->etudiant->matricule ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Email:</label>
                            <span class="info-value">{{ $inscription->etudiant->email ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Téléphone:</label>
                            <span class="info-value">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Statut:</label>
                            <span class="badge bg-primary">{{ ucfirst($inscription->affectation_status ?? 'Affecté') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations Académiques -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-graduation-cap"></i>
                    Informations Académiques
                </div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Filière:</label>
                            <span class="info-value">{{ $inscription->filiere->name ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Niveau:</label>
                            <span class="info-value">{{ $inscription->niveau->name ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Classe:</label>
                            <span class="info-value">{{ $inscription->classe->name ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">Année universitaire:</label>
                            <span class="info-value">{{ $inscription->anneeUniversitaire->name ?? 'Non renseigné' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frais Souscrits - Année Courante -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Détail des Frais Souscrits - Année {{ $inscription->anneeUniversitaire->name }}
                </div>
                <div class="main-card-subtitle">Frais de l'année universitaire en cours</div>
            </div>
            <div class="main-card-body">
                @if($fraisSouscrits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover financial-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Catégorie de Frais</th>
                                    <th width="100" class="text-center">Type</th>
                                    <th width="150" class="text-end">Montant Attendu</th>
                                    <th width="150" class="text-end">Montant Payé</th>
                                    <th width="150" class="text-end">Solde</th>
                                    <th width="100" class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fraisSouscrits as $frais)
                                @php
                                    $montantPaye = $inscription->paiements
                                        ->where('frais_category_id', $frais->frais_category_id)
                                        ->where('status', 'validé')
                                        ->filter(function($paiement) {
                                            return $paiement->type_paiement != 'reliquat' || is_null($paiement->type_paiement);
                                        })
                                        ->sum('montant');
                                    $solde = $frais->amount - $montantPaye;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            <strong>{{ $frais->fraisCategory->name ?? 'Non renseigné' }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($frais->fraisCategory->is_mandatory)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="fas fa-star me-1"></i>Optionnel
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold">{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold {{ $solde > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($solde, 0, ',', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($solde <= 0)
                                            <span class="status-badge soldé">Soldé</span>
                                        @elseif($montantPaye > 0)
                                            <span class="status-badge partiel">Partiel</span>
                                        @else
                                            <span class="status-badge impayé">Impayé</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach

                                {{-- Intégrer les reliquats comme des lignes de frais --}}
                                @if($reliquatsEntrants->count() > 0)
                                    @foreach($reliquatsEntrants as $reliquat)
                                        @if($reliquat->solde_restant > 0)
                                            <tr class="table-warning">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-history me-2 text-warning"></i>
                                                        <div>
                                                            <strong>{{ $reliquat->fraisSubscription->fraisCategory->name ?? 'Non renseigné' }}</strong>
                                                            <br><small class="text-muted">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($reliquat->fraisSubscription->fraisCategory->is_mandatory)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-star me-1"></i>Optionnel
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-success fw-bold">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-warning">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-badge reliquat">Reliquat</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i>
                        <h5>Aucun frais souscrit</h5>
                        <p class="text-muted">Aucun frais souscrit pour cette inscription.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Reliquats d'Années Précédentes -->
        @if($reliquatsEntrants->count() > 0)
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-history"></i>
                    Reliquats d'Années Précédentes
                </div>
                <div class="main-card-subtitle">Frais provenant d'inscriptions antérieures</div>
            </div>
            <div class="main-card-body">
                <div class="table-responsive">
                    <table class="table table-hover financial-table">
                        <thead class="table-light">
                            <tr>
                                <th>Année d'Origine</th>
                                <th>Catégorie de Frais</th>
                                <th width="150" class="text-end">Montant Attendu</th>
                                <th width="150" class="text-end">Montant Payé</th>
                                <th width="150" class="text-end">Reliquat</th>
                                <th width="100" class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reliquatsEntrants as $reliquat)
                            <tr>
                                <td>
                                    <span class="badge bg-warning">{{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'Non renseigné' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock me-2 text-warning"></i>
                                        <strong>{{ $reliquat->fraisSubscription->fraisCategory->name ?? 'Non renseigné' }}</strong>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold">{{ number_format($reliquat->montant_attendu, 0, ',', ' ') }} FCFA</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-success fw-bold">{{ number_format($reliquat->montant_paye, 0, ',', ' ') }} FCFA</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-warning fw-bold">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</span>
                                </td>
                                <td class="text-center">
                                    @if($reliquat->statut == 'soldé')
                                        <span class="status-badge soldé">Soldé</span>
                                    @elseif($reliquat->montant_paye > 0)
                                        <span class="status-badge partiel">Partiel</span>
                                    @else
                                        <span class="status-badge impayé">Impayé</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Historique des Paiements -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-receipt"></i>
                    Historique des Paiements
                </div>
                <div class="main-card-subtitle">{{ $inscription->paiements->where('status', 'validé')->count() }} paiement(s) validé(s)</div>
            </div>
            <div class="main-card-body">
                @if($inscription->paiements->where('status', 'validé')->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Catégorie</th>
                                    <th>Mode de Paiement</th>
                                    <th width="120" class="text-end">Montant</th>
                                    <th width="100" class="text-center">Statut</th>
                                    <th>Référence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscription->paiements->where('status', 'validé')->sortByDesc('date_paiement') as $paiement)
                                <tr>
                                    <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : 'Non renseigné' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($paiement->type_paiement == 'reliquat')
                                                <i class="fas fa-history me-2 text-warning"></i>
                                            @else
                                                <i class="fas fa-money-bill me-2 text-success"></i>
                                            @endif
                                            {{ $paiement->fraisCategory->name ?? 'Non renseigné' }}
                                        </div>
                                    </td>
                                    <td>{{ $paiement->mode_paiement ?? 'Non renseigné' }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ strtoupper($paiement->status) }}</span>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $paiement->numero_recu ?? '-' }}</code>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-receipt text-muted fa-2x mb-3"></i>
                        <h5>Aucun paiement</h5>
                        <p class="text-muted">Aucun paiement validé pour cette inscription.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
            </small>
        </div>
    </div>
</div>
@endsection