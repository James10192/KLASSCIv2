<?php

namespace App\Domain\Assistant\Actions\Inscriptions;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Inscriptions\ObstacleALaValidation;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\ESBTPInscriptionService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose de valider des inscriptions : une, plusieurs (matricules ou
 * identifiants), ou toutes celles d'une classe qui attendent.
 *
 * La règle vit dans ObstacleALaValidation, partagée avec la CLI : une
 * inscription sans versement VALIDÉ n'est jamais validée, un versement en
 * attente ne compte pas (rule inscriptions.md). Celles-là sont montrées, et
 * laissées telles quelles. L'écriture passe par
 * ESBTPInscriptionService::validerInscription(), comme l'écran et la CLI.
 */
class ValiderInscriptions extends ActionAgent
{
    private const MAX = 200;

    public function __construct(
        private DesignationDInscriptions $designation,
        private ObstacleALaValidation $obstacle,
        private ESBTPInscriptionService $service,
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
            . 'Une inscription sans versement validé, ou avec un versement encore en attente, n’est JAMAIS validée : elle est montrée et laissée en l’état. Rien n’est écrit avant « Valider ».';
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
        foreach ($inscriptions as $i) {
            $obstacle = $this->obstacle->pour($i);
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
                .'. Un versement doit d’abord être validé à la caisse.',
            ]);
        }

        sort($aValider);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d inscription(s) validée(s), le compte étudiant activé.', count($aValider)),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Classe', 'Effet'], 'lignes' => $lignes],
            avertissements: $laissees === [] ? [] : [count($laissees).' inscription(s) laissée(s) sans validation : '.implode(', ', $laissees).'.'],
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
            foreach ($ids as $id) {
                $resultat = $this->service->validerInscription($id, (int) $user->id);
                if (! ($resultat['success'] ?? false)) {
                    throw new PropositionPerimee('Inscription #'.$id.' : '.($resultat['message'] ?? 'refusée').'.');
                }
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
