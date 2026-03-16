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
     * Nom unique de l'outil (utilisé par Gemini).
     */
    abstract public function name(): string;

    /**
     * Description courte pour Gemini.
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
     * @param  array  $args  Arguments fournis par Gemini
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
