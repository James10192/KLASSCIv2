<?php

namespace App\Services\ParentChatbot;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Canonical disclosure gate for academic information delivered to parents.
 */
final class ParentChatbotPublicationPolicy
{
    /** @return Builder<ESBTPNote> */
    public function publishedGradesForStudent(int $studentId): Builder
    {
        return ESBTPNote::query()
            ->where('etudiant_id', $studentId)
            ->whereHas('evaluation', fn (Builder $query) => $query
                ->where('is_published', true)
                ->where('notes_published', true));
    }

    public function gradeIsPublished(ESBTPNote $note): bool
    {
        return $this->publishedGradesForStudent((int) $note->etudiant_id)
            ->whereKey($note->getKey())
            ->exists();
    }

    /** @return Builder<ESBTPBulletin> */
    public function publishedReportCardsForStudent(int $studentId): Builder
    {
        return ESBTPBulletin::query()
            ->where('etudiant_id', $studentId)
            ->where('is_published', true)
            ->where('signature_directeur', true)
            ->where('signature_responsable', true);
    }

    public function reportCardIsPublished(ESBTPBulletin $bulletin): bool
    {
        return $this->publishedReportCardsForStudent((int) $bulletin->etudiant_id)
            ->whereKey($bulletin->getKey())
            ->exists();
    }

    /**
     * Revalidate and submit an academic disclosure while the underlying
     * publication rows are locked. This serializes a send with concurrent
     * depublication or signature changes for the same resources.
     *
     * @return array{publication_eligible: bool, result: mixed}
     */
    public function submitIfStillPublishable(?array $claim, Closure $submit): array
    {
        if ($claim === null) {
            return ['publication_eligible' => true, 'result' => $submit()];
        }

        $disclosure = $this->normalizedDisclosure($claim);
        if ($disclosure === null) {
            return ['publication_eligible' => false, 'result' => null];
        }

        return DB::transaction(function () use ($disclosure, $submit): array {
            $publishable = match ($disclosure['type']) {
                'grades' => $this->lockedGradesArePublished($disclosure['student_id'], $disclosure['resource_ids']),
                'report_card' => $this->lockedReportCardsArePublished($disclosure['student_id'], $disclosure['resource_ids']),
            };

            return $publishable
                ? ['publication_eligible' => true, 'result' => $submit()]
                : ['publication_eligible' => false, 'result' => null];
        }, 3);
    }

    /** @return array{type: 'grades'|'report_card', student_id: int, resource_ids: array<int, int>}|null */
    private function normalizedDisclosure(array $claim): ?array
    {
        $type = $claim['type'] ?? null;
        $studentId = $claim['student_id'] ?? null;
        $resourceIds = $claim['resource_ids'] ?? null;
        if (! is_string($type)
            || (! is_int($studentId) && ! ctype_digit((string) $studentId))
            || ! is_array($resourceIds)
            || $resourceIds === []) {
            return null;
        }

        $ids = array_values(array_unique(array_map('intval', $resourceIds)));
        if (count($ids) !== count($resourceIds) || in_array(0, $ids, true)) {
            return null;
        }

        if (! in_array($type, ['grades', 'report_card'], true)) {
            return null;
        }

        return [
            'type' => $type,
            'student_id' => (int) $studentId,
            'resource_ids' => $ids,
        ];
    }

    /** @param array<int, int> $noteIds */
    private function lockedGradesArePublished(int $studentId, array $noteIds): bool
    {
        $notes = ESBTPNote::query()
            ->where('etudiant_id', $studentId)
            ->whereKey($noteIds)
            ->lockForUpdate()
            ->get(['id', 'evaluation_id']);

        if ($notes->count() !== count($noteIds)) {
            return false;
        }

        $evaluationIds = $notes->pluck('evaluation_id')->map(fn ($id): int => (int) $id)->unique()->values();
        if ($evaluationIds->isEmpty()) {
            return false;
        }

        $evaluations = ESBTPEvaluation::query()
            ->whereKey($evaluationIds->all())
            ->lockForUpdate()
            ->get(['id', 'is_published', 'notes_published']);

        return $evaluations->count() === $evaluationIds->count()
            && $evaluations->every(fn (ESBTPEvaluation $evaluation): bool => (bool) $evaluation->is_published
                && (bool) $evaluation->notes_published);
    }

    /** @param array<int, int> $bulletinIds */
    private function lockedReportCardsArePublished(int $studentId, array $bulletinIds): bool
    {
        $bulletins = ESBTPBulletin::query()
            ->where('etudiant_id', $studentId)
            ->whereKey($bulletinIds)
            ->lockForUpdate()
            ->get(['id', 'is_published', 'signature_directeur', 'signature_responsable']);

        return $bulletins->count() === count($bulletinIds)
            && $bulletins->every(fn (ESBTPBulletin $bulletin): bool => (bool) $bulletin->is_published
                && (bool) $bulletin->signature_directeur
                && (bool) $bulletin->signature_responsable);
    }
}
