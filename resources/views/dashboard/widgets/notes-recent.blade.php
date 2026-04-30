@php
    /**
     * Widget : Notes saisies au cours des 7 derniers jours
     */
    $count = \App\Models\ESBTPNote::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(7))
        ->count();
    $color = $widget['color'] ?? 'primary';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-pen-fancy' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">sur les 7 derniers jours</div>
    </div>
</div>
