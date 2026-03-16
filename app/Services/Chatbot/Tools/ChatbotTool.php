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
     * LIKE exact → LIKE par terme → SOUNDEX fallback (si 0 résultat LIKE).
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
