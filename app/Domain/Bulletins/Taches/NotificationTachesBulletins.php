<?php

namespace App\Domain\Bulletins\Taches;

use App\Mail\TacheBulletinsTermineeMail;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient le demandeur qu'un travail sur les bulletins est fini.
 *
 * Trois canaux, chacun indépendant : la cloche de l'application, le toast
 * global (qui lit la table des tâches, pas ce service) et l'e-mail, envoyé
 * seulement à une adresse vérifiée. Un canal qui échoue ne fait pas tomber les
 * autres, et son échec est consigné sur la tâche — jamais avalé.
 */
class NotificationTachesBulletins
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function notifier(BulletinTache $tache): void
    {
        if ($tache->notifiee_at !== null || ! $tache->estFinale()) {
            return;
        }

        $user = $tache->user;
        if ($user === null) {
            Log::warning('Tâche bulletins #'.$tache->id.' terminée sans demandeur à prévenir');
            $tache->forceFill(['notifiee_at' => now()])->save();

            return;
        }

        $reussie = $tache->statut === BulletinTache::TERMINEE;
        $lien = SuiviTachesBulletins::lienResultat($tache);

        $cree = $this->notifications->createNotification(
            $user,
            $reussie ? $tache->libelle().' : terminé' : $tache->libelle().' : échec',
            (string) $tache->message,
            $reussie ? 'success' : 'error',
            $lien
        );

        // createNotification consigne déjà son échec ; on garde la tâche « à
        // prévenir » pour que la planification réessaie.
        if ($cree === null) {
            return;
        }

        $tache->forceFill(['notifiee_at' => now()])->save();
        $this->envoyerLeCourriel($tache, $lien);
    }

    /**
     * Rejoue la notification des tâches finies sans l'avoir reçue — processus
     * tué juste après la fin, base momentanément indisponible.
     */
    public function rattraper(): int
    {
        $taches = BulletinTache::query()
            ->whereIn('statut', BulletinTache::FINAUX)
            ->whereNull('notifiee_at')
            ->where('terminee_at', '>=', now()->subDay())
            ->limit(20)
            ->get();

        foreach ($taches as $tache) {
            $this->notifier($tache);
        }

        return $taches->count();
    }

    private function envoyerLeCourriel(BulletinTache $tache, ?string $lien): void
    {
        $user = $tache->user;
        $adresse = trim((string) ($user->email ?? ''));

        // Une adresse non vérifiée peut appartenir à quelqu'un d'autre : on n'y
        // envoie pas de lien vers des bulletins.
        if ($adresse === '' || $user->email_verified_at === null) {
            return;
        }

        try {
            Mail::to($adresse)->send(new TacheBulletinsTermineeMail($tache, $lien));
            $tache->forceFill(['email_envoye_at' => now(), 'email_erreur' => null])->save();
        } catch (\Throwable $e) {
            Log::error('Tâche bulletins #'.$tache->id.' : e-mail de fin non envoyé', ['exception' => $e]);
            $tache->forceFill(['email_erreur' => mb_substr($e->getMessage(), 0, 250)])->save();
        }
    }
}
