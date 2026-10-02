@php
    /** @var array $widget */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::getCurrent();
    $query = \App\Domain\Inscriptions\StatutRedoublant::contraindreAConfirmer(\App\Models\ESBTPInscription::query());
    if ($anneeEnCours) {
        $query->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id);
    }
    $count = $query->count();
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-redo-alt'"
    :label="$widget['label']"
    :value="number_format($count, 0, ',', ' ')"
    :color="$widget['color'] ?? 'primary'"
    :alert="$count > 0"
    :hint="$count > 0 ? null : 'Tous les statuts redoublant sont confirmés'"
>
    @if ($count > 0 && auth()->user()?->can('inscriptions.view'))
        <a href="{{ route('esbtp.inscriptions.index', ['status' => 'all', 'redoublant' => 'a_confirmer'] + ($anneeEnCours ? ['annee' => $anneeEnCours->id] : [])) }}" class="dw-widget-link">
            <i class="fas fa-arrow-right"></i> Confirmer dans la liste
        </a>
    @endif
</x-dw-widget>
