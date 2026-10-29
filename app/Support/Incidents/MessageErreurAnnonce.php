<?php
namespace App\Support\Incidents;

final class MessageErreurAnnonce
{
    public static function pour(string $operation, ?string $reference): string
    {
        $action = match ($operation) {
            'create' => "créer l'annonce",
            'update' => "modifier l'annonce",
            'delete' => "supprimer l'annonce",
            default => "effectuer cette opération",
        };
        $message = "Impossible de {$action} pour le moment. Réessayez ou contactez le support.";
        if ($reference !== null && preg_match('/^[A-Za-z0-9_-]{16,64}$/D', $reference)) {
            $message .= ' Code de suivi : '.$reference;
        }
        return $message;
    }
}
