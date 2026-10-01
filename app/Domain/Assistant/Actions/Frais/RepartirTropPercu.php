<?php

namespace App\Domain\Assistant\Actions\Frais;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Inscriptions\DesignationDInscriptions;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Exceptions\AllocationIncoherenteException;
use App\Http\Controllers\Concerns\VerrouilleLesPeriodesComptables;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Services\Frais\RepartitionTropPercu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Propose de répartir les versements d'UN élève sur les frais qu'ils couvrent
 * vraiment : un trop-versé sur un frais solde les autres au lieu de rester
 * invisible. On ne crée aucun paiement, on dit où l'argent déjà encaissé est allé.
 *
 * Même calcul que la CLI : RepartitionTropPercu, simulé pour la proposition,
 * appliqué à la validation. Gardes en plus de la CLI, parce que c'est une
 * personne de l'école et non le support qui le demande :
 *  - UNE inscription à la fois (la reprise d'une année entière reste au support) ;
 *  - le droit de corriger une ventilation (`paiements.reventiler`), comme l'écran
 *    qui réécrit l'imputation d'un versement, et un motif ;
 *  - jamais sur un versement d'une période close ou d'une caisse rapprochée,
 *    ni sur un AVOIR qui l'annule : RepartitionTropPercu réaligne les avoirs des
 *    versements répartis (remettreLesAvoirsEnPhase), donc ils bougent aussi.
 *    RepartitionTropPercu ne vérifie aucun verrou lui-même : c'est cette action
 *    qui le fait, à la proposition puis sous verrou à la validation.
 */
class RepartirTropPercu extends ActionAgent
{
    use VerrouilleLesPeriodesComptables;

    public const MOTIF_MIN = 10;

    public function __construct(
        private DesignationDInscriptions $designation,
        private RepartitionTropPercu $repartition,
    ) {
    }

    public function cle(): string
    {
        return 'repartition_trop_percu';
    }

    public function libelle(): string
    {
        return 'Préparation de la répartition des versements…';
    }

    public function description(): string
    {
        return 'PROPOSE de répartir les versements déjà encaissés d’UN élève sur les frais qu’ils couvrent (un trop-versé sur un frais solde les autres). Aucun paiement n’est créé. '
            . 'Désigne l’élève par `matricule` (année en cours) ou `inscription_id`. `reset` : repartir de zéro (après un changement d’ordre des frais). Motif obligatoire. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matricule' => ['type' => 'string'],
                'inscription_id' => ['type' => 'integer'],
                'reset' => ['type' => 'boolean', 'description' => 'Recalculer aussi les versements déjà répartis.'],
                'motif' => ['type' => 'string', 'description' => 'Pourquoi, en une phrase (au moins 10 caractères).'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Répartir les versements sur les frais';
        [$inscription, $manque] = $this->designation->inscriptionCourante($args['matricule'] ?? null, isset($args['inscription_id']) ? (int) $args['inscription_id'] : null);
        $manques = $manque ? [$manque] : [];
        $motif = trim((string) ($args['motif'] ?? ''));
        if (mb_strlen($motif) < self::MOTIF_MIN) {
            $manques[] = 'Pourquoi cette répartition ? Le motif (au moins '.self::MOTIF_MIN.' caractères) reste au journal.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $reset = (bool) ($args['reset'] ?? false);
        try {
            $plan = $this->plan((int) $inscription->id, $reset);
        } catch (AllocationIncoherenteException $e) {
            return new Proposition(titre: $titre, resume: '', manques: [$e->getMessage()]);
        }
        if ($plan['allocations'] === 0 && $plan['effacees'] === 0) {
            return new Proposition(titre: $titre, resume: '', manques: ['Rien à répartir : les versements de cet élève sont déjà imputés là où ils doivent l’être.']);
        }
        if ($verrou = $this->verrou($this->versementsTouches((int) $inscription->id, $plan, $reset))) {
            return new Proposition(titre: $titre, resume: '', manques: [$verrou]);
        }

        $categories = ESBTPFraisCategory::pluck('name', 'id');
        $fcfa = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
        $lignes = [];
        foreach ($plan['lignes'] as $ligne) {
            $lignes[] = [(string) $ligne['numero_recu'], $fcfa($ligne['montant']),
                implode(' + ', array_map(fn ($a) => ($categories[$a['frais_category_id']] ?? '?').' '.$fcfa($a['montant']), $ligne['details']))];
        }
        $inscription->loadMissing('etudiant:id,nom,prenoms,matricule');
        $nom = trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? ''));

        return new Proposition(
            titre: $titre,
            resume: sprintf('%s : %d versement(s) réparti(s) sur %d imputation(s)%s. Aucun paiement créé, aucun montant changé.',
                $nom, $plan['paiements'], $plan['allocations'], $reset ? ', '.$plan['effacees'].' ancienne(s) imputation(s) recalculée(s)' : ''),
            tableau: ['colonnes' => ['Reçu', 'Montant', 'Imputé sur'], 'lignes' => $lignes],
            avertissements: $reset ? ['Les imputations déjà faites sur ses versements sont recalculées selon l’ordre actuel des frais.'] : [],
            donnees: ['inscription_id' => (int) $inscription->id, 'reset' => $reset, 'motif' => $motif],
            etat: $this->etat((int) $inscription->id, $plan),
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('paiements.reventiler')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de corriger l’imputation d’un versement.');
        }
        $d = $proposition->donnees;

        $resultat = DB::transaction(function () use ($d, $proposition) {
            ESBTPPaiement::where('inscription_id', $d['inscription_id'])->lockForUpdate()->get(['id']);
            try {
                $plan = $this->plan((int) $d['inscription_id'], (bool) $d['reset']);
            } catch (AllocationIncoherenteException $e) {
                throw new PropositionPerimee($e->getMessage());
            }
            if ($this->etat((int) $d['inscription_id'], $plan) !== $proposition->etat) {
                throw new PropositionPerimee('Les versements de cet élève ont changé depuis la proposition.');
            }
            if ($verrou = $this->verrou($this->versementsTouches((int) $d['inscription_id'], $plan, (bool) $d['reset']))) {
                throw new PropositionPerimee($verrou);
            }

            return $this->repartition->executer(true, (int) $d['inscription_id'], null, (bool) $d['reset']);
        });

        Log::info('assistant.repartition_trop_percu', [
            'inscription_id' => $d['inscription_id'], 'reset' => $d['reset'], 'motif' => $d['motif'],
            'allocations' => $resultat['allocations'], 'user_id' => $user->id,
        ]);

        return [
            'message' => sprintf('%d imputation(s) écrite(s) sur %d versement(s).', $resultat['allocations'], $resultat['paiements']),
            'lien' => route('esbtp.inscriptions.show', $d['inscription_id'], false),
            'model_type' => ESBTPInscription::class,
            'model_id' => (int) $d['inscription_id'],
            'details' => ['allocations' => $resultat['allocations'], 'effacees' => $resultat['effacees']],
        ];
    }

    private function plan(int $inscriptionId, bool $reset): array
    {
        return $this->repartition->executer(false, $inscriptionId, null, $reset);
    }

    /** Les versements dont l'imputation va bouger, et les avoirs qui les annulent (réalignés avec eux). */
    private function versementsTouches(int $inscriptionId, array $plan, bool $reset): array
    {
        $ids = array_column($plan['lignes'], 'paiement_id');
        if ($reset) {
            $ids = array_merge($ids, ESBTPPaiementAllocation::whereIn('paiement_id', ESBTPPaiement::where('inscription_id', $inscriptionId)->select('id'))
                ->pluck('paiement_id')->all());
        }
        $avoirs = $ids === [] ? [] : ESBTPPaiement::whereIn('parent_paiement_id', $ids)->pluck('id')->all();

        return array_values(array_unique(array_map('intval', array_merge($ids, $avoirs))));
    }

    private function verrou(array $paiementIds): ?string
    {
        foreach (ESBTPPaiement::whereIn('id', $paiementIds)->get() as $paiement) {
            $verrou = $this->assertPeriodNotLocked($paiement) ?? $this->assertReconciliationNotLocked($paiement);
            if ($verrou) {
                return 'Reçu '.$paiement->numero_recu.' : '.$verrou['message'];
            }
        }

        return null;
    }

    private function etat(int $inscriptionId, array $plan): array
    {
        return [
            'plan' => array_map(fn ($l) => [$l['paiement_id'], $l['details']], $plan['lignes']),
            'effacees' => $plan['effacees'],
            'imputations' => ESBTPPaiementAllocation::whereIn('paiement_id', ESBTPPaiement::where('inscription_id', $inscriptionId)->select('id'))
                ->orderBy('id')->get(['paiement_id', 'frais_category_id', 'montant'])
                ->map(fn ($a) => [(int) $a->paiement_id, (int) $a->frais_category_id, (float) $a->montant])->all(),
        ];
    }
}
