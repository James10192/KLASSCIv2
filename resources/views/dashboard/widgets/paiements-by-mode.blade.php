@php
    /** @var array $widget */
    $startMonth = \Carbon\Carbon::now()->startOfMonth();

    // Mapping flexible mode_paiement (DB) → label FR + icon
    // Voir config/payment_modes.php pour la liste canonique + alias
    $labels = config('payment_modes.labels', []);
    $aliases = config('payment_modes.aliases', []);

    // Agrégation : nombre + montant total par mode pour le mois en cours
    // Filtre validés (compatible status / statut, validé / valide)
    $rows = \App\Models\ESBTPPaiement::query()
        ->where(function ($q) {
            $q->whereIn('status', ['validé', 'valide'])
              ->orWhereIn('statut', ['validé', 'valide']);
        })
        ->where(function ($q) use ($startMonth) {
            $q->where('date_paiement', '>=', $startMonth)
              ->orWhere('date_validation', '>=', $startMonth)
              ->orWhere('created_at', '>=', $startMonth);
        })
        ->selectRaw('mode_paiement, COUNT(*) as count, SUM(montant) as total')
        ->groupBy('mode_paiement')
        ->orderByDesc('count')
        ->get();

    // Regroupe les modes (ex : "Espèces" et "especes" → même clé canonique)
    // Préserve l'ordre par count décroissant
    $aggregated = [];
    foreach ($rows as $row) {
        $raw = (string) ($row->mode_paiement ?? '');
        if ($raw === '' || (int) $row->count === 0) {
            continue;
        }

        $slug = \Illuminate\Support\Str::slug($raw, '_');
        $canonical = $aliases[$slug] ?? $slug;

        if (! isset($aggregated[$canonical])) {
            $meta = $labels[$canonical] ?? null;
            $aggregated[$canonical] = [
                'label' => $meta['label'] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $raw)),
                'icon' => $meta['icon'] ?? 'fa-question-circle',
                'count' => 0,
                'total' => 0.0,
            ];
        }

        $aggregated[$canonical]['count'] += (int) $row->count;
        $aggregated[$canonical]['total'] += (float) $row->total;
    }

    // Re-tri final par count décroissant après regroupement
    uasort($aggregated, fn ($a, $b) => $b['count'] <=> $a['count']);
@endphp

<x-dw-widget-list
    :icon="$widget['icon'] ?? 'fa-list-ul'"
    :label="$widget['label']"
    :color="$widget['color'] ?? 'primary'"
>
    @forelse ($aggregated as $mode)
        <li class="dw-widget-list-item">
            <div class="dw-widget-list-title">
                <i class="fas {{ $mode['icon'] }} me-2"></i>
                {{ $mode['label'] }}
            </div>
            <div class="dw-widget-list-meta">
                {{ number_format($mode['count'], 0, ',', ' ') }} paiement{{ $mode['count'] > 1 ? 's' : '' }}
                &mdash;
                {{ number_format($mode['total'], 0, ',', ' ') }} FCFA
            </div>
        </li>
    @empty
        <li class="dw-widget-list-empty">
            <i class="fas fa-inbox"></i>
            Aucun paiement validé ce mois
        </li>
    @endforelse
</x-dw-widget-list>
