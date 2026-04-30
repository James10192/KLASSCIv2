@php
    /**
     * Widget : Inscriptions de l'année universitaire en cours
     */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
    $count = 0;
    if ($anneeEnCours) {
        $count = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count();
    }
    $color = $widget['color'] ?? 'primary';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-file-signature' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        <div class="dw-widget-hint">
            @if ($anneeEnCours)
                Année {{ $anneeEnCours->name }}
            @else
                Aucune année universitaire active
            @endif
        </div>
    </div>
</div>
