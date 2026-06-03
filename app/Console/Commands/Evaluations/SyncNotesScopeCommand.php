<?php

namespace App\Console\Commands\Evaluations;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Console\Command;

/**
 * Sync the denormalized columns (classe_id, matiere_id, semestre) on esbtp_notes
 * to match their parent evaluation's values.
 *
 * Notes were denormalized for fast queries, but they don't auto-sync when the
 * evaluation is updated. Run this command after a bulk evaluation edit or
 * when migrating data, to fix stale note columns.
 */
class SyncNotesScopeCommand extends Command
{
    protected $signature = 'evaluations:sync-notes
                            {--evaluation= : Sync only this evaluation ID (default: all)}
                            {--dry : Show what would be updated without writing}';

    protected $description = 'Sync esbtp_notes.{classe_id, matiere_id, semestre} from their parent evaluation';

    public function handle(): int
    {
        $evaluationId = $this->option('evaluation');
        $dry = (bool) $this->option('dry');

        $query = ESBTPEvaluation::query();
        if ($evaluationId) {
            $query->where('id', $evaluationId);
        }

        $totalNotesFixed = 0;
        $evaluationsTouched = 0;

        $query->chunkById(100, function ($evaluations) use (&$totalNotesFixed, &$evaluationsTouched, $dry) {
            foreach ($evaluations as $eval) {
                $expectedSemestre = (int) str_replace('semestre', '', (string) $eval->periode);

                $staleCount = ESBTPNote::where('evaluation_id', $eval->id)
                    ->where(function ($q) use ($eval, $expectedSemestre) {
                        $q->where('classe_id', '!=', $eval->classe_id)
                            ->orWhere('matiere_id', '!=', $eval->matiere_id)
                            ->orWhere('semestre', '!=', $expectedSemestre);
                    })
                    ->count();

                if ($staleCount === 0) {
                    continue;
                }

                $evaluationsTouched++;
                $totalNotesFixed += $staleCount;
                $this->line(sprintf(
                    '  eval#%d (%s) -> %d note(s) stale | target: classe=%d, matiere=%d, sem=%d',
                    $eval->id, $eval->titre, $staleCount,
                    $eval->classe_id, $eval->matiere_id, $expectedSemestre
                ));

                if (! $dry) {
                    ESBTPNote::where('evaluation_id', $eval->id)->update([
                        'classe_id' => $eval->classe_id,
                        'matiere_id' => $eval->matiere_id,
                        'semestre' => $expectedSemestre,
                    ]);
                }
            }
        });

        $verb = $dry ? 'would be' : 'were';
        $this->newLine();
        $this->info("Total: {$evaluationsTouched} evaluation(s), {$totalNotesFixed} note(s) {$verb} synced.");
        if ($dry) {
            $this->warn('[DRY-RUN] Pass without --dry to actually update.');
        }

        return self::SUCCESS;
    }
}
