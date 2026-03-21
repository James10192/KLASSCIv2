@extends('layouts.app')

@section('title', 'Émargement — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       Émargement — Premium Redesign
       Prefix: em- (emargement)
       ══════════════════════════════════════════════ */
    .em-page { max-width: 1440px; margin: 0 auto; }

    /* Stats row */
    .em-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem; margin-bottom: 1.25rem;
        animation: em-fadeUp .4s ease-out;
    }
    .em-stat {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        padding: 1rem 1.15rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: all .2s;
    }
    .em-stat:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.06); }
    .em-stat-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; flex-shrink: 0;
    }
    .em-stat:nth-child(1) .em-stat-icon { background: rgba(4,83,203,.08); color: #0453cb; }
    .em-stat:nth-child(2) .em-stat-icon { background: rgba(16,185,129,.08); color: #10b981; }
    .em-stat:nth-child(3) .em-stat-icon { background: rgba(251,191,36,.08); color: #f59e0b; }
    .em-stat:nth-child(4) .em-stat-icon { background: rgba(129,140,248,.08); color: #6366f1; }
    .em-stat-value { font-size: 1.35rem; font-weight: 700; color: #1e293b; line-height: 1; }
    .em-stat-label { font-size: .72rem; color: #64748b; margin-top: .1rem; }

    /* Cards */
    .em-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden; margin-bottom: 1.25rem;
    }
    .em-card-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    }
    .em-card-title {
        font-size: .92rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .em-card-title i { color: #0453cb; font-size: .82rem; }
    .em-card-sub { font-size: .72rem; color: #94a3b8; }
    .em-card-body { padding: 1.15rem 1.25rem; }

    /* Active code display */
    .em-code-display {
        text-align: center; padding: 1.5rem;
        background: linear-gradient(135deg, rgba(4,83,203,.03), rgba(59,125,219,.05));
        border-radius: 12px; margin-bottom: 1rem;
    }
    .em-code-value {
        font-family: 'Courier New', monospace; font-size: 2.5rem; font-weight: 900;
        color: #0453cb; letter-spacing: .1em;
    }
    .em-code-meta { display: flex; justify-content: center; gap: 1.5rem; margin-top: .75rem; }
    .em-code-meta-item { font-size: .78rem; color: #64748b; display: flex; align-items: center; gap: .3rem; }
    .em-code-meta-item i { font-size: .7rem; color: #0453cb; }

    /* Multi codes grid */
    .em-codes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .65rem; }
    .em-code-card {
        background: #f8fafc; border-radius: 10px; padding: .85rem;
        border: 1px solid #e8ecf1; text-align: center;
    }
    .em-code-card-value {
        font-family: 'Courier New', monospace; font-size: 1.3rem; font-weight: 700;
        color: #0453cb; margin-bottom: .3rem;
    }
    .em-code-card-info { font-size: .72rem; color: #64748b; }
    .em-code-card-expire { font-size: .68rem; color: #10b981; margin-top: .2rem; }

    /* Séances list */
    .em-seance {
        display: flex; align-items: center; gap: .85rem;
        padding: .7rem .85rem; border-radius: 10px;
        border: 1px solid #f1f5f9; margin-bottom: .5rem;
        transition: all .15s; cursor: pointer; background: #fff;
    }
    .em-seance:hover { background: #f8fafc; border-color: #e2e8f0; }
    .em-seance-time {
        min-width: 65px; text-align: center; flex-shrink: 0;
    }
    .em-seance-hours { font-size: .82rem; font-weight: 700; color: #1e293b; }
    .em-seance-day { font-size: .65rem; color: #94a3b8; }
    .em-seance-info { flex: 1; min-width: 0; }
    .em-seance-mat { font-size: .82rem; font-weight: 600; color: #1e293b; }
    .em-seance-class { font-size: .72rem; color: #64748b; }
    .em-seance-teacher { font-size: .68rem; color: #94a3b8; }
    .em-seance-action {
        padding: .3rem .6rem; border-radius: 6px; font-size: .68rem; font-weight: 600;
        background: rgba(16,185,129,.08); color: #059669; border: none; white-space: nowrap;
    }
    .em-seance.passed { opacity: .5; pointer-events: none; }
    .em-seance.passed .em-seance-action { background: #f1f5f9; color: #94a3b8; }

    /* Quick actions */
    .em-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .em-action-btn {
        display: flex; align-items: center; gap: .4rem;
        padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600;
        text-decoration: none; border: none; cursor: pointer; transition: all .2s;
    }
    .em-action-btn--primary { background: linear-gradient(135deg,#0453cb,#3b7ddb); color: #fff; box-shadow: 0 2px 6px rgba(4,83,203,.2); }
    .em-action-btn--primary:hover { background: linear-gradient(135deg,#0347b0,#2d6bc4); color: #fff; }
    .em-action-btn--secondary { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
    .em-action-btn--secondary:hover { background: #e2e8f0; color: #1e293b; }

    /* History items */
    .em-history-item {
        display: flex; align-items: center; gap: .75rem;
        padding: .65rem 0; border-bottom: 1px solid #f8fafc;
    }
    .em-history-item:last-child { border-bottom: none; }
    .em-history-code {
        font-family: 'Courier New', monospace; font-size: .85rem; font-weight: 700;
        color: #0453cb; min-width: 60px;
    }
    .em-history-info { flex: 1; min-width: 0; }
    .em-history-desc { font-size: .8rem; font-weight: 500; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .em-history-meta { font-size: .7rem; color: #94a3b8; }
    .em-history-badge {
        font-size: .65rem; font-weight: 600; padding: .2rem .5rem; border-radius: 5px;
    }
    .em-history-badge.active { background: rgba(16,185,129,.1); color: #059669; }
    .em-history-badge.expired { background: rgba(239,68,68,.08); color: #dc2626; }
    .em-history-badge.neutral { background: #f1f5f9; color: #64748b; }

    /* Grid layout */
    .em-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr 1fr; }
    .em-grid--full { grid-column: 1 / -1; }

    /* Empty state */
    .em-empty { text-align: center; padding: 2rem; color: #94a3b8; }
    .em-empty i { font-size: 1.3rem; display: block; margin-bottom: .5rem; }

    @keyframes em-fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    @media (max-width: 768px) { .em-grid { grid-template-columns: 1fr; } .em-code-value { font-size: 1.8rem; } }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <x-planning-header
            title="Codes d'Émargement"
            subtitle="Génération et gestion des codes d'émargement pour le suivi des présences"
            active-tab="emargement"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
            :stats="$heroStats ?? null"
        />

        <div id="pg-tab-content">
        <div class="em-page">

        {{-- Stats --}}
        <div class="em-stats">
            <div class="em-stat">
                <div class="em-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="em-stat-value">{{ $stats['total_emargements_aujourd_hui'] }}</div>
                    <div class="em-stat-label">Émargements aujourd'hui</div>
                </div>
            </div>
            <div class="em-stat">
                <div class="em-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div class="em-stat-value">{{ $stats['enseignants_emarges_aujourd_hui'] }}</div>
                    <div class="em-stat-label">Enseignants émargés</div>
                </div>
            </div>
            <div class="em-stat">
                <div class="em-stat-icon"><i class="fas fa-key"></i></div>
                <div>
                    <div class="em-stat-value">{{ $stats['codes_generes_semaine'] }}</div>
                    <div class="em-stat-label">Codes cette semaine</div>
                </div>
            </div>
            <div class="em-stat">
                <div class="em-stat-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="em-stat-value">{{ $stats['taux_emargement_semaine'] }}%</div>
                    <div class="em-stat-label">Taux d'émargement</div>
                </div>
            </div>
        </div>

        <div class="em-grid">
            {{-- Code actuel --}}
            <div class="em-card">
                <div class="em-card-head">
                    <div class="em-card-title"><i class="fas fa-qrcode"></i>Code Actuel</div>
                    <div class="em-card-sub">{{ $activeCodes->count() }} actif(s)</div>
                </div>
                <div class="em-card-body">
                    @if($activeCodes->isNotEmpty())
                        @if($activeCodes->count() == 1)
                            @php $ac = $activeCodes->first(); @endphp
                            <div class="em-code-display">
                                <div class="em-code-value">{{ $ac->code }}</div>
                                <div class="em-code-meta">
                                    <div class="em-code-meta-item"><i class="fas fa-calendar-plus"></i>{{ $ac->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="em-code-meta-item"><i class="fas fa-clock"></i>Jusqu'à {{ $ac->valid_until->format('H:i') }}</div>
                                    <div class="em-code-meta-item"><i class="fas fa-user"></i>{{ $ac->generator->name ?? 'Système' }}</div>
                                </div>
                            </div>
                            @if($ac->seance)
                            <div style="font-size:.82rem; color:#64748b; text-align:center;">
                                <i class="fas fa-link" style="font-size:.7rem; color:#0453cb;"></i>
                                {{ $ac->seance->matiere?->name }} — {{ $ac->seance->classe?->nom }}
                            </div>
                            @endif
                        @else
                            <div class="em-codes-grid">
                                @foreach($activeCodes as $code)
                                <div class="em-code-card">
                                    <div class="em-code-card-value">{{ $code->code }}</div>
                                    <div class="em-code-card-info">
                                        @if($code->seance) {{ $code->seance->matiere?->name ?? 'Séance' }}
                                        @else Code général @endif
                                    </div>
                                    <div class="em-code-card-expire"><i class="fas fa-clock" style="font-size:.6rem;"></i> Jusqu'à {{ $code->valid_until->format('H:i') }}</div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="em-empty">
                            <i class="fas fa-exclamation-circle"></i>
                            <div style="font-size:.85rem;">Aucun code actif</div>
                            <div style="font-size:.75rem; margin-top:.2rem;">Générez un code pour les émargements</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions rapides --}}
            <div class="em-card">
                <div class="em-card-head">
                    <div class="em-card-title"><i class="fas fa-bolt"></i>Actions Rapides</div>
                </div>
                <div class="em-card-body">
                    <div class="em-actions" style="margin-bottom:1rem;">
                        <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}" style="display:contents;">
                            @csrf
                            <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                            <input type="hidden" name="type" value="journee">
                            <input type="hidden" name="duree" value="8">
                            <input type="hidden" name="activation" value="immediate">
                            <button type="submit" class="em-action-btn em-action-btn--primary" onclick="return confirm('Générer un code journée (8h) ?')">
                                <i class="fas fa-sun"></i>Code Journée
                            </button>
                        </form>
                        <button type="button" class="em-action-btn em-action-btn--secondary" data-bs-toggle="modal" data-bs-target="#codeModal">
                            <i class="fas fa-cogs"></i>Personnalisé
                        </button>
                        <a href="{{ route('esbtp.attendance-codes.index') }}" class="em-action-btn em-action-btn--secondary">
                            <i class="fas fa-external-link-alt"></i>Interface complète
                        </a>
                    </div>

                    {{-- Séances à venir --}}
                    @if($seancesAVenir->isNotEmpty())
                        @php
                            $now = \Carbon\Carbon::now();
                            $vraiSeancesAVenir = $seancesAVenir->filter(function($seance) use ($now) {
                                if ($seance->date_seance) {
                                    $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                    return \Carbon\Carbon::parse($seance->date_seance . ' ' . $heureDebut)->gt($now);
                                }
                                $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                $seanceToday = \Carbon\Carbon::today()->setTimeFromTimeString($heureDebut);
                                return !($now->isToday() && $seanceToday->lt($now));
                            });
                        @endphp
                        @if($vraiSeancesAVenir->isNotEmpty())
                        <div style="font-size:.78rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.5rem;">
                            <i class="fas fa-calendar-plus" style="color:#0453cb;"></i> Séances disponibles ({{ $vraiSeancesAVenir->count() }})
                        </div>
                        @foreach($vraiSeancesAVenir->take(5) as $seance)
                            <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}" style="display:contents;">
                                @csrf
                                <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                                <input type="hidden" name="type" value="session">
                                <input type="hidden" name="seance_id" value="{{ $seance->id }}">
                                <input type="hidden" name="duree" value="{{ ceil($seance->getDuration() / 60) }}">
                                <input type="hidden" name="activation" value="immediate">
                                <input type="hidden" name="description" value="Code pour {{ $seance->matiere?->name }} - {{ $seance->classe?->nom }}">
                                <button type="submit" class="em-seance" onclick="return confirm('Générer un code pour cette séance ?')">
                                    <div class="em-seance-time">
                                        <div class="em-seance-hours">{{ $seance->heure_debut->format('H:i') }}</div>
                                        <div class="em-seance-day">{{ $seance->date_seance ? \Carbon\Carbon::parse($seance->date_seance)->format('d/m') : 'Récurrent' }}</div>
                                    </div>
                                    <div class="em-seance-info">
                                        <div class="em-seance-mat">{{ $seance->matiere?->name ?? 'Matière' }}</div>
                                        <div class="em-seance-class">{{ $seance->classe?->nom ?? 'Classe' }}</div>
                                        @if($seance->teacher)<div class="em-seance-teacher">{{ $seance->teacher->name }}</div>@endif
                                    </div>
                                    <div class="em-seance-action"><i class="fas fa-plus-circle" style="font-size:.6rem;"></i> Code</div>
                                </button>
                            </form>
                        @endforeach
                        @endif
                    @endif
                </div>
            </div>

            {{-- Historique --}}
            <div class="em-card em-grid--full">
                <div class="em-card-head">
                    <div class="em-card-title"><i class="fas fa-history"></i>Codes Récents</div>
                    <div class="em-card-sub">{{ $recentCodes->count() }} dernier(s)</div>
                </div>
                <div class="em-card-body">
                    @forelse($recentCodes as $code)
                    <div class="em-history-item">
                        <div class="em-history-code">{{ $code->code }}</div>
                        <div class="em-history-info">
                            <div class="em-history-desc">
                                @if($code->seance) {{ $code->seance->matiere?->name }} — {{ $code->seance->classe?->nom }}
                                @else {{ $code->description ?? 'Code général' }} @endif
                            </div>
                            <div class="em-history-meta">{{ $code->created_at->format('d/m/Y H:i') }}@if($code->generator) · {{ $code->generator->name }}@endif</div>
                        </div>
                        @if($code->status === 'active' && $code->valid_until > now())
                            <span class="em-history-badge active">Actif</span>
                        @elseif($code->status === 'active' || $code->status === 'expired')
                            <span class="em-history-badge expired">Expiré</span>
                        @else
                            <span class="em-history-badge neutral">{{ ucfirst($code->status) }}</span>
                        @endif
                    </div>
                    @empty
                    <div class="em-empty"><i class="fas fa-inbox"></i>Aucun code récent</div>
                    @endforelse
                </div>
            </div>
        </div>

        </div>
        </div>{{-- #pg-tab-content --}}

        {{-- Modal code personnalisé --}}
        <div class="modal fade" id="codeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.15);">
                    <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}">
                        @csrf
                        <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                        <input type="hidden" name="type" value="personnalise">
                        <input type="hidden" name="activation" value="immediate">

                        <div class="modal-header" style="background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb); border:none; padding:1.25rem 1.5rem;">
                            <div>
                                <h5 style="color:#fff; font-weight:700; font-size:1rem; margin:0; display:flex; align-items:center; gap:.5rem;">
                                    <i class="fas fa-sliders-h" style="font-size:.85rem;"></i>Code Personnalisé
                                </h5>
                                <div style="font-size:.75rem; color:rgba(255,255,255,.7); margin-top:.15rem;">Paramètres de génération du code d'émargement</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:brightness(0) invert(1); opacity:.7;"></button>
                        </div>

                        <div class="modal-body" style="padding:1.25rem 1.5rem; background:#f8fafc;">
                            <div style="background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem; margin-bottom:.85rem;">
                                <label style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">Description</label>
                                <input type="text" name="description" id="em-custom-description" placeholder="Ex: Cours de rattrapage..."
                                    style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.88rem;">
                                <div style="font-size:.68rem; color:#94a3b8; margin-top:.25rem;">Optionnel — pour identifier ce code</div>
                            </div>

                            <div style="display:flex; gap:.75rem; margin-bottom:.85rem;">
                                <div style="flex:1; background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem;">
                                    <label style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">
                                        <i class="fas fa-clock" style="color:#0453cb; font-size:.65rem;"></i> Durée
                                    </label>
                                    <select name="duree" id="em-custom-duration" style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                                        <option value="1">1 heure</option>
                                        <option value="2" selected>2 heures</option>
                                        <option value="3">3 heures</option>
                                        <option value="4">4 heures</option>
                                        <option value="8">Journée (8h)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.15rem;">
                                <label style="font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:block;">
                                    <i class="fas fa-calendar-check" style="color:#0453cb; font-size:.65rem;"></i> Séance à venir
                                    <span style="font-weight:400; text-transform:none; letter-spacing:0; color:#94a3b8;">(optionnel)</span>
                                </label>
                                <select name="seance_id" id="em-seance-select" style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                                    <option value="">Aucune séance spécifique</option>
                                    @if($seancesAVenir->isNotEmpty())
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $upcomingOnly = $seancesAVenir->filter(function($s) use ($now) {
                                                if ($s->date_seance) {
                                                    $h = \Carbon\Carbon::parse($s->heure_debut)->format('H:i:s');
                                                    return \Carbon\Carbon::parse($s->date_seance . ' ' . $h)->gt($now);
                                                }
                                                $h = \Carbon\Carbon::parse($s->heure_debut)->format('H:i:s');
                                                return !\Carbon\Carbon::today()->setTimeFromTimeString($h)->lt($now);
                                            });
                                        @endphp
                                        @foreach($upcomingOnly as $seance)
                                        <option value="{{ $seance->id }}"
                                                data-duration="{{ ceil($seance->getDuration() / 60) }}"
                                                data-description="{{ ($seance->matiere->name ?? 'Matière') }} - {{ ($seance->classe->nom ?? 'Classe') }}">
                                            {{ $seance->matiere->name ?? 'Matière' }} — {{ $seance->classe->nom ?? 'Classe' }}
                                            ({{ $seance->heure_debut->format('H:i') }}–{{ $seance->heure_fin->format('H:i') }})
                                            @if($seance->teacher) · {{ $seance->teacher->name }}@endif
                                        </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div style="font-size:.68rem; color:#94a3b8; margin-top:.25rem;">Remplit automatiquement la durée et la description</div>
                            </div>
                        </div>

                        <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:.85rem 1.5rem; background:#fff; gap:.4rem;">
                            <button type="button" class="btn" data-bs-dismiss="modal"
                                style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0;">
                                Annuler
                            </button>
                            <button type="submit" class="btn"
                                style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); border:none; box-shadow:0 2px 6px rgba(4,83,203,.25);">
                                <i class="fas fa-key me-1" style="font-size:.7rem;"></i>Générer le code
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('em-seance-select')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                const dur = opt.dataset.duration;
                const desc = opt.dataset.description;
                if (dur) document.getElementById('em-custom-duration').value = dur;
                if (desc) document.getElementById('em-custom-description').value = desc;
            }
        });
        </script>
    </div>
</div>
@endsection
