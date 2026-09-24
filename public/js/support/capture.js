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
 *     selecteurs premium et Select2, sont couverts de la meme facon. Un nouveau
 *     selecteur pose `data-support-valeur` sur l'element qui affiche la valeur ;
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
    var VALEURS_AFFICHEES = '[data-support-valeur], .au-select-value, [class*="-trigger-selected"], '
        + '.searchable-select-trigger-text, .select2-selection__rendered, output';

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

    /*
     * La copie doit montrer ce que la personne voit. On ne demande PAS a
     * html2canvas de faire defiler sa copie : ce defilement echoue sous Chrome
     * comme sous Safari, et html2canvas ne le rattrape qu'a moitie, pour les
     * seuls appareils Apple (les bandeaux colles y disparaissaient). La copie
     * reste en haut, et on decale sa racine de la hauteur defilee. Les
     * elements fixes restent a l'ecran ; les elements colles, eux, ne collent
     * que si la page defile vraiment, donc on les recale a la main.
     */
    function placerLaCopie(copie, defile) {
        if (defile.x === 0 && defile.y === 0) { return; }
        var racine = copie.documentElement;
        racine.style.setProperty('position', 'relative', 'important');
        racine.style.setProperty('left', -defile.x + 'px', 'important');
        racine.style.setProperty('top', -defile.y + 'px', 'important');
    }

    /*
     * Une seule lecture de la copie placee, puis toutes les ecritures : alterner
     * lecture et ecriture forcerait une mise en page complete a chaque element.
     *
     * 1. Un bloc entierement HORS de l'ecran, au-dessus comme au-dessous,
     *    n'apparaitra pas dans l'image : on le vide en lui gardant sa taille,
     *    rien de ce qui est a l'ecran ne bouge, et le moteur n'a plus a
     *    l'analyser ni a le peindre. Jamais un morceau de tableau (la largeur
     *    des colonnes visibles en depend), ni un element en ligne. Ses
     *    descendants FIXES sont remis dans le bloc vide : hors du flux, ils se
     *    peignent a l'ecran (la bulle de l'assistant vit en bas de page).
     * 2. Un element colle (sticky, avec `top`) remonte au-dessus de son seuil
     *    dans la copie non defilee : on le redescend de ce qui lui manque, sans
     *    sortir de son parent, comme le ferait le navigateur.
     *
     * Rien n'est retire AVANT la mise en page (ignoreElements) : tout ce qui
     * suit remonterait, et une page defilee se capturait blanche.
     */
    function ajusterLaCopie(copie) {
        var vue = copie.defaultView;
        var bas = vue.innerHeight;
        var colles = [];
        var aVider = [];

        function coller(el, r, style) {
            var seuil = parseFloat(style.top);
            if (style.top === 'auto' || !(r.top < seuil)) { return; }
            var manque = Math.min(seuil - r.top, el.parentElement.getBoundingClientRect().bottom - r.bottom);
            if (manque > 0) { colles.push([el, manque]); }
        }

        /* Un element fixe n'a pas de parent de positionnement : offsetParent nul
           l'annonce sans lire le style de tout le sous-arbre. */
        function fixesDans(bloc) {
            var fixes = [];
            bloc.querySelectorAll('*').forEach(function (d) {
                if (d.offsetParent === null && !fixes.some(function (f) { return f.contains(d); })
                    && vue.getComputedStyle(d).position === 'fixed') {
                    fixes.push(d);
                }
            });
            return fixes;
        }

        (function parcourir(parent) {
            for (var el = parent.firstElementChild; el; el = el.nextElementSibling) {
                var r = el.getBoundingClientRect();
                var style;
                if (r.bottom > 0 && r.top < bas) {
                    style = vue.getComputedStyle(el);
                    if (style.position === 'sticky') { coller(el, r, style); }
                    parcourir(el);
                    continue;
                }
                style = vue.getComputedStyle(el);
                if (style.position === 'fixed') { continue; }
                if (style.position === 'sticky') { coller(el, r, style); continue; }
                if (style.display.indexOf('table-') === 0) { continue; }
                if (el.firstChild && style.display !== 'inline' && style.display !== 'contents') {
                    aVider.push([el, r.width, r.height, fixesDans(el)]);
                    continue;
                }
                parcourir(el);
            }
        })(copie.body);

        var repere = repereFixe(copie, vue, bas);
        var avant = repere && repere.getBoundingClientRect().top;

        aVider.forEach(function (x) {
            var el = x[0];
            el.style.setProperty('box-sizing', 'border-box', 'important');
            el.style.setProperty('width', x[1] + 'px', 'important');
            el.style.setProperty('height', x[2] + 'px', 'important');
            el.style.setProperty('min-height', '0', 'important');
            el.style.setProperty('max-height', 'none', 'important');
            /* Un element flexible de base nulle (flex: 1 1 0%) ignore sa hauteur :
               vide, il s'ecraserait et tout ce qui suit remonterait. */
            el.style.setProperty('flex', '0 0 auto', 'important');
            while (el.firstChild) { el.removeChild(el.firstChild); }
            x[3].forEach(function (f) { el.appendChild(f); });
        });
        colles.forEach(function (x) {
            x[0].style.setProperty('transition', 'none', 'important');
            x[0].style.setProperty('position', 'relative', 'important');
            x[0].style.setProperty('top', x[1] + 'px', 'important');
        });

        /* Filet : si un bloc vide au-dessus a malgre tout perdu de la hauteur
           (une marge qui passait a travers lui, un cas non prevu), ce qui est a
           l'ecran a glisse. On le remet a sa place d'avant. */
        if (repere) {
            var ecart = repere.getBoundingClientRect().top - avant;
            if (Math.abs(ecart) >= 1) {
                var racine = copie.documentElement;
                var haut = parseFloat(racine.style.getPropertyValue('top')) || 0;
                /* Sans ceci, une transition de la page anime le recalage et le
                   moteur peindrait l'etat de depart. */
                racine.style.setProperty('transition', 'none', 'important');
                racine.style.setProperty('position', 'relative', 'important');
                racine.style.setProperty('top', (haut - ecart) + 'px', 'important');
            }
        }
    }

    /* Un element du flux au milieu de l'ecran : ni fixe ni colle, sinon il ne
       bougerait pas avec le reste et ne dirait rien. */
    function repereFixe(copie, vue, bas) {
        var el = copie.elementFromPoint(vue.innerWidth / 2, bas * 0.6);
        for (var a = el; a && a !== copie.body; a = a.parentElement) {
            var position = vue.getComputedStyle(a).position;
            if (position === 'fixed' || position === 'sticky') { return null; }
        }
        return el;
    }

    /** Rend la partie visible de la page, masquee. Promet un <canvas>. */
    function capturer() {
        if (typeof window.html2canvas !== 'function') {
            return Promise.reject(new Error('moteur absent'));
        }
        var largeur = document.documentElement.clientWidth;
        var hauteur = window.innerHeight;
        var defile = { x: window.scrollX, y: window.scrollY };
        return window.html2canvas(document.body, {
            x: 0,
            y: 0,
            scrollX: 0,
            scrollY: 0,
            width: largeur,
            height: hauteur,
            windowWidth: largeur,
            windowHeight: hauteur,
            scale: Math.min(window.devicePixelRatio || 1, COTE_MAX / Math.max(largeur, hauteur)),
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff',
            ignoreElements: exclure,
            onclone: function (copie) {
                masquerLaCopie(copie);
                if (copie.defaultView && copie.body) {
                    placerLaCopie(copie, defile);
                    ajusterLaCopie(copie);
                }
            }
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
            /* En taille reelle, le doigt fait defiler l'image : seule la souris ou le stylet dessine. */
            if (self.lecture && ev.pointerType === 'touch') { return; }
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
        setTimeout(function () { champ.focus({ preventScroll: true }); }, 0);
    };

    Editeur.prototype.choisir = function (outil) { this.outil = outil; };

    Editeur.prototype.lireATailleReelle = function (oui) { this.lecture = oui; };

    Editeur.prototype.annuler = function () {
        this.operations.pop();
        this.dessiner();
        this.signaler();
    };

    /* Revient a un etat deja valide : ce qui a ete trace depuis ne partira pas, donc ne reste pas affiche. */
    Editeur.prototype.revenirA = function (operations) {
        this.operations = operations.slice();
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
