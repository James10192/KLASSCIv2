{{--
    Barre d'action fixe au-dessus de la navigation basse (boutons principaux 48px).
    Balise : x-m.actionbar
      slot : un bouton .m-btn.p (pleine largeur) ; avec :row="true", deux boutons côte à côte (.m-btn.g + .m-btn.p).
    Props :
      row   bool   deux colonnes (défaut : une seule, boutons empilés)
    Le socle CSS réserve automatiquement l'espace bas quand une .m-actionbar est présente.
--}}
@props([
    'row' => false,
])
<div {{ $attributes->merge(['class' => 'm-actionbar' . ($row ? ' row' : '')]) }}>
    {{ $slot }}
</div>
