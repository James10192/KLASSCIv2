{{--
    Bulletin de paie d'un enseignant — rendu MOBILE (shell m-*), suite de la
    maquette S['comptable:paie'] (« valider et payer depuis la fiche »).
    Inclus par esbtp/comptabilite/salaires/show.blade.php quand le shell mobile
    est actif ; le DOM de bureau reste dans .m-only-desktop.

    Le bulletin compact reprend les lignes enregistrées (gains : heures × taux
    par type ; retenues : ITS, CNPS, autres) — aucun montant n'est recalculé
    ici. « Valider » et « Marquer payé » passent par les mêmes routes que le
    bureau, en JSON, chacune derrière une confirmation en feuille.

    Namespace CSS propre à l'écran : pysm- (paie-show-mobile).
--}}
@php
    $pysmUser = auth()->user();
    $pysmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $pysmFmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $pysmFmtH = function ($v) {
        $h = (int) floor((float) $v);
        $m = (int) round(((float) $v - $h) * 60);
        return $h . ' h' . ($m > 0 ? sprintf(' %02d', $m) : '');
    };
    $pysmNom = $salaire->teacher?->user?->name ?? ($salaire->teacher?->name ?? 'Enseignant');
    $pysmRegime = \App\Enums\TeacherRegime::tryFrom((string) ($salaire->teacher?->regime ?? ''))?->label();
    $pysmPeriode = $moisLabel . ' ' . $salaire->annee;
    $pysmBrut = (float) $salaire->salaire_base + (float) $salaire->primes;

    // Mêmes conditions que le contrôleur (séparation des devoirs) : le bouton
    // ne s'affiche que si l'appel aboutira.
    $pysmEstPreparateur = in_array($pysmUser?->id, [$salaire->prepared_by, $salaire->createur_id], true);
    $pysmPeutValider = $pysmEstPreparateur
        ? ($pysmUser?->can('comptabilite.salaires.validate_own') ?? false)
        : ($pysmUser?->can('comptabilite.salaires.validate') ?? false);
    $pysmPeutPayer = $pysmUser?->can('comptabilite.salaires.pay') ?? false;

    $pysmChips = [
        \App\Models\ESBTPSalaire::ST_BROUILLON => 'warn',
        \App\Models\ESBTPSalaire::ST_VALIDE    => 'info',
        \App\Models\ESBTPSalaire::ST_PAYE      => 'ok',
        \App\Models\ESBTPSalaire::ST_ANNULE    => 'bad',
    ];
    $pysmLabels = ['brouillon' => 'À valider'] + \App\Models\ESBTPSalaire::statutLabels();

    $pysmCfg = [
        'statut' => (string) $salaire->workflow_status,
        'labels' => $pysmLabels,
        'tons' => $pysmChips,
        'validePar' => $salaire->validePar?->name,
        'dateValidation' => $salaire->date_validation?->format('d/m/Y H:i'),
        'payePar' => $salaire->payePar?->name,
        'modeLabel' => $modeLabel,
        'reference' => $salaire->reference_paiement,
        'datePaiement' => $salaire->date_paiement?->format('d/m/Y'),
        'aujourdhui' => now()->toDateString(),
        'flash' => session('success'),
        'erreur' => session('error'),
        'urls' => [
            'valider' => $pysmPeutValider ? route('esbtp.comptabilite.salaires.validate', $salaire->id) : null,
            'payer' => $pysmPeutPayer ? route('esbtp.comptabilite.salaires.pay', $salaire->id) : null,
        ],
    ];
@endphp

