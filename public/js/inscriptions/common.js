/**
 * common.js — helpers partages par les pages inscriptions (index, administration, sous-reserve).
 *
 * Expose sur window :
 *  - iiConfirm(options) : Promise<bool> — modal BS5 premium
 *  - showToast(message, type, duration) : toast inline 5s auto-dismiss
 *  - updateKpisFromStats(stats) : met a jour les KPIs selon stats object
 *  - triggerRowHighlight(tr, mode) : flash animation sur <tr> (success ou reject)
 *
 * Ces fonctions sont surchargeable page par page si besoin.
 */
(function () {
    'use strict';

    // =========================================================================
    // iiConfirm — Promise-based BS5 modal confirmation
    // =========================================================================
    window.iiConfirm = window.iiConfirm || function (options) {
        return new Promise((resolve) => {
            const opts = Object.assign({
                title: 'Confirmer',
                message: 'Voulez-vous continuer ?',
                confirmLabel: 'Confirmer',
                cancelLabel: 'Annuler',
                danger: false,
            }, options || {});

            let modal = document.getElementById('ii-common-confirm-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'ii-common-confirm-modal';
                modal.className = 'modal fade';
                modal.tabIndex = -1;
                modal.setAttribute('aria-labelledby', 'ii-common-confirm-title');
                modal.setAttribute('aria-hidden', 'true');
                modal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content" style="border:none; border-radius:14px; overflow:hidden;">
                            <div class="modal-header" style="background:linear-gradient(135deg,#0453cb 0%,#3b7ddb 100%); color:#fff; border:none; padding:16px 20px;">
                                <h5 class="modal-title" id="ii-common-confirm-title" style="font-size:1rem; font-weight:700;" data-role="title"></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body" style="padding:20px; font-size:.9rem; color:#334155;" data-role="message"></div>
                            <div class="modal-footer" style="border:none; padding:14px 20px;">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-role="cancel"></button>
                                <button type="button" class="btn btn-primary" data-role="confirm" style="font-weight:600;"></button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            modal.querySelector('[data-role="title"]').textContent = opts.title;
            modal.querySelector('[data-role="message"]').textContent = opts.message;
            const cancelBtn = modal.querySelector('[data-role="cancel"]');
            const confirmBtn = modal.querySelector('[data-role="confirm"]');
            cancelBtn.textContent = opts.cancelLabel;
            confirmBtn.textContent = opts.confirmLabel;
            confirmBtn.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
            confirmBtn.style.fontWeight = '600';

            const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            let decided = false;

            // LA PROMESSE SE RESOUT A `hidden`, PAS AU CLIC, ET C'EST LOAD-BEARING.
            //
            // Elle se resolvait au clic, pendant que la modale entamait son
            // fondu de fermeture. Un appelant qui enchaine DEUX confirmations —
            // c'est le cas du retrait de maquette quand la matiere porte des
            // evaluations — rappelait alors `show()` sur LA MEME modale en
            // pleine transition. Verifie dans le bundle reellement charge
            // (bootstrap 5.3.0, `layouts/app.blade.php`) :
            //
            //   show(t){this._isShown||this._isTransitioning|| ...
            //
            // La seconde modale ne s'ouvrait donc pas, et le `hidden` de la
            // premiere resolvait la seconde promesse a `false` : le retrait
            // etait annule EN SILENCE. Exactement le defaut pour lequel ces
            // confirmations ont quitte les boites natives du navigateur,
            // reintroduit par une autre porte.
            //
            // `_hideModal()` pose `this._isTransitioning=!1` AVANT d'emettre
            // `hidden.bs.modal` (meme bundle) : resoudre la promesse dans
            // `onHidden` rend donc le chainage correct par construction, sans
            // temporisation ni compteur.
            //
            // LE BOUTON N'EST ARME QU'A `shown`, POUR LA MEME RAISON. Pendant le
            // fondu d'ouverture, `hide()` ne fait rien (meme garde
            // `_isTransitioning`). Arme des l'appel, un clic sur Confirmer a cet
            // instant posait `decided = true` sans fermer, consommait l'ecouteur
            // `once`, et la sortie suivante — Annuler, la croix, Echap — resolvait
            // alors a `true` : « Annuler » validait. Arme a `shown`, ce clic-la
            // est simplement ignore.
            const onConfirm = () => { decided = true; bsModal.hide(); };
            const onShown = () => confirmBtn.addEventListener('click', onConfirm, { once: true });
            const onHidden = () => { resolve(decided); cleanup(); };
            const cleanup = () => {
                confirmBtn.removeEventListener('click', onConfirm);
                modal.removeEventListener('shown.bs.modal', onShown);
                modal.removeEventListener('hidden.bs.modal', onHidden);
            };

            modal.addEventListener('shown.bs.modal', onShown, { once: true });
            modal.addEventListener('hidden.bs.modal', onHidden, { once: true });
            bsModal.show();
        });
    };

    // =========================================================================
    // showToast — toast inline 5s auto-dismiss, 4 types
    // =========================================================================
    window.showToast = window.showToast || function (message, type, duration) {
        type = type || 'info';
        duration = duration || 5000;

        let container = document.getElementById('ii-common-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ii-common-toast-container';
            container.style.cssText = 'position:fixed; top:1rem; right:1rem; z-index:10050; display:flex; flex-direction:column; gap:.5rem;';
            document.body.appendChild(container);
        }

        const colors = {
            success: { bg: '#10b981', border: '#059669', icon: 'fa-check-circle' },
            info:    { bg: '#0453cb', border: '#033a8e', icon: 'fa-info-circle' },
            warning: { bg: '#f59e0b', border: '#d97706', icon: 'fa-exclamation-triangle' },
            error:   { bg: '#dc2626', border: '#991b1b', icon: 'fa-times-circle' },
        };
        const c = colors[type] || colors.info;

        const toast = document.createElement('div');
        toast.style.cssText = `
            background:${c.bg}; color:#fff; border-left:4px solid ${c.border};
            padding:.85rem 1.1rem; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.15);
            display:flex; align-items:center; gap:.6rem; font-size:.88rem; font-weight:500;
            max-width:380px; animation:iiToastSlideIn .25s ease-out;
        `;
        toast.innerHTML = `<i class="fas ${c.icon}" style="font-size:1.05rem;"></i><span style="flex:1;">${message}</span><button type="button" style="background:transparent; border:none; color:rgba(255,255,255,.85); cursor:pointer; padding:0 .2rem; font-size:1rem;"><i class="fas fa-times"></i></button>`;

        const closeBtn = toast.querySelector('button');
        const dismiss = () => {
            toast.style.animation = 'iiToastSlideOut .25s ease-in forwards';
            setTimeout(() => toast.remove(), 250);
        };
        closeBtn.addEventListener('click', dismiss);
        container.appendChild(toast);
        setTimeout(dismiss, duration);

        // Style animations once
        if (!document.getElementById('ii-common-toast-style')) {
            const st = document.createElement('style');
            st.id = 'ii-common-toast-style';
            st.textContent = `
                @keyframes iiToastSlideIn { from { transform:translateX(120%); opacity:0; } to { transform:translateX(0); opacity:1; } }
                @keyframes iiToastSlideOut { from { transform:translateX(0); opacity:1; } to { transform:translateX(120%); opacity:0; } }
            `;
            document.head.appendChild(st);
        }
    };

    // =========================================================================
    // updateKpisFromStats — met a jour les KPIs via attribut data-kpi="<key>"
    // =========================================================================
    window.updateKpisFromStats = window.updateKpisFromStats || function (stats) {
        if (!stats || typeof stats !== 'object') return;
        Object.entries(stats).forEach(([key, value]) => {
            const el = document.querySelector(`[data-kpi="${key}"] [data-kpi-value]`);
            if (el) el.textContent = value;
        });
    };

    // =========================================================================
    // triggerRowHighlight — flash animation sur td de la row
    // =========================================================================
    window.triggerRowHighlight = window.triggerRowHighlight || function (tr, mode) {
        if (!tr) return;
        mode = mode || 'success';
        const cells = tr.querySelectorAll('td');
        cells.forEach((td) => {
            td.classList.remove('ii-flash-success', 'ii-flash-reject');
            void td.offsetWidth;
            td.classList.add(mode === 'reject' ? 'ii-flash-reject' : 'ii-flash-success');
            setTimeout(() => td.classList.remove('ii-flash-success', 'ii-flash-reject'), 1600);
        });
    };

    // Styles globaux pour triggerRowHighlight
    if (!document.getElementById('ii-common-flash-style')) {
        const st = document.createElement('style');
        st.id = 'ii-common-flash-style';
        st.textContent = `
            @keyframes iiFlashSuccess { 0% { background-color: rgba(16,185,129,0); } 40% { background-color: rgba(16,185,129,.35); } 100% { background-color: rgba(16,185,129,0); } }
            @keyframes iiFlashReject { 0% { background-color: rgba(220,38,38,0); } 40% { background-color: rgba(220,38,38,.35); } 100% { background-color: rgba(220,38,38,0); } }
            .ii-flash-success { animation: iiFlashSuccess 1.6s ease-out; }
            .ii-flash-reject { animation: iiFlashReject 1.6s ease-out; }
        `;
        document.head.appendChild(st);
    }
})();
