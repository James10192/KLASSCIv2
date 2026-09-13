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
        $peutImprimer = $this->porteLeDroit($user, $documentType);

        // On ne cherche l'accord que pour qui n'a pas le droit : celui qui l'a
        // n'en a pas besoin pour passer la porte, et le solde doit pouvoir le
        // barrer avant toute lecture d'accord.
        $approval = $peutImprimer
            ? null
            : $this->latestApproved($documentType, $etudiantId, $documentId);

        if (! $peutImprimer && $approval === null) {
            // Refus, mais la porte reste ouverte : l'ecran proposera de demander
            // l'autorisation a qui a le droit de l'accorder.
            return PrintDecision::denied(PrintDecision::PERMISSION, 0.0);
        }

        if (! $this->requiresApproval()) {
            return $approval === null
                ? PrintDecision::open()
                : PrintDecision::approved($approval);
        }

        $solde = $this->soldes->impaye($etudiantId);
        if ($solde > 0) {
            return PrintDecision::denied(PrintDecision::SOLDE, $solde);
        }

        $approval ??= $this->latestApproved($documentType, $etudiantId, $documentId);
        if ($approval === null) {
            return PrintDecision::denied(PrintDecision::APPROVAL, $solde);
        }

        return PrintDecision::approved($approval, $solde);
    }

    /**
     * Le droit qui gouverne ce document — et c'est le registre qui le dit, pas
     * cette garde.
     *
     * Certificat et attestation appartiennent a la famille `documents.*` : leur
     * impression demande `documents.print`. Avant, la garde acceptait aussi
     * « voir les etudiants » ou « voir les bulletins », donc elle n'exigeait
     * jamais le droit d'imprimer : caissier, comptable, enseignant, agent
     * d'inscription et directeur des etudes sortaient des certificats signes au
     * nom du directeur alors que le registre le leur refuse.
     *
     * Le bulletin, lui, a sa propre famille (`bulletins.*`) et ses propres
     * gardes de route. Lui imposer EN PLUS `documents.print` ne fermerait pas un
     * abus : cela casserait l'export groupe du coordinateur, a qui l'ecole a
     * justement accorde `bulletins.export.bulk`.
     */
    private function porteLeDroit(User $user, string $documentType): bool
    {
        if ($documentType === 'bulletin') {
            return $user->can('bulletins.view');
        }

        return $user->can('documents.print');
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

        if (! $this->porteLeDroit($user, $documentType)) {
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
