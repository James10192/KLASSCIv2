@once
{{--
    Contrôle avant génération des bulletins LMD, puis suivi de la génération.

    Trois états dans la même fenêtre, sans jamais recharger la page :
      1. « controle »   : ce qui empêche ou accompagne la génération, regroupé
                          par CAUSE (une cause = une phrase, une action), avec
                          la liste des étudiants concernés repliée ;
      2. « generation » : la barre de progression de la tâche de fond ;
      3. « bilan »      : combien de bulletins, et le lien pour les voir.

    Une classe se génère en arrière-plan (tranches, comme le BTS) : on peut
    fermer l'onglet, la planification termine et prévient. Un seul étudiant se
    génère par le formulaire classique, avec un état de chargement visible.
--}}
@php
    $lpPeutPreparer = auth()->user()?->can('academic_health.recalculate') ?? false;
    $lpUrls = [
        'preflight' => route('esbtp.lmd.bulletins.preflight'),
        'tache' => route('esbtp.lmd.bulletins.generer-classe.tache'),
        'preparer' => route('esbtp.lmd.bulletins.preparer-fiches'),
        'liste' => route('esbtp.lmd.bulletins.index'),
    ];
@endphp
<div class="lmd-preflight-backdrop" id="lmd-preflight-modal" aria-hidden="true"
     data-urls='@json($lpUrls)' data-peut-preparer="{{ $lpPeutPreparer ? '1' : '0' }}">
    <div class="lmd-preflight-dialog" role="dialog" aria-modal="true" aria-labelledby="lmd-preflight-title" aria-describedby="lmd-preflight-message">
        <div class="lmd-preflight-header">
            <span class="lmd-preflight-icon" data-lp-icon aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
            <div class="lmd-preflight-head-text">
                <div id="lmd-preflight-title" class="lmd-preflight-title" data-lp-title>Avant de générer</div>
                <div class="lmd-preflight-subtitle" data-lp-subtitle>KLASSCI vérifie que chaque étudiant a de quoi recevoir son bulletin.</div>
            </div>
            <button type="button" class="lmd-preflight-close" data-lmd-preflight-close aria-label="Fermer">&times;</button>
        </div>

        <div class="lmd-preflight-body">
            {{-- 1. Contrôle --}}
            <div data-lp-vue="controle">
                <div id="lmd-preflight-message" class="lmd-preflight-alert" data-lmd-preflight-message role="status"></div>
                <div class="lmd-preflight-feedback" data-lp-feedback role="status" aria-live="polite" hidden></div>
                <div class="lmd-preflight-groupes" data-lp-groupes></div>

                <div class="lmd-preflight-reason" data-lmd-preflight-reason-block hidden>
                    <label class="lmd-preflight-check">
                        <input type="checkbox" data-lp-provisoire>
                        <span>Générer quand même des bulletins <strong>provisoires</strong> pour ces étudiants</span>
                    </label>
                    <div data-lp-motif-bloc hidden>
                        <div class="lmd-preflight-label">Pourquoi maintenant ?</div>
                        <div class="lmd-preflight-chips" role="group" aria-label="Motifs fréquents">
                            <button type="button" class="lmd-preflight-chip" data-lp-motif="Conseil de classe imminent">Conseil de classe</button>
                            <button type="button" class="lmd-preflight-chip" data-lp-motif="Demande de l’étudiant (dossier, bourse, stage)">Demande de l’étudiant</button>
                            <button type="button" class="lmd-preflight-chip" data-lp-motif="Bulletin provisoire en attente des dernières notes">En attente de notes</button>
                        </div>
                        <textarea id="lmd-preflight-reason" class="lmd-preflight-textarea" rows="2" maxlength="1000"
                                  data-lmd-preflight-reason placeholder="Ou écrivez le motif (8 caractères au moins)"></textarea>
                    </div>
                </div>
            </div>

            {{-- 2. Génération en cours --}}
            <div data-lp-vue="generation" hidden>
                <div class="lmd-preflight-progress-head">
                    <strong data-lp-progress-texte>Préparation…</strong>
                    <span data-lp-progress-pct>0 %</span>
                </div>
                <div class="lmd-preflight-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-lp-bar>
                    <span data-lp-bar-fill></span>
                </div>
                <p class="lmd-preflight-hint" data-lp-progress-hint>Vous pouvez fermer cette fenêtre ou la page : la génération continue et une notification apparaîtra dans la cloche à la fin.</p>
            </div>

            {{-- 3. Bilan --}}
            <div data-lp-vue="bilan" hidden>
                <div class="lmd-preflight-alert" data-lp-bilan role="status"></div>
                <ul class="lmd-preflight-names" data-lp-bilan-liste></ul>
            </div>
        </div>

        <div class="lmd-preflight-footer">
            <button type="button" class="lmd-preflight-btn lmd-preflight-btn--secondary" data-lmd-preflight-close data-lp-fermer>Annuler</button>
            <a class="lmd-preflight-btn lmd-preflight-btn--primary" data-lp-voir hidden>Voir les bulletins</a>
            <button type="button" class="lmd-preflight-btn lmd-preflight-btn--primary" data-lmd-preflight-confirm hidden>
                <span class="lmd-preflight-spin" aria-hidden="true" hidden></span><span data-lp-confirm-texte>Générer</span>
            </button>
        </div>
    </div>
