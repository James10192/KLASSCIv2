<?php

namespace App\Services\Chatbot\Tools;

/**
 * Contrat de base pour les outils du chatbot IA.
 *
 * Chaque outil déclare son schéma (JSON Schema pour Claude tool use)
 * et exécute une action concrète quand le LLM le demande.
 */
abstract class ChatbotTool
{
    /**
     * Nom unique de l'outil.
     */
    abstract public function name(): string;

    /**
     * Description courte pour le LLM.
     */
    abstract public function description(): string;

    /**
     * Schéma des paramètres au format JSON Schema.
     *
     * @return array{type:string,properties:array,required?:array}
     */
    abstract public function parameters(): array;

    /**
     * Exécuter l'outil et retourner les résultats.
     *
     * @param  array  $args  Arguments fournis par le LLM
     * @param  \App\Models\User  $user  Utilisateur authentifié
     * @return array  Résultats structurés
     */
    abstract public function execute(array $args, $user): array;

    /**
     * Permissions requises pour utiliser cet outil (Spatie).
     * Retourne null si pas de vérification nécessaire.
     *
     * @return string[]|null
     */
    public function requiredPermissions(): ?array
    {
        return null;
    }

    /**
     * Rôles autorisés à utiliser cet outil.
     * Retourne null si tous les rôles ont accès.
     *
     * @return string[]|null
     */
    public function allowedRoles(): ?array
    {
        return null;
    }

    /**
     * Appliquer une recherche floue nom/prénoms sur une query Eloquent.
     * Stratégie fuzzy-OR : LIKE exact + LIKE par terme + SOUNDEX phonétique (combinés).
     */
    public function applyFuzzyNameSearch($query, string $search, string $nomCol = 'nom', string $prenomsCol = 'prenoms'): void
    {
        $terms = preg_split('/\s+/', trim($search));

        $query->where(function ($q) use ($search, $terms, $nomCol, $prenomsCol) {
            // Exact substring match
            $q->where($nomCol, 'like', "%{$search}%")
              ->orWhere($prenomsCol, 'like', "%{$search}%")
              ->orWhereRaw("CONCAT({$nomCol}, ' ', {$prenomsCol}) LIKE ?", ["%{$search}%"]);

            // Per-term match
            if (count($terms) > 1) {
                $q->orWhere(function ($sub) use ($terms, $nomCol, $prenomsCol) {
                    foreach ($terms as $term) {
                        $sub->where(function ($inner) use ($term, $nomCol, $prenomsCol) {
                            $inner->where($nomCol, 'like', "%{$term}%")
                                  ->orWhere($prenomsCol, 'like', "%{$term}%");
                        });
                    }
                });
            }

            // SOUNDEX fuzzy fallback
            foreach ($terms as $term) {
                $q->orWhereRaw("SOUNDEX({$nomCol}) = SOUNDEX(?)", [$term])
                  ->orWhereRaw("SOUNDEX({$prenomsCol}) = SOUNDEX(?)", [$term]);
            }
        });
    }

    /**
     * Nom complet d'un étudiant.
     */
    protected function studentFullName($etudiant, string $fallback = 'N/A'): string
    {
        return $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : $fallback;
    }

    /**
     * Initiales d'un étudiant (première lettre nom + première lettre prénoms).
     */
    protected function studentInitials($etudiant, string $fallback = '?'): string
    {
        if (!$etudiant) return $fallback;
        return mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1) . mb_substr($etudiant->prenoms ?? '', 0, 1));
    }

    /**
     * Appliquer un filtre classe par nom ou code sur une query.
     */
    protected function applyClasseSearch($query, string $search, string $relation = 'classe'): void
    {
        $query->whereHas($relation, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    /**
     * Clamper la limite de résultats.
     */
    protected function clampLimit(array $args, int $default = 10, int $max = 25): int
    {
        return min(max((int) ($args['limit'] ?? $default), 1), $max);
    }

    /**
     * Formater un montant en FCFA.
     */
    protected function formatFCFA(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Mapper les périodes S1/S2 vers semestre1/semestre2 (format DB).
     */
    protected function mapPeriode(string $periode): ?string
    {
        return match (mb_strtoupper(trim($periode))) {
            'S1' => 'semestre1',
            'S2' => 'semestre2',
            'SEMESTRE1' => 'semestre1',
            'SEMESTRE2' => 'semestre2',
            default => null,
        };
    }

    /**
     * Convertir en définition d'outil Claude (tool use).
     */
    public function toToolDefinition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'input_schema' => $this->parameters(),
        ];
    }
}
