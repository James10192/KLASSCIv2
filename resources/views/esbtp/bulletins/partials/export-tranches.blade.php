{{--
    Export groupé par tranches — panneau et orchestration.

    Une classe entière ne s'exporte pas en une requête : sept bulletins
    consomment déjà trente secondes, pour une limite d'exécution du même ordre.
    Le serveur découpe donc en quatre temps (ouvrir / tranche / assembler /
    telecharger), et ce module enchaîne les tranches en montrant l'avancement.

    Le document n'est pas ouvert automatiquement : après plusieurs minutes
    d'attente, un `window.open` n'est plus rattaché au clic de l'utilisateur et
    le navigateur le bloque. Le panneau propose donc un lien à cliquer.
--}}

<div class="bex" x-show="exportEtat" x-cloak>
    <div class="bex__head">
        <span class="bex__label" :class="`bex__label--${exportEtat?.phase}`">
            <i class="fas" :class="{
                'fa-spinner fa-spin': ['ouverture', 'rendu'].includes(exportEtat?.phase),
                'fa-layer-group fa-fade': exportEtat?.phase === 'assemblage',
                'fa-circle-question': exportEtat?.phase === 'confirmation',
                'fa-circle-check': exportEtat?.phase === 'pret',
                'fa-circle-exclamation': exportEtat?.phase === 'erreur',
            }"></i>
            <span x-text="exportEtat?.texte"></span>
        </span>
        <span class="bex__pct" x-show="exportEtat?.pourcent !== null" x-text="`${exportEtat?.pourcent} %`"></span>
    </div>

    <div class="bex__rail" x-show="exportEtat?.pourcent !== null"
         role="progressbar" aria-label="Avancement de l'export groupé"
         :aria-valuenow="exportEtat?.pourcent" aria-valuemin="0" aria-valuemax="100">
        <div class="bex__fill" :style="`width:${exportEtat?.pourcent}%`"></div>
    </div>

    <div class="bex__foot" aria-live="polite">
        <span x-text="exportEtat?.detail"></span>
        <span x-show="exportEtat?.restant" x-text="`Il reste ${exportEtat?.restant}`"></span>
    </div>

    {{-- Confirmation des bulletins absents : un vrai panneau, pas un confirm() --}}
    <div class="bex__actions" x-show="exportEtat?.phase === 'confirmation'">
        <button type="button" class="bul-btn bul-btn--sm bul-btn--ghost" @click="exportEtat.repondre(false)">
            <i class="fas fa-xmark"></i> Annuler
        </button>
        <button type="button" class="bul-btn bul-btn--sm bul-btn--primary" @click="exportEtat.repondre(true)">
            <i class="fas fa-check"></i> Continuer quand même
        </button>
    </div>

    {{-- Document prêt : c'est le clic de l'utilisateur qui l'ouvre. --}}
    <div class="bex__actions" x-show="exportEtat?.phase === 'pret'">
        <button type="button" class="bul-btn bul-btn--sm bul-btn--ghost" @click="exportEtat = null">
            <i class="fas fa-xmark"></i> Fermer
        </button>
        <a class="bul-btn bul-btn--sm bul-btn--primary" :href="exportEtat?.url" target="_blank" rel="noopener">
            <i class="fas fa-file-pdf"></i>
            <span x-text="exportEtat?.mode === 'apercu' ? 'Ouvrir l\'aperçu' : 'Télécharger le PDF'"></span>
        </a>
    </div>

    <div class="bex__actions" x-show="exportEtat?.phase === 'erreur'">
        <button type="button" class="bul-btn bul-btn--sm bul-btn--ghost" @click="exportEtat = null">
            <i class="fas fa-xmark"></i> Fermer
        </button>
    </div>
</div>

@push('styles')
<style>
    .bex {
        margin-top: .85rem;
        padding: .85rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .bex__head {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; margin-bottom: .55rem;
    }
    .bex__label {
        display: inline-flex; align-items: center; gap: .5rem;
        font-size: .84rem; font-weight: 600; color: #1e293b;
    }
    .bex__label i { color: #0453cb; }
    .bex__label--pret i { color: #10b981; }
    .bex__label--erreur i { color: #dc2626; }
    .bex__pct { font-size: .84rem; font-weight: 700; color: #0453cb; font-variant-numeric: tabular-nums; }
    .bex__rail { height: 8px; border-radius: 999px; background: #eef2f7; overflow: hidden; }
    .bex__fill {
        height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #0453cb, #3b7ddb);
        transition: width .45s cubic-bezier(.4, 0, .2, 1);
    }
    .bex__foot {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; margin-top: .5rem;
        font-size: .72rem; color: #64748b;
    }
    .bex__foot:empty { display: none; }
    .bex__actions {
        display: flex; align-items: center; justify-content: flex-end;
        gap: .5rem; margin-top: .7rem;
    }
    @@media (prefers-reduced-motion: reduce) {
        .bex__fill { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
/**
 * Enchaîne ouvrir → tranches → assembler, en rendant compte de l'avancement.
 *
 * Le panneau est la seule surface de message : succès, attente, question et
 * erreur passent tous par `onEtat`, avec la même forme d'objet.
 *
 * @param {object} o
 * @param {URLSearchParams} o.params  filtres de la vue (classe, période, tri…)
 * @param {'apercu'|'telechargement'} o.mode
 * @param {{ouvrir:string, tranche:string, assembler:string}} o.urls
 * @param {string} o.csrf
 * @param {(etat:object|null)=>void} o.onEtat
 */
window.exportBulletinsParTranches = async function ({ params, mode, urls, csrf, onEtat }) {
    const entete = {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Toujours la même forme : le gabarit n'a pas à deviner quels champs existent.
    const etat = (phase, texte, extra = {}) => onEtat({
        phase, texte, pourcent: null, detail: '', restant: null, ...extra,
    });

    const duree = (s) => s < 60
        ? `${Math.max(1, Math.round(s))} s`
        : `${Math.floor(s / 60)} min ${String(Math.round(s % 60)).padStart(2, '0')} s`;

    /** Un POST JSON qui distingue vraiment les pannes des refus métier. */
    const poster = async (url, corps) => {
        let reponse;
        try {
            reponse = await fetch(url, { method: 'POST', headers: entete, body: corps });
        } catch {
            throw new Error('Connexion perdue. Vérifiez le réseau, puis réessayez.');
        }
        if (reponse.status === 419) {
            throw new Error('Votre session a expiré. Rechargez la page, puis relancez l\'export.');
        }
        let charge;
        try {
            charge = await reponse.json();
        } catch {
            throw new Error(`Réponse inattendue du serveur (code ${reponse.status}).`);
        }
        if (!charge.success) {
            throw new Error(charge.message || `Export interrompu (code ${reponse.status}).`);
        }
        return charge.data;
    };

    try {
        // 1. Ouvrir : le serveur fige la liste des bulletins et rend un jeton.
        etat('ouverture', 'Préparation de l\'export…', { pourcent: 0 });
        const { jeton, total, absents, taille_tranche: taille } = await poster(urls.ouvrir, params);

        // 2. Prévenir avant de lancer cinq minutes de travail pour rien.
        if (absents > 0) {
            const suite = await new Promise((resoudre) => etat(
                'confirmation',
                `${absents} bulletin(s) ne sont pas générés`,
                {
                    detail: `${total} bulletin(s) seront inclus ; les absents seront listés en page de garde.`,
                    repondre: resoudre,
                }
            ));
            if (!suite) { onEtat(null); return; }
        }

        // 3. Les tranches, l'une après l'autre : chacune tient sous la limite.
        const tranches = Math.ceil(total / taille);
        const debut = Date.now();
        let depart = 0;

        for (let n = 1; ; n++) {
            const corps = new URLSearchParams({ jeton, depart: String(depart) });
            const t = await poster(urls.tranche, corps);

            const ecoule = (Date.now() - debut) / 1000;
            const reste = t.traites > 0 ? (ecoule / t.traites) * (t.total - t.traites) : 0;

            etat('rendu', `${t.traites} / ${t.total} bulletins`, {
                pourcent: Math.round((t.traites / t.total) * 100),
                detail: `Tranche ${n} sur ${tranches}`,
                restant: reste > 3 ? duree(reste) : null,
            });

            if (t.termine) break;      // le serveur est l'autorité sur la fin.
            depart = t.traites;
        }

        // 4. Assembler : l'étape la plus coûteuse, donc celle dont les erreurs
        //    doivent absolument revenir ici et pas dans un onglet perdu.
        etat('assemblage', `Assemblage des ${total} bulletins…`, {
            pourcent: 100,
            detail: 'Cela peut prendre une minute ou deux.',
        });

        const { url } = await poster(urls.assembler, new URLSearchParams({ jeton, mode }));

        etat('pret', `PDF prêt — ${total} bulletins`, { url, mode });
    } catch (e) {
        etat('erreur', e.message || 'Export interrompu.');
    }
};
</script>
@endpush
