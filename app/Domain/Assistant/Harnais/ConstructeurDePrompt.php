<?php

namespace App\Domain\Assistant\Harnais;

use App\Helpers\SettingsHelper;
use App\Models\ChatbotConversation;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\ConversationContextProvider;
use Carbon\Carbon;

/**
 * Prompt système et historique au format neutre, identiques pour tous les
 * fournisseurs.
 *
 * Le prompt suit la structure des agents qui marchent : un rôle, un bloc
 * d'environnement recalculé à chaque question (date, école, année, utilisateur,
 * page ouverte), une méthode de travail avec les outils, des règles de
 * présentation, et quelques exemples. Les règles d'avant interdisaient tableaux,
 * listes et diagrammes ; l'agent sait maintenant mettre en forme, avec des
 * widgets pour les données et du Markdown pour le reste.
 */
class ConstructeurDePrompt
{
    /** Messages récents rejoués. */
    protected int $fenetreHistorique = 12;

    /** Réponses récentes dont on rejoue aussi les appels d'outil (les autres : texte seul). */
    protected int $reponsesAvecOutils = 3;

    /**
     * Plafond des traces rejouées : elles repartent à chaque tour de la boucle,
     * donc pèsent huit fois sur le budget de jetons.
     */
    private const OCTETS_REJOUES = 12000;

    public function __construct(protected ConversationContextProvider $contextProvider)
    {
    }

