{{--
    Assistant IA KLASSCI (namespace CSS ast-*).
    Panneau latéral de 440 px sur ordinateur (colonne de 48rem en mode large),
    feuille plein écran sous 768 px.
    Logique : public/js/assistant.js (fabrique Alpine klassciAssistant). Le corps
    d'une réponse (étapes, résultats, texte diffusé) est dessiné par le script
    dans .ast-stream ; Alpine tient l'enveloppe.
    Styles  : public/css/assistant.css (chargé dans le head du layout).
    Réponses diffusées au protocole UI message stream v1 (Vercel AI SDK).
--}}
@once
<div class="ast-root" x-data="klassciAssistant()" x-on:keydown.escape.window="surEchap()">
    <script type="application/json" data-ast-config>@json($astConfig)</script>

    <button type="button" class="ast-launcher" x-ref="lanceur" x-show="!ouvert"
            x-on:click="ouvrir()" aria-haspopup="dialog" aria-label="Ouvrir l'assistant KLASSCI">
        <span class="ast-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="M12 3.5l1.9 4.6 4.6 1.9-4.6 1.9L12 16.5l-1.9-4.6L5.5 10l4.6-1.9L12 3.5z" fill="currentColor"/><path d="M18.5 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" fill="currentColor" opacity=".7"/></svg>
        </span>
        <span class="ast-launcher-label">Assistant</span>
    </button>

    <div class="ast-scrim" x-show="ouvert" x-cloak x-transition.opacity x-on:click="fermer()" aria-hidden="true"></div>

    <section class="ast-panel" x-show="ouvert" x-cloak x-trap="ouvert"
             x-bind:class="{ 'ast-panel--large': large }"
             x-transition:enter="ast-enter" x-transition:enter-start="ast-enter-start" x-transition:enter-end="ast-enter-end"
             x-transition:leave="ast-leave" x-transition:leave-start="ast-enter-end" x-transition:leave-end="ast-enter-start"
             role="dialog" aria-modal="true" aria-labelledby="ast-title">

        <header class="ast-head">
            <button type="button" class="ast-icon-btn" x-show="vue !== 'chat'" x-on:click="vue = 'chat'" aria-label="Retour à la conversation">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </button>
            <span class="ast-mark ast-mark--head" x-show="vue === 'chat'" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none"><path d="M12 3.5l1.9 4.6 4.6 1.9-4.6 1.9L12 16.5l-1.9-4.6L5.5 10l4.6-1.9L12 3.5z" fill="currentColor"/><path d="M18.5 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" fill="currentColor" opacity=".7"/></svg>
            </span>
            <div class="ast-head-titles">
                <h2 id="ast-title" class="ast-title"
                    x-text="vue === 'historique' ? 'Conversations' : (vue === 'preferences' ? 'Préférences' : 'Assistant KLASSCI')">Assistant KLASSCI</h2>
                <p class="ast-subtitle" x-show="vue === 'chat'">
                    <span class="ast-live-dot" aria-hidden="true"></span>
                    <span>Connecté à vos données KLASSCI</span>
                </p>
            </div>
            <div class="ast-head-actions">
                <button type="button" class="ast-icon-btn" x-on:click="nouvelleConversation()" title="Nouvelle conversation" aria-label="Nouvelle conversation">
                    <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                </button>
                <button type="button" class="ast-icon-btn" x-on:click="ouvrirHistorique()" title="Conversations" aria-label="Conversations précédentes"
                        x-bind:aria-pressed="vue === 'historique'">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="ast-icon-btn" x-on:click="ouvrirPreferences()" title="Préférences" aria-label="Préférences de l'assistant"
                        x-bind:aria-pressed="vue === 'preferences'">
                    <i class="fas fa-sliders" aria-hidden="true"></i>
                </button>
                <button type="button" class="ast-icon-btn ast-only-desktop" x-on:click="basculerTaille()"
                        x-bind:title="large ? 'Réduire' : 'Agrandir'" x-bind:aria-label="large ? 'Réduire le panneau' : 'Agrandir le panneau'">
                    <i class="fas" x-bind:class="large ? 'fa-down-left-and-up-right-to-center' : 'fa-up-right-and-down-left-from-center'" aria-hidden="true"></i>
                </button>
                <button type="button" class="ast-icon-btn" x-on:click="fermer()" title="Fermer (Échap)" aria-label="Fermer l'assistant">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </header>

        {{-- Conversation --}}
        <div class="ast-body" x-show="vue === 'chat'">
            <div class="ast-thread" x-ref="fil" tabindex="-1">
                <div class="ast-thread-inner" x-ref="filContenu" x-bind:class="{ 'is-empty': messages.length === 0 && !chargementHistorique }">

                    <div class="ast-empty" x-show="messages.length === 0 && !chargementHistorique">
                        <span class="ast-mark ast-mark--xl" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none"><path d="M12 3.5l1.9 4.6 4.6 1.9-4.6 1.9L12 16.5l-1.9-4.6L5.5 10l4.6-1.9L12 3.5z" fill="currentColor"/><path d="M18.5 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" fill="currentColor" opacity=".7"/></svg>
                        </span>
                        <h3 class="ast-empty-title" x-text="cfg.prenom ? 'Bonjour ' + cfg.prenom : 'Bonjour'">Bonjour</h3>
                        <p class="ast-empty-text">Posez une question sur l'école : je consulte KLASSCI avec les accès de votre compte, et je vous montre ce que j'ai trouvé.</p>
                        <div class="ast-suggestions" x-show="cfg.suggestions.length">
                            <template x-for="s in cfg.suggestions.slice(0, 4)" x-bind:key="s">
                                <button type="button" class="ast-suggestion" x-on:click="proposer(s)">
                                    <span x-text="s"></span>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="ast-loading" x-show="chargementHistorique" x-cloak>
                        <span class="ast-spinner" aria-hidden="true"></span> Chargement de la conversation…
                    </div>

                    <template x-for="msg in messages" x-bind:key="msg.key">
                        <div class="ast-msg" x-bind:class="'ast-msg--' + msg.role" x-bind:data-key="msg.key">
                            <template x-if="msg.role === 'user'">
                                <div class="ast-bubble" x-text="msg.text"></div>
                            </template>

                            <template x-if="msg.role === 'assistant'">
                                <div class="ast-answer" x-bind:aria-busy="enCours(msg) ? 'true' : 'false'">
                                    <div class="ast-stream" x-init="monter($el, msg)"></div>

                                    <div class="ast-error" role="alert" x-show="msg.erreur" x-cloak>
                                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                                        <span x-text="msg.erreur"></span>
                                        <button type="button" class="ast-btn ast-btn--ghost ast-btn--sm" x-show="estDernierAssistant(msg) && !envoiEnCours" x-on:click="relancer(msg)">Réessayer</button>
                                    </div>

                                    <p class="ast-stopped" x-show="msg.status === 'stopped'" x-cloak>
                                        <i class="fas fa-circle-stop" aria-hidden="true"></i> Réponse arrêtée.
                                    </p>

                                    <template x-if="lienMessage(msg)">
                                        <a class="ast-open" x-bind:href="lienMessage(msg)">
                                            <span>Ouvrir la page dans KLASSCI</span>
                                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    </template>

                                    <div class="ast-followups" x-show="!enCours(msg) && (suites(msg).questions.length || suites(msg).actions.length)" x-cloak>
                                        <template x-for="a in suites(msg).actions" x-bind:key="a.label">
                                            <button type="button" class="ast-chip ast-chip--action" x-bind:disabled="a.fait" x-on:click="lancerAction(a, msg)">
                                                <i class="fas" x-bind:class="a.fait ? 'fa-check' : 'fa-bolt'" aria-hidden="true"></i>
                                                <span x-text="a.label"></span>
                                            </button>
                                        </template>
                                        <template x-for="q in suites(msg).questions" x-bind:key="q">
                                            <button type="button" class="ast-chip" x-bind:disabled="envoiEnCours" x-on:click="proposer(q)">
                                                <span x-text="q"></span>
                                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                            </button>
                                        </template>
                                    </div>

                                    <div class="ast-msg-tools" x-bind:class="{ 'is-last': estDernierAssistant(msg) }" x-show="!enCours(msg) && msg.aTexte" x-cloak>
                                        <button type="button" class="ast-icon-btn ast-icon-btn--sm" x-on:click="copier(msg)"
                                                x-bind:aria-label="msg.copie ? 'Copié' : 'Copier la réponse'" x-bind:title="msg.copie ? 'Copié' : 'Copier'">
                                            <i class="fas" x-bind:class="msg.copie ? 'fa-check' : 'fa-copy'" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="ast-icon-btn ast-icon-btn--sm" x-show="estDernierAssistant(msg) && !envoiEnCours"
                                                x-on:click="relancer(msg)" aria-label="Régénérer la réponse" title="Régénérer">
                                            <i class="fas fa-rotate-right" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="ast-composer-wrap">
                <button type="button" class="ast-to-bottom" x-show="!suivreBas && messages.length" x-cloak
                        x-transition.opacity.duration.150ms x-on:click="allerEnBas()" aria-label="Aller au dernier message">
                    <i class="fas fa-arrow-down" aria-hidden="true"></i>
                    <span class="ast-unread" x-show="nonLu" aria-hidden="true"></span>
                </button>

                <form class="ast-composer" x-on:submit.prevent="envoyer()">
                    <label for="ast-input" class="ast-sr">Votre question</label>
                    <textarea id="ast-input" class="ast-textarea" x-ref="saisie" rows="1"
                              x-model="saisie" x-on:input="ajusterHauteur(); erreurSaisie = ''" x-on:keydown="surTouche($event)"
                              x-bind:maxlength="cfg.maxLength" x-bind:disabled="envoiEnCours"
                              placeholder="Demandez à KLASSCI…" aria-describedby="ast-hint"></textarea>
                    <div class="ast-composer-row">
                        <template x-if="cfg.modeles && cfg.modeles.liste">
                            <div class="ast-model" x-on:click.outside="menuModele = false" x-on:keydown.escape.stop="menuModele = false">
                                <button type="button" class="ast-model-btn" x-on:click="menuModele = !menuModele"
                                        aria-haspopup="listbox" x-bind:aria-expanded="menuModele ? 'true' : 'false'" aria-label="Choisir le modèle">
                                    <i class="fas fa-microchip" aria-hidden="true"></i>
                                    <span x-text="libelleModele()"></span>
                                    <i class="fas fa-chevron-up ast-model-caret" aria-hidden="true"></i>
                                </button>
                                <ul class="ast-model-menu" role="listbox" x-show="menuModele" x-cloak aria-label="Modèles disponibles">
                                    <template x-for="m in cfg.modeles.liste" x-bind:key="m.cle">
                                        <li role="option" x-bind:aria-selected="m.cle === modeleChoisi ? 'true' : 'false'">
                                            <button type="button" x-on:click="choisirModele(m.cle)" x-bind:class="{ 'is-on': m.cle === modeleChoisi }">
                                                <span x-text="m.libelle"></span>
                                                <i class="fas fa-check" x-show="m.cle === modeleChoisi" aria-hidden="true"></i>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <span id="ast-hint" class="ast-hint">
                            <span x-show="!erreurSaisie && saisie.length < cfg.maxLength - 150">Entrée pour envoyer · Maj+Entrée pour aller à la ligne</span>
                            <span x-show="!erreurSaisie && saisie.length >= cfg.maxLength - 150" x-text="saisie.length + ' / ' + cfg.maxLength"></span>
                            <span class="ast-hint-error" x-show="erreurSaisie" x-text="erreurSaisie" role="alert"></span>
                        </span>
                        <button type="button" class="ast-send ast-send--stop" x-show="envoiEnCours" x-on:click="arreter()" aria-label="Arrêter la réponse" title="Arrêter">
                            <i class="fas fa-stop" aria-hidden="true"></i>
                        </button>
                        <button type="submit" class="ast-send" x-show="!envoiEnCours" x-bind:disabled="!saisie.trim()" aria-label="Envoyer" title="Envoyer">
                            <i class="fas fa-arrow-up" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <p class="ast-disclaimer">Réponses tirées de vos données KLASSCI. Vérifiez avant toute décision.</p>
            </div>
        </div>

        {{-- Historique --}}
        <div class="ast-body ast-body--scroll" x-show="vue === 'historique'" x-cloak>
            <div class="ast-loading" x-show="chargementListe"><span class="ast-spinner" aria-hidden="true"></span> Chargement…</div>
            <p class="ast-muted ast-pad" x-show="!chargementListe && conversations.length === 0">Aucune conversation pour l'instant.</p>
            <ul class="ast-history" x-show="!chargementListe && conversations.length">
                <template x-for="c in conversations" x-bind:key="c.id">
                    <li class="ast-history-item" x-bind:class="{ 'is-current': c.id === conversationId }">
                        <button type="button" class="ast-history-open" x-on:click="chargerConversation(c.id)">
                            <span class="ast-history-title" x-text="c.title"></span>
                            <span class="ast-history-meta" x-text="c.last_activity"></span>
                        </button>
                        <button type="button" class="ast-icon-btn ast-icon-btn--sm" x-on:click="supprimerConversation(c)" aria-label="Supprimer la conversation" title="Supprimer">
                            <i class="fas fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Préférences --}}
        <div class="ast-body ast-body--scroll" x-show="vue === 'preferences'" x-cloak>
            <template x-if="prefs">
            <form class="ast-prefs" x-on:submit.prevent="enregistrerPreferences()">
                <div class="ast-field">
                    <label class="ast-label" for="ast-pref-name">Comment dois-je vous appeler ?</label>
                    <input id="ast-pref-name" class="ast-input" type="text" maxlength="80" x-model="prefs.preferred_name">
                </div>
                <fieldset class="ast-field">
                    <legend class="ast-label">Longueur des réponses</legend>
                    <div class="ast-seg">
                        <label><input type="radio" value="court" x-model="prefs.response_style"><span>Courtes</span></label>
                        <label><input type="radio" value="standard" x-model="prefs.response_style"><span>Standard</span></label>
                        <label><input type="radio" value="detaille" x-model="prefs.response_style"><span>Détaillées</span></label>
                    </div>
                </fieldset>
                <fieldset class="ast-field">
                    <legend class="ast-label">Ton</legend>
                    <div class="ast-seg">
                        <label><input type="radio" value="direct" x-model="prefs.response_tone"><span>Direct</span></label>
                        <label><input type="radio" value="pedagogique" x-model="prefs.response_tone"><span>Pédagogique</span></label>
                        <label><input type="radio" value="chaleureux" x-model="prefs.response_tone"><span>Chaleureux</span></label>
                    </div>
                </fieldset>
                <fieldset class="ast-field">
                    <legend class="ast-label">Questions de précision</legend>
                    <div class="ast-seg">
                        <label><input type="radio" value="auto" x-model="prefs.clarification_mode"><span>Si besoin</span></label>
                        <label><input type="radio" value="always" x-model="prefs.clarification_mode"><span>Toujours</span></label>
                        <label><input type="radio" value="never" x-model="prefs.clarification_mode"><span>Jamais</span></label>
                    </div>
                </fieldset>
                <div class="ast-field">
                    <label class="ast-label" for="ast-pref-notes">À savoir sur vous</label>
                    <textarea id="ast-pref-notes" class="ast-input" rows="3" maxlength="500" x-model="prefs.notes" placeholder="Ex. : je gère la scolarité des BTS"></textarea>
                </div>
                <div class="ast-prefs-foot">
                    <span class="ast-muted" role="status" x-text="prefsEtat"></span>
                    <button type="submit" class="ast-btn ast-btn--primary">Enregistrer</button>
                </div>
            </form>
            </template>
            <p class="ast-muted ast-pad" x-show="!prefs" x-text="prefsEtat || 'Chargement…'"></p>
        </div>

        <div class="ast-sr" aria-live="polite" x-text="annonce"></div>
    </section>
</div>

@push('scripts')
    <script src="{{ asset('js/assistant.js') }}?v={{ @filemtime(public_path('js/assistant.js')) ?: '1' }}"></script>
@endpush
@endonce
