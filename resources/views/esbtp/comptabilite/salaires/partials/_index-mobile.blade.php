{{--
    Paie des enseignants — rendu MOBILE (shell m-*), maquette S['comptable:paie'].
    Inclus par esbtp/comptabilite/salaires/index.blade.php quand le shell mobile
    est actif ; le DOM de bureau reste dans .m-only-desktop.

    L'écran travaille toujours sur UN mois (le bulletin est mensuel : l'impôt
    progressif se calcule par mois). Le mois se change dans une feuille, la
    liste se recharge via /salaires/data?mode=mobile (cartes-lignes rendues
    côté serveur par _recap-mobile), les segments filtrent par statut.
    « Préparer » calcule et enregistre un bulletin depuis une feuille.

    Namespace CSS propre à l'écran : pym- (paie-index-mobile).
--}}
@php
    $pymPeutCreer = (bool) ($canCreate ?? false);
    $pymPeutExporter = (bool) ($canExport ?? false);
    $pymMois = max(1, min(12, (int) $filtres['mois']));
    $pymAnnee = (int) $filtres['annee'];
    $pymPeriode = ($moisOptions[$pymMois] ?? '') . ' ' . $pymAnnee;
    $pymFmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $pymFmtH = function ($v) {
        $h = (int) floor((float) $v);
        $m = (int) round(((float) $v - $h) * 60);
        return $h . ' h' . ($m > 0 ? sprintf(' %02d', $m) : '');
    };
    // La vue de bureau a pu être ouverte sur un trimestre ou une année : le
    // mobile redemande alors le mois seul au premier affichage.
    $pymRechargerAuDepart = ($filtres['preset'] ?? 'month') !== 'month';

    $pymCfg = [
        'devise' => 'FCFA',
        'mois' => $pymMois,
        'annee' => $pymAnnee,
        'moisLabels' => $moisOptions,
        'anneeMin' => now()->year - 3,
        'anneeMax' => now()->year,
        'maintenant' => ['mois' => now()->month, 'annee' => now()->year],
        'statut' => (string) ($filtres['statut'] ?? ''),
        'q' => (string) ($filtres['q'] ?? ''),
        'kpis' => $kpis,
        'nb' => count($recap),
        'rechargerAuDepart' => $pymRechargerAuDepart,
        'peutCreer' => $pymPeutCreer,
        'teachers' => $pymPeutCreer ? collect($teachers)->values()->all() : [],
        'flash' => session('success'),
        'urls' => [
            'data' => route('esbtp.comptabilite.salaires.data'),
            'prepare' => $pymPeutCreer ? route('esbtp.comptabilite.salaires.prepare') : null,
            'store' => $pymPeutCreer ? route('esbtp.comptabilite.salaires.store') : null,
        ],
        'exports' => $pymPeutExporter ? [
            'apercu' => ['url' => route('esbtp.comptabilite.salaires.export.preview-pdf'), 'onglet' => true],
            'pdf' => ['url' => route('esbtp.comptabilite.salaires.export.pdf'), 'onglet' => false],
            'excel' => ['url' => route('esbtp.comptabilite.salaires.export.excel'), 'onglet' => false],
        ] : [],
    ];
@endphp

