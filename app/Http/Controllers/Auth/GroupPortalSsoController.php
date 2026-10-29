<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SsoFailureReason;
use App\Http\Controllers\Controller;
use App\Models\GroupPortalSsoLog;
use App\Models\User;
use App\Services\SsoTokenVerifier;
use App\Support\SsoClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
 * - Session regenerated after login to prevent session fixation
 * - Referrer-Policy: no-referrer header prevents leaking token to external resources
 *
 * Documented tradeoff: token is in the URL (GET), so it appears in access logs and
 * Referer headers. 2min TTL + nonce uniqueness + HTTPS transport are the mitigations.
 * Future hardening: migrate to POST auto-submit form, or add single-use nonce table.
 */
class GroupPortalSsoController extends Controller
{
    public function __invoke(Request $request, SsoTokenVerifier $verifier): RedirectResponse
    {
        $ipKey = 'sso-from-group:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, maxAttempts: 10)) {
            $this->log($request, null, null, SsoFailureReason::RateLimited);
            abort(429, 'Trop de tentatives. Réessayez dans 1 minute.');
        }
        RateLimiter::hit($ipKey, decaySeconds: 60);

        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            $this->log($request, null, null, SsoFailureReason::MissingToken);
            abort(400, 'Token manquant.');
        }

        $payload = $verifier->verify($token);
        if (! $payload) {
            $this->log($request, null, null, SsoFailureReason::InvalidOrExpiredToken);
            abort(401, 'Token invalide ou expiré.');
        }

        $expectedTenant = config('app.tenant_code');
        if ($expectedTenant && ($payload[SsoClaim::TENANT_CODE] ?? null) !== $expectedTenant) {
            $this->log($request, $payload[SsoClaim::USER_EMAIL] ?? null, $payload, SsoFailureReason::WrongTenant);
            abort(403, 'Ce token ne concerne pas cet établissement.');
        }

        $email = $payload[SsoClaim::USER_EMAIL] ?? '';
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->log($request, $email, $payload, SsoFailureReason::UserNotFound);
            abort(403, 'Accès refusé.');
        }

        // Already logged in as the target user — skip re-auth and just redirect.
        if (Auth::check() && Auth::id() === $user->id) {
            $this->log($request, $email, $payload, null, $user->id);
            return $this->redirectResponse($payload);
        }

        // Logged in as a different user — log out first to avoid mixing sessions.
        if (Auth::check()) {
            Auth::logout();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $this->log($request, $email, $payload, null, $user->id);

        return $this->redirectResponse($payload);
    }

    private function redirectResponse(array $payload): RedirectResponse
    {
        $redirectTo = $this->sanitizeRedirect($payload[SsoClaim::REDIRECT_TO] ?? '/');

        return redirect($redirectTo)->withHeaders([
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Only allow app-internal redirects. Blocks:
     * - protocol-relative URLs (`//evil.com`)
     * - URL-encoded slashes (`/%2f%2fevil.com`)
     * - Windows path confusion (`/\evil.com`)
     * - userinfo confusion (`/@evil.com` — rarely exploitable but cheap to block)
     * - anything that doesn't start with a single `/` followed by safe chars
     * - length cap (defense against pathological inputs)
     */
    private function sanitizeRedirect(string $path): string
    {
        $path = rawurldecode($path);

        if (strlen($path) > 500) {
            return '/';
        }
        if ($path === '' || $path[0] !== '/') {
            return '/';
        }
        if (str_starts_with($path, '//') || str_starts_with($path, '/\\') || str_starts_with($path, '/@')) {
            return '/';
        }
        if (str_contains($path, '\\')) {
            return '/';
        }
        if (! preg_match('~^/[a-zA-Z0-9/_\-?=&.%]*$~', $path)) {
            return '/';
        }

        return $path;
    }

    private function log(Request $request, ?string $email, ?array $payload, ?SsoFailureReason $reason, ?int $userId = null): void
    {
        try {
            GroupPortalSsoLog::create([
                'user_email_requested' => $email ?? 'unknown',
                'issued_by' => $payload[SsoClaim::ISSUED_BY] ?? null,
                'group_member_id' => $payload[SsoClaim::GROUP_MEMBER_ID] ?? null,
                'user_id' => $userId,
                'redirect_to' => $payload[SsoClaim::REDIRECT_TO] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'success' => $reason === null,
                'error_reason' => $reason?->value,
            ]);
        } catch (\Exception $e) {
            Log::warning("SSO audit log insert failed: {$e->getMessage()}");
        }
    }
}
