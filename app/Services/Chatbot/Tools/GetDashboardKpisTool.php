<?php

namespace App\Services\Chatbot\Tools;

use Illuminate\Support\Facades\DB;

class GetDashboardKpisTool extends ChatbotTool
{
    public function name(): string
    {
        return 'get_dashboard_kpis';
    }

    public function description(): string
    {
        return 'Obtenir les indicateurs clés (KPI) du tableau de bord : inscrits de l\'année courante (le nombre à donner quand on demande combien d\'étudiants sont inscrits), étudiants en base toutes années confondues, paiements reçus, taux de présence, évaluations. Utiliser quand l\'utilisateur demande un résumé, des statistiques, ou l\'état général de l\'établissement.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'focus' => [
                    'type' => 'string',
                    'description' => 'Domaine spécifique: "general", "finance", "academic", "attendance". Par défaut: "general" (tous les KPIs).',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $focus = $args['focus'] ?? 'general';
        $kpis = [];

        // KPIs généraux (toujours inclus). Mêmes chiffres que le tableau de bord :
        // « inscrits » porte sur l'année courante, « étudiants en base » sur tout
        // l'historique. Les deux clés disent leur portée, sinon le modèle prend le
        // total de la base pour les inscrits de l'année (262 annoncés pour 214).
        $comptes = app(\App\Domain\Students\StudentCountService::class)->counts();
        $kpis['annee_courante'] = $comptes['annee_courante_label'] ?? 'non définie';
        $kpis['inscrits_annee_courante'] = $comptes['inscrits_annee_courante'];
        $kpis['etudiants_en_base_toutes_annees'] = $comptes['total_base'];
        $kpis['classes'] = DB::table('esbtp_classes')->whereNull('deleted_at')->count();
        $kpis['enseignants'] = DB::table('esbtp_teachers')->where('is_active', true)->whereNull('deleted_at')->count();

        // Cet outil est ouvert a dashboard.view, que portent aussi l'etudiant et
        // l'enseignant : l'encaisse de l'ecole reste derriere la porte financiere.
        if (($focus === 'general' || $focus === 'finance') && $user?->can('finances.etudiants.voir')) {
            $kpis['total_paiements'] = number_format(
                (float) DB::table('esbtp_paiements')->where('status', 'validé')->whereNull('deleted_at')->sum(DB::raw(\App\Models\ESBTPPaiement::sqlCashCase())),
                0, ',', ' '
            ) . ' FCFA';
            $kpis['paiements_en_attente'] = DB::table('esbtp_paiements')
                ->where('status', 'pending')
                ->whereNull('deleted_at')
                ->count();
        }

        if ($focus === 'general' || $focus === 'academic') {
            $kpis['evaluations_total'] = DB::table('esbtp_evaluations')
                ->whereNull('deleted_at')
                ->count();
            $kpis['evaluations_completees'] = DB::table('esbtp_evaluations')
                ->where('status', 'completed')
                ->whereNull('deleted_at')
                ->count();
            $kpis['notes_saisies'] = DB::table('esbtp_notes')->whereNull('deleted_at')->whereNull('archived_at')->count();
        }

        if ($focus === 'general' || $focus === 'attendance') {
            $totalAtt = DB::table('esbtp_attendances')->whereNull('deleted_at')->count();
            $presences = DB::table('esbtp_attendances')
                ->where('statut', 'present')
                ->whereNull('deleted_at')
                ->count();
            $kpis['taux_presence'] = $totalAtt > 0
                ? round(($presences / $totalAtt) * 100, 1) . '%'
                : 'N/A';
            $kpis['absences_non_justifiees'] = DB::table('esbtp_attendances')
                ->where('statut', 'absent')
                ->where('is_justified', false)
                ->whereNull('deleted_at')
                ->count();
        }

        return [
            'results' => [$kpis],
            'count' => 1,
            'display_type' => 'text',
            'kpis' => $kpis,
        ];
    }
}
