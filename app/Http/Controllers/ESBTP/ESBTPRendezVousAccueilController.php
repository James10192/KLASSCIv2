<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\AccueilRdv;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * L'accueil du jour au guichet. Les actions repondent en JSON ; l'ecran
 * redemande sa liste a index(?fragment=1) apres chacune.
 */
class ESBTPRendezVousAccueilController extends Controller
{
    public function __construct(private readonly AccueilRdv $accueil)
    {
    }

    public function index(Request $request)
    {
        $jour = PortailReinscriptionService::interpreterDateIso((string) $request->query('jour', '')) ?? Carbon::today();
        $donnees = $this->accueil->journee($jour) + ['jour' => $jour->startOfDay(), 'accueil' => $this->accueil];

        if ($request->boolean('fragment')) {
            return response()->json([
                'kpis' => view('esbtp.rendez-vous.accueil.partials._kpis', $donnees)->render(),
                'liste' => view('esbtp.rendez-vous.accueil.partials._liste', $donnees)->render(),
            ]);
        }

        return view('esbtp.rendez-vous.accueil.index', $donnees);
    }

    public function recu(Request $request, ESBTPRdvReservation $reservation): JsonResponse
    {
        return $this->repondre($this->accueil->marquerRecu($reservation, (int) $request->user()->id), 'Famille reçue.');
    }

    public function absent(Request $request, ESBTPRdvReservation $reservation): JsonResponse
    {
        return $this->repondre($this->accueil->marquerAbsent($reservation, (int) $request->user()->id), 'Famille marquée absente.');
    }

    public function annuler(ESBTPRdvReservation $reservation): JsonResponse
    {
        return $this->repondre($this->accueil->annulerMarque($reservation), 'Marque annulée : la famille est de nouveau attendue.');
    }

    public function cloturer(Request $request): JsonResponse
    {
        $jour = PortailReinscriptionService::interpreterDateIso((string) $request->input('jour', '')) ?? Carbon::today();
        $n = $this->accueil->cloturer($jour, (int) $request->user()->id);

        return response()->json(['message' => $n > 0
            ? sprintf('%d famille(s) non venue(s) marquée(s) absente(s). Vous pouvez les reprogrammer.', $n)
            : 'Aucune famille attendue sur un créneau terminé.']);
    }

    public function creneaux(): JsonResponse
    {
        return response()->json(['creneaux' => $this->accueil->creneauxProposes()]);
    }

    public function reprogrammer(Request $request, ESBTPRdvReservation $reservation): JsonResponse
    {
        $creneauId = filter_var($request->input('creneau_id'), FILTER_VALIDATE_INT);
        if ($creneauId === false) {
            return response()->json(['message' => 'Choisissez un créneau.'], 422);
        }

        return $this->repondre(
            $this->accueil->reprogrammer($reservation, $creneauId),
            'Rendez-vous reprogrammé. La nouvelle convocation part par e-mail si la famille en a un.'
        );
    }

    private function repondre(?string $refus, string $succes): JsonResponse
    {
        return $refus === null
            ? response()->json(['message' => $succes])
            : response()->json(['message' => $refus], 422);
    }
}
