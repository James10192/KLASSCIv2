@extends('layouts.app')

@section('title', "Audit comptable")

@section('content')
<div class="container-fluid au-page">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="au-hero">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-coins"></i></div>
                <div class="au-hero-info">
                    <h1>Audit comptable</h1>
                    <p>Surveillance ciblée des opérations financières (paiements, factures, dépenses)</p>
                </div>
            </div>
            <div class="au-hero-actions">
                <a href="{{ route('esbtp.audit.index') }}" class="au-btn au-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au journal
                </a>
            </div>
        </div>

        <div class="au-kpis">
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['paiements_modifies']) }}</div>
                    <div class="au-kpi-label">Paiements modifiés (30j)</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['factures_modifiees']) }}</div>
                    <div class="au-kpi-label">Factures modifiées (30j)</div>
                </div>
            </div>
            <div class="au-kpi au-kpi--alert">
                <div class="au-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['annulations_semaine']) }}</div>
                    <div class="au-kpi-label">Annulations cette semaine</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['validations_semaine']) }}</div>
                    <div class="au-kpi-label">Validations cette semaine</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FILTRES ═══════════════════════════════ --}}
    <form method="GET" action="{{ route('esbtp.audit.comptabilite') }}" class="au-filters">
        <div class="au-filters-row">
            <x-au-select
                class="au-filter-grow"
                name="model_type"
                :value="request('model_type')"
                icon="fa-filter"
                placeholder="Tous les types financiers"
                :options="$financialModelsLabels" />
            <x-au-select
                name="event"
                :value="request('event')"
                placeholder="Tous événements"
                :options="[
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                ]" />
            <div class="au-filter-field">
                <input type="number" name="montant_min" value="{{ request('montant_min') }}" placeholder="Montant min (FCFA)" min="0">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date début">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="Date fin">
            </div>
            <button type="submit" class="au-btn au-btn--primary">
                <i class="fas fa-search"></i> Filtrer
            </button>
            <a href="{{ route('esbtp.audit.comptabilite') }}" class="au-filter-reset" title="Réinitialiser">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>

    {{-- ═══════════════════════════════ TABLEAU ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-list-ul"></i> Opérations financières auditées
                <span class="au-badge-count">{{ $audits->total() }} résultats</span>
            </div>
        </div>

        <div class="au-table-wrap">
            @if($audits->isEmpty())
                <div class="au-empty">
                    <i class="fas fa-search"></i>
                    <h3>Aucune opération trouvée</h3>
                    <p>Essayez de modifier vos critères de recherche.</p>
                </div>
            @else
                <table class="au-table">
                    <thead>
                        <tr>
                            <th>Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Montant (avant → après)</th>
                            <th class="au-th-actions">Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audits as $a)
                            @php
                                $eventLabels = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration', 'retrieved' => 'Consultation'];
                                $oldValues = is_array($a->old_values) ? $a->old_values : (json_decode($a->old_values, true) ?? []);
                                $newValues = is_array($a->new_values) ? $a->new_values : (json_decode($a->new_values, true) ?? []);
                                $oldMontant = $oldValues['montant'] ?? null;
                                $newMontant = $newValues['montant'] ?? null;
                                $modelLabel = $financialModelsLabels[$a->auditable_type] ?? class_basename($a->auditable_type);
                            @endphp
                            <tr>
                                <td>
                                    <div class="au-cell-date">
                                        <i class="far fa-clock"></i>
                                        {{ $a->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="au-cell-user">
                                        <span class="au-avatar">{{ mb_substr($a->user?->name ?? 'S', 0, 1, 'UTF-8') }}</span>
                                        <span>{{ $a->user?->name ?? 'Système' }}</span>
                                    </div>
                                </td>
                                <td><span class="au-chip au-chip--{{ $a->event }}">{{ $eventLabels[$a->event] ?? $a->event }}</span></td>
                                <td><span class="au-chip au-chip--neutral">{{ $modelLabel }}</span></td>
                                <td><code class="au-code">#{{ $a->auditable_id }}</code></td>
                                <td>
                                    @if($oldMontant !== null || $newMontant !== null)
                                        <div class="au-amount-diff">
                                            @if($oldMontant !== null)
                                                <span class="au-amount-old">{{ number_format($oldMontant, 0, ',', ' ') }} F</span>
                                            @endif
                                            @if($oldMontant !== null && $newMontant !== null)
                                                <i class="fas fa-arrow-right au-amount-arrow"></i>
                                            @endif
                                            @if($newMontant !== null)
                                                <span class="au-amount-new">{{ number_format($newMontant, 0, ',', ' ') }} F</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="au-meta-empty">—</span>
                                    @endif
                                </td>
                                <td class="au-td-actions">
                                    <a href="{{ route('esbtp.audit.show', $a->id) }}" class="au-icon-btn au-icon-btn--primary" title="Voir détail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($audits->hasPages())
            <div class="au-pagination-laravel">
                {{ $audits->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
[x-cloak] { display: none !important; }
.au-page { padding: 1rem 0; }

/* HERO */
.au-hero { position: relative; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.15); animation: au-fadeDown .5s ease-out; }
@keyframes au-fadeDown { from { opacity:0; transform:translateY(-15px); } to { opacity:1; transform:translateY(0); } }
.au-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.au-hero-left { display: flex; align-items: center; gap: 1rem; }
.au-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; color: #fff; }
.au-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
.au-hero-info p { margin: 0; opacity: .8; font-size: .88rem; color: rgba(255,255,255,.7); }
.au-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.au-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600; text-decoration: none; transition: all .2s; border: 1px solid transparent; cursor: pointer; }
.au-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.au-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.au-btn--primary { background: #0453cb; color: #fff; }
.au-btn--primary:hover { background: #033a8e; color: #fff; }

/* KPIS */
.au-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.au-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; transition: background .2s; }
.au-kpi:hover { background: rgba(255,255,255,.15); }
.au-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .95rem; color: #fff; }
.au-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.au-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; text-transform: uppercase; letter-spacing: .04em; }
.au-kpi--alert { border-color: rgba(252,165,165,.4); background: rgba(220,38,38,.18); }
.au-kpi--alert .au-kpi-icon { background: rgba(220,38,38,.3); }

/* FILTRES */
.au-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.au-filters-row { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
.au-filter-field { position: relative; display: flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; transition: all .2s; min-width: 140px; }
.au-filter-field--grow { flex: 1; min-width: 220px; }
.au-filter-field:focus-within { background: #fff; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.au-filter-field label { padding: 0 .65rem; color: #64748b; font-size: .85rem; }
.au-filter-field input, .au-filter-field select { border: none; background: transparent; outline: none; padding: .55rem .65rem .55rem 0; font-size: .85rem; color: #1e293b; flex: 1; min-width: 0; }
.au-filter-field select { padding-left: .65rem; cursor: pointer; }
.au-filter-reset { width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all .15s; text-decoration: none; }
.au-filter-reset:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }

/* CARD */
.au-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); overflow: hidden; }
.au-card-header { padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; background: #fafbfc; }
.au-card-title { display: flex; align-items: center; gap: .6rem; font-size: 1rem; font-weight: 700; color: #0f172a; }
.au-card-title i { color: #0453cb; }
.au-badge-count { background: #eff6ff; color: #0453cb; padding: .2rem .55rem; border-radius: 8px; font-size: .72rem; font-weight: 600; border: 1px solid #dbeafe; }

/* TABLE */
.au-table-wrap { overflow-x: auto; }
.au-table { width: 100%; border-collapse: collapse; }
.au-table thead th { background: #f8fafc; color: #475569; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: .85rem 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.au-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.au-table tbody tr:hover { background: #f8fafc; }
.au-table tbody tr:last-child { border-bottom: none; }
.au-table tbody td { padding: .85rem 1rem; font-size: .85rem; color: #1e293b; vertical-align: middle; }
.au-th-actions, .au-td-actions { text-align: center; width: 60px; }
.au-cell-date { display: flex; align-items: center; gap: .45rem; color: #64748b; font-variant-numeric: tabular-nums; font-size: .82rem; }
.au-cell-date i { color: #94a3b8; font-size: .8rem; }
.au-cell-user { display: flex; align-items: center; gap: .55rem; }
.au-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; flex-shrink: 0; }
.au-code { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: .78rem; background: #f1f5f9; padding: .15rem .45rem; border-radius: 6px; color: #475569; }

/* CHIPS */
.au-chip { display: inline-flex; align-items: center; padding: .25rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.2; border: 1px solid transparent; white-space: nowrap; }
.au-chip--created { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.au-chip--updated { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--deleted { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--restored { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* AMOUNT DIFF */
.au-amount-diff { display: inline-flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
.au-amount-old { color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; font-variant-numeric: tabular-nums; }
.au-amount-new { color: #065f46; background: #d1fae5; border: 1px solid #a7f3d0; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; font-variant-numeric: tabular-nums; }
.au-amount-arrow { color: #94a3b8; font-size: .75rem; }
.au-meta-empty { color: #94a3b8; font-style: italic; font-size: .82rem; }

/* ICON BTN */
.au-icon-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all .15s; text-decoration: none; }
.au-icon-btn:hover { background: #f1f5f9; color: #0453cb; border-color: #cbd5e1; }
.au-icon-btn--primary { background: #eff6ff; border-color: #dbeafe; color: #0453cb; }
.au-icon-btn--primary:hover { background: #dbeafe; color: #033a8e; }

/* EMPTY */
.au-empty { padding: 3rem 1rem; text-align: center; color: #64748b; display: flex; flex-direction: column; align-items: center; gap: .65rem; }
.au-empty i { font-size: 2.5rem; color: #cbd5e1; }
.au-empty h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.au-empty p { margin: 0; font-size: .85rem; }

/* PAGINATION laravel default */
.au-pagination-laravel { padding: 1rem 1.25rem; border-top: 1px solid #f1f5f9; background: #fafbfc; display: flex; justify-content: center; }
.au-pagination-laravel .pagination { margin: 0; }
.au-pagination-laravel .page-link { color: #0453cb; border-color: #e2e8f0; }
.au-pagination-laravel .page-item.active .page-link { background: #0453cb; border-color: #0453cb; color: #fff; }

/* RESPONSIVE */
@media (max-width: 992px) {
    .au-hero { padding: 1.5rem 1.5rem 1rem; }
    .au-hero-info h1 { font-size: 1.2rem; }
}
@media (max-width: 768px) {
    .au-filters-row { flex-direction: column; align-items: stretch; }
    .au-filter-field { min-width: 0; width: 100%; }
}
@media (max-width: 576px) {
    .au-kpis { gap: .5rem; }
    .au-kpi { min-width: calc(50% - .25rem); padding: .65rem .75rem; }
    .au-kpi-value { font-size: 1.1rem; }
}
</style>
@endpush
