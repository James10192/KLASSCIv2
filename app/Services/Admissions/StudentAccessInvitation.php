<?php

namespace App\Services\Admissions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Invitations de comptes étudiants : aucun secret temporaire ne transite
 * par la session, l'interface ou un courriel. On réutilise le broker Laravel
 * et son jeton à usage unique, valable selon config/auth.php.
 */
final class StudentAccessInvitation
{
    public function inviter(User $user, string $motif = 'activation'): bool
    {
        if (! $user->email || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $token = Password::broker()->createToken($user);

        try {
            $this->envoyerAvecJeton($user, $token, $motif);

            return true;
        } catch (\Throwable $e) {
            Password::broker()->deleteToken($user);
            Log::warning('Invitation étudiant : livraison impossible', [
                'user_id' => $user->id, 'exception' => $e::class,
            ]);

            return false;
        }
    }

    /** Utilisé aussi par le flux natif « mot de passe oublié ». */
    public function envoyerAvecJeton(User $user, string $token, string $motif = 'reinitialisation'): void
    {
        if (! $user->email || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Aucune adresse e-mail valide sur ce compte.');
        }

        $url = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
        $titre = $motif === 'activation'
            ? 'Bienvenue sur KLASSCI — activez votre espace'
            : 'KLASSCI — choisissez un nouveau mot de passe';

        Mail::send('esbtp.emails.student-access', [
            'nom' => $user->name ?: 'étudiant(e)',
            'identifiant' => $user->username,
            'url' => $url,
            'motif' => $motif,
            'expiration' => (int) config('auth.passwords.users.expire', 60),
        ], function ($message) use ($user, $titre): void {
            $message->to($user->email)->subject($titre);
        });

        Log::info('Lien sécurisé étudiant transmis au transport mail', [
            'user_id' => $user->id, 'motif' => $motif,
        ]);
    }
}
