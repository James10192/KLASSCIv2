<?php

namespace App\Console\Commands\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPFiliere;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Commande artisan pour seeder les sorties Tronc Commun → Spécialités.
 *
 * Pourquoi une commande et pas un seeder Database :
 * - Les seeders sont gitignored dans KLASSCI (rule projet)
 * - La commande est idempotente : peut être relancée sans casser
 * - Découverte automatique : parcourt les classes TC, suggère les classes
 *   spécialité du même niveau + même année
 *
 * Usage :
 *   php artisan bts:seed-orientation-targets               (mode auto-détection)
 *   php artisan bts:seed-orientation-targets --dry-run     (preview sans écrire)
 *   php artisan bts:seed-orientation-targets --reset       (vide la table avant)
 */
class SeedOrientationTargets extends Command
{
    protected $signature = 'bts:seed-orientation-targets
                            {--dry-run : Preview sans écrire en base}
                            {--reset : Supprime toutes les sorties existantes avant de seeder}';

    protected $description = 'Auto-détecte les sorties BTS Tronc Commun → Spécialités possibles pour ce tenant';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $shouldReset = $this->option('reset');

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Aucune écriture en base.');
        }

        $classesTc = ESBTPClasse::query()
            ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', true))
            ->with(['filiere', 'niveauEtude', 'anneeUniversitaire'])
            ->where('is_active', true)
            ->orderBy('annee_universitaire_id', 'desc')
            ->orderBy('name')
            ->get();

        if ($classesTc->isEmpty()) {
            $this->warn('Aucune classe TC active sur ce tenant. Marquez d\'abord une filière comme tronc commun.');
            return self::SUCCESS;
        }

        $this->info("Classes TC détectées : {$classesTc->count()}");

        $created = 0;
        $skipped = 0;
        $reset = 0;

        DB::transaction(function () use ($classesTc, $isDryRun, $shouldReset, &$created, &$skipped, &$reset) {
            if ($shouldReset && ! $isDryRun) {
                $reset = ESBTPClasseOrientationTarget::query()->delete();
                $this->warn("Reset : {$reset} sorties supprimées.");
            }

            foreach ($classesTc as $sourceClasse) {
                $filiereTc = $sourceClasse->filiere;
                if (! $filiereTc) {
                    continue;
                }

                // Filières spé candidates : enfants de la filière TC OU non-TC sans parent (fallback)
                $filieresSpeIds = ESBTPFiliere::query()
                    ->where('is_active', true)
                    ->where('is_tronc_commun', false)
                    ->where(function ($q) use ($filiereTc) {
                        $q->where('parent_id', $filiereTc->id)
                          ->orWhereNull('parent_id');
                    })
                    ->pluck('id');

                if ($filieresSpeIds->isEmpty()) {
                    continue;
                }

                // Les classes KLASSCI sont universelles (cf rule classes-universelles-pas-annee.md) :
                // on ne filtre PAS par annee_universitaire_id sur esbtp_classes.
                $classesCibles = ESBTPClasse::query()
                    ->whereIn('filiere_id', $filieresSpeIds)
                    ->where('niveau_etude_id', $sourceClasse->niveau_etude_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

                if ($classesCibles->isEmpty()) {
                    continue;
                }

                $this->line(" → <fg=cyan>{$sourceClasse->name}</> ({$filiereTc->name}) → {$classesCibles->count()} cible(s)");

                foreach ($classesCibles as $i => $target) {
                    if ($isDryRun) {
                        $this->line("     [dry] + {$target->name}");
                        $created++;
                        continue;
                    }

                    $row = ESBTPClasseOrientationTarget::updateOrCreate(
                        [
                            'source_classe_id' => $sourceClasse->id,
                            'target_classe_id' => $target->id,
                        ],
                        [
                            'semestre_activation' => max(2, (int) ($filiereTc->semestres_tronc_commun ?? 1) + 1),
                            'is_active' => true,
                            'sort_order' => $i,
                            'notes' => 'Auto-détecté via bts:seed-orientation-targets',
                        ]
                    );
                    if ($row->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                }
            }
        });

        $this->newLine();
        $this->info("Résumé :");
        $this->info("  Créés    : {$created}");
        $this->info("  Existants (skippés) : {$skipped}");
        if ($shouldReset) {
            $this->info("  Reset    : {$reset}");
        }

        if ($created === 0 && $skipped === 0) {
            $this->warn('Aucune sortie détectée. Vérifie :');
            $this->warn('  1. Les filières TC ont bien un parent_id sur leurs filières enfants (spé)');
            $this->warn('  2. Les classes spé existent pour le même niveau + année que la classe TC');
            $this->warn('  3. Sinon, configure manuellement via /esbtp/admin/orientation-targets');
        }

        return self::SUCCESS;
    }
}
