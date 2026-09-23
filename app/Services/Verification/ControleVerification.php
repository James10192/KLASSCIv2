<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Models\ESBTPVerificationContact;
use App\Services\MailPulse\MailPulseVerifications;
use Illuminate\Support\Facades\DB;

/**
 * Controle un code (ou un jeton de lien) et, s'il est bon, rend la demande
 * visible a l'ecole.
 *
 * Aucune reponse ne distingue « cette demande n'existe pas » de « ce code est
 * faux » : les deux rendent `code_invalide`. Le jeton et le code ne sont
 * jamais journalises.
 */
class ControleVerification
{
    public const CODE_INVALIDE = 'code_invalide';

    public const EXPIRE = 'expire';

    public const TROP_DE_TENTATIVES = 'trop_de_tentatives';

    public const INDISPONIBLE = 'indisponible';

    public function __construct(
        private readonly MailPulseVerifications $whatsapp,
        private readonly FinalisationVerification $finalisation,
    ) {}

    public function parJeton(string $jeton, ?string $canal): ResultatControle
    {
        $verification = ESBTPVerificationContact::query()
            ->where('jeton_hash', SecretsVerification::empreinte($jeton))
            ->first();

        if ($verification === null || ! $this->canalConcorde($verification, $canal)) {
            return ResultatControle::refus(self::CODE_INVALIDE);
        }
        if ($verification->estVerifiee()) {
            return $this->reussite($verification);
        }
        if ($verification->jeton_expire_at === null || $verification->jeton_expire_at->isPast()) {
            return ResultatControle::refus(self::EXPIRE);
        }

        return $this->finalisation->valider($verification);
    }

    public function parCode(string $demandeId, string $code, ?string $canal): ResultatControle
    {
        return DB::transaction(function () use ($demandeId, $code, $canal) {
            $verification = ESBTPVerificationContact::query()->where('demande_id', $demandeId)->lockForUpdate()->first();

            if ($verification === null || ! $this->canalConcorde($verification, $canal)) {
                return ResultatControle::refus(self::CODE_INVALIDE);
            }
            if ($verification->estVerifiee()) {
                return $this->reussite($verification);
            }
            if ($verification->tentatives >= (int) config('verification_contact.tentatives_max', 5)) {
                return ResultatControle::refus(self::TROP_DE_TENTATIVES);
            }
            if ($verification->code_expire_at === null || $verification->code_expire_at->isPast()) {
                return ResultatControle::refus(self::EXPIRE);
            }

            return $verification->canal === CanalVerification::Email
                ? $this->codeEmail($verification, $code)
                : $this->codeWhatsapp($verification, $code);
        });
    }

    private function codeEmail(ESBTPVerificationContact $verification, string $code): ResultatControle
    {
        if (SecretsVerification::concorde($verification->code_hash, $code)) {
            return $this->finalisation->valider($verification);
        }

        return $this->echouer($verification, self::CODE_INVALIDE);
    }

    private function codeWhatsapp(ESBTPVerificationContact $verification, string $code): ResultatControle
    {
        if (! is_string($verification->mailpulse_verification_id) || $verification->mailpulse_verification_id === '') {
            return ResultatControle::refus(self::EXPIRE);
        }

        $resultat = $this->whatsapp->controler($verification->mailpulse_verification_id, $code);
        if ($resultat->ok) {
            return $this->finalisation->valider($verification);
        }

        return match ($resultat->code) {
            self::EXPIRE, self::TROP_DE_TENTATIVES, self::CODE_INVALIDE => $this->echouer($verification, $resultat->code),
            default => ResultatControle::refus(self::INDISPONIBLE),
        };
    }

    private function echouer(ESBTPVerificationContact $verification, string $motif): ResultatControle
    {
        $tentatives = $verification->tentatives + 1;
        $verification->forceFill(['tentatives' => $tentatives])->save();

        return ResultatControle::refus(
            $motif === self::CODE_INVALIDE && $tentatives >= (int) config('verification_contact.tentatives_max', 5)
                ? self::TROP_DE_TENTATIVES
                : $motif
        );
    }

    private function reussite(ESBTPVerificationContact $verification): ResultatControle
    {
        return ResultatControle::verifiee($verification->verifiable?->typeDemandePublique() ?? 'candidature');
    }

    private function canalConcorde(ESBTPVerificationContact $verification, ?string $canal): bool
    {
        return $canal === null || $canal === $verification->canal->value;
    }
}
