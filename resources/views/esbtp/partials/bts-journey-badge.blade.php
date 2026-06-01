@if(!empty($btsJourney))
    @php
        $tone = $btsJourney['badge']['tone'] ?? 'muted';
        $label = $btsJourney['badge']['label'] ?? 'Parcours BTS';
        $currentPhase = $btsJourney['current_phase'] ?? [];
        $semestreMeta = null;

        if (!empty($currentPhase['semestre_debut'])) {
            $semestreMeta = 'S' . $currentPhase['semestre_debut'];

            if (!empty($currentPhase['semestre_fin']) && (int) $currentPhase['semestre_fin'] !== (int) $currentPhase['semestre_debut']) {
                $semestreMeta .= '-S' . $currentPhase['semestre_fin'];
            }
        }

        $meta = trim(implode(' · ', array_filter([
            $currentPhase['classe'] ?? null,
            $semestreMeta,
        ])));
    @endphp

    <style>
        .bts-mini-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .bts-mini-badge--info { background: rgba(4, 83, 203, 0.1); color: #0453cb; }
        .bts-mini-badge--success { background: rgba(5, 150, 105, 0.12); color: #047857; }
        .bts-mini-badge--muted { background: rgba(100, 116, 139, 0.12); color: #475569; }
        .bts-mini-badge__dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.9;
            flex-shrink: 0;
        }
        .bts-mini-badge__meta {
            font-weight: 600;
            opacity: 0.8;
        }
    </style>

    <span class="bts-mini-badge bts-mini-badge--{{ $tone }}" title="Parcours BTS">
        <span class="bts-mini-badge__dot" aria-hidden="true"></span>
        <span>{{ $label }}</span>
        @if($meta !== '')
            <span class="bts-mini-badge__meta">{{ $meta }}</span>
        @endif
    </span>
@endif
