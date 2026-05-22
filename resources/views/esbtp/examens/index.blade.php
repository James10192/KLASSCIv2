@extends('layouts.app')

@section('title', 'Examens planifiés')

@php
    use App\Enums\ExamenStatus;
    use App\Enums\TypeExamen;

    $typeOptions = TypeExamen::selectOptions();
    $statusOptions = ExamenStatus::selectOptions();
    $classeOptions = $classes->mapWithKeys(fn ($c) => [$c->id => $c->name])->all();
    $anneeOptions = $annees->mapWithKeys(fn ($a) => [
        $a->id => $a->name . ($a->is_current ? ' (en cours)' : ''),
    ])->all();
    $matiereOptions = ($matieres ?? collect())->mapWithKeys(fn ($m) => [$m->id => $m->name])->all();
    $parcoursOptions = ($parcours ?? collect())->mapWithKeys(fn ($p) => [
        $p->id => $p->name . ($p->code ? ' · '.$p->code : ''),
    ])->all();
    $sessionOptions = ($sessions ?? collect())->mapWithKeys(fn ($s) => [
        $s->id => $s->libelle . ($s->type ? ' ('.$s->type.')' : ''),
    ])->all();
    $semestreOptions = [
        1 => 'Semestre 1', 2 => 'Semestre 2', 3 => 'Semestre 3', 4 => 'Semestre 4',
        5 => 'Semestre 5', 6 => 'Semestre 6', 7 => 'Semestre 7', 8 => 'Semestre 8',
    ];
    // Mode multi-classes UEMOA — uniquement parcours actifs (LMD)
    $parcoursListAvailable = ($parcours ?? collect())->mapWithKeys(fn ($p) => [
        $p->id => $p->name . ($p->code ? ' · '.$p->code : ''),
    ])->all();
@endphp

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ─── Hero ─── */
.exp-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.exp-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.exp-hero-left { display:flex; align-items:center; gap:1rem; }
.exp-hero-icon { width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);
    backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;
    justify-content:center;font-size:1.35rem;color:#fff;flex-shrink:0;}
.exp-hero h1 { font-size:1.45rem;font-weight:700;color:#fff;margin:0; }
.exp-hero p { color:rgba(255,255,255,.7);font-size:.88rem;margin:0; }
.exp-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; }

.exp-btn { padding:.5rem 1rem;border-radius:10px;font-size:.82rem;font-weight:600;border:1px solid;
    cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;transition:all .15s; }
