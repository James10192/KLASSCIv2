@extends('layouts.app')

@section('title', 'Détail de la note - KLASSCI')

@push('styles')
<style>
    /* ══ Notes — Détail (namespace ns-*) ══════════════════════════ */
    .ns-page { padding-bottom: 2rem; }

    /* Hero */
    .ns-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .ns-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .ns-hero-left { display: flex; align-items: center; gap: 1rem; }
    .ns-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .ns-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .ns-hero-sub { color: rgba(255,255,255,.72); font-size: .88rem; margin: .15rem 0 0; }
    .ns-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }
    .ns-btn {
        display: inline-flex; align-items: center; gap: .45rem;
        border-radius: 10px; padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; border: 1px solid transparent; cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
    }
    .ns-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
    .ns-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .ns-btn--white { background: #fff; color: #0453cb; }
    .ns-btn--white:hover { background: #eef4ff; color: #033a8e; }

    /* KPIs hero */
    .ns-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .ns-kpi {
        flex: 1; min-width: 130px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
    }
    .ns-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .ns-kpi-label { font-size: .68rem; color: rgba(255,255,255,.65); margin-top: .2rem; text-transform: uppercase; letter-spacing: .5px; }

    /* Cards */
    .ns-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
    .ns-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .ns-card-head { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
    .ns-card-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem;
    }
    .ns-card-title { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
    .ns-card-body { padding: 1.1rem 1.25rem; }

    .ns-info { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed #eef2f7; }
    .ns-info:last-child { border-bottom: none; }
    .ns-info-label { font-size: .76rem; color: #64748b; font-weight: 600; }
    .ns-info-value { font-size: .85rem; color: #1e293b; font-weight: 600; text-align: right; }

    /* Note showcase */
    .ns-score-wrap { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
    .ns-score {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        width: 120px; height: 120px; border-radius: 18px; flex-shrink: 0;
        border: 2px solid; background: #f8fafc;
    }
    .ns-score--good { border-color: #10b981; color: #047857; background: rgba(16,185,129,.06); }
    .ns-score--mid  { border-color: #f59e0b; color: #b45309; background: rgba(245,158,11,.06); }
    .ns-score--bad  { border-color: #dc2626; color: #b91c1c; background: rgba(220,38,38,.06); }
    .ns-score--absent { border-color: #94a3b8; color: #475569; background: #f1f5f9; }
    .ns-score-num { font-size: 2rem; font-weight: 800; line-height: 1; }
    .ns-score-den { font-size: .8rem; font-weight: 600; opacity: .7; margin-top: .15rem; }
    .ns-score-meta { display: flex; flex-direction: column; gap: .5rem; }
    .ns-badge { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 700; padding: .3rem .7rem; border-radius: 8px; width: fit-content; }
    .ns-badge--good { background: rgba(16,185,129,.1); color: #047857; }
    .ns-badge--mid  { background: rgba(245,158,11,.12); color: #b45309; }
    .ns-badge--bad  { background: rgba(220,38,38,.1); color: #b91c1c; }
    .ns-badge--absent { background: #f1f5f9; color: #475569; }
    .ns-sur20 { font-size: .82rem; color: #64748b; }
    .ns-sur20 strong { color: #0453cb; }

    .ns-comment {
        margin-top: 1rem; padding: .85rem 1rem; border-radius: 10px;
        background: rgba(4,83,203,.04); border-left: 3px solid #0453cb; color: #334155; font-size: .85rem;
    }
    .ns-meta-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-top: 1rem; }
    .ns-meta-item { display: flex; align-items: center; gap: .5rem; padding: .65rem .85rem; background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; font-size: .82rem; min-width: 0; }
    .ns-meta-item > i { color: #94a3b8; font-size: .85rem; flex-shrink: 0; }
    .ns-meta-label { color: #64748b; font-weight: 600; white-space: nowrap; }
    .ns-meta-label::after { content: ':'; }
    .ns-meta-value { color: #1e293b; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .ns-footer-actions { display: flex; justify-content: space-between; gap: .75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #eef2f7; flex-wrap: wrap; }
    .ns-btn--danger { background: #fff; color: #b91c1c; border-color: rgba(220,38,38,.3); }
    .ns-btn--danger:hover { background: rgba(220,38,38,.06); }
    .ns-btn--success { background: #0453cb; color: #fff; }
    .ns-btn--success:hover { background: #033a8e; color: #fff; }

    @media (max-width: 768px) {
        .ns-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .ns-grid, .ns-meta-row { grid-template-columns: 1fr; }
        .ns-hero-actions { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $_bareme = (float) ($note->evaluation->bareme ?: 20);
    $_sur20 = (!$note->is_absent && $_bareme > 0) ? round(((float) $note->note * 20) / $_bareme, 2) : null;
    $_tone = $note->is_absent ? 'absent' : ($_sur20 === null ? 'absent' : ($_sur20 >= 12 ? 'good' : ($_sur20 >= 10 ? 'mid' : 'bad')));
    $_prenoms = $note->etudiant->prenoms ?? ($note->etudiant->prenom ?? '');
    $_mention = $note->is_absent ? 'Absent' : ($note->mention ?? '—');
@endphp
<div class="dashboard-acasi">
    <div class="main-content ns-page">

        {{-- ══ HERO ══════════════════════════════════════════════ --}}
        <div class="ns-hero">
            <div class="ns-hero-top">
                <div class="ns-hero-left">
                    <div class="ns-hero-icon"><i class="fas fa-star"></i></div>
                    <div>
                        <h1>Détail de la note</h1>
                        <p class="ns-hero-sub">
                            {{ $note->etudiant->nom }} {{ $_prenoms }}
                            @if($note->evaluation) · {{ $note->evaluation->titre }} @endif
                        </p>
                    </div>
                </div>
                <div class="ns-hero-actions">
                    @if($note->evaluation)
                    <a href="{{ route('esbtp.evaluations.show', $note->evaluation) }}" class="ns-btn ns-btn--glass">
                        <i class="fas fa-arrow-left"></i>Retour à l'évaluation
                    </a>
                    @endif
                    @can('notes.edit')
                    <a href="{{ route('esbtp.notes.edit', $note) }}" class="ns-btn ns-btn--white">
                        <i class="fas fa-edit"></i>Modifier
                    </a>
                    @endcan
                </div>
            </div>

            <div class="ns-kpis">
                <div class="ns-kpi">
                    <div class="ns-kpi-value">
                        @if($note->is_absent) Absent @else {{ rtrim(rtrim(number_format((float)$note->note, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($_bareme, 2), '0'), '.') }} @endif
                    </div>
                    <div class="ns-kpi-label">Note brute</div>
                </div>
                <div class="ns-kpi">
                    <div class="ns-kpi-value">{{ $_sur20 !== null ? number_format($_sur20, 2) : '—' }}</div>
                    <div class="ns-kpi-label">Note / 20</div>
                </div>
                <div class="ns-kpi">
                    <div class="ns-kpi-value" style="font-size:1.05rem;">{{ $_mention }}</div>
                    <div class="ns-kpi-label">Mention</div>
                </div>
                <div class="ns-kpi">
                    <div class="ns-kpi-value">{{ $note->evaluation ? ucfirst($note->evaluation->type) : '—' }}</div>
                    <div class="ns-kpi-label">Type d'évaluation</div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ══ CARDS Évaluation + Étudiant ══════════════════════ --}}
        <div class="ns-grid">
            <div class="ns-card">
                <div class="ns-card-head">
                    <div class="ns-card-icon"><i class="fas fa-file-alt"></i></div>
                    <h2 class="ns-card-title">Évaluation</h2>
                </div>
                <div class="ns-card-body">
                    @if($note->evaluation)
                    <div class="ns-info"><span class="ns-info-label">Titre</span><span class="ns-info-value">{{ $note->evaluation->titre }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Type</span><span class="ns-info-value">{{ ucfirst($note->evaluation->type) }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Date</span><span class="ns-info-value">{{ $note->evaluation->date_evaluation ? \Carbon\Carbon::parse($note->evaluation->date_evaluation)->format('d/m/Y') : '—' }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Classe</span><span class="ns-info-value">{{ optional($note->evaluation->classe)->name ?? '—' }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Matière</span><span class="ns-info-value">{{ optional($note->evaluation->matiere)->name ?? '—' }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Barème</span><span class="ns-info-value">{{ rtrim(rtrim(number_format($_bareme, 2), '0'), '.') }} points</span></div>
                    @else
                    <div class="ns-info"><span class="ns-info-label">Évaluation</span><span class="ns-info-value">Aucune évaluation liée</span></div>
                    @endif
                </div>
            </div>

            <div class="ns-card">
                <div class="ns-card-head">
                    <div class="ns-card-icon" style="background:linear-gradient(135deg,#3b7ddb,#5e91de);"><i class="fas fa-user-graduate"></i></div>
                    <h2 class="ns-card-title">Étudiant</h2>
                </div>
                <div class="ns-card-body">
                    <div class="ns-info"><span class="ns-info-label">Matricule</span><span class="ns-info-value">{{ $note->etudiant->matricule }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Nom</span><span class="ns-info-value">{{ $note->etudiant->nom }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Prénoms</span><span class="ns-info-value">{{ $_prenoms ?: '—' }}</span></div>
                    <div class="ns-info"><span class="ns-info-label">Classe</span><span class="ns-info-value">{{ optional($note->etudiant->classe)->name ?? optional($note->classe)->name ?? '—' }}</span></div>
                    <div class="ns-info">
                        <span class="ns-info-label">Statut</span>
                        <span class="ns-info-value">
                            @if($note->etudiant->active ?? true)
                                <span class="ns-badge ns-badge--good">Actif</span>
                            @else
                                <span class="ns-badge ns-badge--bad">Inactif</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ CARD Note ════════════════════════════════════════ --}}
        <div class="ns-card">
            <div class="ns-card-head">
                <div class="ns-card-icon"><i class="fas fa-award"></i></div>
                <h2 class="ns-card-title">Résultat</h2>
            </div>
            <div class="ns-card-body">
                <div class="ns-score-wrap">
                    <div class="ns-score ns-score--{{ $_tone }}">
                        @if($note->is_absent)
                            <i class="fas fa-user-slash" style="font-size:1.6rem;"></i>
                            <span class="ns-score-den" style="margin-top:.4rem;">Absent</span>
                        @else
                            <span class="ns-score-num">{{ rtrim(rtrim(number_format((float)$note->note, 2), '0'), '.') }}</span>
                            <span class="ns-score-den">/ {{ rtrim(rtrim(number_format($_bareme, 2), '0'), '.') }}</span>
                        @endif
                    </div>
                    <div class="ns-score-meta">
                        <span class="ns-badge ns-badge--{{ $_tone }}">
                            <i class="fas fa-medal"></i>{{ $_mention }}
                        </span>
                        @unless($note->is_absent)
                        <div class="ns-sur20">Sur 20 : <strong>{{ $_sur20 !== null ? number_format($_sur20, 2) : '—' }}/20</strong></div>
                        @endunless
                        <div class="ns-sur20">Saisie le {{ $note->created_at?->format('d/m/Y à H:i') }}</div>
                        @if($note->updated_at && $note->updated_at != $note->created_at)
                        <div class="ns-sur20">Modifiée le {{ $note->updated_at->format('d/m/Y à H:i') }}</div>
                        @endif
                    </div>
                </div>

                @if($note->commentaire)
                <div class="ns-comment">
                    <i class="fas fa-quote-left" style="color:#0453cb; margin-right:.4rem;"></i>{{ $note->commentaire }}
                </div>
                @endif

                <div class="ns-meta-row">
                    <div class="ns-meta-item">
                        <i class="fas fa-user"></i>
                        <span class="ns-meta-label">Créé par</span>
                        <span class="ns-meta-value">{{ optional($note->createdBy)->name ?? '—' }}</span>
                    </div>
                    @if($note->updatedBy)
                    <div class="ns-meta-item">
                        <i class="fas fa-user-edit"></i>
                        <span class="ns-meta-label">Mis à jour par</span>
                        <span class="ns-meta-value">{{ $note->updatedBy->name }}</span>
                    </div>
                    @endif
                </div>

                <div class="ns-footer-actions">
                    @can('notes.delete')
                    <button type="button" class="ns-btn ns-btn--danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash"></i>Supprimer
                    </button>
                    @endcan
                    @can('notes.edit')
                    <a href="{{ route('esbtp.notes.edit', $note) }}" class="ns-btn ns-btn--success">
                        <i class="fas fa-edit"></i>Modifier cette note
                    </a>
                    @endcan
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal de suppression --}}
{{-- EXCEPTION ajax-no-reload-premium : la suppression retire l'entité affichée, donc
     une navigation post-action (redirect vers l'évaluation) est justifiée (migration page). --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette note ?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action est irréversible et peut affecter les moyennes et bulletins.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ns-btn ns-btn--glass" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0;" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Annuler
                </button>
                <form action="{{ route('esbtp.notes.destroy', $note) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ns-btn ns-btn--danger">
                        <i class="fas fa-trash"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
