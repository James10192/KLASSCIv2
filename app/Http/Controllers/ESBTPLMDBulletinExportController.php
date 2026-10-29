<?php

namespace App\Http\Controllers;

use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Domain\Bulletins\Taches\SuiviTachesBulletins;
use App\Models\ESBTPLMDBulletin;
use App\Services\DocumentPrintGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sélection et lancement uniquement : aucun calcul de note ni rendu PDF ici. */
class ESBTPLMDBulletinExportController extends Controller
{
    private const PLAFOND_EXPORT = 400;

    public function lancer(Request $request, LancementTachesBulletins $lancement, DocumentPrintGuard $garde): JsonResponse
    {
        // Les droits sont vérifiés par la route ET ici : ne pas dépendre d'un
        // bouton masqué pour protéger les documents d'autres étudiants.
        abort_unless($request->user()?->can('module.lmd.access')
            && $request->user()->can('lmd.bulletins.view')
            && $request->user()->can('lmd.bulletins.export.bulk'), 403);

        $valide = $request->validate([
            'mode' => 'required|in:apercu,telechargement',
            'portee' => 'required|in:selection,filtres',
            'ids' => 'required_if:portee,selection|array|max:400',
            'ids.*' => 'integer|distinct|min:1',
            'classe_id' => 'nullable|integer|min:1',
            'annee_universitaire_id' => 'nullable|integer|min:1',
            'semestre' => 'nullable|integer|between:1,10',
            'search' => 'nullable|string|max:120',
        ]);

        $filtre = ESBTPLMDBulletinController::bulletinsFiltres($request);
        if ($valide['portee'] === 'selection') {
            $selection = array_map('intval', $valide['ids'] ?? []);
            if ($selection === []) {
                return response()->json(['success' => false, 'message' => 'Sélectionnez au moins un bulletin.'], 422);
            }
            $filtre->whereIn('id', $selection);
        }

        $total = (clone $filtre)->count();
        if ($total === 0) {
            return response()->json(['success' => false, 'message' => 'Aucun bulletin LMD ne correspond à cette sélection.'], 422);
        }
        if ($total > self::PLAFOND_EXPORT) {
            return response()->json(['success' => false, 'message' => "$total bulletins : choisissez une classe ou un semestre pour rester sous le plafond de ".self::PLAFOND_EXPORT.'.'], 422);
        }
        if ($valide['portee'] === 'selection' && $total !== count($selection)) {
            return response()->json(['success' => false, 'message' => 'La sélection contient un bulletin extérieur aux filtres courants. Recommencez la sélection.'], 422);
        }

        // Tri reproductible, y compris avec la pagination infinie de la page.
        // Aucun identifiant issu du navigateur n'est accepté sans vérification DB.
        $documents = $filtre->orderBy('classe_id')->orderBy('annee_universitaire_id')
            ->orderBy('semestre')->orderBy('etudiant_id')->orderBy('id')
            ->get(['id', 'etudiant_id']);
        $ids = $documents->pluck('id')->map(fn ($id) => (int) $id)->all();
        $autorises = $garde->filtrerExport($request->user(), 'bulletin', $documents, true);
        $ensemble = array_fill_keys($autorises['allowed_ids'], true);
        $idsPermis = array_values(array_filter($ids, fn ($id) => isset($ensemble[$id])));

        // Jamais de PDF partiel silencieux : la personne sait exactement ce
        // qui l'empêche d'imprimer avant qu'une tâche longue ne démarre.
        if (count($idsPermis) !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Export non lancé : %d bulletin(s) bloqués par un impayé, %d par une autorisation d’impression manquante. Retirez-les de la sélection ou régularisez la situation.',
                    $autorises['bloques_solde'], $autorises['bloques_approbation']
                ),
            ], 422);
        }

        $filtres = array_filter([
            'classe_id' => $valide['classe_id'] ?? null,
            'annee_universitaire_id' => $valide['annee_universitaire_id'] ?? null,
            'semestre' => $valide['semestre'] ?? null,
            'search' => $valide['search'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $tache = $lancement->export($request->user(), $valide['mode'], $idsPermis, [
            'filtres' => $filtres,
            'entete' => [],
            'ungenerated_ids' => [],
        ], 'lmd');

        return response()->json([
            'success' => true,
            'tache' => SuiviTachesBulletins::etat($tache, $request->user()),
        ], 201);
    }
}
