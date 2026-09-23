/*
 * KLASSCI Care — capture d'ecran masquee et annotation (fenetre « Aide / Signaler »).
 *
 * Charge a la demande par signalement.js, seulement quand l'utilisateur choisit
 * « Capturer l'écran » : ni ce fichier ni le moteur de rendu (html2canvas, servi
 * depuis public/vendor) ne pesent sur les autres pages.
 *
 * Ce qui part n'est jamais la page telle quelle. La capture se fait sur une
 * COPIE du document, dans laquelle, avant le rendu :
 *   - la valeur de chaque champ de saisie (input, textarea, select, zone
 *     editable) est effacee et le champ couvert d'un aplat ;
 *   - tout element marque `data-support-masque`, et la valeur qu'affichent les
 *     selecteurs premium et Select2, sont couverts de la meme facon ;
 *   - la fenetre de signalement et ce qui porte `data-support-exclure` sont omis.
 * Un champ qui doit rester lisible le dit : `data-support-visible`.
 * La page affichee n'est jamais modifiee.
 *
 * L'utilisateur voit ensuite l'image, peut l'annoter (cadre, fleche, masquer une
 * zone, texte) et doit appuyer sur « Joindre ». Masquer pixelise la zone dans
 * l'image exportee : ce n'est pas un calque qu'on pourrait retirer.
 */
