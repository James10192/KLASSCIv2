<?php

namespace App\Domain\Assistant\Actions\Frais;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Inscriptions\DesignationDInscriptions;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Exceptions\InKindDepositForbiddenException;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\InKindDepositService;
use Illuminate\Support\Facades\DB;

/**
 * Propose d'annuler un dépôt en nature coché par erreur (« la rame de papier
 * n'a jamais été déposée »). Le frais redevient dû.
 *
 * Même règle que l'écran de l'inscription et la CLI, tenue par
 * InKindDepositService::unmarkDeposited() : refusé dès qu'un versement validé
 * existe sur ce frais.
 */
class AnnulerDepotNature extends ActionAgent
{
    public function __construct(
        private DesignationDInscriptions $designation,
        private InKindDepositService $depots,
    ) {
    }

    public function cle(): string
    {
        return 'annulation_depot_nature';
    }

    public function libelle(): string
    {
        return 'Préparation de l’annulation du dépôt en nature…';
    }

    public function description(): string
    {
        return 'PROPOSE d’annuler un dépôt en nature marqué par erreur sur UNE inscription : le frais redevient dû. Désigne l’élève par `matricule` (inscription de l’année en cours) ou `inscription_id`, '
            . 'et le frais par `categorie` (nom ou identifiant) s’il y en a plusieurs. Refusé si un versement validé existe sur ce frais. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matricule' => ['type' => 'string'],
                'inscription_id' => ['type' => 'integer', 'description' => 'Pour une inscription d’une autre année.'],
                'categorie' => ['type' => 'string', 'description' => 'Nom ou identifiant du frais.'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Annuler un dépôt en nature';
        [$inscription, $manque] = $this->designation->inscriptionCourante($args['matricule'] ?? null, isset($args['inscription_id']) ? (int) $args['inscription_id'] : null);
        if ($manque) {
            return new Proposition(titre: $titre, resume: '', manques: [$manque]);
        }

        $souscriptions = $this->souscriptions((int) $inscription->id, $args['categorie'] ?? null);
        if ($souscriptions->isEmpty()) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Aucun dépôt en nature'.(! empty($args['categorie']) ? ' pour « '.$args['categorie'].' »' : '').' sur cette inscription.',
            ]);
        }

        $annulables = [];
        $bloques = [];
        $lignes = [];
        foreach ($souscriptions as $s) {
            $bloque = $this->depots->hasValidatedPayment((int) $s->inscription_id, (int) $s->frais_category_id);
            $bloque ? $bloques[] = (string) $s->fraisCategory?->name : $annulables[] = (int) $s->id;
            $lignes[] = [(string) $s->fraisCategory?->name, number_format((float) $s->amount, 0, ',', ' ').' FCFA',
                $bloque ? 'Refusé : un versement validé existe sur ce frais' : 'Déposé → À payer'];
        }
        if ($annulables === []) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Impossible : un versement validé existe déjà sur '.implode(', ', $bloques).'. La situation comptable est établie.',
            ]);
        }

        $inscription->loadMissing('etudiant:id,nom,prenoms,matricule');
        $nom = trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? ''));
        sort($annulables);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%s : %d dépôt(s) en nature annulé(s), le(s) frais redevient(nent) dû(s).', $nom, count($annulables)),
            tableau: ['colonnes' => ['Frais', 'Montant', 'Effet'], 'lignes' => $lignes],
            avertissements: $bloques === [] ? [] : ['Laissé(s) en l’état : '.implode(', ', $bloques).' (versement validé).'],
            donnees: ['souscriptions' => $annulables],
            etat: ['souscriptions' => $this->etat($annulables)],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.in_kind.mark')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de modifier un dépôt en nature.');
        }
        $ids = $proposition->donnees['souscriptions'];

        DB::transaction(function () use ($ids, $proposition, $user) {
            DesignationDInscriptions::verrouiller('esbtp_frais_subscriptions', $ids);
            if ($this->etat($ids) !== $proposition->etat['souscriptions']) {
                throw new PropositionPerimee('Ce dépôt a changé depuis la proposition.');
            }
            foreach (ESBTPFraisSubscription::whereIn('id', $ids)->get() as $souscription) {
                try {
                    $this->depots->unmarkDeposited($souscription, (int) $user->id);
                } catch (InKindDepositForbiddenException $e) {
                    throw new PropositionPerimee($e->getMessage());
                }
            }
        });

        $inscriptionId = (int) ESBTPFraisSubscription::whereKey($ids[0])->value('inscription_id');

        return [
            'message' => count($ids).' dépôt(s) en nature annulé(s) : le frais redevient dû.',
            'lien' => route('esbtp.inscriptions.show', $inscriptionId, false),
            'model_type' => ESBTPInscription::class,
            'model_id' => $inscriptionId,
            'details' => ['souscriptions' => $ids],
        ];
    }

    private function souscriptions(int $inscriptionId, $categorie)
    {
        $categorie = is_string($categorie) ? trim($categorie) : $categorie;

        return ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscriptionId)
            ->where('satisfied_in_kind', true)
            ->when($categorie !== null && $categorie !== '', fn ($q) => is_numeric($categorie)
                ? $q->where('frais_category_id', (int) $categorie)
                : $q->whereHas('fraisCategory', fn ($c) => $c->where(fn ($n) => $n->where('name', 'like', '%'.$categorie.'%')->orWhere('code', $categorie))))
            ->with('fraisCategory:id,name')
            ->orderBy('id')
            ->get();
    }

    private function etat(array $ids): array
    {
        return ESBTPFraisSubscription::whereIn('id', $ids)->orderBy('id')->get(['id', 'inscription_id', 'frais_category_id', 'satisfied_in_kind'])
            ->map(fn ($s) => [
                'id' => (int) $s->id, 'depose' => (bool) $s->satisfied_in_kind,
                'verse' => $this->depots->hasValidatedPayment((int) $s->inscription_id, (int) $s->frais_category_id),
            ])->all();
    }
}
