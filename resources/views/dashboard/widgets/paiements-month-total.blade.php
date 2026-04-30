@php
    /**
     * Widget : Encaissements du mois (paiements validés)
     */
    $startMonth = \Carbon\Carbon::now()->startOfMonth();
    $total = \App\Models\ESBTPPaiement::query()
        ->where(function ($q) {
            $q->where('status', 'validé')
              ->orWhere('status', 'valide')
              ->orWhere('statut', 'validé')
              ->orWhere('statut', 'valide');
        })
        ->where(function ($q) use ($startMonth) {
            $q->where('date_paiement', '>=', $startMonth)
              ->orWhere('date_validation', '>=', $startMonth)
              ->orWhere('created_at', '>=', $startMonth);
        })
        ->sum('montant');
    $color = $widget['color'] ?? 'success';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-coins' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format((float) $total, 0, ',', ' ') }} <span class="dw-widget-unit">FCFA</span></div>
        <div class="dw-widget-hint">depuis le {{ $startMonth->format('d/m/Y') }}</div>
    </div>
</div>
