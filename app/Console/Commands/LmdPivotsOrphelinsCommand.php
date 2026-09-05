<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recense — et sur demande supprime — les lignes de pivot LMD qui désignent une
 * entité disparue.
 *
 * `esbtp_ue_matiere` et `esbtp_lmd_parcours_ue` portent bien une contrainte
 * `cascadeOnDelete`, mais les unités, les matières et les parcours sont tous en
 * suppression DOUCE : la ligne de la table parente n'est jamais retirée, donc la
 * cascade ne se déclenche jamais. Chaque suppression d'unité laissait ainsi
 * derrière elle des liens qui pointent vers du vide.
 *
 * Ils ne sont pas inertes : la lecture des éléments constitutifs d'une unité
 * fait l'union du pivot et de la clé étrangère, et le pivot est prioritaire là
 * où il parle. Un lien survivant à sa matière ramène donc une entrée fantôme
 * dans un relevé.
 *
 * La commande RECENSE par défaut. Elle ne supprime qu'avec `--appliquer`, parce
 * qu'un lien retiré ici ne se retrouve pas si l'unité est restaurée ensuite.
 */
class LmdPivotsOrphelinsCommand extends Command
{
    protected $signature = 'lmd:pivots-orphelins
                            {--appliquer : Supprime les lignes recensees (sans cette option, rien n\'est ecrit)}
                            {--json : Sortie JSON}';

    protected $description = 'Recense les lignes de pivot LMD qui designent une unite, une matiere ou un parcours supprimee';

    /**
     * Les quatre sens dans lesquels un lien peut survivre à ce qu'il désigne.
     *
     * @var list<array{0:string, 1:string, 2:string, 3:string}>
     */
    private const LIENS = [
        ['esbtp_ue_matiere', 'unite_enseignement_id', 'esbtp_unites_enseignement', 'esbtp_ue_matiere / unité supprimée'],
        ['esbtp_ue_matiere', 'matiere_id', 'esbtp_matieres', 'esbtp_ue_matiere / matière supprimée'],
        ['esbtp_lmd_parcours_ue', 'unite_enseignement_id', 'esbtp_unites_enseignement', 'esbtp_lmd_parcours_ue / unité supprimée'],
        ['esbtp_lmd_parcours_ue', 'parcours_id', 'esbtp_lmd_parcours', 'esbtp_lmd_parcours_ue / parcours supprimé'],
    ];

    public function handle(): int
    {
        $lots = [];
        foreach (self::LIENS as [$pivot, $colonne, $cible, $libelle]) {
            $lots[] = [
                'pivot' => $pivot,
                'libelle' => $libelle,
                'ids' => $this->orphelins($pivot, $colonne, $cible),
            ];
        }

        $total = array_sum(array_map(fn (array $lot) => count($lot['ids']), $lots));
        $appliquer = (bool) $this->option('appliquer');

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'applique' => $appliquer && $total > 0,
                'total' => $total,
                'lots' => array_map(
                    fn (array $lot) => ['libelle' => $lot['libelle'], 'lignes' => count($lot['ids'])],
                    $lots
                ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            foreach ($lots as $lot) {
                $this->line(sprintf('%-45s %d', $lot['libelle'], count($lot['ids'])));
            }
            $this->newLine();
        }

        if ($total === 0) {
            $this->info('Aucune ligne de pivot orpheline.');

            return self::SUCCESS;
        }

        if (! $appliquer) {
            $this->warn(sprintf(
                '%d ligne(s) orpheline(s) recensée(s). Rien n\'a été écrit — relancez avec --appliquer pour les supprimer.',
                $total
            ));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($lots) {
            foreach ($lots as $lot) {
                if ($lot['ids'] === []) {
                    continue;
                }

                DB::table($lot['pivot'])->whereIn('id', $lot['ids'])->delete();
            }
        });

        $this->info(sprintf('%d ligne(s) de pivot orpheline(s) supprimée(s).', $total));

        return self::SUCCESS;
    }

    /**
     * Identifiants des lignes de `$pivot` dont `$colonne` désigne une ligne
     * absente de `$cible`, ou présente mais en suppression douce.
     *
     * @return list<int>
     */
    private function orphelins(string $pivot, string $colonne, string $cible): array
    {
        return DB::table($pivot)
            ->leftJoin($cible, "{$cible}.id", '=', "{$pivot}.{$colonne}")
            ->where(function ($q) use ($cible) {
                $q->whereNull("{$cible}.id")->orWhereNotNull("{$cible}.deleted_at");
            })
            ->pluck("{$pivot}.id")
            ->map('intval')
            ->all();
    }
}
