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
 *
 * Toute ecriture se fait sous verrou de ligne. Le controle WhatsApp, lui, est
 * un appel reseau a MailPulse : il se fait HORS verrou, puis la ligne est
 * relue sous verrou avant d'appliquer le verdict.
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
        return DB::transaction(function () use ($jeton, $canal) {
            $verification = ESBTPVerificationContact::query()
                ->where('jeton_hash', SecretsVerification::empreinte($jeton))
                ->lockForUpdate()
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
        });
    }

    public function parCode(string $demandeId, string $code, ?string $canal): ResultatControle
    {
        $apercu = ESBTPVerificationContact::query()->where('demande_id', $demandeId)->first();
        if ($apercu === null || ! $this->canalConcorde($apercu, $canal)) {
            return ResultatControle::refus(self::CODE_INVALIDE);
        }

        if ($apercu->canal === CanalVerification::Email || $apercu->estVerifiee() || $this->blocage($apercu) !== null) {
            return $this->sousVerrou($apercu->id, fn (ESBTPVerificationContact $v) => $this->local($v, $code));
        }

        $distant = $this->whatsapp->controler((string) $apercu->mailpulse_verification_id, $code);

        return $this->sousVerrou($apercu->id, function (ESBTPVerificationContact $v) use ($apercu, $distant, $code) {
            if ($v->estVerifiee()) {
                return $this->reussiteAvecCode($v, $code);
            }
            // Un nouveau code (autre canal, autre numero, autre verification
            // MailPulse) est parti pendant l'appel : ce verdict ne vaut plus.
            if ($v->canal !== $apercu->canal || $v->destination !== $apercu->destination
                || $v->mailpulse_verification_id !== $apercu->mailpulse_verification_id) {
                return ResultatControle::refus(self::EXPIRE);
            }
            if ($distant->ok) {
                return $this->finalisation->valider($v, $code);
            }

            return match ($distant->code) {
                self::EXPIRE, self::TROP_DE_TENTATIVES, self::CODE_INVALIDE => $this->echouer($v, $distant->code),
                default => ResultatControle::refus(self::INDISPONIBLE),
            };
        });
    }

    /** Tout ce qui se decide sans MailPulse : code e-mail, ligne deja verifiee ou bloquee. */
    private function local(ESBTPVerificationContact $v, string $code): ResultatControle
    {
        if ($v->estVerifiee()) {
            return $this->reussiteAvecCode($v, $code);
        }
        if (($blocage = $this->blocage($v)) !== null) {
            return ResultatControle::refus($blocage);
        }
        if ($v->canal === CanalVerification::Email && SecretsVerification::concorde($v->code_hash, $code)) {
            return $this->finalisation->valider($v, $code);
        }

        return $v->canal === CanalVerification::Email
            ? $this->echouer($v, self::CODE_INVALIDE)
            : ResultatControle::refus(self::INDISPONIBLE);
    }

    private function blocage(ESBTPVerificationContact $v): ?string
    {
        if ($v->tentatives >= (int) config('verification_contact.tentatives_max', 5)
            || $v->tentatives_total >= (int) config('verification_contact.tentatives_max_total', 15)) {
            return self::TROP_DE_TENTATIVES;
        }
        if ($v->code_expire_at === null || $v->code_expire_at->isPast()) {
            return self::EXPIRE;
        }
        if ($v->canal === CanalVerification::Telephone && (string) $v->mailpulse_verification_id === '') {
            return self::EXPIRE;
        }

        return null;
    }

    private function echouer(ESBTPVerificationContact $v, string $motif): ResultatControle
    {
        // Un code expire n'est pas un essai : il ne compte pas dans les plafonds.
        if ($motif === self::EXPIRE) {
            return ResultatControle::refus(self::EXPIRE);
        }

        $v->forceFill(['tentatives' => $v->tentatives + 1, 'tentatives_total' => $v->tentatives_total + 1])->save();

        return ResultatControle::refus($motif === self::CODE_INVALIDE && $this->blocage($v) === self::TROP_DE_TENTATIVES ? self::TROP_DE_TENTATIVES : $motif);
    }

    /** Une ligne deja verifiee ne repond « verifie » qu'au code qui l'a verifiee. */
    private function reussiteAvecCode(ESBTPVerificationContact $v, string $code): ResultatControle
    {
        return SecretsVerification::concorde($v->code_hash, $code)
            ? $this->reussite($v)
            : ResultatControle::refus(self::CODE_INVALIDE);
    }

    /** Idempotent, et repare une demande restee marquee malgre une verification aboutie. */
    private function reussite(ESBTPVerificationContact $v): ResultatControle
    {
        $demande = $v->verifiable;
        if ($demande !== null && $demande->contactNonVerifie()) {
            return $this->finalisation->valider($v);
        }

        return ResultatControle::verifiee($demande?->typeDemandePublique() ?? 'candidature');
    }

    /** @param  callable(ESBTPVerificationContact): ResultatControle  $travail */
    private function sousVerrou(int $id, callable $travail): ResultatControle
    {
        return DB::transaction(function () use ($id, $travail) {
            $v = ESBTPVerificationContact::query()->whereKey($id)->lockForUpdate()->first();

            return $v === null ? ResultatControle::refus(self::CODE_INVALIDE) : $travail($v);
        });
    }

    private function canalConcorde(ESBTPVerificationContact $verification, ?string $canal): bool
    {
        return $canal === null || $canal === $verification->canal->value;
    }
}