<div class="m-only-mobile m-screen pysm-screen" x-data="pysmBulletin({{ \Illuminate\Support\Js::from($pysmCfg) }})">

    <x-m.appbar title="Bulletin de paie"
                :sub="$pysmNom . ' · ' . $pysmPeriode"
                :back="route('esbtp.comptabilite.salaires.index')">
        <a href="{{ route('esbtp.comptabilite.salaires.payslip', $salaire->id) }}" class="m-ib" aria-label="Télécharger le bulletin PDF">
            <x-m.icon name="dl" />
        </a>
    </x-m.appbar>

    <div class="m-body" data-m-ptr="reload">

        <div class="pysm-note bad" role="alert" x-show="erreur" x-cloak>
            <x-m.icon name="alert" />
            <span x-text="erreur"></span>
        </div>

        @if($salaire->isBrouillon() && ! $pysmPeutValider)
            <div class="pysm-note" role="status" x-show="statut === 'brouillon'">
                <x-m.icon name="clock" />
                <span>Ce bulletin attend sa validation par une autre personne habilitée.</span>
            </div>
        @endif
        <div class="pysm-note ok" role="status" x-show="statut === 'paye'" x-cloak>
            <x-m.icon name="lock" />
            <span>Bulletin payé<template x-if="datePaiement"><span x-text="' le ' + datePaiement"></span></template> — verrouillé.</span>
        </div>

        {{-- Le bulletin compact --}}
        <div class="m-recu">
            <div class="hd">
                <b>{{ $pysmEcole['name'] ?? config('app.name') }}</b>
                <span>Bulletin de paie · {{ $pysmPeriode }}</span>
            </div>
            <dl>
                <dt>Enseignant</dt>
                <dd>{{ $pysmNom }}</dd>
                @if($pysmRegime)
                    <dt>Régime</dt>
                    <dd>{{ $pysmRegime }}</dd>
                @endif
                <dt>Période</dt>
                <dd>{{ $salaire->period_start?->format('d/m') ?? '—' }} → {{ $salaire->period_end?->format('d/m/Y') ?? '—' }}</dd>
                <dt>Heures réalisées</dt>
                <dd>{{ $pysmFmtH($salaire->heures_total) }}</dd>
                <dt>Statut</dt>
                <dd><span class="m-chip" x-bind:class="ton()" x-text="libelle()">{{ $pysmLabels[$salaire->workflow_status] ?? $salaire->statutLabel() }}</span></dd>
            </dl>

            <div class="pysm-sec">Gains</div>
            <div class="pysm-lines">
                @forelse($gains as $g)
                    <div class="pysm-line">
                        <span class="lb">
                            {{ $g->libelle }}
                            @if($g->heures)
                                <small>{{ $pysmFmtH($g->heures) }} × {{ $pysmFmt($g->taux) }} FCFA</small>
                            @endif
                        </span>
                        <span class="am">{{ $pysmFmt($g->montant) }} FCFA</span>
                    </div>
                @empty
                    <div class="pysm-line"><span class="lb muted">Aucune ligne de gain</span></div>
                @endforelse
                <div class="pysm-line total">
                    <span class="lb">Brut</span>
                    <span class="am">{{ $pysmFmt($pysmBrut) }} FCFA</span>
                </div>
            </div>

            <div class="pysm-sec">Retenues</div>
            <div class="pysm-lines">
                @forelse($retenues as $r)
                    <div class="pysm-line">
                        <span class="lb">{{ $r->libelle }}</span>
                        <span class="am neg">− {{ $pysmFmt($r->montant) }} FCFA</span>
                    </div>
                @empty
                    <div class="pysm-line"><span class="lb muted">Aucune retenue</span></div>
                @endforelse
                <div class="pysm-line total">
                    <span class="lb">Total retenues</span>
                    <span class="am neg">− {{ $pysmFmt($salaire->retenues) }} FCFA</span>
                </div>
            </div>

            <div class="tot">
                <span>Net à payer</span>
                <span>{{ $pysmFmt($salaire->net_a_payer) }} FCFA</span>
            </div>
        </div>

        {{-- Suivi du circuit --}}
        <div class="m-list one">
            <x-m.row icon="pen" title="Préparé par"
                     :sub="($salaire->preparePar?->name ?? '—') . ($salaire->prepared_at ? ' · ' . $salaire->prepared_at->format('d/m/Y H:i') : '')" />
            <div class="m-row">
                <div class="av ic" aria-hidden="true"><x-m.icon name="check" /></div>
                <div class="tt">
                    <b>Validé par</b>
                    <span x-text="validePar ? validePar + (dateValidation ? ' · ' + dateValidation : '') : '—'">{{ $salaire->validePar?->name ?? '—' }}</span>
                </div>
            </div>
            <div class="m-row">
                <div class="av ic" aria-hidden="true"><x-m.icon name="hand" /></div>
                <div class="tt">
                    <b>Payé par</b>
                    <span x-text="payePar ? payePar + (modeLabel ? ' · ' + modeLabel : '') + (datePaiement ? ' · ' + datePaiement : '') : '—'">{{ $salaire->payePar?->name ?? '—' }}</span>
                    <span x-show="reference" x-text="'Référence : ' + reference" x-cloak></span>
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="m-list one">
            <x-m.row :href="route('esbtp.comptabilite.salaires.payslip.preview', $salaire->id)"
                     target="_blank" rel="noopener" icon="file"
                     title="Aperçu du bulletin PDF" sub="S'ouvre dans un nouvel onglet" />
            <x-m.row :href="route('esbtp.comptabilite.salaires.payslip', $salaire->id)"
                     icon="dl" title="Télécharger le PDF" :sub="'bulletin_paie · ' . $pysmPeriode" />
        </div>
    </div>

    @if(($pysmPeutValider && $salaire->isBrouillon()) || ($pysmPeutPayer && ! $salaire->isLocked()))
        <x-m.actionbar x-show="(statut === 'brouillon' && peutValider) || (statut === 'valide' && peutPayer)">
            @if($pysmPeutValider && $salaire->isBrouillon())
                <button type="button" class="m-btn p" x-show="statut === 'brouillon'" x-on:click="ouvrir('pysm-valider')">
                    <x-m.icon name="check" />Valider le bulletin
                </button>
            @endif
            @if($pysmPeutPayer && ! $salaire->isLocked())
                <button type="button" class="m-btn p" x-show="statut === 'valide'" x-cloak x-on:click="ouvrir('pysm-payer')">
                    <x-m.icon name="hand" />Marquer comme payé
                </button>
            @endif
        </x-m.actionbar>
    @endif

    {{-- ============ Feuille « Valider » ============ --}}
    @if($pysmPeutValider && $salaire->isBrouillon())
    <x-m.sheet id="pysm-valider" title="Valider ce bulletin ?" :sub="$pysmNom . ' · ' . $pysmPeriode">
        <div class="pysm-form">
            <dl class="pysm-kv">
                <dt>Brut</dt><dd>{{ $pysmFmt($pysmBrut) }} FCFA</dd>
                <dt>Retenues</dt><dd>− {{ $pysmFmt($salaire->retenues) }} FCFA</dd>
                <dt>Net à payer</dt><dd class="strong">{{ $pysmFmt($salaire->net_a_payer) }} FCFA</dd>
            </dl>
            <p class="pysm-hint">Une fois validé, le bulletin peut être marqué payé ; ses montants ne changent plus, sauf nouvelle préparation.</p>
            <button type="button" class="m-btn p" x-on:click="valider()" x-bind:disabled="occupe">
                <span x-show="!occupe">Oui, valider</span>
                <span x-show="occupe" x-cloak>Validation…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </div>
    </x-m.sheet>
    @endif

    {{-- ============ Feuille « Marquer payé » ============ --}}
    @if($pysmPeutPayer && ! $salaire->isLocked())
    <x-m.sheet id="pysm-payer" title="Enregistrer le règlement" :sub="$pysmNom . ' · ' . $pysmFmt($salaire->net_a_payer) . ' FCFA'">
        <form x-on:submit.prevent="payer()" class="pysm-form">
            <div class="m-field">
                <label>Mode de paiement</label>
                <div class="m-opt">
                    @foreach($modesPaiement as $pysmCode => $pysmLibelle)
                        <label x-bind:class="formPaie.mode === @js((string) $pysmCode) ? 'on' : ''">
                            <span class="rd" aria-hidden="true"></span>
                            <span><b>{{ $pysmLibelle }}</b></span>
                            <input type="radio" name="pysm_mode" value="{{ $pysmCode }}" x-model="formPaie.mode" class="pysm-radio">
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="m-field">
                <label for="pysm-ref">Référence (facultatif)</label>
                <input id="pysm-ref" type="text" class="m-in" maxlength="100" autocomplete="off"
                       placeholder="N° de transaction, chèque…" x-model="formPaie.reference">
            </div>
            <div class="m-field">
                <label for="pysm-date">Date de paiement</label>
                <input id="pysm-date" type="date" class="m-in" x-model="formPaie.date">
            </div>
            <button type="submit" class="m-btn p" x-bind:disabled="occupe || !formPaie.mode">
                <span x-show="!occupe">Confirmer le paiement</span>
                <span x-show="occupe" x-cloak>Enregistrement…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </form>
    </x-m.sheet>
    @endif
