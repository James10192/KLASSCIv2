<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Services\BulletinService;

class BulletinRankRecalculationService
{
    private const DEFAULT_PERIODS = ['semestre1', 'semestre2'];
    private const SAMPLE_LIMIT = 24;

    public function __construct(private BulletinService $bulletinService)
    {
    }

    /**
     * Recalculate persisted BTS bulletin ranks for one academic year.
     *
     * @return array{
     *     mode:string,
     *     annee_universitaire_id:int,
     *     classe_id:?int,
     *     periodes:list<string>,
     *     classes_scannees:int,
     *     bulletins_lus:int,
     *     rang_1_avant:int,
     *     rang_1_apres:int,
     *     echantillons:list<array<string, mixed>>
     * }
     */
    public function recalculate(bool $apply, ?int $anneeUniversitaireId = null, ?int $classeId = null, ?string $periodeCsv = null): array
    {
        $annee = $anneeUniversitaireId
            ? ESBTPAnneeUniversitaire::find($anneeUniversitaireId)
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee) {
            throw new \InvalidArgumentException('Annee universitaire introuvable');
        }
        $anneeUniversitaireId = (int) $annee->id;

        $periodes = $this->normalizePeriodes($periodeCsv);
        $classes = $this->btsClasses($anneeUniversitaireId, $classeId);

        $bulletinsLus = 0;
        $rangUnAvant = 0;
        $rangUnApres = 0;
        $rangsChanges = 0;
        $samples = [];

        foreach ($classes as $classe) {
            foreach ($periodes as $periode) {
                $proposals = $this->bulletinService->previewRanksForClasse(
                    (int) $classe->id,
                    $anneeUniversitaireId,
                    $periode
                );
                $bulletinsLus += count($proposals);

                foreach ($proposals as $proposal) {
                    $changed = ($proposal['rang_actuel'] ?? null) !== ($proposal['rang_propose'] ?? null);
                    if (($proposal['rang_actuel'] ?? null) === 1) {
                        $rangUnAvant++;
                    }
                    if (($proposal['rang_propose'] ?? null) === 1) {
                        $rangUnApres++;
                    }
                    if ($changed) {
                        $rangsChanges++;
                    }

                    $sample = [
                        'id' => $proposal['id'],
                        'classe_id' => (int) $classe->id,
                        'periode' => $periode,
                        'moyenne' => $proposal['moyenne'],
                        'rang_actuel' => $proposal['rang_actuel'],
                        'rang_propose' => $proposal['rang_propose'],
                    ];
                    if ($changed) {
                        array_unshift($samples, $sample);
                    } elseif (count($samples) < self::SAMPLE_LIMIT) {
                        $samples[] = $sample;
                    }
                    if (count($samples) > self::SAMPLE_LIMIT) {
                        $samples = array_slice($samples, 0, self::SAMPLE_LIMIT);
                    }
                }

                if ($apply) {
                    $this->bulletinService->calculerRangsPourClasse(
                        (int) $classe->id,
                        $anneeUniversitaireId,
                        $periode
                    );
                }
            }
        }

        return [
            'mode' => $apply ? 'APPLIQUE' : 'DRY-RUN (aucune ecriture)',
            'annee_universitaire_id' => $anneeUniversitaireId,
            'classe_id' => $classeId,
            'periodes' => $periodes,
            'classes_scannees' => $classes->count(),
            'bulletins_lus' => $bulletinsLus,
            'rang_1_avant' => $rangUnAvant,
            'rang_1_apres' => $rangUnApres,
            'rangs_changes' => $rangsChanges,
            'note' => 'Recalcule uniquement bulletin.rang et effectif_classe. Aucun PDF n est regenere.',
            'echantillons' => $samples,
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizePeriodes(?string $periodeCsv): array
    {
        if ($periodeCsv === null || trim($periodeCsv) === '') {
            $raw = self::DEFAULT_PERIODS;
        } else {
            $raw = preg_split('/\s*,\s*/', $periodeCsv) ?: [];
        }

        $periodes = [];
        foreach ($raw as $periode) {
            $normalized = $this->bulletinService->normalizePeriode((string) $periode);
            if ($normalized === '' || $normalized === 'annuel') {
                continue;
            }
            $periodes[] = $normalized;
        }

        $periodes = array_values(array_unique($periodes));

        return $periodes === [] ? self::DEFAULT_PERIODS : $periodes;
    }

    private function btsClasses(int $anneeUniversitaireId, ?int $classeId)
    {
        return ESBTPClasse::query()
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->when($classeId, fn ($query) => $query->where('id', $classeId))
            ->where(fn ($query) => $query->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->whereIn('id', function ($query) use ($anneeUniversitaireId) {
                $query->select('classe_id')
                    ->from('esbtp_bulletins')
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->whereNull('deleted_at')
                    ->whereNull('archived_at');
            })
            ->orderBy('id')
            ->get(['id', 'name', 'systeme_academique']);
    }
}
