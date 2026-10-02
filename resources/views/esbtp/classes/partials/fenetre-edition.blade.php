{{-- Fenêtres création / modification d'une classe (namespace cfm-*).
     Pensées pour enchaîner : chaque aller-retour au serveur coûte ~0,5 s sur
     l'hébergement. Le formulaire se prépare à l'avance, l'enregistrement
     renvoie la carte à jour, « Enregistrer et modifier la suivante » passe à la
     classe d'après sans fermer la fenêtre. La création reste pilotée par
     classes/index (bouton du hero). --}}
<div class="modal fade cfm-modal" id="createClasseModal" tabindex="-1" aria-labelledby="createClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content cfm-content">
            <div class="modal-header cfm-tete">
                <div class="cfm-tete-gauche">
                    <span class="cfm-icone"><i class="fas fa-plus"></i></span>
                    <div>
                        <h5 class="modal-title cfm-titre" id="createClasseModalLabel">Nouvelle classe</h5>
                        <p class="cfm-sous-titre">Nom, niveau, filière ou mention, et capacité.</p>
                    </div>
                </div>
                <button type="button" class="cfm-fermer" data-bs-dismiss="modal" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="modal-body cfm-corps" id="modal-create-body">
                <div class="cfm-attente"><span class="spinner-border text-primary" role="status"></span><span>Chargement du formulaire…</span></div>
            </div>
            <div class="modal-footer cfm-pied">
                <span class="cfm-raccourci"><kbd>Ctrl</kbd> + <kbd>Entrée</kbd> pour enregistrer</span>
                <div class="cfm-pied-actions">
                    <button type="button" class="cfm-btn cfm-btn--ghost" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="cfm-btn cfm-btn--primaire" id="modal-create-submit-btn" disabled>
                        <i class="fas fa-save"></i>Enregistrer la classe
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     MODAL ÉDITION CLASSE (AJAX) — namespace cfm-*
     Pensée pour enchaîner : formulaire préparé à l'avance,
     « Enregistrer et modifier la suivante ».
     ======================================== --}}
<div class="modal fade cfm-modal" id="editClasseModal" tabindex="-1" aria-labelledby="editClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content cfm-content">
            <div class="modal-header cfm-tete">
                <div class="cfm-tete-gauche">
                    <span class="cfm-icone"><i class="fas fa-pen"></i></span>
                    <div>
                        <h5 class="modal-title cfm-titre" id="editClasseModalLabel">Modifier la classe</h5>
                        <p class="cfm-sous-titre" id="editClasseModalSubtitle">Classe</p>
                    </div>
                </div>
                <button type="button" class="cfm-fermer" data-bs-dismiss="modal" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="modal-body cfm-corps" id="modal-edit-body">
                <div class="cfm-attente"><span class="spinner-border text-primary" role="status"></span><span>Chargement du formulaire…</span></div>
            </div>
            <div class="modal-footer cfm-pied">
                <span class="cfm-raccourci"><kbd>Ctrl</kbd> + <kbd>Entrée</kbd> pour enregistrer</span>
                <div class="cfm-pied-actions">
                    <button type="button" class="cfm-btn cfm-btn--ghost" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="cfm-btn cfm-btn--secondaire" id="modal-edit-next-btn" disabled hidden>
                        <i class="fas fa-forward"></i>Enregistrer et modifier la suivante
                    </button>
                    <button type="button" class="cfm-btn cfm-btn--primaire" id="modal-edit-submit-btn" disabled>
                        <i class="fas fa-save"></i>Mettre à jour la classe
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* =========================================
   FENÊTRES CRÉATION / MODIFICATION — namespace cfm-*
   ========================================= */
