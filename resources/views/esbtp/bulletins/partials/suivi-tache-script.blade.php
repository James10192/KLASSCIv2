{{--
    Suivre une tâche longue de bulletins depuis la page qui l'a lancée.

    Tant que l'onglet reste ouvert, il fait avancer la tâche d'une tranche par
    requête : le travail va aussi vite qu'avant. S'il se ferme, rien n'est
    perdu : la planification reprend à la tranche suivante, et le demandeur
    est prévenu à la fin (cloche, toast, e-mail).

    Script en ligne avec garde d'idempotence : le partial est inclus par deux
    pages et pourrait l'être deux fois.
--}}
<script>
if (typeof window.suivreTacheBulletins !== 'function') {
    /**
     * @param {object} o
     * @param {object} o.tache   état rendu par le serveur au lancement
     * @param {string} o.csrf
     * @param {(etat:object, extra:object)=>void} o.onEtat
     * @returns {Promise<object>} l'état final de la tâche
     */
    window.suivreTacheBulletins = async function ({ tache, csrf, onEtat }) {
        const gabarit = {
            avancer: @json(route('esbtp.bulletins.taches.avancer', ['tache' => '__ID__'])),
            etat: @json(route('esbtp.bulletins.taches.etat', ['tache' => '__ID__'])),
            vue: @json(route('esbtp.bulletins.taches.vue', ['tache' => '__ID__'])),
        };
        const url = (cle) => gabarit[cle].replace('__ID__', String(tache.id));
        const entete = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        const pause = (ms) => new Promise((r) => setTimeout(r, ms));

        // Le toast global ne doit pas doubler ce que la page affiche déjà.
        window.dispatchEvent(new CustomEvent('taches-bulletins:nouvelle', { detail: tache }));
        window.dispatchEvent(new CustomEvent('taches-bulletins:suivie', { detail: { id: tache.id } }));

        let etat = tache;
        let relais = false;          // le serveur a lâché une requête : on ne fait que lire…
        let positionAuRelais = null; // …jusqu'à voir le travail repartir
        const debut = Date.now();
        const depart = tache.position || 0;

        const restant = () => {
            const faits = (etat.position || 0) - depart;
            if (faits <= 0) return null;
            const parElement = (Date.now() - debut) / 1000 / faits;
            return parElement * Math.max(0, (etat.total || 0) - (etat.position || 0));
        };

        onEtat(etat, { restant: null, relais });

        while (!etat.finale) {
            let reponse;
            try {
                reponse = relais
                    ? await fetch(url('etat'), { headers: entete })
                    : await fetch(url('avancer'), { method: 'POST', headers: entete });
            } catch {
                onEtat(etat, { restant: restant(), relais: true, reseau: true });
                await pause(10000);
                continue;
            }

            if (reponse.status === 419 || reponse.status === 401) {
                throw new Error('Votre session a expiré. Le travail continue sur le serveur : vous serez prévenu(e) à la fin.');
            }

            const charge = await reponse.json().catch(() => null);
            if (!reponse.ok || !charge?.success) {
                // Une requête coupée par l'hébergement (limite d'exécution) ne
                // perd pas le travail : la planification le reprend. On se
                // contente désormais de suivre.
                if (reponse.status >= 500 || charge === null) {
                    relais = true;
                    positionAuRelais = etat.position || 0;
                    onEtat(etat, { restant: restant(), relais });
                    await pause(10000);
                    continue;
                }
                throw new Error(charge?.message || `Suivi interrompu (code ${reponse.status}).`);
            }

            etat = charge.tache;
            // Une requête coupée une fois n'est pas une raison de ne plus jamais
            // aider : dès que le travail a repris, l'onglet reprend la main.
            if (relais && (etat.position || 0) > positionAuRelais) {
                relais = false;
            }
            onEtat(etat, { restant: restant(), relais: relais || !!charge.occupee });

            if (!etat.finale && (relais || charge.occupee)) {
                await pause(relais ? 10000 : 4000);
            }
        }

        fetch(url('vue'), { method: 'POST', headers: entete }).catch(() => {});

        return etat;
    };
}

if (typeof window.demanderConfirmationCourriel !== 'function') {
    /**
     * Demande l'envoi du lien de confirmation d'adresse (route du support).
     * Rend le message à afficher : le serveur le formule, succès ou refus.
     */
    window.demanderConfirmationCourriel = async function (url, csrf) {
        try {
            const reponse = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (reponse.status === 429) {
                return 'Un lien vient déjà de partir. Réessayez dans quelques minutes.';
            }
            const charge = await reponse.json().catch(() => null);
            return charge?.message || `Le lien n'a pas pu partir (code ${reponse.status}).`;
        } catch {
            return 'Connexion perdue. Vérifiez le réseau, puis réessayez.';
        }
    };
}
</script>
