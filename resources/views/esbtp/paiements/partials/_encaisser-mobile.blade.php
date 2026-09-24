{{--
    Encaisser en pas-à-pas plein écran (shell mobile, profil caissier).
    Inclus depuis esbtp/paiements/create.blade.php quand le shell mobile est actif.

    Quatre étapes : Étudiant → Frais → Montant → Mode. Un seul composant Alpine
    (window.mabEncaisser) porte tout l'état ; rien ici ne calcule une règle de
    caisse : la répartition vient de l'aperçu serveur, et l'enregistrement passe
    par le MÊME store() que le formulaire de bureau, avec les mêmes champs.

    Namespace CSS de la page : mab-*.
--}}
@php
    $mabDevise = 'FCFA';

    // Les modes proposés : ceux du formulaire de bureau, filtrés par le garde
    // (MobileMoneyPaymentGuard), seule source : il sait ce que chaque
    // permission d'encaissement ouvre (tous modes, hors espèces, mobile money).
    // Le repli dérive de l'enum lui aussi : recopié, il perdait Carte, Djamo
    // et Celtiis Cash, donc le pas-à-pas mobile offrait moins que le bureau.
    $mabTousModes = $allModeOptions ?? \App\Enums\ModePaiement::optionsDeGuichet();
    $mabAutorises = $allowedPaymentModes ?? [];
    $mabModesMobiles = app(\App\Services\MobileMoneyPaymentGuard::class)->mobileMoneyModes();
    $mabModes = [];
    foreach ($mabTousModes as $mabLabel => $mabCanon) {
        if (! in_array($mabCanon, $mabAutorises, true)) {
            continue;
        }
        $mabModes[] = [
            'label' => $mabLabel,
            'canon' => $mabCanon,
            'mobile' => in_array($mabCanon, $mabModesMobiles, true),
            'especes' => $mabCanon === \App\Enums\ModePaiement::ESPECES->value,
        ];
    }

    $mabPreselect = ['inscription' => null, 'etudiant' => null];
    if (! empty($inscription)) {
        $mabEtu = $inscription->etudiant ?? $etudiant ?? null;
        $mabPreselect['inscription'] = [
            'id' => $inscription->id,
            'etudiant_id' => $inscription->etudiant_id,
            'nom' => $mabEtu?->user?->name ?? trim(($mabEtu->prenoms ?? '').' '.($mabEtu->nom ?? '')),
            'matricule' => $mabEtu->matricule ?? '',
            'classe' => $inscription->classe->name ?? ($inscription->filiere->name ?? '—'),
            'niveau' => $inscription->niveauEtude->name ?? '',
        ];
    } elseif (! empty($etudiant)) {
        $mabPreselect['etudiant'] = [
            'id' => $etudiant->id,
            'nom' => $etudiant->user?->name ?? trim(($etudiant->prenoms ?? '').' '.($etudiant->nom ?? '')),
            'matricule' => $etudiant->matricule ?? '',
        ];
    }

    $mabCfg = [
        'urls' => [
            'inscriptions' => route('esbtp.api.caisse.inscriptions'),
            'inscriptionsEtudiant' => route('esbtp.api.etudiants.inscriptions'),
            'categories' => route('esbtp.api.frais.categories'),
            'apercu' => route('esbtp.paiements.repartition.apercu'),
            'store' => route('esbtp.paiements.store'),
            'index' => route('esbtp.paiements.index'),
        ],
        'csrf' => csrf_token(),
        'seuil' => (int) ($unusualAmountThreshold ?? 0),
        'date' => now()->toDateString(),
        'dateLabel' => now()->translatedFormat('d M Y'),
        'devise' => $mabDevise,
        'preselect' => $mabPreselect,
    ];
@endphp

