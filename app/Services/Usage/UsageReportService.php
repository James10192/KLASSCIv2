<?php

namespace App\Services\Usage;

/**
 * Rapport d'usage d'une instance : l'etablissement travaille-t-il vraiment
 * dans KLASSCI ? Orchestration seulement, les calculs vivent dans les
 * classes voisines.
 */
class UsageReportService
{
    /**
     * @param int[] $excludedUserIds comptes a traiter comme KLASSCI
     * @param bool  $withNames       exposer les noms du personnel
     */
    public function build(UsageWindow $window, array $excludedUserIds = [], bool $withNames = false): array
    {
        $actors = new UsageActorDirectory($excludedUserIds);
        $query = new AuditActivityQuery($window);
        $threshold = (int) config('usage_report.bulk_actions_per_user_day', 300);

        $timeline = (new ActivityTimeline($window, $actors, $threshold))->build($query->perUserDayEvent());
        $modules = new ModuleAdoption($window, $actors);
        $outcomes = new ConcreteOutcomes($window, $actors);
        $concrete = $outcomes->build($query->webWritesPerModelEventMonth());
        $activeStaffIds = array_column($timeline['comptes'], 'user_id');

        return [
            'periode' => $window->toArray(),
            'methode' => [
                'seuil_journee_de_masse' => $threshold,
                'journee_active' => "Jour où un compte a enregistré au moins une écriture (création, modification, suppression).",
                'origines_exclues' => config('usage_report.internal_url_fragments'),
                'roles_internes' => config('usage_report.internal_roles'),
                'comptes_exclus' => array_values($excludedUserIds),
            ],
            'groupes' => $timeline['groupes'],
            'jours' => $timeline['jours'],
            'semaines' => $timeline['semaines'],
            'mois' => $timeline['mois'],
            'realisations' => $concrete['realisations'],
            'creations_par_mois' => $concrete['creations_par_mois'],
            'paiements_par_mois' => $outcomes->payments(),
            'comptes_actifs' => $this->present($timeline['comptes'], $actors, $withNames),
            'journees_de_masse' => $this->present($timeline['journees_de_masse'], $actors, $withNames),
            'modules' => $modules->build($query->webWritesPerModelDay(), $query->writesPerOriginAndModel()),
            'volumes_sans_audit' => $modules->volumes(),
            'heures' => $modules->heatmap($query->webWritesPerHourAndWeekday()),
            'parc_de_comptes' => (new AccountCoverage($window, $actors))->build($activeStaffIds),
        ];
    }

    /** Pseudonymise par defaut : un identifiant stable, jamais le nom. */
    private function present(array $rows, UsageActorDirectory $actors, bool $withNames): array
    {
        return array_map(function (array $row) use ($actors, $withNames) {
            $row['compte'] = $withNames ? $actors->name($row['user_id']) : 'Compte ' . $row['user_id'];

            return $row;
        }, $rows);
    }
}
