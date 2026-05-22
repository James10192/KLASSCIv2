@extends('layouts.app')
@section('title', $examen->titre)

@php
    use App\Enums\ExamenStatus;
    use App\Enums\TypeExamen;

    $allClasses = $examen->classes;
    $classesCount = $allClasses->count();
    $isMixteSysteme = ! $examen->hasConsistentSysteme();
    $systeme = $isMixteSysteme ? 'MIXTE' : $examen->systeme;

    $scopeLabel = match($examen->scope_type) {
        'parcours' => 'Parcours',
        'mention' => 'Mention (L1 tronc commun)',
        'domaine' => 'Domaine',
        default => 'Classe unique',
    };

    $typeColors = [
        'EXAMEN' => '#0453cb',
        'PARTIEL' => '#3b7ddb',
        'RATTRAPAGE' => '#b45309',
        'SOUTENANCE' => '#033a8e',
    ];
    $typeIcons = [
        'EXAMEN' => 'fa-pen-ruler',
        'PARTIEL' => 'fa-pen-to-square',
        'RATTRAPAGE' => 'fa-rotate-right',
        'SOUTENANCE' => 'fa-microphone',
    ];
    $typeColor = $typeColors[$examen->type_examen] ?? '#0453cb';
    $typeIcon = $typeIcons[$examen->type_examen] ?? 'fa-calendar-check';

    $statusBadgeClass = ExamenStatus::badgeClassFor($examen->status);

    $eventLabels = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
    ];

    $survData = $examen->surveillants->map(function ($s) {
        $roleLabel = match($s->role) {
            'surveillant' => 'Surveillant',
            'surveillant_principal' => 'Surveillant principal',
            'secretaire' => 'Secrétaire',
            'responsable_salle' => 'Responsable salle',
            default => ucfirst(str_replace('_', ' ', $s->role)),
        };
        return [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'user_name' => $s->user?->name,
            'user_email' => $s->user?->email,
            'user_initial' => mb_substr($s->user?->name ?? '?', 0, 1, 'UTF-8'),
            'role' => $s->role,
            'role_label' => $roleLabel,
            'confirmed' => (bool) $s->confirmed,
        ];
    })->values();

    $surveillantOptions = $surveillantsDispo->mapWithKeys(fn ($u) => [
        $u->id => $u->name . ($u->email ? ' · '.$u->email : ''),
    ])->all();
@endphp

@push('styles')
<style>
[x-cloak] { display:none !important; }

/* ════════════════════ HERO ════════════════════ */
.exs-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.exs-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.exs-hero-left { display: flex; gap: 1rem; align-items: flex-start; flex: 1; min-width: 0; }
.exs-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.20);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0; color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.10);
}
.exs-hero-info { flex: 1; min-width: 0; }
.exs-hero-info h1 {
    font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 .35rem;
    letter-spacing: -.01em; line-height: 1.2;
}
.exs-hero-info p {
    color: rgba(255,255,255,.80); font-size: .88rem; margin: 0;
    display: flex; gap: .65rem; align-items: center; flex-wrap: wrap;
}
.exs-hero-info p code {
    background: rgba(255,255,255,.10); padding: .15rem .55rem; border-radius: 5px;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: .78rem; color: #fff; font-weight: 600; letter-spacing: .03em;
    border: 1px solid rgba(255,255,255,.15);
}

