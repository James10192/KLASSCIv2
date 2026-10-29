{{-- Ce qui empeche une famille de reserver ou d'etre convoquee. Rendu aussi seul par index(?fragment=1). --}}
@php
    $_bloquants = collect($maillons)->where('ok', false)->values();
    $_icones = ['canal' => 'fa-door-closed', 'reglages' => 'fa-sliders', 'places' => 'fa-calendar-xmark', 'portail' => 'fa-link-slash', 'messagerie' => 'fa-envelope-circle-check'];
@endphp
@if($_bloquants->isEmpty())
    <div class="rdv-chaine rdv-chaine--ok">
        <i class="fas fa-circle-check"></i>
        <span><strong>Tout est en ordre.</strong> Les familles peuvent réserver en ligne et reçoivent leur convocation par e-mail.</span>
    </div>
@else
    <section class="rdv-card rdv-chaine--ko" aria-labelledby="rdv-chaine-titre">
        <div class="rdv-section-head">
            <div class="rdv-section-icon rdv-section-icon--alerte"><i class="fas fa-triangle-exclamation"></i></div>
            <div>
                <h2 id="rdv-chaine-titre">{{ $_bloquants->count() > 1 ? $_bloquants->count().' points bloquent' : 'Un point bloque' }} les rendez-vous</h2>
                <p>Tant qu'ils ne sont pas réglés, les familles ne peuvent pas réserver ou ne reçoivent rien.</p>
            </div>
        </div>
        <ul class="rdv-maillons">
            @foreach($maillons as $m)
                <li class="rdv-maillon {{ $m['ok'] ? 'rdv-maillon--ok' : 'rdv-maillon--ko' }}">
                    <span class="rdv-maillon-icone"><i class="fas {{ $m['ok'] ? 'fa-check' : ($_icones[$m['cle']] ?? 'fa-xmark') }}"></i></span>
                    <span class="rdv-maillon-texte">
                        <strong>{{ $m['titre'] }}</strong>
                        @unless($m['ok'])<span>{{ $m['detail'] }}</span>@endunless
                    </span>
                    @if(! $m['ok'] && in_array($m['cle'], ['canal', 'reglages'], true) && $peutConfigurer)
                        <button type="button" class="rdv-lien" data-rdv-ouvrir-reglages>Ouvrir les réglages</button>
                    @elseif(! $m['ok'] && $m['cle'] === 'places' && $peutGerer)
                        <button type="button" class="rdv-lien" data-rdv-action="generer">Générer les créneaux</button>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
