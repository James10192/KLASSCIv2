{{--
    Onglet « Assistant IA » de /esbtp/settings : clés des fournisseurs (chiffrées
    en base), modèle par défaut, essai réel. Tout passe en AJAX par
    AssistantReglagesController : aucun champ ne porte de name, ce formulaire
    englobant n'envoie donc jamais une clé en clair par le chemin générique.
--}}
@php
    $asrEtat = app(\App\Domain\Assistant\Reglages\ReglagesAssistant::class)->etat();
    $asrOptions = collect($asrEtat['modeles'])->mapWithKeys(fn ($m) => [
        $m['cle'] => $m['libelle'] . ($m['configure'] ? '' : ' — sans clé'),
    ])->all();
    $asrUrls = [
        'etat' => route('esbtp.settings.assistant.etat'),
        'cle' => route('esbtp.settings.assistant.cle'),
        'retirer' => url('esbtp/settings/assistant/cle'),
        'modele' => route('esbtp.settings.assistant.modele'),
        'tester' => route('esbtp.settings.assistant.tester'),
    ];
@endphp

<style>
    .asr-grid { display: grid; gap: 1rem; }
    .asr-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.35rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .asr-tete { display: flex; align-items: center; gap: .75rem; margin-bottom: .9rem; }
    .asr-icone { width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(135deg, #0453cb, #3b7ddb); }
    .asr-titre { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .asr-sous { font-size: .82rem; color: #64748b; margin: .1rem 0 0; }
    .asr-four { display: grid; grid-template-columns: minmax(150px, 200px) 1fr; gap: .75rem 1rem; align-items: center; padding: .8rem 0; border-top: 1px solid #f1f5f9; }
    .asr-four:first-of-type { border-top: none; }
    .asr-nom { font-weight: 600; color: #1e293b; font-size: .9rem; }
    .asr-reco { display: inline-block; margin-top: .2rem; font-size: .68rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.08); border-radius: 999px; padding: .1rem .5rem; }
    .asr-ligne { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
    .asr-etat { font-size: .76rem; font-weight: 600; border-radius: 999px; padding: .2rem .6rem; white-space: nowrap; }
    .asr-etat--ok { background: rgba(16,185,129,.1); color: #047857; }
    .asr-etat--non { background: #f1f5f9; color: #64748b; }
    .asr-champ { flex: 1 1 220px; min-width: 0; height: 38px; border: 1px solid #dbe3ef; border-radius: 10px; padding: 0 .75rem; font-size: .86rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .asr-champ:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .asr-btn { height: 38px; border-radius: 10px; padding: 0 .95rem; font-size: .84rem; font-weight: 600; border: 1px solid #dbe3ef; background: #fff; color: #334155; white-space: nowrap; transition: background .2s ease, border-color .2s ease; }
    .asr-btn:hover:not(:disabled) { background: #f1f5f9; }
    .asr-btn:disabled { opacity: .6; cursor: wait; }
    .asr-btn--principal { background: #0453cb; border-color: #0453cb; color: #fff; }
    .asr-btn--principal:hover:not(:disabled) { background: #033a8e; }
    .asr-btn--danger { color: #b91c1c; border-color: #fecaca; }
    .asr-btn--danger:hover:not(:disabled) { background: #fef2f2; }
    .asr-modele { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
    .asr-modele .au-select { flex: 1 1 260px; min-width: 0; }
    .asr-note { font-size: .78rem; color: #64748b; margin-top: .6rem; }
    .asr-resultats { margin-top: 1rem; display: grid; gap: .6rem; }
    .asr-res { border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem .9rem; display: grid; grid-template-columns: 1fr auto; gap: .35rem 1rem; }
    .asr-res-nom { font-weight: 700; color: #0f172a; font-size: .88rem; }
    .asr-res-id { font-size: .74rem; color: #64748b; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .asr-res-lignes { grid-column: 1 / -1; display: flex; gap: .5rem; flex-wrap: wrap; font-size: .8rem; }
    .asr-pastille { border-radius: 8px; padding: .2rem .55rem; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; }
    .asr-pastille--ok { background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.3); color: #047857; }
    .asr-pastille--ko { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.25); color: #b91c1c; }
    .asr-message { margin-top: .6rem; font-size: .82rem; }
    .asr-message--ok { color: #047857; }
    .asr-message--ko { color: #b91c1c; }
    @media (max-width: 640px) {
        .asr-four { grid-template-columns: 1fr; }
        .asr-res { grid-template-columns: 1fr; }
    }
</style>

<div class="asr-grid" x-data="asrReglages()" data-etat='@json($asrEtat)' data-urls='@json($asrUrls)'>
    <section class="asr-card">
        <div class="asr-tete">
            <div class="asr-icone"><i class="fas fa-key"></i></div>
            <div>
                <h3 class="asr-titre">Clés des fournisseurs</h3>
                <p class="asr-sous">Stockées chiffrées. Une clé saisie ici remplace celle du serveur ; elle n'est jamais réaffichée.</p>
            </div>
        </div>

        <template x-for="f in fournisseursTries()" :key="f.code">
            <div class="asr-four">
                <div>
                    <div class="asr-nom" x-text="libelleFournisseur(f.code)"></div>
                    <span class="asr-reco" x-show="f.code === 'openrouter'">Une clé, plusieurs modèles</span>
                </div>
                <div class="asr-ligne">
                    <span class="asr-etat" :class="f.source === 'aucune' ? 'asr-etat--non' : 'asr-etat--ok'"
                          x-text="f.source === 'aucune' ? 'Aucune clé' : ('Configurée ••••' + (f.fin || '') + (f.source === 'env' ? ' (serveur)' : ''))"></span>
                    <input type="password" class="asr-champ" autocomplete="off" spellcheck="false"
                           :placeholder="f.source === 'aucune' ? 'Coller la clé' : 'Remplacer la clé'"
                           x-model="saisies[f.code]" x-on:keydown.enter.prevent="poserCle(f.code)"
                           :aria-label="'Clé ' + libelleFournisseur(f.code)">
                    <button type="button" class="asr-btn asr-btn--principal" :disabled="occupe || !(saisies[f.code] || '').trim()" x-on:click="poserCle(f.code)">Enregistrer</button>
                    <button type="button" class="asr-btn asr-btn--danger" x-show="f.source === 'reglages'" x-cloak :disabled="occupe" x-on:click="retirerCle(f.code)">Retirer</button>
                </div>
            </div>
        </template>
        <div class="asr-message" :class="messageOk ? 'asr-message--ok' : 'asr-message--ko'" x-show="message" x-cloak x-text="message" role="status"></div>
    </section>

    <section class="asr-card">
        <div class="asr-tete">
            <div class="asr-icone"><i class="fas fa-microchip"></i></div>
            <div>
                <h3 class="asr-titre">Modèle par défaut</h3>
                <p class="asr-sous">Celui qu'utilise l'assistant. S'il tombe, les autres modèles munis d'une clé prennent le relais.</p>
            </div>
        </div>
        <div class="asr-modele">
            <x-au-select x-model="modele" :options="$asrOptions" :value="$asrEtat['modele_defaut']" icon="fa-robot" :searchable="false" :placeholder-is-first-option="false" />
            <button type="button" class="asr-btn asr-btn--principal" :disabled="occupe" x-on:click="choisirModele()">Enregistrer</button>
        </div>
        <p class="asr-note" x-show="etat.modele_effectif" x-cloak>
            Modèle réellement utilisé en ce moment : <strong x-text="libelleModele(etat.modele_effectif)"></strong>
        </p>
        <p class="asr-note" x-show="!etat.modele_effectif" x-cloak>Aucun modèle n'a de clé : l'assistant ne peut pas répondre.</p>
    </section>

    <section class="asr-card">
        <div class="asr-tete">
            <div class="asr-icone"><i class="fas fa-vial"></i></div>
            <div>
                <h3 class="asr-titre">Tester</h3>
                <p class="asr-sous">Envoie une question courte puis un appel d'outil fictif à chaque modèle configuré. Aucune donnée de l'école n'est transmise.</p>
            </div>
        </div>
        <button type="button" class="asr-btn asr-btn--principal" :disabled="teste" x-on:click="tester()">
            <span x-show="!teste"><i class="fas fa-play me-1"></i>Lancer le test</span>
            <span x-show="teste" x-cloak><i class="fas fa-spinner fa-spin me-1"></i>Test en cours…</span>
        </button>
        <div class="asr-message asr-message--ko" x-show="messageTest" x-cloak x-text="messageTest" role="status"></div>
        <div class="asr-resultats" x-show="resultats.length" x-cloak>
            <template x-for="r in resultats" :key="r.modele">
                <div class="asr-res">
                    <div>
                        <div class="asr-res-nom" x-text="r.libelle"></div>
                        <div class="asr-res-id" x-text="r.identifiant"></div>
                    </div>
                    <div class="asr-res-lignes">
                        <template x-if="!r.configure">
                            <span class="asr-pastille asr-pastille--ko">Aucune clé</span>
                        </template>
                        <template x-if="r.texte">
                            <span class="asr-pastille" :class="r.texte.ok ? 'asr-pastille--ok' : 'asr-pastille--ko'"
                                  x-text="r.texte.ok ? ('Répond en ' + r.texte.ms + ' ms') : ('Réponse : ' + libelleErreur(r.texte.erreur))"></span>
                        </template>
                        <template x-if="r.outils">
                            <span class="asr-pastille" :class="r.outils.ok ? 'asr-pastille--ok' : 'asr-pastille--ko'"
                                  x-text="r.outils.ok ? ('Appelle les outils (' + r.outils.ms + ' ms)') : ('Outils : ' + (r.outils.erreur ? libelleErreur(r.outils.erreur) : 'aucun appel'))"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </section>
</div>

<script>
    if (typeof window.asrReglages !== 'function') {
        window.asrReglages = function () {
            var ORDRE = ['openrouter', 'anthropic', 'openai', 'gemini', 'mistral', 'deepseek'];
            var FOURNISSEURS = { openrouter: 'OpenRouter', anthropic: 'Anthropic (Claude)', openai: 'OpenAI', gemini: 'Google Gemini', mistral: 'Mistral', deepseek: 'DeepSeek' };
            var ERREURS = {
                http_401: 'clé refusée', http_402: 'crédit épuisé', http_403: 'accès refusé par le fournisseur',
                http_404: 'modèle introuvable', limite_debit: 'limite de débit atteinte', reseau: 'fournisseur injoignable',
                non_configure: 'aucune clé', delai: 'délai dépassé'
            };
            return {
                etat: {}, urls: {}, saisies: {}, modele: '', occupe: false, teste: false,
                resultats: [], message: '', messageOk: true, messageTest: '',
                init: function () {
                    this.etat = JSON.parse(this.$root.dataset.etat || '{}');
                    this.urls = JSON.parse(this.$root.dataset.urls || '{}');
                    this.modele = this.etat.modele_defaut || '';
                },
                fournisseursTries: function () {
                    var f = this.etat.fournisseurs || {};
                    return Object.keys(f)
                        .sort(function (a, b) { return (ORDRE.indexOf(a) + 99) % 99 - (ORDRE.indexOf(b) + 99) % 99; })
                        .map(function (code) { return Object.assign({ code: code }, f[code]); });
                },
                libelleFournisseur: function (code) { return FOURNISSEURS[code] || code; },
                libelleModele: function (cle) {
                    var m = (this.etat.modeles || []).find(function (x) { return x.cle === cle; });
                    return m ? m.libelle : cle;
                },
                libelleErreur: function (code) {
                    if (!code) { return 'erreur'; }
                    if (ERREURS[code]) { return ERREURS[code]; }
                    return /^http_5/.test(code) ? 'fournisseur indisponible' : code;
                },
                requete: function (methode, url, corps) {
                    return fetch(url, {
                        method: methode,
                        headers: {
                            'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: corps ? JSON.stringify(corps) : undefined
                    }).then(function (r) {
                        return r.json().catch(function () { return {}; }).then(function (d) {
                            if (!r.ok) { throw new Error(d.message || ('Erreur ' + r.status)); }
                            return d;
                        });
                    });
                },
                agir: function (promesse, apres) {
                    var self = this;
                    this.occupe = true;
                    this.message = '';
                    return promesse.then(function (d) {
                        if (d.etat) { self.etat = d.etat; }
                        self.messageOk = true;
                        self.message = d.message || 'Enregistré.';
                        if (apres) { apres(); }
                    }).catch(function (e) {
                        self.messageOk = false;
                        self.message = e.message;
                    }).finally(function () { self.occupe = false; });
                },
                poserCle: function (code) {
                    var cle = (this.saisies[code] || '').trim();
                    if (!cle) { return; }
                    var self = this;
                    this.agir(this.requete('PUT', this.urls.cle, { fournisseur: code, cle: cle }), function () { self.saisies[code] = ''; });
                },
                retirerCle: function (code) {
                    if (!window.confirm('Retirer la clé ' + this.libelleFournisseur(code) + ' ?')) { return; }
                    this.agir(this.requete('DELETE', this.urls.retirer + '/' + encodeURIComponent(code)));
                },
                choisirModele: function () {
                    if (!this.modele) { return; }
                    this.agir(this.requete('PUT', this.urls.modele, { modele: this.modele }));
                },
                tester: function () {
                    var self = this;
                    this.teste = true;
                    this.resultats = [];
                    this.messageTest = '';
                    this.requete('POST', this.urls.tester, {}).then(function (d) {
                        self.resultats = d.resultats || [];
                        if (!self.resultats.length) {
                            self.messageTest = "Aucun modèle n'a de clé : rien à tester.";
                        }
                    }).catch(function (e) {
                        self.messageTest = e.message;
                    }).finally(function () { self.teste = false; });
                }
            };
        };
    }
</script>
