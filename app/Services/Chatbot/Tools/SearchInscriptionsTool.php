<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Route;

class SearchInscriptionsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_inscriptions';
    }

    public function description(): string
    {
        return 'Rechercher des inscriptions d\'étudiants. Retourne étudiant, classe, type d\'inscription, statut, date.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut: "active", "en_attente", "annulée", "terminée"',
                ],
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe',
                ],
                'filiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la filière',
                ],
                'niveau' => [
                    'type' => 'string',
                    'description' => 'Niveau d\'études (ex: "Première Année", "Deuxième Année")',
                ],
                'type_inscription' => [
                    'type' => 'string',
                    'description' => 'Type: "nouvelle", "renouvellement", "transfert"',
                ],
                'without_payments' => [
                    'type' => 'boolean',
                    'description' => 'Si true, retourne uniquement les inscriptions sans aucun paiement',
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
        $query = ESBTPInscription::query()->with(['etudiant', 'classe.filiere', 'classe.niveau']);

        if (!empty($args['status'])) {
            $query->where('status', $this->normalizeStatus($args['status']));
        }

        if (!empty($args['student_name'])) {
            $tool = $this;
            $query->whereHas('etudiant', function ($q) use ($args, $tool) {
                $tool->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['classe'])) {
            $classeName = $args['classe'];
            $query->whereHas('classe', function ($q) use ($classeName) {
                $q->where('name', 'like', "%{$classeName}%");
            });
        }

        if (!empty($args['filiere'])) {
            $filiereName = $args['filiere'];
            $query->whereHas('classe.filiere', function ($q) use ($filiereName) {
                $q->where('name', 'like', "%{$filiereName}%");
            });
        }

        if (!empty($args['niveau'])) {
            $niveauName = $args['niveau'];
            $query->whereHas('classe.niveau', function ($q) use ($niveauName) {
                $q->where('name', 'like', "%{$niveauName}%");
            });
        }

        if (!empty($args['type_inscription'])) {
            $query->where('type_inscription', $args['type_inscription']);
        }

        if (!empty($args['without_payments'])) {
            $query->whereDoesntHave('paiements');
        }

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $results = $query->orderByDesc('date_inscription')->limit($limit)->get();

        $inscriptions = $results->map(function ($i) {
            $etudiant = $i->etudiant;
            $classe = $i->classe;
            return [
                'id' => $i->id,
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'Inconnu',
                'classe' => $classe?->name ?? 'Non affectée',
                'filiere' => $classe?->filiere?->name ?? 'N/A',
                'type' => ucfirst(str_replace('_', ' ', $i->type_inscription ?? 'N/A')),
                'statut' => ucfirst(str_replace('_', ' ', $i->status ?? 'N/A')),
                'date' => $i->date_inscription?->format('d/m/Y') ?? 'N/A',
                'lien' => Route::has('esbtp.inscriptions.show') ? route('esbtp.inscriptions.show', $i->id) : null,
            ];
        })->toArray();

        return [
            'results' => $inscriptions,
            'count' => count($inscriptions),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.inscriptions.index') ? route('esbtp.inscriptions.index') : null,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = mb_strtolower(trim($status), 'UTF-8');
        return match (true) {
            str_contains($status, 'attente') => 'en_attente',
            str_contains($status, 'activ') => 'active',
            str_contains($status, 'annul') => 'annulée',
            str_contains($status, 'termin') => 'terminée',
            default => $status,
        };
    }
}
