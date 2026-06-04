<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\InstallationHelper;

class EnsureInstalled
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
        // Vérifier si l'application est installée
        $installationStatus = InstallationHelper::getInstallationStatus();
        $installed = $installationStatus['installed'];
        $hasAdminUser = $installationStatus['has_admin_user'];
        
        // Si un administrateur existe, considérer l'application comme installée
        if ($hasAdminUser) {
            $installed = true;
        }
        
        // Journaliser l'état de l'installation pour le débogage
        \Log::info("Middleware EnsureInstalled - Installation status: " . ($installed ? 'Installed' : 'Not installed') . 
                  ", Admin user: " . ($hasAdminUser ? 'Yes' : 'No') . 
                  ", Route: " . $request->path());
        
        // Si l'application n'est pas installée ou s'il n'y a pas d'utilisateur admin, 
        // rediriger vers l'installation
        if (!$installed || !$hasAdminUser) {
            \Log::info("Redirecting to installation page from EnsureInstalled middleware");
            return redirect()->route('install.index');
        }
        
        return $next($request);
    }
} 
