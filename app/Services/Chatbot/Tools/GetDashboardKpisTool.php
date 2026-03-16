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
        return 'Obtenir les indicateurs clés (KPI) du tableau de bord : nombre d\'étudiants, inscriptions actives, paiements reçus, taux de présence, évaluations en cours. Utiliser quand l\'utilisateur demande un résumé, des statistiques, ou l\'état général de l\'établissement.';
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

        // KPIs généraux (toujours inclus)
        $kpis['etudiants'] = DB::table('esbtp_etudiants')->whereNull('deleted_at')->count();
        $kpis['inscriptions_actives'] = DB::table('esbtp_inscriptions')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();
        $kpis['classes'] = DB::table('esbtp_classes')->whereNull('deleted_at')->count();
        $kpis['enseignants'] = DB::table('esbtp_teachers')->where('is_active', true)->whereNull('deleted_at')->count();

        if ($focus === 'general' || $focus === 'finance') {
            $kpis['total_paiements'] = number_format(
                (float) DB::table('esbtp_paiements')->where('status', 'validated')->sum('montant'),
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
            $kpis['notes_saisies'] = DB::table('esbtp_notes')->whereNull('deleted_at')->count();
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
