<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoTokenVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Receives SSO tokens from the group portal (adminKlassci) and logs the founder in.
 *
 * Security posture:
 * - Rate limit: 10 attempts per IP per minute (brute-force mitigation)
 * - Audit every attempt (success AND failure) in group_portal_sso_logs
 * - Token must be valid HMAC-SHA256 and not expired (2min window)
 * - tenant_code in token must match config('app.tenant_code')
 * - user_email must resolve to an existing user; otherwise 403
 * - redirect_to is whitelisted to app-internal paths only (prevents open-redirect)
 */
class GroupPortalSsoController extends Controller
{
    public function __invoke(Request $request, SsoTokenVerifier $verifier): RedirectResponse
    {
        $ipKey = 'sso-from-group:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, maxAttempts: 10)) {
            $this->log($request, null, null, false, 'rate_limited');
            abort(429, 'Trop de tentatives. Réessayez dans 1 minute.');
        }
        RateLimiter::hit($ipKey, decaySeconds: 60);

        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            $this->log($request, null, null, false, 'missing_token');
            abort(400, 'Token manquant.');
        }

        $payload = $verifier->verify($token);
        if (! $payload) {
            $this->log($request, null, null, false, 'invalid_or_expired_token');
            abort(401, 'Token invalide ou expiré.');
        }

        $expectedTenant = config('app.tenant_code');
        if ($expectedTenant && ($payload['tenant_code'] ?? null) !== $expectedTenant) {
            $this->log($request, $payload['user_email'] ?? null, $payload, false, 'wrong_tenant');
            abort(403, 'Ce token ne concerne pas cet établissement.');
        }

        $user = User::where('email', $payload['user_email'] ?? '')->first();
        if (! $user) {
            $this->log($request, $payload['user_email'] ?? null, $payload, false, 'user_not_found');
            abort(403, 'Utilisateur non provisionné dans cet établissement. Contactez votre administrateur.');
        }

        Auth::login($user);

        $redirectTo = $this->sanitizeRedirect($payload['redirect_to'] ?? '/');

        $this->log($request, $user->email, $payload, true, null, $user->id);

        return redirect($redirectTo);
    }

    /**
     * Only allow app-internal redirects (same-origin, no scheme). Prevents open-redirect
     * exploits where a crafted SSO URL could forward to an external phishing site.
     */
    private function sanitizeRedirect(string $path): string
    {
        if ($path === '' || $path[0] !== '/') {
            return '/';
        }
        // Block protocol-relative URLs like //evil.com
        if (str_starts_with($path, '//')) {
            return '/';
        }
        return $path;
    }

    private function log(Request $request, ?string $email, ?array $payload, bool $success, ?string $errorReason, ?int $userId = null): void
    {
        try {
            DB::table('group_portal_sso_logs')->insert([
                'user_email_requested' => $email ?? 'unknown',
                'issued_by' => $payload['issued_by'] ?? null,
                'group_member_id' => $payload['group_member_id'] ?? null,
                'user_id' => $userId,
                'redirect_to' => $payload['redirect_to'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'success' => $success,
                'error_reason' => $errorReason,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("SSO audit log insert failed: {$e->getMessage()}");
        }
    }
}
