<?php

namespace App\Http\Controllers;

use App\Enums\EtatPieceDossier;
use App\Http\Requests\PiecesDossier\CocherPieceRequest;
use App\Http\Requests\PiecesDossier\DecisionPieceRequest;
use App\Http\Requests\PiecesDossier\EcarterPieceRequest;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPieceDeposee;
use App\Models\ESBTPPieceDossier;
use App\Services\DossierPiecesEtudiant;
use App\Services\Pieces\RemisePieceDossier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le suivi des pièces d'un dossier, au guichet.
 *
 * Orchestration seulement : la lecture vit dans {@see DossierPiecesEtudiant},
 * les écritures dans {@see RemisePieceDossier}, la validation dans les
 * FormRequest. Ce contrôleur ne fait que traduire une requête en geste et
 * rendre l'état à jour.
 *
 * Tout répond en JSON : aucune de ces actions ne doit recharger la page. Une
 * secrétaire qui coche huit pièces à la suite ne doit pas voir son écran repartir
 * du haut huit fois.
 */
class ESBTPDossierPieceController extends Controller
{
    public function __construct(
        private readonly DossierPiecesEtudiant $dossier,
        private readonly RemisePieceDossier $remise
    ) {
    }

    /** L'état du dossier, rafraîchi. */
    public function index(Request $request, ESBTPInscription $inscription): JsonResponse
    {
        $this->authorize('view', $inscription);

        return response()->json($this->etatDuDossier($inscription));
    }

    public function cocher(
        CocherPieceRequest $request,
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece
    ): JsonResponse {
        $this->remise->cocher($inscription, $piece, $request->user()?->id, $request->options());

        return response()->json($this->etatDuDossier($inscription) + [
            'success' => true,
            'message' => $this->dossier->exigeUneRelecture()
                ? 'Pièce enregistrée comme remise. Elle attend une relecture.'
                : 'Pièce enregistrée.',
        ]);
    }

    public function decocher(
        Request $request,
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece
    ): JsonResponse {
        abort_unless($request->user()?->can('pieces_dossier.suivre'), 403);

        $this->remise->decocher($inscription, $piece, $request->user()?->id);

        return response()->json($this->etatDuDossier($inscription) + [
            'success' => true,
            'message' => 'Coche retirée pour cette inscription.',
        ]);
    }

    public function ecarter(
        EcarterPieceRequest $request,
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece
    ): JsonResponse {
        $this->remise->ecarter($inscription, $piece, (string) $request->input('motif'), $request->user()?->id);

        return response()->json($this->etatDuDossier($inscription) + [
            'success' => true,
            'message' => 'Pièce écartée pour cette année.',
        ]);
    }

    public function reintegrer(
        Request $request,
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece
    ): JsonResponse {
        abort_unless($request->user()?->can('pieces_dossier.suivre'), 403);

        $this->remise->reintegrer($inscription, $piece, $request->user()?->id);

        return response()->json($this->etatDuDossier($inscription) + [
            'success' => true,
            'message' => 'Pièce de nouveau réclamée.',
        ]);
    }

    /** Le second geste, quand l'école a demandé une relecture. */
    public function decider(DecisionPieceRequest $request, ESBTPPieceDeposee $depot): JsonResponse
    {
        $this->remise->decider(
            $depot,
            $request->etat(),
            $request->input('motif'),
            $request->user()?->id
        );

        $inscription = $depot->inscription;

        $charge = $inscription
            ? $this->etatDuDossier($inscription)
            : [];

        return response()->json($charge + [
            'success' => true,
            'message' => $request->etat() === EtatPieceDossier::REFUSEE
                ? 'Pièce refusée. L\'étudiant devra la redéposer.'
                : 'Pièce validée.',
        ]);
    }

    /** @return array<string, mixed> */
    private function etatDuDossier(ESBTPInscription $inscription): array
    {
        $lignes = $this->dossier->pourInscription($inscription);

        return [
            'lignes' => $lignes->map(fn (array $l) => $this->serialiser($l))->values(),
            'synthese' => $this->dossier->synthese($lignes),
            'relecture' => $this->dossier->exigeUneRelecture(),
        ];
    }

    /**
     * @param  array<string, mixed>  $ligne
     * @return array<string, mixed>
     */
    private function serialiser(array $ligne): array
    {
        $piece = $ligne['piece'];

        return [
            'piece_id' => $piece->id,
            'libelle' => $piece->libelle,
            'description' => $piece->description,
            'obligatoire' => (bool) $piece->is_obligatoire,
            'annuelle' => $ligne['annuelle'],
            'forme' => $piece->forme_attendue?->label(),
            'requis' => $ligne['requis'],
            'depose' => $ligne['depose'],
            'disponible' => $ligne['disponible'],
            'manquant' => $ligne['manquant'],
            'satisfaite' => $ligne['satisfaite'],
            'non_applicable' => $ligne['non_applicable'],
            'motif_non_applicable' => $ligne['motif_non_applicable'],
            'etat' => $ligne['etat']->value,
            'etat_label' => $ligne['etat']->label(),
            'ton' => $ligne['etat']->ton(),
            'perimes' => $ligne['depots_perimes']->count(),
            'depots' => $ligne['depots']->map(fn (ESBTPPieceDeposee $d) => [
                'id' => $d->id,
                'quantite' => $d->quantite_deposee,
                'etat' => $d->etat?->value,
                'etat_label' => $d->etat?->label(),
                'motif' => $d->motif,
                'date_depot' => $d->date_depot?->format('d/m/Y'),
                'date_delivrance' => $d->date_delivrance?->format('d/m/Y'),
                'document_id' => $d->document_id,
                'document_nom' => $d->document?->titre,
            ])->values(),
        ];
    }
}
