<?php

namespace App\Domain\Assistant\Actions\Inscriptions;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Inscriptions\ObstacleALaValidation;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\ESBTPInscriptionService;
use App\Services\InscriptionWorkflowService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose de valider des inscriptions : une, plusieurs (matricules ou
 * identifiants), ou toutes celles d'une classe qui attendent.
 *
 * La règle vit dans ObstacleALaValidation, partagée avec la CLI et alignée sur
 * l'écran : inscription annulée, autre inscription active la même année,
 * versement en attente ou absent, classe pleine. Celles-là sont montrées, et
 * laissées telles quelles. Les places se comptent en lot : dans une classe à
 * trois places libres, seules les trois premières sont proposées.
 *
 * L'écriture passe par la validation groupée de l'écran
 * (ESBTPInscriptionService::processBulkValidation, sans forçage) : même
 * historique, même notification, mêmes rappels désactivés. Si l'écran en
 * laisse une seule de côté, rien n'est écrit.
 */
class ValiderInscriptions extends ActionAgent
{
    private const MAX = 200;

    public function __construct(
        private DesignationDInscriptions $designation,
        private ObstacleALaValidation $obstacle,
        private ESBTPInscriptionService $service,
        private InscriptionWorkflowService $workflow,
    ) {
    }

    public function cle(): string
    {
        return 'validation_inscriptions';
    }

    public function libelle(): string
    {
        return 'Préparation de la validation des inscriptions…';
    }

