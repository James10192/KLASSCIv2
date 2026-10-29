{{--
    Badge de statut d'inscription — 5 états monochrome bleu KLASSCI.
    Piloté par App\View\Components\InscriptionStatusBadge.
    Styles CSS associés : .ii-badge, .ii-badge--* dans inscriptions/index.blade.php
--}}
<span class="ii-badge ii-badge--{{ $key }}" @if($title) title="{{ $title }}" @endif>
    <i class="fas {{ $icon }}"></i>
    <span>{{ $label }}</span>
</span>
