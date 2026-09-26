@extends('layouts.app')

@section('title', "Aujourd'hui à l'accueil - KLASSCI")

@push('styles')
@include('esbtp.admissions.aujourdhui._styles')
@endpush

@section('content')
@php
    $_config = ['index' => route('esbtp.admissions.aujourdhui')];
@endphp
<div class="adj" x-data="aujourdhuiAccueil()" data-adj-config='@json($_config)'>
    <header class="adj-hero">
        <div class="adj-hero-top">
            <div class="adj-hero-left">
                <div class="adj-hero-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
                <div>
                    <span class="adj-hero-sur">{{ ucfirst(now()->translatedFormat('l j F')) }} · Accueil</span>
                    <h1>Aujourd'hui</h1>
                    <p>Les familles attendues au guichet, créneau par créneau. Cochez-les reçues à leur arrivée, puis finalisez leur inscription.</p>
                </div>
            </div>
            <div class="adj-hero-actions">
                @canany(['inscriptions.candidatures.view', 'reinscriptions.demandes.view'])
                    <a class="adj-btn adj-btn--glass" href="{{ route('esbtp.demandes.index') }}"><i class="fas fa-folder-open"></i>Dossiers</a>
                @endcanany
                <a class="adj-btn adj-btn--glass" href="{{ route('esbtp.rendez-vous.recherche') }}"><i class="fas fa-magnifying-glass"></i>Retrouver un rendez-vous</a>
                <a class="adj-btn adj-btn--white" href="{{ route('esbtp.rendez-vous.accueil.index') }}" title="Reprogrammer les absences, noter les appels, voir un autre jour"><i class="fas fa-clipboard-check"></i>Accueil détaillé</a>
            </div>
        </div>
        <div class="adj-kpis" id="adj-kpis" aria-live="polite">
            @include('esbtp.admissions.aujourdhui._compteurs')
        </div>
    </header>

    <div class="adj-corps">
        <section class="adj-liste" aria-label="Créneaux du jour">
            <div class="adj-barre">
                <label class="adj-recherche">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" x-model="q" x-on:input.debounce.150ms="filtrer()" placeholder="Retrouver une famille : nom ou téléphone" aria-label="Retrouver une famille attendue aujourd'hui">
                </label>
                <span class="adj-maj" x-show="majA" x-cloak><i class="fas fa-rotate" :class="chargement ? 'fa-spin' : ''" aria-hidden="true"></i>Mis à jour à <span x-text="majA"></span></span>
            </div>
            <p class="adj-sans-resultat" x-show="q && aucunResultat" x-cloak>
                Aucune famille attendue aujourd'hui ne correspond. Elle a peut-être rendez-vous un autre jour :
                <a href="{{ route('esbtp.rendez-vous.recherche') }}" x-bind:href="'{{ route('esbtp.rendez-vous.recherche') }}?q=' + encodeURIComponent(q)">chercher dans tous les rendez-vous</a>.
            </p>
            <div id="adj-creneaux" :class="chargement ? 'is-chargement' : ''">
                @include('esbtp.admissions.aujourdhui._creneaux')
            </div>
        </section>

        <aside class="adj-guichet" id="adj-guichet" aria-label="Familles au guichet">
            @include('esbtp.admissions.aujourdhui._guichet')
        </aside>
    </div>
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
@include('esbtp.admissions.aujourdhui._script')
@endpush
