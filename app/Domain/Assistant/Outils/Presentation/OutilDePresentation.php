<?php

namespace App\Domain\Assistant\Outils\Presentation;

use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Outil qui ne lit aucune donnée : il met en forme ce que le modèle a déjà
 * obtenu (graphique, tableau calculé, diagramme). Il ne dévoile donc rien que
 * l'utilisateur n'ait déjà le droit de voir, et reste ouvert à tout compte
 * connecté.
 *
 * Le résultat porte le widget, que la boucle envoie à l'écran, et `affiche`,
 * qui dit au modèle de ne pas recopier ce contenu.
 */
abstract class OutilDePresentation extends ChatbotTool
{
    public function isAvailableFor($user): bool
    {
        return (bool) $user;
    }

    /** Données contrôlées, ou le message d'erreur à renvoyer au modèle. */
    abstract protected function widget(array $args): array|string;

    public function execute(array $args, $user): array
    {
        $widget = $this->widget($args);
        if (is_string($widget)) {
            // Le modèle corrige ses arguments au tour suivant : l'erreur doit dire quoi changer.
            return ['error' => $widget];
        }

        return ['affiche' => true, 'widget' => $widget, 'count' => 1];
    }

    protected function texte($valeur, int $max = 120): string
    {
        return mb_substr(trim((string) $valeur), 0, $max, 'UTF-8');
    }
}
