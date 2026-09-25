@extends('layouts.app')

@section('title', "Journal d'audit")

@push('styles')
@include('esbtp.audit._styles-journal')
@endpush

@section('content')
@php
    $_parametres = $filtres->enParametres();
    $_config = [
        'index' => route('esbtp.audit.index'),
        'aRegarder' => $aRegarder,
        'filtres' => [
            'theme' => $filtres->theme,
            'user_id' => $filtres->personne ? (string) $filtres->personne : '',
            'periode' => $filtres->periode,
            'q' => $filtres->recherche,
            'auto' => $filtres->automatiques,
            'model_type' => $filtres->typeObjet,
            'objet_id' => $filtres->idObjet,
        ],
    ];
@endphp
<div class="jda" x-data="journalAudit()" data-jda='@json($_config)'>
    <header class="jda-hero">
        <div class="jda-hero-top">
            <div class="jda-hero-gauche">
                <div class="jda-hero-icone" aria-hidden="true"><i class="fas fa-clipboard-list"></i></div>
                <div style="min-width:0">
                    <h1>Journal d'audit</h1>
                    <p>Qui a fait quoi, sur qui, et quand. Seuls les onglets que vos droits ouvrent apparaissent.</p>
                </div>
            </div>
            <div class="jda-actions">
                @can('security.users.monitor')
                    <a class="jda-btn jda-btn--glass" href="{{ route('esbtp.audit.user-activity') }}"><i class="fas fa-user-clock"></i>Activité des personnes</a>
                @endcan
                @can('security.audit.export')
                    <x-export-modal
                        :preview-url="route('esbtp.audit.export.pdf', ['apercu' => 1])"
                        :pdf-url="route('esbtp.audit.export.pdf')"
                        :excel-url="route('esbtp.audit.export.excel')"
                        button-class="jda-btn jda-btn--white"
                        label="Exporter la vue" />
                @endcan
            </div>
        </div>
        @if(count($themes) > 1)
            <nav class="jda-onglets" aria-label="Vues du journal">
                @foreach($themes as $_theme)
                    <a href="{{ route('esbtp.audit.index', array_merge($_parametres, ['theme' => $_theme])) }}"
                       class="jda-onglet" :class="filtres.theme === '{{ $_theme }}' ? 'is-actif' : ''"
                       :aria-current="filtres.theme === '{{ $_theme }}' ? 'page' : null"
                       data-jda-filtre='@json(['theme' => $_theme])'>{{ \App\Domain\Audit\ThemesDuJournal::LIBELLES[$_theme] }}@if($_theme === \App\Domain\Audit\ThemesDuJournal::A_REGARDER)<span class="jda-onglet-compte" x-show="aRegarder > 0" x-text="aRegarder">{{ $aRegarder }}</span>@endif</a>
                @endforeach
            </nav>
        @else
            <div style="height:1.25rem"></div>
        @endif
    </header>

    <div class="jda-filtres">
        <label class="jda-recherche">
            <i class="fas fa-search" style="color:#64748b" aria-hidden="true"></i>
            <input type="search" x-ref="recherche" x-model="filtres.q" x-on:input.debounce.350ms="recharger()" placeholder="Un étudiant, une classe, un reçu, une personne…" aria-label="Rechercher dans le journal">
            <kbd title="Raccourci">/</kbd>
        </label>
        <div class="jda-filtre" x-on:change="choisir($event)">
            <x-au-user-picker name="user_id" :value="$filtres->personne" :users="$personnes" placeholder="Toutes les personnes" />
        </div>
        <div class="jda-filtre" x-on:change="choisir($event)">
            <x-au-select name="periode" :value="$filtres->periode" icon="fa-calendar" :options="\App\Domain\Audit\FiltresDuJournal::PERIODES" :placeholder-is-first-option="false" />
        </div>
        <button type="button" class="jda-bascule" :class="!filtres.auto ? 'is-actif' : ''" :aria-pressed="(!filtres.auto).toString()" x-on:click="filtrer({auto: !filtres.auto})">
            <i class="fas fa-gear" aria-hidden="true"></i>Masquer les tâches automatiques
        </button>
    </div>

    <section class="jda-carte">
        <div class="jda-liste" id="jda-liste" :class="chargement ? 'is-chargement' : ''" aria-live="polite">
            @include('esbtp.audit._liste')
        </div>
    </section>
</div>

@include('esbtp.audit._script')
@endsection
