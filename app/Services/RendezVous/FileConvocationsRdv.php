<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * La seule porte par laquelle une convocation part.
 *
 * Quatre appelants la sollicitent — le portail public apres une reservation,
 * la boucle de l'ecran, la tache planifiee, l'API CLI — et parfois en meme
 * temps : une relance de plusieurs centaines de convocations depuis l'ecran
 * dure des minutes et croise forcement la tache planifiee. Une reservation
 * reste « en attente » pendant tout l'appel a MailPulse ; sans verrou, deux
 * appelants l'enverraient chacun. D'ou un verrou unique, et des paquets bornes
 * dans le temps : l'ancien lot, envoye d'un bloc dans `terminating`, mourait
 * avec le processus PHP et s'arretait au premier refus.
 *
 * Le verrou tient le temps d'un paquet (budget + un envoi lent, 2 x 20 s).
 */
class FileConvocationsRdv
{
    private const VERROU = 'rdv-convocations-envoi';

    private const DUREE_VERROU_SECONDES = 150;

    public function __construct(private readonly MessagerieRdv $mails)
    {
    }

    /**
     * Apres une reservation du portail : pose la convocation, puis tente de
     * l'envoyer apres la reponse, pour ne pas faire attendre la famille. Si un
     * paquet tient deja le verrou, elle reste en attente : ce paquet a deja choisi
     * ses lignes, elle partira avec le suivant (ecran ou tache planifiee).
     */
    public function confirmer(ESBTPRdvReservation $reservation, string $action = 'confirme'): void
    {
        $this->mails->planifier($reservation, $action);

        if ($reservation->convocation_statut === StatutConvocationRdv::EnAttente) {
            $id = $reservation->id;
            app()->terminating(fn () => $this->envoyerCelle($id));
        }
    }

    /**
     * Pose la convocation sans rien envoyer : pour un lot, ou chaque famille
     * programmerait sinon son propre envoi dans le meme processus, apres la
     * reponse (le schema que placer() a abandonne). Le lot appelle ensuite
     * envoyerUnPaquetApres(), une seule fois.
     */
    public function poser(ESBTPRdvReservation $reservation, string $action): void
    {
        $this->mails->planifier($reservation, $action);
    }

    /**
     * Un seul paquet borne apres la reponse, sous le verrou d'envoi. Le reste
     * part avec la tache planifiee ou le bouton « Envoyer » du planning.
     */
    public function envoyerUnPaquetApres(): void
    {
        app()->terminating(function () {
            try {
                $this->envoyerUnPaquet();
            } catch (\Throwable $e) {
                Log::warning('Convocation rdv : paquet apres reponse interrompu', ['erreur' => $e->getMessage()]);
            }
        });
    }

    /**
     * Ne leve jamais : il tourne dans `terminating`, dont la boucle n'a pas de
     * `try` — une exception y couperait les rappels suivants.
     */
    public function envoyerCelle(int $reservationId): void
    {
        try {
            $this->sousVerrou(function () use ($reservationId) {
                $reservation = ESBTPRdvReservation::query()->with('creneau')->find($reservationId);
                if ($reservation?->convocation_statut === StatutConvocationRdv::EnAttente) {
                    $this->mails->envoyer($reservation);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Convocation rdv : envoi unitaire interrompu', ['reservation_id' => $reservationId, 'erreur' => $e->getMessage()]);
        }
    }

    /**
     * @return array{envoyees: int, echecs: int, restantes: int, bloque: ?string, en_cours: bool}
     *         `bloque` : la raison qui a arrete le paquet (MailPulse desactive,
     *         injoignable...). `en_cours` : un autre envoi tient le verrou —
     *         rien n'a ete tente, rappeler un peu plus tard.
     */
    public function envoyerUnPaquet(int $maximum = 15, float $budgetSecondes = 20.0): array
    {
        $rapport = ['envoyees' => 0, 'echecs' => 0, 'restantes' => 0, 'bloque' => null, 'en_cours' => false];

        $tenu = $this->sousVerrou(function () use (&$rapport, $maximum, $budgetSecondes) {
            $debut = microtime(true);
            $paquet = ESBTPRdvReservation::query()
                ->with('creneau')
                ->where('convocation_statut', StatutConvocationRdv::EnAttente->value)
                ->orderBy('id')
                ->limit($maximum)
                ->get();

            foreach ($paquet as $reservation) {
                if (microtime(true) - $debut >= $budgetSecondes) {
                    break;
                }

                $rapport['bloque'] = $this->mails->envoyer($reservation);
                if ($rapport['bloque'] !== null) {
                    break;
                }

                match ($reservation->convocation_statut) {
                    StatutConvocationRdv::Envoyee => $rapport['envoyees']++,
                    StatutConvocationRdv::Echec, StatutConvocationRdv::SansEmail, StatutConvocationRdv::SansObjet => $rapport['echecs']++,
                    default => null,
                };
            }
        });

        $rapport['en_cours'] = ! $tenu;
        $rapport['restantes'] = $this->enAttente();

        return $rapport;
    }

    /**
     * Les reservations anterieures au suivi (etat inconnu), ou celles en echec,
     * remises en attente. C'est un geste de l'ecole, jamais automatique.
     *
     * @param  'inconnues'|'echecs'  $quoi
     * @param  int|null  $limite  les plus anciennes seulement (verifier un premier envoi)
     */
    public function remettreEnAttente(string $quoi, ?int $limite = null): int
    {
        // Une reservation d'avant le suivi n'interesse que si elle tient encore
        // son creneau. Un echec, lui, peut etre un avis d'annulation a renvoyer.
        $requete = $quoi === 'echecs'
            ? ESBTPRdvReservation::query()->where('convocation_statut', StatutConvocationRdv::Echec->value)
            : ESBTPRdvReservation::query()->occupantes()->whereNull('convocation_statut');

        $n = 0;
        $planifier = function (ESBTPRdvReservation $r) use (&$n) {
            $this->mails->planifier($r, $r->convocation_action ?: 'confirme');
            $n++;
        };

        // Pagination par identifiant, pas par decalage : planifier() fait sortir chaque
        // ligne du filtre, et une page 2 en `offset` sauterait autant de lignes que la
        // page 1 en a traite. Un lot borne, lui, se lit d'un bloc.
        $requete->with('creneau');
        $limite === null
            ? $requete->chunkById(200, fn ($lot) => $lot->each($planifier))
            : $requete->orderBy('id')->limit($limite)->get()->each($planifier);

        return $n;
    }

    public function enAttente(): int
    {
        return ESBTPRdvReservation::query()
            ->where('convocation_statut', StatutConvocationRdv::EnAttente->value)
            ->count();
    }

    /** @return bool faux si un autre envoi tenait deja le verrou */
    private function sousVerrou(callable $travail): bool
    {
        $verrou = Cache::lock(self::VERROU, self::DUREE_VERROU_SECONDES);
        if (! $verrou->get()) {
            return false;
        }

        try {
            $travail();
        } finally {
            $verrou->release();
        }

        return true;
    }
}
