@extends('layouts.app')

@section('title', 'Session ' . $session->code . ' - KLASSCI')

@push('styles')
<style>
    .rec-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .rec-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .rec-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rec-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff;
    }
    .rec-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rec-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .rec-status-tag {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem; border-radius: 999px;
        background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
        color: #fff; font-size: .75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .3px;
    }

    .rec-meta {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem; margin-top: 1.5rem;
    }
    .rec-meta-card {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 10px; padding: .75rem .9rem;
    }
    .rec-meta-label { font-size: .7rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .3px; }
    .rec-meta-value { font-size: .92rem; font-weight: 600; color: #fff; margin-top: .1rem; }

    .rec-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .rec-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
        border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
        padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        cursor: pointer; transition: background .15s;
    }
    .rec-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .rec-btn--white {
        background: #fff; color: #0453cb;
        padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid #fff; cursor: pointer;
    }
    .rec-btn--white:disabled { opacity: .6; cursor: wait; }

    .rec-tabs { display: flex; gap: .25rem; margin-bottom: 1rem; background: #f1f5f9; padding: .25rem; border-radius: 10px; }
    .rec-tab {
        flex: 1; background: transparent; border: none; padding: .55rem 1rem;
        border-radius: 8px; font-size: .85rem; font-weight: 600; color: #64748b;
        cursor: pointer; transition: background .15s, color .15s;
    }
    .rec-tab:hover { color: #1e293b; }
    .rec-tab--active { background: #fff; color: #0453cb; box-shadow: 0 1px 3px rgba(15,23,42,.06); }

    .rec-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 1.25rem; box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }

    .rec-cash-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    .rec-cash-card {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 1rem;
    }
    .rec-cash-card.has-ecart-pos { border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.03); }
    .rec-cash-card.has-ecart-neg { border-color: rgba(220,38,38,.4); background: rgba(220,38,38,.03); }
    .rec-cash-mode { font-weight: 700; color: #1e293b; margin-bottom: .5rem; display: flex; align-items: center; gap: .4rem; }
    .rec-cash-mode i { color: #0453cb; }
    .rec-cash-fields { display: flex; flex-direction: column; gap: .5rem; }
    .rec-cash-row { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; }
    .rec-cash-row label { color: #64748b; }
    .rec-cash-input {
        width: 130px; padding: .35rem .55rem; border: 1px solid #cbd5e1; border-radius: 7px;
        font-size: .85rem; text-align: right; font-variant-numeric: tabular-nums;
    }
    .rec-cash-input:focus { outline: 2px solid rgba(4,83,203,.25); border-color: #0453cb; }
    .rec-ecart-tag {
        font-weight: 700; padding: .2rem .55rem; border-radius: 6px; font-size: .78rem;
        font-variant-numeric: tabular-nums;
    }
    .rec-ecart-tag.pos { background: rgba(16,185,129,.12); color: #047857; }
    .rec-ecart-tag.neg { background: rgba(220,38,38,.12); color: #b91c1c; }
    .rec-ecart-tag.zero { background: rgba(94,145,222,.12); color: #64748b; }

    .rec-save-row {
        margin-top: .55rem; display: flex; justify-content: flex-end; gap: .5rem;
    }
    .rec-save-btn {
        background: #0453cb; color: #fff; border: none; padding: .35rem .85rem;
        border-radius: 7px; font-size: .78rem; font-weight: 600; cursor: pointer;
    }
    .rec-save-btn:disabled { opacity: .5; cursor: wait; }

    .rec-empty { padding: 2.5rem 1rem; text-align: center; color: #64748b; }
    .rec-empty i { font-size: 2rem; color: #94a3b8; margin-bottom: .5rem; }

    .rec-workflow {
        display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap;
        padding-top: 1rem; border-top: 1px solid #f1f5f9;
    }
    .rec-workflow button {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff; border: none; padding: .55rem 1.15rem;
        border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer;
    }
    .rec-workflow button:disabled { opacity: .5; cursor: not-allowed; background: #94a3b8; }
    .rec-workflow button.danger { background: linear-gradient(135deg, #dc2626, #b91c1c); }

    /* Discrepancy row animation rouge → orange → vert */
    .rec-disc-row {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: .85rem;
        transition: background .4s ease, border-color .4s ease;
    }
    .rec-disc-row--alert { background: rgba(220,38,38,.05); border-color: rgba(220,38,38,.3); }
    .rec-disc-row--warn { background: rgba(245,158,11,.05); border-color: rgba(245,158,11,.3); }
    .rec-disc-row--ok {
        background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.35);
        animation: rec-row-resolved .6s ease;
    }
    @keyframes rec-row-resolved {
        0% { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.3); }
        50% { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.4); }
        100% { background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.35); }
    }

    /* Modal résolution */
    .rec-modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(15,23,42,.55);
        backdrop-filter: blur(4px);
        z-index: 1050;
        display: flex; align-items: center; justify-content: center;
        padding: 1.5rem;
    }
    .rec-modal {
        background: #fff; border-radius: 16px; max-width: 720px;
        width: 100%; max-height: 90vh; display: flex; flex-direction: column;
        box-shadow: 0 25px 80px rgba(15,23,42,.25);
        animation: rec-modal-in .25s ease;
    }
    @keyframes rec-modal-in {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to { opacity: 1; transform: none; }
    }
    .rec-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9;
    }
    .rec-modal-header h3 {
        margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .rec-modal-header h3 i { color: #0453cb; }
    .rec-modal-close {
        background: transparent; border: none; font-size: 1.5rem;
        color: #94a3b8; cursor: pointer; line-height: 1;
        padding: 0 .25rem;
    }
    .rec-modal-close:hover { color: #1e293b; }

    .rec-modal-body {
        padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1;
    }
    .rec-modal-summary {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .85rem 1rem; display: flex; gap: 1.5rem; flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .rec-modal-summary > div { display: flex; flex-direction: column; gap: .15rem; }
    .rec-modal-label {
        font-size: .68rem; text-transform: uppercase; color: #64748b;
        letter-spacing: .3px; font-weight: 600;
    }
    .rec-modal-section-title {
        font-size: .82rem; text-transform: uppercase; color: #0453cb;
        letter-spacing: .3px; font-weight: 700; margin: 1rem 0 .65rem;
    }

    .rec-action-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: .65rem; margin-bottom: 1rem;
    }
    .rec-action-card {
        background: #fff; border: 2px solid #e2e8f0; border-radius: 10px;
        padding: .85rem; cursor: pointer; display: flex; gap: .65rem;
        transition: border-color .15s, background .15s;
        position: relative;
    }
    .rec-action-card:hover { border-color: #cbd5e1; background: #f8fafc; }
    .rec-action-card--selected {
        border-color: #0453cb; background: rgba(4,83,203,.04);
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .rec-action-card input[type="radio"] {
        position: absolute; opacity: 0; pointer-events: none;
    }
    .rec-action-card-body { display: flex; flex-direction: column; gap: .15rem; }
    .rec-action-icon { color: #0453cb; font-size: 1.1rem; margin-bottom: .25rem; }
    .rec-action-card strong { font-size: .9rem; color: #1e293b; }
    .rec-action-card span { font-size: .78rem; color: #64748b; line-height: 1.35; }

    .rec-modal-row { margin-bottom: .85rem; }
    .rec-modal-row label {
        display: block; font-size: .82rem; font-weight: 600; color: #1e293b;
        margin-bottom: .3rem;
    }
    .rec-modal-row small { display: block; font-size: .72rem; color: #64748b; margin-top: .25rem; }
    .rec-modal-input {
        width: 100%; padding: .55rem .75rem; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: .88rem;
    }
    .rec-modal-input:focus { outline: 2px solid rgba(4,83,203,.25); border-color: #0453cb; }

    .rec-modal-preview {
        background: linear-gradient(135deg, rgba(4,83,203,.05), rgba(59,125,219,.07));
        border: 1px solid rgba(4,83,203,.2); border-radius: 10px;
        padding: .85rem 1rem; font-size: .85rem; color: #1e293b;
        margin-top: 1rem;
    }
    .rec-modal-preview-title {
        font-size: .72rem; text-transform: uppercase; color: #0453cb;
        font-weight: 700; letter-spacing: .3px; margin-bottom: .35rem;
    }

    .rec-modal-footer {
        display: flex; gap: .5rem; justify-content: flex-end;
        padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9;
    }
    .rec-btn-ghost {
        background: transparent; border: 1px solid #e2e8f0; color: #64748b;
        padding: .55rem 1.15rem; border-radius: 8px; font-weight: 600; cursor: pointer;
    }
    .rec-btn-ghost:hover { background: #f8fafc; color: #1e293b; }
    .rec-modal-submit {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff; border: none; padding: .55rem 1.4rem;
        border-radius: 8px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .rec-modal-submit:disabled { opacity: .5; cursor: not-allowed; }

    @media (max-width: 640px) {
        .rec-modal-backdrop { padding: 0; align-items: flex-end; }
        .rec-modal { border-radius: 16px 16px 0 0; max-height: 95vh; }
    }

    /* PR5 drill-down */
    .rec-cash-actions {
        display: flex; justify-content: space-between; gap: .5rem; align-items: center;
        margin-top: .65rem; padding-top: .55rem; border-top: 1px dashed #e2e8f0;
    }
    .rec-cash-drill-btn {
        background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.18);
        padding: .3rem .65rem; border-radius: 6px; font-size: .74rem; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: .3rem;
        transition: background .15s;
    }
    .rec-cash-drill-btn:hover { background: rgba(4,83,203,.14); }
    .rec-cash-portal-hint {
        font-size: .72rem; color: #64748b;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .rec-cash-portal-hint a {
        color: #0453cb; text-decoration: none; font-weight: 600;
    }
    .rec-cash-portal-hint a:hover { text-decoration: underline; }

    .rec-drill-table {
        width: 100%; border-collapse: collapse; font-size: .85rem;
    }
    .rec-drill-table th {
        text-align: left; background: #f8fafc; padding: .55rem .65rem;
        font-size: .72rem; text-transform: uppercase; color: #64748b;
        font-weight: 700; letter-spacing: .3px;
        border-bottom: 1px solid #e2e8f0;
    }
    .rec-drill-table td {
        padding: .55rem .65rem; border-bottom: 1px solid #f1f5f9; color: #1e293b;
    }
    .rec-drill-table tr:hover td { background: #f8fafc; }
    .rec-drill-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }

    .rec-drill-summary {
        background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
        border: 1px solid rgba(4,83,203,.18); border-radius: 10px;
        padding: .75rem 1rem; margin-bottom: 1rem;
        display: flex; gap: 1.5rem; flex-wrap: wrap;
    }
    .rec-drill-summary > div { display: flex; flex-direction: column; gap: .1rem; }
    .rec-drill-summary .label {
        font-size: .68rem; text-transform: uppercase; color: #64748b;
        letter-spacing: .3px; font-weight: 600;
    }
    .rec-drill-summary .val { font-size: 1rem; font-weight: 700; color: #1e293b; }

    .rec-drill-pagination {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;
        font-size: .82rem; color: #64748b;
    }
    .rec-drill-pagination button {
        background: #fff; border: 1px solid #cbd5e1; color: #0453cb;
        padding: .35rem .85rem; border-radius: 6px; font-size: .78rem; font-weight: 600;
        cursor: pointer;
    }
    .rec-drill-pagination button:disabled { opacity: .4; cursor: not-allowed; }
</style>
@endpush

@section('content')
@php
    $cashCountsData = $cash_counts->map(fn ($c) => [
        'id' => $c->id,
        'mode' => $c->mode_paiement,
        'mode_label' => $c->modeLabel(),
        'montant_compte' => (float) $c->montant_compte,
        'montant_systeme' => (float) $c->montant_systeme,
        'ecart' => $c->ecart,
        'counted_at' => optional($c->counted_at)->toIso8601String(),
    ])->keyBy('mode')->all();
    $modes = \App\Enums\ModePaiement::cases();
    $modesPayload = collect($modes)->map(fn ($m) => ['value' => $m->value, 'label' => $m->label(), 'icon' => $m->icon()])->all();
@endphp

<div class="container-fluid" x-data="recShow()" x-init="init()">
    <div class="rec-hero">
        <div class="rec-hero-top">
            <div class="rec-hero-left">
                <div class="rec-hero-icon"><i class="fas fa-balance-scale"></i></div>
                <div>
                    <h1>{{ $session->code }}</h1>
                    <p>
                        {{ ucfirst($session->frequency) }} · du {{ optional($session->period_start)->format('d/m/Y') }}
                        @if($session->period_start != $session->period_end)
                            au {{ optional($session->period_end)->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="rec-actions">
                <span class="rec-status-tag" x-text="statusLabel">{{ $session->status->label() }}</span>
                <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="rec-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                @if($session->status->value === 'closed')
                    @can('comptabilite.reconciliation.export')
                        <a href="{{ route('esbtp.comptabilite.reconciliation.export-pv', $session) }}"
                           class="rec-btn--white" target="_blank">
                            <i class="fas fa-file-pdf"></i> PV PDF
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="rec-meta">
            <div class="rec-meta-card">
                <div class="rec-meta-label">Ouverte par</div>
                <div class="rec-meta-value">{{ optional($session->opener)->name ?? '—' }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Ouverte le</div>
                <div class="rec-meta-value">{{ optional($session->opened_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Approbateur</div>
                <div class="rec-meta-value">{{ optional($session->approver)->name ?? '—' }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Total écart</div>
                <div class="rec-meta-value" x-text="formatMoney(totalEcart)">{{ number_format($total_ecart, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- Tabs (Alpine state) --}}
    <div class="rec-tabs">
        <button class="rec-tab" :class="tab === 'counts' ? 'rec-tab--active' : ''" @click="tab = 'counts'">
            <i class="fas fa-cash-register"></i> Comptages
        </button>
        <button class="rec-tab" :class="tab === 'discrepancies' ? 'rec-tab--active' : ''" @click="tab = 'discrepancies'">
            <i class="fas fa-exclamation-triangle"></i> Écarts
            <span x-show="discrepancies.length > 0" x-text="'(' + discrepancies.length + ')'"></span>
        </button>
        <button class="rec-tab" :class="tab === 'workflow' ? 'rec-tab--active' : ''" @click="tab = 'workflow'">
            <i class="fas fa-tasks"></i> Workflow
        </button>
    </div>

    {{-- Tab Comptages --}}
    <div class="rec-card" x-show="tab === 'counts'" x-cloak>
        @if($session->isModifiable())
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="background:rgba(4,83,203,.06);border:1px solid rgba(4,83,203,.18);border-radius:8px;padding:.65rem .85rem;font-size:.82rem;color:#0453cb;flex:1;min-width:280px;">
                    <i class="fas fa-info-circle"></i> Saisissez les montants physiquement constatés pour chaque mode utilisé. L'écart se calcule automatiquement vs les paiements validés en système.
                </div>
                @can('comptabilite.reconciliation.resolve')
                <button @click="detectDiscrepancies()" :disabled="detecting"
                    style="background:linear-gradient(135deg,#0453cb,#3b7ddb);color:#fff;border:none;padding:.55rem 1.15rem;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;">
                    <span x-show="!detecting"><i class="fas fa-search"></i> Détecter les écarts</span>
                    <span x-show="detecting" x-cloak><i class="fas fa-spinner fa-spin"></i> …</span>
                </button>
                @endcan
            </div>
        @endif
        <div class="rec-cash-grid">
            <template x-for="mode in modes" :key="mode.value">
                <div class="rec-cash-card" :class="cardClass(mode.value)">
                    <div class="rec-cash-mode">
                        <i :class="'fas ' + mode.icon"></i> <span x-text="mode.label"></span>
                    </div>
                    <div class="rec-cash-fields">
                        <div class="rec-cash-row">
                            <label>Système (validés)</label>
                            <strong x-text="formatMoney(getCount(mode.value).montant_systeme || 0)"></strong>
                        </div>
                        <div class="rec-cash-row">
                            <label>Physique compté</label>
                            <input type="number" step="0.01" min="0" class="rec-cash-input"
                                   :value="getCount(mode.value).montant_compte || 0"
                                   @input="onInput(mode.value, $event.target.value)"
                                   :disabled="!editable">
                        </div>
                        <div class="rec-cash-row">
                            <label>Écart</label>
                            <span class="rec-ecart-tag" :class="ecartTagClass(mode.value)" x-text="formatMoney(getCount(mode.value).ecart || 0)"></span>
                        </div>
                    </div>
                    <div class="rec-save-row" x-show="editable && hasDraft(mode.value)" x-cloak>
                        <button class="rec-save-btn" @click="saveCount(mode.value)" :disabled="saving[mode.value]">
                            <span x-show="!saving[mode.value]"><i class="fas fa-save"></i> Enregistrer</span>
                            <span x-show="saving[mode.value]" x-cloak>…</span>
                        </button>
                    </div>

                    {{-- PR5 drill-down + hint portail --}}
                    <div class="rec-cash-actions" x-show="hasSystemPayments(mode.value)" x-cloak>
                        <button class="rec-cash-drill-btn" @click="openDrillModal(mode.value)">
                            <i class="fas fa-list"></i> Voir transactions
                        </button>
                        <span class="rec-cash-portal-hint" x-show="portalUrl(mode.value)" x-cloak>
                            <i class="fas fa-external-link-alt"></i>
                            <a :href="portalUrl(mode.value)" target="_blank" rel="noopener" x-text="portalLabel(mode.value)"></a>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Tab Écarts --}}
    <div class="rec-card" x-show="tab === 'discrepancies'" x-cloak>
        <div x-show="discrepancies.length === 0" class="rec-empty">
            <i class="fas fa-check-circle"></i>
            <div>Aucun écart à traiter pour le moment.</div>
            @if($session->isModifiable())
            <p style="font-size:.82rem;margin-top:.5rem;">Cliquez sur « Détecter les écarts » dans l'onglet Comptages pour les générer automatiquement.</p>
            @endif
        </div>
        <div x-show="discrepancies.length > 0" x-cloak>
            <div style="display:flex;flex-direction:column;gap:.65rem;">
                <template x-for="d in discrepancies" :key="d.id">
                    <div class="rec-disc-row"
                         :class="d.action === 'resolu' ? 'rec-disc-row--ok' : (d.action === 'en_revue' ? 'rec-disc-row--warn' : 'rec-disc-row--alert')">
                        <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:flex-start;flex-wrap:wrap;">
                            <div style="flex:1;min-width:220px;">
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <strong x-text="discrepancyTypeLabel(d.type)"></strong>
                                    <span class="rec-ecart-tag" :class="d.montant_ecart > 0 ? 'pos' : 'neg'" x-text="formatMoney(d.montant_ecart)"></span>
                                </div>
                                <div style="margin-top:.35rem;color:#64748b;font-size:.82rem;" x-text="d.motif"></div>
                            </div>
                            <div style="display:flex;gap:.4rem;align-items:center;">
                                <span class="rec-badge" :class="d.action === 'resolu' ? 'rec-badge--success' : 'rec-badge--warning'" x-text="discrepancyActionLabel(d.action)"></span>
                                @can('comptabilite.reconciliation.resolve')
                                <button x-show="d.action !== 'resolu' && editable" @click="openResolveModal(d)"
                                    style="background:#0453cb;color:#fff;border:none;padding:.4rem .85rem;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.3rem;">
                                    <i class="fas fa-tools"></i> Résoudre
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- PR5 Modal drill-down : liste paiements détaillés par mode pour pointage manuel vs portail merchant --}}
    <div x-show="drillModal.open" x-cloak class="rec-modal-backdrop" @keydown.escape.window="closeDrillModal()">
        <div class="rec-modal" style="max-width:920px;" @click.outside="closeDrillModal()">
            <div class="rec-modal-header">
                <h3>
                    <i class="fas fa-list"></i>
                    Transactions <span x-text="drillModal.modeLabel"></span>
                </h3>
                <button @click="closeDrillModal()" class="rec-modal-close">×</button>
            </div>
            <div class="rec-modal-body">
                <div class="rec-drill-summary" x-show="!drillModal.loading" x-cloak>
                    <div>
                        <span class="label">Nombre</span>
                        <span class="val" x-text="drillModal.totals.count + ' paiement(s)'"></span>
                    </div>
                    <div>
                        <span class="label">Total système</span>
                        <span class="val" x-text="formatMoney(drillModal.totals.total_amount)"></span>
                    </div>
                    <div x-show="drillModal.portalUrl" x-cloak>
                        <span class="label">Portail merchant</span>
                        <a :href="drillModal.portalUrl" target="_blank" rel="noopener" style="color:#0453cb;font-weight:700;font-size:.88rem;">
                            <i class="fas fa-external-link-alt"></i> Ouvrir
                        </a>
                    </div>
                </div>

                <div x-show="drillModal.loading" x-cloak style="padding:2rem;text-align:center;color:#64748b;">
                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;"></i><br>Chargement…
                </div>

                <div x-show="!drillModal.loading && drillModal.payments.length === 0" x-cloak style="padding:2rem;text-align:center;color:#64748b;">
                    <i class="fas fa-inbox" style="font-size:1.5rem;"></i><br>Aucun paiement validé sur cette période et ce mode.
                </div>

                <div x-show="!drillModal.loading && drillModal.payments.length > 0" x-cloak style="overflow-x:auto;">
                    <table class="rec-drill-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Étudiant</th>
                                <th>Référence</th>
                                <th class="num">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="p in drillModal.payments" :key="p.id">
                                <tr>
                                    <td x-text="'#' + p.id"></td>
                                    <td x-text="p.date_paiement || '—'"></td>
                                    <td>
                                        <span x-show="p.etudiant" x-text="(p.etudiant?.matricule || '—') + ' — ' + ((p.etudiant?.nom || '') + ' ' + (p.etudiant?.prenoms || '')).trim()"></span>
                                        <span x-show="!p.etudiant" style="color:#94a3b8;">—</span>
                                    </td>
                                    <td x-text="p.reference_paiement || p.numero_recu || '—'"></td>
                                    <td class="num" x-text="formatMoney(p.montant)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="rec-drill-pagination" x-show="!drillModal.loading && drillModal.pagination.last_page > 1" x-cloak>
                    <button @click="drillPrev()" :disabled="drillModal.pagination.current_page <= 1">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </button>
                    <span>Page <strong x-text="drillModal.pagination.current_page"></strong> / <span x-text="drillModal.pagination.last_page"></span></span>
                    <button @click="drillNext()" :disabled="drillModal.pagination.current_page >= drillModal.pagination.last_page">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal résolution écart (Alpine, AJAX no-reload) --}}
    <div x-show="resolveModal.open" x-cloak class="rec-modal-backdrop" @keydown.escape.window="closeResolveModal()">
        <div class="rec-modal" @click.outside="closeResolveModal()">
            <div class="rec-modal-header">
                <h3>
                    <i class="fas fa-tools"></i>
                    Résoudre l'écart
                </h3>
                <button @click="closeResolveModal()" class="rec-modal-close">×</button>
            </div>
            <div class="rec-modal-body">
                <div class="rec-modal-summary">
                    <div>
                        <span class="rec-modal-label">Type</span>
                        <strong x-text="resolveModal.discrepancy ? discrepancyTypeLabel(resolveModal.discrepancy.type) : ''"></strong>
                    </div>
                    <div>
                        <span class="rec-modal-label">Montant écart</span>
                        <strong :class="resolveModal.discrepancy && resolveModal.discrepancy.montant_ecart > 0 ? 'rec-ecart--pos' : 'rec-ecart--neg'"
                                x-text="resolveModal.discrepancy ? formatMoney(resolveModal.discrepancy.montant_ecart) : ''"></strong>
                    </div>
                </div>

                <h4 class="rec-modal-section-title">Choisissez l'action de résolution</h4>
                <div class="rec-action-grid">
                    <label class="rec-action-card" :class="resolveModal.resolution_type === 'create_corrective' ? 'rec-action-card--selected' : ''">
                        <input type="radio" x-model="resolveModal.resolution_type" value="create_corrective">
                        <div class="rec-action-card-body">
                            <i class="fas fa-plus-circle rec-action-icon"></i>
                            <strong>Créer paiement correctif</strong>
                            <span>Crée un nouveau paiement validé pour combler l'écart.</span>
                        </div>
                    </label>
                    <label class="rec-action-card" :class="resolveModal.resolution_type === 'adjust_payment' ? 'rec-action-card--selected' : ''">
                        <input type="radio" x-model="resolveModal.resolution_type" value="adjust_payment">
                        <div class="rec-action-card-body">
                            <i class="fas fa-edit rec-action-icon"></i>
                            <strong>Ajuster paiement existant</strong>
                            <span>Modifie montant/mode/date d'un paiement déjà enregistré.</span>
                        </div>
                    </label>
                    <label class="rec-action-card" :class="resolveModal.resolution_type === 'cancel_payment' ? 'rec-action-card--selected' : ''">
                        <input type="radio" x-model="resolveModal.resolution_type" value="cancel_payment">
                        <div class="rec-action-card-body">
                            <i class="fas fa-ban rec-action-icon"></i>
                            <strong>Annuler paiement saisi en trop</strong>
                            <span>Passe en statut rejeté un paiement qui n'aurait pas dû être validé.</span>
                        </div>
                    </label>
                    <label class="rec-action-card" :class="resolveModal.resolution_type === 'no_action' ? 'rec-action-card--selected' : ''">
                        <input type="radio" x-model="resolveModal.resolution_type" value="no_action">
                        <div class="rec-action-card-body">
                            <i class="fas fa-check rec-action-icon"></i>
                            <strong>Accepter l'écart</strong>
                            <span>Documente le motif sans modification (perte/bonus exceptionnel).</span>
                        </div>
                    </label>
                </div>

                {{-- Payload conditionnel selon action --}}
                <div x-show="['adjust_payment', 'cancel_payment'].includes(resolveModal.resolution_type)" x-cloak class="rec-modal-row">
                    <label>ID du paiement concerné</label>
                    <input type="number" min="1" x-model="resolveModal.payload.paiement_id" class="rec-modal-input"
                           placeholder="Ex: 1234">
                    <small>Identifiez le paiement à ajuster/annuler (visible dans /esbtp/paiements).</small>
                </div>

                <div x-show="resolveModal.resolution_type === 'adjust_payment'" x-cloak class="rec-modal-row">
                    <label>Nouveau montant (optionnel)</label>
                    <input type="number" step="0.01" min="0" x-model.number="resolveModal.payload.montant" class="rec-modal-input">
                </div>

                <div x-show="resolveModal.resolution_type === 'create_corrective'" x-cloak class="rec-modal-row">
                    <label>Mode du paiement correctif</label>
                    <x-au-select
                        name="resolve_mode_paiement"
                        :value="''"
                        icon="fa-mobile-screen"
                        placeholder="Mode de paiement"
                        x-model="resolveModal.payload.mode_paiement"
                        :options="\App\Enums\ModePaiement::selectOptions()" />
                    <small>Le montant et la date seront initialisés depuis l'écart détecté.</small>
                </div>

                <div class="rec-modal-row">
                    <label>Motif (obligatoire, minimum 10 caractères)</label>
                    <textarea x-model="resolveModal.motif" rows="3" class="rec-modal-input" placeholder="Justification audit fiscal — pourquoi cette action ?"></textarea>
                    <small><span x-text="resolveModal.motif.length"></span> / 10 caractères minimum</small>
                </div>

                {{-- Preview impact temps réel --}}
                <div class="rec-modal-preview" x-show="resolveModal.resolution_type" x-cloak>
                    <div class="rec-modal-preview-title"><i class="fas fa-eye"></i> Aperçu impact</div>
                    <div x-text="previewMessage()"></div>
                </div>
            </div>
            <div class="rec-modal-footer">
                <button @click="closeResolveModal()" class="rec-btn-ghost">Annuler</button>
                <button @click="submitResolve()" :disabled="!canSubmit || resolveModal.submitting"
                        class="rec-modal-submit">
                    <span x-show="!resolveModal.submitting"><i class="fas fa-check"></i> Confirmer</span>
                    <span x-show="resolveModal.submitting" x-cloak><i class="fas fa-spinner fa-spin"></i> …</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Tab Workflow --}}
    <div class="rec-card" x-show="tab === 'workflow'" x-cloak>
        <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.5rem;">Étapes du workflow OHADA</h3>
        <p style="color:#64748b;font-size:.85rem;">
            Brouillon → En revue → Approuvée (séparation des devoirs) → Clôturée
        </p>
        <div class="rec-workflow">
            <button @click="transition('review')" :disabled="!canReview">
                <i class="fas fa-eye"></i> Passer en revue
            </button>
            @can('comptabilite.reconciliation.approve')
                <button @click="transition('approve')" :disabled="!canApprove">
                    <i class="fas fa-check-double"></i> Approuver
                </button>
                <button @click="transition('close')" :disabled="!canClose">
                    <i class="fas fa-lock"></i> Clôturer
                </button>
            @endcan
            @can('comptabilite.reconciliation.bypass_lock')
                <button class="danger" @click="reopenPrompt()" :disabled="!canReopen">
                    <i class="fas fa-unlock"></i> Rouvrir (exception)
                </button>
            @endcan
        </div>
    </div>
</div>

@php
    $jsPayload = [
        'sessionId' => $session->id,
        'status' => $session->status->value,
        'statusLabel' => $session->status->label(),
        'totalEcart' => (float) $total_ecart,
        'isModifiable' => $session->isModifiable(),
        'cashCounts' => $cashCountsData,
        'modes' => $modesPayload,
        'discrepancies' => $discrepancies->map(fn ($d) => [
            'id' => $d->id,
            'type' => $d->type,
            'montant_ecart' => (float) $d->montant_ecart,
            'motif' => $d->motif,
        ])->all(),
    ];
@endphp

<script>
window.recShow = function () {
    return {
        tab: 'counts',
        sessionId: @json($jsPayload['sessionId']),
        status: @json($jsPayload['status']),
        statusLabel: @json($jsPayload['statusLabel']),
        totalEcart: @json($jsPayload['totalEcart']),
        editable: @json($jsPayload['isModifiable']),
        cashCounts: @json($jsPayload['cashCounts']),
        modes: @json($jsPayload['modes']),
        discrepancies: @json($jsPayload['discrepancies']),
        portalUrls: @json($portalUrls ?? []),
        drafts: {},
        saving: {},
        detecting: false,
        resolveModal: {
            open: false,
            discrepancy: null,
            resolution_type: '',
            motif: '',
            payload: { paiement_id: null, montant: null, mode_paiement: null },
            submitting: false,
        },
        drillModal: {
            open: false,
            mode: '',
            modeLabel: '',
            portalUrl: null,
            payments: [],
            totals: { count: 0, total_amount: 0 },
            pagination: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
            loading: false,
        },

        init() {
            window.addEventListener('reconciliation:refresh', () => this.reload());
        },

        async detectDiscrepancies() {
            this.detecting = true;
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/detect-discrepancies`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                this.discrepancies = data.discrepancies || [];
                this.tab = 'discrepancies';
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: data.created_count > 0 ? 'warning' : 'success', message: data.message }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.detecting = false;
            }
        },

        openResolveModal(d) {
            this.resolveModal = {
                open: true,
                discrepancy: d,
                resolution_type: '',
                motif: '',
                payload: { paiement_id: null, montant: null, mode_paiement: null },
                submitting: false,
            };
        },

        closeResolveModal() {
            this.resolveModal.open = false;
        },

        get canSubmit() {
            const m = this.resolveModal;
            if (!m.resolution_type) return false;
            if (m.motif.length < 10) return false;
            if (['adjust_payment', 'cancel_payment'].includes(m.resolution_type) && !m.payload.paiement_id) return false;
            return true;
        },

        previewMessage() {
            const m = this.resolveModal;
            if (!m.resolution_type || !m.discrepancy) return '';
            const ecart = m.discrepancy.montant_ecart;
            const abs = Math.abs(ecart);
            const fmt = this.formatMoney(abs);
            switch (m.resolution_type) {
                case 'create_corrective':
                    return `Un nouveau paiement validé de ${fmt} sera créé et lié à cet écart. L'écart passera à 0 après détection suivante.`;
                case 'adjust_payment':
                    if (!m.payload.paiement_id) return 'Renseignez l\'ID du paiement à ajuster.';
                    return `Le paiement #${m.payload.paiement_id} sera ajusté (montant: ${m.payload.montant ?? 'inchangé'}). Audit log écrit.`;
                case 'cancel_payment':
                    if (!m.payload.paiement_id) return 'Renseignez l\'ID du paiement à annuler.';
                    return `Le paiement #${m.payload.paiement_id} passera en statut "rejeté" et sera retiré des KPIs validés.`;
                case 'no_action':
                    return `Aucune mutation. L'écart de ${fmt} sera documenté avec votre motif. À utiliser pour perte/bonus exceptionnel.`;
            }
            return '';
        },

        async submitResolve() {
            if (!this.canSubmit) return;
            this.resolveModal.submitting = true;
            try {
                const payload = {
                    resolution_type: this.resolveModal.resolution_type,
                    motif: this.resolveModal.motif,
                    payload: {},
                };
                if (this.resolveModal.payload.paiement_id) {
                    payload.payload.paiement_id = parseInt(this.resolveModal.payload.paiement_id);
                }
                if (this.resolveModal.payload.montant !== null && this.resolveModal.payload.montant !== '') {
                    payload.payload.montant = parseFloat(this.resolveModal.payload.montant);
                }
                if (this.resolveModal.payload.mode_paiement) {
                    payload.payload.mode_paiement = this.resolveModal.payload.mode_paiement;
                }

                const res = await fetch(`/esbtp/comptabilite/reconciliation/discrepancies/${this.resolveModal.discrepancy.id}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                const idx = this.discrepancies.findIndex(x => x.id === this.resolveModal.discrepancy.id);
                if (idx !== -1) this.discrepancies[idx] = data.discrepancy;
                this.closeResolveModal();
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: data.message }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.resolveModal.submitting = false;
            }
        },

        discrepancyActionLabel(a) {
            return { a_traiter: 'À traiter', en_revue: 'En revue', resolu: 'Résolu', rejete: 'Rejeté' }[a] || a;
        },

        // PR5 drill-down
        hasSystemPayments(mode) {
            const c = this.cashCounts[mode];
            return c && parseFloat(c.montant_systeme) > 0;
        },
        portalUrl(mode) {
            return this.portalUrls[mode] || null;
        },
        portalLabel(mode) {
            const labels = { orange_money: 'Voir portail Orange Money', mtn_money: 'Voir portail MTN MoMo', moov_money: 'Voir portail Moov Money', wave: 'Voir Wave Business' };
            return labels[mode] || 'Voir portail';
        },
        async openDrillModal(mode) {
            const modeObj = this.modes.find(m => m.value === mode);
            this.drillModal = {
                open: true,
                mode: mode,
                modeLabel: modeObj ? modeObj.label : mode,
                portalUrl: this.portalUrls[mode] || null,
                payments: [],
                totals: { count: 0, total_amount: 0 },
                pagination: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
                loading: true,
            };
            await this.loadDrillPage(1);
        },
        closeDrillModal() {
            this.drillModal.open = false;
        },
        async drillPrev() { if (this.drillModal.pagination.current_page > 1) await this.loadDrillPage(this.drillModal.pagination.current_page - 1); },
        async drillNext() { if (this.drillModal.pagination.current_page < this.drillModal.pagination.last_page) await this.loadDrillPage(this.drillModal.pagination.current_page + 1); },
        async loadDrillPage(page) {
            this.drillModal.loading = true;
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/payments-by-mode/${this.drillModal.mode}?page=${page}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                this.drillModal.payments = data.payments || [];
                this.drillModal.totals = data.totals || { count: 0, total_amount: 0 };
                this.drillModal.pagination = data.pagination || this.drillModal.pagination;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.drillModal.loading = false;
            }
        },

        getCount(mode) {
            if (this.drafts[mode] !== undefined) {
                const existing = this.cashCounts[mode] || { montant_systeme: 0, ecart: 0 };
                return {
                    montant_compte: this.drafts[mode],
                    montant_systeme: existing.montant_systeme || 0,
                    ecart: (parseFloat(this.drafts[mode]) || 0) - (existing.montant_systeme || 0),
                };
            }
            return this.cashCounts[mode] || { montant_compte: 0, montant_systeme: 0, ecart: 0 };
        },

        hasDraft(mode) { return this.drafts[mode] !== undefined; },

        onInput(mode, value) { this.drafts[mode] = value; },

        cardClass(mode) {
            const e = this.getCount(mode).ecart || 0;
            if (e > 0) return 'has-ecart-pos';
            if (e < 0) return 'has-ecart-neg';
            return '';
        },
        ecartTagClass(mode) {
            const e = this.getCount(mode).ecart || 0;
            if (e > 0) return 'pos';
            if (e < 0) return 'neg';
            return 'zero';
        },

        async saveCount(mode) {
            this.saving[mode] = true;
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/cash-counts`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        mode_paiement: mode,
                        montant_compte: parseFloat(this.drafts[mode]) || 0,
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                this.cashCounts[mode] = data.cash_count;
                delete this.drafts[mode];
                this.recalcTotal();
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Comptage enregistré.' }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.saving[mode] = false;
            }
        },

        recalcTotal() {
            this.totalEcart = Object.values(this.cashCounts).reduce((s, c) => s + (parseFloat(c.ecart) || 0), 0);
        },

        async reload() {
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.status = data.session.status;
                this.totalEcart = data.total_ecart;
                this.editable = ['draft', 'reopened'].includes(this.status);
            } catch (e) { /* silent */ }
        },

        get canReview() { return this.status === 'draft' || this.status === 'reopened'; },
        get canApprove() { return this.status === 'review'; },
        get canClose() { return this.status === 'approved'; },
        get canReopen() { return this.status === 'closed'; },

        async transition(action) {
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: data.message }
                }));
                window.location.reload(); // EXCEPTION ajax-no-reload-premium : changement de statut majeur (workflow final)
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },

        async reopenPrompt() {
            const reason = prompt('Motif de réouverture (minimum 30 caractères, audit fiscal) :');
            if (!reason || reason.length < 30) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'warning', message: 'Motif trop court (30 caractères min).' }
                }));
                return;
            }
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/reopen`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                window.location.reload(); // EXCEPTION ajax-no-reload-premium : réouverture change tout
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },

        formatMoney(v) {
            const n = parseFloat(v) || 0;
            return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
        },

        discrepancyTypeLabel(t) {
            return {
                paiement_manquant: 'Paiement manquant',
                paiement_en_trop: 'Paiement en trop',
                montant_errone: 'Montant erroné',
                mode_errone: 'Mode erroné',
                date_erronee: 'Date erronée',
                autre: 'Autre',
            }[t] || t;
        },
    };
};
</script>
@endsection
