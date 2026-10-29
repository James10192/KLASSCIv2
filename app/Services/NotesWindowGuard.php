<?php

namespace App\Services;

use App\Models\ESBTPNotesWindow;
use App\Models\User;

class NotesWindowGuard
{
    public function __construct(private readonly TenantScolariteSettings $settings)
    {
    }

    public function isBound(User $user): bool
    {
        if (! $this->settings->splitRolesEnabled()) {
            return false;
        }

        return ! $user->can('identity.teach');
    }

    public function canWrite(User $user, int $classeId): bool
    {
        if (! $this->isBound($user)) {
            return true;
        }

        return $this->openWindowFor($classeId) !== null;
    }

    public function openWindowFor(int $classeId): ?ESBTPNotesWindow
    {
        $today = now()->toDateString();

        return ESBTPNotesWindow::query()
            ->where('classe_id', $classeId)
            ->whereNull('closed_at')
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->latest('id')
            ->first();
    }
}