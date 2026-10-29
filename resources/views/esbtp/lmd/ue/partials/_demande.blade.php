{{-- ══ MODAL Confirmation (remplace confirm() du navigateur) ══
     Un confirm() natif peut etre bloque pour de bon par la case « Ne pas
     autoriser ce site a vous solliciter » : l'action devient alors impossible
     sans un mot. Et il ne sait poser qu'une question oui/non. --}}
<div class="modal fade lu-modal" id="modalDemandeLu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lu-modal-hero w-100">
                    <div class="lu-modal-hero-top">
                        <div class="lu-modal-hero-left">
                            <div class="lu-modal-icon"><i class="fas fa-question"></i></div>
                            <div><h5 class="lu-modal-title" id="dl_titre">Confirmer</h5></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <p class="lu-demande-message" id="dl_message"></p>
                <div class="lu-choix-liste" id="dl_options" role="radiogroup"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button type="button" class="lu-modal-btn lu-modal-btn--submit" id="dl_valider"><i class="fas fa-check"></i> <span id="dl_valider_texte">Confirmer</span></button>
            </div>
        </div>
    </div>
</div>

<script>
function lmdEchapper(texte) {
    const d = document.createElement('div');
    d.textContent = texte == null ? '' : String(texte);
    return d.innerHTML;
}

/**
 * Pose une question dans une fenetre KLASSCI et rend la reponse :
 * true / false sans options, la valeur choisie (ou null) avec options.
 */
function demanderLu({ titre, message, options = null, valider = 'Confirmer', danger = false }) {
    const el = document.getElementById('modalDemandeLu');
    // Deja ouverte (double clic) : une seconde question poserait un second
    // couple d'ecouteurs, et un seul « Valider » repondrait aux deux.
    if (el.classList.contains('show')) return Promise.resolve(options ? null : false);
    const liste = document.getElementById('dl_options');
    const bouton = document.getElementById('dl_valider');
    document.getElementById('dl_titre').textContent = titre;
    document.getElementById('dl_message').textContent = message;
    document.getElementById('dl_valider_texte').textContent = valider;
    bouton.classList.toggle('lu-modal-btn--danger', danger);
    liste.innerHTML = '';

    (options || []).forEach(o => {
        liste.insertAdjacentHTML('beforeend', `
            <label class="lu-choix${o.possible ? '' : ' lu-choix--off'}">
                <input type="radio" name="dl_choix" value="${lmdEchapper(o.valeur)}"${o.possible ? '' : ' disabled'}${o.recommande ? ' checked' : ''}>
                <span class="lu-choix-texte">
                    <span class="lu-choix-titre">${lmdEchapper(o.libelle)}${o.recommande ? ' <span class="lu-choix-reco">Conseillé</span>' : ''}</span>
                    <span class="lu-choix-aide">${lmdEchapper(o.aide)}</span>
                </span>
            </label>`);
    });

    const modal = bootstrap.Modal.getOrCreateInstance(el);
    return new Promise(resolve => {
        let reponse = options ? null : false;
        const surValider = () => {
            if (options) {
                const choisi = liste.querySelector('input[name="dl_choix"]:checked');
                if (!choisi) return;
                reponse = choisi.value;
            } else {
                reponse = true;
            }
            modal.hide();
        };
        const surFermer = () => {
            bouton.removeEventListener('click', surValider);
            el.removeEventListener('hidden.bs.modal', surFermer);
            resolve(reponse);
        };
        bouton.addEventListener('click', surValider);
        el.addEventListener('hidden.bs.modal', surFermer);
        modal.show();
    });
}
</script>
