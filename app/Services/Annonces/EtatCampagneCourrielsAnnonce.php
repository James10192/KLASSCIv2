<?php

namespace App\Services\Annonces;

/**
 * Read-only state of an announcement email campaign.
 * Only provider-confirmed delivered rows count as delivered.
 * No state in this class can initiate or resume email dispatch.
 */
final class EtatCampagneCourrielsAnnonce
{
    private const EN_COURS = ['pending', 'processing', 'retrying', 'awaiting_dispatch', 'submitted', 'delayed'];

    /** @param array<string,int|string> $statuts
     *  @return array{status:string,total:int,delivered:int,failed:int,pending:int,percent:int}
     */
    public function calculer(array $statuts): array
    {
        $total = array_sum(array_map('intval', $statuts));
        $delivered = max(0, (int) ($statuts['delivered'] ?? 0));
        $failed = max(0, (int) ($statuts['failed'] ?? 0));
        $pending = 0;
        foreach (self::EN_COURS as $etat) {
            $pending += max(0, (int) ($statuts[$etat] ?? 0));
        }

        $status = match (true) {
            $total === 0 => 'no_recipients',
            $pending !== 0 => 'pending',
            $failed !== 0 => 'complete_with_failures',
            $delivered === $total => 'complete_delivered',
            default => 'pending', // unknown future statuses never imply completion
        };

        return [
            'status' => $status,
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'percent' => $total > 0 ? (int) floor(100 * $delivered / $total) : 0,
        ];
    }
}
