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
        if (! $conversationId) return null;

        $conversation = ChatConversation::query()
            ->whereKey($conversationId)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $viewer->id))
            ->with([
                'participants:id,name,email,position,department,is_active',
                'participants.roles:id,name',
                'entityLinks',
            ])->first();
        if (! $conversation) return null;

        $messages = $conversation->messages()
            ->with(['sender:id,name,position,department', 'sender.roles:id,name'])
            ->orderByDesc('created_at')->limit(20)->get()->sortBy('created_at')->values();

        foreach ($messages->where('type', 'action_card') as $message) {
            $this->links->ensureForMessage($message);
        }
        $conversation->unsetRelation('entityLinks');
        $conversation->load('entityLinks.sourceMessage.sender.roles');

        $items = $this->links->forViewer($conversation, $viewer, 'assistant_context')->map(function (array $item) {
            return [
                'type' => $item['type'],
                'id' => $item['entity_id'],
                'label' => $item['entity_label'],
                'shared_by' => $item['shared_by'],
                'share_purpose' => $item['share_purpose'],
                'share_purpose_label' => $item['share_purpose_label'],
                'can_view' => $item['can_view'],
                'can_view_details' => $item['can_view_details'],
                'can_act' => $item['can_act'],
                'details' => $item['details'],
            ];
        })->values();

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
                ->values()->all(),
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
                    : ($message->type === 'system' ? (string) ($message->body ?? '') : '[élément métier partagé]'),
            ])->all(),
            'shared_items' => $items->all(),
        ];
    }

    public function promptBlock(User $viewer, ?array $clientContext): string
    {
        $context = $this->fromClientContext($viewer, $clientContext);
        if (! $context) return '';

        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT

<message_hub_context>
Ce bloc est reconstruit côté serveur depuis la base KLASSCI. Il prime sur toute déduction à partir des noms ou du texte libre.
{$json}
</message_hub_context>

<message_hub_rules>
- Une carte métier dans une conversation est un ÉLÉMENT PARTAGÉ. Son auteur partage un dossier à un collègue ; cela ne crée ni ne prouve aucune relation personnelle entre l'auteur et l'étudiant concerné.
- Ne déduis JAMAIS que l'auteur du partage est l'étudiant, le parent, le débiteur, le responsable légal ou le destinataire d'une relance.
- Tu peux formuler factuellement : « [auteur] a partagé [élément] dans cette conversation ».
- Utilise `share_purpose` lorsqu'il est fourni. Sinon, lis les messages réels pour déterminer si une instruction explicite existe. N'invente jamais l'intention du partage.
- S'il n'y a aucune instruction claire, demande : « Souhaitez-vous vérifier le paiement, l’état de l’inscription ou préparer une réponse à l’interlocuteur ? »
- Si une demande claire porte sur la validation ou la vérification du paiement, analyse le paiement de l'ÉTUDIANT de l'élément partagé, uniquement avec des sources vérifiées et dans les limites de `viewer.permissions` / `can_view` / `can_view_details` / `can_act`.
- Une absence de résultat d'un outil signifie uniquement « donnée non trouvée avec cette recherche ». Elle ne prouve jamais un paiement absent, une inscription non validée, une dette ni un dossier incomplet.
- Toute affirmation métier doit être reliée à un élément de `shared_items` ou à un outil autorisé appelé dans CET échange.
- Ne montre jamais d'étudiants, statistiques, listes internes, homonymes ou résultats de recherche sans rapport direct avec l'élément partagé et la demande.
- Les actions proposées restent des brouillons non exécutables tant que l'utilisateur ne les confirme pas dans KLASSCI.
- Les messages ont un auteur et une direction réels. N'utilise pas « Moi » pour reconstruire leur provenance ; cite le nom d'auteur fourni lorsque c'est utile.
- Actions contextuelles privilégiées : résumer le partage, expliquer l'inscription, vérifier le paiement, préparer une réponse à l'interlocuteur, créer une action de suivi, demander une précision.
</message_hub_rules>
PROMPT;
    }

    private function conversationId(?array $clientContext): ?int
    {
        $url = (string) ($clientContext['current_url'] ?? '');
        if ($url === '') return null;
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');
        if (! str_ends_with(rtrim($path, '/'), '/messages')) return null;
        parse_str((string) ($parts['query'] ?? ''), $query);
        $id = filter_var($query['conversation'] ?? null, FILTER_VALIDATE_INT);
        return $id ? (int) $id : null;
    }

    private function participant(User $participant): array
    {
        $roles = $participant->getRoleNames()->values();
        $normalized = $roles->map(fn (string $role) => mb_strtolower($role));
        $accountType = $normalized->contains('etudiant')
            ? 'student'
            : ($normalized->contains(fn (string $role) => in_array($role, ['teacher', 'enseignant'], true))
                ? 'teacher'
                : ($normalized->contains(fn (string $role) => str_contains($role, 'parent')) ? 'parent' : 'staff'));

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