.cfm-modal .cfm-content { border: none; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.22); }
.cfm-modal .cfm-tete { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); color: #fff; border: none; padding: 1.1rem 1.5rem; align-items: center; }
.cfm-tete-gauche { display: flex; align-items: center; gap: .85rem; min-width: 0; }
.cfm-icone { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
.cfm-modal .cfm-titre { color: #fff; font-weight: 700; font-size: 1.12rem; margin: 0; }
.cfm-sous-titre { margin: .1rem 0 0; color: rgba(255,255,255,.75); font-size: .84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 52ch; }
.cfm-fermer { margin-left: auto; width: 36px; height: 36px; border-radius: 10px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.12); color: #fff; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }
.cfm-fermer:hover { background: rgba(255,255,255,.22); }
.cfm-modal .cfm-corps { background: #f8fafc; padding: 1.25rem 1.5rem; }
.cfm-attente { display: flex; align-items: center; justify-content: center; gap: .75rem; padding: 3rem 0; color: #64748b; font-size: .9rem; }
/* Le formulaire partagé (partials/form) prend l'allure des cartes premium dans la fenêtre. */
.cfm-corps form > .row { margin-bottom: 0 !important; }
.cfm-corps .card { border: 1px solid #e2e8f0 !important; border-radius: 14px !important; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06) !important; margin-bottom: 1rem !important; background: #fff; }
.cfm-corps .card-header { background: #fff !important; border-bottom: 1px solid #eef2f7 !important; padding: .85rem 1.1rem; }
.cfm-corps .card-header h6 { font-weight: 700; color: #1e293b; font-size: .92rem; }
.cfm-corps .card-header h6 i { width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; }
.cfm-corps .card-body { padding: 1rem 1.1rem .25rem; }
.cfm-corps .form-label { font-size: .78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .35rem; }
.cfm-corps .form-control, .cfm-corps .form-select { border: 1px solid #cbd5e1; border-radius: 10px; padding: .55rem .8rem; font-size: .9rem; transition: border-color .2s ease, box-shadow .2s ease; }
.cfm-corps .form-control:focus, .cfm-corps .form-select:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.cfm-corps .select2-container--bootstrap4 .select2-selection { border: 1px solid #cbd5e1; border-radius: 10px; min-height: calc(1.5em + 1.1rem + 2px); }
.cfm-corps .form-check-input:checked { background-color: #0453cb; border-color: #0453cb; }
.cfm-modal .cfm-pied { background: #fff; border-top: 1px solid #e2e8f0; padding: .85rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
.cfm-pied-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-left: auto; }
.cfm-raccourci { font-size: .76rem; color: #64748b; }
.cfm-raccourci kbd { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 5px; padding: .05rem .35rem; font-size: .72rem; box-shadow: none; }
.cfm-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-size: .86rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; transition: background .2s ease, border-color .2s ease, color .2s ease; white-space: nowrap; }
.cfm-btn:disabled { opacity: .55; cursor: not-allowed; }
.cfm-btn--ghost { background: transparent; color: #475569; border-color: #cbd5e1; }
.cfm-btn--ghost:hover { background: #f1f5f9; }
.cfm-btn--secondaire { background: rgba(4,83,203,.08); color: #0453cb; border-color: rgba(4,83,203,.25); }
.cfm-btn--secondaire:hover:not(:disabled) { background: rgba(4,83,203,.14); }
.cfm-btn--primaire { background: #0453cb; color: #fff; }
.cfm-btn--primaire:hover:not(:disabled) { background: #033a8e; }
@media (max-width: 576px) {
    .cfm-modal .cfm-tete, .cfm-modal .cfm-corps, .cfm-modal .cfm-pied { padding-left: 1rem; padding-right: 1rem; }
    .cfm-raccourci { display: none; }
    .cfm-pied-actions { width: 100%; }
    .cfm-pied-actions .cfm-btn { flex: 1 1 auto; justify-content: center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('editClasseModal') || window.__fenetreEditionClasse) return;
    window.__fenetreEditionClasse = true;

    const editModalEl = document.getElementById('editClasseModal');
    const editClasseModal = new bootstrap.Modal(editModalEl);
    const modalEditBody = document.getElementById('modal-edit-body');
    const modalEditSubmitBtn = document.getElementById('modal-edit-submit-btn');
    // ---------------------------------------------------------------
    // Edition a la chaine : chaque aller-retour au serveur coute ~0,5 s
    // sur l'hebergement. Le formulaire d'une classe se prepare des que
    // son menu s'ouvre, celui de la suivante pendant qu'on remplit la
    // fenetre, et l'enregistrement renvoie la carte a jour.
    // ---------------------------------------------------------------
    const formulairesEdition = new Map();
    let classeEnEdition = null;
    let enchainerVers = null;
    const modalEditNextBtn = document.getElementById('modal-edit-next-btn');

    function chargerFormulaireEdition(classeId) {
        const enCache = formulairesEdition.get(classeId);
        if (enCache && Date.now() - enCache.le < 60000) return enCache.promesse;
        const promesse = fetch(`/esbtp/classes/${classeId}/edit?ajax=1`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        }).then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.text();
        });
        const entree = { promesse, le: Date.now(), pret: false };
        formulairesEdition.set(classeId, entree);
        promesse.then(() => { entree.pret = true; }, () => formulairesEdition.delete(classeId));
        return promesse;
    }

    function cartesModifiables() {
        return Array.from(document.querySelectorAll('#classes-grid .btn-open-edit-modal[data-classe-id]'))
            .map(b => b.getAttribute('data-classe-id'));
    }

    function classeSuivante(classeId) {
        const ids = cartesModifiables();
        const i = ids.indexOf(String(classeId));
        return i >= 0 && i + 1 < ids.length ? ids[i + 1] : null;
    }

    function nomDeLaClasse(classeId) {
        const carte = document.querySelector(`#classes-grid .ci-card[data-classe-id="${classeId}"]`);
        const titre = carte ? carte.querySelector('.ci-card-title, h3, h4, h5') : null;
        return titre ? titre.textContent.trim() : '';
    }

    function ouvrirEdition(classeId) {
        classeEnEdition = String(classeId);
        const suivante = classeSuivante(classeEnEdition);
        const nom = nomDeLaClasse(classeEnEdition);
        const sousTitre = document.getElementById('editClasseModalSubtitle');
        if (sousTitre) sousTitre.textContent = nom || 'Classe';
        if (modalEditNextBtn) {
            modalEditNextBtn.hidden = !suivante;
            modalEditNextBtn.title = suivante ? `Puis : ${nomDeLaClasse(suivante)}` : '';
        }

        // Tant que le formulaire n'est pas arrive, on attend : jamais
        // l'ancien formulaire sous le nom de la nouvelle classe.
        const enCache = formulairesEdition.get(classeEnEdition);
        if (!enCache || !enCache.pret) {
            modalEditBody.innerHTML = `
                <div class="cfm-attente"><span class="spinner-border text-primary" role="status"></span><span>Chargement du formulaire…</span></div>
            `;
        }
        modalEditSubmitBtn.disabled = true;
        if (modalEditNextBtn) modalEditNextBtn.disabled = true;
        modalEditSubmitBtn.setAttribute('data-classe-id', classeEnEdition);

        // La fenetre s'ouvre d'abord : un echec de chargement reste visible.
        editClasseModal.show();

        const demande = classeEnEdition;
        chargerFormulaireEdition(demande)
            .then(html => {
                if (demande !== classeEnEdition) return;
                // Servi une fois : une reouverture relit la classe a jour.
                formulairesEdition.delete(demande);
                injectHtmlWithScripts(modalEditBody, html);
                initClasseFormScripts('modal-edit-classe-form');
                modalEditSubmitBtn.disabled = false;
                if (modalEditNextBtn) modalEditNextBtn.disabled = false;
                const premier = document.getElementById('modal-edit-classe-form_name');
                if (premier) premier.focus({ preventScroll: true });
                if (suivante) chargerFormulaireEdition(suivante);
            })
            .catch(error => {
                if (demande !== classeEnEdition) return;
                console.error('Erreur chargement formulaire édition:', error);
                modalEditBody.innerHTML = `
                    <div class="ci-alert ci-alert--danger">
                        <i class="fas fa-exclamation-triangle"></i>Impossible de charger le formulaire (${error.message}). Veuillez réessayer ou signaler ce message.
                    </div>
                `;
            });
    }

    // Le menu d'une carte s'ouvre : on prepare son formulaire.
    document.addEventListener('show.bs.dropdown', function(e) {
        const carte = e.target.closest ? e.target.closest('#classes-grid .ci-card[data-classe-id]') : null;
        if (carte && carte.querySelector('.btn-open-edit-modal')) {
            chargerFormulaireEdition(carte.getAttribute('data-classe-id'));
        }
    });

    // Click handler délégation pour .btn-open-edit-modal
    document.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-open-edit-modal');
        if (!btnEdit) return;

        e.preventDefault();
        const classeId = btnEdit.getAttribute('data-classe-id');
        if (!classeId) return;
        enchainerVers = null;
        ouvrirEdition(classeId);
    });

    // Ctrl/Cmd + Entree enregistre la fenetre ouverte.
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' || !(e.ctrlKey || e.metaKey)) return;
        const ouverte = document.querySelector('.cfm-modal.show');
        if (!ouverte) return;
        const bouton = ouverte.id === 'editClasseModal' ? modalEditSubmitBtn : document.getElementById('modal-create-submit-btn');
        if (bouton && !bouton.disabled) { e.preventDefault(); bouton.click(); }
    });

    if (modalEditNextBtn) {
        modalEditNextBtn.addEventListener('click', function() {
            const form = document.getElementById('modal-edit-classe-form');
            if (!form || !form.reportValidity()) return;
            enchainerVers = classeSuivante(classeEnEdition);
            form.requestSubmit();
        });
    }

    if (modalEditSubmitBtn) {
        modalEditSubmitBtn.addEventListener('click', function() {
            const form = document.getElementById('modal-edit-classe-form');
            // Un « suivante » refuse par la validation du navigateur ne doit
            // pas faire enchainer l'enregistrement simple d'apres.
            enchainerVers = null;
            if (form) form.requestSubmit();
        });
    }

    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'modal-edit-classe-form') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = e.target;
            const versSuivante = enchainerVers;
            enchainerVers = null;
            modalEditSubmitBtn.disabled = true;
            if (modalEditNextBtn) modalEditNextBtn.disabled = true;
            modalEditSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mise à jour...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                modalEditSubmitBtn.disabled = false;
                if (modalEditNextBtn) modalEditNextBtn.disabled = false;
                modalEditSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour la classe';
                if (data.success) {
                    formulairesEdition.delete(String(data.classe.id));
                    updateClasseCard(data.classe, data.message || 'La classe a été mise à jour.', data.html || null);
                    if (versSuivante) {
                        ouvrirEdition(versSuivante);
                    } else {
                        editClasseModal.hide();
                    }
                } else {
                    displayValidationErrors(data.errors, 'modal-edit-classe-form');
                }
            })
            .catch(error => {
                console.error('Erreur soumission formulaire:', error);
                alert('Une erreur est survenue lors de la mise à jour.');
                modalEditSubmitBtn.disabled = false;
                if (modalEditNextBtn) modalEditNextBtn.disabled = false;
                modalEditSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour la classe';
            });

            return false;
        }
    }, true);
});
</script>
