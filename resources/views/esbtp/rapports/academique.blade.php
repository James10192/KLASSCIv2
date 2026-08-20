@extends('layouts.app')

@section('title', $title.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@php
    $kind = $kind ?? ($report['kind'] ?? null);
    $pdfRoute = match($kind) {
        'rentree' => 'esbtp.rapports.rentree.pdf',
        'trimestre' => 'esbtp.rapports.trimestre.pdf',
        'annuel' => 'esbtp.rapports.annuel.pdf',
        default => null,
    };
    $previewRoute = $pdfRoute ? $pdfRoute.'-preview' : null;

    $heroIcon = match($kind) {
        'rentree' => 'fa-door-open',
        'trimestre' => 'fa-calendar-week',
        'annuel' => 'fa-book',
        default => 'fa-chart-line',
    };

    // Chaque indicateur porte un sens : « à surveiller » quand une valeur
    // non nulle traduit un manque, neutre quand c'est un simple effectif.
    $indicateurs = [
        [
            'icon' => 'fa-user-graduate',
            'label' => 'Inscrits valides',
            'value' => $report['inscrits_valides'],
            'hint' => 'Dossiers validés sur l\'année',
            'watch' => false,
        ],
        [
            'icon' => 'fa-chalkboard',
            'label' => 'Classes',
            'value' => $report['classes'],
            'hint' => 'Classes actives',
            'watch' => false,
        ],
        [
            'icon' => 'fa-user-tie',
            'label' => 'Enseignants',
            'value' => $report['enseignants'],
            'hint' => 'Intervenants rattachés',
            'watch' => false,
        ],
        [
            'icon' => 'fa-calendar-xmark',
            'label' => 'Emplois du temps manquants',
            'value' => $report['edt_manquants'],
            'hint' => 'Classes sans planning',
            'watch' => true,
        ],
        [
            'icon' => 'fa-user-clock',
            'label' => 'Étudiants non soldés',
            'value' => $report['etudiants_non_soldes'],
            'hint' => 'Effectif seul, aucun montant',
            'watch' => true,
        ],
        [
            'icon' => 'fa-pen-clip',
            'label' => 'Notes manquantes',
            'value' => $report['notes_manquantes'],
            'hint' => 'Évaluations sans saisie',
            'watch' => true,
        ],
    ];

    $aSurveiller = collect($indicateurs)->filter(fn ($i) => $i['watch'] && $i['value'] > 0);
@endphp

<div class="main-content">

    <x-role-hero
        :icon="$heroIcon"
        :title="$title"
        :subtitle="'Effectifs et pilotage pédagogique'.($report['annee'] ? ' · '.$report['annee'] : '').'. Aucun montant n\'apparaît dans ce rapport.'"
        :kpis="[
            [
                'icon' => 'fa-user-graduate',
                'value' => $report['inscrits_valides'],
                'label' => 'Inscrits valides',
            ],
            [
                'icon' => 'fa-chalkboard',
                'value' => $report['classes'],
                'label' => 'Classes',
            ],
            [
                'icon' => 'fa-user-tie',
                'value' => $report['enseignants'],
                'label' => 'Enseignants',
            ],
            [
                'icon' => 'fa-triangle-exclamation',
                'value' => $aSurveiller->count(),
                'label' => 'Points à surveiller',
                'tone' => $aSurveiller->count() > 0 ? 'alert' : null,
            ],
        ]">
        @if($previewRoute)
            <x-slot:actions>
                <x-pdf-actions
                    :preview-url="route($previewRoute)"
                    :download-url="route($pdfRoute)"
                    label="rapport"
                    button-class="rdx-btn"
                    download-class="rdx-btn rdx-btn--white" />
            </x-slot:actions>
        @endif
    </x-role-hero>

    @if($aSurveiller->isNotEmpty())
        <div class="rp-flag">
            <span class="rp-flag-icon"><i class="fas fa-triangle-exclamation"></i></span>
            <div>
                <div class="rp-flag-title">
                    {{ $aSurveiller->count() }} point{{ $aSurveiller->count() > 1 ? 's' : '' }} à traiter avant la clôture
                </div>
                <div class="rp-flag-text">
                    {{ $aSurveiller->map(fn ($i) => mb_strtolower($i['label']).' ('.$i['value'].')')->join(', ', ' et ') }}.
                </div>
            </div>
        </div>
    @endif

    <div class="rp-grid">
        @foreach($indicateurs as $ind)
            @php $alerte = $ind['watch'] && $ind['value'] > 0; @endphp
            <div class="rp-card {{ $alerte ? 'rp-card--warn' : '' }}">
                <div class="rp-card-icon"><i class="fas {{ $ind['icon'] }}"></i></div>
                <div class="rp-card-value">{{ $ind['value'] }}</div>
                <div class="rp-card-label">{{ $ind['label'] }}</div>
                <div class="rp-card-hint">{{ $ind['hint'] }}</div>
                @if($alerte)
                    <span class="rp-card-badge">À traiter</span>
                @elseif($ind['watch'])
                    <span class="rp-card-badge rp-card-badge--ok">Complet</span>
                @endif
            </div>
        @endforeach
    </div>

</div>
@endsection

@push('styles')
<style>
    /* Namespace rp-* : rapports de pilotage académique */
    .rp-flag {
        display: flex; align-items: flex-start; gap: .85rem;
        background: #fff; border: 1px solid #e2e8f0;
        border-left: 4px solid #f59e0b; border-radius: 12px;
        padding: .9rem 1.1rem; margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
    }
    .rp-flag-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: rgba(245, 158, 11, .12); color: #b45309;
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .rp-flag-title { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .rp-flag-text { font-size: .8rem; color: #64748b; margin-top: .15rem; }

    /* Six indicateurs : trois colonnes donnent deux rangs pleins, la grille
       auto-fit laissait deux cartes seules sur le second rang. */
    .rp-grid {
        display: grid; gap: 1rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    @media (max-width: 1200px) {
        .rp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .rp-card {
        position: relative;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 1.25rem 1.2rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
        transition: box-shadow .2s ease;
    }
    .rp-card:hover { box-shadow: 0 8px 24px rgba(4, 83, 203, .07); }
    .rp-card--warn { border-color: rgba(245, 158, 11, .35); background: rgba(245, 158, 11, .03); }
    .rp-card-icon {
        width: 40px; height: 40px; border-radius: 10px; margin-bottom: .7rem;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .92rem;
    }
    .rp-card--warn .rp-card-icon { background: rgba(245, 158, 11, .12); color: #b45309; }
    .rp-card-value { font-size: 2rem; font-weight: 700; color: #1e293b; line-height: 1; }
    .rp-card--warn .rp-card-value { color: #b45309; }
    .rp-card-label { font-size: .84rem; font-weight: 600; color: #1e293b; margin-top: .4rem; }
    .rp-card-hint { font-size: .74rem; color: #64748b; margin-top: .15rem; }
    .rp-card-badge {
        position: absolute; top: 1rem; right: 1rem;
        font-size: .66rem; font-weight: 700; letter-spacing: .3px;
        padding: .15rem .45rem; border-radius: 5px;
        background: rgba(245, 158, 11, .14); color: #b45309;
    }
    .rp-card-badge--ok { background: rgba(16, 185, 129, .12); color: #047857; }

    @media (max-width: 768px) {
        .rp-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush
