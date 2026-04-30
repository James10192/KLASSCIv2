@php
    /**
     * Widget : Bulletins générés au cours des 30 derniers jours
     */
    $count = \App\Models\ESBTPBulletin::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(30))
        ->count();
    $color = $widget['color'] ?? 'info';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-file-alt' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">sur les 30 derniers jours</div>
    </div>
</div>
