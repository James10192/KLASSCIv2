<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Testing\ReinscriptionStudentFixture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class CLITestFixtureController extends BaseApiController
{
    public function createReinscriptionStudent(
        Request $request,
        ReinscriptionStudentFixture $fixture
    ): JsonResponse {
        if ($request->getHost() !== 'presentation.klassci.com') {
            return $this->errorResponse('Test fixture disponible uniquement sur presentation.', [], 403);
        }
        if (! $request->user()?->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'telephone' => ['required', 'regex:/^0\d{9}$/'],
            'matricule' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'apply' => ['nullable', 'boolean'],
        ]);

        return $this->successResponse(
            $fixture->create(
                $data['email'],
                $data['telephone'],
                (bool) ($data['apply'] ?? false),
                $data['matricule'] ?? null,
            ),
            ($data['apply'] ?? false)
                ? 'Etudiant de test cree sur l annee precedente.'
                : 'Simulation uniquement. Utilisez apply=true pour creer.'
        );
    }

    /**
     * Pont E2E temporaire, presentation uniquement.
     *
     * Le jeton Sanctum n'est jamais transmis. Le client prouve qu'il le connait
     * avec un HMAC court terme construit a partir du hash deja stocke par Sanctum.
     * Le nonce ne peut servir qu'une fois et le lien expire en deux minutes.
     * Les contacts et le matricule font partie de la signature afin qu'aucun
     * parametre de creation ne puisse etre modifie en transit.
     */
    public function signedAgentBridge(
        Request $request,
        ReinscriptionStudentFixture $fixture
    ): JsonResponse {
        if ($request->getHost() !== 'presentation.klassci.com') {
            abort(404);
        }

        $data = $request->validate([
            'token_id' => ['required', 'integer', 'min:1'],
            'ts' => ['required', 'integer'],
            'nonce' => ['required', 'string', 'size:32', 'regex:/^[a-f0-9]+$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'telephone' => ['required', 'regex:/^0\d{9}$/'],
            'matricule' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'sig' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ]);

        $timestamp = (int) $data['ts'];
        if (abs(now()->timestamp - $timestamp) > 120) {
            return response()->json(['message' => 'Lien de test expire.'], 403);
        }

        $token = PersonalAccessToken::query()->find((int) $data['token_id']);
        $abilities = $token?->abilities ?? [];
        if (! $token || (! in_array('*', $abilities, true) && ! in_array('cli:admin', $abilities, true))) {
            return response()->json(['message' => 'Preuve CLI invalide.'], 403);
        }

        $message = implode('|', [
            'presentation',
            'create-reinscription-student',
            $timestamp,
            $data['nonce'],
            mb_strtolower(trim($data['email'])),
            $data['telephone'],
            $data['matricule'] ?? '',
        ]);
        $expected = hash_hmac('sha256', $message, (string) $token->token);
        if (! hash_equals($expected, $data['sig'])) {
            return response()->json(['message' => 'Preuve CLI invalide.'], 403);
        }

        $nonceKey = 'e2e:reinscription-student:' . $data['nonce'];
        if (! Cache::add($nonceKey, true, now()->addMinutes(10))) {
            return response()->json(['message' => 'Preuve deja utilisee.'], 409);
        }

        $result = $fixture->create(
            $data['email'],
            $data['telephone'],
            true,
            $data['matricule'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Etudiant E2E cree sur l annee precedente.',
            'data' => $result,
        ], 201);
    }

    /**
     * Pont mono-usage pour la campagne E2E en cours. Il ne prend aucune donnee
     * utilisateur en entree : il reutilise strictement les destinataires de test
     * deja configures dans l'instance presentation. Cela permet au runner externe
     * de creer un dossier sans transporter le jeton CLI ni les contacts dans Git.
     * A retirer apres la recette.
     */
    public function configuredTestContactBridge(
        Request $request,
        ReinscriptionStudentFixture $fixture,
        MailPulseClient $mailpulse
    ): JsonResponse {
        if ($request->getHost() !== 'presentation.klassci.com') {
            abort(404);
        }

        $email = $this->firstActiveRecipient(
            $mailpulse->getSetting('mailpulse_test_email_recipients', 'mailpulse_test_email_recipients', '')
        );
        if ($email === null) {
            $legacy = trim($mailpulse->getSetting('mailpulse_test_email', 'test_notification_email', ''));
            $email = filter_var($legacy, FILTER_VALIDATE_EMAIL) ? $legacy : null;
        }

        $phone = $this->firstActiveRecipient(
            $mailpulse->getSetting('mailpulse_test_phone_recipients', 'mailpulse_test_phone_recipients', '')
        );
        if ($phone === null) {
            $raw = trim($mailpulse->getSetting('mailpulse_test_phone', 'test_notification_phone', ''));
            if ($raw === '') {
                $raw = trim($mailpulse->getSetting('mailpulse_test_phones', 'test_notification_phones', ''));
            }
            $phone = preg_split('/[\r\n,;]+/', $raw)[0] ?? null;
        }
        $phone = $this->nationalPhone($phone);

        if ($email === null || $phone === null) {
            return response()->json([
                'success' => false,
                'message' => 'Les destinataires de test MailPulse ne sont pas configures avec un email et un mobile ivoirien valides.',
                'email_configured' => $email !== null,
                'phone_configured' => $phone !== null,
            ], 422);
        }

        if (! Cache::add('e2e:reinscription-student:configured:20260929', true, now()->addHour())) {
            return response()->json(['message' => 'Fixture configuree deja executee.'], 409);
        }

        $result = $fixture->create($email, $phone, true, 'E2E260929PATRICK');

        return response()->json([
            'success' => true,
            'message' => 'Etudiant E2E cree avec les destinataires de test configures.',
            'data' => $result,
        ], 201);
    }

    private function firstActiveRecipient(string $json): ?string
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return null;
        }

        foreach ($decoded as $recipient) {
            if (! is_array($recipient) || ($recipient['enabled'] ?? true) === false) {
                continue;
            }
            $value = trim((string) ($recipient['value'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function nationalPhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?: '';
        if (preg_match('/^0\d{9}$/', $digits)) {
            return $digits;
        }
        if (preg_match('/^225(0\d{9})$/', $digits, $m)) {
            return $m[1];
        }

        return null;
    }
}
