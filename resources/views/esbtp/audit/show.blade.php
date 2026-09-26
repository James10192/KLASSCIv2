@extends('layouts.app')

@section('title', $ligne->phrase())

@push('styles')
@include('esbtp.audit._styles-journal')
@endpush

@section('content')
@php
    $_objet = $ligne->objet;
    $_creation = $audit->event === 'created';
    $_suppression = $audit->event === 'deleted';
    // Une action lancee en ligne de commande porte le chemin du script, pas un ecran.
    $_web = str_starts_with((string) $audit->url, 'http');
    $_navigateur = $_web ? \App\Domain\Audit\JournalLisible::navigateur($audit->user_agent) : null;
    // Le resolveur renvoie parfois l'objet lui-meme : la premiere carte le montre deja.
    $_touches = collect($touches)->reject(fn ($t) => str_ends_with((string) ($t['key'] ?? ''), '_self'))->values();
@endphp
<div class="jda">
    <header class="jda-hero jda-hero--detail">
        <div class="jda-d-titre">
            <a class="jda-d-retour" href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), '/audit') ? url()->previous() : route('esbtp.audit.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i>Journal d'audit</a>
            <h1>{{ $ligne->phrase() }}</h1>
            <div class="jda-d-badges">
                @foreach($ligne->motifs as $_motif)<span class="jda-d-badge jda-d-badge--alerte">{{ $_motif }}</span>@endforeach
                <span class="jda-d-badge"><time datetime="{{ $ligne->quand->toIso8601String() }}">{{ \Illuminate\Support\Str::ucfirst($ligne->quandEnClair()) }}</time></span>
                @if($ligne->automatique)
                    <span class="jda-d-badge"><i class="fas fa-gear" aria-hidden="true"></i> Tâche automatique</span>
                @elseif($ligne->role)
                    <span class="jda-d-badge">{{ \Illuminate\Support\Str::ucfirst($ligne->role) }}</span>
                @endif
            </div>
        </div>
        <div class="jda-actions">
            @if(! $ligne->automatique && $audit->user_id)
                <a class="jda-btn jda-btn--glass" href="{{ route('esbtp.audit.index', ['user_id' => $audit->user_id, 'periode' => 'tout']) }}">Toutes les actions de {{ \Illuminate\Support\Str::before($ligne->acteur, ' ') ?: $ligne->acteur }}</a>
            @endif
            @if($_objet->url)
                <a class="jda-btn jda-btn--white" href="{{ $_objet->url }}">{{ str_ends_with($_objet->designation, ' de') ? 'Ouvrir la fiche' : 'Ouvrir '.$_objet->designation }}</a>
            @endif
        </div>
    </header>

    <div class="jda-d-grille">
        <div class="jda-d-principal">
            <section class="jda-d-bloc" aria-labelledby="jda-touche">
                <h2 id="jda-touche">CE QUI A ÉTÉ TOUCHÉ</h2>
                <div class="jda-d-cartes">
                    @php $_balise = $_objet->url ? 'a' : 'div'; @endphp
                    <{{ $_balise }} class="jda-d-carte jda-d-carte--objet" @if($_objet->url) href="{{ $_objet->url }}" @endif>
                        <span class="jda-d-carte-type">{{ $_objet->type }}@if($_objet->supprime) · supprimé@endif</span>
                        <span class="jda-d-carte-nom">{{ $_objet->nom }}</span>
                        @if($_objet->reperes !== [])<span class="jda-d-carte-info">{{ implode(' · ', $_objet->reperes) }}</span>@endif
                    </{{ $_balise }}>
                    @foreach($_touches as $_t)
                        @php $_balise = ! empty($_t['route']) ? 'a' : 'div'; @endphp
                        <{{ $_balise }} class="jda-d-carte" @if(! empty($_t['route'])) href="{{ $_t['route'] }}" @endif>
                            <span class="jda-d-carte-type">{{ $_t['label'] }}</span>
                            <span class="jda-d-carte-nom">{{ $_t['value'] }}</span>
                            @if(! empty($_t['sublabel']))<span class="jda-d-carte-info">{{ $_t['sublabel'] }}</span>@endif
                        </{{ $_balise }}>
                    @endforeach
                </div>
            </section>

            <section class="jda-d-bloc" aria-labelledby="jda-change">
                <h2 id="jda-change">{{ $_creation ? 'CE QUI A ÉTÉ ENREGISTRÉ' : ($_suppression ? 'CE QUI EXISTAIT AVANT LA SUPPRESSION' : 'CE QUI A CHANGÉ') }}</h2>
                @if($changements === [])
                    <p class="jda-d-vide">Aucune information lisible n'a changé. Les détails techniques, plus bas, gardent la trace brute.</p>
                @elseif($_creation || $_suppression)
                    <div class="jda-d-table jda-d-table--2" role="table">
                        <div class="jda-d-entete" role="row"><span role="columnheader">INFORMATION</span><span role="columnheader">VALEUR</span></div>
                        @foreach($changements as $_c)
                            <div class="jda-d-rangee" role="row">
                                <span class="jda-d-champ" role="cell">{{ $_c['champ'] }}</span>
                                <span class="jda-d-apres" role="cell">{{ $_suppression ? $_c['avant'] : $_c['apres'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="jda-d-table" role="table">
                        <div class="jda-d-entete" role="row"><span role="columnheader">INFORMATION</span><span role="columnheader">AVANT</span><span role="columnheader">APRÈS</span></div>
                        @foreach($changements as $_c)
                            <div class="jda-d-rangee" role="row">
                                <span class="jda-d-champ" role="cell">{{ $_c['champ'] }}</span>
                                <span class="jda-d-avant" role="cell">{{ $_c['avant'] }}</span>
                                <span class="jda-d-apres" role="cell"><span class="jda-d-mobile">Après : </span>{{ $_c['apres'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="jda-d-cote">
            <section class="jda-d-bloc" aria-labelledby="jda-vie">
                <h2 id="jda-vie">TOUTE SON HISTOIRE</h2>
                <ol class="jda-d-vie">
                    @foreach($vie as $_v)
                        <li class="{{ $_v->id === $ligne->id ? 'is-courant' : '' }} {{ $_v->motifs !== [] ? 'is-alerte' : '' }}">
                            <span class="jda-d-point" aria-hidden="true"></span>
                            @if($_v->id === $ligne->id || ! $_v->peutOuvrir)
                                <span class="jda-d-vie-titre">{{ \Illuminate\Support\Str::ucfirst(preg_replace('/^a /u', '', $_v->verbe)) }}@if($_v->id === $ligne->id) <span class="jda-sup">cette action</span>@endif</span>
                            @else
                                <a class="jda-d-vie-titre" href="{{ route('esbtp.audit.show', $_v->id) }}">{{ \Illuminate\Support\Str::ucfirst(preg_replace('/^a /u', '', $_v->verbe)) }}</a>
                            @endif
                            <span class="jda-d-vie-meta">{{ \Illuminate\Support\Str::ucfirst($_v->quandEnClair()) }} · {{ $_v->acteur }}@if($_v->role) ({{ $_v->role }})@endif</span>
                            @if($_v->changement)<span class="jda-puce jda-puce--change">{{ $_v->changement }}</span>@endif
                        </li>
                    @endforeach
                </ol>
                @if($vieTronquee)
                    <a class="jda-lien" href="{{ route('esbtp.audit.index', ['model_type' => $audit->auditable_type, 'objet_id' => $audit->auditable_id, 'periode' => 'tout', 'auto' => 1]) }}">Voir toute son histoire</a>
                @endif
            </section>

            <details class="jda-d-bloc jda-d-tech">
                <summary>DÉTAILS TECHNIQUES</summary>
                <dl>
                    <dt>Identifiant</dt><dd>{{ mb_strtolower($_objet->type, 'UTF-8') }} {{ $audit->auditable_id }} · entrée {{ $audit->id }}</dd>
                    <dt>Classe</dt><dd>{{ $audit->auditable_type }}</dd>
                    <dt>Événement</dt><dd>{{ $audit->event }}</dd>
                    @if($audit->ip_address)<dt>Adresse IP</dt><dd>{{ $audit->ip_address }}</dd>@endif
                    @if($_navigateur)<dt>Navigateur</dt><dd title="{{ $audit->user_agent }}">{{ $_navigateur }}</dd>@endif
                    @if($_web)<dt>Écran</dt><dd>{{ parse_url($audit->url, PHP_URL_PATH) ?: $audit->url }}</dd>@elseif($audit->url)<dt>Origine</dt><dd>Ligne de commande</dd>@endif
                    @if($audit->tags)<dt>Étiquettes</dt><dd>{{ $audit->tags }}</dd>@endif
                </dl>
            </details>
        </aside>
    </div>
</div>
@endsection
