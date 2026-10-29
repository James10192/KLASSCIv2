<?php

namespace App\Http\Controllers\API\Public;

use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ce qu'une instance KLASSCI montre d'elle-même à klassci.com.
 *
 * Troisième surface non authentifiée de l'application, et de loin la plus
 * anodine : les deux autres — réinscription et candidature — parlent d'un
 * étudiant et sont donc signées, limitées en débit et ouvertes seulement en
 * saison. Celle-ci ne parle que de l'établissement, et ce qu'elle sert est
 * déjà imprimé en tête de chaque bulletin qu'il distribue. Elle n'est donc pas
 * signée : exiger une signature obligerait le site vitrine à en tirer une pour
 * afficher un logo, et surtout n'empêcherait rien, puisqu'il n'y a rien à
 * protéger. Le débit reste borné par `throttle:api`, contre l'usage abusif du
 * point d'entrée comme hébergeur d'images.
 *
 * Ce que le contrôleur NE fait pas :
 *
 * 1. Il ne dit jamais si l'établissement est client, sur quelle offre, ni
 *    combien d'étudiants il compte. Ces informations vivent chez adminKlassci
 *    et n'ont pas à traverser cette porte.
 * 2. Il ne sert jamais la marque KLASSCI à la place d'un logo absent. Une
 *    école sans logo rend 404, et le site vitrine affiche un monogramme à son
 *    nom — bien mieux qu'un mur de logos KLASSCI identiques présentés comme
 *    « nos établissements ».
 */
class EtablissementPublicController extends Controller
{
    /** Une heure : un logo et des couleurs ne changent que quelques fois par an. */
    private const DUREE_CACHE_SECONDES = 3600;

    public function show(IdentitePublique $identite): JsonResponse
    {
        return response()
            ->json($identite->decrire())
            ->header('Cache-Control', 'public, max-age=' . self::DUREE_CACHE_SECONDES);
    }

    public function logo(): Response|BinaryFileResponse
    {
        $chemin = SettingsHelper::resolveLogoPath();

        if ($chemin === null) {
            // 404 et non une image de repli : c'est la réponse honnête, et
            // c'est elle qui permet au site vitrine de choisir son monogramme.
            return response()->json(['message' => 'Aucun logo configuré.'], 404);
        }

        return response()->file($chemin, [
            'Content-Type' => SettingsHelper::mimeImage($chemin),
            'Cache-Control' => 'public, max-age=' . self::DUREE_CACHE_SECONDES,
            // Le fichier vient d'un dépôt par formulaire, sur le domaine où les
            // utilisateurs de l'école sont connectés. Le formulaire n'accepte
            // que JPEG, PNG et GIF, mais une valeur héritée pourrait encore
            // désigner un SVG — lequel exécute son propre script quand on
            // l'ouvre directement. Ces deux en-têtes le neutralisent : le
            // navigateur ne renifle pas un autre type que celui annoncé, et
            // rien ne s'exécute à l'intérieur du document.
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
