{{--
    Liste des paiements — rendu MOBILE (shell m-*), maquettes S['comptable:paiements']
    et S['caissier:encaissements']. Inclus par esbtp/paiements/index.blade.php quand
    le shell mobile est actif ; le DOM de bureau reste dans .m-only-desktop.

    Le perimetre (tout l'etablissement / ses propres encaissements) et les filtres
    sont appliques par le controleur via PaymentFilterService : l'ecran ne fait
    que demander des pages (mode=mobile) et les rendre en cartes-lignes.

    Namespace CSS propre a l'ecran : pim- (paiements-index-mobile).
--}}
@php
    $pimUser = auth()->user();
    $pimVoitTout = $pimUser?->can('paiements.view') ?? false;
    $pimMienSeul = ! $pimVoitTout && ($pimUser?->can('paiements.view_own') ?? false);
    $pimTitre = $pimMienSeul ? 'Mes encaissements' : 'Paiements';
    $pimPeutExporter = $pimUser?->can('paiements.export') ?? false;
    // L'etat financier suit le perimetre de l'utilisateur, comme sur bureau.
    $pimEtatFinancier = $pimPeutExporter && ($pimVoitTout || $pimMienSeul);
    $pimDevise = 'FCFA';

    $pimListe = $listeMobile ?? [
        'items' => [],
        'has_more' => false,
        'next_page' => 2,
        'summary' => ['total' => 0, 'page' => 1, 'per_page' => 15],
        'stats' => ['total' => 0, 'valides' => 0, 'en_attente' => 0, 'rejetes' => 0, 'montant_total' => 0, 'montant_valide' => 0],
        'url' => route('esbtp.paiements.index'),
    ];
    $pimTotal = (int) ($pimListe['summary']['total'] ?? 0);
    $pimMontant = (float) ($pimListe['stats']['montant_total'] ?? 0);
    $pimSousTitre = $pimTotal . ' · ' . number_format($pimMontant, 0, ',', ' ') . ' ' . $pimDevise;

    $pimFrais = collect($fraisCategories ?? [])
        ->map(fn ($nom, $id) => ['id' => (string) $id, 'nom' => (string) $nom])
        ->values()
        ->all();

    $pimCfg = [
        'devise' => $pimDevise,
        'filtres' => [
            'search' => (string) request('search', ''),
            'status' => (string) request('status', ''),
            'frais_category_id' => (string) request('frais_category_id', ''),
            'date_debut' => (string) request('date_debut', ''),
            'date_fin' => (string) request('date_fin', ''),
        ],
        'initial' => $pimListe,
        'flash' => session('success'),
        'urls' => [
            'liste' => route('esbtp.paiements.refresh'),
            'index' => route('esbtp.paiements.index'),
        ],
        'exports' => $pimPeutExporter ? [
            'pdf-preview' => ['url' => route('esbtp.paiements.export.pdf-preview'), 'onglet' => true],
            'etat-financier-preview' => ['url' => route('esbtp.paiements.export.etat-financier'), 'onglet' => true, 'inline' => true],
            'etat-financier' => ['url' => route('esbtp.paiements.export.etat-financier'), 'onglet' => false],
            'excel' => ['url' => route('esbtp.paiements.export.excel'), 'onglet' => false],
            'saari' => ['url' => route('esbtp.paiements.export.saari'), 'onglet' => false],
            'csv' => ['url' => route('esbtp.paiements.export.csv'), 'onglet' => false],
            'pdf' => ['url' => route('esbtp.paiements.export.pdf'), 'onglet' => false],
        ] : [],
    ];
@endphp

<div class="m-only-mobile m-screen pim-screen" x-data="pimListe({{ \Illuminate\Support\Js::from($pimCfg) }})">

    <x-m.appbar :title="$pimTitre"
                :sub="$pimSousTitre"
                :action="$pimPeutExporter ? 'dl' : null"
                action-label="Exporter"
                x-on:click="ouvrir('pim-export')" />

    <div class="m-body" x-ref="corps">

        <div class="m-sticky pim-sticky">
            <div class="pim-bar">
                <label class="m-search">
                    <x-m.icon name="search" />
                    <input type="search"
                           inputmode="search"
                           autocomplete="off"
                           placeholder="Étudiant, reçu, matricule…"
                           aria-label="Rechercher un paiement"
                           x-model="filtres.search"
                           x-on:input.debounce.350ms="recharger()">
                </label>
                <button type="button"
                        class="pim-filtre"
                        x-bind:class="nbFiltres() > 0 ? 'on' : ''"
                        x-on:click="ouvrirFiltres()"
                        aria-label="Filtres">
                    <x-m.icon name="menu" />
                    <span class="pim-badge" x-show="nbFiltres() > 0" x-text="nbFiltres()" x-cloak></span>
                </button>
            </div>
            <div class="m-seg pim-seg" role="tablist" aria-label="Filtrer par statut">
                <button type="button" role="tab"
                        x-bind:aria-selected="filtres.status === '' ? 'true' : 'false'"
                        x-bind:class="filtres.status === '' ? 'on' : ''"
                        x-on:click="segment('')"
                        x-text="libelleSegment('Tous', compteurs.tous)">Tous</button>
                <button type="button" role="tab"
                        x-bind:aria-selected="filtres.status === 'en_attente' ? 'true' : 'false'"
                        x-bind:class="filtres.status === 'en_attente' ? 'on' : ''"
                        x-on:click="segment('en_attente')"
                        x-text="libelleSegment('À valider', compteurs.en_attente)">À valider</button>
                <button type="button" role="tab"
                        x-bind:aria-selected="filtres.status === 'rejeté' ? 'true' : 'false'"
                        x-bind:class="filtres.status === 'rejeté' ? 'on' : ''"
                        x-on:click="segment('rejeté')"
                        x-text="libelleSegment('Rejetés', compteurs.rejetes)">Rejetés</button>
            </div>
        </div>

        <div class="pim-err" role="alert" x-show="erreur" x-cloak>
            <x-m.icon name="alert" />
            <span x-text="erreur"></span>
            <button type="button" class="pim-err-retry" x-on:click="recharger()">Réessayer</button>
        </div>

        {{-- Squelette pendant le premier chargement --}}
        <div class="m-skel" x-show="chargement && items.length === 0" x-cloak aria-hidden="true">
            <i></i><i></i><i></i><i></i><i></i>
        </div>

        {{-- Cartes-lignes : meme DOM que x-m.row, rendu cote client pour les pages suivantes --}}
        <div class="m-list" x-show="items.length > 0" x-bind:class="chargement ? 'is-loading' : ''" aria-live="polite">
            <template x-for="p in items" x-bind:key="p.id">
                <a x-bind:href="p.url" class="m-row">
                    <div class="av" aria-hidden="true" x-text="p.initiales"></div>
                    <div class="tt">
                        <b x-text="p.nom"></b>
                        <span x-text="sousTitre(p)"></span>
                    </div>
                    <div class="tr">
                        <span class="amt" x-bind:class="p.avoir ? 'neg' : ''" x-text="montantLigne(p)"></span>
                        <span class="m-chip" x-bind:class="toneStatut(p.statut)" x-text="libelleStatut(p.statut)"></span>
                    </div>
                </a>
            </template>
        </div>

        <template x-if="!chargement && items.length === 0 && !erreur">
            <div>
                <template x-if="filtresActifs()">
                    <x-m.empty icon="search" title="Aucun paiement ne correspond" text="Modifiez la recherche ou retirez un filtre.">
                        <button type="button" class="m-btn g" x-on:click="toutEffacer()">Effacer les filtres</button>
                    </x-m.empty>
                </template>
                <template x-if="!filtresActifs()">
                    <x-m.empty icon="inbox" :title="$pimMienSeul ? 'Aucun encaissement' : 'Aucun paiement'" text="Le premier paiement de l'année apparaîtra ici." />
                </template>
            </div>
        </template>

        <div class="pim-suite" x-show="hasMore" x-cloak>
            <button type="button" class="m-btn g" x-on:click="suite()" x-bind:disabled="chargementSuite">
                <span x-show="!chargementSuite">Voir plus</span>
                <span x-show="chargementSuite" x-cloak>Chargement…</span>
            </button>
        </div>
        <div x-ref="sentinelle" class="pim-sentinelle" aria-hidden="true"></div>

        <p class="pim-fin" x-show="!hasMore && items.length > 0" x-cloak
           x-text="items.length + ' sur ' + total + (total > 1 ? ' paiements' : ' paiement')"></p>
    </div>

    {{-- Feuille : filtres --}}
    <x-m.sheet id="pim-filtres" title="Filtres" sub="La liste et les exports suivent ces filtres.">
        <div class="pim-form">
            <div class="m-field">
                <label>Période</label>
                <div class="m-seg pim-presets">
                    <button type="button" x-bind:class="presetActif('jour') ? 'on' : ''" x-on:click="preset('jour')">Aujourd'hui</button>
                    <button type="button" x-bind:class="presetActif('semaine') ? 'on' : ''" x-on:click="preset('semaine')">7 jours</button>
                    <button type="button" x-bind:class="presetActif('mois') ? 'on' : ''" x-on:click="preset('mois')">Ce mois</button>
                    <button type="button" x-bind:class="presetActif('tout') ? 'on' : ''" x-on:click="preset('tout')">Tout</button>
                </div>
                <div class="pim-dates">
                    <input type="date" class="m-in" aria-label="Du" x-model="brouillon.date_debut" x-bind:max="brouillon.date_fin || null">
                    <input type="date" class="m-in" aria-label="Au" x-model="brouillon.date_fin" x-bind:min="brouillon.date_debut || null">
                </div>
            </div>

            <div class="m-field">
                <label>Statut</label>
                <div class="m-seg">
                    <button type="button" x-bind:class="brouillon.status === '' ? 'on' : ''" x-on:click="brouillon.status = ''">Tous</button>
                    <button type="button" x-bind:class="brouillon.status === 'validé' ? 'on' : ''" x-on:click="brouillon.status = 'validé'">Validés</button>
                    <button type="button" x-bind:class="brouillon.status === 'en_attente' ? 'on' : ''" x-on:click="brouillon.status = 'en_attente'">À valider</button>
                    <button type="button" x-bind:class="brouillon.status === 'rejeté' ? 'on' : ''" x-on:click="brouillon.status = 'rejeté'">Rejetés</button>
                </div>
            </div>

            @if(count($pimFrais) > 0)
                <div class="m-field">
                    <label>Frais</label>
                    <div class="m-opt pim-opt">
                        <label>
                            <input type="radio" name="pim_frais" value="" x-model="brouillon.frais_category_id">
                            <span class="rd" aria-hidden="true"></span>
                            <span>Tous les frais</span>
                        </label>
                        @foreach($pimFrais as $pimF)
                            <label>
                                <input type="radio" name="pim_frais" value="{{ $pimF['id'] }}" x-model="brouillon.frais_category_id">
                                <span class="rd" aria-hidden="true"></span>
                                <span>{{ $pimF['nom'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <button type="button" class="m-btn p" x-on:click="appliquerFiltres()">Appliquer</button>
            <button type="button" class="m-btn g" x-on:click="reinitialiser()">Réinitialiser</button>
        </div>
    </x-m.sheet>

    {{-- Feuille : exports --}}
    @if($pimPeutExporter)
        <x-m.sheet id="pim-export" title="Exporter" sub="La liste telle qu'elle est filtrée.">
            <div class="m-menu">
                <button type="button" x-on:click="exporter('pdf-preview')">
                    <x-m.icon name="file" />
                    <span class="pim-menu-tt">Aperçu PDF<small>Voir avant de télécharger</small></span>
                    <span class="ch"><x-m.icon name="chr" /></span>
                </button>
                @if($pimEtatFinancier)
                    <button type="button" x-on:click="exporter('etat-financier-preview')">
                        <x-m.icon name="scale" />
                        <span class="pim-menu-tt">Aperçu état financier<small>Qui a soldé quel frais</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                    <button type="button" x-on:click="exporter('etat-financier')">
                        <x-m.icon name="dl" />
                        <span class="pim-menu-tt">Télécharger l'état financier</span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                @endif
                <button type="button" x-on:click="exporter('excel')">
                    <x-m.icon name="dl" />
                    <span class="pim-menu-tt">Excel (.xlsx)</span>
                    <span class="ch"><x-m.icon name="chr" /></span>
                </button>
                <button type="button" x-on:click="exporter('saari')">
                    <x-m.icon name="dl" />
                    <span class="pim-menu-tt">Excel SAARI<small>Format Sage Saari</small></span>
                    <span class="ch"><x-m.icon name="chr" /></span>
                </button>
                <button type="button" x-on:click="exporter('csv')">
                    <x-m.icon name="dl" />
                    <span class="pim-menu-tt">CSV</span>
                    <span class="ch"><x-m.icon name="chr" /></span>
                </button>
                <button type="button" x-on:click="exporter('pdf')">
                    <x-m.icon name="dl" />
                    <span class="pim-menu-tt">Télécharger PDF</span>
                    <span class="ch"><x-m.icon name="chr" /></span>
                </button>
            </div>
        </x-m.sheet>
    @endif
</div>

@push('styles')
<style>
    /* Liste des paiements mobile — namespace pim- */
    .pim-screen { font-family: var(--m-font); }
    .pim-bar { display: grid; grid-template-columns: 1fr 48px; gap: 8px; align-items: center; }
    .pim-bar .m-search { margin: 0; cursor: text; }
    .pim-seg { margin-top: 8px; }
    .pim-filtre { position: relative; width: 48px; height: 48px; border-radius: 14px; border: 1px solid #e6eaf2; background: #fff; color: #0453cb; display: grid; place-items: center; cursor: pointer; -webkit-tap-highlight-color: transparent; transition: transform 120ms ease, background 120ms ease; }
    .pim-filtre svg { width: 20px; height: 20px; }
    .pim-filtre:active { transform: scale(.94); }
    .pim-filtre.on { background: rgba(4,83,203,.08); border-color: #c7d7f3; }
    .pim-badge { position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #0453cb; color: #fff; font-size: 10.5px; font-weight: 700; display: grid; place-items: center; }
    .pim-err { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; background: #fdecea; color: #a12016; border: 1px solid #f5c6c0; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; }
    .pim-err svg { width: 20px; height: 20px; }
    .pim-err-retry { border: 0; background: transparent; color: #a12016; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 6px 0; }
    .m-list.is-loading { opacity: .6; transition: opacity 120ms ease; }
    .pim-suite { display: grid; }
    .pim-sentinelle { height: 1px; }
    .pim-fin { margin: 0; text-align: center; font-size: 12px; color: #94a3b8; }
    .pim-form { display: grid; gap: 14px; padding-bottom: 4px; }
    .pim-presets { margin-bottom: 4px; }
    .pim-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .pim-dates .m-in { padding: 0 10px; font-size: 14px; }
    .pim-opt { max-height: 40vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .pim-opt label { position: relative; }
    .pim-opt label > span:nth-child(3) { display: block; font-size: 14px; font-weight: 600; color: #0f172a; }
    .pim-menu-tt { display: grid; gap: 1px; min-width: 0; }
    .pim-menu-tt small { font-size: 12px; color: #64748b; font-weight: 500; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.pimListe !== 'function') {
        window.pimListe = function (cfg) {
            var initial = cfg.initial || {};
            var stats = initial.stats || {};
            var filtresInit = Object.assign({ search: '', status: '', frais_category_id: '', date_debut: '', date_fin: '' }, cfg.filtres || {});

            return {
                devise: cfg.devise || 'FCFA',
                urls: cfg.urls || {},
                exports: cfg.exports || {},
                filtres: filtresInit,
                brouillon: Object.assign({}, filtresInit),
                items: initial.items || [],
                hasMore: !!initial.has_more,
                nextPage: initial.next_page || 2,
                total: (initial.summary && initial.summary.total) || 0,
                montantTotal: Number(stats.montant_total || 0),
                // Les compteurs des segments ne sont fiables que quand aucun
                // statut n'est filtre : null = inconnu, le segment reste sans chiffre.
                compteurs: filtresInit.status === ''
                    ? { tous: Number(stats.total || 0), en_attente: Number(stats.en_attente || 0), rejetes: Number(stats.rejetes || 0) }
                    : { tous: null, en_attente: null, rejetes: null },
                chargement: false,
                chargementSuite: false,
                erreur: null,
                _seq: 0,
                _observer: null,
                _onPtr: null,

                init() {
                    var self = this;
                    if (cfg.flash) {
                        this.toast(cfg.flash, 'success');
                    }
                    if (this.filtres.status !== '') {
                        this.compteurs[this.filtres.status === 'en_attente' ? 'en_attente' : (this.filtres.status === 'rejeté' ? 'rejetes' : 'tous')] = this.total;
                    }
                    if (this.$refs.corps) {
                        this._onPtr = function () { self.recharger(); };
                        this.$refs.corps.addEventListener('m-ptr:refresh', this._onPtr);
                    }
                    if ('IntersectionObserver' in window && this.$refs.sentinelle) {
                        this._observer = new IntersectionObserver(function (entries) {
                            if (entries.some(function (e) { return e.isIntersecting; })) {
                                self.suite();
                            }
                        }, { rootMargin: '200px 0px' });
                        this._observer.observe(this.$refs.sentinelle);
                    }
                },
                destroy() {
                    if (this._observer) { this._observer.disconnect(); this._observer = null; }
                    if (this._onPtr && this.$refs.corps) { this.$refs.corps.removeEventListener('m-ptr:refresh', this._onPtr); this._onPtr = null; }
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

                /* ---- mise en forme ---- */
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
                },
                montantLigne(p) {
                    return (p.avoir ? '− ' : '') + this.format(p.montant) + ' ' + this.devise;
                },
                sousTitre(p) {
                    var classe = p.classe ? (p.parcours ? p.classe + ' · ' + p.parcours : p.classe) : null;
                    return [classe, p.frais, p.mode, p.quand].filter(function (x) { return x; }).join(' · ');
                },
                libelleStatut(s) {
                    return { 'validé': 'Validé', 'en_attente': 'À valider', 'rejeté': 'Rejeté' }[s] || s || '—';
                },
                toneStatut(s) {
                    return { 'validé': 'ok', 'en_attente': 'warn', 'rejeté': 'bad' }[s] || 'mute';
                },
                libelleSegment(texte, n) {
                    return n === null || n === undefined ? texte : texte + ' · ' + this.format(n);
                },
                majSousTitre() {
                    var small = this.$root.querySelector('.m-appbar .t small');
                    if (small) {
                        small.textContent = this.format(this.total) + ' · ' + this.format(this.montantTotal) + ' ' + this.devise;
                    }
                },

                /* ---- filtres ---- */
                nbFiltres() {
                    var n = 0;
                    if (this.filtres.frais_category_id) { n++; }
                    if (this.filtres.date_debut || this.filtres.date_fin) { n++; }
                    if (this.filtres.status === 'validé') { n++; }
                    return n;
                },
                filtresActifs() {
                    return this.nbFiltres() > 0 || this.filtres.search !== '' || this.filtres.status !== '';
                },
                ouvrirFiltres() {
                    this.brouillon = Object.assign({}, this.filtres);
                    this.ouvrir('pim-filtres');
                },
                appliquerFiltres() {
                    this.filtres = Object.assign({}, this.brouillon);
                    this.fermerTout();
                    this.recharger();
                },
                reinitialiser() {
                    this.brouillon = { search: this.filtres.search, status: '', frais_category_id: '', date_debut: '', date_fin: '' };
                },
                toutEffacer() {
                    this.filtres = { search: '', status: '', frais_category_id: '', date_debut: '', date_fin: '' };
                    this.recharger();
                },
                segment(status) {
                    if (this.filtres.status === status) { return; }
                    this.filtres.status = status;
                    this.recharger();
                },
                _iso(d) {
                    var m = String(d.getMonth() + 1).padStart(2, '0');
                    var j = String(d.getDate()).padStart(2, '0');
                    return d.getFullYear() + '-' + m + '-' + j;
                },
                _bornes(cle) {
                    var auj = new Date();
                    if (cle === 'jour') { return [this._iso(auj), this._iso(auj)]; }
                    if (cle === 'semaine') { var d = new Date(auj); d.setDate(d.getDate() - 6); return [this._iso(d), this._iso(auj)]; }
                    if (cle === 'mois') { return [this._iso(new Date(auj.getFullYear(), auj.getMonth(), 1)), this._iso(auj)]; }
                    return ['', ''];
                },
                preset(cle) {
                    var b = this._bornes(cle);
                    this.brouillon.date_debut = b[0];
                    this.brouillon.date_fin = b[1];
                },
                presetActif(cle) {
                    var b = this._bornes(cle);
                    return this.brouillon.date_debut === b[0] && this.brouillon.date_fin === b[1];
                },
                parametres() {
                    var p = new URLSearchParams();
                    var f = this.filtres;
                    Object.keys(f).forEach(function (k) { if (f[k] !== '' && f[k] !== null) { p.set(k, f[k]); } });
                    return p;
                },

                /* ---- chargement ---- */
                async charger(page, remplacer) {
                    var seq = ++this._seq;
                    if (remplacer) { this.chargement = true; } else { this.chargementSuite = true; }
                    this.erreur = null;
                    try {
                        var p = this.parametres();
                        p.set('mode', 'mobile');
                        p.set('page', String(page));
                        var res = await fetch(this.urls.liste + '?' + p.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) { throw new Error('Impossible de charger les paiements (' + res.status + ').'); }
                        var data = await res.json();
                        if (seq !== this._seq) { return; }
                        this.items = remplacer ? (data.items || []) : this.items.concat(data.items || []);
                        this.hasMore = !!data.has_more;
                        this.nextPage = data.next_page || (page + 1);
                        this.total = (data.summary && data.summary.total) || 0;
                        var s = data.stats || {};
                        this.montantTotal = Number(s.montant_total || 0);
                        if (this.filtres.status === '') {
                            this.compteurs = { tous: Number(s.total || 0), en_attente: Number(s.en_attente || 0), rejetes: Number(s.rejetes || 0) };
                        } else if (this.filtres.status === 'en_attente') {
                            this.compteurs.en_attente = this.total;
                        } else if (this.filtres.status === 'rejeté') {
                            this.compteurs.rejetes = this.total;
                        }
                        this.majSousTitre();
                        if (data.url && window.history && window.history.replaceState) {
                            window.history.replaceState(null, '', data.url);
                        }
                    } catch (e) {
                        if (seq !== this._seq) { return; }
                        this.erreur = e.message || 'Impossible de charger les paiements.';
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
                    if (ex.inline) { p.set('inline', '1'); }
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
