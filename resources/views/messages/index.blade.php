@extends('layouts.app')

@section('title', 'Messages & Centre d’actions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/messages-hub-v2.css') }}">
@endpush

@section('content')
<div class="container-fluid py-2 py-md-3">
    <div class="mh2-root" data-message-hub-v2>
        <script type="application/json" data-message-hub-config>{!! json_encode([
            'bootstrap' => route('message-hub.bootstrap'),
            'conversationBase' => url('/message-hub/conversations'),
            'stateBase' => url('/message-hub/conversations'),
            'actionBase' => url('/message-hub/actions'),
            'assignees' => route('message-hub.actions.assignees'),
            'linkBase' => url('/message-hub/entity-links'),
            'legacyReadBase' => url('/message-hub/legacy-actions'),
            'sendBase' => url('/messages/conversations'),
            'usersSearch' => route('chat.users.search'),
            'startDm' => route('chat.dm.start'),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

        <header class="mh2-top">
            <div class="mh2-brand">
                <h1>Messages</h1>
                <p>Échanges humains, dossiers liés et actions métier — chacun à sa place.</p>
            </div>
            <div class="mh2-tabs" role="tablist" aria-label="Espaces du module">
                <button type="button" class="mh2-tab is-active" data-space="inbox" role="tab" aria-selected="true">
                    <i class="fas fa-inbox" aria-hidden="true"></i> Boîte de réception
                    <span class="mh2-count" data-inbox-count>0</span>
                </button>
                <button type="button" class="mh2-tab" data-space="actions" role="tab" aria-selected="false">
                    <i class="fas fa-list-check" aria-hidden="true"></i> Centre d’actions
                    <span class="mh2-count" data-action-count>0</span>
                </button>
            </div>
            <button type="button" class="mh2-primary" data-new>
                <i class="fas fa-plus" aria-hidden="true"></i><span>Nouveau</span>
            </button>
        </header>

        <section class="mh2-workspace" data-inbox>
            <aside class="mh2-sidebar" aria-label="Conversations">
                <div class="mh2-list-head">
                    <label class="mh2-search">
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" data-conversation-search placeholder="Rechercher nom, rôle ou message…" aria-label="Rechercher dans les conversations">
                    </label>
                    <div class="mh2-filters" aria-label="Filtres de conversations">
                        <button type="button" class="mh2-filter is-active" data-filter="all">Tous</button>
                        <button type="button" class="mh2-filter" data-filter="unread">Non lus</button>
                        <button type="button" class="mh2-filter" data-filter="important">Importants</button>
                        <button type="button" class="mh2-filter" data-filter="groups">Groupes</button>
                        <button type="button" class="mh2-filter" data-filter="archived">Archivés</button>
                    </div>
                </div>
                <div class="mh2-conversation-list" data-conversation-list aria-live="polite"></div>
            </aside>

            <main class="mh2-thread-pane">
                <header class="mh2-thread-head">
                    <button type="button" class="mh2-icon mh2-mobile-back" data-mobile-back aria-label="Retour aux conversations" title="Retour">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <div class="mh2-thread-main">
                        <h2 data-thread-title>Sélectionnez une conversation</h2>
                        <p data-thread-sub>Les personnes et les dossiers liés sont volontairement séparés.</p>
                    </div>
                    <div class="mh2-head-actions">
                        <button type="button" class="mh2-icon" data-important aria-label="Marquer la conversation importante" aria-pressed="false" title="Important">
                            <i class="fas fa-star" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="mh2-icon" data-archive aria-label="Archiver la conversation" aria-pressed="false" title="Archiver">
                            <i class="fas fa-box-archive" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="mh2-icon" data-context-toggle aria-label="Afficher le contexte lié" title="Contexte lié">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                        </button>
                    </div>
                </header>

                <div class="mh2-thread" data-thread aria-live="polite"></div>

                <footer class="mh2-composer" data-composer hidden>
                    <div class="mh2-ai-strip" data-ai-strip aria-label="Aides de Nanan"></div>
                    <div class="mh2-compose">
                        <textarea rows="1" maxlength="4000" data-compose placeholder="Écrire un message…" aria-label="Votre message"></textarea>
                        <button type="button" class="mh2-send" data-send aria-label="Envoyer le message" title="Envoyer">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                </footer>
            </main>

            <aside class="mh2-context" data-context aria-label="Contexte lié">
                <header class="mh2-context-head">
                    <h3>Contexte lié</h3>
                    <button type="button" class="mh2-icon" data-context-close aria-label="Fermer le contexte">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="mh2-context-body" data-context-body>
                    <div class="mh2-muted">Ouvrez une conversation pour afficher les participants et les dossiers explicitement liés.</div>
                </div>
            </aside>
        </section>

        <section class="mh2-actions" data-actions hidden aria-label="Centre d’actions">
            <div class="mh2-actions-toolbar">
                <label class="mh2-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" data-action-search placeholder="Rechercher une action, un dossier, un service…" aria-label="Rechercher une action">
                </label>
                <select data-action-status aria-label="Filtrer par statut">
                    <option value="">Tous les statuts</option>
                    <option value="todo">À faire</option>
                    <option value="in_progress">En cours</option>
                    <option value="waiting_info">En attente</option>
                    <option value="done">Terminé</option>
                    <option value="rejected">Rejeté</option>
                </select>
                <select data-action-priority aria-label="Filtrer par priorité">
                    <option value="">Toutes les priorités</option>
                    <option value="urgent">Urgente</option>
                    <option value="high">Haute</option>
                    <option value="normal">Normale</option>
                    <option value="low">Faible</option>
                </select>
                <div class="mh2-view-switch" aria-label="Mode d’affichage">
                    <button type="button" class="is-active" data-action-view="list" title="Vue liste"><i class="fas fa-list"></i> Liste</button>
                    <button type="button" data-action-view="kanban" title="Vue Kanban"><i class="fas fa-table-columns"></i> Kanban</button>
                </div>
                <button type="button" class="mh2-primary" data-new><i class="fas fa-plus"></i><span>Créer une action</span></button>
            </div>

            <div class="mh2-actions-body">
                <div class="mh2-action-list" data-action-list></div>
                <div class="mh2-kanban" data-kanban hidden></div>
                <aside class="mh2-action-detail" data-action-detail aria-label="Détail de l’action">
                    <div class="mh2-empty"><h3>Sélectionnez une action</h3><p>Son historique, son affectation et ses changements de statut apparaîtront ici.</p></div>
                </aside>
            </div>
        </section>

        <div class="mh2-modal" data-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mh2-modal-title">
            <div class="mh2-modal-backdrop" data-modal-close></div>
            <div class="mh2-modal-card">
                <header class="mh2-modal-head">
                    <h3 id="mh2-modal-title">Nouveau</h3>
                    <button type="button" class="mh2-icon" data-modal-close aria-label="Fermer"><i class="fas fa-xmark"></i></button>
                </header>
                <div class="mh2-modal-body">
                    <div data-modal-panel="intents">
                        <div class="mh2-intents">
                            <button type="button" class="mh2-intent" data-intent-person>
                                <i class="fas fa-user"></i><span><strong>Écrire à une personne</strong><small>Conversation privée avec un membre du personnel autorisé.</small></span>
                            </button>
                            @can('annonces.create')
                            <a class="mh2-intent" href="{{ route('esbtp.annonces.create') }}">
                                <i class="fas fa-users"></i><span><strong>Écrire à une classe ou promotion</strong><small>Diffusion ciblée via le module d’annonces existant.</small></span>
                            </a>
                            <a class="mh2-intent" href="{{ route('esbtp.annonces.create') }}">
                                <i class="fas fa-user-graduate"></i><span><strong>Contacter un étudiant ou parent</strong><small>Utilise la diffusion ciblée : aucun DM étudiant n’est créé en contournant les droits.</small></span>
                            </a>
                            <a class="mh2-intent" href="{{ route('esbtp.annonces.create') }}">
                                <i class="fas fa-bullhorn"></i><span><strong>Publier une annonce</strong><small>Communication descendante vers les audiences autorisées.</small></span>
                            </a>
                            @endcan
                            <button type="button" class="mh2-intent" data-intent-internal>
                                <i class="fas fa-arrows-turn-to-dots"></i><span><strong>Créer une demande interne</strong><small>Crée une vraie action suivie, affectable et journalisée.</small></span>
                            </button>
                            <button type="button" class="mh2-intent" data-intent-action>
                                <i class="fas fa-list-check"></i><span><strong>Créer une action à traiter</strong><small>Priorité, responsable, échéance, statut et historique.</small></span>
                            </button>
                        </div>
                        <div class="mh2-inline-warning">
                            <i class="fas fa-circle-info"></i>
                            <span>La création de groupe d’équipe n’est pas proposée tant que son CRUD complet et ses permissions ne sont pas implémentés. Aucun bouton factice n’est laissé en production.</span>
                        </div>
                    </div>

                    <div data-modal-panel="person" hidden>
                        <div class="mh2-panel-title">
                            <button type="button" data-back-intents aria-label="Retour"><i class="fas fa-arrow-left"></i></button>
                            <h4>Écrire à une personne</h4>
                        </div>
                        <label class="mh2-search">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="search" data-user-search placeholder="Nom ou adresse e-mail…" aria-label="Rechercher une personne">
                        </label>
                        <div class="mh2-user-results" data-user-results></div>
                    </div>

                    <div data-modal-panel="action" hidden>
                        <div class="mh2-panel-title">
                            <button type="button" data-back-intents aria-label="Retour"><i class="fas fa-arrow-left"></i></button>
                            <h4>Créer une action à traiter</h4>
                        </div>
                        <form class="mh2-action-form" data-action-form onsubmit="return false;">
                            <label class="mh2-field">
                                Type
                                <select name="action_type" required>
                                    <option value="internal_request">Demande interne</option>
                                    <option value="verification">Vérification de dossier</option>
                                    <option value="inscription">Inscription</option>
                                    <option value="paiement">Paiement</option>
                                    <option value="reclamation">Réclamation</option>
                                    <option value="grade">Correction pédagogique</option>
                                    <option value="absence">Absence / justificatif</option>
                                    <option value="document">Pièce / document</option>
                                </select>
                            </label>
                            <label class="mh2-field">
                                Priorité
                                <select name="priority" required>
                                    <option value="normal">Normale</option>
                                    <option value="high">Haute</option>
                                    <option value="urgent">Urgente</option>
                                    <option value="low">Faible</option>
                                </select>
                            </label>
                            <label class="mh2-field is-wide">
                                Titre
                                <input name="title" maxlength="180" required placeholder="Ex. Vérifier la pièce manquante du dossier">
                            </label>
                            <label class="mh2-field is-wide">
                                Personne / dossier concerné
                                <input name="subject" maxlength="255" placeholder="Ex. Inscription — KIPRE JEAN">
                            </label>
                            <label class="mh2-field is-wide">
                                Description
                                <textarea name="description" maxlength="4000" placeholder="Décrivez ce qui doit être vérifié ou traité."></textarea>
                            </label>
                            <label class="mh2-field">
                                Service responsable
                                <input name="service" maxlength="120" placeholder="Scolarité, Caisse, Direction…">
                            </label>
                            <label class="mh2-field">
                                Affecter à
                                <select name="assigned_to" data-action-assignee><option value="">Non affectée</option></select>
                            </label>
                            <label class="mh2-field">
                                Échéance
                                <input type="datetime-local" name="due_at">
                            </label>
                            <div class="mh2-form-actions">
                                <button type="button" class="mh2-btn is-ghost" data-modal-close>Annuler</button>
                                <button type="button" class="mh2-btn" data-action-submit><i class="fas fa-plus"></i> Créer l’action</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="mh2-toast" data-toast role="status" aria-live="polite"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/messages-hub-v2.js') }}" defer></script>
<script src="{{ asset('js/messages-hub-v2-hardening.js') }}" defer></script>
@endpush