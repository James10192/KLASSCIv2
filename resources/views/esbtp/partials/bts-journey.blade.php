@php
    $btsInscription = $inscription ?? ($btsJourney['inscription'] ?? null);
    $currentType = $btsJourney['current_phase']['type_phase'] ?? null;
    $sourceModel = $btsJourney['source_model'] ?? null;
    $timeline = $btsJourney['timeline'] ?? [];
    $hasActiveSpe = collect($timeline)
        ->contains(fn ($p) => ($p['type_phase'] ?? null) === 'specialisation' && ! empty($p['is_active']));
    $canOrient = $currentType === 'tronc_commun'
        && $sourceModel === 'phase_based'
        && ! $hasActiveSpe
        && $btsInscription !== null
        && auth()->user()?->can('bts_tronc_commun.orient');
    $orientationTargetsCount = $btsInscription?->classe?->orientationTargets?->where('is_active', true)->count() ?? 0;
    $currentPhase = $btsJourney['current_phase'] ?? null;
    $badgeLabel = $btsJourney['badge']['label'] ?? 'Parcours BTS';
    $badgeTone = $btsJourney['badge']['tone'] ?? 'muted';
    $isAnnuelInscription = $btsInscription?->anneeUniversitaire?->is_current ?? false;
@endphp
@if(!empty($btsJourney))
    @include('partials._klassci_toast')
    <style>
        .bj-hero {
            position: relative;
            background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
            border-radius: 18px;
            padding: 1.5rem 1.75rem;
            color: #fff;
            margin: 1.25rem 0;
            box-shadow: 0 12px 32px rgba(4,83,203,.22);
        }
        .bj-hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .bj-hero-left {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
            flex: 1;
        }
        .bj-hero-icon {
            width: 48px; height: 48px;
            border-radius: 13px;
            background: rgba(255,255,255,.14);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .bj-hero-title { font-size: 1.15rem; font-weight: 700; line-height: 1.2; margin: 0; }
        .bj-hero-sub { font-size: .82rem; color: rgba(255,255,255,.78); margin-top: .2rem; }
        .bj-hero-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .35rem .7rem;
            border-radius: 999px;
            font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .4px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.22);
            color: #fff;
            white-space: nowrap;
        }
        .bj-hero-badge.success { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.45); }
        .bj-hero-badge.info { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.32); }
        .bj-hero-badge.muted { background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.18); color: rgba(255,255,255,.78); }
        .bj-hero-badge i { font-size: .68rem; }

        .bj-timeline {
            margin-top: 1.25rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
            padding: .85rem 1rem;
            display: flex;
            align-items: stretch;
            gap: .65rem;
            flex-wrap: wrap;
        }
        .bj-step {
            flex: 1; min-width: 180px;
            display: flex; align-items: center; gap: .6rem;
            padding: .45rem .6rem;
            border-radius: 9px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            transition: background .15s ease;
        }
        .bj-step.active {
            background: rgba(255,255,255,.18);
            border-color: rgba(255,255,255,.32);
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .bj-step-dot {
            width: 30px; height: 30px;
            border-radius: 999px;
            background: rgba(255,255,255,.16);
            color: #fff;
            font-size: .76rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,.18);
        }
        .bj-step.active .bj-step-dot {
            background: #fff;
            color: #0453cb;
            border-color: #fff;
            box-shadow: 0 0 0 3px rgba(255,255,255,.20);
        }
        .bj-step-body { min-width: 0; flex: 1; }
        .bj-step-label { font-size: .82rem; font-weight: 700; color: #fff; line-height: 1.2; }
        .bj-step-meta { font-size: .7rem; color: rgba(255,255,255,.7); margin-top: .15rem; line-height: 1.3; }

        .bj-actions {
            margin-top: 1.1rem;
            display: flex; align-items: center;
            gap: .55rem; flex-wrap: wrap;
        }
        .bj-info {
            flex: 1; min-width: 200px;
            display: flex; align-items: center; gap: .5rem;
            padding: .55rem .8rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px;
            font-size: .78rem;
            color: rgba(255,255,255,.92);
        }
        .bj-info i { font-size: .82rem; color: #fff; }
        .bj-info strong { color: #fff; font-weight: 700; }

        .bj-warn {
            flex: 1; min-width: 200px;
            display: flex; align-items: center; gap: .5rem;
            padding: .55rem .8rem;
            background: rgba(245,158,11,.18);
            border: 1px solid rgba(245,158,11,.40);
            border-radius: 10px;
            font-size: .78rem;
            color: #fff;
        }
        .bj-warn i { color: #fef3c7; }

        .bj-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1rem;
            border-radius: 10px;
            font-size: .82rem; font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .bj-btn--primary {
            background: #fff;
            color: #0453cb;
            border-color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .bj-btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0,0,0,.22);
            color: #033a8e;
        }
        .bj-btn--glass {
            background: rgba(255,255,255,.14);
            color: #fff;
            border-color: rgba(255,255,255,.22);
        }
        .bj-btn--glass:hover {
            background: rgba(255,255,255,.24);
            color: #fff;
            transform: translateY(-1px);
        }
        .bj-btn--disabled {
            background: rgba(255,255,255,.08);
            color: rgba(255,255,255,.5);
            cursor: not-allowed;
            border-color: rgba(255,255,255,.10);
        }
        .bj-btn--disabled:hover { transform: none; background: rgba(255,255,255,.08); }

        @media (max-width: 768px) {
            .bj-hero { padding: 1.25rem; }
            .bj-hero-title { font-size: 1rem; }
            .bj-step { min-width: 100%; }
            .bj-actions { flex-direction: column; align-items: stretch; }
            .bj-btn, .bj-info, .bj-warn { width: 100%; justify-content: center; }
        }
    </style>

    <section class="bj-hero" data-bts-journey="1" data-inscription-id="{{ $btsInscription?->id ?? '' }}"
             x-data="{
                syncing: false,
                async sync() {
                    if (this.syncing) return;
                    if (!confirm('Actualiser le parcours BTS de cet étudiant ?\n\nSi sa classe actuelle est non-TC mais qu\'une phase TC est encore active, elle sera supprimée. Cette opération est tracée dans l\'audit.')) return;
                    this.syncing = true;
                    try {
                        const url = '{{ $btsInscription ? route('esbtp.inscriptions.bts-sync', $btsInscription) : '' }}';
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (!res.ok || data.success === false) throw new Error(data?.result?.message || data?.message || 'Erreur de synchronisation');
                        const status = data.result?.status || 'ok';
                        const msg = data.result?.message || 'Sync terminée.';
                        const toastType = status === 'fixed' ? 'success' : (status === 'ok' ? 'info' : (status === 'error' ? 'error' : 'warning'));
                        const labelMap = { fixed: 'Parcours BTS resynchronisé', ok: 'Aucun changement nécessaire', skipped: 'Sync ignorée', error: 'Erreur' };
                        window.klassciToast(toastType, `<strong>${labelMap[status] || 'Sync'}</strong><br>${msg}`);
                        if (data.html && data.has_banner) {
                            const tmp = document.createElement('div');
                            tmp.innerHTML = data.html;
                            const newSection = tmp.querySelector('[data-bts-journey]');
                            if (newSection) this.$root.replaceWith(newSection);
                        } else {
                            this.$root.remove();
                        }
                    } catch (err) {
                        window.klassciToast('error', err.message || 'Erreur réseau');
                    } finally {
                        this.syncing = false;
                    }
                }
             }">
        <div class="bj-hero-top">
            <div class="bj-hero-left">
                <div class="bj-hero-icon">
                    <i class="fas fa-route"></i>
                </div>
                <div style="min-width:0;">
                    <div class="bj-hero-title">Parcours BTS Tronc Commun</div>
                    <div class="bj-hero-sub">
                        @if($sourceModel === 'legacy_dual_inscription')
                            Lecture compatible du dossier historique
                        @else
                            Inscription annuelle avec phases intra-année
                        @endif
                        @if($btsInscription?->anneeUniversitaire)
                            · {{ $btsInscription->anneeUniversitaire->name ?? $btsInscription->anneeUniversitaire->annee_libelle ?? '' }}
                        @endif
                    </div>
                </div>
            </div>
            <span class="bj-hero-badge {{ $badgeTone }}">
                <i class="fas {{ $badgeTone === 'success' ? 'fa-graduation-cap' : ($badgeTone === 'info' ? 'fa-seedling' : 'fa-circle') }}"></i>
                {{ $badgeLabel }}
            </span>
        </div>

        @if(!empty($timeline))
            <div class="bj-timeline">
                @foreach($timeline as $phase)
                    @php
                        $semestreDebut = $phase['semestre_debut'] ?? null;
                        $semestreFin = $phase['semestre_fin'] ?? null;
                        $semestreLabel = match (true) {
                            empty($semestreDebut) => 'Semestre à définir',
                            empty($semestreFin), (int) $semestreDebut === (int) $semestreFin => 'Semestre ' . $semestreDebut,
                            default => 'Semestres ' . $semestreDebut . ' à ' . $semestreFin,
                        };
                    @endphp
                    <div class="bj-step {{ !empty($phase['is_active']) ? 'active' : '' }}">
                        <div class="bj-step-dot">{{ $loop->iteration }}</div>
                        <div class="bj-step-body">
                            <div class="bj-step-label">
                                {{ $phase['label'] }}
                                @if(!empty($phase['classe']))
                                    · {{ $phase['classe'] }}
                                @endif
                            </div>
                            <div class="bj-step-meta">
                                {{ $semestreLabel }}
                                @if(!empty($phase['filiere']))
                                    · {{ $phase['filiere'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($currentType === 'tronc_commun' && $sourceModel === 'phase_based' && ! $hasActiveSpe && $btsInscription !== null)
            <div class="bj-actions">
                @if($canOrient && $orientationTargetsCount > 0)
                    <div class="bj-info">
                        <i class="fas fa-route"></i>
                        <span><strong>{{ $orientationTargetsCount }}</strong> spécialité{{ $orientationTargetsCount > 1 ? 's' : '' }} configurée{{ $orientationTargetsCount > 1 ? 's' : '' }} pour cette classe — orientation officielle avec transition tracée.</span>
                    </div>
                    <a href="{{ route('esbtp.inscriptions.specialisation', $btsInscription) }}" class="bj-btn bj-btn--primary">
                        <i class="fas fa-graduation-cap"></i>
                        Choisir la spécialité
                    </a>
                @elseif($canOrient && $orientationTargetsCount === 0)
                    <div class="bj-warn">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>Aucune spécialité configurée pour <strong>{{ $btsInscription->classe?->name ?? 'cette classe' }}</strong>. Admin → <em>Sorties BTS Tronc Commun</em>.</span>
                    </div>
                    <button class="bj-btn bj-btn--disabled" disabled title="Aucune spécialité configurée">
                        <i class="fas fa-graduation-cap"></i>
                        Orienter
                    </button>
                @elseif(! auth()->user()?->can('bts_tronc_commun.orient'))
                    <div class="bj-info">
                        <i class="fas fa-lock"></i>
                        <span>Permission requise pour orienter cet étudiant. Contactez un admin scolarité.</span>
                    </div>
                @endif

                @can('admin.access')
                    <button type="button" class="bj-btn bj-btn--glass"
                            @click.prevent="sync()"
                            :disabled="syncing"
                            :class="syncing ? 'bj-btn--disabled' : ''"
                            title="Resynchronise les phases avec la classe actuelle (corrige les désynchronisations historiques)">
                        <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-arrows-rotate'"></i>
                        <span x-text="syncing ? 'Synchronisation…' : 'Actualiser'"></span>
                    </button>
                @endcan
            </div>
        @endif
    </section>
@endif
