@props([
    'icon' => 'fa-list',
    'label' => '',
    'color' => 'primary',
])

<div {{ $attributes->merge(['class' => 'dw-widget dw-widget--list dw-widget--' . $color]) }}>
    <div class="dw-widget-list-header">
        <div class="dw-widget-icon dw-widget-icon--small">
            <i class="fas {{ $icon }}"></i>
        </div>
        <div class="dw-widget-label">{{ $label }}</div>
    </div>
    <ul class="dw-widget-list">
        {{ $slot }}
    </ul>
</div>
