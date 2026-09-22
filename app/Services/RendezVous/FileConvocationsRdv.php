<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;

/**
 * Les convocations en attente, envoyees par paquets bornes dans le temps.
 *
 * C'est ce qui remplace le lot envoye dans `terminating` : la-bas, des centaines
 * d'envois tournaient a la suite dans un seul processus PHP, tue par sa limite
 * d'execution bien avant la fin, et le premier refus arretait tous les suivants.
 * Ici chaque appel s'arrete de lui-meme avant la limite, rend ce qui reste, et
 * l'appelant — l'ecran, la tache planifiee — rappelle.
 */
class FileConvocationsRdv
{
    public function __construct(private readonly MessagerieRdv $mails)
    {
    }

    /**
     * @return array{envoyees: int, echecs: int, restantes: int, bloque: ?string}
     *         `bloque` : la raison qui a arrete le paquet (MailPulse desactive,
     *         injoignable...). Rappeler ne servira a rien tant qu'elle tient.
     */
    public function envoyerUnPaquet(int $maximum = 15, float $budgetSecondes = 20.0): array
    {
        $debut = microtime(true);
        $envoyees = 0;
        $echecs = 0;
        $bloque = null;

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

            $bloque = $this->mails->envoyer($reservation);
            if ($bloque !== null) {
                break;
            }

            match ($reservation->convocation_statut) {
                StatutConvocationRdv::Envoyee => $envoyees++,
                StatutConvocationRdv::Echec, StatutConvocationRdv::SansEmail => $echecs++,
                default => null,
            };
        }

        return [
            'envoyees' => $envoyees,
            'echecs' => $echecs,
            'restantes' => $this->enAttente(),
            'bloque' => $bloque,
        ];
    }

    /**
     * Les reservations anterieures au suivi (etat inconnu), plus celles en echec,
     * remises en attente. C'est un geste de l'ecole, jamais automatique.
     *
     * @param  'inconnues'|'echecs'  $quoi
     */
    public function remettreEnAttente(string $quoi): int
    {
        // Une reservation d'avant le suivi n'interesse que si elle tient encore
        // son creneau. Un echec, lui, peut etre un avis d'annulation a renvoyer.
        $requete = $quoi === 'echecs'
            ? ESBTPRdvReservation::query()->where('convocation_statut', StatutConvocationRdv::Echec->value)
            : ESBTPRdvReservation::query()->occupantes()->whereNull('convocation_statut');

        $n = 0;
        $requete->with('creneau')->orderBy('id')->each(function (ESBTPRdvReservation $r) use (&$n) {
            $this->mails->planifier($r, $r->convocation_action ?: 'confirme');
            $n++;
        });

        return $n;
    }

    public function enAttente(): int
    {
        return ESBTPRdvReservation::query()
            ->where('convocation_statut', StatutConvocationRdv::EnAttente->value)
            ->count();
    }
}
