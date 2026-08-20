@extends('layouts.app')

@section('title', "Tableau de bord Agent d'inscription - KLASSCI")

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@php
    $etapes = $funnel['etapes'] ?? [];
    $totalDossiers = $funnel['total'] ?? 0;
    $bloques = $funnel['bloquees'] ?? 0;

    $urlParStatut = [
        'en_attente' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
        'sous_reserve' => route('esbtp.inscriptions.sous-reserve'),
        'active' => route('esbtp.inscriptions.index', ['status' => 'active']),
    ];

    // Un dossier finalisé ne stagne pas : le garder dans le graphique
    // ecraserait les trois etapes ou un dossier peut rester coince, qui sont
    // precisement celles sur lesquelles ce role agit. Il est repris en
    // chiffre de reference sous le graphique.
    $finalisees = 0;
    $enCours = [];
    foreach ($etapes as $etape) {
        if ($etape['statut'] === 'active') {
            $finalisees = $etape['value'];
            continue;
        }
        $enCours[] = $etape;
    }
    $totalEnCours = array_sum(array_column($enCours, 'value'));

    // « À valider » est la seule etape ou l'agent peut agir seul : elle est
    // bleue. Les deux autres attendent un tiers, elles sont orange.
    $tons = array_map(
        fn ($e) => $e['label'] === 'À valider' ? '#0453cb' : 'alerte',
        $enCours
    );

    $chartData = [
        'labels' => array_column($enCours, 'label'),
        'datasets' => [[
            'label' => 'Dossiers',
            'data' => array_column($enCours, 'value'),
            'tones' => $tons,
        ]],
        'links' => array_map(fn ($e) => $urlParStatut[$e['statut']] ?? '#', $enCours),
    ];
@endphp

