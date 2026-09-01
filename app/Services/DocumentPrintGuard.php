<?php

namespace App\Services;

use App\Exceptions\ImpressionBloquee;
use App\Models\ESBTPDocumentApproval;
use App\Models\User;

class DocumentPrintGuard
{
    public function __construct(
        private readonly TenantScolariteSettings $settings,
        private readonly SoldeEtudiant $soldes,
    ) {
    }

    public function requiresApproval(): bool
    {
        return $this->settings->printRequiresApproval();
    }

    public function decide(User $user, string $documentType, int $etudiantId, ?int $documentId = null): PrintDecision
    {
        if (! $user->can('documents.print') && ! $user->can('students.view') && ! $user->can('bulletins.view')) {
            return PrintDecision::denied(PrintDecision::PERMISSION, 0.0, false);
        }

        if (! $this->requiresApproval()) {
            return PrintDecision::open();
        }

        $solde = $this->soldes->impaye($etudiantId);
        if ($solde > 0) {
            return PrintDecision::denied(PrintDecision::SOLDE, $solde);
        }

        $approval = $this->latestApproved($documentType, $etudiantId, $documentId);
        if ($approval === null) {
            return PrintDecision::denied(PrintDecision::APPROVAL, $solde);
        }

        return PrintDecision::approved($approval, $solde);
    }

    public function assertPrintable(User $user, string $documentType, int $etudiantId, ?int $documentId = null): PrintDecision
    {
        $decision = $this->decide($user, $documentType, $etudiantId, $documentId);
        if (! $decision->allowed) {
            throw new ImpressionBloquee($decision);
        }

        return $decision;
    }

    /**
     * @param  iterable<int, object>  $documents  objets avec id + etudiant_id
     * @return array{allowed_ids: array<int, int>, bloques_solde: int, bloques_approbation: int}
     */
    public function filtrerExport(User $user, string $documentType, iterable $documents): array
    {
        $docs = collect($documents);
        $ids = $docs->map(fn ($d) => (int) $d->id)->all();

        if ($docs->isEmpty() || ! $this->requiresApproval()) {
            return ['allowed_ids' => $ids, 'bloques_solde' => 0, 'bloques_approbation' => 0];
        }

        if (! $user->can('documents.print') && ! $user->can('students.view') && ! $user->can('bulletins.view')) {
            return ['allowed_ids' => [], 'bloques_solde' => 0, 'bloques_approbation' => 0];
        }

        $etudiantIds = $docs->map(fn ($d) => (int) $d->etudiant_id)->unique()->values()->all();
        $soldes = $this->soldes->impayes($etudiantIds);
        $approvals = $this->approvalsIndex($documentType, $etudiantIds, $ids);

        $allowed = [];
        $solde = 0;
        $approbation = 0;

        foreach ($docs as $document) {
            $etudiantId = (int) $document->etudiant_id;
            $documentId = (int) $document->id;

            if (($soldes[$etudiantId] ?? 0) > 0) {
                $solde++;
                continue;
            }

            if (! isset($approvals[$etudiantId][$documentId]) && ! isset($approvals[$etudiantId][0])) {
                $approbation++;
                continue;
            }

            $allowed[] = $documentId;
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

    /**
     * @param  array<int, int>  $etudiantIds
     * @param  array<int, int>  $documentIds
     * @return array<int, array<int, true>>
     */
    private function approvalsIndex(string $documentType, array $etudiantIds, array $documentIds): array
    {
        if ($etudiantIds === []) {
            return [];
        }

        $rows = ESBTPDocumentApproval::query()
            ->where('document_type', $documentType)
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('status', ESBTPDocumentApproval::STATUS_APPROVED)
            ->where(function ($q) use ($documentIds) {
                $q->whereIn('document_id', $documentIds)->orWhereNull('document_id');
            })
            ->get(['etudiant_id', 'document_id']);

        $index = [];
        foreach ($rows as $row) {
            $key = $row->document_id === null ? 0 : (int) $row->document_id;
            $index[(int) $row->etudiant_id][$key] = true;
        }

        return $index;
    }
}
