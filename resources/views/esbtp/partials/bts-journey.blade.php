@if(!empty($btsJourney))
    <style>
        .bts-journey-card {
            background: #fff;
            border: 1px solid rgba(4, 83, 203, 0.12);
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        .bts-journey-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .bts-journey-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .bts-journey-sub { color: #475569; font-size: 0.86rem; }
        .bts-journey-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .bts-journey-badge.info { background: rgba(4, 83, 203, 0.1); color: #0453cb; }
        .bts-journey-badge.success { background: rgba(5, 150, 105, 0.12); color: #047857; }
        .bts-journey-badge.muted { background: rgba(100, 116, 139, 0.12); color: #475569; }
        .bts-journey-line { display: grid; gap: 10px; }
        .bts-journey-step {
            display: grid;
            grid-template-columns: 28px 1fr;
            gap: 10px;
            align-items: start;
        }
        .bts-journey-dot {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .bts-journey-step.active .bts-journey-dot { background: #0453cb; color: #fff; }
        .bts-journey-label { font-weight: 700; color: #0f172a; }
        .bts-journey-meta { color: #64748b; font-size: 0.82rem; }
    </style>

    <section class="bts-journey-card" data-bts-journey="1">
        <div class="bts-journey-top">
            <div>
                <div class="bts-journey-title">Parcours BTS</div>
                <div class="bts-journey-sub">
                    @if(($btsJourney['source_model'] ?? null) === 'legacy_dual_inscription')
                        Lecture compatible du dossier historique
                    @else
                        Inscription annuelle avec phases intra-année
                    @endif
                </div>
            </div>
            <span class="bts-journey-badge {{ $btsJourney['badge']['tone'] ?? 'muted' }}">
                {{ $btsJourney['badge']['label'] ?? 'Parcours BTS' }}
            </span>
        </div>

        <div class="bts-journey-line">
            @foreach(($btsJourney['timeline'] ?? []) as $phase)
                <div class="bts-journey-step {{ !empty($phase['is_active']) ? 'active' : '' }}">
                    <div class="bts-journey-dot">{{ $loop->iteration }}</div>
                    <div>
                        <div class="bts-journey-label">
                            {{ $phase['label'] }}
                            @if(!empty($phase['classe']))
                                · {{ $phase['classe'] }}
                            @endif
                        </div>
                        <div class="bts-journey-meta">
                            Semestres {{ $phase['semestre_debut'] ?? '?' }} à {{ $phase['semestre_fin'] ?? '...' }}
                            @if(!empty($phase['filiere']))
                                · {{ $phase['filiere'] }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
