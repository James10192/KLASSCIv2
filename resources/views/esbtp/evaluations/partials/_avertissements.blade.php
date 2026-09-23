{{-- Les avertissements d'une action faite sans recharger la page (annulation,
     réactivation ou suppression d'une évaluation) : une moyenne laissée sans rien à moyenner,
     un recalcul en échec. Ils disent « vérifiez avant de régénérer les
     bulletins » : ils ne s'effacent pas seuls, ils restent jusqu'à ce qu'on
     les ferme. Le texte et les libellés portent des noms d'élèves : ils sont
     posés en texte, jamais en HTML. Les liens (pré-contrôle des bulletins,
     « Modifier les moyennes ») ne sont envoyés qu'à qui peut ouvrir l'écran
     visé (MoyennesLaissees::liens()).
     Le toast partagé est chargé ici aussi : toastr ne l'est nulle part, et
     sans lui le « supprimée avec succès » de la liste n'allait qu'à la console. --}}
<div id="evaluations-avertissements"></div>
@include('partials._klassci_toast')

<style>
.ev-flash--warning { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.35); color: #92400e; }
.ev-flash-liens { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin-top: .4rem; }
.ev-flash-liens a { color: #0453cb; font-weight: 600; text-decoration: underline; }
/* Sur mobile, la barre d'onglets fixe couvre le bas de l'écran : le défilement s'arrête au-dessus. */
#evaluations-avertissements .ev-flash { scroll-margin: 1rem 0 6rem; }
</style>

<script>
if (typeof window.evAfficherAvertissement !== 'function') {
    window.evAfficherAvertissement = function (message, liens = []) {
        const zone = document.getElementById('evaluations-avertissements');
        if (!zone) {
            console.warn('[Evaluations]', message);
            return;
        }
        const bloc = document.createElement('div');
        bloc.className = 'ev-flash ev-flash--warning';
        bloc.setAttribute('role', 'alert');
        const icone = document.createElement('i');
        icone.className = 'fas fa-exclamation-triangle';
        const texte = document.createElement('span');
        texte.textContent = message;
        if (Array.isArray(liens) && liens.length) {
            const actions = document.createElement('span');
            actions.className = 'ev-flash-liens';
            liens.forEach((lien) => {
                const a = document.createElement('a');
                a.href = lien.url;
                a.textContent = lien.libelle;
                actions.appendChild(a);
            });
            texte.appendChild(actions);
        }
        const fermer = document.createElement('button');
        fermer.type = 'button';
        fermer.className = 'ev-flash-close';
        fermer.setAttribute('aria-label', 'Fermer');
        fermer.innerHTML = '<i class="fas fa-times"></i>';
        fermer.addEventListener('click', () => bloc.remove());
        bloc.append(icone, texte, fermer);
        zone.appendChild(bloc);
        bloc.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };
}
</script>
