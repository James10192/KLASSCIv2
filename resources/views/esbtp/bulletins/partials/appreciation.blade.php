@php
    $appreciationScale = app(\App\Services\AppreciationScaleService::class);
    $classification = $appreciationScale->classificationFor(
        $moyenne === null ? null : (float) $moyenne,
        $system ?? 'bts',
        $emptyLabel ?? '-'
    );
    $badgeClass = $badgeClass ?? null;
@endphp

@if($badgeClass)
    <span class="{{ $badgeClass }} {{ $badgeClass }}--{{ $appreciationScale->toneFor($classification['slug']) }}">{{ $classification['label'] }}</span>
@else
    {{ $classification['label'] }}
@endif
