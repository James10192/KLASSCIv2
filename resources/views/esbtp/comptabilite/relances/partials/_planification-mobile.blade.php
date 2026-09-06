{{--
    Planification avancée des relances en pas-à-pas (shell mobile, profil comptable).
    Inclus par config.blade.php (segment « Planification ») et par
    planification-avancee.blade.php (écran mobile). Quatre écrans :
      1. Population (segmentation, niveaux, canaux)
      2. Aperçu de la population (POST preview-segmentation)
      3. Confirmation (récapitulatif, exécution immédiate ou programmée)
      4. Terminé
    Un seul composant Alpine (window.rlcPlanif) porte l'état ; la même fabrique
    sert au formulaire de bureau de planification-avancee.blade.php, qui lit sa
    configuration dans window.rlpCfgPartage (posé ici, une fois par page).

    Variables optionnelles à l'inclusion :
      $rlpRendreDom  (bool, défaut true)  false = ne poser que les styles/scripts partagés.

    Namespace CSS : rlpm-*.
--}}
@php
    $rlpRendreDom = $rlpRendreDom ?? true;
    $rlpPeutLancer = auth()->user()?->can('comptabilite.relances.send') ?? false;

    // Les stratégies acceptées par planifierRelancesAvancees() (validation in:…).
    // Les bornes numériques de chaque segment vivent dans NotificationService :
    // on ne les recopie pas ici, on décrit seulement la découpe.
    $rlpSegmentations = [
        ['value' => 'auto',                'label' => 'Automatique',            'desc' => 'Combine dette et retard en trois priorités (recommandé)'],
        ['value' => 'niveau_retard',       'label' => 'Par niveau de retard',   'desc' => 'Retard léger, moyen ou sévère'],
        ['value' => 'montant_dette',       'label' => 'Par montant de dette',   'desc' => 'Dette faible, moyenne ou élevée'],
        ['value' => 'historique_paiement', 'label' => 'Par historique',         'desc' => 'Bon payeur, irrégulier, mauvais payeur'],
        ['value' => 'classe',              'label' => 'Par classe',             'desc' => 'Un segment par classe'],
    ];
    $rlpCanaux = [
        ['value' => 'email',    'label' => 'E-mail',   'desc' => 'Tous niveaux'],
        ['value' => 'sms',      'label' => 'SMS',      'desc' => 'Privilégié dès le 2e niveau'],
        ['value' => 'courrier', 'label' => 'Courrier', 'desc' => 'Privilégié au 3e niveau'],
    ];
    // Libellés des segments renvoyés par le service (les segments « par classe »
    // portent le nom de la classe et passent tels quels).
    $rlpSegmentLabels = [
        'priorite_haute' => 'Priorité haute', 'priorite_moyenne' => 'Priorité moyenne', 'priorite_faible' => 'Priorité faible',
        'retard_leger' => 'Retard léger', 'retard_moyen' => 'Retard moyen', 'retard_severe' => 'Retard sévère',
        'dette_faible' => 'Dette faible', 'dette_moyenne' => 'Dette moyenne', 'dette_elevee' => 'Dette élevée',
        'bon_payeur' => 'Bon payeur', 'payeur_irregulier' => 'Payeur irrégulier', 'mauvais_payeur' => 'Mauvais payeur',
    ];
    // Bornes de la validation serveur (niveau_max 1..5) ; trois niveaux par défaut,
    // comme les trois délais de la configuration des relances.
    $rlpNiveauxMax = range(1, 5);

    $rlpCfg = [
        'urls' => [
            'preview'   => route('esbtp.comptabilite.relances.preview.segmentation'),
            'planifier' => route('esbtp.comptabilite.relances.planifier.avancees'),
            'index'     => route('esbtp.comptabilite.relances.index'),
        ],
        'csrf' => csrf_token(),
        'peutLancer' => $rlpPeutLancer,
        'segmentations' => $rlpSegmentations,
        'canaux' => $rlpCanaux,
        'canauxDefaut' => ['email', 'sms'],
        'segmentLabels' => $rlpSegmentLabels,
        'niveauxMax' => $rlpNiveauxMax,
        'niveauDefaut' => 3,
        'aujourdhui' => now()->toDateString(),
    ];
@endphp

