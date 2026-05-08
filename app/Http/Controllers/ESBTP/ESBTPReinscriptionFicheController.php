<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Students\Actions\UpdateStudentReinscriptionFicheAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStudentReinscriptionFicheRequest;
use App\Models\ESBTPEtudiant;

class ESBTPReinscriptionFicheController extends Controller
{
    public function update(
        UpdateStudentReinscriptionFicheRequest $request,
        ESBTPEtudiant $etudiant,
        UpdateStudentReinscriptionFicheAction $action
    ) {
        try {
            // accessibility[*] n'est pas dans le FormRequest (validation déléguée à l'action
            // dédiée AttachAccessibilityProfile, cohérent avec ESBTPInscriptionController).
            $payload = $request->validated() + $request->only('accessibility');

            $result = $action->execute($etudiant, $payload, $request->user());
            $updated = $result['etudiant'];
            $warning = $result['accessibility_warning'];

            return response()->json([
                'success'     => true,
                'message'     => $warning ?? 'Fiche étudiant mise à jour.',
                'has_warning' => $warning !== null,
                'etudiant'    => [
                    'id'                        => $updated->id,
                    'nom'                       => $updated->nom,
                    'prenoms'                   => $updated->prenoms,
                    'telephone'                 => $updated->telephone,
                    'email_personnel'           => $updated->email_personnel,
                    'ville'                     => $updated->ville,
                    'commune'                   => $updated->commune,
                    'urgence_contact_telephone' => $updated->urgence_contact_telephone,
                    'parents_count'             => $updated->parents->count(),
                    'has_accessibility_profile' => $updated->accessibilityProfile !== null,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('quick-update-fiche failed', [
                'etudiant_id' => $etudiant->id,
                'user_id'     => $request->user()?->id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour de la fiche.',
            ], 500);
        }
    }
}
