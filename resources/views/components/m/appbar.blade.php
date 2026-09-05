{{--
    Barre d'application du shell mobile (56px, collante en haut).
    Balise : x-m.appbar
      ex. title="Paiement" sub="Reçu N° 2026-09-00417" :back="route('esbtp.paiements.index')" action="more" action-label="Actions"
      ex. title="Encaissements" action="search" :action-url="route('esbtp.paiements.index')"
    Props :
      title        string        titre (obligatoire)
      sub          string|null   sous-titre (petite ligne grise)
      back         string|null   URL du bouton retour ; null = pas de bouton
      backLabel    string        libellé accessible du retour
      action       string|null   nom d'icône (x-m.icon) du bouton d'action à droite ; null = rien
      actionUrl    string|null   URL de l'action ; null = bouton (attributs restants transmis, ex. x-on:click)
      actionLabel  string        libellé accessible de l'action
    Slot : contenu supplémentaire à droite (autres .m-ib).
--}}
@props([
    'title' => '',
    'sub' => null,
    'back' => null,
    'backLabel' => 'Retour',
    'action' => null,
    'actionUrl' => null,
    'actionLabel' => 'Action',
])
<header class="m-appbar">
    @if($back)
        <a href="{{ $back }}" class="m-ib ghost" aria-label="{{ $backLabel }}">
            <x-m.icon name="chl" />
        </a>
    @endif
    <div class="t">
        {{ $title }}
        @if($sub)
            <small>{{ $sub }}</small>
        @endif
    </div>
    {{ $slot }}
    @if($action)
        @if($actionUrl)
            <a href="{{ $actionUrl }}" class="m-ib" aria-label="{{ $actionLabel }}">
                <x-m.icon :name="$action" />
            </a>
        @else
            <button type="button" class="m-ib" aria-label="{{ $actionLabel }}" {{ $attributes }}>
                <x-m.icon :name="$action" />
            </button>
        @endif
    @endif
</header>
