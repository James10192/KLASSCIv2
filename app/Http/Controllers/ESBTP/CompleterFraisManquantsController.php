<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * « Régénérer les frais » : remet les souscriptions d'accord avec le barème.
 *
 * Deux portées, une seule mécanique :
 *  - une liste d'inscriptions (sélection dans la liste, ou une fiche) ;
 *  - `scope=annee` : toutes les inscriptions actives d'une année, ce qu'il faut
 *    après avoir corrigé un tarif dans le paramétrage des frais — sans quoi il
 *    faudrait cocher les étudiants page par page.
 */
class CompleterFraisManquantsController extends Controller
{
    /**
     * Au-delà, on ne renvoie plus le détail : l'aperçu doit rester lisible et la
     * réponse transportable. Les totaux, eux, restent exacts.
     */
    private const MAX_LIGNES_RENDUES = 300;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inscriptions.edit');
    }

    public function preview(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse
    {
        [$ids, $anneeId] = $this->portee($request);
        $this->laisserLeTempsDeBalayer($anneeId);
        $resultat = $rattrapage->executer(false, $anneeId, $ids, true);

        return response()->json($this->charge($resultat));
    }

    public function apply(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse|RedirectResponse
    {
        [$ids, $anneeId] = $this->portee($request);
        $this->laisserLeTempsDeBalayer($anneeId);
        $resultat = $rattrapage->executer(true, $anneeId, $ids, true);

        $message = $this->message($resultat);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $message] + $this->charge($resultat));
        }

        return back()->with('success', $message);
    }

    /**
     * Une année entière, c'est plusieurs milliers d'inscriptions à comparer au
     * barème. Le temps d'exécution par défaut d'une requête web les coupe en
     * plein milieu — et une régénération interrompue laisse la caisse à
     * moitié réalignée.
     */
    private function laisserLeTempsDeBalayer(?int $anneeId): void
    {
        if ($anneeId !== null) {
            @set_time_limit(300);
        }
    }

    /**
     * Qui régénère-t-on : une sélection, ou toute une année.
     *
     * @return array{0: array<int, int>|null, 1: int|null}
     */
    private function portee(Request $request): array
    {
        $validated = $request->validate([
            'scope' => 'nullable|in:selection,annee',
            'annee_id' => 'nullable|integer|exists:esbtp_annee_universitaires,id',
            'inscription_ids' => 'required_unless:scope,annee|array|min:1',
            'inscription_ids.*' => 'integer|exists:esbtp_inscriptions,id',
        ]);

        if (($validated['scope'] ?? 'selection') === 'annee') {
            $anneeId = $validated['annee_id']
                ?? ESBTPAnneeUniversitaire::anneeCourante()?->id;

            // Sans année identifiable, la portée « toute l'année » viserait le
            // tenant entier, toutes promotions confondues. On refuse plutôt que
            // de deviner.
            abort_if(! $anneeId, 422, "Aucune année universitaire courante : précisez l'année à régénérer.");

            return [null, (int) $anneeId];
        }

        return [array_map('intval', $validated['inscription_ids']), null];
    }

    /**
     * @param  array<string, mixed>  $resultat
     * @return array<string, mixed>
     */
    private function charge(array $resultat): array
    {
        return [
            'success' => true,
            'total' => $resultat['total_ajouter'] + $resultat['total_retirer'] + $resultat['total_ajuster'],
            'total_ajouter' => $resultat['total_ajouter'],
            'total_retirer' => $resultat['total_retirer'],
            'total_ajuster' => $resultat['total_ajuster'],
            'inscriptions' => $resultat['inscriptions'],
            'lignes' => array_slice($resultat['lignes'], 0, self::MAX_LIGNES_RENDUES),
            'lignes_retrait' => array_slice($resultat['lignes_retrait'], 0, self::MAX_LIGNES_RENDUES),
            'lignes_ajustement' => array_slice($resultat['lignes_ajustement'], 0, self::MAX_LIGNES_RENDUES),
            'tronque' => max(
                count($resultat['lignes']),
                count($resultat['lignes_retrait']),
                count($resultat['lignes_ajustement']),
            ) > self::MAX_LIGNES_RENDUES,
        ];
    }

    private function message(array $resultat): string
    {
        $ajoutes = (int) $resultat['total_ajouter'];
        $retires = (int) $resultat['total_retirer'];
        $ajustes = (int) $resultat['total_ajuster'];

        if ($ajoutes === 0 && $retires === 0 && $ajustes === 0) {
            return 'Aucun écart : les frais sont à jour.';
        }

        $parts = [];
        if ($ajoutes > 0) {
            $parts[] = sprintf('%d ajouté(s)', $ajoutes);
        }
        if ($retires > 0) {
            $parts[] = sprintf('%d retiré(s)', $retires);
        }
        if ($ajustes > 0) {
            $parts[] = sprintf('%d montant(s) mis à jour', $ajustes);
        }

        return sprintf('%s sur %d inscription(s).', implode(', ', $parts), $resultat['inscriptions']);
    }
}
