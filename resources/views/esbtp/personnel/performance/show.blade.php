@extends('layouts.app')
@section('title', 'Activité — '.$personne->name)

{{--
    Le détail de l'activité d'une personne (namespace ap-*), preuves à l'appui :
    chaque chiffre du bandeau a sa liste juste en dessous.
--}}

@push('styles')
    @include('esbtp.personnel.performance.partials._styles')
@endpush

@section('content')
@php
    $l = $ligne ?? [];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $prevues = (int) ($l['seances_prevues'] ?? 0);
    $tenues = (int) ($l['seances_tenues'] ?? 0);
    $attendues = (int) ($l['notes_attendues'] ?? 0);
    $recues = (int) ($l['notes_recues'] ?? 0);
    // Un bloc n'apparaît que s'il concerne la personne : un enseignant n'a pas
    // de ligne « paiements saisis : 0 », une caissière pas de « séances ».
    $aSeances = $prevues > 0;
    $aNotes = $attendues > 0;
    $aPaiements = ($l['paiements_saisis'] ?? 0) + ($l['paiements_valides'] ?? 0) + ($l['paiements_en_attente'] ?? 0) > 0;
    $aInscriptions = ($l['inscriptions'] ?? 0) > 0;
    $routeRetour = auth()->user()->can('performance.view_all') ? route('esbtp.personnel.performance.index', ['periode' => $fenetre->periode]) : null;
