<?php

namespace App\Http\Controllers;

use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Domain\BtsTroncCommun\PlanningToMaquetteImporter;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestes de maquette qui debordent l'edition ligne a ligne : import depuis le
 * planning general, promotion d'un ordre en ordre general, retour a l'ordre
 * general.
 *
 * Separe de `ESBTPMatiereClassificationController` pour que ni l'un ni l'autre
 * ne devienne un fourre-tout.
 */
class ESBTPMatiereMaquetteController extends Controller
{
    public function __construct(private readonly PlanningToMaquetteImporter $importer)
    {
        $this->middleware(['auth', 'permission:matieres.edit']);
    }

    /**
     * Apercu, puis application, de l'import depuis le planning general.
     *
     * `appliquer = false` (defaut) n'ecrit RIEN : ni semestre, ni drapeau de
     * validation, ni audit. C'est un apercu, et un apercu qui ecrit n'est pas
     * un apercu.
     */
    public function importPlanning(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'filiere_id' => ['required', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['required', 'integer', 'exists:esbtp_niveau_etudes,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'appliquer' => ['sometimes', 'boolean'],
            // Empreinte rendue par l'apercu. OBLIGATOIRE pour appliquer : si
            // elle etait facultative, un appel qui l'omet sauterait la garde en
            // silence et ecraserait le travail d'un autre — c'est exactement ce
            // que cette garde existe pour empecher.
            'empreinte' => ['required_if:appliquer,true', 'required_if:appliquer,1', 'string', 'size:64'],
        ]);

        $filiereId = (int) $valide['filiere_id'];
        $niveauId = (int) $valide['niveau_id'];
        $anneeId = (int) $valide['annee_universitaire_id'];

        if (! $request->boolean('appliquer')) {
            return response()->json([
                'success' => true,
                'applied' => false,
                'diff' => $this->importer->diff($filiereId, $niveauId, $anneeId),
            ]);
        }

        try {
            $diff = $this->importer->appliquer($filiereId, $niveauId, $anneeId, $valide['empreinte'] ?? null);
        } catch (\RuntimeException $e) {
            // 409 : la source a bouge depuis l'apercu. Ce n'est pas une erreur
            // de saisie, c'est un conflit — l'utilisateur doit relire.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'diff' => $this->importer->diff($filiereId, $niveauId, $anneeId),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'applied' => true,
            'message' => 'Semestres importés depuis le planning général.',
            'diff' => $diff,
        ]);
    }

    /**
     * Promeut l'ordre du combo en ordre general.
     *
     * L'ordre general sert de repli a tous les combos qui n'ont pas le leur.
     */
    public function ordreGeneral(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'ordres' => ['required', 'array', 'min:1'],
            'ordres.*.matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'ordres.*.ordre_bulletin' => ['required', 'integer', 'min:1', 'max:'.BulletinSubjectOrder::RANG_MAX],
        ]);

        try {
            $modifiees = 0;
            DB::transaction(function () use ($valide, &$modifiees) {
                // Un `update()` de masse ne declenche aucun evenement de modele,
                // donc aucune trace d'audit — alors que `ESBTPMatiere` demande
                // explicitement de tracer `ordre_bulletin`. Cette action reecrit
                // l'ordre de TOUTES les filieres a la fois : c'est precisement
                // celle dont on voudra retrouver l'auteur si un bulletin deja
                // distribue est conteste. On passe donc par le modele.
                $matieres = ESBTPMatiere::query()
                    ->whereIn('id', array_column($valide['ordres'], 'matiere_id'))
                    ->get()
                    ->keyBy('id');

                foreach ($valide['ordres'] as $ligne) {
                    $matiere = $matieres->get((int) $ligne['matiere_id']);

                    if (! $matiere) {
                        continue;
                    }

                    $matiere->ordre_bulletin = (int) $ligne['ordre_bulletin'];

                    if ($matiere->isDirty('ordre_bulletin')) {
                        $modifiees++;
                    }

                    $matiere->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Ordre général enregistré pour {$modifiees} matière(s).",
                'updated' => $modifiees,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement ordre général du bulletin', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'enregistrement de l'ordre général.",
            ], 500);
        }
    }

    /** Efface les rangs propres du combo : les matieres retrouvent l'ordre general. */
    public function resetOrdre(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'filiere_id' => ['required', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['required', 'integer', 'exists:esbtp_niveau_etudes,id'],
        ]);

        $modifiees = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', (int) $valide['filiere_id'])
            ->where('niveau_etude_id', (int) $valide['niveau_id'])
            ->whereNotNull('ordre_bulletin')
            ->update(['ordre_bulletin' => null]);

        return response()->json([
            'success' => true,
            'message' => "Retour à l'ordre général pour {$modifiees} matière(s).",
            'updated' => $modifiees,
        ]);
    }
}
