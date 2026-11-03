{{-- Fenêtre de confirmation partagée : window.klassciConfirmer(message, options) rend une Promise<bool>.
     Remplace window.confirm, que KLASSCI n'utilise pas (règle de design). Sûr en double inclusion. --}}
<div id="kcd-voile" class="kcd-voile" hidden>
    <div class="kcd-fenetre" role="alertdialog" aria-modal="true" aria-labelledby="kcd-titre" aria-describedby="kcd-message">
        <div class="kcd-tete">
            <span class="kcd-icone" aria-hidden="true"><i class="fas fa-triangle-exclamation"></i></span>
            <h2 id="kcd-titre" class="kcd-titre">Confirmer</h2>
        </div>
        <p id="kcd-message" class="kcd-message"></p>
        <div class="kcd-pied">
            <button type="button" class="kcd-btn kcd-btn--secondaire" data-kcd="non">Retour</button>
            <button type="button" class="kcd-btn kcd-btn--primaire" data-kcd="oui">Confirmer</button>
        </div>
    </div>
</div>
<style>
.kcd-voile { position: fixed; inset: 0; z-index: 100000; background: rgba(15,23,42,.5); display: flex; align-items: center; justify-content: center; padding: 1rem; }
.kcd-voile[hidden] { display: none; }
.kcd-fenetre { background: #fff; border-radius: 14px; width: min(440px, 100%); padding: 1.5rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.kcd-tete { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; }
.kcd-icone { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(4,83,203,.1); color: #0453cb; flex-shrink: 0; }
.kcd-fenetre.kcd--danger .kcd-icone { background: rgba(220,38,38,.1); color: #dc2626; }
.kcd-titre { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.kcd-message { color: #1e293b; font-size: .92rem; line-height: 1.5; margin: 0 0 1.25rem; }
.kcd-pied { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.kcd-btn { min-height: 44px; padding: .55rem 1.1rem; border-radius: 10px; font-weight: 600; font-size: .88rem; border: 1px solid transparent; cursor: pointer; transition: background .2s ease; }
.kcd-btn--secondaire { background: #fff; border-color: #e2e8f0; color: #1e293b; }
.kcd-btn--secondaire:hover { background: #f8fafc; }
.kcd-btn--primaire { background: #0453cb; color: #fff; }
.kcd-btn--primaire:hover { background: #033a8e; }
.kcd-fenetre.kcd--danger .kcd-btn--primaire { background: #dc2626; }
.kcd-btn:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
</style>
<script>
if (typeof window.klassciConfirmer !== 'function') {
    window.klassciConfirmer = function (message, options) {
        options = options || {};
        var voile = document.getElementById('kcd-voile');
        if (!voile) { return Promise.resolve(false); }
        var fenetre = voile.querySelector('.kcd-fenetre');
        var oui = voile.querySelector('[data-kcd="oui"]');
        var non = voile.querySelector('[data-kcd="non"]');
        voile.querySelector('#kcd-titre').textContent = options.titre || 'Confirmer';
        voile.querySelector('#kcd-message').textContent = message;
        oui.textContent = options.libelle || 'Confirmer';
        fenetre.classList.toggle('kcd--danger', !!options.danger);
        var retour = document.activeElement;
        voile.hidden = false;
        non.focus();
        return new Promise(function (resolve) {
            function fermer(valeur) {
                voile.hidden = true;
                oui.removeEventListener('click', surOui);
                non.removeEventListener('click', surNon);
                voile.removeEventListener('click', surVoile);
                document.removeEventListener('keydown', surTouche);
                if (retour && typeof retour.focus === 'function') { retour.focus(); }
                resolve(valeur);
            }
            function surOui() { fermer(true); }
            function surNon() { fermer(false); }
            function surVoile(e) { if (e.target === voile) { fermer(false); } }
            function surTouche(e) { if (e.key === 'Escape') { fermer(false); } }
            oui.addEventListener('click', surOui);
            non.addEventListener('click', surNon);
            voile.addEventListener('click', surVoile);
            document.addEventListener('keydown', surTouche);
        });
    };
}
</script>
