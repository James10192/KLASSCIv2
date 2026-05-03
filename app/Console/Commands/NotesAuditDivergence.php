<?php

namespace App\Console\Commands;

use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Console\Command;

/**
 * Audite les divergences entre les moyennes par matière persistées dans
 * `esbtp_resultats` et celles recalculées avec la nouvelle logique
 * (normalisation par barème + exclusion des absences) introduite en mai 2026.
 *
 * Usage typique post-déploiement du hotfix calcul de moyenne :
 *   php artisan notes:audit-divergence --dry-run
 *   php artisan notes:audit-divergence --classe=12 --export-csv=storage/audit.csv
 *
 * NB : ne modifie aucune donnée. Le recompute viendra dans une PR ultérieure.
 */
class NotesAuditDivergence extends Command
{
    /** Seuil au-delà duquel un écart est considéré significatif. */
    private const SIGNIFICANT_GAP = 0.5;

    protected $signature = 'notes:audit-divergence
        {--tenant=all : Code du tenant à auditer (réservé pour usage cross-tenant futur, ignoré ici).}
        {--classe= : Limiter l\'audit à une classe (ID).}
        {--dry-run : Mode lecture seule (par défaut, ne modifie rien — flag conservé pour clarté).}
        {--export-csv= : Chemin du fichier CSV où exporter le rapport complet (optionnel).}';

    protected $description = 'Détecte les bulletins/résultats matière dont la moyenne persistée diverge de la moyenne recalculée (post-fix barème).';

    public function handle(BulletinService $bulletinService): int
    {
        $classeId = $this->option('classe');
        $exportCsvPath = $this->option('export-csv');
        $tenant = $this->option('tenant');

        if ($tenant && $tenant !== 'all') {
            $this->warn("Note : --tenant={$tenant} est ignoré dans cette version (audit single-tenant uniquement).");
        }

        $this->info('Audit de divergence des moyennes — démarrage...');
        if ($classeId) {
            $this->line("Filtre actif : classe_id = {$classeId}");
        }

        // Eager-load anti N+1 — colonnes minimales pour réduire la mémoire sur gros volumes.
        $query = ESBTPResultat::query()
            ->with([
                'etudiant:id,nom,prenoms,matricule',
                'matiere:id,name',
                'classe:id,name',
            ]);

        if ($classeId) {
            $query->where('classe_id', $classeId);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('Aucun résultat à auditer.');

            return self::SUCCESS;
        }

        $this->line("{$total} résultat(s) à auditer.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $rows = [];
        $significantGaps = 0;
        $maxGap = 0.0;

        $query->chunkById(500, function ($chunk) use (&$rows, &$significantGaps, &$maxGap, $bulletinService, $bar) {
            foreach ($chunk as $resultat) {
                $bar->advance();

                // Si la moyenne provient d'une saisie 100 % manuelle (aucune note rattachée),
                // on ne peut pas recalculer — on saute pour éviter de signaler des faux positifs.
                $notes = ESBTPNote::query()
                    ->where('etudiant_id', $resultat->etudiant_id)
                    ->where(function ($q) use ($resultat) {
                        $q->where('matiere_id', $resultat->matiere_id)
                            ->orWhereHas('evaluation', function ($sub) use ($resultat) {
                                $sub->where('matiere_id', $resultat->matiere_id);
                            });
                    })
                    ->whereHas('evaluation', function ($q) use ($resultat) {
                        $q->where('annee_universitaire_id', $resultat->annee_universitaire_id)
                            ->where('periode', $resultat->periode)
                            ->where('status', '!=', 'cancelled');
                    })
                    ->with('evaluation:id,coefficient,bareme')
                    ->get();

                if ($notes->isEmpty()) {
                    continue;  // résultat manuel — pas auditable
                }

                $notesData = $notes->map(function ($note) {
                    return [
                        'note' => $note->note,
                        'coefficient' => $note->evaluation->coefficient ?? 1,
                        'bareme' => ($note->evaluation->bareme ?? 0) > 0 ? $note->evaluation->bareme : 20,
                        'is_absent' => (bool) $note->is_absent,
                    ];
                })->all();

                $recalculee = $bulletinService->computeMoyenneFromNotesData($notesData);
                $persistee = (float) $resultat->moyenne;
                $ecart = round(abs($recalculee - $persistee), 2);

                if ($ecart > 0) {
                    if ($ecart > $maxGap) {
                        $maxGap = $ecart;
                    }
                    if ($ecart > self::SIGNIFICANT_GAP) {
                        $significantGaps++;
                    }

                    $etudiant = $resultat->etudiant;
                    $rows[] = [
                        'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) . ' (' . ($etudiant->matricule ?? '?') . ')' : '?',
                        'classe' => $resultat->classe->name ?? '?',
                        'matiere' => $resultat->matiere->name ?? '?',
                        'periode' => $resultat->periode,
                        'persistee' => number_format($persistee, 2),
                        'recalculee' => number_format($recalculee, 2),
                        'ecart' => number_format($ecart, 2),
                        '_significant' => $ecart > self::SIGNIFICANT_GAP,
                    ];
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        if (empty($rows)) {
            $this->info('Aucune divergence détectée. Toutes les moyennes persistées sont cohérentes.');

            return self::SUCCESS;
        }

        // Affichage console — coloration rouge sur les écarts significatifs.
        $tableRows = array_map(function ($row) {
            $marker = $row['_significant'] ? '<fg=red>' . $row['ecart'] . '</>' : $row['ecart'];

            return [
                $row['etudiant'],
                $row['classe'],
                $row['matiere'],
                $row['periode'],
                $row['persistee'],
                $row['recalculee'],
                $marker,
            ];
        }, $rows);

        $this->table(
            ['Étudiant', 'Classe', 'Matière', 'Période', 'Moy. persistée', 'Moy. recalculée', 'Écart'],
            array_slice($tableRows, 0, 50)  // limite affichage console à 50, le CSV contient tout
        );

        if (count($rows) > 50) {
            $this->line('... (' . (count($rows) - 50) . ' lignes supplémentaires non affichées — utilisez --export-csv pour le rapport complet)');
        }

        // Export CSV optionnel.
        if ($exportCsvPath) {
            $this->exportCsv($exportCsvPath, $rows);
            $this->info("Rapport CSV exporté : {$exportCsvPath}");
        }

        // Résumé final.
        $this->newLine();
        $this->info(sprintf(
            '%d résultat(s) audité(s), %d divergence(s) totale(s), %d significative(s) (> %.2f), écart max %.2f.',
            $total,
            count($rows),
            $significantGaps,
            self::SIGNIFICANT_GAP,
            $maxGap
        ));

        if ($significantGaps > 0) {
            $this->warn('Des écarts significatifs détectés. Le recompute des moyennes persistées sera traité dans une PR séparée.');
        }

        return self::SUCCESS;
    }

    /**
     * Export CSV — encoding UTF-8 + BOM pour Excel-friendly.
     */
    private function exportCsv(string $path, array $rows): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'w');
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['Étudiant', 'Classe', 'Matière', 'Période', 'Moyenne persistée', 'Moyenne recalculée', 'Écart absolu', 'Significatif (> 0.5)']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['etudiant'],
                $row['classe'],
                $row['matiere'],
                $row['periode'],
                $row['persistee'],
                $row['recalculee'],
                $row['ecart'],
                $row['_significant'] ? 'OUI' : 'non',
            ]);
        }

        fclose($handle);
    }
}
