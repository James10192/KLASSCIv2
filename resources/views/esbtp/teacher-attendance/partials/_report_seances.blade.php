{{-- Lignes de séances (infinity scroll). Reçoit $rows (collection décorée). --}}
@php $canEditAttendance = auth()->user()?->can('attendances.edit'); @endphp
@foreach($rows as $row)
    @php
        $h = (int) floor($row['duree']);
        $m = (int) round(($row['duree'] - $h) * 60);
        $dureeFmt = $h . 'h' . ($m > 0 ? sprintf('%02d', $m) : '');
    @endphp
    <div class="tar-seance-row" data-seance-id="{{ $row['id'] }}"
         data-matiere="{{ $row['matiere'] }}"
         data-classe="{{ $row['classe'] }}"
         data-teacher="{{ $row['teacher'] }}"
         data-type="{{ $row['type']->label() }}"
         data-type-style="{{ $row['type']->badgeInlineStyle() }}"
         data-type-icon="{{ $row['type']->badgeIcon() }}"
         data-date="{{ $row['date_full'] }}"
         data-horaire="{{ $row['heure_debut'] ? $row['heure_debut'].'–'.$row['heure_fin'] : '' }}"
         data-duree="{{ $dureeFmt }}"
         data-salle="{{ $row['salle'] }}"
         data-statut-label="{{ $row['statut_label'] }}"
         data-statut-bg="{{ $row['statut_bg'] }}"
         data-statut-color="{{ $row['statut_color'] }}"
         data-show-url="{{ $row['show_url'] }}">
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
            @if($canEditAttendance && !$row['future'])
                <div class="tar-seance-actions">
                    @if($row['statut'] !== 'present')
                        <button type="button" class="tar-act tar-act--ok" title="Marquer présent"
                                onclick="markSeanceStatus({{ $row['id'] }}, 'present')"><i class="fas fa-check"></i></button>
                    @endif
                    @if($row['statut'] !== 'late')
                        <button type="button" class="tar-act tar-act--late" title="Marquer en retard"
                                onclick="markSeanceStatus({{ $row['id'] }}, 'late')"><i class="fas fa-clock"></i></button>
                    @endif
                    @if($row['statut'] !== 'absent')
                        <button type="button" class="tar-act tar-act--no" title="Marquer absent"
                                onclick="markSeanceStatus({{ $row['id'] }}, 'absent')"><i class="fas fa-user-xmark"></i></button>
                    @endif
                </div>
            @endif
            <div class="tar-seance-view">
                <button type="button" class="tar-vw" title="Aperçu de la séance"
                        onclick="openSeanceModal({{ $row['id'] }})"><i class="fas fa-eye"></i></button>
                <a href="{{ $row['show_url'] }}" class="tar-vw" title="Ouvrir la séance complète"><i class="fas fa-up-right-from-square"></i></a>
            </div>
        </div>
    </div>
@endforeach
