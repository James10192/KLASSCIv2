<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\DB;

class FixTimetablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:fix-timetables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix timetables to ensure only one timetable per class is active';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting timetable fix...');

        // Get counts before update
        $totalTimetables = ESBTPEmploiTemps::count();
        $activeTimetables = ESBTPEmploiTemps::where('is_active', true)->count();
        $currentTimetables = ESBTPEmploiTemps::where('is_current', true)->count();

        $this->info("Before fix: {$totalTimetables} total timetables, {$activeTimetables} active, {$currentTimetables} current");

        // First, set all timetables to inactive and not current
        DB::table('esbtp_emploi_temps')->update([
            'is_active' => false,
            'is_current' => false
        ]);

        $this->info('Reset all timetables to inactive and not current');

        // For each class, find the most recent timetable and set it as active and current
        $classes = ESBTPClasse::all();
        $this->info("Processing {$classes->count()} classes...");

        $activatedCount = 0;

        foreach ($classes as $classe) {
            // Find the most recent timetable for this class
            $mostRecentTimetable = ESBTPEmploiTemps::where('classe_id', $classe->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($mostRecentTimetable) {
                // Set the most recent one to active and current
                $mostRecentTimetable->update([
                    'is_active' => true,
                    'is_current' => true
                ]);
                $activatedCount++;
                $this->info("Activated timetable ID {$mostRecentTimetable->id} for class: {$classe->name}");
            } else {
                $this->warn("No timetable found for class: {$classe->name}");
            }
        }

        // Get counts after update
        $activeAfter = ESBTPEmploiTemps::where('is_active', true)->count();
        $currentAfter = ESBTPEmploiTemps::where('is_current', true)->count();

        $this->info("After fix: {$totalTimetables} total timetables, {$activeAfter} active, {$currentAfter} current");
        $this->info("Activated {$activatedCount} timetables (one per class)");
        $this->info('Timetable fix completed successfully!');

        return 0;
    }
}
