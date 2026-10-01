<?php

namespace App\Domain\Assistant\Support;

/**
 * Les questions de Nanan quand aucun modèle ne répond (pas de clé, budget en
 * pause, fournisseur en panne, réponse illisible). Le parcours reste le même
 * pour la personne : une question à la fois, puis le récapitulatif.
 *
 * Sans état : la prochaine question se déduit du fil renvoyé par l'écran.
 */
final class GuideDeSupport
{
    public const TEXTE_RECAP = 'Voici votre demande. Relisez-la, corrigez si besoin, puis envoyez-la au support.';

    /**
     * @param array{titre?:?string,route?:?string,chemin?:?string} $page
     */
    public function tour(Intention $intention, FilDeSupport $fil, array $page, int $questionsMax, bool $forcerRecap = false): TourDeSupport
    {
        if ($fil->estVide()) {
            return $this->premiereQuestion($intention);
        }

        $etapes = $this->etapes($intention, $fil, $page);
        $rang = $fil->toursDeNanan();

        if ($forcerRecap || $rang >= count($etapes) || $rang >= $questionsMax) {
            return TourDeSupport::recapitulatif(
                $intention === Intention::COMMENT && ! $forcerRecap
                    ? "Je ne peux pas vous répondre seule pour le moment : je transmets votre question à l'équipe support. Relisez-la, puis envoyez-la."
                    : self::TEXTE_RECAP,
                $this->recap($intention, $fil, $page)
            );
        }

        [$texte, $choix] = $etapes[$rang];

        return TourDeSupport::question($texte, $choix);
    }

    public function premiereQuestion(Intention $intention): TourDeSupport
    {
        return match ($intention) {
            Intention::PROBLEME => TourDeSupport::question("Que se passe-t-il ? Décrivez-le avec vos mots, en une ou deux phrases."),
            Intention::COMMENT => TourDeSupport::question('Que voulez-vous faire ? Posez votre question en une phrase.'),
            Intention::IDEE => TourDeSupport::question('Quelle est votre idée ? Dites ce que vous aimeriez pouvoir faire dans KLASSCI.'),
        };
    }

    /**
     * Les questions dans l'ordre. Si le fil a commencé par la question
     * d'ouverture de Nanan, elle compte comme la première étape.
     *
     * @return list<array{0:string,1:list<string>}>
     */
    private function etapes(Intention $intention, FilDeSupport $fil, array $page): array
    {
        $ouverture = ($fil->messages()[0]['role'] ?? null) === 'nanan' ? [['', []]] : [];
        $titre = $this->titrePage($page);

        $suite = match ($intention) {
            Intention::PROBLEME => [
                $titre !== null
                    ? ["Cela se passe-t-il sur la page « {$titre} » ?", ['Oui, sur cette page', 'Non, sur une autre page']]
                    : ['Sur quelle page ou quel écran cela se passe-t-il ?', []],
                ['Qui ou quoi est concerné ? Par exemple un élève, une classe ou un paiement : donnez son nom ou son numéro.', ['Plusieurs élèves', 'Toute une classe', 'Rien de précis']],
                ["Qu'attendiez-vous, et qu'avez-vous obtenu à la place ?", []],
                ['Depuis quand cela arrive-t-il ?', ["Aujourd'hui", 'Depuis quelques jours', 'Depuis toujours', 'Je ne sais pas']],
                ["Un message d'erreur s'est-il affiché ? Recopiez-le, ou répondez simplement.", ['Aucun message', 'Je ne m\'en souviens pas']],
            ],
            Intention::IDEE => [
                ['Qu\'est-ce que cela vous ferait gagner au quotidien ?', ['Du temps', 'Moins d\'erreurs', 'Un meilleur suivi des élèves']],
            ],
            // Une question d'usage sans modèle : rien à demander de plus, on transmet.
            Intention::COMMENT => [],
        };

        return array_merge($ouverture, $suite);
    }

    /** @return array{titre:string,description:string,categorie:string} */
    public function recap(Intention $intention, FilDeSupport $fil, array $page): array
    {
        $reponses = $fil->reponsesDeLaPersonne();
        $premier = $reponses[0] ?? '';

        $lignes = [$premier];
        $messages = $fil->messages();
        foreach ($messages as $i => $m) {
            if ($m['role'] !== 'nanan') {
                continue;
            }
            $reponse = $messages[$i + 1] ?? null;
            if ($reponse === null || $reponse['role'] !== 'personne' || $reponse['texte'] === $premier) {
                continue;
            }
            $lignes[] = '- ' . $m['texte'] . ' ' . $reponse['texte'];
        }
        $titrePage = $this->titrePage($page);
        if ($titrePage !== null) {
            $lignes[] = "Page ouverte : {$titrePage}";
        }

        return [
            'titre' => self::titreCourt($premier),
            'description' => trim(implode("\n", array_filter($lignes, fn ($l) => trim($l) !== ''))),
            'categorie' => $intention->categorieParDefaut(),
        ];
    }

    /** La première phrase, coupée sur un mot, sans dépasser 80 caractères. */
    public static function titreCourt(string $texte): string
    {
        $ligne = trim((string) preg_replace('/\s+/u', ' ', $texte));
        if ($ligne === '') {
            return 'Demande d\'aide';
        }
        if (mb_strlen($ligne) <= 80) {
            return $ligne;
        }
        $coupe = mb_substr($ligne, 0, 80);
        $espace = mb_strrpos($coupe, ' ');

        return rtrim($espace !== false && $espace > 40 ? mb_substr($coupe, 0, $espace) : $coupe, ' ,;:.') . '…';
    }

    private function titrePage(array $page): ?string
    {
        $titre = trim((string) ($page['titre'] ?? ''));
        // Le titre de l'onglet porte souvent « - KLASSCI » : on le retire.
        $titre = trim((string) preg_replace('/\s*[-–|]\s*KLASSCI.*$/iu', '', $titre));

        return $titre !== '' ? mb_substr($titre, 0, 80) : null;
    }
}
