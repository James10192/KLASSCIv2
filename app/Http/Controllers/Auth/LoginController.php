<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
// Commentons cette ligne car le trait n'est pas trouvé
// use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Helpers\InstallationHelper;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    // Commentons cette ligne car le trait n'est pas trouvé
    // use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Vérifier si l'application est installée
        $installationStatus = InstallationHelper::getInstallationStatus();
        $hasAdminUser = $installationStatus['has_admin_user'];

        // Journaliser l'état pour le débogage
        \Log::info("LoginController: Installation status: " .
                  ($installationStatus['installed'] ? "Installed" : "Not installed") .
                  ", Match: {$installationStatus['match_percentage']}%, Admin user: " .
                  ($hasAdminUser ? "Yes" : "No"));

        // Si l'application n'est pas installée du tout, rediriger vers l'installation
        if (!$installationStatus['installed']) {
            \Log::info("LoginController: Application not installed, redirecting to installation");
            return redirect()->route('install.index');
        }

        // Vérifier s'il existe au moins un utilisateur admin
        if (!$hasAdminUser) {
            \Log::info("LoginController: No admin user found, redirecting to installation");
            return redirect()->route('install.index');
        }

        // Même si les migrations ne correspondent pas à 100%, permettre l'accès à la page de connexion
        return view('auth.login');
    }

    /**
     * Gère une demande de connexion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Si la tentative de connexion échoue, nous augmenterons le nombre de tentatives
        // et redirigerons l'utilisateur vers le formulaire de connexion. Bien sûr, lorsque cela
        // l'utilisateur dépasse leur nombre maximum de tentatives, il sera bloqué.
        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // Si la tentative de connexion a échoué, nous augmenterons le nombre de tentatives
        // pour connecter l'utilisateur et rediriger l'utilisateur vers le formulaire de connexion.
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Valide la demande de connexion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Tente de connecter l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::attempt(
            $this->credentials($request), $request->filled('remember')
        );
    }

    /**
     * Obtient les informations d'identification nécessaires pour la tentative de connexion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        return [
            $field => $request->username,
            'password' => $request->password,
        ];
    }

    /**
     * Envoie la réponse après que l'utilisateur se soit connecté.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Obtient le chemin de redirection après la connexion.
     *
     * @return string
     */
    protected function redirectPath()
    {
        return $this->redirectTo;
    }

    /**
     * Envoie la réponse après que la tentative de connexion de l'utilisateur ait échoué.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'username' => [trans('auth.failed')],
        ]);
    }

    /**
     * Déconnecte l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Vérifie si l'application est installée
     */
    private function isInstalled()
    {
        return InstallationHelper::isInstalled();
    }
}
