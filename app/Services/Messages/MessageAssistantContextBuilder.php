<?php

namespace App\Services\Messages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;

class MessageAssistantContextBuilder
{
    public function __construct(private readonly ConversationEntityLinkService $links)
    {
    }

    public function fromClientContext(User $viewer, ?array $clientContext): ?array
    {
        $conversationId = $this->conversationId($clientContext);
        if (! $conversationId) {
            return null;
        }

        $conversation = ChatConversation::query()
            ->whereKey($conversationId)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $viewer->id))
            ->with([
                'participants:id,name,email,position,department,is_active',
                'participants.roles:id,name',
                'entityLinks',
            ])
            ->first();

        if (! $conversation) {
            return null;
        }

        $messages = $conversation->messages()
            ->with(['sender:id,name,position,department', 'sender.roles:id,name'])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->sortBy('created_at')
            ->values();

        foreach ($messages->where('type', 'action_card') as $message) {
            $this->links->ensureForMessage($message);
        }
        $conversation->unsetRelation('entityLinks');
        $conversation->load('entityLinks');

        $entities = $this->links->forViewer($conversation, $viewer, 'assistant_context')->map(function (array $entity) {
            return [
                'type' => $entity['type'],
                'id' => $entity['entity_id'],
                'label' => $entity['entity_label'],
                'relation' => $entity['relation'],
                'relation_label' => $entity['relation_label'],
                'confidence' => $entity['confidence'],
                'relation_verified' => $entity['relation_verified'],
                'can_view' => $entity['can_view'],
                'can_view_details' => $entity['can_view_details'],
                'sensitive_actions_allowed' => $entity['sensitive_actions_allowed'],
                'details' => $entity['details'],
            ];
        })->values();

        $ambiguous = $entities->contains(fn (array $entity) => ! $entity['relation_verified']);

        return [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title
                    ?: $conversation->participants->where('id', '!=', $viewer->id)->first()?->name
                    ?: 'Conversation',
                'type' => $conversation->type,
            ],
            'viewer' => [
                'id' => $viewer->id,
                'name' => $viewer->name,
                'permissions' => [
                    'inscriptions_view' => $viewer->can('inscriptions.view'),
                    'inscriptions_validate' => $viewer->can('inscriptions.validate'),
                    'paiements_view' => $viewer->can('paiements.view'),
                    'paiements_validate' => $viewer->can('paiements.validate'),
                    'finances_students_view' => $viewer->can('finances.etudiants.voir'),
                ],
            ],
            'participants' => $conversation->participants
                ->where('id', '!=', $viewer->id)
                ->map(fn (User $participant) => $this->participant($participant))
                ->values()
                ->all(),
            'messages' => $messages->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'author_id' => $message->sender_id,
                'author_name' => $message->sender?->name ?? 'Système',
                'author_roles' => $message->sender?->getRoleNames()->values()->all() ?? [],
                'direction' => $message->sender_id === $viewer->id ? 'outgoing' : 'incoming',
                'type' => $message->type,
                'date' => $message->created_at?->toIso8601String(),
                'content' => $message->type === 'text'
                    ? (string) $message->body
                    : ($message->type === 'system' ? (string) ($message->body ?? '') : '[carte métier liée]'),
            ])->all(),
            'linked_entities' => $entities->all(),
            'relationship_guard' => [
                'ambiguous' => $ambiguous,
                'financial_or_enrolment_action_allowed' => ! $ambiguous
                    && $entities->isNotEmpty()
                    && $entities->every(fn (array $entity) => $entity['sensitive_actions_allowed']),
            ],
        ];
    }

    public function promptBlock(User $viewer, ?array $clientContext): string
    {
        $context = $this->fromClientContext($viewer, $clientContext);
        if (! $context) {
            return '';
        }

        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT

<message_hub_context>
Ce bloc est reconstruit côté serveur depuis la base KLASSCI. Il prime sur toute déduction à partir des noms ou du texte libre.
{$json}
</message_hub_context>

<message_hub_rules>
- Ne déduis JAMAIS qu'un participant est l'étudiant, le parent ou le débiteur d'une entité liée à partir de son nom, du titre de la conversation ou du contenu libre des messages.
- Une absence de résultat d'un outil signifie uniquement « donnée non trouvée avec cette recherche ». Elle ne prouve jamais un paiement absent, une inscription non validée, une dette ni un dossier incomplet.
- Toute affirmation métier sur une inscription, un paiement ou un étudiant doit être reliée à une entité du bloc `linked_entities` ou à un outil autorisé appelé dans CET échange.
- Si `relationship_guard.ambiguous` vaut true, réponds explicitement : « Je ne peux pas établir le lien entre cet interlocuteur et ce dossier. Vérifiez la relation avant toute action. » Tu peux décrire séparément l'interlocuteur et le dossier lié si les droits le permettent, mais tu ne peux pas attribuer le dossier à l'interlocuteur.
- Tant que la relation n'est pas vérifiée, ne recommande ni validation d'inscription, ni validation de paiement, ni relance financière, ni demande de paiement à un participant. Propose seulement : vérifier le lien, demander une précision, ou créer une action de vérification.
- Même avec une relation vérifiée, une action financière envers le participant n'est permise que si `sensitive_actions_allowed` vaut true pour l'entité concernée.
- Ne montre jamais des étudiants, statistiques ou résultats de recherche sans rapport direct avec la demande. Si une recherche renvoie des homonymes ou des résultats voisins, ne les expose pas : demande de préciser.
- Les actions proposées restent des brouillons non exécutables tant que l'utilisateur ne les confirme pas via l'interface KLASSCI. Indique le niveau de confiance et les éléments à vérifier.
- Respecte les permissions contenues dans `viewer.permissions` et les champs `can_view` / `can_view_details`. Une donnée masquée n'est pas à rechercher par contournement.
- Les messages du bloc ont un auteur réel et une direction réelle. N'utilise pas « Moi » pour reconstruire leur provenance : cite le nom d'auteur fourni quand c'est nécessaire.
</message_hub_rules>
PROMPT;
    }

    private function conversationId(?array $clientContext): ?int
    {
        $url = (string) ($clientContext['current_url'] ?? '');
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');
        if (! str_ends_with(rtrim($path, '/'), '/messages')) {
            return null;
        }
        parse_str((string) ($parts['query'] ?? ''), $query);
        $id = filter_var($query['conversation'] ?? null, FILTER_VALIDATE_INT);
        return $id ? (int) $id : null;
    }

    private function participant(User $participant): array
    {
        $roles = $participant->getRoleNames()->values();
        $accountType = $roles->contains('etudiant')
            ? 'student'
            : ($roles->contains(fn (string $role) => str_contains(mb_strtolower($role), 'parent')) ? 'parent' : 'staff');

        return [
            'id' => $participant->id,
            'name' => $participant->name,
            'account_type' => $accountType,
            'roles' => $roles->all(),
            'verified_role_label' => $participant->position ?: $roles->first() ?: 'Personnel de l’école',
            'department' => $participant->department,
            'active' => (bool) $participant->is_active,
        ];
    }
}
