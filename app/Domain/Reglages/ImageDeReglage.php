<?php

namespace App\Domain\Reglages;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Poser l'image d'un réglage (logo, favicon, filigrane, signature) : le CLI
 * (`POST /api/cli/settings/{key}/image`) et Nanan (depuis une image jointe).
 *
 * La clé est restreinte à une liste : ce point d'entrée écrit un fichier, il
 * ne doit pas pouvoir viser un réglage arbitraire. Pas de SVG : servi depuis la
 * même origine, il peut porter du script. 2 Mo au plus, comme l'écran.
 */
class ImageDeReglage
{
    public const DOSSIERS = [
        'school_logo' => 'logos',
        'school_favicon' => 'logos',
        'bulletin_logo' => 'logos',
        'header_logo' => 'logos',
        'watermark_image' => 'documents',
        'signature_image' => 'documents',
    ];

    public const OCTETS_MAX = 2048 * 1024;

    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    public function refusCle(string $cle): ?string
    {
        return array_key_exists($cle, self::DOSSIERS)
            ? null
            : "« {$cle} » n'est pas un réglage d'image. Réglages possibles : ".implode(', ', array_keys(self::DOSSIERS)).'.';
    }

    /**
     * Lit les octets comme une image et dit pourquoi ils ne conviennent pas.
     *
     * @return array{extension: ?string, largeur: int, hauteur: int, refus: ?string}
     */
    public function examinerOctets(string $octets): array
    {
        if (strlen($octets) > self::OCTETS_MAX) {
            return ['extension' => null, 'largeur' => 0, 'hauteur' => 0, 'refus' => "L'image dépasse 2 Mo : réduisez-la avant de la joindre."];
        }
        $info = $octets === '' ? false : @getimagesizefromstring($octets);
        $extension = $info ? (self::TYPES[$info[2] ?? 0] ?? null) : null;
        if ($extension === null) {
            return ['extension' => null, 'largeur' => 0, 'hauteur' => 0, 'refus' => "Le fichier n'est pas une image JPEG, PNG, GIF ou WebP lisible."];
        }

        return ['extension' => $extension, 'largeur' => (int) $info[0], 'hauteur' => (int) $info[1], 'refus' => null];
    }

    /**
     * Enregistre le fichier, pointe le réglage dessus, puis retire l'ancien
     * fichier — seulement une fois le nouveau en place.
     *
     * @return array{key: string, value: string, previous_value: ?string, url: string}
     */
    public function poser(string $cle, string $octets, string $extension, ?int $userId): array
    {
        $reglage = Setting::where('key', $cle)->first();
        $ancien = $reglage?->value;

        $chemin = self::DOSSIERS[$cle].'/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($chemin, $octets);

        if ($reglage) {
            // update() sur l'instance : l'événement saved purge le cache.
            $reglage->update(['value' => $chemin, 'updated_by' => $userId]);
        } else {
            Setting::create([
                'key' => $cle,
                'value' => $chemin,
                'type' => 'file',
                'group' => 'establishment',
                'description' => "CLI-provisioned: {$cle}",
                'is_required' => false,
            ]);
        }

        if ($ancien && $ancien !== $chemin && Storage::disk('public')->exists($ancien)) {
            Storage::disk('public')->delete($ancien);
        }

        Log::warning('[reglages] image remplacee a distance', ['key' => $cle, 'avant' => $ancien, 'apres' => $chemin, 'user_id' => $userId]);

        return ['key' => $cle, 'value' => $chemin, 'previous_value' => $ancien, 'url' => asset('storage/'.$chemin)];
    }
}