</div>

<style>
    .lmd-preflight-backdrop {
        position: fixed; inset: 0; z-index: 1070; display: none;
        align-items: center; justify-content: center; padding: 12px;
        background: rgba(8,25,51,.55);
    }
    .lmd-preflight-backdrop.is-open { display: flex; }
    .lmd-preflight-dialog {
        width: min(600px, 100%); max-height: calc(100dvh - 24px); display: flex; flex-direction: column;
        background: #fff; border-radius: 16px; box-shadow: 0 24px 70px rgba(15,23,42,.28); overflow: hidden;
    }
    .lmd-preflight-header { padding: 1rem 1.25rem; display: flex; gap: .8rem; align-items: center; border-bottom: 1px solid #eef2f7; }
    .lmd-preflight-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #0453cb; }
    .lmd-preflight-icon.is-danger { background: #fef2f2; color: #dc2626; }
    .lmd-preflight-icon.is-ok { background: #ecfdf5; color: #10b981; }
    .lmd-preflight-head-text { flex: 1; min-width: 0; }
    .lmd-preflight-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
    .lmd-preflight-subtitle { font-size: .82rem; color: #64748b; margin-top: .1rem; }
    .lmd-preflight-close { border: 0; background: #f1f5f9; color: #64748b; width: 36px; height: 36px; border-radius: 9px; font-size: 1.3rem; line-height: 1; flex-shrink: 0; }
    .lmd-preflight-body { padding: 1rem 1.25rem; overflow-y: auto; }
    .lmd-preflight-alert { border-radius: 10px; padding: .8rem 1rem; background: #eff6ff; color: #1e3a8a; font-weight: 600; font-size: .9rem; line-height: 1.45; }
    .lmd-preflight-alert.is-danger { background: #fef2f2; color: #991b1b; }
    .lmd-preflight-alert.is-ok { background: #ecfdf5; color: #065f46; }
    .lmd-preflight-feedback { margin-top: .6rem; border-radius: 9px; padding: .6rem .85rem; font-size: .85rem; background: #ecfdf5; color: #065f46; }
    .lmd-preflight-feedback.is-danger { background: #fef2f2; color: #991b1b; }
    .lmd-preflight-groupes { display: flex; flex-direction: column; gap: .65rem; margin-top: .85rem; }
    .lmd-preflight-groupe { border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; }
    .lmd-preflight-groupe.is-blocking { border-left: 4px solid #dc2626; }
    .lmd-preflight-groupe.is-warning { border-left: 4px solid #f59e0b; }
    .lmd-preflight-groupe-tete { display: flex; gap: .6rem; align-items: flex-start; }
    .lmd-preflight-groupe-texte { flex: 1; font-size: .9rem; color: #1e293b; line-height: 1.45; }
    .lmd-preflight-count { flex-shrink: 0; font-size: .74rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; background: #f1f5f9; color: #334155; white-space: nowrap; }
    .lmd-preflight-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .7rem; }
    .lmd-preflight-action { display: inline-flex; align-items: center; gap: .4rem; border-radius: 9px; padding: .5rem .85rem; font-size: .83rem; font-weight: 700; border: 1px solid #c7d4e5; background: #fff; color: #0453cb; text-decoration: none; cursor: pointer; min-height: 40px; }
    .lmd-preflight-action--plein { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lmd-preflight-action:disabled { opacity: .6; cursor: wait; }
    .lmd-preflight-details { margin-top: .55rem; }
    .lmd-preflight-details summary { cursor: pointer; font-size: .82rem; color: #475569; font-weight: 600; min-height: 32px; display: flex; align-items: center; }
    .lmd-preflight-names { margin: .4rem 0 0; padding: 0; list-style: none; columns: 2 220px; font-size: .82rem; color: #334155; }
    .lmd-preflight-names li { padding: .15rem 0; break-inside: avoid; }
    .lmd-preflight-reason { margin-top: 1rem; padding-top: .85rem; border-top: 1px dashed #e2e8f0; }
    .lmd-preflight-check { display: flex; gap: .55rem; align-items: flex-start; font-size: .88rem; color: #1e293b; cursor: pointer; }
    .lmd-preflight-check input { width: 18px; height: 18px; margin-top: 2px; accent-color: #0453cb; }
    .lmd-preflight-label { font-size: .78rem; color: #64748b; font-weight: 700; margin: .75rem 0 .35rem; }
    .lmd-preflight-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .5rem; }
    .lmd-preflight-chip { border: 1px solid #c7d4e5; background: #fff; color: #1e3a8a; border-radius: 999px; padding: .35rem .75rem; font-size: .8rem; font-weight: 600; min-height: 34px; cursor: pointer; }
    .lmd-preflight-chip.is-on { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lmd-preflight-textarea { box-sizing: border-box; width: 100%; border: 1px solid #dce3ef; border-radius: 9px; padding: .6rem .7rem; resize: vertical; font-size: .88rem; }
    .lmd-preflight-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .lmd-preflight-progress-head { display: flex; justify-content: space-between; align-items: baseline; gap: .75rem; font-size: .92rem; color: #0f172a; }
    .lmd-preflight-progress-head span { font-weight: 800; color: #0453cb; }
    .lmd-preflight-bar { margin-top: .6rem; height: 10px; border-radius: 999px; background: #e8eef8; overflow: hidden; }
    .lmd-preflight-bar span { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, #0453cb, #3b7ddb); transition: width .4s ease; }
    .lmd-preflight-bar.is-indetermine span { width: 35%; animation: lmdPfGlisse 1.2s ease-in-out infinite; }
    @@keyframes lmdPfGlisse { 0% { margin-left: -35%; } 100% { margin-left: 100%; } }
    .lmd-preflight-hint { margin: .75rem 0 0; font-size: .82rem; color: #64748b; line-height: 1.45; }
    .lmd-preflight-footer { padding: .85rem 1.25rem; display: flex; justify-content: flex-end; gap: .6rem; border-top: 1px solid #eef2f7; flex-wrap: wrap; }
    .lmd-preflight-btn { border: 0; border-radius: 10px; padding: .6rem 1.1rem; font-weight: 800; font-size: .88rem; min-height: 42px; display: inline-flex; align-items: center; gap: .45rem; text-decoration: none; cursor: pointer; }
    .lmd-preflight-btn[hidden] { display: none; }
    .lmd-preflight-btn--secondary { background: #f1f5f9; color: #334155; }
    .lmd-preflight-btn--primary { background: #0453cb; color: #fff; }
    .lmd-preflight-btn:disabled { opacity: .65; cursor: wait; }
    .lmd-preflight-spin { width: 15px; height: 15px; border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; border-radius: 50%; animation: lmdPfTourne .7s linear infinite; }
    .lmd-preflight-spin[hidden] { display: none; }
    @@keyframes lmdPfTourne { to { transform: rotate(360deg); } }
    /* Le bouton de la page pendant la vérification. */
    [data-lmd-preflight].is-busy { opacity: .75; cursor: wait; }
    @@media (max-width: 576px) {
        .lmd-preflight-dialog { border-radius: 14px; }
        .lmd-preflight-footer .lmd-preflight-btn { flex: 1; justify-content: center; }
        .lmd-preflight-names { columns: 1; }
    }
</style>

@include('esbtp.bulletins.partials.suivi-tache-script')

<script>
if (!window.lmdPreflightModalLoaded) {
    window.lmdPreflightModalLoaded = true;
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('lmd-preflight-modal');
        if (!modal) return;

        const urls = JSON.parse(modal.dataset.urls || '{}');
        const peutPreparer = modal.dataset.peutPreparer === '1';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const $ = (sel) => modal.querySelector(sel);
        const vues = modal.querySelectorAll('[data-lp-vue]');
        const message = $('[data-lmd-preflight-message]');
        const feedback = $('[data-lp-feedback]');
        const groupesNode = $('[data-lp-groupes]');
        const reasonBlock = $('[data-lmd-preflight-reason-block]');
        const provisoire = $('[data-lp-provisoire]');
        const motifBloc = $('[data-lp-motif-bloc]');
        const reasonInput = $('[data-lmd-preflight-reason]');
        const confirmBtn = $('[data-lmd-preflight-confirm]');
        const confirmTexte = $('[data-lp-confirm-texte]');
        const spin = confirmBtn.querySelector('.lmd-preflight-spin');
        const fermer = $('[data-lp-fermer]');
        const voir = $('[data-lp-voir]');
        const icon = $('[data-lp-icon]');
        let pendingForm = null;
        let pendingMode = 'classe';
        let enGeneration = false;
        let seulementPrets = false;
        let libelleLancer = 'Générer';
        let libelleTous = 'Générer';

        const montrer = (vue) => vues.forEach((v) => { v.hidden = v.dataset.lpVue !== vue; });
        const entete = (titre, sousTitre, ton, iconeFa) => {
            $('[data-lp-title]').textContent = titre;
            $('[data-lp-subtitle]').textContent = sousTitre;
            icon.className = 'lmd-preflight-icon' + (ton ? ' is-' + ton : '');
            icon.innerHTML = `<i class="fas ${iconeFa}"></i>`;
        };
        const occupe = (on, texte) => {
            confirmBtn.disabled = on;
            spin.hidden = !on;
            if (texte) confirmTexte.textContent = texte;
        };
        const toast = (type, msg) => window.dispatchEvent(new CustomEvent('toast', { detail: { type, message: msg } }));

        const ouvrir = () => { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); };
        const fermerModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!enGeneration) {
                pendingForm = null;
                confirmBtn.hidden = true;
                reasonBlock.hidden = true;
                provisoire.checked = false;
                motifBloc.hidden = true;
                reasonInput.value = '';
                feedback.hidden = true;
            }
        };
        modal.querySelectorAll('[data-lmd-preflight-close]').forEach((b) => b.addEventListener('click', fermerModal));
        modal.addEventListener('click', (e) => { if (e.target === modal) fermerModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('is-open')) fermerModal(); });

        provisoire.addEventListener('change', () => {
            motifBloc.hidden = !provisoire.checked;
            confirmBtn.hidden = !provisoire.checked && !pendingForm?.dataset.lpPret;
            // Provisoire coché : toute la classe ; sinon, seulement les prêts.
            confirmTexte.textContent = provisoire.checked ? libelleTous : libelleLancer;
            if (provisoire.checked) reasonInput.focus();
        });
        modal.querySelectorAll('[data-lp-motif]').forEach((chip) => chip.addEventListener('click', () => {
            modal.querySelectorAll('[data-lp-motif]').forEach((c) => c.classList.toggle('is-on', c === chip));
            reasonInput.value = chip.dataset.lpMotif;
        }));

        const texteSur = (n, un, plusieurs) => `${n} ${n > 1 ? plusieurs : un}`;

        const groupeHtml = (g) => {
            const carte = document.createElement('div');
            carte.className = 'lmd-preflight-groupe is-' + (g.severity === 'blocking' ? 'blocking' : 'warning');
            const tete = document.createElement('div');
            tete.className = 'lmd-preflight-groupe-tete';
            const texte = document.createElement('div');
            texte.className = 'lmd-preflight-groupe-texte';
            texte.textContent = g.message;
            const compte = document.createElement('span');
            compte.className = 'lmd-preflight-count';
            compte.textContent = texteSur(g.count, 'étudiant', 'étudiants');
            tete.append(texte, compte);
            carte.appendChild(tete);

            const actions = document.createElement('div');
            actions.className = 'lmd-preflight-actions';
            if (g.code === 'grade_sheets_missing' && peutPreparer && pendingMode === 'classe') {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'lmd-preflight-action lmd-preflight-action--plein';
                b.innerHTML = '<i class="fas fa-magic"></i> Préparer les fiches de la classe';
                b.addEventListener('click', () => preparerFiches(b));
                actions.appendChild(b);
            }
            if (g.action_url) {
                const a = document.createElement('a');
                a.className = 'lmd-preflight-action';
                a.href = g.action_url;
                a.innerHTML = '<i class="fas fa-arrow-right"></i> ';
                a.append(g.action_label || 'Corriger');
                actions.appendChild(a);
            }
            if (actions.children.length) carte.appendChild(actions);

            if (g.etudiants?.length && pendingMode === 'classe') {
                const details = document.createElement('details');
                details.className = 'lmd-preflight-details';
                const sum = document.createElement('summary');
                sum.textContent = 'Voir les étudiants concernés';
                const liste = document.createElement('ul');
                liste.className = 'lmd-preflight-names';
                g.etudiants.forEach((e) => {
                    const li = document.createElement('li');
                    const nom = typeof e === 'string' ? e : e.nom;
                    if (e && e.url) {
                        // Un clic ouvre la fiche, sans quitter le contrôle en cours.
                        const a = document.createElement('a');
                        a.href = e.url; a.target = '_blank'; a.rel = 'noopener';
                        a.textContent = nom;
                        a.style.cssText = 'color:#0453cb;font-weight:600;text-decoration:none;';
                        li.appendChild(a);
                    } else {
                        li.textContent = nom;
                    }
                    liste.appendChild(li);
                });
                details.append(sum, liste);
                carte.appendChild(details);
            }
            return carte;
        };

        const afficherControle = (data, form, peutGenerer) => {
            pendingForm = form;
            montrer('controle');
            const bloques = data.blocked_count || 0;
            entete(
                bloques ? ((data.ready_count || 0) > 0 && pendingMode === 'classe' ? 'Presque prêt' : 'Pas encore prêt') : 'Prêt à générer',
                bloques ? 'Voici ce qui manque, et comment le régler.' : 'Vérifiez ces informations avant de lancer.',
                bloques ? 'danger' : 'ok',
                bloques ? 'fa-exclamation-circle' : 'fa-check-circle',
            );
            message.textContent = data.message || 'Contrôle terminé.';
            message.className = 'lmd-preflight-alert' + (bloques ? ' is-danger' : '');
            groupesNode.replaceChildren(...(data.groupes || []).map(groupeHtml));

            reasonBlock.hidden = !data.requires_incomplete_reason;
            // Classe : ceux qui sont prêts n'attendent pas ceux qui ne le sont pas.
            const prets = data.ready_count || 0;
            seulementPrets = pendingMode === 'classe' && bloques > 0 && prets > 0;
            const lancable = (peutGenerer && bloques === 0) || seulementPrets;
            form.dataset.lpPret = lancable ? '1' : '';
            confirmBtn.hidden = !lancable;
            libelleLancer = pendingMode !== 'classe'
                ? 'Générer le bulletin'
                : (seulementPrets
                    ? `Générer ${prets > 1 ? 'les ' + prets + ' bulletins prêts' : 'le bulletin prêt'}`
                    : `Générer ${texteSur(data.students_count || 0, 'bulletin', 'bulletins')}`);
            libelleTous = `Générer ${texteSur(data.students_count || 0, 'bulletin', 'bulletins')}`;
            occupe(false, libelleLancer);
            fermer.textContent = 'Fermer';
            ouvrir();
        };

        async function preparerFiches(bouton) {
            const fd = new FormData(pendingForm);
            bouton.disabled = true;
            bouton.innerHTML = '<span class="lmd-preflight-spin" style="border-color:rgba(255,255,255,.45);border-top-color:#fff"></span> Préparation…';
            try {
                const r = await fetch(urls.preparer, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
                const d = await r.json().catch(() => ({}));
                feedback.hidden = false;
                feedback.className = 'lmd-preflight-feedback' + (r.ok ? '' : ' is-danger');
                feedback.textContent = d.message || (r.ok ? 'Fiches préparées.' : 'Préparation impossible. Réessayez.');
                bouton.remove();
            } catch {
                feedback.hidden = false;
                feedback.className = 'lmd-preflight-feedback is-danger';
                feedback.textContent = 'Connexion interrompue. Réessayez dans un instant.';
                bouton.disabled = false;
                bouton.innerHTML = '<i class="fas fa-magic"></i> Préparer les fiches de la classe';
            }
        }

        // Classe : tâche de fond suivie dans la fenêtre. Étudiant : formulaire.
        async function lancer(form, motif) {
            const champMotif = form.querySelector('[data-lmd-reason-field]');
            if (champMotif) champMotif.value = motif || '';

            if (pendingMode !== 'classe') {
                montrer('generation');
                entete('Génération en cours', 'Calcul des moyennes, des crédits et des mentions.', '', 'fa-cog fa-spin');
                $('[data-lp-progress-texte]').textContent = 'Calcul du bulletin…';
                $('[data-lp-progress-pct]').textContent = '';
                $('[data-lp-bar]').classList.add('is-indetermine');
                $('[data-lp-progress-hint]').textContent = 'Merci de patienter, la page va s’ouvrir sur le bulletin.';
                confirmBtn.hidden = true;
                fermer.hidden = true;
                ouvrir();
                form.submit();
                return;
            }

            occupe(true, 'Lancement…');
            const fd = new FormData(form);
            if (seulementPrets && !motif) fd.set('seulement_prets', '1');
            let lance;
            try {
                const r = await fetch(urls.tache, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
                lance = await r.json().catch(() => ({}));
                if (!r.ok || !lance.tache) throw new Error(lance.message || `Lancement refusé (code ${r.status}).`);
            } catch (err) {
                occupe(false, 'Réessayer');
                feedback.hidden = false;
                feedback.className = 'lmd-preflight-feedback is-danger';
                feedback.textContent = err.message || 'Lancement impossible.';
                return;
            }

            enGeneration = true;
            montrer('generation');
            entete('Génération en cours', lance.tache.libelle || 'Bulletins LMD', '', 'fa-cog fa-spin');
            confirmBtn.hidden = true;
            fermer.textContent = 'Fermer (la génération continue)';
            fermer.hidden = false;
            ouvrir();
            const barre = $('[data-lp-bar]');
            barre.classList.remove('is-indetermine');

            let fin;
            try {
                fin = await window.suivreTacheBulletins({
                    tache: lance.tache,
                    csrf,
                    onEtat: (etat, extra) => {
                        const pct = etat.pourcent || 0;
                        $('[data-lp-bar-fill]').style.width = pct + '%';
                        barre.setAttribute('aria-valuenow', String(pct));
                        $('[data-lp-progress-pct]').textContent = pct + ' %';
                        let t = `${etat.position || 0} / ${etat.total || 0} bulletins calculés`;
                        if (extra?.restant) t += ` · environ ${Math.max(1, Math.round(extra.restant / 60))} min restante(s)`;
                        if (extra?.reseau) t = 'Connexion instable, la génération continue sur le serveur…';
                        $('[data-lp-progress-texte]').textContent = t;
                    },
                });
            } catch (err) {
                enGeneration = false;
                toast('info', err.message || 'Le suivi a été interrompu. La génération continue sur le serveur.');
                fermerModal();
                return;
            }
            enGeneration = false;
            afficherBilan(fin);
        }

        function afficherBilan(tache) {
            montrer('bilan');
            const r = tache.resultat || {};
            const ecrits = (r.created || 0) + (r.regenerated || 0);
            const rates = [...(r.blocking_errors || []), ...(r.errors || [])];
            const ok = tache.statut === 'terminee' && rates.length === 0;
            entete(ok ? 'Bulletins générés' : 'Génération terminée avec des réserves', tache.libelle || '', ok ? 'ok' : (ecrits ? '' : 'danger'), ok ? 'fa-check-circle' : 'fa-info-circle');
            const bilan = $('[data-lp-bilan]');
            bilan.className = 'lmd-preflight-alert' + (ok ? ' is-ok' : (ecrits ? '' : ' is-danger'));
            bilan.textContent = tache.message || `${ecrits} bulletin(s) généré(s).`;
            const liste = $('[data-lp-bilan-liste]');
            liste.replaceChildren(...rates.slice(0, 40).map((e) => {
                const li = document.createElement('li');
                li.textContent = `${e.nom || 'Étudiant #' + e.etudiant_id} : ${e.message || 'non généré'}`;
                return li;
            }));
            voir.hidden = !ecrits;
            voir.href = tache.url || urls.liste;
            fermer.textContent = 'Fermer';
            if (ecrits) toast(ok ? 'success' : 'warning', bilan.textContent);
        }

        confirmBtn.addEventListener('click', () => {
            if (!pendingForm || confirmBtn.disabled) return;
            let motif = '';
            if (!reasonBlock.hidden && provisoire.checked) {
                motif = reasonInput.value.trim();
                if (motif.length < 8) {
                    reasonInput.focus();
                    feedback.hidden = false;
                    feedback.className = 'lmd-preflight-feedback is-danger';
                    feedback.textContent = 'Choisissez un motif ou écrivez-en un (8 caractères au moins).';
                    return;
                }
            }
            lancer(pendingForm, motif);
        });

        document.querySelectorAll('[data-lmd-preflight]').forEach((button) => {
            const libelle = button.innerHTML;
            button.addEventListener('click', async () => {
                const form = document.getElementById(button.dataset.formId);
                if (!form || button.disabled) return;
                pendingMode = button.dataset.mode || 'classe';

                const payload = new FormData(form);
                payload.set('mode', pendingMode);

                // La vérification lit les notes de toute la classe : quelques
                // secondes sur un réseau lent. Le bouton le dit.
                button.disabled = true;
                button.classList.add('is-busy');
                button.innerHTML = '<span class="lmd-preflight-spin"></span> Vérification…';
                try {
                    const response = await fetch(urls.preflight, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: payload,
                    });
                    const data = await response.json();
                    const peutGenerer = response.ok && data.can_generate;
                    const toutPret = peutGenerer && !data.requires_incomplete_reason && !(data.groupes || []).length;
                    if (toutPret) {
                        pendingForm = form;
                        lancer(form, '');
                        return;
                    }
                    afficherControle(data, form, peutGenerer);
                } catch (error) {
                    afficherControle({
                        message: 'Vérification impossible pour le moment (connexion ?). Réessayez dans un instant.',
                        groupes: [], blocked_count: 1,
                    }, form, false);
                } finally {
                    button.disabled = false;
                    button.classList.remove('is-busy');
                    button.innerHTML = libelle;
                }
            });
        });
    });
}
</script>
@endonce
