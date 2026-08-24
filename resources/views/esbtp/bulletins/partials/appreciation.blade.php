@php
    $appreciationScale = app(\App\Services\AppreciationScaleService::class);
    $classification = $appreciationScale->classificationFor(
        $moyenne === null ? null : (float) $moyenne,
        $system ?? 'bts',
        $emptyLabel ?? '-'
    );
    $badgeClass = $badgeClass ?? null;
    // Setting instance : appreciations en noir sans couleur de fond
    // (jamais hardcode, l'ecole choisit dans /esbtp/bulletins/configuration).
    $plainAppreciation = \App\Helpers\SettingsHelper::get('bulletin_appreciation_plain', '0') == '1';
@endphp

@if($badgeClass && ! $plainAppreciation)
    <span class="{{ $badgeClass }} {{ $badgeClass }}--{{ $appreciationScale->toneFor($classification['slug']) }}">{{ $classification['label'] }}</span>
@else
    {{ $classification['label'] }}
@endif
