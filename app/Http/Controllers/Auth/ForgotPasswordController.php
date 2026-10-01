<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
// Commentons cette ligne car le trait n'est pas trouvé
// use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    // Commentons cette ligne car le trait n'est pas trouvé
    // use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Affiche le formulaire de demande de réinitialisation du mot de passe.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Envoie un lien de réinitialisation du mot de passe à l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // Nous enverrons le lien de réinitialisation du mot de passe à cet utilisateur. Une fois que nous
        // l'avons envoyé, nous informerons l'utilisateur que nous avons envoyé un e-mail. Ensuite, ils
        // peuvent utiliser le lien pour réinitialiser leur mot de passe.
        try {
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );
        } catch (TransportExceptionInterface $e) {
            // Le courriel n'est pas parti (messagerie refusée ou plafond atteint) :
            // le dire à l'écran plutôt qu'une page d'erreur, et le garder au journal.
            Log::warning('Réinitialisation du mot de passe : courriel non envoyé', ['erreur' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'email' => ["Le courriel de réinitialisation n'a pas pu partir. Réessayez dans quelques minutes."],
            ]);
        }

        return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Valide l'adresse e-mail pour la demande de réinitialisation du mot de passe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    }

    /**
     * Obtient les informations d'identification nécessaires pour la demande de réinitialisation du mot de passe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only('email');
    }

    /**
     * Obtient le courtier de réinitialisation du mot de passe à utiliser lors de la réinitialisation.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Obtient la réponse pour une demande de réinitialisation de mot de passe réussie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', trans($response));
    }

    /**
     * Obtient la réponse pour une demande de réinitialisation de mot de passe échouée.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        throw ValidationException::withMessages([
            'email' => [trans($response)],
        ]);
    }
} 