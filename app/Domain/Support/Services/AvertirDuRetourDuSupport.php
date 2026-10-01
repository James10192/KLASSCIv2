<?php

namespace App\Domain\Support\Services;

use App\Mail\Support\ReponseDuSupportMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avertit le rapporteur d'une demande d'aide : le support a repondu, ou
 * la demande est resolue / fermee.
 *
 * Toujours dans l'application (cloche). Par e-mail en plus, seulement vers une
 * adresse confirmee (AdresseJoignable) : le contenu d'une demande ne part pas
 * vers une adresse dont personne n'a prouve qu'elle est la bonne. Un e-mail
 * qui echoue ne retient pas l'avertissement dans l'application.
 */
class AvertirDuRetourDuSupport
{
    private const EXTRAIT_CLOCHE = 240;

    private const EXTRAIT_COURRIEL = 600;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * « Le support a répondu à « titre » », ou « Votre demande « titre » est
     * résolue ». Sans titre, la référence le remplace. Partagé avec l'e-mail.
     *
     * @param  array<string, mixed>  $resume
     */
    public static function titre(array $resume, bool $aRepondu): string
    {
        $objet = trim((string) ($resume['titre'] ?? ''));
        $objet = $objet !== '' ? '« '.mb_strimwidth($objet, 0, 90, '…').' »' : (string) ($resume['reference'] ?? '');

        return self::formuler($objet, (array) ($resume['statut'] ?? []), $aRepondu);
    }

    /**
     * La phrase elle-même. Sans objet, elle parle de « votre demande » : c'est
     * l'en-tête du courriel, dont la carte juste en dessous porte le titre.
     *
     * @param  array{libelle?: ?string, code?: ?string}  $statut
     */
    public static function formuler(?string $objet, array $statut, bool $aRepondu): string
    {
        if ($aRepondu) {
            return 'Le support a répondu à '.($objet ?? 'votre demande');
        }
        $sujet = $objet === null ? 'Votre demande' : "Votre demande {$objet}";
        // « est action requise » ne se lit pas : ce statut se dit autrement.
        if (($statut['code'] ?? null) === 'ACTION_REQUISE') {
            return "{$sujet} attend une action de votre part";
        }

        return "{$sujet} est ".mb_strtolower((string) ($statut['libelle'] ?? 'mise à jour'), 'UTF-8');
    }

    /** @param  array<string, mixed>  $resume  le resume d'une demande, tel que le Master le rend */
    public function executer(User $user, array $resume, bool $aRepondu, bool $cloturee): void
    {
        $reference = (string) $resume['reference'];
        $statut = $resume['statut']['libelle'] ?? null;
        $corps = ($resume['derniere_reponse']['auteur'] ?? null) === 'SUPPORT'
            ? trim((string) ($resume['derniere_reponse']['corps'] ?? ''))
            : '';

        $titre = self::titre($resume, $aRepondu);
        $message = $corps !== '' && $aRepondu
            ? mb_strimwidth($corps, 0, self::EXTRAIT_CLOCHE, '…')
            : 'Votre demande a été mise à jour.';
        if ($cloturee && $aRepondu && $statut) {
            $message .= "\nStatut : {$statut}.";
        }
        // La personne reconnait sa demande a son titre ; la reference reste
        // dans le corps, pour l'echange avec l'equipe support.
        $message .= "\nRéférence : {$reference}";

        $this->notifications->createNotification(
            $user,
            mb_substr($titre, 0, 255),
            $message,
            $cloturee ? 'success' : 'info',
            route('support.demandes.show', $reference, false),
        );

        $adresse = AdresseJoignable::pour($user);
        if ($adresse === null) {
            return;
        }

        try {
            Mail::to($adresse)->send(new ReponseDuSupportMail([
                'reference' => $reference,
                'titre' => $resume['titre'] ?? null,
                'nom' => (string) $user->name,
                'a_repondu' => $aRepondu,
                'statut_libelle' => $statut,
                'statut_code' => $resume['statut']['code'] ?? null,
                'extrait' => $corps !== '' && $aRepondu ? mb_strimwidth($corps, 0, self::EXTRAIT_COURRIEL, '…') : null,
                'lien' => route('support.demandes.show', $reference),
            ]));
        } catch (Throwable $e) {
            Log::warning('KLASSCI Care : e-mail de retour du support non envoyé', ['reference' => $reference, 'user_id' => $user->getKey(), 'erreur' => $e->getMessage()]);
        }
    }
}
