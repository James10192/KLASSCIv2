@php
    /** @var array $widget */
    $count = \App\Models\ESBTPNote::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(7))
        ->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-pen-fancy'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="sur les 7 derniers jours"
    :color="$widget['color'] ?? 'primary'"
/>
