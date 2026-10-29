{{--
    Ligne de liste (64px) : avatar/icône · titre + sous-titre · montant + puce.
    Balise : x-m.row
      ex. :href="route('esbtp.paiements.show', $p)" av="KJ" title="Koffi Jean" sub="L2 Droit privé A · Espèces" amount="250 000 FCFA" chip="Validé" chip-type="ok"
      ex. icon="clock" title="Droit constitutionnel" sub="mer. 2 sept. · 08:00–10:00" chip="À justifier" chip-type="warn"
      ex. av="AB" title="Reste dû" amount="-120 000 FCFA" :neg="true"
    Props :
      href      string|null   lien ; null = bloc div (attributs restants transmis, ex. x-on:click)
      av        string|null   initiales (2-3 lettres) dans la pastille
      icon      string|null   nom d'icône (x-m.icon) à la place des initiales
      title     string        titre (une ligne, tronqué)
      sub       string|null   sous-titre gris
      amount    string|null   montant/valeur à droite, déjà formaté
      neg       bool          montant en rouge (sens : reste dû / rejeté)
      chip      string|null   texte de la puce
      chipType  string        ok | warn | bad | info | mute
    Slot : contenu additionnel dans la colonne de droite.
--}}
@props([
    'href' => null,
    'av' => null,
    'icon' => null,
    'title' => '',
    'sub' => null,
    'amount' => null,
    'neg' => false,
    'chip' => null,
    'chipType' => 'mute',
])
@php
    $tag = $href ? 'a' : 'div';
    $allowedChip = in_array($chipType, ['ok', 'warn', 'bad', 'info', 'mute'], true) ? $chipType : 'mute';
    $hasRight = $amount !== null || $chip !== null || trim((string) $slot) !== '';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'm-row']) }}>
    @if($icon)
        <div class="av ic" aria-hidden="true"><x-m.icon :name="$icon" /></div>
    @elseif($av !== null)
        <div class="av" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $av, 0, 3, 'UTF-8'), 'UTF-8') }}</div>
    @else
        <div class="av" aria-hidden="true"><x-m.icon name="chr" /></div>
    @endif
    <div class="tt">
        <b>{{ $title }}</b>
        @if($sub !== null)
            <span>{{ $sub }}</span>
        @endif
    </div>
    @if($hasRight)
        <div class="tr">
            @if($amount !== null)
                <span class="amt {{ $neg ? 'neg' : '' }}">{{ $amount }}</span>
            @endif
            @if($chip !== null)
                <span class="m-chip {{ $allowedChip }}">{{ $chip }}</span>
            @endif
            {{ $slot }}
        </div>
    @else
        <span class="tr" aria-hidden="true"><x-m.icon name="chr" class="m-ic ch" /></span>
    @endif
</{{ $tag }}>
