{{--
    Liste des etudiants — rendu MOBILE (shell m-*). Inclus par
    esbtp/etudiants/index.blade.php quand le shell mobile est actif ; l'en-tete,
    les filtres et la liste de bureau portent alors .m-only-desktop.

    Les lignes viennent du controleur (LigneEtudiantMobile) : premiere tranche
    dans la page, suivantes en JSON (mode=mobile), meme requete filtree.

    Namespace CSS propre a l'ecran : eim- (etudiants-index-mobile).
--}}
@php
    $eimUser = auth()->user();
    $eimListe = $listeMobile ?? ['items' => [], 'has_more' => false, 'next_page' => 2, 'total' => 0];
    $eimActions = array_values(array_filter([
        ($eimUser?->can('inscriptions.view')) ? ['type' => 'lien', 'url' => route('esbtp.reinscription.index'), 'icone' => 'refresh', 'titre' => 'Réinscriptions', 'detail' => 'Étudiants à réinscrire cette année'] : null,
        ($eimUser?->can('inscriptions.create')) ? ['type' => 'modale', 'cible' => 'bulkReinscriptionModal', 'icone' => 'users', 'titre' => 'Réinscription groupée', 'detail' => 'Plusieurs étudiants en une fois'] : null,
        ['type' => 'modale', 'cible' => 'exportModal', 'icone' => 'dl', 'titre' => 'Exporter', 'detail' => 'PDF ou Excel de la liste'],
        ($eimUser?->can('trash.view')) ? ['type' => 'lien', 'url' => route('esbtp.trash.index'), 'icone' => 'inbox', 'titre' => 'Corbeille', 'detail' => 'Étudiants et inscriptions supprimés'] : null,
    ]));
    // Filtres poses par l'URL que cet ecran ne sait pas regler (classe, filiere...) :
    // gardes a chaque rechargement et annonces, au lieu d'etre perdus en silence.
    $eimAutres = collect(request()->query())
        ->except(['search', 'inscrit_annee_courante', 'page', 'mode', 'sort', 'order', 'per_page', 'open_bulk'])
        ->filter(fn ($v) => is_scalar($v) && (string) $v !== '')
        ->map(fn ($v) => (string) $v)
        ->all();
    $eimCfg = [
        'autres' => (object) $eimAutres,
        'segmentsActifs' => (bool) ($eimListe['segments'] ?? true),
        'filtres' => [
            'search' => (string) request('search', ''),
            'inscrit_annee_courante' => (string) request('inscrit_annee_courante', ''),
        ],
        'initial' => $eimListe,
        'url' => route('esbtp.etudiants.index'),
    ];
@endphp

