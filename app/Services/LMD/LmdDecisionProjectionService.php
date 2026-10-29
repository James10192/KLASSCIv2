<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LmdDecisionProjectionService
{
    public function publish(ESBTPLMDJury $jury, ?int $actorId): int
    {
        return DB::transaction(function () use ($jury, $actorId): int {
            $decisions = $jury->decisions()->lockForUpdate()->get();
            $projected = 0;

            foreach ($decisions as $decision) {
                if ($decision->bulletin_id === null) {
                    throw new RuntimeException("La décision de l'étudiant {$decision->etudiant_id} n'est liée à aucun bulletin.");
                }

                $updated = ESBTPLMDBulletin::query()
                    ->forJury($jury)
                    ->whereKey($decision->bulletin_id)
                    ->where('etudiant_id', $decision->etudiant_id)
                    ->update([
                        'decision_deliberation' => $decision->decision,
                        'is_published' => true,
                        'updated_by' => $actorId,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException("Le bulletin de l'étudiant {$decision->etudiant_id} est introuvable ou incohérent.");
                }

                $projected++;
            }

            return $projected;
        }, 3);
    }
}
