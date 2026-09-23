<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Les familles qu'aucun courriel n'a prevenues de leur rendez-vous a venir :
 * sans adresse, envoi refuse, ou reservation d'avant le suivi des envois.
 *
 * Une convocation « en attente » n'y figure pas : elle partira au prochain
 * envoi. Un creneau deja commence non plus : il n'y a plus personne a prevenir,
 * c'est l'accueil du jour qui prend le relais. Une famille prevenue par
 * telephone en sort : la liste raccourcit au fil des appels.
 */
class FamillesAPrevenirRdv
{
    /** Les etats qui laissent une famille sans nouvelle de son rendez-vous. NULL : avant le suivi. */
    private const A_PREVENIR = [StatutConvocationRdv::SansEmail, StatutConvocationRdv::Echec];

    public function __construct(
        private readonly ContactsFamilleRdv $contacts,
        private readonly MessagerieRdv $messagerie,
    ) {
    }

    /** @return string|null le refus, ou null si c'est note */
    public function marquerPrevenue(ESBTPRdvReservation $reservation, int $agentId): ?string
    {
        return DB::transaction(function () use ($reservation, $agentId) {
            $r = ESBTPRdvReservation::query()->whereKey($reservation->id)->lockForUpdate()->first();
            if ($r === null || $r->statut !== StatutReservationRdv::Confirmee) {
                return 'Ce rendez-vous n\'est plus attendu.';
            }
            if ($r->convocation_statut !== null && ! in_array($r->convocation_statut, self::A_PREVENIR, true)) {
                return 'Cette famille a déjà reçu sa convocation, ou elle est en cours d\'envoi.';
            }

            $r->forceFill([
                'convocation_statut' => StatutConvocationRdv::Telephone,
                'convocation_envoyee_at' => now(),
                'convocation_erreur' => null,
                'prevenue_par' => $agentId,
            ])->save();

            return null;
        });
    }

    /**
     * Annule un « prevenue » pose par erreur : la convocation est replanifiee
     * (en attente d'envoi si l'adresse est valide, sans e-mail sinon).
     */
    public function annulerPrevenue(ESBTPRdvReservation $reservation): ?string
    {
        if ($reservation->convocation_statut !== StatutConvocationRdv::Telephone) {
            return 'Cette famille n\'est pas notée prévenue par téléphone.';
        }
        $this->messagerie->planifier($reservation, $reservation->convocation_action ?: 'confirme');

        return null;
    }

    public function compter(): int
    {
        return $this->requete()->count();
    }

    /**
     * @return list<array{date: string, jour: string, heure: string, nom: string, prenoms: string, telephone: string, contact2_nom: ?string, contact2_telephone: ?string, reference: string, motif: string}>
     */
    public function lignes(): array
    {
        return $this->requete()
            ->with(array_merge(['creneau'], ContactsFamilleRdv::chargements()))
            ->get()
            ->map(function (ESBTPRdvReservation $r) {
                $second = $this->contacts->second($r);

                return [
                    'date' => $r->creneau->date->toDateString(),
                    'jour' => ucfirst($r->creneau->date->translatedFormat('l j F')),
                    'heure' => $r->creneau->heureDebutHi().' – '.$r->creneau->heureFinHi(),
                    'nom' => (string) $r->nom,
                    'prenoms' => (string) $r->prenoms,
                    'telephone' => (string) $r->telephone,
                    'contact2_nom' => $second['nom'] ?? null,
                    'contact2_telephone' => $second['telephone'] ?? null,
                    'reference' => $this->contacts->reference($r),
                    'motif' => $this->motif($r),
                ];
            })->all();
    }

    private function motif(ESBTPRdvReservation $r): string
    {
        return match ($r->convocation_statut) {
            StatutConvocationRdv::SansEmail => 'Pas d\'adresse e-mail',
            StatutConvocationRdv::Echec => 'Envoi refusé'.($r->convocation_erreur ? ' : '.$r->convocation_erreur : ''),
            default => 'Réservation d\'avant le suivi des envois',
        };
    }

    private function requete(): Builder
    {
        $maintenant = Carbon::now();

        return ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->where('esbtp_rdv_reservations.statut', StatutReservationRdv::Confirmee->value)
            ->where(function (Builder $q) {
                $q->whereIn('esbtp_rdv_reservations.convocation_statut', array_column(self::A_PREVENIR, 'value'))
                    ->orWhereNull('esbtp_rdv_reservations.convocation_statut');
            })
            ->where(function (Builder $q) use ($maintenant) {
                $q->whereDate('c.date', '>', $maintenant->toDateString())
                    ->orWhere(fn (Builder $j) => $j->whereDate('c.date', $maintenant->toDateString())
                        ->where('c.heure_debut', '>', $maintenant->format('H:i:s')));
            })
            ->orderBy('c.date')->orderBy('c.heure_debut')
            ->orderBy('esbtp_rdv_reservations.nom')->orderBy('esbtp_rdv_reservations.prenoms');
    }
}