(function () {
    'use strict';

    if (window.KlassciCapture) { return; }

    var COTE_MAX = 1600;
    var APLAT = '#cbd5e1';
    var TRAIT = '#dc2626';
    var TYPES_NON_SAISIS = ['button', 'submit', 'reset', 'checkbox', 'radio', 'hidden', 'range', 'color', 'image', 'file'];

    /*
     * Ce qui affiche une valeur saisie sans etre un champ : les selecteurs premium
     * (x-au-select, x-au-user-picker, x-au-mention-picker et leurs clones) et Select2
     * montrent la valeur choisie dans un <span>, le <select> reel etant cache.
     */
    var VALEURS_AFFICHEES = '.au-select-value, [class*="-trigger-selected"], .select2-selection__rendered, output';

    function estAMasquer(el) {
        if (el.closest('[data-support-visible]')) { return false; }
        if (el.matches('[data-support-masque], ' + VALEURS_AFFICHEES)) { return true; }
        if (el.isContentEditable) { return true; }
        if (el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') { return true; }
        return el.tagName === 'INPUT' && TYPES_NON_SAISIS.indexOf((el.getAttribute('type') || 'text').toLowerCase()) === -1;
    }

    /*
     * Coupe d'abord transitions et animations : des feuilles de l'application posent
     * `transition: ... !important`, et le changement serait alors ANIME. html2canvas
     * lit la valeur de depart, et le texte partait en clair sur une vraie page.
     */
    function forcer(el, propriete, valeur) {
        el.style.setProperty('transition', 'none', 'important');
        el.style.setProperty('animation', 'none', 'important');
        el.style.setProperty(propriete, valeur, 'important');
    }

    /* Travaille sur la copie que html2canvas vient de fabriquer : jamais sur la page. */
    function masquerLaCopie(copie) {
        copie.querySelectorAll('input, textarea, select, [contenteditable], [data-support-masque], ' + VALEURS_AFFICHEES).forEach(function (el) {
            if (!estAMasquer(el)) { return; }
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.value = '';
                el.removeAttribute('placeholder');
            } else if (el.tagName === 'SELECT') {
                el.innerHTML = '<option></option>';
            }
            forcer(el, 'background', APLAT);
            forcer(el, 'border-color', APLAT);
            forcer(el, 'color', 'transparent');
            forcer(el, 'text-shadow', 'none');
            // Les enfants gardent leur place (la mise en page ne bouge pas) mais ne se peignent plus.
            el.querySelectorAll('*').forEach(function (enfant) { forcer(enfant, 'visibility', 'hidden'); });
        });
    }

    function exclure(el) {
        return el.id === 'sp-modal'
            || (el.classList && (el.classList.contains('modal-backdrop') || el.classList.contains('toast-container')))
            || (el.hasAttribute && el.hasAttribute('data-support-exclure'));
    }

    /** Rend la partie visible de la page, masquee. Promet un <canvas>. */
    function capturer() {
        if (typeof window.html2canvas !== 'function') {
            return Promise.reject(new Error('moteur absent'));
        }
        var largeur = document.documentElement.clientWidth;
        var hauteur = window.innerHeight;
        return window.html2canvas(document.body, {
            x: window.scrollX,
            y: window.scrollY,
            width: largeur,
            height: hauteur,
            windowWidth: largeur,
            windowHeight: hauteur,
            scale: Math.min(window.devicePixelRatio || 1, COTE_MAX / Math.max(largeur, hauteur)),
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff',
            ignoreElements: exclure,
            onclone: masquerLaCopie
        });
    }

    /** Une image choisie par l'utilisateur, ramenee sur un canvas pour l'annoter. */
    function depuisFichier(fichier) {
        return new Promise(function (resoudre, rejeter) {
            var url = URL.createObjectURL(fichier);
            var img = new Image();
            img.onload = function () {
                var r = Math.min(1, COTE_MAX / Math.max(img.naturalWidth, img.naturalHeight));
                var c = document.createElement('canvas');
                c.width = Math.max(1, Math.round(img.naturalWidth * r));
                c.height = Math.max(1, Math.round(img.naturalHeight * r));
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                resoudre(c);
            };
            img.onerror = function () { URL.revokeObjectURL(url); rejeter(new Error('image illisible')); };
            img.src = url;
        });
    }

    /* ---------------------------------------------------------------- Editeur */

    function Editeur(conteneur, source) {
        this.conteneur = conteneur;
        this.source = source;
        this.operations = [];
        this.outil = 'cadre';
        this.enCours = null;
        this.toile = document.createElement('canvas');
        this.toile.width = source.width;
        this.toile.height = source.height;
        this.toile.className = 'sp-toile';
        this.toile.setAttribute('role', 'img');
        this.toile.setAttribute('aria-label', 'Capture de l\'écran à annoter');
        conteneur.innerHTML = '';
        conteneur.appendChild(this.toile);
        this.ctx = this.toile.getContext('2d');
        this.brancher();
        this.dessiner();
    }

    /* Epaisseur et taille de texte proportionnelles a l'image : lisibles une fois reduite a 1600 px. */
    Editeur.prototype.unite = function () {
        return Math.max(2, Math.round(Math.max(this.toile.width, this.toile.height) / 400));
    };

    Editeur.prototype.point = function (ev) {
        var r = this.toile.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(this.toile.width, (ev.clientX - r.left) * this.toile.width / r.width)),
            y: Math.max(0, Math.min(this.toile.height, (ev.clientY - r.top) * this.toile.height / r.height))
        };
    };

    Editeur.prototype.brancher = function () {
        var self = this;
        this.toile.addEventListener('pointerdown', function (ev) {
            if (self.saisie) { return; }
            var p = self.point(ev);
            if (self.outil === 'texte') { self.demanderTexte(p, ev); return; }
            self.toile.setPointerCapture(ev.pointerId);
            self.enCours = { outil: self.outil, x1: p.x, y1: p.y, x2: p.x, y2: p.y };
            ev.preventDefault();
        });
        this.toile.addEventListener('pointermove', function (ev) {
            if (!self.enCours) { return; }
            var p = self.point(ev);
            self.enCours.x2 = p.x;
            self.enCours.y2 = p.y;
            self.dessiner();
        });
        var finir = function () {
            if (!self.enCours) { return; }
            var op = self.enCours;
            self.enCours = null;
            /* Un simple toucher sans glisser ne laisse pas de trace minuscule. */
            if (Math.abs(op.x2 - op.x1) + Math.abs(op.y2 - op.y1) > self.unite() * 3) { self.operations.push(op); }
            self.dessiner();
            self.signaler();
        };
        this.toile.addEventListener('pointerup', finir);
        this.toile.addEventListener('pointercancel', finir);
    };

    Editeur.prototype.demanderTexte = function (p, ev) {
        var self = this;
        /* Place par rapport au cadre, defilement compris : la toile y est centree et peut deborder. */
        var r = this.conteneur.getBoundingClientRect();
        var champ = document.createElement('input');
        champ.type = 'text';
        champ.maxLength = 80;
        champ.className = 'sp-toile-texte';
        champ.placeholder = 'Votre texte, puis Entrée';
        champ.setAttribute('aria-label', 'Texte à ajouter sur la capture');
        champ.style.left = (ev.clientX - r.left + this.conteneur.scrollLeft) + 'px';
        champ.style.top = (ev.clientY - r.top + this.conteneur.scrollTop) + 'px';
        this.conteneur.appendChild(champ);
        this.saisie = champ;
        var clore = function (garder) {
            if (!self.saisie) { return; }
            var texte = champ.value.trim();
            self.saisie = null;
            champ.remove();
            if (garder && texte) {
                self.operations.push({ outil: 'texte', x1: p.x, y1: p.y, texte: texte });
                self.dessiner();
                self.signaler();
            }
        };
        champ.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); clore(true); }
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); clore(false); }
        });
        champ.addEventListener('blur', function () { clore(true); });
        setTimeout(function () { champ.focus(); }, 0);
    };

    Editeur.prototype.choisir = function (outil) { this.outil = outil; };

    Editeur.prototype.annuler = function () {
        this.operations.pop();
        this.dessiner();
        this.signaler();
    };

    /* Revient a un etat deja valide : ce qui a ete trace depuis ne partira pas, donc ne reste pas affiche. */
    Editeur.prototype.revenirA = function (nombre) {
        this.operations.length = Math.min(this.operations.length, nombre);
        this.dessiner();
        this.signaler();
    };

    Editeur.prototype.signaler = function () {
        this.conteneur.dispatchEvent(new CustomEvent('sp-capture:modifiee', { detail: { operations: this.operations.length } }));
    };

    Editeur.prototype.dessiner = function () {
        var ctx = this.ctx;
        ctx.drawImage(this.source, 0, 0);
        var ops = this.enCours ? this.operations.concat([this.enCours]) : this.operations;
        /* Les zones masquees d'abord : un cadre trace par-dessus reste visible. */
        ops.forEach(function (op) { if (op.outil === 'masquer') { this.pixeliser(op); } }, this);
        ops.forEach(function (op) { if (op.outil !== 'masquer') { this.tracer(op); } }, this);
    };

    Editeur.prototype.pixeliser = function (op) {
        var x = Math.round(Math.min(op.x1, op.x2)), y = Math.round(Math.min(op.y1, op.y2));
        var l = Math.round(Math.abs(op.x2 - op.x1)), h = Math.round(Math.abs(op.y2 - op.y1));
        if (l < 1 || h < 1) { return; }
        var bloc = this.unite() * 6;
        var petite = document.createElement('canvas');
        petite.width = Math.max(1, Math.ceil(l / bloc));
        petite.height = Math.max(1, Math.ceil(h / bloc));
        petite.getContext('2d').drawImage(this.toile, x, y, l, h, 0, 0, petite.width, petite.height);
        this.ctx.save();
        this.ctx.imageSmoothingEnabled = false;
        this.ctx.drawImage(petite, 0, 0, petite.width, petite.height, x, y, l, h);
        this.ctx.restore();
    };

    Editeur.prototype.tracer = function (op) {
        var ctx = this.ctx, u = this.unite();
        ctx.save();
        ctx.strokeStyle = TRAIT;
        ctx.fillStyle = TRAIT;
        ctx.lineWidth = u;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        if (op.outil === 'cadre') {
            ctx.strokeRect(Math.min(op.x1, op.x2), Math.min(op.y1, op.y2), Math.abs(op.x2 - op.x1), Math.abs(op.y2 - op.y1));
        } else if (op.outil === 'fleche') {
            var angle = Math.atan2(op.y2 - op.y1, op.x2 - op.x1), pointe = u * 5;
            ctx.beginPath();
            ctx.moveTo(op.x1, op.y1);
            ctx.lineTo(op.x2, op.y2);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(op.x2, op.y2);
            ctx.lineTo(op.x2 - pointe * Math.cos(angle - Math.PI / 7), op.y2 - pointe * Math.sin(angle - Math.PI / 7));
            ctx.lineTo(op.x2 - pointe * Math.cos(angle + Math.PI / 7), op.y2 - pointe * Math.sin(angle + Math.PI / 7));
            ctx.closePath();
            ctx.fill();
        } else if (op.outil === 'texte') {
            var taille = u * 8;
            ctx.font = '600 ' + taille + 'px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textBaseline = 'top';
            var largeur = ctx.measureText(op.texte).width;
            ctx.fillStyle = 'rgba(255,255,255,.92)';
            ctx.fillRect(op.x1 - u, op.y1 - u, largeur + u * 2, taille + u * 2);
            ctx.fillStyle = TRAIT;
            ctx.fillText(op.texte, op.x1, op.y1);
        }
        ctx.restore();
    };

    /**
     * L'image finale : WebP 0,8, cote le plus long a 1600 px au plus. Un
     * navigateur qui ne sait pas produire du WebP (Safari ancien) rend du JPEG.
     */
    Editeur.prototype.exporter = function () {
        var r = Math.min(1, COTE_MAX / Math.max(this.toile.width, this.toile.height));
        var sortie = document.createElement('canvas');
        sortie.width = Math.max(1, Math.round(this.toile.width * r));
        sortie.height = Math.max(1, Math.round(this.toile.height * r));
        sortie.getContext('2d').drawImage(this.toile, 0, 0, sortie.width, sortie.height);
        var produire = function (type, qualite) {
            return new Promise(function (resoudre) { sortie.toBlob(resoudre, type, qualite); });
        };
        return produire('image/webp', 0.8).then(function (blob) {
            return blob && blob.type === 'image/webp' ? blob : produire('image/jpeg', 0.85);
        }).then(function (blob) {
            if (!blob) { throw new Error('export impossible'); }
            return blob;
        });
    };

    window.KlassciCapture = { capturer: capturer, depuisFichier: depuisFichier, Editeur: Editeur, estAMasquer: estAMasquer };
})();
