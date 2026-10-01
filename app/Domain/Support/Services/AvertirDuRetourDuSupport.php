<?php

namespace App\Domain\Support\Services;

use App\Mail\Support\ReponseDuSupportMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avertit le rapporteur d'une demande KLASSCI Care : le support a repondu, ou
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

    /** @param  array<string, mixed>  $resume  le resume d'une demande, tel que le Master le rend */
    public function executer(User $user, array $resume, bool $aRepondu, bool $cloturee): void
    {
        $reference = (string) $resume['reference'];
        $statut = $resume['statut']['libelle'] ?? null;
        $corps = ($resume['derniere_reponse']['auteur'] ?? null) === 'SUPPORT'
            ? trim((string) ($resume['derniere_reponse']['corps'] ?? ''))
            : '';

        $titre = $aRepondu ? "Le support a répondu · {$reference}" : "Demande ".mb_strtolower((string) $statut, 'UTF-8')." · {$reference}";
        $message = $corps !== '' && $aRepondu
            ? mb_strimwidth($corps, 0, self::EXTRAIT_CLOCHE, '…')
            : (string) ($resume['titre'] ?? 'Votre demande au support KLASSCI a été mise à jour.');
        if ($cloturee && $aRepondu && $statut) {
            $message .= "\nStatut : {$statut}.";
        }

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