@if($rlpRendreDom)
<div class="rlpm" x-data="rlcPlanif({{ \Illuminate\Support\Js::from($rlpCfg) }})">
    <div class="m-step" aria-hidden="true">
        <i x-bind:class="etape >= 1 ? 'on' : ''"></i>
        <i x-bind:class="etape >= 2 ? 'on' : ''"></i>
        <i x-bind:class="etape >= 3 ? 'on' : ''"></i>
    </div>

    <div class="rlpm-err" x-show="erreur" x-cloak x-text="erreur" role="alert"></div>

    {{-- Étape 1 : la population --}}
    <div class="rlpm-etape" x-show="etape === 1" x-cloak>
        <div class="rlpm-title">1 · Qui relancer ?</div>

        <div class="rlpm-block">
            <div class="rlpm-block-title">Découpage de la population</div>
            <div class="m-opt">
                @foreach($rlpSegmentations as $rlpS)
                    <label x-bind:class="form.segmentation === '{{ $rlpS['value'] }}' ? 'on' : ''">
                        <span class="rd" aria-hidden="true"></span>
                        <span><b>{{ $rlpS['label'] }}</b><span>{{ $rlpS['desc'] }}</span></span>
                        <input type="radio" name="rlpm_segmentation" value="{{ $rlpS['value'] }}" x-model="form.segmentation">
                    </label>
                @endforeach
            </div>
        </div>

        <div class="rlpm-block">
            <div class="rlpm-block-title">Niveaux de relance au maximum</div>
            <div class="rlpm-niv" role="radiogroup" aria-label="Nombre maximum de niveaux">
                @foreach($rlpNiveauxMax as $rlpN)
                    <button type="button" role="radio"
                            x-bind:aria-checked="form.niveau_max === {{ $rlpN }} ? 'true' : 'false'"
                            x-bind:class="form.niveau_max === {{ $rlpN }} ? 'on' : ''"
                            x-on:click="form.niveau_max = {{ $rlpN }}">{{ $rlpN }}</button>
                @endforeach
            </div>
            <span class="rlpm-hint">Un étudiant déjà relancé au dernier niveau n'est pas relancé à nouveau.</span>
        </div>

        <div class="rlpm-block">
            <div class="rlpm-block-title">Canaux autorisés</div>
            <div class="m-opt">
                @foreach($rlpCanaux as $rlpC)
                    <label x-bind:class="form.types_relance.includes('{{ $rlpC['value'] }}') ? 'on' : ''">
                        <span class="rlpm-ck" aria-hidden="true"><x-m.icon name="check" /></span>
                        <span><b>{{ $rlpC['label'] }}</b><span>{{ $rlpC['desc'] }}</span></span>
                        <input type="checkbox" value="{{ $rlpC['value'] }}" x-model="form.types_relance">
                    </label>
                @endforeach
            </div>
        </div>

        <button type="button" class="m-btn p" x-bind:disabled="chargement || form.types_relance.length === 0" x-on:click="chargerApercu(true)">
            <span x-show="!chargement"><x-m.icon name="users" /></span>
            <span x-text="chargement ? 'Calcul de la population…' : 'Voir la population concernée'"></span>
        </button>
    </div>

    {{-- Étape 2 : aperçu de la population --}}
    <div class="rlpm-etape" x-show="etape === 2" x-cloak>
        <div class="rlpm-title">2 · Population concernée</div>

        <div class="m-skel" x-show="chargement"><i></i><i></i><i></i></div>

        <template x-if="!chargement && segments">
            <div class="rlpm-etape">
                <section class="m-hero rlpm-hero">
                    <span class="k" x-text="libelleSegmentation"></span>
                    <span class="v"><span x-text="totalEtudiants"></span><small x-text="(totalEtudiants > 1 ? 'étudiants' : 'étudiant') + ' · ' + formatMoney(totalDette) + ' FCFA'"></small></span>
                    <div class="row">
                        <span class="pill" x-text="segmentsListe.length + (segmentsListe.length > 1 ? ' segments' : ' segment')"></span>
                        <span class="pill" x-text="'jusqu\'à ' + form.niveau_max + (form.niveau_max > 1 ? ' niveaux' : ' niveau')"></span>
                    </div>
                </section>

                <div class="m-empty" x-show="totalEtudiants === 0">
                    <x-m.icon name="check" />
                    <b>Personne à relancer</b>
                    <span>Aucun étudiant ne remplit les critères de ce découpage aujourd'hui.</span>
                </div>

                <div class="m-list one" x-show="totalEtudiants > 0">
                    <template x-for="s in segmentsListe" x-bind:key="s.key">
                        <div class="m-row rlpm-seg" x-bind:class="s.nombre === 0 ? 'rlpm-seg--vide' : ''">
                            <div class="av" aria-hidden="true" x-text="s.nombre"></div>
                            <div class="tt">
                                <b x-text="s.label"></b>
                                <span x-text="s.exemples.length ? s.exemples.join(', ') + (s.nombre > s.exemples.length ? '…' : '') : 'Aucun étudiant'"></span>
                            </div>
                            <div class="tr">
                                <span class="amt" x-text="formatMoney(s.dette) + ' FCFA'"></span>
                                <span class="m-chip" x-bind:class="s.nombre ? 'info' : 'mute'" x-text="s.nombre + (s.nombre > 1 ? ' étudiants' : ' étudiant')"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <div class="rlpm-acts">
            <button type="button" class="m-btn g" x-on:click="etape = 1"><x-m.icon name="chl" /> Modifier</button>
            <button type="button" class="m-btn p" x-bind:disabled="chargement || !peutContinuer" x-on:click="versConfirmation()">
                Continuer <x-m.icon name="chr" />
            </button>
        </div>
    </div>

    {{-- Étape 3 : confirmation --}}
    <div class="rlpm-etape" x-show="etape === 3" x-cloak>
        <div class="rlpm-title">3 · Confirmer la planification</div>

        <div class="m-recu rlpm-recap">
            <dl>
                <dt>Découpage</dt><dd x-text="libelleSegmentation"></dd>
                <dt>Niveaux</dt><dd x-text="'jusqu\'au niveau ' + form.niveau_max"></dd>
                <dt>Canaux</dt><dd x-text="libelleCanaux"></dd>
                <dt>Segments</dt><dd x-text="segmentsListe.length"></dd>
            </dl>
            <div class="tot">
                <span>Étudiants ciblés</span>
                <span x-text="totalEtudiants + ' · ' + formatMoney(totalDette) + ' FCFA'"></span>
            </div>
        </div>

        <div class="rlpm-block">
            <div class="rlpm-block-title">Quand ?</div>
            <div class="m-opt">
                <label x-bind:class="form.execution_type === 'immediate' ? 'on' : ''">
                    <span class="rd" aria-hidden="true"></span>
                    <span><b>Maintenant</b><span>Les relances sont créées tout de suite</span></span>
                    <input type="radio" name="rlpm_execution" value="immediate" x-model="form.execution_type">
                </label>
                <label x-bind:class="form.execution_type === 'programmee' ? 'on' : ''">
                    <span class="rd" aria-hidden="true"></span>
                    <span><b>À une date</b><span>La planification part le jour choisi</span></span>
                    <input type="radio" name="rlpm_execution" value="programmee" x-model="form.execution_type">
                </label>
            </div>
            <div class="m-field" x-show="form.execution_type === 'programmee'" x-cloak>
                <label for="rlpm-date">Date d'exécution</label>
                <input type="date" class="m-in" id="rlpm-date" x-model="form.date_execution" min="{{ $rlpCfg['aujourdhui'] }}">
            </div>
        </div>

        <div class="rlpm-acts">
            <button type="button" class="m-btn g" x-on:click="etape = 2"><x-m.icon name="chl" /> Retour</button>
            @if($rlpPeutLancer)
                <button type="button" class="m-btn p" x-bind:disabled="lancement || !formValide" x-on:click="lancer()">
                    <span x-show="!lancement"><x-m.icon name="check" /></span>
                    <span x-text="lancement ? 'Lancement…' : 'Lancer la planification'"></span>
                </button>
            @endif
        </div>
        @unless($rlpPeutLancer)
            <span class="rlpm-hint"><x-m.icon name="lock" /> Le lancement demande le droit d'envoyer des relances.</span>
        @endunless
    </div>

    {{-- Étape 4 : terminé --}}
    <div class="rlpm-etape" x-show="etape === 4" x-cloak>
        <div class="m-empty rlpm-done">
            <x-m.icon name="check" />
            <b>Planification lancée</b>
            <span x-text="resultat"></span>
        </div>
        <a href="{{ $rlpCfg['urls']['index'] }}" class="m-btn p"><x-m.icon name="bell" /> Voir les relances</a>
        <button type="button" class="m-btn g" x-on:click="reset()">Nouvelle planification</button>
    </div>
