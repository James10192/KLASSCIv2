<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Emails\AnalyseurEmail;
use Illuminate\Database\Eloquent\Model;

/**
 * Le contact d'une demande vient d'etre prouve (code saisi par la famille, ou
 * « Confirmer le contact » par l'ecole) : ses rendez-vous deja pris dont la
 * convocation avait ete retenue (« sans e-mail » ou en echec) reprennent
 * l'adresse du dossier et repartent dans la file d'envoi. Les autres ne sont
 * pas touches.
 *
 * Un seul chemin pour les deux declencheurs : une famille qui saisit son code
 * n'a pas a attendre qu'un agent la rappelle.
 */
class ReprisesConvocationsRetenues
{
    public function __construct(
        private readonly FileConvocationsRdv $convocations,
        private readonly AnalyseurEmail $emails,
    ) {}

    /** @return int le nombre de convocations remises en file */
    public function reprendre(Model $demande): int
    {
        $reservations = $demande->reservations()
            ->occupantes()
            ->whereIn('convocation_statut', [StatutConvocationRdv::SansEmail->value, StatutConvocationRdv::Echec->value])
            ->get();

        // L'adresse du dossier ne remplace celle saisie a la reservation que si
        // elle recoit du courrier : un contact prouve par WhatsApp, ou un
        // `@esbtp.edu.ci` en base, ne doit pas effacer une adresse valide.
        $dossier = $demande->emailRdv();
        $remplacer = $this->emails->analyser($dossier)->joignable();

        foreach ($reservations as $reservation) {
            /** @var ESBTPRdvReservation $reservation */
            if ($remplacer) {
                $reservation->forceFill(['email' => $dossier])->save();
            }
            $this->convocations->poser($reservation, $reservation->convocation_action ?: 'confirme');
        }

        return $reservations->count();
    }
}
