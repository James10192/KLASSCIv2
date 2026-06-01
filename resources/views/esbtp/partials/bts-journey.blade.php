@php
    $btsInscription = $inscription ?? null;
    $currentType = $btsJourney['current_phase']['type_phase'] ?? null;
    $sourceModel = $btsJourney['source_model'] ?? null;
    $hasActiveSpe = collect($btsJourney['timeline'] ?? [])
        ->contains(fn ($p) => ($p['type_phase'] ?? null) === 'specialisation' && ! empty($p['is_active']));
    $canOrient = $currentType === 'tronc_commun'
        && $sourceModel === 'phase_based'
        && ! $hasActiveSpe
        && $btsInscription !== null
        && auth()->user()?->can('bts_tronc_commun.orient');
    $orientationTargetsCount = $btsInscription?->classe?->orientationTargets?->where('is_active', true)->count() ?? 0;
@endphp
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
        .bts-journey-cta-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            margin-top: 14px; padding-top: 14px;
            border-top: 1px dashed rgba(4, 83, 203, 0.18);
        }
        .bts-journey-cta-info {
            font-size: 0.82rem; color: #475569; flex: 1; min-width: 0;
            display: flex; gap: .5rem; align-items: center;
        }
        .bts-journey-cta-info i { color: #0453cb; font-size: .82rem; }
        .bts-journey-cta-btn {
            padding: .55rem 1rem; border-radius: 10px;
            background: linear-gradient(135deg, #0453cb, #3b7ddb);
            color: #fff; text-decoration: none; font-weight: 600; font-size: .85rem;
            display: inline-flex; align-items: center; gap: .4rem;
            border: none; cursor: pointer;
            box-shadow: 0 2px 8px rgba(4,83,203,.22);
            transition: all .15s ease;
        }
        .bts-journey-cta-btn:hover { background: linear-gradient(135deg, #033a8e, #0453cb); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.30); }
        .bts-journey-cta-btn--disabled {
            background: #f1f5f9; color: #94a3b8;
            cursor: not-allowed; box-shadow: none;
        }
        .bts-journey-cta-btn--disabled:hover { background: #f1f5f9; color: #94a3b8; transform: none; box-shadow: none; }
        .bts-journey-warn {
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.25);
            border-radius: 9px;
            padding: .55rem .8rem;
            font-size: .78rem; color: #92400e;
            display: flex; align-items: center; gap: .45rem; flex: 1; min-width: 0;
        }
        .bts-journey-warn i { color: #b45309; }
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
                @php
                    $semestreDebut = $phase['semestre_debut'] ?? null;
                    $semestreFin = $phase['semestre_fin'] ?? null;
                    $semestreLabel = match (true) {
                        empty($semestreDebut) => 'Semestre à définir',
                        empty($semestreFin), (int) $semestreDebut === (int) $semestreFin => 'Semestre ' . $semestreDebut,
                        default => 'Semestres ' . $semestreDebut . ' à ' . $semestreFin,
                    };
                @endphp
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
                            {{ $semestreLabel }}
                            @if(!empty($phase['filiere']))
                                · {{ $phase['filiere'] }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CTA Orienter — Bug #1 fix : bouton visible quand TC active sans spé --}}
        @if($currentType === 'tronc_commun' && $sourceModel === 'phase_based' && ! $hasActiveSpe && $btsInscription !== null)
            <div class="bts-journey-cta-row">
                @if($canOrient && $orientationTargetsCount > 0)
                    <div class="bts-journey-cta-info">
                        <i class="fas fa-route"></i>
                        <span><strong>{{ $orientationTargetsCount }}</strong> spécialité{{ $orientationTargetsCount > 1 ? 's' : '' }} configurée{{ $orientationTargetsCount > 1 ? 's' : '' }} pour cette classe. Lance l'orientation officielle (transition tracée + audit).</span>
                    </div>
                    <a href="{{ route('esbtp.inscriptions.specialisation', $btsInscription) }}" class="bts-journey-cta-btn">
                        <i class="fas fa-graduation-cap"></i>
                        Orienter vers une spécialité
                    </a>
                @elseif($canOrient && $orientationTargetsCount === 0)
                    <div class="bts-journey-warn">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>Aucune spécialité configurée pour la classe <strong>{{ $btsInscription->classe?->name ?? '—' }}</strong>. Demande à un admin de configurer les sorties depuis <em>Administration → Sorties BTS Tronc Commun</em>.</span>
                    </div>
                    <button class="bts-journey-cta-btn bts-journey-cta-btn--disabled" disabled title="Aucune spécialité configurée">
                        <i class="fas fa-graduation-cap"></i>
                        Orienter
                    </button>
                @elseif(! auth()->user()?->can('bts_tronc_commun.orient'))
                    <div class="bts-journey-cta-info">
                        <i class="fas fa-lock"></i>
                        <span>Permission requise pour orienter cet étudiant. Contactez un admin scolarité.</span>
                    </div>
                @endif
            </div>
        @endif
    </section>
@endif
