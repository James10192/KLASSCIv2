{{--
    Carte héro gradient (chiffre clé du jour).
    Balise : x-m.hero — ex. label="Encaissé aujourd'hui" value="1 240 000" unit="FCFA" :pills="['582 paiements', '3 en attente', 'Caisse ouverte 07:58']"
    Props :
      label   string        étiquette en capitales (au-dessus)
      value   string        valeur principale, déjà formatée
      unit    string|null   unité en petit à droite de la valeur
      pills   array         libellés des pastilles (strings)
    Slot : contenu additionnel sous les pastilles (ex. bouton .m-btn.g).
--}}
@props([
    'label' => '',
    'value' => '',
    'unit' => null,
    'pills' => [],
])
<section {{ $attributes->merge(['class' => 'm-hero']) }}>
    @if($label !== '')
        <span class="k">{{ $label }}</span>
    @endif
    <span class="v">{{ $value }}@if($unit)<small>{{ $unit }}</small>@endif</span>
    @if(count($pills))
        <div class="row">
            @foreach($pills as $pill)
                <span class="pill">{{ $pill }}</span>
            @endforeach
        </div>
    @endif
    {{ $slot }}
</section>
