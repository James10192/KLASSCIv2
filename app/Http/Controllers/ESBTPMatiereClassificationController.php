<?php

namespace App\Http\Controllers;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Services\ClasseManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Affectation Tronc Commun / Spécialité des matières, au grain (matière, filière, niveau).
 *
 * Permet à l'école de marquer, pour un combo (filière, niveau), quelles matières
 * sont du tronc commun et lesquelles sont de spécialité. Le bulletin de tronc commun
 * n'affiche alors que les matières TC (ou non classées). BTS uniquement, LMD intouché.
 *
 * @see \App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver
 * @see .claude/rules/classe-lmd-filiere-as-mention.md
 */
class ESBTPMatiereClassificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:matieres.edit']);
    }

    /**
     * Page d'affectation TC / Spécialité (pickers filière + niveau).
     */
    public function index()
    {
        $filieres = ESBTPFiliere::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_tronc_commun', 'parent_id']);

        // Niveaux BTS uniquement (les niveaux LMD gèrent leurs matières via MatiereTreeBuilder).
        $niveaux = ESBTPNiveauEtude::query()
            ->whereNotIn('type', ClasseManagementService::LMD_TYPES)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('esbtp.matieres.classification', compact('filieres', 'niveaux'));
    }

    /**
     * Matières d'un combo (filière, niveau) avec leur classification + suggestion.
     */
    public function combo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
        ]);

        $filiereId = (int) $validated['filiere_id'];
        $niveauId = (int) $validated['niveau_id'];

        $filiere = ESBTPFiliere::find($filiereId);
        $isTroncCommun = $filiere && $filiere->isTroncCommun();

        // Suggestion : sur un combo TC, une matière aussi rattachée à une filière fille
        // (spécialité) du même niveau est probablement de spécialité mal rattachée.
        $specialiteSuggestionIds = [];
        if ($isTroncCommun) {
            $childFiliereIds = ESBTPFiliere::where('parent_id', $filiereId)->pluck('id');
            if ($childFiliereIds->isNotEmpty()) {
                $specialiteSuggestionIds = ESBTPMatiereFilierNiveau::query()
                    ->whereIn('filiere_id', $childFiliereIds)
                    ->where('niveau_etude_id', $niveauId)
                    ->pluck('matiere_id')
                    ->unique()
                    ->all();
            }
        }
        $specialiteSuggestionIds = array_flip($specialiteSuggestionIds);

        $rows = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->with('matiere:id,name,code,unite_enseignement_id,is_active')
            ->get()
            ->filter(fn ($row) => $row->matiere && $row->matiere->unite_enseignement_id === null) // BTS only
            ->map(fn ($row) => [
                'matiere_id' => $row->matiere_id,
                'name' => $row->matiere->name,
                'code' => $row->matiere->code,
                'is_active' => (bool) $row->matiere->is_active,
                'classification' => $row->classification,
                'suggested' => $row->classification === null && isset($specialiteSuggestionIds[$row->matiere_id])
                    ? ESBTPMatiereFilierNiveau::SPECIALITE
                    : null,
            ])
            ->sortBy('name')
            ->values();

        return response()->json([
            'success' => true,
            'is_tronc_commun' => $isTroncCommun,
            'filiere' => $filiere?->name,
            'matieres' => $rows,
            'kpis' => [
                'total' => $rows->count(),
                'tronc_commun' => $rows->where('classification', ESBTPMatiereFilierNiveau::TRONC_COMMUN)->count(),
                'specialite' => $rows->where('classification', ESBTPMatiereFilierNiveau::SPECIALITE)->count(),
                'non_classe' => $rows->whereNull('classification')->count(),
            ],
        ]);
    }

    /**
     * Enregistre en masse la classification des matières d'un combo.
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'classifications' => 'required|array',
            'classifications.*.matiere_id' => 'required|integer',
            'classifications.*.classification' => 'nullable|in:tronc_commun,specialite',
        ]);

        $filiereId = (int) $validated['filiere_id'];
        $niveauId = (int) $validated['niveau_id'];

        try {
            $updated = 0;
            DB::transaction(function () use ($validated, $filiereId, $niveauId, &$updated) {
                foreach ($validated['classifications'] as $item) {
                    $affected = ESBTPMatiereFilierNiveau::query()
                        ->where('filiere_id', $filiereId)
                        ->where('niveau_etude_id', $niveauId)
                        ->where('matiere_id', (int) $item['matiere_id'])
                        ->update(['classification' => $item['classification'] ?? null]);
                    $updated += $affected;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Classification enregistrée pour {$updated} matière(s).",
                'updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement classification TC/Spécialité', [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la classification.',
            ], 500);
        }
    }
}
