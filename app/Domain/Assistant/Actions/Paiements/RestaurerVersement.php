<?php

namespace App\Domain\Assistant\Actions\Paiements;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Comptabilite\Paiements\Actions\RestaurerPaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\DB;

/**
 * Propose de remettre un versement supprimé, comme le bouton « Restaurer » de
 * la corbeille et la CLI : par RestaurerPaiement, qui remet aussi
 * l'inscription et l'étudiant supprimés en cascade, et qui refuse dans une
 * période close ou une caisse rapprochée.
 */
class RestaurerVersement extends ActionAgent
{
    public function __construct(private RestaurerPaiement $restauration)
    {
    }

    public function cle(): string
    {
        return 'restauration_versement';
    }

    public function libelle(): string
    {
        return 'Préparation de la restauration du versement…';
    }

    public function description(): string
    {
        return 'PROPOSE de remettre UN versement supprimé (corbeille), par son `numero_recu`. L’inscription et l’élève supprimés avec lui reviennent aussi. '
            . 'Refusé dans une période comptable close ou une caisse rapprochée. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'numero_recu' => ['type' => 'string', 'description' => 'Numéro du reçu du versement supprimé.'],
                'paiement_id' => ['type' => 'integer', 'description' => 'Identifiant du versement, si un outil l’a donné.'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Restaurer un versement supprimé';
        [$paiement, $manque] = Versements::trouver($args, true);
        if ($manque) {
            return new Proposition(titre: $titre, resume: '', manques: [$manque]);
        }
        if ($refus = $this->restauration->refus($paiement)) {
            return new Proposition(titre: $titre, resume: '', manques: [$refus]);
        }

        [$inscription, $etudiant] = $this->cascade($paiement);
        $avertissements = array_values(array_filter([
            $inscription?->trashed() ? 'L’inscription liée, supprimée elle aussi, sera restaurée.' : null,
            $etudiant?->trashed() ? 'L’élève '.trim($etudiant->nom.' '.$etudiant->prenoms).', supprimé lui aussi, sera restauré.' : null,
        ]));
        $nom = $etudiant ? trim($etudiant->nom.' '.$etudiant->prenoms) : '—';

        return new Proposition(
            titre: $titre,
            resume: sprintf('Le versement %s (%s FCFA) de %s revient dans les encaissements.', $paiement->numero_recu, number_format((float) $paiement->montant, 0, ',', ' '), $nom),
            tableau: [
                'colonnes' => ['Reçu', 'Étudiant', 'Matricule', 'Date', 'Montant', 'Supprimé le', 'Motif de suppression'],
                'lignes' => [[(string) $paiement->numero_recu, $nom, (string) ($etudiant->matricule ?? '—'), (string) $paiement->date_paiement?->format('d/m/Y'),
                    number_format((float) $paiement->montant, 0, ',', ' ').' FCFA', (string) $paiement->deleted_at?->format('d/m/Y'), (string) ($paiement->motif_suppression ?? '—')]],
            ],
            avertissements: $avertissements,
            donnees: ['paiement_id' => (int) $paiement->id],
            etat: Versements::etat($paiement) + ['cascade' => [(bool) $inscription?->trashed(), (bool) $etudiant?->trashed()]],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('trash.view') || ! $user->can('paiements.restore')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de restaurer un versement.');
        }

        $cascade = DB::transaction(function () use ($proposition, $user) {
            $paiement = ESBTPPaiement::onlyTrashed()->whereKey($proposition->donnees['paiement_id'])->lockForUpdate()->first();
            if (! $paiement) {
                throw new PropositionPerimee('Ce versement n’est plus dans la corbeille.');
            }
            [$inscription, $etudiant] = $this->cascade($paiement);
            $etat = Versements::etat($paiement) + ['cascade' => [(bool) $inscription?->trashed(), (bool) $etudiant?->trashed()]];
            if ($etat !== $proposition->etat) {
                throw new PropositionPerimee('Ce versement a changé depuis la proposition.');
            }
            try {
                return $this->restauration->execute($paiement, (int) $user->id);
            } catch (\DomainException $e) {
                throw new PropositionPerimee($e->getMessage());
            }
        });

        return [
            'message' => 'Versement restauré.'.($cascade['inscription'] ? ' Son inscription aussi.' : '').($cascade['etudiant'] ? ' L’élève aussi.' : ''),
            'lien' => route('esbtp.paiements.show', $proposition->donnees['paiement_id'], false),
            'model_type' => ESBTPPaiement::class,
            'model_id' => (int) $proposition->donnees['paiement_id'],
            'details' => $cascade,
        ];
    }

    /** @return array{0: ?ESBTPInscription, 1: ?ESBTPEtudiant} */
    private function cascade(ESBTPPaiement $paiement): array
    {
        $inscription = ESBTPInscription::withTrashed()->find($paiement->inscription_id);
        $etudiant = $inscription ? ESBTPEtudiant::withTrashed()->find($inscription->etudiant_id) : null;

        return [$inscription, $etudiant];
    }
}
