<?php

namespace App\Policies;

use App\Models\ESBTPParent;
use App\Models\User;

final class ESBTPParentPolicy
{
    /**
     * Parent records are tenant-local. Route model binding resolves on the
     * current tenant connection, and soft-deleted records are never manageable.
     */
    public function manageChatbot(User $user, ESBTPParent $parent): bool
    {
        return ! $parent->trashed() && $user->can('parent_chatbot.manage');
    }
}
