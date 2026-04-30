@php
    /**
     * Widget : Nombre total d'étudiants
     * @var array $widget Config du widget (clé, label, icon, color, ...)
     * @var \App\Models\User $user
     */
    $count = \App\Models\ESBTPEtudiant::query()->count();
    $color = $widget['color'] ?? 'primary';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-user-graduate' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">étudiants enregistrés</div>
    </div>
</div>
