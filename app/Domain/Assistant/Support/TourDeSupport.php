<?php

namespace App\Domain\Assistant\Support;

/**
 * Un tour de Nanan en mode support, tel que l'écran le reçoit.
 *
 *   question        une seule question, avec au plus quatre réponses en un clic
 *   reponse         Nanan répond elle-même ; l'écran demande « Ça a résolu ? »
 *   recapitulatif   la demande prête à relire, puis à envoyer par la personne
 *
 * `source` dit d'où vient le tour : `ia` (le modèle) ou `guide` (questions
 * scriptées, quand aucun modèle ne répond). L'écran ne change pas de forme.
 */
final class TourDeSupport
{
    public const QUESTION = 'question';
    public const REPONSE = 'reponse';
    public const RECAPITULATIF = 'recapitulatif';

    /**
     * @param list<string> $choix
     * @param array{titre:string,description:string,categorie:string}|null $recap
     */
    public function __construct(
        public readonly string $action,
        public readonly string $texte,
        public readonly array $choix = [],
        public readonly ?array $recap = null,
        public readonly string $source = 'guide',
    ) {
    }

    public static function question(string $texte, array $choix = [], string $source = 'guide'): self
    {
        return new self(self::QUESTION, $texte, array_slice(array_values($choix), 0, 4), null, $source);
    }

    public static function reponse(string $texte, string $source = 'ia'): self
    {
        return new self(self::REPONSE, $texte, [], null, $source);
    }

    /** @param array{titre:string,description:string,categorie:string} $recap */
    public static function recapitulatif(string $texte, array $recap, string $source = 'guide'): self
    {
        return new self(self::RECAPITULATIF, $texte, [], $recap, $source);
    }

    public function versTableau(): array
    {
        return [
            'action' => $this->action,
            'texte' => $this->texte,
            'choix' => $this->choix,
            'recap' => $this->recap,
            'source' => $this->source,
        ];
    }
}
