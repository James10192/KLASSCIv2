<?php

namespace App\Console\Commands;

use App\Services\ClasseManagementService;
use Illuminate\Console\Command;

class SyncSystemeAcademique extends Command
{
    protected $signature = 'esbtp:sync-systeme-academique
                            {--dry-run : Prévisualiser sans modifier la base}
                            {--classe= : Synchroniser une seule classe (ID)}';

    protected $description = 'Synchroniser le système académique (BTS/LMD) des classes depuis leur niveau d\'études (Licence/Master/Doctorat → LMD, sinon BTS)';

    public function handle(ClasseManagementService $service): int
    {
        $isDryRun = $this->option('dry-run');
        $classeId = $this->option('classe') ? (int) $this->option('classe') : null;

        if ($isDryRun) {
            $this->warn('MODE DRY-RUN — Aucune modification ne sera effectuée');
        }

        $this->info('Synchronisation des systèmes académiques...');
        $this->newLine();

        if ($isDryRun) {
            // Preview: show what would change
            $query = \App\Models\ESBTPClasse::with('niveau');
            if ($classeId) {
                $query->where('id', $classeId);
            }
            $classes = $query->get();

            $wouldUpdate = 0;
            $rows = [];

            foreach ($classes as $classe) {
                if (!$classe->niveau) continue;
                $expected = ClasseManagementService::determinerSystemeAcademique($classe->niveau->type ?? '');
                $needsUpdate = $classe->systeme_academique !== $expected;

                if ($needsUpdate) {
                    $rows[] = [
                        $classe->id,
                        $classe->name,
                        $classe->niveau->type ?? '—',
                        $classe->systeme_academique,
                        $expected,
                    ];
                    $wouldUpdate++;
                }
            }

            if ($wouldUpdate > 0) {
                $this->table(['ID', 'Classe', 'Type niveau', 'Actuel', 'Attendu'], $rows);
                $this->warn("{$wouldUpdate} classe(s) à corriger sur {$classes->count()} total.");
            } else {
                $this->info("Toutes les {$classes->count()} classe(s) sont déjà correctes.");
            }

            return Command::SUCCESS;
        }

        // Execute
        $result = $service->syncSystemeAcademique($classeId);

        if ($result['updated'] > 0) {
            $rows = collect($result['details'])->map(fn($d) => [
                $d['id'], $d['name'], $d['niveau_type'], $d['old'], $d['new'],
            ])->toArray();

            $this->table(['ID', 'Classe', 'Type niveau', 'Ancien', 'Nouveau'], $rows);
            $this->info("{$result['updated']} classe(s) mise(s) à jour sur {$result['total']} total.");
        } else {
            $this->info("Toutes les {$result['total']} classe(s) sont déjà correctement configurées.");
        }

        return Command::SUCCESS;
    }
}
