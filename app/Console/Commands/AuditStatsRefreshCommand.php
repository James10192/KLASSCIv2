<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditStatsSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit;

/**
 * Calcule les compteurs de la page d'audit, hors du chemin web.
 *
 * Ces sept COUNT(*) etaient lances a chaque affichage de /esbtp/audit et
 * rendaient la page inatteignable sur les grosses instances. Ils sont desormais
 * calcules ici, une fois par periode, et deposes dans un instantane que la page
 * se contente de lire.
 *
 * La commande se limite elle-meme : si l'instantane est plus jeune que la
 * frequence reglee pour l'instance, elle ne fait rien. Le planificateur peut
 * donc l'appeler souvent sans que la base en paie le prix.
 */
class AuditStatsRefreshCommand extends Command
{
    protected $signature = 'audit:rafraichir-statistiques
                            {--force : Recalcule meme si l\'instantane est encore frais}
                            {--json : Sortie JSON}';

    protected $description = 'Recalcule les statistiques de la page d\'audit et depose l\'instantane lu par la page';

    public function handle(AuditStatsSnapshot $instantane): int
    {
        $frequence = AuditStatsSnapshot::frequenceMinutes();
        $existant = $instantane->lire();
        $force = (bool) $this->option('force');

        if (! $force && $existant !== null) {
            $age = $existant['calcule_le']->diffInMinutes(Carbon::now(), false);

            if ($age < $frequence) {
                return $this->rendre([
                    'statut' => 'ignore',
                    'raison' => 'instantane encore frais',
                    'age_minutes' => $age,
                    'frequence_minutes' => $frequence,
                ], self::SUCCESS);
            }
        }

        $debut = microtime(true);
        $statistiques = $this->calculer();
        $duree = (int) round((microtime(true) - $debut) * 1000);

        $instantane->ecrire($statistiques);

        return $this->rendre([
            'statut' => 'calcule',
            'duree_ms' => $duree,
            'frequence_minutes' => $frequence,
            'peremption_minutes' => AuditStatsSnapshot::peremptionMinutes(),
            'fichier' => $instantane->chemin(),
            'statistiques' => $statistiques,
        ], self::SUCCESS);
    }

    /**
     * Les sept compteurs, a l'identique de ce que la page affichait — aucun
     * chiffre ne change.
     *
     * Seule la forme des deux filtres de date change : `whereDate(created_at)`
     * applique une fonction a la colonne et rend l'index `audits_created_at_idx`
     * inutilisable. L'intervalle borne donne le meme resultat en se servant de
     * l'index.
     */
    private function calculer(): array
    {
        $aujourdhui = Carbon::today();
        $cetteSemaine = Carbon::now()->startOfWeek();
        $ceMois = Carbon::now()->startOfMonth();

        return [
            'total_audits' => Audit::count(),
            'today_audits' => Audit::whereBetween('created_at', [
                $aujourdhui->copy()->startOfDay(),
                $aujourdhui->copy()->endOfDay(),
            ])->count(),
            'week_audits' => Audit::where('created_at', '>=', $cetteSemaine)->count(),
            'month_audits' => Audit::where('created_at', '>=', $ceMois)->count(),
            'financial_audits' => Audit::whereIn('auditable_type', [
                'App\Models\ESBTPPaiement',
                'App\Models\ESBTPDepense',
                'App\Models\ESBTPFacture',
            ])->count(),
            'critical_events' => Audit::whereIn('event', ['deleted', 'restored'])->count(),
            'unique_users' => Audit::distinct('user_id')->count('user_id'),
        ];
    }

    private function rendre(array $charge, int $code): int
    {
        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($charge, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $code;
        }

        if (($charge['statut'] ?? null) === 'ignore') {
            $this->line(sprintf(
                'Instantane deja frais (%d min, frequence reglee a %d min). Rien a faire — utilisez --force pour recalculer.',
                $charge['age_minutes'],
                $charge['frequence_minutes']
            ));

            return $code;
        }

        $this->info(sprintf('Statistiques d\'audit recalculees en %d ms.', $charge['duree_ms']));
        $this->line('Depose dans : ' . $charge['fichier']);

        foreach ($charge['statistiques'] as $cle => $valeur) {
            $this->line(sprintf('  %-18s %s', $cle, number_format((int) $valeur, 0, ',', ' ')));
        }

        return $code;
    }
}