@push('styles')
<style>
    .mab-screen { font-family: var(--m-font); }
    .mab-etape { display: grid; gap: 14px; }
    .mab-row { width: 100%; text-align: left; font: inherit; cursor: pointer; -webkit-tap-highlight-color: transparent; }
    .mab-note { grid-template-columns: 44px 1fr; }
    .mab-note .nm { font-weight: 600; font-size: 14.5px; color: #0f172a; }
    .mab-note .mt { font-size: 11.5px; color: #64748b; }
    .mab-reste { font-weight: 700; font-size: 13.5px; color: #0f172a; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mab-reste.neg { color: #b42318; }
    .mab-off { opacity: .55; }
    .mab-dl-total dt { font-weight: 700; color: #0f172a; }
    .mab-dl-total dd { color: #b42318; }
    .mab-hint { font-size: 12px; color: #64748b; text-align: center; }
    .mab-card { background: #fff; border: 1px solid #e6eaf2; border-radius: 14px; padding: 12px; display: grid; gap: 6px; }
    .mab-card h5 { margin: 0; font-size: 12px; font-weight: 700; color: #475569; letter-spacing: .04em; text-transform: uppercase; }
    .mab-alloc { display: flex; justify-content: space-between; gap: 10px; font-size: 13.5px; padding: 6px 0; border-top: 1px solid #eef2f7; }
    .mab-alloc:first-of-type { border-top: 0; }
    .mab-alloc b { font-weight: 700; font-variant-numeric: tabular-nums; color: #0453cb; white-space: nowrap; }
    .mab-alloc span { color: #0f172a; }
    .mab-err { background: #fdecea; color: #a12016; border-radius: 12px; padding: 10px 12px; font-size: 13px; font-weight: 600; line-height: 1.4; }
    .mab-field-err { font-size: 12px; color: #a12016; font-weight: 600; }
    .mab-sheet-acts { display: grid; gap: 8px; padding: 4px 16px 16px; }
    .mab-spin { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: m-spin .8s linear infinite; }
    .mab-pad button:active { background: #eef2f7; }
    .mab-big.is-zero { color: #94a3b8; }
</style>
@endpush

<div class="m-only-mobile m-screen mab-screen"
     x-data="mabEncaisser()"
     data-mab-cfg="{{ json_encode($mabCfg, JSON_UNESCAPED_UNICODE) }}">

    <header class="m-appbar">
        <button type="button" class="m-ib ghost" x-on:click="retour()" aria-label="Retour">
            <x-m.icon name="chl" />
        </button>
        <div class="t">
            Encaisser
            <small x-text="sousTitre"></small>
        </div>
        <button type="button" class="m-ib ghost" x-on:click="abandonner()" aria-label="Abandonner l'encaissement">
            <x-m.icon name="x" />
        </button>
    </header>

    <div class="m-body">
        <div class="m-step" aria-hidden="true">
            <i x-bind:class="etape >= 1 ? 'on' : ''"></i>
            <i x-bind:class="etape >= 2 ? 'on' : ''"></i>
            <i x-bind:class="etape >= 3 ? 'on' : ''"></i>
            <i x-bind:class="etape >= 4 ? 'on' : ''"></i>
        </div>

        <div class="mab-err" x-show="erreurGlobale" x-cloak x-text="erreurGlobale" role="alert"></div>

        {{-- Étape 1 : l'étudiant --}}
        <div class="mab-etape" x-show="etape === 1" x-cloak>
            <label class="m-search">
                <x-m.icon name="search" />
                <input type="search" x-model="q" x-on:input.debounce.300ms="chercher()" x-ref="recherche"
                       placeholder="Nom, prénom ou matricule" autocomplete="off" inputmode="search">
            </label>

            <div class="m-skel" x-show="chargement.liste" x-cloak><i></i><i></i><i></i></div>

            <div class="m-list one" x-show="!chargement.liste">
                <template x-for="row in inscriptions" x-bind:key="row.id">
                    <button type="button" class="m-row mab-row" x-on:click="choisirInscription(row)">
                        <div class="av" aria-hidden="true" x-text="initiales(row.nom)"></div>
                        <div class="tt">
                            <b x-text="row.nom"></b>
                            <span x-text="row.classe + ' · ' + row.matricule"></span>
                        </div>
                        <div class="tr">
                            <span class="m-chip info" x-text="row.niveau"></span>
                        </div>
                    </button>
                </template>
                <x-m.empty x-show="inscriptions.length === 0 && !chargement.liste" x-cloak
                           icon="search" title="Aucun dossier trouvé"
                           text="Vérifiez l'orthographe du nom ou le matricule. Seules les inscriptions de l'année en cours apparaissent." />
            </div>
        </div>

        {{-- Étape 2 : les frais --}}
        <div class="mab-etape" x-show="etape === 2" x-cloak>
            <div class="m-note mab-note" x-show="sel">
                <div class="av" aria-hidden="true" x-text="initiales(sel ? sel.nom : '')"></div>
                <div>
                    <div class="nm" x-text="sel ? sel.nom : ''"></div>
                    <div class="mt" x-text="sel ? (sel.classe + ' · Mat. ' + sel.matricule) : ''"></div>
                </div>
            </div>

            <div class="m-skel" x-show="chargement.frais" x-cloak><i></i><i></i></div>

            <div class="m-opt" x-show="!chargement.frais && frais.length > 0">
                <template x-for="f in frais" x-bind:key="f.id">
                    <label x-bind:class="f.satisfied_in_kind ? 'mab-off' : ''">
                        <input type="checkbox" x-bind:value="String(f.id)" x-model="choix" x-bind:disabled="f.satisfied_in_kind">
                        <span class="rd" aria-hidden="true"></span>
                        <div>
                            <b x-text="f.name"></b>
                            <span x-text="detailFrais(f)"></span>
                        </div>
                        <b class="mab-reste" x-bind:class="Number(f.remaining) > 0 ? 'neg' : ''" x-text="resteFrais(f)"></b>
                    </label>
                </template>
            </div>

            <x-m.empty x-show="!chargement.frais && frais.length === 0" x-cloak
                       icon="inbox" title="Aucun frais à encaisser"
                       text="Cette inscription ne porte aucun frais configuré. Vérifiez la souscription de l'étudiant." />

            <div class="mab-field-err" x-show="erreurs.frais_category_id" x-cloak x-text="erreurs.frais_category_id"></div>
        </div>

        {{-- Étape 3 : le montant --}}
        <div class="mab-etape" x-show="etape === 3" x-cloak>
            <div class="m-note mab-note" x-show="sel">
                <div class="av" aria-hidden="true" x-text="initiales(sel ? sel.nom : '')"></div>
                <div>
                    <div class="nm" x-text="sel ? sel.nom : ''"></div>
                    <div class="mt" x-text="sel ? (sel.classe + ' · Mat. ' + sel.matricule) : ''"></div>
                </div>
            </div>

            <dl class="m-dl">
                <dt x-text="libelleChoix"></dt><dd x-text="fmt(duChoisi)"></dd>
                <dt>Déjà versé</dt><dd x-text="fmt(verseChoisi)"></dd>
                <div class="mab-dl-total" style="display: contents;">
                    <dt>Reste dû</dt><dd x-text="fmt(resteChoisi)"></dd>
                </div>
            </dl>

            <div class="m-field">
                <label>Montant encaissé</label>
                <div class="m-big mab-big" x-bind:class="montant > 0 ? '' : 'is-zero'" aria-live="polite">
                    <span x-text="fmtNu(montant)"></span><small x-text="cfg.devise"></small>
                </div>
                <div class="mab-field-err" x-show="erreurs.montant" x-cloak x-text="erreurs.montant"></div>
            </div>

            <div class="m-pad mab-pad" role="group" aria-label="Clavier numérique">
                <template x-for="k in ['1','2','3','4','5','6','7','8','9']" x-bind:key="k">
                    <button type="button" x-on:click="tape(k)" x-text="k"></button>
                </template>
                <button type="button" class="k" x-on:click="tape('000')">000</button>
                <button type="button" x-on:click="tape('0')">0</button>
                <button type="button" class="k" x-on:click="tape('efface')" aria-label="Effacer le dernier chiffre">⌫</button>
            </div>

            <div class="mab-card" x-show="montant > 0" x-cloak>
                <h5>Ce montant couvre</h5>
                <div class="mab-hint" x-show="chargement.apercu">Vérification…</div>
                <template x-for="a in apercu.allocations" x-bind:key="a.frais_category_id">
                    <div class="mab-alloc">
                        <span x-text="a.name"></span>
                        <b x-text="fmt(a.montant)"></b>
                    </div>
                </template>
                <div class="mab-err" x-show="apercu.message" x-text="apercu.message"></div>
            </div>

            <div class="m-opt" x-show="depasseSeuil" x-cloak>
                <label>
                    <input type="checkbox" x-model="confirme">
                    <span class="rd" aria-hidden="true"></span>
                    <div>
                        <b>Je confirme ce montant</b>
                        <span x-text="'Il dépasse le seuil habituel de ' + fmt(cfg.seuil) + ' configuré pour l\'école.'"></span>
                    </div>
                </label>
            </div>
        </div>

        {{-- Étape 4 : le mode --}}
        <div class="mab-etape" x-show="etape === 4" x-cloak>
            <div class="m-opt" role="radiogroup" aria-label="Mode de paiement">
                @foreach($mabModes as $mabMode)
                    @if($mabMode['mobile'])
                            <label>
                                <input type="radio" name="mab_mode" value="{{ $mabMode['label'] }}"
                                       data-canon="{{ $mabMode['canon'] }}" data-label="{{ $mabMode['label'] }}"
                                       x-model="form.mode" x-on:change="choisirMode($event.target)">
                                <span class="rd" aria-hidden="true"></span>
                                <div><b>{{ $mabMode['label'] }}</b><span>Numéro de transaction à saisir</span></div>
                                <x-m.icon name="phone" class="m-ic" />
                            </label>
                    @else
                            <label>
                                <input type="radio" name="mab_mode" value="{{ $mabMode['label'] }}"
                                       data-canon="{{ $mabMode['canon'] }}" data-label="{{ $mabMode['label'] }}"
                                       x-model="form.mode" x-on:change="choisirMode($event.target)">
                                <span class="rd" aria-hidden="true"></span>
                                <div>
                                    <b>{{ $mabMode['label'] }}</b>
                                    <span>{{ $mabMode['especes'] ? 'Remis en main propre à la caisse' : 'Référence à saisir' }}</span>
                                </div>
                                <x-m.icon :name="$mabMode['especes'] ? 'cash' : 'file'" class="m-ic" />
                            </label>
                    @endif
                @endforeach
            </div>
            @if($mabModes === [])
                <x-m.empty icon="lock" title="Aucun mode disponible"
                           text="Votre compte n'est autorisé sur aucun mode de paiement. Rapprochez-vous de la comptabilité." />
            @endif
            <div class="mab-field-err" x-show="erreurs.mode_paiement" x-cloak x-text="erreurs.mode_paiement"></div>

            <div class="m-field" x-show="form.mode && form.canon !== 'especes'" x-cloak>
                <label x-text="form.mobile ? 'Numéro de transaction ou téléphone' : 'Référence (n° de chèque, virement…)'"></label>
                <input type="text" class="m-in" x-model="form.reference" autocomplete="off"
                       x-bind:inputmode="form.mobile ? 'tel' : 'text'"
                       placeholder="Facultatif mais recommandé">
                <div class="mab-field-err" x-show="erreurs.reference_paiement" x-cloak x-text="erreurs.reference_paiement"></div>
            </div>

            <dl class="m-dl">
                <dt>Étudiant</dt><dd x-text="sel ? sel.nom : ''"></dd>
                <dt>Frais</dt><dd x-text="libelleChoix"></dd>
                <dt>Montant</dt><dd x-text="fmt(montant)"></dd>
                <dt>Mode</dt><dd x-text="form.label || '—'"></dd>
                <dt>Date</dt><dd x-text="cfg.dateLabel"></dd>
            </dl>
        </div>
    </div>

    {{-- Barre d'action : change avec l'étape (aucune à l'étape 1, la liste occupe l'écran) --}}
    <x-m.actionbar :row="true" x-show="etape === 2 || etape === 3" x-cloak>
        <button type="button" class="m-btn g" x-on:click="solderTout()" x-bind:disabled="chargement.frais || !peutSolder">Solder tout</button>
        <button type="button" class="m-btn p" x-on:click="continuer()" x-bind:disabled="chargement.frais">
            Continuer <x-m.icon name="chr" />
        </button>
    </x-m.actionbar>

    <x-m.actionbar x-show="etape === 4" x-cloak>
        <button type="button" class="m-btn p" x-on:click="encaisser()" x-bind:disabled="envoi || !form.mode">
            <span class="mab-spin" x-show="envoi" x-cloak aria-hidden="true"></span>
            <span x-text="envoi ? 'Enregistrement…' : ('Encaisser ' + fmt(montant))"></span>
        </button>
    </x-m.actionbar>

    <x-m.sheet id="mab-abandon" title="Abandonner l'encaissement ?" sub="Les informations saisies seront perdues.">
        <div class="mab-sheet-acts">
            <a href="{{ route('esbtp.paiements.index') }}" class="m-btn d">Abandonner</a>
            <button type="button" class="m-btn g" x-on:click="hide()">Continuer la saisie</button>
        </div>
    </x-m.sheet>
</div>

@push('scripts')
<script>
if (typeof window.mabEncaisser !== 'function') {
    window.mabEncaisser = function () {
        // Hors de l'état réactif : un AbortController derrière le Proxy d'Alpine
        // lève « Illegal invocation » à l'appel de abort().
        let listeCtrl = null;
        let apercuCtrl = null;
        let apercuTimer = null;

        return {
            cfg: { urls: {}, seuil: 0, devise: '', date: '', dateLabel: '', preselect: {} },
            etape: 1,
            q: '',
            inscriptions: [],
            sel: null,
            frais: [],
            choix: [],
            montant: 0,
            confirme: false,
            apercu: { allocations: [], message: '' },
            form: { mode: '', canon: '', label: '', mobile: false, reference: '' },
            erreurs: {},
            erreurGlobale: '',
            envoi: false,
            chargement: { liste: false, frais: false, apercu: false },

            init() {
                try {
                    this.cfg = JSON.parse(this.$root.dataset.mabCfg || '{}');
                } catch (e) {
                    this.erreurGlobale = "Écran indisponible : configuration illisible.";
                    return;
                }
                const pre = this.cfg.preselect || {};
                if (pre.inscription) {
                    this.choisirInscription(pre.inscription);
                    return;
                }
                if (pre.etudiant) {
                    this.resoudreInscription(pre.etudiant);
                    return;
                }
                this.chercher();
            },

            /* ---------- libellés ---------- */
            get sousTitre() {
                const noms = ['Étudiant', 'Frais', 'Montant', 'Mode'];
                return 'Étape ' + this.etape + ' sur 4 · ' + noms[this.etape - 1];
            },
            get fraisChoisis() {
                const ids = this.choix.map(String);
                return this.frais.filter(f => ids.includes(String(f.id)));
            },
            get libelleChoix() {
                const c = this.fraisChoisis;
                if (c.length === 0) return 'Aucun frais';
                if (c.length === 1) return c[0].name;
                return c.length + ' frais : ' + c.map(f => f.name).join(', ');
            },
            get duChoisi() { return this.fraisChoisis.reduce((s, f) => s + Number(f.montant || 0), 0); },
            get verseChoisi() { return this.fraisChoisis.reduce((s, f) => s + Number(f.paid || 0), 0); },
            get resteChoisi() { return this.fraisChoisis.reduce((s, f) => s + Number(f.remaining || 0), 0); },
            get resteTotal() {
                return this.frais.filter(f => !f.satisfied_in_kind).reduce((s, f) => s + Number(f.remaining || 0), 0);
            },
            get peutSolder() {
                return this.etape === 3 ? this.resteChoisi > 0 : this.resteTotal > 0;
            },
            get depasseSeuil() { return this.montant > Number(this.cfg.seuil || 0); },
            get saisieEnCours() { return this.sel !== null || this.montant > 0; },

            fmtNu(n) {
                const v = Number(n || 0);
                return new Intl.NumberFormat('fr-FR').format(Number.isFinite(v) ? v : 0);
            },
            fmt(n) { return this.fmtNu(n) + ' ' + (this.cfg.devise || ''); },
            initiales(nom) {
                return String(nom || '').split(/\s+/).filter(Boolean).slice(0, 2)
                    .map(m => m.charAt(0)).join('').toUpperCase();
            },
            detailFrais(f) {
                if (f.satisfied_in_kind) return 'Déjà déposé en nature — soldé';
                if (Number(f.montant || 0) === 0) return 'Tarif non configuré par l\'école';
                return 'Dû ' + this.fmt(f.montant) + ' · versé ' + this.fmt(f.paid);
            },
            resteFrais(f) {
                if (f.satisfied_in_kind) return 'Soldé';
                if (Number(f.montant || 0) === 0) return '—';
                return Number(f.remaining) > 0 ? this.fmt(f.remaining) : 'Soldé';
            },

            /* ---------- navigation ---------- */
            retour() {
                this.erreurGlobale = '';
                if (this.etape > 1) {
                    this.etape -= 1;
                    return;
                }
                this.abandonner();
            },
            abandonner() {
                if (this.saisieEnCours) {
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: 'mab-abandon' } }));
                    return;
                }
                window.location.href = this.cfg.urls.index;
            },
            continuer() {
                this.erreurs = {};
                this.erreurGlobale = '';
                if (this.etape === 2) {
                    if (this.fraisChoisis.length === 0) {
                        this.erreurs.frais_category_id = 'Choisissez au moins un frais à encaisser.';
                        return;
                    }
                    this.etape = 3;
                    if (this.montant === 0) { this.montant = Math.round(this.resteChoisi); }
                    this.planifierApercu();
                    return;
                }
                if (this.etape === 3) {
                    if (this.montant <= 0) {
                        this.erreurs.montant = 'Saisissez un montant supérieur à 0. Un encaissement à zéro franc n\'est pas un encaissement.';
                        return;
                    }
                    if (this.apercu.message) {
                        this.erreurs.montant = this.apercu.message;
                        return;
                    }
                    if (this.depasseSeuil && !this.confirme) {
                        this.erreurs.montant = 'Ce montant dépasse le seuil habituel : cochez la confirmation pour continuer.';
                        return;
                    }
                    this.etape = 4;
                }
            },

            /* ---------- étape 1 ---------- */
            async chercher() {
                if (listeCtrl) { listeCtrl.abort(); }
                listeCtrl = new AbortController();
                this.chargement.liste = true;
                const params = new URLSearchParams();
                if (this.q.trim()) { params.set('q', this.q.trim()); }
                try {
                    const res = await fetch(this.cfg.urls.inscriptions + '?' + params.toString(), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: listeCtrl.signal,
                    });
                    if (!res.ok) { throw new Error('HTTP ' + res.status); }
                    const data = await res.json();
                    this.inscriptions = data.results || [];
                } catch (e) {
                    if (e.name === 'AbortError') { return; }
                    this.inscriptions = [];
                    this.erreurGlobale = 'Liste des étudiants indisponible. Vérifiez la connexion puis réessayez.';
                } finally {
                    this.chargement.liste = false;
                }
            },
            async resoudreInscription(etu) {
                // Arrivée depuis une fiche étudiant sans inscription précisée : la
                // même règle que le bureau — année en cours, sinon la plus récente.
                this.chargement.frais = true;
                this.etape = 2;
                try {
                    const res = await fetch(this.cfg.urls.inscriptionsEtudiant + '?etudiant_id=' + encodeURIComponent(etu.id), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = res.ok ? await res.json() : [];
                    const liste = Array.isArray(data) ? data : (data.data || []);
                    const insc = liste.find(i => i && i.is_current_year) || liste[0];
                    if (!insc) {
                        this.chargement.frais = false;
                        this.etape = 1;
                        this.erreurGlobale = 'Cet étudiant n\'a aucune inscription : rien à encaisser.';
                        this.chercher();
                        return;
                    }
                    this.choisirInscription({
                        id: insc.id,
                        etudiant_id: etu.id,
                        nom: etu.nom,
                        matricule: etu.matricule,
                        classe: (insc.filiere || '') + ' · ' + (insc.niveau || ''),
                        niveau: insc.niveau || '',
                    });
                } catch (e) {
                    this.chargement.frais = false;
                    this.etape = 1;
                    this.erreurGlobale = 'Impossible de charger les inscriptions de cet étudiant.';
                    this.chercher();
                }
            },
            choisirInscription(row) {
                this.sel = {
                    id: row.id,
                    etudiant_id: row.etudiant_id,
                    nom: row.nom || '',
                    matricule: row.matricule || '',
                    classe: row.classe || '',
                    niveau: row.niveau || '',
                };
                this.choix = [];
                this.montant = 0;
                this.confirme = false;
                this.apercu = { allocations: [], message: '' };
                this.erreurs = {};
                this.erreurGlobale = '';
                this.etape = 2;
                this.chargerFrais(row.id);
            },

            /* ---------- étape 2 ---------- */
            async chargerFrais(inscriptionId) {
                this.chargement.frais = true;
                this.frais = [];
                try {
                    const res = await fetch(this.cfg.urls.categories + '?inscription_id=' + encodeURIComponent(inscriptionId), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json().catch(() => []);
                    if (!res.ok) { throw new Error(data.message || data.error || ('HTTP ' + res.status)); }
                    this.frais = Array.isArray(data) ? data : [];
                } catch (e) {
                    this.frais = [];
                    this.erreurGlobale = 'Frais de cette inscription indisponibles : ' + (e.message || 'réessayez.');
                } finally {
                    this.chargement.frais = false;
                }
            },
            solderTout() {
                this.erreurs = {};
                if (this.etape === 3) {
                    // Les frais sont déjà choisis : on encaisse tout ce qu'ils réclament.
                    this.montant = Math.round(this.resteChoisi);
                    this.confirme = false;
                    this.planifierApercu();
                    return;
                }
                this.choix = this.frais
                    .filter(f => !f.satisfied_in_kind && Number(f.remaining) > 0)
                    .map(f => String(f.id));
                if (this.choix.length === 0) {
                    this.erreurs.frais_category_id = 'Aucun frais ne reste dû : rien à solder.';
                    return;
                }
                this.montant = Math.round(this.resteChoisi);
                this.confirme = false;
                this.etape = 3;
                this.planifierApercu();
            },

            /* ---------- étape 3 ---------- */
            tape(k) {
                this.erreurs.montant = '';
                let v = Math.round(this.montant);
                if (k === 'efface') {
                    v = Math.floor(v / 10);
                } else if (k === '000') {
                    v = v * 1000;
                } else {
                    v = v * 10 + Number(k);
                }
                if (v > 999999999999) { return; }
                this.montant = v;
                this.confirme = false;
                this.planifierApercu();
            },
            repartitionChoisie() {
                // Plusieurs frais choisis : on PROPOSE de les servir dans l'ordre de
                // l'école, l'excédent restant sur le premier (le frais désigné).
                // Le serveur vérifie cette proposition dans l'aperçu ET à
                // l'enregistrement ; il reste seul à faire foi.
                const c = this.fraisChoisis;
                if (c.length <= 1) { return null; }
                const parts = {};
                let reste = Math.round(this.montant);
                c.forEach(f => {
                    const cap = Number(f.remaining || 0);
                    const part = Math.min(reste, cap);
                    parts[String(f.id)] = part;
                    reste -= part;
                });
                if (reste > 0) { parts[String(c[0].id)] += reste; }
                return parts;
            },
            planifierApercu() {
                clearTimeout(apercuTimer);
                apercuTimer = setTimeout(() => this.demanderApercu(), 350);
            },
            async demanderApercu() {
                if (!this.sel || this.montant <= 0 || this.fraisChoisis.length === 0) {
                    this.apercu = { allocations: [], message: '' };
                    return;
                }
                if (apercuCtrl) { apercuCtrl.abort(); }
                apercuCtrl = new AbortController();
                this.chargement.apercu = true;
                const corps = {
                    inscription_id: this.sel.id,
                    frais_category_id: this.fraisChoisis[0].id,
                    montant: this.montant,
                };
                const rep = this.repartitionChoisie();
                if (rep) { corps.repartition = rep; }
                try {
                    const res = await fetch(this.cfg.urls.apercu, {
                        method: 'POST',
                        headers: this.entetes(),
                        body: JSON.stringify(corps),
                        signal: apercuCtrl.signal,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok) {
                        this.apercu = { allocations: data.allocations || [], message: '' };
                    } else {
                        this.apercu = { allocations: [], message: data.message || 'Impossible de vérifier la répartition de ce versement.' };
                    }
                } catch (e) {
                    if (e.name === 'AbortError') { return; }
                    this.apercu = { allocations: [], message: 'Vérification impossible : connexion perdue ?' };
                } finally {
                    this.chargement.apercu = false;
                }
            },

            /* ---------- étape 4 ---------- */
            choisirMode(el) {
                this.form.canon = el.dataset.canon || '';
                this.form.label = el.dataset.label || el.value;
                this.form.mobile = !['especes', 'cheque', 'virement'].includes(this.form.canon);
                this.erreurs.mode_paiement = '';
            },
            entetes() {
                return {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.cfg.csrf,
                };
            },
            charge() {
                // Les MÊMES champs que le formulaire de bureau, vers le MÊME store().
                const corps = {
                    _token: this.cfg.csrf,
                    etudiant_id: this.sel.etudiant_id,
                    inscription_id: this.sel.id,
                    frais_category_id: this.fraisChoisis[0].id,
                    montant: this.montant,
                    date_paiement: this.cfg.date,
                    mode_paiement: this.form.mode,
                    reference_paiement: this.form.reference || null,
                    tranche: null,
                    commentaire: null,
                };
                if (this.confirme) { corps.confirmed_unusual_amount = '1'; }
                const rep = this.repartitionChoisie();
                if (rep) { corps.repartition = rep; }
                return corps;
            },
            async encaisser() {
                if (this.envoi) { return; }
                this.erreurs = {};
                this.erreurGlobale = '';
                if (!this.sel || this.fraisChoisis.length === 0) { this.etape = 2; return; }
                if (this.montant <= 0) { this.etape = 3; this.erreurs.montant = 'Saisissez un montant supérieur à 0.'; return; }
                if (!this.form.mode) { this.erreurs.mode_paiement = 'Choisissez un mode de paiement.'; return; }

                this.envoi = true;
                let parti = false;
                try {
                    const res = await fetch(this.cfg.urls.store, {
                        method: 'POST',
                        headers: this.entetes(),
                        body: JSON.stringify(this.charge()),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        const p = data.paiement || {};
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { type: 'success', message: data.message || 'Paiement enregistré.' },
                        }));
                        const cible = p.url_recu || p.url_show || this.cfg.urls.index;
                        parti = true;
                        setTimeout(() => { window.location.href = cible; }, 350);
                        return;
                    }
                    this.montrerErreurs(res.status, data);
                } catch (e) {
                    this.erreurGlobale = 'Enregistrement impossible : connexion perdue ? Rien n\'a été encaissé.';
                } finally {
                    // Après un succès la page part, le bouton reste bloqué ; sinon on rend la main.
                    if (!parti) { this.envoi = false; }
                }
            },
            montrerErreurs(status, data) {
                const errs = data.errors || {};
                const premier = (k) => Array.isArray(errs[k]) ? errs[k][0] : (errs[k] || '');
                const champsMontant = ['montant', 'repartition', 'frais_category_id'];
                const champsMode = ['mode_paiement', 'reference_paiement'];
                let place = false;
                champsMode.forEach(k => { if (errs[k]) { this.erreurs[k] = premier(k); place = true; } });
                champsMontant.forEach(k => { if (errs[k]) { this.erreurs[k === 'repartition' ? 'montant' : k] = premier(k); place = true; } });
                if (errs.montant || errs.repartition) { this.etape = 3; }
                else if (errs.frais_category_id) { this.etape = 2; }
                if (!place) {
                    const autres = Object.keys(errs).map(premier).filter(Boolean);
                    this.erreurGlobale = data.message || autres[0]
                        || (status === 403 ? 'Ce mode de paiement n\'est pas autorisé pour votre compte.'
                            : 'Enregistrement refusé (' + status + '). Rien n\'a été encaissé.');
                }
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', message: this.erreurGlobale || 'Vérifiez la saisie.' },
                }));
            },
        };
    };
}
</script>
@endpush
