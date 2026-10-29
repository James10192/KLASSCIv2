<?php

namespace App\Domain\Support\Services;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

/**
 * Une action de scolarité ne doit jamais être adressée à l'étudiant qui l'a
 * signalée. Deux permissions effectives sont nécessaires pour le personnel,
 * sans dépendance à un nom de rôle, un ID utilisateur ou une école en dur.
 */
final class AvertirAdministration
{
    private const PERMISSION_LECTURE = 'support.tickets.view_school';
    private const PERMISSION_ACTION = 'support.tickets.respond_school';

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /** Nombre de personnes effectivement notifiées. Zéro => retenter au prochain suivi. */
    public function executer(array $resume): int
    {
        $reference = (string) ($resume['reference'] ?? '');
        $date = (string) ($resume['derniere_demande_etablissement_le'] ?? '');
        if (preg_match('/^KC-\d{4}-\d{6,}$/', $reference) !== 1 || $date === '') {
            return 0;
        }

        if (! Permission::query()->where('name', self::PERMISSION_ACTION)->exists()) {
            Log::warning('KLASSCI Care : permission d intervention administration non synchronisée', ['reference' => $reference]);
            return 0;
        }

        $link = route('support.demandes.show', [$reference, 'portee' => 'ecole'], false);
        $title = 'Action attendue de l’administration — '.$reference;
        $message = 'Le support KLASSCI sollicite une intervention de votre établissement. '
            .'Ouvrez la demande dans votre espace support. Référence : '.$reference.' ; événement : '.$date;
        $envoyees = 0;
        User::permission(self::PERMISSION_ACTION)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($personnes) use ($title, $message, $link, &$envoyees) {
                foreach ($personnes as $user) {
                    // La permission de réponse seule ne donne pas accès à tous
                    // les tickets. Une révocation doit prendre effet immédiatement.
                    if (! $user->can(self::PERMISSION_LECTURE)) {
                        continue;
                    }
                    // Le même événement ne crée pas de doublons si un envoi
                    // à une autre personne a échoué avant la fin de la boucle.
                    if (Notification::where('user_id', $user->getKey())
                        ->where('title', $title)->where('message', $message)->exists()) {
                        $envoyees++;
                        continue;
                    }
                    if ($this->notifications->createNotification($user, $title, $message, 'warning', $link) !== null) {
                        $envoyees++;
                    }
                }
            });

        if ($envoyees === 0) {
            Log::warning('KLASSCI Care : aucune personne habilitée notifiée de l action administration', ['reference' => $reference]);
        }

        return $envoyees;
    }
}
