@extends('layouts.app')

@section('title', 'Gestion des notes - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ══════════════════════════════════════════════
   Teacher Grades — Premium
   Prefix: tg- (teacher-grades)
   Aligned with planning-header / KLASSCI design system
   ══════════════════════════════════════════════ */
.tg-wrap {
    padding: 1.5rem;
    max-width: 100%;
    overflow-x: hidden;
}

/* ───────── HERO ───────── */
.tg-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
    animation: tg-fade-down .5s ease-out;
}
@keyframes tg-fade-down {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.tg-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.tg-hero-left { display: flex; align-items: center; gap: 1rem; }

.tg-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff; flex-shrink: 0;
}

.tg-hero-info h1 {
    font-size: 1.45rem;
    font-weight: 700;
    margin: 0 0 .2rem;
    color: #fff;
    letter-spacing: -.02em;
}
.tg-hero-info p { margin: 0; opacity: .75; font-size: .88rem; }

.tg-hero-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .5rem;
}

.tg-badge-year {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .8rem;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 10px;
    font-size: .8rem; font-weight: 600;
    color: rgba(255, 255, 255, .92);
}
.tg-badge-year i { opacity: .8; }

.tg-btn {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem 1rem;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    border: 1px solid rgba(255, 255, 255, .2);
    cursor: pointer;
    line-height: 1;
}
.tg-btn--glass {
    background: rgba(255, 255, 255, .15);
    color: #fff;
}
.tg-btn--glass:hover { background: rgba(255, 255, 255, .22); color: #fff; }

.tg-btn--white {
    background: #fff;
    color: #0453cb;
    border-color: transparent;
}
.tg-btn--white:hover { background: #f0f4ff; color: #033a8e; }

/* KPIs in hero (row 2) */
.tg-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
    margin-top: 1.5rem;
    position: relative;
}

.tg-kpi {
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: background .2s ease, transform .2s ease;
}
.tg-kpi:hover { background: rgba(255, 255, 255, .15); transform: translateY(-1px); }

.tg-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    background: rgba(255, 255, 255, .18);
    color: #fff;
}
.tg-kpi--pub    .tg-kpi-icon { background: rgba(255, 255, 255, .22); }
.tg-kpi--draft  .tg-kpi-icon { background: rgba(255, 255, 255, .14); color: rgba(255, 255, 255, .85); }
.tg-kpi--recent .tg-kpi-icon { background: rgba(255, 255, 255, .22); }

.tg-kpi-body { min-width: 0; }
.tg-kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}
.tg-kpi-label {
    font-size: .72rem;
    color: rgba(255, 255, 255, .65);
    margin-top: .2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ───────── LAYOUT ───────── */
.tg-layout {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* ───────── CARDS ───────── */
.tg-card {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
    overflow: hidden;
}

.tg-card-head {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.tg-card-head-left {
    display: flex;
    align-items: center;
    gap: .85rem;
}
.tg-card-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    box-shadow: 0 4px 12px rgba(4, 83, 203, .25);
}
.tg-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -.01em;
}
.tg-card-sub {
    margin: .1rem 0 0;
    font-size: .8rem;
    color: #64748b;
}
.tg-card-body { padding: 1.25rem 1.5rem 1.5rem; }

/* ───────── EVALUATION GRID ───────── */
.tg-eval-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}

.tg-eval {
    position: relative;
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 14px;
    padding: 1.15rem 1.2rem 1.05rem;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.tg-eval::after {
    content: '';
    position: absolute;
    left: 0; top: 14px; bottom: 14px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #0453cb, #3b7ddb);
    opacity: .55;
    transition: opacity .2s ease;
}
.tg-eval:hover {
    border-color: rgba(4, 83, 203, .35);
    box-shadow: 0 8px 24px rgba(4, 83, 203, .1), 0 2px 6px rgba(15, 23, 42, .04);
    transform: translateY(-2px);
}
.tg-eval:hover::after { opacity: 1; }

.tg-eval-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
}

