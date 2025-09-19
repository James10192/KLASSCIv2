<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPEtudiant;

class DeleteReliquatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:delete-reliquats
                          {--etudiant-id= : ID spécifique de l\'étudiant}
                          {--matricule= : Matricule de l\'étudiant}
                          {--all : Supprimer TOUS les reliquats (DANGEREUX)}
                          {--date-from= : Supprimer les reliquats créés après cette date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprimer les reliquats créés par erreur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗑️  Commande de suppression des reliquats');
        $this->warn('⚠️  ATTENTION: Cette action est irréversible!');

        $etudiantId = $this->option('etudiant-id');
        $matricule = $this->option('matricule');
        $all = $this->option('all');
        $dateFrom = $this->option('date-from');

        // Construire la query
        $query = ESBTPReliquatDetail::query();

        if ($etudiantId) {
            $query->whereHas('inscriptionSource', function($q) use ($etudiantId) {
                $q->where('etudiant_id', $etudiantId);
            });

            $etudiant = ESBTPEtudiant::find($etudiantId);
            if (!$etudiant) {
                $this->error("Aucun étudiant trouvé avec l'ID: {$etudiantId}");
                return 1;
            }
            $this->info("🎯 Ciblage: Étudiant {$etudiant->nom} {$etudiant->prenoms} (ID: {$etudiantId})");

        } elseif ($matricule) {
            $etudiant = ESBTPEtudiant::where('matricule', $matricule)->first();
            if (!$etudiant) {
                $this->error("Aucun étudiant trouvé avec le matricule: {$matricule}");
                return 1;
            }

            $query->whereHas('inscriptionSource', function($q) use ($etudiant) {
                $q->where('etudiant_id', $etudiant->id);
            });

            $this->info("🎯 Ciblage: Étudiant {$etudiant->nom} {$etudiant->prenoms} (Matricule: {$matricule})");

        } elseif ($dateFrom) {
            $query->where('date_creation', '>=', $dateFrom);
            $this->info("🎯 Ciblage: Reliquats créés à partir du {$dateFrom}");

        } elseif ($all) {
            $this->error("🚨 Option --all désactivée pour sécurité. Utilisez --date-from ou --etudiant-id");
            return 1;
        } else {
            $this->error("❌ Vous devez spécifier soit --etudiant-id, --matricule, ou --date-from");
            $this->info("Exemples:");
            $this->info("  php artisan esbtp:delete-reliquats --etudiant-id=123");
            $this->info("  php artisan esbtp:delete-reliquats --matricule=EST2024001");
            $this->info("  php artisan esbtp:delete-reliquats --date-from=2024-01-15");
            return 1;
        }

        // Récupérer les reliquats à supprimer
        $reliquats = $query->with(['inscriptionSource.etudiant', 'inscriptionDestination'])->get();

        if ($reliquats->isEmpty()) {
            $this->info("✅ Aucun reliquat trouvé avec les critères spécifiés.");
            return 0;
        }

        $this->info("📊 {$reliquats->count()} reliquat(s) trouvé(s):");

        foreach ($reliquats as $reliquat) {
            $etudiant = $reliquat->inscriptionSource->etudiant;
            $this->line("  - Étudiant: {$etudiant->nom} {$etudiant->prenoms} | Montant: {$reliquat->montant_reliquat} FCFA | Créé: {$reliquat->date_creation}");
        }

        // Confirmation
        if (!$this->confirm("\n❓ Êtes-vous sûr de vouloir supprimer ces {$reliquats->count()} reliquat(s) ?")) {
            $this->info("❌ Suppression annulée.");
            return 0;
        }

        // Suppression
        $this->info("🗑️ Suppression en cours...");

        foreach ($reliquats as $reliquat) {
            $etudiant = $reliquat->inscriptionSource->etudiant;
            $this->line("  Suppression du reliquat de {$etudiant->nom} {$etudiant->prenoms} ({$reliquat->montant_reliquat} FCFA)");

            $reliquat->delete();
        }

        $this->info("✅ {$reliquats->count()} reliquat(s) supprimé(s) avec succès!");

        return 0;
    }
}