<?php

namespace App\Services;

use App\Models\ESBTPDocumentApproval;
use App\Models\User;

class DocumentPrintGuard
{
    public function __construct(private readonly TenantScolariteSettings $settings)
    {
    }

    public function requiresApproval(): bool
    {
        return $this->settings->printRequiresApproval();
    }

    public function canPreview(User $user): bool
    {
        return $user->can('documents.view')
            || $user->can('documents.print')
            || $user->can('documents.approve')
            || $user->can('students.view')
            || $user->can('bulletins.view');
    }

    public function canPrint(User $user, string $documentType, int $etudiantId, ?int $documentId = null): bool
    {
        if (! $user->can('documents.print') && ! $user->can('students.view') && ! $user->can('bulletins.view')) {
            return false;
        }

        if (! $this->requiresApproval()) {
            return true;
        }

        return $this->latestApproved($documentType, $etudiantId, $documentId) !== null;
    }

    public function latestApproved(string $documentType, int $etudiantId, ?int $documentId = null): ?ESBTPDocumentApproval
    {
        $query = ESBTPDocumentApproval::query()
            ->where('document_type', $documentType)
            ->where('etudiant_id', $etudiantId)
            ->where('status', ESBTPDocumentApproval::STATUS_APPROVED);

        if ($documentId !== null) {
            $query->where(function ($inner) use ($documentId) {
                $inner->where('document_id', $documentId)->orWhereNull('document_id');
            });
        }

        return $query->latest('approved_at')->first();
    }
}