/* Monochrome bleu tonal — l'icône distingue les types, pas la couleur */
.tg-eval-type {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .32rem .65rem;
    border-radius: 999px;
    background: rgba(4, 83, 203, .08);
    border: 1px solid rgba(4, 83, 203, .18);
    color: #033a8e;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.tg-eval-type i { font-size: .78rem; color: #0453cb; }
.tg-eval-type--examen     { background: rgba(4, 83, 203, .14); border-color: rgba(4, 83, 203, .3); }
.tg-eval-type--rattrapage { background: rgba(4, 83, 203, .12); border-style: dashed; }
.tg-eval-type--projet     { background: rgba(4, 83, 203, .06); }

.tg-eval-date {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .78rem;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: .28rem .55rem;
    border-radius: 8px;
}
.tg-eval-date i { font-size: .72rem; color: #64748b; }

.tg-eval-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.3;
    letter-spacing: -.005em;
}

.tg-eval-meta {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
}
.tg-eval-meta li {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .55rem;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    font-size: .75rem;
    color: #334155;
    font-weight: 500;
}
.tg-eval-meta li i { color: #0453cb; font-size: .72rem; }

.tg-eval-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .65rem;
    padding-top: .65rem;
    border-top: 1px dashed #eef2f7;
    flex-wrap: wrap;
}

.tg-eval-status { display: flex; gap: .35rem; flex-wrap: wrap; }
.tg-pill {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .28rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    line-height: 1;
}
.tg-pill i { font-size: .68rem; }
.tg-pill--ok    { background: rgba(16, 185, 129, .12); color: #047857; border: 1px solid rgba(16, 185, 129, .25); }
.tg-pill--draft { background: rgba(148, 163, 184, .14); color: #475569; border: 1px solid rgba(148, 163, 184, .3); }
.tg-pill--count { background: rgba(4, 83, 203, .08); color: #033a8e;  border: 1px solid rgba(4, 83, 203, .18); }

.tg-eval-actions {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.tg-eval-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .45rem .8rem;
    border-radius: 9px;
    font-size: .78rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    border: 1px solid transparent;
    cursor: pointer;
    line-height: 1;
}
.tg-eval-btn--ghost {
    background: #fff;
    color: #0453cb;
    border-color: rgba(4, 83, 203, .25);
}
.tg-eval-btn--ghost:hover { background: rgba(4, 83, 203, .07); border-color: rgba(4, 83, 203, .4); color: #033a8e; }

.tg-eval-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    box-shadow: 0 4px 12px rgba(4, 83, 203, .22);
}
.tg-eval-btn--primary:hover {
    background: linear-gradient(135deg, #033a8e, #2c69cf);
    box-shadow: 0 6px 16px rgba(4, 83, 203, .3);
    color: #fff;
}
.tg-eval-btn.is-disabled {
    background: #f1f5f9;
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    border-color: #e2e8f0;
}

.tg-eval-helper {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .4rem .65rem;
    border-radius: 8px;
    background: rgba(251, 191, 36, .1);
    border: 1px solid rgba(251, 191, 36, .25);
    color: #92400e;
    font-size: .74rem;
    font-weight: 500;
    width: 100%;
    margin-top: .25rem;
}
.tg-eval-helper i { color: #b45309; }

/* Flash success */
.card-flash-success { animation: tg-flash-success 1.2s ease; }
@keyframes tg-flash-success {
    0%   { box-shadow: 0 0 0 rgba(16, 185, 129, 0);   transform: translateY(-2px) scale(1.01); }
    40%  { box-shadow: 0 0 0 6px rgba(16, 185, 129, .22); }
    100% { box-shadow: 0 0 0 rgba(16, 185, 129, 0);   transform: translateY(0) scale(1); }
}

/* ───────── PAGINATION ───────── */
.tg-pagination {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}
.tg-pagination .pagination {
    margin: 0;
    justify-content: center;
    gap: .25rem;
}
.tg-pagination .page-item .page-link {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    color: #0453cb;
    font-size: .82rem;
    font-weight: 600;
    padding: .4rem .75rem;
}
.tg-pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    border-color: transparent;
    color: #fff;
}

/* ───────── RECENT NOTES ───────── */
.tg-recent {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: .55rem;
}
.tg-recent-item {
    display: grid;
    grid-template-columns: 44px 1fr auto;
    gap: .9rem;
    align-items: center;
    padding: .8rem 1rem;
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 12px;
    transition: border-color .15s ease, background .15s ease;
}
.tg-recent-item:hover { background: #f8fafc; border-color: rgba(4, 83, 203, .2); }

.tg-recent-avatar {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    font-weight: 700;
    box-shadow: 0 3px 8px rgba(4, 83, 203, .25);
}

.tg-recent-info { min-width: 0; }
.tg-recent-name {
    font-weight: 700;
    color: #0f172a;
    font-size: .9rem;
    line-height: 1.2;
    margin-bottom: .2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tg-recent-sub {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    font-size: .75rem;
    color: #64748b;
}
.tg-recent-sub span { display: inline-flex; align-items: center; gap: .3rem; }
.tg-recent-sub i { color: #0453cb; opacity: .75; font-size: .68rem; }
.tg-recent-eval { color: #475569; font-weight: 500; }

.tg-recent-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .35rem;
    flex-shrink: 0;
}

.tg-score {
    display: inline-flex;
    align-items: baseline;
    gap: .15rem;
    padding: .3rem .6rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: .9rem;
    line-height: 1;
    border: 1px solid transparent;
}
.tg-score small { font-weight: 500; font-size: .72rem; opacity: .8; }
.tg-score--pass {
    background: rgba(16, 185, 129, .12);
    color: #047857;
    border-color: rgba(16, 185, 129, .25);
}
.tg-score--fail {
    background: rgba(220, 38, 38, .1);
    color: #b91c1c;
    border-color: rgba(220, 38, 38, .22);
}
.tg-score--absent {
    background: rgba(148, 163, 184, .14);
    color: #475569;
    border-color: rgba(148, 163, 184, .3);
    font-size: .78rem;
}
.tg-score--absent i { margin-right: .3rem; }

.tg-recent-date {
    font-size: .72rem;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
}
.tg-recent-date i { font-size: .68rem; }

/* ───────── EMPTY STATES ───────── */
.tg-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    border: 1px dashed #e2e8f0;
    border-radius: 14px;
    background: linear-gradient(180deg, #f8fafc, #ffffff);
}
.tg-empty-icon {
    width: 72px; height: 72px;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(4, 83, 203, .1), rgba(59, 125, 219, .12));
    display: flex; align-items: center; justify-content: center;
    color: #0453cb;
    font-size: 1.75rem;
    border: 1px solid rgba(4, 83, 203, .15);
}
.tg-empty h3 {
    margin: 0 0 .35rem;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
}
.tg-empty p {
    margin: 0 0 1.25rem;
    font-size: .88rem;
    color: #64748b;
}
.tg-empty--small { padding: 2rem 1rem; }
.tg-empty--small h4 {
    margin: 0 0 .25rem;
    font-size: .95rem;
    font-weight: 700;
    color: #0f172a;
}
.tg-empty--small p { margin: 0; font-size: .82rem; color: #64748b; }
.tg-empty-icon--small {
    width: 56px; height: 56px;
    font-size: 1.4rem;
    border-radius: 14px;
}

.tg-btn-cta {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .65rem 1.2rem;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    font-size: .88rem;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 6px 16px rgba(4, 83, 203, .25);
    transition: all .2s ease;
}
.tg-btn-cta:hover {
    color: #fff;
    box-shadow: 0 8px 22px rgba(4, 83, 203, .35);
    transform: translateY(-1px);
}

/* ───────── MODAL preserved ───────── */
.modal-premium-shell {
    border-radius: 24px;
    border: none;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(4, 83, 203, .08), rgba(255, 255, 255, .95));
}
.modal-premium {
    padding: 2rem;
    background:
        radial-gradient(circle at top left, rgba(4, 83, 203, .08), transparent 55%),
        radial-gradient(circle at bottom right, rgba(94, 145, 222, .08), transparent 55%),
        #fff;
}
.modal-premium-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.modal-premium-title {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: .25rem;
    color: #0f172a;
}
.modal-premium-subtitle { color: #64748b; margin: 0; }
.modal-premium-badges { display: flex; gap: .5rem; flex-wrap: wrap; }
.modal-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .4rem .7rem;
    border-radius: 999px;
    background: rgba(4, 83, 203, .12);
    color: #0453cb;
    font-size: .85rem;
    border: 1px solid rgba(4, 83, 203, .2);
}
.modal-pill-neutral {
    background: rgba(59, 130, 246, .12);
    color: #1d4ed8;
    border: 1px solid rgba(59, 130, 246, .25);
}
.modal-pill-warning {
    background: rgba(245, 158, 11, .12);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, .25);
}
.modal-premium-body { display: flex; flex-direction: column; gap: 1.5rem; }
.modal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}
.modal-section {
    background: rgba(15, 23, 42, .03);
    padding: 1rem;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, .2);
}
.modal-section-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-weight: 600;
    margin-bottom: .75rem;
    color: #0f172a;
}
.modal-premium-footer {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1.5rem;
}

/* ───────── RESPONSIVE ───────── */
@media (max-width: 992px) {
    .tg-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .tg-wrap { padding: 1rem; }
    .tg-hero {
        padding: 1.5rem 1.5rem 1.25rem;
        border-radius: 14px;
    }
    .tg-hero-top { flex-direction: column; align-items: stretch; }
    .tg-hero-actions { justify-content: flex-start; }
    .tg-hero-info h1 { font-size: 1.2rem; }
    .tg-card-head, .tg-card-body { padding-left: 1.1rem; padding-right: 1.1rem; }
    .tg-eval-grid { grid-template-columns: 1fr; }
    .tg-recent-item {
        grid-template-columns: 40px 1fr;
        grid-template-rows: auto auto;
        gap: .55rem .8rem;
    }
    .tg-recent-meta {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
@media (max-width: 480px) {
    .tg-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
    .tg-kpi { padding: .7rem .8rem; }
    .tg-kpi-value { font-size: 1.15rem; }
    .tg-eval-foot { flex-direction: column; align-items: stretch; }
    .tg-eval-actions { justify-content: stretch; }
    .tg-eval-btn { flex: 1; justify-content: center; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content tg-wrap">

        {{-- HERO --}}
        <section class="tg-hero">
            <div class="tg-hero-top">
                <div class="tg-hero-left">
                    <div class="tg-hero-icon"><i class="fa-solid fa-pen-ruler"></i></div>
                    <div class="tg-hero-info">
                        <h1>Gestion des notes</h1>
                        <p>Vos évaluations et la saisie des notes en un coup d'œil</p>
                    </div>
                </div>
                <div class="tg-hero-actions">
                    <span class="tg-badge-year">
                        <i class="fa-regular fa-calendar"></i>
                        {{ $anneeEnCours->name ?? 'Année non définie' }}
                    </span>
                    <a href="{{ route('teacher.dashboard') }}" class="tg-btn tg-btn--glass">
                        <i class="fa-solid fa-arrow-left"></i> Retour
                    </a>
                    <a href="{{ route('esbtp.evaluations.create') }}" class="tg-btn tg-btn--white">
                        <i class="fa-solid fa-circle-plus"></i> Nouvelle évaluation
                    </a>
                </div>
            </div>

            <div class="tg-kpis">
                <div class="tg-kpi tg-kpi--total">
                    <div class="tg-kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="tg-kpi-body">
                        <div class="tg-kpi-value">{{ $evaluations->total() ?? 0 }}</div>
                        <div class="tg-kpi-label">Évaluations totales</div>
                    </div>
                </div>
                <div class="tg-kpi tg-kpi--pub">
                    <div class="tg-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="tg-kpi-body">
                        <div class="tg-kpi-value">{{ $evaluations->where('is_published', true)->count() }}</div>
                        <div class="tg-kpi-label">Publiées (page)</div>
                    </div>
                </div>
                <div class="tg-kpi tg-kpi--draft">
                    <div class="tg-kpi-icon"><i class="fa-solid fa-circle-minus"></i></div>
                    <div class="tg-kpi-body">
                        <div class="tg-kpi-value">{{ $evaluations->where('is_published', false)->count() }}</div>
                        <div class="tg-kpi-label">En brouillon (page)</div>
                    </div>
                </div>
                <div class="tg-kpi tg-kpi--recent">
                    <div class="tg-kpi-icon"><i class="fa-solid fa-pen-fancy"></i></div>
                    <div class="tg-kpi-body">
                        <div class="tg-kpi-value">{{ $recentGrades->count() ?? 0 }}</div>
                        <div class="tg-kpi-label">Notes saisies récemment</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- LAYOUT VERTICAL --}}
        <div class="tg-layout">

            {{-- MES EVALUATIONS --}}
            <section class="tg-card">
                <header class="tg-card-head">
                    <div class="tg-card-head-left">
                        <div class="tg-card-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div>
                            <h2 class="tg-card-title">Mes évaluations</h2>
                            <p class="tg-card-sub">{{ $evaluations->total() }} évaluation{{ $evaluations->total() > 1 ? 's' : '' }} au total</p>
                        </div>
                    </div>
                </header>
                <div class="tg-card-body">
                    @if($evaluations->count() > 0)
                        <div class="tg-eval-grid">
                            @foreach($evaluations as $evaluation)
                                @include('teacher.partials.evaluation-card', ['evaluation' => $evaluation])
                            @endforeach
                        </div>
                        <nav class="tg-pagination">
                            {{ $evaluations->links() }}
                        </nav>
                    @else
                        <div class="tg-empty">
                            <div class="tg-empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                            <h3>Aucune évaluation pour l'instant</h3>
                            <p>Créez votre première évaluation pour commencer à saisir les notes de vos étudiants.</p>
                            <a href="{{ route('esbtp.evaluations.create') }}" class="tg-btn-cta">
                                <i class="fa-solid fa-circle-plus"></i> Créer une évaluation
                            </a>
                        </div>
                    @endif
                </div>
            </section>

            {{-- NOTES RECENTES --}}
            <section class="tg-card">
                <header class="tg-card-head">
                    <div class="tg-card-head-left">
                        <div class="tg-card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <h2 class="tg-card-title">Notes récemment saisies</h2>
                            <p class="tg-card-sub">Les 10 dernières notes que vous avez enregistrées</p>
                        </div>
                    </div>
                </header>
                <div class="tg-card-body">
                    @if($recentGrades->count() > 0)
                        <ul class="tg-recent">
                            @foreach($recentGrades as $note)
                                @php
                                    $score = (float) ($note->note ?? 0);
                                    $bareme = (float) ($note->evaluation->bareme ?? 20);
                                    $isAbsent = (int) ($note->is_absent ?? 0) === 1;
                                    $pct = $bareme > 0 ? ($score / $bareme) * 100 : 0;
                                    $tone = $isAbsent ? 'absent' : ($pct >= 50 ? 'pass' : 'fail');
                                    $nomRaw = trim(($note->etudiant->nom ?? '') . ' ' . ($note->etudiant->prenoms ?? ''));
                                    $initial = mb_strtoupper(mb_substr($nomRaw !== '' ? $nomRaw : '?', 0, 1, 'UTF-8'), 'UTF-8');
                                    $scoreFmt = rtrim(rtrim(number_format($score, 2, ',', ''), '0'), ',');
                                    if ($scoreFmt === '' || $scoreFmt === '-') { $scoreFmt = '0'; }
                                @endphp
                                <li class="tg-recent-item">
                                    <div class="tg-recent-avatar">{{ $initial }}</div>
                                    <div class="tg-recent-info">
                                        <div class="tg-recent-name">{{ $nomRaw !== '' ? $nomRaw : 'Étudiant·e' }}</div>
                                        <div class="tg-recent-sub">
                                            <span><i class="fa-solid fa-people-group"></i>{{ $note->evaluation->classe->name ?? 'Classe' }}</span>
                                            <span class="tg-recent-eval">
                                                <i class="fa-solid fa-book"></i>
                                                {{ $note->evaluation->matiere->name ?? '' }}
                                                @if(!empty($note->evaluation->titre))
                                                    · {{ $note->evaluation->titre }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="tg-recent-meta">
                                        <span class="tg-score tg-score--{{ $tone }}">
                                            @if($isAbsent)
                                                <i class="fa-solid fa-user-xmark"></i> Absent
                                            @else
                                                <strong>{{ $scoreFmt }}</strong><small>/{{ (int) $bareme }}</small>
                                            @endif
                                        </span>
                                        <span class="tg-recent-date">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $note->created_at ? $note->created_at->diffForHumans() : '—' }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="tg-empty tg-empty--small">
                            <div class="tg-empty-icon tg-empty-icon--small"><i class="fa-solid fa-chart-line"></i></div>
                            <h4>Aucune note récente</h4>
                            <p>Vos saisies de notes apparaîtront ici en temps réel.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- MODAL Saisie de notes (préservé) --}}
    <div class="modal fade" id="teacherNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-premium-shell">
                <div class="modal-body p-0" id="teacherNoteModalBody">
                    <div class="p-4 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-2">Chargement...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('teacherNoteModal');
    const modalBody = document.getElementById('teacherNoteModalBody');
    const csrfToken = '{{ csrf_token() }}';
    const noteModalUrlTemplate = @json(route('teacher.grades.note-modal', ['evaluation' => '__id__']));
    const refreshCardUrlTemplate = @json(route('teacher.grades.card', ['evaluation' => '__id__']));
    let currentEvaluationId = null;

    function buildUrl(template, id) {
        return template.replace('__id__', id);
    }

    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1rem;">
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        const mainContent = document.querySelector('.tg-wrap');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHtml);
            setTimeout(() => {
                const alert = mainContent.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    }

    function refreshEvaluationCard(evaluationId) {
        const card = document.querySelector(`[data-evaluation-id="${evaluationId}"]`);
        if (!card) {
            return;
        }

        fetch(buildUrl(refreshCardUrlTemplate, evaluationId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Refresh invalide');
            }
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const newCard = template.content.querySelector(`[data-evaluation-id="${evaluationId}"]`) || template.content.firstElementChild;
            if (newCard) {
                card.replaceWith(newCard);
                newCard.classList.add('card-flash-success');
                setTimeout(() => {
                    newCard.classList.remove('card-flash-success');
                }, 1200);
            }
        })
        .catch(() => {
            window.location.reload();
        });
    }

    function openNoteModal(evaluationId) {
        if (!modalElement) {
            return;
        }
        currentEvaluationId = evaluationId;
        modalBody.innerHTML = `
            <div class="p-4 text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-2">Chargement...</div>
            </div>
        `;

        fetch(buildUrl(noteModalUrlTemplate, evaluationId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Impossible de charger le modal');
            }
            modalBody.innerHTML = data.html;
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="p-4 text-danger">Erreur de chargement.</div>';
        });

        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-action="open-notes-modal"]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        const evaluationId = trigger.dataset.evaluationId;
        if (evaluationId) {
            openNoteModal(evaluationId);
        }
    });

    modalElement?.addEventListener('submit', function (event) {
        const form = event.target.closest('#teacherNoteForm');
        if (!form) {
            return;
        }
        event.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                let message = data.message || 'Erreur lors de l\'enregistrement.';
                if (!data.message && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) {
                        message = data.errors[firstKey][0];
                    }
                }
                throw new Error(message);
            }
            return data;
        })
        .then(data => {
            showSuccessMessage(data.message || 'Note enregistrée.');
            const bsModal = bootstrap.Modal.getInstance(modalElement);
            bsModal.hide();
        })
        .catch(error => {
            const existingAlert = form.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger mt-3';
            alert.textContent = error.message;
            form.prepend(alert);
        });
    });

    modalBody?.addEventListener('change', function (event) {
        const checkbox = event.target.closest('#teacher_note_absent');
        if (!checkbox) {
            return;
        }
        const noteInput = modalBody.querySelector('input[name="note"]');
        if (!noteInput) {
            return;
        }
        if (checkbox.checked) {
            noteInput.value = '';
            noteInput.setAttribute('disabled', 'disabled');
            noteInput.removeAttribute('required');
        } else {
            noteInput.removeAttribute('disabled');
            noteInput.setAttribute('required', 'required');
        }
    });

    modalElement?.addEventListener('hidden.bs.modal', function () {
        if (currentEvaluationId) {
            refreshEvaluationCard(currentEvaluationId);
        }
    });
});
</script>
@endsection
