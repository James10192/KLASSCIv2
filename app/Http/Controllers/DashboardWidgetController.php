<?php

namespace App\Http\Controllers;

use App\Services\DashboardWidgetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Lot 9 — Dashboard widgets configurables.
 *
 * Gère :
 * - L'affichage du dashboard widget-based (universel, basé sur permissions)
 * - La page/modal de configuration personnelle des widgets
 * - La sauvegarde et le reset de la préférence utilisateur
 *
 * Premier consommateur : les rôles custom (Lot 8) qui n'ont pas de dashboard
 * dédié hard-codé. Reste accessible aux autres rôles via /dashboard/widgets.
 */
class DashboardWidgetController extends Controller
{
    public function __construct(private DashboardWidgetRegistry $registry)
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard widget-based universel.
     */
    public function index(): View
    {
        $user = Auth::user();
        $widgets = $this->registry->userLayout($user);
        $hasCustomLayout = $user->dashboard_widgets !== null;
        $availableGrouped = $this->registry->availableGroupedFor($user);
        $activeKeys = $widgets->pluck('key')->all();

        return view('dashboard.widget-based', [
            'user' => $user,
            'widgets' => $widgets,
            'hasCustomLayout' => $hasCustomLayout,
            'availableGrouped' => $availableGrouped,
            'activeKeys' => $activeKeys,
        ]);
    }

    /**
     * Page dédiée de configuration (alternative à la modal).
     */
    public function configure(): View
    {
        $user = Auth::user();
        $availableGrouped = $this->registry->availableGroupedFor($user);
        $widgets = $this->registry->userLayout($user);
        $activeKeys = $widgets->pluck('key')->all();

        return view('dashboard.configure', [
            'user' => $user,
            'availableGrouped' => $availableGrouped,
            'activeKeys' => $activeKeys,
        ]);
    }

    /**
     * Sauvegarde la sélection user.
     *
     * Body JSON ou form attendu : { widgets: ['students.total', 'paiements.pending', ...] }
     * (ordre = ordre d'affichage souhaité).
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'widgets' => ['nullable', 'array'],
            'widgets.*' => ['string', 'max:128'],
        ]);

        $user = Auth::user();
        $orderedKeys = $request->input('widgets', []);
        $payload = $this->registry->buildLayoutPayload($user, $orderedKeys);

        $user->dashboard_widgets = $payload;
        $user->save();

        return redirect()
            ->route('dashboard.widgets.index')
            ->with('status', 'Tableau de bord mis à jour.');
    }

    /**
     * Réinitialise la préférence aux défauts du rôle.
     */
    public function reset(): RedirectResponse
    {
        $user = Auth::user();
        $user->dashboard_widgets = null;
        $user->save();

        return redirect()
            ->route('dashboard.widgets.index')
            ->with('status', 'Tableau de bord réinitialisé aux paramètres par défaut.');
    }
}
