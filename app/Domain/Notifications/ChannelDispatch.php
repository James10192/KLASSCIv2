<?php

namespace App\Domain\Notifications;

/**
 * Résultat d'un dispatch via un NotificationChannelInterface.
 *
 * - Mode manuel (ex WhatsApp deeplink) : `automated=false`, `deeplinkUrl` rempli.
 *   L'utilisateur clique le lien pour finaliser l'envoi.
 * - Mode automatique (ex WhatsApp Business API future) : `automated=true`,
 *   `deeplinkUrl=null`, `success` reflète le statut API.
 */
final class ChannelDispatch
{
    public function __construct(
        public readonly string $channel,
        public readonly bool $automated,
        public readonly bool $success,
        public readonly ?string $deeplinkUrl = null,
        public readonly ?string $errorReason = null,
    ) {}

    public static function manual(string $channel, string $deeplinkUrl): self
    {
        return new self(
            channel: $channel,
            automated: false,
            success: true,
            deeplinkUrl: $deeplinkUrl,
        );
    }

    public static function unavailable(string $channel, string $reason): self
    {
        return new self(
            channel: $channel,
            automated: false,
            success: false,
            deeplinkUrl: null,
            errorReason: $reason,
        );
    }

    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'automated' => $this->automated,
            'success' => $this->success,
            'deeplink_url' => $this->deeplinkUrl,
            'error_reason' => $this->errorReason,
        ];
    }
}
