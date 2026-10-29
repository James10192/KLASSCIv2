{{--
    État vide (icône + titre + texte + action optionnelle).
    Balise : x-m.empty — ex. icon="inbox" title="Aucun encaissement aujourd'hui" text="Le premier paiement du jour apparaîtra ici."
      slot : un lien .m-btn.g vers l'action suivante.
    Props :
      icon    string   nom d'icône (x-m.icon), défaut inbox
      title   string   titre court
      text    string   phrase d'aide
--}}
@props([
    'icon' => 'inbox',
    'title' => '',
    'text' => null,
])
<div {{ $attributes->merge(['class' => 'm-empty']) }}>
    <x-m.icon :name="$icon" />
    @if($title !== '')
        <b>{{ $title }}</b>
    @endif
    @if($text)
        <span>{{ $text }}</span>
    @endif
    {{ $slot }}
</div>
