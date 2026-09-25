<?php

namespace App\Domain\Assistant\Harnais;

use App\Models\ChatbotConversation;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\ConversationContextProvider;

/**
 * Prompt système et historique au format neutre, identiques pour tous les
 * fournisseurs (repris de l'ancien ClaudeAgentService, sans changement de fond).
 */
class ConstructeurDePrompt
{
    protected int $fenetreHistorique = 10;

    public function __construct(protected ConversationContextProvider $contextProvider)
    {
    }

    /**
     * Messages neutres : l'historique récent puis la question courante.
     * Le message utilisateur vient d'être enregistré : il est retiré de
     * l'historique pour ne pas partir deux fois.
     */
    public function messages(ChatbotConversation $conversation, string $question): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($this->fenetreHistorique)
            ->get()
            ->reverse()
            ->values();

        $neutres = [];
        foreach ($messages as $msg) {
            $role = $msg->role === 'assistant' ? 'assistant' : 'user';
            $texte = (string) ($msg->content ?? '');

            // Les réponses qui portaient des données sont remplacées par un repère :
            // le modèle doit rappeler l'outil plutôt que répondre de mémoire.
            if ($role === 'assistant' && $msg->display_type !== 'text') {
                $texte = '[Résultats affichés via widget - appeler l\'outil pour des données fraîches]';
            }

            $neutres[] = ['role' => $role, 'texte' => $texte];
        }

        $dernier = end($neutres);
        if ($dernier && $dernier['role'] === 'user' && $dernier['texte'] === $question) {
            array_pop($neutres);
        }

        // Toutes les API exigent que l'échange commence par l'utilisateur.
        while ($neutres !== [] && $neutres[0]['role'] !== 'user') {
            array_shift($neutres);
        }

        $neutres[] = ['role' => 'user', 'texte' => $question];

