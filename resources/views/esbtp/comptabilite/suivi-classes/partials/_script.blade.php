<script>
window.suiviClasses = function (config) {
    const LIBELLES_FILTRES = { filiere: 'Filière', niveau: 'Niveau', classe: 'Classe', systeme: 'Système', categorie: 'Catégorie', search: 'Recherche' };
    const AVANCES = ['filiere', 'niveau', 'classe', 'systeme', 'categorie'];

    return {
        filters: { ...config.filters },
        totaux: config.totaux,
        categorieFiltre: Boolean(config.filters.categorie),
        plusOuvert: AVANCES.some((k) => config.filters[k] !== ''),
        page: 1,
        loading: false,
        message: '',
        annonce: '',
        detailOpen: false,
        detailLoading: false,
        detailTitre: '',
        _minuterie: null,
        _requete: 0,
        _declencheur: null,

        money(n) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(n || 0)); },
        percent(n) { return n === null || n === undefined ? '—' : Number(n).toFixed(1).replace('.', ',') + ' %'; },

        init() {
            window.exportFilters = () => ({ ...this.filters });
        },

        params(page = 1) {
            const qs = new URLSearchParams();
            for (const [cle, valeur] of Object.entries(this.filters)) {
                if (valeur !== '' && valeur !== null) qs.set(cle, valeur);
            }
            if (page > 1) qs.set('page', page);
            return qs;
        },

        nbFiltresAvances() { return AVANCES.filter((k) => this.filters[k] !== '').length; },

        puces() {
            return Object.keys(LIBELLES_FILTRES)
                .filter((cle) => this.filters[cle] !== '')
                .map((cle) => {
                    const valeur = this.filters[cle];
                    const nom = cle === 'search' ? '« ' + valeur + ' »' : ((config.libelles[cle] || {})[valeur] || valeur);
                    return { cle, texte: LIBELLES_FILTRES[cle] + ' : ' + nom };
                });
        },

        retirer(cle) {
            this.filters[cle] = '';
            this.synchroniserSelecteurs();
            this.refresh(1);
        },

        surChangement(event) {
            // La recherche a sa propre temporisation ; tout autre choix recharge aussitôt.
            if (event.target && event.target.id === 'sfc-search') return;
            if (!event.target || !event.target.matches('select')) return;
            this.$nextTick(() => this.refresh(1));
        },

        rechercheDifferee() {
            clearTimeout(this._minuterie);
            this._minuterie = setTimeout(() => this.refresh(1), 350);
        },

        synchroniserSelecteurs() {
            this.$nextTick(() => {
                this.$el.querySelectorAll('.sfc-filters .au-select-native').forEach((s) => {
                    const cle = s.getAttribute('name');
                    if (cle in this.filters && s.value !== this.filters[cle]) {
                        s.value = this.filters[cle];
                        s.dispatchEvent(new Event('change', { bubbles: false }));
                    }
                });
            });
        },

        reset() {
            for (const cle of [...AVANCES, 'search']) this.filters[cle] = '';
            this.synchroniserSelecteurs();
            this.refresh(1);
        },

        async refresh(page = 1) {
            // Une réponse plus ancienne n'écrase jamais une plus récente.
            const numero = ++this._requete;
            this.loading = true;
            this.message = '';
            try {
                const response = await fetch(config.dataUrl + '?' + this.params(page).toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (numero !== this._requete) return;
                if (response.status === 422) {
                    const corps = await response.json().catch(() => ({}));
                    throw new Error(Object.values(corps.errors || {}).flat()[0] || 'Filtre invalide.');
                }
                if (!response.ok) throw new Error(response.status === 403 ? 'Vous n’avez pas accès à ce rapport.' : 'Le rapport n’a pas pu être chargé.');
                const data = await response.json();
                this.$refs.results.innerHTML = data.html;
                if (window.Alpine && typeof Alpine.initTree === 'function') Alpine.initTree(this.$refs.results);
                this.totaux = data.totaux;
                this.categorieFiltre = data.categorie;
                this.page = page;
                this.annonce = data.totaux.classes + ' classes affichées.';
                history.replaceState(null, '', location.pathname + '?' + this.params(page).toString());
            } catch (e) {
                if (numero === this._requete) this.message = e.message;
            } finally {
                if (numero === this._requete) this.loading = false;
            }
        },

        handlePagination(e) {
            const lien = e.target.closest('a[href]');
            if (!lien || !lien.closest('.sfc-pagination')) return;
            e.preventDefault();
            const cible = new URL(lien.href, location.href);
            this.refresh(Math.max(1, Number(cible.searchParams.get('page') || 1)));
            this.$refs.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        async openDetails(id, nom) {
            if (!this.$refs.detailBody) return;
            this._declencheur = document.activeElement;
            this.detailTitre = 'Étudiants · ' + (nom || 'classe');
            this.detailOpen = true;
            this.detailLoading = true;
            this.$refs.detailBody.innerHTML = '';
            try {
                const params = this.params();
                params.delete('tri');
                const response = await fetch(config.detailsPrefix + '/' + id + '/details?' + params.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(response.status === 403 ? 'Vous n’avez pas accès au détail des étudiants.' : 'Le détail n’a pas pu être chargé.');
                this.$refs.detailBody.innerHTML = (await response.json()).html;
            } catch (e) {
                this.$refs.detailBody.innerHTML = '';
                const p = document.createElement('div');
                p.className = 'sfc-error';
                p.textContent = e.message;
                this.$refs.detailBody.appendChild(p);
            } finally {
                this.detailLoading = false;
            }
        },

        fermerDetail() {
            if (!this.detailOpen) return;
            this.detailOpen = false;
            if (this._declencheur && typeof this._declencheur.focus === 'function') this._declencheur.focus();
        },
    };
};
</script>
