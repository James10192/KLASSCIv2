<?php

namespace App\Http\Controllers;

use App\Models\ChatConversationEntityLink;
use App\Models\User;
use App\Services\Messages\ConversationEntityLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageHubEntityLinkController extends Controller
{
    public function __construct(private readonly ConversationEntityLinkService $links)
    {
    }

    public function update(Request $request, ChatConversationEntityLink $link): JsonResponse
    {
        $actor = $request->user();
        $link->loadMissing('conversation.participants');
        abort_unless(
            $link->conversation?->participants->contains('id', $actor->id),
            403,
            'Cette liaison n’appartient pas à une de vos conversations.'
        );

        if ($link->required_view_permission) {
            abort_unless($actor->can($link->required_view_permission), 403, 'Vous ne pouvez pas vérifier ce dossier.');
        }

        $verificationPermission = match ($link->entity_type) {
            'inscription' => 'inscriptions.validate',
            'paiement' => 'paiements.validate',
            default => 'admin.access',
        };
        abort_unless(
            $actor->can('admin.access') || $actor->can($verificationPermission),
            403,
            'La vérification de cette relation nécessite un droit de validation.'
        );

        $data = $request->validate([
            'relation' => ['required', Rule::in(array_values(array_diff(ConversationEntityLinkService::RELATIONS, ['unknown_verify'])))],
            'related_user_id' => 'required|integer|exists:users,id',
        ]);

        $related = $link->conversation->participants->firstWhere('id', (int) $data['related_user_id']);
        abort_unless($related instanceof User, 422, 'La personne choisie n’est pas participante à cette conversation.');

        $verified = $this->links->verify($link, $actor, $data['relation'], $related->id);
        $presented = $this->links->present($verified, $actor, 'link_verified');

        return response()->json([
            'link' => $presented,
            'message' => 'La relation a été vérifiée et journalisée.',
        ]);
    }
}
