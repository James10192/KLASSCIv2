@php
    /** @var array $widget */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $count = $anneeEnCours
        ? \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count()
        : 0;
    $hint = $anneeEnCours ? 'Année ' . $anneeEnCours->name : 'Aucune année universitaire active';
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-file-signature'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    :hint="$hint"
    :color="$widget['color'] ?? 'primary'"
/>
