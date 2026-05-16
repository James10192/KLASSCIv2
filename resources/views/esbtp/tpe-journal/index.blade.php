@extends('layouts.app')

@section('title', 'Journal TPE')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Journal TPE — Travail Personnel Étudiant (étudiant)
   Namespace : tj-* (TPE Journal)
   Palette : monochrome bleu KLASSCI + couleurs sémantiques workflow
   ═══════════════════════════════════════════════════════════ */
.tj-page { max-width: 1200px; margin: 0 auto; }

/* Hero — pattern KLASSCI canonique (planning-header) */
.tj-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.tj-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.tj-hero-left { display: flex; align-items: center; gap: 1rem; }
.tj-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.tj-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.tj-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

/* KPIs hero */
.tj-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.tj-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.tj-kpi-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
.tj-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
.tj-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

/* Progress bar (heures déclarées vs attendues TPE) */
.tj-progress-wrap {
    margin-top: 1.25rem;
    background: rgba(255,255,255,.12);
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    position: relative;
}
.tj-progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width .4s ease;
}
.tj-progress-meta {
    display: flex; justify-content: space-between;
    margin-top: .5rem;
    font-size: .78rem;
    color: rgba(255,255,255,.85);
}

/* Cards section */
.tj-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1rem;
}
.tj-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .75rem;
}
.tj-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
}
.tj-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.tj-card-subtitle { font-size: .78rem; color: #64748b; margin: 0; }
.tj-card-body { padding: 1.25rem; }

/* Form saisie */
.tj-form-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr .8fr;
    gap: .75rem;
    margin-bottom: .75rem;
}
@@media (max-width: 768px) {
    .tj-form-grid { grid-template-columns: 1fr; }
}
.tj-form-label {
    display: block; font-size: .75rem; font-weight: 700;
    color: #64748b; text-transform: uppercase; letter-spacing: .5px;
    margin-bottom: .4rem;
}
.tj-input, .tj-textarea {
    width: 100%;
    padding: .65rem .9rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .9rem;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
    color: #1e293b;
}
.tj-input:focus, .tj-textarea:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.tj-textarea { resize: vertical; min-height: 60px; }
.tj-btn--primary {
    background: #0453cb;
    color: #fff;
    border: none;
    padding: .65rem 1.5rem;
    border-radius: 10px;
    font-size: .88rem; font-weight: 600;
    cursor: pointer;
    transition: background .15s, transform .15s;
    display: inline-flex; align-items: center; gap: .5rem;
}
.tj-btn--primary:hover { background: #033a8e; }
.tj-btn--ghost {
    background: transparent;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: .4rem .75rem;
    border-radius: 8px;
    font-size: .82rem;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}
.tj-btn--ghost:hover { border-color: #0453cb; color: #0453cb; }
.tj-btn--danger-ghost {
    background: transparent;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: .35rem .65rem;
    border-radius: 8px;
    font-size: .78rem;
    cursor: pointer;
}
.tj-btn--danger-ghost:hover { background: #fef2f2; }

/* Liste déclarations groupées par semaine */
.tj-week-header {
    display: flex; align-items: center; gap: .5rem;
    font-size: .85rem; font-weight: 700;
    color: #033a8e;
    padding: .5rem 0;
    margin-top: .5rem;
    border-bottom: 1px solid #f1f5f9;
}
.tj-decl-row {
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 1rem;
    align-items: center;
    padding: .75rem 0;
    border-bottom: 1px solid #f8fafc;
}
.tj-decl-row:last-child { border-bottom: none; }
.tj-decl-matiere { font-weight: 600; color: #1e293b; font-size: .9rem; }
.tj-decl-meta { font-size: .75rem; color: #64748b; margin-top: .2rem; }
.tj-decl-heures {
    font-weight: 700; color: #0453cb; font-size: .95rem;
    background: rgba(4,83,203,.06); padding: .2rem .6rem; border-radius: 8px;
}

/* Badges statuts (sémantiques OK — workflow validation) */
.tj-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .55rem;
    border-radius: 6px;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
}
.tj-badge--success { background: rgba(16,185,129,.10); color: #047857; border: 1px solid rgba(16,185,129,.25); }
.tj-badge--warning { background: rgba(245,158,11,.10); color: #b45309; border: 1px solid rgba(245,158,11,.30); }
.tj-badge--danger  { background: rgba(220,38,38,.10);  color: #b91c1c; border: 1px solid rgba(220,38,38,.30); }

.tj-empty {
    text-align: center; padding: 2rem 1rem;
    color: #64748b;
}
.tj-empty-icon {
    font-size: 2.2rem; color: #cbd5e1; margin-bottom: .75rem;
}
.tj-rejet-note {
    margin-top: .35rem;
    padding: .5rem .7rem;
    background: rgba(220,38,38,.06);
    border-left: 3px solid #dc2626;
    border-radius: 6px;
    font-size: .78rem;
    color: #7f1d1d;
}

[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="tj-page">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ═══ Hero premium ═══ --}}
    <div class="tj-hero">
        <div class="tj-hero-top">
            <div class="tj-hero-left">
                <div class="tj-hero-icon"><i class="fas fa-book-reader"></i></div>
                <div>
                    <h1>Journal de Travail Personnel</h1>
                    <p>
                        @if ($classe)
                            {{ $classe->name ?? 'Classe' }}
                            @if ($annee) · {{ $annee->name ?? 'Année en cours' }} @endif
                        @else
                            Déclarez vos heures de TPE par matière et par semaine
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="tj-kpis">
            <div class="tj-kpi">
                <div class="tj-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="tj-kpi-value">{{ number_format($progress['declared_total'], 1, ',', ' ') }}h</div>
                    <div class="tj-kpi-label">Heures déclarées validées</div>
                </div>
            </div>
            <div class="tj-kpi">
                <div class="tj-kpi-icon"><i class="fas fa-bullseye"></i></div>
                <div>
                    <div class="tj-kpi-value">{{ $progress['expected_total'] }}h</div>
                    <div class="tj-kpi-label">Volume TPE attendu (semestre)</div>
                </div>
            </div>
            <div class="tj-kpi">
                <div class="tj-kpi-icon"><i class="fas fa-percentage"></i></div>
                <div>
                    <div class="tj-kpi-value">{{ rtrim(rtrim(number_format($progress['pct'], 1, ',', ' '), '0'), ',') }}%</div>
                    <div class="tj-kpi-label">Progression</div>
                </div>
            </div>
            <div class="tj-kpi">
                <div class="tj-kpi-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <div class="tj-kpi-value">{{ $declarations->count() }}</div>
                    <div class="tj-kpi-label">Déclarations totales</div>
                </div>
            </div>
        </div>

        @php
            $pct = $progress['pct'];
            $color = $pct >= 80 ? '#34d399' : ($pct >= 40 ? '#fbbf24' : '#93c5fd');
        @endphp
        <div class="tj-progress-wrap" title="{{ number_format($pct, 1, ',', ' ') }}% des heures TPE déclarées">
            <div class="tj-progress-bar" style="width: {{ $pct }}%; background: {{ $color }};"></div>
        </div>
        <div class="tj-progress-meta">
            <span>Plafond hebdo / ECUE : {{ rtrim(rtrim(number_format($maxHoursPerWeek, 1, ',', ' '), '0'), ',') }}h</span>
            <span>Fenêtre déclarative : {{ $windowWeeks }} dernière(s) semaine(s)</span>
        </div>
    </div>

    {{-- ═══ Section saisie ═══ --}}
    @if ($ecues->isEmpty())
        <div class="tj-card">
            <div class="tj-card-body tj-empty">
                <div class="tj-empty-icon"><i class="fas fa-graduation-cap"></i></div>
                <div><strong>Aucune matière planifiée pour votre classe cette année.</strong></div>
                <div style="margin-top:.5rem; font-size:.85rem;">Contactez la scolarité.</div>
            </div>
        </div>
    @else
        <div class="tj-card">
            <div class="tj-card-header">
                <div class="tj-card-icon"><i class="fas fa-pen-to-square"></i></div>
                <div>
                    <p class="tj-card-title">Déclarer mes heures de la semaine</p>
                    <p class="tj-card-subtitle">
                        @if ($requiresValidation)
                            La déclaration sera <strong>en attente de validation</strong> par l'enseignant.
                        @else
                            La déclaration est enregistrée immédiatement.
                        @endif
                    </p>
                </div>
            </div>
            <div class="tj-card-body">
                <form method="POST" action="{{ route('esbtp.tpe-journal.store') }}">
                    @csrf
                    <input type="hidden" name="annee_universitaire_id" value="{{ $annee?->id }}">

                    <div class="tj-form-grid">
                        <div>
                            <label class="tj-form-label">Matière (ECUE)</label>
                            <x-au-select
                                name="matiere_id"
                                :options="$ecues->pluck('name', 'id')->toArray()"
                                placeholder="— Choisir une matière —"
                                icon="fa-book"
                                :searchable="$ecues->count() > 8" />
                        </div>
                        <div>
                            <label class="tj-form-label">Semaine du lundi</label>
                            <input type="date" name="semaine_debut" class="tj-input"
                                   value="{{ now()->startOfWeek()->format('Y-m-d') }}"
                                   max="{{ now()->startOfWeek()->format('Y-m-d') }}"
                                   min="{{ now()->startOfWeek()->subWeeks($windowWeeks)->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div>
                            <label class="tj-form-label">Heures effectuées</label>
                            <input type="number" name="heures" class="tj-input"
                                   step="0.25" min="0.25" max="{{ $maxHoursPerWeek }}"
                                   placeholder="Ex: 4.5" required>
                        </div>
                    </div>

                    <div style="margin-bottom: .75rem;">
                        <label class="tj-form-label">Description (optionnel) — décrivez ce que vous avez fait</label>
                        <textarea name="description" class="tj-textarea" maxlength="1000"
                                  placeholder="Ex: Lecture du chapitre 3, exercices p.42..."></textarea>
                    </div>

                    <button type="submit" class="tj-btn--primary">
                        <i class="fas fa-plus"></i>Enregistrer la déclaration
                    </button>
                </form>

                @if ($errors->any())
                    <div style="margin-top: 1rem; padding: .65rem .85rem; background: rgba(220,38,38,.08); border-left: 3px solid #dc2626; border-radius: 6px;">
                        @foreach ($errors->all() as $err)
                            <div style="font-size: .82rem; color: #b91c1c;">{{ $err }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══ Historique déclarations ═══ --}}
    <div class="tj-card">
        <div class="tj-card-header">
            <div class="tj-card-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <div>
                <p class="tj-card-title">Mes déclarations</p>
                <p class="tj-card-subtitle">Groupées par semaine, plus récentes en premier</p>
            </div>
        </div>
        <div class="tj-card-body">
            @if ($declarationsParSemaine->isEmpty())
                <div class="tj-empty">
                    <div class="tj-empty-icon"><i class="fas fa-inbox"></i></div>
                    <div>Aucune déclaration pour le moment.</div>
                    <div style="margin-top: .5rem; font-size: .85rem;">Saisissez votre première déclaration ci-dessus.</div>
                </div>
            @else
                @foreach ($declarationsParSemaine as $semaineDebut => $declsBucket)
                    @php
                        $semaineLabel = $semaineDebut
                            ? \Carbon\Carbon::parse($semaineDebut)->isoFormat('[Semaine du] D MMMM YYYY')
                            : 'Semaine inconnue';
                    @endphp
                    <div class="tj-week-header">
                        <i class="fas fa-calendar-week"></i>
                        <span>{{ $semaineLabel }}</span>
                    </div>
                    @foreach ($declsBucket as $decl)
                        <div class="tj-decl-row">
                            <div>
                                <div class="tj-decl-matiere">{{ $decl->matiere->name ?? 'Matière supprimée' }}</div>
                                @if ($decl->matiere && $decl->matiere->uniteEnseignement)
                                    <div class="tj-decl-meta">{{ $decl->matiere->uniteEnseignement->name }}</div>
                                @endif
                                @if ($decl->description)
                                    <div class="tj-decl-meta">{{ \Illuminate\Support\Str::limit($decl->description, 160) }}</div>
                                @endif
                                @if ($decl->statut->value === \App\Enums\TpeDeclarationStatut::REJETE->value && $decl->commentaire_rejet)
                                    <div class="tj-rejet-note">
                                        <strong>Motif du rejet :</strong> {{ $decl->commentaire_rejet }}
                                    </div>
                                @endif
                            </div>
                            <div class="tj-decl-heures">{{ number_format((float) $decl->heures, 2, ',', ' ') }}h</div>
                            <span class="tj-badge {{ $decl->statut->badgeClass() }}">
                                <i class="fas {{ $decl->statut->icon() }}"></i>
                                {{ $decl->statut->label() }}
                            </span>
                            <div>
                                @if ($decl->statut->isEditableByStudent())
                                    <form method="POST" action="{{ route('esbtp.tpe-journal.destroy', $decl) }}" style="display:inline;"
                                          onsubmit="return confirm('Supprimer cette déclaration ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tj-btn--danger-ghost" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
