<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentChatbotInboundEvent;
use App\Services\ParentChatbot\ParentChatbotInboundSignature;
use App\Services\ParentChatbot\ParentChatbotResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParentChatbotInboundController extends Controller
{
    public function __invoke(
        Request $request,
        ParentChatbotInboundSignature $signature,
        ParentChatbotResponder $responder,
    ): JsonResponse {
        if (! $signature->isValid($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $request->validate([
            'event_id' => ['required', 'string', 'max:100'],
            'sender.phone' => ['required', 'string', 'max:30'],
            'message.text' => ['required', 'string', 'max:500'],
        ]);

        $claim = ParentChatbotInboundEvent::claim(
            $payload['event_id'],
            hash('sha256', $request->getContent()),
        );

        if ($claim->hasPayloadConflict()) {
            return response()->json(['message' => 'Conflict.'], 409);
        }

        if ($claim->isBusy()) {
            return response()
                ->json(['accepted' => false], 503)
                ->header('Retry-After', (string) $claim->retryAfterSeconds());
        }

        if ($claim->isProcessedDuplicate()) {
            return response()->json(['accepted' => true, 'duplicate' => true], 202);
        }

        $event = $claim->event;
        $token = (string) $event->processing_token;

        try {
            try {
                $response = $responder->prepareInboundResponse(
                    $event,
                    $token,
                    $payload['sender']['phone'],
                    $payload['message']['text'],
                );
            } catch (\UnexpectedValueException $exception) {
                if (! $event->discardRecordedResponse($token)) {
                    throw new \RuntimeException('The invalid parent chatbot response could not be discarded.', previous: $exception);
                }

                $event = $event->fresh();
                $response = $responder->prepareInboundResponse(
                    $event,
                    $token,
                    $payload['sender']['phone'],
                    $payload['message']['text'],
                );
            }
            if (! isset($response['idempotency_key'])) {
                $idempotencyKey = 'klassci-parent-inbound-'.$event->source_event_id;
                if (! $event->recordResponse(
                    $token,
                    $response['phone'],
                    $response['intent'],
                    $response['outcome'],
                    $response['reply'],
                    $idempotencyKey,
                    $response['should_dispatch'],
                    $response['disclosure'] ?? null,
                    $response['authorization_claim'] ?? null,
                )) {
                    Log::error('Parent chatbot inbound response persistence failed');

                    return response()->json(['accepted' => false], 503);
                }

                $response['idempotency_key'] = $idempotencyKey;
            }

            $outcome = $responder->dispatchRecordedResponse($event, $token);

            if (! $event->complete($token, $outcome)) {
                Log::error('Parent chatbot inbound completion failed');

                return response()->json(['accepted' => false], 503);
            }
        } catch (\Throwable $e) {
            Log::error('Parent chatbot inbound processing failed', ['exception' => $e]);
            $event->release($token);

            return response()->json(['accepted' => false], 503);
        }

        return response()->json(['accepted' => true], 202);
    }
}
