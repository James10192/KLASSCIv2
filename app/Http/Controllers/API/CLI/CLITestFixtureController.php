<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
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
}
