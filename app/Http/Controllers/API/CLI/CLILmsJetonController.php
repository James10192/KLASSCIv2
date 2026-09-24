<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use App\Support\Lms\JetonServeurLms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Jetons SERVEUR du LMS : creation, liste, revocation. Voir
 * docs/api/LMS_JETON_SERVEUR.md.
 *
 * Le jeton est porte par le compte technique « Service LMS » de l'ecole :
 * aucun role, un mot de passe aleatoire jamais communique — il ne sert qu'a
 * porter les jetons. Le jeton en clair n'est rendu qu'une fois, a la creation.
 */
class CLILmsJetonController extends BaseApiController
{
    /** POST /api/cli/lms/jeton-serveur — body: nom?, droits?[], remplacer? */
    public function creer(Request $request): JsonResponse
    {
        if ($refus = $this->exigerAdmin($request)) {
            return $refus;
        }

        $v = $request->validate([
            'nom' => 'nullable|string|max:60',
            'droits' => 'nullable|array|min:1',
            'droits.*' => 'string|in:'.implode(',', JetonServeurLms::DROITS),
            'remplacer' => 'nullable|boolean',
        ]);

        $compte = $this->compteTechnique();
        $droits = array_values(array_unique($v['droits'] ?? JetonServeurLms::DROITS));

        $revoques = 0;
        if ((bool) ($v['remplacer'] ?? false)) {
            $revoques = $compte->tokens()->delete();
        }

        $nom = 'lms-serveur:'.($v['nom'] ?? now()->format('Y-m-d'));
        $jeton = $compte->createToken($nom, array_merge([JetonServeurLms::SERVEUR], $droits));

        Log::warning('CLI: jeton serveur LMS cree', [
            'jeton_id' => $jeton->accessToken->id,
            'droits' => $droits,
            'jetons_revoques' => $revoques,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'jeton' => $jeton->plainTextToken,
            'jeton_id' => $jeton->accessToken->id,
            'nom' => $nom,
            'droits' => $droits,
            'jetons_revoques' => $revoques,
        ], 'Jeton créé. Il ne sera plus jamais affiché : transmettez-le au LMS par un canal privé.');
    }

    /** GET /api/cli/lms/jetons-serveur — sans les secrets. */
    public function lister(Request $request): JsonResponse
    {
        if ($refus = $this->exigerAdmin($request)) {
            return $refus;
        }

        $compte = User::where('username', JetonServeurLms::COMPTE)->first();

        $jetons = $compte ? $compte->tokens()->orderByDesc('id')->get()->map(fn (PersonalAccessToken $j) => [
            'jeton_id' => $j->id,
            'nom' => $j->name,
            'droits' => array_values(array_diff($j->abilities ?? [], [JetonServeurLms::SERVEUR])),
            'cree_le' => optional($j->created_at)->toIso8601String(),
            'dernier_usage' => optional($j->last_used_at)->toIso8601String(),
        ])->all() : [];

        return $this->successResponse(['jetons' => $jetons], count($jetons).' jeton(s) serveur.');
    }

    /** DELETE /api/cli/lms/jeton-serveur/{id} */
    public function revoquer(Request $request, int $id): JsonResponse
    {
        if ($refus = $this->exigerAdmin($request)) {
            return $refus;
        }

        $compte = User::where('username', JetonServeurLms::COMPTE)->first();
        $supprimes = $compte ? $compte->tokens()->whereKey($id)->delete() : 0;

        if ($supprimes === 0) {
            return $this->errorResponse("Aucun jeton serveur #{$id}.", [], 404);
        }

        Log::warning('CLI: jeton serveur LMS revoque', [
            'jeton_id' => $id,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(['jeton_id' => $id], 'Jeton révoqué : le LMS ne peut plus l’utiliser.');
    }

    private function exigerAdmin(Request $request): ?JsonResponse
    {
        return $request->user()->tokenCan('cli:admin')
            ? null
            : $this->errorResponse('Token missing cli:admin ability', [], 403);
    }

    /** Le compte technique de l'ecole, cree au premier jeton. */
    private function compteTechnique(): User
    {
        $domaine = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'klassci.local';

        return User::firstOrCreate(
            ['username' => JetonServeurLms::COMPTE],
            [
                'name' => 'Service LMS (compte technique)',
                'email' => JetonServeurLms::COMPTE.'@'.$domaine,
                'password' => Hash::make(Str::random(64)),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
    }
}