<div class="m-only-mobile m-screen eim-screen" x-data="eimListe({{ \Illuminate\Support\Js::from($eimCfg) }})">

    <x-m.appbar title="Étudiants"
                :sub="$eimListe['total'] . ' étudiant' . ($eimListe['total'] > 1 ? 's' : '')"
                action="more"
                action-label="Autres actions"
                x-on:click="ouvrir('eim-actions')">
        @can('inscriptions.create')
            <a href="{{ route('esbtp.inscriptions.create') }}" class="m-ib" aria-label="Ajouter un étudiant">
                <x-m.icon name="plus" />
            </a>
        @endcan
    </x-m.appbar>

    <div class="m-body" x-ref="corps">

        <div class="m-sticky eim-sticky">
            <label class="m-search">
                <x-m.icon name="search" />
                <input type="search"
                       inputmode="search"
                       autocomplete="off"
                       placeholder="Nom, matricule, téléphone…"
                       aria-label="Rechercher un étudiant"
                       x-model="filtres.search"
                       x-on:input.debounce.350ms="recharger()">
            </label>
            <div class="eim-autres" x-show="nbAutres() > 0" x-cloak>
                <span x-text="nbAutres() + (nbAutres() > 1 ? ' filtres actifs' : ' filtre actif')"></span>
                <button type="button" x-on:click="effacerAutres()">Effacer</button>
            </div>
            <div class="m-seg eim-seg" role="tablist" aria-label="Filtrer par inscription" x-show="segmentsActifs">
                <template x-for="s in segments" x-bind:key="s.valeur">
                    <button type="button" role="tab"
                            x-bind:aria-selected="filtres.inscrit_annee_courante === s.valeur ? 'true' : 'false'"
                            x-bind:class="filtres.inscrit_annee_courante === s.valeur ? 'on' : ''"
                            x-on:click="segment(s.valeur)"
                            x-text="s.libelle"></button>
                </template>
            </div>
        </div>

        <div class="eim-err" role="alert" x-show="erreur" x-cloak>
            <x-m.icon name="alert" />
            <span x-text="erreur"></span>
            <button type="button" class="eim-err-retry" x-on:click="recharger()">Réessayer</button>
        </div>

        <div class="m-skel" x-show="chargement && items.length === 0" x-cloak aria-hidden="true">
            <i></i><i></i><i></i><i></i><i></i>
        </div>

        <div class="m-list" x-show="items.length > 0" x-bind:class="chargement ? 'is-loading' : ''" aria-live="polite">
            <template x-for="e in items" x-bind:key="e.id">
                <div class="eim-item">
                <a x-bind:href="e.url" class="m-row eim-row">
                    <div class="av eim-av" aria-hidden="true">
                        <template x-if="e.photo"><img x-bind:src="e.photo" alt="" loading="lazy"></template>
                        <template x-if="!e.photo"><span x-text="e.initiales"></span></template>
                    </div>
                    <div class="tt">
                        <b><span x-text="e.nom"></span><i class="fas fa-universal-access eim-a11y" x-show="e.a11y" x-bind:title="e.a11y" aria-label="Aménagements"></i></b>
                        <span class="eim-sous">
                            <span x-text="classeLigne(e)"></span>
                            <span class="eim-lmd" x-show="e.lmd">LMD</span>
                        </span>
                    </div>
                    <div class="tr">
                        <span class="m-chip" x-bind:class="ton(e)" x-text="e.actif ? e.etat_libelle : 'Inactif'"></span>
                        <span class="eim-mat" x-text="e.matricule"></span>
                    </div>
                </a>
                <a class="eim-valider" x-show="e.a_valider_url" x-bind:href="e.a_valider_url">
                    <x-m.icon name="check" /> Inscription à valider
                </a>
                </div>
            </template>
        </div>

        <template x-if="!chargement && items.length === 0 && !erreur">
            <div>
                <template x-if="filtresActifs()">
                    <x-m.empty icon="search" title="Aucun étudiant ne correspond" text="Modifiez la recherche ou choisissez « Tous ».">
                        <button type="button" class="m-btn g" x-on:click="toutEffacer()">Effacer la recherche</button>
                    </x-m.empty>
                </template>
                <template x-if="!filtresActifs()">
                    <x-m.empty icon="users" title="Aucun étudiant" text="Le premier étudiant inscrit apparaîtra ici." />
                </template>
            </div>
        </template>

        <div class="eim-suite" x-show="hasMore" x-cloak>
            <button type="button" class="m-btn g" x-on:click="suite()" x-bind:disabled="chargementSuite">
                <span x-show="!chargementSuite">Voir plus</span>
                <span x-show="chargementSuite" x-cloak>Chargement…</span>
            </button>
        </div>
        <div x-ref="sentinelle" class="eim-sentinelle" aria-hidden="true"></div>

        <p class="eim-fin" x-show="!hasMore && items.length > 0" x-cloak
           x-text="items.length + ' sur ' + total + (total > 1 ? ' étudiants' : ' étudiant')"></p>
    </div>

    <x-m.sheet id="eim-actions" title="Étudiants" sub="Autres actions">
        <div class="m-menu">
            @foreach($eimActions as $eimA)
                @if($eimA['type'] === 'lien')
                    <a href="{{ $eimA['url'] }}">
                        <x-m.icon :name="$eimA['icone']" />
                        <span class="eim-menu-tt">{{ $eimA['titre'] }}<small>{{ $eimA['detail'] }}</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </a>
                @else
                    <button type="button" x-on:click="ouvrirModale('{{ $eimA['cible'] }}')">
                        <x-m.icon :name="$eimA['icone']" />
                        <span class="eim-menu-tt">{{ $eimA['titre'] }}<small>{{ $eimA['detail'] }}</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                @endif
            @endforeach
        </div>
    </x-m.sheet>
