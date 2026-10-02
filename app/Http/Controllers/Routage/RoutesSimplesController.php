<?php

namespace App\Http\Controllers\Routage;

use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Les routes d'une ligne qui étaient des closures.
 *
 * `route:cache` sérialise une closure en la signant avec APP_KEY : la clé
 * changée, chaque route en closure lève InvalidSignatureException jusqu'au
 * prochain `route:clear`. Une action de contrôleur, elle, ne dépend d'aucune
 * clé. `RoutesSansClosureTest` refuse leur retour.
 */
class RoutesSimplesController extends Controller
{
    /** GET / — la page marketing vit sur klassci.com : l'instance renvoie au login. */
    public function accueil(): RedirectResponse
    {
        return redirect()->route('login');
    }

    /** GET /csrf-token-refresh — garde vivants les formulaires de connexion laissés ouverts. */
    public function jetonCsrf(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    /** GET /roles */
    public function roles(): View
    {
        $roles = Role::with('permissions')->get();

        return view('admin.roles.index', compact('roles'));
    }

    /** GET /esbtp/classes/{classe}/semestres-lmd */
    public function semestresLmd(ESBTPClasse $classe): JsonResponse
    {
        return response()->json(['semestres' => $classe->getSemestresLMD()]);
    }

    /** Anciennes adresses du module comptabilité, renvoyées vers les paiements. */
    public function paiementHerite(string $id): RedirectResponse
    {
        return redirect()->route('esbtp.paiements.show', $id);
    }

    public function paiementHeriteEdition(string $id): RedirectResponse
    {
        return redirect()->route('esbtp.paiements.edit', $id);
    }

    public function paiementHeriteRecu(string $id): RedirectResponse
    {
        return redirect()->route('esbtp.paiements.recu', $id);
    }

    /** GET /api/user */
    public function utilisateurConnecte(Request $request): mixed
    {
        return $request->user();
    }
}
