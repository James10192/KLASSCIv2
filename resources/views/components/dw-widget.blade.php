@props([
    'icon' => 'fa-chart-line',
    'label' => '',
    'value' => null,
    'unit' => null,
    'hint' => null,
    'color' => 'primary',
    'alert' => false,
])

@php
    $widgetClass = 'dw-widget dw-widget--' . $color . ($alert ? ' dw-widget--alert' : '');
@endphp

<div {{ $attributes->merge(['class' => $widgetClass]) }}>
    <div class="dw-widget-icon">
        <i class="fas {{ $icon }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $label }}</div>
        @if ($value !== null)
            <div class="dw-widget-value">
                {{ $value }}@if ($unit)<span class="dw-widget-unit">{{ $unit }}</span>@endif
            </div>
        @endif
        {{-- Slot personnalisé prioritaire ; le hint sert de fallback texte court. --}}
        {{ $slot }}
        @if ($hint && $slot->isEmpty())
            <div class="dw-widget-hint">{{ $hint }}</div>
        @endif
    </div>
</div>
