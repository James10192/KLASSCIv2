<?php

namespace App\Services\Verification;

use App\Domain\Notifications\PhoneNormalizer;
use App\Enums\CanalVerification;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Emails\AnalyseurEmail;
use Illuminate\Database\Eloquent\Model;

/**
 * Par ou verifier une demande : l'e-mail s'il est joignable, sinon WhatsApp
 * sur le telephone, sinon rien.
 *
 * Pour une reinscription, le contact est celui du dossier de l'etudiant : le
 * formulaire public n'en demande pas. Une adresse fabriquee par KLASSCI
 * (`@esbtp.edu.ci`) n'est pas joignable, et bascule donc sur WhatsApp au lieu
 * d'envoyer un code dans le vide.
 *
 * @phpstan-type Contact array{canal: CanalVerification, destination: string}
 */
class ContactDeVerification
{
    public function __construct(private readonly AnalyseurEmail $emails) {}

    /** @return array{canal: CanalVerification, destination: string}|null */
    public function pour(Model $demande): ?array
    {
        [$email, $telephone] = match (true) {
            $demande instanceof ESBTPCandidature => [$demande->email, $demande->telephone],
            $demande instanceof ESBTPReinscriptionDemande => [$demande->etudiant?->email_personnel ?: $demande->etudiant?->email, $demande->etudiant?->telephone],
            default => [null, null],
        };

        $email = trim((string) $email);
        if ($email !== '' && $this->emails->analyser($email)->joignable()) {
            return ['canal' => CanalVerification::Email, 'destination' => $email];
        }

        $numero = PhoneNormalizer::estMobileNational($telephone) ? PhoneNormalizer::toE164($telephone) : null;

        return $numero === null ? null : ['canal' => CanalVerification::Telephone, 'destination' => $numero];
    }
}
