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
        return 'Rechercher des classes. Retourne nom, code, filière, niveau, places totales/occupées/disponibles et statut places (disponible/limité/presque_complet/complet).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe',
                ],
                'filiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la filière',
                ],
                'niveau' => [
                    'type' => 'string',
                    'description' => 'Niveau d\'études',
                ],
                'systeme' => [
                    'type' => 'string',
                    'description' => 'Système académique : "BTS" ou "LMD"',
                ],
                'active_only' => [
                    'type' => 'boolean',
                    'description' => 'Si true, retourne uniquement les classes actives (défaut: true)',
                ],
                'has_places' => [
                    'type' => 'boolean',
                    'description' => 'Si true, retourne uniquement les classes avec au moins une place disponible',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 50)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $query = ESBTPClasse::query()->with(['filiere', 'niveau']);

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

        if (!empty($args['systeme'])) {
            $query->where('systeme_academique', strtoupper($args['systeme']));
        }

        $activeOnly = $args['active_only'] ?? true;
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $limit = min(max((int) ($args['limit'] ?? 15), 1), 50);
        $total = (clone $query)->count();
        // Charger un peu plus large pour pouvoir filtrer has_places en PHP (l'accessor places_disponibles n'est pas queryable en SQL)
        $fetchLimit = !empty($args['has_places']) ? min($total, max($limit * 3, 50)) : $limit;
        $results = $query->orderBy('name')->limit($fetchLimit)->get();

        if (!empty($args['has_places'])) {
            $results = $results->filter(fn ($c) => ($c->places_disponibles ?? 0) > 0)->take($limit)->values();
        }

        $classes = $results->map(function ($c) {
            $placesTotales = (int) ($c->places_totales ?? 0);
            $placesDisponibles = (int) ($c->places_disponibles ?? 0);
            $placesOccupees = max(0, $placesTotales - $placesDisponibles);
            $ratio = $placesTotales > 0 ? $placesDisponibles / $placesTotales : 0;

            $statut = match (true) {
                $placesDisponibles <= 0 => 'complet',
                $ratio < 0.10 => 'presque_complet',
                $ratio < 0.30 => 'limite',
                default => 'disponible',
            };

            return [
                'id' => $c->id,
                'nom' => $c->name,
                'code' => $c->code ?? 'N/A',
                'filiere' => $c->filiere?->name ?? 'N/A',
                'niveau' => $c->niveau?->name ?? 'N/A',
                'systeme' => $c->systeme_academique ?? 'N/A',
                'places_totales' => $placesTotales,
                'places_occupees' => $placesOccupees,
                'places_disponibles' => $placesDisponibles,
                'statut_places' => $statut,
                'active' => $c->is_active ? 'Oui' : 'Non',
            ];
        })->values()->toArray();

        return [
            'results' => $classes,
            'count' => count($classes),
            'total' => $total,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.classes.index') ? route('esbtp.classes.index') : null,
        ];
    }
}
