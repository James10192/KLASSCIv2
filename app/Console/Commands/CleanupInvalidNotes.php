<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;

class CleanupInvalidNotes extends Command
{
    protected $signature = 'esbtp:cleanup-notes {--delete} {--report}';
    protected $description = 'Find and fix notes with invalid or missing evaluations';

    public function handle()
    {
        $this->info('Starting cleanup of invalid notes...');

        // Find notes with missing evaluations
        $invalidNotes = ESBTPNote::whereDoesntHave('evaluation')->get();

        if ($invalidNotes->isEmpty()) {
            $this->info('No invalid notes found. Database is clean!');
            return 0;
        }

        $this->info("Found {$invalidNotes->count()} notes with missing evaluations.");

        if ($this->option('report')) {
            $this->table(
                ['ID', 'Étudiant ID', 'Note', 'Date de création'],
                $invalidNotes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'etudiant_id' => $note->etudiant_id,
                        'note' => $note->note,
                        'created_at' => $note->created_at
                    ];
                })
            );
        }

        if ($this->option('delete')) {
            if ($this->confirm('Do you wish to delete all invalid notes?', true)) {
                DB::beginTransaction();
                try {
                    $deleted = 0;
                    foreach ($invalidNotes as $note) {
                        $note->delete();
                        $deleted++;
                    }
                    DB::commit();
                    $this->info("Successfully deleted {$deleted} invalid notes.");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("An error occurred: {$e->getMessage()}");
                    return 1;
                }
            }
        } else {
            $this->info('No changes made. Run with --delete to remove invalid notes.');
        }

        return 0;
    }
}
