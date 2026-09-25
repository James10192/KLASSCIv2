<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\RechercheRdv;
use App\Support\ListeInfinie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Retrouver un rendez-vous » : le rendez-vous d'une famille, quel que soit
 * le jour. La liste se charge au defilement (ListeInfinie).
 */
class ESBTPRendezVousRechercheController extends Controller
{
    public function index(Request $request, RechercheRdv $recherche, AccueilRdv $accueil): View|JsonResponse
    {
        $filtres = $recherche->filtres($request);
        $reservations = $recherche->requete($filtres)->paginate(25)->withQueryString();

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse(
                $reservations,
                fn (ESBTPRdvReservation $resa) => view('esbtp.rendez-vous.recherche._ligne', ['resa' => $resa, 'accueil' => $accueil])->render(),
            );
        }

        return view('esbtp.rendez-vous.recherche.index', compact('reservations', 'filtres', 'accueil'));
    }
}
