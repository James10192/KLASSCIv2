@extends('layouts.app')

@section('title', 'Situation financière des classes')

@push('styles')
    @include('esbtp.comptabilite.suivi-classes.partials._styles')
@endpush

@section('content')
@php
    $sfcListe = fn ($items, string $vide = '') => ($vide !== '' ? ['' => $vide] : [])
        + $items->mapWithKeys(fn ($item) => [(string) $item->id => (string) $item->name])->all();
    $sfOptions = [
        'annee' => $sfcListe($annees),
        'filiere' => $sfcListe($filieres, 'Toutes'),
        'niveau' => $sfcListe($niveaux, 'Tous'),
        'classe' => $sfcListe($classesOptions, 'Toutes'),
        'categorie' => $sfcListe($categories, 'Toutes'),
        'systeme' => ['' => 'BTS et LMD'] + $systemes->mapWithKeys(fn ($s) => [(string) $s => (string) $s])->all(),
        'tri' => \App\Domain\Comptabilite\SuiviClasses\SituationClassesService::TRIS,
    ];
    $sfConfig = [
        'filters' => [
            'annee' => (string) ($filtres['annee'] ?? ''),
            'filiere' => (string) ($filtres['filiere'] ?? ''),
            'niveau' => (string) ($filtres['niveau'] ?? ''),
            'classe' => (string) ($filtres['classe'] ?? ''),
            'categorie' => (string) ($filtres['categorie'] ?? ''),
            'systeme' => (string) ($filtres['systeme'] ?? ''),
            'search' => (string) ($filtres['search'] ?? ''),
            'tri' => (string) ($filtres['tri'] ?? \App\Domain\Comptabilite\SuiviClasses\SituationClassesService::TRI_DEFAUT),
        ],
        'libelles' => $sfOptions,
        'totaux' => $rapport['totaux'],
        'dataUrl' => route('esbtp.comptabilite.suivi-classes.data'),
        'detailsPrefix' => route('esbtp.comptabilite.suivi-classes.index'),
    ];
@endphp
<div class="sfc-page" x-data="suiviClasses({{ \Illuminate\Support\Js::from($sfConfig) }})">
    @include('esbtp.comptabilite.suivi-classes.partials._hero')

    <div class="sfc-error" role="alert" x-show="message" x-cloak>
        <i class="fas fa-circle-exclamation"></i><span x-text="message"></span>
        <button type="button" @click="refresh(page)">Réessayer</button>
    </div>

    @include('esbtp.comptabilite.suivi-classes.partials._filtres')

    <div x-ref="results" @click="handlePagination($event)" style="display:flex;flex-direction:column;gap:1.1rem">
        @include('esbtp.comptabilite.suivi-classes.partials.resultats', ['rapport' => $rapport])
    </div>

    <p class="sfc-note">
        <i class="fas fa-circle-info" aria-hidden="true"></i>
        <span>« Réglé » compte les versements imputés aux frais, avoirs déduits. Le reste vient des échéanciers.
            <span x-show="categorieFiltre" x-cloak>Avec une catégorie de frais choisie, la caisse n’est pas répartie par catégorie : sa colonne disparaît.</span>
            <span x-show="!categorieFiltre">L’écart entre caisse et imputations est détaillé dans l’export Excel.</span>
        </span>
    </p>

    @can('comptabilite.suivi_classes.view_details')
        <div x-show="detailOpen" x-cloak class="sfc-backdrop" @keydown.escape.window="fermerDetail()" @click.self="fermerDetail()">
            <div role="dialog" aria-modal="true" aria-labelledby="sfc-detail-title" class="sfc-dialog" x-trap.noscroll="detailOpen">
                <div class="sfc-dialog-head">
                    <h2 id="sfc-detail-title" x-text="detailTitre"></h2>
                    <button type="button" class="sfc-dialog-close" @click="fermerDetail()" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </div>
                <div class="sfc-dialog-body">
                    <div x-show="detailLoading" role="status" aria-label="Chargement">
                        <div class="sfc-skeleton" style="width:60%"></div><div class="sfc-skeleton"></div><div class="sfc-skeleton"></div><div class="sfc-skeleton" style="width:80%"></div>
                    </div>
                    <div x-ref="detailBody"></div>
                </div>
            </div>
        </div>
    @endcan
    <div class="sfc-sr-only" aria-live="polite" x-text="annonce"></div>
</div>
@endsection

@push('scripts')
    @include('esbtp.comptabilite.suivi-classes.partials._script')
@endpush