        return $neutres;
    }

    /**
     * Construire l'instruction système.
     */
    public function systeme(
        $user,
        ?ChatbotUserPreference $preferences,
        ?array $clientContext,
        ?ChatbotConversation $conversation = null,
    ): string {
        $domainContext = $this->getDomainContext();
        $userName = $preferences?->preferred_name ?? $user->name ?? 'utilisateur';
        $roleName = $user->roles?->first()?->name ?? 'utilisateur';

        $styleInstructions = '';
        if ($preferences) {
            $style = $preferences->response_style ?? 'standard';
            $tone = $preferences->response_tone ?? 'pedagogique';
            $styleInstructions = match ($style) {
                'court' => 'Réponses très concises (1-2 phrases max).',
                'detaille' => 'Réponses détaillées avec explications.',
                default => 'Réponses de longueur standard.',
            };
            $styleInstructions .= ' ' . match ($tone) {
                'direct' => 'Ton direct et professionnel.',
                'chaleureux' => 'Ton chaleureux et encourageant.',
                default => 'Ton pédagogique et bienveillant.',
            };
        }

        $pageContext = '';
        if ($clientContext) {
            $pageName = $clientContext['page_title'] ?? $clientContext['current_path'] ?? null;
            if ($pageName) {
                $pageContext = "\nL'utilisateur est actuellement sur la page : {$pageName}.";
            }
        }

        $notesUtilisateur = '';
        if ($preferences?->notes) {
            $notesUtilisateur = "\nNotes personnelles de l'utilisateur : {$preferences->notes}";
        }

        // Contexte conversationnel : résumé minimal du dernier tool result, pour les questions de suivi.
        // Le modèle doit UTILISER les IDs exacts listés ici (cf. règle 3b) si l'utilisateur fait référence à un élément précédent.
        $previousContext = '';
        if ($conversation) {
            $summary = $this->contextProvider->summaryBlock($conversation);
            if ($summary) {
                $previousContext = "\nContexte du dernier résultat d'outil (pour les questions de suivi) : {$summary}\n"
                    . "→ Si l'utilisateur fait référence implicitement à ces résultats (\"et pour l'autre classe ?\", \"et dans la 2e année ?\"), utilise ces IDs/noms exacts. Sinon, fais un nouvel appel d'outil.";
            }
        }

        // Le pays et le nom viennent des réglages de l'école : une instance au Bénin
        // ne doit pas se présenter comme ivoirienne.
        $ecole = trim((string) \App\Helpers\SettingsHelper::get('school_name', ''));
        $pays = trim((string) \App\Helpers\SettingsHelper::get('school_country', ''));
        $cadre = ($ecole !== '' ? " pour l'établissement « {$ecole} »" : '') . ($pays !== '' ? " ({$pays})" : '');

        return <<<PROMPT
Tu es l'assistant IA de KLASSCI, un système de gestion d'établissement scolaire professionnel (BTS, Licence, Master){$cadre}.

{$domainContext}

L'utilisateur s'appelle {$userName} et a le rôle "{$roleName}".
{$styleInstructions}{$pageContext}{$notesUtilisateur}{$previousContext}

WORKFLOW EMPLOI DU TEMPS :
- La page "Emplois du temps" (index) liste tous les emplois du temps + raccourci pour créer rapidement + bouton "Modifier rapidement" (multi-sélection)
- Créer un emploi du temps = créer le socle (classe, dates, semestre). C'est un conteneur vide.
- Ensuite, on ajoute des séances de cours dessus depuis la vue détaillée (show) : matière, enseignant, jour, horaire, salle
- "Modifier rapidement" = sélectionner plusieurs emplois du temps et les voir/éditer en même temps (vue accordéon)
- Quand l'outil navigate_to_page retourne un champ "page_guide", utilise-le comme base pour ton guide détaillé.

RÈGLES IMPORTANTES :
1. Réponds toujours en français.
2. Utilise les outils (tools) pour récupérer des données réelles. NE JAMAIS inventer de données. NE JAMAIS répondre de mémoire ou à partir de l'historique de conversation — appelle TOUJOURS l'outil même si tu penses déjà connaître la réponse.
3. Si l'utilisateur pose une question sur des données (étudiants, paiements, inscriptions, frais, classes), appelle OBLIGATOIREMENT l'outil approprié, même si une recherche similaire a déjà été faite dans la conversation.
3b. PARAMÈTRES DES OUTILS : utilise TOUJOURS les IDs exacts retournés par les résultats d'un outil précédent (ex: inscription_id=47). Ne confonds JAMAIS la position dans une liste (1er, 2ème...) avec l'ID réel de l'objet. Si tu ne connais pas l'ID exact, fais d'abord une recherche pour le trouver.
4. Pour les salutations ou questions générales, réponds directement sans outil.
5. INTERDIT ABSOLU : après un outil, ne reproduis JAMAIS les données (noms, montants, listes, formules) dans ton texte. Le frontend affiche un widget visuel EN DESSOUS. Écris SEULEMENT 1-2 phrases d'introduction. Exemple CORRECT : "Voici les frais optionnels configurés. Les détails s'affichent ci-dessous." Exemple INTERDIT : "Cantine : - Repas complet : 455 000 FCFA..." ← NE FAIS JAMAIS ÇA.
6. Si un outil retourne 0 résultat, dis-le clairement et suggère des alternatives.
7. Ne génère JAMAIS de tableaux markdown, de listes de données, de code, ou de JSON. Réponds en langage naturel concis.
8. Si l'utilisateur demande comment faire quelque chose (créer une inscription, saisir des notes...), utilise navigate_to_page pour lui donner un lien direct. Pour ces réponses de navigation, fournis un guide détaillé avec les étapes numérotées que l'utilisateur devra suivre sur la page (champs à remplir, options à sélectionner, etc.). Le bouton "Ouvrir la page" s'affiche automatiquement en dessous.
9. NE METS PAS de suggestions de suivi dans ta réponse texte (ex: "Tu veux aussi voir les paiements ?"). Le système les génère automatiquement sous forme de boutons cliquables. Par contre, pour les données (règle 5), ta réponse doit contenir UNIQUEMENT le résumé introductif (1-3 phrases max).
PROMPT;
    }
    protected function getDomainContext(): string
    {
        try {
            $prompt = ChatbotSystemPrompt::active()->default()->highestPriority()->first();
            return $prompt ? trim($prompt->prompt) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
