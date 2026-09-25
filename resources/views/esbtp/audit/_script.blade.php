{{-- Comportement du journal : filtres sans rechargement, onglets, raccourci « / ».
     Tout changement relit la liste seule (index?fragment=1) et met l'adresse a jour. --}}
<script>
if (typeof window.journalAudit !== 'function') {
window.journalAudit = function () {
    return {
        cfg: {}, filtres: {}, aRegarder: 0, chargement: false, _requete: null,
        init() {
            this.cfg = JSON.parse(this.$root.dataset.jda || '{}');
            this.filtres = Object.assign({ theme: 'tout', user_id: '', periode: '7', q: '', auto: false }, this.cfg.filtres || {});
            this.aRegarder = this.cfg.aRegarder || 0;
            this._clic = (e) => this.surClic(e);
            this._clavier = (e) => this.surTouche(e);
            this._retour = () => { this.filtres = Object.assign(this.filtres, this.lireAdresse()); this.recharger(false); };
            this.$root.addEventListener('click', this._clic);
            document.addEventListener('keydown', this._clavier);
            window.addEventListener('popstate', this._retour);
            // L'export reprend la vue affichee (x-export-modal).
            window.exportFilters = () => Object.fromEntries(this.parametres());
        },
        destroy() {
            this.$root.removeEventListener('click', this._clic);
            document.removeEventListener('keydown', this._clavier);
            window.removeEventListener('popstate', this._retour);
        },
        parametres() {
            const p = new URLSearchParams();
            const f = this.filtres;
            if (f.theme) p.set('theme', f.theme);
            if (f.user_id) p.set('user_id', f.user_id);
            if (f.periode && f.periode !== '7') p.set('periode', f.periode);
            if ((f.q || '').trim()) p.set('q', f.q.trim());
            if (f.auto) p.set('auto', '1');
            if (f.model_type) p.set('model_type', f.model_type);
            if (f.objet_id) p.set('objet_id', f.objet_id);
            if (f.date_from) p.set('date_from', f.date_from);
            if (f.date_to) p.set('date_to', f.date_to);
            return p;
        },
        lireAdresse() {
            const p = new URLSearchParams(window.location.search);
            return {
                theme: p.get('theme') || this.filtres.theme, user_id: p.get('user_id') || '', periode: p.get('periode') || '7',
                q: p.get('q') || '', auto: p.get('auto') === '1', model_type: p.get('model_type') || '', objet_id: p.get('objet_id') || '',
                date_from: p.get('date_from') || '', date_to: p.get('date_to') || '',
            };
        },
        filtrer(changements) {
            Object.assign(this.filtres, changements);
            this.recharger(true);
        },
        /* Les selecteurs premium emettent un « change » natif : on lit leur nom. */
        choisir(e) {
            const champ = e.target && e.target.name;
            if (champ === 'user_id') this.filtrer({ user_id: e.target.value || '' });
            // Choisir une periode abandonne la plage libre venue de l'activite des personnes.
            if (champ === 'periode') this.filtrer({ periode: e.target.value || '7', date_from: '', date_to: '' });
        },
        async recharger(historique = true) {
            this.chargement = true;
            const p = this.parametres();
            const adresse = this.cfg.index + (p.toString() ? '?' + p.toString() : '');
            p.set('fragment', '1');
            // Une frappe plus recente annule la precedente : une reponse lente
            // a « KON » n'ecrase jamais celle de « KONE ».
            if (this._requete) this._requete.abort();
            const requete = this._requete = new AbortController();
            try {
                const r = await fetch(this.cfg.index + '?' + p.toString(), { signal: requete.signal, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error('Le journal n\'a pas pu être relu (' + r.status + ').');
                const d = await r.json();
                document.getElementById('jda-liste').innerHTML = d.liste;
                // Pendant une recherche le compte n'est pas refait : on garde le dernier.
                if (d.aRegarder !== null) this.aRegarder = d.aRegarder;
                if (historique) window.history.pushState({}, '', adresse);
            } catch (e) {
                if (e.name === 'AbortError') return;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                if (this._requete === requete) { this.chargement = false; this._requete = null; }
            }
        },
        surClic(e) {
            const cible = e.target.closest('[data-jda-filtre]');
            if (!cible || e.ctrlKey || e.metaKey || e.shiftKey) return;
            e.preventDefault();
            this.filtrer(JSON.parse(cible.dataset.jdaFiltre));
        },
        surTouche(e) {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.target.closest('input, textarea, [contenteditable]')) return;
            e.preventDefault();
            this.$refs.recherche.focus();
        },
    };
};
}
</script>
