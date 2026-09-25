<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\Diagnostics\TcSpecialiteLeakDiagnostic;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * GET /api/cli/diagnostics/tc-specialite-leak — lecture seule.
 *
 * Matieres de specialite qui atteignent les bulletins de tronc commun, avec les
 * etudiants et les notes qui les y portent, la cause probable et l'action
 * suggeree. N'ecrit rien : ni classification, ni deplacement de note, ni
 * regeneration de bulletin. Le sort de chaque ligne reste une decision d'ecole.
 */
class CLITcSpecialiteLeakController extends BaseApiController
{
    public function index(Request $request, TcSpecialiteLeakDiagnostic $diagnostic): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $validator = Validator::make($request->query(), [
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'etudiant_id' => ['nullable', 'integer', 'exists:esbtp_etudiants,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Parametres invalides.', $validator->errors()->toArray(), 422);
        }

        try {
            $rapport = $diagnostic->executer(
                $this->entier($request, 'annee_universitaire_id'),
                $this->entier($request, 'classe_id'),
                $this->entier($request, 'etudiant_id'),
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        }

        $resume = $rapport['resume'];

        return $this->successResponse($rapport, $resume['matieres_suspectes'] === 0
            ? 'Aucune matiere de specialite ne remonte sur un bulletin de tronc commun.'
            : $resume['matieres_suspectes'].' matiere(s) suspecte(s), '.$resume['etudiants_touches'].' etudiant(s) touche(s).');
    }

    private function entier(Request $request, string $cle): ?int
    {
        return $request->filled($cle) ? (int) $request->query($cle) : null;
    }
}
