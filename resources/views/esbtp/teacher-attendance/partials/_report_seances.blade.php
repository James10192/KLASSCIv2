{{-- Lignes de séances (infinity scroll). Reçoit $rows (collection décorée). --}}
@foreach($rows as $row)
    @php
        $h = (int) floor($row['duree']);
        $m = (int) round(($row['duree'] - $h) * 60);
        $dureeFmt = $h . 'h' . ($m > 0 ? sprintf('%02d', $m) : '');
    @endphp
    <div class="tar-seance-row">
        <div class="tar-seance-date">
            <div class="tar-seance-day">{{ $row['date'] ? $row['date']->format('d') : '--' }}</div>
            <div class="tar-seance-mon">{{ $row['date'] ? $row['date']->translatedFormat('M') : '' }}</div>
        </div>
        <div class="tar-seance-main">
            <div class="tar-seance-top">
                <span class="tar-seance-matiere">{{ $row['matiere'] }}</span>
                <span class="tar-seance-typechip" style="{{ $row['type']->badgeInlineStyle() }}">
                    <i class="fas {{ $row['type']->badgeIcon() }}"></i> {{ $row['type']->value }}
                </span>
            </div>
            <div class="tar-seance-meta">
                <span><i class="fas fa-user-tie"></i> {{ $row['teacher'] }}</span>
                <span><i class="fas fa-users"></i> {{ $row['classe'] }}</span>
                @if($row['heure_debut'])
                    <span><i class="fas fa-clock"></i> {{ $row['heure_debut'] }}–{{ $row['heure_fin'] }}</span>
                @endif
            </div>
        </div>
        <div class="tar-seance-duree">
            <div class="tar-seance-duree-val">{{ $dureeFmt }}</div>
            <div class="tar-seance-duree-lbl">durée</div>
        </div>
        <div class="tar-seance-statut">
            <span class="tar-statut-badge" style="background:{{ $row['statut_bg'] }};color:{{ $row['statut_color'] }};">
                {{ $row['statut_label'] }}
            </span>
            @if($row['en_retard'])
                <span class="tar-warn tar-warn--late" title="Enseignant en retard sur cette séance">
                    <i class="fas fa-triangle-exclamation"></i> Retard
                </span>
            @elseif($row['non_emarge'])
                <span class="tar-warn tar-warn--miss" title="Séance passée sans émargement">
                    <i class="fas fa-circle-exclamation"></i> Non émargé
                </span>
            @endif
        </div>
    </div>
@endforeach
