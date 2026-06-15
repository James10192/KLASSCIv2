{{-- Étapes du workflow journalier. Reçoit $stats. --}}
@php
    $total = max(1, $stats['scheduled_courses_today'] ?? 0);
    $steps = [
        ['ico' => 'fa-door-open',   'lbl' => 'Émargement début', 'val' => $stats['teacher_start_attendances_today'] ?? 0],
        ['ico' => 'fa-door-closed', 'lbl' => 'Émargement fin',   'val' => $stats['teacher_end_attendances_today'] ?? 0],
        ['ico' => 'fa-list-check',  'lbl' => 'Appel début',      'val' => $stats['call_start_done_today'] ?? 0],
        ['ico' => 'fa-clipboard-check', 'lbl' => 'Appel fin',    'val' => $stats['call_end_done_today'] ?? 0],
        ['ico' => 'fa-circle-check', 'lbl' => 'Cours bouclés',   'val' => $stats['roll_calls_completed_today'] ?? 0],
    ];
@endphp
@foreach($steps as $s)
    @php $pct = min(100, round(($s['val'] / $total) * 100)); @endphp
    <div class="cad-step">
        <div class="cad-step-ico"><i class="fas {{ $s['ico'] }}"></i></div>
        <div class="cad-step-body">
            <div class="cad-step-top">
                <span class="cad-step-lbl">{{ $s['lbl'] }}</span>
                <span class="cad-step-val">{{ $s['val'] }}<span class="cad-step-tot">/{{ $stats['scheduled_courses_today'] ?? 0 }}</span></span>
            </div>
            <div class="cad-step-bar"><div class="cad-step-bar-fill" style="width:{{ $pct }}%;"></div></div>
        </div>
    </div>
@endforeach
