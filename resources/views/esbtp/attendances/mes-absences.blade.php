@extends('layouts.app')

@section('title', 'Mes absences')

@push('styles')
<style>
/* =========================================================
   MES ABSENCES — Premium (namespace ja-*)
   Etudiant : visualisation + justification d'absences
   Design system KLASSCI monochrome bleu.
   ========================================================= */
[x-cloak] { display: none !important; }

.ja-page { padding: 0 0 2rem; }

/* ----- HERO (planning-header pattern) ----- */
.ja-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ja-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.ja-hero-left { display: flex; align-items: center; gap: 1rem; }
.ja-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff; flex-shrink: 0;
}
.ja-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.ja-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; line-height: 1.4; }
.ja-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.ja-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
    transition: background .15s, transform .15s;
}
.ja-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }

/* ----- KPIs ----- */
.ja-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.ja-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.ja-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem; flex-shrink: 0;
}
.ja-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.ja-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .15rem; }

/* ----- Filter chips ----- */
.ja-filters {
    display: flex; gap: .5rem; flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: .75rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1rem;
}
.ja-chip {
    padding: .5rem .9rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: .8rem; font-weight: 600;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: all .15s;
}
.ja-chip:hover { border-color: #0453cb; color: #0453cb; }
.ja-chip--active {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.ja-chip-count {
    display: inline-block; min-width: 22px; padding: 0 .4rem;
    border-radius: 999px;
    background: rgba(255,255,255,.25);
    font-size: .68rem; font-weight: 700;
    text-align: center;
}
.ja-chip:not(.ja-chip--active) .ja-chip-count { background: #f1f5f9; color: #475569; }

/* ----- Card de l'absence ----- */
.ja-list { display: flex; flex-direction: column; gap: .75rem; }
.ja-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    transition: box-shadow .2s, transform .2s;
}
.ja-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    transform: translateY(-1px);
}
.ja-card-top {
    display: flex; align-items: flex-start;
    justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.ja-card-left {
    display: flex; align-items: flex-start; gap: .9rem;
    min-width: 0; flex: 1;
}
.ja-date-block {
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.10));
    border: 1px solid rgba(4,83,203,.16);
    border-radius: 12px;
    padding: .55rem .7rem .65rem;
    text-align: center; min-width: 64px; flex-shrink: 0;
}
.ja-date-block-day { font-size: 1.45rem; font-weight: 700; color: #0453cb; line-height: 1; }
.ja-date-block-mois { font-size: .65rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-top: .15rem; }
.ja-date-block-year { font-size: .65rem; color: #94a3b8; margin-top: .1rem; }

.ja-card-info { min-width: 0; }
.ja-card-matiere {
    font-size: 1rem; font-weight: 700; color: #1e293b;
    margin: 0 0 .2rem;
    overflow: hidden; text-overflow: ellipsis;
}
.ja-card-meta {
    display: flex; gap: .9rem; flex-wrap: wrap;
    font-size: .75rem; color: #64748b;
}
.ja-card-meta i { color: #94a3b8; }

.ja-card-right { display: flex; flex-direction: column; align-items: flex-end; gap: .5rem; }

/* Badges statut */
.ja-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .65rem;
    border-radius: 8px;
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .3px;
}
.ja-badge--muted     { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.ja-badge--warning   { background: rgba(245,158,11,.10); color: #b45309; border: 1px solid rgba(245,158,11,.30); }
.ja-badge--success   { background: rgba(16,185,129,.10); color: #047857; border: 1px solid rgba(16,185,129,.30); }
.ja-badge--danger    { background: rgba(220,38,38,.08); color: #b91c1c; border: 1px solid rgba(220,38,38,.30); }

.ja-card-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.ja-action-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .45rem .85rem;
    border-radius: 9px;
    font-size: .78rem; font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer; transition: all .15s;
    text-decoration: none;
}
.ja-action-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border: none;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.ja-action-btn--primary:hover { transform: translateY(-1px); color: #fff; box-shadow: 0 4px 12px rgba(4,83,203,.35); }
.ja-action-btn--ghost {
    background: #fff; color: #0453cb; border-color: #0453cb;
}
.ja-action-btn--ghost:hover { background: rgba(4,83,203,.05); color: #0453cb; }

.ja-admin-comment {
    margin-top: .75rem;
    padding: .7rem .85rem;
    border-radius: 10px;
    background: rgba(220,38,38,.04);
    border: 1px solid rgba(220,38,38,.18);
    border-left: 3px solid #dc2626;
    color: #7f1d1d;
    font-size: .82rem;
    display: flex; gap: .5rem; align-items: flex-start;
}
.ja-admin-comment i { color: #dc2626; flex-shrink: 0; margin-top: .15rem; }

.ja-doc-link {
    display: inline-flex; align-items: center; gap: .4rem;
    margin-top: .5rem;
    padding: .4rem .6rem;
    border-radius: 8px;
    background: rgba(4,83,203,.06);
    color: #0453cb;
    font-size: .75rem; font-weight: 600;
    text-decoration: none;
    border: 1px dashed rgba(4,83,203,.30);
}
.ja-doc-link:hover { background: rgba(4,83,203,.12); color: #0453cb; }

/* ----- Empty state ----- */
.ja-empty {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 3rem 1.5rem;
    text-align: center;
    color: #94a3b8;
}
.ja-empty-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.12));
    color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.ja-empty h3 { font-size: 1rem; color: #1e293b; margin: 0 0 .35rem; font-weight: 700; }
.ja-empty p { font-size: .85rem; color: #64748b; margin: 0; }

/* ----- Modal upload (Alpine 3) ----- */
.ja-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(2px);
    z-index: 1050;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.ja-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%; max-width: 540px;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(15,23,42,.30);
}
.ja-modal-header {
    background: linear-gradient(135deg, #0a3d8f, #0453cb 70%);
    color: #fff;
    padding: 1.25rem 1.5rem;
    border-radius: 16px 16px 0 0;
    display: flex; align-items: center; gap: .75rem;
}
.ja-modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #fff; }
.ja-modal-header-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
}
.ja-modal-body { padding: 1.5rem; }
.ja-modal-row { margin-bottom: 1rem; }
.ja-modal-row label { font-size: .82rem; font-weight: 600; color: #334155; margin-bottom: .35rem; display: block; }
.ja-modal-row textarea, .ja-modal-row input[type=file] {
    width: 100%;
    padding: .65rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .9rem;
    color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.ja-modal-row textarea:focus, .ja-modal-row input[type=file]:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ja-modal-row .ja-hint { font-size: .72rem; color: #94a3b8; margin-top: .3rem; }
.ja-modal-context {
    background: rgba(4,83,203,.04);
    border: 1px solid rgba(4,83,203,.15);
    border-radius: 10px;
    padding: .75rem .9rem;
    margin-bottom: 1rem;
    font-size: .85rem; color: #334155;
}
.ja-modal-context strong { color: #0453cb; }
.ja-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex; gap: .5rem; justify-content: flex-end;
}
.ja-btn--secondary {
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .55rem 1.1rem;
    font-size: .85rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.ja-btn--secondary:hover { background: #e2e8f0; }
.ja-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border: none;
    border-radius: 10px;
    padding: .55rem 1.3rem;
    font-size: .85rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.ja-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.35); }

/* Mobile */
@media (max-width: 768px) {
    .ja-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .ja-hero h1 { font-size: 1.2rem; }
    .ja-kpis { gap: .5rem; }
    .ja-kpi { min-width: calc(50% - .25rem); }
    .ja-card-top { flex-direction: column; align-items: stretch; }
    .ja-card-right { align-items: flex-start; }
    .ja-modal { max-width: 95vw; }
}
@media (max-width: 576px) {
    .ja-kpi { min-width: 100%; }
    .ja-chip { font-size: .72rem; padding: .4rem .7rem; }
}
</style>
@endpush

@section('content')
<div class="ja-page"
     x-data="mesAbsencesPage()"
     x-cloak>
    {{-- HERO --}}
    <div class="ja-hero">
        <div class="ja-hero-top">
            <div class="ja-hero-left">
                <div class="ja-hero-icon"><i class="fas fa-file-medical"></i></div>
                <div>
                    <h1>Mes absences</h1>
                    <p>
                        Consultez vos absences et justifiez-les en joignant un certificat médical ou tout autre document
                        @if(!empty($anneeCourante))
                            — Année universitaire <strong>{{ $anneeCourante->libelle ?? ('en cours') }}</strong>
                        @endif
                    </p>
                </div>
            </div>
            <div class="ja-hero-actions">
                <a href="{{ route('dashboard') }}" class="ja-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="ja-kpis">
            <div class="ja-kpi">
                <div class="ja-kpi-icon"><i class="fas fa-calendar-times"></i></div>
                <div>
                    <div class="ja-kpi-value">{{ (int) ($totalAbsences ?? 0) }}</div>
                    <div class="ja-kpi-label">Total absences</div>
                </div>
            </div>
            <div class="ja-kpi">
                <div class="ja-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="ja-kpi-value">{{ (int) ($absencesJustifiees ?? 0) }}</div>
                    <div class="ja-kpi-label">Validées</div>
                </div>
            </div>
            <div class="ja-kpi">
                <div class="ja-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="ja-kpi-value">{{ (int) ($absencesEnAttente ?? 0) }}</div>
                    <div class="ja-kpi-label">En attente</div>
                </div>
            </div>
            <div class="ja-kpi">
                <div class="ja-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div>
                    <div class="ja-kpi-value">{{ (int) ($absencesNonJustifiees ?? 0) }}</div>
                    <div class="ja-kpi-label">À justifier</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info" role="alert">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTER CHIPS --}}
    <div class="ja-filters">
        <button type="button" class="ja-chip" :class="{ 'ja-chip--active': statusFilter === 'all' }" @click="statusFilter = 'all'">
            <i class="fas fa-list"></i> Toutes <span class="ja-chip-count">{{ (int) ($totalAbsences ?? 0) }}</span>
        </button>
        <button type="button" class="ja-chip" :class="{ 'ja-chip--active': statusFilter === 'to_justify' }" @click="statusFilter = 'to_justify'">
            <i class="fas fa-exclamation-circle"></i> À justifier <span class="ja-chip-count">{{ (int) ($absencesNonJustifiees ?? 0) }}</span>
        </button>
        <button type="button" class="ja-chip" :class="{ 'ja-chip--active': statusFilter === 'pending' }" @click="statusFilter = 'pending'">
            <i class="fas fa-clock"></i> En attente <span class="ja-chip-count">{{ (int) ($absencesEnAttente ?? 0) }}</span>
        </button>
        <button type="button" class="ja-chip" :class="{ 'ja-chip--active': statusFilter === 'approved' }" @click="statusFilter = 'approved'">
            <i class="fas fa-check-circle"></i> Validées <span class="ja-chip-count">{{ (int) ($absencesJustifiees ?? 0) }}</span>
        </button>
        <button type="button" class="ja-chip" :class="{ 'ja-chip--active': statusFilter === 'rejected' }" @click="statusFilter = 'rejected'">
            <i class="fas fa-times-circle"></i> Rejetées <span class="ja-chip-count">{{ (int) ($absencesRejetees ?? 0) }}</span>
        </button>
    </div>

    {{-- LIST --}}
    @php
        $rows = $absences ?? collect();
    @endphp

    @if($rows->isEmpty())
        <div class="ja-empty">
            <div class="ja-empty-icon"><i class="fas fa-check-double"></i></div>
            <h3>Aucune absence enregistrée</h3>
            <p>Vous n'avez pas d'absence pour la période sélectionnée. Continuez comme ça !</p>
        </div>
    @else
        <div class="ja-list">
            @foreach($rows as $abs)
                @php
                    $status = $abs->justification_status;
                    $statusVal = $status instanceof \App\Enums\JustificationStatus ? $status->value : null;
                    $matiereName = optional($abs->matiere)->name ?? optional($abs->seanceCours->matiere ?? null)->name ?? '—';
                    $rowFlag = match ($statusVal) {
                        'approved' => 'approved',
                        'pending'  => 'pending',
                        'rejected' => 'rejected',
                        default    => ($abs->statut === 'excuse' ? 'approved' : 'to_justify'),
                    };
                    $canSubmit = in_array($rowFlag, ['to_justify', 'rejected'], true);
                    $hasDoc = !empty($abs->document_path);
                @endphp

                <div class="ja-card"
                     x-show="statusFilter === 'all' || statusFilter === '{{ $rowFlag }}'"
                     x-transition.opacity>
                    <div class="ja-card-top">
                        <div class="ja-card-left">
                            <div class="ja-date-block">
                                <div class="ja-date-block-day">{{ optional($abs->date)->format('d') ?? '—' }}</div>
                                <div class="ja-date-block-mois">{{ optional($abs->date)->translatedFormat('M') ?? '' }}</div>
                                <div class="ja-date-block-year">{{ optional($abs->date)->format('Y') ?? '' }}</div>
                            </div>
                            <div class="ja-card-info">
                                <div class="ja-card-matiere">{{ $matiereName }}</div>
                                <div class="ja-card-meta">
                                    @if($abs->heure_debut)
                                        <span><i class="fas fa-clock"></i> {{ \Illuminate\Support\Str::limit((string) $abs->heure_debut, 5, '') }}@if($abs->heure_fin) — {{ \Illuminate\Support\Str::limit((string) $abs->heure_fin, 5, '') }}@endif</span>
                                    @endif
                                    @if($statusVal === 'pending' && $abs->justified_at)
                                        <span><i class="fas fa-paper-plane"></i> Soumis le {{ $abs->justified_at->format('d/m/Y') }}</span>
                                    @elseif($statusVal === 'approved' && $abs->processed_at)
                                        <span><i class="fas fa-check"></i> Validé le {{ $abs->processed_at->format('d/m/Y') }}</span>
                                    @elseif($statusVal === 'rejected' && $abs->processed_at)
                                        <span><i class="fas fa-times"></i> Rejeté le {{ $abs->processed_at->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="ja-card-right">
                            @if($rowFlag === 'approved')
                                <span class="ja-badge ja-badge--success"><i class="fas fa-check-circle"></i> Validée</span>
                            @elseif($rowFlag === 'pending')
                                <span class="ja-badge ja-badge--warning"><i class="fas fa-clock"></i> En attente</span>
                            @elseif($rowFlag === 'rejected')
                                <span class="ja-badge ja-badge--danger"><i class="fas fa-times-circle"></i> Rejetée</span>
                            @else
                                <span class="ja-badge ja-badge--muted"><i class="fas fa-exclamation"></i> Non justifiée</span>
                            @endif

                            <div class="ja-card-actions">
                                @if($canSubmit)
                                    <button type="button"
                                            class="ja-action-btn ja-action-btn--primary"
                                            @click="openModal(
                                                {{ $abs->id }},
                                                '{{ optional($abs->date)->format('d/m/Y') }}',
                                                @js($matiereName),
                                                {{ $rowFlag === 'rejected' ? 'true' : 'false' }},
                                                @js($abs->commentaire ?? '')
                                            )">
                                        <i class="fas fa-file-medical"></i>
                                        {{ $rowFlag === 'rejected' ? 'Re-soumettre' : 'Justifier' }}
                                    </button>
                                @endif
                                @if($hasDoc)
                                    <a class="ja-action-btn ja-action-btn--ghost"
                                       href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('esbtp.justifications.document', now()->addMinutes(5), ['absence' => $abs->id]) }}"
                                       target="_blank" rel="noopener">
                                        <i class="fas fa-eye"></i> Document
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($rowFlag === 'rejected' && !empty($abs->admin_comment))
                        <div class="ja-admin-comment">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Motif du rejet :</strong>
                                {{ $abs->admin_comment }}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- MODAL JUSTIFICATION --}}
    <div class="ja-modal-backdrop"
         x-show="modalOpen"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="closeModal()"
         @click.self="closeModal()">
        <div class="ja-modal" @click.stop>
            <div class="ja-modal-header">
                <div class="ja-modal-header-icon"><i class="fas fa-file-medical"></i></div>
                <h3 x-text="modalContext.isResubmit ? 'Re-soumettre la justification' : 'Justifier l\'absence'"></h3>
            </div>
            <form :action="modalAction()" method="POST" enctype="multipart/form-data" @submit="submitting = true">
                @csrf
                <div class="ja-modal-body">
                    <div class="ja-modal-context">
                        Absence du <strong x-text="modalContext.date"></strong>
                        — <strong x-text="modalContext.matiere"></strong>
                    </div>
                    <div class="ja-modal-row">
                        <label>Justification <span style="color:#dc2626">*</span></label>
                        <textarea name="justification"
                                  rows="4"
                                  required
                                  minlength="5"
                                  maxlength="1000"
                                  placeholder="Expliquez le motif de votre absence (maladie, événement familial, etc.)"
                                  x-model="modalContext.justification"></textarea>
                        <div class="ja-hint">5 à 1000 caractères.</div>
                    </div>
                    <div class="ja-modal-row">
                        <label>Document justificatif (optionnel)</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="ja-hint">
                            Formats acceptés : PDF, JPG, PNG — 5 Mo maximum.
                            Recommandé : certificat médical scanné.
                        </div>
                    </div>
                </div>
                <div class="ja-modal-footer">
                    <button type="button" class="ja-btn--secondary" @click="closeModal()" :disabled="submitting">
                        Annuler
                    </button>
                    <button type="submit" class="ja-btn--primary" :disabled="submitting">
                        <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                        <span x-text="submitting ? 'Envoi...' : (modalContext.isResubmit ? 'Re-soumettre' : 'Soumettre')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mesAbsencesPage() {
    return {
        statusFilter: 'all',
        modalOpen: false,
        submitting: false,
        modalContext: { id: null, date: '', matiere: '', isResubmit: false, justification: '' },
        baseJustifyUrl: @json(rtrim(route('esbtp.mes-absences.index'), '/')),
        openModal(id, date, matiere, isResubmit, oldComment) {
            this.modalContext = {
                id: id,
                date: date,
                matiere: matiere,
                isResubmit: !!isResubmit,
                justification: isResubmit ? (oldComment || '') : ''
            };
            this.modalOpen = true;
            this.submitting = false;
        },
        closeModal() {
            if (this.submitting) return;
            this.modalOpen = false;
        },
        modalAction() {
            return this.baseJustifyUrl + '/' + this.modalContext.id + '/justify';
        }
    };
}
</script>
@endpush
