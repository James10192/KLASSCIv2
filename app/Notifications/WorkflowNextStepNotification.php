<?php

namespace App\Notifications;

use App\Events\WorkflowStepCompleted;
use App\Services\WorkflowNextStepResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkflowNextStepNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly WorkflowStepCompleted $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $resolver = app(WorkflowNextStepResolver::class);
        return [
            'type'        => $this->event->type,
            'actor_id'    => $this->event->actor->id,
            'actor_name'  => $this->event->actor->name,
            'context'     => $this->event->context,
            'next_label'  => $resolver->nextLabel($this->event->type),
            'next_url'    => $resolver->nextActionUrl($this->event->type, $this->event->context),
            'next_perm'   => $resolver->nextPermission($this->event->type),
            'created_at'  => now()->toIso8601String(),
        ];
    }
}
