<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\Route;

class SearchPaymentsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_payments';
    }

    public function description(): string
    {
        return 'Rechercher des paiements groupés par inscription. Si l\'étudiant a plusieurs inscriptions et que inscription_id n\'est pas fourni, retourne la liste des inscriptions pour clarification — demande alors à l\'utilisateur laquelle il souhaite consulter avant de rappeler avec inscription_id.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom de l\'étudiant',
                ],
                'inscription_id' => [
                    'type' => 'integer',
                    'description' => 'ID de l\'inscription spécifique (utiliser après clarification si l\'étudiant a plusieurs inscriptions)',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut du paiement: "en_attente", "validé", "rejeté", "annulé"',
                ],
                'month' => [
                    'type' => 'integer',
                    'description' => 'Mois (1-12). Utiliser le mois en cours si "ce mois".',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Date de début au format YYYY-MM-DD',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'Date de fin au format YYYY-MM-DD',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 10, max: 25)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        // Si student_name fourni sans inscription_id, vérifier s'il a plusieurs inscriptions
        if (!empty($args['student_name']) && empty($args['inscription_id'])) {
            $inscriptions = \App\Models\ESBTPInscription::query()
                ->with(['classe.filiere', 'etudiant'])
                ->whereHas('etudiant', function ($q) use ($args) {
                    $this->applyFuzzyNameSearch($q, $args['student_name']);
                })
                ->orderByDesc('date_inscription')
                ->get();

            // Grouper par étudiant et prendre celui qui matche le mieux (le plus d'inscriptions ou LIKE exact)
            $byEtudiant = $inscriptions->groupBy('etudiant_id');
            if ($byEtudiant->count() > 1) {
                // Plusieurs étudiants matchent → garder celui avec LIKE exact sur nom
                $search = $args['student_name'];
                $bestMatch = $byEtudiant->filter(function ($group) use ($search) {
                    $e = $group->first()->etudiant;
                    $fullName = mb_strtolower(trim(($e->nom ?? '') . ' ' . ($e->prenoms ?? '')));
                    return str_contains($fullName, mb_strtolower($search));
                })->first();
                $inscriptions = $bestMatch ?? $byEtudiant->first();
            } else {
                $inscriptions = $byEtudiant->first();
            }

            if ($inscriptions->count() > 1) {
                // Plusieurs inscriptions → demander clarification
                $options = $inscriptions->map(function ($i) {
                    return [
                        'inscription_id' => $i->id,
                        'classe' => $i->classe?->name ?? 'N/A',
                        'filiere' => $i->classe?->filiere?->name ?? 'N/A',
                        'type' => ucfirst(str_replace('_', ' ', $i->type_inscription ?? 'N/A')),
                        'statut' => ucfirst(str_replace('_', ' ', $i->status ?? 'N/A')),
                        'date' => $i->date_inscription?->format('d/m/Y') ?? 'N/A',
                    ];
                })->toArray();

                $etudiantName = $inscriptions->first()->etudiant
                    ? trim($inscriptions->first()->etudiant->nom . ' ' . $inscriptions->first()->etudiant->prenoms)
                    : $args['student_name'];

                return [
                    'results' => [],
                    'count' => 0,
                    'needs_clarification' => true,
                    'message' => "{$etudiantName} a {$inscriptions->count()} inscriptions. Demande à l'utilisateur laquelle il souhaite consulter, puis rappelle search_payments avec inscription_id.",
                    'inscriptions' => $options,
                    'display_type' => 'text',
                ];
            }
        }

        $query = ESBTPPaiement::query()->with(['etudiant', 'inscription.classe.filiere', 'fraisCategory']);

        // Filtre par inscription_id spécifique
        if (!empty($args['inscription_id'])) {
            $query->where('inscription_id', $args['inscription_id']);
        }

        if (!empty($args['status'])) {
            $status = $this->normalizeStatus($args['status']);
            $query->where('status', $status);
        }

        if (!empty($args['student_name']) && empty($args['inscription_id'])) {
            $tool = $this;
            $query->whereHas('etudiant', function ($q) use ($args, $tool) {
                $tool->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['month'])) {
            $query->whereMonth('date_paiement', (int) $args['month']);
        }

        if (!empty($args['date_from'])) {
            $query->whereDate('date_paiement', '>=', $args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $query->whereDate('date_paiement', '<=', $args['date_to']);
        }

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $results = $query->orderByDesc('date_paiement')->limit($limit)->get();

        // Grouper les paiements par inscription
        $grouped = $results->groupBy('inscription_id');
        $groups = [];

        foreach ($grouped as $inscriptionId => $paiements) {
            $first = $paiements->first();
            $inscription = $first->inscription;
            $etudiant = $first->etudiant;
            $classe = $inscription?->classe;

            $inscriptionInfo = [
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'Inconnu',
                'classe' => $classe?->name ?? 'N/A',
                'filiere' => $classe?->filiere?->name ?? 'N/A',
                'type' => ucfirst(str_replace('_', ' ', $inscription?->type_inscription ?? 'N/A')),
                'statut' => ucfirst(str_replace('_', ' ', $inscription?->status ?? 'N/A')),
                'lien' => $inscriptionId && Route::has('esbtp.inscriptions.show')
                    ? route('esbtp.inscriptions.show', $inscriptionId) : null,
            ];

            $items = $paiements->map(function ($p) {
                return [
                    'categorie' => $p->fraisCategory?->name ?? 'N/A',
                    'montant' => number_format($p->montant ?? 0, 0, ',', ' ') . ' FCFA',
                    'statut' => ucfirst(str_replace('_', ' ', $p->status ?? 'inconnu')),
                    'date' => $p->date_paiement?->format('d/m/Y') ?? 'N/A',
                    'mode' => ucfirst($p->mode_paiement ?? 'N/A'),
                    'tranche' => $p->tranche ? 'Tranche ' . $p->tranche : null,
                ];
            })->toArray();

            $totalMontant = $paiements->sum('montant');

            $groups[] = [
                'inscription' => $inscriptionInfo,
                'payments' => $items,
                'total_paye' => number_format($totalMontant, 0, ',', ' ') . ' FCFA',
                'nb_paiements' => count($items),
            ];
        }

        return [
            'results' => $groups,
            'count' => $total,
            'total' => $total,
            'display_type' => 'payment_groups',
            'deep_link' => Route::has('esbtp.paiements.index') ? route('esbtp.paiements.index') : null,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = mb_strtolower(trim($status), 'UTF-8');
        return match (true) {
            str_contains($status, 'attente') => 'en_attente',
            str_contains($status, 'valid') => 'validé',
            str_contains($status, 'rejet') => 'rejeté',
            str_contains($status, 'annul') => 'annulé',
            default => $status,
        };
    }
}
