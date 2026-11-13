<?php

namespace App\Domain\Bulletins\Taches;

use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\BulletinGenerationReadinessService;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Services\LMD\Exceptions\MaquetteSansCompositionException;
use App\Services\LMDBulletinService;
use Illuminate\Support\Facades\Log;

/**
 * Une tranche de génération de bulletins LMD, pour le moteur de tâches.
 *
 * Le calcul reste celui de LMDBulletinService (rule lmd-bts-bulletin-separation) :
 * cette classe ne fait que l'appeler élève par élève, sans classer, et rendre
 * le bilan dans le même format que la génération BTS pour que le suivi, le
 * toast et la notification n'aient rien à distinguer.
 */
class GenerationLmdParTranche
{
    public function __construct(
        private readonly LMDBulletinService $service,
        private readonly BulletinGenerationReadinessService $readiness,
    ) {}

    /** @param array<int, int> $ids */
    public function generer(BulletinTache $tache, array $ids): BulkBulletinGenerationResult
    {
        $classeId = (int) $tache->classe_id;
        $anneeId = (int) $tache->annee_universitaire_id;
        $semestre = (int) $tache->periode;
        $created = 0;
        $regenerated = 0;
        $blocking = [];
        $errors = [];
        $noms = ESBTPEtudiant::select('id', 'nom', 'prenoms')->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($ids as $id) {
            $nom = trim((string) ($noms->get($id)?->nom_complet ?? '')) ?: "Étudiant #{$id}";
            try {
                // Recontrôlé ici : les notes ont pu bouger depuis le lancement.
                $this->readiness->assertReady(
                    'LMD', $id, $classeId, $anneeId, 'semestre'.$semestre,
                    $tache->user,
                    $tache->parametre('incomplete_reason'),
                );

                $existait = ESBTPLMDBulletin::where('etudiant_id', $id)->where('classe_id', $classeId)
                    ->where('annee_universitaire_id', $anneeId)->where('semestre', $semestre)->exists();

                $this->service->genererBulletinLMD($id, $classeId, $anneeId, $semestre, skipRanksAndStats: true);
                $existait ? $regenerated++ : $created++;
            } catch (MaquetteSansCompositionException $e) {
                // Vaut pour toute la classe : inutile de le constater 26 fois.
                throw new TacheBulletinsImpossible($e->getMessage());
            } catch (AcademicPilotageException $e) {
                $blocking[] = ['etudiant_id' => $id, 'nom' => $nom, 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                Log::error("Tâche bulletins #{$tache->id} : bulletin LMD de l'étudiant {$id} impossible", ['exception' => $e]);
                $errors[] = ['etudiant_id' => $id, 'nom' => $nom, 'message' => $e->getMessage()];
            }
        }

        return new BulkBulletinGenerationResult(
            created: $created,
            regenerated: $regenerated,
            blockingErrors: $blocking,
            errors: $errors,
        );
    }

    /** Rangs et statistiques, une seule fois sur toute la classe. */
    public function reclasser(BulletinTache $tache): void
    {
        $classeId = (int) $tache->classe_id;
        $anneeId = (int) $tache->annee_universitaire_id;
        $semestre = (int) $tache->periode;

        $this->service->calculerRangsClasse($classeId, $anneeId, $semestre);
        $this->service->calculerStatsPromo($classeId, $anneeId, $semestre);
    }
}
