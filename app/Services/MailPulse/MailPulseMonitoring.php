<?php

namespace App\Services\MailPulse;

use App\Models\ParentNotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Tableau de bord LECTURE SEULE fondé sur l'outbox parent existante.
 *
 * Chaque instance KLASSCI possède sa propre base de données. Le fournisseur
 * renvoie une acceptation de dispatch, pas une preuve de livraison. Les
 * anciennes lignes "sent" ne sont donc jamais comptées dans "delivered".
 */
final class MailPulseMonitoring
{
    /** @return array{available:bool,counts:array<string,int>,entries:\Illuminate\Support\Collection} */
    public function snapshot(): array
    {
        $empty = [
            'total' => 0,
            'pending' => 0,
            'accepted' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
        ];
        $unavailable = ['available' => false, 'counts' => $empty, 'entries' => collect()];

        try {
            if (! Schema::hasTable('parent_notification_logs')) {
                return $unavailable;
            }

            $base = ParentNotificationLog::query()
                ->where('metadata->provider', 'mailpulse')
                ->where('created_at', '>=', now()->subDays(7));

            $groups = (clone $base)->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')->all();

            $counts = [
                'total' => array_sum(array_map('intval', $groups)),
                'pending' => (int) ($groups['pending'] ?? 0),
                'accepted' => (int) ($groups['sent'] ?? 0) + (int) ($groups['accepted'] ?? 0),
                'delivered' => (int) ($groups['delivered'] ?? 0),
                'read' => (int) ($groups['read'] ?? 0),
                'failed' => (int) ($groups['failed'] ?? 0),
            ];

            $entries = (clone $base)
                ->orderByDesc('id')
                ->limit(30)
                ->get(['id', 'created_at', 'notification_type', 'channel', 'status', 'attempt_count', 'metadata']);

            return ['available' => true, 'counts' => $counts, 'entries' => $entries];
        } catch (\Throwable $exception) {
            Log::warning('MailPulse monitoring unavailable', [
                'exception' => $exception::class,
            ]);

            return $unavailable;
        }
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending', 'queued' => 'En attente',
            'sent', 'accepted' => 'Accepté / émis — remise non confirmée',
            'delivered' => 'Livré (statut enregistré)',
            'read' => 'Lu (statut enregistré)',
            'failed' => 'Échec',
            default => 'Autre état (' . preg_replace('/[^a-z0-9_-]/i', '', $status) . ')',
        };
    }
}
