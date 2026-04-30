@php
    /**
     * Widget : Nouveaux étudiants ce mois
     */
    $count = \App\Models\ESBTPEtudiant::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())
        ->count();
    $color = $widget['color'] ?? 'success';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-user-plus' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">depuis le 1er {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('MMMM') }}</div>
    </div>
</div>
