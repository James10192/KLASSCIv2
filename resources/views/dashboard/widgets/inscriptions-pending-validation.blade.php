@php
    /** @var array $widget */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $query = \App\Models\ESBTPInscription::query()->pendingValidation();
    if ($anneeEnCours) {
        $query->where('annee_universitaire_id', $anneeEnCours->id);
    }
    $count = $query->count();
    $hasUrgent = $count > 0;
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-hourglass-half'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    :color="$widget['color'] ?? 'warning'"
    :alert="$hasUrgent"
    :hint="$hasUrgent ? null : 'Aucune inscription en attente'"
>
    @if ($hasUrgent)
        <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="dw-widget-link">
            <i class="fas fa-arrow-right"></i> Consulter et valider
        </a>
    @endif
</x-dw-widget>
