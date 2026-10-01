<?php

namespace App\Services\Verification;

use App\Enums\StatutVerificationContact;
use App\Services\RendezVous\ReprisesConvocationsRetenues;
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
 * - Possible quel que soit le reglage : une demande marquee pendant qu'il
 *   etait actif garde son badge jusqu'a confirmation, meme apres coupure.
 * - Sauvegarde normale : le journal d'audit garde qui, quand, et l'etat.
 * - Les convocations retenues repartent (ReprisesConvocationsRetenues).
 */
class ConfirmationContactEcole
{
    public const CONFIRME = 'confirme';

    public const PAS_A_CONFIRMER = 'pas_a_confirmer';

    public const MODIFIE_ENTRE_TEMPS = 'modifie_entre_temps';

    public function __construct(private readonly ReprisesConvocationsRetenues $reprises) {}

    /** @return array{0: string, 1: int} le resultat, et le nombre de convocations replanifiees */
    public function confirmer(Model $demande, string $empreinte, int $agentId): array
    {
        return $this->poser($demande, $empreinte, $agentId, fn (Model $ligne): bool => $ligne->contactMarque());
    }

    /**
     * La meme confirmation, faite au guichet du parcours d'inscription, devant
     * l'etudiant : elle vaut pour tout contact encore non prouve, et pas
     * seulement pour un contact marque. Un dossier dont le code n'a jamais ete
     * demande (depose avant l'activation de la verification, ou repris d'un
     * depot anterieur) n'avait sinon aucune issue pour recevoir son lien
     * d'activation.
     *
     * @return array{0: string, 1: int}
     */
    public function confirmerAuGuichet(Model $demande, string $empreinte, int $agentId): array
    {
        return $this->poser($demande, $empreinte, $agentId, fn (Model $ligne): bool => $ligne->contact_confirme_at === null
            && $ligne->email_verifie_at === null
            && $ligne->telephone_verifie_at === null);
    }

    /**
     * @param  callable(Model): bool  $aConfirmer
     * @return array{0: string, 1: int}
     */
    private function poser(Model $demande, string $empreinte, int $agentId, callable $aConfirmer): array
    {
        return DB::transaction(function () use ($demande, $empreinte, $agentId, $aConfirmer) {
            $ligne = $demande::query()->whereKey($demande->getKey())->lockForUpdate()->first();

            if ($ligne === null || ! hash_equals($ligne->empreinteContact(), $empreinte)) {
                return [self::MODIFIE_ENTRE_TEMPS, 0];
            }
            if (! $aConfirmer($ligne)) {
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

            return [self::CONFIRME, $this->reprises->reprendre($ligne)];
        });
    }
}