</div>

@push('styles')
<style>
    /* Liste des etudiants mobile — namespace eim- */
    .eim-screen { font-family: var(--m-font); }
    .eim-seg { margin-top: 8px; }
    .eim-seg button { font-size: 12.5px; padding-left: 4px; padding-right: 4px; white-space: nowrap; }
    .eim-av { overflow: hidden; }
    .eim-av img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 20%; }
    .eim-row .tt { min-width: 0; }
    .eim-sous { display: flex; align-items: center; gap: 6px; min-width: 0; }
    .eim-sous > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .eim-lmd { flex-shrink: 0; font-size: 10px; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.1); border-radius: 4px; padding: 0 4px; line-height: 16px; }
    .eim-mat { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11px; color: #94a3b8; white-space: nowrap; }
    .eim-err { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; background: #fdecea; color: #a12016; border: 1px solid #f5c6c0; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; }
    .eim-err svg { width: 20px; height: 20px; }
    .eim-err-retry { border: 0; background: transparent; color: #a12016; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 6px 0; }
    .eim-suite { display: grid; }
    .eim-sentinelle { height: 1px; }
    .eim-fin { margin: 0; text-align: center; font-size: 12px; color: #94a3b8; }
    .eim-menu-tt { display: grid; gap: 1px; min-width: 0; }
    .eim-autres { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; padding: 8px 12px; border-radius: 12px; background: rgba(4,83,203,.08); color: #0453cb; font-size: 13px; font-weight: 600; }
    .eim-autres button { border: 0; background: transparent; color: #0453cb; font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 4px 0; }
    .eim-a11y { color: #0453cb; font-size: .8em; margin-left: 6px; }
    /* Chaque ligne est enveloppee (pour porter son lien de validation) : le filet
       de separation du shell, pose sur .m-list > .m-row, passe sur l'enveloppe. */
    .eim-screen .m-list > .eim-item { border-bottom: 1px solid #f1f5f9; }
    .eim-screen .m-list > .eim-item:last-child { border-bottom: 0; }
    .eim-screen .eim-item > .m-row { border: 0; border-radius: 0; box-shadow: none; min-height: 60px; }
    .eim-valider { display: flex; align-items: center; gap: 6px; margin: -6px 0 0 64px; padding: 0 12px 10px 0; font-size: 12.5px; font-weight: 700; color: #b45309; text-decoration: none; }
    .eim-valider svg { width: 16px; height: 16px; }
    .eim-menu-tt small { font-size: 12px; color: #64748b; font-weight: 500; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.eimListe !== 'function') {
        window.eimListe = function (cfg) {
            var initial = cfg.initial || {};
            var filtresInit = Object.assign({ search: '', inscrit_annee_courante: '' }, cfg.filtres || {});
            var autresInit = Object.assign({}, cfg.autres || {});

            return {
                url: cfg.url,
                filtres: filtresInit,
                autres: autresInit,
                segmentsActifs: cfg.segmentsActifs !== false,
                segments: [
                    { valeur: '', libelle: 'Tous' },
                    { valeur: 'validee', libelle: 'Inscrits' },
                    { valeur: 'en_attente', libelle: 'En cours' },
                    { valeur: 'absente', libelle: 'Sans inscription' }
                ],
                items: initial.items || [],
                hasMore: !!initial.has_more,
                nextPage: initial.next_page || 2,
                total: initial.total || 0,
                chargement: false,
                chargementSuite: false,
                erreur: null,
                _seq: 0,
                _observer: null,
                _onPtr: null,

                init() {
                    var self = this;
                    if (this.$refs.corps) {
                        this._onPtr = function () { self.recharger(); };
                        this.$refs.corps.addEventListener('m-ptr:refresh', this._onPtr);
                    }
                    if ('IntersectionObserver' in window && this.$refs.sentinelle) {
                        this._observer = new IntersectionObserver(function (entries) {
                            if (entries.some(function (e) { return e.isIntersecting; })) { self.suite(); }
                        }, { rootMargin: '200px 0px' });
                        this._observer.observe(this.$refs.sentinelle);
                    }
                },
                destroy() {
                    if (this._observer) { this._observer.disconnect(); this._observer = null; }
                    if (this._onPtr && this.$refs.corps) { this.$refs.corps.removeEventListener('m-ptr:refresh', this._onPtr); this._onPtr = null; }
                },

                ouvrir(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
                },
                ouvrirModale(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                    var el = document.getElementById(id);
                    if (el && window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(el).show(); }
                },

                classeLigne(e) {
                    if (!e.classe) { return 'Aucune classe'; }
                    return e.annee ? e.classe + ' · ' + e.annee : e.classe;
                },
                ton(e) {
                    if (!e.actif) { return 'bad'; }
                    return { inscrit: 'ok', en_cours: 'warn', aucune: 'mute' }[e.etat] || 'mute';
                },
                majSousTitre() {
                    var small = this.$root.querySelector('.m-appbar .t small');
                    if (small) { small.textContent = this.total + ' étudiant' + (this.total > 1 ? 's' : ''); }
                },

                nbAutres() {
                    return Object.keys(this.autres).length;
                },
                effacerAutres() {
                    this.autres = {};
                    this.recharger();
                },
                filtresActifs() {
                    return this.filtres.search !== '' || this.filtres.inscrit_annee_courante !== '' || this.nbAutres() > 0;
                },
                toutEffacer() {
                    this.filtres = { search: '', inscrit_annee_courante: '' };
                    this.autres = {};
                    this.recharger();
                },
                segment(valeur) {
                    if (this.filtres.inscrit_annee_courante === valeur) { return; }
                    this.filtres.inscrit_annee_courante = valeur;
                    this.recharger();
                },
                parametres() {
                    var p = new URLSearchParams();
                    var a = this.autres;
                    Object.keys(a).forEach(function (k) { p.set(k, a[k]); });
                    var f = this.filtres;
                    Object.keys(f).forEach(function (k) { if (f[k] !== '' && f[k] !== null) { p.set(k, f[k]); } });
                    return p;
                },

                async charger(page, remplacer) {
                    var seq = ++this._seq;
                    if (remplacer) { this.chargement = true; } else { this.chargementSuite = true; }
                    this.erreur = null;
                    try {
                        var p = this.parametres();
                        p.set('mode', 'mobile');
                        p.set('page', String(page));
                        var res = await fetch(this.url + '?' + p.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) { throw new Error('Impossible de charger les étudiants (' + res.status + ').'); }
                        var data = await res.json();
                        if (seq !== this._seq) { return; }
                        var nouveaux = data.items || [];
                        if (remplacer) {
                            this.items = nouveaux;
                        } else {
                            // Un etudiant cree pendant qu'on defile decale les tranches :
                            // on ne repete pas une fiche deja affichee.
                            var vus = {};
                            this.items.forEach(function (e) { vus[e.id] = true; });
                            this.items = this.items.concat(nouveaux.filter(function (e) { return !vus[e.id]; }));
                        }
                        this.hasMore = !!data.has_more;
                        this.nextPage = data.next_page || (page + 1);
                        this.total = data.total || 0;
                        this.majSousTitre();
                        if (window.history && window.history.replaceState) {
                            var q = this.parametres().toString();
                            window.history.replaceState(null, '', this.url + (q ? '?' + q : ''));
                        }
                    } catch (e) {
                        if (seq !== this._seq) { return; }
                        this.erreur = e.message || 'Impossible de charger les étudiants.';
                    } finally {
                        if (seq === this._seq) {
                            this.chargement = false;
                            this.chargementSuite = false;
                        }
                    }
                },
                recharger() { return this.charger(1, true); },
                suite() {
                    if (!this.hasMore || this.chargement || this.chargementSuite) { return; }
                    return this.charger(this.nextPage, false);
                }
            };
        };
    }
</script>
@endpush
