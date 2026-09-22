<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rebasculer une evaluation vers la matiere du bon systeme academique.
 *
 * Contrôleur à lui seul, comme {@see CLIEvaluationDeplacementController} et
 * {@see CLINotesRecomputeController} : cette action vivait dans
 * `CLIMaintenanceController`, qui passait déjà 1500 lignes, et le recalcul
 * des agrégats l'aurait fait grossir encore.
 */
class CLIEvaluationMatiereController extends BaseApiController
{
    /**
     * POST /api/cli/evaluations/{id}/matiere — rebascule une evaluation.
     *
     * Sert a reparer une fuite de selecteur : une evaluation posee sur une
     * matiere du mauvais systeme academique. Le mouvement n'est autorise que
     * s'il RETABLIT la coherence, jamais s'il la rompt.
     *
     * esbtp_notes porte une copie denormalisee de matiere_id : la deplacer en
     * meme temps est obligatoire, sinon les notes restent rattachees a
     * l'ancienne matiere et le bulletin continue de l'afficher.
     *
     * Body: { matiere_id: int, dry_run?: bool }
     */
    public function evaluationChangeMatiere(Request $request, $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
            'dry_run' => 'nullable|boolean',
        ]);

        $evaluation = ESBTPEvaluation::with(['classe', 'matiere'])->find($id);
        if (! $evaluation) {
            return $this->errorResponse("Evaluation #{$id} not found", [], 404);
        }

        $cible = ESBTPMatiere::find($validated['matiere_id']);

        if ($refus = $this->refuserUneCibleIncoherente($evaluation, $cible)) {
            return $refus;
        }

        $notes = ESBTPNote::where('evaluation_id', $evaluation->id)->count();

        if ((bool) ($validated['dry_run'] ?? false)) {
            return $this->apercuDeRebascule($evaluation, $cible, $notes);
        }

        $avant = ['matiere_id' => $evaluation->matiere_id, 'matiere' => $evaluation->matiere?->name];

        // Coordonnees completes d'AVANT : le recalcul doit rafraichir les deux
        // cotes du deplacement, celui qu'on quitte comme celui qu'on rejoint.
        $coordonneesAvant = [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];

        DB::transaction(function () use ($evaluation, $cible) {
            $evaluation->matiere_id = $cible->id;
            $evaluation->save();

            // Colonne denormalisee : sans cette mise a jour, les notes
            // resteraient rattachees a l'ancienne matiere.
            ESBTPNote::where('evaluation_id', $evaluation->id)
                ->update(['matiere_id' => $cible->id]);
        });

        // Cet `update()` de query builder n'emet aucun evenement Eloquent :
        // sans l'appel qui suit, `esbtp_resultats` garderait des deux cotes la
        // moyenne d'avant, et cette moyenne perimee l'emporte sur les notes a
        // l'affichage comme au bulletin. Hors transaction a dessein : le
        // deplacement est acquis meme si un recalcul echoue.
        $recalcul = RecalculApresDeplacement::pour($evaluation, $coordonneesAvant, $request->user()->id);

        Log::warning('CLI: evaluation rebasculee', [
            'evaluation_id' => $evaluation->id,
            'classe' => $evaluation->classe?->name,
            'avant' => $avant,
            'apres' => ['matiere_id' => $cible->id, 'matiere' => $cible->name],
            'notes_deplacees' => $notes,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'evaluation_id' => $evaluation->id,
            'classe' => $evaluation->classe?->name,
            'matiere_avant' => $avant['matiere'],
            'matiere_apres' => $cible->name,
            'notes_deplacees' => $notes,
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'agregats_orphelins' => $recalcul['orphelins'],
            'recalculs_en_echec' => $recalcul['echecs'],
        ], $this->messageDeRebascule($evaluation->id, $cible->name, $notes, $recalcul));
    }

    /** Previsualisation : aucune ecriture, ni sur l'evaluation ni sur les agregats. */
    private function apercuDeRebascule(ESBTPEvaluation $evaluation, ESBTPMatiere $cible, int $notes): JsonResponse
    {
        return $this->successResponse([
            'dry_run' => true,
            'evaluation_id' => $evaluation->id,
            'titre' => $evaluation->titre,
            'classe' => $evaluation->classe?->name,
            'matiere_actuelle' => $evaluation->matiere?->name,
            'matiere_cible' => $cible->name,
            'notes_a_deplacer' => $notes,
        ], 'Aucune ecriture : previsualisation seulement.');
    }

    /**
     * Refuse un mouvement qui ROMPRAIT la coherence au lieu de la retablir :
     * une ECUE du LMD vers une classe BTS, ou l'inverse. Le garde vit dans
     * {@see CoherenceSystemeAcademique}, partage avec les deux modeles qui
     * refusent a l'ecriture.
     */
    private function refuserUneCibleIncoherente(ESBTPEvaluation $evaluation, ESBTPMatiere $cible): ?JsonResponse
    {
        if (CoherenceSystemeAcademique::estCoherente(
            $evaluation->classe?->systeme_academique,
            $cible->unite_enseignement_id
        )) {
            return null;
        }

        $classeEstLmd = CoherenceSystemeAcademique::classeEstLmd($evaluation->classe?->systeme_academique);
        $cibleEstEcue = CoherenceSystemeAcademique::matiereEstEcue($cible->unite_enseignement_id);

        return $this->errorResponse(
            'Refus : la matiere cible ne correspond pas au systeme de la classe. '
            .'Classe '.($classeEstLmd ? 'LMD' : 'BTS').', matiere cible '
            .($cibleEstEcue ? 'ECUE LMD' : 'BTS').'.',
            [],
            422
        );
    }

    /**
     * @param  array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $recalcul
     */
    private function messageDeRebascule(int $evaluationId, string $cible, int $notes, array $recalcul): string
    {
        $message = "Evaluation #{$evaluationId} rebasculee sur '{$cible}' ({$notes} note(s) suivie(s), "
            ."{$recalcul['recalculs_tentes']} recalcul(s) lance(s)).";

        if ($recalcul['orphelins'] !== []) {
            $message .= ' '.count($recalcul['orphelins']).' agregat(s) n ont plus rien a moyenner (aucune note, '
                .'ou seulement des absences) : ils sont laisses en place, jamais remis a zero, '
                .'et leur sort est une decision d ecole.';
        }

        return $message;
    }
}
