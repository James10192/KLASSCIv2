{{--
    KLASSCI Care — fenetre « Aide / Signaler ».

    Incluse une fois par le layout. Ne rend rien tant que KLASSCI Care n'est pas
    ouvert a l'instance (DisponibiliteSupport). Les boutons qui l'ouvrent portent
    `data-support-ouvrir` (menu du compte, feuille mobile, page d'erreur) : pas de
    bulle flottante, le coin bas-droit appartient deja a l'assistant.

    Le contexte de la page est calcule ICI, cote serveur, a partir de la route
    servie ; le navigateur n'y ajoute que ce qu'il est seul a connaitre (taille
    d'ecran, fuseau). Le serveur le re-verifie a la soumission.
--}}
@auth
@php
    $_spDisponibilite = app(\App\Domain\Support\Services\DisponibiliteSupport::class);
    $_spOuvert = $_spDisponibilite->signalement();
@endphp
@if($_spOuvert)
@php
    $_spConfig = [
        'url' => route('support.demandes.store'),
        'suivi' => $_spDisponibilite->suivi() ? route('support.demandes.index') : null,
        'page' => \App\Domain\Support\Services\ContexteDePage::courant(request()),
        'requestId' => request()->attributes->get('request_id'),
        'categories' => collect(config('support.categories'))->map(fn ($c, $code) => ['code' => $code] + $c)->values(),
        'supportEmail' => config('app.support_email'),
        'limites' => $_spLimites = app(\App\Services\Care\ClientMasterSupport::class)->limites(),
        // Le brouillon est range sous l'utilisateur : sur un poste partage, le
        // suivant ne retrouve ni le texte ni la cle du precedent.
        'utilisateur' => auth()->id(),
    ];
@endphp
<div class="modal fade sp-modal" id="sp-modal" tabindex="-1" aria-labelledby="sp-modal-titre" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="sp-head">
                <div class="sp-head-icon"><i class="fas fa-life-ring"></i></div>
                <div>
                    <h2 id="sp-modal-titre">Aide &amp; signalement</h2>
                    <p>Expliquez-nous simplement : nous joignons le contexte technique pour vous.</p>
                </div>
                <button type="button" class="sp-fermer" data-bs-dismiss="modal" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="sp-body">
                {{-- Etape 1 : que se passe-t-il, et racontez --}}
                <section class="sp-etape" data-sp-etape="saisie">
                    <p class="sp-question" id="sp-choix-titre">Que se passe-t-il ?</p>
                    <div class="sp-choix" role="group" aria-labelledby="sp-choix-titre" data-sp-choix></div>
                    <label class="sp-question sp-question--suite" for="sp-description">Expliquez simplement ce qui s'est passé.</label>
                    <textarea id="sp-description" class="sp-textarea" rows="5" maxlength="{{ $_spLimites['description_max'] }}"
                              placeholder="Par exemple : « Je clique sur Valider les notes et rien ne se passe. »"></textarea>
                    <div class="sp-aide-saisie"><span data-sp-compteur>0</span> / {{ $_spLimites['description_max'] }}</div>
                    <div class="sp-erreur" data-sp-erreur role="alert" hidden></div>
                    <div class="sp-actions">
                        <button type="button" class="sp-btn sp-btn--primaire" data-sp-aller="recap">Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>

                {{-- Etape 2 : voici ce que nous transmettons --}}
                <section class="sp-etape" data-sp-etape="recap" hidden>
                    <button type="button" class="sp-retour" data-sp-aller="saisie"><i class="fas fa-arrow-left"></i> Modifier</button>
                    <p class="sp-question" tabindex="-1" data-sp-focus>Voici ce que nous allons transmettre.</p>
                    <dl class="sp-recap">
                        <dt>Type</dt><dd data-sp-recap-categorie></dd>
                        <dt>Votre message</dt><dd class="sp-recap-texte" data-sp-recap-description></dd>
                        <dt>Page</dt><dd data-sp-recap-page></dd>
                    </dl>
                    <p class="sp-note"><i class="fas fa-shield-halved"></i>
                        Nous joignons automatiquement la page, votre navigateur et un code de suivi technique. Aucun contenu de la page n'est transmis.</p>
                    <div class="sp-erreur" data-sp-erreur role="alert" hidden></div>
                    <div class="sp-actions">
                        <button type="button" class="sp-btn sp-btn--primaire" data-sp-envoyer>
                            <span data-sp-envoyer-libelle>Envoyer</span>
                        </button>
                    </div>
                </section>

                {{-- Etape 3 : c est recu --}}
                <section class="sp-etape sp-fin" data-sp-etape="fin" hidden>
                    <div class="sp-fin-icon"><i class="fas fa-check"></i></div>
                    <p class="sp-question" tabindex="-1" data-sp-focus data-sp-fin-titre>Demande reçue</p>
                    <p class="sp-fin-texte" data-sp-fin-texte></p>
                    <div class="sp-actions sp-actions--centre">
                        <a class="sp-btn sp-btn--secondaire" data-sp-suivi hidden>Suivre ma demande</a>
                        <button type="button" class="sp-btn sp-btn--primaire" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    .sp-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.18); }
    /* La barre basse du shell mobile (z 1080) passe au-dessus du fond Bootstrap (1050) et
       cachait le bas de la fenetre : on la renvoie sous le fond tant que la fenetre est ouverte. */
    body:has(#sp-modal.show) .m-bottomnav, body:has(#sp-modal.show) .m-fab { z-index: 1040; }
    .sp-head { display: flex; align-items: flex-start; gap: .9rem; padding: 1.25rem 1.5rem; color: #fff;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); }
    .sp-head-icon { width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); font-size: 1.15rem; }
    .sp-head h2 { font-size: 1.1rem; font-weight: 700; margin: 0; color: #fff; }
    .sp-head p { font-size: .82rem; margin: .15rem 0 0; color: rgba(255,255,255,.75); }
    .sp-fermer { margin-left: auto; background: rgba(255,255,255,.14); border: 0; color: #fff; width: 32px; height: 32px; border-radius: 8px; }
    .sp-fermer:hover { background: rgba(255,255,255,.24); }
    .sp-body { padding: 1.25rem 1.5rem 1.5rem; }
    .sp-question { font-weight: 700; color: #1e293b; font-size: .98rem; margin: 0 0 .85rem; display: block; }
    .sp-question--suite { margin-top: 1.1rem; }
    .sp-choix { display: flex; flex-wrap: wrap; gap: .45rem; }
    .sp-pastille { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .8rem; border: 1px solid #e2e8f0;
        border-radius: 999px; background: #fff; color: #1e293b; font-size: .82rem; font-weight: 600;
        transition: border-color .15s ease, background .15s ease, color .15s ease; }
    .sp-pastille i { color: #0453cb; }
    .sp-pastille:hover, .sp-pastille:focus-visible { border-color: #0453cb; outline: none; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .sp-pastille[aria-pressed="true"] { background: #0453cb; border-color: #0453cb; color: #fff; }
    .sp-pastille[aria-pressed="true"] i { color: #fff; }
    .sp-retour { background: none; border: 0; color: #0453cb; font-size: .82rem; font-weight: 600; padding: 0; margin-bottom: .75rem; }
    .sp-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: .75rem .9rem; font-size: .92rem; resize: vertical; min-height: 130px; }
    .sp-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .sp-aide-saisie { text-align: right; font-size: .72rem; color: #64748b; margin-top: .3rem; }
    .sp-erreur { margin-top: .75rem; padding: .6rem .8rem; border-radius: 10px; background: rgba(220,38,38,.07); color: #b91c1c; font-size: .84rem; }
    .sp-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
    .sp-actions--centre { justify-content: center; }
    .sp-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .6rem 1.1rem; font-size: .86rem; font-weight: 600;
        border: 1px solid transparent; text-decoration: none; transition: background .15s ease; }
    .sp-btn--primaire { background: #0453cb; color: #fff; }
    .sp-btn--primaire:hover { background: #033a8e; color: #fff; }
    .sp-btn--primaire:disabled { opacity: .6; cursor: wait; }
    .sp-btn--secondaire { background: #fff; color: #0453cb; border-color: #bfd3f2; }
    .sp-recap { display: grid; grid-template-columns: 110px 1fr; gap: .45rem .9rem; margin: 0; font-size: .86rem; }
    .sp-recap dt { color: #64748b; font-weight: 600; }
    .sp-recap dd { margin: 0; color: #1e293b; }
    .sp-recap-texte { white-space: pre-line; max-height: 160px; overflow: auto; }
    .sp-note { margin: 1rem 0 0; padding: .65rem .8rem; border-radius: 10px; background: #f8fafc; color: #475569; font-size: .8rem; }
    .sp-note i { color: #0453cb; margin-right: .3rem; }
    .sp-fin { text-align: center; padding: .5rem 0; }
    .sp-fin-icon { width: 56px; height: 56px; margin: 0 auto .8rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: rgba(16,185,129,.12); color: #10b981; font-size: 1.4rem; }
    .sp-fin-texte { color: #475569; font-size: .88rem; }
    .sp-fin-texte strong { font-family: 'Courier New', monospace; color: #0453cb; }
    @media (max-width: 576px) {
        .sp-recap { grid-template-columns: 1fr; }
        .sp-body { padding: 1rem; }
    }
</style>
<script>window.KLASSCI_SUPPORT = @json($_spConfig);</script>
<script src="{{ asset('js/support/signalement.js') }}?v={{ filemtime(public_path('js/support/signalement.js')) }}" defer></script>
@endif
@endauth
