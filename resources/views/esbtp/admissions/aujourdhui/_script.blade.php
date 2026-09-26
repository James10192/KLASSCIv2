<script>
/*
 * « Aujourd'hui » : un seul composant Alpine. Les creneaux, les compteurs et le
 * panneau du guichet sont rendus cote serveur et remplaces par fragments apres
 * chaque coche, et toutes les minutes tant que l'onglet est visible.
 */
if (typeof window.aujourdhuiAccueil !== 'function') {
window.aujourdhuiAccueil = function () {
    const jeton = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const echapper = (texte) => { const d = document.createElement('div'); d.textContent = texte || ''; return d.innerHTML; };
    const heure = () => new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

    return {
        cfg: {}, q: '', chargement: false, aucunResultat: false, majA: '',

        init() {
            document.body.setAttribute('data-m-toast', 'off');
            this.cfg = JSON.parse(this.$root.dataset.adjConfig || '{}');
            this.majA = heure();
            this._clic = (e) => this.surClic(e);
            this.$root.addEventListener('click', this._clic);
            this._minuteur = setInterval(() => { if (!document.hidden && !this.chargement) this.rafraichir(); }, 60000);
        },

        destroy() {
            clearInterval(this._minuteur);
            this.$root.removeEventListener('click', this._clic);
        },

        notifier(type, html) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: html } }));
        },

        surClic(e) {
            const bouton = e.target.closest('[data-adj-cocher]');
            if (bouton) { e.preventDefault(); this.cocher(bouton); }
        },

        /* Une coche, puis la journee relue : compteurs, liste et guichet bougent ensemble. */
        async cocher(bouton) {
            if (bouton.classList.contains('is-occupe')) return;
            bouton.classList.add('is-occupe');
            bouton.setAttribute('aria-busy', 'true');
            try {
                const r = await fetch(bouton.dataset.adjCocher, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': jeton(), 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ creneau_id: Number(bouton.dataset.adjCreneauId) }),
                });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const repli = ({ 419: 'Votre session a expiré : rechargez la page.', 429: 'Trop de demandes, patientez une minute.', 403: "Vous n'avez pas le droit de cocher cette famille." })[r.status];
                    this.notifier('error', echapper(d.message || repli || ('Action impossible (erreur ' + r.status + ').')));
                    // Une ligne deplacee ou annulee par un autre poste : la liste affichee est perimee.
                    if (d.code) await this.rafraichir();
                    return;
                }
                await this.rafraichir();
                const ligne = this.ligneApres(bouton);
                const lien = ligne?.querySelector('a.adj-btn');
                const suite = lien && !lien.href.startsWith('tel:') ? ' <a href="' + echapper(lien.href) + '">' + echapper(lien.textContent.trim()) + '</a>' : '';
                this.notifier('success', echapper(d.message || 'Fait.') + suite);
            } catch (err) {
                this.notifier('error', 'Connexion perdue : la coche n\'a pas été enregistrée. Réessayez.');
            } finally {
                bouton.classList.remove('is-occupe');
                bouton.removeAttribute('aria-busy');
            }
        },

        /* La ligne de la meme famille, retrouvee dans la liste rechargee. */
        ligneApres(bouton) {
            const id = bouton.closest('[data-adj-famille]')?.dataset.adjFamille;
            return id ? document.querySelector('#adj-creneaux [data-adj-famille="' + CSS.escape(id) + '"]') : null;
        },

        async rafraichir() {
            this.chargement = true;
            try {
                const r = await fetch(this.cfg.index + '?fragment=1', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const d = await r.json();
                document.getElementById('adj-kpis').innerHTML = d.kpis;
                document.getElementById('adj-creneaux').innerHTML = d.creneaux;
                document.getElementById('adj-guichet').innerHTML = d.guichet;
                this.majBadgeDuMenu(d.compteurs?.a_recevoir || 0);
                this.majA = heure();
                this.filtrer();
            } catch (err) {
                this.notifier('error', 'La liste du jour n\'a pas pu être relue. Elle se remettra à jour à la prochaine minute.');
            } finally {
                this.chargement = false;
            }
        },

        /* Le badge du menu compte les familles encore attendues : il suit sans recharger. */
        majBadgeDuMenu(n) {
            const lien = document.querySelector('.menu-sublink[href="' + this.cfg.index + '"]');
            if (!lien) return;
            let badge = lien.querySelector('.menu-badge');
            if (!n) { if (badge) badge.remove(); return; }
            if (!badge) { badge = document.createElement('span'); badge.className = 'menu-badge'; lien.querySelector('.menu-text').appendChild(badge); }
            badge.textContent = n > 999 ? '999+' : String(n);
        },

        /* Recherche locale : la journee tient en une page, rien ne repart au serveur. */
        filtrer() {
            const texte = (this.q || '').trim().toLowerCase();
            const chiffres = texte.replace(/\D/g, '');
            let visibles = 0;
            document.querySelectorAll('#adj-creneaux [data-adj-creneau]').forEach((creneau) => {
                let dans = 0;
                creneau.querySelectorAll('[data-adj-famille]').forEach((l) => {
                    const cle = l.dataset.adjRecherche || '';
                    const ok = !texte || cle.includes(texte) || (chiffres.length >= 4 && cle.includes(chiffres));
                    l.hidden = !ok;
                    if (ok) dans++;
                });
                creneau.hidden = dans === 0;
                visibles += dans;
            });
            this.aucunResultat = texte !== '' && visibles === 0;
        },
    };
};
}
</script>
