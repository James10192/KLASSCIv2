<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * La feuille de suivi des rendez-vous, a imprimer : qui vient, a quelle heure,
 * avec une case a cocher a l'arrivee et de la place pour noter. Le guichet la
 * tient sans ecran ; l'accueil du jour reste l'outil pour saisir.
 *
 * Une journee (`jour`) ou la semaine qui la contient. Seules les reservations
 * qui occupent un creneau y figurent : une annulation ou une liberation n'a
 * plus personne a accueillir.
 */
class FeuilleRendezVous
{
    public function __construct(private readonly ContactsFamilleRdv $contacts)
    {
    }

    /**
     * @return array{debut: Carbon, fin: Carbon, jourSeul: bool}
     */
    public function periode(?string $debutBrut, ?string $jourBrut): array
    {
        $jour = PortailReinscriptionService::interpreterDateIso((string) $jourBrut);
        if ($jour !== null) {
            return ['debut' => $jour->copy()->startOfDay(), 'fin' => $jour->copy()->startOfDay(), 'jourSeul' => true];
        }

        $debut = (PortailReinscriptionService::interpreterDateIso((string) $debutBrut) ?? Carbon::today())
            ->copy()->startOfWeek(Carbon::MONDAY);

        return ['debut' => $debut, 'fin' => $debut->copy()->addDays(6), 'jourSeul' => false];
    }

    public function compter(Carbon $debut, Carbon $fin): int
    {
        return $this->requete($debut, $fin)->count();
    }

    /**
     * Une ligne par famille attendue, dans l'ordre du guichet.
     *
     * @return list<array{date: string, jour: string, heure: string, nom: string, prenoms: string, dossier: string, reference: string, telephone: string, contact2_nom: ?string, contact2_telephone: ?string, convocation: string, statut: string, a_verifier: bool}>
     */
    public function lignes(Carbon $debut, Carbon $fin): array
    {
        return $this->requete($debut, $fin)
            ->with(array_merge(['creneau'], ContactsFamilleRdv::chargements()))
            ->get()
            ->map(function (ESBTPRdvReservation $r) {
                $second = $this->contacts->second($r);

                return [
                    'date' => $r->creneau->date->toDateString(),
                    'jour' => ucfirst($r->creneau->date->translatedFormat('l j F Y')),
                    'heure' => $r->creneau->heureDebutHi().' – '.$r->creneau->heureFinHi(),
                    'nom' => (string) $r->nom,
                    'prenoms' => (string) $r->prenoms,
                    'dossier' => $r->candidature_id !== null ? 'Inscription' : 'Réinscription',
                    'reference' => $this->contacts->reference($r),
                    'telephone' => (string) $r->telephone,
                    'contact2_nom' => $second['nom'] ?? null,
                    'contact2_telephone' => $second['telephone'] ?? null,
                    'convocation' => $this->convocation($r->convocation_statut),
                    'statut' => match ($r->statut) {
                        StatutReservationRdv::Honoree => 'Reçue',
                        StatutReservationRdv::Manquee => 'Non venue',
                        default => '',
                    },
                    'a_verifier' => (bool) $r->porteur()?->contactMarque(),
                ];
            })->all();
    }

    private function convocation(?StatutConvocationRdv $statut): string
    {
        return match ($statut) {
            null => 'Non suivie',
            StatutConvocationRdv::Envoyee => 'E-mail',
            StatutConvocationRdv::Telephone => 'Téléphone',
            StatutConvocationRdv::EnAttente => 'À envoyer',
            StatutConvocationRdv::SansEmail, StatutConvocationRdv::Echec => 'À prévenir',
            StatutConvocationRdv::SansObjet => '—',
        };
    }

    private function requete(Carbon $debut, Carbon $fin): Builder
    {
        return ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->occupantes()
            ->whereDate('c.date', '>=', $debut->toDateString())
            ->whereDate('c.date', '<=', $fin->toDateString())
            ->orderBy('c.date')->orderBy('c.heure_debut')
            ->orderBy('esbtp_rdv_reservations.nom')->orderBy('esbtp_rdv_reservations.prenoms');
    }
}
