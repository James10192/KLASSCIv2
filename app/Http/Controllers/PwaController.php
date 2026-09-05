<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur PWA : sert le Web App Manifest dynamique, branché par tenant.
 *
 * Le manifest reflète l'identité de l'établissement courant (nom, couleur, icônes)
 * avec un fallback KLASSCI. Servi hors auth pour être disponible avant login.
 */
class PwaController extends Controller
{
    /**
     * Web App Manifest dynamique (application/manifest+json).
     */
    public function manifest(Request $request, MobileProfileResolver $resolver): JsonResponse
    {
        // La route est hors auth, mais la session est la : une personne
        // connectee recoit un manifest qui ouvre directement SON profil.
        // Sans personne connectee, /dashboard suffit (il aiguille au login).
        $connecte = auth()->check();
        $profil = $connecte ? $resolver->resolve(auth()->user()) : null;

        $school = SettingsHelper::getSchoolInfo();
        $schoolName = trim((string) ($school['name'] ?? '')) ?: 'KLASSCI';
        $shortName = trim((string) ($school['acronym'] ?? '')) ?: $schoolName;

        // Couleur de thème : priorité au primaire PDF (défaut #0453cb = bleu KLASSCI),
        // sinon thème, sinon fallback bleu KLASSCI.
        $themeColor = $this->resolvePrimaryColor();

        $manifest = [
            'id' => '/',
            'name' => $schoolName,
            'short_name' => $shortName,
            'description' => 'Espace ' . $schoolName . ' — accédez à vos notes, emploi du temps, bulletins et paiements.',
            'lang' => 'fr',
            'dir' => 'ltr',
            'start_url' => MobileProfileResolver::startUrl($profil),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $themeColor,
            'background_color' => '#ffffff',
            'icons' => [
                [
                    'src' => '/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-maskable-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ];

        // Un manifest calcule pour une personne ne doit pas etre servi a une
        // autre depuis un cache partage : prive et revalide des qu'on est connecte.
        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => $connecte ? 'private, no-cache' : 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Le superAdmin choisit le profil mobile qu'il veut voir pour la session.
     *
     * Il possede toutes les permissions : la cascade du resolver le rangerait
     * toujours dans le premier profil. Cette bascule lui permet de verifier
     * chaque barre d'onglets telle que la verra le personnel. Une valeur vide
     * revient au defaut. Seule exception toleree a l'interdiction de hasRole().
     */
    public function profil(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->hasRole('superAdmin'), 403);

        $valide = $request->validate([
            'profil' => ['nullable', 'string', Rule::in(MobileProfileResolver::PROFILS)],
        ], [
            'profil.in' => 'Ce profil mobile n\'existe pas.',
        ]);

        $profil = $valide['profil'] ?? null;

        if ($profil === null) {
            $request->session()->forget(MobileProfileResolver::CLE_SESSION);
        } else {
            $request->session()->put(MobileProfileResolver::CLE_SESSION, $profil);
        }

        MobileProfileResolver::oublier();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'profil' => $profil ?? MobileProfileResolver::COMPTABLE,
                'start_url' => MobileProfileResolver::startUrl($profil ?? MobileProfileResolver::COMPTABLE),
            ]);
        }

        return redirect()->to(MobileProfileResolver::startUrl($profil ?? MobileProfileResolver::COMPTABLE));
    }

    /**
     * Résout la couleur primaire du tenant pour le thème PWA.
     *
     * Le primaire PDF a un défaut conforme à la charte KLASSCI (#0453cb),
     * contrairement au thème UI (défaut Bootstrap #007bff). On le préfère.
     */
    private function resolvePrimaryColor(): string
    {
        $pdf = SettingsHelper::getPdfSettings();
        $color = trim((string) ($pdf['primary_color'] ?? ''));

        if ($this->isValidHexColor($color)) {
            return $color;
        }

        return '#0453cb';
    }

    private function isValidHexColor(string $value): bool
    {
        return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
    }
}
