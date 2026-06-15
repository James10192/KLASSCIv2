{{-- KPIs du dashboard coordinateur. Reçoit $stats. --}}
@php
    $tauxEmarge = $stats['teacher_attendance_rate'] ?? 0;
    $tauxPres   = $stats['student_attendance_rate'] ?? 0;
    $kpis = [
        ['ico' => 'fa-calendar-day',     'val' => $stats['scheduled_courses_today'] ?? 0,   'lbl' => 'Cours programmés'],
        ['ico' => 'fa-signature',        'val' => ($stats['teacher_attendances_today'] ?? 0), 'sub' => '/' . ($stats['scheduled_courses_today'] ?? 0), 'lbl' => 'Émargements complets'],
        ['ico' => 'fa-gauge-high',       'val' => $tauxEmarge . '%', 'lbl' => 'Taux d\'émargement', 'accent' => true],
        ['ico' => 'fa-user-check',       'val' => $stats['presences_today'] ?? 0,           'lbl' => 'Présences étudiants'],
        ['ico' => 'fa-user-xmark',       'val' => $stats['absences_today'] ?? 0,            'lbl' => 'Absences', 'warn' => true],
        ['ico' => 'fa-user-clock',       'val' => $stats['retards_today'] ?? 0,             'lbl' => 'Retards'],
        ['ico' => 'fa-chalkboard-user',  'val' => $stats['active_teachers_today'] ?? 0,     'lbl' => 'Enseignants actifs', 'accent' => true],
        ['ico' => 'fa-circle-check',     'val' => $stats['courses_completed_today'] ?? 0,   'lbl' => 'Cours complétés'],
    ];
@endphp
@foreach($kpis as $k)
    <div class="cad-kpi{{ !empty($k['warn']) ? ' cad-kpi--warn' : '' }}">
        <div class="cad-kpi-ico"{!! !empty($k['accent']) ? ' style="background:rgba(255,255,255,.16);"' : '' !!}><i class="fas {{ $k['ico'] }}"></i></div>
        <div>
            <div class="cad-kpi-val">{{ $k['val'] }}@isset($k['sub'])<span class="cad-kpi-sub">{{ $k['sub'] }}</span>@endisset</div>
            <div class="cad-kpi-lbl">{{ $k['lbl'] }}</div>
        </div>
    </div>
@endforeach
