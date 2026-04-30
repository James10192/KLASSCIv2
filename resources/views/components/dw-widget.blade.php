@props([
    'icon' => 'fa-chart-line',
    'label' => '',
    'value' => null,
    'unit' => null,
    'hint' => null,
    'color' => 'primary',
    'alert' => false,
])

<div {{ $attributes->merge(['class' => 'dw-widget dw-widget--' . $color . ($alert ? ' dw-widget--alert' : '')]) }}>
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
        {{ $slot }}
        @if ($hint && $slot->isEmpty())
            <div class="dw-widget-hint">{{ $hint }}</div>
        @endif
    </div>
</div>
