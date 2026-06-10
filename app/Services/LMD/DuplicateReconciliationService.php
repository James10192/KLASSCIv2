<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Collection;

/**
 * Détection des quasi-doublons UE / ECUE LMD.
 *
 * Why: quand des maquettes LMD de parcours différents (ex Génie Civil :
 * Bâtiment & Urbanisme, Travaux Publics) sont importées, les MÊMES matières / UE
 * apparaissent en double avec des codes/crédits/coefficients différents (ex
 * « Physique des matériaux (PM) » avec un code BPM3xx côté Bâtiment et TPPM3xx
 * côté Travaux Publics). Le métier veut les garder distinctes à l'import MAIS
 * pouvoir les DÉTECTER puis FUSIONNER en une entité partagée liée à plusieurs
 * parcours via les pivots existants (esbtp_lmd_parcours_ue, esbtp_ue_matiere).
 *
 * La détection se fait par similarité du `name` normalisé (minuscule, sans
 * accents, espaces collapsés). Deux entités du même niveau + semestre dont les
 * noms sont similaires à >= seuil (défaut 85%) forment un groupe de doublons.
 *
 * Ce service NE MUTE RIEN : il ne fait que détecter et décrire les écarts.
 * La fusion réelle est confiée aux Actions MergeDuplicateUe / MergeDuplicateEcue.
 */
class DuplicateReconciliationService
{
    /** Seuil de similarité par défaut (en pourcentage 0-100). */
    public const DEFAULT_SIMILARITY_THRESHOLD = 85.0;

    /**
     * Détecte les groupes de doublons d'UE.
     *
     * @param  array{mention_id?:int|null, parcours_id?:int|null, same_level_only?:bool, threshold?:float}  $options
     * @return array<int, array<string, mixed>>  liste de groupes
     */
    public function detectDuplicateUes(array $options = []): array
    {
        $threshold = (float) ($options['threshold'] ?? self::DEFAULT_SIMILARITY_THRESHOLD);
        $sameLevelOnly = (bool) ($options['same_level_only'] ?? true);

        $query = ESBTPUniteEnseignement::query()
            ->with(['parcoursMultiple:id,name,code', 'parcours:id,name,code', 'niveau:id,name']);

        $this->applyUeScope($query, $options);

        $ues = $query->get();

        $groups = $this->groupBySimilarity(
            $ues,
            $threshold,
            $sameLevelOnly,
            fn (ESBTPUniteEnseignement $ue) => $this->ueGroupingKey($ue)
        );

        return $groups
            ->filter(fn ($bucket) => $bucket->count() >= 2)
            ->map(fn ($bucket, $idx) => $this->describeUeGroup($bucket, $idx))
            ->values()
            ->all();
    }

    /**
     * Détecte les groupes de doublons d'ECUE (matières liées à une UE).
     *
     * @param  array{mention_id?:int|null, parcours_id?:int|null, same_level_only?:bool, threshold?:float}  $options
     * @return array<int, array<string, mixed>>  liste de groupes
     */
    public function detectDuplicateEcues(array $options = []): array
    {
        $threshold = (float) ($options['threshold'] ?? self::DEFAULT_SIMILARITY_THRESHOLD);
        $sameLevelOnly = (bool) ($options['same_level_only'] ?? true);

        $query = ESBTPMatiere::query()
            ->whereNotNull('unite_enseignement_id')
            ->with(['uniteEnseignement:id,name,code,semestre,niveau_id', 'niveauEtude:id,name']);

        $this->applyEcueScope($query, $options);

        $ecues = $query->get();

        $groups = $this->groupBySimilarity(
            $ecues,
            $threshold,
            $sameLevelOnly,
            fn (ESBTPMatiere $ecue) => $this->ecueGroupingKey($ecue)
        );

        return $groups
            ->filter(fn ($bucket) => $bucket->count() >= 2)
            ->map(fn ($bucket, $idx) => $this->describeEcueGroup($bucket, $idx))
            ->values()
            ->all();
    }

