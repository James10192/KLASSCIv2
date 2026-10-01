<?php

namespace App\Domain\Assistant\Support;

/**
 * Ce que le modèle a écrit, ramené à un tour que l'écran sait montrer.
 *
 * Le modèle doit rendre un objet JSON ; tout ce qui s'en écarte (texte libre,
 * action inconnue, catégorie hors liste, champ vide) est refusé ici, et le
 * guide scripté prend le relais. Jamais de réponse à moitié valide à l'écran.
 */
final class LectureDeReponse
{
    private const TEXTE_MAX = 1200;
    private const REPONSE_MAX = 2500;
    private const CHOIX_MAX = 4;

    /**
     * Une promesse de délai que KLASSCI ne peut pas tenir : la phrase qui la
     * porte est retirée, même si le prompt l'interdit déjà.
     */
    private const PROMESSE_DE_DELAI = '/\b(sous|dans|d\'ici|en moins de)\s+(\d+|une|deux|quelques|la)\s*(min(ute)?s?|h|heures?|jours?|journée|semaines?)\b|\bd\'ici (demain|ce soir|la fin de)|\b(dans|sous) les plus brefs délais\b/iu';

    /** @param list<string> $categories codes admis (config('support.categories')) */
    public function lire(string $brut, Intention $intention, array $categories): ?TourDeSupport
    {
        $donnees = $this->objetJson($brut);
        if ($donnees === null) {
            return null;
        }

        $action = $donnees['action'] ?? null;
        $texte = $this->texte($donnees['texte'] ?? null, $action === TourDeSupport::REPONSE ? self::REPONSE_MAX : self::TEXTE_MAX);

        if ($action === TourDeSupport::QUESTION && $texte !== null) {
            return TourDeSupport::question($texte, $this->choix($donnees['choix'] ?? []), 'ia');
        }

        if ($action === TourDeSupport::REPONSE && $texte !== null) {
            return TourDeSupport::reponse($texte, 'ia');
        }

        if ($action === TourDeSupport::RECAPITULATIF && is_array($donnees['recap'] ?? null)) {
            $recap = $donnees['recap'];
            $titre = $this->texte($recap['titre'] ?? null, 120);
            $description = $this->texte($recap['description'] ?? null, 3000);
            if ($titre === null || $description === null) {
                return null;
            }
            $categorie = is_string($recap['categorie'] ?? null) && in_array($recap['categorie'], $categories, true)
                ? $recap['categorie']
                : $intention->categorieParDefaut();

            return TourDeSupport::recapitulatif(
                $texte ?? GuideDeSupport::TEXTE_RECAP,
                ['titre' => str_replace(["\r", "\n"], ' ', $titre), 'description' => $description, 'categorie' => $categorie],
                'ia'
            );
        }

        return null;
    }

    /** Le premier objet JSON du texte, même entouré de ``` ou d'une phrase. */
    private function objetJson(string $brut): ?array
    {
        $brut = trim($brut);
        $debut = strpos($brut, '{');
        $fin = strrpos($brut, '}');
        if ($debut === false || $fin === false || $fin <= $debut) {
            return null;
        }
        $donnees = json_decode(substr($brut, $debut, $fin - $debut + 1), true);

        return is_array($donnees) ? $donnees : null;
    }

    private function texte(mixed $valeur, int $max): ?string
    {
        if (! is_string($valeur)) {
            return null;
        }
        $texte = trim(self::sansPromesseDeDelai($valeur));

        return $texte === '' ? null : mb_substr($texte, 0, $max);
    }

    /** @return list<string> */
    private function choix(mixed $choix): array
    {
        if (! is_array($choix)) {
            return [];
        }
        $propres = [];
        foreach ($choix as $c) {
            if (is_string($c) && trim($c) !== '' && mb_strlen(trim($c)) <= 60) {
                $propres[] = trim($c);
            }
        }

        return array_slice(array_values(array_unique($propres)), 0, self::CHOIX_MAX);
    }

    public static function sansPromesseDeDelai(string $texte): string
    {
        if (! preg_match(self::PROMESSE_DE_DELAI, $texte)) {
            return $texte;
        }
        $phrases = preg_split('/(?<=[.!?])\s+/u', $texte) ?: [$texte];

        return implode(' ', array_filter($phrases, fn ($p) => ! preg_match(self::PROMESSE_DE_DELAI, $p)));
    }
}
