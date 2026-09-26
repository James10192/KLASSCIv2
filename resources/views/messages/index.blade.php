@extends('layouts.app')

@section('title', 'Messages')

@php
    $hub = app(\App\Support\Messages\MessageHubProjection::class)->project($conversations, auth()->user());
    $inbox = collect($hub['conversation']);
    $actions = collect($hub['workflowAction']);
    $statusLabels = [
        'todo' => 'À faire',
        'in_progress' => 'En cours',
        'waiting_info' => 'En attente d’information',
        'done' => 'Terminé',
        'rejected' => 'Rejeté',
    ];
    $priorityLabels = [
        'low' => 'Faible',
        'normal' => 'Normale',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/messages-hub.css') }}">
@endpush

@section('content')
<div class="container-fluid py-2 py-md-3">
    <div class="mh-page" data-message-hub>
        <script type="application/json" data-message-hub-config>{!! json_encode([
            'conversationBase' => url('/messages/conversations'),
            'conversationsList' => route('chat.conversations.list'),
            'usersSearch' => route('chat.users.search'),
            'startDm' => route('chat.dm.start'),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

        <header class="mh-topbar">
            <div class="mh-title-wrap">
                <div>
                    <h1 class="mh-title">Messages</h1>
                    <p class="mh-subtitle">Conversations humaines et actions métier, enfin séparées.</p>
                </div>
            </div>

            <div class="mh-tabs" role="tablist" aria-label="Espaces de messagerie">
                <button type="button" class="mh-tab is-active" data-mh-view="inbox" role="tab" aria-selected="true">
                    <i class="fas fa-inbox" aria-hidden="true"></i>
                    <span>Boîte de réception</span>
                    <span class="mh-count">{{ $inbox->count() }}</span>
                </button>
                <button type="button" class="mh-tab" data-mh-view="actions" role="tab" aria-selected="false">
                    <i class="fas fa-list-check" aria-hidden="true"></i>
                    <span>Centre d’actions</span>
                    <span class="mh-count">{{ $actions->whereNotIn('status', ['done', 'rejected'])->count() }}</span>
                </button>
            </div>

            <button type="button" class="mh-primary" data-mh-new aria-haspopup="dialog">
                <i class="fas fa-plus" aria-hidden="true"></i>
                <span>Nouveau</span>
            </button>
        </header>

        {{-- Inbox -------------------------------------------------------- --}}
        <section class="mh-inbox-workspace" data-mh-inbox aria-label="Boîte de réception">
            <aside class="mh-conversations" aria-label="Liste des conversations">
                <div class="mh-list-head">
                    <label class="mh-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" data-mh-conversation-search placeholder="Nom, rôle, classe ou contenu…" aria-label="Rechercher une conversation">
                    </label>
                    <div class="mh-filters" role="group" aria-label="Filtres de conversations">
                        <button type="button" class="mh-filter is-active" data-filter="all">Tous</button>
                        <button type="button" class="mh-filter" data-filter="unread">Non lus</button>
                        <button type="button" class="mh-filter" data-filter="important">Importants</button>
                        <button type="button" class="mh-filter" data-filter="groups">Groupes</button>
                        <button type="button" class="mh-filter" data-filter="archived">Archivés</button>
                    </div>
                </div>

                <div class="mh-conversation-list" role="list">
                    @forelse($inbox as $conversation)
                        @php
                            $model = $conversations->firstWhere('id', $conversation['id']);
                            $unreadCount = $model ? $model->unreadCountFor(auth()->user()) : 0;
                            $searchText = collect([
                                $conversation['title'],
                                $conversation['subtitle'],
                                $conversation['preview'],
                                collect($conversation['participants'])->pluck('name')->implode(' '),
                            ])->filter()->implode(' ');
                        @endphp
                        <button type="button"
                                class="mh-conversation"
                                role="listitem"
                                data-conversation-id="{{ $conversation['id'] }}"
                                data-type="{{ $conversation['type'] }}"
                                data-unread="{{ $unreadCount }}"
                                data-important="0"
                                data-archived="0"
                                data-search="{{ e($searchText) }}">
                            <span class="mh-avatar {{ $conversation['type'] === 'group' ? 'is-group' : '' }}" aria-hidden="true">{{ $conversation['initials'] }}</span>
                            <span class="mh-conversation-main">
                                <span class="mh-conversation-title">
                                    <span>{{ $conversation['title'] }}</span>
                                    @if(!empty($conversation['context']))
                                        <i class="fas fa-link" aria-label="Contexte métier lié" title="Contexte métier lié"></i>
                                    @endif
                                </span>
                                @if($conversation['subtitle'])
                                    <span class="mh-conversation-sub">{{ $conversation['subtitle'] }}</span>
                                @endif
                                <span class="mh-conversation-preview">{{ $conversation['preview'] }}</span>
                            </span>
                            <span class="mh-conversation-meta">
                                <time datetime="{{ $conversation['last_message_at'] }}">{{ $conversation['last_message_at'] ? \Carbon\Carbon::parse($conversation['last_message_at'])->format('H:i') : '' }}</time>
                                <span class="mh-unread" aria-label="{{ $unreadCount }} message(s) non lu(s)">{{ $unreadCount ?: '' }}</span>
                            </span>
                        </button>
                    @empty
                        <div class="mh-empty" style="min-height:320px">
                            <div class="mh-empty-card">
                                <div class="mh-empty-icon"><i class="fas fa-comments"></i></div>
                                <h3>Aucune conversation</h3>
                                <p>Utilisez « Nouveau » pour écrire à une personne ou lancer une communication.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </aside>

            <main class="mh-thread" aria-label="Conversation active">
                <header class="mh-thread-head">
                    <button type="button" class="mh-icon-btn mh-mobile-back" data-mh-mobile-back aria-label="Retour aux conversations">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <span class="mh-avatar" aria-hidden="true"><i class="fas fa-comment"></i></span>
                    <div class="mh-thread-head-main">
                        <h2 class="mh-thread-title" data-mh-thread-title>Sélectionnez une conversation</h2>
                        <div class="mh-thread-sub" data-mh-thread-sub>Les échanges humains restent simples. Les validations sont dans le Centre d’actions.</div>
                    </div>
                    <button type="button" class="mh-icon-btn" data-mh-context-toggle aria-label="Afficher les informations liées" title="Informations liées">
                        <i class="fas fa-circle-info"></i>
                    </button>
                </header>

                <div class="mh-thread-scroll" data-mh-thread-scroll tabindex="0">
                    <div class="mh-empty">
                        <div class="mh-empty-card">
                            <div class="mh-empty-icon"><i class="fas fa-message"></i></div>
                            <h3>Votre boîte de réception</h3>
                            <p>Choisissez une conversation à gauche. Les paiements, inscriptions et réclamations restent accessibles comme contexte sans envahir le fil.</p>
                        </div>
                    </div>
                </div>

                <footer class="mh-composer" data-mh-composer hidden>
                    <div class="mh-ai-strip" aria-label="Suggestions Nanan">
                        <button type="button" class="mh-ai-chip" data-mh-ai="Résume cette conversation en distinguant décisions, informations importantes et prochaines étapes.">
                            <i class="fas fa-wand-magic-sparkles"></i> Résumer
                        </button>
                        <button type="button" class="mh-ai-chip" data-mh-ai="Propose une réponse professionnelle, concise et adaptée au contexte de cette conversation.">
                            <i class="fas fa-reply"></i> Proposer une réponse
                        </button>
                        <button type="button" class="mh-ai-chip" data-mh-ai="Analyse mon prochain message et reformule-le de façon plus claire et polie, sans changer le fond.">
                            <i class="fas fa-pen"></i> Reformuler
                        </button>
                        <button type="button" class="mh-ai-chip" data-mh-ai="Détecte si cette conversation nécessite une action métier. Si oui, propose le type de tâche, la priorité, le service et l’échéance. Ne crée rien automatiquement.">
                            <i class="fas fa-list-check"></i> Détecter une action
                        </button>
                    </div>
                    <div class="mh-compose-box">
                        <textarea rows="1" maxlength="4000" data-mh-compose-input placeholder="Écrire un message…" aria-label="Votre message"></textarea>
                        <button type="button" class="mh-send" data-mh-send aria-label="Envoyer le message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </footer>
            </main>

            <aside class="mh-context" data-mh-context aria-label="Contexte lié">
                <header class="mh-context-head">
                    <h3>Contexte lié</h3>
                    <button type="button" class="mh-icon-btn" data-mh-context-close aria-label="Fermer le contexte">
                        <i class="fas fa-xmark"></i>
                    </button>
                </header>
                <div class="mh-context-body" data-mh-context-body>
                    <div class="mh-empty" style="min-height:280px;padding:18px">
                        <div class="mh-empty-card">
                            <div class="mh-empty-icon"><i class="fas fa-link"></i></div>
                            <h3>Aucun contexte sélectionné</h3>
                            <p>La fiche étudiant, l’inscription ou les données financières apparaîtront ici selon vos permissions.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        {{-- Action center ------------------------------------------------ --}}
        <section class="mh-actions-workspace" data-mh-actions aria-label="Centre d’actions">
            <div class="mh-actions-toolbar">
                <label class="mh-search">
                    <i class="fas fa-search"></i>
                    <input type="search" data-mh-action-search placeholder="Rechercher une action ou un dossier…" aria-label="Rechercher une action">
                </label>
                <label>
                    <span class="visually-hidden">Statut</span>
                    <select data-mh-status-filter aria-label="Filtrer par statut">
                        <option value="">Tous les statuts</option>
                        <option value="todo">À faire</option>
                        <option value="in_progress">En cours</option>
                        <option value="waiting_info">En attente d’information</option>
                        <option value="done">Terminé</option>
                        <option value="rejected">Rejeté</option>
                    </select>
                </label>
                <label>
                    <span class="visually-hidden">Priorité</span>
                    <select data-mh-priority-filter aria-label="Filtrer par priorité">
                        <option value="">Toutes les priorités</option>
                        <option value="urgent">Urgente</option>
                        <option value="high">Haute</option>
                        <option value="normal">Normale</option>
                        <option value="low">Faible</option>
                    </select>
                </label>
                <div class="mh-view-switch" role="group" aria-label="Mode d’affichage">
                    <button type="button" class="is-active" data-mh-action-view="list"><i class="fas fa-list"></i> Liste</button>
                    <button type="button" data-mh-action-view="kanban"><i class="fas fa-table-columns"></i> Kanban</button>
                </div>
            </div>

            <div class="mh-actions-layout">
                <div class="mh-actions-main">
                    <div data-mh-action-table-wrap>
                        @if($actions->isEmpty())
                            <div class="mh-empty" style="min-height:420px">
                                <div class="mh-empty-card">
                                    <div class="mh-empty-icon"><i class="fas fa-circle-check"></i></div>
                                    <h3>Rien à traiter pour le moment</h3>
                                    <p>Les validations, corrections, réclamations et demandes internes apparaîtront ici au lieu d’être mélangées aux conversations.</p>
                                </div>
                            </div>
                        @else
                            <table class="mh-actions-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Statut</th>
                                        <th>Priorité</th>
                                        <th>Service</th>
                                        <th>Échéance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($actions as $action)
                                    @php
                                        $search = collect([$action['title'], $action['subject'], $action['service'], $action['summary']])->filter()->implode(' ');
                                    @endphp
                                    <tr class="mh-action-row"
                                        tabindex="0"
                                        data-action-id="{{ $action['id'] }}"
                                        data-conversation-id="{{ $action['conversation_id'] }}"
                                        data-title="{{ e($action['title']) }}"
                                        data-subject="{{ e($action['subject']) }}"
                                        data-service="{{ e($action['service']) }}"
                                        data-status="{{ $action['status'] }}"
                                        data-priority="{{ $action['priority'] }}"
                                        data-created="{{ $action['created_at'] }}"
                                        data-updated="{{ $action['updated_at'] }}"
                                        data-search="{{ e($search) }}">
                                        <td>
                                            <div class="mh-action-title">{{ $action['title'] }}</div>
                                            <div class="mh-action-sub">{{ $action['subject'] }}</div>
                                        </td>
                                        <td><span class="mh-badge {{ $action['status'] }}">{{ $statusLabels[$action['status']] ?? $action['status'] }}</span></td>
                                        <td><span class="mh-priority {{ $action['priority'] }}">{{ $priorityLabels[$action['priority']] ?? $action['priority'] }}</span></td>
                                        <td>{{ $action['service'] }}</td>
                                        <td>{{ $action['due_at'] ? \Carbon\Carbon::parse($action['due_at'])->format('d/m/Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div class="mh-kanban" data-mh-kanban aria-label="Vue Kanban">
                        @foreach(['todo' => 'À faire', 'in_progress' => 'En cours', 'waiting_info' => 'En attente', 'done' => 'Terminé'] as $status => $label)
                            <section class="mh-kanban-column" data-kanban-status="{{ $status }}">
                                <header class="mh-kanban-head"><span>{{ $label }}</span><span class="mh-count">{{ $actions->where('status', $status)->count() }}</span></header>
                                <div class="mh-kanban-body"></div>
                            </section>
                        @endforeach
                    </div>

                    @foreach($actions as $action)
                        <article class="mh-kanban-card"
                                 style="display:none"
                                 data-kanban-for="{{ $action['id'] }}"
                                 data-action-id="{{ $action['id'] }}"
                                 data-conversation-id="{{ $action['conversation_id'] }}"
                                 data-status="{{ $action['status'] }}"
                                 data-priority="{{ $action['priority'] }}"
                                 data-title="{{ e($action['title']) }}"
                                 data-subject="{{ e($action['subject']) }}"
                                 data-service="{{ e($action['service']) }}"
                                 data-created="{{ $action['created_at'] }}"
                                 data-updated="{{ $action['updated_at'] }}"
                                 data-search="{{ e(collect([$action['title'], $action['subject'], $action['service']])->implode(' ')) }}">
                            <div class="mh-action-title">{{ $action['title'] }}</div>
                            <div class="mh-action-sub">{{ $action['subject'] }}</div>
                            <div style="display:flex;gap:6px;margin-top:9px;flex-wrap:wrap">
                                <span class="mh-badge {{ $action['status'] }}">{{ $statusLabels[$action['status']] ?? $action['status'] }}</span>
                                <span class="mh-priority {{ $action['priority'] }}">{{ $priorityLabels[$action['priority']] ?? $action['priority'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="mh-action-detail" data-mh-action-detail aria-label="Détail de l’action">
                    <div class="mh-empty" style="min-height:100%">
                        <div class="mh-empty-card">
                            <div class="mh-empty-icon"><i class="fas fa-list-check"></i></div>
                            <h3>Sélectionnez une action</h3>
                            <p>Le dossier, la priorité, le service responsable et l’historique s’afficheront ici.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        {{-- Intent-first composer --------------------------------------- --}}
        <div class="mh-modal" data-mh-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mh-new-title">
            <div class="mh-modal-backdrop" data-mh-modal-close></div>
            <div class="mh-modal-card">
                <header class="mh-modal-head">
                    <div>
                        <h3 id="mh-new-title">Que voulez-vous faire ?</h3>
                        <div class="mh-context-muted">Choisissez d’abord l’intention. KLASSCI vous demandera ensuite uniquement les informations utiles.</div>
                    </div>
                    <button type="button" class="mh-icon-btn" data-mh-modal-close aria-label="Fermer"><i class="fas fa-xmark"></i></button>
                </header>
                <div class="mh-modal-body">
                    <div class="mh-intents">
                        <button type="button" class="mh-intent" data-mh-intent="person">
                            <span class="mh-intent-icon"><i class="fas fa-user"></i></span>
                            <span><strong>Écrire à une personne</strong><small>Conversation privée avec un membre autorisé.</small></span>
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <button type="button" class="mh-intent" disabled aria-disabled="true" title="La persistance des groupes arrive avec la seconde étape backend">
                            <span class="mh-intent-icon"><i class="fas fa-user-group"></i></span>
                            <span><strong>Créer un groupe d’équipe</strong><small>Scolarité, caisse, direction, enseignants…</small></span>
                            <span class="mh-badge todo">Backend</span>
                        </button>

                        @can('annonces.create')
                        <a class="mh-intent" href="{{ route('esbtp.annonces.create') }}">
                            <span class="mh-intent-icon"><i class="fas fa-users-rectangle"></i></span>
                            <span><strong>Écrire à une classe ou promotion</strong><small>Utilise la diffusion ciblée déjà sécurisée de KLASSCI.</small></span>
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>

                        <a class="mh-intent" href="{{ route('esbtp.annonces.create') }}">
                            <span class="mh-intent-icon"><i class="fas fa-user-graduate"></i></span>
                            <span><strong>Contacter un étudiant ou parent</strong><small>Audience ciblée sans ouvrir de DM étudiant non autorisé.</small></span>
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>

                        <a class="mh-intent" href="{{ route('esbtp.annonces.create') }}">
                            <span class="mh-intent-icon"><i class="fas fa-bullhorn"></i></span>
                            <span><strong>Publier une annonce</strong><small>Communication descendante vers une audience précise.</small></span>
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                        @endcan

                        <button type="button" class="mh-intent" data-mh-goto-actions>
                            <span class="mh-intent-icon"><i class="fas fa-arrow-right-arrow-left"></i></span>
                            <span><strong>Créer une demande interne</strong><small>Préparez et suivez la demande dans le Centre d’actions.</small></span>
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <button type="button" class="mh-intent" data-mh-goto-actions>
                            <span class="mh-intent-icon"><i class="fas fa-list-check"></i></span>
                            <span><strong>Créer une action à traiter</strong><small>Validation, correction, pièce manquante ou réclamation.</small></span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div class="mh-person-search" data-mh-person-search-panel>
                        <div class="mh-context-label">Choisir une personne</div>
                        <label class="mh-search">
                            <i class="fas fa-search"></i>
                            <input type="search" data-mh-user-search placeholder="Nom ou e-mail…" autocomplete="off" aria-label="Rechercher une personne">
                        </label>
                        <div class="mh-user-results" data-mh-user-results aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mh-toast" data-mh-toast role="status" aria-live="polite"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/messages-hub.js') }}" defer></script>
@endpush
