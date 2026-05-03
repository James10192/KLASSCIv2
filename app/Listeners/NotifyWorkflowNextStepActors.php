<?php

namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Notifications\WorkflowNextStepNotification;
use App\Services\WorkflowNextStepResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Listener du WorkflowStepCompleted : notifie les détenteurs de la permission
 * pour l'étape suivante, en EXCLUANT l'acteur courant (anti-self-notif —
 * gérée côté UI via modal "tu peux maintenant X").
 *
 * Queued pour ne pas bloquer la requête utilisateur qui a déclenché l'event :
 * la résolution des recipients fait une requête DB indexée par permission, et
 * `Notification::send` itère sur N users.
 */
class NotifyWorkflowNextStepActors implements ShouldQueue
{
    public function __construct(private readonly WorkflowNextStepResolver $resolver)
    {
    }

    public function handle(WorkflowStepCompleted $event): void
    {
        $recipients = $this->resolver->recipients($event->type, $event->actor->id);
        if ($recipients->isEmpty()) {
            return;
        }
        Notification::send($recipients, new WorkflowNextStepNotification($event));
    }
}
