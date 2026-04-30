@php
    /** @var array $widget */
    $count = \App\Models\ESBTPEtudiant::query()->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-user-graduate'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="étudiants enregistrés"
    :color="$widget['color'] ?? 'primary'"
/>
