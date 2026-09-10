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

    /* ===== Écran mobile (namespace rsm- : reconciliation-show-mobile) ===== */
    .rsm-etape { display: flex; justify-content: space-between; align-items: baseline; margin: -6px 0 0; font-size: 12.5px; color: #64748b; font-weight: 600; }
    .rsm-etape b { color: #0f172a; font-size: 13px; }
    .rsm-bill { grid-template-columns: 1fr; gap: 8px; padding: 12px; }
    .rsm-bill.is-warn { border-color: #f4d9a6; }
    .rsm-bill.is-bad { border-color: #f5c2bd; }
    .rsm-bill.is-ok { border-color: #bfe8d3; }
    .rsm-bill-hd { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .rsm-bill-hd b { font-size: 14.5px; color: #0f172a; }
    .rsm-bill-sys { display: flex; justify-content: space-between; font-size: 12.5px; color: #64748b; }
    .rsm-bill-sys b { color: #0f172a; font-weight: 700; }
    .rsm-bill-in { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
    .rsm-bill-in .m-in { text-align: right; font-weight: 700; font-size: 17px; }
    .rsm-bill-in .m-in:disabled { background: #f8fafc; color: #475569; }
    .rsm-bill-in .m-btn { width: auto; height: 48px; padding: 0 14px; font-size: 13.5px; }
    .rsm-bill-in .m-btn svg { width: 18px; height: 18px; }
    .rsm-note { background: #fff; border: 1px solid #e6eaf2; border-radius: 14px; padding: 12px; display: grid; grid-template-columns: 44px 1fr; gap: 12px; align-items: center; }
    .rsm-note .av { width: 44px; height: 44px; border-radius: 14px; background: rgba(4,83,203,.1); color: #0453cb; display: grid; place-items: center; }
    .rsm-note .av svg { width: 20px; height: 20px; }
    .rsm-note .av.bad { background: #fdecea; color: #a12016; }
    .rsm-note .av.ok { background: #e6f6ef; color: #0f6b4c; }
    .rsm-note .nm { font-weight: 600; font-size: 14.5px; color: #0f172a; }
    .rsm-note .mt { font-size: 11.5px; color: #64748b; line-height: 1.4; }
    .rsm-row .tt span { white-space: normal; }
    .rsm-row .amt.warn { color: #8a5200; }
    .rsm-row.is-resolu { opacity: .82; }
    .rsm-form { display: grid; gap: 14px; padding: 0 16px 16px; }
    .rsm-hint { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.45; }
    .rsm-count { font-size: 11.5px; color: #64748b; text-align: right; }
    .rsm-count.bad { color: #a12016; }
    .rsm-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .rsm-modes button { min-height: 44px; border-radius: 12px; border: 1.5px solid #e6eaf2; background: #fff; font: inherit; font-size: 13px; font-weight: 600; color: #0f172a; padding: 8px 10px; cursor: pointer; }
    .rsm-modes button.on { border-color: #0453cb; background: rgba(4,83,203,.05); color: #0453cb; }
    .rsm-opt-off { opacity: .5; }
    .rsm-actions-menu .m-menu button.danger,
    .rsm-actions-menu .m-menu a.danger { color: #a12016; }
    .rsm-actions-menu .m-menu button.danger svg { color: #a12016; }
    .rsm-dl dd.wrap { white-space: normal; text-align: right; }
    .rsm-actionbar .m-btn.g svg { width: 18px; height: 18px; }
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

    // Shell mobile : le DOM de bureau reste dans .m-only-desktop, l'écran mobile
    // (maquette S['comptable:reconciliation']) vit à côté, sur le MÊME état Alpine.
    $rsmShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $rsmUser = auth()->user();
    $rsmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $rsmEcoleNom = $rsmEcole['name'] ?: ($rsmEcole['acronym'] ?: config('app.name'));
    $rsmPeutCompter = $rsmUser?->can('comptabilite.reconciliation.open') ?? false;
    $rsmPeutResoudre = $rsmUser?->can('comptabilite.reconciliation.resolve') ?? false;
    $rsmPeutApprouver = $rsmUser?->can('comptabilite.reconciliation.approve') ?? false;
    $rsmPeutExporter = $rsmUser?->can('comptabilite.reconciliation.export') ?? false;
    $rsmPeutRouvrir = $rsmUser?->can('comptabilite.reconciliation.bypass_lock') ?? false;
    $rsmADesActions = $rsmPeutResoudre || $rsmPeutExporter || $rsmPeutRouvrir;
    $rsmPeriode = optional($session->period_start)->format('d/m/Y');
    if ($session->period_start != $session->period_end) {
        $rsmPeriode = 'du ' . $rsmPeriode . ' au ' . optional($session->period_end)->format('d/m/Y');
    }
    $rsmFrequences = ['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel'];
    $rsmStatuts = collect(\App\Enums\ReconciliationSessionStatus::cases())
        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
        ->all();
    $rsmResolutions = [
        'create_corrective' => ['Créer un paiement correctif', 'Un paiement validé du montant de l\'écart est créé et lié à cet écart.'],
        'adjust_payment' => ['Ajuster le paiement concerné', 'Corrige le montant ou le mode d\'un paiement déjà enregistré.'],
        'cancel_payment' => ['Annuler le paiement concerné', 'Le paiement passe en « rejeté » : il n\'aurait pas dû être validé.'],
        'no_action' => ['Accepter l\'écart', 'Aucune modification : l\'écart est documenté avec votre motif.'],
    ];
@endphp

<div class="container-fluid" x-data="recShow()" x-init="init()">
<div class="{{ $rsmShell ? 'm-only-desktop' : '' }}">
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

@if($rsmShell)
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rsm-screen">
    <x-m.appbar title="Réconciliation"
                :sub="$session->code . ' · ' . $rsmEcoleNom"
                :back="route('esbtp.comptabilite.reconciliation.index')"
                :action="$rsmADesActions ? 'more' : null"
                action-label="Actions"
                x-on:click="mOuvrir('rsm-actions')" />

    <div class="m-body" data-m-ptr="reload">
        {{-- Progression du bouclage : Comptages · Écarts · Revue · Clôture --}}
        <div class="m-step" aria-hidden="true">
            <i x-bind:class="mEtape() >= 1 ? 'on' : ''"></i>
            <i x-bind:class="mEtape() >= 2 ? 'on' : ''"></i>
            <i x-bind:class="mEtape() >= 3 ? 'on' : ''"></i>
            <i x-bind:class="mEtape() >= 4 ? 'on' : ''"></i>
        </div>
        <p class="rsm-etape">
            <b x-text="'Étape ' + mEtape() + ' · ' + mEtapeLibelle()">Étape 1 · Comptages</b>
            <span x-text="statusLabel">{{ $session->status->label() }}</span>
        </p>

        <section class="m-hero">
            <span class="k">Écart total · {{ $rsmFrequences[$session->frequency] ?? ucfirst($session->frequency) }} {{ $rsmPeriode }}</span>
            <span class="v" x-text="mEcartSigne(totalEcart)">{{ number_format($total_ecart, 0, ',', ' ') }} FCFA</span>
            <div class="row">
                <span class="pill" x-text="mModesComptes() + (mModesComptes() > 1 ? ' modes comptés' : ' mode compté')">{{ $cash_counts->count() }} {{ $cash_counts->count() > 1 ? 'modes comptés' : 'mode compté' }}</span>
                <span class="pill" x-text="discrepancies.length + (discrepancies.length > 1 ? ' écarts' : ' écart')">{{ $discrepancies->count() }} {{ $discrepancies->count() > 1 ? 'écarts' : 'écart' }}</span>
                <span class="pill">Ouverte par {{ optional($session->opener)->name ?? '—' }}</span>
            </div>
        </section>

        <div class="m-seg" role="tablist" aria-label="Sections de la session">
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'comptages' ? 'true' : 'false'"
                    x-bind:class="mSeg === 'comptages' ? 'on' : ''" x-on:click="mSeg = 'comptages'">Comptages</button>
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'ecarts' ? 'true' : 'false'"
                    x-bind:class="mSeg === 'ecarts' ? 'on' : ''" x-on:click="mSeg = 'ecarts'"
                    x-text="'Écarts · ' + discrepancies.length">Écarts · {{ $discrepancies->count() }}</button>
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'revue' ? 'true' : 'false'"
                    x-bind:class="mSeg === 'revue' ? 'on' : ''" x-on:click="mSeg = 'revue'">Revue</button>
        </div>

        {{-- ---------- Comptages ---------- --}}
        <div x-show="mSeg === 'comptages'" class="m-list one">
            <div class="rsm-note" x-show="editable" role="status">
                <div class="av"><x-m.icon name="cash" /></div>
                <div>
                    <div class="nm">Comptez chaque mode à la main</div>
                    <div class="mt">Saisissez le montant réellement constaté ; l'écart avec les paiements validés se calcule en direct. Un montant à 0 est un vrai comptage.</div>
                </div>
            </div>
            <div class="rsm-note" x-show="!editable" role="status">
                <div class="av"><x-m.icon name="lock" /></div>
                <div>
                    <div class="nm">Comptages figés</div>
                    <div class="mt">La session n'est plus modifiable dans son statut actuel.</div>
                </div>
            </div>

            <template x-for="mode in modes" :key="'m-' + mode.value">
                <div class="m-bill rsm-bill" x-bind:class="'is-' + mTonMode(mode.value)">
                    <div class="rsm-bill-hd">
                        <b x-text="mode.label"></b>
                        <span class="m-chip" x-bind:class="mTonMode(mode.value)" x-text="mEcartMode(mode.value)"></span>
                    </div>
                    <div class="rsm-bill-sys">
                        <span>Paiements validés (système)</span>
                        <b x-text="formatMoney(getCount(mode.value).montant_systeme || 0)"></b>
                    </div>
                    <div class="rsm-bill-in">
                        <input type="number" inputmode="decimal" min="0" step="1" class="m-in"
                               x-bind:id="'rsm-in-' + mode.value"
                               x-bind:aria-label="'Montant compté · ' + mode.label"
                               placeholder="Non compté"
                               x-bind:value="mCompte(mode.value)"
                               x-on:input="onInput(mode.value, $event.target.value)"
                               x-bind:disabled="!editable || !@js($rsmPeutCompter)">
                        @can('comptabilite.reconciliation.open')
                            <button type="button" class="m-btn g" x-show="editable && hasDraft(mode.value)"
                                    x-on:click="saveCount(mode.value)" x-bind:disabled="saving[mode.value]">
                                <x-m.icon name="check" />
                                <span x-show="!saving[mode.value]">Enregistrer</span>
                                <span x-show="saving[mode.value]" x-cloak>…</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </template>
        </div>

        {{-- ---------- Écarts ---------- --}}
        <div x-show="mSeg === 'ecarts'" x-cloak>
            <div x-show="discrepancies.length === 0">
                <x-m.empty icon="check" title="Aucun écart détecté" text="Quand tous les modes comptés correspondent aux paiements validés, il n'y a rien à justifier.">
                    @can('comptabilite.reconciliation.resolve')
                        <button type="button" class="m-btn g" x-show="editable" x-on:click="mDetecter()" x-bind:disabled="detecting">
                            <x-m.icon name="search" />
                            <span x-show="!detecting">Détecter les écarts</span>
                            <span x-show="detecting" x-cloak>Analyse…</span>
                        </button>
                    @endcan
                </x-m.empty>
            </div>
            <div class="m-list one" x-show="discrepancies.length > 0" x-cloak>
                <div class="rsm-note" x-show="mEcartsATraiter() > 0" role="status">
                    <div class="av bad"><x-m.icon name="alert" /></div>
                    <div>
                        <div class="nm" x-text="mEcartsATraiter() + (mEcartsATraiter() > 1 ? ' écarts à justifier' : ' écart à justifier')"></div>
                        <div class="mt">Touchez un écart pour le résoudre : chaque action est motivée (10 caractères minimum) et tracée.</div>
                    </div>
                </div>
                <div class="rsm-note" x-show="mEcartsATraiter() === 0" role="status">
                    <div class="av ok"><x-m.icon name="check" /></div>
                    <div>
                        <div class="nm">Tous les écarts sont traités</div>
                        <div class="mt">La session peut passer en revue.</div>
                    </div>
                </div>
                <template x-for="d in discrepancies" :key="'d-' + d.id">
                    <div class="m-row rsm-row"
                         x-bind:class="d.action === 'resolu' ? 'is-resolu' : ''"
                         x-bind:role="mPeutResoudre(d) ? 'button' : null"
                         x-bind:tabindex="mPeutResoudre(d) ? 0 : null"
                         x-on:click="mPeutResoudre(d) && mResoudre(d)"
                         x-on:keydown.enter.prevent="mPeutResoudre(d) && mResoudre(d)">
                        <div class="av ic" aria-hidden="true"><x-m.icon name="alert" /></div>
                        <div class="tt">
                            <b x-text="discrepancyTypeLabel(d.type) + (d.mode_label ? ' · ' + d.mode_label : '')"></b>
                            <span x-text="d.motif"></span>
                        </div>
                        <div class="tr">
                            <span class="amt" x-bind:class="d.montant_ecart < 0 ? 'neg' : 'warn'" x-text="mEcartSigne(d.montant_ecart)"></span>
                            <span class="m-chip" x-bind:class="d.action === 'resolu' ? 'ok' : (d.action === 'en_revue' ? 'warn' : 'bad')" x-text="discrepancyActionLabel(d.action)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ---------- Revue ---------- --}}
        <div x-show="mSeg === 'revue'" x-cloak class="m-list one">
            <dl class="m-dl rsm-dl">
                <dt>Statut</dt><dd x-text="statusLabel">{{ $session->status->label() }}</dd>
                <dt>Période</dt><dd class="wrap">{{ $rsmPeriode }}</dd>
                <dt>Ouverte par</dt><dd class="wrap">{{ optional($session->opener)->name ?? '—' }} · {{ optional($session->opened_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt>Passée en revue</dt><dd class="wrap">{{ $session->reviewer ? $session->reviewer->name . ' · ' . optional($session->reviewed_at)->format('d/m/Y H:i') : '—' }}</dd>
                <dt>Approuvée</dt><dd class="wrap">{{ $session->approver ? $session->approver->name . ' · ' . optional($session->approved_at)->format('d/m/Y H:i') : '—' }}</dd>
                <dt>Clôturée</dt><dd class="wrap">{{ $session->closer ? $session->closer->name . ' · ' . optional($session->closed_at)->format('d/m/Y H:i') : '—' }}</dd>
                @if($session->reopen_reason)
                    <dt>Réouverture</dt><dd class="wrap">{{ $session->reopen_reason }}</dd>
                @endif
                <dt>Écart total</dt><dd x-text="mEcartSigne(totalEcart)">{{ number_format($total_ecart, 0, ',', ' ') }} FCFA</dd>
                <dt>Écarts résolus</dt><dd x-text="mEcartsResolus() + ' / ' + discrepancies.length">{{ $discrepancies->where('action', 'resolu')->count() }} / {{ $discrepancies->count() }}</dd>
            </dl>
            <div class="rsm-note">
                <div class="av"><x-m.icon name="users" /></div>
                <div>
                    <div class="nm">Séparation des devoirs</div>
                    <div class="mt">Brouillon → En revue → Approuvée → Clôturée. Selon le réglage de l'établissement, la personne qui a ouvert la session ne peut pas l'approuver.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre d'action : une seule action principale à la fois, chacune sous sa permission --}}
    <x-m.actionbar class="rsm-actionbar">
        @can('comptabilite.reconciliation.resolve')
            <button type="button" class="m-btn p" x-show="mSeg === 'comptages' && editable"
                    x-on:click="mPasserAuxEcarts()" x-bind:disabled="detecting">
                <span x-show="!detecting">Passer aux écarts</span>
                <span x-show="detecting" x-cloak>Analyse…</span>
                <x-m.icon name="chr" />
            </button>
        @endcan
        @can('comptabilite.reconciliation.open')
            <button type="button" class="m-btn p" x-show="mSeg !== 'comptages' && canReview"
                    x-on:click="mConfirmer('review')" x-bind:disabled="mEcartsATraiter() > 0">
                <x-m.icon name="check" />Passer en revue
            </button>
        @endcan
        @can('comptabilite.reconciliation.approve')
            <button type="button" class="m-btn p" x-show="canApprove" x-on:click="mConfirmer('approve')">
                <x-m.icon name="check" />Approuver la session
            </button>
            <button type="button" class="m-btn p" x-show="canClose" x-on:click="mConfirmer('close')">
                <x-m.icon name="lock" />Clôturer la session
            </button>
        @endcan
        @can('comptabilite.reconciliation.export')
            <a href="{{ route('esbtp.comptabilite.reconciliation.export-pv', $session) }}" class="m-btn g" x-show="status === 'closed'">
                <x-m.icon name="dl" />Télécharger le PV
            </a>
        @endcan
    </x-m.actionbar>

    {{-- ============ Feuille « Actions » ============ --}}
    @if($rsmADesActions)
    <x-m.sheet id="rsm-actions" title="Actions" :sub="$session->code" class="rsm-actions-menu">
        <div class="m-menu">
            @can('comptabilite.reconciliation.resolve')
                <button type="button" x-show="editable" x-on:click="hide(); mDetecter()">
                    <x-m.icon name="search" />Détecter les écarts<span class="ch"><x-m.icon name="chr" /></span>
                </button>
            @endcan
            @can('comptabilite.reconciliation.export')
                <a href="{{ route('esbtp.comptabilite.reconciliation.export-pv', $session) }}" x-show="status === 'closed'" x-on:click="hide()">
                    <x-m.icon name="dl" />Télécharger le PV (PDF)<span class="ch"><x-m.icon name="chr" /></span>
                </a>
            @endcan
            @can('comptabilite.reconciliation.bypass_lock')
                <button type="button" class="danger" x-show="canReopen" x-on:click="hide(); mOuvrir('rsm-rouvrir')">
                    <x-m.icon name="refresh" />Rouvrir la session (exception)<span class="ch"><x-m.icon name="chr" /></span>
                </button>
            @endcan
            <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}">
                <x-m.icon name="list" />Toutes les sessions<span class="ch"><x-m.icon name="chr" /></span>
            </a>
        </div>
    </x-m.sheet>
    @endif

    {{-- ============ Feuille « Résoudre l'écart » ============ --}}
    @can('comptabilite.reconciliation.resolve')
    <x-m.sheet id="rsm-resoudre" title="Résoudre l'écart" :sub="$session->code">
        <form class="rsm-form" x-on:submit.prevent="mSoumettreResolution()">
            <dl class="m-dl" x-show="resolveModal.discrepancy">
                <dt>Type</dt><dd x-text="resolveModal.discrepancy ? discrepancyTypeLabel(resolveModal.discrepancy.type) : ''"></dd>
                <dt>Mode</dt><dd x-text="resolveModal.discrepancy && resolveModal.discrepancy.mode_label ? resolveModal.discrepancy.mode_label : '—'"></dd>
                <dt>Écart</dt><dd x-text="resolveModal.discrepancy ? mEcartSigne(resolveModal.discrepancy.montant_ecart) : ''"></dd>
            </dl>

            <div class="m-field">
                <label>Que faire de cet écart ?</label>
                <div class="m-opt">
                    @foreach($rsmResolutions as $rsmCle => [$rsmLibelle, $rsmAide])
                        <label x-bind:class="(resolveModal.resolution_type === @js($rsmCle) ? 'on ' : '') + (mResolutionPossible(@js($rsmCle)) ? '' : 'rsm-opt-off')">
                            <span class="rd" aria-hidden="true"></span>
                            <div><b>{{ $rsmLibelle }}</b><span>{{ $rsmAide }}</span></div>
                            <input type="radio" name="rsm_resolution" value="{{ $rsmCle }}" x-model="resolveModal.resolution_type"
                                   x-bind:disabled="!mResolutionPossible(@js($rsmCle))">
                        </label>
                    @endforeach
                </div>
                <p class="rsm-hint" x-show="resolveModal.discrepancy && !resolveModal.discrepancy.paiement_concerne_id">Aucun paiement précis n'est lié à cet écart : seuls le paiement correctif et l'acceptation sont possibles.</p>
            </div>

            <div class="m-field" x-show="resolveModal.resolution_type === 'adjust_payment'" x-cloak>
                <label for="rsm-montant">Nouveau montant du paiement</label>
                <input id="rsm-montant" type="number" inputmode="decimal" min="0" step="1" class="m-in"
                       x-model.number="resolveModal.payload.montant" placeholder="Laisser vide pour ne pas changer">
            </div>

            <div class="m-field" x-show="resolveModal.resolution_type === 'create_corrective'" x-cloak>
                <label>Mode du paiement correctif</label>
                <div class="rsm-modes" role="radiogroup" aria-label="Mode du paiement correctif">
                    <template x-for="mode in modes" :key="'rm-' + mode.value">
                        <button type="button" role="radio"
                                x-bind:aria-checked="resolveModal.payload.mode_paiement === mode.value ? 'true' : 'false'"
                                x-bind:class="resolveModal.payload.mode_paiement === mode.value ? 'on' : ''"
                                x-on:click="resolveModal.payload.mode_paiement = mode.value"
                                x-text="mode.label"></button>
                    </template>
                </div>
                <p class="rsm-hint" x-text="resolveModal.discrepancy ? 'Montant du correctif : ' + formatMoney(Math.abs(resolveModal.discrepancy.montant_ecart)) + ', daté d\'aujourd\'hui.' : ''"></p>
            </div>

            <div class="m-field">
                <label for="rsm-motif">Motif (10 caractères minimum)</label>
                <textarea id="rsm-motif" class="m-in ta" rows="3" minlength="10" maxlength="1000" required
                          x-model="resolveModal.motif" placeholder="Pourquoi cette décision ? Elle sera lue en cas de contrôle."></textarea>
                <small class="rsm-count" x-bind:class="resolveModal.motif.trim().length < 10 ? 'bad' : ''"
                       x-text="resolveModal.motif.trim().length + ' / 10 minimum'"></small>
            </div>

            <div class="rsm-note" x-show="resolveModal.resolution_type">
                <div class="av"><x-m.icon name="file" /></div>
                <div>
                    <div class="nm">Ce qui va se passer</div>
                    <div class="mt" x-text="previewMessage()"></div>
                </div>
            </div>

            <button type="submit" class="m-btn p" x-bind:disabled="!canSubmit || resolveModal.submitting">
                <span x-show="!resolveModal.submitting">Confirmer la résolution</span>
                <span x-show="resolveModal.submitting" x-cloak>Enregistrement…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </form>
    </x-m.sheet>
    @endcan

    {{-- ============ Feuille « Confirmer » (revue / approbation / clôture) ============ --}}
    @canany(['comptabilite.reconciliation.open', 'comptabilite.reconciliation.approve'])
    <x-m.sheet id="rsm-confirmer" title="Confirmer" :sub="$session->code">
        <div class="rsm-form">
            <div class="rsm-note">
                <div class="av"><x-m.icon name="alert" /></div>
                <div>
                    <div class="nm" x-text="confirmation.titre"></div>
                    <div class="mt" x-text="confirmation.texte"></div>
                </div>
            </div>
            <button type="button" class="m-btn p" x-on:click="mTransition(confirmation.action)" x-bind:disabled="transitionEnCours">
                <span x-show="!transitionEnCours" x-text="confirmation.bouton"></span>
                <span x-show="transitionEnCours" x-cloak>Enregistrement…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Pas maintenant</button>
        </div>
    </x-m.sheet>
    @endcanany

    {{-- ============ Feuille « Rouvrir » (exception, motif long) ============ --}}
    @can('comptabilite.reconciliation.bypass_lock')
    <x-m.sheet id="rsm-rouvrir" title="Rouvrir la session" :sub="$session->code . ' · action exceptionnelle'">
        <form class="rsm-form" x-on:submit.prevent="mRouvrir()">
            <div class="rsm-note">
                <div class="av bad"><x-m.icon name="alert" /></div>
                <div>
                    <div class="nm">Réouverture tracée</div>
                    <div class="mt">La session clôturée redevient modifiable. Le motif est conservé dans le journal d'audit.</div>
                </div>
            </div>
            <div class="m-field">
                <label for="rsm-reason">Motif de réouverture (30 caractères minimum)</label>
                <textarea id="rsm-reason" class="m-in ta" rows="4" minlength="30" maxlength="2000" required
                          x-model="reopenReason" placeholder="Expliquez précisément pourquoi cette session doit être rouverte."></textarea>
                <small class="rsm-count" x-bind:class="reopenReason.trim().length < 30 ? 'bad' : ''"
                       x-text="reopenReason.trim().length + ' / 30 minimum'"></small>
            </div>
            <button type="submit" class="m-btn d" x-bind:disabled="transitionEnCours || reopenReason.trim().length < 30">
                <span x-show="!transitionEnCours">Rouvrir la session</span>
                <span x-show="transitionEnCours" x-cloak>Réouverture…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </form>
    </x-m.sheet>
    @endcan
</div>
@endif
</div>
@endsection

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
            'action' => $d->action,
            'montant_ecart' => (float) $d->montant_ecart,
            'motif' => $d->motif,
            'paiement_concerne_id' => $d->paiement_concerne_id,
            'mode_paiement' => $d->cashCount?->mode_paiement,
            'mode_label' => $d->cashCount?->modeLabel(),
        ])->all(),
        'statuts' => $rsmStatuts,
        'resolveUrl' => route('esbtp.comptabilite.reconciliation.resolve', ['discrepancy' => '__ID__']),
    ];
    $jsConfirmations = [
        'review' => [
            'action' => 'review',
            'titre' => 'Passer la session en revue',
            'texte' => 'Les comptages et les écarts sont figés. Une autre personne habilitée pourra ensuite approuver la session.',
            'bouton' => 'Passer en revue',
        ],
        'approve' => [
            'action' => 'approve',
            'titre' => 'Approbation de la session',
            'texte' => 'Vous validez les comptages et la résolution des écarts. Selon le réglage de l\'établissement, la personne qui a ouvert la session ne peut pas l\'approuver.',
            'bouton' => 'Approuver',
        ],
        'close' => [
            'action' => 'close',
            'titre' => 'Clôture de la session',
            'texte' => 'La clôture est définitive : les paiements de la période sont verrouillés et le PV devient disponible.',
            'bouton' => 'Clôturer',
        ],
    ];
@endphp

@push('scripts')
<script>
if (typeof window.recShow !== 'function') {
window.recShow = function () {
    return {
        tab: 'counts',
        sessionId: @json($jsPayload['sessionId']),
        status: @json($jsPayload['status']),
        statusLabel: @json($jsPayload['statusLabel']),
        statuts: @json($jsPayload['statuts']),
        totalEcart: @json($jsPayload['totalEcart']),
        editable: @json($jsPayload['isModifiable']),
        cashCounts: @json($jsPayload['cashCounts']),
        modes: @json($jsPayload['modes']),
        discrepancies: @json($jsPayload['discrepancies']),
        portalUrls: @json($portalUrls ?? []),
        resolveUrl: @json($jsPayload['resolveUrl']),
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
        // Écran mobile
        mSeg: 'comptages',
        confirmations: @json($jsConfirmations),
        confirmation: { action: '', titre: '', texte: '', bouton: '' },
        transitionEnCours: false,
        reopenReason: '',

        init() {
            window.addEventListener('reconciliation:refresh', () => this.reload());
            if (this.discrepancies.some(d => d.action !== 'resolu') && !this.editable) {
                this.mSeg = 'ecarts';
            }
            if (['review', 'approved', 'closed'].includes(this.status)) {
                this.mSeg = 'revue';
            }
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

                this.discrepancies = (data.discrepancies || []).map(d => this.mNormaliserEcart(d));
                this.tab = 'discrepancies';
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: data.created_count > 0 ? 'warning' : 'success', message: data.message }
                }));
                return true;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
                return false;
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
            if (m.motif.trim().length < 10) return false;
            if (['adjust_payment', 'cancel_payment'].includes(m.resolution_type)
                && !m.payload.paiement_id && !(m.discrepancy && m.discrepancy.paiement_concerne_id)) return false;
            return true;
        },

        previewMessage() {
            const m = this.resolveModal;
            if (!m.resolution_type || !m.discrepancy) return '';
            const ecart = m.discrepancy.montant_ecart;
            const abs = Math.abs(ecart);
            const fmt = this.formatMoney(abs);
            const pid = m.payload.paiement_id || m.discrepancy.paiement_concerne_id;
            switch (m.resolution_type) {
                case 'create_corrective':
                    return `Un nouveau paiement validé de ${fmt} sera créé et lié à cet écart. L'écart passera à 0 après détection suivante.`;
                case 'adjust_payment':
                    if (!pid) return 'Aucun paiement lié à cet écart : renseignez le paiement à ajuster.';
                    return `Le paiement #${pid} sera ajusté (montant : ${m.payload.montant ?? 'inchangé'}). Le journal d'audit garde l'avant et l'après.`;
                case 'cancel_payment':
                    if (!pid) return 'Aucun paiement lié à cet écart : renseignez le paiement à annuler.';
                    return `Le paiement #${pid} passera en statut « rejeté » et sortira des totaux validés.`;
                case 'no_action':
                    return `Aucune modification. L'écart de ${fmt} sera documenté avec votre motif (perte ou excédent exceptionnel).`;
            }
            return '';
        },

        async submitResolve() {
            if (!this.canSubmit) return;
            this.resolveModal.submitting = true;
            try {
                const payload = {
                    resolution_type: this.resolveModal.resolution_type,
                    motif: this.resolveModal.motif.trim(),
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

                const res = await fetch(this.resolveUrl.replace('__ID__', this.resolveModal.discrepancy.id), {
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
                const ancien = idx !== -1 ? this.discrepancies[idx] : {};
                const nouveau = this.mNormaliserEcart(data.discrepancy);
                nouveau.mode_paiement = nouveau.mode_paiement || ancien.mode_paiement || null;
                nouveau.mode_label = nouveau.mode_label || ancien.mode_label || null;
                if (idx !== -1) this.discrepancies[idx] = nouveau;
                this.closeResolveModal();
                window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: { id: 'rsm-resoudre' } }));
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: data.message }
                }));
                return true;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
                return false;
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
                this.mAppliquerSession(data.session);
                this.totalEcart = data.total_ecart;
                if (Array.isArray(data.cash_counts)) {
                    const counts = {};
                    data.cash_counts.forEach(c => { counts[c.mode_paiement] = c; });
                    this.cashCounts = counts;
                }
                if (Array.isArray(data.discrepancies)) {
                    this.discrepancies = data.discrepancies.map(d => this.mNormaliserEcart(d));
                }
            } catch (e) { /* silencieux : la page reste utilisable */ }
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

        /* ---------- écran mobile : l'état est celui du bureau, sans rechargement ---------- */
        mOuvrir(id) {
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
        },
        mNormaliserEcart(d) {
            return {
                id: d.id,
                type: d.type,
                action: d.action || 'a_traiter',
                montant_ecart: parseFloat(d.montant_ecart) || 0,
                motif: d.motif || '',
                paiement_concerne_id: d.paiement_concerne_id || null,
                mode_paiement: d.mode_paiement || (d.cash_count ? d.cash_count.mode_paiement : null),
                mode_label: d.mode_label || null,
            };
        },
        mAppliquerSession(session) {
            if (!session) return;
            this.status = session.status;
            this.statusLabel = this.statuts[session.status] || session.status;
            this.editable = ['draft', 'reopened'].includes(this.status);
        },
        mEtape() {
            if (this.status === 'closed') return 4;
            if (this.status === 'approved') return 4;
            if (this.status === 'review') return 3;
            return this.mModesComptes() > 0 ? 2 : 1;
        },
        mEtapeLibelle() {
            return ['Comptages', 'Écarts', 'Revue', 'Clôture'][this.mEtape() - 1];
        },
        mModesComptes() {
            return Object.keys(this.cashCounts).length;
        },
        mCompte(mode) {
            if (this.drafts[mode] !== undefined) return this.drafts[mode];
            const c = this.cashCounts[mode];
            if (!c || c.montant_compte === null || c.montant_compte === undefined) return '';
            return parseFloat(c.montant_compte);
        },
        mTonMode(mode) {
            if (this.drafts[mode] === undefined && !this.cashCounts[mode]) return 'mute';
            const e = this.getCount(mode).ecart || 0;
            if (e > 0) return 'warn';
            if (e < 0) return 'bad';
            return 'ok';
        },
        mEcartMode(mode) {
            if (this.drafts[mode] === undefined && !this.cashCounts[mode]) return 'À compter';
            const e = this.getCount(mode).ecart || 0;
            if (e === 0) return 'Écart 0';
            return 'Écart ' + this.mEcartSigne(e);
        },
        mEcartSigne(v) {
            const n = parseFloat(v) || 0;
            const abs = new Intl.NumberFormat('fr-FR').format(Math.abs(n));
            if (n === 0) return '0 FCFA';
            return (n > 0 ? '+' : '−') + abs + ' FCFA';
        },
        mEcartsATraiter() {
            return this.discrepancies.filter(d => d.action !== 'resolu' && d.action !== 'rejete').length;
        },
        mEcartsResolus() {
            return this.discrepancies.filter(d => d.action === 'resolu').length;
        },
        mPeutResoudre(d) {
            return @json($rsmPeutResoudre) && this.editable && d.action !== 'resolu';
        },
        mResolutionPossible(type) {
            if (!['adjust_payment', 'cancel_payment'].includes(type)) return true;
            return !!(this.resolveModal.discrepancy && this.resolveModal.discrepancy.paiement_concerne_id);
        },
        mResoudre(d) {
            this.openResolveModal(d);
            this.resolveModal.open = false; // la feuille remplace le modal de bureau
            this.resolveModal.payload.mode_paiement = d.mode_paiement || null;
            this.mOuvrir('rsm-resoudre');
        },
        async mSoumettreResolution() {
            const ok = await this.submitResolve();
            if (ok) this.mSeg = 'ecarts';
        },
        async mDetecter() {
            const ok = await this.detectDiscrepancies();
            if (ok) this.mSeg = 'ecarts';
        },
        async mPasserAuxEcarts() {
            if (Object.keys(this.drafts).length > 0) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'warning', message: 'Enregistrez d\'abord les comptages modifiés.' }
                }));
                return;
            }
            if (this.mModesComptes() === 0) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'warning', message: 'Saisissez au moins un comptage avant de chercher les écarts.' }
                }));
                return;
            }
            await this.mDetecter();
        },
        mConfirmer(action) {
            this.confirmation = this.confirmations[action] || { action: action, titre: 'Confirmer', texte: '', bouton: 'Confirmer' };
            this.mOuvrir('rsm-confirmer');
        },
        async mTransition(action) {
            if (!action || this.transitionEnCours) return;
            this.transitionEnCours = true;
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                this.mAppliquerSession(data.session);
                window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: { id: 'rsm-confirmer' } }));
                this.mSeg = 'revue';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: data.message } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.transitionEnCours = false;
            }
        },
        async mRouvrir() {
            const reason = this.reopenReason.trim();
            if (reason.length < 30 || this.transitionEnCours) return;
            this.transitionEnCours = true;
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
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                this.mAppliquerSession(data.session);
                this.reopenReason = '';
                window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: { id: 'rsm-rouvrir' } }));
                this.mSeg = 'comptages';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: data.message } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.transitionEnCours = false;
            }
        },
    };
};
}
</script>
@endpush
