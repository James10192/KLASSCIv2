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
        return 'Rechercher des paiements dans le système. Retourne étudiant, montant, statut, date, mode de paiement.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut du paiement: "en_attente", "validé", "rejeté", "annulé"',
                ],
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom de l\'étudiant',
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
        $query = ESBTPPaiement::query()->with(['etudiant']);

        if (!empty($args['status'])) {
            $status = $this->normalizeStatus($args['status']);
            $query->where('status', $status);
        }

        if (!empty($args['student_name'])) {
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

        $payments = $results->map(function ($p) {
            $etudiant = $p->etudiant;
            return [
                'id' => $p->id,
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'Inconnu',
                'montant' => number_format($p->montant ?? 0, 0, ',', ' ') . ' FCFA',
                'montant_brut' => $p->montant ?? 0,
                'statut' => ucfirst(str_replace('_', ' ', $p->status ?? 'inconnu')),
                'date' => $p->date_paiement?->format('d/m/Y') ?? 'N/A',
                'mode' => ucfirst($p->mode_paiement ?? 'N/A'),
                'lien_inscription' => $p->inscription_id && Route::has('esbtp.inscriptions.show')
                    ? route('esbtp.inscriptions.show', $p->inscription_id) : null,
            ];
        })->toArray();

        return [
            'results' => $payments,
            'count' => count($payments),
            'total' => $total,
            'display_type' => 'table',
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
