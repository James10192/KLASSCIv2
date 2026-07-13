@php
    $year = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $count = $year ? \App\Domain\AcademicPilotage\Models\AcademicAlert::query()
        ->where('annee_universitaire_id', $year->id)
        ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
        ->count() : 0;
@endphp

<x-dw-widget
    :icon="$widget['icon']"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="alertes ouvertes ou en cours"
    :color="$widget['color']"
    :alert="$count > 0"
>
    <div class="dw-widget-hint">Alertes ouvertes ou en cours</div>
    <a class="dw-widget-link" href="{{ route('esbtp.pilotage-academique.index') }}#alerts">Traiter les alertes <i class="fas fa-arrow-right"></i></a>
</x-dw-widget>
