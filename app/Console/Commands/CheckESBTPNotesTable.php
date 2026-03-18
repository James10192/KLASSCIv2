<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckESBTPNotesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:notes-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la structure de la table esbtp_notes';

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
        $this->info('Vérification de la table esbtp_notes...');
        
        if (Schema::hasTable('esbtp_notes')) {
            $this->info('La table esbtp_notes existe.');
            
            // Récupérer les colonnes
            $columns = Schema::getColumnListing('esbtp_notes');
            $this->info('Colonnes de la table esbtp_notes:');
            foreach ($columns as $column) {
                $this->line(' - ' . $column);
            }
            
            // Vérifier le nombre de notes
            $count = DB::table('esbtp_notes')->whereNull('archived_at')->count();
            $this->info('Nombre d\'enregistrements: ' . $count);
            
            return 0;
        } else {
            $this->error('La table esbtp_notes n\'existe pas.');
            return 1;
        }
    }
} 