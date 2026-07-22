@php
    /** @var array $widget */
    // ESBTPPaiement utilise les colonnes `status` ET `statut` (alias) — filtre les deux
    $count = \App\Models\ESBTPPaiement::query()
        ->whereIn('status', ['en_attente', 'pending'])
        ->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-clock'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    :color="$widget['color'] ?? 'warning'"
    :alert="$count > 0"
    :hint="$count > 0 ? 'paiement(s) à valider' : 'Aucun paiement en attente'"
/>
