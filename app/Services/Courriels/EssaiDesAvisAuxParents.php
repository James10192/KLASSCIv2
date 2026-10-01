<?php

namespace App\Services\Courriels;

use App\Mail\Parents\AvisDExemple;
use App\Services\MailPulse\DestinatairesDeTest;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mime\Email;

/**
 * Envoie un vrai avis aux parents — vraie classe Mailable, vrai gabarit, mailer
 * de l'école (MailPulse quand il est réglé) — aux SEULES adresses de test.
 *
 * Les destinataires ne viennent que de `DestinatairesDeTest` : ce service n'a
 * aucun paramètre d'adresse, il ne peut donc pas écrire à un parent réel.
 */
class EssaiDesAvisAuxParents
{
    public const TOUS = 'tous';

    public const PREFIXE_SUJET = '[Essai] ';

    public function __construct(private DestinatairesDeTest $destinataires) {}

    /**
     * @return array{ok: bool, dryRun: bool, avis: list<array{avis: string, destinataire: string, sujet: string, statut: string, erreur?: string}>}
     *
     * @throws ValidationException avis inconnu, ou aucune adresse de test active
     */
    public function envoyer(string $avis, bool $dryRun): array
    {
        $noms = $this->avisDemandes($avis);
        $adresses = $this->destinataires->courriels();

        if ($adresses === []) {
            throw ValidationException::withMessages([
                'destinataires' => "Aucune adresse de test active (réglage « mailpulse_test_email_recipients »). "
                    . "L'essai n'écrit jamais à un parent réel : configurez d'abord une adresse de test.",
            ]);
        }

        $lignes = [];
        foreach ($noms as $nom) {
            foreach ($adresses as $adresse) {
                $lignes[] = $this->envoyerUn($nom, $adresse, $dryRun);
            }
        }

        return [
            'ok' => ! in_array('erreur', array_column($lignes, 'statut'), true),
            'dryRun' => $dryRun,
            'avis' => $lignes,
        ];
    }

    /** @return list<string> */
    private function avisDemandes(string $avis): array
    {
        if ($avis === self::TOUS) {
            return AvisDExemple::noms();
        }

        if (! array_key_exists($avis, AvisDExemple::MAILABLES)) {
            throw ValidationException::withMessages([
                'avis' => "Avis inconnu « {$avis} ». Valeurs admises : " . self::TOUS . ', '
                    . implode(', ', AvisDExemple::noms()) . '.',
            ]);
        }

        return [$avis];
    }

    private function envoyerUn(string $nom, string $adresse, bool $dryRun): array
    {
        $classe = AvisDExemple::MAILABLES[$nom];
        $sujet = self::PREFIXE_SUJET . $this->sujetDe($classe);
        $ligne = ['avis' => $nom, 'destinataire' => $adresse, 'sujet' => $sujet];

        if ($dryRun) {
            return $ligne + ['statut' => 'simulé'];
        }

        /** @var Mailable $courriel */
        $courriel = new $classe(AvisDExemple::donnees());
        // Le sujet est posé par build() à l'envoi : le rappel des messages Symfony
        // passe après, c'est le seul endroit où le préfixe tient.
        $courriel->withSymfonyMessage(fn (Email $message) => $message->subject($sujet));

        try {
            Mail::to($adresse)->send($courriel);
        } catch (\Throwable $e) {
            Log::warning('Essai avis parents : envoi échoué', ['avis' => $nom, 'erreur' => $e->getMessage()]);

            return $ligne + ['statut' => 'erreur', 'erreur' => $e->getMessage()];
        }

        return $ligne + ['statut' => 'envoyé'];
    }

    /** Le sujet que pose la classe elle-même, lu sur une instance jetable. */
    private function sujetDe(string $classe): string
    {
        $courriel = new $classe(AvisDExemple::donnees());
        $courriel->build();

        return (string) $courriel->subject;
    }
}
