@extends('layouts.app')

@section('title', 'Filière : ' . $filiere->name . ' - KLASSCI')

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ============ Hero ============ */
.fs-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.fs-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.fs-hero-left { display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0; }
.fs-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.fs-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0 0 .25rem; }
.fs-hero-sub { color: rgba(255,255,255,.78); font-size: .88rem; margin: 0; display: flex; gap: .55rem; align-items: center; flex-wrap: wrap; }
.fs-hero-sub code { background: rgba(255,255,255,.16); padding: .12rem .45rem; border-radius: 5px; font-size: .75rem; color: #fff; }
.fs-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

.fs-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .55rem 1rem;
    border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background .15s, transform .12s;
}
.fs-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.fs-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.fs-btn--white  { background: #fff; color: #0453cb; }
.fs-btn--white:hover  { background: #f1f5ff; color: #0453cb; }
.fs-btn--primary { background: #0453cb; color: #fff; }
.fs-btn--primary:hover { background: #033a8e; color: #fff; }
.fs-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.fs-btn--ghost:hover { background: #f8fafc; color: #0453cb; border-color: #c7d4e5; }
.fs-btn--danger { background: #fff; color: #dc2626; border-color: #fecaca; }
.fs-btn--danger:hover { background: #fef2f2; }
.fs-btn--sm { padding: .35rem .7rem; font-size: .76rem; }
.fs-btn:disabled, .fs-btn[disabled] { opacity: .55; cursor: not-allowed; }

/* ============ KPIs hero ============ */
.fs-kpis {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.fs-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.fs-kpi-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .85rem;
}
.fs-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.fs-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

/* ============ Cards ============ */
.fs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.fs-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.fs-card-header {
    display: flex; align-items: center; gap: .65rem;
    padding: 1rem 1.25rem .85rem;
    border-bottom: 1px solid #f1f5f9;
}
.fs-card-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: .9rem; box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.fs-card-title { font-size: .98rem; font-weight: 700; color: #0f172a; margin: 0; }
.fs-card-count { margin-left: auto; font-size: .72rem; color: #64748b; font-weight: 600; padding: .15rem .55rem; background: #f1f5f9; border-radius: 6px; }
.fs-card-body { padding: 1rem 1.25rem; }

/* ============ Info list ============ */
.fs-info-list { list-style: none; margin: 0; padding: 0; }
.fs-info-list li {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; padding: .55rem 0;
    border-bottom: 1px dashed #f1f5f9;
}
.fs-info-list li:last-child { border-bottom: 0; }
.fs-info-list .fs-info-label { font-size: .76rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.fs-info-list .fs-info-value { font-size: .88rem; color: #1e293b; font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }
.fs-info-list .fs-info-value a { color: #0453cb; text-decoration: none; }
.fs-info-list .fs-info-value a:hover { text-decoration: underline; }

.fs-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 5px; font-size: .72rem; font-weight: 700; }
.fs-badge--success { background: rgba(16,185,129,.12); color: #0f766e; border: 1px solid rgba(16,185,129,.3); }
.fs-badge--danger  { background: rgba(220,38,38,.10); color: #b91c1c; border: 1px solid rgba(220,38,38,.25); }
.fs-badge--primary { background: rgba(4,83,203,.10); color: #0453cb; border: 1px solid rgba(4,83,203,.25); }
.fs-badge--accent  { background: rgba(59,125,219,.10); color: #3b7ddb; border: 1px solid rgba(59,125,219,.25); }
.fs-badge--muted   { background: rgba(94,145,222,.08); color: #5e91de; border: 1px solid rgba(94,145,222,.20); }

.fs-description {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: .85rem 1rem;
    color: #334155; font-size: .88rem; line-height: 1.5;
    white-space: pre-line;
}
.fs-empty-mini { text-align: center; color: #94a3b8; font-size: .82rem; padding: 1rem 0; }
.fs-empty-mini i { display: block; font-size: 1.5rem; color: #cbd5e1; margin-bottom: .4rem; }

/* ============ Liste compacte (niveaux, classes) ============ */
.fs-list-row {
    display: flex; align-items: center; gap: .65rem;
    padding: .55rem .25rem;
    border-bottom: 1px solid #f1f5f9;
}
.fs-list-row:last-child { border-bottom: 0; }
.fs-list-row-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(4,83,203,.08); color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem; flex-shrink: 0;
}
.fs-list-row-name { flex: 1; min-width: 0; font-size: .88rem; font-weight: 600; color: #1e293b; }
.fs-list-row-name a { color: inherit; text-decoration: none; }
.fs-list-row-name a:hover { color: #0453cb; }
.fs-list-row-meta { font-size: .72rem; color: #64748b; }

/* ============ Section "Sorties BTS Tronc Commun" ============ */
.fs-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.fs-section-header {
    display: flex; align-items: center; gap: .75rem;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.fs-section-icon-lg {
    width: 44px; height: 44px; border-radius: 11px;
    background: linear-gradient(135deg, #033a8e, #0453cb, #3b7ddb);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; box-shadow: 0 3px 10px rgba(4,83,203,.25);
}
.fs-section-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.fs-section-hint  { font-size: .8rem; color: #64748b; margin: .2rem 0 0; }
.fs-section-body { padding: 1.25rem; }

.fs-tc-classe-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: .85rem;
    overflow: visible;
}
.fs-tc-classe-card:last-child { margin-bottom: 0; }
.fs-tc-classe-head {
    display: flex; align-items: center; gap: .7rem;
    padding: .85rem 1rem;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    border-radius: 12px 12px 0 0;
}
.fs-tc-classe-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.fs-tc-classe-title { font-size: .92rem; font-weight: 700; color: #0f172a; }
.fs-tc-classe-meta  { font-size: .72rem; color: #64748b; font-weight: 500; margin-top: .15rem; }
.fs-tc-count {
    margin-left: auto;
    font-size: .7rem; color: #0453cb;
    padding: .18rem .55rem;
    background: rgba(4,83,203,.10);
    border: 1px solid rgba(4,83,203,.22);
    border-radius: 6px; font-weight: 700;
}
.fs-tc-classe-body { padding: .85rem 1rem 1rem; }

.fs-sortie-row {
    display: flex; align-items: center; gap: .7rem;
    padding: .65rem .85rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: .5rem;
    transition: border-color .15s;
}
.fs-sortie-row:last-child { margin-bottom: 0; }
.fs-sortie-row.is-inactive { background: #f8fafc; opacity: .7; }
.fs-sortie-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(59,125,219,.10); color: #3b7ddb;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
}
.fs-sortie-name { font-size: .87rem; font-weight: 700; color: #1e293b; }
.fs-sortie-meta { font-size: .72rem; color: #64748b; margin-top: .12rem; }
.fs-sortie-semestre {
    font-size: .68rem; color: #0453cb; font-weight: 700;
    padding: .15rem .5rem; background: rgba(4,83,203,.08);
    border: 1px solid rgba(4,83,203,.2);
    border-radius: 5px;
    font-family: 'Courier New', monospace;
}

/* Toggle switch monochrome bleu */
.fs-switch { position: relative; display: inline-block; width: 38px; height: 22px; flex-shrink: 0; }
.fs-switch input { opacity: 0; width: 0; height: 0; }
.fs-switch-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #cbd5e1; border-radius: 22px;
    transition: background .2s;
}
.fs-switch-slider::before {
    content: ''; position: absolute;
    width: 16px; height: 16px;
    left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.fs-switch input:checked + .fs-switch-slider { background: #0453cb; }
.fs-switch input:checked + .fs-switch-slider::before { transform: translateX(16px); }

.fs-add-form {
    display: grid;
    grid-template-columns: 1fr 90px 1fr auto;
    gap: .55rem;
    margin-top: .85rem;
    padding-top: .85rem;
    border-top: 1px dashed #cbd5e1;
}
.fs-add-form .au-select { min-width: 0; }
.fs-add-form input[type="number"],
.fs-add-form input[type="text"] {
    padding: .5rem .7rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: .82rem; color: #1e293b;
    background: #fff;
}
.fs-add-form input[type="number"]:focus,
.fs-add-form input[type="text"]:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
@media (max-width: 768px) {
    .fs-add-form { grid-template-columns: 1fr; }
}

.fs-tc-warn {
    padding: .65rem .85rem;
    background: rgba(245,158,11,.08);
    border: 1px solid rgba(245,158,11,.25);
    border-radius: 9px;
    font-size: .78rem; color: #92400e;
    display: flex; align-items: center; gap: .55rem;
    margin-top: .65rem;
}

.fs-empty-section {
    text-align: center;
    padding: 2.5rem 1.5rem;
    color: #64748b;
}
.fs-empty-section i { font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: .75rem; }
.fs-empty-section h4 { font-size: 1rem; color: #1e293b; margin: 0 0 .35rem; font-weight: 700; }
.fs-empty-section p { font-size: .85rem; margin: 0; }

/* ============ Toasts ============ */
.fs-toasts {
    position: fixed; bottom: 1.25rem; right: 1.25rem;
    z-index: 10000;
    display: flex; flex-direction: column; gap: .55rem;
    pointer-events: none;
}
.fs-toast {
    pointer-events: auto;
    background: #fff;
    border-left: 4px solid #0453cb;
    border-radius: 8px;
    padding: .75rem 1rem;
    box-shadow: 0 10px 30px rgba(15,23,42,.12);
    font-size: .85rem; color: #1e293b;
    display: flex; align-items: center; gap: .55rem;
    min-width: 240px;
    animation: fs-toast-in .25s ease-out;
}
.fs-toast--success { border-left-color: #10b981; }
.fs-toast--success i { color: #10b981; }
.fs-toast--error   { border-left-color: #dc2626; }
.fs-toast--error i { color: #dc2626; }
@@keyframes fs-toast-in { from { transform: translateY(.5rem); opacity: 0; } to { transform: none; opacity: 1; } }

/* Modal confirm danger */
.fs-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    z-index: 9990;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.fs-modal {
    background: #fff;
    border-radius: 14px;
    width: 100%; max-width: 460px;
    box-shadow: 0 24px 60px rgba(15,23,42,.2);
    overflow: hidden;
}
.fs-modal-header {
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff;
    display: flex; align-items: center; gap: .55rem;
}
.fs-modal-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.fs-modal-body { padding: 1.25rem; font-size: .9rem; color: #334155; line-height: 1.55; }
.fs-modal-actions { padding: 1rem 1.25rem; background: #f8fafc; display: flex; gap: .55rem; justify-content: flex-end; border-top: 1px solid #f1f5f9; }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="filiereShow()" x-init="init()" x-cloak>

    {{-- ================ HERO ================ --}}
    <div class="fs-hero">
        <div class="fs-hero-top">
            <div class="fs-hero-left">
                <div class="fs-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div style="min-width:0; flex:1;">
                    <h1>{{ $filiere->name }}</h1>
                    <p class="fs-hero-sub">
                        <code>{{ $filiere->code }}</code>
                        @if($filiere->isTroncCommun())
                            <span class="fs-badge fs-badge--accent" style="background:rgba(255,255,255,.18); color:#fff; border-color:rgba(255,255,255,.25);">
                                <i class="fas fa-route"></i> Tronc commun
                            </span>
                        @endif
                        @if($filiere->parent)
                            <span style="opacity:.85;">· Option de <a href="{{ route('esbtp.filieres.show', $filiere->parent) }}" style="color:#fff; text-decoration:underline;">{{ $filiere->parent->name }}</a></span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="fs-hero-actions">
                <a href="{{ route('esbtp.filieres.index') }}" class="fs-btn fs-btn--glass">
                    <i class="fas fa-arrow-left"></i> Liste
                </a>
                @can('filieres.edit')
                <a href="{{ route('esbtp.filieres.edit', $filiere) }}" class="fs-btn fs-btn--white">
                    <i class="fas fa-pen"></i> Modifier
                </a>
                @endcan
            </div>
        </div>

        <div class="fs-kpis">
            <div class="fs-kpi">
                <div class="fs-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="fs-kpi-value">{{ $filiere->niveaux ? $filiere->niveaux->count() : 0 }}</div>
                    <div class="fs-kpi-label">Niveaux d'études</div>
                </div>
            </div>
            <div class="fs-kpi">
                <div class="fs-kpi-icon"><i class="fas fa-chalkboard"></i></div>
                <div>
                    <div class="fs-kpi-value">{{ $filiere->classes ? $filiere->classes->count() : 0 }}</div>
                    <div class="fs-kpi-label">Classes</div>
                </div>
            </div>
            <div class="fs-kpi">
                <div class="fs-kpi-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="fs-kpi-value">{{ $filiere->matieres ? $filiere->matieres->count() : 0 }}</div>
                    <div class="fs-kpi-label">Matières</div>
                </div>
            </div>
            @if($filiere->options && $filiere->options->count() > 0)
            <div class="fs-kpi">
                <div class="fs-kpi-icon"><i class="fas fa-stream"></i></div>
                <div>
                    <div class="fs-kpi-value">{{ $filiere->options->count() }}</div>
                    <div class="fs-kpi-label">Options</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ================ INFOS + DESCRIPTION ================ --}}
    <div class="fs-grid">
        <div class="fs-card">
            <div class="fs-card-header">
                <div class="fs-card-icon"><i class="fas fa-info-circle"></i></div>
                <h6 class="fs-card-title">Informations générales</h6>
            </div>
            <div class="fs-card-body">
                <ul class="fs-info-list">
                    <li>
                        <span class="fs-info-label">Nom</span>
                        <span class="fs-info-value">{{ $filiere->name }}</span>
                    </li>
                    <li>
                        <span class="fs-info-label">Code</span>
                        <span class="fs-info-value"><code>{{ $filiere->code }}</code></span>
                    </li>
                    <li>
                        <span class="fs-info-label">Statut</span>
                        <span class="fs-info-value">
                            @if($filiere->is_active)
                                <span class="fs-badge fs-badge--success"><i class="fas fa-circle-check"></i> Active</span>
                            @else
                                <span class="fs-badge fs-badge--danger"><i class="fas fa-circle-xmark"></i> Inactive</span>
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="fs-info-label">Tronc commun</span>
                        <span class="fs-info-value">
                            @if($filiere->isTroncCommun())
                                <span class="fs-badge fs-badge--accent"><i class="fas fa-route"></i> Oui — {{ $filiere->semestres_tronc_commun ?? 1 }} semestre(s)</span>
                            @else
                                <span class="fs-badge fs-badge--muted">Non</span>
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="fs-info-label">Filière parente</span>
                        <span class="fs-info-value">
                            @if($filiere->parent)
                                <a href="{{ route('esbtp.filieres.show', $filiere->parent) }}">{{ $filiere->parent->name }} <small>({{ $filiere->parent->code }})</small></a>
                            @else
                                <span style="color:#94a3b8; font-weight:500;">Aucune (principale)</span>
                            @endif
                        </span>
                    </li>
                    @if($filiere->option_filiere)
                    <li>
                        <span class="fs-info-label">Option</span>
                        <span class="fs-info-value">{{ $filiere->option_filiere }}</span>
                    </li>
                    @endif
                    <li>
                        <span class="fs-info-label">Créée le</span>
                        <span class="fs-info-value">{{ $filiere->created_at?->format('d/m/Y H:i') }}</span>
                    </li>
                    <li>
                        <span class="fs-info-label">Modifiée le</span>
                        <span class="fs-info-value">{{ $filiere->updated_at?->format('d/m/Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="fs-card">
            <div class="fs-card-header">
                <div class="fs-card-icon"><i class="fas fa-align-left"></i></div>
                <h6 class="fs-card-title">Description</h6>
            </div>
            <div class="fs-card-body">
                @if($filiere->description)
                    <div class="fs-description">{{ $filiere->description }}</div>
                @else
                    <div class="fs-empty-mini">
                        <i class="fas fa-align-left"></i>
                        Aucune description fournie pour cette filière.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================ NIVEAUX + CLASSES ================ --}}
    <div class="fs-grid">
        <div class="fs-card">
            <div class="fs-card-header">
                <div class="fs-card-icon"><i class="fas fa-layer-group"></i></div>
                <h6 class="fs-card-title">Niveaux d'études</h6>
                <span class="fs-card-count">{{ $filiere->niveaux ? $filiere->niveaux->count() : 0 }}</span>
            </div>
            <div class="fs-card-body">
                @if($filiere->niveaux && $filiere->niveaux->count() > 0)
                    @foreach($filiere->niveaux as $niveau)
                        <div class="fs-list-row">
                            <div class="fs-list-row-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div class="fs-list-row-name">
                                <a href="{{ route('esbtp.niveaux-etudes.show', $niveau) }}">{{ $niveau->name }}</a>
                                <div class="fs-list-row-meta">{{ $niveau->code }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="fs-empty-mini">
                        <i class="fas fa-layer-group"></i>
                        Aucun niveau d'étude associé.
                    </div>
                @endif
            </div>
        </div>

        <div class="fs-card">
            <div class="fs-card-header">
                <div class="fs-card-icon"><i class="fas fa-chalkboard"></i></div>
                <h6 class="fs-card-title">Classes</h6>
                <span class="fs-card-count">{{ $filiere->classes ? $filiere->classes->count() : 0 }}</span>
            </div>
            <div class="fs-card-body">
                @if($filiere->classes && $filiere->classes->count() > 0)
                    @foreach($filiere->classes as $classe)
                        <div class="fs-list-row">
                            <div class="fs-list-row-icon"><i class="fas fa-chalkboard"></i></div>
                            <div class="fs-list-row-name">
                                <a href="{{ route('esbtp.classes.show', $classe) }}">{{ $classe->name }}</a>
                                <div class="fs-list-row-meta">
                                    {{ $classe->niveauEtude?->name ?? '—' }}
                                    @if($classe->anneeUniversitaire) · {{ $classe->anneeUniversitaire->name }} @endif
                                </div>
                            </div>
                            @if(isset($classe->inscriptions_count))
                                <span class="fs-badge fs-badge--primary">{{ $classe->inscriptions_count }} inscrits</span>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="fs-empty-mini">
                        <i class="fas fa-chalkboard"></i>
                        Aucune classe associée à cette filière.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================ SORTIES BTS TRONC COMMUN (TC uniquement) ================ --}}
    @if($filiere->isTroncCommun())
    <div class="fs-section" id="sorties-tc">
        <div class="fs-section-header">
            <div class="fs-section-icon-lg"><i class="fas fa-route"></i></div>
            <div style="flex:1; min-width:0;">
                <h2 class="fs-section-title">Sorties BTS Tronc Commun</h2>
                <p class="fs-section-hint">
                    Les étudiants de cette classe TC pourront choisir parmi ces filières à la fin du semestre {{ $filiere->semestres_tronc_commun ?? 1 }}.
                    Sans configuration, le bouton « Orienter » sur la fiche étudiant affiche un message d'erreur.
                </p>
            </div>
            @can('bts_tronc_commun.manage_targets')
            <a href="{{ route('esbtp.admin.orientation-targets.index') }}" class="fs-btn fs-btn--ghost fs-btn--sm" title="Voir la page d'administration complète">
                <i class="fas fa-external-link-alt"></i> Vue globale
            </a>
            @endcan
        </div>
        <div class="fs-section-body">

            @if($sourceClasses->isEmpty())
                <div class="fs-empty-section">
                    <i class="fas fa-chalkboard"></i>
                    <h4>Aucune classe TC active pour cette filière</h4>
                    <p>Créez d'abord une classe rattachée à cette filière tronc commun, puis revenez configurer ses sorties.</p>
                    @can('classes.create')
                    <a href="{{ route('esbtp.classes.create', ['filiere_id' => $filiere->id]) }}" class="fs-btn fs-btn--primary fs-btn--sm" style="margin-top:.85rem;">
                        <i class="fas fa-plus"></i> Créer une classe
                    </a>
                    @endcan
                </div>
            @else
                @foreach($sourceClasses as $sourceClasse)
                    @php
                        $candidates = $candidatesByClasse[$sourceClasse->id] ?? collect();
                        $candidateOptions = $candidates->mapWithKeys(fn ($c) => [
                            $c->id => $c->name . ($c->filiere ? ' — ' . $c->filiere->name : ''),
                        ])->toArray();
                    @endphp
                    <div class="fs-tc-classe-card">
                        <div class="fs-tc-classe-head">
                            <div class="fs-tc-classe-icon"><i class="fas fa-chalkboard"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="fs-tc-classe-title">{{ $sourceClasse->name }}</div>
                                <div class="fs-tc-classe-meta">
                                    {{ $sourceClasse->niveauEtude?->name ?? '—' }}
                                    @if($sourceClasse->anneeUniversitaire) · {{ $sourceClasse->anneeUniversitaire->name }} @endif
                                </div>
                            </div>
                            <span class="fs-tc-count">
                                {{ $sourceClasse->orientationTargets->count() }}
                                spécialité{{ $sourceClasse->orientationTargets->count() > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="fs-tc-classe-body">
                            <div data-targets-for="{{ $sourceClasse->id }}">
                                @forelse($sourceClasse->orientationTargets->sortBy('sort_order') as $target)
                                    <div class="fs-sortie-row {{ $target->is_active ? '' : 'is-inactive' }}" data-target-id="{{ $target->id }}">
                                        <div class="fs-sortie-icon"><i class="fas fa-graduation-cap"></i></div>
                                        <div style="flex:1; min-width:0;">
                                            <div class="fs-sortie-name">{{ $target->targetClasse?->name ?? '— classe supprimée —' }}</div>
                                            <div class="fs-sortie-meta">
                                                {{ $target->targetClasse?->filiere?->name ?? '—' }}
                                                @if($target->notes) · <em>{{ $target->notes }}</em> @endif
                                            </div>
                                        </div>
                                        <span class="fs-sortie-semestre" title="Semestre d'activation">S{{ $target->semestre_activation }}</span>
                                        @can('bts_tronc_commun.manage_targets')
                                        <label class="fs-switch" title="Activer/désactiver cette sortie">
                                            <input type="checkbox" {{ $target->is_active ? 'checked' : '' }}
                                                @change="toggleSortie({{ $target->id }}, $event.target.checked, $event.target.closest('.fs-sortie-row'))">
                                            <span class="fs-switch-slider"></span>
                                        </label>
                                        <button class="fs-btn fs-btn--danger fs-btn--sm" type="button"
                                            @click="confirmDelete({{ $target->id }}, '{{ addslashes($target->targetClasse?->name ?? 'cette sortie') }}', {{ $sourceClasse->id }})"
                                            title="Supprimer cette sortie">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                @empty
                                    <div class="fs-empty-mini" data-empty-marker>
                                        <i class="fas fa-arrow-down"></i>
                                        Aucune sortie configurée — ajoutez-en une ci-dessous.
                                    </div>
                                @endforelse
                            </div>

                            @can('bts_tronc_commun.manage_targets')
                                @if($candidates->isNotEmpty())
                                    <form class="fs-add-form" @submit.prevent="addSortie({{ $sourceClasse->id }}, $event.target)">
                                        @csrf
                                        <x-au-select
                                            name="target_classe_id"
                                            placeholder="— Choisir une classe spécialité —"
                                            icon="fa-graduation-cap"
                                            :searchable="count($candidateOptions) > 6"
                                            :options="$candidateOptions" />
                                        <input type="number" name="semestre_activation" min="1" max="8" value="{{ ($filiere->semestres_tronc_commun ?? 1) + 1 }}" title="Semestre d'activation">
                                        <input type="text" name="notes" maxlength="500" placeholder="Note interne (optionnel)">
                                        <button type="submit" class="fs-btn fs-btn--primary">
                                            <i class="fas fa-plus"></i> Ajouter
                                        </button>
                                    </form>
                                @else
                                    <div class="fs-tc-warn">
                                        <i class="fas fa-circle-exclamation"></i>
                                        Aucune classe candidate (même niveau, filière non-TC). Créez d'abord les classes de spécialité dans Admin → Classes.
                                    </div>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    @endif

    {{-- ================ FOOTER ACTIONS ================ --}}
    <div style="display:flex; justify-content:space-between; gap:.55rem; flex-wrap:wrap; margin-top:1rem;">
        @can('filieres.delete')
        <button type="button" class="fs-btn fs-btn--danger" @click="showDeleteFiliereModal = true">
            <i class="fas fa-trash"></i> Supprimer la filière
        </button>
        @else
        <span></span>
        @endcan
        @can('filieres.edit')
        <a href="{{ route('esbtp.filieres.edit', $filiere) }}" class="fs-btn fs-btn--primary">
            <i class="fas fa-pen"></i> Modifier la filière
        </a>
        @endcan
    </div>

    {{-- Modal confirm — Supprimer filière (form HTML classique car redirige hors AJAX) --}}
    @can('filieres.delete')
    <div class="fs-modal-backdrop" x-show="showDeleteFiliereModal" x-cloak @keydown.escape.window="showDeleteFiliereModal = false">
        <div class="fs-modal" @click.outside="showDeleteFiliereModal = false">
            <div class="fs-modal-header">
                <i class="fas fa-triangle-exclamation"></i>
                <h3>Supprimer la filière ?</h3>
            </div>
            <div class="fs-modal-body">
                <p style="margin:0 0 .65rem;">Êtes-vous sûr de vouloir supprimer la filière <strong>{{ $filiere->name }}</strong> ({{ $filiere->code }}) ?</p>
                @if(($filiere->options && $filiere->options->count() > 0) || ($filiere->classes && $filiere->classes->count() > 0))
                    <div style="background:rgba(220,38,38,.08); border:1px solid rgba(220,38,38,.2); border-radius:9px; padding:.65rem .85rem; font-size:.82rem; color:#7f1d1d;">
                        <strong>Attention :</strong> cette filière est liée à
                        @if($filiere->options && $filiere->options->count() > 0) {{ $filiere->options->count() }} option(s) @endif
                        @if(($filiere->options && $filiere->options->count() > 0) && ($filiere->classes && $filiere->classes->count() > 0)) et @endif
                        @if($filiere->classes && $filiere->classes->count() > 0) {{ $filiere->classes->count() }} classe(s) @endif. Supprimer la filière peut causer des erreurs.
                    </div>
                @endif
            </div>
            <div class="fs-modal-actions">
                <button type="button" class="fs-btn fs-btn--ghost" @click="showDeleteFiliereModal = false">Annuler</button>
                {{-- EXCEPTION ajax-no-reload-premium : suppression filière = changement majeur, redirige vers index --}}
                <form action="{{ route('esbtp.filieres.destroy', $filiere) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="fs-btn" style="background:#dc2626; color:#fff;">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modal confirm — Supprimer sortie TC (AJAX) --}}
    <div class="fs-modal-backdrop" x-show="deleteTargetModal.open" x-cloak @keydown.escape.window="deleteTargetModal.open = false">
        <div class="fs-modal" @click.outside="deleteTargetModal.open = false">
            <div class="fs-modal-header">
                <i class="fas fa-triangle-exclamation"></i>
                <h3>Supprimer cette sortie ?</h3>
            </div>
            <div class="fs-modal-body">
                <p style="margin:0;">Supprimer définitivement la sortie vers <strong x-text="deleteTargetModal.name"></strong> ? Les étudiants de cette classe TC ne pourront plus s'orienter vers cette spécialité.</p>
            </div>
            <div class="fs-modal-actions">
                <button type="button" class="fs-btn fs-btn--ghost" @click="deleteTargetModal.open = false" :disabled="saving">Annuler</button>
                <button type="button" class="fs-btn" style="background:#dc2626; color:#fff;" @click="performDelete()" :disabled="saving">
                    <span x-show="!saving">Confirmer</span>
                    <span x-show="saving" x-cloak>Suppression…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toasts --}}
    <div class="fs-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="fs-toast" :class="'fs-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function filiereShow() {
    return {
        showDeleteFiliereModal: false,
        deleteTargetModal: { open: false, id: null, name: '', sourceClasseId: null },
        toasts: [],
        toastId: 0,
        saving: false,
        urls: {
            add: '{{ route("esbtp.filieres.sorties-tc.add", $filiere) }}',
            base: '{{ url("/esbtp/filieres/" . $filiere->id . "/sorties-tc") }}',
        },

        init() {},

        toast(type, message) {
            const id = ++this.toastId;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3500);
        },

        async addSortie(sourceClasseId, formEl) {
            const fd = new FormData(formEl);
            const payload = {
                source_classe_id: sourceClasseId,
                target_classe_id: fd.get('target_classe_id'),
                semestre_activation: fd.get('semestre_activation') || null,
                notes: fd.get('notes') || null,
            };
            if (!payload.target_classe_id) {
                this.toast('error', 'Choisissez une classe spécialité.');
                return;
            }
            try {
                const res = await fetch(this.urls.add, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    const errors = Object.values(body.errors || {}).flat();
                    this.toast('error', errors.join(' · ') || (body.message || 'Validation échouée'));
                    return;
                }
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.toast('success', 'Sortie ajoutee.');
                // EXCEPTION ajax-no-reload-premium : reload justifie car
                // l ajout d une sortie change la liste des candidates + les compteurs.
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async toggleSortie(targetId, isActive, rowEl) {
            try {
                const res = await fetch(`${this.urls.base}/${targetId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ is_active: isActive ? 1 : 0 }),
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const body = await res.json();
                if (rowEl) rowEl.classList.toggle('is-inactive', !body.is_active);
                this.toast('success', body.is_active ? 'Sortie activée.' : 'Sortie désactivée.');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        confirmDelete(targetId, name, sourceClasseId) {
            this.deleteTargetModal = { open: true, id: targetId, name: name, sourceClasseId: sourceClasseId };
        },

        async performDelete() {
            const id = this.deleteTargetModal.id;
            if (!id) return;
            this.saving = true;
            try {
                const res = await fetch(`${this.urls.base}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                // Retirer la row du DOM
                const row = document.querySelector(`[data-target-id="${id}"]`);
                if (row) row.remove();
                this.toast('success', 'Sortie supprimee.');
                this.deleteTargetModal.open = false;
                // EXCEPTION ajax-no-reload-premium : reload justifie car
                // la suppression peut faire reapparaitre la classe en candidate + maj compteurs.
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.toast('error', e.message);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
@endsection
