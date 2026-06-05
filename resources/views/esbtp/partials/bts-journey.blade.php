@php
    $btsInscription = $inscription ?? ($btsJourney['inscription'] ?? null);
    $currentType = $btsJourney['current_phase']['type_phase'] ?? null;
    $sourceModel = $btsJourney['source_model'] ?? null;
    $timeline = $btsJourney['timeline'] ?? [];
    $hasActiveSpe = collect($timeline)
        ->contains(fn ($p) => ($p['type_phase'] ?? null) === 'specialisation' && ! empty($p['is_active']));
    // Permission canonique : inscriptions.specialisation.manage (alignée avec
    // ESBTPSpecialisationController middleware et avec le bouton header de
    // show.blade.php). bts_tronc_commun.orient était redondant et créait une
    // incohérence UI (header visible vs banner « Permission requise »).
    $canOrient = $currentType === 'tronc_commun'
        && $sourceModel === 'phase_based'
        && ! $hasActiveSpe
        && $btsInscription !== null
        && auth()->user()?->can('inscriptions.specialisation.manage');
    $orientationTargetsCount = $btsInscription?->classe?->orientationTargets?->where('is_active', true)->count() ?? 0;
    // Filière du tronc commun (pour deep-link vers la page de configuration des sorties)
    $btsTroncFiliere = $btsInscription?->classe?->filiere;
    // Coordination bug 3 agent : la page filieres.show exposera un ancre #sorties-tc
    // (ou onglet) pour permettre la configuration des sorties d'un TC.
    $canConfigureSorties = auth()->user()?->can('bts_tronc_commun.manage_targets')
        || auth()->user()?->can('filieres.edit');
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
            border-radius: 14px;
            padding: .9rem 1.1rem;
            color: #fff;
            margin: .85rem 0;
            box-shadow: 0 6px 18px rgba(4,83,203,.16);
        }
        .bj-hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .bj-hero-left {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
            flex: 1;
        }
        .bj-hero-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: .88rem;
            flex-shrink: 0;
        }
        .bj-hero-title { font-size: .92rem; font-weight: 700; line-height: 1.15; margin: 0; letter-spacing: -.01em; }
        .bj-hero-sub { font-size: .7rem; color: rgba(255,255,255,.72); margin-top: .15rem; line-height: 1.25; }
        .bj-hero-badge {
            display: inline-flex; align-items: center; gap: .28rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .65rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .35px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.22);
            color: #fff;
            white-space: nowrap;
        }
        .bj-hero-badge.success { background: rgba(16,185,129,.28); border-color: rgba(16,185,129,.45); }
        .bj-hero-badge.info { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.32); }
        .bj-hero-badge.muted { background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.18); color: rgba(255,255,255,.78); }
        .bj-hero-badge i { font-size: .62rem; }

        .bj-timeline {
            margin-top: .7rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 9px;
            padding: .45rem .55rem;
            display: flex;
            align-items: stretch;
            gap: .4rem;
            flex-wrap: wrap;
        }
        .bj-step {
            flex: 1; min-width: 170px;
            display: flex; align-items: center; gap: .45rem;
            padding: .25rem .4rem;
            border-radius: 7px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            transition: background .15s ease;
        }
        .bj-step.active {
            background: rgba(255,255,255,.18);
            border-color: rgba(255,255,255,.32);
        }
        .bj-step-dot {
            width: 22px; height: 22px;
            border-radius: 999px;
            background: rgba(255,255,255,.16);
            color: #fff;
            font-size: .65rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,.18);
        }
        .bj-step.active .bj-step-dot {
            background: #fff;
            color: #0453cb;
            border-color: #fff;
        }
        .bj-step-body { min-width: 0; flex: 1; }
        .bj-step-label { font-size: .72rem; font-weight: 700; color: #fff; line-height: 1.2; }
        .bj-step-meta { font-size: .62rem; color: rgba(255,255,255,.68); margin-top: .08rem; line-height: 1.25; }

        .bj-actions {
            margin-top: .65rem;
            display: flex; align-items: center;
            gap: .45rem; flex-wrap: wrap;
        }
        .bj-info {
            flex: 1; min-width: 180px;
            display: flex; align-items: center; gap: .4rem;
            padding: .38rem .6rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 7px;
            font-size: .7rem;
            color: rgba(255,255,255,.92);
            line-height: 1.3;
        }
        .bj-info i { font-size: .72rem; color: #fff; }
        .bj-info strong { color: #fff; font-weight: 700; }

        .bj-warn {
            flex: 1; min-width: 180px;
            display: flex; align-items: center; gap: .4rem;
            padding: .38rem .6rem;
            background: rgba(245,158,11,.18);
            border: 1px solid rgba(245,158,11,.40);
            border-radius: 7px;
            font-size: .7rem;
            color: #fff;
            line-height: 1.3;
        }
        .bj-warn i { color: #fef3c7; }
        .bj-warn-link {
            color: #fef3c7;
            text-decoration: underline;
            font-weight: 600;
            transition: color .15s ease;
            white-space: nowrap;
        }
        .bj-warn-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        .bj-btn {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .35rem .8rem;
            border-radius: 7px;
            font-size: .72rem; font-weight: 600;
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
                        <span>
                            Aucune spécialité configurée pour <strong>{{ $btsInscription->classe?->name ?? 'cette classe' }}</strong>.
                            @if($canConfigureSorties && $btsTroncFiliere)
                                <a href="{{ route('esbtp.filieres.show', $btsTroncFiliere) }}#sorties-tc" class="bj-warn-link">
                                    Configurer les sorties autorisées de la filière TC
                                </a>.
                            @else
                                Contactez un administrateur pour configurer les sorties autorisées de la filière Tronc Commun.
                            @endif
                        </span>
                    </div>
                    <button class="bj-btn bj-btn--disabled" disabled title="Aucune spécialité configurée">
                        <i class="fas fa-graduation-cap"></i>
                        Orienter
                    </button>
                @elseif(! auth()->user()?->can('inscriptions.specialisation.manage'))
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
