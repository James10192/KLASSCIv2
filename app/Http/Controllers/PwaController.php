<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function manifest(Request $request): JsonResponse
    {
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
            'start_url' => '/dashboard',
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

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
