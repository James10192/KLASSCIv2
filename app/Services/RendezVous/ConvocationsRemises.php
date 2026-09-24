<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;

/**
 * Les convocations vues depuis MailPulse : acceptees, remises, perdues.
 *
 * `acceptees` compte tout ce que MailPulse a pris en charge (remis ou non) ;
 * `non_synchronisees`, les acceptees dont l'etat reel n'a jamais ete relu.
 * Les `echecs` se detaillent en `rebonds` et `supprimees` quand le code
 * distant le permet.
 */
class ConvocationsRemises
{
    /** @return array{acceptees: int, delivrees: int, en_attente: int, echecs: int, rebonds: int, supprimees: int, non_synchronisees: int, derniere_synchro: ?string} */
    public function comptes(): array
    {
        $envoyees = fn () => ESBTPRdvReservation::query()->where('convocation_statut', StatutConvocationRdv::Envoyee->value);

        $rebonds = 0;
        $supprimees = 0;
        $codes = ESBTPRdvReservation::query()
            ->where('convocation_statut', StatutConvocationRdv::Echec->value)
            ->whereNotNull('convocation_code_distant')
            ->selectRaw('convocation_code_distant AS code, COUNT(*) AS n')
            ->groupBy('convocation_code_distant')
            ->pluck('n', 'code');
        foreach ($codes as $code => $n) {
            match (MotifsRemiseConvocation::famille((string) $code)) {
                'rebond' => $rebonds += (int) $n,
                'suppression' => $supprimees += (int) $n,
                default => null,
            };
        }

        $derniere = ESBTPRdvReservation::query()->max('convocation_synchro_at');

        return [
            'acceptees' => $envoyees()->count(),
            'delivrees' => $envoyees()->whereNotNull('convocation_delivree_at')->count(),
            'en_attente' => ESBTPRdvReservation::query()->where('convocation_statut', StatutConvocationRdv::EnAttente->value)->count(),
            'echecs' => ESBTPRdvReservation::query()->where('convocation_statut', StatutConvocationRdv::Echec->value)->count(),
            'rebonds' => $rebonds,
            'supprimees' => $supprimees,
            'non_synchronisees' => $envoyees()->whereNull('convocation_synchro_at')->count(),
            'derniere_synchro' => $derniere ? \Illuminate\Support\Carbon::parse($derniere)->toIso8601String() : null,
        ];
    }
}
