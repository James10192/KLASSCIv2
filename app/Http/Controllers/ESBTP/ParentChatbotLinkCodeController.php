<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPParent;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Services\ParentChatbot\ParentChatbotLinkCodeDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParentChatbotLinkCodeController extends Controller
{
    public function store(
        Request $request,
        ESBTPParent $parent,
        ParentChatbotLinkCodeDeliveryService $delivery,
    ): RedirectResponse {
        $this->authorize('manageChatbot', $parent);

        try {
            $issuance = $delivery->issueAndDeliver(
                $parent,
                $request->user()?->id,
                'klassci-parent-link-'.(string) Str::uuid(),
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Parent chatbot link code delivery failed', ['parent_id' => $parent->id, 'actor_id' => $request->user()?->id]);

            return back()->with('error', 'Le code n’a pas pu être transmis.');
        }

        Log::notice('Parent chatbot link code delivery requested', [
            'parent_id' => $parent->id,
            'actor_id' => $request->user()?->id,
            'issuance_id' => $issuance->id,
            'status' => $issuance->status,
        ]);

        return match ($issuance->status) {
            ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED => back()->with('success', 'Le code de liaison a été envoyé au tuteur.'),
            ParentChatbotLinkCodeIssuance::STATUS_PENDING => back()->with('success', 'La transmission du code de liaison est déjà en cours.'),
            default => back()->with('error', 'Le code n’a pas pu être transmis.'),
        };
    }
}
