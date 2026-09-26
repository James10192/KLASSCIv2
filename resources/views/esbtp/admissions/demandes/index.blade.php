@extends('layouts.app')

@section('title', "Dossiers d'admission - KLASSCI")

@push('styles')
@include('esbtp.admissions.demandes._styles')
@endpush

@section('content')
@php
    $_config = [
        'index' => route('esbtp.demandes.index'),
        'store' => route('esbtp.inscriptions.store'),
        'frais' => route('esbtp.inscriptions.frais-by-classe', ['classeId' => '__ID__']),
        'etudiant' => auth()->user()->can('students.view') ? route('esbtp.etudiants.show', ['etudiant' => '__ID__']) : null,
        'creneaux' => auth()->user()->can('inscriptions.rdv.manage') ? route('esbtp.demandes.creneaux') : null,
        'filtres' => $filtres,
        'compteurs' => $compteurs,
        'ouvrir' => $ouvrir,
        'agir' => $agir,
        // Les deux listes fermees des fenetres, lues a leur source.
        'liensTuteur' => array_keys(\App\Models\ESBTPCandidature::liensTuteurDeclarables()),
        'decisions' => \App\Models\ESBTPReinscriptionDemande::DECISIONS,
    ];
@endphp
<div class="dmi" x-data="demandesInscription()" data-dmi-config='@json($_config)' data-dmi-classes='@json($classes)'>
    <header class="dmi-hero">
        <div class="dmi-hero-top">
            <div class="dmi-hero-left">
                <div class="dmi-hero-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div>
                <div>
                    <span class="dmi-hero-sur">Admissions{{ $campagne ? ' · Année '.$campagne : '' }}</span>
                    <h1>Dossiers</h1>
                    <p>Nouvelles inscriptions et réinscriptions, du dépôt en ligne à l'inscription, avec leur rendez-vous et leur convocation.</p>
                </div>
            </div>
            <div class="dmi-hero-actions">
                @can('inscriptions.rdv.accueil')
                    <a class="dmi-btn dmi-btn--glass" href="{{ route('esbtp.admissions.aujourdhui') }}"><i class="fas fa-clock"></i>Aujourd'hui<span x-text="' · ' + compteurs.attendues_aujourdhui" x-show="compteurs.attendues_aujourdhui > 0"></span></a>
                @endcan
                @can('inscriptions.rdv.view')
                    <a class="dmi-btn dmi-btn--glass" href="{{ route('esbtp.rendez-vous.index') }}"><i class="fas fa-calendar-week"></i>Planning des rendez-vous</a>
                @endcan
                @can('inscriptions.ouvrir-formulaire')
                    <a class="dmi-btn dmi-btn--white" href="{{ route('esbtp.inscriptions.create') }}" title="Une famille sans demande en ligne"><i class="fas fa-user-plus"></i>Inscription directe</a>
                @endcan
            </div>
        </div>
        <div class="dmi-kpis" id="dmi-kpis">
            @include('esbtp.admissions.demandes._kpis')
        </div>
    </header>

    <div class="dmi-corps">
        <section class="dmi-carte" aria-label="Liste des dossiers">
            <div class="dmi-barre">
                @if(count($types) > 1)
                    <div class="dmi-seg" role="tablist" aria-label="Type de dossier">
                        <button type="button" role="tab" :aria-selected="filtres.type === ''" :class="filtres.type === '' ? 'is-actif' : ''" x-on:click="changerType('')">Tous · <span x-text="compteurs.a_traiter"></span></button>
                        <button type="button" role="tab" :aria-selected="filtres.type === 'nouvelle'" :class="filtres.type === 'nouvelle' ? 'is-actif' : ''" x-on:click="changerType('nouvelle')">Nouvelles inscriptions · <span x-text="compteurs.nouvelles"></span></button>
                        <button type="button" role="tab" :aria-selected="filtres.type === 'reinscription'" :class="filtres.type === 'reinscription' ? 'is-actif' : ''" x-on:click="changerType('reinscription')">Réinscriptions · <span x-text="compteurs.reinscriptions"></span></button>
                    </div>
                @endif
                <label class="dmi-recherche">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" x-ref="recherche" x-model="filtres.q" x-on:input.debounce.350ms="filtrer({})" placeholder="Nom, téléphone, matricule ou référence" aria-label="Rechercher un dossier">
                    <kbd title="Raccourci clavier">/</kbd>
                </label>
                <button type="button" class="dmi-puce" :class="filtres.sans_rdv ? 'is-actif' : ''" :aria-pressed="filtres.sans_rdv" x-on:click="filtrer({sans_rdv: !filtres.sans_rdv})">Sans rendez-vous · <span x-text="compteurs.sans_rdv"></span></button>
                {{-- L'etape « Contact à confirmer » remplace ce filtre ; il ne s'affiche que
                     s'il arrive par une ancienne adresse (?contact=1), pour pouvoir le retirer. --}}
                <button type="button" class="dmi-puce dmi-puce--alerte is-actif" aria-pressed="true" x-on:click="filtrer({contact: false})" x-show="filtres.contact" x-cloak>Contact à vérifier <i class="fas fa-xmark" aria-hidden="true"></i></button>
            </div>
            <div class="dmi-liste" id="dmi-liste" :class="chargement ? 'is-chargement' : ''" aria-live="polite">
                @include('esbtp.admissions.demandes._liste')
            </div>
        </section>

        <aside class="dmi-panneau dmi-carte" id="dmi-panneau" aria-label="Dossier sélectionné" :class="{'is-ouvert': !!ouvert, 'is-vide': !ouvert}">
            <div x-show="!ouvert" class="dmi-panneau-vide">
                <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                Choisissez un dossier pour voir son rendez-vous, sa convocation, son parcours et la suite à donner.
                <div class="dmi-raccourcis" aria-label="Raccourcis clavier">
                    <kbd>/</kbd><span>chercher</span>
                    <kbd>↑ ↓</kbd><span>passer d'un dossier à l'autre</span>
                    <kbd>Entrée</kbd><span>ouvrir le dossier</span>
                    <kbd>I</kbd><span>inscrire ou réinscrire</span>
                    <kbd>Échap</kbd><span>fermer</span>
                </div>
            </div>
            <div x-show="ouvert && chargementDossier" class="dmi-panneau-vide"><i class="fas fa-circle-notch fa-spin"></i>Ouverture du dossier…</div>
            <div id="dmi-dossier" x-show="ouvert && !chargementDossier"></div>
        </aside>
    </div>

    @include('esbtp.admissions.demandes._fenetre-inscrire')
    @include('esbtp.admissions.demandes._fenetres')
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
@include('esbtp.admissions.demandes._script')
@endpush
