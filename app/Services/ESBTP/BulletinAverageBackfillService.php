<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Services\BulletinService;

class BulletinAverageBackfillService
{
    private const SAMPLE_LIMIT = 24;

    public function __construct(private BulletinService $bulletinService)
    {
    }

    /**
     * Fill empty official bulletin averages from the live semester snapshot.
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

        $empty = 0;
        $fillable = 0;
        $unchanged = 0;
        $samples = [];

        foreach ($bulletins as $bulletin) {
            $current = $bulletin->moyenne_generale;
            if ($current !== null && (float) $current > 0) {
                $unchanged++;
                continue;
            }

            $empty++;
            $live = $this->bulletinService->calculateStudentAverageForPeriode(
                (int) $bulletin->etudiant_id,
                (int) $bulletin->classe_id,
                (int) $annee->id,
                $periode
            );
            $attendance = $this->bulletinService->calculateEffectiveAttendanceNoteForStudent(
                (int) $bulletin->etudiant_id,
                (int) $bulletin->classe_id,
                (int) $annee->id,
                $periode
            );

            if ($live === null) {
                if (count($samples) < self::SAMPLE_LIMIT) {
                    $samples[] = [
                        'id' => (int) $bulletin->id,
                        'etudiant_id' => (int) $bulletin->etudiant_id,
                        'moyenne_actuelle' => $current,
                        'moyenne_proposee' => null,
                        'note_assiduite_proposee' => round((float) $attendance, 3),
                    ];
                }
                continue;
            }

            $fillable++;
            if (count($samples) < self::SAMPLE_LIMIT) {
                $samples[] = [
                    'id' => (int) $bulletin->id,
                    'etudiant_id' => (int) $bulletin->etudiant_id,
                    'moyenne_actuelle' => $current,
                    'moyenne_proposee' => round((float) $live, 2),
                    'note_assiduite_proposee' => round((float) $attendance, 3),
                ];
            }

            if ($apply) {
                $bulletin->moyenne_generale = round((float) $live, 2);
                $bulletin->note_assiduite = round((float) $attendance, 3);
                $bulletin->save();
            }
        }

        if ($apply && $fillable > 0) {
            $this->bulletinService->calculerRangsPourClasse($classeId, (int) $annee->id, $periode);
        }

        return [
            'mode' => $apply ? 'APPLIQUE' : 'DRY-RUN (aucune ecriture)',
            'annee_universitaire_id' => (int) $annee->id,
            'classe_id' => $classeId,
            'classe' => $classe->name,
            'periode' => $periode,
            'bulletins_lus' => $bulletins->count(),
            'vides' => $empty,
            'remplissables' => $fillable,
            'deja_remplis' => $unchanged,
            'echantillons' => $samples,
            'note' => $apply
                ? 'Moyennes vides remplies depuis le snapshot live, puis rangs recalcules. Aucun PDF regenere.'
                : 'Simulation. apply=1 ecrit moyenne_generale + note_assiduite, puis recalcule les rangs.',
        ];
    }
}
