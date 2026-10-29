<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\DB;

class ActivateAllTimetables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:activate-timetables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate all timetables to make them visible to students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting timetable activation...');

        // Get count of timetables before update
        $totalCount = DB::table('esbtp_emploi_temps')->count();
        $activeCount = DB::table('esbtp_emploi_temps')->where('is_active', true)->count();
        $currentCount = DB::table('esbtp_emploi_temps')->where('is_current', true)->count();

        $this->info("Before update: Total: $totalCount, Active: $activeCount, Current: $currentCount");

        // Update all timetables to be active
        $updated = DB::table('esbtp_emploi_temps')->update([
            'is_active' => true,
            'is_current' => true
        ]);

        $this->info("Updated $updated timetables to be active and current");

        // Get count of timetables after update
        $activeCountAfter = DB::table('esbtp_emploi_temps')->where('is_active', true)->count();
        $currentCountAfter = DB::table('esbtp_emploi_temps')->where('is_current', true)->count();

        $this->info("After update: Active: $activeCountAfter, Current: $currentCountAfter");

        // Now make sure each class has only one current timetable (the most recent one)
        $classes = DB::table('esbtp_emploi_temps')
            ->select('classe_id')
            ->distinct()
            ->get()
            ->pluck('classe_id');

        $this->info("Found " . count($classes) . " classes with timetables");

        foreach ($classes as $classeId) {
            // Get the most recent timetable for this class
            $mostRecent = DB::table('esbtp_emploi_temps')
                ->where('classe_id', $classeId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($mostRecent) {
                // Set all other timetables for this class to not current
                DB::table('esbtp_emploi_temps')
                    ->where('classe_id', $classeId)
                    ->where('id', '!=', $mostRecent->id)
                    ->update(['is_current' => false]);

                // Make sure the most recent one is current
                DB::table('esbtp_emploi_temps')
                    ->where('id', $mostRecent->id)
                    ->update(['is_current' => true]);

                $this->info("Set timetable ID {$mostRecent->id} as current for class ID $classeId");
            }
        }

        $this->info('Timetable activation completed successfully!');
    }
}
