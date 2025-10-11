<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle de tracking des notifications parents multi-canal
 *
 * Permet :
 * - Analyse ROI (coût total par canal)
 * - Debugging (voir échecs d'envoi)
 * - Statistiques (taux de livraison par canal)
 * - Audit trail complet
 */
class ParentNotificationLog extends Model
{
    protected $fillable = [
        'parent_id',
        'etudiant_id',
        'notification_type',
        'channel',
        'status',
        'recipient',
        'message_preview',
        'external_id',
        'cost_fcfa',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'cost_fcfa' => 'decimal:2',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Relation avec le parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    /**
     * Relation avec l'étudiant
     */
    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Marquer comme envoyé avec succès
     */
    public function markAsSent($externalId = null)
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'external_id' => $externalId,
        ]);
    }

    /**
     * Marquer comme livré (via webhook)
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Marquer comme lu (via webhook)
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scope : Filtrer par canal
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope : Filtrer par type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope : Filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope : Statistiques par canal (derniers 30 jours)
     */
    public function scopeStatsLast30Days($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(cost_fcfa) as total_cost,
                AVG(cost_fcfa) as avg_cost
            ')
            ->groupBy('channel');
    }

    /**
     * Calculer coût total pour un parent
     */
    public static function getTotalCostForParent($parentId, $days = 30)
    {
        return self::where('parent_id', $parentId)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('cost_fcfa');
    }

    /**
     * Calculer coût total par canal
     */
    public static function getTotalCostByChannel($channel, $days = 30)
    {
        return self::where('channel', $channel)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('cost_fcfa');
    }

    /**
     * Obtenir taux de succès par canal
     */
    public static function getSuccessRateByChannel($channel, $days = 30)
    {
        $stats = self::where('channel', $channel)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("sent", "delivered", "read") THEN 1 ELSE 0 END) as success
            ')
            ->first();

        if ($stats && $stats->total > 0) {
            return round(($stats->success / $stats->total) * 100, 2);
        }

        return 0;
    }
}
