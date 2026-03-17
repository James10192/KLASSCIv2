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
                'focus' => [
                    'type' => 'string',
                    'description' => 'Focus: "global" (tout), "recouvrement" (taux), "impayes" (détail impayés). Défaut: global.',
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
            ->with(['classe', 'etudiant'])
            ->where('status', 'active');

        if (!empty($args['classe'])) {
            $search = $args['classe'];
            $inscriptionQuery->whereHas('classe', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
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

        // Paiements en attente de validation
        $enAttente = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('status', 'en_attente')
            ->count();

        $montantEnAttente = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('status', 'en_attente')
            ->sum('montant');

        $results = [
            [
                'label' => 'Taux de recouvrement',
                'value' => $tauxRecouvrement . '%',
                'detail' => number_format($totalPaye, 0, ',', ' ') . ' / ' . number_format($totalAttendu, 0, ',', ' ') . ' FCFA',
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
                'detail' => number_format($impayeTotal, 0, ',', ' ') . ' FCFA d\'impayés',
                'icon' => 'fas fa-user-clock',
                'color' => $enRetard > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Paiements en attente',
                'value' => (string) $enAttente,
                'detail' => number_format($montantEnAttente, 0, ',', ' ') . ' FCFA à valider',
                'icon' => 'fas fa-hourglass-half',
                'color' => $enAttente > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Total encaissé',
                'value' => number_format($totalPaye, 0, ',', ' ') . ' FCFA',
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
