<?php

namespace App\Domain\Bulletins\Taches;

use App\Helpers\SettingsHelper;
use App\Mail\TacheBulletinsTermineeMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient le demandeur (et les personnes qui ont rejoint la tâche) qu'un
 * travail sur les bulletins est fini.
 *
 * Trois canaux, chacun indépendant :
 *
 *  - la cloche, posée dès la fin, y compris dans la requête d'un onglet ;
 *  - le toast global, qui lit la table des tâches, pas ce service ;
 *  - l'e-mail, envoyé UNIQUEMENT par la planification, et seulement si la
 *    personne n'a pas vu la fin à l'écran dans les minutes qui suivent. Un
 *    serveur SMTP lent ne retient ainsi jamais une requête web, et quelqu'un
 *    qui regardait sa page ne reçoit pas un courriel inutile.
 *
 * Un canal qui échoue ne fait pas tomber les autres, et son échec est consigné
 * sur la tâche — jamais avalé.
 */
class NotificationTachesBulletins
{
    /** Réglage d'instance : minutes d'attente avant l'e-mail. */
    public const REGLAGE_DELAI_COURRIEL = 'bulletins_taches_delai_courriel_minutes';

    public const DELAI_COURRIEL_DEFAUT = 3;

    public function __construct(private readonly NotificationService $notifications) {}

    /** La cloche : immédiate, rejouée par la planification si elle a échoué. */
    public function notifier(BulletinTache $tache): void
    {
        if ($tache->cloche_at !== null || ! $tache->estFinale()) {
            return;
        }

        $destinataires = $tache->destinataires();
        if ($destinataires->isEmpty()) {
            Log::warning('Tâche bulletins #'.$tache->id.' terminée sans demandeur à prévenir');
            $tache->forceFill(['cloche_at' => now(), 'notifiee_at' => now()])->save();

            return;
        }

        $reussie = $tache->statut === BulletinTache::TERMINEE;
        $lien = SuiviTachesBulletins::lienResultat($tache);
        $toutes = true;

        foreach ($destinataires as $destinataire) {
            $cree = $this->notifications->createNotification(
                $destinataire['user'],
                $reussie ? $tache->libelle().' : terminé' : $tache->libelle().' : échec',
                (string) $tache->message,
                $reussie ? 'success' : 'error',
                $lien
            );
            $toutes = $toutes && $cree !== null;
        }

        // createNotification consigne déjà son échec ; on garde la tâche « à
        // prévenir » pour que la planification réessaie.
        if ($toutes) {
            $tache->forceFill(['cloche_at' => now()])->save();
        }
    }

    /**
     * Passage de la planification : cloches manquées, puis e-mails dus.
     */
    public function rattraper(): int
    {
        $traitees = 0;

        $sansCloche = BulletinTache::query()
            ->whereIn('statut', BulletinTache::FINAUX)
            ->whereNull('cloche_at')
            ->where('terminee_at', '>=', now()->subDay())
            ->limit(20)
            ->get();
        foreach ($sansCloche as $tache) {
            $this->notifier($tache);
            $traitees++;
        }

        $dues = BulletinTache::query()
            ->whereIn('statut', BulletinTache::FINAUX)
            ->whereNull('notifiee_at')
            ->where('terminee_at', '<=', now()->subMinutes($this->delaiCourriel()))
            ->where('terminee_at', '>=', now()->subDay())
            ->limit(20)
            ->get();
        foreach ($dues as $tache) {
            $this->envoyerLesCourriels($tache);
            $traitees++;
        }

        return $traitees;
    }

    private function envoyerLesCourriels(BulletinTache $tache): void
    {
        $lien = SuiviTachesBulletins::lienResultat($tache);
        $absolu = $lien !== null ? url($lien) : null;
        $envoye = false;
        $erreur = null;

        foreach ($tache->destinataires() as $destinataire) {
            if ($destinataire['vue']) {
                continue;
            }

            /** @var User $user */
            $user = $destinataire['user'];
            $adresse = trim((string) ($user->email ?? ''));

            // Une adresse non vérifiée peut appartenir à quelqu'un d'autre : on
            // n'y envoie pas de lien vers des bulletins.
            if ($adresse === '' || $user->email_verified_at === null) {
                continue;
            }

            try {
                Mail::to($adresse)->send(new TacheBulletinsTermineeMail($tache, $absolu, $user));
                $envoye = true;
            } catch (\Throwable $e) {
                Log::error('Tâche bulletins #'.$tache->id.' : e-mail de fin non envoyé à l\'utilisateur #'.$user->id, ['exception' => $e]);
                $erreur = mb_substr($e->getMessage(), 0, 250);
            }
        }

        // Tentative faite (ou devenue inutile) : on ne recommence pas à chaque minute.
        $tache->forceFill(array_filter([
            'notifiee_at' => now(),
            'email_envoye_at' => $envoye ? now() : null,
            'email_erreur' => $erreur,
        ], fn ($v) => $v !== null))->save();
    }

    private function delaiCourriel(): int
    {
        try {
            $minutes = (int) SettingsHelper::get(self::REGLAGE_DELAI_COURRIEL, self::DELAI_COURRIEL_DEFAUT);
        } catch (\Throwable $e) {
            Log::warning('Délai de l\'e-mail des tâches bulletins illisible, valeur par défaut : '.$e->getMessage());
            $minutes = self::DELAI_COURRIEL_DEFAUT;
        }

        return max(0, $minutes);
    }
}
