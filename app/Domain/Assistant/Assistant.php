<?php

namespace App\Domain\Assistant;

use App\Domain\Assistant\Affichage\ConstructeurAffichage;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Harnais\FilDeReponse;
use App\Domain\Assistant\Harnais\ResultatBoucle;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Models\ChatbotConversation;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\ConversationContextProvider;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée de l'assistant IA : un échange = prompt + boucle d'agent +
 * mise en forme + journal. Indépendant du fournisseur de modèle.
 *
 *   Harnais\ConstructeurDePrompt    prompt système et historique neutres
 *   Modeles\RegistreDesModeles      modèle par défaut, autorisés, chaîne de repli
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
    ): array {
        $requete = new RequeteModele(
            systeme: $this->prompt->systeme($user, $preferences, $contexteClient, $conversation),
            messages: $this->prompt->messages($conversation, $question),
            outils: $this->catalogue->schemas($user),
            maxTokens: (int) config('assistant.limites.max_tokens', 2048),
            temperature: (float) config('assistant.limites.temperature', 0.2),
        );

        $affichage = new ConstructeurAffichage($this->contextProvider);
        $fil = new FilDeReponse();
        $resultat = $this->boucle->executer(
            $this->registre->candidats($modeleDemande),
            $requete,
            $user,
            $ui,
            function (string $nom, array $args, array $res) use ($affichage, $conversation): ?array {
                $affichage->enregistrer($conversation, $nom, $args, $res);

                return $affichage->widgetPour($nom, $res);
            },
            $fil,
        );

        $this->journaliser($resultat, $conversation, $user);

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
        ];
    }

    /**
     * Titre court de conversation, par le premier modèle disponible, sans outil.
     */
    public function genererTitre(string $message): ?string
    {
        foreach (array_slice($this->registre->candidats(), 0, 2) as $modele) {
            $requete = new RequeteModele(
                systeme: 'Génère un titre court (40 caractères au plus) pour cette conversation. Réponds uniquement par le titre, sans guillemets ni ponctuation finale.',
                messages: [['role' => 'user', 'texte' => $message]],
                maxTokens: 40,
                temperature: 0.2,
            );

            $texte = '';
            $erreur = false;
            $fournisseur = app(config('assistant.adaptateurs.' . $modele->adaptateur));
            foreach ($fournisseur->diffuser($requete, $modele, fn () => false) as $evenement) {
                if ($evenement->type === EvenementModele::TEXTE) {
                    $texte .= $evenement->donnees['delta'];
                } elseif ($evenement->type === EvenementModele::ERREUR) {
                    $erreur = true;
                    break;
                }
            }

            if (!$erreur && trim($texte) !== '') {
                return mb_substr(trim($texte, " \t\n\r\"'"), 0, 40, 'UTF-8');
            }
        }

        return null;
    }

    /** Un échange = une ligne de journal, sans le contenu des messages. */
    private function journaliser(ResultatBoucle $r, ChatbotConversation $conversation, $user): void
    {
        $contexte = [
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
