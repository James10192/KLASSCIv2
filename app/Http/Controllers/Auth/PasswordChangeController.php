<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\MotDePasseNonGenerique;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
        $this->middleware('force.password.change')->except(['showChangeForm', 'updatePassword']);
    }

    /**
     * Affiche le formulaire de changement de mot de passe
     */
    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    /**
     * Met à jour le mot de passe de l'utilisateur
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        // À la première connexion imposée, l'ancien mot de passe n'est pas
        // requis. Pour une expiration ou un changement volontaire, on exige
        // toujours la connaissance de l'ancien secret.
        $requiresCurrent = ! (bool) $user->must_change_password
            || UserService::isPasswordExpired($user);

        $rules = [
            'password' => ['required', 'confirmed', new MotDePasseNonGenerique,
                Password::min(8)->letters()->numbers()],
        ];
        if ($requiresCurrent) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'][] = 'different:current_password';
        }

        $request->validate($rules, [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Choisissez un mot de passe différent.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
        ]);

        // Mettre à jour le mot de passe (le mutateur setPasswordAttribute hashe automatiquement)
        $user->update([
            'password' => $request->password,
        ]);

        // Marquer que l'utilisateur a changé son mot de passe
        $this->userService->markPasswordChanged($user);

        // Marquer la première connexion si nécessaire
        $this->userService->markFirstLogin($user);

        \Log::info('Mot de passe changé avec succès', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('dashboard')->with('success',
            'Votre mot de passe a été changé avec succès. Bienvenue sur KLASSCI !');
    }
}