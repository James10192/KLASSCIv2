<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\InstallationHelper;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Toujours autoriser l'accès aux pages marketing publiques et aux assets.
        // (register exclu — route supprimée pour des raisons de sécurité, audit 2026-05-21)
        if ($request->is('/') || $request->is('assets/*') || $request->is('css/*') || $request->is('js/*') ||
            $request->is('logout') ||
            $request->is('changelog') || $request->is('docs') || $request->is('docs/*') ||
            $request->is('api-reference') || $request->is('robots.txt') || $request->is('sitemap.xml')) {
            return $next($request);
        }

        // Vérifier si l'application est installée et si les migrations correspondent
        $installationStatus = InstallationHelper::getInstallationStatus();
        $installed = $installationStatus['installed'];
        $matchPercentage = $installationStatus['match_percentage'] ?? 0;
        $hasAdminUser = InstallationHelper::hasAdminUser();

        // Si un administrateur existe, considérer l'application comme installée
        if ($hasAdminUser) {
            $installed = true;
        }

        // Journaliser l'état de l'installation pour le débogage
        \Log::info("Middleware CheckInstalled - Installation status: " . ($installed ? 'Installed' : 'Not installed') .
                  ", Match: {$matchPercentage}%, Admin user: " . ($hasAdminUser ? 'Yes' : 'No') .
                  ", Route: " . $request->path());

        // Si nous sommes sur les routes d'installation, toujours permettre l'accès
        if ($request->is('install') || $request->is('install/*')) {
            \Log::info("Allowing access to installation routes");
            return $next($request);
        }

        // Si l'application n'est pas installée du tout ou s'il n'y a pas d'utilisateur admin,
        // rediriger vers l'installation
        if (!$installed || !$hasAdminUser) {
            \Log::info("Redirecting to installation page. Installed: " . ($installed ? 'Yes' : 'No') .
                      ", Admin user exists: " . ($hasAdminUser ? 'Yes' : 'No'));

            // Si l'utilisateur essaie d'accéder au login, rediriger vers l'installation
            if ($request->is('login')) {
                return redirect()->route('install.index');
            }

            // Pour les autres routes, uniquement rediriger si ce n'est pas une installation en cours
            if (!session('installation_in_progress', false)) {
                return redirect()->route('install.index');
            }
        }

        // Si nous sommes sur la page de login, autoriser l'accès
        if ($request->is('login')) {
            \Log::info("Allowing access to login page");
            return $next($request);
        }

        // Si l'application est installée mais que les migrations ne correspondent pas à 100%
        // et que nous ne sommes pas déjà sur la page d'accueil
        if ($matchPercentage < 100 && !$request->is('/')) {
            // Afficher un message d'avertissement mais permettre l'accès à l'application
            // au lieu de rediriger en boucle
            \Log::warning("Application installed but migrations don't match 100% ({$matchPercentage}%)");
            // On pourrait ajouter un flash message ici pour informer l'administrateur

            // Continuer la requête sans redirection
            return $next($request);
        }

        return $next($request);
    }
}
