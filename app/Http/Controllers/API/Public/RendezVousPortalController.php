<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\RendezVous\PortailRdvCreneauRequest;
use App\Http\Requests\RendezVous\PortailRdvRequest;
use App\Http\Requests\RendezVous\PortailRdvRetrouverRequest;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\CatalogueCreneaux;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\Portail\ReferencePublique;
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

    public function reserver(PortailRdvCreneauRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (($refus = $this->seauPlein($donnees['reference'])) !== null) {
            return $refus;
        }

        $resultat = $this->reservateur->reserver(
            $donnees['reference'],
            $donnees['date_naissance'],
            (int) $donnees['creneau_id']
        );

        if ($resultat['ok']) {
            RateLimiter::clear(ReservateurRdv::seauParReference($donnees['reference'])->cle);
        }

        return $this->repondreMutation($resultat, $donnees['reference'], 'confirme', 201);
    }

    public function consulter(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (($refus = $this->seauPlein($donnees['reference'])) !== null) {
            return $refus;
        }
        $lecture = $this->reservateur->consulter($donnees['reference'], $donnees['date_naissance']);

        if (! $lecture['trouve']) {
            return $this->introuvable($donnees['reference']);
        }

        RateLimiter::clear(ReservateurRdv::seauParReference($donnees['reference'])->cle);

        $reservation = $lecture['reservation'];

        return response()->json([
            'trouve' => true,
            'reservation' => $reservation === null ? null : $this->presenter($reservation),
            'peut_modifier' => $reservation !== null && $this->reservateur->peutModifier($reservation),
        ]);
    }

    public function deplacer(PortailRdvCreneauRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (($refus = $this->seauPlein($donnees['reference'])) !== null) {
            return $refus;
        }

        $resultat = $this->reservateur->deplacer(
            $donnees['reference'],
            $donnees['date_naissance'],
            (int) $donnees['creneau_id']
        );

        return $this->repondreMutation($resultat, $donnees['reference'], 'deplace');
    }

    public function annuler(PortailRdvRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        if (($refus = $this->seauPlein($donnees['reference'])) !== null) {
            return $refus;
        }
        $resultat = $this->reservateur->annuler($donnees['reference'], $donnees['date_naissance']);

        return $this->repondreMutation($resultat, $donnees['reference'], 'annule');
    }

    public function retrouver(PortailRdvRetrouverRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $seau = ReservateurRdv::seauParIdentifiant($donnees['identifiant']);
        if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
            return response()->json($seau->refus(), 429);
        }
        $reference = $this->reservateur->retrouver($donnees['identifiant'], $donnees['date_naissance']);

        if ($reference === null) {
            RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

            return response()->json(['trouve' => false]);
        }

        RateLimiter::clear(ReservateurRdv::seauParIdentifiant($donnees['identifiant'])->cle);

        return response()->json([
            'trouve' => true,
            'reference' => $this->references->formater($reference),
        ]);
    }

    /**
     * @param  array{ok: bool, code?: string, creneaux?: list<array<string, mixed>>, reservation?: ESBTPRdvReservation}  $resultat
     */
    private function repondreMutation(array $resultat, string $reference, string $action, int $statutOk = 200): JsonResponse
    {
        if (! $resultat['ok']) {
            if (($resultat['code'] ?? '') === 'introuvable') {
                return $this->introuvable($reference);
            }

            $corps = ['enregistre' => false, 'code' => $resultat['code'] ?? 'erreur'];
            if (isset($resultat['creneaux'])) {
                $corps['creneaux'] = $resultat['creneaux'];
            }

            return response()->json($corps, 409);
        }

        if (isset($resultat['reservation'])) {
            $this->mails->confirmer($resultat['reservation'], $action);
        }

        $corps = ['enregistre' => true];
        if (isset($resultat['reservation']) && $action !== 'annule') {
            $corps['reservation'] = $this->presenter($resultat['reservation']);
        }

        return response()->json($corps, $statutOk);
    }

    private function seauPlein(string $reference): ?JsonResponse
    {
        $seau = ReservateurRdv::seauParReference($reference);
        if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
            return response()->json($seau->refus(), 429);
        }

        return null;
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
