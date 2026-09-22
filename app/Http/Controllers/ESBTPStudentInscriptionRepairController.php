<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Services\StudentInscriptionRepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ESBTPStudentInscriptionRepairController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:admin.access', 'paywall']);
    }

    public function diagnose(
        Request $request,
        ESBTPEtudiant $etudiant,
        StudentInscriptionRepairService $service
    ): JsonResponse {
        $this->authorizeDiagnostic($request);

        $validated = $request->validate([
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'target_classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
        ]);

        $diagnostic = $service->diagnose(
            $etudiant,
            isset($validated['annee_universitaire_id']) ? (int) $validated['annee_universitaire_id'] : null,
            isset($validated['target_classe_id']) ? (int) $validated['target_classe_id'] : null,
        );

        // Le nombre de versements aide a choisir quelle inscription garder ;
        // les montants restent derriere la porte financiere.
        if (! $request->user()->can('finances.etudiants.voir')) {
            foreach ($diagnostic['inscriptions'] ?? [] as $i => $profil) {
                $diagnostic['inscriptions'][$i]['payments']['total'] = null;
                $diagnostic['inscriptions'][$i]['payments']['valid_total'] = null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $diagnostic,
        ]);
    }

    public function repair(
        Request $request,
        ESBTPEtudiant $etudiant,
        StudentInscriptionRepairService $service
    ): JsonResponse {
        $this->authorizeRepair($request);

        $validated = $request->validate([
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'target_classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $result = $service->repair($etudiant, $validated, $request->user()?->id);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function authorizeDiagnostic(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user?->can('students.view') || $user?->can('inscriptions.view'),
            403,
            'Permission students.view ou inscriptions.view requise.'
        );
    }

    private function authorizeRepair(Request $request): void
    {
        $user = $request->user();
        $allowed = $user?->can('inscriptions.manage')
            || ($user?->can('inscriptions.edit') && $user?->can('inscriptions.delete'));

        abort_unless(
            $allowed,
            403,
            'Permission inscriptions.manage ou inscriptions.edit + inscriptions.delete requise.'
        );
    }
}
