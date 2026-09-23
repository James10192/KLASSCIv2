<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\FamillesAPrevenirRdv;
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
        $jour = $this->jour($request->query('jour'));
        $donnees = $this->accueil->journee($jour) + ['jour' => $jour, 'accueil' => $this->accueil];

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
        return $this->repondre(
            $this->deplacee($request, $reservation) ?? $this->accueil->marquerRecu($reservation, (int) $request->user()->id),
            'Famille reçue.'
        );
    }

    public function annuler(Request $request, ESBTPRdvReservation $reservation): JsonResponse
    {
        return $this->repondre(
            $this->deplacee($request, $reservation) ?? $this->accueil->annulerRecu($reservation),
            'Coche annulée : la famille est de nouveau attendue.'
        );
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

        // `creneau_vu` : le creneau que l'ecran affichait, distinct du creneau cible.
        $vu = filter_var($request->input('creneau_vu'), FILTER_VALIDATE_INT);

        return $this->repondre(
            $this->accueil->reprogrammer($reservation, $creneauId, (int) $request->user()->id, $vu === false ? null : $vu),
            'Rendez-vous reprogrammé. La nouvelle convocation part par e-mail si la famille en a un ; sinon, elle rejoint la liste des familles à prévenir.'
        );
    }

    public function reprogrammerNonVenues(Request $request): JsonResponse
    {
        $jour = $this->jour($request->input('jour'));
        $r = $this->accueil->reprogrammerNonVenues($jour, (int) $request->user()->id);

        return response()->json(['message' => match (true) {
            $r['faites'] === 0 && $r['sans_place'] === 0 => 'Aucune famille non venue à reprogrammer ce jour.',
            $r['sans_place'] > 0 => sprintf('%d famille(s) reprogrammée(s). %d restent faute de créneau libre : générez ou ouvrez des créneaux.', $r['faites'], $r['sans_place']),
            default => sprintf('%d famille(s) reprogrammée(s) sur les prochains créneaux libres. Leurs convocations partent.', $r['faites']),
        }] + $r, $r['sans_place'] > 0 && $r['faites'] === 0 ? 422 : 200);
    }

    public function prevenue(Request $request, ESBTPRdvReservation $reservation, FamillesAPrevenirRdv $familles): JsonResponse
    {
        return $this->repondre($familles->marquerPrevenue($reservation, (int) $request->user()->id), 'Famille notée prévenue par téléphone.');
    }

    public function annulerPrevenue(ESBTPRdvReservation $reservation, FamillesAPrevenirRdv $familles): JsonResponse
    {
        return $this->repondre($familles->annulerPrevenue($reservation), 'Annulé : la famille revient dans la liste à prévenir.');
    }

    /**
     * L'ecran envoie le creneau qu'il affichait. S'il ne correspond plus, un
     * autre poste a reprogramme la famille entre-temps : on refuse plutot que
     * de cocher sur un creneau que l'agent n'a pas sous les yeux.
     */
    private function deplacee(Request $request, ESBTPRdvReservation $reservation): ?string
    {
        $vu = filter_var($request->input('creneau_id'), FILTER_VALIDATE_INT);

        return $vu !== false && $vu !== null && $vu !== (int) $reservation->creneau_id
            ? 'Ce rendez-vous vient d\'être déplacé par un autre poste. La liste est rechargée.'
            : null;
    }

    private function jour(mixed $valeur): Carbon
    {
        return PortailReinscriptionService::interpreterDateIso(is_string($valeur) ? $valeur : '')?->startOfDay() ?? Carbon::today();
    }

    private function repondre(?string $refus, string $succes): JsonResponse
    {
        return $refus === null
            ? response()->json(['message' => $succes])
            : response()->json(['message' => $refus], 422);
    }
}