</div>

@push('styles')
<style>
    /* Bulletin de paie mobile — namespace pysm- */
    .pysm-note { display: grid; grid-template-columns: auto 1fr; gap: 10px; align-items: center; background: #fff3df; color: #8a5200; border: 1px solid #f6dfb3; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
    .pysm-note svg { width: 20px; height: 20px; }
    .pysm-note.bad { background: #fdecea; color: #a12016; border-color: #f5c6c0; }
    .pysm-note.ok { background: #e6f6ef; color: #0f6b4c; border-color: #bfe8d4; }
    .pysm-screen .m-recu .pysm-sec { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: #64748b; font-weight: 700; border-top: 1px solid #eef2f7; padding-top: 10px; margin-top: 2px; }
    .pysm-lines { display: grid; gap: 6px; }
    .pysm-line { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 13.5px; color: #0f172a; }
    .pysm-line .lb { display: grid; min-width: 0; }
    .pysm-line .lb small { font-size: 11.5px; color: #64748b; }
    .pysm-line .lb.muted { color: #94a3b8; }
    .pysm-line .am { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .pysm-line .am.neg { color: #a12016; }
    .pysm-line.total { border-top: 1px solid #eef2f7; padding-top: 6px; font-weight: 800; }
    .pysm-screen .m-recu .tot span:last-child { color: #0453cb; font-variant-numeric: tabular-nums; }
    .pysm-form { display: grid; gap: 12px; }
    .pysm-hint { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.45; font-family: var(--m-font); }
    .pysm-kv { margin: 0; display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; font-size: 13.5px; font-family: var(--m-font); }
    .pysm-kv dt { color: #64748b; }
    .pysm-kv dd { margin: 0; font-weight: 600; text-align: right; color: #0f172a; font-variant-numeric: tabular-nums; }
    .pysm-kv dd.strong { font-weight: 800; color: #0453cb; font-size: 15px; }
    .pysm-radio { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
    .pysm-screen .m-opt label { position: relative; }
    .pysm-screen .m-opt label.on .rd { border: 7px solid #0453cb; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.pysmBulletin !== 'function') {
        window.pysmBulletin = function (cfg) {
            return {
                statut: cfg.statut,
                labels: cfg.labels || {},
                tons: cfg.tons || {},
                urls: cfg.urls || {},
                peutValider: !!(cfg.urls && cfg.urls.valider),
                peutPayer: !!(cfg.urls && cfg.urls.payer),
                validePar: cfg.validePar || null,
                dateValidation: cfg.dateValidation || null,
                payePar: cfg.payePar || null,
                modeLabel: cfg.modeLabel || null,
                reference: cfg.reference || null,
                datePaiement: cfg.datePaiement || null,
                erreur: cfg.erreur || null,
                occupe: false,
                formPaie: { mode: '', reference: '', date: cfg.aujourdhui || '' },

                init() {
                    if (cfg.flash) { this.toast(cfg.flash, 'success'); }
                },

                ouvrir(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
                },
                fermerTout() {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                },
                toast(message, type) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message: message } }));
                },
                libelle() {
                    return this.labels[this.statut] || this.statut || '—';
                },
                ton() {
                    return this.tons[this.statut] || 'mute';
                },

                async appeler(url, body) {
                    var res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body || {}),
                        credentials: 'same-origin',
                    });
                    var data = await res.json().catch(function () { return {}; });
                    if (!res.ok || data.success === false) {
                        var msg = data.message;
                        if (!msg && data.errors) { msg = Object.values(data.errors).flat().join(' '); }
                        throw new Error(msg || ('Erreur ' + res.status));
                    }
                    return data;
                },
                async executer(action) {
                    if (this.occupe) { return; }
                    this.occupe = true;
                    this.erreur = null;
                    try {
                        await action();
                    } catch (err) {
                        this.toast(err.message || 'Une erreur est survenue.', 'error');
                    } finally {
                        this.occupe = false;
                    }
                },

                valider() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.valider, {});
                        self.statut = data.statut || 'valide';
                        self.validePar = data.valide_par || self.validePar;
                        self.dateValidation = data.date_validation || self.dateValidation;
                        self.fermerTout();
                        self.toast(data.message || 'Bulletin validé.', 'success');
                    });
                },
                payer() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.payer, {
                            mode_paiement: self.formPaie.mode,
                            reference_paiement: self.formPaie.reference.trim() || null,
                            date_paiement: self.formPaie.date || null,
                        });
                        self.statut = data.statut || 'paye';
                        self.payePar = data.paye_par || self.payePar;
                        self.modeLabel = data.mode_label || self.modeLabel;
                        self.reference = data.reference || null;
                        self.datePaiement = data.date_paiement || self.datePaiement;
                        self.fermerTout();
                        self.toast(data.message || 'Bulletin marqué comme payé.', 'success');
                    });
                },
            };
        };
    }
</script>
@endpush
