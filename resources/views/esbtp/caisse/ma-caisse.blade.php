@extends('layouts.app')

@section('title', 'Ma caisse - KLASSCI')

@push('styles')
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

    /* ---- Ecran mobile (shell m-*), namespace mcm- ---- */
    .mcm-bill-in { -moz-appearance: textfield; }
    .mcm-bill-in::-webkit-outer-spin-button,
    .mcm-bill-in::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .mcm-bill-in:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .mcm-bill-in.is-set { border-color: #0453cb; }
    .m-bill .mcm-bill-unit { color: #64748b; font-size: 12px; }
    .mcm-ecart-lbl { font-weight: 700; color: #0f172a; }
    .mcm-ok { color: #0f6b4c !important; }
    .mcm-bad { color: #a12016 !important; }
    .mcm-sec-btn { font-size: 12.5px; color: #0453cb; font-weight: 600; background: none; border: 0; padding: 6px 0; font-family: inherit; cursor: pointer; min-height: 44px; }
    .mcm-note { background: #fff; border: 1px solid #e6eaf2; border-radius: 14px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start; font-size: 13.5px; color: #334155; line-height: 1.4; }
    .mcm-note svg { width: 20px; height: 20px; flex-shrink: 0; color: #0453cb; margin-top: 1px; }
    .mcm-note.warn { border-color: #fcd9b6; background: #fff7ed; color: #7c2d12; }
    .mcm-note.warn svg { color: #c2410c; }
    .mcm-form { display: grid; gap: 12px; padding: 0 16px 16px; }
    .mcm-form .m-dl { margin: 0; }
    .mcm-count { font-size: 11.5px; color: #94a3b8; text-align: right; }
    .mcm-chip-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .mcm-wrap { white-space: normal; text-align: left; font-weight: 500; }
    .m-dl dd.mcm-wrap { text-align: left; grid-column: 1 / -1; color: #475569; }
</style>
@endpush

@section('content')
@php
    $locked = $session->isLocked();
    $statusClass = $session->status->value === 'open' ? 'open' : ($session->status->value === 'auto_closed' ? 'auto' : 'closed');
    $horsTiroir = collect($aggregat['par_mode'] ?? [])->filter(function ($mode, $cle) {
        $canon = \App\Enums\ModePaiement::tryFrom((string) $cle);
        return $canon === null || ! $canon->isDrawer();
    });
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ').' F';

    // Shell mobile actif (layout + composer) : le DOM de bureau reste rendu
    // dans .m-only-desktop et l'ecran m-* prend la place sous 992px.
    $mcShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $mcPeutGererCaisse = auth()->user()?->canAny(['cash_session.manage', 'module.caisse.access']) ?? false;
@endphp

@if($mcShell)
@php
    $mcmFmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $mcmPills = [];
    if ($session->opened_at) {
        $mcmPills[] = 'Ouverte ' . $session->opened_at->format('H:i');
    }
    if ($locked && $session->closed_at) {
        $mcmPills[] = $session->status->label() . ' ' . $session->closed_at->format('H:i');
    }
    $mcmPills[] = $aggregat['count'] . ' ' . ($aggregat['count'] > 1 ? 'versements validés' : 'versement validé');
    if (($aggregat['annules'] ?? 0) > 0) {
        $mcmPills[] = $aggregat['annules'] . ' rejeté' . ($aggregat['annules'] > 1 ? 's' : '');
    }

    $mcmFlash = session('success') ?: (session('warning') ?: session('error'));
    $mcmFlashType = session('success') ? 'success' : (session('warning') ? 'warning' : (session('error') ? 'error' : null));

    $mcmConfig = [
        'sessionId' => $session->id,
        'coupures' => array_values($coupures),
        'attendu' => (float) $aggregat['especes'],
        'verrouillee' => $locked,
        'resume' => $locked ? [
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'counted_amount' => $session->counted_amount !== null ? (float) $session->counted_amount : null,
            'expected_amount' => (float) $session->expected_amount,
            'variance' => $session->variance !== null ? (float) $session->variance : null,
            'closed_at' => $session->closed_at?->format('H:i'),
            'notes' => $session->notes,
        ] : null,
        'flash' => $mcmFlash,
        'flashType' => $mcmFlashType,
        'urls' => [
            'cloturer' => route('esbtp.caisse.cloturer'),
            'bordereau' => route('esbtp.caisse.bordereau', ['preview' => 1]),
            'bordereauPdf' => route('esbtp.caisse.bordereau'),
        ],
    ];
@endphp
<div class="m-only-mobile m-screen mcm-screen" x-data="mcmCaisse({{ \Illuminate\Support\Js::from($mcmConfig) }})">

    <x-m.appbar title="Ma caisse" :sub="'Session du ' . $session->business_date->isoFormat('D MMMM')">
        @canany(['cash_session.manage', 'module.caisse.access'])
            <a href="{{ route('esbtp.caisse.bordereau', ['preview' => 1]) }}" target="_blank" rel="noopener" class="m-ib" aria-label="Imprimer le bordereau">
                <x-m.icon name="print" />
            </a>
        @endcanany
    </x-m.appbar>

    <div class="m-body" data-m-ptr="reload">

        {{-- Chiffre-cle : les especes que le tiroir doit contenir --}}
        <x-m.hero label="Attendu en caisse (espèces)" :value="$mcmFmt($aggregat['especes'])" unit="FCFA" :pills="$mcmPills" />

        {{-- Journee deja cloturee (au chargement ou apres la cloture AJAX) --}}
        <template x-if="verrouillee">
            <div class="m-list one">
                <div class="mcm-chip-row">
                    <div class="m-sec"><b>Clôture</b></div>
                    <span class="m-chip" x-bind:class="resume && resume.status === 'closed' ? 'ok' : 'warn'" x-text="resume ? resume.status_label : ''"></span>
                </div>
                <dl class="m-dl">
                    <dt>Compté</dt>
                    <dd x-text="resume && resume.counted_amount !== null ? format(resume.counted_amount) + ' FCFA' : '—'"></dd>
                    <dt>Attendu</dt>
                    <dd x-text="resume ? format(resume.expected_amount) + ' FCFA' : '—'"></dd>
                    <dt class="mcm-ecart-lbl">Écart</dt>
                    <dd x-bind:class="classeEcart(resume ? resume.variance : null)" x-text="libelleEcart(resume ? resume.variance : null)"></dd>
                    <template x-if="resume && resume.notes">
                        <dt>Notes</dt>
                    </template>
                    <template x-if="resume && resume.notes">
                        <dd class="mcm-wrap" x-text="resume.notes"></dd>
                    </template>
                </dl>
                <template x-if="resume && resume.status === 'auto_closed'">
                    <div class="mcm-note warn">
                        <x-m.icon name="alert" />
                        <span>Cette journée a été fermée d'office sans comptage. Signalez-le à la comptabilité si le tiroir n'a pas été vérifié.</span>
                    </div>
                </template>
            </div>
        </template>

        {{-- Comptage des coupures, total en direct --}}
        <template x-if="!verrouillee">
            <div class="m-list one">
                <div class="m-sec">
                    <b>Comptage des coupures</b>
                    <button type="button" class="mcm-sec-btn" x-show="compte() > 0" x-on:click="reinitialiser()">Effacer</button>
                </div>
                <template x-for="valeur in coupures" x-bind:key="valeur">
                    <label class="m-bill">
                        <b x-text="format(valeur)"></b>
                        <span class="mcm-bill-unit">FCFA</span>
                        <input type="number"
                               class="cnt mcm-bill-in"
                               x-bind:class="quantite(valeur) > 0 ? 'is-set' : ''"
                               inputmode="numeric"
                               min="0"
                               step="1"
                               placeholder="0"
                               x-bind:aria-label="'Nombre de ' + format(valeur) + ' FCFA'"
                               x-bind:value="quantites[valeur] === 0 || quantites[valeur] === undefined ? '' : quantites[valeur]"
                               x-on:input="saisir(valeur, $event.target.value)">
                        <span class="sum" x-text="format(sousTotal(valeur))"></span>
                    </label>
                </template>
                <dl class="m-dl">
                    <dt>Compté</dt>
                    <dd x-text="format(compte()) + ' FCFA'"></dd>
                    <dt>Attendu</dt>
                    <dd>{{ $mcmFmt($aggregat['especes']) }} FCFA</dd>
                    <dt class="mcm-ecart-lbl">Écart</dt>
                    <dd x-bind:class="classeEcart(ecart())" x-text="libelleEcart(ecart())"></dd>
                </dl>
                <div class="mcm-note">
                    <x-m.icon name="cash" />
                    <span>Comptez les billets et les pièces sans recopier le montant attendu. Un écart n'empêche pas de clôturer : il sera inscrit sur le bordereau.</span>
                </div>
            </div>
        </template>

        {{-- Encaissements hors tiroir (mobile money, virement...) --}}
        @if($horsTiroir->isNotEmpty())
            <div class="m-list one">
                <div class="m-sec"><b>Hors tiroir</b></div>
                <dl class="m-dl">
                    @foreach($horsTiroir as $mode)
                        <dt>{{ $mode['label'] }} · {{ $mode['count'] }}</dt>
                        <dd>{{ $mcmFmt($mode['total']) }} FCFA</dd>
                    @endforeach
                </dl>
            </div>
        @endif

        {{-- Registre du jour --}}
        <div class="m-list one">
            <div class="m-sec">
                <b>Registre du jour</b>
                <span class="mcm-count">{{ $aggregat['count'] }} validé(s) · {{ $mcmFmt($aggregat['total']) }} FCFA</span>
            </div>
            @if($paiements->isEmpty())
                <x-m.empty icon="inbox" title="Aucun versement pour l'instant" text="Le premier encaissement du jour apparaîtra ici.">
                    @can('paiements.create')
                        <a href="{{ route('esbtp.paiements.create') }}" class="m-btn g"><x-m.icon name="plus" />Encaisser</a>
                    @endcan
                </x-m.empty>
            @else
                @foreach($paiements as $p)
                    @php
                        $mcmCanon = \App\Enums\ModePaiement::fromLegacy((string) $p->mode_paiement);
                        $mcmSt = mb_strtolower((string) $p->status, 'UTF-8');
                        [$mcmStLabel, $mcmStTone] = match ($mcmSt) {
                            'validé', 'valide' => ['Validé', 'ok'],
                            'rejeté', 'rejete' => ['Rejeté', 'bad'],
                            'en_attente', 'en attente' => ['À valider', 'warn'],
                            default => [$p->status ?: '—', 'mute'],
                        };
                        $mcmNom = trim(($p->etudiant->nom ?? '').' '.($p->etudiant->prenoms ?? '')) ?: '—';
                        $mcmAv = mb_substr($p->etudiant->prenoms ?? 'E', 0, 1, 'UTF-8') . mb_substr($p->etudiant->nom ?? '', 0, 1, 'UTF-8');
                    @endphp
                    <x-m.row :href="route('esbtp.paiements.show', $p->id)"
                             :av="$mcmAv"
                             :title="$mcmNom"
                             :sub="($p->created_at?->format('H:i') ?? '—') . ' · ' . ($mcmCanon?->label() ?? ($p->mode_paiement ?: '—'))"
                             :amount="$mcmFmt($p->montant) . ' FCFA'"
                             :neg="$mcmSt === 'rejeté' || $mcmSt === 'rejete'"
                             :chip="$mcmStLabel"
                             :chip-type="$mcmStTone" />
                @endforeach
            @endif
        </div>
    </div>

    @canany(['cash_session.manage', 'module.caisse.access'])
        <x-m.actionbar>
            <button type="button" class="m-btn p" x-show="!verrouillee" x-on:click="ouvrir('mcm-cloturer')">
                <x-m.icon name="lock" />Clôturer et générer le bordereau
            </button>
            <a class="m-btn p" x-show="verrouillee" x-cloak x-bind:href="urls.bordereau" target="_blank" rel="noopener">
                <x-m.icon name="file" />Voir le bordereau
            </a>
        </x-m.actionbar>

        {{-- Feuille de confirmation de la cloture --}}
        <x-m.sheet id="mcm-cloturer" title="Clôturer ma journée" :sub="'Session du ' . $session->business_date->isoFormat('D MMMM YYYY')">
            <form class="mcm-form" x-on:submit.prevent="cloturer()">
                <dl class="m-dl">
                    <dt>Compté</dt>
                    <dd x-text="format(compte()) + ' FCFA'"></dd>
                    <dt>Attendu</dt>
                    <dd x-text="format(attendu) + ' FCFA'"></dd>
                    <dt class="mcm-ecart-lbl">Écart</dt>
                    <dd x-bind:class="classeEcart(ecart())" x-text="libelleEcart(ecart())"></dd>
                </dl>
                <template x-if="compte() === 0">
                    <div class="mcm-note warn">
                        <x-m.icon name="alert" />
                        <span>Aucune coupure saisie : la caisse sera déclarée vide (0 FCFA).</span>
                    </div>
                </template>
                <template x-if="ecart() !== 0">
                    <div class="mcm-note warn">
                        <x-m.icon name="alert" />
                        <span>La caisse n'est pas juste. Indiquez ce qui explique l'écart : la comptabilité le lira sur le bordereau.</span>
                    </div>
                </template>
                <div class="m-field">
                    <label for="mcm-notes">Notes (écart, incident)</label>
                    <textarea id="mcm-notes" class="m-in ta" rows="3" maxlength="1000" x-model="notes"
                              placeholder="Ex : billet de 10 000 refusé, rendu au parent."></textarea>
                    <small class="mcm-count" x-text="notes.length + ' / 1000'"></small>
                </div>
                <button type="submit" class="m-btn p" x-bind:disabled="occupe">
                    <x-m.icon name="lock" />
                    <span x-show="!occupe">Confirmer la clôture</span>
                    <span x-show="occupe" x-cloak>Clôture en cours…</span>
                </button>
                <button type="button" class="m-btn g" x-on:click="hide()" x-bind:disabled="occupe">Revenir au comptage</button>
            </form>
        </x-m.sheet>
    @endcanany
</div>
@endif

<div class="dashboard-acasi mc-page {{ $mcShell ? 'm-only-desktop' : '' }}">
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
                @can('paiements.create')
                <a class="btn-acasi secondary" href="{{ route('esbtp.paiements.create') }}">
                    <i class="fas fa-plus"></i>Encaisser
                </a>
                @endcan
                @if($mcPeutGererCaisse)
                <a class="btn-acasi secondary" href="{{ route('esbtp.caisse.bordereau', ['preview' => 1]) }}" target="_blank">
                    <i class="fas fa-file-pdf"></i>Bordereau
                </a>
                @endif
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
                        @can('paiements.create')
                        <div class="mt-3">
                            <a class="btn-acasi primary" href="{{ route('esbtp.paiements.create') }}">Encaisser</a>
                        </div>
                        @endcan
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

                    @if($mcPeutGererCaisse)
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
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>

@if(! $locked && $mcPeutGererCaisse)
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
@endif
@endsection

@if($mcShell)
@push('scripts')
<script>
    if (typeof window.mcmCaisse !== 'function') {
        window.mcmCaisse = function (cfg) {
            var cle = 'mcm-comptage-' + (cfg.sessionId || 'x');
            return {
                coupures: (cfg.coupures || []).map(Number),
                quantites: {},
                attendu: Number(cfg.attendu || 0),
                verrouillee: !!cfg.verrouillee,
                resume: cfg.resume || null,
                urls: cfg.urls || {},
                notes: '',
                occupe: false,

                init() {
                    this.restaurer();
                    if (cfg.flash) {
                        this.toast(cfg.flash, cfg.flashType || 'success');
                    }
                },

                /* Le comptage survit a un rechargement ou a un appel telephonique :
                   memoire du navigateur seulement, jamais envoyee au serveur. */
                restaurer() {
                    try {
                        var brut = window.sessionStorage.getItem(cle);
                        var sauve = brut ? JSON.parse(brut) : null;
                        if (sauve && typeof sauve === 'object') {
                            var self = this;
                            Object.keys(sauve).forEach(function (k) {
                                var q = parseInt(sauve[k], 10);
                                if (q > 0 && self.coupures.indexOf(Number(k)) !== -1) {
                                    self.quantites[k] = q;
                                }
                            });
                        }
                    } catch (e) { /* stockage indisponible : on repart de zero */ }
                },
                memoriser() {
                    try {
                        window.sessionStorage.setItem(cle, JSON.stringify(this.quantites));
                    } catch (e) { /* ignore */ }
                },
                oublier() {
                    try { window.sessionStorage.removeItem(cle); } catch (e) { /* ignore */ }
                },

                quantite(valeur) {
                    var q = parseInt(this.quantites[valeur], 10);
                    return isNaN(q) || q < 0 ? 0 : q;
                },
                saisir(valeur, brut) {
                    var q = parseInt(String(brut).replace(/\D/g, ''), 10);
                    if (isNaN(q) || q <= 0) {
                        delete this.quantites[valeur];
                    } else {
                        this.quantites[valeur] = q;
                    }
                    this.memoriser();
                },
                reinitialiser() {
                    this.quantites = {};
                    this.oublier();
                },
                sousTotal(valeur) {
                    return this.quantite(valeur) * Number(valeur);
                },
                compte() {
                    var total = 0;
                    for (var i = 0; i < this.coupures.length; i++) {
                        total += this.sousTotal(this.coupures[i]);
                    }
                    return total;
                },
                ecart() {
                    return Math.round((this.compte() - this.attendu) * 100) / 100;
                },
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Number(n) || 0);
                },
                /* 0 FCFA est une valeur (caisse juste) ; « — » seulement sans comptage. */
                libelleEcart(v) {
                    if (v === null || v === undefined || isNaN(Number(v))) { return '—'; }
                    v = Number(v);
                    if (Math.abs(v) < 0.005) { return '0 FCFA ✓'; }
                    return (v < 0 ? '− ' : '+ ') + this.format(Math.abs(v)) + ' FCFA ' + (v < 0 ? '(manquant)' : '(excédent)');
                },
                classeEcart(v) {
                    if (v === null || v === undefined || isNaN(Number(v))) { return ''; }
                    return Math.abs(Number(v)) < 0.005 ? 'mcm-ok' : 'mcm-bad';
                },

                ouvrir(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
                },
                fermerTout() {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                },
                toast(message, type) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message: message } }));
                },

                async cloturer() {
                    if (this.occupe || this.verrouillee) { return; }
                    this.occupe = true;
                    try {
                        var res = await fetch(this.urls.cloturer, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ counted_amount: this.compte(), notes: this.notes.trim() || null }),
                        });
                        var data = await res.json().catch(function () { return {}; });
                        if (!res.ok || data.success === false) {
                            var msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'La clôture a échoué (HTTP ' + res.status + ').');
                            throw new Error(msg);
                        }
                        this.resume = data.session || null;
                        this.verrouillee = true;
                        if (data.bordereau_url) { this.urls.bordereau = data.bordereau_url; }
                        this.oublier();
                        this.fermerTout();
                        this.toast(data.message || 'Journée clôturée.', this.resume && Math.abs(Number(this.resume.variance)) < 0.005 ? 'success' : 'warning');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } catch (err) {
                        this.toast(err.message || 'La clôture a échoué.', 'error');
                    } finally {
                        this.occupe = false;
                    }
                }
            };
        };
    }
</script>
@endpush
@endif
