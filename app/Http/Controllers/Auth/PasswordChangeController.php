<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        // Vérifier le mot de passe actuel EN PREMIER (avant la validation du nouveau)
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Le mot de passe actuel est incorrect. Vérifiez que vous avez bien saisi votre mot de passe actuel (celui utilisé pour vous connecter).'
            ])->withInput($request->only('current_password'));
        }

        // Valider le nouveau mot de passe seulement après confirmation du mot de passe actuel
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->numbers()
            ],
        ], [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'password.required' => 'Le nouveau mot de passe est requis.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas. Vérifiez que les deux champs "Nouveau mot de passe" sont identiques.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.letters' => 'Le nouveau mot de passe doit contenir au moins une lettre.',
            'password.numbers' => 'Le nouveau mot de passe doit contenir au moins un chiffre.',
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
            'Votre mot de passe a été changé avec succès. Bienvenue dans le système ESBTP!');
    }
}