.exs-hero-actions { display: flex; gap: .45rem; flex-shrink: 0; }
.exs-btn {
    padding: .55rem 1.05rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    border: 1px solid; cursor: pointer;
    display: inline-flex; align-items: center; gap: .45rem;
    text-decoration: none; transition: all .15s ease;
}
.exs-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.25); }
.exs-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; transform: translateY(-1px); }
.exs-btn--white { background: #fff; color: #0453cb; border-color: transparent; }
.exs-btn--white:hover { background: #f1f5ff; color: #033a8e; transform: translateY(-1px); }
.exs-btn--primary { background: #0453cb; color: #fff; border-color: #0453cb; }
.exs-btn--primary:hover { background: #033a8e; transform: translateY(-1px); }
.exs-btn--secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.exs-btn--secondary:hover { background: #e2e8f0; color: #1e293b; }
.exs-btn--warning { background: rgba(245,158,11,.12); color: #b45309; border-color: rgba(245,158,11,.30); }
.exs-btn--warning:hover { background: rgba(245,158,11,.20); color: #92400e; }
.exs-btn--danger { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.25); }
.exs-btn--danger:hover { background: rgba(220,38,38,.16); color: #991b1b; }
.exs-btn:disabled { opacity: .55; cursor: wait; transform: none !important; }

/* Hero chips row */
.exs-hero-chips { display: flex; gap: .5rem; margin-top: 1.25rem; flex-wrap: wrap; }
.exs-hero-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .75rem; border-radius: 8px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.22);
    color: #fff; font-size: .78rem; font-weight: 600;
    backdrop-filter: blur(8px);
}
.exs-hero-chip i { font-size: .72rem; opacity: .9; }
.exs-hero-chip--type {
    background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.30);
}
.exs-hero-chip--status-cancelled { text-decoration: line-through; opacity: .7; }
.exs-hero-chip--lock { background: rgba(220,38,38,.30); border-color: rgba(255,200,200,.35); }
.exs-hero-chip--anon { background: rgba(4,83,203,.40); border-color: rgba(173,200,255,.40); }

/* Hero KPIs */
.exs-hero-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .65rem; margin-top: 1.25rem; }
.exs-hero-kpi {
    background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.16);
    border-radius: 10px; padding: .65rem .85rem;
}
.exs-hero-kpi-label { font-size: .62rem; color: rgba(255,255,255,.65);
    text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
.exs-hero-kpi-value { font-size: 1rem; font-weight: 700; color: #fff; margin-top: .2rem;
    display: flex; align-items: center; gap: .35rem; }
.exs-hero-kpi-value i { font-size: .82rem; opacity: .8; }

/* ════════════════════ GRID SECTIONS ════════════════════ */
.exs-grid {
    display: grid; gap: 1rem;
    grid-template-columns: 1.4fr 1fr;
    margin-bottom: 1rem;
}
@@media (max-width: 992px) { .exs-grid { grid-template-columns: 1fr; } }

.exs-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    /* IMPORTANT : pas d'overflow:hidden — le dropdown du composant
       au-select de la section Surveillants doit pouvoir déborder de la
       card. Voir rule .claude/rules/css-stacking-pitfalls.md */
}
/* Le header conserve son border-radius via background sans clip */
.exs-card > .exs-card-header:first-child { border-radius: 14px 14px 0 0; }
.exs-card > *:last-child:not(.exs-card-header) { border-radius: 0 0 14px 14px; }
/* La card avec dropdown ouvert passe au-dessus des sœurs (stacking) */
.exs-card { position: relative; z-index: 1; }
.exs-card:focus-within { z-index: 10; }
.exs-card-header {
    display: flex; align-items: center; gap: .55rem;
    padding: 1rem 1.25rem .85rem;
    border-bottom: 1px solid #f1f5f9;
}
.exs-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.exs-card-title { font-size: .95rem; font-weight: 700; color: #0f172a; flex: 1; min-width: 0; }
.exs-card-subtitle { font-size: .72rem; color: #64748b; font-weight: 500; }
.exs-card-meta { font-size: .72rem; color: #64748b; font-weight: 600;
    padding: .15rem .55rem; background: #f1f5f9; border-radius: 6px; }
.exs-card-body { padding: 1.05rem 1.25rem 1.15rem; }
.exs-card-body--flush { padding: 0; }

/* Key-Value list */
.exs-kv { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.exs-kv-row {
    display: flex; flex-direction: column; gap: .15rem;
    padding: .7rem 1.25rem;
    border-top: 1px solid #f1f5f9;
}
.exs-kv-row--full { grid-column: 1 / -1; }
.exs-kv-row:first-child, .exs-kv-row:nth-child(2) { border-top: none; }
.exs-kv-label {
    font-size: .65rem; color: #64748b; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
}
.exs-kv-value {
    font-size: .9rem; color: #0f172a; font-weight: 600;
    display: flex; align-items: center; gap: .4rem;
}
.exs-kv-value i { color: #0453cb; font-size: .8rem; }
.exs-kv-value code {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: .85rem; background: rgba(4,83,203,.07);
    color: #0453cb; padding: .1rem .45rem; border-radius: 5px;
    font-weight: 700;
}
.exs-kv-empty { color: #94a3b8; font-style: italic; font-weight: 500; }
.exs-kv-row--accent { background: linear-gradient(90deg, rgba(4,83,203,.04), transparent); }

/* Description block */
.exs-description {
    padding: .85rem 1rem; background: #f8fafc;
    border-radius: 10px; color: #475569; font-size: .85rem;
    margin: 0 1.25rem 1rem;
    border-left: 3px solid #0453cb;
    line-height: 1.55;
}

/* ════════════════════ COHORTE CLASSES ════════════════════ */
.exs-classes-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .55rem;
}
.exs-classe-card {
    display: flex; align-items: center; gap: .65rem;
    padding: .65rem .8rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 11px;
    text-decoration: none; color: #1e293b;
    transition: all .15s ease;
    min-width: 0;
}
.exs-classe-card:hover {
    background: #eff6ff; border-color: rgba(4,83,203,.30);
    transform: translateY(-1px); color: #0f172a;
    box-shadow: 0 4px 10px rgba(4,83,203,.08), 0 1px 3px rgba(15,23,42,.04);
}
.exs-classe-icon {
    width: 32px; height: 32px; border-radius: 9px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: .78rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.20);
}
.exs-classe-body { flex: 1; min-width: 0; }
.exs-classe-name { font-weight: 700; font-size: .85rem; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.exs-classe-meta { font-size: .68rem; color: #64748b; margin-top: .1rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.exs-empty {
    padding: 2rem; text-align: center; color: #94a3b8; font-size: .85rem;
}
.exs-empty i { font-size: 1.8rem; color: #cbd5e1; display: block; margin-bottom: .6rem; }

/* ════════════════════ SURVEILLANTS ════════════════════ */
.exs-surv-list { display: flex; flex-direction: column; gap: .4rem; padding: 0 1.25rem; margin-bottom: 1rem; }
.exs-surv-card {
    display: flex; align-items: center; gap: .65rem;
    padding: .55rem .75rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    transition: background .15s, border-color .15s;
}
.exs-surv-card:hover { background: #f1f5f9; border-color: #cbd5e1; }
.exs-surv-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .82rem; flex-shrink: 0;
}
.exs-surv-body { flex: 1; min-width: 0; }
.exs-surv-name { font-weight: 600; font-size: .82rem; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.exs-surv-email { font-size: .7rem; color: #64748b; margin-top: .05rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.exs-surv-right { display: flex; flex-direction: column; align-items: flex-end; gap: .15rem; flex-shrink: 0; }
.exs-surv-role {
    padding: .15rem .5rem; border-radius: 5px; font-size: .62rem;
    background: rgba(4,83,203,.10); color: #0453cb; font-weight: 700;
    text-transform: uppercase; letter-spacing: .03em;
}
.exs-surv-role--principal { background: rgba(4,83,203,.18); }
.exs-surv-role--responsable_salle { background: rgba(245,158,11,.12); color: #b45309; }
.exs-surv-role--secretaire { background: rgba(16,185,129,.10); color: #047857; }
.exs-surv-status {
    font-size: .62rem; font-weight: 600;
}
.exs-surv-status--confirmed { color: #047857; }
.exs-surv-status--pending { color: #b45309; }

/* Add surveillant form */
.exs-surv-add {
    padding: .85rem 1.25rem 1.15rem;
    border-top: 1px dashed #e2e8f0;
    background: linear-gradient(180deg, transparent, rgba(4,83,203,.02));
}
.exs-surv-add-title {
    font-size: .72rem; font-weight: 700; color: #0453cb;
    text-transform: uppercase; letter-spacing: .04em;
    margin-bottom: .55rem;
    display: flex; align-items: center; gap: .35rem;
}
.exs-surv-add-form { display: flex; flex-direction: column; gap: .5rem; }
.exs-surv-add-form .au-select, .exs-surv-add-form .au-select-trigger { width: 100%; box-sizing: border-box; }
.exs-surv-add-form select.exs-native {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 9px;
    padding: .5rem .7rem; font-size: .85rem; background: #fff; color: #1e293b;
}

/* ════════════════════ AUDIT TIMELINE ════════════════════ */
.exs-timeline { padding: .5rem 1.25rem 1.15rem; }
.exs-timeline-item {
    display: flex; gap: .65rem; padding: .55rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.exs-timeline-item:last-child { border-bottom: none; }
.exs-timeline-dot {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(4,83,203,.10); color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .68rem; flex-shrink: 0;
}
.exs-timeline-dot--created { background: rgba(16,185,129,.12); color: #047857; }
.exs-timeline-dot--updated { background: rgba(4,83,203,.12); color: #0453cb; }
.exs-timeline-dot--deleted { background: rgba(220,38,38,.10); color: #b91c1c; }
.exs-timeline-body { flex: 1; min-width: 0; }
.exs-timeline-meta { font-size: .82rem; color: #0f172a; font-weight: 500;
    display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
.exs-timeline-meta strong { font-weight: 700; color: #0453cb; }
.exs-timeline-meta time { font-size: .68rem; color: #94a3b8; }
.exs-timeline-link {
    margin-top: .35rem; display: inline-flex; align-items: center; gap: .25rem;
    font-size: .7rem; color: #0453cb; text-decoration: none; font-weight: 600;
}
.exs-timeline-link:hover { text-decoration: underline; }

/* ════════════════════ FOOTER ACTIONS ════════════════════ */
.exs-footer {
    background: linear-gradient(135deg, #fff, #f8faff);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: .55rem; flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.exs-footer-meta {
    font-size: .72rem; color: #64748b; flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: .15rem;
}
.exs-footer-meta strong { color: #0f172a; font-weight: 600; }

/* ════════════════════ TOAST ════════════════════ */
.exs-toasts { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1100;
    display: flex; flex-direction: column; gap: .5rem; max-width: 400px; }
.exs-toast {
    display: flex; align-items: flex-start; gap: .65rem;
    padding: .75rem 1rem; border-radius: 10px;
    background: #fff; border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15,23,42,.12);
    font-size: .85rem;
}
.exs-toast--success { border-left: 4px solid #10b981; color: #065f46; }
.exs-toast--error { border-left: 4px solid #dc2626; color: #991b1b; }
.exs-toast--info { border-left: 4px solid #0453cb; color: #1e3a8a; }

@@media (max-width: 768px) {
    .exs-hero { padding: 1.25rem 1rem; }
    .exs-hero-top { flex-direction: column; }
    .exs-kv { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div x-data="examenShow()" class="exs-page">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="exs-hero">
        <div class="exs-hero-top">
            <div class="exs-hero-left">
                <div class="exs-hero-icon"><i class="fas {{ $typeIcon }}"></i></div>
                <div class="exs-hero-info">
                    <h1>{{ $examen->titre }}</h1>
                    <p>
                        @if($examen->numero_convocation)
                            <code>{{ $examen->numero_convocation }}</code>
                        @endif
                        @if($examen->matiere)
                            <span><i class="fas fa-book" style="opacity:.7;font-size:.72rem;"></i> {{ $examen->matiere->name }}</span>
                        @endif
                        @if($examen->uniteEnseignement)
                            <span><i class="fas fa-cubes" style="opacity:.7;font-size:.72rem;"></i> UE : {{ $examen->uniteEnseignement->name }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="exs-hero-actions">
                @can('lmd.examens.manage')
                    @if(! $examen->notes_locked)
                        <a href="{{ route('esbtp.examens.edit', $examen) }}" class="exs-btn exs-btn--glass">
                            <i class="fas fa-pen"></i> Modifier
                        </a>
                    @endif
                @endcan
                <a href="{{ route('esbtp.examens.convocations.preview', ['classe_id' => $examen->classe_id, 'annee_universitaire_id' => $examen->annee_universitaire_id]) }}"
                   target="_blank" class="exs-btn exs-btn--white">
                    <i class="fas fa-file-pdf"></i> Convocations PDF
                </a>
                <a href="{{ route('esbtp.examens.index') }}" class="exs-btn exs-btn--glass">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <div class="exs-hero-chips">
            <span class="exs-hero-chip exs-hero-chip--type" style="background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.30);">
                <i class="fas {{ $typeIcon }}"></i>
                {{ TypeExamen::labelFor($examen->type_examen) }}
            </span>
            <span class="exs-hero-chip">
                <i class="fas {{ $systeme === 'LMD' ? 'fa-graduation-cap' : ($systeme === 'MIXTE' ? 'fa-circle-exclamation' : 'fa-screwdriver-wrench') }}"></i>
                {{ $systeme }}
            </span>
            <span class="exs-hero-chip exs-hero-chip--status-{{ $examen->status }}">
                <i class="fas {{ ExamenStatus::tryFrom($examen->status)?->icon() ?? 'fa-circle' }}"></i>
                {{ ExamenStatus::labelFor($examen->status) }}
            </span>
            @if($examen->is_anonymous)
                <span class="exs-hero-chip exs-hero-chip--anon">
                    <i class="fas fa-mask"></i> Copies anonymisées
                </span>
            @endif
            @if($examen->notes_locked)
                <span class="exs-hero-chip exs-hero-chip--lock">
                    <i class="fas fa-lock"></i> Notes verrouillées
                </span>
            @endif
        </div>

        <div class="exs-hero-kpis">
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Date</div>
                <div class="exs-hero-kpi-value">
                    <i class="far fa-calendar"></i>{{ optional($examen->date_debut)->format('d/m/Y') ?? '—' }}
                </div>
            </div>
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Horaires</div>
                <div class="exs-hero-kpi-value">
                    <i class="far fa-clock"></i>{{ optional($examen->date_debut)->format('H:i') }}–{{ optional($examen->date_fin)->format('H:i') }}
                </div>
            </div>
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Durée</div>
                <div class="exs-hero-kpi-value">
                    <i class="fas fa-hourglass-half"></i>{{ $examen->duree_minutes ?? '—' }} min
                </div>
            </div>
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Salle</div>
                <div class="exs-hero-kpi-value">
                    <i class="fas fa-door-open"></i>{{ $examen->salle ?? '—' }}
                </div>
            </div>
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Classes</div>
                <div class="exs-hero-kpi-value">
                    <i class="fas fa-chalkboard"></i>{{ $classesCount }}
                </div>
            </div>
            <div class="exs-hero-kpi">
                <div class="exs-hero-kpi-label">Coef × Barème</div>
                <div class="exs-hero-kpi-value">
                    <i class="fas fa-calculator"></i>{{ rtrim(rtrim(number_format($examen->coefficient, 2, '.', ''), '0'), '.') }} × /{{ (int) $examen->bareme }}
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ GRID 2 COLONNES ═══════════════════════════════ --}}
    <div class="exs-grid">

        {{-- COLONNE GAUCHE : Logistique + Notation --}}
        <div>
            <div class="exs-card" style="margin-bottom:1rem;">
                <div class="exs-card-header">
                    <div class="exs-card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <div class="exs-card-title">Logistique</div>
                        <div class="exs-card-subtitle">Date, horaires, salle, scope académique</div>
                    </div>
                </div>
                <div class="exs-card-body exs-card-body--flush">
                    <div class="exs-kv">
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Date début</div>
                            <div class="exs-kv-value"><i class="far fa-calendar"></i>{{ optional($examen->date_debut)->format('d/m/Y H:i') ?? '—' }}</div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Date fin</div>
                            <div class="exs-kv-value"><i class="far fa-calendar"></i>{{ optional($examen->date_fin)->format('d/m/Y H:i') ?? '—' }}</div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Durée</div>
                            <div class="exs-kv-value">{{ $examen->duree_minutes ?? '—' }} minutes</div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Salle</div>
                            <div class="exs-kv-value">
                                @if($examen->salle)
                                    <i class="fas fa-door-open"></i>{{ $examen->salle }}
                                @else
                                    <span class="exs-kv-empty">À définir</span>
                                @endif
                            </div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Semestre</div>
                            <div class="exs-kv-value">{{ $examen->semestre ? 'Semestre '.$examen->semestre : '—' }}</div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Scope</div>
                            <div class="exs-kv-value">{{ $scopeLabel }}</div>
                        </div>
                        @if($examen->parcours)
                            <div class="exs-kv-row exs-kv-row--full">
                                <div class="exs-kv-label">Parcours</div>
                                <div class="exs-kv-value">
                                    <i class="fas fa-route"></i>{{ $examen->parcours->name }}
                                    @if($examen->parcours->code) · <code>{{ $examen->parcours->code }}</code> @endif
                                </div>
                            </div>
                        @endif
                        @if($examen->anneeUniversitaire)
                            <div class="exs-kv-row exs-kv-row--full">
                                <div class="exs-kv-label">Année universitaire</div>
                                <div class="exs-kv-value"><i class="fas fa-calendar-alt"></i>{{ $examen->anneeUniversitaire->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="exs-card">
                <div class="exs-card-header">
                    <div class="exs-card-icon" style="background:linear-gradient(135deg,{{ $typeColor }},#3b7ddb);">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="exs-card-title">Notation & Configuration</div>
                        <div class="exs-card-subtitle">Coefficient, barème, anti-tampering</div>
                    </div>
                </div>
                <div class="exs-card-body exs-card-body--flush">
                    <div class="exs-kv">
                        <div class="exs-kv-row exs-kv-row--accent">
                            <div class="exs-kv-label">Coefficient</div>
                            <div class="exs-kv-value"><code>×{{ rtrim(rtrim(number_format($examen->coefficient, 2, '.', ''), '0'), '.') }}</code></div>
                        </div>
                        <div class="exs-kv-row exs-kv-row--accent">
                            <div class="exs-kv-label">Barème</div>
                            <div class="exs-kv-value"><code>/{{ (int) $examen->bareme }}</code></div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Anonymisation copies</div>
                            <div class="exs-kv-value">
                                @if($examen->is_anonymous)
                                    <i class="fas fa-mask" style="color:#0453cb;"></i> Activée
                                @else
                                    <span class="exs-kv-empty">Désactivée</span>
                                @endif
                            </div>
                        </div>
                        <div class="exs-kv-row">
                            <div class="exs-kv-label">Statut</div>
                            <div class="exs-kv-value">{{ ExamenStatus::labelFor($examen->status) }}</div>
                        </div>
                        @if($examen->notes_locked)
                            <div class="exs-kv-row exs-kv-row--full" style="background: rgba(220,38,38,.04);">
                                <div class="exs-kv-label" style="color:#b91c1c;">🔒 Notes verrouillées</div>
                                <div class="exs-kv-value" style="color:#991b1b;">
                                    Le {{ $examen->notes_locked_at?->format('d/m/Y à H:i') }}
                                    @if($examen->notesLockedBy)
                                        · par <strong>{{ $examen->notesLockedBy->name }}</strong>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($examen->description)
                <div class="exs-card" style="margin-top:1rem;">
                    <div class="exs-card-header">
                        <div class="exs-card-icon"><i class="fas fa-align-left"></i></div>
                        <div class="exs-card-title">Description / Consignes étudiants</div>
                    </div>
                    <div class="exs-description" style="margin: 0;">
                        {!! nl2br(e($examen->description)) !!}
                    </div>
                </div>
            @endif
        </div>

        {{-- COLONNE DROITE : Surveillants + Audit récent --}}
        <div>
            <div class="exs-card" style="margin-bottom:1rem;">
                <div class="exs-card-header">
                    <div class="exs-card-icon" style="background:linear-gradient(135deg,#0453cb,#3b7ddb);">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="exs-card-title">Surveillants</div>
                        <div class="exs-card-subtitle">Équipe de surveillance UEMOA</div>
                    </div>
                    <span class="exs-card-meta" x-text="`${surveillants.length} assigné${surveillants.length > 1 ? 's' : ''}`">{{ $survData->count() }} assigné{{ $survData->count() > 1 ? 's' : '' }}</span>
                </div>

                <div class="exs-card-body" style="padding: .55rem 0 0;">
                    <template x-if="surveillants.length === 0">
                        <div class="exs-empty">
                            <i class="fas fa-user-shield"></i>
                            Aucun surveillant assigné
                        </div>
                    </template>
                    <div class="exs-surv-list" x-show="surveillants.length > 0" x-cloak>
                        <template x-for="s in surveillants" :key="s.id">
                            <div class="exs-surv-card">
                                <div class="exs-surv-avatar" x-text="s.user_initial"></div>
                                <div class="exs-surv-body">
                                    <div class="exs-surv-name" x-text="s.user_name"></div>
                                    <div class="exs-surv-email" x-text="s.user_email"></div>
                                </div>
                                <div class="exs-surv-right">
                                    <span class="exs-surv-role" :class="'exs-surv-role--' + s.role" x-text="s.role_label"></span>
                                    <span class="exs-surv-status" :class="s.confirmed ? 'exs-surv-status--confirmed' : 'exs-surv-status--pending'"
                                          x-text="s.confirmed ? '✓ Confirmé' : '⏳ En attente'"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    @can('lmd.examens.manage')
                    @if(! $examen->notes_locked)
                    <div class="exs-surv-add">
                        <div class="exs-surv-add-title"><i class="fas fa-plus-circle"></i> Ajouter un surveillant</div>
                        <form @submit.prevent="assignSurveillant()" class="exs-surv-add-form">
                            <x-au-select name="user_id"
                                :options="$surveillantOptions"
                                icon="fa-user"
                                placeholder="— Choisir un surveillant —"
                                :searchable="count($surveillantOptions) > 8" />
                            <x-au-select name="role"
                                value="surveillant"
                                :options="[
                                    'surveillant' => 'Surveillant',
                                    'surveillant_principal' => 'Surveillant principal',
                                    'secretaire' => 'Secrétaire',
                                    'responsable_salle' => 'Responsable salle',
                                ]"
                                icon="fa-user-tag"
                                :placeholderIsFirstOption="false" />
                            <button type="submit" class="exs-btn exs-btn--primary" :disabled="assigning" style="justify-content:center;">
                                <i class="fas" :class="assigning ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                                <span x-text="assigning ? 'Ajout en cours…' : 'Ajouter au jury'"></span>
                            </button>
                        </form>
                    </div>
                    @endif
                    @endcan
                </div>
            </div>

            @if($recentAudits->isNotEmpty())
                <div class="exs-card">
                    <div class="exs-card-header">
                        <div class="exs-card-icon" style="background:linear-gradient(135deg,#475569,#64748b);">
                            <i class="fas fa-history"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="exs-card-title">Historique récent</div>
                            <div class="exs-card-subtitle">5 dernières actions</div>
                        </div>
                    </div>
                    <div class="exs-timeline">
                        @foreach($recentAudits as $audit)
                            <div class="exs-timeline-item">
                                <span class="exs-timeline-dot exs-timeline-dot--{{ $audit->event }}">
                                    @switch($audit->event)
                                        @case('created') <i class="fas fa-plus"></i> @break
                                        @case('updated') <i class="fas fa-pen"></i> @break
                                        @case('deleted') <i class="fas fa-trash"></i> @break
                                        @case('restored') <i class="fas fa-undo"></i> @break
                                        @default <i class="fas fa-eye"></i>
                                    @endswitch
                                </span>
                                <div class="exs-timeline-body">
                                    <div class="exs-timeline-meta">
                                        <strong>{{ $eventLabels[$audit->event] ?? ucfirst($audit->event) }}</strong>
                                        par <strong>{{ $audit->user?->name ?? 'Système' }}</strong>
                                        <time>· {{ $audit->created_at->diffForHumans() }}</time>
                                    </div>
                                    @can('security.audit.view')
                                        <a href="{{ route('esbtp.audit.show', $audit->id) }}" class="exs-timeline-link">
                                            Voir détail <i class="fas fa-arrow-right"></i>
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════ COHORTE FULL-WIDTH ═══════════════════════════════ --}}
    @if($classesCount > 0)
        <div class="exs-card" style="margin-bottom:1rem;">
            <div class="exs-card-header">
                <div class="exs-card-icon"><i class="fas fa-users"></i></div>
                <div style="flex:1;">
                    <div class="exs-card-title">Cohorte — Classes concernées</div>
                    <div class="exs-card-subtitle">Cliquer une classe pour ouvrir sa fiche</div>
                </div>
                <span class="exs-card-meta">{{ $classesCount }} classe{{ $classesCount > 1 ? 's' : '' }} · {{ $scopeLabel }}</span>
            </div>
            <div class="exs-card-body">
                <div class="exs-classes-grid">
                    @foreach($allClasses as $cls)
                        <a href="{{ route('esbtp.classes.show', $cls->id) }}" class="exs-classe-card">
                            <span class="exs-classe-icon"><i class="fas fa-chalkboard"></i></span>
                            <div class="exs-classe-body">
                                <div class="exs-classe-name">{{ $cls->name }}</div>
                                <div class="exs-classe-meta">
                                    @if($cls->filiere?->name)
                                        {{ $cls->filiere->name }}
                                    @endif
                                    @if($cls->niveau?->name)
                                        · {{ $cls->niveau->name }}
                                    @endif
                                </div>
                            </div>
                            <x-systeme-chip
                                :systeme="strtoupper((string) ($cls->systeme_academique ?? 'BTS')) === 'LMD' ? 'LMD' : 'BTS'"
                                size="sm" />
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════ FOOTER ACTIONS ═══════════════════════════════ --}}
    <div class="exs-footer">
        <div class="exs-footer-meta">
            <div>
                Créé le <strong>{{ $examen->created_at?->format('d/m/Y à H:i') }}</strong>
                @if($examen->createdBy)
                    par <strong>{{ $examen->createdBy->name }}</strong>
                @endif
            </div>
            @if($examen->updated_at && $examen->updated_at->ne($examen->created_at))
                <div>Modifié <strong>{{ $examen->updated_at->diffForHumans() }}</strong></div>
            @endif
        </div>

        @can('lmd.examens.notes_lock')
            @if(! $examen->notes_locked && $examen->status === 'completed')
                <button type="button" class="exs-btn exs-btn--warning" @click="lockNotes()" :disabled="locking">
                    <i class="fas" :class="locking ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
                    <span x-text="locking ? 'Verrouillage…' : 'Verrouiller les notes'"></span>
                </button>
            @endif
        @endcan
        @can('lmd.examens.manage')
            @if(! $examen->notes_locked)
                <form method="POST" action="{{ route('esbtp.examens.destroy', $examen) }}" style="display:inline;"
                      onsubmit="return confirm('Supprimer définitivement cet examen ? Cette action est tracée dans l\'audit.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="exs-btn exs-btn--danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            @endif
        @endcan
    </div>

    {{-- ═══════════════════════════════ TOASTS ═══════════════════════════════ --}}
    <div class="exs-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="exs-toast" :class="'exs-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : (t.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info')"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

</div>

@push('scripts')
<script>
function examenShow() {
    return {
        surveillants: @json($survData),
        locking: false,
        assigning: false,
        toasts: [],
        toastId: 0,

        init() {
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
        },

        async assignSurveillant() {
            const userId = document.querySelector('select[name="user_id"]')?.value;
            const role = document.querySelector('select[name="role"]')?.value || 'surveillant';
            if (! userId) {
                this.pushToast({ type: 'error', message: 'Sélectionnez un surveillant.' });
                return;
            }
            this.assigning = true;
            try {
                const res = await fetch('{{ route('esbtp.examens.surveillants.assign', $examen) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ user_ids: [parseInt(userId)], role }),
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                // Enrichir les données avec role_label + user_initial pour cohérence côté client
                const roleLabels = { surveillant: 'Surveillant', surveillant_principal: 'Surveillant principal', secretaire: 'Secrétaire', responsable_salle: 'Responsable salle' };
                this.surveillants = data.surveillants.map(s => ({
                    ...s,
                    role_label: roleLabels[s.role] || s.role,
                    user_initial: (s.user_name || '?').charAt(0).toUpperCase(),
                    user_email: s.user_email || '',
                }));
                // Reset le picker
                const userSel = document.querySelector('select[name="user_id"]');
                if (userSel) { userSel.value = ''; userSel.dispatchEvent(new Event('change', { bubbles: true })); }
                this.pushToast({ type: 'success', message: 'Surveillant ajouté à l\'équipe.' });
            } catch (e) {
                this.pushToast({ type: 'error', message: e.message });
            } finally {
                this.assigning = false;
            }
        },

        async lockNotes() {
            if (!confirm('⚠️ Verrouiller les notes ?\n\nCette action est IRRÉVERSIBLE (anti-tampering UEMOA) :\n• Les notes ne pourront plus être modifiées\n• L\'examen sera marqué notes_locked = true\n• Un événement audit sera créé')) return;
            this.locking = true;
            try {
                const res = await fetch('{{ route('esbtp.examens.lock-notes', $examen) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.pushToast({ type: 'success', message: 'Notes verrouillées. Anti-tampering UEMOA activé.' });
                setTimeout(() => window.location.reload(), 900);
            } catch (e) {
                this.pushToast({ type: 'error', message: e.message });
            } finally {
                this.locking = false;
            }
        },

        pushToast(detail) {
            const id = ++this.toastId;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
@endsection
