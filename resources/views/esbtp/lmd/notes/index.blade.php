@extends('layouts.app')

@section('title', 'Notes LMD | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Notes Index — Premium Redesign
       Prefix: ln- (lmd-notes)
       ══════════════════════════════════════════════ */

    .ln-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .ln-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: ln-fadeDown .5s ease-out;
    }
    .ln-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-hero::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: 5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }

    .ln-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .ln-hero-left { display: flex; align-items: center; gap: 1rem; }
    .ln-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border: 1px solid rgba(255,255,255,.15);
        flex-shrink: 0;
    }
    .ln-hero-info h1 {
        font-size: 1.45rem;
        font-weight: 700;
        margin: 0 0 .2rem;
        color: #fff;
        letter-spacing: -.02em;
    }
    .ln-hero-info p {
        margin: 0;
        opacity: .8;
        font-size: .88rem;
    }
    .ln-hero-actions {
        display: flex;
        gap: .5rem;
        position: relative;
        z-index: 1;
    }
    .ln-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 10px;
        font-size: .84rem;
        font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.3);
        color: #fff;
        background: rgba(255,255,255,.08);
        text-decoration: none;
        transition: all .2s;
        backdrop-filter: blur(4px);
    }
    .ln-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .ln-hero-btn--solid {
        background: #fff;
        color: #0453cb;
        border-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .ln-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs inside hero */
    .ln-hero-kpis {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .ln-kpi {
        flex: 1;
        min-width: 150px;
        background: rgba(255,255,255,.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        transition: background .2s;
    }
    .ln-kpi:hover { background: rgba(255,255,255,.15); }
    .ln-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .ln-kpi--evals .ln-kpi-icon    { background: rgba(255,255,255,.18); color: #fff; }
    .ln-kpi--classes .ln-kpi-icon   { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .ln-kpi--complete .ln-kpi-icon  { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .ln-kpi--pending .ln-kpi-icon   { background: rgba(251,191,36,.2); color: #fcd34d; }
    .ln-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
    }
    .ln-kpi-label {
        font-size: .75rem;
        color: rgba(255,255,255,.65);
        margin-top: .15rem;
    }

    /* ── Section header ── */
    .ln-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .85rem;
    }
    .ln-section-title {
        font-size: 1.02rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .ln-section-title i { color: #0453cb; font-size: .88rem; }
    .ln-section-count {
        font-size: .8rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ── Eval cards grid ── */
    .ln-evals {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: .85rem;
        margin-bottom: 1.75rem;
        animation: ln-fadeUp .45s ease-out .1s both;
    }
    .ln-eval {
        background: #fff;
        border-radius: 13px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        padding: 1.15rem 1.25rem;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s;
        display: flex;
        flex-direction: column;
        gap: .6rem;
    }
    .ln-eval:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(4,83,203,.1);
        border-color: #c7d6f0;
    }

    .ln-eval-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
    }
    .ln-eval-title {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.3;
    }
    .ln-eval-matiere {
        font-size: .82rem;
        color: #64748b;
        margin-top: .15rem;
    }

    /* UE badge */
    .ln-ue-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .2rem .55rem;
        border-radius: 6px;
        font-size: .7rem;
        font-weight: 700;
        background: #eef2ff;
        color: #4338ca;
        letter-spacing: .02em;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Status badge */
    .ln-status {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .2rem .55rem;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .ln-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .ln-status--completed { background: #ecfdf5; color: #059669; }
    .ln-status--completed .ln-status-dot { background: #10b981; }
    .ln-status--pending { background: #fef9ee; color: #b45309; }
    .ln-status--pending .ln-status-dot { background: #f59e0b; }
    .ln-status--in_progress { background: #eff6ff; color: #0453cb; }
    .ln-status--in_progress .ln-status-dot { background: #3b82f6; }

    /* Eval meta row */
    .ln-eval-meta {
        display: flex;
        gap: .85rem;
        flex-wrap: wrap;
    }
    .ln-eval-meta-item {
        display: flex;
        align-items: center;
        gap: .3rem;
        font-size: .78rem;
        color: #64748b;
    }
    .ln-eval-meta-item i { font-size: .72rem; color: #94a3b8; }

    /* Eval footer */
    .ln-eval-foot {
        margin-top: auto;
        padding-top: .5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
    }
    .ln-eval-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .9rem;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s;
        background: #0453cb;
        color: #fff;
        border: none;
    }
    .ln-eval-btn:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* ── Classes table card ── */
    .ln-table-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden;
        animation: ln-fadeUp .45s ease-out .2s both;
    }
    .ln-table-header {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .ln-table-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .ln-table-title i { color: #0453cb; font-size: .9rem; }
    .ln-table-count {
        font-size: .8rem;
        color: #94a3b8;
        font-weight: 500;
    }
    .ln-table-wrapper { overflow-x: auto; }

    .ln-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ln-table thead th {
        padding: .75rem 1rem;
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafbfc;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .ln-table tbody tr {
        transition: background .15s;
        border-bottom: 1px solid #f8fafc;
    }
    .ln-table tbody tr:hover { background: #f8fbff; }
    .ln-table tbody tr:last-child { border-bottom: none; }
    .ln-table tbody td {
        padding: .8rem 1rem;
        font-size: .87rem;
        color: #475569;
        vertical-align: middle;
    }
    .ln-class-name {
        font-weight: 600;
        color: #1e293b;
    }
    .ln-badge-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        padding: .15rem .5rem;
        border-radius: 6px;
        font-size: .82rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }

    /* ── Empty state ── */
    .ln-empty-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
    }
    .ln-empty {
        text-align: center;
        padding: 3rem 2rem;
    }
    .ln-empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: #cbd5e1;
        margin-bottom: .85rem;
    }
    .ln-empty-title {
        font-size: 1rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: .3rem;
    }
    .ln-empty-text {
        font-size: .85rem;
        color: #94a3b8;
    }

    /* Pagination */
    .ln-pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
    }
    .ln-pagination .pagination { margin: 0; }

    /* ── Animations ── */
    @keyframes ln-fadeDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes ln-fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .ln-hero { padding: 1.5rem; border-radius: 14px; }
        .ln-hero-top { flex-direction: column; }
        .ln-hero-kpis { flex-direction: column; }
        .ln-evals { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="ln-page">

    {{-- ══ Hero ══ --}}
    @php
        $totalEvals = $evaluationsRecentes->count();
        $completedEvals = $evaluationsRecentes->where('status', 'completed')->count();
        $pendingEvals = $totalEvals - $completedEvals;
        $totalClasses = $classes->total();
    @endphp

    <div class="ln-hero">
        <div class="ln-hero-top">
            <div class="ln-hero-left">
                <div class="ln-hero-icon"><i class="fas fa-edit"></i></div>
                <div class="ln-hero-info">
                    <h1>Notes LMD</h1>
                    <p>Saisie et gestion des notes par ECUE</p>
                </div>
            </div>
            <div class="ln-hero-actions">
                <a href="{{ route('esbtp.lmd.resultats.index') }}" class="ln-hero-btn">
                    <i class="fas fa-chart-bar"></i>Résultats
                </a>
                <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="ln-hero-btn">
                    <i class="fas fa-file-alt"></i>Bulletins
                </a>
            </div>
        </div>

        <div class="ln-hero-kpis">
            <div class="ln-kpi ln-kpi--evals">
                <div class="ln-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalEvals }}</div>
                    <div class="ln-kpi-label">Évaluations</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--classes">
                <div class="ln-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalClasses }}</div>
                    <div class="ln-kpi-label">Classes LMD</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--complete">
                <div class="ln-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $completedEvals }}</div>
                    <div class="ln-kpi-label">Complétées</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--pending">
                <div class="ln-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $pendingEvals }}</div>
                    <div class="ln-kpi-label">En attente</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Évaluations récentes ══ --}}
    <div class="ln-section-header">
        <div class="ln-section-title">
            <i class="fas fa-clipboard-check"></i>
            Évaluations récentes
        </div>
        <div class="ln-section-count">{{ $totalEvals }} évaluation{{ $totalEvals > 1 ? 's' : '' }}</div>
    </div>

    @if($evaluationsRecentes->isEmpty())
        <div class="ln-empty-card" style="margin-bottom: 1.5rem;">
            <div class="ln-empty">
                <div class="ln-empty-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="ln-empty-title">Aucune évaluation LMD</div>
                <div class="ln-empty-text">Créez d'abord des évaluations pour les classes LMD.</div>
            </div>
        </div>
    @else
        <div class="ln-evals">
            @foreach($evaluationsRecentes as $eval)
                @php
                    $statusKey = $eval->status ?? 'pending';
                    $statusLabel = match($statusKey) {
                        'completed' => 'Terminée',
                        'in_progress' => 'En cours',
                        default => 'En attente',
                    };
                @endphp
                <div class="ln-eval">
                    <div class="ln-eval-top">
                        <div>
                            <div class="ln-eval-title">
                                {{ $eval->titre }}
                                @if($eval->matiere && $eval->matiere->uniteEnseignement)
                                    <span class="ln-ue-badge" style="margin-left:.4rem;">{{ $eval->matiere->uniteEnseignement->code }}</span>
                                @endif
                            </div>
                            <div class="ln-eval-matiere">
                                {{ $eval->matiere->name ?? '' }} — {{ $eval->classe->name ?? '' }}
                            </div>
                        </div>
                        <span class="ln-status ln-status--{{ $statusKey }}">
                            <span class="ln-status-dot"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="ln-eval-meta">
                        @if($eval->date_evaluation)
                            <span class="ln-eval-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                {{ \Carbon\Carbon::parse($eval->date_evaluation)->format('d/m/Y') }}
                            </span>
                        @endif
                        <span class="ln-eval-meta-item">
                            <i class="fas fa-balance-scale"></i>
                            Coeff. {{ $eval->coefficient }}
                        </span>
                        <span class="ln-eval-meta-item">
                            <i class="fas fa-ruler-horizontal"></i>
                            Barème /{{ $eval->bareme }}
                        </span>
                    </div>

                    <div class="ln-eval-foot">
                        <a href="{{ route('esbtp.lmd.notes.saisie', $eval) }}" class="ln-eval-btn">
                            <i class="fas fa-edit"></i>Saisir les notes
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══ Classes LMD ══ --}}
    <div class="ln-table-card">
        <div class="ln-table-header">
            <div class="ln-table-title">
                <i class="fas fa-layer-group"></i>
                Classes LMD
            </div>
            <div class="ln-table-count">{{ $classes->total() }} classe{{ $classes->total() > 1 ? 's' : '' }}</div>
        </div>

        @if($classes->isEmpty())
            <div class="ln-empty">
                <div class="ln-empty-icon"><i class="fas fa-layer-group"></i></div>
                <div class="ln-empty-title">Aucune classe LMD configurée</div>
                <div class="ln-empty-text">Ajoutez des classes avec système académique = LMD.</div>
            </div>
        @else
            <div class="ln-table-wrapper">
                <table class="ln-table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th style="text-align:center;">Étudiants</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $classe)
                            <tr>
                                <td><span class="ln-class-name">{{ $classe->name }}</span></td>
                                <td style="color:#64748b;">{{ $classe->filiere->name ?? '—' }}</td>
                                <td style="color:#64748b;">{{ $classe->niveau->name ?? '—' }}</td>
                                <td style="text-align:center;">
                                    <span class="ln-badge-count">{{ $classe->inscriptions_count }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($classes->hasPages())
                <div class="ln-pagination">
                    {{ $classes->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
