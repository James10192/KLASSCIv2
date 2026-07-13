@php
    $year = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $query = $year ? \App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot::query()
        ->where('scope_type', 'class')
        ->where('annee_universitaire_id', $year->id)
        ->where('coverage_pct', '>=', 60)
        ->where('is_dirty', false) : null;
    $score = $query ? (clone $query)->avg('academic_score') : null;
    $classes = $query ? $query->count() : 0;
@endphp

<x-dw-widget
    :icon="$widget['icon']"
    :label="$widget['label']"
    :value="$score === null ? '—' : number_format($score, 0, ',', ' ').'%'"
    :hint="$classes.' classe(s) avec données suffisantes'"
    :color="$widget['color']"
>
    <div class="dw-widget-hint">{{ $classes }} classe(s) avec données suffisantes</div>
    <a class="dw-widget-link" href="{{ route('esbtp.pilotage-academique.index') }}#classes">Voir la santé des classes <i class="fas fa-arrow-right"></i></a>
</x-dw-widget>
