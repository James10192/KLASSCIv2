<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use App\Services\MailPulse\MailPulseTestNotificationService;
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
}
