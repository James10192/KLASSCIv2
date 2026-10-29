<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPClasse;

class FixClassCapacity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:fix-class-capacity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes the capacity for classes where it is null or zero';

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
        $this->info('Searching for classes with invalid capacity (<= 0)...');

        $classesToFix = ESBTPClasse::where('places_totales', '<=', 0)->orWhereNull('places_totales')->get();

        if ($classesToFix->isEmpty()) {
            $this->info('No classes with invalid capacity found.');
            return 0;
        }

        $this->info($classesToFix->count() . ' class(es) found to be fixed.');

        $defaultCapacity = 50;

        foreach ($classesToFix as $class) {
            $class->places_totales = $defaultCapacity;
            $class->save();
            $this->line('Fixed capacity for class: ' . $class->name . ' (ID: ' . $class->id . ')');
        }

        $this->info('All invalid class capacities have been fixed to default value (' . $defaultCapacity . ').');
        return 0;
    }
} 