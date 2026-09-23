<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Models\ESBTPVerificationContact;
use App\Services\MailPulse\MailPulseVerifications;
use App\Services\MailPulse\ResultatVerificationDistante;
use App\Services\MailPulse\RefusMailPulse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fait partir un NOUVEAU code (et, pour l'e-mail, un nouveau lien) et
 * consigne l'envoi sur la ligne. Tout code precedent cesse de valoir.
 *
 * Ne journalise jamais ni le code, ni le jeton, ni la destination.
 */
class ExpediteurVerification
{
    /**
     * La ligne a change de contact pendant l'envoi (redepot concurrent) : le
     * resultat est jete, rien n'est ecrit, et l'appelant ne touche a rien.
     */
    public const CONTACT_CHANGE = 'contact_change';

    public function __construct(
        private readonly CourrielVerification $courriel,
        private readonly MailPulseVerifications $whatsapp,
    ) {}

    public function expedier(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $resultat = $verification->canal === CanalVerification::Email
            ? $this->parEmail($verification)
            : $this->parWhatsapp($verification);

        if ($resultat->code === self::CONTACT_CHANGE) {
            Log::info('Verification de contact : contact change pendant l\'envoi, resultat ignore', ['demande_id' => $verification->demande_id]);

            return $resultat;
        }

        $consigne = $this->ecrire($verification, [
            'dernier_envoi_at' => now(),
            'dernier_echec' => $resultat->ok ? null : mb_substr($resultat->code, 0, 60),
        ]);
        if (! $consigne) {
            return ResultatVerificationDistante::echec(self::CONTACT_CHANGE);
        }

        if (! $resultat->ok) {
            Log::warning('Verification de contact : envoi refuse', [
                'demande_id' => $verification->demande_id,
                'canal' => $verification->canal->value,
                'code' => $resultat->code,
                'definitif' => $resultat->definitif(),
            ]);
        }

        return $resultat;
    }

    private function parEmail(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $code = SecretsVerification::nouveauCode();
        $jeton = SecretsVerification::nouveauJeton();

        // Les secrets ne s'ecrivent que sur la ligne telle qu'on l'a lue : si un
        // redepot a change l'adresse entre-temps, aucun code ne part.
        $ecrit = $this->ecrire($verification, [
            'code_hash' => SecretsVerification::empreinte($code),
            'jeton_hash' => SecretsVerification::empreinte($jeton),
            'code_expire_at' => now()->addMinutes((int) config('verification_contact.code_expire_minutes', 30)),
            'jeton_expire_at' => now()->addHours((int) config('verification_contact.lien_expire_heures', 48)),
            'tentatives' => 0,
        ]);
        if (! $ecrit) {
            return ResultatVerificationDistante::echec(self::CONTACT_CHANGE);
        }

        $envoi = $this->courriel->expedier($verification->destination, $code, $jeton);

        if ($envoi->ok) {
            $ecrit = $this->ecrire($verification, ['mailpulse_message_id' => $envoi->id ? mb_substr($envoi->id, 0, 100) : null]);

            return $ecrit ? ResultatVerificationDistante::ok('sent', (string) $envoi->id) : ResultatVerificationDistante::echec(self::CONTACT_CHANGE);
        }

        return in_array($envoi->status, RefusMailPulse::CONFIGURATION, true)
            ? ResultatVerificationDistante::echec('verification_indisponible', $envoi->httpStatus)
            : ResultatVerificationDistante::echec($envoi->errorCode ?: $envoi->status, $envoi->httpStatus);
    }

    private function parWhatsapp(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $resultat = $this->whatsapp->creer($verification->destination, $verification->demande_id);
        if (! $resultat->ok) {
            return $resultat;
        }

        // L'identifiant MailPulse ne vaut que pour le numero appele.
        $ecrit = $this->ecrire($verification, [
            'mailpulse_verification_id' => mb_substr((string) $resultat->id, 0, 100),
            'tentatives' => 0,
            'code_expire_at' => now()->addMinutes((int) config('verification_contact.code_whatsapp_expire_minutes', 10)),
        ]);

        return $ecrit ? $resultat : ResultatVerificationDistante::echec(self::CONTACT_CHANGE);
    }

    /**
     * Ecriture CONDITIONNELLE : seulement si la ligne a encore le canal et la
     * destination de l'instance en main. Rend faux si elle a change.
     *
     * @param  array<string, mixed>  $champs
     */
    private function ecrire(ESBTPVerificationContact $verification, array $champs): bool
    {
        // Verrou, puis ecriture seulement si la ligne a ete TROUVEE : le nombre de
        // lignes que MySQL rend a l'update compte les lignes modifiees, et une
        // ecriture identique dans la meme seconde rendrait 0 a tort.
        $ecrit = DB::transaction(function () use ($verification, $champs) {
            $trouvee = ESBTPVerificationContact::query()
                ->whereKey($verification->getKey())
                ->where('canal', $verification->canal->value)
                ->where('destination', $verification->destination)
                ->lockForUpdate()
                ->first();

            if ($trouvee === null) {
                return false;
            }

            ESBTPVerificationContact::query()->whereKey($trouvee->getKey())->update($champs + ['updated_at' => now()]);

            return true;
        });

        if (! $ecrit) {
            return false;
        }

        $verification->forceFill($champs)->syncOriginal();

        return true;
    }
}
