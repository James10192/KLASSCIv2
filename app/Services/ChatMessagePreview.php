<?php

namespace App\Services;

use App\Models\ChatMessage;

/**
 * Synthèse du dernier message d'une conversation pour la sidebar chat.
 * Source unique consommée par ChatController::conversationsList et le @php block
 * de messages/index.blade.php pour éviter le drift des règles de preview.
 */
class ChatMessagePreview
{
    /**
     * @return array{id: int, body: ?string, type: string, preview: string, sender_id: int}|null
     */
    public static function forMessage(?ChatMessage $message): ?array
    {
        if (!$message) {
            return null;
        }

        return [
            'id' => $message->id,
            'body' => $message->body,
            'type' => $message->type,
            'preview' => self::previewText($message),
            'sender_id' => $message->sender_id,
        ];
    }

    private static function previewText(ChatMessage $message): string
    {
        $kind = is_array($message->payload ?? null) ? ($message->payload['kind'] ?? null) : null;

        return match ($message->type) {
            'action_card' => '📎 ' . match ($kind) {
                'inscription' => 'Inscription partagée',
                'paiement' => 'Paiement partagé',
                default => 'Card partagée',
            },
            'system' => $message->body ?? 'Notification',
            default => $message->body ?? '',
        };
    }
}
