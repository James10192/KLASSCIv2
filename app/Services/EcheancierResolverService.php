<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPInscription;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Support\Collection;

class EcheancierResolverService
{
    public function resolveForConfiguration(?ESBTPFraisConfiguration $configuration, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        if (!$configuration) {
            return null;
        }

        return $this->resolveByScope(
            ESBTPEcheancierRule::SCOPE_CONFIGURATION,
            (int) $configuration->id,
            $affectationStatus
        );
    }

    public function resolveForOptionAssignment(?ESBTPOptionAssignment $assignment, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        if (!$assignment) {
            return null;
        }

        return $this->resolveByScope(
            ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
            (int) $assignment->id,
            $affectationStatus
        );
    }

    public function resolveByScope(string $scopeType, int $scopeId, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        $normalized = ESBTPEcheancierRule::normalizeStatus($affectationStatus);

        return ESBTPEcheancierRule::query()
            ->forScope($scopeType, $scopeId)
            ->active()
            ->validAt(now()->toDateString())
            ->whereIn('affectation_status', [$normalized, ESBTPEcheancierRule::STATUS_ALL])
            ->orderByRaw(
                "CASE WHEN affectation_status = ? THEN 0 WHEN affectation_status = ? THEN 1 ELSE 2 END",
                [$normalized, ESBTPEcheancierRule::STATUS_ALL]
            )
            ->orderBy('priority')
            ->orderByDesc('updated_at')
            ->with(['lines' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->first();
    }

    public function findBestAssignmentForInscription(?ESBTPFraisOption $option, ESBTPInscription $inscription): ?ESBTPOptionAssignment
    {
        if (!$option) {
            return null;
        }

        $assignments = $option->relationLoaded('assignments')
            ? $option->assignments
            : $option->assignments()->active()->get();

        if ($assignments->isEmpty()) {
            return null;
        }

        return $this->matchByPriority($assignments, $inscription);
    }

    private function matchByPriority(Collection $assignments, ESBTPInscription $inscription): ?ESBTPOptionAssignment
    {
        $assignments = $assignments->where('is_active', true)->values();

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'classe'
                && (int) $assignment->filiere_id === (int) $inscription->filiere_id
                && (int) $assignment->niveau_id === (int) $inscription->niveau_id;
        });
        if ($match) {
            return $match;
        }

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'filiere'
                && (int) $assignment->filiere_id === (int) $inscription->filiere_id;
        });
        if ($match) {
            return $match;
        }

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'niveau'
                && (int) $assignment->niveau_id === (int) $inscription->niveau_id;
        });
        if ($match) {
            return $match;
        }

        return $assignments->first(function ($assignment) {
            return $assignment->assignment_type === 'all';
        });
    }
}
