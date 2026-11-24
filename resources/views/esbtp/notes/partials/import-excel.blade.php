{{--
    Import Excel des notes d'une matière (BTS) ou d'un élément constitutif (LMD),
    partagé par /esbtp/notes et /esbtp/lmd/notes.

    La page ouvre la fenêtre avec son contexte :
        window.nieOuvrir({ classeId, matiereId, periode: 'semestre3', anneeId, libelle })
    et recharge sa grille sur l'événement « notes-excel:importe ».

    Le fichier se vérifie d'abord (aucune écriture), puis s'enregistre tout ou
    rien. Les colonnes ajoutées et remplies deviennent des évaluations.
--}}
@can('notes.import_excel')
@php
    $_nieRoutes = [
        'simuler' => route('esbtp.notes.import.dry-run'),
        'appliquer' => route('esbtp.notes.import.apply'),
    ];
@endphp
<style>
    .nie-dialog { position: fixed; inset: 0; margin: auto; box-sizing: border-box; width: min(760px, calc(100vw - 24px)); max-width: calc(100vw - 24px); max-height: calc(100dvh - 24px); overflow-y: auto; border: 0; border-radius: 16px; padding: 0; background: #fff; color: #1e293b; box-shadow: 0 24px 60px rgba(15,23,42,.25); }
    .nie-dialog::backdrop { background: rgba(8,25,51,.55); }
    .nie-tete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem .9rem; border-bottom: 1px solid #e2e8f0; }
    .nie-tete h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #0f172a; }
    .nie-tete p { margin: .2rem 0 0; font-size: .82rem; color: #64748b; }
    .nie-fermer { border: 0; background: transparent; color: #64748b; font-size: 1.1rem; cursor: pointer; padding: .25rem .4rem; border-radius: 8px; }
    .nie-fermer:hover { background: #f1f5f9; color: #0f172a; }
    .nie-corps { padding: 1rem 1.25rem; }
    .nie-depot { border: 2px dashed #c7d4e5; border-radius: 12px; padding: 1.25rem; text-align: center; color: #64748b; font-size: .88rem; cursor: pointer; transition: all .2s ease; }
    .nie-depot:hover, .nie-depot.is-survol { border-color: #0453cb; background: rgba(4,83,203,.04); color: #0453cb; }
    .nie-depot i { font-size: 1.4rem; display: block; margin-bottom: .35rem; }
    .nie-aide { font-size: .8rem; color: #64748b; margin: .75rem 0 0; }
    .nie-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin: .9rem 0 .6rem; }
    .nie-chip { padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: rgba(4,83,203,.08); color: #0453cb; }
    .nie-chip--ko { background: rgba(220,38,38,.08); color: #dc2626; }
    .nie-nouvelles { font-size: .84rem; color: #0f172a; background: #eff6ff; border-radius: 10px; padding: .55rem .75rem; margin-bottom: .6rem; }
    .nie-erreurs { font-size: .82rem; color: #dc2626; background: rgba(220,38,38,.05); border-radius: 10px; padding: .55rem .75rem; margin-bottom: .6rem; max-height: 160px; overflow-y: auto; }
    .nie-table-wrap { max-height: 280px; overflow: auto; border: 1px solid #e2e8f0; border-radius: 10px; }
    .nie-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .nie-table th, .nie-table td { border-bottom: 1px solid #eef2f7; padding: .4rem .55rem; text-align: left; white-space: nowrap; }
    .nie-table th { position: sticky; top: 0; background: #f8fafc; color: #64748b; font-weight: 600; font-size: .74rem; text-transform: uppercase; }
    .nie-pied { display: flex; justify-content: flex-end; gap: .5rem; padding: .85rem 1.25rem 1.1rem; border-top: 1px solid #e2e8f0; }
    .nie-btn { display: inline-flex; align-items: center; gap: .45rem; min-height: 40px; padding: .5rem 1rem; border-radius: 10px; font-weight: 600; font-size: .86rem; border: 1px solid #0453cb; background: #0453cb; color: #fff; cursor: pointer; transition: all .2s ease; }
    .nie-btn:hover:not(:disabled) { background: #033a8e; }
    .nie-btn--ghost { background: #fff; color: #0453cb; }
    .nie-btn--ghost:hover:not(:disabled) { background: rgba(4,83,203,.06); }
    .nie-btn:disabled { opacity: .55; cursor: not-allowed; }
    @@media (max-width: 576px) { .nie-pied { flex-direction: column-reverse; } .nie-btn { justify-content: center; } }
</style>

<dialog class="nie-dialog" id="nie-dialog" aria-labelledby="nie-titre">
    <div class="nie-tete">
        <div>
            <h2 id="nie-titre"><i class="fas fa-file-import me-1" aria-hidden="true"></i> Importer des notes Excel</h2>
            <p id="nie-contexte"></p>
        </div>
        <button type="button" class="nie-fermer" data-nie-fermer aria-label="Fermer"><i class="fas fa-times"></i></button>
    </div>
    <div class="nie-corps">
        <input type="file" id="nie-fichier" accept=".xlsx,.xls" hidden>
        <div class="nie-depot" id="nie-depot" role="button" tabindex="0">
            <i class="fas fa-file-arrow-up" aria-hidden="true"></i>
            <span id="nie-depot-texte">Choisissez ou déposez le fichier Excel rempli (.xlsx)</span>
        </div>
        <p class="nie-aide">Utilisez le fichier téléchargé avec « Notes Excel » pour cette matière et ce semestre. KLASSCI montre d'abord ce qui va changer ; rien n'est enregistré avant votre confirmation. Une colonne « Évaluation » ajoutée et remplie devient une évaluation.</p>

        <div id="nie-resultat" hidden>
            <div class="nie-chips">
                <span class="nie-chip" id="nie-n-creer"></span>
                <span class="nie-chip" id="nie-n-modifier"></span>
                <span class="nie-chip" id="nie-n-inchange"></span>
                <span class="nie-chip nie-chip--ko" id="nie-n-erreurs"></span>
            </div>
            <div class="nie-nouvelles" id="nie-nouvelles" hidden></div>
            <div class="nie-erreurs" id="nie-erreurs" hidden></div>
            <div class="nie-table-wrap" id="nie-table-wrap">
                <table class="nie-table">
                    <thead><tr><th>Ligne</th><th>Étudiant</th><th>Évaluation</th><th>Avant</th><th>Après</th></tr></thead>
                    <tbody id="nie-changements"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="nie-pied">
        <button type="button" class="nie-btn nie-btn--ghost" data-nie-fermer>Annuler</button>
        <button type="button" class="nie-btn" id="nie-enregistrer" disabled><i class="fas fa-check" aria-hidden="true"></i> <span>Enregistrer ces notes</span></button>
    </div>
</dialog>

<script>
(function () {
    if (window.nieOuvrir) return;
    const routes = @json($_nieRoutes);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    let contexte = null, fichier = null, declencheur = null, enCours = false;

    function toast(type, message) {
        // L'écran BTS a sa pile de toasts ; ailleurs, le composant global écoute « toast ».
        if (typeof window.nmShowToast === 'function') { window.nmShowToast(type, message); return; }
        window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
    }
    function echapper(v) { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; }

    function reinitialiser() {
        fichier = null;
        $('nie-fichier').value = '';
        $('nie-depot-texte').textContent = 'Choisissez ou déposez le fichier Excel rempli (.xlsx)';
        $('nie-resultat').hidden = true;
        $('nie-enregistrer').disabled = true;
    }

    function corps() {
        const f = new FormData();
        f.append('file', fichier);
        f.append('classe_id', contexte.classeId);
        f.append('matiere_id', contexte.matiereId);
        f.append('periode', contexte.periode);
        if (contexte.anneeId) f.append('annee_universitaire_id', contexte.anneeId);
        return f;
    }

    async function envoyer(url) {
        const res = await fetch(url, { method: 'POST', body: corps(), credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json().catch(() => ({}));
        if (res.status === 429) throw new Error('Trop de tentatives : patientez une minute puis recommencez.');
        if (!res.ok || !data.success) {
            const detail = (data.errors && data.errors.length) ? data : null;
            const err = new Error(data.message || (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors) ? Object.values(data.errors).flat()[0] : null) || 'Le fichier n\'a pas pu être traité.');
            err.detail = detail;
            throw err;
        }
        return data;
    }

    function afficher(data) {
        const s = data.summary || {};
        $('nie-n-creer').textContent = (s.will_create || 0) + ' note(s) à créer';
        $('nie-n-modifier').textContent = (s.will_update || 0) + ' modifiée(s)';
        $('nie-n-inchange').textContent = (s.unchanged || 0) + ' inchangée(s)';
        const erreurs = data.errors || [];
        $('nie-n-erreurs').textContent = erreurs.length + ' erreur(s)';
        $('nie-n-erreurs').hidden = erreurs.length === 0;

        // Seules les colonnes ajoutées qui portent une note seront créées.
        const remplies = new Set((data.changes || []).map(c => c.evaluation_id));
        const nouvelles = (data.evaluations || []).filter(e => e.nouvelle && remplies.has(e.id)).map(e => e.titre);
        $('nie-nouvelles').hidden = nouvelles.length === 0;
        $('nie-nouvelles').innerHTML = '<i class="fas fa-plus-circle me-1"></i>' + (nouvelles.length > 1 ? 'Évaluations créées : ' : 'Évaluation créée : ') + nouvelles.map(echapper).join(', ');

        $('nie-erreurs').hidden = erreurs.length === 0;
        $('nie-erreurs').innerHTML = erreurs.slice(0, 50).map(e => '<div>Ligne ' + echapper(e.row) + ' ' + echapper(e.col || '') + ' : ' + echapper(e.reason) + '</div>').join('');

        const changes = data.changes || [];
        $('nie-table-wrap').hidden = changes.length === 0;
        $('nie-changements').innerHTML = changes.slice(0, 300).map(c => '<tr><td>' + echapper(c.row) + '</td><td>' + echapper(c.etudiant_nom) + '</td><td>' + echapper(c.evaluation) + '</td><td>' + (c.before == null ? '—' : echapper(c.before)) + '</td><td><strong>' + echapper(c.after) + '</strong></td></tr>').join('');
        $('nie-resultat').hidden = false;
        $('nie-enregistrer').disabled = erreurs.length > 0 || changes.length === 0;
        if (!erreurs.length && !changes.length) toast('info', 'Aucune note à enregistrer dans ce fichier.');
    }

    async function choisir(f) {
        if (!f || enCours) return;
        if (!/\.(xlsx|xls)$/i.test(f.name)) { toast('error', 'Choisissez un fichier Excel (.xlsx).'); return; }
        if (f.size > 5 * 1024 * 1024) { toast('error', 'Fichier trop volumineux (5 Mo au plus).'); return; }
        fichier = f;
        $('nie-depot-texte').textContent = 'Lecture de « ' + f.name + ' »…';
        enCours = true;
        try {
            afficher(await envoyer(routes.simuler));
            $('nie-depot-texte').textContent = f.name + ' — cliquez pour choisir un autre fichier';
        } catch (e) {
            reinitialiser();
            toast('error', e.message);
        } finally { enCours = false; }
    }

    async function enregistrer() {
        if (!fichier || enCours) return;
        enCours = true;
        const btn = $('nie-enregistrer');
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Enregistrement…';
        try {
            const data = await envoyer(routes.appliquer);
            toast('success', data.message || 'Notes enregistrées.');
            fermer();
            window.dispatchEvent(new CustomEvent('notes-excel:importe', { detail: { contexte, resultat: data } }));
        } catch (e) {
            toast('error', e.message);
            btn.disabled = false;
        } finally {
            enCours = false;
            btn.querySelector('span').textContent = 'Enregistrer ces notes';
        }
    }

    // Ouverte depuis une modale Bootstrap, la fenêtre se ferait voler le focus
    // par son piège à focus : on le suspend le temps de l'import.
    let pieceBootstrap = null;
    function suspendreModaleBootstrap() {
        const ouverte = document.querySelector('.modal.show');
        const inst = ouverte && window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(ouverte) : null;
        pieceBootstrap = inst && inst._focustrap ? inst._focustrap : null;
        if (pieceBootstrap) pieceBootstrap.deactivate();
    }

    function fermer() {
        const d = $('nie-dialog');
        if (d.open) d.close();
    }

    window.nieOuvrir = function (ctx) {
        if (!ctx || !ctx.classeId || !ctx.matiereId || !/^semestre([1-9]|10)$/.test(String(ctx.periode || ''))) {
            toast('warning', 'Choisissez la classe, la matière et un semestre avant d\'importer.');
            return;
        }
        contexte = ctx;
        declencheur = document.activeElement;
        reinitialiser();
        $('nie-contexte').textContent = ctx.libelle || '';
        const d = $('nie-dialog');
        if (d.parentElement !== document.body) document.body.appendChild(d);
        suspendreModaleBootstrap();
        d.showModal();
    };

    const d = $('nie-dialog');
    d.addEventListener('close', () => {
        if (pieceBootstrap) { pieceBootstrap.activate(); pieceBootstrap = null; }
        if (declencheur && declencheur.focus) declencheur.focus();
    });
    d.querySelectorAll('[data-nie-fermer]').forEach(b => b.addEventListener('click', fermer));
    $('nie-depot').addEventListener('click', () => $('nie-fichier').click());
    $('nie-depot').addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $('nie-fichier').click(); } });
    $('nie-fichier').addEventListener('change', (e) => choisir(e.target.files && e.target.files[0]));
    $('nie-depot').addEventListener('dragover', (e) => { e.preventDefault(); $('nie-depot').classList.add('is-survol'); });
    $('nie-depot').addEventListener('dragleave', () => $('nie-depot').classList.remove('is-survol'));
    $('nie-depot').addEventListener('drop', (e) => { e.preventDefault(); $('nie-depot').classList.remove('is-survol'); choisir(e.dataTransfer.files && e.dataTransfer.files[0]); });
    $('nie-enregistrer').addEventListener('click', enregistrer);
})();
</script>
@endcan
