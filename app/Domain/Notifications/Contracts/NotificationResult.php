<?php

namespace App\Domain\Notifications\Contracts;

/**
 * DTO retourné par chaque notifier après tentative d'envoi.
 *
 * Contient le statut global, les canaux dispatchés avec leur état individuel,
 * et les erreurs éventuelles. Immuable.
 *
 * Exemple :
 *   $result = app(RelanceNotifier::class)->relanceEnvoyee($relance);
 *   if ($result->success) { ... }
 *   foreach ($result->dispatched as $channel => $state) { ... }
 */
class NotificationResult
{
    /**
     * @param array<string, string> $dispatched ['email' => 'sent', 'whatsapp' => 'queued', 'app' => 'created']
     * @param array<string, string> $errors ['sms' => 'Orange contract expired']
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly array $dispatched = [],
        public readonly array $errors = [],
    ) {
    }

    public static function success(string $message = '', array $dispatched = []): self
    {
        return new self(true, $message, $dispatched);
    }

    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, $message, [], $errors);
    }

    public static function partial(string $message, array $dispatched, array $errors): self
    {
        return new self(true, $message, $dispatched, $errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'dispatched' => $this->dispatched,
            'errors' => $this->errors,
        ];
    }
}
