@php
    /**
     * Widget : Paiements en attente de validation
     * Le modèle ESBTPPaiement utilise les colonnes `status` ET `statut` (alias).
     * On filtre prudemment sur les deux variantes pour rester compatible.
     */
    $count = \App\Models\ESBTPPaiement::query()
        ->where(function ($q) {
            $q->where('status', 'en_attente')
              ->orWhere('statut', 'en_attente')
              ->orWhere('status', 'pending');
        })
        ->count();
    $color = $widget['color'] ?? 'warning';
@endphp

<div class="dw-widget dw-widget--{{ $color }} {{ $count > 0 ? 'dw-widget--alert' : '' }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-clock' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        @if ($count > 0)
            <div class="dw-widget-hint">paiement(s) à valider</div>
        @else
            <div class="dw-widget-hint">Aucun paiement en attente</div>
        @endif
    </div>
</div>
