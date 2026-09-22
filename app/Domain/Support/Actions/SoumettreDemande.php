<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Models\SupportOutbox;
use App\Services\Care\ClientMasterSupport;
use App\Models\User;

/**
 * Transmet un signalement au Master, ou le met de cote s'il est injoignable.
 *
 * Le rapporteur est toujours l'utilisateur connecte : jamais un identifiant
 * venu du navigateur. Rend la projection du Master (reference, statut), ou
 * `['en_attente' => true]` quand le signalement attend dans la boite d'envoi.
 *
 * Un REFUS du Master (MasterSupportRefus) n'est pas rattrape ici : il remonte
 * a l'appelant, qui le montre. Le mettre de cote ne servirait a rien, il
 * serait refuse a chaque essai.
 */
class SoumettreDemande
{
    public function __construct(private readonly ClientMasterSupport $master)
    {
    }

    /**
     * @param  array<string, mixed>  $contexte  deja assaini (ContexteDePage::assainir)
     */
    public function executer(User $user, string $categorie, string $description, array $contexte, string $cle, ?string $requestId): array
    {
        $charge = [
            'api_version' => 1,
            'report' => ['category' => $categorie, 'description' => trim($description)],
            'reporter' => [
                'external_id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
            'context' => $contexte ?: null,
        ];

        try {
            return $this->master->creerTicket($charge, $cle, $requestId);
        } catch (MasterSupportIndisponible $e) {
            SupportOutbox::firstOrCreate(
                ['idempotency_key' => $cle],
                ['user_id' => $user->getKey(), 'payload' => $charge, 'request_id' => $requestId],
            );

            return ['en_attente' => true];
        }
    }
}
