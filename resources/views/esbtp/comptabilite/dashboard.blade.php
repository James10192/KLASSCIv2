@extends('layouts.app')

@section('title', 'Dashboard Comptabilité')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.aging-badge { display: inline-block; width: 12px; height: 12px; border-radius: 50%; }
.aging-row:hover { background: rgba(4,83,203,.04); cursor: pointer; }
.pending-badge { font-size: .75rem; padding: 3px 8px; border-radius: 12px; }
.quick-action-card { transition: transform .15s; }
.quick-action-card:hover { transform: translateY(-2px); }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="page-title"><i class="fas fa-chart-line me-2 text-primary"></i>Dashboard Comptabilité</h1>
                <p class="text-muted mb-0">Vue financière de l'établissement</p>
            </div>
            <div class="header-actions d-flex gap-2">
                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi warning">
                    <i class="fas fa-bell me-1"></i>Relances
                </a>
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi primary">
                    <i class="fas fa-money-bill-wave me-1"></i>Paiements
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Filtres -->
        <div class="main-card p-4 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size:.85rem;">Année universitaire</label>
                    <select name="annee" class="form-select form-select-sm">
                        <option value="">Toutes les années</option>
                        @foreach($annees as $a)
                            <option value="{{ $a->id }}" {{ request('annee') == $a->id ? 'selected' : '' }}>{{ $a->name ?? $a->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size:.85rem;">Filière</label>
                    <select name="filiere" class="form-select form-select-sm">
                        <option value="">Toutes les filières</option>
                        @foreach($filieres as $f)
                            <option value="{{ $f->id }}" {{ request('filiere') == $f->id ? 'selected' : '' }}>{{ $f->name ?? $f->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size:.85rem;">Classe</label>
                    <select name="classe" class="form-select form-select-sm">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('classe') == $c->id ? 'selected' : '' }}>{{ $c->name ?? $c->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn-acasi primary flex-grow-1">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- KPI Cards Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="main-card p-4 text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:48px;height:48px;background:linear-gradient(135deg,#0453cb,#5e91de);">
                            <i class="fas fa-wallet"></i>
                        </span>
                    </div>
                    <div class="fw-bold mb-1" style="font-size:1.3rem;color:var(--primary);">{{ number_format($totalDue, 0, ',', ' ') }}</div>
                    <div class="text-muted small mb-2">FCFA Frais totaux</div>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3">Total dû</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="main-card p-4 text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:48px;height:48px;background:linear-gradient(135deg,#10b981,#059669);">
                            <i class="fas fa-coins"></i>
                        </span>
                    </div>
                    <div class="fw-bold mb-1" style="font-size:1.3rem;color:#10b981;">{{ number_format($totalPaid, 0, ',', ' ') }}</div>
                    <div class="text-muted small mb-2">FCFA Encaissé</div>
                    <span class="badge bg-success bg-opacity-10 text-success px-3">Validé</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="main-card p-4 text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:48px;height:48px;background:linear-gradient(135deg,#ef4444,#dc2626);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </div>
                    <div class="fw-bold mb-1" style="font-size:1.3rem;color:#ef4444;">{{ number_format($totalOverdue, 0, ',', ' ') }}</div>
                    <div class="text-muted small mb-2">FCFA Restant dû</div>
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3">Impayé</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="main-card p-4 text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:48px;height:48px;background:linear-gradient(135deg,#f59e0b,#d97706);">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                    </div>
                    <div class="fw-bold mb-1" style="font-size:1.3rem;color:#f59e0b;">{{ $countPartiallyPaid }}</div>
                    <div class="text-muted small mb-2">Paiements en attente</div>
                    <span class="badge bg-warning bg-opacity-10 text-warning px-3">À valider</span>
                </div>
            </div>
        </div>

        <!-- Aging Buckets + Graphique -->
        <div class="row g-4 mb-4">

            <!-- Aging Buckets -->
            <div class="col-12 col-lg-5">
                <div class="main-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-warning"></i>Ancienneté des impayés</h5>
                        <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi warning btn-sm">
                            <i class="fas fa-bell me-1"></i>Relancer
                        </a>
                    </div>

                    @php
                        $agingConfig = [
                            '0-30'  => ['label' => '0 – 30 jours',  'color' => '#10b981', 'risk' => 'Faible'],
                            '31-60' => ['label' => '31 – 60 jours', 'color' => '#f59e0b', 'risk' => 'Moyen'],
                            '61-90' => ['label' => '61 – 90 jours', 'color' => '#ef4444', 'risk' => 'Élevé'],
                            '90+'   => ['label' => '90+ jours',     'color' => '#7c3aed', 'risk' => 'Critique'],
                        ];
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr style="font-size:.8rem;color:var(--text-secondary);">
                                    <th class="border-0 pb-2">Tranche</th>
                                    <th class="border-0 pb-2 text-center">Étudiants</th>
                                    <th class="border-0 pb-2 text-end">Montant</th>
                                    <th class="border-0 pb-2 text-center">Risque</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agingBuckets as $key => $bucket)
                                @php $cfg = $agingConfig[$key]; @endphp
                                <tr class="aging-row" style="border-bottom:1px solid var(--border-light);">
                                    <td class="py-2">
                                        <span class="aging-badge me-2" style="background:{{ $cfg['color'] }};"></span>
                                        <span style="font-size:.85rem;">{{ $cfg['label'] }}</span>
                                    </td>
                                    <td class="py-2 text-center fw-bold" style="color:{{ $cfg['color'] }};">{{ $bucket['count'] }}</td>
                                    <td class="py-2 text-end" style="font-size:.85rem;">{{ number_format($bucket['amount'], 0, ',', ' ') }} F</td>
                                    <td class="py-2 text-center">
                                        <span class="pending-badge" style="background:{{ $cfg['color'] }}20;color:{{ $cfg['color'] }};">{{ $cfg['risk'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="pt-3 fw-bold" style="font-size:.85rem;">Total</td>
                                    <td class="pt-3 text-center fw-bold">{{ array_sum(array_column($agingBuckets, 'count')) }}</td>
                                    <td class="pt-3 text-end fw-bold text-danger" style="font-size:.85rem;">{{ number_format(array_sum(array_column($agingBuckets, 'amount')), 0, ',', ' ') }} F</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Actions rapides -->
                    <div class="mt-3 pt-3" style="border-top:1px solid var(--border-light);">
                        <p class="text-muted mb-2" style="font-size:.8rem;">Actions groupées</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi warning btn-sm">
                                <i class="fas fa-envelope me-1"></i>Envoyer relances
                            </a>
                            <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi secondary btn-sm">
                                <i class="fas fa-list me-1"></i>Voir paiements
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique Encaissements -->
            <div class="col-12 col-lg-7">
                <div class="main-card p-4 h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Encaissements mensuels</h5>
                    @if(count($labelsMois) > 0)
                    <canvas id="encaissementsChart" height="180"></canvas>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                        <p>Sélectionnez une année pour afficher le graphique</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Paiements en attente + Accès rapides -->
        <div class="row g-4">

            <!-- Paiements récents en attente -->
            <div class="col-12 col-lg-8">
                <div class="main-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-warning"></i>Paiements en attente de validation</h5>
                        <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}" class="btn-acasi secondary btn-sm">Voir tout</a>
                    </div>
                    @if($paiementsEnAttente->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="mb-0">Aucun paiement en attente</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead style="font-size:.8rem;color:var(--text-secondary);">
                                <tr>
                                    <th class="border-0 pb-2">Étudiant</th>
                                    <th class="border-0 pb-2">Catégorie</th>
                                    <th class="border-0 pb-2 text-end">Montant</th>
                                    <th class="border-0 pb-2">Date</th>
                                    <th class="border-0 pb-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiementsEnAttente as $paiement)
                                <tr style="font-size:.85rem;">
                                    <td class="py-2">
                                        <strong>{{ $paiement->inscription->etudiant->nom ?? 'N/A' }}</strong>
                                        <br><small class="text-muted">{{ $paiement->inscription->etudiant->prenoms ?? '' }}</small>
                                    </td>
                                    <td class="py-2">
                                        <span class="text-muted">{{ $paiement->fraisCategory->name ?? $paiement->motif ?? '—' }}</span>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-primary">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                                    <td class="py-2 text-muted">{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') }}</td>
                                    <td class="py-2 text-center">
                                        <a href="{{ route('esbtp.paiements.show', $paiement) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Accès rapides -->
            <div class="col-12 col-lg-4">
                <div class="main-card p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-rocket me-2 text-primary"></i>Accès rapides</h5>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('esbtp.frais.index') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-tags fa-2x text-warning mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Frais</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('esbtp.paiements.index') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Paiements</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Relances</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('esbtp.frais.configure') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-cog fa-2x text-secondary mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Config Frais</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-chart-pie fa-2x text-info mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Suivi</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('esbtp.paiements.index', ['format' => 'export-excel']) }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none quick-action-card d-block">
                                <i class="fas fa-file-excel fa-2x text-success mb-2"></i>
                                <small class="fw-semibold text-dark d-block">Export</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
@if(count($labelsMois) > 0)
const ctx = document.getElementById('encaissementsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($labelsMois),
        datasets: [{
            label: 'Encaissements (FCFA)',
            data: @json($dataEncaissements),
            borderColor: '#0453cb',
            backgroundColor: 'rgba(4,83,203,0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#0453cb',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => new Intl.NumberFormat('fr-FR').format(ctx.raw) + ' FCFA'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => new Intl.NumberFormat('fr-FR', {notation:'compact'}).format(val)
                },
                grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: { grid: { display: false } }
        }
    }
});
@endif
</script>
@endpush
