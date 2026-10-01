<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Services\AdresseJoignable;
use App\Http\Controllers\Controller;
use App\Mail\Support\LienVerificationCourrielMail;
use App\Services\Verification\MasqueContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

/**
 * Confirmer l'adresse e-mail d'un compte, pour etre averti par e-mail des
 * reponses du support. Rien n'en depend durement : sans adresse confirmee,
 * l'avertissement dans l'application suffit.
 *
 * Le lien porte l'empreinte de l'adresse : si elle change entre l'envoi et le
 * clic, le lien ne confirme rien.
 */
class VerificationCourrielController extends Controller
{
    private const HEURES = 48;

    public function envoyer(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! AdresseJoignable::aVerifier($user)) {
            return response()->json([
                'envoye' => false,
                'deja_verifiee' => AdresseJoignable::estJoignable($user),
                'message' => AdresseJoignable::estJoignable($user)
                    ? 'Votre adresse e-mail est déjà confirmée.'
                    : "Aucune adresse e-mail valide n'est enregistrée sur votre compte.",
            ], 422);
        }

        $lien = URL::temporarySignedRoute('support.courriel.confirmer', now()->addHours(self::HEURES), [
            'id' => $user->getKey(),
            'hash' => sha1((string) $user->email),
        ]);

        try {
            Mail::to($user->email)->send(new LienVerificationCourrielMail((string) $user->name, $lien, self::HEURES));
        } catch (Throwable $e) {
            Log::warning('KLASSCI Care : lien de confirmation d\'adresse non envoyé', ['user_id' => $user->getKey(), 'erreur' => $e->getMessage()]);

            return response()->json(['envoye' => false, 'message' => "Le courriel n'a pas pu partir. Réessayez dans un instant."], 503);
        }

        return response()->json([
            'envoye' => true,
            'email_masque' => MasqueContact::email($user->email),
            'message' => 'Un lien de confirmation vient de partir vers '.MasqueContact::email($user->email).'. Il est valable '.self::HEURES.' heures.',
        ]);
    }

    /**
     * Le lien est controle ici plutot que par le middleware `signed` : un
     * lien expire ou deja depasse doit dire quoi faire, pas rendre un 403.
     */
    public function confirmer(Request $request, int $id, string $hash): RedirectResponse|View
    {
        $user = $request->user();
        $valide = $request->hasValidSignature()
            && (int) $user->getKey() === $id
            && hash_equals(sha1((string) $user->email), $hash);

        if (! $valide) {
            return view('support.courriel.lien-expire', [
                'peutRenvoyer' => AdresseJoignable::aVerifier($user),
                'dejaConfirmee' => AdresseJoignable::estJoignable($user),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route('support.demandes.index')
            ->with('success', 'Votre adresse e-mail est confirmée. Vous recevrez un e-mail quand le support vous répond.');
    }
}
