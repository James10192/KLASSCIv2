@php
    /** @var array $widget */
    // Une seule query agrégée : count + sum conditional via CASE — évite 2 round-trips DB
    $row = \App\Models\ESBTPAttendance::query()
        ->whereDate('date', \Carbon\Carbon::today())
        ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'present' OR statut = 'present' THEN 1 ELSE 0 END) as presents")
        ->first();
    $total = (int) ($row->total ?? 0);
    $presents = (int) ($row->presents ?? 0);
    $rate = $total > 0 ? round(($presents / $total) * 100) : null;
    $color = $widget['color'] ?? 'primary';
    if ($rate !== null && $rate < 50) {
        $color = 'warning';
    }

    $value = $rate === null ? '—' : (string) $rate;
    $unit = $rate === null ? null : '%';
    $hint = $total === 0
        ? "Aucune présence saisie aujourd'hui"
        : "{$presents} / {$total} étudiant(s) présents";
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-clipboard-check'"
    :label="$widget['label']"
    :value="$value"
    :unit="$unit"
    :hint="$hint"
    :color="$color"
/>
