<?php

namespace App\Http\Controllers;

use App\Domain\Inscriptions\StatutRedoublant;
use App\Models\ESBTPInscription;
use App\Services\Inscriptions\SelectionDInscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Confirmer ou corriger le statut redoublant : depuis la fiche d'une
 * inscription, ou pour une sélection de la liste.
 */
class ESBTPInscriptionRedoublantController extends Controller
{
    public function __construct(private readonly StatutRedoublant $statut)
    {
    }

    public function etablir(Request $request, ESBTPInscription $inscription): JsonResponse
    {
        $donnees = $request->validate([
            'valeur' => 'required|in:0,1',
            'motif' => 'nullable|string|max:500',
        ]);

        $inscription->loadMissing('anneeUniversitaire');
        $this->statut->etablir($inscription, $request->user(), (bool) (int) $donnees['valeur'], $donnees['motif'] ?? null);

        return response()->json([
            'success' => true,
            'statut' => $this->statut->pourAffichage($inscription->fresh(['redoublantConfirmePar'])),
        ]);
    }

    /**
     * Confirme tel quel le statut de chaque inscription sélectionnée qui
     * attendait une confirmation. Aucune valeur n'est changée : corriger se fait
     * fiche par fiche, avec un motif.
     */
    public function confirmerEnMasse(Request $request, SelectionDInscriptions $selection): JsonResponse
    {
        $ids = $selection->identifiants($request);
        $personne = $request->user();
        $confirmees = 0;

        DB::transaction(function () use ($ids, $personne, &$confirmees) {
            ESBTPInscription::query()->whereIn('id', $ids)->with('anneeUniversitaire')
                ->chunkById(200, function ($inscriptions) use ($personne, &$confirmees) {
                    foreach ($inscriptions as $inscription) {
                        if (! $this->statut->aConfirmer($inscription)) {
                            continue;
                        }
                        $this->statut->confirmer($inscription, $personne);
                        $confirmees++;
                    }
                });
        });

        return response()->json([
            'success' => true,
            'confirmees' => $confirmees,
            'ignorees' => count($ids) - $confirmees,
            'message' => $confirmees === 0
                ? 'Aucune inscription de la sélection n\'attendait de confirmation.'
                : $confirmees.' statut(s) redoublant confirmé(s).',
        ]);
    }
}
