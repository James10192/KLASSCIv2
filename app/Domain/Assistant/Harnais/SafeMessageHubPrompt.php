<?php

namespace App\Domain\Assistant\Harnais;

use App\Models\ChatbotConversation;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\ConversationContextProvider;
use App\Services\Messages\MessageAssistantContextBuilder;

/**
 * Extends Nanan's normal prompt with an authoritative Message Hub block when
 * the current page is /messages?conversation=<id>. The browser sends only the
 * current URL; all identities, messages, permissions and entity links are read
 * again on the server from KLASSCI.
 */
class SafeMessageHubPrompt extends ConstructeurDePrompt
{
    public function __construct(
        ConversationContextProvider $contextProvider,
        private readonly MessageAssistantContextBuilder $messageContext,
    ) {
        parent::__construct($contextProvider);
    }

    public function systeme(
        $user,
        ?ChatbotUserPreference $preferences,
        ?array $clientContext,
        ?ChatbotConversation $conversation = null,
    ): string {
        $base = parent::systeme($user, $preferences, $clientContext, $conversation);
        if (! $user) {
            return $base;
        }

        return $base . $this->messageContext->promptBlock($user, $clientContext);
    }
}
