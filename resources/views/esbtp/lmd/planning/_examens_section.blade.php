{{--
    Section "Examens planifiés" — LMD planning tab dédié (PR6).
    Namespace CSS lpx-* (LMD Planning eXamens).
    Scope query : seance_cours.type_seance IN (EXAMEN, PARTIEL, RATTRAPAGE, SOUTENANCE)
    via $examensRows passé par ESBTPLMDPlanningController::index().

    Phase 1 — utilise infrastructure existante seance_cours (pas nouvelle table).
    Phase 2 (PR8-13) — migration vers esbtp_examens_planifies pour workflow scolarité.

    Rules :
    - .claude/rules/premium-redesign.md (hero monochrome bleu)
    - .claude/rules/ajax-no-reload-premium.md (toutes actions AJAX)
    - .claude/rules/type-seance-enum-extension.md
--}}

@php
    use App\Enums\TypeSeance;

    $examensRows = $examensRows ?? collect();
    $now = now();

    // KPIs hero
    $totalExamens = $examensRows->count();
    $examensPasses = $examensRows->filter(fn ($s) => $s->date_seance && $s->date_seance < $now)->count();
    $examensAvenir = $totalExamens - $examensPasses;
    $surveillantsAssignes = $examensRows->pluck('teacher_id')->filter()->unique()->count();
@endphp

<div class="lpx-section" x-data="{ filterStatus: 'all', filterType: 'all' }">
    {{-- Hero KPIs --}}
    <div class="lpx-hero">
        <div class="lpx-hero-top">
            <div class="lpx-hero-left">
                <div class="lpx-hero-icon"><i class="fas fa-file-circle-check"></i></div>
                <div>
                    <h2 class="lpx-hero-title">Examens planifiés</h2>
                    <p class="lpx-hero-subtitle">
                        Sessions LMD UEMOA — Phase 1 (scope query <code>type_seance ∈ EXAMEN/PARTIEL/RATTRAPAGE/SOUTENANCE</code>)
                    </p>
                </div>
            </div>
            <div class="lpx-hero-actions">
                {{-- Bouton AJOUTER UN EXAMEN — redirige vers seances-cours/create avec type_seance pré-rempli --}}
                <a href="{{ route('esbtp.seances-cours.create') }}?type_seance=EXAMEN"
                   class="lpx-btn lpx-btn--white">
                    <i class="fas fa-plus"></i>
                    Programmer un examen
                </a>
            </div>
        </div>

        <div class="lpx-hero-kpis">
            <div class="lpx-kpi">
                <div class="lpx-kpi-icon"><i class="fas fa-file-lines"></i></div>
                <div>
                    <div class="lpx-kpi-value">{{ $totalExamens }}</div>
                    <div class="lpx-kpi-label">Total examens</div>
                </div>
            </div>
            <div class="lpx-kpi">
                <div class="lpx-kpi-icon"><i class="fas fa-check"></i></div>
                <div>
                    <div class="lpx-kpi-value">{{ $examensPasses }}</div>
                    <div class="lpx-kpi-label">Examens passés</div>
                </div>
            </div>
            <div class="lpx-kpi">
                <div class="lpx-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="lpx-kpi-value">{{ $examensAvenir }}</div>
                    <div class="lpx-kpi-label">À venir</div>
                </div>
            </div>
            <div class="lpx-kpi">
                <div class="lpx-kpi-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="lpx-kpi-value">{{ $surveillantsAssignes }}</div>
                    <div class="lpx-kpi-label">Surveillants assignés</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table chronologique --}}
    @if($examensRows->isEmpty())
        <div class="lpx-empty">
            <i class="fas fa-calendar-xmark"></i>
            <h3>Aucun examen planifié</h3>
            <p>Programmez votre premier examen via le bouton ci-dessus ou depuis l'emploi du temps d'une classe LMD.</p>
        </div>
    @else
        <div class="lpx-table-wrap">
            <table class="lpx-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Date</th>
                        <th><i class="fas fa-book"></i> ECUE</th>
                        <th><i class="fas fa-cubes"></i> UE</th>
                        <th><i class="fas fa-users"></i> Classe</th>
                        <th><i class="fas fa-door-open"></i> Salle</th>
                        <th><i class="fas fa-user-tie"></i> Surveillant</th>
                        <th><i class="fas fa-tag"></i> Type</th>
                        <th><i class="fas fa-circle-info"></i> Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examensRows as $exam)
                        @php
                            $typeSeance = TypeSeance::tryFrom($exam->type_seance);
                            $badgeStyle = $typeSeance?->badgeInlineStyle() ?? '';
                            $badgeIcon = $typeSeance?->badgeIcon() ?? 'fa-file';
                            $isPasse = $exam->date_seance && $exam->date_seance < $now;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $exam->date_seance ? \Carbon\Carbon::parse($exam->date_seance)->format('d/m/Y') : '—' }}</strong>
                                <small class="text-muted">{{ $exam->heure_debut ?? '—' }} → {{ $exam->heure_fin ?? '—' }}</small>
                            </td>
                            <td>
                                @if($exam->matiere)
                                    <strong>{{ $exam->matiere->name }}</strong>
                                    @if($exam->matiere->code)
                                        <small class="text-muted">{{ $exam->matiere->code }}</small>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ optional($exam->matiere?->uniteEnseignement)->code ?? '—' }}</td>
                            <td>{{ optional($exam->emploiTemps?->classe)->name ?? '—' }}</td>
                            <td>{{ $exam->salle ?? '—' }}</td>
                            <td>
                                @if($exam->teacher?->user)
                                    {{ $exam->teacher->user->name }}
                                @else
                                    <span class="lpx-no-surveillant">Non assigné</span>
                                @endif
                            </td>
                            <td>
                                <span class="lpx-badge" style="{{ $badgeStyle }}">
                                    <i class="fas {{ $badgeIcon }}"></i>
                                    {{ $typeSeance?->label() ?? $exam->type_seance }}
                                </span>
                            </td>
                            <td>
                                @if($isPasse)
                                    <span class="lpx-status lpx-status--passe"><i class="fas fa-check"></i>Passé</span>
                                @else
                                    <span class="lpx-status lpx-status--avenir"><i class="fas fa-clock"></i>À venir</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('styles')
