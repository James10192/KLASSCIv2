<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPVerificationContact;
use App\Services\Emails\AnalyseurEmail;
use App\Support\ColonnesDeployees;
use Illuminate\Support\Facades\RateLimiter;

/**
 * « Je ne reçois rien sur WhatsApp » : recevoir le code de réinscription par
 * e-mail, à l'adresse du dossier ou à une nouvelle.
 *
 * Le code partait au seul contact que le dossier désignait — e-mail joignable,
 * sinon WhatsApp — et un WhatsApp qui ne passait pas laissait la famille sans
 * aucune issue. Ici elle choisit le canal, et peut donner l'adresse qu'elle lit
 * vraiment.
 *
 * Une nouvelle adresse est rangée sur la DEMANDE (`email_contact`), jamais sur
 * le dossier de l'étudiant : seul un matricule et une date de naissance ont été
 * prouvés, et écrire l'adresse du dossier ouvrirait les réinitialisations de mot
 * de passe à quiconque connaît ce couple. Une fois l'adresse confirmée par code,
 * c'est elle qui reçoit la convocation (`emailRdv`) ; la scolarité la voit sur
 * la demande, et la reporte au dossier si elle le juge bon.
 */
class ChangementCanalVerification
{
    public const MOTIF_INVALIDE = 'code_invalide';

    public const MOTIF_EMAIL = 'email_invalide';

    public const MOTIF_CANAL = 'canal_indisponible';

    public const MOTIF_INDISPONIBLE = 'indisponible';

    public const MOTIF_TROP_TOT = 'trop_de_tentatives';

    private const MAX_PAR_HEURE = 3;

    public function __construct(
        private readonly AnalyseurEmail $emails,
        private readonly ContactDeVerification $contacts,
        private readonly DemarrageVerification $demarrage,
    ) {}

    /**
     * @return array{0: int, 1: array<string, mixed>} statut HTTP et corps
     */
    public function changer(string $demandeId, CanalVerification $canal, ?string $email): array
    {
        $ligne = ESBTPVerificationContact::query()->where('demande_id', $demandeId)->first();
        $demande = $ligne?->verifiable;

        if ($ligne === null || $ligne->estVerifiee() || ! $demande instanceof ESBTPReinscriptionDemande) {
            return [422, ['envoye' => false, 'motif' => self::MOTIF_INVALIDE]];
        }

        $cle = 'verif-canal:'.hash('sha256', $demandeId);
        if (RateLimiter::tooManyAttempts($cle, self::MAX_PAR_HEURE)) {
            return [429, ['envoye' => false, 'motif' => self::MOTIF_TROP_TOT, 'retry_after' => RateLimiter::availableIn($cle)]];
        }
        RateLimiter::hit($cle, 3600);

        $email = trim((string) $email);
        if ($email !== '' && ! $this->emails->analyser($email)->joignable()) {
            return [422, ['envoye' => false, 'motif' => self::MOTIF_EMAIL]];
        }

        $champs = [];
        if ($email !== '' && ColonnesDeployees::existe('esbtp_reinscription_demandes', 'email_contact')) {
            $champs['email_contact'] = mb_strtolower($email);
        }
        if (ColonnesDeployees::existe('esbtp_reinscription_demandes', 'canal_prefere')) {
            $champs['canal_prefere'] = $canal->value;
        }
        if ($champs !== []) {
            $demande->forceFill($champs)->save();
        }

        $contact = $this->contacts->pour($demande->fresh() ?? $demande);
        if ($contact === null || $contact['canal'] !== $canal) {
            return [422, ['envoye' => false, 'motif' => self::MOTIF_CANAL]];
        }

        $verification = $this->demarrage->demarrer($demande, false);

        return $verification === null
            ? [503, ['envoye' => false, 'motif' => self::MOTIF_INDISPONIBLE]]
            : [200, ['envoye' => true] + $verification->reponse()];
    }
}
