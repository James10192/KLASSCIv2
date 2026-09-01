<?php

namespace App\Services;

use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\User;

class DocumentPrintGuard
{
    public const DENY_PERMISSION = 'permission';

    public const DENY_SOLDE = 'solde';

    public const DENY_APPROVAL = 'approval';

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
        return $this->denyReason($user, $documentType, $etudiantId, $documentId) === null;
    }

    public function denyReason(User $user, string $documentType, int $etudiantId, ?int $documentId = null): ?string
    {
        if (! $user->can('documents.print') && ! $user->can('students.view') && ! $user->can('bulletins.view')) {
            return self::DENY_PERMISSION;
        }

        if (! $this->requiresApproval()) {
            return null;
        }

        if ($this->soldeImpaye($etudiantId) > 0) {
            return self::DENY_SOLDE;
        }

        if ($this->latestApproved($documentType, $etudiantId, $documentId) === null) {
            return self::DENY_APPROVAL;
        }

        return null;
    }

    public function soldeImpaye(int $etudiantId): float
    {
        $etudiant = ESBTPEtudiant::query()->find($etudiantId);
        $inscription = $etudiant?->inscription_active;

        if (! $inscription) {
            return 0.0;
        }

        $du = ESBTPFraisSubscription::dueAmountForInscription($inscription->id);
        $paye = ESBTPPaiement::netPaidForInscription((int) $inscription->id);

        return round(max(0, $du - $paye), 2);
    }

    public function message(string $reason, int $etudiantId = 0): string
    {
        return match ($reason) {
            self::DENY_SOLDE => sprintf(
                'Impression bloquée : solde impayé de %s F. L\'étudiant doit régulariser en caisse.',
                number_format($this->soldeImpaye($etudiantId), 0, ',', ' ')
            ),
            self::DENY_APPROVAL => 'Impression bloquée : l\'accord de la responsable scolarité est requis.',
            self::DENY_PERMISSION => 'Vous n\'avez pas le droit d\'imprimer ce document.',
            default => 'Impression bloquée.',
        };
    }

    /**
     * @param  iterable<int, object>  $documents  objets avec id + etudiant_id
     * @return array{allowed_ids: array<int, int>, bloques_solde: int, bloques_approbation: int}
     */
    public function filtrerExport(User $user, string $documentType, iterable $documents): array
    {
        $allowed = [];
        $solde = 0;
        $approbation = 0;

        foreach ($documents as $document) {
            $reason = $this->denyReason(
                $user,
                $documentType,
                (int) $document->etudiant_id,
                (int) $document->id
            );

            if ($reason === self::DENY_SOLDE) {
                $solde++;
                continue;
            }

            if ($reason === self::DENY_APPROVAL || $reason === self::DENY_PERMISSION) {
                $approbation++;
                continue;
            }

            $allowed[] = (int) $document->id;
        }

        return [
            'allowed_ids' => $allowed,
            'bloques_solde' => $solde,
            'bloques_approbation' => $approbation,
        ];
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
