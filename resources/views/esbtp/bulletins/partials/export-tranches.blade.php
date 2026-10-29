{{--
    Export groupé en arrière-plan — panneau et suivi.

    Une classe entière ne s'exporte pas en une requête : sept bulletins
    consomment déjà trente secondes, pour une limite d'exécution du même ordre.
    Le serveur fige la liste et crée une tâche ; l'onglet la fait avancer
    tranche par tranche tant qu'il reste ouvert, et la planification la finit
    sinon. Le demandeur est prévenu à la fin (cloche, toast, e-mail).

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

    <p class="bex__courriel" x-show="exportEtat?.courriel" x-cloak>
        {{-- Le message vit sur la page : chaque état d'avancement remplace exportEtat. --}}
        <button type="button" class="bex__courriel-lien" x-show="!courrielMessage"
                @click="courrielMessage = await window.demanderConfirmationCourriel(exportEtat.courriel.url, document.querySelector('meta[name=csrf-token]').content)">
            <i class="fas fa-envelope"></i> Recevoir aussi un e-mail : confirmer mon adresse
        </button>
        <span x-show="courrielMessage" x-text="courrielMessage"></span>
    </p>

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
        <button type="button" class="bul-btn bul-btn--sm bul-btn--primary" x-show="exportEtat?.mode"
                @click="lancerExportGroupe(exportEtat.mode)">
            <i class="fas fa-rotate-right"></i> Réessayer
        </button>
    </div>
</div>

@push('styles')
<style>
    .bex {
        /* La rangee d export est un conteneur flex : sans cela le panneau
           se glisse a cote des boutons au lieu de prendre sa ligne. */
        flex: 1 1 100%;
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
    .bex__courriel { margin: .45rem 0 0; font-size: .74rem; color: #475569; }
    .bex__courriel-lien {
        border: none; background: none; padding: 0; cursor: pointer;
        color: #0453cb; font-weight: 600; text-decoration: underline;
    }
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
@include('esbtp.bulletins.partials.suivi-tache-script')
<script>
/**
 * Lance l'export en arrière-plan et en rend compte dans le panneau.
 *
 * Le panneau est la seule surface de message : succès, attente, question et
 * erreur passent tous par `onEtat`, avec la même forme d'objet.
 *
 * @param {object} o
 * @param {URLSearchParams} o.params  filtres de la vue (classe, période, tri…)
 * @param {'apercu'|'telechargement'} o.mode
 * @param {string} o.urlLancer
 * @param {string} o.csrf
 * @param {(etat:object|null)=>void} o.onEtat
 */
window.exportBulletinsParTranches = async function ({ params, mode, urlLancer, csrf, onEtat }) {
    const entete = {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Toujours la même forme : le gabarit n'a pas à deviner quels champs existent.
    // `mode` voyage avec chaque état : « Réessayer » relance le même export.
    const etat = (phase, texte, extra = {}) => onEtat({
        phase, texte, pourcent: null, detail: '', restant: null, mode, courriel: null, ...extra,
    });

    const duree = (s) => s < 60
        ? `${Math.max(1, Math.round(s))} s`
        : `${Math.floor(s / 60)} min ${String(Math.round(s % 60)).padStart(2, '0')} s`;

    /** Un POST JSON qui distingue vraiment les pannes des refus métier. */
    const lancer = async (confirme) => {
        const corps = new URLSearchParams(params);
        corps.set('mode', mode);
        if (confirme) corps.set('confirme', '1');

        let reponse;
        try {
            reponse = await fetch(urlLancer, { method: 'POST', headers: entete, body: corps });
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
        return charge;
    };

    const quitter = 'Vous pouvez quitter la page. Une notification apparaîtra dans la cloche à la fin.';
    // Adresse non confirmée : on propose de la confirmer pour recevoir aussi un e-mail.
    const courriel = (t) => (t.email_a_verifier && t.email_verification_url)
        ? { url: t.email_verification_url, message: '' }
        : null;

    try {
        // 1. Le serveur fige la liste des bulletins.
        etat('ouverture', 'Préparation de l\'export…', { pourcent: 0 });
        let charge = await lancer(false);

        // 2. Prévenir avant de lancer cinq minutes de travail pour rien.
        if (charge.confirmation) {
            const { total, absents, absents_noms: nomsAbsents } = charge.confirmation;
            const qui = Array.isArray(nomsAbsents) && nomsAbsents.length
                ? nomsAbsents.slice(0, 5).join(' · ') + (nomsAbsents.length > 5 ? '…' : '')
                : '';
            const suite = await new Promise((resoudre) => etat(
                'confirmation',
                `${absents} élève(s) de la classe n'ont pas de bulletin généré`,
                {
                    detail: qui
                        ? `${qui}. ${total} bulletin(s) seront inclus.`
                        : `${total} bulletin(s) seront inclus ; les absents seront listés en page de garde.`,
                    repondre: resoudre,
                }
            ));
            if (!suite) { onEtat(null); return; }
            charge = await lancer(true);
        }

        // 3. La tâche avance tant que la page reste ouverte ; sinon le serveur
        //    la finit et prévient. Le serveur est l'autorité sur la fin.
        const fin = await window.suivreTacheBulletins({
            tache: charge.tache,
            csrf,
            onEtat: (t, extra) => {
                if (t.position >= t.total && !t.finale) {
                    etat('assemblage', `Assemblage des ${t.total} bulletins…`, { pourcent: 100, detail: quitter, courriel: courriel(t) });
                    return;
                }
                etat('rendu', `${t.position} / ${t.total} bulletins`, {
                    pourcent: t.pourcent,
                    detail: t.en_pause
                        ? 'Le travail est en pause, il reprendra automatiquement.'
                        : (extra.relais ? 'L\'export continue, même si vous quittez la page.' : quitter),
                    restant: extra.restant > 3 ? duree(extra.restant) : null,
                    courriel: courriel(t),
                });
            },
        });

        if (fin.statut !== 'terminee') {
            etat('erreur', fin.message || 'Export interrompu.');
            return;
        }

        etat('pret', fin.message || `PDF prêt — ${fin.total} bulletins`, { url: fin.url, mode });
    } catch (e) {
        etat('erreur', e.message || 'Export interrompu.');
    }
};
</script>
@endpush
