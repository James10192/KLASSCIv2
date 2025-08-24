@props([
    'title' => '',
    'value' => 0,
    'trend' => '',
    'icon' => 'fa-chart-bar',
    'color' => 'var(--primary)'
])

<div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
    <div class="kpi-title" style="color: #000; font-weight: 600;">{{ $title }}</div>
    <div class="kpi-value" style="color: {{ $color }}; font-size: 2.5rem; font-weight: bold;">{{ $value }}</div>
    <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
        <i class="fas {{ $icon }}"></i>
        {{ $trend }}
    </div>
</div>