<?php

namespace App\Services\Chatbot\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SearchFeesTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_fees';
    }

    public function description(): string
    {
        return 'Rechercher les frais (scolarité, inscription, cantine, transport...) configurés par filière, niveau et type d\'affectation. Retourne catégorie, montants par type (affectés/réaffectés/non affectés).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'description' => 'Catégorie de frais: "inscription", "scolarité", "cantine", "transport", etc.',
                ],
                'filiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la filière (ex: "BTS Bâtiment")',
                ],
                'niveau' => [
                    'type' => 'string',
                    'description' => 'Niveau (ex: "Première Année", "Deuxième Année")',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        // Trouver la catégorie
        $categoryQuery = DB::table('esbtp_frais_categories');
        if (!empty($args['category'])) {
            $categoryQuery->where('name', 'like', '%' . $args['category'] . '%');
        }
        $categories = $categoryQuery->get();

        if ($categories->isEmpty()) {
            return [
                'results' => [],
                'count' => 0,
                'message' => 'Aucune catégorie de frais trouvée' . (!empty($args['category']) ? " pour \"{$args['category']}\"" : '') . '.',
                'categories_disponibles' => DB::table('esbtp_frais_categories')->pluck('name')->toArray(),
            ];
        }

        // Chercher les configurations
        $configQuery = DB::table('esbtp_frais_configurations as fc')
            ->join('esbtp_frais_categories as cat', 'fc.frais_category_id', '=', 'cat.id')
            ->join('esbtp_filieres as f', 'fc.filiere_id', '=', 'f.id')
            ->join('esbtp_niveau_etudes as n', 'fc.niveau_id', '=', 'n.id')
            ->whereIn('fc.frais_category_id', $categories->pluck('id'));

        if (!empty($args['filiere'])) {
            $configQuery->where('f.name', 'like', '%' . $args['filiere'] . '%');
        }

        if (!empty($args['niveau'])) {
            $configQuery->where('n.name', 'like', '%' . $args['niveau'] . '%');
        }

        $configs = $configQuery->select([
            'fc.id as config_id',
            'cat.name as categorie',
            'cat.is_mandatory',
            'f.name as filiere',
            'n.name as niveau',
            'fc.amount_affecte',
            'fc.amount_reaffecte',
            'fc.amount_non_affecte',
            'fc.is_active',
        ])->get();

        if ($configs->isEmpty()) {
            return [
                'results' => [],
                'count' => 0,
                'message' => 'Aucune configuration de frais trouvée pour ces critères.',
                'suggestion' => 'Les frais doivent être configurés par filière et niveau dans Comptabilité > Gestion des frais.',
                'deep_link' => Route::has('esbtp.frais.index') ? route('esbtp.frais.index') : null,
            ];
        }

        // Grouper par catégorie
        $groups = [];
        $grouped = $configs->groupBy('categorie');

        foreach ($grouped as $catName => $catConfigs) {
            $first = $catConfigs->first();
            $isMandatory = (bool) $first->is_mandatory;

            if ($isMandatory) {
                // Obligatoire : montants affecté/réaffecté/non affecté par filière+niveau
                $items = [];
                foreach ($catConfigs as $c) {
                    $items[] = [
                        'label' => $c->filiere . ' — ' . $c->niveau,
                        'affectes' => number_format($c->amount_affecte ?? 0, 0, ',', ' ') . ' FCFA',
                        'reaffectes' => number_format($c->amount_reaffecte ?? 0, 0, ',', ' ') . ' FCFA',
                        'non_affectes' => number_format($c->amount_non_affecte ?? 0, 0, ',', ' ') . ' FCFA',
                    ];
                }
                $groups[] = [
                    'title' => $catName,
                    'type' => 'mandatory',
                    'items' => $items,
                ];
            } else {
                // Optionnel : formules (options) avec montant, pas de filière/niveau
                $allOptions = [];
                $configIds = $catConfigs->pluck('config_id')->unique();
                $options = DB::table('esbtp_frais_options')
                    ->whereIn('configuration_id', $configIds)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['name', 'additional_amount']);

                // Dédupliquer par nom (même option sur plusieurs configs)
                $seen = [];
                foreach ($options as $opt) {
                    if (isset($seen[$opt->name])) {
                        continue;
                    }
                    $seen[$opt->name] = true;
                    $allOptions[] = [
                        'name' => $opt->name,
                        'montant' => number_format($opt->additional_amount ?? 0, 0, ',', ' ') . ' FCFA',
                    ];
                }

                $groups[] = [
                    'title' => $catName,
                    'type' => 'optional',
                    'options' => $allOptions,
                ];
            }
        }

        $totalConfigs = $configs->count();

        return [
            'results' => $groups,
            'count' => $totalConfigs,
            'display_type' => 'fee_groups',
            'deep_link' => Route::has('esbtp.frais.index') ? route('esbtp.frais.index') : null,
        ];
    }
}
