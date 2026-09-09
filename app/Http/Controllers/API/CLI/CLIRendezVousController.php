<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Http\Controllers\API\BaseApiController;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\GenerateurCreneaux;
use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIRendezVousController extends BaseApiController
{
    public function generer(Request $request, GenerateurCreneaux $generateur): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        try {
            $rapport = $generateur->generer();
        } catch (ReglagesRdvIncomplets $e) {
            return $this->errorResponse($e->getMessage(), ['cles' => $e->cles], 422);
        }

        return $this->successResponse([
            'crees' => $rapport->crees,
            'mis_a_jour' => $rapport->misAJour,
            'fermes' => $rapport->fermes,
            'conserves_occupes' => $rapport->conservesOccupes,
        ], sprintf('%d créneaux créés.', $rapport->crees));
    }

    public function placer(Request $request, AffecteurDossiersRdv $affecteur): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $ecrire = $request->boolean('apply', false);
        $rapport = $affecteur->placer($ecrire);

        return $this->successResponse(
            $rapport + ['applique' => $ecrire],
            $ecrire
                ? sprintf('%d dossiers placés et convoqués, %d sans email, %d sans créneau.', $rapport['places'], $rapport['sans_email'], $rapport['sans_creneau'])
                : sprintf('%d dossiers à placer, %d sans email, %d sans créneau. Rien écrit (apply=true pour placer).', $rapport['places'], $rapport['sans_email'], $rapport['sans_creneau'])
        );
    }

    public function relancer(Request $request, MessagerieRdv $mails): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $ecrire = $request->boolean('apply', false);
        $rapport = $mails->inviterEnAttente($ecrire);

        return $this->successResponse(
            $rapport + ['applique' => $ecrire],
            $ecrire
                ? sprintf('%d mails envoyés, %d sans email, %d déjà relancés.', $rapport['envoyes'], $rapport['sans_email'], $rapport['deja'])
                : sprintf('%d destinataires, %d sans email, %d déjà relancés. Rien envoyé (apply=true pour envoyer).', $rapport['envoyes'], $rapport['sans_email'], $rapport['deja'])
        );
    }
}
