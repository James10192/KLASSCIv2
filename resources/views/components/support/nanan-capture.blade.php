{{--
    Capture d'écran jointe à une demande faite avec Nanan (namespace nsp-*).

    Trois façons d'apporter une image, sans quitter la fenêtre :
      - « Capturer cette page » : rend la page ouverte SOUS la fenêtre de Nanan
        (qui porte data-support-exclure et n'apparaît donc pas), avec le moteur
        de la fenêtre classique (js/support/capture.js) : champs de saisie et
        valeurs des listes masqués d'office, jamais la page elle-même modifiée ;
      - coller une capture (Ctrl+V / Cmd+V), n'importe où dans la fenêtre ;
      - choisir ou glisser-déposer une image.
    Chaque image passe par l'éditeur (cadre, flèche, masquer, texte) avant d'être
    retenue : une capture collée peut montrer un nom ou un montant.

    L'image part après l'envoi de la demande, comme pièce jointe (il faut sa
    référence). Elle ne survit pas à un rechargement : un Blob ne se garde pas
    dans le brouillon.

    Rendu par nanan.blade.php : `klassciNananCapture()` y est fusionné à l'état
    de la fenêtre. Sans `KLASSCI_SUPPORT.capture` (fonction coupée par le
    Master), rien n'est proposé et l'ancien lien vers la page de suivi reste.
--}}
<script>
if (typeof window.klassciNananCapture !== 'function') {
    window.klassciNananCapture = function () {
        /* Au-delà, on rend la main : un téléphone lent peut rendre une page lourde bien plus longtemps. */
        var DELAI_MAX_MS = 25000;
        var TYPES = ['image/png', 'image/jpeg', 'image/webp'];
        var scripts = {};

        function charger(url) {
            if (!scripts[url]) {
                scripts[url] = new Promise(function (resoudre, rejeter) {
                    var el = document.createElement('script');
                    el.src = url;
                    el.async = true;
                    el.onload = resoudre;
                    el.onerror = function () { delete scripts[url]; rejeter(new Error('chargement')); };
                    document.head.appendChild(el);
                });
            }
            return scripts[url];
        }

        function cle() {
            return window.crypto && typeof window.crypto.randomUUID === 'function'
                ? window.crypto.randomUUID()
                : String(Date.now()) + Math.random().toString(16).slice(2);
        }

        /* La première image d'un presse-papiers ou d'un dépôt, ou null. */
        function imageDe(transfert) {
            if (!transfert) { return null; }
            var fichiers = Array.prototype.slice.call(transfert.files || []);
            Array.prototype.forEach.call(transfert.items || [], function (item) {
                if (item.kind === 'file') { var f = item.getAsFile(); if (f) { fichiers.push(f); } }
            });
            return fichiers.find(function (f) { return TYPES.indexOf(f.type) !== -1; }) || null;
        }

        var editeur = null;

        return {
            capture: null,
            captureEtat: 'repos',
            captureMessage: '',
            captureAvis: '',
            captureOutil: 'cadre',
            captureReelle: false,
            captureProvenance: 'ecran',
            captureAnnulable: false,
            capturePrecedente: 'recap',
            captureSurvol: false,
            captureReference: null,
            captureEchec: false,

            captureDisponible() { return !!(this.support && this.support.capture); },

            outilsCapture() {
                var c = this.support.capture;
                return charger(c.script).then(function () { return charger(c.moteur); });
            },

            /* Collée n'importe où dans la fenêtre : seulement une image, le texte collé reste du texte. */
            surCollage(ev) {
                if (!this.captureDisponible() || ['conversation', 'recap'].indexOf(this.vue) === -1) { return; }
                var image = imageDe(ev.clipboardData);
                if (!image) { return; }
                ev.preventDefault();
                this.depuisImage(image);
            },

            surDepot(ev) {
                this.captureSurvol = false;
                var image = imageDe(ev.dataTransfer);
                if (!image) {
                    this.captureMessage = 'Déposez une image PNG, JPEG ou WebP.';
                    return;
                }
                this.depuisImage(image);
            },

            choisirImage(champ) {
                var image = champ.files && champ.files[0];
                champ.value = '';
                if (image) { this.depuisImage(image); }
            },

            depuisImage(image) {
                var self = this;
                this.captureMessage = '';
                charger(this.support.capture.script).then(function () {
                    return window.KlassciCapture.depuisFichier(image);
                }).then(function (source) { self.ouvrirEditeur(source, 'fichier'); }).catch(function () {
                    self.captureMessage = "Cette image n'a pas pu être ouverte. Utilisez une capture PNG, JPEG ou WebP.";
                });
            },

            capturerPage() {
                if (this.captureEtat === 'rendu') { return; }
                var self = this;
                var tentative = this._captureTentative = (this._captureTentative || 0) + 1;
                this.captureEtat = 'rendu';
                this.captureMessage = '';
                var delai = new Promise(function (resoudre, rejeter) { setTimeout(function () { rejeter(new Error('delai')); }, DELAI_MAX_MS); });
                Promise.race([this.outilsCapture().then(function () { return window.KlassciCapture.capturer(); }), delai])
                    .then(function (source) {
                        if (tentative === self._captureTentative && self.ouvert) { self.ouvrirEditeur(source, 'ecran'); }
                    }).catch(function (e) {
                        if (tentative !== self._captureTentative) { return; }
                        self.captureMessage = e && e.message === 'delai'
                            ? "La capture prend trop de temps sur cet appareil. Faites une capture avec les touches de l'appareil, puis collez-la ou choisissez-la."
                            : "Cette page n'a pas pu être capturée. Collez une capture ou choisissez une image à la place.";
                    }).then(function () {
                        if (tentative === self._captureTentative) { self.captureEtat = 'repos'; }
                    });
            },

            ouvrirEditeur(source, provenance) {
                this.capturePrecedente = this.vue === 'capture' ? this.capturePrecedente : this.vue;
                this.vue = 'capture';
                this.captureOutil = 'cadre';
                this.captureProvenance = provenance;
                this.captureReelle = false;
                this.captureAnnulable = false;
                var self = this;
                this.$nextTick(function () {
                    var cadre = self.$refs.toile;
                    cadre.classList.remove('sp-toile-cadre--reelle');
                    editeur = new window.KlassciCapture.Editeur(cadre, source);
                    editeur.provenance = provenance;
                    if (!cadre.dataset.nspEcoute) {
                        cadre.dataset.nspEcoute = '1';
                        cadre.addEventListener('sp-capture:modifiee', function (ev) { self.captureAnnulable = ev.detail.operations > 0; });
                    }
                    self.focaliser();
                });
            },

            choisirOutil(outil) {
                this.captureOutil = outil;
                if (editeur) { editeur.choisir(outil); }
            },

            basculerTaille() {
                this.captureReelle = !this.captureReelle;
                this.$refs.toile.classList.toggle('sp-toile-cadre--reelle', this.captureReelle);
                if (editeur) { editeur.lireATailleReelle(this.captureReelle); }
            },

            annulerTrait() { if (editeur) { editeur.annuler(); } },

            /* « Ne pas joindre » ne retire pas une capture déjà retenue : on y revient, sans les traits d'après. */
            abandonnerCapture() {
                if (this.capture && editeur && editeur.source === this.capture.source) { editeur.revenirA(this.capture.operations); }
                this.vue = this.capturePrecedente;
            },

            retenirCapture() {
                if (!editeur) { return; }
                var self = this;
                var limite = (this.support.limites && this.support.limites.piece_octets_max) || 0;
                editeur.exporter().then(function (blob) {
                    if (limite && blob.size > limite) {
                        self.captureMessage = 'La capture est trop lourde. Masquez moins large, ou choisissez une image plus petite.';
                        return;
                    }
                    if (self.capture) { URL.revokeObjectURL(self.capture.url); }
                    self.capture = {
                        blob: blob,
                        url: URL.createObjectURL(blob),
                        cle: cle(),
                        source: editeur.source,
                        operations: editeur.operations.slice(),
                        provenance: editeur.provenance,
                        taille: Math.max(1, Math.round(blob.size / 1024)) + ' Ko'
                    };
                    self.vue = self.capturePrecedente;
                }).catch(function () {
                    self.captureMessage = "La capture n'a pas pu être préparée. Réessayez, ou choisissez une image.";
                });
            },

            modifierCapture() {
                if (!this.capture) { return; }
                var operations = this.capture.operations;
                this.ouvrirEditeur(this.capture.source, this.capture.provenance);
                this.$nextTick(function () { if (editeur) { editeur.revenirA(operations); } });
            },

            retirerCapture() {
                if (this.capture) { URL.revokeObjectURL(this.capture.url); }
                this.capture = null;
                editeur = null;
            },

            /* La demande existe : l'image part comme pièce jointe, une clé par image pour « Réessayer ». */
            joindreCapture(reference) {
                var capture = this.capture;
                if (!capture) { return; }
                if (!reference) {
                    this.captureAvis = "La capture n'a pas pu être jointe : la demande attend d'être transmise. Vous pourrez la joindre depuis son suivi.";
                    this.retirerCapture();
                    return;
                }
                var self = this;
                this.captureReference = reference;
                this.captureEchec = false;
                this.captureAvis = 'Envoi de la capture…';
                var donnees = new FormData();
                donnees.append('fichier', new File([capture.blob], 'capture-ecran.' + (capture.blob.type === 'image/webp' ? 'webp' : 'jpg'), { type: capture.blob.type }));
                donnees.append('cle', capture.cle);
                var jeton = document.querySelector('meta[name="csrf-token"]');
                fetch(this.support.capture.pieces.replace('__REFERENCE__', encodeURIComponent(reference)), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : '' },
                    body: donnees
                }).then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (corps) {
                        if (!r.ok) { throw Object.assign(new Error(corps.message || ''), { statut: r.status }); }
                    });
                }).then(function () {
                    /* Entre-temps, la personne a pu recommencer et préparer une autre image : on n'y touche pas. */
                    if (self.capture !== capture) { return; }
                    self.captureAvis = 'Capture jointe à la demande.';
                    self.retirerCapture();
                }).catch(function (e) {
                    if (self.capture !== capture) { return; }
                    self.captureEchec = true;
                    self.captureAvis = "La capture n'a pas pu être jointe" + (e.message ? ' : ' + e.message : '.');
                    /* Refus définitif : inutile de proposer « Réessayer » (le bouton suit `capture`). */
                    if (e.statut === 422 || e.statut === 403 || e.statut === 409) { self.retirerCapture(); }
                });
            },

            /* Un échec passager (réseau) garde l'image : on la renvoie avec la même clé. */
            peutReessayerCapture() { return !!(this.capture && this.captureReference && this.captureEchec); }
        };
    };
}
</script>
