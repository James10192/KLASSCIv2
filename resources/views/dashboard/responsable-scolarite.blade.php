@extends('layouts.app')

@section('title', 'Tableau de bord Responsable scolarité - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@php
    $progress = $noteProgress['rows'] ?? [];
    $completes = $noteProgress['completes'] ?? 0;
    $nbFenetres = $noteProgress['total'] ?? 0;

    // Vert quand la classe est complete : elle peut etre fermee. Orange sinon,
    // c'est exactement la decision que ce role doit prendre.
    $tons = array_map(fn ($r) => $r['complete'] ? '#0453cb' : 'alerte', $progress);

    $chartData = [
        'labels' => array_map(fn ($r) => $r['name'], $progress),
        'datasets' => [[
            'label' => 'Saisie',
            'data' => array_map(fn ($r) => $r['pourcentage'], $progress),
            'tones' => $tons,
        ]],
        'links' => array_map(
            fn ($r) => route('esbtp.notes.index', ['classe_id' => $r['classe_id']]),
            $progress
        ),
    ];
@endphp

<div class="main-content">

    <x-role-hero
        icon="fa-user-check"
        title="Responsable scolarité"
        subtitle="Approbations de documents, fenêtres de saisie des notes et inscriptions à valider. Aucun montant n'est affiché."
        :kpis="[
            [
                'icon' => 'fa-stamp',
                'value' => $pendingApprovals->count(),
                'label' => 'Documents à approuver',
                'tone' => $pendingApprovals->count() > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-door-open',
                'value' => $nbFenetres,
                'label' => 'Fenêtres ouvertes',
            ],
            [
                'icon' => 'fa-circle-check',
                'value' => $completes.' / '.max($nbFenetres, 1),
                'label' => 'Classes prêtes à fermer',
            ],
            [
                'icon' => 'fa-clipboard-list',
                'value' => $pendingInscriptions,
                'label' => 'Inscriptions à valider',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
                'tone' => $pendingInscriptions > 0 ? 'alert' : null,
            ],
        ]">
        <x-slot:actions>
            <a class="rdx-btn" href="{{ route('esbtp.bulletins.index') }}">
                <i class="fas fa-file-lines"></i>Bulletins
            </a>
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.inscriptions.index') }}">
                <i class="fas fa-folder-open"></i>Inscriptions
            </a>
        </x-slot:actions>
    </x-role-hero>

    <x-role-dashboard>

        @if($completes > 0)
            <x-slot:alerts>
                <div class="dsh-alert">
                    <span class="dsh-alert-icon"><i class="fas fa-lock"></i></span>
                    <span class="dsh-alert-body">
                        <span class="dsh-alert-title">{{ $completes }} classe{{ $completes > 1 ? 's' : '' }} peu{{ $completes > 1 ? 'vent' : 't' }} être fermée{{ $completes > 1 ? 's' : '' }}</span>
                        <span class="dsh-alert-text">Toutes les évaluations attendues y sont saisies. Fermer la fenêtre verrouille la saisie.</span>
                    </span>
                </div>
            </x-slot:alerts>
        @endif

        <x-slot:focal>
            <x-role-panel
                icon="fa-chart-simple"
                title="Puis-je fermer cette fenêtre ?"
                subtitle="Part des évaluations saisies, par classe ouverte. Cliquez une barre pour ouvrir la classe.">
                <x-role-chart
                    id="rs-progress"
                    type="bar"
                    :data="$chartData"
                    :options="['scales' => ['y' => ['max' => 100]]]"
                    :is-empty="$nbFenetres === 0"
                    empty-icon="fa-door-closed"
                    empty-title="Aucune fenêtre ouverte"
                    empty-hint="Ouvrez une fenêtre ci-contre pour autoriser la saisie des notes d'une classe."
                    :height="250" />

                @if($nbFenetres > 0)
                    <div class="dsh-legend">
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#0453cb"></span>Complète, fermeture possible
                        </span>
                        <span class="dsh-legend-item">
                            <span class="dsh-legend-dot" style="background:#f59e0b"></span>Saisie en cours
                        </span>
                    </div>

                    <div class="rs-lignes">
                        @foreach($progress as $row)
                            <div class="rs-ligne">
                                <span class="rs-ligne-nom">{{ $row['name'] }}</span>
                                <span class="rs-ligne-barre">
                                    <span class="rs-ligne-remplissage {{ $row['complete'] ? 'is-complete' : '' }}"
                                          style="width: {{ $row['pourcentage'] }}%;"></span>
                                </span>
                                <span class="rs-ligne-chiffre">{{ $row['faites'] }}&nbsp;/&nbsp;{{ $row['attendues'] }}</span>
                                <span class="rs-ligne-reste {{ $row['jours_restants'] <= 2 ? 'is-soon' : '' }}">
                                    {{ $row['jours_restants'] < 0 ? 'expirée' : ($row['jours_restants'] === 0 ? 'dernier jour' : 'J-'.$row['jours_restants']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-role-panel>
        </x-slot:focal>

        <x-slot:rail>
            <x-role-panel
                icon="fa-stamp"
                title="File d'approbation"
                :count="$pendingApprovals->count()">
                @forelse($pendingApprovals as $approval)
                    <div class="rdx-row">
                        <div class="rdx-row-icon"><i class="fas fa-file-signature"></i></div>
                        <div class="rdx-row-main">
                            <div class="rdx-row-title">{{ ucfirst($approval->document_type) }}</div>
                            <div class="rdx-row-meta">Étudiant&nbsp;#{{ $approval->etudiant_id }}</div>
                        </div>
                    </div>
                    @can('documents.approve')
                        <div class="rs-approbation">
                            <form method="POST" action="{{ route('esbtp.documents.approvals.approve', $approval) }}">
                                @csrf
                                <button class="rdx-act rdx-act--primary" type="submit">
                                    <i class="fas fa-check"></i>Approuver
                                </button>
                            </form>
                            <form method="POST" action="{{ route('esbtp.documents.approvals.reject', $approval) }}">
                                @csrf
                                <button class="rdx-act rdx-act--danger" type="submit">
                                    <i class="fas fa-xmark"></i>Refuser
                                </button>
                            </form>
                        </div>
                    @endcan
                @empty
                    <x-role-empty
                        icon="fa-circle-check"
                        title="Aucun document en attente"
                        hint="Les demandes de certificat, d'attestation ou de bulletin apparaîtront ici." />
                @endforelse
            </x-role-panel>

            @can('notes.window.manage')
                <x-role-panel
                    icon="fa-calendar-plus"
                    title="Ouvrir une fenêtre"
                    subtitle="Autorise le service scolarité à saisir">
                    <form method="POST" action="{{ route('esbtp.notes-windows.store') }}" class="rs-window-form">
                        @csrf
                        <div class="rs-field">
                            <label for="classe_id">Classe</label>
                            <x-au-select
                                class="rs-au-full"
                                name="classe_id"
                                icon="fa-chalkboard"
                                placeholder="Choisir une classe"
                                :searchable="true"
                                :options="$classes->pluck('name', 'id')->toArray()" />
                        </div>
                        <div class="rs-form-dates">
                            <div class="rs-field">
                                <label for="starts_at">Du</label>
                                <input class="form-control" type="date" id="starts_at" name="starts_at" required>
                            </div>
                            <div class="rs-field">
                                <label for="ends_at">Au</label>
                                <input class="form-control" type="date" id="ends_at" name="ends_at" required>
                            </div>
                        </div>
                        <button class="rdx-act rdx-act--primary rs-submit" type="submit">
                            <i class="fas fa-door-open"></i>Ouvrir la fenêtre
                        </button>
                    </form>

                    @if($openWindows->isNotEmpty())
                        <div class="rs-fenetres">
                            @foreach($openWindows as $window)
                                <div class="rdx-row">
                                    <div class="rdx-row-main">
                                        <div class="rdx-row-title">{{ $window->classe->name ?? 'Classe #'.$window->classe_id }}</div>
                                        <div class="rdx-row-meta">jusqu'au {{ $window->ends_at->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="rdx-row-actions">
                                        <form method="POST" action="{{ route('esbtp.notes-windows.close', $window) }}">
                                            @csrf
                                            <button class="rdx-act rdx-act--ghost" type="submit">
                                                <i class="fas fa-lock"></i>Fermer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-role-panel>
            @endcan
        </x-slot:rail>

    </x-role-dashboard>
</div>
@endsection

@push('styles')
<style>
    /* Namespace rs-* : tableau de bord responsable scolarité */
    .rs-lignes { display: flex; flex-direction: column; gap: .5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
    .rs-ligne { display: grid; grid-template-columns: minmax(0, 1fr) 2fr auto auto; align-items: center; gap: .75rem; }
    .rs-ligne-nom { font-size: .82rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rs-ligne-barre { height: 8px; background: #eef2f7; border-radius: 999px; overflow: hidden; }
    .rs-ligne-remplissage { display: block; height: 100%; background: #f59e0b; border-radius: 999px; }
    .rs-ligne-remplissage.is-complete { background: linear-gradient(90deg, #0453cb, #3b7ddb); }
    .rs-ligne-chiffre { font-size: .78rem; font-weight: 700; color: #1e293b; white-space: nowrap; }
    .rs-ligne-reste { font-size: .72rem; color: #64748b; white-space: nowrap; }
    .rs-ligne-reste.is-soon { color: #b45309; font-weight: 600; }

    .rs-approbation { display: flex; gap: .4rem; margin: .4rem 0 .8rem; }

    .rs-window-form {
        background: rgba(4, 83, 203, .04);
        border: 1px solid rgba(4, 83, 203, .14);
        border-radius: 10px;
        padding: .85rem .9rem .9rem;
    }
    .rs-form-dates { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: .6rem; }
    .rs-field { display: flex; flex-direction: column; gap: .25rem; }
    .rs-field label {
        font-size: .7rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .3px;
    }
    /* Le picker premium est en inline-flex : sans cela il se réduit à son
       contenu dans une cellule qui n'est pas un conteneur flex. */
    .rs-au-full { display: flex !important; width: 100%; }
    .rs-au-full .au-select-trigger { width: 100%; }
    .rs-field .form-control {
        border: 1px solid #e2e8f0; border-radius: 8px;
        padding: .45rem .6rem; font-size: .82rem;
    }
    .rs-field .form-control:focus {
        border-color: #0453cb; outline: none;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .1);
    }
    .rs-submit { margin-top: .75rem; width: 100%; justify-content: center; }
    .rs-fenetres { margin-top: .9rem; display: flex; flex-direction: column; gap: .5rem; }

    @media (max-width: 768px) {
        .rs-ligne { grid-template-columns: 1fr auto; }
        .rs-ligne-barre { grid-column: 1 / -1; }
    }
</style>
@endpush
