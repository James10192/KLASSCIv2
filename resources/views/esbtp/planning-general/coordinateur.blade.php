@extends('layouts.app')

@section('title', 'Coordinateur — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       Coordinateur — Premium Redesign
       Prefix: co- (coordinateur)
       ══════════════════════════════════════════════ */

    .co-page { max-width: 1440px; margin: 0 auto; }

    /* ── Filters bar ── */
    .co-filters {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        padding: 1rem 1.25rem; margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        display: flex; gap: .75rem; align-items: center; flex-wrap: wrap;
        animation: co-fadeUp .4s ease-out;
    }
    .co-filter-select {
        border-radius: 10px; border: 1px solid #e2e8f0; font-size: .85rem;
        padding: .45rem .75rem; min-width: 180px;
    }
    .co-filter-label {
        font-size: .72rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em;
    }

    /* ── Quick actions strip ── */
    .co-actions {
        display: flex; gap: .5rem; margin-bottom: 1.25rem; flex-wrap: wrap;
        animation: co-fadeUp .4s ease-out .05s both;
    }
    .co-action {
        display: flex; align-items: center; gap: .5rem;
        padding: .65rem 1.15rem; border-radius: 12px;
        background: #fff; border: 1px solid #e8ecf1;
        text-decoration: none; color: #1e293b; font-size: .82rem;
        font-weight: 500; transition: all .2s; flex: 1; min-width: 200px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .co-action:hover { border-color: #0453cb; color: #0453cb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.08); }
    .co-action-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; flex-shrink: 0;
    }
    .co-action--codes .co-action-icon    { background: rgba(4,83,203,.08); color: #0453cb; }
    .co-action--teachers .co-action-icon { background: rgba(16,185,129,.08); color: #10b981; }
    .co-action--students .co-action-icon { background: rgba(129,140,248,.08); color: #6366f1; }

    /* ── Section card ── */
    .co-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .co-card-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    }
    .co-card-title {
        font-size: .92rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .co-card-title i { color: #0453cb; font-size: .82rem; }
    .co-card-sub { font-size: .72rem; color: #94a3b8; }
    .co-card-body { padding: 1.15rem 1.25rem; }

    /* ── Grid layout ── */
    .co-grid { display: grid; gap: 1.25rem; }
    .co-grid--2 { grid-template-columns: 1fr 2fr; }
    .co-grid--half { grid-template-columns: 1fr 1fr; }

    /* ── Allocation items ── */
    .co-alloc {
        display: flex; align-items: center; gap: .75rem;
        padding: .7rem 0; border-bottom: 1px solid #f8fafc;
    }
    .co-alloc:last-child { border-bottom: none; }
    .co-alloc-icon {
        width: 34px; height: 34px; border-radius: 8px;
        background: rgba(4,83,203,.06); color: #0453cb;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; flex-shrink: 0;
    }
    .co-alloc-name { font-size: .85rem; font-weight: 600; color: #1e293b; }
    .co-alloc-desc { font-size: .72rem; color: #94a3b8; }
    .co-alloc-hours {
        margin-left: auto; font-size: .95rem; font-weight: 700; color: #0453cb;
        white-space: nowrap;
    }

    /* ── Schedule grid ── */
    .co-schedule {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: .65rem;
    }
    .co-day {
        background: #f8fafc; border-radius: 10px; padding: .75rem;
        border: 1px solid #f1f5f9; min-height: 100px;
    }
    .co-day-name {
        font-size: .72rem; font-weight: 700; color: #0453cb;
        text-transform: uppercase; letter-spacing: .04em; margin-bottom: .5rem;
    }
    .co-seance {
        background: #fff; border-radius: 7px; padding: .4rem .55rem;
        border: 1px solid #e8ecf1; margin-bottom: .35rem; font-size: .72rem;
    }
    .co-seance-mat { font-weight: 600; color: #1e293b; }
    .co-seance-info { color: #94a3b8; font-size: .65rem; }
    .co-day-empty { color: #cbd5e1; font-size: .75rem; text-align: center; padding: 1rem 0; }

    /* ── Emargement codes ── */
    .co-code {
        display: flex; align-items: center; gap: .75rem;
        padding: .7rem 0; border-bottom: 1px solid #f8fafc;
    }
    .co-code:last-child { border-bottom: none; }
    .co-code-badge {
        font-family: 'Courier New', monospace; font-size: .85rem; font-weight: 700;
        padding: .3rem .6rem; border-radius: 7px; letter-spacing: .08em;
    }
    .co-code.active .co-code-badge  { background: rgba(16,185,129,.1); color: #059669; }
    .co-code.expire .co-code-badge  { background: rgba(239,68,68,.08); color: #dc2626; }
    .co-code-info { flex: 1; }
    .co-code-cours { font-size: .8rem; font-weight: 500; color: #1e293b; }
    .co-code-expire { font-size: .7rem; }

    /* ── Presence bars ── */
    .co-presence {
        padding: .65rem 0; border-bottom: 1px solid #f8fafc;
    }
    .co-presence:last-child { border-bottom: none; }
    .co-presence-top {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: .35rem;
    }
    .co-presence-name { font-size: .82rem; font-weight: 600; color: #1e293b; }
    .co-presence-eff { font-size: .7rem; color: #94a3b8; }
    .co-presence-taux { font-size: .9rem; font-weight: 700; }
    .co-bar {
        height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;
    }
    .co-bar-fill {
        height: 100%; border-radius: 3px; transition: width .6s ease-out;
    }

    /* ── Quick links grid ── */
    .co-links {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .5rem;
    }
    .co-link {
        display: flex; align-items: center; gap: .5rem;
        padding: .55rem .85rem; border-radius: 9px;
        background: #f8fafc; border: 1px solid #f1f5f9;
        text-decoration: none; color: #334155; font-size: .78rem;
        font-weight: 500; transition: all .2s;
    }
    .co-link:hover { background: #f0f4ff; border-color: #0453cb; color: #0453cb; }
    .co-link i { color: #0453cb; font-size: .75rem; width: 16px; text-align: center; }

    @keyframes co-fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width: 992px) {
        .co-grid--2, .co-grid--half { grid-template-columns: 1fr; }
        .co-schedule { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 576px) {
        .co-schedule { grid-template-columns: 1fr 1fr; }
        .co-actions { flex-direction: column; }
        .co-action { min-width: unset; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <x-planning-header
            title="Interface Coordinateur"
            subtitle="Gestion avancée du planning et supervision académique"
            active-tab="coordinateur"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
            :stats="$stats ?? null"
        />

        <div id="pg-tab-content">
        <div class="co-page">

        {{-- Filters --}}
        <div class="co-filters">
            <form method="GET" style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; flex:1;">
                <div>
                    <div class="co-filter-label">Année</div>
                    <select name="annee_id" class="form-select co-filter-select" onchange="this.form.submit()">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>{{ $annee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="co-filter-label">Mois</div>
                    <select name="mois" class="form-select co-filter-select" onchange="this.form.submit()">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $mois == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $i)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div style="margin-left:auto; font-size:.8rem; color:#94a3b8;">
                    {{ $anneeSelectionnee ? $anneeSelectionnee->name : '—' }}
                </div>
            </form>
        </div>

        {{-- Quick actions --}}
        <div class="co-actions">
            <a href="{{ url('esbtp/attendance-codes') }}" class="co-action co-action--codes">
                <div class="co-action-icon"><i class="fas fa-qrcode"></i></div>
                <div>
                    <div style="font-weight:600;">Codes d'Émargement</div>
                    <div style="font-size:.72rem; color:#94a3b8;">Générer et gérer</div>
                </div>
            </a>
            <a href="{{ url('esbtp/teacher-attendance/report') }}" class="co-action co-action--teachers">
                <div class="co-action-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div style="font-weight:600;">Suivi Enseignants</div>
                    <div style="font-size:.72rem; color:#94a3b8;">Émargements</div>
                </div>
            </a>
            <a href="{{ route('esbtp.attendances.index') }}" class="co-action co-action--students">
                <div class="co-action-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div style="font-weight:600;">Appels Étudiants</div>
                    <div style="font-size:.72rem; color:#94a3b8;">Consulter les présences</div>
                </div>
            </a>
        </div>

        {{-- Main grid: Allocation + Schedule --}}
        <div class="co-grid co-grid--2">
            {{-- Allocation horaire --}}
            <div class="co-card">
                <div class="co-card-head">
                    <div class="co-card-title"><i class="fas fa-clock"></i>Allocation Horaire</div>
                </div>
                <div class="co-card-body">
                    @forelse($allocationHoraire as $allocation)
                    <div class="co-alloc">
                        <div class="co-alloc-icon"><i class="fas fa-book"></i></div>
                        <div>
                            <div class="co-alloc-name">{{ $allocation['module'] }}</div>
                            <div class="co-alloc-desc">{{ $allocation['description'] }}</div>
                        </div>
                        <div class="co-alloc-hours">{{ $allocation['heures'] }}h</div>
                    </div>
                    @empty
                    <div style="text-align:center; padding:1.5rem; color:#94a3b8; font-size:.85rem;">
                        <i class="fas fa-clock" style="font-size:1.2rem; display:block; margin-bottom:.4rem;"></i>
                        Aucune allocation
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Programmation hebdomadaire --}}
            <div class="co-card">
                <div class="co-card-head">
                    <div class="co-card-title"><i class="fas fa-calendar-week"></i>Programmation Hebdomadaire</div>
                    <div class="co-card-sub">{{ \Carbon\Carbon::create(null, $mois)->translatedFormat('F') }}</div>
                </div>
                <div class="co-card-body">
                    <div class="co-schedule">
                        @foreach($programmationHebdomadaire as $jour => $seances)
                        <div class="co-day">
                            <div class="co-day-name">{{ ucfirst($jour) }}</div>
                            @if(count($seances) > 0)
                                @foreach($seances as $seance)
                                <div class="co-seance">
                                    <div class="co-seance-mat">{{ $seance['matiere'] }}</div>
                                    <div class="co-seance-info">{{ $seance['horaire'] }} · {{ $seance['classe'] }}</div>
                                </div>
                                @endforeach
                            @else
                                <div class="co-day-empty"><i class="fas fa-moon"></i></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom grid: Émargement + Présences --}}
        <div class="co-grid co-grid--half">
            {{-- Codes émargement --}}
            <div class="co-card">
                <div class="co-card-head">
                    <div class="co-card-title"><i class="fas fa-qrcode"></i>Codes Émargement</div>
                    <div class="co-card-sub">Actifs</div>
                </div>
                <div class="co-card-body">
                    @forelse($codesEmargement as $code)
                    <div class="co-code {{ $code['expire'] ? 'expire' : 'active' }}">
                        <div class="co-code-badge">{{ $code['code'] }}</div>
                        <div class="co-code-info">
                            <div class="co-code-cours">{{ $code['cours'] }}</div>
                            <div class="co-code-expire" style="color:{{ $code['expire'] ? '#ef4444' : '#10b981' }};">{{ $code['expire_dans'] }}</div>
                        </div>
                    </div>
                    @empty
                    <div style="text-align:center; padding:1.5rem; color:#94a3b8; font-size:.85rem;">
                        <i class="fas fa-qrcode" style="font-size:1.2rem; display:block; margin-bottom:.4rem;"></i>
                        Aucun code actif
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Taux de présence --}}
            <div class="co-card">
                <div class="co-card-head">
                    <div class="co-card-title"><i class="fas fa-chart-line"></i>Taux de Présence</div>
                    <div class="co-card-sub">Par classe</div>
                </div>
                <div class="co-card-body">
                    @forelse($tauxPresenceClasses as $classe)
                    @php
                        $taux = $classe['taux'];
                        $barColor = $taux >= 80 ? '#10b981' : ($taux >= 60 ? '#f59e0b' : '#ef4444');
                    @endphp
                    <div class="co-presence">
                        <div class="co-presence-top">
                            <div>
                                <div class="co-presence-name">{{ $classe['nom'] }}</div>
                                <div class="co-presence-eff">{{ $classe['effectif'] }} étudiants</div>
                            </div>
                            <div class="co-presence-taux" style="color:{{ $barColor }};">{{ $taux }}%</div>
                        </div>
                        <div class="co-bar">
                            <div class="co-bar-fill" style="width:{{ $taux }}%; background:{{ $barColor }};"></div>
                        </div>
                    </div>
                    @empty
                    <div style="text-align:center; padding:1.5rem; color:#94a3b8; font-size:.85rem;">
                        <i class="fas fa-chart-line" style="font-size:1.2rem; display:block; margin-bottom:.4rem;"></i>
                        Aucune donnée
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="co-card">
            <div class="co-card-head">
                <div class="co-card-title"><i class="fas fa-bolt"></i>Accès Rapides</div>
            </div>
            <div class="co-card-body">
                <div class="co-links">
                    <a href="{{ route('esbtp.planning-general.annuel', ['annee_id' => request('annee_id')]) }}" class="co-link"><i class="fas fa-calendar-alt"></i>Planning Annuel</a>
                    <a href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => request('annee_id')]) }}" class="co-link"><i class="fas fa-chart-pie"></i>Répartition Matières</a>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="co-link"><i class="fas fa-table"></i>Emplois du Temps</a>
                    <a href="{{ route('esbtp.etudiants.index') }}" class="co-link"><i class="fas fa-user-graduate"></i>Étudiants</a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="co-link"><i class="fas fa-chalkboard-teacher"></i>Personnel</a>
                    <a href="{{ route('esbtp.attendances.index') }}" class="co-link"><i class="fas fa-user-check"></i>Présences</a>
                    <a href="{{ route('esbtp.evaluations.index') }}" class="co-link"><i class="fas fa-clipboard-list"></i>Évaluations</a>
                    <a href="{{ route('esbtp.notes.index') }}" class="co-link"><i class="fas fa-edit"></i>Notes</a>
                    <a href="{{ route('esbtp.bulletins.index') }}" class="co-link"><i class="fas fa-trophy"></i>Bulletins</a>
                    <a href="{{ route('esbtp.annonces.index') }}" class="co-link"><i class="fas fa-bullhorn"></i>Annonces</a>
                </div>
            </div>
        </div>

        </div>
        </div>{{-- #pg-tab-content --}}
    </div>
</div>
@endsection
