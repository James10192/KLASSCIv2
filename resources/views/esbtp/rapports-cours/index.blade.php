@extends('layouts.app')

@section('title', 'Rapports de cours')

@push('styles')
<style>
    .rc-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .rc-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .rc-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rc-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .rc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rc-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .rc-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .rc-kpi {
        flex: 1; min-width: 170px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .rc-kpi-ic {
        width: 40px; height: 40px; border-radius: 10px;
        background: rgba(255,255,255,.12);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1rem; flex-shrink: 0;
    }
    .rc-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .rc-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    .rc-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .rc-card-body { padding: 1.5rem; }

    .rc-filters { display: flex; flex-direction: column; gap: 1rem; }
    .rc-filter-row1 {
        display: grid; gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .rc-filter-row2 {
        display: grid; gap: .75rem;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.6fr) auto;
        align-items: end;
    }
    @media (max-width: 992px) {
        .rc-filter-row1 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rc-filter-row2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .rc-filter-row1 { grid-template-columns: 1fr; }
    }

    .rc-filter-field { display: flex; flex-direction: column; min-width: 0; }
    .rc-filter-label {
        font-size: .7rem; font-weight: 600;
        color: #64748b; text-transform: uppercase; letter-spacing: .04em;
        margin-bottom: .35rem;
    }
    .rc-filter-actions { display: flex; gap: .5rem; align-items: center; }

    .rc-date-range {
        display: grid; grid-template-columns: 1fr 1fr; gap: .5rem;
    }
    .rc-date-input {
        display: flex; align-items: center; gap: .35rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 0 .75rem; height: 38px;
        transition: border-color .15s, box-shadow .15s;
    }
    .rc-date-input:focus-within { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
    .rc-date-prefix {
        font-size: .72rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em;
        padding-right: .35rem; border-right: 1px solid #e2e8f0;
    }
    .rc-date-input input[type="date"] {
        border: 0; background: transparent; outline: none;
        flex: 1; min-width: 0; font-size: .85rem; color: #1e293b;
        padding: 0; height: 100%;
    }
    .rc-date-input input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer; opacity: .6;
    }
    .rc-date-input input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }

    .rc-search {
        display: flex; align-items: center; gap: .5rem;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: 0 .9rem; height: 38px;
        transition: border-color .15s, box-shadow .15s;
    }
    .rc-search:focus-within { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
    .rc-search i { color: #64748b; font-size: .85rem; }
    .rc-search input { border: 0; background: transparent; outline: none; flex: 1; min-width: 0; font-size: .88rem; color: #1e293b; }

    .rc-btn-reset {
        background: transparent; color: #64748b; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: .55rem .9rem; font-size: .82rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        transition: all .2s ease;
    }
    .rc-btn-reset:hover { color: #0453cb; border-color: #0453cb; }

    .rc-btn-apply {
        background: linear-gradient(135deg,#0453cb,#3b7ddb); color: #fff;
        border: 0; border-radius: 10px; padding: .55rem 1.1rem; font-size: .85rem;
        font-weight: 600; display: inline-flex; align-items: center; gap: .4rem;
        cursor: pointer; transition: all .2s ease;
    }
    .rc-btn-apply:hover { box-shadow: 0 6px 18px rgba(4,83,203,.25); transform: translateY(-1px); }

    .rc-table { width: 100%; border-collapse: collapse; }
    .rc-table thead th {
        background: #f8fafc; color: #64748b; font-size: .72rem;
        text-transform: uppercase; letter-spacing: .04em;
        font-weight: 600; padding: .85rem 1rem; text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .rc-table tbody td {
        padding: 1rem; vertical-align: top; border-bottom: 1px solid #f1f5f9;
        font-size: .88rem; color: #1e293b;
    }
    .rc-table tbody tr:hover { background: #f8fafc; }

    .rc-meta-strong { font-weight: 600; color: #0f172a; }
    .rc-meta-muted { color: #64748b; font-size: .78rem; margin-top: .15rem; }

    .rc-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .2rem .6rem; border-radius: 999px;
        font-size: .72rem; font-weight: 600;
    }
    .rc-badge--excellent { background: #d1fae5; color: #065f46; }
    .rc-badge--good { background: #dbeafe; color: #1e40af; }
    .rc-badge--satisfactory { background: #fef3c7; color: #92400e; }
    .rc-badge--difficult { background: #fee2e2; color: #991b1b; }

    .rc-summary {
        color: #475569; font-size: .82rem; line-height: 1.45;
        max-width: 380px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .rc-action-btn {
        background: transparent; color: #0453cb; border: 1px solid #cbd5e1;
        border-radius: 8px; padding: .35rem .7rem; font-size: .78rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
        transition: all .2s ease;
    }
    .rc-action-btn:hover { background: #0453cb; color: #fff; border-color: #0453cb; }

    .rc-empty {
        padding: 3rem 2rem; text-align: center; color: #64748b;
    }
    .rc-empty i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; }
    .rc-empty h3 { font-size: 1rem; color: #1e293b; margin: 0 0 .25rem; }
    .rc-empty p { font-size: .85rem; margin: 0; }

    @media (max-width: 768px) {
        .rc-hero { padding: 1.5rem; }
        .rc-table thead { display: none; }
        .rc-table tbody td { display: block; padding: .5rem 0; border-bottom: 0; }
        .rc-table tbody tr { display: block; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="rc-hero">
        <div class="rc-hero-top">
            <div class="rc-hero-left">
                <div class="rc-hero-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h1>Rapports de cours</h1>
                    <p>Comptes-rendus soumis par les enseignants à la fin de leurs séances</p>
                </div>
            </div>
        </div>

        <div class="rc-kpis">
            <div class="rc-kpi">
                <div class="rc-kpi-ic"><i class="fas fa-calendar-week"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $kpis['total_week'] }}</div>
                    <div class="rc-kpi-label">Cette semaine</div>
                </div>
            </div>
            <div class="rc-kpi">
                <div class="rc-kpi-ic"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $kpis['total_month'] }}</div>
                    <div class="rc-kpi-label">Ce mois</div>
                </div>
            </div>
            <div class="rc-kpi">
                <div class="rc-kpi-ic"><i class="fas fa-archive"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $kpis['total_all'] }}</div>
                    <div class="rc-kpi-label">Total soumis</div>
                </div>
            </div>
            <div class="rc-kpi">
                <div class="rc-kpi-ic"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $kpis['difficult'] }}</div>
                    <div class="rc-kpi-label">Comportement difficile (mois)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rc-card mb-3">
        <div class="rc-card-body">
            <form method="GET" action="{{ route('esbtp.rapports-cours.index') }}" class="rc-filters">
                <div class="rc-filter-row1">
                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Enseignant</label>
                        <x-au-user-picker
                            name="teacher_id"
                            :value="$filters['teacher_id']"
                            :users="$teachers"
                            placeholder="— Tous les enseignants —" />
                    </div>

                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Classe</label>
                        <x-au-select
                            name="classe_id"
                            :value="$filters['classe_id']"
                            icon="fa-chalkboard"
                            placeholder="Toutes les classes"
                            :searchable="$classes->count() > 8"
                            :options="$classes->pluck('name', 'id')->toArray()" />
                    </div>

                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Matière</label>
                        <x-au-select
                            name="matiere_id"
                            :value="$filters['matiere_id']"
                            icon="fa-book"
                            placeholder="Toutes les matières"
                            :searchable="$matieres->count() > 8"
                            :options="$matieres->pluck('name', 'id')->toArray()" />
                    </div>

                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Comportement</label>
                        <x-au-select
                            name="behavior"
                            :value="$filters['behavior']"
                            icon="fa-users"
                            placeholder="Tous comportements"
                            :options="$behaviors" />
                    </div>
                </div>

                <div class="rc-filter-row2">
                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Période</label>
                        <div class="rc-date-range">
                            <div class="rc-date-input">
                                <span class="rc-date-prefix">Du</span>
                                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" aria-label="Date de début">
                            </div>
                            <div class="rc-date-input">
                                <span class="rc-date-prefix">Au</span>
                                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" aria-label="Date de fin">
                            </div>
                        </div>
                    </div>

                    <div class="rc-filter-field">
                        <label class="rc-filter-label">Recherche dans le contenu</label>
                        <div class="rc-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Mot-clé…">
                        </div>
                    </div>

                    <div class="rc-filter-actions">
                        <button type="submit" class="rc-btn-apply">
                            <i class="fas fa-filter"></i>Filtrer
                        </button>
                        @if(array_filter($filters))
                            <a href="{{ route('esbtp.rapports-cours.index') }}" class="rc-btn-reset">
                                <i class="fas fa-times"></i>Réinitialiser
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="rc-card">
        @if($reports->count() === 0)
            <div class="rc-empty">
                <i class="fas fa-inbox"></i>
                <h3>Aucun rapport de cours</h3>
                <p>Aucun rapport ne correspond à vos critères de recherche.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="rc-table">
                    <thead>
                        <tr>
                            <th>Date séance</th>
                            <th>Matière · Classe</th>
                            <th>Enseignant</th>
                            <th>Comportement</th>
                            <th>Aperçu contenu</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            @php
                                $seance = $report->seanceCours;
                                $dateSeance = $seance?->date_seance
                                    ? \Carbon\Carbon::parse($seance->date_seance)->format('d/m/Y')
                                    : '—';
                                $heure = $seance && $seance->heure_debut
                                    ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i')
                                    : null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="rc-meta-strong">{{ $dateSeance }}</div>
                                    @if($heure)<div class="rc-meta-muted">{{ $heure }}</div>@endif
                                </td>
                                <td>
                                    <div class="rc-meta-strong">{{ $seance?->matiere?->name ?? '—' }}</div>
                                    <div class="rc-meta-muted">{{ $seance?->classe?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="rc-meta-strong">{{ $report->teacher?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    <span class="rc-badge rc-badge--{{ $report->student_behavior }}">
                                        {{ $report->student_behavior_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="rc-summary">{{ $report->content_summary }}</div>
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('esbtp.rapports-cours.show', $report->id) }}" class="rc-action-btn">
                                        <i class="fas fa-eye"></i>Voir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end p-3">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
