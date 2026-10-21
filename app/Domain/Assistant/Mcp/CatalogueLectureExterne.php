<?php

namespace App\Domain\Assistant\Mcp;

use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Models\User;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
use App\Services\Chatbot\Tools\SearchClassesTool;

/**
 * Adaptateur interne, non exposé sur HTTP.
 *
 * Le catalogue interne de Nanan contient également des écritures. Un client
 * externe NE DOIT JAMAIS recevoir la liste entière via schemas() ni choisir
 * librement un nom d'outil. On n'expose ici que deux classes de lecture
 * explicitement connues. Ajouter une entrée demande une revue de sécurité.
 *
 * La résolution de l'utilisateur ET de son établissement doit être réalisée
 * avant l'appel par un futur serveur OAuth/MCP. Ne jamais passer un utilisateur
 * issu d'un argument fourni par le modèle.
 */
class CatalogueLectureExterne
{
    /** @var array<string, class-string<ChatbotTool>> */
    private const OUTILS = [
        'search_classes' => SearchClassesTool::class,
        'get_dashboard_kpis' => GetDashboardKpisTool::class,
    ];

    public function __construct(private CatalogueOutils $catalogue)
    {
    }

    /**
     * @return array<int, array<string, mixed>> Schémas directement adaptables en outils MCP.
     */
    public function schemas(User $user): array
    {
        if (! $this->active()) {
            return [];
        }

        $schemas = [];

        foreach (array_keys(self::OUTILS) as $nom) {
            $outil = $this->autorise($nom, $user);

            if ($outil === null) {
                continue;
            }

            $schemas[] = [
                'name' => $outil->name(),
                'description' => $outil->description(),
                'inputSchema' => $outil->parameters(),
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                ],
            ];
        }

        return $schemas;
    }

    /**
     * Seules des données de lecture structurées sortent de l'adaptateur.
     * Aucune URL interne, carte HTML, jeton ou état de conversation Nanan.
     *
     * @return array<string, mixed>
     */
    public function executer(string $nom, array $arguments, User $user): array
    {
        if (! $this->active() || $this->autorise($nom, $user) === null) {
            return ['error' => 'Outil indisponible.'];
        }

        // CatalogueOutils revérifie les permissions à l'instant de l'exécution.
        $resultat = $this->catalogue->executer($nom, $arguments, $user);

        // Supprime widgets, liens internes, informations de navigation et
        // tout nouveau champ ajouté ultérieurement au retour d'un outil.
        return array_intersect_key($resultat, array_flip([
            'results', 'count', 'total', 'kpis', 'error',
        ]));
    }

    private function active(): bool
    {
        return config('mcp.read_only.enabled', false) === true;
    }

    private function autorise(string $nom, User $user): ?ChatbotTool
    {
        $classeAttendue = self::OUTILS[$nom] ?? null;

        if ($classeAttendue === null) {
            return null;
        }

        $outil = $this->catalogue->outil($nom);

        // Non seulement le nom, mais aussi la classe doit correspondre.
        // Une action renommée en search_classes reste donc refusée.
        if (! $outil || get_class($outil) !== $classeAttendue || ! $outil->isAvailableFor($user)) {
            return null;
        }

        return $outil;
    }
}
