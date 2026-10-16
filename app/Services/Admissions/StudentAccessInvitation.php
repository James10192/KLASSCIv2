<?php

namespace App\Services\Admissions;

use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Services\Emails\AnalyseurEmail;
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
        $token = null;

        try {
            if (! $this->assurerEmailPrincipal($user)) {
                return false;
            }

            // createToken() supprime l'ancien jeton avant d'en insérer un :
            // une table manquante doit rester une erreur de notification.
            $token = Password::broker()->createToken($user);
            $this->envoyerAvecJeton($user, $token, $motif);

            return true;
        } catch (\Throwable $e) {
            if ($token !== null) {
                try {
                    Password::broker()->deleteToken($user);
                } catch (\Throwable $nettoyage) {
                    Log::warning('Invitation étudiant : nettoyage du jeton impossible', [
                        'user_id' => $user->id, 'exception' => $nettoyage::class,
                    ]);
                }
            }

            Log::warning('Invitation étudiant : génération ou livraison impossible', [
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

        $sent = 0;
        foreach ($this->destinataires($user) as $email) {
          try {
            Mail::send('esbtp.emails.student-access', [
            'nom' => $user->name ?: 'étudiant(e)',
            'identifiant' => $user->username,
            'url' => $url,
            'motif' => $motif,
            'expiration' => (int) config('auth.passwords.users.expire', 60),
        ], function ($message) use ($email, $titre): void {
                $message->to($email)->subject($titre);
            });
            $sent++;
          } catch (\Throwable $e) {
            Log::warning('Erreur livraison accès famille', ['user_id' => $user->id, 'exception' => $e::class]);
          }
        }
        if ($sent === 0) {
            throw new \RuntimeException('Le transport mail a refusé tous les destinataires du compte.');
        }

        Log::info('Lien sécurisé étudiant transmis au transport mail', [
            'user_id' => $user->id, 'motif' => $motif,
        ]);
    }
    /** @return list<string> */
    public function destinataires(User $user): array
    {
        $emails = [$user->email];
        foreach (ESBTPEtudiant::with('parents')->where('user_id', $user->id)->get() as $etudiant) {
            $emails[] = $etudiant->email_personnel;
            $emails[] = $etudiant->email;
            foreach ($etudiant->parents as $parent) {
                $emails[] = $parent->email;
            }
        }

        $analyseur = app(AnalyseurEmail::class);
        $valides = [];
        foreach ($emails as $email) {
            $email = mb_strtolower(trim((string) $email));
            if ($email && $analyseur->analyser($email)->joignable()) {
                $valides[$email] = $email;
            }
        }

        return array_values($valides);
    }

    public function envoyerCode(User $user, string $code): bool
    {
        $sent = 0;
        foreach ($this->destinataires($user) as $email) {
            try {
                Mail::send('esbtp.emails.student-login-code', [
                    'nom' => $user->name ?: 'étudiant(e)',
                    'code' => $code,
                    'minutes' => 5,
                ], function ($message) use ($email): void {
                    $message->to($email)->subject('KLASSCI — votre code de connexion');
                });
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Code de connexion : envoi impossible', [
                    'user_id' => $user->id, 'exception' => $e::class,
                ]);
            }
        }

        return $sent > 0;
    }

    /**
     * Le broker Laravel indexe les jetons par users.email. Les comptes
     * historiques sans e-mail peuvent récupérer l'adresse d'un parent lié.
     */
    public function assurerEmailPrincipal(User $user): bool
    {
        if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        foreach ($this->destinataires($user) as $candidate) {
            $email = app(\App\Services\Emails\AdresseDeCompte::class)->pour($candidate);
            if ($email) {
                $user->email = $email;
                $user->save();

                return true;
            }
        }

        return false;
    }


}
