@php
    /**
     * Widget : Inscriptions en attente de validation
     * Reprend la logique de DashboardController::superAdminDashboard pour rester cohérent
     * (workflow_step in (prospect, documents_complets, en_validation) ou null + status active).
     */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
    $query = \App\Models\ESBTPInscription::where(function ($q) {
        $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
            $subQ->where('status', 'active')
                ->where(function ($wq) {
                    $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                        ->orWhereNull('workflow_step');
                });
        });
    });
    if ($anneeEnCours) {
        $query->where('annee_universitaire_id', $anneeEnCours->id);
    }
    $count = $query->count();
    $color = $widget['color'] ?? 'warning';
    $hasUrgent = $count > 0;
@endphp

<div class="dw-widget dw-widget--{{ $color }} {{ $hasUrgent ? 'dw-widget--alert' : '' }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-hourglass-half' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($count, 0, ',', ' ') }}</div>
        @if ($hasUrgent)
            <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="dw-widget-link">
                <i class="fas fa-arrow-right"></i> Consulter et valider
            </a>
        @else
            <div class="dw-widget-hint">Aucune inscription en attente</div>
        @endif
    </div>
</div>
