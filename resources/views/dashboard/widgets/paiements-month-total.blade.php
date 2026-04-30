@php
    /** @var array $widget */
    $startMonth = \Carbon\Carbon::now()->startOfMonth();
    $total = \App\Models\ESBTPPaiement::query()
        ->where(function ($q) {
            $q->whereIn('status', ['validé', 'valide'])
              ->orWhereIn('statut', ['validé', 'valide']);
        })
        ->where(function ($q) use ($startMonth) {
            $q->where('date_paiement', '>=', $startMonth)
              ->orWhere('date_validation', '>=', $startMonth)
              ->orWhere('created_at', '>=', $startMonth);
        })
        ->sum('montant');
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-coins'"
    :label="$widget['label']"
    :value="number_format((float) $total, 0, ',', ' ')"
    unit="FCFA"
    :hint="'depuis le ' . $startMonth->format('d/m/Y')"
    :color="$widget['color'] ?? 'primary'"
/>
