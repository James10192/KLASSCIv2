@props([
    'title' => '',
    'subtitle' => '',
    'icon' => 'fa-chart-bar'
])

<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas {{ $icon }}"></i>
            {{ $title }}
        </div>
        <div class="main-card-subtitle">{{ $subtitle }}</div>
    </div>

    <div class="main-card-body">
        {{ $slot }}
    </div>
</div>