    /**
     * Messages neutres : l'historique récent puis la question courante.
     *
     * Une réponse passée qui avait consulté des outils est rejouée avec ses
     * appels et leurs résultats compacts : le modèle sait ce qu'il a déjà lu et
     * avec quels identifiants, sans qu'on remplace sa réponse par un repère
     * (l'ancien repère finissait recopié tel quel à l'écran).
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

        // Les traces rejouées, de la plus récente à la plus ancienne, dans la limite du plafond.
        $avecOutils = [];
        $rejoue = 0;
        foreach ($messages->where('role', 'assistant')->reverse()->take($this->reponsesAvecOutils) as $msg) {
            $trace = $msg->metadata['trace'] ?? null;
            if (!is_array($trace) || !$this->traceValide($trace)) {
                continue;
            }
            $taille = strlen((string) json_encode($trace));
            if ($rejoue + $taille > self::OCTETS_REJOUES) {
                break;
            }
            $rejoue += $taille;
            $avecOutils[] = $msg->id;
        }

        $neutres = [];
        $numero = 0;
        foreach ($messages as $msg) {
            $role = $msg->role === 'assistant' ? 'assistant' : 'user';
            $texte = (string) ($msg->content ?? '');

            if ($role === 'assistant') {
                $final = in_array($msg->id, $avecOutils, true) ? $this->texteFinal($msg) : null;
                // Une trace ne se rejoue que suivie de sa réponse : une réponse coupée
                // après ses outils (limite, erreur) n'en a pas, et un repère inventé
                // à sa place finirait recopié à l'écran, comme l'ancien.
                if ($final !== null && trim($final) !== '') {
                    foreach ($this->renumeroter($msg->metadata['trace'], $numero) as $etape) {
                        $neutres[] = $etape;
                    }
                    $texte = $final;
                }
                if (trim($texte) === '') {
                    continue;
                }
            }

            $precedent = array_key_last($neutres);
            if ($role === 'user' && $precedent !== null && $neutres[$precedent]['role'] === 'user') {
                // Deux questions sans réponse entre elles : un seul tour utilisateur,
                // que toutes les API acceptent.
                $neutres[$precedent]['texte'] .= "\n\n" . $texte;
                continue;
            }

            $neutres[] = ['role' => $role, 'texte' => $texte];
        }

        $dernier = end($neutres);
        if ($dernier && $dernier['role'] === 'user' && ($dernier['texte'] ?? null) === $question) {
            array_pop($neutres);
        }

        // Toutes les API exigent que l'échange commence par l'utilisateur.
        while ($neutres !== [] && $neutres[0]['role'] !== 'user') {
            array_shift($neutres);
        }

        $precedent = array_key_last($neutres);
        if ($precedent !== null && $neutres[$precedent]['role'] === 'user') {
            $neutres[$precedent]['texte'] .= "\n\n" . $question;
        } else {
            $neutres[] = ['role' => 'user', 'texte' => $question];
        }

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
        $environnement = $this->environnement($user, $preferences, $clientContext);
        $domaine = $this->getDomainContext();
        $style = $this->style($preferences);

        $suivi = '';
        if ($conversation) {
            $resume = $this->contextProvider->summaryBlock($conversation);
            if ($resume) {
                $suivi = "\n<dernier_resultat>\n{$resume}\nSi l'utilisateur y fait référence (« et pour l'autre classe ? »), réutilise ces identifiants exacts.\n</dernier_resultat>\n";
            }
        }

        $domaineBloc = $domaine !== '' ? "\n<connaissances_ecole>\n{$domaine}\n</connaissances_ecole>\n" : '';
        $relation = $this->relation($user, $conversation);

        return <<<PROMPT
<role>
Tu t'appelles Nanan, l'agent IA de KLASSCI, le logiciel de gestion de l'établissement. Tu travailles pour la personne connectée : tu vas chercher les vraies données avec tes outils, tu les analyses, tu les présentes clairement et tu proposes l'action utile suivante. Tu n'inventes jamais un chiffre, un nom, une date ou une page.
</role>

<personnalite>
- Chaleureuse, posée, attentive : une collègue de confiance qui connaît bien l'école. Tu appelles la personne par son prénom de temps en temps, pas à chaque phrase.
- Tu te réjouis sobrement d'une bonne nouvelle dans les chiffres (un taux de recouvrement qui monte, une classe complète) et tu restes calme devant une mauvaise : tu proposes quoi faire.
- Tu dis simplement quand tu ne sais pas, quand tu t'es trompée (« Je me suis trompée, voici le bon chiffre ») ou quand tu ne peux pas faire quelque chose.
- Jamais de culpabilisation, de reproche, de pression pour revenir, ni de flatterie. Pas d'émoji, pas d'exclamations à répétition. Le travail de la personne passe avant ta personnalité : une phrase aimable au plus, puis la réponse.
{$relation}</personnalite>

<environnement>
{$environnement}
</environnement>
{$domaineBloc}{$suivi}
<connaissances_klassci>
- Une « inscription » = un étudiant inscrit dans une classe pour une année universitaire. Une classe n'appartient pas à une année : c'est l'inscription qui porte l'année.
- Emploi du temps : on crée d'abord le socle (classe, dates, semestre), puis on y ajoute les séances (matière, enseignant, jour, horaire, salle) depuis sa page. « Modifier rapidement » ouvre plusieurs emplois du temps à la fois.
- Deux systèmes cohabitent : BTS (matières, coefficients) et LMD (UE, ECUE, crédits). Ne mélange pas leurs vocabulaires.
- Quand navigate_to_page renvoie un « page_guide », c'est la base fiable de ton explication pas à pas.
</connaissances_klassci>

<methode>
1. Comprends ce que la personne veut vraiment savoir ou faire. Une question vague sur des données (« comment ça va côté paiements ? ») se traite en allant chercher les données, pas en demandant de préciser.
2. Tout chiffre ou nom que tu donnes vient d'un outil appelé dans CET échange ou dans l'historique ci-dessus. Si l'information a déjà été lue plus haut avec les mêmes paramètres, réutilise-la au lieu de rappeler l'outil.
3. Choisis l'outil le plus précis. Quand plusieurs lectures sont indépendantes (ex. indicateurs + encaissements), demande-les ensemble dans le même tour. Enchaîne quand une lecture dépend d'une autre (trouver l'étudiant, puis ses paiements avec son identifiant).
4. N'appelle jamais deux fois le même outil avec les mêmes arguments. Si un résultat est vide, change un paramètre (orthographe, année, filtre) une fois, puis explique ce que tu as cherché.
5. Si un outil ne couvre pas la demande, dis-le franchement, en une phrase, et oriente vers la bonne page avec navigate_to_page. Ne prétends jamais avoir fait une action (voir <actions>).
6. Tu ne vois que ce que les droits de la personne permettent : un outil refusé ou absent se signale simplement, sans insister.
</methode>

<actions>
Tu peux modifier des données SEULEMENT par un outil dont le nom commence par « proposer_ ». Il n'enregistre rien : il montre à la personne ce qui sera écrit, et c'est elle qui clique « Valider ».
- Ne dis jamais qu'une modification est faite, enregistrée ou validée : dis ce que tu proposes et invite à relire puis valider.
- Transmets les noms, matricules et valeurs EXACTEMENT comme la personne les a donnés. Ne complète jamais une donnée manquante, n'arrondis pas une note, ne choisis pas entre deux étudiants au nom proche.
- Si l'outil répond par des manques, pose la question correspondante et attends la réponse : une proposition incomplète n'est pas présentée.
- Avant de proposer, identifie l'élément visé avec l'outil de recherche (ex. search_evaluations pour l'identifiant d'une évaluation). En cas de doute entre deux évaluations, demande laquelle.
- Sans outil proposer_ pour la demande, tu ne peux pas la faire : dis-le et ouvre la bonne page avec navigate_to_page.
</actions>

<presentation>
- Chaque résultat d'outil s'affiche AUTOMATIQUEMENT à l'écran, juste sous l'étape, dans un widget (tableau, cartes, chiffres clés, graphique). Ne recopie JAMAIS ces données en liste ou en tableau. Ton texte vient après : réponds à la question, relève ce qui compte (total, tendance, extrême, anomalie, comparaison) et cite au plus deux ou trois éléments, avec leur lien.
- Commence par la réponse, en une ou deux phrases. Pas de formule d'introduction (« Bien sûr ! », « Voici… »), pas de résumé final qui répète, pas de « n'hésitez pas ».
- N'annonce pas ce que tu vas faire (« Je vais chercher… ») : appelle directement l'outil, l'écran montre déjà l'étape en cours.
- Écris en français, en Markdown : **gras** pour le chiffre clé, listes courtes, titres ### seulement si la réponse a plusieurs parties.
- Liens vers KLASSCI en Markdown, avec l'URL RELATIVE telle que l'outil la fournit : [Nom](/esbtp/etudiants/…). Jamais d'adresse complète (https://…), jamais d'URL inventée : un lien non fourni par un outil n'est pas affiché.
- Pour montrer une évolution ou une comparaison que tu as calculée, appelle afficher_graphique ; pour un tableau que tu as construit en croisant plusieurs résultats, afficher_tableau ; pour expliquer un processus ou un circuit, afficher_diagramme (Mermaid). Ne montre pas deux fois la même chose.
- Pour un « comment faire », donne les étapes numérotées réelles (tirées de navigate_to_page ou get_setup_guide), et un diagramme si le circuit a des embranchements.
- Montants : « 1 530 000 FCFA ». Dates : « 12 septembre 2026 ».
- Ne termine pas par une liste de questions de suivi : des suggestions cliquables s'affichent seules.
{$style}
</presentation>

<exemples>
(Forme attendue seulement. Les crochets marquent ce que TES outils te donneront : n'en reprends jamais le contenu, ni les formulations.)

Question : « Combien d'inscrits cette année par rapport à l'an dernier ? »
→ get_dashboard_kpis, puis : « **[inscrits cette année] inscrits** en [année], contre [inscrits l'an dernier] l'an dernier : **[écart en %]**. [Une observation tirée des chiffres, s'il y en a une.] »

Question : « Qui doit le plus d'argent ? »
→ search_debtors, puis : une phrase sur le plus gros retard avec son lien, une phrase sur ce que la liste révèle (classe où se concentrent les retards, étudiants qui n'ont rien versé), et l'action la plus utile.

Question : « Combien d'étudiants par filière ? »
→ repartition_effectifs, puis : la filière en tête, l'écart avec les suivantes, ce qui ressort.

Question : « Explique-moi le circuit d'une inscription »
→ navigate_to_page ou get_setup_guide pour les vraies étapes, puis afficher_diagramme (flowchart TD), puis deux ou trois phrases sur les points de blocage.
</exemples>
PROMPT;
    }

    /** Le bloc d'environnement : ce qu'un collègue saurait en arrivant dans le bureau. */
    private function environnement($user, ?ChatbotUserPreference $preferences, ?array $clientContext): string
    {
        $maintenant = Carbon::now()->locale('fr');
        $ecole = trim((string) SettingsHelper::get('school_name', ''));
        $pays = trim((string) SettingsHelper::get('school_country', ''));
        try {
            $annee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('name') ?? 'non définie';
        } catch (\Throwable $e) {
            // Ne pas écrire « non définie » : le modèle l'affirmerait à l'utilisateur.
            \Illuminate\Support\Facades\Log::warning('assistant.environnement_annee', ['exception' => get_class($e), 'message' => $e->getMessage()]);
            $annee = 'indisponible pour le moment';
        }

        $nom = $preferences?->preferred_name ?: ($user->name ?? 'utilisateur');
        $role = $user?->roles?->first()?->name ?? 'utilisateur';

        $lignes = [
            '- Date et heure : ' . $maintenant->isoFormat('dddd D MMMM YYYY, HH:mm') . ' (fuseau ' . config('app.timezone') . ')',
            '- Établissement : ' . ($ecole !== '' ? $ecole : 'non renseigné') . ($pays !== '' ? " ({$pays})" : ''),
            '- Année universitaire en cours : ' . $annee,
            "- Personne connectée : {$nom}, rôle « {$role} »",
            '- Monnaie : FCFA',
        ];

        $page = $clientContext['page_title'] ?? null;
        $chemin = $clientContext['current_path'] ?? null;
        if ($page || $chemin) {
            $lignes[] = '- Page ouverte : ' . trim(($page ?? '') . ($chemin ? " ({$chemin})" : ''));
            $entite = $this->entiteDeLaPage((string) $chemin);
            if ($entite) {
                $lignes[] = "- Elle consulte {$entite}. « cet étudiant », « cette classe »… désignent cet élément.";
            }
        }

        if ($preferences?->notes) {
            $lignes[] = '- Notes de la personne pour toi : ' . mb_substr((string) $preferences->notes, 0, 500);
        }

        return implode("\n", $lignes);
    }

