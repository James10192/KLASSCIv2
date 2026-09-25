<?php

namespace App\Domain\Assistant\Outils\Presentation;

class AfficherDiagramme extends OutilDePresentation
{
    /** Types Mermaid acceptés : ceux qui servent à expliquer un processus ou une organisation. */
    private const TYPES = ['flowchart', 'graph', 'sequenceDiagram', 'stateDiagram-v2', 'stateDiagram', 'timeline', 'mindmap', 'journey', 'gantt', 'pie'];

    public function name(): string
    {
        return 'afficher_diagramme';
    }

    public function libelle(): string
    {
        return 'Dessin du diagramme…';
    }

    public function description(): string
    {
        return "Affiche un diagramme Mermaid : processus (flowchart TD), étapes d'un dossier (stateDiagram-v2), "
            . "chronologie (timeline), organisation (mindmap). Libellés courts en français entre guillemets, "
            . "ex. A[\"Dossier déposé\"] --> B{\"Pièces complètes ?\"}. Pas de directive %%{init}, pas de click, pas de style. "
            . "Pour expliquer un processus de KLASSCI, appuie-toi sur navigate_to_page ou get_setup_guide pour connaître les vraies étapes.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'titre' => ['type' => 'string'],
                'mermaid' => ['type' => 'string', 'description' => 'Code Mermaid complet, commençant par le type (flowchart TD, stateDiagram-v2, timeline…).'],
            ],
            'required' => ['titre', 'mermaid'],
        ];
    }

    protected function widget(array $args): array|string
    {
        $code = trim(str_replace(["\r\n", "\r"], "\n", (string) ($args['mermaid'] ?? '')));
        $code = preg_replace('/^```(?:mermaid)?\s*|\s*```$/', '', $code);

        if ($code === '' || strlen($code) > 6000) {
            return 'Le code Mermaid est vide ou trop long (6000 caractères au plus).';
        }

        $premier = strtok(ltrim($code), " \n");
        if (!in_array($premier, self::TYPES, true)) {
            return 'Le code doit commencer par un de ces types : ' . implode(', ', self::TYPES) . '.';
        }

        // Le rendu se fait en mode strict côté navigateur ; on refuse en plus ce qui
        // n'a rien à faire dans un diagramme explicatif.
        if (preg_match('/%%\{|\bclick\b|<\s*script|javascript:|\bhref\b/i', $code)) {
            return 'Retire les directives %%{…}, les « click » et les liens : un diagramme explicatif n\'en a pas besoin.';
        }

        return ['kind' => 'diagramme', 'titre' => $this->texte($args['titre'] ?? ''), 'mermaid' => $code];
    }
}
