<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gestes ciblés du guichet, partagés par Nanan et les futurs boutons/API.
 *
 * Cette classe ne choisit jamais un dossier ou un créneau : elle reçoit des IDs
 * déjà lus. Elle conserve les verrous métier de ReservateurRdv/AccueilRdv et
 * garde les convocations cohérentes avec le rendez-vous.
 */
final class GestionRendezVousCible
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly ReservateurRdv $reservateur,
        private readonly AccueilRdv $accueil,
        private readonly FileConvocationsRdv $convocations,
    ) {}

    /** @return array{ok:bool,code?:string,reservation?:ESBTPRdvReservation} */
    public function programmer(string $type, int $dossierId, int $creneauId): array
    {
        $porteur = match ($type) {
            'candidature' => ESBTPCandidature::find($dossierId),
            'reinscription' => ESBTPReinscriptionDemande::find($dossierId),
            default => null,
        };
        if (! $porteur) {
            return ['ok' => false, 'code' => 'introuvable'];
        }
        if (($refus = $this->refusCreneau($creneauId)) !== null) {
            return ['ok' => false, 'code' => $refus];
        }

        $resultat = $this->reservateur->placer($porteur, $creneauId);
        if (! ($resultat['ok'] ?? false)) {
            return $resultat;
        }

        $reservation = $resultat['reservation'];
        $this->convocations->confirmer($reservation, 'confirme');

        return ['ok' => true, 'reservation' => $reservation->fresh()->load('creneau')];
    }

    /** @return array{ok:bool,code?:string,reservation?:ESBTPRdvReservation} */
    public function reprogrammer(int $reservationId, int $creneauId, int $agentId, ?int $creneauVu = null): array
    {
        if (($refus = $this->refusCreneau($creneauId)) !== null) {
            return ['ok' => false, 'code' => $refus];
        }

        $reservation = ESBTPRdvReservation::with('creneau')->find($reservationId);
        if (! $reservation) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        $refus = $this->accueil->reprogrammer($reservation, $creneauId, $agentId, $creneauVu);
        if ($refus !== null) {
            return ['ok' => false, 'code' => $refus];
        }

        return ['ok' => true, 'reservation' => $reservation->fresh()->load('creneau')];
    }

    /**
     * Annulation décidée par l'école. Une convocation encore en file devient
     * sans objet ; une convocation déjà remise n'est pas effacée de l'historique.
     *
     * @return array{ok:bool,code?:string,reservation?:ESBTPRdvReservation}
     */
    public function annuler(int $reservationId, ?int $creneauVu = null): array
    {
        return DB::transaction(function () use ($reservationId, $creneauVu) {
            $reservation = ESBTPRdvReservation::query()->whereKey($reservationId)->lockForUpdate()->first();
            if (! $reservation || ! $reservation->statut?->occupeLeCreneau()) {
                return ['ok' => false, 'code' => 'introuvable'];
            }
            if ($creneauVu !== null && (int) $reservation->creneau_id !== $creneauVu) {
                return ['ok' => false, 'code' => 'deplacee'];
            }
            if ($reservation->statut === StatutReservationRdv::Honoree) {
                return ['ok' => false, 'code' => 'recue'];
            }

            $valeurs = ['statut' => StatutReservationRdv::Annulee->value];
            if ($reservation->convocation_statut === StatutConvocationRdv::EnAttente) {
                $valeurs += [
                    'convocation_statut' => StatutConvocationRdv::SansObjet->value,
                    'convocation_erreur' => 'Rendez-vous annulé par l’école avant envoi.',
                ];
            }
            $reservation->update($valeurs);

            return ['ok' => true, 'reservation' => $reservation->fresh()->load('creneau')];
        });
    }

    /** @return array{ok:bool,code?:string,creneau?:ESBTPRdvCreneau} */
    public function fermerCreneau(int $creneauId): array
    {
        $creneau = ESBTPRdvCreneau::find($creneauId);
        if (! $creneau) {
            return ['ok' => false, 'code' => 'introuvable'];
        }
        if ($creneau->ouvert) {
            $creneau->update(['ouvert' => false]);
        }

        return ['ok' => true, 'creneau' => $creneau->fresh()];
    }

    /** @return array{ok:bool,code?:string,creneau?:ESBTPRdvCreneau} */
    public function ouvrirCreneau(int $creneauId): array
    {
        $creneau = ESBTPRdvCreneau::find($creneauId);
        if (! $creneau) {
            return ['ok' => false, 'code' => 'introuvable'];
        }
        if ($this->dateInterdite($creneau) || $creneau->aCommence()) {
            return ['ok' => false, 'code' => 'jour_ferme'];
        }
        if (! $creneau->ouvert) {
            $creneau->update(['ouvert' => true]);
        }

        return ['ok' => true, 'creneau' => $creneau->fresh()];
    }

    public static function message(string $code): string
    {
        return match ($code) {
            'jour_ferme' => 'La journée de ce créneau est fermée et ne peut plus recevoir de nouvelle réservation.',
            'ferme' => 'Ce créneau est fermé.',
            'complet' => 'Ce créneau est complet.',
            'trop_tot' => 'Ce créneau n’est plus réservable.',
            'deja_reserve' => 'Ce dossier possède déjà un rendez-vous actif.',
            'deplacee' => 'Le rendez-vous a été déplacé entre-temps. Relisez-le avant de recommencer.',
            'recue' => 'Cette famille a déjà été reçue : son rendez-vous ne peut plus être annulé ou déplacé.',
            default => 'Le dossier, le rendez-vous ou le créneau n’existe plus.',
        };
    }

    private function refusCreneau(int $creneauId): ?string
    {
        $creneau = ESBTPRdvCreneau::find($creneauId);
        if (! $creneau) {
            return 'introuvable';
        }
        if (! $creneau->ouvert) {
            return 'ferme';
        }
        if ($this->dateInterdite($creneau)) {
            return 'jour_ferme';
        }

        return null;
    }

    private function dateInterdite(ESBTPRdvCreneau $creneau): bool
    {
        return $this->reglages->fermerJourAMinuit()
            && $creneau->date->copy()->startOfDay()->lte(Carbon::today());
    }
}
