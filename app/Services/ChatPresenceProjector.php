<?php

namespace App\Services;

use App\Models\User;

/**
 * Projection d'un user en participant chat avec présence.
 * Source unique consommée par ChatController + le @php block de messages/index
 * pour la sidebar.
 */
class ChatPresenceProjector
{
    /**
     * @return array{id: int, name: string, is_online: bool, last_seen_at: ?string}
     */
    public static function project(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'is_online' => $user->isOnline(),
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
        ];
    }
}
