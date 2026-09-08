<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\RendezVous\PortailRdvRequest;
use App\Http\Requests\RendezVous\PortailRdvRetrouverRequest;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\CatalogueCreneaux;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\RendezVous\ReferencePublique;
use App\Services\RendezVous\ReservateurRdv;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class RendezVousPortalController extends Controller
{
    public function __construct(
        private readonly CatalogueCreneaux $catalogue,
        private readonly ReservateurRdv $reservateur,
        private readonly ReferencePublique $references,
        private readonly MessagerieRdv $mails,
    ) {
    }

    public function creneaux(): JsonResponse
    {
        return response()->json(['creneaux' => $this->catalogue->publier()]);
    }

    public function reserver(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (! isset($donnees['creneau_id'])) {
            return response()->json(['enregistre' => false, 'code' => 'creneau_manquant'], 422);
        }

        $resultat = $this->reservateur->reserver(
            $donnees['reference'],
            $donnees['date_naissance'],
            (int) $donnees['creneau_id']
        );

        if (! $resultat['ok']) {
            if ($resultat['code'] === 'introuvable') {
                return $this->introuvable($donnees['reference']);
            }

            return response()->json([
                'enregistre' => false,
                'code' => $resultat['code'],
                'creneaux' => $resultat['creneaux'] ?? $this->catalogue->publier(),
            ], 409);
        }

        RateLimiter::clear(ReservateurRdv::seauParReference($donnees['reference'])->cle);
        $this->mails->confirmer($resultat['reservation'], 'confirme');

        return response()->json([
            'enregistre' => true,
            'reservation' => $this->presenter($resultat['reservation']),
        ], 201);
    }

    public function consulter(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $reservation = $this->reservateur->consulter($donnees['reference'], $donnees['date_naissance']);

        if ($reservation === null) {
            return $this->introuvable($donnees['reference']);
        }

        RateLimiter::clear(ReservateurRdv::seauParReference($donnees['reference'])->cle);

        return response()->json([
            'trouve' => true,
            'reservation' => $this->presenter($reservation),
            'peut_modifier' => $this->reservateur->peutModifier($reservation),
        ]);
    }

    public function deplacer(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (! isset($donnees['creneau_id'])) {
            return response()->json(['enregistre' => false, 'code' => 'creneau_manquant'], 422);
        }

        $resultat = $this->reservateur->deplacer(
            $donnees['reference'],
            $donnees['date_naissance'],
            (int) $donnees['creneau_id']
        );

        if (! $resultat['ok']) {
            if ($resultat['code'] === 'introuvable') {
                return $this->introuvable($donnees['reference']);
            }

            return response()->json([
                'enregistre' => false,
                'code' => $resultat['code'],
                'creneaux' => $resultat['creneaux'] ?? $this->catalogue->publier(),
            ], 409);
        }

        $this->mails->confirmer($resultat['reservation'], 'deplace');

        return response()->json([
            'enregistre' => true,
            'reservation' => $this->presenter($resultat['reservation']),
        ]);
    }

    public function annuler(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $resultat = $this->reservateur->annuler($donnees['reference'], $donnees['date_naissance']);

        if (! $resultat['ok']) {
            if ($resultat['code'] === 'introuvable') {
                return $this->introuvable($donnees['reference']);
            }

            return response()->json(['enregistre' => false, 'code' => $resultat['code']], 409);
        }

        if (isset($resultat['reservation'])) {
            $this->mails->confirmer($resultat['reservation'], 'annule');
        }

        return response()->json(['enregistre' => true]);
    }

    public function retrouver(PortailRdvRetrouverRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $reference = $this->reservateur->retrouver($donnees['identifiant'], $donnees['date_naissance']);

        if ($reference === null) {
            $seau = ReservateurRdv::seauParIdentifiant($donnees['identifiant']);
            RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

            return response()->json(['trouve' => false]);
        }

        RateLimiter::clear(ReservateurRdv::seauParIdentifiant($donnees['identifiant'])->cle);

        return response()->json([
            'trouve' => true,
            'reference' => $this->references->formater($reference),
        ]);
    }

    private function introuvable(string $reference): JsonResponse
    {
        $seau = ReservateurRdv::seauParReference($reference);
        RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

        return response()->json(['trouve' => false, 'enregistre' => false, 'code' => 'introuvable']);
    }

    /**
     * @return array{date: string, heure_debut: string, heure_fin: string, statut: string}
     */
    private function presenter(ESBTPRdvReservation $reservation): array
    {
        $creneau = $reservation->creneau;

        return [
            'date' => $creneau?->date->toDateString(),
            'heure_debut' => $creneau?->heureDebutHi(),
            'heure_fin' => $creneau?->heureFinHi(),
            'statut' => $reservation->statut->value,
        ];
    }
}
