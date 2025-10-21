@once
    <div id="chatbot-backdrop" class="chatbot-backdrop" aria-hidden="true"></div>

    <div id="chatbot-widget" class="chatbot-widget" aria-live="polite">
        <button id="chatbot-toggle" class="chatbot-toggle" type="button" aria-label="Ouvrir l'assistant KLASSCI">
            <i class="fas fa-comments chatbot-toggle-icon"></i>
        </button>

        <div id="chatbot-window" class="chatbot-window" role="dialog" aria-modal="false" aria-hidden="true">
            <div class="chatbot-header">
                <div class="chatbot-title-stack">
                    <span class="chatbot-title">Assistant KLASSCI</span>
                    <span class="chatbot-subtitle">Vos données, vos permissions, en temps réel.</span>
                </div>
                <div class="chatbot-header-actions">
                    <button id="chatbot-new-conversation" class="chatbot-icon-button" type="button" title="Nouvelle conversation">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button id="chatbot-expand" class="chatbot-icon-button" type="button" title="Agrandir" aria-pressed="false">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button id="chatbot-conversations-toggle" class="chatbot-icon-button" type="button" title="Conversations récentes">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button id="chatbot-close" class="chatbot-icon-button" type="button" title="Fermer">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="chatbot-body">
                <aside id="chatbot-conversations" class="chatbot-conversations" aria-label="Conversations récentes">
                    <div class="chatbot-conversations-header">
                        <span>Conversations</span>
                    </div>
                    <div id="chatbot-conversation-list" class="chatbot-conversation-list"></div>
                </aside>

                <div class="chatbot-content">
                    <div id="chatbot-messages" class="chatbot-messages"></div>

                    <div class="chatbot-input">
                        <textarea
                            id="chatbot-textarea"
                            class="chatbot-textarea"
                            placeholder="Écrivez votre question…"
                            rows="1"
                            aria-label="Zone de saisie du chatbot"
                        ></textarea>
                        <button id="chatbot-send" class="chatbot-send-btn" type="button" aria-label="Envoyer le message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="chatbot-resize-handle" class="chatbot-resize-handle" role="presentation" aria-hidden="true">
                <i class="fas fa-grip-lines"></i>
            </div>
        </div>

        <div id="chatbot-toast" class="chatbot-toast" role="status" aria-live="polite"></div>
    </div>

    @push('scripts')
        <script>
            window.KLASSCI_CHATBOT_CONFIG = Object.assign({}, window.KLASSCI_CHATBOT_CONFIG || {}, {
                routes: {
                    message: '{{ route('chatbot.message') }}',
                    conversations: '{{ route('chatbot.conversations') }}',
                    history: '{{ route('chatbot.history', ['conversationId' => '__ID__']) }}',
                    delete: '{{ route('chatbot.delete', ['conversationId' => '__ID__']) }}',
                },
                csrfToken: '{{ csrf_token() }}',
            });
        </script>
        <script src="{{ asset('js/chatbot-widget.js') }}" defer></script>
    @endpush
@endonce
