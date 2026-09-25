@extends('layouts.app')

@section('title', 'Années universitaires')

@push('styles')
<style>
.auy-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
.auy-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.auy-hero-left { display: flex; align-items: center; gap: 1rem; }
.auy-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
.auy-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.auy-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .2rem 0 0; }
.auy-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: 10px; font-size: .84rem; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .2s ease; }
.auy-btn--white { background: #fff; color: #0453cb; }
.auy-btn--white:hover { color: #033a8e; box-shadow: 0 6px 18px rgba(15,23,42,.18); }
.auy-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; margin-top: 1.5rem; }
.auy-kpi { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; }
.auy-kpi-value { font-size: 1.3rem; font-weight: 700; color: #fff; white-space: nowrap; }
.auy-kpi-label { font-size: .72rem; color: rgba(255,255,255,.68); margin-top: .15rem; }

.auy-alert { padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .88rem; font-weight: 600; display: flex; gap: .5rem; align-items: center; }
.auy-alert--ok { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.25); }
.auy-alert--ko { background: rgba(220,38,38,.07); color: #b91c1c; border: 1px solid rgba(220,38,38,.2); }

.auy-list { display: grid; gap: .75rem; }
.auy-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.1rem 1.25rem; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; align-items: center; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); transition: box-shadow .2s ease, border-color .2s ease; }
.auy-card:hover { border-color: #c7d4e5; box-shadow: 0 8px 26px rgba(4,83,203,.08); }
.auy-card--current { border-color: rgba(4,83,203,.45); box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.auy-card-head { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.auy-name { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.auy-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .18rem .55rem; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.auy-badge--current { background: #0453cb; color: #fff; }
.auy-badge--active { background: rgba(4,83,203,.1); color: #0453cb; }
.auy-badge--inactive { background: #f1f5f9; color: #64748b; }
.auy-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .8rem; color: #64748b; margin-top: .35rem; }
.auy-meta i { color: #94a3b8; margin-right: .3rem; }
.auy-desc { font-size: .8rem; color: #475569; margin-top: .35rem; }
.auy-progress { height: 6px; background: #eef2f7; border-radius: 99px; overflow: hidden; margin-top: .65rem; max-width: 420px; }
.auy-progress > span { display: block; height: 100%; background: linear-gradient(90deg, #0453cb, #5e91de); border-radius: 99px; }
.auy-progress-label { font-size: .72rem; color: #64748b; margin-top: .25rem; }
.auy-actions { display: flex; gap: .4rem; flex-wrap: wrap; justify-content: flex-end; }
.auy-act { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .75rem; border-radius: 9px; font-size: .78rem; font-weight: 600; text-decoration: none; border: 1px solid #e2e8f0; background: #fff; color: #0453cb; cursor: pointer; transition: all .2s ease; }
.auy-act:hover { border-color: #0453cb; background: rgba(4,83,203,.05); color: #033a8e; }
.auy-act--primary { background: #0453cb; color: #fff; border-color: #0453cb; }
.auy-act--primary:hover { background: #033a8e; color: #fff; }
.auy-act--danger { color: #dc2626; }
.auy-act--danger:hover { border-color: #dc2626; background: rgba(220,38,38,.05); color: #b91c1c; }
.auy-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 2.5rem 1.5rem; text-align: center; color: #64748b; }
.auy-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: .75rem; }

@media (max-width: 768px) {
    .auy-hero { padding: 1.25rem 1.1rem 1rem; border-radius: 14px; }
    .auy-hero h1 { font-size: 1.2rem; }
    .auy-card { grid-template-columns: 1fr; }
    .auy-actions { justify-content: flex-start; }
}
</style>
@endpush

@section('content')
@php
    $auyListe = $anneesUniversitaires->filter()->values();
    $auyCourante = $auyListe->firstWhere('is_current', true);
    $auyDate = fn ($d) => $d ? \Carbon\Carbon::parse($d) : null;
    $auyFinCourante = $auyCourante ? $auyDate($auyCourante->end_date) : null;
    $auyJoursRestants = $auyFinCourante ? (int) now()->startOfDay()->diffInDays($auyFinCourante, false) : null;
@endphp
<div class="container-fluid">
    <div class="auy-hero">
        <div class="auy-hero-top">
            <div class="auy-hero-left">
                <div class="auy-hero-icon"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <h1>Années universitaires</h1>
                    <p>L’année en cours décide de ce que voient les inscriptions, les notes et la comptabilité.</p>
                </div>
            </div>
            @can('annees.create')
                <a href="{{ route('esbtp.annees-universitaires.create') }}" class="auy-btn auy-btn--white">
                    <i class="fas fa-plus-circle"></i>Nouvelle année
                </a>
            @endcan
        </div>
        <div class="auy-kpis">
            <div class="auy-kpi">
                <div class="auy-kpi-value">{{ $auyCourante->name ?? 'Aucune' }}</div>
                <div class="auy-kpi-label">Année en cours</div>
            </div>
            <div class="auy-kpi">
                <div class="auy-kpi-value">
                    @if($auyJoursRestants === null) —
                    @elseif($auyJoursRestants < 0) Terminée
                    @else {{ $auyJoursRestants }} j
                    @endif
                </div>
                <div class="auy-kpi-label">Avant la fin de l’année en cours</div>
            </div>
            <div class="auy-kpi">
                <div class="auy-kpi-value">{{ $auyListe->count() }}</div>
                <div class="auy-kpi-label">Années enregistrées</div>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="auy-alert auy-alert--ok"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="auy-alert auy-alert--ko"><i class="fas fa-circle-exclamation"></i>{{ session('error') }}</div>@endif

    @if($auyListe->isEmpty())
        <div class="auy-empty">
            <i class="fas fa-calendar-xmark"></i>
            Aucune année universitaire n’a été créée.
            @can('annees.create')
                <div style="margin-top:1rem;"><a href="{{ route('esbtp.annees-universitaires.create') }}" class="auy-act auy-act--primary"><i class="fas fa-plus"></i>Créer la première année</a></div>
            @endcan
        </div>
    @else
        <div class="auy-list">
            @foreach($auyListe as $annee)
                @php
                    $debut = $auyDate($annee->start_date);
                    $fin = $auyDate($annee->end_date);
                    $avancement = null;
                    if ($debut && $fin && $fin->gt($debut)) {
                        $avancement = (int) round(max(0, min(1, $debut->diffInDays(now(), false) / max(1, $debut->diffInDays($fin)))) * 100);
                    }
                @endphp
                <article class="auy-card {{ $annee->is_current ? 'auy-card--current' : '' }}">
                    <div>
                        <div class="auy-card-head">
                            <h2 class="auy-name">{{ $annee->name ?? 'Année sans nom' }}</h2>
                            @if($annee->is_current)
                                <span class="auy-badge auy-badge--current"><i class="fas fa-circle-dot"></i>En cours</span>
                            @elseif($annee->is_active)
                                <span class="auy-badge auy-badge--active">Active</span>
                            @else
                                <span class="auy-badge auy-badge--inactive">Inactive</span>
                            @endif
                        </div>
                        <div class="auy-meta">
                            <span><i class="fas fa-play"></i>Rentrée {{ $debut?->format('d/m/Y') ?? '—' }}</span>
                            <span><i class="fas fa-flag-checkered"></i>Fin {{ $fin?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                        @if($annee->description)
                            <div class="auy-desc">{{ \Illuminate\Support\Str::limit($annee->description, 140) }}</div>
                        @endif
                        @if($avancement !== null && $annee->is_current)
                            <div class="auy-progress" role="progressbar" aria-valuenow="{{ $avancement }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $avancement }}%;"></span></div>
                            <div class="auy-progress-label">{{ $avancement }} % de l’année écoulée</div>
                        @endif
                    </div>
                    <div class="auy-actions">
                        @can('annees.view')
                            <a href="{{ route('esbtp.annees-universitaires.show', $annee) }}" class="auy-act"><i class="fas fa-eye"></i>Voir</a>
                        @endcan
                        @can('annees.edit')
                            <a href="{{ route('esbtp.annees-universitaires.edit', $annee) }}" class="auy-act"><i class="fas fa-pen"></i>Modifier</a>
                        @endcan
                        @can('annees.set_current')
                            @unless($annee->is_current)
                                <form action="{{ route('esbtp.annees-universitaires.set-current', $annee) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="auy-act auy-act--primary"><i class="fas fa-check-circle"></i>Définir comme année en cours</button>
                                </form>
                            @endunless
                        @endcan
                        @can('annees.delete')
                            <button type="button" class="auy-act auy-act--danger" data-bs-toggle="modal" data-bs-target="#auyDelete{{ $annee->id }}"><i class="fas fa-trash"></i>Supprimer</button>
                        @endcan
                    </div>
                </article>

                @can('annees.delete')
                <div class="modal fade" id="auyDelete{{ $annee->id }}" tabindex="-1" aria-labelledby="auyDeleteLabel{{ $annee->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                            <div class="modal-header" style="background:#dc2626;color:#fff;border:none;">
                                <h5 class="modal-title" id="auyDeleteLabel{{ $annee->id }}"><i class="fas fa-trash me-2"></i>Supprimer {{ $annee->name }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body" style="font-size:.9rem;color:#334155;">
                                Cette action est définitive. Une année qui a des inscriptions ne peut pas être supprimée.
                            </div>
                            <div class="modal-footer" style="border-top:1px solid #e2e8f0;">
                                <button type="button" class="auy-act" data-bs-dismiss="modal">Annuler</button>
                                <form action="{{ route('esbtp.annees-universitaires.destroy', $annee) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="auy-act auy-act--danger"><i class="fas fa-trash"></i>Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            @endforeach
        </div>
    @endif
</div>
@endsection
