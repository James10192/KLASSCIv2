<?php

namespace App\Services\WhatsApp;

use App\Models\ParentNotificationLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service de métriques WhatsApp pour dashboards monitoring (Phase 16 Plan v4).
 *
 * Consomme parent_notification_logs pour calculer KPIs :
 *  - Delivery rate (sent → delivered)
 *  - Read rate (delivered → read)
 *  - Cost FCFA mensuel par tenant
 *  - Opt-out rate
 *  - Top notification types
 *  - Latence moyenne envoi → delivered
 */
class WhatsAppMetricsService
{
    /**
     * KPIs globaux WhatsApp sur fenêtre temporelle.
     *
     * @return array{
     *     total_sent: int,
     *     delivered: int,
     *     read: int,
     *     failed: int,
     *     delivery_rate: float,
     *     read_rate: float,
     *     cost_total_fcfa: float,
     *     by_type: array<string, int>
     * }
     */
    public function kpis(int $daysBack = 30): array
    {
        $since = now()->subDays($daysBack);

        $base = ParentNotificationLog::where('channel', 'whatsapp')
            ->where('created_at', '>=', $since);

        $total = (clone $base)->count();
        $sent = (clone $base)->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $delivered = (clone $base)->whereIn('status', ['delivered', 'read'])->count();
        $read = (clone $base)->where('status', 'read')->count();
        $failed = (clone $base)->where('status', 'failed')->count();
        $cost = (clone $base)->sum('cost_fcfa');

        $byType = (clone $base)
            ->select('notification_type', DB::raw('COUNT(*) as c'))
            ->groupBy('notification_type')
            ->pluck('c', 'notification_type')
            ->toArray();

        return [
            'total_sent' => $total,
            'delivered' => $delivered,
            'read' => $read,
            'failed' => $failed,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0,
            'read_rate' => $delivered > 0 ? round(($read / $delivered) * 100, 2) : 0,
            'cost_total_fcfa' => (float) $cost,
            'by_type' => $byType,
        ];
    }

    /**
     * Latence moyenne envoi → delivered en secondes (P50, P95).
     */
    public function latency(int $daysBack = 7): array
    {
        $since = now()->subDays($daysBack);

        $rows = DB::table('parent_notification_logs')
            ->where('channel', 'whatsapp')
            ->whereNotNull('sent_at')
            ->whereNotNull('delivered_at')
            ->where('sent_at', '>=', $since)
            ->select(DB::raw('TIMESTAMPDIFF(SECOND, sent_at, delivered_at) as latency_s'))
            ->orderBy('latency_s')
            ->pluck('latency_s')
            ->toArray();

        if (empty($rows)) {
            return ['p50_seconds' => 0, 'p95_seconds' => 0, 'samples' => 0];
        }

        $count = count($rows);
        $p50 = $rows[(int) ($count * 0.5)];
        $p95 = $rows[(int) ($count * 0.95)];

        return [
            'p50_seconds' => $p50,
            'p95_seconds' => $p95,
            'samples' => $count,
        ];
    }

    /**
     * Top numéros opt-out via STOP keyword (Phase 7 webhook).
     */
    public function topOptOuts(int $daysBack = 30, int $limit = 10): array
    {
        $since = now()->subDays($daysBack);

        return DB::table('whatsapp_inbound_messages')
            ->where('received_at', '>=', $since)
            ->whereIn(DB::raw('UPPER(TRIM(body))'), ['STOP', 'ARRET', 'ARRÊT', 'UNSUBSCRIBE'])
            ->select('from_phone', DB::raw('COUNT(*) as c'), DB::raw('MAX(received_at) as last_at'))
            ->groupBy('from_phone')
            ->orderByDesc('c')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Coût FCFA mensuel par tenant — base pour budget alerts (Phase 4).
     */
    public function monthlyCostByChannel(?Carbon $month = null): array
    {
        $month = $month ?? now()->startOfMonth();

        return DB::table('parent_notification_logs')
            ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
            ->select('channel', DB::raw('SUM(cost_fcfa) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();
    }
}
