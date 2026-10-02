{{-- Étape suivante d'un workflow, pour la personne qui vient d'agir et qui a
     le droit de la faire (namespace wns-*). WorkflowFlash la pose en session.

     L'ancienne fenêtre s'ouvrait même sur la page où l'étape se fait, avec un
     bouton bleu « Valider ce paiement » qui n'était qu'un lien vers cette même
     page, et la même allure que la vraie confirmation : on cliquait, on
     croyait avoir validé, rien ne l'était.

     - L'étape se fait ICI : un bandeau discret, sans bouton d'action, qui dit
       que rien n'est encore fait et où se trouve le vrai bouton.
     - Elle se fait AILLEURS : une fenêtre neutre, sans fond bloquant, dont le
       bouton dit qu'il OUVRE une page, jamais qu'il valide. --}}
@if(session('workflow_next_step') && session('workflow_next_step.url'))
@php
    $_wns = session('workflow_next_step');
    // Meme adresse que la page ou l'on arrive (sans la requete) : l'etape se fait ici.
    $_wnsIci = rtrim(strtok((string) $_wns['url'], '?'), '/') === rtrim(url()->current(), '/');
    $_wnsEtape = $_wns['label'] ?? 'Étape suivante';
@endphp
@if($_wnsIci)
    <div class="wns-bandeau" role="status" data-fenetre-prioritaire x-data="{ ouvert: true }" x-show="ouvert" x-transition.opacity>
        <span class="wns-icone"><i class="fas fa-circle-info"></i></span>
        <div class="wns-texte">
            <strong>Prochaine étape, sur cette page : {{ $_wnsEtape }}.</strong>
            <span>Rien n'est encore fait : utilisez le bouton de la page pour la faire.</span>
        </div>
        <button type="button" class="wns-fermer" x-on:click="ouvert = false" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
    </div>
@else
    <div class="modal fade" id="workflowNextStepModal" data-fenetre-prioritaire tabindex="-1" aria-labelledby="wnsTitre" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content wns-fenetre">
                <div class="wns-tete">
                    <span class="wns-icone"><i class="fas fa-route"></i></span>
                    <div>
                        <h5 class="wns-titre" id="wnsTitre">Et maintenant ?</h5>
                        <p class="wns-sous-titre">Votre action est enregistrée. L'étape suivante se fait sur une autre page.</p>
                    </div>
                    <button type="button" class="wns-fermer" data-bs-dismiss="modal" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="wns-corps">
                    <div class="wns-etape">
                        <span class="wns-etape-libelle">Prochaine étape</span>
                        <span class="wns-etape-nom">{{ $_wnsEtape }}</span>
                        <span class="wns-etape-note">Pas encore faite : elle vous attend sur sa page.</span>
                    </div>
                </div>
                <div class="wns-pied">
                    <button type="button" class="wns-btn wns-btn--ghost" data-bs-dismiss="modal">Plus tard</button>
                    <a href="{{ $_wns['url'] }}" class="wns-btn wns-btn--lien">Ouvrir la page<i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    {{-- Sur téléphone la fenêtre ne s'ouvre pas d'elle-même : un bandeau la remplace. --}}
    <div class="wns-bandeau wns-bandeau--telephone" role="status" x-data="{ ouvert: true }" x-show="ouvert">
        <span class="wns-icone"><i class="fas fa-route"></i></span>
        <div class="wns-texte">
            <strong>Prochaine étape : {{ $_wnsEtape }}.</strong>
            <span>Pas encore faite. <a href="{{ $_wns['url'] }}" class="wns-lien">Ouvrir la page</a></span>
        </div>
        <button type="button" class="wns-fermer" x-on:click="ouvert = false" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => {
        // Shell mobile : pas d'ouverture automatique sous 992px (rappel non bloquant).
        if (window.matchMedia('(max-width:991.98px)').matches) return;
        new bootstrap.Modal(document.getElementById('workflowNextStepModal')).show();
    });</script>
@endif
<style>
    .wns-bandeau { position: fixed; right: 1.25rem; top: 5rem; z-index: 1040; max-width: 420px; display: flex; align-items: flex-start; gap: .75rem; background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #0453cb; border-radius: 12px; padding: .85rem 1rem; box-shadow: 0 8px 30px rgba(4,83,203,.12), 0 2px 8px rgba(15,23,42,.06); }
    .wns-bandeau .wns-icone { color: #0453cb; font-size: 1.05rem; margin-top: .1rem; }
    .wns-texte { display: flex; flex-direction: column; gap: .15rem; font-size: .85rem; color: #475569; }
    .wns-texte strong { color: #1e293b; font-weight: 700; }
    .wns-fermer { margin-left: auto; width: 30px; height: 30px; flex-shrink: 0; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; }
    .wns-fermer:hover { background: #f1f5f9; }
    .wns-fenetre { border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .wns-tete { display: flex; align-items: flex-start; gap: .85rem; padding: 1.1rem 1.25rem .5rem; }
    .wns-tete .wns-icone { width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .wns-titre { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
    .wns-sous-titre { margin: .15rem 0 0; font-size: .84rem; color: #64748b; }
    .wns-corps { padding: .5rem 1.25rem 1rem; }
    .wns-etape { display: flex; flex-direction: column; gap: .2rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: .85rem 1rem; }
    .wns-etape-libelle { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
    .wns-etape-nom { font-size: .98rem; font-weight: 700; color: #1e293b; }
    .wns-etape-note { font-size: .8rem; color: #64748b; }
    .wns-pied { display: flex; justify-content: flex-end; gap: .5rem; padding: .85rem 1.25rem; border-top: 1px solid #eef2f7; }
    .wns-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .5rem .95rem; font-size: .86rem; font-weight: 600; border: 1px solid #cbd5e1; background: #fff; color: #475569; text-decoration: none; transition: background .2s ease, border-color .2s ease; }
    .wns-btn:hover { background: #f1f5f9; color: #1e293b; }
    .wns-btn--lien { color: #0453cb; border-color: rgba(4,83,203,.35); }
    .wns-btn--lien:hover { background: rgba(4,83,203,.06); color: #033a8e; }
    .wns-lien { color: #0453cb; font-weight: 600; }
    .wns-bandeau--telephone { display: none; }
    @media (max-width: 991.98px) { .wns-bandeau--telephone { display: flex; } }
    @media (max-width: 576px) { .wns-bandeau { left: 1rem; right: 1rem; top: 4.5rem; max-width: none; } }
</style>
@endif
