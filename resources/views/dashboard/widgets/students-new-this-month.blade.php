@php
    /** @var array $widget */
    $count = \App\Models\ESBTPEtudiant::query()
        ->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())
        ->count();
    $month = \Carbon\Carbon::now()->locale('fr')->isoFormat('MMMM');
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-user-plus'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    :hint="'depuis le 1er ' . $month"
    :color="$widget['color'] ?? 'primary'"
/>
