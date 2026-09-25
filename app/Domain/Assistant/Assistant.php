<?php

namespace App\Domain\Assistant;

use App\Domain\Assistant\Affichage\ConstructeurAffichage;
use App\Domain\Assistant\Consommation\Compteur;
use App\Domain\Assistant\Consommation\JournalDeConsommation;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Harnais\FilDeReponse;
use App\Domain\Assistant\Harnais\ResultatBoucle;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Routage\Decision;
use App\Domain\Assistant\Routage\Routeur;
use App\Models\ChatbotConversation;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\ConversationContextProvider;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée de l'assistant IA : un échange = prompt + boucle d'agent +
 * mise en forme + journal. Indépendant du fournisseur de modèle.
 *
 *   Harnais\ConstructeurDePrompt    prompt système et historique neutres
 *   Modeles\RegistreDesModeles      modèles déclarés, disponibles, chaîne de repli
 *   Routage\Routeur                 palier et modèles de l'échange, budget du mois
 *   Consommation\*                  coût de chaque appel, écrit par échange
 *   Harnais\BoucleAgent             tours, outils, garde-fous, parties du flux
 *   Fournisseurs\*                  Anthropic, OpenAI-compatible, Gemini
 *   Affichage\ConstructeurAffichage résultats d'outils → display_data
 */
class Assistant
{
    private const PLACEHOLDER = 'Je suis là pour vous aider. Que souhaitez-vous savoir ?';

    public function __construct(
        private ConstructeurDePrompt $prompt,
        private RegistreDesModeles $registre,
        private BoucleAgent $boucle,
        private CatalogueOutils $catalogue,
        private ConversationContextProvider $contextProvider,
        private Routeur $routeur,
        private JournalDeConsommation $journal,
    ) {
    }

    /**
     * @return array{text:string,tool_calls:array,display_type:string,display_data:?array,deep_link:?string,erreur:bool,interrompu:bool,modele:?string}
     */
    public function repondre(
        ChatbotConversation $conversation,
        string $question,
        $user,
        ?ChatbotUserPreference $preferences,
        ?array $contexteClient,
        UiMessageStream $ui,
        ?string $modeleDemande = null,
        bool $relance = false,
    ): array {
        $decision = $this->routeur->decider($question, $conversation, $relance, $modeleDemande);
        if ($decision->pause) {
            Log::info('assistant.pause_budget', ['conversation_id' => $conversation->id, 'user_id' => $user?->id]);

            return $this->reponseEnPause($ui);
        }

        $requete = new RequeteModele(
            systeme: $this->prompt->systeme($user, $preferences, $contexteClient, $conversation),
            messages: $this->prompt->messages($conversation, $question),
            outils: $this->catalogue->schemas($user),
            maxTokens: (int) config('assistant.limites.max_tokens', 2048),
            temperature: (float) config('assistant.limites.temperature', 0.2),
        );

        $affichage = new ConstructeurAffichage($this->contextProvider);
        $fil = new FilDeReponse();
        $compteur = new Compteur();
        $resultat = $this->boucle->executer(
            $decision->candidats,
            $requete,
            $user,
            $ui,
            function (string $nom, array $args, array $res) use ($affichage, $conversation): ?array {
                $affichage->enregistrer($conversation, $nom, $args, $res);

                return $affichage->widgetPour($nom, $res);
            },
            $fil,
            $compteur,
        );

        $this->journaliser($resultat, $conversation, $user, $decision);
        $consommation = $this->journal->enregistrer($compteur, $user?->id, $conversation->id, 'question', $decision->palier, $resultat->statut);
        $palier = $this->routeur->palierApres($decision, $resultat, $conversation);

        if ($resultat->estErreur() || $resultat->estInterrompu()) {
            return [
                'text' => $resultat->texte !== '' ? $resultat->texte : "Désolé, l'assistant n'a pas pu répondre. Réessayez dans un instant.",
                'tool_calls' => $resultat->appels,
                'display_type' => 'text',
                'display_data' => null,
                'deep_link' => null,
                'erreur' => $resultat->estErreur(),
                'interrompu' => $resultat->estInterrompu(),
                'modele' => $resultat->modele,
                'parties' => $fil->toArray(),
                'trace' => $resultat->trace,
                'suites' => [],
                'consommation' => $consommation,
                'palier' => $palier,
            ];
        }

        $dernierTour = $resultat->texteDernierTour !== '' ? $resultat->texteDernierTour : self::PLACEHOLDER;
        $final = $affichage->finaliser($dernierTour);

        // Ce que la mise en forme ajoute au dernier tour (guide de page, texte
        // par défaut) part en un dernier bloc : l'écran et l'historique montrent
        // la même chose.
        $ajout = '';
        if ($resultat->texteDernierTour === '') {
            // Rien d'écrit au dernier tour : texte de mise en forme, sauf le
            // texte par défaut quand une réponse a déjà été montrée.
            if (!($final['text'] === self::PLACEHOLDER && $resultat->texte !== '')) {
                $ajout = $final['text'];
            }
        } elseif ($final['text'] !== $resultat->texteDernierTour) {
            $ajout = str_starts_with($final['text'], $resultat->texteDernierTour)
                ? ltrim(substr($final['text'], strlen($resultat->texteDernierTour)), "\n")
                : $final['text'];
        }
        $ui->text($ajout);
        $fil->texte($ajout);
        $texte = $ajout === '' ? $resultat->texte : trim($resultat->texte . "\n\n" . $ajout);

        return [
            'text' => $texte,
            'tool_calls' => $resultat->appels,
            'display_type' => $final['display_type'],
            'display_data' => $final['display_data'],
            'deep_link' => $final['deep_link'],
            'erreur' => false,
            'interrompu' => false,
            'modele' => $resultat->modele,
            'parties' => $fil->toArray(),
            'trace' => $resultat->trace,
            'suites' => $affichage->suites(),
            'consommation' => $consommation,
            'palier' => $palier,
        ];
    }

