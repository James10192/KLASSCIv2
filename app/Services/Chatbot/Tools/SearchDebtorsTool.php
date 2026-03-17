<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\Route;

class SearchDebtorsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_debtors';
    }

    public function description(): string
    {
        return 'Rechercher les étudiants qui n\'ont pas payé ou qui sont en retard de paiement. Montre le montant dû, payé et le reste pour chaque étudiant. Utiliser pour "qui n\'a pas payé", "impayés", "étudiants en retard".';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Filtrer par classe (nom ou code)',
                ],
                'min_reste' => [
                    'type' => 'integer',
                    'description' => 'Montant minimum restant dû (en FCFA) pour filtrer',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 25)',
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
            ->with(['etudiant', 'classe.filiere'])
            ->where('status', 'active');

        if (!empty($args['classe'])) {
            $this->applyClasseSearch($inscriptionQuery, $args['classe']);
        }

        $inscriptions = $inscriptionQuery->get();

        if ($inscriptions->isEmpty()) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => 'Aucune inscription active trouvée.',
            ];
        }

        $inscriptionIds = $inscriptions->pluck('id');

        // Montant dû par inscription
        $subscriptions = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('is_active', true)
            ->selectRaw('inscription_id, SUM(amount) as total_du')
            ->groupBy('inscription_id')
            ->pluck('total_du', 'inscription_id');

        // Montant payé (validé) par inscription
        $paiements = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('status', 'validé')
            ->selectRaw('inscription_id, SUM(montant) as total_paye')
            ->groupBy('inscription_id')
            ->pluck('total_paye', 'inscription_id');

        $minReste = (int) ($args['min_reste'] ?? 0);
        $debtors = [];

        foreach ($inscriptions as $insc) {
            $du = (float) ($subscriptions[$insc->id] ?? 0);
            $paye = (float) ($paiements[$insc->id] ?? 0);
            $reste = $du - $paye;

            if ($reste <= 0 || $du === 0.0) {
                continue;
            }

            if ($minReste > 0 && $reste < $minReste) {
                continue;
            }

            $etudiant = $insc->etudiant;
            $nom = $this->studentFullName($etudiant);
            $initials = $this->studentInitials($etudiant);
            $taux = round(($paye / $du) * 100, 0);

            $debtors[] = [
                'nom' => $nom,
                'initials' => $initials,
                'classe' => $insc->classe?->name ?? 'N/A',
                'reste' => $this->formatFCFA($reste),
                'detail' => $this->formatFCFA($paye) . ' / ' . $this->formatFCFA($du),
                'taux' => $taux . '%',
                'statut' => $taux >= 75 ? 'Presque à jour' : ($taux >= 50 ? 'Partiel' : ($taux > 0 ? 'En retard' : 'Impayé')),
                'reste_brut' => $reste,
                'lien' => $etudiant && Route::has('esbtp.etudiants.show')
                    ? route('esbtp.etudiants.show', $etudiant->id) : null,
                'lien_label' => 'Fiche',
                'lien_icon' => 'fas fa-user',
            ];
        }

        // Trier par reste décroissant (les plus gros impayés en premier)
        usort($debtors, fn ($a, $b) => $b['reste_brut'] <=> $a['reste_brut']);

        $limit = $this->clampLimit($args, 15);
        $total = count($debtors);
        $debtors = array_slice($debtors, 0, $limit);

        // Retirer le champ de tri
        $debtors = array_map(function ($d) {
            unset($d['reste_brut']);
            return $d;
        }, $debtors);

        return [
            'results' => $debtors,
            'count' => count($debtors),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.comptabilite.dashboard') ? route('esbtp.comptabilite.dashboard') : null,
        ];
    }
}
