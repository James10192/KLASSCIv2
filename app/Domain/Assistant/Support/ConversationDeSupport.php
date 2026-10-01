<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Consommation\Compteur;
use App\Domain\Assistant\Consommation\JournalDeConsommation;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Routage\Routeur;
use Illuminate\Support\Facades\Log;

/**
 * Un tour de Nanan dans « Aide & support » : le modèle d'abord, le guide
 * scripté dès qu'il ne répond pas ou répond hors format.
 *
 * Le modèle est le moins cher qui suffit (palier de départ du routeur) : le
 * mode support ne lit aucune donnée, il pose des questions et rédige. Chaque
 * appel est compté au budget de l'école, sous la fonction « support ».
 */
class ConversationDeSupport
{
    /** Modèles essayés au plus, avant de passer au guide. */
    private const ESSAIS = 2;

    public function __construct(
        private readonly Routeur $routeur,
        private readonly PromptDeSupport $prompt,
        private readonly LectureDeReponse $lecture,
        private readonly GuideDeSupport $guide,
        private readonly JournalDeConsommation $journal,
    ) {
    }

    /**
     * @param array{titre?:?string,route?:?string,module?:?string,entite?:?array} $page
     */
    public function tour($user, Intention $intention, FilDeSupport $fil, array $page, bool $forcerRecap = false): TourDeSupport
    {
        $questionsMax = max(1, (int) config('assistant.support.questions_max', 6));

        // Rien n'est encore dit : la question d'ouverture n'a pas besoin du modèle.
        if ($fil->estVide()) {
            return $this->guide->premiereQuestion($intention);
        }

        // Plafond atteint : le récapitulatif est forcé, même si le modèle insiste.
        $forcer = $forcerRecap || $fil->toursDeNanan() >= $questionsMax;

        $tour = $this->parLeModele($user, $intention, $fil, $page, $questionsMax, $forcer);
        if ($tour !== null && (! $forcer || $tour->action === TourDeSupport::RECAPITULATIF)) {
            return $tour;
        }

        return $this->guide->tour($intention, $fil, $page, $questionsMax, $forcer);
    }

    private function parLeModele($user, Intention $intention, FilDeSupport $fil, array $page, int $questionsMax, bool $forcer): ?TourDeSupport
    {
        if (! (bool) config('assistant.support.ia', true)) {
            return null;
        }

        try {
            $decision = $this->routeur->decider('', null);
        } catch (\Throwable $e) {
            Log::warning('assistant.support.routage_impossible', ['erreur' => $e->getMessage()]);

            return null;
        }
        if ($decision->pause || $decision->candidats === []) {
            return null;
        }

        $categories = (array) config('support.categories', []);
        $requete = new RequeteModele(
            systeme: $this->prompt->systeme($user, $intention, $page, $categories, $fil->toursDeNanan(), $questionsMax, $forcer),
            messages: $fil->pourLeModele(),
            maxTokens: (int) config('assistant.support.max_tokens', 900),
            temperature: 0.2,
        );

        $compteur = new Compteur();
        $tour = null;
        foreach (array_slice($decision->candidats, 0, self::ESSAIS) as $modele) {
            [$texte, $erreur, $usage] = $this->appeler($requete, $modele);

            $estime = ! $erreur && $usage === [];
            $compteur->ajouter(
                $modele,
                (int) ($usage['entree'] ?? ($estime ? (int) ceil(mb_strlen($requete->systeme, 'UTF-8') / 4) : 0)),
                (int) ($usage['sortie'] ?? ($estime ? (int) ceil(mb_strlen($texte, 'UTF-8') / 4) : 0)),
                (int) ($usage['cache'] ?? 0),
                $usage['cout'] ?? null,
                0,
                $erreur || trim($texte) === ''
            );

            if (! $erreur) {
                $tour = $this->lecture->lire($texte, $intention, array_keys($categories));
                if ($tour !== null) {
                    break;
                }
                Log::info('assistant.support.reponse_hors_format', ['modele' => $modele->cle]);
            }
        }

        $this->journal->enregistrer($compteur, $user?->id, null, 'support', $decision->palier, $tour === null ? 'erreur' : 'ok');

        return $tour;
    }

    /** @return array{0:string,1:bool,2:array} texte, erreur, usage */
    private function appeler(RequeteModele $requete, $modele): array
    {
        $texte = '';
        $usage = [];
        try {
            $fournisseur = app(config('assistant.adaptateurs.' . $modele->adaptateur));
            foreach ($fournisseur->diffuser($requete, $modele, fn () => false) as $evenement) {
                if ($evenement->type === EvenementModele::TEXTE) {
                    $texte .= $evenement->donnees['delta'];
                } elseif ($evenement->type === EvenementModele::USAGE) {
                    $usage = $evenement->donnees;
                } elseif ($evenement->type === EvenementModele::ERREUR) {
                    return [$texte, true, $usage];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('assistant.support.modele_en_echec', ['modele' => $modele->cle, 'erreur' => $e->getMessage()]);

            return [$texte, true, $usage];
        }

        return [$texte, false, $usage];
    }
}
