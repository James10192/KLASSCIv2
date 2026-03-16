<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPClasse;
use Illuminate\Support\Facades\Route;

class SearchClassesTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_classes';
    }

    public function description(): string
    {
        return 'Rechercher des classes. Retourne nom, code, filière, niveau, effectif, statut actif/inactif.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'name' => [
                    'type' => 'STRING',
                    'description' => 'Nom ou code de la classe',
                ],
                'filiere' => [
                    'type' => 'STRING',
                    'description' => 'Nom de la filière',
                ],
                'niveau' => [
                    'type' => 'STRING',
                    'description' => 'Niveau d\'études',
                ],
                'active_only' => [
                    'type' => 'BOOLEAN',
                    'description' => 'Si true, retourne uniquement les classes actives (défaut: true)',
                ],
                'limit' => [
                    'type' => 'INTEGER',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 50)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $query = ESBTPClasse::query()
            ->with(['filiere', 'niveau'])
            ->withCount('inscriptions');

        if (!empty($args['name'])) {
            $search = $args['name'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($args['filiere'])) {
            $filiereName = $args['filiere'];
            $query->whereHas('filiere', function ($q) use ($filiereName) {
                $q->where('name', 'like', "%{$filiereName}%");
            });
        }

        if (!empty($args['niveau'])) {
            $niveauName = $args['niveau'];
            $query->whereHas('niveau', function ($q) use ($niveauName) {
                $q->where('name', 'like', "%{$niveauName}%");
            });
        }

        $activeOnly = $args['active_only'] ?? true;
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $limit = min(max((int) ($args['limit'] ?? 15), 1), 50);
        $total = (clone $query)->count();
        $results = $query->orderBy('name')->limit($limit)->get();

        $classes = $results->map(function ($c) {
            return [
                'id' => $c->id,
                'nom' => $c->name,
                'code' => $c->code ?? 'N/A',
                'filiere' => $c->filiere?->name ?? 'N/A',
                'niveau' => $c->niveau?->name ?? 'N/A',
                'effectif' => $c->inscriptions_count ?? 0,
                'active' => $c->is_active ? 'Oui' : 'Non',
            ];
        })->toArray();

        return [
            'results' => $classes,
            'count' => count($classes),
            'total' => $total,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.classes.index') ? route('esbtp.classes.index') : null,
        ];
    }
}