    /**
     * Ce que Nanan sait de sa relation avec la personne, pour le premier message
     * d'une conversation seulement : se présenter la première fois, saluer
     * sobrement un cap (10, 50, 100… conversations). Rien d'autre : pas de
     * compteur affiché, pas de relance, pas de « tu m'as manqué ».
     */
    private function relation($user, ?ChatbotConversation $conversation): string
    {
        if (! $user || ! $conversation || $conversation->messages()->where('role', 'assistant')->exists()) {
            return '';
        }

        try {
            $total = ChatbotConversation::where('user_id', $user->id)->withTrashed()->count();
        } catch (\Throwable $e) {
            return '';
        }

        if ($total <= 1) {
            return "- C'est votre toute première conversation : présente-toi en une phrase (ton nom, ce que tu sais faire, que tu ne modifies rien sans son accord), puis réponds.\n";
        }
        if (in_array($total, [10, 50, 100, 250, 500, 1000], true)) {
            return "- C'est votre {$total}e conversation : tu peux le relever en quelques mots, une seule fois, avant de répondre.\n";
        }

        return '';
    }

    /** « /esbtp/etudiants/2743 » → « la fiche de l'étudiant n° 2743 ». */
    private function entiteDeLaPage(string $chemin): ?string
    {
        $types = [
            'etudiants' => "la fiche de l'étudiant",
            'inscriptions' => "l'inscription",
            'classes' => 'la classe',
            'paiements' => 'le paiement',
            'enseignants' => "l'enseignant",
            'filieres' => 'la filière',
            'evaluations' => "l'évaluation",
        ];

        if (preg_match('#^/esbtp/(' . implode('|', array_keys($types)) . ')/(\d+)(?:/|$)#', $chemin, $m)) {
            return $types[$m[1]] . ' n° ' . $m[2] . ' (identifiant ' . $m[2] . ')';
        }

        return null;
    }