    public function description(): string
    {
        return 'PROPOSE de valider des inscriptions de l’année en cours : par `matricules`, par `inscriptions` (identifiants), ou par `classe` (code : toutes celles qui attendent). '
            . 'Mêmes règles que la validation groupée de l’écran : JAMAIS une inscription annulée, sans versement validé ou au versement en attente, ni un élève déjà inscrit ailleurs cette année, ni au-delà des places de la classe. '
            . 'Celles-là sont montrées et laissées en l’état. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matricules' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Matricules des élèves.'],
                'inscriptions' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Identifiants d’inscription.'],
                'classe' => ['type' => 'string', 'description' => 'Code de classe : toutes ses inscriptions de l’année qui attendent.'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Valider des inscriptions';
        [$inscriptions, $manques] = $this->inscriptions($args);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $aValider = [];
        $laissees = [];
        $lignes = [];
        $classes = [];
        foreach ($inscriptions as $i) {
            $obstacle = $this->obstacle->horsPlaces($i);
            if ($obstacle === null && ! $this->prendUnePlace($i, $classes)) {
                $obstacle = ObstacleALaValidation::CLASSE_PLEINE;
            }
            $nom = trim(($i->etudiant->nom ?? '').' '.($i->etudiant->prenoms ?? ''));
            $lignes[] = [$nom, (string) ($i->etudiant->matricule ?? '—'), (string) ($i->classe->name ?? '—'),
                $obstacle ? 'Laissée : '.ObstacleALaValidation::libelle($obstacle) : 'En attente → Validée'];
            if ($obstacle === null) {
                $aValider[] = (int) $i->id;
            } elseif ($obstacle !== ObstacleALaValidation::DEJA_VALIDEE) {
                $laissees[] = $nom.' ('.ObstacleALaValidation::libelle($obstacle).')';
            }
        }

        if ($aValider === []) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Aucune de ces inscriptions ne peut être validée'.($laissees ? ' : '.implode(', ', $laissees) : ' (déjà validées)')
                .'.',
            ]);
        }

        sort($aValider);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d inscription(s) validée(s), le compte étudiant activé.', count($aValider)),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Classe', 'Effet'], 'lignes' => $lignes],
            avertissements: array_merge(
                $laissees === [] ? [] : [count($laissees).' inscription(s) laissée(s) sans validation : '.implode(', ', $laissees).'.'],
                $this->depassements($classes),
            ),
            donnees: ['ids' => $aValider],
            etat: ['inscriptions' => $this->etat($aValider)],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.validate')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de valider une inscription.');
        }
        $ids = $proposition->donnees['ids'];

        DB::transaction(function () use ($ids, $proposition, $user) {
            DesignationDInscriptions::verrouiller('esbtp_inscriptions', $ids);
            if ($this->etat($ids) !== $proposition->etat['inscriptions']) {
                throw new PropositionPerimee('Ces inscriptions ont changé depuis la proposition.');
            }
            $stats = $this->service->processBulkValidation($ids, false, $this->workflow, (int) $user->id);
            if (($stats['validees_direct'] ?? 0) !== count($ids)) {
                $raisons = array_merge(
                    array_map(fn ($r) => ($r['etudiant'] ?? '#'.$r['id']).' : '.$r['raison'], $stats['ignorees'] ?? []),
                    array_map(fn ($r) => '#'.$r['id'].' : '.$r['erreur'], $stats['erreurs'] ?? []),
                );
                throw new PropositionPerimee('Validation refusée par l’écran — '.($raisons ? implode(' ; ', $raisons) : 'inscription déjà traitée').'.');
            }
        });

        return [
            'message' => count($ids).' inscription(s) validée(s).',
            'lien' => route('esbtp.inscriptions.index', [], false),
            'model_type' => ESBTPInscription::class,
            'model_id' => $ids[0] ?? null,
            'details' => ['ids' => $ids],
        ];
    }

    /**
     * Les places se comptent en lot, comme l'écran qui valide une à une : sans
     * dérogation, une classe pleine refuse la suite ; avec la permission
     * inscriptions.override_capacity, elle l'accepte et le dépassement est annoncé.
     *
     * @param array<int, array> $classes état par classe, tenu d'une inscription à l'autre
     */
    private function prendUnePlace(ESBTPInscription $i, array &$classes): bool
    {
        $etat = $classes[$i->classe_id] ??= ($this->obstacle->capacite($i) ?? ['places' => 0, 'inscrits' => 0, 'derogation' => false]) + ['acceptees' => 0];
        $libre = $etat['places'] === null || $etat['inscrits'] + $etat['acceptees'] < $etat['places'];
        if (! $libre && ! $etat['derogation']) {
            return false;
        }
        $classes[$i->classe_id]['acceptees']++;

        return true;
    }

    /** @return string[] */
    private function depassements(array $classes): array
    {
        $avertissements = [];
        foreach ($classes as $etat) {
            $total = $etat['inscrits'] + $etat['acceptees'];
            if ($etat['places'] !== null && $etat['acceptees'] > 0 && $total > $etat['places']) {
                $avertissements[] = sprintf('%s passera à %d inscrits pour %d places (dérogation).', $etat['classe']->name, $total, $etat['places']);
            }
        }

        return $avertissements;
    }

    /** @return array{0: Collection, 1: string[]} */
    private function inscriptions(array $args): array
    {
        $annee = $this->designation->anneeCouranteId();
        $avec = ['etudiant:id,nom,prenoms,matricule', 'classe:id,name'];

        if (! empty($args['classe'])) {
            $classe = ESBTPClasse::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim((string) $args['classe']))])->first();
            if (! $classe) {
                return [collect(), ["Classe {$args['classe']} introuvable : vérifie son code avec search_classes."]];
            }
            $liste = ESBTPInscription::with($avec)->where('classe_id', $classe->id)
                ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee))
                ->whereNotIn('status', ESBTPInscription::STATUTS_ANNULES)
                ->where(fn ($q) => $q->where('status', '!=', 'active')->orWhere('workflow_step', '!=', 'etudiant_cree'))
                ->orderBy('id')->get();

            return [$liste, $liste->isEmpty() ? ["Aucune inscription en attente dans {$classe->name} cette année."] : ($liste->count() > self::MAX ? ['Plus de '.self::MAX.' inscriptions : précise la sélection.'] : [])];
        }

        $manques = [];
        $liste = new EloquentCollection();
        foreach ((array) ($args['inscriptions'] ?? []) as $id) {
            [$i, $manque] = $this->designation->inscriptionCourante(null, (int) $id);
            $manque ? $manques[] = $manque : $liste->push($i);
        }
        foreach ((array) ($args['matricules'] ?? []) as $matricule) {
            [$i, $manque] = $this->designation->inscriptionCourante((string) $matricule, null);
            $manque ? $manques[] = $manque : $liste->push($i);
        }
        if ($liste->isEmpty() && $manques === []) {
            $manques[] = 'Quelles inscriptions ? Donne des matricules, ou le code d’une classe.';
        }
        if ($liste->count() > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' inscriptions : précise la sélection.';
        }

        return [$liste->unique('id')->values()->load($avec), $manques];
    }

    /** @return array<int, array> */
    private function etat(array $ids): array
    {
        return ESBTPInscription::whereIn('id', $ids)->orderBy('id')->get(['id', 'status', 'workflow_step', 'classe_id'])
            ->map(fn ($i) => [
                'id' => (int) $i->id, 'status' => (string) $i->status, 'etape' => (string) $i->workflow_step,
                'versement_valide' => $i->paiements()->where('status', 'validé')->exists(),
            ])->all();
    }
}
