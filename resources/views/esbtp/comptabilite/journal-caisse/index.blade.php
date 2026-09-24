@extends('layouts.app')

@section('title', 'Journal de caisse OHADA')

@push('styles')
<style>
/* Namespace jc-* — Journal de caisse OHADA */
.jc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.jc-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.jc-hero-left { display: flex; align-items: center; gap: 1rem; }
.jc-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.jc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; letter-spacing: -.01em; }
.jc-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
.jc-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.jc-btn {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
    padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
    transition: all .2s ease;
}
.jc-btn:hover { background: rgba(255,255,255,.22); color: #fff; text-decoration: none; transform: translateY(-1px); }
.jc-btn--white { background: #fff; color: #0453cb; border-color: transparent; }
.jc-btn--white:hover { background: #f8fafc; color: #033a8e; }

.jc-hero-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.jc-hero-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 12px; padding: .9rem 1rem;
}
.jc-hero-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-bottom: 4px; font-weight: 600; }
.jc-hero-kpi-value { font-size: 1.2rem; font-weight: 700; color: #fff; line-height: 1.1; letter-spacing: -.02em; }
.jc-hero-kpi-unit { font-size: .7rem; font-weight: 600; opacity: .65; margin-left: 4px; }

/* Filters */
.jc-filters {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.25rem; margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.jc-filters-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; }
.jc-filter-group { display: flex; flex-direction: column; gap: 4px; }
.jc-filter-label {
    font-size: .7rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .04em;
}
.jc-filter-input, .jc-filter-select {
    width: 100%; padding: .5rem .65rem;
    border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: .85rem; color: #1e293b; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.jc-filter-input:focus, .jc-filter-select:focus {
    outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.jc-filter-actions { display: flex; gap: .5rem; align-items: flex-end; }
.jc-btn-filter, .jc-btn-reset {
    padding: .5rem 1rem; border-radius: 8px; font-size: .82rem; font-weight: 600;
    border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
    transition: all .15s;
}
.jc-btn-filter { background: #0453cb; color: #fff; }
.jc-btn-filter:hover { background: #033a8e; }
.jc-btn-reset { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; text-decoration: none; }
.jc-btn-reset:hover { background: #e2e8f0; color: #1e293b; }

/* Table */
.jc-table-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    overflow: hidden;
}
.jc-table-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;
}
.jc-table-title {
    font-weight: 700; color: #1e293b; font-size: 1rem;
    display: flex; align-items: center; gap: .5rem;
}
.jc-table-title i { color: #0453cb; }
.jc-table-count { color: #64748b; font-size: .82rem; font-weight: 500; }
.jc-table-wrap { overflow-x: auto; }
.jc-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.jc-table th {
    background: #f8fafc; color: #475569;
    text-transform: uppercase; font-size: .68rem; font-weight: 700; letter-spacing: .04em;
    padding: .65rem .75rem; text-align: left;
    border-bottom: 1px solid #e2e8f0;
    position: sticky; top: 0;
}
.jc-table td { padding: .65rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.jc-table tr:hover { background: #fafbfd; }
.jc-table tr:last-child td { border-bottom: none; }
.jc-table-num { font-weight: 700; color: #0453cb; }
.jc-table-amount { font-weight: 700; color: #059669; text-align: right; white-space: nowrap; }
.jc-table-amount--rejected { color: #dc2626; }
.jc-table-amount--pending { color: #d97706; }
.jc-table-meta { font-size: .75rem; color: #64748b; }
.jc-table-empty { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
.jc-table-empty i { font-size: 2rem; color: #cbd5e1; margin-bottom: .5rem; }

.jc-statut {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    font-size: .7rem; font-weight: 700;
}
.jc-statut--validé { background: rgba(16,185,129,.12); color: #059669; }
.jc-statut--en_attente { background: rgba(245,158,11,.12); color: #b45309; }
.jc-statut--rejeté { background: rgba(220,38,38,.12); color: #b91c1c; }

.jc-pagination { padding: 1rem 1.25rem; border-top: 1px solid #f1f5f9; }

.jc-dynamic {
    position: relative;
}
.jc-dynamic.is-loading {
    opacity: .58;
    pointer-events: none;
}
.jc-dynamic.is-loading::after {
    content: "";
    position: absolute;
    top: 16px;
    right: 16px;
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 3px solid rgba(4,83,203,.18);
    border-top-color: #0453cb;
    animation: jc-spin .75s linear infinite;
    z-index: 5;
}
@keyframes jc-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .jc-hero { padding: 1.5rem 1.25rem 1rem; }
    .jc-hero h1 { font-size: 1.2rem; }
    .jc-table { font-size: .78rem; }
    .jc-table th, .jc-table td { padding: .5rem; }
}

[x-cloak] { display: none !important; }

/* =========================================================
   JOURNAL DE CAISSE — écran mobile du shell (namespace jcm-*)
   Le socle m-* vit dans public/css/mobile-shell.css.
   ========================================================= */
.jcm-screen { font-family: var(--m-font); }
.jcm-seg { margin: 0; }
.jcm-jour { display: grid; gap: 8px; }
.jcm-jour .m-sec { gap: 10px; }
.jcm-jour .m-sec b { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.jcm-solde { font-size: 12.5px; font-weight: 700; color: #0f6b4c; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0; }
.jcm-solde.neg { color: #b42318; }
.jcm-solde.zero { color: #64748b; }
/* Un montant ne se tronque jamais : c'est le titre qui cède la place. */
.jcm-jour .m-row .amt { white-space: nowrap; }
.jcm-jour .m-row.is-static { cursor: default; }
.jcm-jour .m-row.is-static:active { background: #fff; transform: none; }
.m-list.is-loading { opacity: .6; transition: opacity 120ms ease; }
.jcm-err { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; background: #fdecea; color: #a12016; border: 1px solid #f5c6c0; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; }
.jcm-err svg { width: 20px; height: 20px; }
.jcm-err-retry { border: 0; background: transparent; color: #a12016; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 6px 0; min-height: 44px; }
.jcm-suite { display: grid; }
.jcm-fin { margin: 0; text-align: center; font-size: 12px; color: #94a3b8; }
.jcm-menu-tt { display: grid; gap: 1px; min-width: 0; }
.jcm-menu-tt small { font-size: 12px; color: #64748b; font-weight: 500; }
</style>
@endpush

@section('content')
@php
    // Shell mobile : le DOM de bureau passe sous .m-only-desktop au profit de
    // l'écran m-* ci-dessous. Shell coupé : rien ne change.
    $jcmShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null) && isset($journalMobile) && is_array($journalMobile);
@endphp
<div class="{{ $jcmShell ? 'm-only-desktop' : '' }}">
<div class="dashboard-acasi">
    <div class="main-content">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div id="jc-dynamic" class="jc-dynamic">
        {{-- ── HERO ── --}}
        <div class="jc-hero">
            <div class="jc-hero-top">
                <div class="jc-hero-left">
                    <div class="jc-hero-icon"><i class="fas fa-book"></i></div>
                    <div>
                        <h1>Journal de caisse</h1>
                        <p>Livre des recettes chronologique conforme OHADA · {{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="jc-hero-actions">
                    <a href="{{ route('esbtp.comptabilite.journal-caisse.preview-pdf', request()->query()) }}" target="_blank" class="jc-btn">
                        <i class="fas fa-eye"></i> Aperçu PDF
                    </a>
                    <a href="{{ route('esbtp.comptabilite.journal-caisse.export-pdf', request()->query()) }}" class="jc-btn jc-btn--white">
                        <i class="fas fa-file-pdf"></i> Télécharger PDF
                    </a>
                </div>
            </div>

            <div class="jc-hero-kpis">
                <div class="jc-hero-kpi">
                    <div class="jc-hero-kpi-label">Lignes</div>
                    <div class="jc-hero-kpi-value">{{ number_format($totals['count'], 0, ',', ' ') }}</div>
                </div>
                <div class="jc-hero-kpi">
                    <div class="jc-hero-kpi-label">Total encaissé</div>
                    <div class="jc-hero-kpi-value">{{ number_format($totals['total'], 0, ',', ' ') }}<span class="jc-hero-kpi-unit">FCFA</span></div>
                </div>
                @foreach($totals['by_mode'] as $mode => $stat)
                <div class="jc-hero-kpi">
                    <div class="jc-hero-kpi-label">{{ $mode ?: 'Non renseigné' }}</div>
                    <div class="jc-hero-kpi-value">{{ number_format($stat['total'], 0, ',', ' ') }}<span class="jc-hero-kpi-unit">F · {{ $stat['count'] }}</span></div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── FILTERS ── --}}
        <form method="GET" action="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="jc-filters">
            <div class="jc-filters-row">
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Du</label>
                    <input type="date" name="date_debut" value="{{ $filters['date_debut'] }}" class="jc-filter-input">
                </div>
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Au</label>
                    <input type="date" name="date_fin" value="{{ $filters['date_fin'] }}" class="jc-filter-input">
                </div>
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Filière</label>
                    <select name="filiere_id" class="jc-filter-select">
                        <option value="">Toutes</option>
                        @foreach($filieres as $f)
                        <option value="{{ $f->id }}" {{ $filters['filiere_id'] == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Classe</label>
                    <select name="classe_id" class="jc-filter-select">
                        <option value="">Toutes</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $filters['classe_id'] == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Mode</label>
                    <select name="mode_paiement" class="jc-filter-select">
                        <option value="">Tous</option>
                        @foreach($modes as $m)
                        <option value="{{ $m }}" {{ $filters['mode_paiement'] == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jc-filter-group">
                    <label class="jc-filter-label">Statut</label>
                    <select name="statut" class="jc-filter-select">
                        <option value="validé" {{ $filters['statut'] == 'validé' ? 'selected' : '' }}>Validé</option>
                        <option value="en_attente" {{ $filters['statut'] == 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="rejeté" {{ $filters['statut'] == 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                        <option value="" {{ $filters['statut'] == '' ? 'selected' : '' }}>Tous</option>
                    </select>
                </div>
                <div class="jc-filter-actions">
                    <button type="submit" class="jc-btn-filter"><i class="fas fa-filter"></i> Filtrer</button>
                    <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="jc-btn-reset"><i class="fas fa-times"></i> Reset</a>
                </div>
            </div>
        </form>

        {{-- ── TABLE ── --}}
        <div class="jc-table-card">
            <div class="jc-table-header">
                <div class="jc-table-title">
                    <i class="fas fa-list-ol"></i>
                    Détail chronologique des encaissements
                </div>
                <div class="jc-table-count">{{ $totals['count'] }} ligne{{ $totals['count'] > 1 ? 's' : '' }}</div>
            </div>

            @if($paiements->total() === 0)
            <div class="jc-table-empty">
                <i class="fas fa-folder-open d-block"></i>
                <div>Aucun paiement pour cette période/critères.</div>
            </div>
            @else
            <div class="jc-table-wrap">
                <table class="jc-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>N° Reçu</th>
                            <th>Étudiant</th>
                            <th>Catégorie</th>
                            <th>Mode</th>
                            <th class="text-end">Montant</th>
                            <th>Encaissé par</th>
                            <th>Validé par</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $p)
                        <tr>
                            <td>{{ optional($p->date_paiement)->format('d/m/Y') ?? '—' }}</td>
                            <td><a href="{{ route('esbtp.paiements.show', $p->id) }}" class="jc-table-num">{{ $p->numero_recu ?: '#'.$p->id }}</a></td>
                            <td>
                                @if($p->inscription && $p->inscription->etudiant)
                                <div>{{ trim(($p->inscription->etudiant->prenoms ?? '') . ' ' . ($p->inscription->etudiant->nom ?? '')) }}</div>
                                <div class="jc-table-meta">{{ $p->inscription->etudiant->matricule ?? '—' }}{{ $p->inscription->classe ? ' · ' . $p->inscription->classe->name : '' }}</div>
                                @else
                                <span class="jc-table-meta">—</span>
                                @endif
                            </td>
                            <td>{{ $p->fraisCategory->name ?? $p->motif ?? '—' }}</td>
                            <td>{{ $p->mode_paiement ?? '—' }}</td>
                            <td class="jc-table-amount {{ $p->status === 'rejeté' ? 'jc-table-amount--rejected' : ($p->status === 'en_attente' ? 'jc-table-amount--pending' : '') }}">
                                {{ number_format((float) $p->montant, 0, ',', ' ') }}
                            </td>
                            <td>{{ $p->createdBy->name ?? '—' }}</td>
                            <td>
                                @if($p->validatedBy)
                                {{ $p->validatedBy->name }}
                                <div class="jc-table-meta">{{ optional($p->date_validation)->format('d/m/Y H:i') }}</div>
                                @else
                                <span class="jc-table-meta">—</span>
                                @endif
                            </td>
                            <td><span class="jc-statut jc-statut--{{ $p->status }}">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="jc-pagination">
                {{ $paiements->links() }}
            </div>
            @endif
        </div>
        </div>

    </div>
</div>
</div>

@if($jcmShell)
    @php
        // Écran mobile — maquette S['comptable:journal'] : app bar « Journal de
        // caisse » (Conforme OHADA · année, export), segments de mois, une
        // section par jour avec son solde, une carte-ligne par mouvement.
        $jcmAnnee = $anneeActive?->display_name ?: ($anneeActive?->name ?: null);
        $jcmSub = 'Conforme OHADA' . ($jcmAnnee ? ' · ' . $jcmAnnee : '');
        $jcmPeutExporter = auth()->user()?->can('comptabilite.journal.view') ?? false;
        $jcmCfg = [
            'devise' => 'FCFA',
            'filtres' => $journalMobile['filtres'] ?? $filters,
            'initial' => $journalMobile,
            'flash' => ['success' => session('success'), 'error' => session('error')],
            'urls' => [
                'index' => route('esbtp.comptabilite.journal-caisse.index'),
            ],
            'exports' => $jcmPeutExporter ? [
                'apercu' => ['url' => route('esbtp.comptabilite.journal-caisse.preview-pdf'), 'onglet' => true],
                'pdf' => ['url' => route('esbtp.comptabilite.journal-caisse.export-pdf'), 'onglet' => false],
            ] : [],
        ];
    @endphp
    <div class="m-only-mobile m-screen jcm-screen" x-data="jcmJournal({{ \Illuminate\Support\Js::from($jcmCfg) }})">

        <x-m.appbar title="Journal de caisse"
                    :sub="$jcmSub"
                    :back="route('esbtp.comptabilite.dashboard')"
                    back-label="Analyse financière"
                    :action="$jcmPeutExporter ? 'dl' : null"
                    action-label="Exporter le journal"
                    x-on:click="ouvrir('jcm-export')" />

        <div class="m-body" x-ref="corps">

            {{-- Segments de mois : le mois courant et les deux précédents --}}
            <div class="m-sticky">
                <div class="m-seg jcm-seg" role="tablist" aria-label="Choisir le mois">
                    <template x-for="m in mois" x-bind:key="m.cle">
                        <button type="button" role="tab"
                                x-bind:aria-selected="m.on ? 'true' : 'false'"
                                x-bind:class="m.on ? 'on' : ''"
                                x-bind:disabled="chargement"
                                x-on:click="choisirMois(m)"
                                x-text="m.libelle"></button>
                    </template>
                </div>
            </div>

            <div class="jcm-err" role="alert" x-show="erreur" x-cloak>
                <x-m.icon name="alert" />
                <span x-text="erreur"></span>
                <button type="button" class="jcm-err-retry" x-on:click="recharger()">Réessayer</button>
            </div>

            {{-- Squelette pendant un chargement qui remplace la liste --}}
            <div class="m-skel" x-show="chargement && jours.length === 0" x-cloak aria-hidden="true">
                <i></i><i></i><i></i><i></i><i></i>
            </div>

            {{-- Une section par jour : libellé, solde du jour, cartes-lignes --}}
            <template x-for="j in jours" x-bind:key="j.date">
                <section class="jcm-jour" x-bind:class="chargement ? 'is-loading' : ''" aria-live="polite">
                    <div class="m-sec">
                        <b x-text="j.libelle"></b>
                        <span class="jcm-solde" x-bind:class="toneSolde(j.solde)" x-text="'Solde ' + montant(j.solde)"></span>
                    </div>
                    <div class="m-list one">
                        <template x-for="mv in j.mouvements" x-bind:key="mv.id">
                            <a class="m-row"
                               x-bind:href="mv.url"
                               x-bind:class="mv.url ? '' : 'is-static'"
                               x-bind:aria-label="mv.titre + ', ' + montantSigne(mv)">
                                <div class="av" x-bind:class="mv.icone ? 'ic' : ''" aria-hidden="true">
                                    <template x-if="mv.icone === 'cash'">
                                        <x-m.icon name="cash" />
                                    </template>
                                    <template x-if="mv.icone === 'user'">
                                        <x-m.icon name="user" />
                                    </template>
                                    <template x-if="!mv.icone">
                                        <span x-text="mv.initiales"></span>
                                    </template>
                                </div>
                                <div class="tt">
                                    <b x-text="mv.titre"></b>
                                    <span x-text="mv.sous_titre"></span>
                                </div>
                                <div class="tr">
                                    <span class="amt" x-bind:class="mv.sortie ? 'neg' : ''" x-text="montantSigne(mv)"></span>
                                    <template x-if="mv.statut !== 'validé'">
                                        <span class="m-chip" x-bind:class="toneStatut(mv.statut)" x-text="libelleStatut(mv.statut)"></span>
                                    </template>
                                </div>
                            </a>
                        </template>
                    </div>
                </section>
            </template>

            <template x-if="!chargement && jours.length === 0 && !erreur">
                <div>
                    <x-m.empty icon="book" title="Aucun mouvement" text="Aucun encaissement ni remboursement sur cette période avec ces critères." />
                </div>
            </template>

            {{-- Pagination par jour --}}
            <div class="jcm-suite" x-show="hasMore" x-cloak>
                <button type="button" class="m-btn g" x-on:click="suite()" x-bind:disabled="chargementSuite">
                    <span x-show="!chargementSuite">Jours précédents</span>
                    <span x-show="chargementSuite" x-cloak>Chargement…</span>
                </button>
            </div>

            <p class="jcm-fin" x-show="!hasMore && jours.length > 0" x-cloak
               x-text="jours.length + ' sur ' + nbJours + (nbJours > 1 ? ' jours' : ' jour') + ' · ' + nbMouvements() + (nbMouvements() > 1 ? ' mouvements' : ' mouvement')"></p>
        </div>

        {{-- Feuille : exports — mêmes routes et mêmes filtres que le bureau --}}
        @can('comptabilite.journal.view')
            <x-m.sheet id="jcm-export" title="Exporter" sub="Le journal du mois affiché, tel qu'il est filtré.">
                <div class="m-menu">
                    <button type="button" x-on:click="exporter('apercu')">
                        <x-m.icon name="file" />
                        <span class="jcm-menu-tt">Aperçu PDF<small>Voir avant de télécharger</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                    <button type="button" x-on:click="exporter('pdf')">
                        <x-m.icon name="dl" />
                        <span class="jcm-menu-tt">Télécharger le PDF<small>Format officiel OHADA</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                </div>
            </x-m.sheet>
        @endcan
    </div>
@endif

<x-fab-encaisser />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dynamicSelector = '#jc-dynamic';

    async function refreshJournal(url, pushState = true) {
        const current = document.querySelector(dynamicSelector);
        if (!current) return;

        current.classList.add('is-loading');
        current.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.querySelector(dynamicSelector);

            if (!next) {
                window.location.href = url;
                return;
            }

            current.replaceWith(next);
            if (pushState) window.history.pushState({}, '', url);
        } catch (error) {
            window.location.href = url;
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.jc-filters');
        if (!form) return;

        event.preventDefault();

        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach((key) => {
            if (!params.get(key)) params.delete(key);
        });

        const query = params.toString();
        refreshJournal(`${form.action}${query ? `?${query}` : ''}`);
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.jc-btn-reset, .jc-pagination a[href]');
        if (!link) return;

        event.preventDefault();
        refreshJournal(link.href);
    });

    window.addEventListener('popstate', () => {
        refreshJournal(window.location.href, false);
    });
});
</script>
<script>
    /* Journal de caisse mobile (shell m-*) : la fabrique Alpine de l'ecran.
       Exposee globalement sous garde : la vue peut etre rendue plusieurs fois
       (rafraichissement AJAX du bureau) sans redeclarer la fonction. */
    if (typeof window.jcmJournal !== 'function') {
        window.jcmJournal = function (cfg) {
            var initial = cfg.initial || {};
            var filtresInit = Object.assign(
                { date_debut: '', date_fin: '', filiere_id: '', classe_id: '', mode_paiement: '', statut: '' },
                cfg.filtres || {}
            );
            Object.keys(filtresInit).forEach(function (k) { if (filtresInit[k] === null) { filtresInit[k] = ''; } });

            return {
                devise: cfg.devise || 'FCFA',
                urls: cfg.urls || {},
                exports: cfg.exports || {},
                filtres: filtresInit,
                mois: initial.mois || [],
                jours: initial.jours || [],
                hasMore: !!initial.has_more,
                nextPage: initial.next_page || 2,
                nbJours: Number(initial.nb_jours || 0),
                chargement: false,
                chargementSuite: false,
                erreur: null,
                _seq: 0,
                _onPtr: null,

                init() {
                    var self = this;
                    var flash = cfg.flash || {};
                    if (flash.success) { this.toast(flash.success, 'success'); }
                    if (flash.error) { this.toast(flash.error, 'error'); }
                    if (this.$refs.corps) {
                        // Tirer pour rafraichir : on recharge la premiere page du mois affiche.
                        this._onPtr = function () { self.recharger(); };
                        this.$refs.corps.addEventListener('m-ptr:refresh', this._onPtr);
                    }
                },
                destroy() {
                    if (this._onPtr && this.$refs.corps) {
                        this.$refs.corps.removeEventListener('m-ptr:refresh', this._onPtr);
                        this._onPtr = null;
                    }
                },

                /* ---- feuilles, toasts ---- */
                ouvrir(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
                },
                fermerTout() {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                },
                toast(message, type) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message: message } }));
                },

                /* ---- mise en forme : fr-FR, jamais tronque ---- */
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
                },
                montant(n) {
                    var v = Number(n) || 0;
                    return (v < 0 ? '− ' : '') + this.format(Math.abs(v)) + ' ' + this.devise;
                },
                montantSigne(mv) {
                    return (mv.sortie ? '− ' : '+ ') + this.format(mv.montant) + ' ' + this.devise;
                },
                toneSolde(n) {
                    var v = Number(n) || 0;
                    return v < 0 ? 'neg' : (v === 0 ? 'zero' : '');
                },
                libelleStatut(s) {
                    return { 'validé': 'Validé', 'en_attente': 'À valider', 'rejeté': 'Rejeté' }[s] || s || '—';
                },
                toneStatut(s) {
                    return { 'validé': 'ok', 'en_attente': 'warn', 'rejeté': 'bad' }[s] || 'mute';
                },
                nbMouvements() {
                    return this.jours.reduce(function (acc, j) { return acc + Number(j.nb || 0); }, 0);
                },

                /* ---- filtres : mois choisi + criteres herites de l'URL ---- */
                choisirMois(m) {
                    if (m.on) { return; }
                    this.filtres.date_debut = m.date_debut;
                    this.filtres.date_fin = m.date_fin;
                    this.recharger();
                },
                parametres() {
                    var p = new URLSearchParams();
                    var f = this.filtres;
                    Object.keys(f).forEach(function (k) { if (f[k] !== '' && f[k] !== null && f[k] !== undefined) { p.set(k, f[k]); } });
                    return p;
                },

                /* ---- chargement : la premiere page remplace, les suivantes s'ajoutent ---- */
                async charger(page, remplacer) {
                    var seq = ++this._seq;
                    if (remplacer) { this.chargement = true; } else { this.chargementSuite = true; }
                    this.erreur = null;
                    try {
                        var p = this.parametres();
                        p.set('mode', 'mobile');
                        p.set('page', String(page));
                        var res = await fetch(this.urls.index + '?' + p.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) { throw new Error('Impossible de charger le journal (' + res.status + ').'); }
                        var data = await res.json();
                        if (seq !== this._seq) { return; }
                        this.jours = remplacer ? (data.jours || []) : this.jours.concat(data.jours || []);
                        this.hasMore = !!data.has_more;
                        this.nextPage = data.next_page || (page + 1);
                        this.nbJours = Number(data.nb_jours || 0);
                        if (Array.isArray(data.mois) && data.mois.length) { this.mois = data.mois; }
                        if (data.filtres) {
                            var self = this;
                            Object.keys(data.filtres).forEach(function (k) {
                                self.filtres[k] = data.filtres[k] === null ? '' : String(data.filtres[k]);
                            });
                        }
                        if (remplacer && data.url && window.history && window.history.replaceState) {
                            window.history.replaceState(null, '', data.url);
                        }
                    } catch (e) {
                        if (seq !== this._seq) { return; }
                        this.erreur = e.message || 'Impossible de charger le journal.';
                    } finally {
                        if (seq === this._seq) {
                            this.chargement = false;
                            this.chargementSuite = false;
                        }
                    }
                },
                recharger() {
                    return this.charger(1, true);
                },
                suite() {
                    if (!this.hasMore || this.chargement || this.chargementSuite) { return; }
                    return this.charger(this.nextPage, false);
                },

                /* ---- exports : memes routes que le bureau, memes filtres ---- */
                exporter(format) {
                    var ex = this.exports[format];
                    if (!ex || !ex.url) { return; }
                    var p = this.parametres();
                    var url = ex.url + (p.toString() ? '?' + p.toString() : '');
                    this.fermerTout();
                    if (ex.onglet) {
                        // Pas de 3e argument : avec des options, window.open ouvre
                        // une popup que les bloqueurs avalent en silence.
                        var w = window.open(url, '_blank');
                        if (!w) { window.location.href = url; }
                        return;
                    }
                    window.location.href = url;
                }
            };
        };
    }
</script>
@endpush