</div>
@endif

@once
@push('styles')
<style>
/* ── Planification avancée (mobile) — namespace rlpm-* ──────────────────── */
.rlpm { display: grid; gap: 14px; font-family: var(--m-font); }
.rlpm-etape { display: grid; gap: 14px; }
.rlpm-title { font-size: 16px; font-weight: 800; color: #0f172a; letter-spacing: -.01em; }
.rlpm-block { display: grid; gap: 8px; }
.rlpm-block-title { font-size: 12px; font-weight: 700; color: #475569; letter-spacing: .04em; text-transform: uppercase; }
.rlpm-hint { font-size: 12.5px; color: #64748b; display: flex; gap: 6px; align-items: center; line-height: 1.45; }
.rlpm-hint svg { width: 16px; height: 16px; flex: 0 0 16px; }
.rlpm-err { background: #fdecea; color: #a12016; border-radius: 12px; padding: 10px 12px; font-size: 13px; font-weight: 600; line-height: 1.4; }
.rlpm .m-opt label { position: relative; }
.rlpm-ck { width: 22px; height: 22px; border-radius: 7px; border: 2px solid #cbd5e1; display: grid; place-items: center; color: transparent; background: #fff; }
.rlpm-ck svg { width: 14px; height: 14px; stroke-width: 3; }
.rlpm .m-opt label.on .rlpm-ck { border-color: #0453cb; background: #0453cb; color: #fff; }
.rlpm-niv { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.rlpm-niv button { height: 44px; border-radius: 12px; border: 1.5px solid #e6eaf2; background: #fff; font: inherit; font-size: 16px; font-weight: 700; color: #475569; cursor: pointer; -webkit-tap-highlight-color: transparent; transition: transform 120ms ease, background 120ms ease; }
.rlpm-niv button.on { background: #0453cb; border-color: #0453cb; color: #fff; }
.rlpm-niv button:active { transform: scale(.96); }
.rlpm-hero .v { font-size: 34px; }
.rlpm-seg .av { background: rgba(4,83,203,.1); color: #0453cb; font-weight: 800; font-size: 15px; }
.rlpm-seg .tt span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.rlpm-seg--vide { opacity: .55; }
.rlpm-recap dl { grid-template-columns: auto 1fr; }
.rlpm-recap dd { text-align: right; }
.rlpm-acts { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.rlpm-done svg { color: #0f6b4c; }
</style>
@endpush

@push('scripts')
<script>
window.rlpCfgPartage = {{ \Illuminate\Support\Js::from($rlpCfg) }};
if (typeof window.rlcPlanif !== 'function') {
window.rlcPlanif = function (cfg) {
    return {
        cfg: cfg,
        etape: 1,
        form: {
            segmentation: 'auto',
            niveau_max: cfg.niveauDefaut,
            types_relance: (cfg.canauxDefaut || []).slice(),
            execution_type: 'immediate',
            date_execution: '',
        },
        segments: null,
        chargement: false,
        lancement: false,
        erreur: '',
        resultat: '',
        confirmOpen: false,   /* fenêtre de confirmation (bureau) */
        toast: null,
        toastType: 'info',

        get segmentsListe() {
            if (!this.segments) { return []; }
            return Object.keys(this.segments).map((k) => {
                var s = this.segments[k] || {};
                var ex = s.exemple_etudiants || {};
                return {
                    key: k,
                    label: this.libelleSegment(k),
                    nombre: parseInt(s.nombre_etudiants, 10) || 0,
                    dette: parseFloat(s.total_dette) || 0,
                    exemples: Array.isArray(ex) ? ex : Object.values(ex),
                };
            }).sort((a, b) => b.nombre - a.nombre);
        },
        get totalEtudiants() { return this.segmentsListe.reduce((t, s) => t + s.nombre, 0); },
        get totalDette() { return this.segmentsListe.reduce((t, s) => t + s.dette, 0); },
        get peutContinuer() { return !!this.segments && this.totalEtudiants > 0; },
        get formValide() {
            return this.form.types_relance.length > 0
                && (this.form.execution_type === 'immediate' || !!this.form.date_execution);
        },
        get libelleSegmentation() {
            var s = (this.cfg.segmentations || []).find((x) => x.value === this.form.segmentation);
            return s ? s.label : this.form.segmentation;
        },
        get libelleCanaux() {
            var labels = {};
            (this.cfg.canaux || []).forEach((c) => { labels[c.value] = c.label; });
            return this.form.types_relance.map((v) => labels[v] || v).join(', ') || '—';
        },

        libelleSegment(k) {
            if (this.cfg.segmentLabels && this.cfg.segmentLabels[k]) { return this.cfg.segmentLabels[k]; }
            var s = String(k).replace(/_/g, ' ');
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)); },

        toggleCanal(v) {
            var i = this.form.types_relance.indexOf(v);
            if (i === -1) { this.form.types_relance.push(v); } else { this.form.types_relance.splice(i, 1); }
        },

        estShellMobile() {
            return document.body.classList.contains('has-m-shell')
                && window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        },

        notifier(message, type) {
            type = type || 'info';
            if (this.estShellMobile()) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
                return;
            }
            this.toast = message;
            this.toastType = type;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast = null; }, 3800);
        },

        async requete(url, body) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.cfg.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
        },

        async lireErreur(reponse) {
            var data = null;
            try { data = await reponse.json(); } catch (e) { data = null; }
            if (data && data.message) { return data.message; }
            if (reponse.status === 403) { return 'Vous n\'avez pas le droit d\'effectuer cette action.'; }
            if (reponse.status === 429) { return 'Trop de demandes en peu de temps : réessayez dans une minute.'; }
            return 'Erreur ' + reponse.status + ' : la demande n\'a pas abouti.';
        },

        /* Aperçu serveur de la population ; avancer=true passe à l'étape 2 (mobile). */
        async chargerApercu(avancer) {
            if (this.chargement) { return false; }
            this.erreur = '';
            this.chargement = true;
            this.segments = null;
            if (avancer) { this.etape = 2; }
            try {
                var reponse = await this.requete(this.cfg.urls.preview, { type_segmentation: this.form.segmentation });
                if (!reponse.ok) {
                    this.erreur = await this.lireErreur(reponse);
                    return false;
                }
                var data = await reponse.json();
                if (!data || data.success === false) {
                    this.erreur = (data && data.message) || 'Aperçu indisponible.';
                    return false;
                }
                this.segments = data.segments || {};
                return true;
            } catch (e) {
                this.erreur = 'Connexion impossible : aperçu indisponible.';
                return false;
            } finally {
                this.chargement = false;
            }
        },

        versConfirmation() {
            if (!this.peutContinuer) { return; }
            this.erreur = '';
            this.etape = 3;
        },

        /* Bureau : la confirmation s'appuie sur l'aperçu réel, jamais sur une estimation. */
        async ouvrirConfirmation() {
            if (!this.form.types_relance.length) {
                this.erreur = 'Choisissez au moins un canal.';
                return;
            }
            if (!this.segments) {
                var ok = await this.chargerApercu(false);
                if (!ok) { return; }
            }
            this.confirmOpen = true;
        },

        async lancer() {
            if (!this.cfg.peutLancer || this.lancement) { return false; }
            if (!this.form.types_relance.length) { this.erreur = 'Choisissez au moins un canal.'; return false; }
            if (this.form.execution_type === 'programmee' && !this.form.date_execution) {
                this.erreur = 'Choisissez la date d\'exécution.';
                return false;
            }
            this.erreur = '';
            this.lancement = true;
            try {
                var reponse = await this.requete(this.cfg.urls.planifier, {
                    segmentation: this.form.segmentation,
                    niveau_max: this.form.niveau_max,
                    types_relance: this.form.types_relance,
                    date_execution: this.form.execution_type === 'programmee' ? this.form.date_execution : null,
                });
                if (!reponse.ok) {
                    this.erreur = await this.lireErreur(reponse);
                    return false;
                }
                var data = await reponse.json();
                if (!data || data.success === false) {
                    this.erreur = (data && data.message) || 'La planification a été refusée.';
                    return false;
                }
                this.resultat = data.message || 'Les relances ont été planifiées.';
                this.confirmOpen = false;
                this.etape = 4;
                this.notifier(this.resultat, 'success');
                return true;
            } catch (e) {
                this.erreur = 'Connexion impossible : la planification n\'a pas été lancée.';
                return false;
            } finally {
                this.lancement = false;
            }
        },

        reset() {
            this.etape = 1;
            this.segments = null;
            this.erreur = '';
            this.resultat = '';
            this.confirmOpen = false;
            this.form.execution_type = 'immediate';
            this.form.date_execution = '';
        },
    };
};
}
</script>
@endpush
@endonce
