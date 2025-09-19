<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPFacture;
use App\Models\ESBTPNote;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Models\ESBTPRelance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteStudentCommand extends Command
{
    protected $signature = 'esbtp:delete-student
                           {identifier : Matricule, ID ou email de l\'étudiant}
                           {--dry-run : Afficher ce qui serait supprimé sans effectuer la suppression}
                           {--force : Passer la confirmation}
                           {--keep-user : Garder le compte utilisateur associé}';

    protected $description = 'Supprimer complètement un étudiant et toutes ses données liées';

    protected $statistics = [
        'etudiant' => null,
        'inscriptions' => 0,
        'paiements' => 0,
        'frais_subscriptions' => 0,
        'factures' => 0,
        'notes' => 0,
        'absences' => 0,
        'attendances' => 0,
        'bulletins' => 0,
        'resultats' => 0,
        'relances' => 0,
        'parents_relations' => 0,
        'user_account' => false,
        'errors' => []
    ];

    public function handle()
    {
        $this->info('🗑️  Commande de suppression d\'étudiant');
        $this->info('=======================================');

        $identifier = $this->argument('identifier');
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        $keepUser = $this->option('keep-user');

        if ($isDryRun) {
            $this->warn('🔍 MODE DRY-RUN - Aucune suppression ne sera effectuée');
        }

        // 1. Trouver l'étudiant
        $etudiant = $this->findStudent($identifier);
        if (!$etudiant) {
            return 1;
        }

        $this->statistics['etudiant'] = $etudiant;

        // 2. Analyser toutes les données liées
        $this->info("\n📊 Analyse des données à supprimer...");
        $this->analyzeRelatedData($etudiant);

        // 3. Afficher le résumé
        $this->displaySummary();

        // 4. Vérifier s'il y a des erreurs critiques
        if (!empty($this->statistics['errors'])) {
            $this->error("\n❌ ERREURS CRITIQUES DÉTECTÉES:");
            foreach ($this->statistics['errors'] as $error) {
                $this->error("• $error");
            }
            return 1;
        }

        if ($isDryRun) {
            $this->warn("\n🔍 DRY-RUN TERMINÉ - Aucune suppression effectuée");
            $this->info("Exécutez sans --dry-run pour procéder à la suppression");
            return 0;
        }

        // 5. Confirmation
        if (!$isForced) {
            $confirm = $this->confirm(
                "\n⚠️  ATTENTION: Cette action est IRRÉVERSIBLE!\n" .
                "Voulez-vous vraiment supprimer cet étudiant et toutes ses données?",
                false
            );

            if (!$confirm) {
                $this->info('Suppression annulée.');
                return 0;
            }
        }

        // 6. Procéder à la suppression
        return $this->deleteStudent($etudiant, $keepUser);
    }

    protected function findStudent($identifier)
    {
        $this->info("🔍 Recherche de l'étudiant: $identifier");

        // Recherche par matricule, ID ou email
        $etudiant = ESBTPEtudiant::where('matricule', $identifier)
            ->orWhere('id', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$etudiant) {
            $this->error("❌ Étudiant introuvable avec l'identifiant: $identifier");
            $this->info("💡 Utilisez le matricule, l'ID ou l'email de l'étudiant");
            return null;
        }

        $this->info("✅ Étudiant trouvé:");
        $this->line("   • Nom: {$etudiant->nom} {$etudiant->prenoms}");
        $this->line("   • Matricule: {$etudiant->matricule}");
        $this->line("   • Email: {$etudiant->email}");
        $this->line("   • ID: {$etudiant->id}");

        return $etudiant;
    }

    protected function analyzeRelatedData(ESBTPEtudiant $etudiant)
    {
        try {
            // Inscriptions et données liées
            $inscriptions = $etudiant->inscriptions()->get();
            $this->statistics['inscriptions'] = $inscriptions->count();

            foreach ($inscriptions as $inscription) {
                // Frais subscriptions
                $this->statistics['frais_subscriptions'] += ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();

                // Factures
                $this->statistics['factures'] += ESBTPFacture::where('inscription_id', $inscription->id)->count();
            }

            // Paiements directs
            $this->statistics['paiements'] = ESBTPPaiement::where('etudiant_id', $etudiant->id)->count();

            // Notes et évaluations
            $this->statistics['notes'] = ESBTPNote::where('etudiant_id', $etudiant->id)->count();

            // Absences
            $this->statistics['absences'] = ESBTPAbsence::where('etudiant_id', $etudiant->id)->count();

            // Attendances
            $this->statistics['attendances'] = ESBTPAttendance::where('etudiant_id', $etudiant->id)->count();

            // Bulletins
            $this->statistics['bulletins'] = ESBTPBulletin::where('etudiant_id', $etudiant->id)->count();

            // Résultats
            $this->statistics['resultats'] = ESBTPResultat::where('etudiant_id', $etudiant->id)->count();

            // Relances
            $this->statistics['relances'] = ESBTPRelance::where('etudiant_id', $etudiant->id)->count();

            // Relations parents
            $this->statistics['parents_relations'] = $etudiant->parents()->count();

            // Compte utilisateur
            if ($etudiant->user_id) {
                $user = User::find($etudiant->user_id);
                $this->statistics['user_account'] = $user ? true : false;
            }

        } catch (\Exception $e) {
            $this->statistics['errors'][] = "Erreur lors de l'analyse: " . $e->getMessage();
        }
    }

    protected function displaySummary()
    {
        $etudiant = $this->statistics['etudiant'];

        $this->info("\n📋 RÉSUMÉ DE LA SUPPRESSION:");
        $this->info("============================");

        $this->table(
            ['Type de données', 'Nombre d\'éléments'],
            [
                ['👤 Étudiant', "1 ({$etudiant->nom} {$etudiant->prenoms})"],
                ['📚 Inscriptions', $this->statistics['inscriptions']],
                ['💰 Paiements', $this->statistics['paiements']],
                ['📄 Frais Subscriptions', $this->statistics['frais_subscriptions']],
                ['🧾 Factures', $this->statistics['factures']],
                ['📝 Notes', $this->statistics['notes']],
                ['🚫 Absences', $this->statistics['absences']],
                ['✅ Présences', $this->statistics['attendances']],
                ['📊 Bulletins', $this->statistics['bulletins']],
                ['🏆 Résultats', $this->statistics['resultats']],
                ['📞 Relances', $this->statistics['relances']],
                ['👪 Relations parents', $this->statistics['parents_relations']],
                ['🔐 Compte utilisateur', $this->statistics['user_account'] ? '1' : '0'],
            ]
        );

        $total = $this->statistics['inscriptions'] +
                $this->statistics['paiements'] +
                $this->statistics['frais_subscriptions'] +
                $this->statistics['factures'] +
                $this->statistics['notes'] +
                $this->statistics['absences'] +
                $this->statistics['attendances'] +
                $this->statistics['bulletins'] +
                $this->statistics['resultats'] +
                $this->statistics['relances'] +
                $this->statistics['parents_relations'] +
                ($this->statistics['user_account'] ? 1 : 0) +
                1; // +1 pour l'étudiant lui-même

        $this->warn("\n📊 TOTAL: $total éléments seront supprimés");
    }

    protected function deleteStudent(ESBTPEtudiant $etudiant, $keepUser = false)
    {
        $this->info("\n🔄 Début de la suppression...");

        DB::beginTransaction();

        try {
            $deleted = [
                'frais_subscriptions' => 0,
                'factures' => 0,
                'paiements' => 0,
                'notes' => 0,
                'absences' => 0,
                'attendances' => 0,
                'bulletins' => 0,
                'resultats' => 0,
                'relances' => 0,
                'inscriptions' => 0,
                'parents_relations' => 0,
                'user_account' => 0,
                'etudiant' => 0
            ];

            // 1. Supprimer les données liées aux inscriptions
            $inscriptions = $etudiant->inscriptions()->get();
            foreach ($inscriptions as $inscription) {
                // Frais subscriptions
                $deleted['frais_subscriptions'] += ESBTPFraisSubscription::where('inscription_id', $inscription->id)->delete();

                // Factures
                $deleted['factures'] += ESBTPFacture::where('inscription_id', $inscription->id)->delete();
            }

            // 2. Supprimer les paiements
            $deleted['paiements'] = ESBTPPaiement::where('etudiant_id', $etudiant->id)->delete();

            // 3. Supprimer les données académiques
            $deleted['notes'] = ESBTPNote::where('etudiant_id', $etudiant->id)->delete();
            $deleted['absences'] = ESBTPAbsence::where('etudiant_id', $etudiant->id)->delete();
            $deleted['attendances'] = ESBTPAttendance::where('etudiant_id', $etudiant->id)->delete();
            $deleted['bulletins'] = ESBTPBulletin::where('etudiant_id', $etudiant->id)->delete();
            $deleted['resultats'] = ESBTPResultat::where('etudiant_id', $etudiant->id)->delete();

            // 4. Supprimer les relances
            $deleted['relances'] = ESBTPRelance::where('etudiant_id', $etudiant->id)->delete();

            // 5. Supprimer les relations parents (pivot table)
            $deleted['parents_relations'] = $etudiant->parents()->detach();

            // 6. Supprimer les inscriptions
            $deleted['inscriptions'] = $etudiant->inscriptions()->delete();

            // 7. Supprimer le compte utilisateur si demandé
            if (!$keepUser && $etudiant->user_id) {
                $user = User::find($etudiant->user_id);
                if ($user) {
                    $user->delete();
                    $deleted['user_account'] = 1;
                }
            }

            // 8. Supprimer l'étudiant
            $etudiant->delete();
            $deleted['etudiant'] = 1;

            DB::commit();

            // Afficher le résumé de suppression
            $this->info("\n✅ SUPPRESSION RÉUSSIE!");
            $this->info("========================");

            $this->table(
                ['Type supprimé', 'Nombre'],
                [
                    ['👤 Étudiant', $deleted['etudiant']],
                    ['📚 Inscriptions', $deleted['inscriptions']],
                    ['💰 Paiements', $deleted['paiements']],
                    ['📄 Frais Subscriptions', $deleted['frais_subscriptions']],
                    ['🧾 Factures', $deleted['factures']],
                    ['📝 Notes', $deleted['notes']],
                    ['🚫 Absences', $deleted['absences']],
                    ['✅ Présences', $deleted['attendances']],
                    ['📊 Bulletins', $deleted['bulletins']],
                    ['🏆 Résultats', $deleted['resultats']],
                    ['📞 Relances', $deleted['relances']],
                    ['👪 Relations parents', $deleted['parents_relations']],
                    ['🔐 Compte utilisateur', $deleted['user_account']],
                ]
            );

            $totalDeleted = array_sum($deleted);
            $this->info("\n🎯 Total supprimé: $totalDeleted éléments");

            Log::info('Étudiant supprimé avec succès', [
                'etudiant_id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => $etudiant->nom . ' ' . $etudiant->prenoms,
                'deleted_counts' => $deleted,
                'total_deleted' => $totalDeleted,
                'keep_user' => $keepUser,
                'executed_by' => auth()->user()->id ?? 'console'
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("\n❌ ERREUR lors de la suppression!");
            $this->error("Erreur: " . $e->getMessage());
            $this->info("Toutes les modifications ont été annulées.");

            Log::error('Erreur lors de la suppression d\'étudiant', [
                'etudiant_id' => $etudiant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}