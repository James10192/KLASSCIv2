{{-- Cartographie du workflow journalier (pipeline). Reçoit $stats. --}}
@php
    $total = (int) ($stats['scheduled_courses_today'] ?? 0);
    $nodes = [
        ['ico' => 'fa-calendar-day',     'val' => $total,                                          'title' => 'Séances',          'sub' => 'Programmées', 'tone' => 'start'],
        ['ico' => 'fa-door-open',        'val' => $stats['teacher_start_attendances_today'] ?? 0,  'title' => 'Émarg. début',     'sub' => 'Début signé', 'tone' => 'step'],
        ['ico' => 'fa-play',             'val' => $stats['call_start_done_today'] ?? 0,            'title' => 'Appel début',      'sub' => 'Appel fait',  'tone' => 'step'],
        ['ico' => 'fa-door-closed',      'val' => $stats['teacher_end_attendances_today'] ?? 0,    'title' => 'Émarg. fin',       'sub' => 'Fin signée',  'tone' => 'step'],
        ['ico' => 'fa-check-double',     'val' => $stats['teacher_attendances_today'] ?? 0,        'title' => 'Émarg. complet',   'sub' => 'Début + fin', 'tone' => 'step'],
        ['ico' => 'fa-stop',             'val' => $stats['call_end_done_today'] ?? 0,              'title' => 'Appel fin',        'sub' => 'Clôture',     'tone' => 'step'],
        ['ico' => 'fa-clipboard-check',  'val' => $stats['roll_calls_completed_today'] ?? 0,       'title' => 'Appels',           'sub' => 'Terminés',    'tone' => 'step'],
        ['ico' => 'fa-circle-check',     'val' => $stats['courses_completed_today'] ?? 0,          'title' => 'Cours bouclés',    'sub' => 'Workflow ✓',  'tone' => 'done'],
    ];
    $completed = (int) ($stats['courses_completed_today'] ?? 0);
    $pct = $total > 0 ? round($completed / $total * 100, 1) : 0;
@endphp
<div class="cad-flow">
    @foreach($nodes as $i => $n)
        @if($i > 0)
            <div class="cad-flow-arrow"><i class="fas fa-chevron-right"></i></div>
        @endif
        <div class="cad-node cad-node--{{ $n['tone'] }}">
            <div class="cad-node-ico"><i class="fas {{ $n['ico'] }}"></i></div>
            <div class="cad-node-val">{{ $n['val'] }}</div>
            <div class="cad-node-title">{{ $n['title'] }}</div>
            <div class="cad-node-sub">{{ $n['sub'] }}</div>
        </div>
    @endforeach
</div>
<div class="cad-flow-summary">
    <div class="cad-flow-summary-head">
        <span><i class="fas fa-chart-pie"></i> Progression globale de la journée</span>
        <strong>{{ $pct }}%</strong>
    </div>
    <div class="cad-flow-bar"><div class="cad-flow-bar-fill" style="width:{{ min(100, $pct) }}%;"></div></div>
    <div class="cad-flow-summary-meta">{{ $completed }} cours entièrement bouclés sur {{ $total }} programmés</div>
</div>
