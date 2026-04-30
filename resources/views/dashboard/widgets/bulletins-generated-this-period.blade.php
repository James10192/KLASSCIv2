@php
    /** @var array $widget */
    $count = \App\Models\ESBTPBulletin::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(30))
        ->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-file-alt'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="sur les 30 derniers jours"
    :color="$widget['color'] ?? 'primary'"
/>
