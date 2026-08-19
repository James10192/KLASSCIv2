<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\DB;

class UnpaidStudentCountService
{
    public function count(?int $anneeId = null): int
    {
        $anneeId = $anneeId ?: ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');
        if (! $anneeId) {
            return 0;
        }

        try {
            $rows = DB::table('esbtp_frais_subscriptions as fs')
                ->join('esbtp_inscriptions as i', 'fs.inscription_id', '=', 'i.id')
                ->leftJoin(DB::raw("(SELECT inscription_id, frais_category_id, SUM(montant) as total_paye FROM esbtp_paiements WHERE status IN ('valide', 'validé') AND deleted_at IS NULL GROUP BY inscription_id, frais_category_id) as p"), function ($join) {
                    $join->on('p.inscription_id', '=', 'i.id')
                        ->on('p.frais_category_id', '=', 'fs.frais_category_id');
                })
                ->where('i.annee_universitaire_id', $anneeId)
                ->where('i.status', 'active')
                ->where('fs.is_active', true)
                ->selectRaw('i.etudiant_id')
                ->groupBy('i.etudiant_id')
                ->havingRaw('SUM(fs.amount) - SUM(COALESCE(p.total_paye, 0)) > 0')
                ->pluck('etudiant_id');

            return $rows->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}