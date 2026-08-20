@extends('layouts.app')

@section('title', 'Tableau de bord Service scolarité - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@php
    $segments = $workload['segments'] ?? [];
    $totalCharge = $workload['total'] ?? 0;

    // L'impression est bloquante pour l'etudiant qui attend son document,
    // la saisie l'est moins : la teinte suit cette hierarchie.
    $tons = array_map(
        fn ($s) => ($s['kind'] ?? null) === 'impression' ? 'alerte' : '#0453cb',
        $segments
    );

    $chartData = [
        'labels' => array_column($segments, 'label'),
        'datasets' => [[
            'label' => 'À traiter',
            'data' => array_column($segments, 'value'),
            'tones' => $tons,
        ]],
    ];
@endphp

<div class="main-content">

    <x-role-hero
        icon="fa-print"
        title="Service scolarité"
        subtitle="Documents approuvés à imprimer et saisie des notes limitée aux fenêtres ouvertes."
        :kpis="[
            [
                'icon' => 'fa-file-circle-check',
                'value' => $approvedDocuments->count(),
                'label' => 'Documents à imprimer',
                'tone' => $approvedDocuments->count() > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-door-open',
                'value' => $openWindows->count(),
                'label' => 'Classes ouvertes à la saisie',
            ],
            [
                'icon' => 'fa-list-check',
                'value' => $totalCharge,
                'label' => 'Total à traiter',
            ],
        ]">
        <x-slot:actions>
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.etudiants.index') }}">
                <i class="fas fa-user-graduate"></i>Liste des étudiants
            </a>
        </x-slot:actions>
    </x-role-hero>

    <x-role-dashboard>

        @if($approvedDocuments->count() > 0)
            <x-slot:alerts>
                <div class="dsh-alert dsh-alert--warning">
                    <span class="dsh-alert-icon"><i class="fas fa-print"></i></span>
                    <span class="dsh-alert-body">
                        <span class="dsh-alert-title">{{ $approvedDocuments->count() }} document{{ $approvedDocuments->count() > 1 ? 's' : '' }} approuvé{{ $approvedDocuments->count() > 1 ? 's' : '' }} en attente d'impression</span>
                        <span class="dsh-alert-text">Un étudiant attend chacun de ces documents.</span>
                    </span>
                </div>
            </x-slot:alerts>
        @endif

        <x-slot:focal>
            <x-role-panel
                icon="fa-chart-simple"
                title="Ce qu'il vous reste à traiter"
                subtitle="Répartition par nature de tâche">
                <x-role-chart
                    id="ss-workload"
                    type="bar"
                    :data="$chartData"
                    :is-empty="$totalCharge === 0"
                    empty-icon="fa-circle-check"
                    empty-title="Rien en attente"
                    empty-hint="Les documents approuvés et les classes ouvertes à la saisie apparaîtront ici."
                    :height="240" />

                @if($totalCharge > 0)
                    <div class="dsh-legend">
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#f59e0b"></span>Impression, un étudiant attend
                        </span>
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#0453cb"></span>Saisie, dans la fenêtre ouverte
                        </span>
                    </div>
                @endif
            </x-role-panel>

            <x-role-panel
                icon="fa-print"
                title="File d'impression"
                subtitle="Uniquement les documents déjà approuvés par le responsable"
                :count="$approvedDocuments->count()">
                @forelse($approvedDocuments as $document)
                    @php
                        $printUrl = match ($document->document_type) {
                            'certificat' => route('esbtp.etudiants.certificat', $document->etudiant_id),
                            'attestation' => route('esbtp.etudiants.attestation-frequentation', $document->etudiant_id),
                            'bulletin' => $document->document_id ? route('esbtp.bulletins.download', $document->document_id) : null,
                            default => null,
                        };
                    @endphp
                    <div class="rdx-row">
                        <div class="rdx-row-icon"><i class="fas fa-file-signature"></i></div>
                        <div class="rdx-row-main">
                            <div class="rdx-row-title">{{ ucfirst($document->document_type) }}</div>
                            <div class="rdx-row-meta">
                                Étudiant&nbsp;#{{ $document->etudiant_id }}
                                @if($document->updated_at)
                                    · approuvé le {{ $document->updated_at->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="rdx-row-actions">
                            @if($printUrl)
                                <a class="rdx-act rdx-act--primary" href="{{ $printUrl }}">
                                    <i class="fas fa-print"></i>Imprimer
                                </a>
                            @else
                                <span class="ss-unavailable" title="Document introuvable">
                                    <i class="fas fa-triangle-exclamation"></i>Indisponible
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-role-empty
                        icon="fa-inbox"
                        title="Aucun document approuvé"
                        hint="Les documents validés par le responsable scolarité arriveront dans cette file." />
                @endforelse
            </x-role-panel>
        </x-slot:focal>

        <x-slot:rail>
            <x-role-panel
                icon="fa-pen-to-square"
                title="Saisie autorisée"
                subtitle="Une classe disparaît dès que sa fenêtre se referme"
                :count="$openWindows->count()">
                @forelse($openWindows as $window)
                    @php $joursRestants = (int) now()->startOfDay()->diffInDays($window->ends_at, false); @endphp
                    <div class="rdx-row">
                        <div class="rdx-row-icon"><i class="fas fa-chalkboard"></i></div>
                        <div class="rdx-row-main">
                            <div class="rdx-row-title">{{ $window->classe->name ?? 'Classe #'.$window->classe_id }}</div>
                            <div class="rdx-row-meta">
                                @if($joursRestants >= 0)
                                    <span class="ss-remaining {{ $joursRestants <= 2 ? 'ss-remaining--soon' : '' }}">
                                        {{ $joursRestants === 0 ? 'dernier jour' : $joursRestants.' jour'.($joursRestants > 1 ? 's' : '') }}
                                    </span>
                                @else
                                    fenêtre expirée
                                @endif
                            </div>
                        </div>
                        <div class="rdx-row-actions">
                            <a class="rdx-act rdx-act--primary" href="{{ route('esbtp.notes.index', ['classe_id' => $window->classe_id]) }}">
                                <i class="fas fa-pen"></i>Saisir
                            </a>
                        </div>
                    </div>
                @empty
                    <x-role-empty
                        icon="fa-lock"
                        title="Saisie fermée"
                        hint="Demandez au responsable scolarité d'ouvrir une fenêtre." />
                @endforelse
            </x-role-panel>
        </x-slot:rail>

    </x-role-dashboard>
</div>
@endsection

@push('styles')
<style>
    /* Namespace ss-* : tableau de bord service scolarité */
    .ss-unavailable {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .35rem .7rem; border-radius: 8px;
        font-size: .76rem; font-weight: 600;
        background: rgba(245, 158, 11, .1); color: #b45309;
        border: 1px solid rgba(245, 158, 11, .25);
    }
    .ss-remaining { font-weight: 600; color: #0453cb; }
    .ss-remaining--soon { color: #b45309; }
</style>
@endpush
