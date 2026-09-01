<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Services\MailPulse\MailPulseTestNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunicationMailPulseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:mailpulse.view')->only('index');
        $this->middleware('permission:mailpulse.send')->only('test');
    }

    public function index(): View
    {
        $apiConfigured = Setting::query()->where('key', 'mailpulse_api_key')->whereNotNull('value')->where('value', '!=', '')->exists()
            || trim((string) config('services.mailpulse.api_key', '')) !== '';

        return view('esbtp.communication.mailpulse', [
            'enabled' => SettingsHelper::get('mailpulse_enabled', '0') === '1',
            'apiConfigured' => $apiConfigured,
        ]);
    }

    public function test(Request $request, MailPulseTestNotificationService $service): JsonResponse
    {
        $payload = $request->validate([
            'event' => ['required', 'string', 'in:payment_received,payment_submitted,payment_rejected,absence_reported,grade_published,fee_reminder,registration_confirmed,re_registration_confirmed,bulletin_published,low_grades_alert,low_attendance_alert'],
            'channel' => ['required', 'string', 'in:email,whatsapp,both'],
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
                'message' => 'Configuration de test MailPulse incomplète.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($result, $result['ok'] ? 200 : 502);
    }
}
