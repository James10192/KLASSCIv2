<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPInscription;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class EcheancierResolverService
{
    public function resolveForConfiguration(?ESBTPFraisConfiguration $configuration, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        if (!$configuration) {
            return null;
        }

        return $this->resolveByScope(
            ESBTPEcheancierRule::SCOPE_CONFIGURATION,
            (int) $configuration->id,
            $affectationStatus
        );
    }

    public function resolveForOptionAssignment(?ESBTPOptionAssignment $assignment, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        if (!$assignment) {
            return null;
        }

        return $this->resolveByScope(
            ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
            (int) $assignment->id,
            $affectationStatus
        );
    }

    /**
     * Le calcul d'echeancier interroge ce service pour chaque inscription et
     * chaque categorie de frais : sans memoire, une page de relances sur 3000
     * eleves relancait des milliers de fois la meme poignee de requetes.
     * La regle ne depend que de (portee, statut, jour) : on la retient par
     * instance. La duree de vie reste celle de l'appelant (une page, une
     * tache), et toute ecriture par modele sur une regle ou une ligne vide la
     * memoire. Une ecriture par requete brute ne declenche pas d'evenement :
     * elle doit appeler oublierRegles() elle-meme, comme le fait la bascule
     * groupee `bulkStatus()` de ESBTPEcheancierController.
     *
     * @var array<string, ESBTPEcheancierRule|null>
     */
    private array $regles = [];

    /** @var array<string, array<int, true>> scope_ids portant au moins une regle active, par type de portee */
    private array $portees = [];

    private int $generation = -1;

    /** Bumpee par les evenements des modeles de regle : invalide toutes les memoires. */
    private static int $generationCourante = 0;

    /** Seul un resultat positif est retenu : une migration en cours de route reste vue. */
    private bool $tablesPresentes = false;

    public static function oublierRegles(): void
    {
        self::$generationCourante++;
    }

    /** Pour les autres memoires qui dependent des regles (mode configure / repli). */
    public static function generationDesRegles(): int
    {
        return self::$generationCourante;
    }

    public function resolveByScope(string $scopeType, int $scopeId, ?string $affectationStatus): ?ESBTPEcheancierRule
    {
        if (!$this->tablesPresentes()) {
            return null;
        }

        if ($this->generation !== self::$generationCourante) {
            $this->regles = [];
            $this->portees = [];
            $this->generation = self::$generationCourante;
        }

        // Portee sans aucune regle active : la requete complete rendrait null.
        if (!isset($this->porteesAvecRegle($scopeType)[$scopeId])) {
            return null;
        }

        $normalized = ESBTPEcheancierRule::normalizeStatus($affectationStatus);
        $today = now()->toDateString();
        $cle = $scopeType . '|' . $scopeId . '|' . $normalized . '|' . $today;

        if (array_key_exists($cle, $this->regles)) {
            return $this->regles[$cle];
        }

        return $this->regles[$cle] = ESBTPEcheancierRule::query()
            ->forScope($scopeType, $scopeId)
            ->active()
            ->validAt($today)
            ->whereIn('affectation_status', [$normalized, ESBTPEcheancierRule::STATUS_ALL])
            ->orderByRaw(
                "CASE WHEN affectation_status = ? THEN 0 WHEN affectation_status = ? THEN 1 ELSE 2 END",
                [$normalized, ESBTPEcheancierRule::STATUS_ALL]
            )
            ->orderBy('priority')
            ->orderByDesc('updated_at')
            ->with(['lines' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->first();
    }

    /**
     * Une requete par type de portee : les identifiants qui portent au moins
     * une regle active. Sur filtre plus large que la requete de resolution
     * (ni date ni statut), donc ne peut ecarter qu'une portee qui n'aurait
     * rien rendu. Sur une ecole en mode repli, plus aucune requete par eleve.
     *
     * @return array<int, true>
     */
    private function porteesAvecRegle(string $scopeType): array
    {
        return $this->portees[$scopeType] ??= ESBTPEcheancierRule::query()
            ->where('scope_type', $scopeType)
            ->active()
            ->distinct()
            ->pluck('scope_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    private function tablesPresentes(): bool
    {
        if ($this->tablesPresentes) {
            return true;
        }

        return $this->tablesPresentes = Schema::hasTable('esbtp_echeancier_rules')
            && Schema::hasTable('esbtp_echeancier_rule_lines');
    }

    public function findBestAssignmentForInscription(?ESBTPFraisOption $option, ESBTPInscription $inscription): ?ESBTPOptionAssignment
    {
        if (!$option) {
            return null;
        }

        $assignments = $option->relationLoaded('assignments')
            ? $option->assignments
            : $option->assignments()->active()->get();

        if ($assignments->isEmpty()) {
            return null;
        }

        return $this->matchByPriority($assignments, $inscription);
    }

    private function matchByPriority(Collection $assignments, ESBTPInscription $inscription): ?ESBTPOptionAssignment
    {
        $assignments = $assignments->where('is_active', true)->values();

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'classe'
                && (int) $assignment->filiere_id === (int) $inscription->filiere_id
                && (int) $assignment->niveau_id === (int) $inscription->niveau_id;
        });
        if ($match) {
            return $match;
        }

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'filiere'
                && (int) $assignment->filiere_id === (int) $inscription->filiere_id;
        });
        if ($match) {
            return $match;
        }

        $match = $assignments->first(function ($assignment) use ($inscription) {
            return $assignment->assignment_type === 'niveau'
                && (int) $assignment->niveau_id === (int) $inscription->niveau_id;
        });
        if ($match) {
            return $match;
        }

        return $assignments->first(function ($assignment) {
            return $assignment->assignment_type === 'all';
        });
    }
}
