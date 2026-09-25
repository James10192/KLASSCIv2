@extends('layouts.app')

@section('title', 'Activité des personnes')

@push('styles')
@include('esbtp.audit._styles-journal')
@endpush

@section('content')
@php
    $_max = max(1, max($hourlyDistribution ?: [0]));
    // Le journal reprend exactement la plage de cette page.
    $_journal = array_filter(['user_id' => $selectedUser?->id, 'date_from' => $dateFrom->format('Y-m-d'), 'date_to' => $dateTo->format('Y-m-d')]);
    $_peutLire = auth()->user()->can('security.audit.view');
@endphp
<div class="jda">
    <header class="jda-hero">
        <div class="jda-hero-top">
            <div class="jda-hero-gauche">
                <div class="jda-hero-icone" aria-hidden="true"><i class="fas fa-user-clock"></i></div>
                <div style="min-width:0">
                    <h1>Activité des personnes</h1>
                    <p>@if($selectedUser)Ce qu'a fait <strong>{{ $selectedUser->name }}</strong>@else{{ "Ce que chacun a fait dans l'application" }}@endif, du {{ $dateFrom->format('d/m/Y') }} au {{ $dateTo->format('d/m/Y') }}.</p>
                </div>
            </div>
            <div class="jda-actions">
                @canany(['security.audit.view', 'comptabilite.audit.view'])
                    <a class="jda-btn jda-btn--glass" href="{{ route('esbtp.audit.index', $_journal) }}"><i class="fas fa-clipboard-list"></i>Journal d'audit</a>
                @endcanany
                @if($selectedUser)
                    <a class="jda-btn jda-btn--white" href="{{ route('esbtp.audit.user-activity', ['date_from' => $dateFrom->format('Y-m-d'), 'date_to' => $dateTo->format('Y-m-d')]) }}">Tout le monde</a>
                @endif
            </div>
        </div>
        <div class="jda-kpis">
            <div class="jda-kpi"><div class="jda-kpi-valeur">{{ number_format($stats['total_actions'], 0, ',', ' ') }}</div><div class="jda-kpi-libelle">actions sur la période</div></div>
            <div class="jda-kpi"><div class="jda-kpi-valeur">{{ number_format($stats['unique_users'], 0, ',', ' ') }}</div><div class="jda-kpi-libelle">personnes actives</div></div>
            <div class="jda-kpi"><div class="jda-kpi-valeur">{{ $stats['peak_hour'] }}</div><div class="jda-kpi-libelle">heure la plus chargée</div></div>
            @php $_balise = $_peutLire ? 'a' : 'div'; @endphp
            <{{ $_balise }} class="jda-kpi {{ $stats['a_regarder'] > 0 ? 'jda-kpi--alerte' : '' }}" @if($_peutLire) href="{{ route('esbtp.audit.index', $_journal + ['theme' => \App\Domain\Audit\ThemesDuJournal::A_REGARDER]) }}" @endif>
                <div class="jda-kpi-valeur">{{ number_format($stats['a_regarder'], 0, ',', ' ') }}</div>
                <div class="jda-kpi-libelle">actions à regarder @if($_peutLire)<i class="fas fa-arrow-right" aria-hidden="true"></i>@endif</div>
            </{{ $_balise }}>
        </div>
    </header>

    {{-- Un changement de personne ou de dates ouvre une autre vue, qu'on peut partager :
         c'est une navigation, pas une mutation. --}}
    <form class="jda-filtres" method="GET" action="{{ route('esbtp.audit.user-activity') }}">
        <div class="jda-filtre jda-filtre--large">
            <x-au-user-picker name="user_id" :value="$selectedUser?->id" :users="$users" placeholder="Tout le monde" :submit-on-change="true" />
        </div>
        <div class="jda-periode">
            <label class="visually-hidden" for="jda-du">Du</label>
            <input id="jda-du" type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" onchange="this.form.requestSubmit()">
            <span aria-hidden="true">→</span>
            <label class="visually-hidden" for="jda-au">Au</label>
            <input id="jda-au" type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" onchange="this.form.requestSubmit()">
        </div>
    </form>

    <div class="jda-activite">
        <section class="jda-carte">
            @php $_lignes = $lignes; @endphp
            <div id="jda-lignes">
                @forelse($_lignes as $_i => $l)
                    @include('esbtp.audit._phrase', ['l' => $l, 'afficherJour' => $_i === 0 || $_lignes[$_i - 1]->quand->format('Y-m-d') !== $l->quand->format('Y-m-d')])
                @empty
                    <div class="jda-vide"><strong>Aucune action sur cette période.</strong>Élargissez les dates ou choisissez une autre personne.</div>
                @endforelse
            </div>
            <x-liste-infinie :paginateur="$tranche" cible="#jda-lignes" libelle="actions" :url="route('esbtp.audit.user-activity')" />
        </section>

        <aside class="jda-d-cote">
            <section class="jda-d-bloc">
                <h2>À QUELLE HEURE</h2>
                <div class="jda-heures" role="img" aria-label="Actions par heure de la journée">
                    @foreach($hourlyDistribution as $_h => $_n)
                        <span style="height: {{ max(2, round($_n / $_max * 100)) }}%" title="{{ sprintf('%02dh', $_h) }} : {{ $_n }} action{{ $_n > 1 ? 's' : '' }}"></span>
                    @endforeach
                </div>
                <div class="jda-heures-axe"><span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span></div>
            </section>
            <section class="jda-d-bloc">
                <h2>CE QUI A ÉTÉ LE PLUS TOUCHÉ</h2>
                @if($topModels->isEmpty())
                    <p class="jda-d-vide">Rien sur cette période.</p>
                @else
                    <ol class="jda-classement">
                        @foreach($topModels as $_m)
                            <li><span>{{ $_m['label'] }}</span><strong>{{ number_format($_m['count'], 0, ',', ' ') }}</strong></li>
                        @endforeach
                    </ol>
                @endif
            </section>
            <section class="jda-d-bloc">
                <h2>DEPUIS QUELLES ADRESSES</h2>
                @if($topIps->isEmpty())
                    <p class="jda-d-vide">Aucune adresse enregistrée.</p>
                @else
                    <ol class="jda-classement">
                        @foreach($topIps as $_ip)
                            <li><span style="font-family:ui-monospace,monospace">{{ $_ip->ip_address }}</span><strong>{{ number_format($_ip->total, 0, ',', ' ') }}</strong></li>
                        @endforeach
                    </ol>
                    <p class="jda-d-vide" style="margin-top:.6rem;font-size:.78rem">{{ number_format($stats['unique_ips'], 0, ',', ' ') }} adresse{{ $stats['unique_ips'] > 1 ? 's' : '' }} différente{{ $stats['unique_ips'] > 1 ? 's' : '' }} en tout.</p>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
