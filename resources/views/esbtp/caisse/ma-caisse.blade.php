@extends('layouts.app')

@section('title', 'Ma caisse - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .mc-page {
        --mc-primary: #0453cb;
        --mc-ink: #1e293b;
        --mc-muted: #475569;
        --mc-line: #dbe4f0;
        --mc-ok: #047857;
        --mc-ok-bg: #ecfdf5;
        --mc-wait: #9a3412;
        --mc-wait-bg: #fff7ed;
        max-width: 1180px;
    }
    .mc-page .dashboard-header {
        border: 1px solid var(--mc-line);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.05));
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .mc-page .header-actions {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .mc-page .header-left h1 { color: var(--mc-primary); }
    .mc-head {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }
    .mc-head-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0453cb;
        color: #fff;
        flex-shrink: 0;
    }
    .mc-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.4rem;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .mc-status.open { background: var(--mc-ok-bg); color: var(--mc-ok); }
    .mc-status.closed { background: #e2e8f0; color: #334155; }
    .mc-status.auto { background: var(--mc-wait-bg); color: var(--mc-wait); }
    .mc-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 1rem;
        align-items: start;
    }
    .mc-panel {
        background: #fff;
        border: 1px solid var(--mc-line);
        border-radius: 12px;
        overflow: hidden;
    }
    .mc-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        padding: 1rem 1.1rem 0.75rem;
        border-bottom: 1px solid #eef2f7;
    }
    .mc-panel-head h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--mc-ink);
        text-wrap: balance;
    }
    .mc-panel-head span {
        color: var(--mc-muted);
        font-size: 0.82rem;
        font-variant-numeric: tabular-nums;
    }
    .mc-table { width: 100%; border-collapse: collapse; }
    .mc-table th {
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--mc-muted);
        padding: 0.55rem 0.9rem;
        border-bottom: 1px solid var(--mc-line);
        background: #f8fafc;
    }
    .mc-table td {
        padding: 0.7rem 0.9rem;
        border-bottom: 1px solid #eef2f7;
        color: var(--mc-ink);
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .mc-table tbody tr:last-child td { border-bottom: 0; }
    .mc-table tbody tr { transition: background-color 180ms cubic-bezier(0.16, 1, 0.3, 1); }
    .mc-table tbody tr:hover td { background: #f8fafc; }
    .mc-time {
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        color: #334155;
    }
    .mc-amount { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mc-badge {
        display: inline-flex;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .mc-badge.ok { background: var(--mc-ok-bg); color: var(--mc-ok); }
    .mc-badge.wait { background: #e0f2fe; color: #075985; }
    .mc-badge.no { background: #f1f5f9; color: #334155; }
    .mc-empty {
        padding: 2.2rem 1.25rem;
        text-align: center;
        color: var(--mc-muted);
    }
    .mc-empty strong { display: block; color: var(--mc-ink); margin-bottom: 0.35rem; }
    .mc-drawer {
        padding: 1.15rem 1.15rem 1.25rem;
    }
    .mc-drawer-label {
        margin: 0;
        color: var(--mc-muted);
        font-size: 0.82rem;
    }
    .mc-drawer-amount {
        margin: 0.2rem 0 0;
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--mc-ink);
        font-variant-numeric: tabular-nums;
        line-height: 1.15;
    }
    .mc-drawer-hint {
        margin: 0.65rem 0 0;
        color: var(--mc-muted);
        font-size: 0.84rem;
        max-width: 36ch;
    }
    .mc-settle {
        margin-top: 1rem;
        width: 100%;
        border-collapse: collapse;
    }
    .mc-settle td {
        padding: 0.45rem 0;
        border-bottom: 1px solid #eef2f7;
        font-size: 0.88rem;
        color: var(--mc-ink);
    }
    .mc-settle tr:last-child td { border-bottom: 0; }
    .mc-settle td:last-child {
        text-align: right;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .mc-out {
        margin-top: 1rem;
        padding-top: 0.9rem;
        border-top: 1px solid #eef2f7;
    }
    .mc-out h3 {
        margin: 0 0 0.55rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--mc-muted);
    }
    .mc-out-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.35rem 0;
        font-size: 0.86rem;
        color: var(--mc-ink);
    }
    .mc-out-row strong { font-variant-numeric: tabular-nums; }
    .mc-drawer-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1.1rem;
    }
    .mc-drawer-actions .btn-acasi { justify-content: center; }
    .mc-dialog {
        border: 1px solid var(--mc-line);
        border-radius: 12px;
        padding: 0;
        max-width: 440px;
        width: calc(100% - 32px);
        color: var(--mc-ink);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
    }
    .mc-dialog::backdrop { background: rgba(15, 23, 42, 0.45); }
    .mc-dialog form { padding: 1.25rem; }
    .mc-dialog h3 { margin: 0 0 0.5rem; font-size: 1.05rem; }
    .mc-dialog p { margin: 0 0 1rem; color: var(--mc-muted); font-size: 0.9rem; }
    .mc-dialog .form-label-moderne { color: var(--mc-ink); }
    .mc-dialog-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
    @media (max-width: 960px) {
        .mc-layout { grid-template-columns: 1fr; }
        .mc-page .header-actions { width: 100%; flex-wrap: wrap; }
    }
    @media (prefers-reduced-motion: reduce) {
        .mc-table tbody tr { transition: none; }
    }
</style>
@endsection

@section('content')
@php
    $locked = $session->isLocked();
    $statusClass = $session->status->value === 'open' ? 'open' : ($session->status->value === 'auto_closed' ? 'auto' : 'closed');
    $horsTiroir = collect($aggregat['par_mode'] ?? [])->filter(function ($mode, $cle) {
        $canon = \App\Enums\ModePaiement::tryFrom((string) $cle);
        return $canon === null || ! $canon->isDrawer();
    });
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ').' F';
@endphp
<div class="dashboard-acasi mc-page">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <div class="mc-head">
                    <div class="mc-head-icon" aria-hidden="true"><i class="fas fa-cash-register"></i></div>
                    <div>
                        <h1>Ma caisse</h1>
                        <p class="header-subtitle">{{ $session->business_date->isoFormat('dddd D MMMM YYYY') }}</p>
                        <span class="mc-status {{ $statusClass }}">{{ $session->status->label() }}</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn-acasi secondary" href="{{ route('esbtp.paiements.create') }}">
                    <i class="fas fa-plus"></i>Encaisser
                </a>
                <a class="btn-acasi secondary" href="{{ route('esbtp.caisse.bordereau', ['preview' => 1]) }}" target="_blank">
                    <i class="fas fa-file-pdf"></i>Bordereau
                </a>
            </div>
        </div>

        <div class="mc-layout">
            <section class="mc-panel" aria-labelledby="mc-ledger-title">
                <div class="mc-panel-head">
                    <h2 id="mc-ledger-title">Registre du jour</h2>
                    <span>{{ $aggregat['count'] }} validé(s) · {{ $fmt($aggregat['total']) }}</span>
                </div>
                @if($paiements->isEmpty())
                    <div class="mc-empty">
                        <strong>Aucun versement pour l’instant</strong>
                        Encaisser un paiement ouvre le tiroir de la journée.
                        <div class="mt-3">
                            <a class="btn-acasi primary" href="{{ route('esbtp.paiements.create') }}">Encaisser</a>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="mc-table">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Étudiant</th>
                                    <th>Mode</th>
                                    <th>Statut</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiements as $p)
                                    @php
                                        $canon = \App\Enums\ModePaiement::fromLegacy((string) $p->mode_paiement);
                                        $st = mb_strtolower((string) $p->status, 'UTF-8');
                                        $stClass = in_array($st, ['validé', 'valide'], true) ? 'ok' : (str_contains($st, 'attente') ? 'wait' : 'no');
                                        $stLabel = match ($st) {
                                            'validé', 'valide' => 'Validé',
                                            'rejeté', 'rejete' => 'Rejeté',
                                            'en_attente', 'en attente' => 'En attente',
                                            default => $p->status ?: '—',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="mc-time">{{ $p->created_at?->format('H:i') }}</td>
                                        <td>{{ trim(($p->etudiant->nom ?? '').' '.($p->etudiant->prenoms ?? '')) ?: '—' }}</td>
                                        <td>{{ $canon?->label() ?? ($p->mode_paiement ?: '—') }}</td>
                                        <td><span class="mc-badge {{ $stClass }}">{{ $stLabel }}</span></td>
                                        <td class="mc-amount">{{ $fmt($p->montant) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <aside class="mc-panel" aria-labelledby="mc-drawer-title">
                <div class="mc-panel-head">
                    <h2 id="mc-drawer-title">Tiroir espèces</h2>
                </div>
                <div class="mc-drawer">
                    @if($locked)
                        <p class="mc-drawer-label">Compté à la clôture</p>
                        <p class="mc-drawer-amount">{{ $session->counted_amount !== null ? $fmt($session->counted_amount) : 'inconnu' }}</p>
                        <table class="mc-settle">
                            <tr>
                                <td>Attendu</td>
                                <td>{{ $fmt($session->expected_amount) }}</td>
                            </tr>
                            <tr>
                                <td>Écart</td>
                                <td>
                                    @if($session->variance === null) Inconnu
                                    @elseif((float) $session->variance == 0) Juste
                                    @elseif((float) $session->variance < 0) Manquant {{ $fmt(abs((float) $session->variance)) }}
                                    @else Excédent {{ $fmt($session->variance) }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @if($session->notes)
                            <p class="mc-drawer-hint">{{ $session->notes }}</p>
                        @endif
                    @else
                        <p class="mc-drawer-label">À compter dans le tiroir</p>
                        <p class="mc-drawer-amount">{{ $fmt($aggregat['especes']) }}</p>
                        <p class="mc-drawer-hint">Comptez les billets sans recopier ce montant. Un écart n’empêche pas de clôturer.</p>
                    @endif

                    @if($horsTiroir->isNotEmpty())
                        <div class="mc-out">
                            <h3>Hors tiroir</h3>
                            @foreach($horsTiroir as $mode)
                                <div class="mc-out-row">
                                    <span>{{ $mode['label'] }} · {{ $mode['count'] }}</span>
                                    <strong>{{ $fmt($mode['total']) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mc-drawer-actions">
                        @unless($locked)
                        <button type="button" class="btn-acasi primary" onclick="document.getElementById('mc-close').showModal()">
                            Clôturer ma journée
                        </button>
                        @endunless
                        <a class="btn-acasi secondary" href="{{ route('esbtp.caisse.bordereau', ['preview' => 1]) }}" target="_blank">
                            Imprimer le bordereau
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

@unless($locked)
<dialog id="mc-close" class="mc-dialog">
    <form method="POST" action="{{ route('esbtp.caisse.cloturer') }}">
        @csrf
        <h3>Clôturer ma journée</h3>
        <p>Espèces théoriques : <strong>{{ $fmt($aggregat['especes']) }}</strong>. Saisissez le comptage réel du tiroir.</p>
        <label class="form-label-moderne" for="counted_amount">Montant compté (espèces)</label>
        <input class="form-input-moderne" id="counted_amount" type="number" min="0" step="1" name="counted_amount" required placeholder="Ne pas recopier le théorique" autocomplete="off">
        <label class="form-label-moderne mt-2" for="mc-notes">Notes (écart, incident)</label>
        <textarea class="form-input-moderne" id="mc-notes" name="notes" rows="3"></textarea>
        <div class="mc-dialog-actions">
            <button type="button" class="btn-acasi secondary" onclick="document.getElementById('mc-close').close()">Annuler</button>
            <button type="submit" class="btn-acasi primary">Clôturer</button>
        </div>
    </form>
</dialog>
@endunless
@endsection
