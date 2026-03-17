<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use Illuminate\Support\Facades\Route;

class GetFinancialSummaryTool extends ChatbotTool
{
    public function name(): string
    {
        return 'get_financial_summary';
    }

    public function description(): string
    {
        return 'Obtenir un résumé financier : taux de recouvrement, total des paiements, montant des impayés, nombre d\'étudiants à jour/en retard. Pour la comptabilité et le suivi financier.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Filtrer par classe (nom ou code). Si omis, résumé global.',
                ],
            ],
        ];
    }

    public function allowedRoles(): ?array
    {
        return ['superAdmin', 'comptable', 'secretaire'];
    }

    public function execute(array $args, $user): array
    {
        $inscriptionQuery = ESBTPInscription::query()
            ->where('status', 'active');

        if (!empty($args['classe'])) {
            $this->applyClasseSearch($inscriptionQuery, $args['classe']);
        }

        $inscriptions = $inscriptionQuery->get();
        $totalInscriptions = $inscriptions->count();

        if ($totalInscriptions === 0) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => 'Aucune inscription active trouvée' . (!empty($args['classe']) ? " pour \"{$args['classe']}\"" : '') . '.',
            ];
        }

        // Calculer montant attendu par inscription (somme des frais souscrits)
        $inscriptionIds = $inscriptions->pluck('id');

        $subscriptions = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('is_active', true)
            ->selectRaw('inscription_id, SUM(amount) as total_du')
            ->groupBy('inscription_id')
            ->pluck('total_du', 'inscription_id');

        // Calculer montant payé par inscription (paiements validés)
        $paiements = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('status', 'validé')
            ->selectRaw('inscription_id, SUM(montant) as total_paye')
            ->groupBy('inscription_id')
            ->pluck('total_paye', 'inscription_id');

        $totalAttendu = 0;
        $totalPaye = 0;
        $aJour = 0;
        $enRetard = 0;
        $impayeTotal = 0;

        foreach ($inscriptionIds as $id) {
            $du = (float) ($subscriptions[$id] ?? 0);
            $paye = (float) ($paiements[$id] ?? 0);
            $totalAttendu += $du;
            $totalPaye += $paye;

            if ($du > 0 && $paye >= $du) {
                $aJour++;
            } elseif ($du > 0) {
                $enRetard++;
                $impayeTotal += ($du - $paye);
            }
        }

        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        // Paiements en attente de validation (une seule requête)
        $pending = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('status', 'en_attente')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(montant), 0) as total')
            ->first();
        $enAttente = (int) $pending->cnt;
        $montantEnAttente = (float) $pending->total;

        $results = [
            [
                'label' => 'Taux de recouvrement',
                'value' => $tauxRecouvrement . '%',
                'detail' => $this->formatFCFA($totalPaye) . ' / ' . $this->formatFCFA($totalAttendu),
                'icon' => 'fas fa-chart-pie',
                'color' => $tauxRecouvrement >= 80 ? 'success' : ($tauxRecouvrement >= 50 ? 'warning' : 'danger'),
            ],
            [
                'label' => 'Étudiants à jour',
                'value' => $aJour . ' / ' . $totalInscriptions,
                'detail' => $totalInscriptions > 0 ? round(($aJour / $totalInscriptions) * 100, 1) . '% des inscrits' : 'N/A',
                'icon' => 'fas fa-user-check',
                'color' => 'success',
            ],
            [
                'label' => 'Étudiants en retard',
                'value' => (string) $enRetard,
                'detail' => $this->formatFCFA($impayeTotal) . ' d\'impayés',
                'icon' => 'fas fa-user-clock',
                'color' => $enRetard > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Paiements en attente',
                'value' => (string) $enAttente,
                'detail' => $this->formatFCFA($montantEnAttente) . ' à valider',
                'icon' => 'fas fa-hourglass-half',
                'color' => $enAttente > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Total encaissé',
                'value' => $this->formatFCFA($totalPaye),
                'detail' => $totalInscriptions . ' inscriptions actives',
                'icon' => 'fas fa-coins',
                'color' => 'primary',
            ],
        ];

        return [
            'results' => $results,
            'count' => count($results),
            'display_type' => 'stat_cards',
            'deep_link' => Route::has('esbtp.comptabilite.dashboard') ? route('esbtp.comptabilite.dashboard') : null,
        ];
    }
}
