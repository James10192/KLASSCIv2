@props([
    'title' => '',
    'value' => 0,
    'subtitle' => '',
    'icon' => 'fa-chart-bar',
    'type' => 'primary',
    'footer' => null,
    'progress' => null,
    'badge' => null
])

<div class="main-card {{ $type }}">
    <div class="main-card-header">
        <div class="main-card-icon {{ $type }}">
            <i class="fas {{ $icon }}"></i>
        </div>
        <div class="main-card-content flex-grow-1">
            <h3>{{ $value }}</h3>
            <div class="main-card-subtitle">{{ $title }}</div>
        </div>
        @if($badge)
            <div class="ms-2">
                {!! $badge !!}
            </div>
        @endif
    </div>
    
    @if($progress || $footer)
    <div class="main-card-footer">
        @if($progress)
            <div class="progress-indicator">
                <div class="progress-bar" style="width: {{ $progress['value'] ?? 0 }}%; background: var(--{{ $progress['color'] ?? $type }});"></div>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted small">{{ $progress['label'] ?? '' }}</span>
                <span class="text-{{ $progress['color'] ?? $type }} fw-bold">{{ $progress['percentage'] ?? 0 }}%</span>
            </div>
        @elseif($footer)
            {!! $footer !!}
        @endif
    </div>
    @endif
</div>