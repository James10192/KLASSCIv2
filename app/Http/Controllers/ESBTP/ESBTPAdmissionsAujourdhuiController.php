<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Admissions\JourneeAuGuichet;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Aujourd'hui » : le poste de l'accueil pendant la journee des rendez-vous.
 *
 * Il ne fait que LIRE. Cocher une famille reçue (ou annuler la coche) passe par
 * les routes de l'Accueil du jour (ESBTPRendezVousAccueilController::recu et
 * ::annuler), avec les memes verrous ; l'ecran redemande ensuite ses fragments
 * a index(?fragment=1), sans recharger la page.
 */
class ESBTPAdmissionsAujourdhuiController extends Controller
{
    public function __invoke(Request $request, JourneeAuGuichet $journee): View|JsonResponse
    {
        $donnees = $journee->pour($request->user());

        if ($request->boolean('fragment')) {
            return response()->json([
                'compteurs' => $donnees['compteurs'],
                'kpis' => view('esbtp.admissions.aujourdhui._compteurs', $donnees)->render(),
                'creneaux' => view('esbtp.admissions.aujourdhui._creneaux', $donnees)->render(),
                'guichet' => view('esbtp.admissions.aujourdhui._guichet', $donnees)->render(),
            ]);
        }

        return view('esbtp.admissions.aujourdhui.index', $donnees);
    }
}
