<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ProfilActif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ChoixProfilController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! ProfilActif::doitChoisir($user)) {
            return redirect()->route('dashboard');
        }

        $roles = $user->roles->sortBy('name')->map(static fn ($role) => [
            'name' => $role->name,
            'label' => $role->label_fr ?: ucfirst($role->name),
        ])->values()->all();

        return view('auth.choix-profil', [
            'roles' => $roles,
            'profilActif' => ProfilActif::courant($request),
        ]);
    }

    public function activer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(ProfilActif::disponibles($request->user()))],
        ]);

        ProfilActif::choisir($request, $validated['role']);

        return redirect()->intended(route('dashboard'));
    }
}
