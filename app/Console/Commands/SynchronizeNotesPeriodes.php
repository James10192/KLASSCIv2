<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use Illuminate\Support\Facades\DB;

class SynchronizeNotesPeriodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:sync-notes-periodes {--dry-run : Show changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize semestre field in notes with periode field in evaluations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to synchronize note periods with evaluation periods...');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Running in dry-run mode. No changes will be applied.');
        }

        // Get all notes with valid evaluations
        $notes = ESBTPNote::with('evaluation')->get();
        $totalNotes = $notes->count();
        $this->info("Found {$totalNotes} notes to process.");

        $updated = 0;
        $mismatched = 0;
        $missingEvaluations = 0;

        $bar = $this->output->createProgressBar($totalNotes);
        $bar->start();

        foreach ($notes as $note) {
            if (!$note->evaluation) {
                $missingEvaluations++;
                $bar->advance();
                continue;
            }

            $evaluationPeriode = $note->evaluation->periode;

            if ($note->semestre !== $evaluationPeriode) {
                $mismatched++;

                if (!$dryRun) {
                    $note->semestre = $evaluationPeriode;
                    $note->save();
                    $updated++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Notes processed: {$totalNotes}");
        $this->info("Notes with mismatched periods: {$mismatched}");
        $this->info("Notes with missing evaluations: {$missingEvaluations}");

        if (!$dryRun) {
            $this->info("Notes updated: {$updated}");
        } else {
            $this->warn("Dry run mode: {$mismatched} notes would have been updated.");
        }

        // Verify années universitaires
        $this->info('Checking for evaluations without academic years...');
        $evaluationsSansAnnee = ESBTPEvaluation::whereNull('annee_universitaire_id')->count();

        if ($evaluationsSansAnnee > 0) {
            $this->warn("Found {$evaluationsSansAnnee} evaluations without academic years.");

            if (!$dryRun && $this->confirm('Attribuer une année à ces évaluations (tirée des inscriptions ou de la date) ?')) {
                $this->call('esbtp:check-evaluations-annees');
            }
        } else {
            $this->info('All evaluations have academic years assigned.');
        }

        return Command::SUCCESS;
    }
}
