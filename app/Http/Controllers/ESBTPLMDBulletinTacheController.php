<?php

namespace App\Http\Controllers;

use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Domain\AcademicPilotage\Services\BulletinGenerationReadinessService;
use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Domain\Bulletins\Taches\SuiviTachesBulletins;
use App\Models\ESBTPClasse;
use App\Services\LMDBulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lance la génération LMD d'une classe en arrière-plan.
 *
 * Avant, la génération tournait dans la requête : sur une connexion lente,
 * la page restait figée sans aucun signe, l'utilisateur recliquait, et
 * l'hébergement coupe une requête à trente secondes. La tâche, elle, avance
 * par tranches tant que l'onglet est ouvert, et la planification la finit
 * si on le ferme — exactement comme pour le BTS.
 */
class ESBTPLMDBulletinTacheController extends Controller
{
    /** POST /esbtp/lmd/bulletins/generer-classe/tache */
    public function lancer(
        Request $request,
        LMDBulletinService $service,
        BulletinGenerationReadinessService $readiness,
        LancementTachesBulletins $lancement,
    ): JsonResponse {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
            'incomplete_reason' => 'nullable|string|max:1000',
            'seulement_prets' => 'sometimes|boolean',
        ]);

        $classe = ESBTPClasse::findOrFail($validated['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422, 'Cette classe est BTS. Utilisez la génération des bulletins BTS.');

        $semestre = (int) $validated['semestre'];
        if (! in_array($semestre, $classe->getSemestresLMD(), true)) {
            return $this->refus("Le semestre S{$semestre} ne correspond pas au niveau de cette classe.");
        }

        $anneeId = (int) $validated['annee_universitaire_id'];
        $ids = $service->studentIdsForGenerationCohort((int) $classe->id, $anneeId)
            ->map(fn ($id) => (int) $id)->values()->all();
        if ($ids === []) {
            return $this->refus('Aucun étudiant actif trouvé pour cette classe et cette année universitaire.');
        }

        // Le pré-contrôle a déjà prévenu ; on ne lance pas une tâche vouée à
        // tout refuser. Un dossier bloqué ne passe qu'avec le droit et un motif.
        $motif = trim((string) ($validated['incomplete_reason'] ?? ''));
        $prets = array_values(array_filter($ids, fn (int $id) => $readiness
            ->inspect('LMD', $id, (int) $classe->id, $anneeId, 'semestre'.$semestre)->ready));
        $bloques = count($ids) - count($prets);
        if ($bloques > 0) {
            $provisoire = $request->user()->can('bulletins.generate_incomplete') && mb_strlen($motif) >= 8;
            if ($provisoire) {
                // Tous, avec le motif : les dossiers incomplets reçoivent un bulletin provisoire.
            } elseif ($request->boolean('seulement_prets') && $prets !== []) {
                // Un étudiant sans note ne doit pas priver les autres de leur bulletin.
                $ids = $prets;
                $motif = '';
            } else {
                return $this->refus("{$bloques} dossier(s) ne peuvent pas encore recevoir de bulletin. Relancez le contrôle pour voir quoi corriger.");
            }
        }

        $tache = $lancement->generation(
            $request->user(),
            $classe,
            $anneeId,
            (string) $semestre,
            $ids,
            false,
            $motif !== '' ? $motif : null,
            'lmd',
        );

        return response()->json(['success' => true, 'tache' => SuiviTachesBulletins::etat($tache)], 201);
    }

    /**
     * POST /esbtp/lmd/bulletins/preparer-fiches
     *
     * Crée les fiches de notes manquantes du semestre et y reporte les notes
     * déjà saisies, sans quitter l'écran. Ne valide rien : la validation reste
     * un geste humain dans le Pilotage académique.
     */
    public function preparerFiches(
        Request $request,
        AcademicPilotageBackfillService $backfill,
        AcademicPeriodNormalizer $periods,
    ): JsonResponse {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
        ]);

        // Écran LMD : on ne prépare jamais les fiches d'une classe BTS d'ici.
        $classe = ESBTPClasse::findOrFail($validated['classe_id']);
        abort_unless(($classe->systeme_academique ?? '') === 'LMD', 422, 'Cette classe est BTS.');

        $creees = 0;
        $echecs = 0;
        // Les évaluations portent le semestre sous plusieurs écritures (« 1 »,
        // « semestre1 », « S1 »…) : le rattrapage filtre par égalité stricte.
        $variantes = array_unique(array_merge(
            $periods->databaseVariants('semestre'.$validated['semestre']),
            ['semestre '.$validated['semestre']],
        ));
        foreach ($variantes as $periode) {
            $bilan = $backfill->run([
                'class_id' => (int) $validated['classe_id'],
                'year_id' => (int) $validated['annee_universitaire_id'],
                'period' => $periode,
                'limit' => 1000,
            ], $request->user());
            $creees += (int) $bilan['created'];
            $echecs += (int) $bilan['failed'];
        }

        $message = $creees > 0
            ? "{$creees} fiche(s) préparée(s) avec les notes déjà saisies. Il reste à les valider dans le Pilotage académique."
            : 'Aucune fiche à préparer : elles existent déjà. Il reste à les valider dans le Pilotage académique.';
        if ($echecs > 0) {
            $message .= " {$echecs} évaluation(s) n'ont pas pu être préparées (classe, matière ou semestre manquant).";
        }

        return response()->json(['success' => true, 'creees' => $creees, 'echecs' => $echecs, 'message' => $message]);
    }

    private function refus(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
