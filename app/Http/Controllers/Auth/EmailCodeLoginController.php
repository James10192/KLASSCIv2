<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\Admissions\StudentAccessInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class EmailCodeLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    /** Une adresse familiale peut correspondre à plusieurs enfants/comptes. */
    public function envoyer(Request $request, StudentAccessInvitation $invitations)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim((string) $request->input('email')));

        $ids = User::query()->where('email', $email)->pluck('id')
            ->merge(ESBTPEtudiant::query()
                ->where('email_personnel', $email)
                ->orWhere('email', $email)
                ->orWhereHas('parents', fn ($q) => $q->where('email', $email))
                ->pluck('user_id'))
            ->filter()->unique()->take(6);

        $request->session()->forget('klassci_email_login_ids');
        $delivered = [];

        foreach ($ids as $id) {
            $user = User::find($id);
            if (! $user || ! $user->is_active || ! in_array($email, $invitations->destinataires($user), true)) {
                continue;
            }
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $key = $this->cacheKey($request, $user->id);
            Cache::put($key, [
                'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
                'tries' => 0,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ], now()->addMinutes(5));

            if ($invitations->envoyerCode($user, $code)) {
                $delivered[] = $user->id;
            } else {
                Cache::forget($key);
            }
        }

        if ($delivered !== []) {
            $request->session()->put('klassci_email_login_ids', $delivered);
        }

        return redirect()->route('login', ['mode' => 'code'])
            ->with('status', 'Si cette adresse correspond à un compte KLASSCI, un code de connexion a été adressé à l’étudiant et aux parents associés.');
    }

    public function verifier(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $ids = (array) $request->session()->get('klassci_email_login_ids', []);
        $codeHash = hash_hmac('sha256', (string) $request->input('code'), (string) config('app.key'));

        foreach ($ids as $id) {
            $key = $this->cacheKey($request, (int) $id);
            $challenge = Cache::get($key);
            if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= time()) {
                Cache::forget($key);
                continue;
            }
            $challenge['tries'] = ((int) $challenge['tries']) + 1;
            if ($challenge['tries'] > 5) {
                Cache::forget($key);
                continue;
            }
            if (! hash_equals((string) $challenge['hash'], $codeHash)) {
                Cache::put($key, $challenge, max(1, (int) $challenge['expires_at'] - time()));
                continue;
            }

            $user = User::find((int) $id);
            if (! $user || ! $user->is_active) {
                Cache::forget($key);
                continue;
            }
            foreach ($ids as $relatedId) {
                Cache::forget($this->cacheKey($request, (int) $relatedId));
            }
            $request->session()->forget('klassci_email_login_ids');
            Auth::login($user);
            $request->session()->regenerate();
            \App\Support\ProfilActif::oublier($request);
            // Le middleware de second facteur reste actif pour tous les comptes
            // ayant déjà configuré la double authentification TOTP.
            return \App\Support\ProfilActif::doitChoisir($user)
                ? redirect()->route('profil.choisir')
                : redirect()->intended('/dashboard');
        }

        return back()->withErrors(['code' => 'Code incorrect ou expiré. Demandez un nouveau code.']);
    }

    private function cacheKey(Request $request, int $userId): string
    {
        return 'klassci:email-login:'.hash('sha256', $request->session()->getId().':'.$userId);
    }
}
