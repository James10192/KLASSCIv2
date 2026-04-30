@php
    /**
     * Widget : Taux de présence aujourd'hui
     * Calcul : (présents / total saisi) sur les présences ESBTPAttendance du jour.
     */
    $today = \Carbon\Carbon::today();
    $base = \App\Models\ESBTPAttendance::query()->whereDate('date', $today);
    $total = (clone $base)->count();
    $presents = $total > 0
        ? (clone $base)->where(function ($q) {
            $q->where('statut', 'present')->orWhere('status', 'present');
        })->count()
        : 0;
    $rate = $total > 0 ? round(($presents / $total) * 100) : null;
    $color = $widget['color'] ?? 'success';
    if ($rate !== null && $rate < 50) {
        $color = 'warning';
    }
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-clipboard-check' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">
            @if ($rate === null)
                —
            @else
                {{ $rate }}<span class="dw-widget-unit">%</span>
            @endif
        </div>
        <div class="dw-widget-hint">
            @if ($total === 0)
                Aucune présence saisie aujourd'hui
            @else
                {{ $presents }} / {{ $total }} étudiant(s) présents
            @endif
        </div>
    </div>
</div>