@endphp
<div class="container-fluid ap-shell">
    <header class="ap-hero">
        <div class="ap-hero-top">
            <div class="ap-hero-left">
                <div class="ap-hero-icon">{{ mb_strtoupper(mb_substr($personne->name, 0, 1, 'UTF-8'), 'UTF-8') }}</div>
                <div>
                    <h1>{{ $estSoi ? 'Mon activité' : $personne->name }}</h1>
                    <p>{{ $l['role'] ?? ($personne->roles->first()?->label_fr ?? $personne->roles->first()?->name ?? '') }} · {{ $fenetre->libelle }}, du {{ $fenetre->debut->format('d/m/Y') }} au {{ $fenetre->fin->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="ap-hero-actions">
                <nav class="ap-segments" aria-label="Période">
                    @foreach($periodes as $cle => $libelle)
                        <a href="{{ route('esbtp.personnel.performance.show', ['user' => $personne->id, 'periode' => $cle]) }}" class="{{ $fenetre->periode === $cle ? 'is-active' : '' }}">{{ $libelle }}</a>
                    @endforeach
                </nav>
                @if($routeRetour)
                    <a href="{{ $routeRetour }}" class="ap-btn ap-btn--glass"><i class="fas fa-arrow-left"></i>Tout le personnel</a>
                @endif
            </div>
        </div>
        <div class="ap-hero-kpis">
            @if($aSeances)
            <a class="ap-kpi" href="#ap-seances">
                <span class="ap-kpi-icone"><i class="fas fa-chalkboard-user"></i></span>
                <span class="ap-kpi-texte">
                    <span class="ap-kpi-valeur">{{ $prevues > 0 ? $tenues : '—' }}<small>{{ $prevues > 0 ? ' / '.$prevues : '' }}</small></span>
                    <span class="ap-kpi-libelle">{{ $prevues > 0 ? 'séances tenues · '.($l['retards'] ?? 0).' avec retard' : 'aucune séance prévue sur la période' }}</span>
                </span>
            </a>
            @endif
            @if($aNotes)
            <a class="ap-kpi" href="#ap-evaluations">
                <span class="ap-kpi-icone"><i class="fas fa-pen-to-square"></i></span>
                <span class="ap-kpi-texte">
                    <span class="ap-kpi-valeur">{{ $attendues > 0 ? (int) floor($recues / $attendues * 100).' %' : '—' }}</span>
                    <span class="ap-kpi-libelle">{{ $attendues > 0 ? 'des notes rendues · '.$fmt($recues).' sur '.$fmt($attendues).' attendues' : 'aucune évaluation à son nom sur la période' }}</span>
                </span>
            </a>
            @endif
            @if($aPaiements)
            <a class="ap-kpi" href="#ap-paiements">
                <span class="ap-kpi-icone"><i class="fas fa-cash-register"></i></span>
                <span class="ap-kpi-texte">
                    <span class="ap-kpi-valeur">{{ $fmt($l['paiements_saisis'] ?? 0) }}</span>
                    <span class="ap-kpi-libelle">paiements saisis{{ ($l['montant_saisi'] ?? 0) > 0 ? ' · '.$fmt($l['montant_saisi']).' FCFA' : '' }} · {{ $fmt($l['paiements_valides'] ?? 0) }} validés par cette personne</span>
                </span>
            </a>
            @endif
            @if($aInscriptions)
            <div class="ap-kpi">
                <span class="ap-kpi-icone"><i class="fas fa-user-plus"></i></span>
                <span class="ap-kpi-texte">
                    <span class="ap-kpi-valeur">{{ $fmt($l['inscriptions'] ?? 0) }}</span>
                    <span class="ap-kpi-libelle">inscriptions saisies</span>
                </span>
            </div>
            @endif
        </div>
    </header>

    <div class="ap-grille">
        @if(! ($aSeances || $aNotes || $aPaiements || $aInscriptions))
            <div class="ap-card ap-vide"><i class="fas fa-circle-info"></i><strong>Rien d'enregistré sur cette période</strong><span>Aucune séance, évaluation, inscription ni paiement à ce nom. Essayez une autre période.</span></div>
        @endif
        @if($aSeances)
        <section class="ap-card" id="ap-seances">
            <div class="ap-card-tete">
                <div class="ap-section-icone"><i class="fas fa-calendar-xmark"></i></div>
                <div>
                    <h2>Séances non émargées</h2>
                    <p>Séances passées de l'emploi du temps sans émargement de début de cours. Les plus récentes d'abord.</p>
                </div>
            </div>
            @forelse($preuves['seances_non_emargees'] as $s)
                <div class="ap-preuve">
                    <time>{{ \Illuminate\Support\Carbon::parse($s->date_seance)->format('d/m/Y') }}</time>
                    <span>{{ $s->classe ?? 'Classe' }} · {{ $s->matiere ?? 'Matière' }}</span>
                    <em>{{ substr((string) $s->heure_debut, 0, 5) }}–{{ substr((string) $s->heure_fin, 0, 5) }}</em>
                </div>
            @empty
                <div class="ap-vide"><i class="fas fa-circle-check"></i><span>Toutes les séances passées sont émargées.</span></div>
            @endforelse
            @if(($l['seances_non_emargees'] ?? 0) > count($preuves['seances_non_emargees']))
                <p class="ap-note">Et {{ $l['seances_non_emargees'] - count($preuves['seances_non_emargees']) }} autre(s) plus ancienne(s).</p>
            @endif
        </section>
        @endif

        @if($aNotes)
        <section class="ap-card" id="ap-evaluations">
            <div class="ap-card-tete">
                <div class="ap-section-icone"><i class="fas fa-file-circle-question"></i></div>
                <div>
                    <h2>Évaluations sans toutes leurs notes</h2>
                    <p>Évaluations à son nom dont il manque des notes, la plus ancienne d'abord.</p>
                </div>
            </div>
            @forelse($preuves['evaluations_incompletes'] as $e)
                <div class="ap-preuve">
                    <time>{{ \Illuminate\Support\Carbon::parse($e->date_evaluation)->format('d/m/Y') }}</time>
                    <span>{{ $e->classe ?? 'Classe' }} · {{ $e->matiere ?? $e->titre }}</span>
                    <em>{{ $e->recues }} / {{ $e->attendues }} notes</em>
                </div>
            @empty
                <div class="ap-vide"><i class="fas fa-circle-check"></i><span>Toutes les notes sont rendues.</span></div>
            @endforelse
        </section>
        @endif

        @if($aPaiements)
        <section class="ap-card" id="ap-paiements">
            <div class="ap-card-tete">
                <div class="ap-section-icone"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <h2>Paiements saisis, en attente de validation</h2>
                    <p>Saisis par cette personne il y a plus de {{ $attenteJours }} jours et toujours pas validés.</p>
                </div>
            </div>
            @forelse($preuves['paiements_en_attente'] as $p)
                <div class="ap-preuve">
                    <time>{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('d/m/Y') }}</time>
                    <span>{{ $p->numero_recu ?: 'Paiement n° '.$p->id }}</span>
                    <em>{{ $fmt($p->montant) }} FCFA</em>
                    @can('paiements.view')
                        <a class="ap-btn ap-btn--ghost ap-btn--compact" href="{{ route('esbtp.paiements.show', $p->id) }}">Voir</a>
                    @endcan
                </div>
            @empty
                <div class="ap-vide"><i class="fas fa-circle-check"></i><span>Aucun paiement en attente de validation.</span></div>
            @endforelse
        </section>
        @endif
    </div>

    <p class="ap-note">Aucune note n'est attribuée : ces chiffres sont des faits enregistrés dans KLASSCI, chacun vérifiable dans la liste qui le suit.</p>
</div>
@endsection
