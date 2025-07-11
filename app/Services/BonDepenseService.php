<?php

namespace App\Services;

use App\Models\ESBTPBonSortie;
use App\Models\ESBTPDepense;
use Illuminate\Support\Facades\DB;

class BonDepenseService
{
    /**
     * Get all approved bons de sortie that are not yet linked to a depense.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBonsApprouves()
    {
        return ESBTPBonSortie::where('statut', 'approuve')
            ->whereDoesntHave('depenses')
            ->with('approbateur')
            ->get();
    }

    /**
     * Link a bon de sortie to a new depense.
     *
     * @param int $bonId
     * @param array $depenseData
     * @return ESBTPDepense
     */
    public function linkBonToDepense($bonId, $depenseData)
    {
        return DB::transaction(function () use ($bonId, $depenseData) {
            $bon = ESBTPBonSortie::where('id', $bonId)->where('statut', 'approuve')->firstOrFail();

            if ($bon->depense) {
                throw new \Exception('Ce bon de sortie est déjà lié à une dépense.');
            }

            $depenseData['bon_sortie_id'] = $bon->id;

            $depense = ESBTPDepense::create($depenseData);

            return $depense;
        });
    }
} 