.exp-btn--glass { background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2); }
.exp-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff;}
.exp-btn--white { background:#fff;color:#0453cb;border-color:transparent; }
.exp-btn--white:hover { background:#f1f5ff;color:#033a8e;}
.exp-btn--primary { background:#0453cb;color:#fff;border-color:#0453cb;}
.exp-btn--primary:hover { background:#033a8e;color:#fff;}
.exp-btn--secondary { background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.exp-btn--secondary:hover { background:#e2e8f0;color:#1e293b;}
.exp-btn:disabled { opacity:.6;cursor:wait; }

.exp-kpis { display:flex; gap:.75rem; margin-top:1.5rem; flex-wrap:wrap; }
.exp-kpi { flex:1;min-width:160px;background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;
    display:flex;align-items:center;gap:.75rem; }
.exp-kpi-icon { width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);
    display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.exp-kpi-value { font-size:1.35rem;font-weight:700;color:#fff;line-height:1; }
.exp-kpi-label { font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.2rem;text-transform:uppercase;letter-spacing:.5px;}

/* ─── Filtres premium ─── */
.exp-filters {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.25rem; margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem;
    align-items: end;
}
.exp-filter-label { font-size: .68rem; color: #64748b; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: .35rem; display:block; }
.exp-filter-date {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 9px;
    padding: .55rem .7rem; font-size: .85rem; color: #1e293b; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.exp-filter-date:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
.exp-filter-reset {
    align-self: end; padding: .55rem .8rem; border-radius: 9px;
    background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
    font-size: .82rem; font-weight: 600; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: .35rem;
}
.exp-filter-reset:hover { background: #e2e8f0; color: #0f172a; }

/* ─── Table ─── */
.exp-card { background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
    box-shadow:0 1px 3px rgba(15,23,42,.04); }
.exp-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.85rem; }
.exp-table th { background:#f8fafc;color:#475569;font-weight:600;font-size:.7rem;text-transform:uppercase;
    letter-spacing:.5px; padding:.7rem .9rem; text-align:left; border-bottom:1px solid #e2e8f0; }
.exp-table td { padding:.85rem .9rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; color:#1e293b;}
.exp-table tbody tr { cursor: pointer; transition: background .15s; }
.exp-table tbody tr:hover { background:#eff6ff; }
.exp-table tbody tr:last-child td { border-bottom: none; }

.exp-chip { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .55rem;border-radius:6px;
    font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
.exp-chip--examen { background:rgba(4,83,203,.10);color:#0453cb;border:1px solid rgba(4,83,203,.25);}
.exp-chip--partiel { background:rgba(59,125,219,.10);color:#3b7ddb;border:1px solid rgba(59,125,219,.25);}
.exp-chip--rattrapage { background:rgba(245,158,11,.10);color:#b45309;border:1px solid rgba(245,158,11,.25);}
.exp-chip--soutenance { background:rgba(16,185,129,.10);color:#047857;border:1px solid rgba(16,185,129,.25);}

.exp-status { display:inline-flex;align-items:center;gap:.3rem;padding:.22rem .55rem;border-radius:5px;
    font-size:.68rem;font-weight:700;}
.exp-status--draft { background:rgba(100,116,139,.10);color:#475569;}
.exp-status--planned { background:rgba(4,83,203,.10);color:#0453cb;}
.exp-status--in_progress { background:rgba(245,158,11,.10);color:#b45309;}
.exp-status--completed { background:rgba(16,185,129,.10);color:#047857;}
.exp-status--notes_locked { background:rgba(220,38,38,.10);color:#b91c1c;}
.exp-status--cancelled { background:rgba(100,116,139,.10);color:#475569;text-decoration:line-through;}

.exp-empty { padding:3rem 1.5rem;text-align:center;color:#64748b; }
.exp-empty i { font-size:2.5rem;color:#cbd5e1;margin-bottom:1rem; }
.exp-empty h3 { font-size:1.05rem;color:#1e293b;margin:0 0 .5rem; }
.exp-empty p { font-size:.85rem;margin:0; }

/* ─── Modal ─── */
.exp-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: flex; align-items: flex-start; justify-content: center;
    padding: 3rem 1rem;
    overflow-y: auto;
}
.exp-modal {
    background: #fff; border-radius: 16px;
    width: 100%; max-width: 760px;
    box-shadow: 0 20px 60px rgba(15,23,42,.25);
    animation: expModalIn .25s ease;
}
@@keyframes expModalIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.exp-modal-header {
    background: linear-gradient(135deg, #0a3d8f, #0453cb, #3b7ddb);
    border-radius: 16px 16px 0 0;
    padding: 1.25rem 1.5rem;
    color: #fff;
    display: flex; align-items: center; justify-content: space-between;
}
.exp-modal-title { font-size: 1.1rem; font-weight: 700; margin: 0; display:flex;align-items:center;gap:.55rem; }
.exp-modal-title p { font-size: .78rem; color: rgba(255,255,255,.75); margin: .2rem 0 0; font-weight: 400; }
.exp-modal-close {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,.15); border: none; color: #fff; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
    transition: background .15s;
}
.exp-modal-close:hover { background: rgba(255,255,255,.28); }

.exp-modal-body { padding: 1.5rem; }
.exp-modal-section {
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px dashed #e2e8f0;
}
.exp-modal-section:last-of-type { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
.exp-modal-section-title {
    font-size: .72rem; color: #0453cb; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    margin-bottom: .85rem; display: flex; align-items: center; gap: .4rem;
}
.exp-modal-section-title i { font-size: .8rem; }

.exp-modal-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;
}
.exp-modal-grid .full { grid-column: 1 / -1; }
.exp-modal-grid .field label {
    display: block; font-size: .72rem; color: #475569; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: .35rem;
}
.exp-modal-grid .field input,
.exp-modal-grid .field textarea {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 9px;
    padding: .55rem .7rem; font-size: .88rem;
    background: #fff; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.exp-modal-grid .field input:focus,
.exp-modal-grid .field textarea:focus {
    outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10);
}
.exp-modal-grid .field input[type="number"]::-webkit-inner-spin-button {
    opacity: .5; cursor: pointer;
}

/* Force au-select à occuper toute la cellule grid */
.exp-modal-grid .field .au-select,
.exp-modal-grid .field .au-select-trigger { width: 100%; }
.exp-modal-grid .field .au-select-trigger { box-sizing: border-box; }

/* Pareil pour les filtres au-dessus de la table */
.exp-filters .au-select,
.exp-filters .au-select-trigger { width: 100%; }
.exp-filters .au-select-trigger { box-sizing: border-box; }
.exp-checkbox {
    display: flex; align-items: center; gap: .55rem;
    padding: .7rem .8rem; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 9px; font-size: .85rem; color: #1e293b; cursor: pointer;
    transition: background .15s, border-color .15s;
}
.exp-checkbox:hover { background: #eff6ff; border-color: #dbeafe; }
.exp-checkbox input { width: auto; margin: 0; accent-color: #0453cb; }

.exp-modal-footer {
    background: #f8fafc; border-top: 1px solid #e2e8f0;
    border-radius: 0 0 16px 16px;
    padding: 1rem 1.5rem;
    display: flex; gap: .5rem; justify-content: flex-end;
}

/* Mode toggle (Cohorte UEMOA vs Classe unique) */
.exp-mode-btn {
    flex: 1; padding: .65rem .8rem; border-radius: 10px;
    border: 1px solid #e2e8f0; background: #fff; cursor: pointer;
    font-size: .82rem; font-weight: 600; color: #475569;
    display: inline-flex; align-items: center; gap: .5rem; justify-content: center;
    transition: all .15s;
}
.exp-mode-btn:hover { background: #eff6ff; border-color: #dbeafe; color: #0453cb; }
.exp-mode-btn--active {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.exp-mode-btn--active:hover { color: #fff; background: linear-gradient(135deg, #033a8e, #0453cb); }

/* Banner d'auto-detect de scope */
.exp-scope-banner {
    display: flex; align-items: center; gap: .6rem;
    padding: .7rem 1rem; border-radius: 10px;
    font-size: .85rem; color: #fff; font-weight: 500;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
}
.exp-scope-banner--mention { background: linear-gradient(135deg, #033a8e, #0a3d8f); }
.exp-scope-banner--domaine { background: linear-gradient(135deg, #0a3d8f, #1e3a8a); }
.exp-scope-banner--classe { background: linear-gradient(135deg, #475569, #64748b); }
.exp-scope-banner i { font-size: .9rem; }

.exp-error-banner {
    margin-bottom: 1rem; padding: .75rem 1rem;
    background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.2);
    border-radius: 10px; color: #b91c1c; font-size: .85rem;
}
.exp-error-banner ul { margin: 0; padding-left: 1.2rem; }

/* ─── Toast ─── */
.exp-toasts { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1100;
    display: flex; flex-direction: column; gap: .5rem; max-width: 400px; }
.exp-toast {
    display: flex; align-items: flex-start; gap: .65rem;
    padding: .75rem 1rem; border-radius: 10px;
    background: #fff; border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15,23,42,.12);
    font-size: .85rem;
    animation: expToastIn .25s ease;
}
@@keyframes expToastIn {
    from { opacity:0; transform: translateX(8px); }
    to { opacity:1; transform: translateX(0); }
}
.exp-toast--success { border-left: 4px solid #10b981; color: #065f46; }
.exp-toast--error { border-left: 4px solid #dc2626; color: #991b1b; }
.exp-toast--info { border-left: 4px solid #0453cb; color: #1e3a8a; }
.exp-toast i { padding-top: .15rem; }

@@media (max-width: 768px) {
    .exp-hero { padding:1.25rem 1rem; }
    .exp-hero-top { flex-direction:column; align-items:flex-start; }
    .exp-kpi { min-width:48%; }
    .exp-modal-grid { grid-template-columns: 1fr; }
    .exp-modal { max-width: 100%; margin-top: 1rem; }
}
</style>
@endpush

@section('content')
<div x-data="examensIndex()" x-init="init()">

    {{-- ═══════════════════════════ HERO ═══════════════════════════ --}}
    <div class="exp-hero">
        <div class="exp-hero-top">
            <div class="exp-hero-left">
                <div class="exp-hero-icon"><i class="fas fa-pen-ruler"></i></div>
                <div>
                    <h1>Examens planifiés</h1>
                    <p>Année universitaire <strong>{{ $annee->name ?? '—' }}</strong> · workflow UEMOA</p>
                </div>
            </div>
            <div class="exp-hero-actions">
                @can('lmd.examens.manage')
                <button type="button" class="exp-btn exp-btn--white" @click="openCreateModal()">
                    <i class="fas fa-plus"></i> Nouvel examen
                </button>
                @endcan
                <a href="{{ route('esbtp.examens.convocations.preview', request()->query()) }}" target="_blank" class="exp-btn exp-btn--glass">
                    <i class="fas fa-file-pdf"></i> Convocations PDF
                </a>
            </div>
        </div>

        <div class="exp-kpis">
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.total">{{ $kpis['total'] }}</div>
                <div class="exp-kpi-label">Total examens</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-calendar-day"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.a_venir">{{ $kpis['a_venir'] }}</div>
                <div class="exp-kpi-label">À venir</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-spinner"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.en_cours">{{ $kpis['en_cours'] }}</div>
                <div class="exp-kpi-label">En cours</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-lock"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.notes_lockees">{{ $kpis['notes_lockees'] }}</div>
                <div class="exp-kpi-label">Notes verrouillées</div></div></div>
        </div>
    </div>

    {{-- ═══════════════════════════ FILTRES PREMIUM ═══════════════════════════ --}}
    <form method="GET" action="{{ route('esbtp.examens.index') }}" class="exp-filters">
        <div>
            <label class="exp-filter-label">Année universitaire</label>
            <x-au-select name="annee_universitaire_id"
                :value="$annee->id"
                :options="$anneeOptions"
                icon="fa-calendar"
                placeholder="—"
                onchange="this.form.submit()" />
        </div>
        <div>
            <label class="exp-filter-label">Classe</label>
            <x-au-select name="classe_id"
                :value="request('classe_id')"
                :options="$classeOptions"
                icon="fa-chalkboard"
                placeholder="Toutes"
                :searchable="count($classeOptions) > 8"
                onchange="this.form.submit()" />
        </div>
        <div>
            <label class="exp-filter-label">Type d'épreuve</label>
            <x-au-select name="type"
                :value="request('type')"
                :options="$typeOptions"
                icon="fa-pen-ruler"
                placeholder="Tous"
                onchange="this.form.submit()" />
        </div>
        <div>
            <label class="exp-filter-label">Statut</label>
            <x-au-select name="status"
                :value="request('status')"
                :options="$statusOptions"
                icon="fa-circle-info"
                placeholder="Tous"
                onchange="this.form.submit()" />
        </div>
        <div>
            <label class="exp-filter-label">Du</label>
            <input type="date" name="from" value="{{ request('from') }}" class="exp-filter-date" onchange="this.form.submit()">
        </div>
        <div>
            <label class="exp-filter-label">Au</label>
            <input type="date" name="to" value="{{ request('to') }}" class="exp-filter-date" onchange="this.form.submit()">
        </div>
        @if(request()->hasAny(['classe_id', 'type', 'status', 'from', 'to']))
        <a href="{{ route('esbtp.examens.index', ['annee_universitaire_id' => $annee->id]) }}" class="exp-filter-reset">
            <i class="fas fa-xmark"></i> Réinitialiser
        </a>
        @endif
    </form>

    {{-- ═══════════════════════════ TABLE ═══════════════════════════ --}}
    <div class="exp-card">
        @if($examens->isEmpty())
            <div class="exp-empty">
                <i class="fas fa-pen-ruler"></i>
                <h3>Aucun examen planifié</h3>
                <p>Créez un examen ou ajustez vos filtres pour en voir.</p>
            </div>
        @else
            <table class="exp-table">
                <thead>
                    <tr>
                        <th>Convocation</th>
                        <th>Date / Heure</th>
                        <th>Classe · Matière</th>
                        <th>Type</th>
                        <th>Salle</th>
                        <th>Coef × Barème</th>
                        <th>Statut</th>
                        <th style="width:1%;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($examens as $e)
                    @php
                        $statusLabel = ExamenStatus::labelFor($e->status);
                        $statusClass = ExamenStatus::badgeClassFor($e->status);
                    @endphp
                    <tr onclick="window.location='{{ route('esbtp.examens.show', $e) }}'">
                        <td style="font-family:'Courier New',monospace;font-size:.78rem;color:#0453cb;font-weight:700;">
                            {{ $e->numero_convocation ?? '—' }}
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ optional($e->date_debut)->format('d/m/Y') }}</div>
                            <div style="color:#64748b;font-size:.75rem;">
                                {{ optional($e->date_debut)->format('H:i') }}–{{ optional($e->date_fin)->format('H:i') }}
                                @if($e->duree_minutes) · {{ $e->duree_minutes }}min @endif
                            </div>
                        </td>
                        <td>
                            @php
                                $classeNames = $e->classes->pluck('name');
                                $primaryClasse = $classeNames->first() ?? $e->classe?->name ?? '—';
                                $extras = max(0, $classeNames->count() - 1);
                                $ueName = $e->uniteEnseignement?->name;
                            @endphp
                            <div style="font-weight:600;">
                                {{ $primaryClasse }}
                                @if($extras > 0)
                                    <span title="{{ $classeNames->skip(1)->join(', ') }}" style="display:inline-block;padding:.05rem .35rem;background:rgba(4,83,203,.10);color:#0453cb;border-radius:5px;font-size:.65rem;margin-left:.25rem;font-weight:700;">+{{ $extras }}</span>
                                @endif
                            </div>
                            <div style="color:#64748b;font-size:.75rem;">
                                {{ $e->matiere->name ?? '—' }}
                                @if($ueName) · <em style="color:#3b7ddb;">{{ $ueName }}</em> @endif
                            </div>
                        </td>
                        <td><span class="exp-chip exp-chip--{{ strtolower($e->type_examen) }}">{{ TypeExamen::labelFor($e->type_examen) }}</span></td>
                        <td>{{ $e->salle ?? '—' }}</td>
                        <td style="color:#64748b;font-size:.78rem;">coef {{ rtrim(rtrim(number_format($e->coefficient, 2, '.', ''), '0'), '.') }} × /{{ (int) $e->bareme }}</td>
                        <td>
                            <span class="exp-status {{ $statusClass }}">
                                @if($e->notes_locked) <i class="fas fa-lock"></i> @endif
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <a href="{{ route('esbtp.examens.show', $e) }}" class="exp-btn exp-btn--secondary" style="padding:.3rem .7rem;font-size:.78rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($examens->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid #e2e8f0;">
                {{ $examens->links() }}
            </div>
            @endif
        @endif
    </div>

    {{-- ═══════════════════════════ MODAL CRÉATION (AJAX no-reload) ═══════════════════════════ --}}
    @can('lmd.examens.manage')
    <div class="exp-modal-backdrop"
         x-show="modalOpen"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="closeModal()"
         @click.self="closeModal()">
        <div class="exp-modal" @click.stop>
            <div class="exp-modal-header">
                <div class="exp-modal-title">
                    <i class="fas fa-plus-circle"></i>
                    <div>
                        Nouvel examen
                        <p>Workflow UEMOA — scolarité</p>
                    </div>
                </div>
                <button type="button" class="exp-modal-close" @click="closeModal()"><i class="fas fa-xmark"></i></button>
            </div>

            <form x-ref="createForm" @submit.prevent="submitCreate()" class="exp-modal-body">
                <input type="hidden" name="annee_universitaire_id" value="{{ $annee->id }}">

                <div x-show="errors.length > 0" x-cloak class="exp-error-banner">
                    <ul>
                        <template x-for="err in errors" :key="err">
                            <li x-text="err"></li>
                        </template>
                    </ul>
                </div>

                <div>
                    {{-- Toggle mode classique vs cohorte UEMOA --}}
                    <div class="exp-modal-section" style="padding-bottom:.75rem;">
                        <div style="display:flex;gap:.5rem;">
                            <button type="button" class="exp-mode-btn"
                                    :class="mode === 'cohorte' ? 'exp-mode-btn--active' : ''"
                                    @click="switchMode('cohorte')">
                                <i class="fas fa-users"></i>
                                Mode cohorte UEMOA <span style="opacity:.7;font-weight:400;">(plusieurs classes)</span>
                            </button>
                            <button type="button" class="exp-mode-btn"
                                    :class="mode === 'classe' ? 'exp-mode-btn--active' : ''"
                                    @click="switchMode('classe')">
                                <i class="fas fa-chalkboard"></i>
                                Mode classe unique <span style="opacity:.7;font-weight:400;">(BTS / TP)</span>
                            </button>
                        </div>
                    </div>

                    {{-- ════════════ MODE COHORTE UEMOA (cascade Parcours → UE → ECUE) ════════════ --}}
                    <div class="exp-modal-section" x-show="mode === 'cohorte'" x-cloak>
                        <div class="exp-modal-section-title"><i class="fas fa-sitemap"></i> Cohorte académique (UEMOA)</div>
                        <div class="exp-modal-grid">
                            <div class="field">
                                <label>Parcours *</label>
                                <x-au-select name="parcours_id"
                                    :options="$parcoursListAvailable"
                                    icon="fa-route"
                                    placeholder="— Sélectionner un parcours —"
                                    :searchable="count($parcoursListAvailable) > 8"
                                    onchange="window.dispatchEvent(new CustomEvent('exam-parcours-changed', { detail: { id: this.value } }))" />
                            </div>
                            <div class="field">
                                <label>UE *</label>
                                <select x-ref="ueSelect" name="unite_enseignement_id"
                                        @change="onUeChange($event.target.value)"
                                        :disabled="!parcoursId || ues.length === 0"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:9px;padding:.55rem .7rem;font-size:.88rem;background:#fff;color:#1e293b;cursor:pointer;">
                                    <option value="">— UE —</option>
                                    <template x-for="ue in ues" :key="ue.id">
                                        <option :value="ue.id" x-text="ue.name + (ue.code ? ' · ' + ue.code : '')"></option>
                                    </template>
                                </select>
                                <small x-show="parcoursId && ues.length === 0 && !loadingUes" x-cloak style="color:#b91c1c;font-size:.7rem;">Aucune UE pour ce parcours.</small>
                                <small x-show="loadingUes" x-cloak style="color:#64748b;font-size:.7rem;"><i class="fas fa-spinner fa-spin"></i> Chargement…</small>
                            </div>
                            <div class="field full">
                                <label>ECUE * <span style="font-weight:400;color:#64748b;font-size:.72rem;text-transform:none;">(Élément Constitutif d'UE)</span></label>
                                <select name="matiere_id" x-model="ecueId"
                                        :disabled="!ueId || ecues.length === 0"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:9px;padding:.55rem .7rem;font-size:.88rem;background:#fff;color:#1e293b;cursor:pointer;">
                                    <option value="">— ECUE —</option>
                                    <template x-for="ecue in ecues" :key="ecue.id">
                                        <option :value="ecue.id" x-text="ecue.name + (ecue.code ? ' · ' + ecue.code : '')"></option>
                                    </template>
                                </select>
                                <small x-show="ueId && ecues.length === 0" x-cloak style="color:#b91c1c;font-size:.7rem;">Aucun ECUE configuré pour cette UE.</small>
                            </div>
                            <div class="field">
                                <label>Type d'épreuve *</label>
                                <x-au-select name="type_examen"
                                    value="EXAMEN"
                                    :options="$typeOptions"
                                    icon="fa-pen-ruler"
                                    :placeholderIsFirstOption="false" />
                            </div>
                            <div class="field">
                                <label>Semestre</label>
                                <x-au-select name="semestre"
                                    :options="$semestreOptions"
                                    icon="fa-layer-group"
                                    placeholder="—" />
                            </div>
                            <div class="field">
                                <label>Session UEMOA</label>
                                <x-au-select name="session_id"
                                    :options="$sessionOptions"
                                    icon="fa-calendar-check"
                                    placeholder="— Aucune —" />
                            </div>
                        </div>
                    </div>

                    {{-- ════════════ AUTO-DETECT + PREVIEW CLASSES ════════════ --}}
                    <div class="exp-modal-section" x-show="mode === 'cohorte' && scopeResolved" x-cloak>
                        <div class="exp-modal-section-title">
                            <i class="fas fa-bullseye"></i> Classes ciblées
                            <span x-show="resolvedClasses.length > 0" x-cloak style="margin-left:auto;font-size:.7rem;background:#eff6ff;color:#0453cb;padding:.15rem .5rem;border-radius:5px;font-weight:700;">
                                <span x-text="selectedClassesCount"></span>/<span x-text="resolvedClasses.length"></span>
                            </span>
                        </div>
                        <div class="exp-scope-banner" :class="'exp-scope-banner--' + scopeType">
                            <i class="fas fa-magic-wand-sparkles"></i>
                            Scope auto-détecté : <strong x-text="scopeLabel"></strong>
                            <button type="button" @click="cycleScope()" style="margin-left:auto;background:rgba(255,255,255,.25);border:1px solid rgba(255,255,255,.4);color:#fff;border-radius:5px;padding:.15rem .55rem;font-size:.72rem;cursor:pointer;">
                                <i class="fas fa-shuffle"></i> Changer
                            </button>
                        </div>

                        {{-- Inter-parcours toggle (si ECUE partagé) --}}
                        <div x-show="sharedParcours.length > 0" x-cloak style="margin-top:.85rem;padding:.7rem .9rem;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:9px;font-size:.82rem;">
                            <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.45rem;color:#b45309;font-weight:600;">
                                <i class="fas fa-link"></i> Cet ECUE existe aussi dans
                                <span x-text="sharedParcours.length"></span> autre(s) parcours
                            </div>
                            <template x-for="p in sharedParcours" :key="p.id">
                                <label style="display:flex;align-items:center;gap:.45rem;padding:.35rem .55rem;background:#fff;border:1px solid #fde68a;border-radius:7px;margin:.2rem 0;cursor:pointer;font-size:.8rem;">
                                    <input type="checkbox" :value="p.id" x-model="extraParcoursIds" @change="refreshClassesPreview()" style="accent-color:#b45309;">
                                    <span x-text="p.name + (p.code ? ' · ' + p.code : '')"></span>
                                </label>
                            </template>
                        </div>

                        {{-- Liste classes avec checkboxes --}}
                        <div x-show="loadingClasses" x-cloak style="padding:1.5rem;text-align:center;color:#64748b;">
                            <i class="fas fa-spinner fa-spin" style="color:#0453cb;font-size:1.2rem;"></i>
                            Calcul des classes…
                        </div>
                        <div x-show="!loadingClasses && resolvedClasses.length === 0" x-cloak style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.85rem;">
                            <i class="fas fa-circle-exclamation" style="color:#f59e0b;"></i>
                            Aucune classe trouvée pour ce scope.
                        </div>
                        <div x-show="!loadingClasses && resolvedClasses.length > 0" x-cloak
                             style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;margin-top:.85rem;max-height:240px;overflow-y:auto;padding-right:.4rem;">
                            <template x-for="cls in resolvedClasses" :key="cls.id">
                                <label style="display:flex;align-items:center;gap:.5rem;padding:.5rem .65rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:all .15s;"
                                       :style="excludedClasses.includes(cls.id) ? 'opacity:.45;background:#fafafa;' : ''">
                                    <input type="checkbox" :checked="!excludedClasses.includes(cls.id)" @change="toggleClasse(cls.id)" style="accent-color:#0453cb;">
                                    <span style="flex:1;font-size:.82rem;color:#1e293b;font-weight:500;" x-text="cls.name"></span>
                                </label>
                            </template>
                        </div>
                        <div style="display:flex;gap:.5rem;margin-top:.6rem;">
                            <button type="button" @click="excludedClasses = []" class="exp-btn exp-btn--secondary" style="font-size:.75rem;padding:.35rem .7rem;">
                                <i class="fas fa-check-double"></i> Tout cocher
                            </button>
                            <button type="button" @click="excludedClasses = resolvedClasses.map(c => c.id)" class="exp-btn exp-btn--secondary" style="font-size:.75rem;padding:.35rem .7rem;">
                                <i class="fas fa-xmark"></i> Tout décocher
                            </button>
                        </div>
                    </div>

                    {{-- ════════════ MODE CLASSE UNIQUE (legacy / BTS) ════════════ --}}
                    <div class="exp-modal-section" x-show="mode === 'classe'" x-cloak>
                        <div class="exp-modal-section-title"><i class="fas fa-chalkboard"></i> Classe + Matière</div>
                        <div class="exp-modal-grid">
                            <div class="field">
                                <label>Classe *</label>
                                <x-au-select name="classe_id"
                                    :options="$classeOptions"
                                    icon="fa-chalkboard"
                                    placeholder="— Sélectionner —"
                                    :searchable="count($classeOptions) > 8" />
                            </div>
                            <div class="field">
                                <label>Matière *</label>
                                <x-au-select name="matiere_id"
                                    :options="$matiereOptions"
                                    icon="fa-book"
                                    placeholder="— Sélectionner —"
                                    :searchable="count($matiereOptions) > 8" />
                            </div>
                            <div class="field">
                                <label>Type d'épreuve *</label>
                                <x-au-select name="type_examen"
                                    value="EXAMEN"
                                    :options="$typeOptions"
                                    icon="fa-pen-ruler"
                                    :placeholderIsFirstOption="false" />
                            </div>
                            <div class="field">
                                <label>Semestre</label>
                                <x-au-select name="semestre"
                                    :options="$semestreOptions"
                                    icon="fa-layer-group"
                                    placeholder="—" />
                            </div>
                            <div class="field">
                                <label>Session UEMOA</label>
                                <x-au-select name="session_id"
                                    :options="$sessionOptions"
                                    icon="fa-calendar-check"
                                    placeholder="— Aucune —" />
                            </div>
                        </div>
                    </div>

                    {{-- Section identité --}}
                    <div class="exp-modal-section">
                        <div class="exp-modal-section-title"><i class="fas fa-file-signature"></i> Identité de l'épreuve</div>
                        <div class="exp-modal-grid">
                            <div class="field full">
                                <label>Titre *</label>
                                <input type="text" x-model="form.titre" required maxlength="255"
                                       placeholder="Ex : Examen final — Droit des contrats — S1">
                            </div>
                            <div class="field full">
                                <label>Description (consignes étudiants)</label>
                                <textarea x-model="form.description" rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Section logistique --}}
                    <div class="exp-modal-section">
                        <div class="exp-modal-section-title"><i class="fas fa-calendar-day"></i> Logistique</div>
                        <div class="exp-modal-grid">
                            <div class="field">
                                <label>Date & heure début *</label>
                                <input type="datetime-local" x-model="form.date_debut" required>
                            </div>
                            <div class="field">
                                <label>Date & heure fin *</label>
                                <input type="datetime-local" x-model="form.date_fin" required>
                            </div>
                            <div class="field">
                                <label>Durée (minutes)</label>
                                <input type="number" x-model.number="form.duree_minutes" min="15" max="360">
                            </div>
                            <div class="field">
                                <label>Salle</label>
                                <input type="text" x-model="form.salle" maxlength="100" placeholder="Ex : Amphi A">
                            </div>
                        </div>
                    </div>

                    {{-- Section notation --}}
                    <div class="exp-modal-section">
                        <div class="exp-modal-section-title"><i class="fas fa-calculator"></i> Notation</div>
                        <div class="exp-modal-grid">
                            <div class="field">
                                <label>Coefficient</label>
                                <input type="number" x-model.number="form.coefficient" step="0.5" min="0" max="99">
                            </div>
                            <div class="field">
                                <label>Barème</label>
                                <input type="number" x-model.number="form.bareme" step="1" min="1" max="100">
                            </div>
                            <div class="field full">
                                <label class="exp-checkbox" style="font-weight:500;text-transform:none;font-size:.85rem;color:#1e293b;">
                                    <input type="checkbox" x-model="form.is_anonymous">
                                    <span><i class="fas fa-mask" style="color:#0453cb;margin-right:.35rem;"></i>
                                    Anonymiser les copies (génère un numéro d'anonymat par étudiant)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="exp-modal-footer">
                    <button type="button" class="exp-btn exp-btn--secondary" @click="closeModal()" :disabled="saving">
                        <i class="fas fa-xmark"></i> Annuler
                    </button>
                    <button type="submit" class="exp-btn exp-btn--primary" :disabled="saving || loadingOptions">
                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="saving ? 'Création…' : 'Créer l\'examen'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ═══════════════════════════ TOASTS ═══════════════════════════ --}}
    <div class="exp-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="exp-toast" :class="'exp-toast--' + t.type" x-transition.opacity>
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : (t.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info')"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

</div>

@push('scripts')
<script>
function examensIndex() {
    return {
        kpis: @json($kpis),
        modalOpen: false,
        saving: false,
        errors: [],
        toasts: [],
        toastId: 0,
        form: {
            titre: '',
            description: '',
            date_debut: '',
            date_fin: '',
            duree_minutes: 120,
            salle: '',
            coefficient: 1,
            bareme: 20,
            is_anonymous: false,
        },
        anneeId: {{ $annee->id }},

        // ─── Mode cohorte UEMOA (cascade Parcours → UE → ECUE) ───
        mode: 'cohorte',  // 'cohorte' | 'classe'
        parcoursId: '',
        ueId: '',
        ecueId: '',
        ues: [],
        ecuesAll: [],          // tous les ECUE chargés pour le parcours
        loadingUes: false,
        loadingClasses: false,
        scopeType: 'parcours',
        scopeId: null,
        scopeLabel: '',
        scopeResolved: false,
        resolvedClasses: [],
        excludedClasses: [],
        extraParcoursIds: [],
        sharedParcours: [],

        // Cycle des scopes possibles
        scopeCycleOrder: ['parcours', 'mention', 'domaine', 'classe'],
        scopeLabels: { parcours: 'Parcours', mention: 'Mention (L1 tronc commun)', domaine: 'Domaine', classe: 'Classe unique' },

        init() {
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
            window.addEventListener('exam-parcours-changed', (ev) => this.onParcoursChange(ev.detail.id));
            const params = new URLSearchParams(window.location.search);
            if (params.get('open_create') === '1') {
                this.openCreateModal();
                params.delete('open_create');
                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }
        },

        defaultForm() {
            return {
                titre: '',
                description: '',
                date_debut: '',
                date_fin: '',
                duree_minutes: 120,
                salle: '',
                coefficient: 1,
                bareme: 20,
                is_anonymous: false,
            };
        },

        get ecues() {
            // ECUE filtrés par UE sélectionnée
            if (!this.ueId) return [];
            return this.ecuesAll.filter(e => String(e.unite_enseignement_id) === String(this.ueId));
        },

        get selectedClassesCount() {
            return this.resolvedClasses.length - this.excludedClasses.length;
        },

        openCreateModal() {
            this.form = this.defaultForm();
            this.resetCohorte();
            this.errors = [];
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.errors = [];
        },

        resetCohorte() {
            this.parcoursId = '';
            this.ueId = '';
            this.ecueId = '';
            this.ues = [];
            this.ecuesAll = [];
            this.scopeResolved = false;
            this.resolvedClasses = [];
            this.excludedClasses = [];
            this.extraParcoursIds = [];
            this.sharedParcours = [];
            this.scopeType = 'parcours';
            this.scopeId = null;
            this.scopeLabel = '';
        },

        switchMode(newMode) {
            this.mode = newMode;
            if (newMode === 'classe') {
                this.resetCohorte();
            }
        },

        async onParcoursChange(parcoursId) {
            this.parcoursId = parcoursId || '';
            this.ueId = '';
            this.ecueId = '';
            this.ues = [];
            this.ecuesAll = [];
            this.scopeResolved = false;
            if (!parcoursId) return;
            this.loadingUes = true;
            try {
                const url = new URL('{{ route("esbtp.examens.ecues-by-parcours") }}', window.location.origin);
                url.searchParams.set('parcours_id', parcoursId);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                this.ues = data.groups.map(g => g.ue);
                // Tous les ECUEs en flat avec leur unite_enseignement_id
                this.ecuesAll = data.groups.flatMap(g => g.ecues.map(e => ({ ...e, unite_enseignement_id: g.ue.id })));
            } catch (e) {
                this.pushToast({ type: 'error', message: 'Erreur chargement UE/ECUE : ' + e.message });
            } finally {
                this.loadingUes = false;
            }
            // Reset scope auto sur le parcours sélectionné
            this.scopeType = 'parcours';
            this.scopeId = parseInt(parcoursId);
            this.scopeLabel = this.scopeLabels.parcours + ' #' + parcoursId;
            this.tryResolveClasses();
        },

        onUeChange(ueId) {
            this.ueId = ueId;
            this.ecueId = '';
        },

        async tryResolveClasses() {
            if (this.mode !== 'cohorte' || !this.scopeId) return;
            this.loadingClasses = true;
            this.scopeResolved = true;
            try {
                const res = await fetch('{{ route("esbtp.examens.resolve-scope-classes") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        scope_type: this.scopeType,
                        scope_id: this.scopeId,
                        parcours_ids: this.extraParcoursIds,
                        matiere_id: this.ecueId || null,
                    }),
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                this.resolvedClasses = data.classes;
                this.sharedParcours = data.shared_parcours;
                this.excludedClasses = [];
                // Re-affiner le label de scope avec le nombre
                this.scopeLabel = this.scopeLabels[this.scopeType] + ' — ' + this.resolvedClasses.length + ' classe' + (this.resolvedClasses.length > 1 ? 's' : '');
            } catch (e) {
                this.pushToast({ type: 'error', message: 'Erreur résolution classes : ' + e.message });
                this.resolvedClasses = [];
                this.sharedParcours = [];
            } finally {
                this.loadingClasses = false;
            }
        },

        async refreshClassesPreview() {
            await this.tryResolveClasses();
        },

        cycleScope() {
            // Cycle parcours → mention → domaine → classe → parcours
            const idx = this.scopeCycleOrder.indexOf(this.scopeType);
            const next = this.scopeCycleOrder[(idx + 1) % this.scopeCycleOrder.length];
            this.scopeType = next;
            // scope_id : conserve le parcours_id par défaut, l'utilisateur ajustera via filière/mention si besoin
            if (next === 'mention' || next === 'domaine') {
                // On garde temporairement scope_id du parcours, le backend résoudra via la classe parent
                // Simplification : scope_id devient null → mode "cohérent avec parcours sélectionné" si besoin
                // Note : pour mention/domaine précis, l'utilisateur peut rester sur parcours et utiliser inter-parcours toggle
            }
            this.tryResolveClasses();
        },

        toggleClasse(classeId) {
            const idx = this.excludedClasses.indexOf(classeId);
            if (idx >= 0) this.excludedClasses.splice(idx, 1);
            else this.excludedClasses.push(classeId);
        },

        async submitCreate() {
            this.errors = [];
            this.saving = true;
            try {
                const formEl = this.$refs.createForm;
                // FormData lit tous les inputs nommés (y compris les hidden selects
                // du composant premium au-select qui sont la source de vérité)
                const fd = new FormData(formEl);
                const payload = {};
                fd.forEach((v, k) => {
                    if (v === '' || v === null) return;
                    payload[k] = v;
                });
                // Merge avec les champs Alpine non couverts par FormData (textarea/inputs liés via x-model)
                Object.assign(payload, this.cleanPayload(this.form));
                payload.is_anonymous = this.form.is_anonymous ? 1 : 0;

                // Mode cohorte → ajouter scope + classe_ids résolues - exclusions
                if (this.mode === 'cohorte') {
                    payload.scope_type = this.scopeType;
                    payload.scope_id = this.scopeId;
                    payload.parcours_ids = this.extraParcoursIds;
                    payload.classe_ids = this.resolvedClasses
                        .filter(c => !this.excludedClasses.includes(c.id))
                        .map(c => c.id);
                    if (!payload.classe_ids.length) {
                        this.errors = ['Sélectionnez au moins une classe ciblée.'];
                        this.saving = false;
                        return;
                    }
                    // En cohorte, classe_id principale = la 1ère cochée
                    payload.classe_id = payload.classe_ids[0];
                } else {
                    payload.scope_type = 'classe';
                }

                const res = await fetch('{{ route("esbtp.examens.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    this.errors = Object.values(body.errors || { error: ['Validation échouée'] }).flat();
                    this.saving = false;
                    return;
                }
                if (!res.ok) throw new Error('Erreur HTTP ' + res.status);
                const data = await res.json();
                this.kpis = data.kpis;
                this.modalOpen = false;
                this.pushToast({ type: 'success', message: 'Examen créé : ' + (data.examen.numero_convocation || data.examen.titre) });
                // Reload après 600ms pour afficher le nouvel examen dans la table
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.errors = [e.message];
                this.pushToast({ type: 'error', message: e.message });
            } finally {
                this.saving = false;
            }
        },

        cleanPayload(form) {
            const out = {};
            Object.entries(form).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) return;
                out[k] = v;
            });
            return out;
        },

        async refreshKpis() {
            try {
                const url = new URL('{{ route("esbtp.examens.kpis") }}', window.location.origin);
                url.searchParams.set('annee_universitaire_id', {{ $annee->id }});
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (res.ok) this.kpis = await res.json();
            } catch (e) { /* silent */ }
        },

        pushToast(detail) {
            const id = ++this.toastId;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        },
    };
}
</script>
@endpush

@endsection
