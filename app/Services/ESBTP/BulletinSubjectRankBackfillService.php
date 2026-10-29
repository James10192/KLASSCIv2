<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPResultatMatiere;
use App\Services\BulletinService;

class BulletinSubjectRankBackfillService
{
    private const SAMPLE_LIMIT = 24;

    public function __construct(private BulletinService $bulletinService)
    {
    }

    /**
     * Recalculate persisted per-subject ranks for existing official bulletins.
     *
     * @return array<string, mixed>
     */
    public function backfill(bool $apply, ?int $anneeUniversitaireId, int $classeId, string $periode): array
    {
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
        $bulletins = ESBTPBulletin::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('periode', $this->bulletinService->periodeAliases($periode))
            ->get();

        $rowsRead = 0;
        $rankOneBefore = 0;
        $rankOneAfter = 0;
        $changed = 0;
        $samples = [];

        foreach ($bulletins as $bulletin) {
            $rows = ESBTPResultatMatiere::query()
                ->where('bulletin_id', $bulletin->id)
                ->get();
            if ($rows->isEmpty()) {
                continue;
            }

            // Une ligne dispensee ou non notee ne se classe pas : on ne la
            // soumet meme pas au calcul, qui saurait lui trouver un rang a
            // partir des notes brutes restees en base.
            $ranks = $this->bulletinService->calculerRangsParMatierePourEtudiant(
                $rows->filter(fn ($row) => $row->estNotee())
                    ->pluck('matiere_id')->map(fn ($id) => (int) $id)->all(),
                (int) $bulletin->etudiant_id,
                $classeId,
                (int) $annee->id,
                $periode
            );

            foreach ($rows as $row) {
                $rowsRead++;
                $current = $row->rang === null ? null : (int) $row->rang;
                $proposedRaw = $row->estNotee() ? ($ranks[(int) $row->matiere_id] ?? '-') : '-';
                $proposed = is_numeric($proposedRaw) ? (int) $proposedRaw : null;
                if ($current === 1) {
                    $rankOneBefore++;
                }
                if ($proposed === 1) {
                    $rankOneAfter++;
                }
                if ($current === $proposed) {
                    continue;
                }

                $changed++;
                if (count($samples) < self::SAMPLE_LIMIT) {
                    $samples[] = [
                        'bulletin_id' => (int) $bulletin->id,
                        'etudiant_id' => (int) $bulletin->etudiant_id,
                        'matiere_id' => (int) $row->matiere_id,
                        'rang_actuel' => $current,
                        'rang_propose' => $proposed,
                    ];
                }

                if ($apply) {
                    $row->rang = $proposed;
                    $row->save();
                }
            }
        }

        return [
            'mode' => $apply ? 'APPLIQUE' : 'DRY-RUN (aucune ecriture)',
            'annee_universitaire_id' => (int) $annee->id,
            'classe_id' => $classeId,
            'classe' => $classe->name,
            'periode' => $periode,
            'bulletins_lus' => $bulletins->count(),
            'lignes_lues' => $rowsRead,
            'rangs_changes' => $changed,
            'rang_1_avant' => $rankOneBefore,
            'rang_1_apres' => $rankOneAfter,
            'echantillons' => $samples,
            'note' => $apply
                ? 'Rangs par matiere recalcules depuis les notes live de la cohorte. Aucun PDF regenere.'
                : 'Simulation. apply=1 ecrit esbtp_resultats_matieres.rang seulement.',
        ];
    }
}