    /**
     * Normalise un nom pour comparaison : minuscule, sans accents, espaces collapsés.
     */
    public function normalizeName(?string $name): string
    {
        $name = (string) $name;
        // Translittération des accents vers ASCII.
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($ascii !== false) {
            $name = $ascii;
        }
        $name = mb_strtolower($name, 'UTF-8');
        // Retire la ponctuation, garde lettres/chiffres/espaces.
        $name = preg_replace('/[^a-z0-9\s]/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }

    /**
     * Similarité (0-100) entre deux noms déjà normalisés ou non.
     */
    public function similarity(?string $a, ?string $b): float
    {
        $na = $this->normalizeName($a);
        $nb = $this->normalizeName($b);

        if ($na === '' && $nb === '') {
            return 0.0;
        }
        if ($na === $nb) {
            return 100.0;
        }

        // similar_text donne un pourcentage de caractères communs ; robuste pour
        // des libellés courts. On complète par une distance de Levenshtein
        // normalisée pour ne pas surévaluer les sous-chaînes.
        similar_text($na, $nb, $percentSimilar);

        $maxLen = max(strlen($na), strlen($nb));
        $lev = $maxLen > 0 ? levenshtein($na, $nb) : 0;
        $levSimilar = $maxLen > 0 ? (1 - ($lev / $maxLen)) * 100 : 0.0;

        // Moyenne des deux mesures pour lisser les faux positifs/négatifs.
        return round(($percentSimilar + $levSimilar) / 2, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scope helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function applyUeScope($query, array $options): void
    {
        if (!empty($options['parcours_id'])) {
            $pId = (int) $options['parcours_id'];
            $query->where(function ($q) use ($pId) {
                $q->where('parcours_id', $pId)
                    ->orWhereHas('parcoursMultiple', fn ($p) => $p->where('esbtp_lmd_parcours.id', $pId));
            });
        }

        if (!empty($options['mention_id'])) {
            $mId = (int) $options['mention_id'];
            $query->where(function ($q) use ($mId) {
                $q->whereHas('parcours', fn ($p) => $p->where('mention_id', $mId))
                    ->orWhereHas('parcoursMultiple', fn ($p) => $p->where('esbtp_lmd_parcours.mention_id', $mId));
            });
        }
    }

    private function applyEcueScope($query, array $options): void
    {
        if (!empty($options['parcours_id'])) {
            $pId = (int) $options['parcours_id'];
            $query->whereHas('uniteEnseignement', function ($ue) use ($pId) {
                $ue->where('parcours_id', $pId)
                    ->orWhereHas('parcoursMultiple', fn ($p) => $p->where('esbtp_lmd_parcours.id', $pId));
            });
        }

        if (!empty($options['mention_id'])) {
            $mId = (int) $options['mention_id'];
            $query->whereHas('uniteEnseignement', function ($ue) use ($mId) {
                $ue->whereHas('parcours', fn ($p) => $p->where('mention_id', $mId))
                    ->orWhereHas('parcoursMultiple', fn ($p) => $p->where('esbtp_lmd_parcours.mention_id', $mId));
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Grouping core
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Regroupe une collection d'entités par similarité de nom.
     *
     * Algorithme : union-find approximatif simple — on parcourt les entités, et
     * chaque entité rejoint le premier groupe existant dont le représentant est
     * suffisamment similaire (et compatible niveau/semestre si demandé), sinon
     * elle crée un nouveau groupe. Suffisant pour des cohortes de maquettes (N
     * petit par scope).
     *
     * @param  Collection  $entities
     * @return Collection<int, Collection>  buckets
     */
    private function groupBySimilarity(Collection $entities, float $threshold, bool $sameLevelOnly, callable $compatKey): Collection
    {
        /** @var array<int, Collection> $buckets */
        $buckets = [];

        foreach ($entities as $entity) {
            $name = $this->entityName($entity);
            $placed = false;

            foreach ($buckets as $b => $bucket) {
                $rep = $bucket->first();

                if ($sameLevelOnly && $compatKey($rep) !== $compatKey($entity)) {
                    continue;
                }

                if ($this->similarity($name, $this->entityName($rep)) >= $threshold) {
                    $buckets[$b]->push($entity);
                    $placed = true;
                    break;
                }
            }

            if (!$placed) {
                $buckets[] = collect([$entity]);
            }
        }

        return collect($buckets);
    }

    private function entityName($entity): ?string
    {
        return $entity->name ?? null;
    }

    private function ueGroupingKey(ESBTPUniteEnseignement $ue): string
    {
        return ($ue->niveau_id ?? 'x') . '|' . ($ue->semestre ?? 'x');
    }

    private function ecueGroupingKey(ESBTPMatiere $ecue): string
    {
        $ue = $ecue->uniteEnseignement;

        return ($ecue->niveau_etude_id ?? 'x') . '|' . ($ue?->semestre ?? 'x');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Group description (écarts code/crédit/coef + parcours rattachés)
    // ─────────────────────────────────────────────────────────────────────────

    private function describeUeGroup(Collection $bucket, int $idx): array
    {
        $candidates = $bucket->map(function (ESBTPUniteEnseignement $ue) {
            return [
                'id' => $ue->id,
                'name' => $ue->name,
                'code' => $ue->code,
                'credit' => $ue->credit,
                'semestre' => $ue->semestre,
                'niveau_id' => $ue->niveau_id,
                'niveau' => $ue->niveau?->name,
                'parcours' => $this->ueParcours($ue),
            ];
        })->values();

        return [
            'group_id' => 'ue-' . $idx,
            'type' => 'ue',
            'normalized_name' => $this->normalizeName($bucket->first()->name),
            'count' => $bucket->count(),
            'candidates' => $candidates->all(),
            'discrepancies' => [
                'code' => $candidates->pluck('code')->unique()->count() > 1,
                'credit' => $candidates->pluck('credit')->unique()->count() > 1,
                'niveau' => $candidates->pluck('niveau_id')->unique()->count() > 1,
                'semestre' => $candidates->pluck('semestre')->unique()->count() > 1,
            ],
        ];
    }

    private function describeEcueGroup(Collection $bucket, int $idx): array
    {
        $candidates = $bucket->map(function (ESBTPMatiere $ecue) {
            $ue = $ecue->uniteEnseignement;

            return [
                'id' => $ecue->id,
                'name' => $ecue->name,
                'code' => $ecue->code,
                'credit_ecue' => $ecue->credit_ecue,
                'coefficient_ecue' => $ecue->coefficient_ecue,
                'niveau_id' => $ecue->niveau_etude_id,
                'niveau' => $ecue->niveauEtude?->name,
                'ue' => $ue ? ['id' => $ue->id, 'name' => $ue->name, 'code' => $ue->code, 'semestre' => $ue->semestre] : null,
            ];
        })->values();

        return [
            'group_id' => 'ecue-' . $idx,
            'type' => 'ecue',
            'normalized_name' => $this->normalizeName($bucket->first()->name),
            'count' => $bucket->count(),
            'candidates' => $candidates->all(),
            'discrepancies' => [
                'code' => $candidates->pluck('code')->unique()->count() > 1,
                'credit' => $candidates->pluck('credit_ecue')->unique()->count() > 1,
                'coefficient' => $candidates->pluck('coefficient_ecue')->unique()->count() > 1,
                'niveau' => $candidates->pluck('niveau_id')->unique()->count() > 1,
            ],
        ];
    }

    /**
     * Parcours rattachés à une UE (pivot multiple + FK direct legacy).
     *
     * @return array<int, array{id:int, name:string, code:?string}>
     */
    private function ueParcours(ESBTPUniteEnseignement $ue): array
    {
        $parcours = collect();

        if ($ue->relationLoaded('parcoursMultiple')) {
            $parcours = $ue->parcoursMultiple->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code]);
        }

        if ($ue->parcours_id && $ue->relationLoaded('parcours') && $ue->parcours) {
            $parcours->push(['id' => $ue->parcours->id, 'name' => $ue->parcours->name, 'code' => $ue->parcours->code]);
        }

        return $parcours->unique('id')->values()->all();
    }
}