<div class="main-content">

    <x-role-hero
        icon="fa-user-plus"
        title="Agent d'inscription"
        subtitle="File des dossiers à créer, éditer et valider après encaissement. Aucun montant n'est affiché."
        :kpis="[
            [
                'icon' => 'fa-clipboard-check',
                'value' => $pendingValidation,
                'label' => 'Dossiers à valider',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
                'tone' => $pendingValidation > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-hourglass-half',
                'value' => $waitingPayment,
                'label' => 'En attente de paiement',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
            ],
            [
                'icon' => 'fa-circle-check',
                'value' => $validatedToday,
                'label' => 'Validés ce jour',
            ],
            [
                'icon' => 'fa-file-shield',
                'value' => $sousReserve,
                'label' => 'Dossiers sous réserve',
                'href' => route('esbtp.inscriptions.sous-reserve'),
                'tone' => $sousReserve > 0 ? 'alert' : null,
            ],
        ]">
        <x-slot:actions>
            @can('inscriptions.create')
                <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.inscriptions.create') }}">
                    <i class="fas fa-plus"></i>Nouvelle inscription
                </a>
            @endcan
        </x-slot:actions>
    </x-role-hero>

    <x-role-dashboard>

        @if($bloques > 0)
            <x-slot:alerts>
                <a class="dsh-alert dsh-alert--warning" href="{{ route('esbtp.inscriptions.index', ['status' => 'en_attente']) }}">
                    <span class="dsh-alert-icon"><i class="fas fa-hourglass-half"></i></span>
                    <span class="dsh-alert-body">
                        <span class="dsh-alert-title">{{ $bloques }} dossier{{ $bloques > 1 ? 's' : '' }} ne peu{{ $bloques > 1 ? 'vent' : 't' }} pas avancer</span>
                        <span class="dsh-alert-text">En attente d'encaissement ou sous réserve : la validation est bloquée tant que la caisse n'a pas encaissé.</span>
                    </span>
                    <i class="fas fa-chevron-right dsh-alert-go"></i>
                </a>
            </x-slot:alerts>
        @endif

        <x-slot:focal>
            <x-role-panel
                icon="fa-filter-circle-dollar"
                title="Où stagnent les dossiers"
                subtitle="Cliquez une barre pour ouvrir la liste correspondante">
                <x-role-chart
                    id="ai-funnel"
                    type="bar"
                    :data="$chartData"
                    :is-empty="$totalEnCours === 0"
                    empty-icon="fa-folder-open"
                    empty-title="Aucun dossier en cours"
                    empty-hint="Tous les dossiers de l'année sont finalisés. Une nouvelle inscription réapparaîtra ici."
                    :height="270" />

                @if($totalEnCours > 0)
                    <div class="dsh-legend">
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#f59e0b"></span>Bloqué, en attente d'un tiers
                        </span>
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#0453cb"></span>Action possible de votre part
                        </span>
                    </div>

                    <div class="dsh-figures">
                        <div class="dsh-figure">
                            <span class="dsh-figure-value">{{ $totalEnCours }}</span>
                            <span class="dsh-figure-label">Dossiers en cours</span>
                        </div>
                        <div class="dsh-figure {{ $bloques > 0 ? 'dsh-figure--warn' : 'dsh-figure--ok' }}">
                            <span class="dsh-figure-value">{{ $bloques }}</span>
                            <span class="dsh-figure-label">Attendent un tiers</span>
                        </div>
                        <div class="dsh-figure dsh-figure--ok">
                            <span class="dsh-figure-value">{{ $finalisees }}</span>
                            <span class="dsh-figure-label">Finalisés cette année</span>
                        </div>
                    </div>
                @endif
            </x-role-panel>
        </x-slot:focal>

        <x-slot:rail>
            <x-role-panel
                icon="fa-bolt"
                title="Actions courantes">
                <div class="ai-actions">
                    @can('inscriptions.create')
                        <a class="ai-action" href="{{ route('esbtp.inscriptions.create') }}">
                            <span class="ai-action-icon"><i class="fas fa-user-plus"></i></span>
                            <span class="ai-action-body">
                                <span class="ai-action-title">Nouvelle inscription</span>
                                <span class="ai-action-hint">Créer un dossier étudiant</span>
                            </span>
                        </a>
                    @endcan
                    <a class="ai-action" href="{{ route('esbtp.inscriptions.index') }}">
                        <span class="ai-action-icon"><i class="fas fa-folder-open"></i></span>
                        <span class="ai-action-body">
                            <span class="ai-action-title">Liste des inscriptions</span>
                            <span class="ai-action-hint">Rechercher et éditer</span>
                        </span>
                    </a>
                    <a class="ai-action" href="{{ route('esbtp.etudiants.index') }}">
                        <span class="ai-action-icon"><i class="fas fa-user-graduate"></i></span>
                        <span class="ai-action-body">
                            <span class="ai-action-title">Liste des étudiants</span>
                            <span class="ai-action-hint">Consulter les fiches</span>
                        </span>
                    </a>
                    <a class="ai-action" href="{{ route('esbtp.reinscription.index') }}">
                        <span class="ai-action-icon"><i class="fas fa-rotate"></i></span>
                        <span class="ai-action-body">
                            <span class="ai-action-title">Réinscriptions</span>
                            <span class="ai-action-hint">Renouveler une année</span>
                        </span>
                    </a>
                </div>
            </x-role-panel>

            <x-role-panel
                icon="fa-circle-info"
                title="Votre périmètre">
                <ul class="ai-scope">
                    <li class="ai-scope-item ai-scope-item--yes">
                        <i class="fas fa-check"></i><span>Créer, éditer et valider les dossiers</span>
                    </li>
                    <li class="ai-scope-item ai-scope-item--yes">
                        <i class="fas fa-check"></i><span>Consulter étudiants, classes et filières</span>
                    </li>
                    <li class="ai-scope-item ai-scope-item--no">
                        <i class="fas fa-xmark"></i><span>Aucun montant ni solde affiché</span>
                    </li>
                    <li class="ai-scope-item ai-scope-item--no">
                        <i class="fas fa-xmark"></i><span>Pas de notes, personnel ni paramètres</span>
                    </li>
                </ul>
            </x-role-panel>
        </x-slot:rail>

    </x-role-dashboard>
</div>
@endsection

@push('styles')
<style>
    /* Namespace ai-* : tableau de bord agent d'inscription */
    .ai-actions { display: flex; flex-direction: column; gap: .5rem; }
    .ai-action {
        display: flex; align-items: center; gap: .7rem;
        padding: .65rem .75rem;
        border: 1px solid #e2e8f0; border-radius: 10px;
        text-decoration: none; background: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .ai-action:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4, 83, 203, .06); }
    .ai-action-icon {
        width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .8rem;
    }
    .ai-action-body { display: flex; flex-direction: column; min-width: 0; }
    .ai-action-title { font-size: .84rem; font-weight: 600; color: #1e293b; }
    .ai-action-hint { font-size: .73rem; color: #64748b; margin-top: .05rem; }

    .ai-scope { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; }
    .ai-scope-item { display: flex; align-items: flex-start; gap: .55rem; font-size: .81rem; color: #1e293b; }
    .ai-scope-item i {
        width: 18px; height: 18px; border-radius: 5px; flex-shrink: 0; margin-top: .12rem;
        display: flex; align-items: center; justify-content: center; font-size: .6rem;
    }
    .ai-scope-item--yes i { background: rgba(16, 185, 129, .12); color: #10b981; }
    .ai-scope-item--no i { background: rgba(100, 116, 139, .12); color: #64748b; }
    .ai-scope-item--no span { color: #64748b; }
</style>
@endpush
