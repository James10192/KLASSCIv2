<?php

namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Notifications\WorkflowNextStepNotification;
use App\Services\WorkflowNextStepResolver;
use Illuminate\Support\Facades\Notification;

/**
 * Listener du WorkflowStepCompleted : notifie les détenteurs de la permission
 * pour l'étape suivante, en EXCLUANT l'acteur courant (anti-self-notif —
 * gérée côté UI via modal "tu peux maintenant X").
 */
class NotifyWorkflowNextStepActors
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
