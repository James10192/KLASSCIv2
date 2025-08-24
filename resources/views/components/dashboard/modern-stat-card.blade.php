@props([
    'title' => '',
    'value' => 0,
    'subtitle' => '',
    'icon' => 'fa-chart-bar',
    'type' => 'primary',
    'progress' => null,
    'badge' => null,
    'footer' => null
])

<div class="combinaison-card {{ $type }}" data-aos="fade-up">
    <!-- Card Header Section -->
    <div class="card-header-section">
        <div class="card-logo-info">
            <div class="stat-icon-planning {{ $type }}">
                <i class="fas {{ $icon }}"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="stat-value-planning">{{ $value }}</h3>
                <h6 class="stat-label-planning">{{ $title }}</h6>
            </div>
        </div>
        @if($badge)
            <div class="stat-badge {{ $type }}">
                {!! $badge !!}
            </div>
        @endif
    </div>

    <!-- Card Body Section -->
    <div class="card-body-section">
        @if($subtitle)
        <p class="stat-description">{{ $subtitle }}</p>
        @endif
        
        @if($progress)
        <div class="progress-container">
            <div class="progress-bar-modern">
                <div class="progress-fill {{ $type }}" style="width: {{ $progress['value'] ?? 0 }}%"></div>
            </div>
            <div class="progress-text">
                <span class="progress-label">{{ $progress['label'] ?? '' }}</span>
                <span class="progress-percentage {{ $type }}">{{ $progress['percentage'] ?? 0 }}%</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Card Footer Section -->
    @if($footer)
    <div class="card-footer-section">
        {!! $footer !!}
    </div>
    @endif
</div>