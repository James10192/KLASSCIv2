@php
    /** @var array $widget */
    $count = \App\Models\User::query()->where('is_active', true)->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-users'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="comptes activés"
    :color="$widget['color'] ?? 'primary'"
/>
