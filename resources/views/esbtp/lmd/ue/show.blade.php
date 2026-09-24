@extends('layouts.app')

@section('title', "Unite d'Enseignement — " . $ue->name)

@section('content')
<div class="lmd-page">

    <div class="lmd-hero">
        <div class="lmd-hero-bar">
            <div>
                <div class="lmd-hero-title">
                    <i class="fas fa-cube me-2"></i>{{ $ue->name }}
                </div>
                <div class="lmd-hero-subtitle">
                    @if($ue->code)
                        <span class="lmd-hero-code">{{ $ue->code }}</span>
                    @else
                        <span class="lmd-hero-code lmd-hero-code--muted">Sans code</span>
                    @endif
                    @if($ue->type_ue)
                        <span class="lmd-hero-sep">·</span>{{ $ue->type_ue->label() }}
                    @endif
                    @if(!$ue->is_active)
                        <span class="lmd-hero-sep">·</span><span class="lmd-badge-inactive">Desactivee</span>
                    @endif
                </div>
            </div>
            <div class="lmd-hero-actions">
                <a href="{{ route('esbtp.lmd.ue.index') }}" class="lmd-btn lmd-btn--glass">
                    <i class="fas fa-arrow-left me-1"></i> Retour a la liste
                </a>
                <a href="{{ route('esbtp.lmd.ue.edit', $ue) }}" class="lmd-btn lmd-btn--white">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
            </div>
        </div>

        <div class="lmd-kpis">
            <div class="lmd-kpi">
                <div class="lmd-kpi-value">{{ $ue->credit !== null ? (int) $ue->credit : '—' }}</div>
                <div class="lmd-kpi-label">Credits de l'UE</div>
            </div>
            <div class="lmd-kpi">
                <div class="lmd-kpi-value">{{ $nbEcues }}</div>
                <div class="lmd-kpi-label">Elements constitutifs</div>
            </div>
            <div class="lmd-kpi">
                <div class="lmd-kpi-value">{{ count($rattachement['parcours']) }}</div>
                <div class="lmd-kpi-label">Parcours</div>
            </div>
            <div class="lmd-kpi">
                <div class="lmd-kpi-value">{{ $rattachement['semestres'] ? collect($rattachement['semestres'])->map(fn ($s) => 'S' . $s)->implode(' · ') : '—' }}</div>
                <div class="lmd-kpi-label">Semestre</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius:.5rem;margin-bottom:1rem;">
            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        </div>
    @endif

    <div class="lmd-form-card">
        <div class="lmd-section-title"><i class="fas fa-info-circle me-2" style="color:#0453cb;"></i>Rattachement académique</div>
        <div class="lmd-info-grid">
            <div class="lmd-info">
                <div class="lmd-info-label">Parcours</div>
                {{-- Même source que le bloc « Parcours et semestres rattachés » plus bas. --}}
                <div class="lmd-info-value">{{ $rattachement['est_partagee'] ? 'Partagée entre ' . count($rattachement['parcours']) . ' parcours (détail ci-dessous)' : ($rattachement['parcours'][0]['nom'] ?? 'Non rattachée') }}</div>
            </div>
            <div class="lmd-info">
                <div class="lmd-info-label">{{ count($rattachement['filieres']) > 1 ? 'Filières' : 'Filière' }}</div>
                <div class="lmd-info-value">{{ $rattachement['filieres'] ? implode(', ', $rattachement['filieres']) . ($rattachement['parcours_sans_filiere'] ? ' (' . $rattachement['parcours_sans_filiere'] . ' parcours sans filière)' : '') : ($ue->filiere?->name ?? 'Non renseignée') }}</div>
            </div>
            <div class="lmd-info">
                <div class="lmd-info-label">Niveau</div>
                <div class="lmd-info-value">{{ $ue->niveau?->name ?? 'Non renseigné' }}</div>
            </div>
            <div class="lmd-info">
                <div class="lmd-info-label">Semestre</div>
                <div class="lmd-info-value">{{ $rattachement['semestres'] ? (count($rattachement['semestres']) > 1 ? 'Semestres ' : 'Semestre ') . implode(', ', $rattachement['semestres']) : 'Non renseigné' }}</div>
            </div>
            <div class="lmd-info">
                <div class="lmd-info-label">Type</div>
                <div class="lmd-info-value">{{ $ue->type_ue?->label() ?? 'Non renseigné' }}</div>
            </div>
            <div class="lmd-info">
                <div class="lmd-info-label">Responsable</div>
                <div class="lmd-info-value">{{ $ue->responsableUe?->name ?? 'Non désigné' }}</div>
            </div>
        </div>

        @if($ue->description)
            <div class="lmd-description">
                <div class="lmd-info-label">Description</div>
                <p class="mb-0">{{ $ue->description }}</p>
            </div>
        @endif
    </div>

    <div class="lmd-form-card">
        <div class="lmd-section-title"><i class="fas fa-route me-2" style="color:#0453cb;"></i>Parcours et semestres rattachés</div>

        @if(empty($rattachement['parcours']))
            <div class="lmd-empty">
                <i class="fas fa-unlink d-block mb-2" style="font-size:1.4rem;"></i>
                Cette unite d'enseignement n'est rattachee a aucun parcours : elle n'apparaitra
                ni dans les calculs, ni sur les bulletins. Ouvrez « Modifier » pour choisir un
                parcours et un semestre.
            </div>
        @else
            <div class="lmd-chips">
                @foreach($rattachement['parcours'] as $lien)
                    <div class="lmd-chip">
                        <span class="lmd-chip-name">{{ $lien['nom'] }}</span>
                        @if($lien['code'])
                            <span class="lmd-chip-code">{{ $lien['code'] }}</span>
                        @endif
                        @foreach($lien['semestres'] as $semestre)
                            <span class="lmd-chip-sem">S{{ $semestre }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="lmd-form-card" x-data="{ onglet: 0 }">
        <div class="lmd-section-title" style="display:flex;justify-content:space-between;align-items:center;">
            <span><i class="fas fa-list-ul me-2" style="color:#0453cb;"></i>Elements constitutifs</span>
            <a href="{{ route('esbtp.lmd.ue.edit', $ue) }}" class="btn btn-acasi secondary btn-sm">
                <i class="fas fa-pen me-1"></i> Gerer
            </a>
        </div>

        @if(count($maquettes) > 1)
            <p class="lmd-footnote" style="margin:0 0 .75rem;">
                Cette unite sert plusieurs parcours. Chaque parcours a sa propre liste d'elements
                et ses propres heures : choisissez-le ci-dessous.
            </p>
            <div class="lmd-tabs" role="tablist">
                @foreach($maquettes as $i => $maquette)
                    <button type="button" class="lmd-tab" role="tab"
                            :class="onglet === {{ $i }} ? 'lmd-tab--active' : ''"
                            x-on:click="onglet = {{ $i }}">
                        {{ $maquette['parcours']->code ?: $maquette['parcours']->name }}
                        @if($maquette['semestre'])
                            <span class="lmd-tab-sem">S{{ $maquette['semestre'] }}</span>
                        @endif
                        <span class="lmd-tab-count">{{ $maquette['ecues']->count() }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @foreach($maquettes as $i => $maquette)
            @php
                $ecarts = $maquette['credit_ue'] !== null && $maquette['ecues']->isNotEmpty() ? $maquette['credits'] - $maquette['credit_ue'] : 0;
            @endphp
            <div x-show="onglet === {{ $i }}" @if($i > 0) x-cloak @endif>
                @if($ecarts !== 0)
                    <div class="alert alert-warning" style="border-radius:.5rem;margin-bottom:1rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Les credits des elements constitutifs totalisent {{ $maquette['credits'] }},
                        alors que l'unite d'enseignement en porte {{ $maquette['credit_ue'] }} dans cette maquette.
                    </div>
                @endif

                @if($maquette['ecues']->isEmpty())
                    <div class="lmd-empty">
                        <i class="fas fa-inbox d-block mb-2" style="font-size:1.4rem;"></i>
                        @if($maquette['parcours'])
                            Aucun element constitutif dans la maquette de ce parcours.
                        @else
                            Aucun element constitutif rattache a cette unite d'enseignement.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="lmd-ecue-table">
                            <thead>
                                <tr>
                                    <th style="width:5%;">#</th>
                                    <th>Intitule</th>
                                    <th style="width:14%;">Code</th>
                                    <th style="width:10%;">Coefficient</th>
                                    <th style="width:9%;">Credits</th>
                                    <th style="width:8%;">CM</th>
                                    <th style="width:8%;">TD</th>
                                    <th style="width:8%;">TP</th>
                                    <th style="width:9%;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($maquette['ecues'] as $index => $ecue)
                                    @php
                                        $volume = $maquette['volumes'][$ecue->id] ?? ['cm' => 0, 'td' => 0, 'tp' => 0, 'total' => 0, 'source' => 'matiere'];
                                        $credit = $ecue->pivot?->credit_ecue ?? $ecue->credit_ecue;
                                        $coefficient = $ecue->pivot?->coefficient_ecue ?? $ecue->coefficient_ecue;
                                    @endphp
                                    <tr>
                                        <td style="color:#94a3b8;font-weight:600;">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="lmd-ecue-name">{{ $ecue->name }}</span>
                                            @if($volume['source'] === 'matiere' && $volume['total'] > 0)
                                                <span class="lmd-ecue-hint" title="Heures portees par la matiere, pas encore planifiees">indicatif</span>
                                            @endif
                                        </td>
                                        <td><span class="lmd-code">{{ $ecue->code ?: '—' }}</span></td>
                                        <td>{{ $coefficient !== null && $coefficient !== '' ? rtrim(rtrim(number_format((float) $coefficient, 2, ',', ' '), '0'), ',') : '—' }}</td>
                                        <td>{{ $credit !== null && $credit !== '' ? (int) $credit : '—' }}</td>
                                        <td>{{ $volume['cm'] ?: '—' }}</td>
                                        <td>{{ $volume['td'] ?: '—' }}</td>
                                        <td>{{ $volume['tp'] ?: '—' }}</td>
                                        <td><strong>{{ $volume['total'] ?: '—' }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="lmd-ecue-total">
                                    <td colspan="4">Total</td>
                                    <td>{{ $maquette['credits'] }}</td>
                                    <td colspan="3"></td>
                                    <td>{{ $maquette['heures'] ?: '—' }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach

        <p class="lmd-footnote">
            Les volumes horaires proviennent de la maquette horaire (menu Maquettes) du parcours
            et du semestre affiches. Les valeurs marquees « indicatif » sont celles portees par
            la matiere, faute de saisie.
        </p>
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.lmd-tabs { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
.lmd-tab { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem; border-radius:8px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .15s, color .15s, border-color .15s; }
.lmd-tab:hover:not(.lmd-tab--active) { background:rgba(4,83,203,.06); color:#0453cb; }
.lmd-tab--active { background:#0453cb; border-color:#0453cb; color:#fff; }
.lmd-tab-sem { font-size:.7rem; opacity:.8; }
.lmd-tab-count { font-size:.7rem; padding:.05rem .4rem; border-radius:6px; background:rgba(15,23,42,.08); }
.lmd-tab--active .lmd-tab-count { background:rgba(255,255,255,.2); }
    /* Detail d'une unite d'enseignement — namespace lmd- (meme famille que le formulaire) */
    .lmd-hero {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        border-radius: 1rem;
        padding: 2rem;
        color: #ffffff;
        margin-bottom: 1.5rem;
    }
    .lmd-hero-bar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .lmd-hero-title { font-size: 1.5rem; font-weight: 700; margin-bottom: .35rem; }
    .lmd-hero-subtitle { opacity: .9; font-size: .9rem; }
    .lmd-hero-sep { margin: 0 .5rem; opacity: .6; }
    .lmd-hero-code {
        font-family: 'Courier New', monospace;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.22);
        border-radius: .35rem;
        padding: .1rem .45rem;
        font-size: .78rem;
        font-weight: 700;
    }
    .lmd-hero-code--muted { opacity: .7; font-style: italic; }
    .lmd-badge-inactive {
        background: rgba(255,255,255,.9);
        color: #b45309;
        border-radius: .35rem;
        padding: .1rem .45rem;
        font-size: .72rem;
        font-weight: 700;
    }
    .lmd-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }
    .lmd-btn {
        display: inline-flex; align-items: center;
        border-radius: .6rem; padding: .5rem 1rem;
        font-size: .82rem; font-weight: 600; text-decoration: none;
        transition: background .15s, box-shadow .15s;
    }
    .lmd-btn--glass { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.25); }
    .lmd-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .lmd-btn--white { background: #fff; color: #0453cb; border: 1px solid transparent; }
    .lmd-btn--white:hover { color: #033a8e; box-shadow: 0 4px 14px rgba(0,0,0,.15); }

    .lmd-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .lmd-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: .75rem;
        padding: .9rem 1rem;
    }
    .lmd-kpi-value { font-size: 1.35rem; font-weight: 700; }
    .lmd-kpi-label { font-size: .72rem; opacity: .75; margin-top: .15rem; }

    .lmd-form-card {
        background: #ffffff;
        border-radius: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .lmd-section-title {
        font-size: 1.1rem; font-weight: 700; color: #1e293b;
        margin-bottom: 1rem; padding-bottom: .5rem;
        border-bottom: 2px solid #eff6ff;
    }
    .lmd-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .lmd-info-label {
        font-size: .68rem; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: .05em; margin-bottom: .2rem;
    }
    .lmd-info-value { font-size: .95rem; font-weight: 600; color: #1e293b; }
    .lmd-description { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; color: #475569; font-size: .9rem; }

    .lmd-chips { display: flex; flex-wrap: wrap; gap: .6rem; }
    .lmd-chip {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(4,83,203,.06);
        border: 1px solid rgba(4,83,203,.18);
        border-radius: .6rem; padding: .45rem .7rem;
    }
    .lmd-chip-name { font-weight: 700; color: #1e293b; font-size: .85rem; }
    .lmd-chip-code {
        font-family: 'Courier New', monospace; font-size: .7rem; font-weight: 700;
        color: #0453cb; background: rgba(4,83,203,.1);
        border-radius: .3rem; padding: .05rem .35rem;
    }
    .lmd-chip-sem {
        font-size: .7rem; font-weight: 700; color: #fff;
        background: #3b7ddb; border-radius: .3rem; padding: .05rem .4rem;
    }

    .lmd-ecue-table { width: 100%; border-collapse: collapse; }
    .lmd-ecue-table th {
        background: #f8fafc; padding: .6rem .75rem;
        font-size: .78rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .03em;
        border-bottom: 1px solid #e2e8f0; text-align: left;
    }
    .lmd-ecue-table td {
        padding: .55rem .75rem; vertical-align: middle;
        border-bottom: 1px solid #f1f5f9; font-size: .875rem; color: #1e293b;
    }
    .lmd-ecue-total td { font-weight: 700; background: #f8fafc; border-top: 2px solid #e2e8f0; }
    .lmd-ecue-name { font-weight: 600; }
    .lmd-ecue-hint {
        margin-left: .4rem; font-size: .66rem; font-weight: 700;
        color: #b45309; background: rgba(245,158,11,.12);
        border-radius: .3rem; padding: .05rem .35rem;
    }
    .lmd-code { font-family: 'Courier New', monospace; font-size: .8rem; color: #0453cb; }
    .lmd-empty { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: .875rem; }
    .lmd-footnote { margin-top: .9rem; font-size: .75rem; color: #94a3b8; }
</style>
@endpush
