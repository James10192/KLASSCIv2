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

/* Responsive */
@media (max-width: 768px) {
    .jc-hero { padding: 1.5rem 1.25rem 1rem; }
    .jc-hero h1 { font-size: 1.2rem; }
    .jc-table { font-size: .78rem; }
    .jc-table th, .jc-table td { padding: .5rem; }
}

[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
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

<x-fab-encaisser />
@endsection
