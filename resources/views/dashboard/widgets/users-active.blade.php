@php
    /**
     * Widget : Utilisateurs actifs
     */
    $count = \App\Models\User::query()->where('is_active', true)->count();
    $color = $widget['color'] ?? 'primary';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-users' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">comptes activés</div>
    </div>
</div>