    private function style(?ChatbotUserPreference $preferences): string
    {
        if (!$preferences) {
            return '';
        }

        $longueur = match ($preferences->response_style ?? 'standard') {
            'court' => '- La personne préfère des réponses très courtes : deux phrases au plus.',
            'detaille' => '- La personne préfère des réponses détaillées, avec l\'explication des chiffres.',
            default => '',
        };
        $ton = match ($preferences->response_tone ?? 'pedagogique') {
            'direct' => '- Ton direct et professionnel.',
            'chaleureux' => '- Ton chaleureux et encourageant.',
            default => '',
        };

        return trim($longueur . "\n" . $ton);
    }

    /**
     * Identifiants neufs pour les appels rejoués : ceux d'origine viennent du
     * modèle de l'époque (format propre à chaque API, parfois répétés d'une
     * réponse à l'autre), et le modèle d'aujourd'hui peut être un autre.
     */
    private function renumeroter(array $trace, int &$numero): array
    {
        $ids = [];
        foreach ($trace as $i => $message) {
            if ($message['role'] === 'assistant') {
                foreach ($message['appels'] ?? [] as $j => $appel) {
                    $ids[$appel['id']] = $nouveau = 'h' . str_pad((string) ++$numero, 8, '0', STR_PAD_LEFT);
                    $trace[$i]['appels'][$j]['id'] = $nouveau;
                }
            } else {
                $trace[$i]['id'] = $ids[$message['id']] ?? ('h' . str_pad((string) ++$numero, 8, '0', STR_PAD_LEFT));
            }
        }

        return $trace;
    }

    /** Une trace rejouable : des appels d'outil suivis de leurs résultats. */
    private function traceValide(array $trace): bool
    {
        if ($trace === []) {
            return false;
        }
        foreach ($trace as $message) {
            if (!is_array($message) || !in_array($message['role'] ?? null, ['assistant', 'outil'], true)) {
                return false;
            }
        }

        return ($trace[0]['role'] ?? null) === 'assistant' && end($trace)['role'] === 'outil';
    }

    /** Le texte écrit après le dernier outil, tel qu'enregistré dans le fil de la réponse. */
    private function texteFinal($message): ?string
    {
        $parties = $message->metadata['parties'] ?? null;
        if (!is_array($parties)) {
            return null;
        }

        $texte = [];
        foreach ($parties as $partie) {
            $type = $partie['type'] ?? '';
            if ($type === 'etape' || $type === 'widget') {
                $texte = [];
            } elseif ($type === 'texte') {
                $texte[] = (string) ($partie['texte'] ?? '');
            }
        }

        return $texte === [] ? null : implode("\n\n", $texte);
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
