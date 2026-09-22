<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Http\Controllers\API\BaseApiController;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\EtatChaineRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\GenerateurCreneaux;
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

        $rapport = $affecteur->placer();
        if ($rapport['refus'] !== null) {
            return $this->errorResponse($rapport['refus'], $rapport, 422);
        }

        return $this->successResponse(
            $rapport,
            sprintf('%d dossiers placés, convocation en attente d\'envoi. %d sans email, %d sans créneau.', $rapport['places'], $rapport['sans_email'], $rapport['sans_creneau'])
        );
    }

    public function diagnostic(Request $request, EtatChaineRdv $etat): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        return $this->successResponse([
            'tout_en_ordre' => $etat->toutEstEnOrdre(),
            'maillons' => $etat->maillons(),
            'convocations' => $etat->convocations(),
        ]);
    }

    public function envoyerConvocations(Request $request, FileConvocationsRdv $file): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $rapport = $file->envoyerUnPaquet(50, 25.0);
        if ($rapport['en_cours']) {
            return $this->errorResponse('Un autre envoi est en cours. Rappeler dans une minute.', $rapport, 409);
        }
        if ($rapport['bloque'] !== null) {
            return $this->errorResponse($rapport['bloque'], $rapport, 503);
        }

        return $this->successResponse($rapport, sprintf('%d convocations envoyées, %d restantes.', $rapport['envoyees'], $rapport['restantes']));
    }

    /**
     * Le bouton « Convoquer les non suivies » / « Relancer les echecs » de l'ecran.
     * Remet en attente sans rien envoyer.
     *
     * `limite` borne CE geste, et c'est le seul endroit ou borner tient : une fois
     * en attente, une convocation part au prochain passage de la tache planifiee
     * (5 min), qu'on ait fini de verifier le premier courriel ou non. Remettre 1,
     * verifier sa reception, puis remettre le reste.
     */
    public function remettreConvocations(Request $request, FileConvocationsRdv $file): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $quoi = $request->input('quoi');
        if (! in_array($quoi, ['inconnues', 'echecs'], true)) {
            return $this->errorResponse('Préciser quoi : « inconnues » (réservations d\'avant le suivi) ou « echecs ».', [], 422);
        }

        $limite = $request->input('limite');
        if ($limite !== null && (filter_var($limite, FILTER_VALIDATE_INT) === false || (int) $limite < 1)) {
            return $this->errorResponse('« limite » doit être un entier positif.', [], 422);
        }

        $remises = $file->remettreEnAttente($quoi, $limite === null ? null : (int) $limite);

        return $this->successResponse(
            ['remises' => $remises, 'a_envoyer' => $file->enAttente()],
            sprintf('%d convocations remises en attente. Rien n\'est encore envoyé.', $remises)
        );
    }
}
