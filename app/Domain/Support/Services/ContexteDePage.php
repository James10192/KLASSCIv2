<?php

namespace App\Domain\Support\Services;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Le contexte d'une page, tel qu'il accompagne un signalement.
 *
 * Deux moments, une seule liste blanche :
 *  - au RENDU, `courant()` lit la route servie (nom, module, element concerne
 *    dans les parametres de route) et le pose dans la page ;
 *  - a la SOUMISSION, `assainir()` reprend ce que le navigateur renvoie et ne
 *    garde que ce qui se verifie : une route qui existe, un module recalcule
 *    depuis elle, un type d'element connu, des identifiants entiers.
 *
 * Jamais de HTML, de valeur de champ, de chaine de requete, de cookie ni
 * d'en-tete.
 */
final class ContexteDePage
{
    public static function courant(Request $request): array
    {
        $route = $request->route();
        $nom = $route?->getName();

        return array_filter([
            'route_name' => $nom,
            'module' => ModuleDeRoute::pour($nom),
            'entity' => $route ? self::entiteDepuisParametres($route->parameters()) : null,
        ]);
    }

    /** Ce que le navigateur renvoie, ramene a ce qui se verifie. */
    public static function assainir(array $brut, Request $request): array
    {
        $nom = is_string($brut['route_name'] ?? null) && Route::has($brut['route_name']) ? $brut['route_name'] : null;
        $chemin = is_string($brut['url_path'] ?? null) ? strtok($brut['url_path'], '?#') : null;

        $entite = null;
        $types = array_values(config('support.entites_de_route', []));
        if (in_array($brut['entity']['type'] ?? null, $types, true) && ctype_digit((string) ($brut['entity']['id'] ?? ''))) {
            $entite = ['type' => $brut['entity']['type'], 'id' => (int) $brut['entity']['id']];
        }

        $annee = ESBTPAnneeUniversitaire::getCurrent();
        $navigateur = AnalyseNavigateur::depuis($request->userAgent());

        return array_filter([
            'route_name' => $nom,
            'url_path' => $chemin !== false && $chemin !== null ? mb_substr($chemin, 0, 255) : null,
            'module' => ModuleDeRoute::pour($nom),
            'page_title' => self::texte($brut['page_title'] ?? null, 160),
            'entity' => $entite,
            'academic_year_id' => $annee?->getKey(),
            'class_id' => ctype_digit((string) ($brut['class_id'] ?? '')) ? (int) $brut['class_id'] : null,
            'browser' => array_filter($navigateur['browser']) ?: null,
            'os' => $navigateur['os'],
            'device' => $navigateur['device'],
            'viewport' => is_string($brut['viewport'] ?? null) && preg_match('/^\d{2,5}x\d{2,5}$/', $brut['viewport']) ? $brut['viewport'] : null,
            'locale' => self::texte($brut['locale'] ?? null, 12),
            'timezone' => self::texte($brut['timezone'] ?? null, 64),
            'request_ids' => self::identifiantsRequete($brut['request_ids'] ?? []),
            'extras' => self::extras($brut['extras'] ?? []),
        ], fn ($v) => $v !== null && $v !== []);
    }

    private static function entiteDepuisParametres(array $parametres): ?array
    {
        $carte = config('support.entites_de_route', []);

        foreach ($parametres as $nom => $valeur) {
            if (! isset($carte[$nom])) {
                continue;
            }
            $id = $valeur instanceof Model ? $valeur->getKey() : $valeur;
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                return ['type' => $carte[$nom], 'id' => (int) $id];
            }
        }

        return null;
    }

    /** @return list<string>|null */
    private static function identifiantsRequete(mixed $ids): ?array
    {
        if (! is_array($ids)) {
            return null;
        }

        $valides = array_values(array_filter($ids, fn ($id) => is_string($id)
            && preg_match('/^([0-9A-HJKMNP-TV-Z]{26}|[0-9a-f-]{36})$/i', $id)));

        return array_slice(array_unique($valides), -10) ?: null;
    }

    private static function extras(mixed $extras): ?array
    {
        if (! is_array($extras)) {
            return null;
        }
        $gardes = array_intersect_key($extras, array_flip(['semestre', 'etat_affiche', 'composant', 'periode']));

        return array_map(fn ($v) => is_scalar($v) ? mb_substr((string) $v, 0, 64) : null, $gardes) ?: null;
    }

    private static function texte(mixed $valeur, int $max): ?string
    {
        return is_string($valeur) && trim($valeur) !== '' ? mb_substr(trim($valeur), 0, $max) : null;
    }
}
