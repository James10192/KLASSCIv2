<?php

namespace App\Services\Pieces;

use App\Enums\EtatPieceDossier;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDeposee;
use App\Models\ESBTPPieceDossier;
use App\Services\DossierPiecesEtudiant;
use Illuminate\Support\Facades\DB;

/**
 * Le geste de guichet : cocher, décocher, écarter, décider.
 *
 * Toutes les écritures du suivi passent ici. Le contrôleur n'écrit jamais
 * directement : sinon la règle « une coche crée un dépôt ET une consommation »
 * finirait par être réécrite un peu différemment à chaque endroit, et un dossier
 * complet à l'inscription apparaîtrait incomplet sur la fiche étudiant.
 *
 * LE GESTE PRINCIPAL EST LA COCHE. Le téléversement est un surplus : il évite le
 * trajet jusqu'au classeur le jour où quelqu'un veut relire la pièce, mais
 * l'original déposé reste ce qui fait foi, et une école qui ne numérise rien
 * doit pouvoir suivre ses dossiers de bout en bout.
 */
class RemisePieceDossier
{
    public function __construct(private DossierPiecesEtudiant $dossier)
    {
    }

    /**
     * L'étudiant a remis la pièce.
     *
     * Deux écritures indissociables, donc une transaction : le dépôt — ce qui
     * entre dans le stock — et la consommation — ce que cette rentrée-ci en
     * prend. Écrire l'une sans l'autre laisserait soit un stock qui grossit sans
     * jamais servir, soit une consommation sans contrepartie.
     *
     * @param  array{quantite?:int|null, date_delivrance?:string|null, document_id?:int|null}  $options
     */
    public function cocher(
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece,
        ?int $userId,
        array $options = []
    ): ESBTPPieceDeposee {
        $requis = max(1, (int) $piece->exemplaires_par_inscription);
        $quantite = (int) ($options['quantite'] ?? $requis);
        $quantite = $quantite > 0 ? $quantite : $requis;

        $etat = $this->dossier->etatApresCoche();

        return DB::transaction(function () use ($inscription, $piece, $userId, $options, $quantite, $requis, $etat) {
            $depot = ESBTPPieceDeposee::create([
                'etudiant_id' => $inscription->etudiant_id,
                'piece_dossier_id' => $piece->id,
                'inscription_id' => $inscription->id,
                'quantite_deposee' => $quantite,
                'etat' => $etat->value,
                'date_depot' => now()->toDateString(),
                'date_delivrance' => $options['date_delivrance'] ?? null,
                'document_id' => $options['document_id'] ?? null,
                // Une coche qui vaut validation EST une décision : elle doit
                // dire qui l'a prise. Sans cela, une école qui active la
                // relecture plus tard ne saurait pas qui a validé l'existant.
                'decidee_par' => $etat->estSoldee() ? $userId : null,
                'decidee_at' => $etat->estSoldee() ? now() : null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->consommation($inscription, $piece)->fill([
                'quantite_consommee' => $requis,
                'non_applicable' => false,
                'motif' => null,
                'updated_by' => $userId,
            ])->save();

            return $depot;
        });
    }

    /**
     * Annuler la coche posée à CE guichet.
     *
     * Seuls les dépôts rattachés à cette inscription partent. Un extrait remis
     * en première année n'est pas effacé parce qu'on corrige une saisie de
     * troisième année : le papier est toujours dans le classeur.
     */
    public function decocher(ESBTPInscription $inscription, ESBTPPieceDossier $piece, ?int $userId): void
    {
        DB::transaction(function () use ($inscription, $piece, $userId) {
            ESBTPPieceDeposee::query()
                ->where('inscription_id', $inscription->id)
                ->where('piece_dossier_id', $piece->id)
                ->get()
                ->each(function (ESBTPPieceDeposee $depot) use ($userId) {
                    $depot->forceFill(['updated_by' => $userId])->saveQuietly();
                    $depot->delete();
                });

            ESBTPInscriptionPiece::query()
                ->where('inscription_id', $inscription->id)
                ->where('piece_dossier_id', $piece->id)
                ->delete();
        });
    }

    /**
     * Cette pièce ne concerne pas cet étudiant cette année.
     *
     * Le motif est obligatoire, et ce n'est pas une formalité : c'est ce qui
     * distingue une dispense décidée d'un dossier qu'on a renoncé à réclamer.
     * Six mois plus tard, seul le motif dira lequel des deux.
     */
    public function ecarter(
        ESBTPInscription $inscription,
        ESBTPPieceDossier $piece,
        string $motif,
        ?int $userId
    ): ESBTPInscriptionPiece {
        $ligne = $this->consommation($inscription, $piece);

        $ligne->fill([
            'non_applicable' => true,
            'motif' => $motif,
            'updated_by' => $userId,
        ])->save();

        return $ligne;
    }

    /** Remettre une pièce écartée dans ce qui est réclamé. */
    public function reintegrer(ESBTPInscription $inscription, ESBTPPieceDossier $piece, ?int $userId): void
    {
        ESBTPInscriptionPiece::query()
            ->where('inscription_id', $inscription->id)
            ->where('piece_dossier_id', $piece->id)
            ->get()
            ->each(fn (ESBTPInscriptionPiece $l) => $l->fill([
                'non_applicable' => false,
                'motif' => null,
                'quantite_consommee' => max(1, (int) $piece->exemplaires_par_inscription),
                'updated_by' => $userId,
            ])->save());
    }

    /**
     * Valider ou refuser un dépôt — le second geste, quand l'école le demande.
     *
     * Un refus sans motif est refusé par le modèle ET par la base ; ce contrôle
     * est ici pour que l'utilisateur voie un message plutôt qu'une page blanche.
     */
    public function decider(
        ESBTPPieceDeposee $depot,
        EtatPieceDossier $etat,
        ?string $motif,
        ?int $userId
    ): ESBTPPieceDeposee {
        $depot->fill([
            'etat' => $etat->value,
            'motif' => $etat->exigeUnMotif() ? $motif : $depot->motif,
            'decidee_par' => $userId,
            'decidee_at' => now(),
            'updated_by' => $userId,
        ])->save();

        return $depot;
    }

    /**
     * La ligne de consommation de cette inscription pour cette pièce, créée si
     * elle manque.
     *
     * `firstOrNew` et non `firstOrCreate` : l'appelant remplit ensuite, et une
     * création en deux temps éviterait d'écrire une ligne vide si le remplissage
     * échoue. L'unicité (inscription, pièce) tient de toute façon en base.
     */
    private function consommation(ESBTPInscription $inscription, ESBTPPieceDossier $piece): ESBTPInscriptionPiece
    {
        $ligne = ESBTPInscriptionPiece::query()
            ->where('inscription_id', $inscription->id)
            ->where('piece_dossier_id', $piece->id)
            ->first();

        return $ligne ?? new ESBTPInscriptionPiece([
            'etudiant_id' => $inscription->etudiant_id,
            'piece_dossier_id' => $piece->id,
            'inscription_id' => $inscription->id,
            'quantite_consommee' => max(1, (int) $piece->exemplaires_par_inscription),
        ]);
    }
}
