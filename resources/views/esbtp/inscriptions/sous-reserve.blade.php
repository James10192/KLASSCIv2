@extends('layouts.app')

@section('title', 'Inscriptions sous réserve')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inscriptions-common.css') }}">
<style>
    .isr-hero-icon { background: rgba(255, 255, 255, 0.14); }
    .isr-condition-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        background: rgba(245, 158, 11, 0.1);
        color: #92400e;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.25rem; max-width: 100%;">

        {{-- ======================= HERO ======================= --}}
        <div class="ii-hero" data-kpis-wrapper>
            <div class="ii-hero-top">
                <div class="ii-hero-left">
                    <div class="ii-hero-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h1>Inscriptions sous réserve</h1>
                        <p>Inscriptions conditionnelles en attente de confirmation du document manquant</p>
                        <span class="ii-hero-chip">
                            <i class="fas fa-calendar-alt"></i>
                            @if(!empty($anneeFilterId) && ($selectedAnnee = $annees->firstWhere('id', $anneeFilterId)))
                                {{ $selectedAnnee->name }}
                            @else
                                Toutes les années
                            @endif
                        </span>
                    </div>
                </div>
                <div class="ii-hero-actions">
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="ii-btn ii-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            {{-- KPIs cliquables (filtre paiement) --}}
            <div class="ii-kpis">
                <button type="button" class="ii-kpi {{ $hasPayment === '' ? 'is-active' : '' }}" data-kpi="total" data-filter-payment="">
                    <div class="ii-kpi-icon"><i class="fas fa-list"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi-value>{{ $stats['total'] }}</div>
                        <div class="ii-kpi-label">Total</div>
                    </div>
                </button>
                <button type="button" class="ii-kpi {{ $hasPayment === 'yes' ? 'is-active' : '' }}" data-kpi="avec_paiement" data-filter-payment="yes">
                    <div class="ii-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi-value>{{ $stats['avec_paiement'] }}</div>
                        <div class="ii-kpi-label">Avec paiement</div>
                    </div>
                </button>
                <button type="button" class="ii-kpi {{ $hasPayment === 'no' ? 'is-active' : '' }}" data-kpi="sans_paiement" data-filter-payment="no">
                    <div class="ii-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi-value>{{ $stats['sans_paiement'] }}</div>
                        <div class="ii-kpi-label">Sans paiement</div>
                    </div>
                </button>
            </div>
        </div>

        {{-- ======================= TOOLBAR ======================= --}}
        <div class="ii-toolbar">
            <div class="ii-search">
                <i class="fas fa-search"></i>
                <input type="search" id="isr-search" placeholder="Nom, prénom ou matricule..." value="{{ $search }}">
            </div>
            <select id="isr-annee" class="ii-select" aria-label="Année universitaire">
                <option value="">Toutes les années</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" {{ ($anneeFilterId ?? '') == $annee->id ? 'selected' : '' }}>
                        {{ $annee->name }}@if($annee->is_current) (Courante)@endif
                    </option>
                @endforeach
            </select>
            <select id="isr-condition" class="ii-select" aria-label="Condition">
                <option value="">Toutes conditions</option>
                <option value="BACCALAURÉAT" {{ $condition === 'BACCALAURÉAT' ? 'selected' : '' }}>BACCALAURÉAT</option>
                <option value="BTS" {{ $condition === 'BTS' ? 'selected' : '' }}>BTS</option>
            </select>
            <button type="button" class="ii-btn ii-btn--ghost" id="isr-reset">
                <i class="fas fa-rotate-left"></i> Réinitialiser
            </button>
        </div>

        {{-- ======================= RESULTS ======================= --}}
        <div class="ii-results-card" id="isr-results-card">
            <div class="ii-results-header">
                <div class="ii-results-count">
                    <strong id="isr-total">{{ $inscriptions->total() }}</strong> inscription(s) sous réserve
                </div>
                <div class="ii-per-page">
                    <span>Afficher</span>
                    <select id="isr-per-page" aria-label="Lignes par page">
                        @foreach([15, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ request('per_page', 25) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span>par page</span>
                </div>
            </div>
            <div id="isr-results">
                @include('esbtp.inscriptions.partials.sous-reserve-results', ['inscriptions' => $inscriptions, 'anneeEnCours' => $anneeEnCours])
            </div>
        </div>
    </div>
</div>

{{-- Bulk bar — visible si l'utilisateur a au moins une action de masse --}}
@if(auth()->user()->canAny(['inscriptions.edit', 'inscriptions.view']))
<div class="ii-bulk-bar" id="isr-bulk-bar">
    <div class="ii-bulk-count">
        <i class="fas fa-check-circle me-1"></i>
        <span id="isr-bulk-count">0</span> sélectionnée(s)
    </div>
    @can('inscriptions.edit')
    <button type="button" class="ii-btn ii-btn--white" onclick="isrBulkLever()">
        <i class="fas fa-check-double"></i> Lever réserves
    </button>
    @endcan
    @can('inscriptions.view')
    <button type="button" class="ii-btn ii-btn--glass" onclick="isrBulkExporter()">
        <i class="fas fa-file-export"></i> Exporter CSV
    </button>
    @endcan
    <button type="button" class="ii-btn ii-btn--glass" onclick="isrClearSelection()" title="Fermer">
        <i class="fas fa-times"></i>
    </button>
</div>
@endif

@endsection

@push('scripts')
<script>
    window.KLASSCI_SOUSRESERVE_ROUTES = {
        index: '{{ route('esbtp.inscriptions.sous-reserve') }}',
        leverReservesBulk: '{{ route('esbtp.inscriptions.lever-reserves-bulk') }}',
        bulkExport: '{{ route('esbtp.inscriptions.bulk-export') }}',
        csrf: '{{ csrf_token() }}',
    };
</script>
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script src="{{ asset('js/inscriptions/sous-reserve.js') }}"></script>
@endpush
