<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OpenGraphImageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Image d'apercu servie aux robots des reseaux et des messageries.
 *
 * Publique et sans authentification : un robot ne se connecte pas, et une
 * image derriere un login ne s'afficherait jamais dans un apercu.
 */
class OpenGraphImageController extends Controller
{
    public function __invoke(Request $request, OpenGraphImageService $service): Response
    {
        $image = $service->image();

        if ($image === null) {
            // Pas de logo exploitable : on ne renvoie pas une image vide, qui
            // afficherait un cadre casse. Le robot se rabat sur og:title.
            return response('', 404);
        }

        $etag = '"'.$image['etag'].'"';

        if (trim((string) $request->headers->get('If-None-Match')) === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        return response($image['contenu'], 200, [
            'Content-Type' => $image['mime'],
            // Les robots remettent l'apercu en cache longtemps de leur cote ;
            // l'ETag permet malgre tout de refleter un changement de logo.
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => $etag,
        ]);
    }
}
