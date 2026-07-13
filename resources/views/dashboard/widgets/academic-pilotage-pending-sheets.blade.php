@php
    $year = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $count = $year ? \App\Domain\AcademicPilotage\Models\GradeSheet::query()
        ->where('annee_universitaire_id', $year->id)
        ->whereNotIn('status', ['validated', 'cancelled'])
        ->count() : 0;
@endphp

<x-dw-widget
    :icon="$widget['icon']"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    hint="fiches en cours de traitement"
    :color="$widget['color']"
    :alert="$count > 0"
>
    <div class="dw-widget-hint">Fiches en cours de traitement</div>
    <a class="dw-widget-link" href="{{ route('esbtp.pilotage-academique.index') }}#sheets">Ouvrir les fiches <i class="fas fa-arrow-right"></i></a>
</x-dw-widget>
