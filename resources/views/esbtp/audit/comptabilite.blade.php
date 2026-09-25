@extends('layouts.app')

@section('title', "Audit comptable")

@section('content')
<div class="container-fluid au-page">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="au-hero">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-coins"></i></div>
                <div class="au-hero-info">
                    <h1>Audit comptable</h1>
                    <p>Surveillance ciblée des opérations financières (paiements, factures, dépenses)</p>
                </div>
            </div>
            <div class="au-hero-actions">
                <a href="{{ route('esbtp.audit.index') }}" class="au-btn au-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au journal
                </a>
            </div>
        </div>

        <div class="au-kpis">
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['paiements_modifies']) }}</div>
                    <div class="au-kpi-label">Paiements modifiés (30j)</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['factures_modifiees']) }}</div>
                    <div class="au-kpi-label">Factures modifiées (30j)</div>
                </div>
            </div>
            <div class="au-kpi au-kpi--alert">
                <div class="au-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['annulations_semaine']) }}</div>
                    <div class="au-kpi-label">Annulations cette semaine</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['validations_semaine']) }}</div>
                    <div class="au-kpi-label">Validations cette semaine</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FILTRES ═══════════════════════════════ --}}
    <form method="GET" action="{{ route('esbtp.audit.comptabilite') }}" class="au-filters">
        <div class="au-filters-row">
            <x-au-select
                class="au-filter-grow"
                name="model_type"
                :value="request('model_type')"
                icon="fa-filter"
                placeholder="Tous les types financiers"
                :options="$financialModelsLabels" />
            <x-au-select
                name="event"
                :value="request('event')"
                placeholder="Tous événements"
                :options="[
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                ]" />
            <div class="au-filter-field">
                <input type="number" name="montant_min" value="{{ request('montant_min') }}" placeholder="Montant min (FCFA)" min="0">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date début">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="Date fin">
            </div>
            <button type="submit" class="au-btn au-btn--primary">
                <i class="fas fa-search"></i> Filtrer
            </button>
            <a href="{{ route('esbtp.audit.comptabilite') }}" class="au-filter-reset" title="Réinitialiser">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>

    {{-- ═══════════════════════════════ TABLEAU ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-list-ul"></i> Opérations financières auditées
                <span class="au-badge-count">{{ $audits->total() }} résultats</span>
            </div>
        </div>

        <div class="au-table-wrap">
            @if($audits->isEmpty())
                <div class="au-empty">
                    <i class="fas fa-search"></i>
                    <h3>Aucune opération trouvée</h3>
                    <p>Essayez de modifier vos critères de recherche.</p>
                </div>
            @else
                <table class="au-table au-table--expandable" x-data="{ openIds: [] }">
                    <thead>
                        <tr>
                            <th style="width:42px"></th>
                            <th>Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Montant (avant → après)</th>
                            <th>Liens</th>
                            <th class="au-th-actions">Détails</th>
                        </tr>
                    </thead>
                    <tbody id="auc-tbody">
                        @foreach($audits as $a)
                            @include('esbtp.audit._ligne-comptabilite')
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <x-liste-infinie :paginateur="$audits" cible="#auc-tbody" libelle="opérations" />
    </div>

</div>
@endsection

@push('styles')
<style>
@include('esbtp.audit._styles')
</style>
@endpush
