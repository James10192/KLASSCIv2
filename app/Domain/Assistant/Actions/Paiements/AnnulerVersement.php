<?php

namespace App\Domain\Assistant\Actions\Paiements;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Exceptions\AvoirForbiddenException;
use App\Models\ESBTPPaiement;
use App\Services\AvoirService;
use Illuminate\Support\Facades\DB;

/**
 * Propose d'annuler un versement par un AVOIR : la contre-écriture comptable,
 * la même que le bouton « Avoir » de la fiche du paiement et que la CLI.
 * Annuler n'est pas supprimer : le versement reste, l'avoir le compense.
 *
 * Toute la règle est dans AvoirService (versement validé, pas un avoir, reste
 * disponible, ni période close ni caisse rapprochée). Ce que devient l'argent
 * — crédit gardé par l'école ou remboursement sorti de la caisse — ne se
 * devine pas, le motif non plus.
 */
class AnnulerVersement extends ActionAgent
{
    public const MOTIF_MIN = 10;

    public function __construct(private AvoirService $avoirs)
    {
    }

    public function cle(): string
    {
        return 'annulation_versement';
    }

    public function libelle(): string
    {
        return 'Préparation de l’annulation du versement…';
    }

    public function description(): string
    {
        return 'PROPOSE d’annuler UN versement validé par un avoir (contre-écriture, le versement reste visible). Désigne-le par son `numero_recu` (celui du reçu). '
            . '`avoir_kind` : « credit » (l’école garde l’argent au crédit de l’élève) ou « refund » (remboursé, sort de la caisse) : demande-le, ne le choisis jamais. '
            . 'Motif obligatoire, donné par la personne. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'numero_recu' => ['type' => 'string', 'description' => 'Numéro du reçu du versement.'],
                'paiement_id' => ['type' => 'integer', 'description' => 'Identifiant du versement, si un outil l’a donné.'],
                'avoir_kind' => ['type' => 'string', 'enum' => [AvoirService::KIND_CREDIT, AvoirService::KIND_REFUND]],
                'motif' => ['type' => 'string', 'description' => 'Pourquoi, en une phrase (au moins 10 caractères).'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Annuler un versement par un avoir';
        [$paiement, $manque] = Versements::trouver($args, false);
        $manques = $manque ? [$manque] : [];
        $kind = (string) ($args['avoir_kind'] ?? '');
        $motif = trim((string) ($args['motif'] ?? ''));
        if (! in_array($kind, [AvoirService::KIND_CREDIT, AvoirService::KIND_REFUND], true)) {
            $manques[] = 'Que devient l’argent : gardé au crédit de l’élève (credit), ou remboursé et sorti de la caisse (refund) ?';
        }
        if (mb_strlen($motif) < self::MOTIF_MIN) {
            $manques[] = 'Pourquoi annuler ce versement ? Le motif (au moins '.self::MOTIF_MIN.' caractères) figure sur l’avoir.';
        }
        if ($paiement && $manques === []) {
            $refus = $this->avoirs->availableAmount($paiement) <= 0
                ? 'Ce versement est déjà entièrement annulé par des avoirs.'
                : $this->avoirs->refus($paiement, $this->avoirs->availableAmount($paiement), $kind, $motif);
            if ($refus) {
                $manques[] = $refus;
            }
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $montant = $this->avoirs->availableAmount($paiement);
        $nom = trim(($paiement->etudiant->nom ?? '').' '.($paiement->etudiant->prenoms ?? ''));
        $fcfa = fn (float $v) => number_format($v, 0, ',', ' ').' FCFA';

        return new Proposition(
            titre: $titre,
            resume: sprintf('Le versement %s de %s (%s) sera annulé par un avoir de %s, %s.', $paiement->numero_recu, $nom, $fcfa((float) $paiement->montant), $fcfa($montant),
                $kind === AvoirService::KIND_REFUND ? 'remboursé (sortie de caisse)' : 'gardé au crédit de l’élève'),
            tableau: [
                'colonnes' => ['Reçu', 'Étudiant', 'Matricule', 'Date', 'Montant', 'Avoir', 'Motif'],
                'lignes' => [[(string) $paiement->numero_recu, $nom, (string) ($paiement->etudiant->matricule ?? '—'),
                    (string) $paiement->date_paiement?->format('d/m/Y'), $fcfa((float) $paiement->montant), $fcfa($montant), $motif]],
            ],
            avertissements: $kind === AvoirService::KIND_REFUND ? ['Un remboursement sort de la caisse : l’argent doit réellement être rendu.'] : [],
            donnees: ['paiement_id' => (int) $paiement->id, 'montant' => $montant, 'avoir_kind' => $kind, 'motif' => $motif],
            etat: Versements::etat($paiement),
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('paiements.avoir')) {
            throw new PropositionPerimee('Vous n’avez plus le droit d’émettre un avoir.');
        }
        $d = $proposition->donnees;

        $avoir = DB::transaction(function () use ($d, $proposition, $user) {
            $paiement = ESBTPPaiement::whereKey($d['paiement_id'])->lockForUpdate()->first();
            if (! $paiement || Versements::etat($paiement) !== $proposition->etat) {
                throw new PropositionPerimee('Ce versement a changé depuis la proposition.');
            }
            try {
                return $this->avoirs->issue($paiement, (float) $d['montant'], (string) $d['avoir_kind'], (string) $d['motif'], (int) $user->id);
            } catch (AvoirForbiddenException $e) {
                throw new PropositionPerimee($e->getMessage());
            }
        });

        return [
            'message' => "Avoir {$avoir->numero_avoir} enregistré : le versement est annulé.",
            'lien' => route('esbtp.paiements.avoir.pdf', $avoir->id, false),
            'model_type' => ESBTPPaiement::class,
            'model_id' => (int) $avoir->id,
            'details' => ['avoir_id' => (int) $avoir->id, 'numero' => $avoir->numero_avoir, 'montant' => (float) $avoir->montant],
        ];
    }
}
