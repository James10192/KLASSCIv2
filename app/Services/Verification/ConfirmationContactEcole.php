<?php

namespace App\Services\Verification;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPRdvReservation;
use App\Services\Emails\AnalyseurEmail;
use App\Services\RendezVous\FileConvocationsRdv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * « Confirmer le contact » : un agent a joint la famille et atteste son
 * adresse ou son numero.
 *
 * - N'agit que si la demande est encore celle que l'agent avait a l'ecran
 *   (empreinte), sous verrou : deux agents, ou un redepot entre-temps, ne se
 *   marchent pas dessus.
 * - Sauvegarde normale : le journal d'audit garde qui, quand, et l'etat.
 * - Les rendez-vous deja pris dont la convocation avait ete retenue (« sans
 *   e-mail » ou en echec) reprennent l'adresse du dossier et repartent dans
 *   la file d'envoi. Les autres ne sont pas touches.
 */
class ConfirmationContactEcole
{
    public const CONFIRME = 'confirme';

    public const PAS_A_CONFIRMER = 'pas_a_confirmer';

    public const MODIFIE_ENTRE_TEMPS = 'modifie_entre_temps';

    public function __construct(
        private readonly FileConvocationsRdv $convocations,
        private readonly AnalyseurEmail $emails,
    ) {}

    /** @return array{0: string, 1: int} le resultat, et le nombre de convocations replanifiees */
    public function confirmer(Model $demande, string $empreinte, int $agentId): array
    {
        return DB::transaction(function () use ($demande, $empreinte, $agentId) {
            $ligne = $demande::query()->whereKey($demande->getKey())->lockForUpdate()->first();

            if ($ligne === null || ! hash_equals($ligne->empreinteContact(), $empreinte)) {
                return [self::MODIFIE_ENTRE_TEMPS, 0];
            }
            if (! $ligne->contactAConfirmer()) {
                return [self::PAS_A_CONFIRMER, 0];
            }

            $ligne->forceFill([
                'verification_contact' => StatutVerificationContact::Verifie->value,
                'contact_confirme_par' => $agentId,
                'contact_confirme_at' => now(),
            ])->save();

            Log::info('Contact confirme par l\'etablissement', [
                'type' => $ligne->typeDemandePublique(),
                'id' => $ligne->getKey(),
                'par_utilisateur' => $agentId,
            ]);

            return [self::CONFIRME, $this->replanifier($ligne)];
        });
    }

    private function replanifier(Model $demande): int
    {
        $reservations = $demande->reservations()
            ->occupantes()
            ->whereIn('convocation_statut', [StatutConvocationRdv::SansEmail->value, StatutConvocationRdv::Echec->value])
            ->get();

        foreach ($reservations as $reservation) {
            /** @var ESBTPRdvReservation $reservation */
            // L'adresse du dossier ne remplace celle saisie a la reservation que si
            // elle recoit du courrier : un contact confirme par telephone, ou un
            // `@esbtp.edu.ci` en base, ne doit pas effacer une adresse valide.
            $dossier = $demande->emailRdv();
            if ($this->emails->analyser($dossier)->joignable()) {
                $reservation->forceFill(['email' => $dossier])->save();
            }
            $this->convocations->poser($reservation, $reservation->convocation_action ?: 'confirme');
        }

        return $reservations->count();
    }
}