    /**
     * Budget du mois épuisé au-delà du seuil de pause : aucun appel au modèle,
     * une réponse qui dit pourquoi et jusqu'à quand.
     */
    private function reponseEnPause(UiMessageStream $ui): array
    {
        $reprise = now()->addMonthNoOverflow()->startOfMonth()->locale('fr')->isoFormat('D MMMM');
        $texte = "Je me repose : le budget d'intelligence artificielle prévu ce mois-ci pour l'école est épuisé. "
            . "Je reprends le {$reprise}. En attendant, toutes les pages de KLASSCI restent accessibles, "
            . "et l'équipe KLASSCI Care peut relever ce budget si l'école le demande.";
        $ui->text($texte);

        return [
            'text' => $texte, 'tool_calls' => [], 'display_type' => 'text', 'display_data' => null,
            'deep_link' => null, 'erreur' => false, 'interrompu' => false, 'modele' => null,
            'parties' => [['type' => 'texte', 'texte' => $texte]], 'trace' => [], 'suites' => [],
            'consommation' => [], 'palier' => null,
        ];
    }

    /**
     * Titre court de conversation, par le premier modèle disponible, sans outil.
     */
    public function genererTitre(string $message, ?int $userId = null, ?int $conversationId = null): ?string
    {
        // Un titre ne demande aucun raisonnement : palier le moins cher d'abord.
        $decision = $this->routeur->decider('', null);
        if ($decision->pause) {
            return null;
        }
        $candidats = $decision->candidats ?: $this->registre->candidats();
        $compteur = new Compteur();
        $titre = null;
        foreach (array_slice($candidats, 0, 2) as $modele) {
            $requete = new RequeteModele(
                systeme: 'Génère un titre court (40 caractères au plus) pour cette conversation. Réponds uniquement par le titre, sans guillemets ni ponctuation finale.',
                messages: [['role' => 'user', 'texte' => $message]],
                maxTokens: 40,
                temperature: 0.2,
            );

            $texte = '';
            $erreur = false;
            $usage = [];
            $fournisseur = app(config('assistant.adaptateurs.' . $modele->adaptateur));
            foreach ($fournisseur->diffuser($requete, $modele, fn () => false) as $evenement) {
                if ($evenement->type === EvenementModele::TEXTE) {
                    $texte .= $evenement->donnees['delta'];
                } elseif ($evenement->type === EvenementModele::USAGE) {
                    $usage = $evenement->donnees;
                } elseif ($evenement->type === EvenementModele::ERREUR) {
                    $erreur = true;
                    break;
                }
            }
            // Un appel est facturé même raté ; il est compté, marqué en échec s'il a échoué.
            // Sans usage rapporté, les jetons sont estimés (4 caractères par jeton) plutôt
            // que comptés à zéro : un coût nul mentirait sur un appel bel et bien facturé.
            $compteur->ajouter(
                $modele,
                (int) ($usage['entree'] ?? (int) ceil(mb_strlen($requete->systeme . $message, 'UTF-8') / 4)),
                (int) ($usage['sortie'] ?? (int) ceil(mb_strlen($texte, 'UTF-8') / 4)),
                (int) ($usage['cache'] ?? 0),
                $usage['cout'] ?? null,
                0,
                $erreur || trim($texte) === ''
            );

            if (!$erreur && trim($texte) !== '') {
                $titre = mb_substr(trim($texte, " \t\n\r\"'"), 0, 40, 'UTF-8');
                break;
            }
        }

        $this->journal->enregistrer($compteur, $userId, $conversationId, 'titre', null, $titre === null ? 'erreur' : 'ok');

        return $titre;
    }

    /** Un échange = une ligne de journal, sans le contenu des messages. */
    private function journaliser(ResultatBoucle $r, ChatbotConversation $conversation, $user, Decision $decision): void
    {
        $contexte = [
            'palier' => $decision->palier,
            'routage' => $decision->raison,
            'statut' => $r->statut,
            'fournisseur' => $r->fournisseur,
            'modele' => $r->modele,
            'essais_en_echec' => $r->essais,
            'latence_ms' => $r->latenceMs,
            'tokens_entree' => $r->tokensEntree,
            'tokens_sortie' => $r->tokensSortie,
            'tours' => $r->tours,
            'outils' => array_column($r->appels, 'tool'),
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
        ];

        $r->estErreur()
            ? Log::warning('assistant.echange', $contexte)
            : Log::info('assistant.echange', $contexte);
    }
}
