<?php

namespace App\Console\Commands;

use App\Models\ESBTPEtudiant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStudentEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:sync-student-emails {--force : Force update all student emails even if not empty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize empty student email_personnel fields with their associated user emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to synchronize student emails...');

        // Check if force option is enabled
        $force = $this->option('force');

        try {
            DB::beginTransaction();

            // Get all students with a user_id
            $query = ESBTPEtudiant::whereNotNull('user_id');

            // If not forcing, only get students with empty email_personnel
            if (!$force) {
                $query->where(function($q) {
                    $q->whereNull('email_personnel')
                      ->orWhere('email_personnel', '');
                });
            }

            $students = $query->with('user')->get();

            $this->info("Found {$students->count()} students to update.");

            $updated = 0;
            $skipped = 0;

            foreach ($students as $student) {
                if (!$student->user) {
                    $this->warn("Skipping student ID {$student->id}: No associated user found.");
                    $skipped++;
                    continue;
                }

                if (!$student->user->email) {
                    $this->warn("Skipping student ID {$student->id}: User has no email.");
                    $skipped++;
                    continue;
                }

                // Update the student's email_personnel with the user's email
                $student->email_personnel = $student->user->email;
                $student->save();

                $updated++;
                $this->line("Updated student ID {$student->id}: {$student->nom} {$student->prenoms} with email {$student->email_personnel}");
            }

            DB::commit();

            $this->info("Synchronization completed: {$updated} updated, {$skipped} skipped.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("An error occurred: {$e->getMessage()}");
            Log::error("Error in SyncStudentEmailsCommand: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
