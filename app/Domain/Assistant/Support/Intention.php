<?php

namespace App\Domain\Assistant\Support;

/**
 * Ce que la personne vient chercher en ouvrant « Aide & support ».
 *
 * Chaque intention propose une catégorie de demande par défaut ; la personne
 * peut la changer au récapitulatif, et le modèle aussi, parmi les catégories
 * de config/support.php seulement.
 */
enum Intention: string
{
    case PROBLEME = 'probleme';
    case COMMENT = 'comment';
    case IDEE = 'idee';

    public function categorieParDefaut(): string
    {
        return match ($this) {
            self::PROBLEME => 'PROBLEME',
            self::COMMENT => 'QUESTION',
            self::IDEE => 'SUGGESTION',
        };
    }

    /** Ce que fait Nanan dans ce mode, dit au modèle. */
    public function consigne(): string
    {
        return match ($this) {
            self::PROBLEME => "La personne signale un problème. Ton but : réunir le contexte utile à l'équipe support, puis rédiger la demande. Si, et seulement si, le problème est clairement une erreur d'utilisation que tu sais corriger avec certitude, tu peux répondre directement (action « reponse »).",
            self::COMMENT => "La personne demande comment faire quelque chose. Réponds directement si tu es certaine de la réponse (action « reponse »), en étapes courtes. Sinon, dis-le en une phrase et passe au récapitulatif pour transmettre la question au support.",
            self::IDEE => "La personne propose une idée d'amélioration. Fais-la préciser : ce qu'elle voudrait pouvoir faire, et ce que cela lui ferait gagner. Puis rédige la demande. Ne promets jamais que l'idée sera retenue.",
        };
    }
}
