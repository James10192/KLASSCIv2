<script>
/*
 * Guide pas a pas et aide du module rendez-vous (rule interactive-guides).
 * Chaque page declare AVANT l'inclusion :
 *   window.__rdvGuideEtapes = [{ sel, titre, texte, ouvrir? }]
 *   window.__rdvGuideAvant  = function () {}   // facultatif : injecter un exemple, fermer un panneau
 * Les exemples injectes portent data-tour-demo et sont retires a la fermeture.
 */
(function () {
    if (window.__rdvGuideInit) return;
    window.__rdvGuideInit = true;


    let pas = 0, etapes = [], spot = null, carte = null;

    function visible(el) {
        if (!el) return false;
        const r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden';
    }

    function nettoyer() {
        spot?.remove(); carte?.remove(); spot = carte = null;
        document.querySelectorAll('[data-tour-demo]').forEach((d) => d.remove());
        document.removeEventListener('keydown', surTouche);
        window.removeEventListener('resize', placer);
        window.removeEventListener('scroll', placer, true);
    }

    function surTouche(ev) { if (ev.key === 'Escape') nettoyer(); }

    function placer() {
        const etape = etapes[pas];
        if (!etape || !spot) return;
        const r = etape.el.getBoundingClientRect();
        Object.assign(spot.style, { top: (r.top - 6) + 'px', left: (r.left - 6) + 'px', width: (r.width + 12) + 'px', height: (r.height + 12) + 'px' });
        if (window.innerWidth <= 576) return;
        const h = carte.offsetHeight, w = carte.offsetWidth;
        let top = r.bottom + 14;
        if (top + h > window.innerHeight - 12) top = Math.max(12, r.top - h - 14);
        const left = Math.min(Math.max(12, r.left), window.innerWidth - w - 12);
        Object.assign(carte.style, { top: top + 'px', left: left + 'px' });
    }

    function montrer(i) {
        pas = i;
        const etape = etapes[pas];
        if (etape.ouvrir && etape.el.tagName === 'DETAILS') etape.el.open = true;
        etape.el.scrollIntoView({ block: 'center', behavior: 'instant' });
        carte.querySelector('h3').textContent = etape.titre;
        carte.querySelector('p').textContent = etape.texte;
        carte.querySelector('.rdv-tour-pas').textContent = (pas + 1) + ' / ' + etapes.length;
        carte.querySelector('[data-tour="retour"]').disabled = pas === 0;
        carte.querySelector('[data-tour="suivant"]').innerHTML = pas === etapes.length - 1 ? '<i class="fas fa-check"></i>Terminer' : 'Suivant<i class="fas fa-arrow-right"></i>';
        placer();
        carte.querySelector('[data-tour="suivant"]').focus();
    }

    function demarrer() {
        nettoyer();
        if (typeof window.__rdvGuideAvant === 'function') window.__rdvGuideAvant();
        etapes = (window.__rdvGuideEtapes || []).map((e) => Object.assign({}, e, { el: document.querySelector(e.sel) })).filter((e) => visible(e.el));
        if (!etapes.length) return;
        spot = document.createElement('div');
        spot.className = 'rdv-tour-spot';
        carte = document.createElement('div');
        carte.className = 'rdv-tour-carte';
        carte.setAttribute('role', 'dialog');
        carte.setAttribute('aria-live', 'polite');
        carte.innerHTML = '<h3></h3><p></p><div class="rdv-tour-pied"><span class="rdv-tour-pas"></span>'
            + '<button type="button" class="rdv-btn rdv-btn--ghost rdv-btn--sm" data-tour="quitter">Quitter</button>'
            + '<button type="button" class="rdv-btn rdv-btn--ghost rdv-btn--sm" data-tour="retour"><i class="fas fa-arrow-left"></i>Retour</button>'
            + '<button type="button" class="rdv-btn rdv-btn--primary rdv-btn--sm" data-tour="suivant"></button></div>';
        document.body.append(spot, carte);
        carte.addEventListener('click', (ev) => {
            const b = ev.target.closest('[data-tour]');
            if (!b) return;
            if (b.dataset.tour === 'quitter') return nettoyer();
            if (b.dataset.tour === 'retour' && pas > 0) return montrer(pas - 1);
            if (b.dataset.tour === 'suivant') return pas < etapes.length - 1 ? montrer(pas + 1) : nettoyer();
        });
        document.addEventListener('keydown', surTouche);
        window.addEventListener('resize', placer);
        window.addEventListener('scroll', placer, true);
        montrer(0);
    }

    const aide = document.getElementById('rdv-aide');
    let retourAide = null;
    function fermerAide() {
        aide.classList.remove('is-ouverte');
        if (retourAide && document.contains(retourAide)) retourAide.focus();
        retourAide = null;
    }
    document.addEventListener('click', (ev) => {
        if (ev.target.closest('[data-page-tour-open]')) { ev.preventDefault(); return demarrer(); }
        if (!aide) return;
        if (ev.target.closest('[data-page-help-open]')) { ev.preventDefault(); retourAide = document.activeElement; aide.classList.add('is-ouverte'); aide.querySelector('[data-rdv-aide-fermer]').focus(); return; }
        if (ev.target === aide || ev.target.closest('[data-rdv-aide-fermer]')) fermerAide();
    });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape' && aide && aide.classList.contains('is-ouverte')) fermerAide(); });
})();
</script>