<div class="m-only-mobile m-screen pym-screen" x-data="pymPaie({{ \Illuminate\Support\Js::from($pymCfg) }})">

    <x-m.appbar title="Paie enseignants"
                :sub="$pymPeriode"
                :action="$pymPeutExporter ? 'dl' : null"
                action-label="Exporter l'état de paie"
                x-on:click="ouvrir('pym-export')">
        <button type="button" class="m-ib ghost" aria-label="Changer de mois" x-on:click="ouvrir('pym-mois')">
            <x-m.icon name="cal" />
        </button>
    </x-m.appbar>

    <div class="m-body" x-ref="corps">

        {{-- Héro : l'état de paie du mois (totaux hors filtre) --}}
        <section class="m-hero" role="button" tabindex="0" aria-label="Changer de mois"
                 x-on:click="ouvrir('pym-mois')" x-on:keydown.enter.prevent="ouvrir('pym-mois')">
            <span class="k" x-text="'État de paie · ' + periodeLabel()">État de paie · {{ $pymPeriode }}</span>
            <span class="v"><span x-text="format(hero.net)">{{ $pymFmt($kpis['total_net']) }}</span><small>FCFA net</small></span>
            <div class="row">
                <span class="pill" x-text="pluriel(hero.nb, 'enseignant')">{{ $kpis['nb_total'] }} {{ $kpis['nb_total'] > 1 ? 'enseignants' : 'enseignant' }}</span>
                <span class="pill" x-text="formatH(hero.heures)">{{ $pymFmtH($kpis['heures_total'] ?? 0) }}</span>
                <span class="pill" x-show="hero.aPreparer > 0" x-text="hero.aPreparer + ' à préparer'" x-cloak></span>
            </div>
        </section>

        <div class="m-sticky pym-sticky">
            <label class="m-search">
                <x-m.icon name="search" />
                <input type="search" inputmode="search" autocomplete="off"
                       placeholder="Nom de l'enseignant…"
                       aria-label="Rechercher un enseignant"
                       x-model="q"
                       x-on:input.debounce.350ms="recharger()">
            </label>
            <div class="m-seg pym-seg" role="tablist" aria-label="Filtrer par statut">
                <button type="button" role="tab" x-bind:aria-selected="statut === '' ? 'true' : 'false'"
                        x-bind:class="statut === '' ? 'on' : ''" x-on:click="segment('')"
                        x-text="libelleSegment('Tous', compteurs.tous)">Tous</button>
                <button type="button" role="tab" x-bind:aria-selected="statut === 'a_preparer' ? 'true' : 'false'"
                        x-bind:class="statut === 'a_preparer' ? 'on' : ''" x-on:click="segment('a_preparer')"
                        x-text="libelleSegment('À préparer', compteurs.a_preparer)">À préparer</button>
                <button type="button" role="tab" x-bind:aria-selected="statut === 'brouillon' ? 'true' : 'false'"
                        x-bind:class="statut === 'brouillon' ? 'on' : ''" x-on:click="segment('brouillon')"
                        x-text="libelleSegment('À valider', compteurs.brouillon)">À valider</button>
                <button type="button" role="tab" x-bind:aria-selected="statut === 'valide' ? 'true' : 'false'"
                        x-bind:class="statut === 'valide' ? 'on' : ''" x-on:click="segment('valide')"
                        x-text="libelleSegment('Validés', compteurs.valide)">Validés</button>
                <button type="button" role="tab" x-bind:aria-selected="statut === 'paye' ? 'true' : 'false'"
                        x-bind:class="statut === 'paye' ? 'on' : ''" x-on:click="segment('paye')"
                        x-text="libelleSegment('Payés', compteurs.paye)">Payés</button>
            </div>
        </div>

        <div class="pym-err" role="alert" x-show="erreur" x-cloak>
            <x-m.icon name="alert" />
            <span x-text="erreur"></span>
            <button type="button" class="pym-err-retry" x-on:click="recharger()">Réessayer</button>
        </div>

        {{-- Squelette pendant un rechargement --}}
        <div class="m-skel" x-show="chargement" x-cloak aria-hidden="true">
            <i></i><i></i><i></i><i></i>
        </div>

        {{-- Cartes-lignes rendues côté serveur (même DOM que x-m.row) --}}
        <div class="m-list" x-ref="liste" x-show="!chargement" aria-live="polite">
            @include('esbtp.comptabilite.salaires.partials._recap-mobile', ['recap' => $recap, 'canCreate' => $pymPeutCreer])
        </div>

        <p class="pym-fin" x-show="!chargement && nb > 0" x-cloak x-text="pluriel(nb, 'enseignant') + ' · ' + periodeLabel()"></p>
    </div>

    @if($pymPeutCreer)
        <x-m.actionbar>
            <button type="button" class="m-btn p" x-on:click="ouvrirPreparer()">
                <x-m.icon name="plus" />Préparer un bulletin
            </button>
        </x-m.actionbar>
    @endif

    {{-- ============ Feuille « Mois » ============ --}}
    <x-m.sheet id="pym-mois" title="Mois de paie" sub="Un bulletin par enseignant et par mois.">
        <div class="pym-annee">
            <button type="button" class="m-ib ghost" aria-label="Année précédente" x-on:click="anneePrecedente()" x-bind:disabled="annee <= anneeMin">
                <x-m.icon name="chl" />
            </button>
            <b x-text="annee">{{ $pymAnnee }}</b>
            <button type="button" class="m-ib ghost" aria-label="Année suivante" x-on:click="anneeSuivante()" x-bind:disabled="annee >= anneeMax">
                <x-m.icon name="chr" />
            </button>
        </div>
        <div class="pym-mois-grid">
            <template x-for="m in 12" x-bind:key="m">
                <button type="button"
                        x-bind:class="m === mois ? 'on' : ''"
                        x-bind:disabled="moisFutur(m)"
                        x-bind:aria-pressed="m === mois ? 'true' : 'false'"
                        x-on:click="changerMois(m)"
                        x-text="moisLabels[m]"></button>
            </template>
        </div>
        <p class="pym-hint">Les mois à venir n'ont pas encore d'heures réalisées.</p>
    </x-m.sheet>

    {{-- ============ Feuille « Exporter » ============ --}}
    @if($pymPeutExporter)
    <x-m.sheet id="pym-export" title="Exporter l'état de paie" sub="Le mois, le statut et la recherche affichés sont repris.">
        <div class="m-menu">
            <button type="button" x-on:click="exporter('apercu')">
                <x-m.icon name="file" />Aperçu PDF<span class="ch"><x-m.icon name="chr" /></span>
            </button>
            <button type="button" x-on:click="exporter('pdf')">
                <x-m.icon name="dl" />Télécharger le PDF<span class="ch"><x-m.icon name="chr" /></span>
            </button>
            <button type="button" x-on:click="exporter('excel')">
                <x-m.icon name="chart" />Télécharger l'Excel<span class="ch"><x-m.icon name="chr" /></span>
            </button>
        </div>
    </x-m.sheet>
    @endif

    {{-- ============ Feuille « Préparer un bulletin » ============ --}}
    @if($pymPeutCreer)
    <x-m.sheet id="pym-preparer" title="Préparer un bulletin" sub="Heures réalisées × taux par type, retenues calculées d'après les paramètres de paie.">
        <div class="pym-form">

            <div class="m-field">
                <label>Mois</label>
                <button type="button" class="m-in pym-in-btn" x-on:click="ouvrir('pym-mois')" aria-label="Changer de mois">
                    <span x-text="periodeLabel()">{{ $pymPeriode }}</span>
                    <x-m.icon name="cal" />
                </button>
            </div>

            <div class="m-field">
                <label>Enseignant</label>
                <template x-if="prep.teacher_id">
                    <div class="pym-choisi">
                        <div class="av" aria-hidden="true" x-text="initiales(prep.teacherName)"></div>
                        <b x-text="prep.teacherName"></b>
                        <button type="button" class="pym-link" x-on:click="retirerEnseignant()">Changer</button>
                    </div>
                </template>
                <template x-if="!prep.teacher_id">
                    <div class="pym-form">
                        <label class="m-search">
                            <x-m.icon name="search" />
                            <input type="search" inputmode="search" autocomplete="off" placeholder="Rechercher un enseignant…"
                                   aria-label="Rechercher un enseignant" x-model="prep.q">
                        </label>
                        <div class="m-opt pym-opt" role="listbox" aria-label="Enseignants">
                            <template x-for="t in enseignantsFiltres()" x-bind:key="t.id">
                                <label role="option" x-on:click="choisirEnseignant(t)">
                                    <span class="rd" aria-hidden="true"></span>
                                    <span><b x-text="t.name"></b></span>
                                </label>
                            </template>
                        </div>
                        <p class="pym-hint" x-show="enseignantsFiltres().length === 0" x-cloak>Aucun enseignant ne correspond à cette recherche.</p>
                        <p class="pym-hint" x-show="enseignantsTronques()" x-cloak>Affinez la recherche pour voir les autres enseignants.</p>
                    </div>
                </template>
            </div>

            {{-- Aperçu du calcul --}}
            <div class="pym-calc" x-show="calcul" x-cloak aria-live="polite">
                <x-m.icon name="clock" /><span>Calcul en cours…</span>
            </div>

            <template x-if="preview">
                <div class="pym-recap">
                    <div class="pym-sec">Gains</div>
                    <template x-for="(g, i) in preview.gains" x-bind:key="'g' + i">
                        <div class="pym-line">
                            <span class="lb">
                                <span x-text="g.libelle"></span>
                                <small x-show="g.heures" x-text="formatH(g.heures) + ' × ' + format(g.taux) + ' ' + devise"></small>
                            </span>
                            <span class="am" x-text="format(g.montant) + ' ' + devise"></span>
                        </div>
                    </template>
                    <div class="pym-line total">
                        <span class="lb">Brut</span>
                        <span class="am" x-text="format(preview.brut) + ' ' + devise"></span>
                    </div>

                    <div class="pym-sec">Retenues</div>
                    <template x-for="(r, i) in preview.retenues" x-bind:key="'r' + i">
                        <div class="pym-line">
                            <span class="lb" x-text="r.libelle"></span>
                            <span class="am neg" x-text="'− ' + format(r.montant) + ' ' + devise"></span>
                        </div>
                    </template>
                    <p class="pym-hint" x-show="preview.retenues.length === 0" x-cloak>Aucune retenue.</p>

                    <div class="pym-grid2">
                        <div class="m-field">
                            <label for="pym-its">Impôt ITS</label>
                            <input id="pym-its" type="number" inputmode="numeric" min="0" step="100" class="m-in"
                                   x-model="prep.impot_its" x-bind:placeholder="format(preview.impot_its)"
                                   x-on:change="calculer()">
                        </div>
                        <div class="m-field">
                            <label for="pym-cnps">CNPS</label>
                            <input id="pym-cnps" type="number" inputmode="numeric" min="0" step="100" class="m-in"
                                   x-model="prep.cnps" x-bind:placeholder="format(preview.cnps)"
                                   x-on:change="calculer()">
                        </div>
                    </div>
                    <p class="pym-hint">Laissez vide pour garder le calcul automatique (barème ITS et taux CNPS configurés). Primes et autres retenues se saisissent depuis l'ordinateur.</p>

                    <div class="pym-net">
                        <span>Net à payer</span>
                        <b x-text="format(preview.net) + ' ' + devise"></b>
                    </div>

                    <div class="pym-note" x-show="existe && !verrouille" x-cloak>
                        <x-m.icon name="alert" />
                        <span>Un bulletin existe déjà pour ce mois : il sera mis à jour et repassera en brouillon.</span>
                    </div>
                    <div class="pym-note bad" x-show="verrouille" x-cloak>
                        <x-m.icon name="lock" />
                        <span>Ce bulletin est payé ou annulé : il ne peut plus être modifié.</span>
                    </div>
                </div>
            </template>

            <button type="button" class="m-btn p" x-on:click="enregistrer()" x-bind:disabled="!preview || enregistrement || verrouille || calcul">
                <span x-show="!enregistrement">Enregistrer le bulletin</span>
                <span x-show="enregistrement" x-cloak>Enregistrement…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </div>
    </x-m.sheet>
    @endif
</div>

@push('styles')
<style>
    /* Paie enseignants mobile — namespace pym- */
    .pym-screen .m-hero { cursor: pointer; }
    .pym-sticky { display: grid; gap: 8px; }
    .pym-screen .pym-seg { grid-auto-columns: max-content; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .pym-screen .pym-seg::-webkit-scrollbar { display: none; }
    .pym-screen .pym-seg button { padding: 8px 12px; white-space: nowrap; min-width: 72px; }
    .pym-fin { margin: 0; text-align: center; font-size: 12px; color: #94a3b8; font-family: var(--m-font); }
    .pym-err { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; background: #fdecea; color: #a12016; border: 1px solid #f5c6c0; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
    .pym-err svg { width: 20px; height: 20px; }
    .pym-err-retry { border: 0; background: #fff; color: #a12016; font: inherit; font-weight: 700; padding: 8px 12px; border-radius: 10px; min-height: 40px; cursor: pointer; }
    .pym-form { display: grid; gap: 12px; }
    .pym-hint { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.45; font-family: var(--m-font); }
    .pym-link { background: none; border: 0; padding: 0 4px; font-size: 12.5px; font-weight: 700; color: #0453cb; cursor: pointer; font-family: inherit; min-height: 44px; }
    .pym-annee { display: grid; grid-template-columns: 44px 1fr 44px; align-items: center; }
    .pym-annee b { text-align: center; font-size: 17px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
    .pym-annee .m-ib:disabled { opacity: .35; cursor: default; }
    .pym-mois-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .pym-mois-grid button { min-height: 44px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #fff; font: inherit; font-size: 14px; font-weight: 600; color: #0f172a; cursor: pointer; -webkit-tap-highlight-color: transparent; }
    .pym-mois-grid button.on { border-color: #0453cb; background: rgba(4,83,203,.08); color: #0453cb; }
    .pym-mois-grid button:disabled { opacity: .35; cursor: default; }
    .pym-in-btn { justify-content: space-between; cursor: pointer; text-align: left; }
    .pym-in-btn svg { width: 20px; height: 20px; color: #0453cb; }
    .pym-choisi { display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: center; background: #fff; border: 1.5px solid #0453cb; border-radius: 14px; padding: 6px 12px; min-height: 56px; }
    .pym-choisi .av { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 14px; }
    .pym-choisi b { font-size: 14.5px; color: #0f172a; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pym-opt { max-height: 40vh; overflow-y: auto; }
    .pym-opt label { min-height: 48px; padding: 8px 12px; }
    .pym-calc { display: flex; align-items: center; gap: 8px; color: #0453cb; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
    .pym-calc svg { width: 18px; height: 18px; }
    .pym-recap { display: grid; gap: 8px; background: #fff; border: 1px solid #e6eaf2; border-radius: 16px; padding: 14px; font-family: var(--m-font); }
    .pym-sec { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: #64748b; font-weight: 700; margin-top: 4px; }
    .pym-line { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 13.5px; color: #0f172a; }
    .pym-line .lb { display: grid; min-width: 0; }
    .pym-line .lb small { font-size: 11.5px; color: #64748b; }
    .pym-line .am { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .pym-line .am.neg { color: #a12016; }
    .pym-line.total { border-top: 1px solid #eef2f7; padding-top: 8px; font-weight: 800; }
    .pym-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .pym-net { display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 16px; font-weight: 800; color: #0453cb; }
    .pym-net b { font-variant-numeric: tabular-nums; }
    .pym-note { display: grid; grid-template-columns: auto 1fr; gap: 10px; align-items: center; background: #fff3df; color: #8a5200; border: 1px solid #f6dfb3; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
    .pym-note svg { width: 20px; height: 20px; }
    .pym-note.bad { background: #fdecea; color: #a12016; border-color: #f5c6c0; }
    .pym-screen .m-hero .pill:empty { display: none; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.pymPaie !== 'function') {
        window.pymPaie = function (cfg) {
            var k = cfg.kpis || {};
            var heroDepuis = function (kpis) {
                return {
                    net: Number(kpis.total_net || 0),
                    nb: Number(kpis.nb_total || 0),
                    heures: Number(kpis.heures_total || 0),
                    aPreparer: Number(kpis.nb_a_preparer || 0),
                };
            };
            var compteursDepuis = function (kpis) {
                return {
                    tous: Number(kpis.nb_total || 0),
                    a_preparer: Number(kpis.nb_a_preparer || 0),
                    brouillon: Number(kpis.nb_brouillon || 0),
                    valide: Number(kpis.nb_valide || 0),
                    paye: Number(kpis.nb_paye || 0),
                };
            };
            var sansFiltre = (cfg.statut || '') === '' && (cfg.q || '') === '';

            return {
                devise: cfg.devise || 'FCFA',
                urls: cfg.urls || {},
                exports: cfg.exports || {},
                moisLabels: cfg.moisLabels || {},
                anneeMin: Number(cfg.anneeMin),
                anneeMax: Number(cfg.anneeMax),
                maintenant: cfg.maintenant || {},
                mois: Number(cfg.mois),
                annee: Number(cfg.annee),
                statut: cfg.statut || '',
                q: cfg.q || '',
                nb: Number(cfg.nb || 0),
                hero: heroDepuis(k),
                // Les compteurs des segments ne sont fiables que sans filtre :
                // null = inconnu, le segment reste sans chiffre.
                compteurs: sansFiltre ? compteursDepuis(k) : { tous: null, a_preparer: null, brouillon: null, valide: null, paye: null },
                chargement: false,
                erreur: null,
                _seq: 0,
                _onPtr: null,
                _onPreparer: null,

                // Préparation d'un bulletin
                teachers: cfg.teachers || [],
                prep: { teacher_id: '', teacherName: '', q: '', impot_its: '', cnps: '' },
                preview: null,
                existe: false,
                verrouille: false,
                calcul: false,
                enregistrement: false,

                init() {
                    var self = this;
                    if (cfg.flash) { this.toast(cfg.flash, 'success'); }
                    if (this.$refs.corps) {
                        this._onPtr = function () { self.recharger(); };
                        this.$refs.corps.addEventListener('m-ptr:refresh', this._onPtr);
                    }
                    if (cfg.peutCreer) {
                        this._onPreparer = function (ev) { self.preparerPour(ev.detail || {}); };
                        window.addEventListener('paie:prepare-mobile', this._onPreparer);
                    }
                    if (cfg.rechargerAuDepart) { this.recharger(); }
                },
                destroy() {
                    if (this._onPtr && this.$refs.corps) { this.$refs.corps.removeEventListener('m-ptr:refresh', this._onPtr); this._onPtr = null; }
                    if (this._onPreparer) { window.removeEventListener('paie:prepare-mobile', this._onPreparer); this._onPreparer = null; }
                },

                /* ---- feuilles, toasts ---- */
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

                /* ---- mise en forme ---- */
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
                },
                formatH(v) {
                    var h = Math.floor(Number(v) || 0);
                    var m = Math.round(((Number(v) || 0) - h) * 60);
                    return h + ' h' + (m > 0 ? ' ' + String(m).padStart(2, '0') : '');
                },
                pluriel(n, mot) {
                    n = Number(n) || 0;
                    return this.format(n) + ' ' + mot + (n > 1 ? 's' : '');
                },
                periodeLabel() {
                    return (this.moisLabels[this.mois] || '') + ' ' + this.annee;
                },
                libelleSegment(texte, n) {
                    return n === null || n === undefined ? texte : texte + ' · ' + this.format(n);
                },
                initiales(nom) {
                    var lettres = '';
                    String(nom || '').split(/[\s\-]+/).forEach(function (mot) {
                        if (!mot || /^(dr|pr|m|mr|mme|mlle)\.?$/i.test(mot) || lettres.length >= 2) { return; }
                        lettres += mot.charAt(0);
                    });
                    return (lettres || String(nom || '').slice(0, 2)).toUpperCase();
                },
                majSousTitre() {
                    var small = this.$root.querySelector('.m-appbar .t small');
                    if (small) { small.textContent = this.periodeLabel(); }
                },

                /* ---- mois ---- */
                moisFutur(m) {
                    var now = this.maintenant;
                    return this.annee > Number(now.annee) || (this.annee === Number(now.annee) && m > Number(now.mois));
                },
                anneePrecedente() { if (this.annee > this.anneeMin) { this.annee -= 1; this.recharger(); } },
                anneeSuivante() {
                    if (this.annee < this.anneeMax) {
                        this.annee += 1;
                        if (this.moisFutur(this.mois)) { this.mois = Number(this.maintenant.mois) || this.mois; }
                        this.recharger();
                    }
                },
                changerMois(m) {
                    if (this.moisFutur(m)) { return; }
                    this.mois = Number(m);
                    this.fermerTout();
                    this.recharger();
                },
                segment(s) {
                    if (this.statut === s) { return; }
                    this.statut = s;
                    this.recharger();
                },
                parametres() {
                    var p = new URLSearchParams();
                    p.set('preset', 'month');
                    p.set('mois', String(this.mois));
                    p.set('annee', String(this.annee));
                    if (this.statut) { p.set('statut', this.statut); }
                    if (this.q.trim() !== '') { p.set('q', this.q.trim()); }
                    return p;
                },

                /* ---- chargement de la liste ---- */
                async recharger() {
                    var seq = ++this._seq;
                    this.chargement = true;
                    this.erreur = null;
                    try {
                        var p = this.parametres();
                        p.set('mode', 'mobile');
                        var res = await fetch(this.urls.data + '?' + p.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) { throw new Error('Impossible de charger la paie (' + res.status + ').'); }
                        var data = await res.json();
                        if (seq !== this._seq) { return; }
                        if (this.$refs.liste) { this.$refs.liste.innerHTML = data.mobile_html || ''; }
                        this.nb = Number(data.mobile_count || 0);
                        var kpis = data.kpis || {};
                        var sansFiltre = this.statut === '' && this.q.trim() === '';
                        if (sansFiltre) {
                            this.hero = heroDepuis(kpis);
                            this.compteurs = compteursDepuis(kpis);
                        } else if (this.q.trim() === '' && this.statut !== '') {
                            this.compteurs[this.statut] = this.nb;
                        }
                        this.majSousTitre();
                    } catch (e) {
                        if (seq !== this._seq) { return; }
                        this.erreur = e.message || 'Impossible de charger la paie.';
                    } finally {
                        if (seq === this._seq) { this.chargement = false; }
                    }
                },

                /* ---- exports : mêmes routes que le bureau, mêmes filtres ---- */
                exporter(format) {
                    var ex = this.exports[format];
                    if (!ex || !ex.url) { return; }
                    var url = ex.url + '?' + this.parametres().toString();
                    this.fermerTout();
                    if (ex.onglet) {
                        // Pas de 3e argument : avec des options, window.open ouvre
                        // une popup que les bloqueurs avalent en silence.
                        var w = window.open(url, '_blank');
                        if (!w) { window.location.href = url; }
                        return;
                    }
                    window.location.href = url;
                },

                /* ---- préparer un bulletin ---- */
                resetPrep() {
                    this.prep = { teacher_id: '', teacherName: '', q: '', impot_its: '', cnps: '' };
                    this.preview = null;
                    this.existe = false;
                    this.verrouille = false;
                },
                ouvrirPreparer() {
                    this.resetPrep();
                    this.ouvrir('pym-preparer');
                },
                preparerPour(detail) {
                    var id = String(detail.id || '');
                    var t = this.teachers.find(function (x) { return String(x.id) === id; });
                    if (!t) { return; }
                    this.resetPrep();
                    if (detail.mois && detail.annee) { this.mois = Number(detail.mois); this.annee = Number(detail.annee); }
                    this.prep.teacher_id = String(t.id);
                    this.prep.teacherName = t.name;
                    this.ouvrir('pym-preparer');
                    this.calculer();
                },
                enseignantsFiltres() {
                    var q = this.prep.q.trim().toLowerCase();
                    var liste = q === '' ? this.teachers : this.teachers.filter(function (t) { return String(t.name).toLowerCase().indexOf(q) !== -1; });
                    return liste.slice(0, 30);
                },
                enseignantsTronques() {
                    var q = this.prep.q.trim().toLowerCase();
                    var liste = q === '' ? this.teachers : this.teachers.filter(function (t) { return String(t.name).toLowerCase().indexOf(q) !== -1; });
                    return liste.length > 30;
                },
                choisirEnseignant(t) {
                    this.prep.teacher_id = String(t.id);
                    this.prep.teacherName = t.name;
                    this.preview = null;
                    this.calculer();
                },
                retirerEnseignant() {
                    this.prep.teacher_id = '';
                    this.prep.teacherName = '';
                    this.preview = null;
                    this.existe = false;
                    this.verrouille = false;
                },
                payload() {
                    return {
                        teacher_id: this.prep.teacher_id,
                        mois: this.mois,
                        annee: this.annee,
                        impot_its: this.prep.impot_its === '' ? null : this.prep.impot_its,
                        cnps: this.prep.cnps === '' ? null : this.prep.cnps,
                        primes: [],
                        retenues: [],
                    };
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
                        body: JSON.stringify(body),
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
                async calculer() {
                    if (!this.prep.teacher_id || !this.urls.prepare) { return; }
                    this.calcul = true;
                    try {
                        var data = await this.appeler(this.urls.prepare, this.payload());
                        this.preview = data.preview;
                        this.existe = !!data.exists;
                        this.verrouille = !!data.locked;
                    } catch (e) {
                        this.toast(e.message || 'Le calcul a échoué.', 'error');
                    } finally {
                        this.calcul = false;
                    }
                },
                async enregistrer() {
                    if (!this.preview || this.verrouille || this.enregistrement || !this.urls.store) { return; }
                    this.enregistrement = true;
                    try {
                        var data = await this.appeler(this.urls.store, this.payload());
                        this.fermerTout();
                        this.toast(data.message || 'Bulletin enregistré.', 'success');
                        // EXCEPTION ajax-no-reload-premium : un bulletin vient d'être créé, on ouvre sa fiche.
                        if (data.redirect) { setTimeout(function () { window.location.assign(data.redirect); }, 400); }
                    } catch (e) {
                        this.toast(e.message || 'L\'enregistrement a échoué.', 'error');
                    } finally {
                        this.enregistrement = false;
                    }
                },
            };
        };
    }
</script>
@endpush
