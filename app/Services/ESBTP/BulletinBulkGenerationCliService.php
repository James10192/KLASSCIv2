<?php

namespace App\Services\ESBTP;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Services\BulletinService;

class BulletinBulkGenerationCliService
{
    public function __construct(
        private BtsBulkBulletinGenerationService $bulkGeneration,
        private BulletinService $bulletinService,
    ) {
    }

    /**
     * Preview or generate missing official BTS bulletins for one class.
     *
     * @return array<string, mixed>
     */
    public function generate(
        bool $apply,
        ?int $anneeUniversitaireId,
        int $classeId,
        string $periode,
        bool $recalculate = false,
        ?string $incompleteReason = null,
        $actor = null,
    ): array {
        $annee = $anneeUniversitaireId
            ? ESBTPAnneeUniversitaire::find($anneeUniversitaireId)
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee) {
            throw new \InvalidArgumentException('Annee universitaire introuvable');
        }

        $classe = ESBTPClasse::find($classeId);
        if (! $classe) {
            throw new \InvalidArgumentException('Classe introuvable');
        }
        if (($classe->systeme_academique ?? '') === 'LMD') {
            throw new \InvalidArgumentException('Classe LMD hors perimetre BTS');
        }

        $periode = $this->bulletinService->normalizePeriode($periode);
        $preflight = $this->bulkGeneration->preflight(
            $classe,
            (int) $annee->id,
            $periode,
            $actor,
            $recalculate
        );

        $payload = [
            'mode' => $apply ? 'APPLIQUE' : 'DRY-RUN (aucune ecriture)',
            'annee_universitaire_id' => (int) $annee->id,
            'classe_id' => (int) $classe->id,
            'classe' => $classe->name,
            'periode' => $periode,
            'recalculer' => $recalculate,
            'students_count' => $preflight['students_count'] ?? 0,
            'generatable_count' => $preflight['generatable_count'] ?? 0,
            'existing_count' => $preflight['existing_count'] ?? 0,
            'status' => $preflight['status'] ?? null,
            'can_generate' => (bool) ($preflight['can_generate'] ?? false),
            'preflight' => $this->compactPreflight($preflight),
        ];

        if (! $apply) {
            $payload['created'] = 0;
            $payload['regenerated'] = 0;
            $payload['note'] = 'Preflight seulement. apply=1 cree les bulletins manquants puis recalcule les rangs.';

            return $payload;
        }

        if (! ($preflight['can_generate'] ?? false) && $incompleteReason === null) {
            $payload['created'] = 0;
            $payload['regenerated'] = 0;
            $payload['note'] = 'Apply refuse : le preflight n est pas ready.';

            return $payload;
        }

        $result = $this->bulkGeneration->generate(
            $classe,
            (int) $annee->id,
            $periode,
            $actor,
            $recalculate,
            $incompleteReason
        );
        $array = $result->toArray();

        return $payload + [
            'created' => $array['created'],
            'regenerated' => $array['regenerated'],
            'skipped_count' => count($array['skipped'] ?? []),
            'blocking_count' => count($array['blocking_errors'] ?? []),
            'error_count' => count($array['errors'] ?? []),
            'result' => [
                'ok' => $array['ok'],
                'message' => $array['message'],
                'blocking_errors' => array_slice($array['blocking_errors'] ?? [], 0, 12),
                'errors' => array_slice($array['errors'] ?? [], 0, 8),
            ],
            'note' => 'Generation officielle terminee. Les rangs de la classe sont recalcules si au moins un bulletin a ete ecrit.',
        ];
    }

    /**
     * @param array<string, mixed> $preflight
     * @return array<string, mixed>
     */
    private function compactPreflight(array $preflight): array
    {
        return [
            'status' => $preflight['status'] ?? null,
            'message' => $preflight['message'] ?? null,
            'students_count' => $preflight['students_count'] ?? 0,
            'generatable_count' => $preflight['generatable_count'] ?? 0,
            'existing_count' => $preflight['existing_count'] ?? 0,
            'existing_empty_count' => $preflight['existing_empty_count'] ?? 0,
            'has_hard_blocks' => (bool) ($preflight['has_hard_blocks'] ?? false),
            'missing_professeurs' => $preflight['missing_professeurs'] ?? [],
            'missing_coefficients' => $preflight['missing_coefficients'] ?? [],
            'blocking_errors' => array_slice($preflight['blocking_errors'] ?? [], 0, 12),
        ];
    }
}
