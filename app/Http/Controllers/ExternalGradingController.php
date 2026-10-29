<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAnneeUniversitaire;

class ExternalGradingController extends Controller
{
    /**
     * Affiche le formulaire de saisie des notes via lien externe
     */
    public function show(Request $request, $token)
    {
        // Vérifier que le token existe et n'est pas expiré
        $evaluation = ESBTPEvaluation::where('token_saisie_externe', $token)
            ->where('token_expire_at', '>', now())
            ->first();

        if (!$evaluation) {
            return view('external-grading.expired')->with('error', 'Ce lien a expiré ou n\'est pas valide.');
        }

        // Récupérer les étudiants de la classe pour l'année courante de l'évaluation
        $anneeId = $evaluation->annee_universitaire_id
            ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($evaluation, $anneeId) {
            $query->where('classe_id', $evaluation->classe_id)
                  ->where('status', 'active');
            if ($anneeId) {
                $query->where('annee_universitaire_id', $anneeId);
            }
        })->orderBy('nom')->orderBy('prenoms')->get();

        // Récupérer les notes existantes
        $notes = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->pluck('note', 'etudiant_id');

        return view('external-grading.form', compact('evaluation', 'etudiants', 'notes'));
    }

    /**
     * Sauvegarde les notes saisies via le lien externe
     */
    public function store(Request $request, $token)
    {
        // Vérifier que le token existe et n'est pas expiré
        $evaluation = ESBTPEvaluation::where('token_saisie_externe', $token)
            ->where('token_expire_at', '>', now())
            ->first();

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Ce lien a expiré ou n\'est pas valide.');
        }

        $request->validate([
            'notes' => 'required|array',
            'notes.*' => 'nullable|numeric|min:0|max:' . $evaluation->bareme,
        ]);

        try {
            // Sauvegarder les notes
            foreach ($request->notes as $etudiantId => $note) {
                if ($note !== null && $note !== '') {
                    ESBTPNote::updateOrCreate(
                        [
                            'evaluation_id' => $evaluation->id,
                            'etudiant_id' => $etudiantId
                        ],
                        [
                            'note' => $note,
                            'created_by' => null, // Saisie externe
                            'updated_by' => null,
                            'commentaire' => 'Saisie externe via lien'
                        ]
                    );
                }
            }

            return redirect()->back()->with('success', 'Les notes ont été enregistrées avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'enregistrement des notes.');
        }
    }
}
