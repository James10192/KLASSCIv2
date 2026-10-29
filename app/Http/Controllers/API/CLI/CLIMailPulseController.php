<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use App\Services\MailPulse\MailPulseTestNotificationService;
use App\Services\ParentChatbot\ParentChatbotE2EHarness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CLIMailPulseController extends Controller
{
    public function testNotification(Request $request, MailPulseTestNotificationService $service): JsonResponse
    {
        if (! $request->user()?->tokenCan('cli:admin')) {
            return response()->json([
                'ok' => false,
                'message' => 'Token missing cli:admin ability',
            ], 403);
        }

        $payload = $request->validate([
            'event' => ['required', 'string'],
            'channel' => ['required', 'string'],
            'dryRun' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $service->send(
                $payload['event'],
                $payload['channel'],
                (bool) ($payload['dryRun'] ?? true)
            );
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Payload ou configuration invalide.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($result, $result['ok'] ? 200 : 502);
    }

    public function prepareParentChatbotFixture(Request $request, ParentChatbotE2EHarness $harness): JsonResponse
    {
        if (! $request->user()?->tokenCan('cli:admin')) {
            return $this->forbidden();
        }

        $payload = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30'],
            'student_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = $harness->prepareFixture(
                $payload['label'],
                $payload['phone'],
                (int) $payload['student_id'],
            );
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response()->json($result);
    }

    public function triggerParentChatbotInbound(Request $request, ParentChatbotE2EHarness $harness): JsonResponse
    {
        if (! $request->user()?->tokenCan('cli:admin')) {
            return $this->forbidden();
        }

        $payload = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'event_id' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        try {
            $result = $harness->invokeSignedInbound(
                $payload['label'],
                $payload['event_id'],
                $payload['phone'],
                $payload['message'],
                $request->getSchemeAndHttpHost(),
            );
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response()->json($result, $result['ok'] ? 200 : 502);
    }

    public function cleanupParentChatbotFixture(Request $request, ParentChatbotE2EHarness $harness): JsonResponse
    {
        if (! $request->user()?->tokenCan('cli:admin')) {
            return $this->forbidden();
        }

        $payload = $request->validate([
            'label' => ['required', 'string', 'max:80'],
        ]);

        try {
            $result = $harness->cleanupFixture($payload['label']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response()->json($result);
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'Token missing cli:admin ability',
        ], 403);
    }

    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'Payload ou configuration invalide.',
            'errors' => $e->errors(),
        ], 422);
    }
}
