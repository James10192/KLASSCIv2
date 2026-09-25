{{-- Comportement du journal : filtres sans rechargement, onglets, raccourci « / ».
     Tout changement relit la liste seule (index?fragment=1) et met l'adresse a jour. --}}
<script>
if (typeof window.journalAudit !== 'function') {
window.journalAudit = function () {
    return {
        cfg: {}, filtres: {}, aRegarder: 0, chargement: false,
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
            return p;
        },
        lireAdresse() {
            const p = new URLSearchParams(window.location.search);
            return { theme: p.get('theme') || this.filtres.theme, user_id: p.get('user_id') || '', periode: p.get('periode') || '7', q: p.get('q') || '', auto: p.get('auto') === '1' };
        },
        filtrer(changements) {
            Object.assign(this.filtres, changements);
            this.recharger(true);
        },
        /* Les selecteurs premium emettent un « change » natif : on lit leur nom. */
        choisir(e) {
            const champ = e.target && e.target.name;
            if (champ === 'user_id' || champ === 'periode') this.filtrer({ [champ]: e.target.value || '' });
        },
        async recharger(historique = true) {
            this.chargement = true;
            const p = this.parametres();
            const adresse = this.cfg.index + (p.toString() ? '?' + p.toString() : '');
            p.set('fragment', '1');
            try {
                const r = await fetch(this.cfg.index + '?' + p.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error('Le journal n\'a pas pu être relu (' + r.status + ').');
                const d = await r.json();
                document.getElementById('jda-liste').innerHTML = d.liste;
                this.aRegarder = d.aRegarder;
                if (historique) window.history.pushState({}, '', adresse);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.chargement = false;
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
