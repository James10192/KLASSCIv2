{{--
    Toasts de fin des travaux longs (génération des bulletins, PDF groupé).

    Rendu sur toutes les pages : la personne qui a lancé une génération puis
    est partie ailleurs dans l'application voit un toast quand elle se termine.
    Il n'interroge le serveur que s'il sait une tâche en cours (toutes les
    15 s) ; sinon il ne fait rien. Et tant qu'il en sait une, il la fait
    avancer d'une tranche à chaque tour : n'importe quelle page ouverte fait
    progresser le travail, même si la planification ne tourne pas (le verrou
    de la tâche empêche qu'une tranche soit rendue deux fois). Une tâche finie
    pendant qu'on était déconnecté s'annonce au chargement suivant.

    Script et style en ligne, avec garde d'idempotence : le composant ne
    dépend d'aucune pile du layout.
--}}
@php
    $tapEtatInitial = auth()->check()
        ? \App\Domain\Bulletins\Taches\SuiviTachesBulletins::pourUtilisateur(auth()->user())
        : [];
    $tapUrls = [
        'suivi' => route('esbtp.bulletins.taches.suivi', [], false),
        'avancer' => route('esbtp.bulletins.taches.avancer', ['tache' => '__ID__'], false),
        'vue' => route('esbtp.bulletins.taches.vue', ['tache' => '__ID__'], false),
        'bulletins' => route('esbtp.bulletins.select', [], false),
    ];
@endphp
@auth
<div class="tap-pile" x-data="tachesArrierePlan()" data-taches='@json($tapEtatInitial)' data-urls='@json($tapUrls)'
     aria-live="polite" role="status">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="tap-toast" :class="toast.pause ? 'tap-toast--pause' : (toast.reussie ? 'tap-toast--ok' : 'tap-toast--ko')" x-transition.opacity>
            <span class="tap-toast__icone">
                <i class="fas" :class="toast.pause ? 'fa-circle-pause' : (toast.reussie ? 'fa-circle-check' : 'fa-circle-exclamation')"></i>
            </span>
            <div class="tap-toast__corps">
                <p class="tap-toast__titre" x-text="toast.titre"></p>
                <p class="tap-toast__texte" x-text="toast.texte"></p>
                <a class="tap-toast__lien" x-show="toast.url" :href="toast.url"
                   :target="toast.nouvelOnglet ? '_blank' : null" rel="noopener"
                   @click="fermer(toast.id)" x-text="toast.libelleLien"></a>
            </div>
            <button type="button" class="tap-toast__fermer" @click="fermer(toast.id)" aria-label="Fermer">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </template>
</div>

<style>
    .tap-pile {
        position: fixed; right: 1rem; bottom: 1rem; z-index: 1090;
        display: flex; flex-direction: column; gap: .6rem;
        width: min(380px, calc(100vw - 2rem));
        pointer-events: none;
    }
    .tap-toast {
        pointer-events: auto;
        display: flex; align-items: flex-start; gap: .7rem;
        padding: .85rem .9rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #0453cb;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(4,83,203,.12), 0 2px 8px rgba(15,23,42,.06);
    }
    .tap-toast--ok { border-left-color: #10b981; }
    .tap-toast--ko { border-left-color: #dc2626; }
    .tap-toast--pause { border-left-color: #f59e0b; }
    .tap-toast--pause .tap-toast__icone { color: #d97706; }
    .tap-toast__icone { font-size: 1.05rem; line-height: 1.2; color: #0453cb; }
    .tap-toast--ok .tap-toast__icone { color: #10b981; }
    .tap-toast--ko .tap-toast__icone { color: #dc2626; }
    .tap-toast__corps { flex: 1; min-width: 0; }
    .tap-toast__titre { margin: 0; font-size: .84rem; font-weight: 700; color: #0f172a; }
    .tap-toast__texte { margin: .2rem 0 0; font-size: .78rem; color: #475569; }
    .tap-toast__lien {
        display: inline-flex; margin-top: .5rem;
        padding: .35rem .7rem; border-radius: 8px;
        background: #0453cb; color: #fff; font-size: .76rem; font-weight: 600;
        text-decoration: none;
    }
    .tap-toast__lien:hover { background: #033a8e; color: #fff; }
    .tap-toast__fermer {
        border: none; background: transparent; color: #94a3b8;
        width: 28px; height: 28px; border-radius: 8px; cursor: pointer;
    }
    .tap-toast__fermer:hover { background: #f1f5f9; color: #0f172a; }
    @@media (max-width: 576px) {
        .tap-pile { right: .5rem; left: .5rem; bottom: .5rem; width: auto; }
    }
</style>

<script>
if (typeof window.tachesArrierePlan !== 'function') {
window.tachesArrierePlan = function () {
    return {
        taches: [],
        toasts: [],
        annoncees: {},
        suiviesParLaPage: {},
        urls: {},
        minuteur: null,
        _surNouvelle: null,
        _surSuivie: null,

        init() {
            const lire = (cle, defaut) => {
                try { return JSON.parse(this.$root.dataset[cle] || ''); } catch { return defaut; }
            };
            this.taches = lire('taches', []);
            this.urls = lire('urls', {});

            this._surNouvelle = (ev) => {
                if (ev.detail?.id && !this.taches.some((t) => t.id === ev.detail.id)) {
                    this.taches.push(ev.detail);
                }
                this.planifier();
            };
            // La page qui a lancé la tâche affiche elle-même la fin : pas de doublon.
            this._surSuivie = (ev) => { if (ev.detail?.id) this.suiviesParLaPage[ev.detail.id] = true; };
            window.addEventListener('taches-bulletins:nouvelle', this._surNouvelle);
            window.addEventListener('taches-bulletins:suivie', this._surSuivie);

            this.traiter(this.taches);
            this.planifier();
        },

        destroy() {
            clearTimeout(this.minuteur);
            window.removeEventListener('taches-bulletins:nouvelle', this._surNouvelle);
            window.removeEventListener('taches-bulletins:suivie', this._surSuivie);
        },

        actives() {
            return this.taches.some((t) => !t.finale);
        },

        // Aucune requête tant que rien ne tourne.
        planifier() {
            clearTimeout(this.minuteur);
            if (!this.actives()) return;
            this.minuteur = setTimeout(() => this.interroger(), 15000);
        },

        // Une tranche de la première tâche que la page ne pilote pas déjà.
        async avancerUneTache() {
            const t = this.taches.find((x) => !x.finale && !this.suiviesParLaPage[x.id]);
            if (!t) return;
            const jeton = document.querySelector('meta[name="csrf-token"]')?.content || '';
            await fetch(this.urls.avancer.replace('__ID__', String(t.id)), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': jeton, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        },

        async interroger() {
            if (document.hidden) { this.planifier(); return; }
            try {
                // Une 500 ici (requête coupée) ne perd rien : le suivi qui suit
                // dit où en est le travail.
                await this.avancerUneTache();
                const reponse = await fetch(this.urls.suivi, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (reponse.ok) {
                    const charge = await reponse.json();
                    this.taches = Array.isArray(charge.taches) ? charge.taches : [];
                    this.traiter(this.taches);
                }
            } catch {
                // Réseau coupé : on réessaie au prochain tour, la tâche, elle, continue.
            }
            this.planifier();
        },

        traiter(taches) {
            taches.filter((t) => t.finale).forEach((t) => this.annoncer(t));
            taches.filter((t) => !t.finale && t.en_pause).forEach((t) => this.signalerPause(t));
        },

        // Une tâche immobile depuis plusieurs minutes : le dire une fois, sans alarmer.
        signalerPause(t) {
            const cle = 'pause-' + t.id;
            if (this.annoncees[cle] || this.suiviesParLaPage[t.id]) return;
            this.annoncees[cle] = true;
            this.toasts.push({
                id: cle,
                pause: true,
                reussie: false,
                titre: 'En pause · ' + t.libelle,
                texte: 'Le travail est en pause, il reprendra automatiquement. Vous pouvez aussi rouvrir la page des bulletins.',
                url: this.urls.bulletins,
                nouvelOnglet: false,
                libelleLien: 'Ouvrir les bulletins',
            });
        },

        annoncer(t) {
            if (this.annoncees[t.id]) return;
            this.annoncees[t.id] = true;
            if (this.suiviesParLaPage[t.id]) return;

            const reussie = t.statut === 'terminee';
            this.fermer('pause-' + t.id);
            this.toasts.push({
                id: t.id,
                reussie,
                titre: (reussie ? 'Terminé · ' : 'Échec · ') + t.libelle,
                texte: t.message || '',
                url: t.url,
                nouvelOnglet: reussie && t.type === 'export',
                // Le serveur nomme le lien, comme dans la cloche et l'e-mail.
                libelleLien: t.libelle_lien,
            });
            this.marquerVue(t.id);
        },

        marquerVue(id) {
            const jeton = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(this.urls.vue.replace('__ID__', String(id)), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': jeton, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        },

        fermer(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    };
};
}
</script>
@endauth