<style>
    /* PR6 chantier emploi-temps-lmd-unification : section "Examens planifiés" LMD planning.
       Namespace lpx-* (LMD Planning eXamens). Rule premium-redesign monochrome bleu. */
    .lpx-section { margin-top: 1.5rem; }

    .lpx-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.65rem 2rem 1.4rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .lpx-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .lpx-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .lpx-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff;
    }
    .lpx-hero-title { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .lpx-hero-subtitle {
        color: rgba(255,255,255,.78);
        font-size: .85rem;
        margin: .25rem 0 0;
    }
    .lpx-hero-subtitle code {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        padding: .1rem .35rem;
        border-radius: 4px;
        font-size: .72rem;
    }
    .lpx-hero-actions { display: flex; gap: .6rem; }
    .lpx-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600; text-decoration: none;
        border: 1px solid transparent; transition: all .15s;
    }
    .lpx-btn--white { background: #fff; color: #0453cb; }
    .lpx-btn--white:hover { background: #f8fafc; color: #033a8e; }

    .lpx-hero-kpis { display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .lpx-kpi {
        flex: 1; min-width: 160px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .85rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .lpx-kpi-icon {
        width: 36px; height: 36px; border-radius: 9px;
        background: rgba(255,255,255,.14);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem; flex-shrink: 0;
    }
    .lpx-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .lpx-kpi-label { font-size: .68rem; color: rgba(255,255,255,.7); margin-top: .12rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }

    /* Empty state */
    .lpx-empty {
        text-align: center; padding: 3rem 1rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        color: #64748b;
    }
    .lpx-empty i { font-size: 2.5rem; color: #94a3b8; margin-bottom: .75rem; }
    .lpx-empty h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 .35rem; color: #475569; }
    .lpx-empty p { font-size: .88rem; margin: 0; }

    /* Table */
    .lpx-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .lpx-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .lpx-table th { background: #f8fafc; color: #475569; font-weight: 600; padding: .75rem 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    .lpx-table th i { margin-right: .35rem; color: #94a3b8; }
    .lpx-table td { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .lpx-table tr:last-child td { border-bottom: none; }
    .lpx-table tr:hover { background: rgba(4,83,203,.02); }
    .lpx-table td small { display: block; color: #64748b; font-size: .72rem; margin-top: .1rem; }

    /* Badge type */
    .lpx-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .65rem; border-radius: 6px;
        font-size: .72rem; font-weight: 700; letter-spacing: .3px;
        border: 1px solid;
    }
    .lpx-badge i { font-size: .68rem; }

    /* Status */
    .lpx-status {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 5px;
        font-size: .7rem; font-weight: 600;
    }
    .lpx-status--passe { background: rgba(16, 185, 129, .12); color: #047857; }
    .lpx-status--avenir { background: rgba(4, 83, 203, .12); color: #0453cb; }

    .lpx-no-surveillant { color: #b45309; font-style: italic; font-size: .82rem; }

    @media (max-width: 768px) {
        .lpx-table-wrap { overflow-x: auto; }
        .lpx-table { min-width: 800px; }
    }
</style>
@endpush
