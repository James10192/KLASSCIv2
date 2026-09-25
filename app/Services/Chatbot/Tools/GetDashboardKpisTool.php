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
        $precedente = $this->anneePrecedente($comptes['annee_courante_id'] ?? null);
        if ($precedente) {
            $kpis['annee_precedente'] = $precedente->name;
            $kpis['inscrits_annee_precedente'] = app(\App\Domain\Students\StudentCountService::class)->inscritsDe($precedente->id);
        }
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
                ->where('status', 'en_attente')
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
            // Un retard est une présence, comme à l'écran des présences
            // (total_present_with_retards) ; 'retard' subsiste sur d'anciennes lignes.
            $presences = DB::table('esbtp_attendances')
                ->whereIn('statut', ['present', 'late', 'retard'])
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
            'widget' => $this->widget($kpis, $user),
        ];
    }

    /** L'année qui commence juste avant l'année courante. */
    private function anneePrecedente(?int $courante): ?\App\Models\ESBTPAnneeUniversitaire
    {
        $annee = $courante ? \App\Models\ESBTPAnneeUniversitaire::find($courante) : null;
        if (!$annee || !$annee->start_date) {
            return null;
        }

        return \App\Models\ESBTPAnneeUniversitaire::query()
            ->where('start_date', '<', $annee->start_date)
            ->orderByDesc('start_date')
            ->first();
    }

    /** Cartes de chiffres clés, avec le repère de l'année précédente pour les inscrits. */
    private function widget(array $kpis, $user): array
    {
        // Un lien n'apparaît que vers une liste que la personne peut ouvrir : l'outil
        // est ouvert à dashboard.view, que portent aussi l'étudiant et l'enseignant.
        // PorteDeRoute lit les vrais middlewares de la route : une permission de liste
        // sans la porte d'identité de l'écran mènerait encore à un 403.
        $lien = fn (string $route) => ($user instanceof \Illuminate\Contracts\Auth\Access\Authorizable && \App\Support\PorteDeRoute::ouverte($route, $user)) ? route($route, [], false) : null;

        $elements = [];
        $inscrits = (int) $kpis['inscrits_annee_courante'];
        $repere = null;
        $tonInscrits = null;
        if (isset($kpis['inscrits_annee_precedente'])) {
            $avant = (int) $kpis['inscrits_annee_precedente'];
            $ecart = $inscrits - $avant;
            $repere = $avant > 0
                ? sprintf('%s %s %% vs %s (%d)', $ecart >= 0 ? '▲' : '▼', ($ecart >= 0 ? '+' : '') . number_format($ecart / $avant * 100, 1, ',', ' '), $kpis['annee_precedente'], $avant)
                : sprintf('%d en %s', $avant, $kpis['annee_precedente']);
            $tonInscrits = $avant > 0 ? ($ecart >= 0 ? 'succes' : 'danger') : null;
        }
        $elements[] = ['libelle' => 'Inscrits ' . $kpis['annee_courante'], 'valeur' => $inscrits, 'unite' => null, 'repere' => $repere, 'ton' => $tonInscrits, 'url' => $lien('esbtp.inscriptions.index')];
        $elements[] = ['libelle' => 'Étudiants en base', 'valeur' => (int) $kpis['etudiants_en_base_toutes_annees'], 'unite' => null, 'repere' => 'toutes années', 'ton' => null, 'url' => $lien('esbtp.etudiants.index')];
        $elements[] = ['libelle' => 'Classes', 'valeur' => (int) $kpis['classes'], 'unite' => null, 'repere' => null, 'ton' => null, 'url' => $lien('esbtp.classes.index')];
        $elements[] = ['libelle' => 'Enseignants actifs', 'valeur' => (int) $kpis['enseignants'], 'unite' => null, 'repere' => null, 'ton' => null, 'url' => null];
        if (isset($kpis['total_paiements'])) {
            $elements[] = ['libelle' => 'Encaissé (validé)', 'valeur' => (int) preg_replace('/\D/', '', $kpis['total_paiements']), 'unite' => 'FCFA', 'repere' => $kpis['paiements_en_attente'] . ' paiement(s) en attente', 'ton' => $kpis['paiements_en_attente'] > 0 ? 'alerte' : null, 'url' => $lien('esbtp.paiements.index')];
        }
        if (isset($kpis['taux_presence'])) {
            $elements[] = ['libelle' => 'Taux de présence', 'valeur' => $kpis['taux_presence'] === 'N/A' ? null : $kpis['taux_presence'], 'unite' => null, 'repere' => $kpis['absences_non_justifiees'] . ' absence(s) non justifiée(s)', 'ton' => null, 'url' => null];
        }

        return ['kind' => 'kpis', 'titre' => 'Chiffres clés', 'elements' => $elements];
    }
}
