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
            'type' => 'OBJECT',
            'properties' => [
                'category' => [
                    'type' => 'STRING',
                    'description' => 'Catégorie de frais: "inscription", "scolarité", "cantine", "transport", etc.',
                ],
                'filiere' => [
                    'type' => 'STRING',
                    'description' => 'Nom de la filière (ex: "BTS Bâtiment")',
                ],
                'niveau' => [
                    'type' => 'STRING',
                    'description' => 'Niveau (ex: "Première Année", "Deuxième Année")',
                ],
                'type_affectation' => [
                    'type' => 'STRING',
                    'description' => 'Type: "affectés", "réaffectés", "non affectés". Si omis, retourne les 3 types.',
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
            'cat.name as categorie',
            'f.name as filiere',
            'n.name as niveau',
            'fc.amount_affecte',
            'fc.amount_reaffecte',
            'fc.amount_non_affecte',
            'fc.is_active',
            'fc.effective_date',
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

        $typeFilter = !empty($args['type_affectation']) ? mb_strtolower($args['type_affectation'], 'UTF-8') : null;

        // Déterminer quel(s) type(s) afficher
        $showAffecte = !$typeFilter;
        $showReaffecte = !$typeFilter;
        $showNonAffecte = !$typeFilter;

        if ($typeFilter) {
            $showNonAffecte = str_contains($typeFilter, 'non affecté') || str_contains($typeFilter, 'non-affecté');
            $showReaffecte = !$showNonAffecte && (str_contains($typeFilter, 'réaffecté') || str_contains($typeFilter, 'reaffecté') || str_contains($typeFilter, 'réa') || str_contains($typeFilter, 'rea'));
            $showAffecte = !$showNonAffecte && !$showReaffecte && str_contains($typeFilter, 'affecté');
        }

        $results = [];
        foreach ($configs as $c) {
            $base = [
                'categorie' => $c->categorie,
                'filiere' => $c->filiere,
                'niveau' => $c->niveau,
                'actif' => $c->is_active ? 'Oui' : 'Non',
            ];

            if ($showAffecte) {
                $results[] = array_merge($base, [
                    'type_tarif' => 'Affectés',
                    'montant' => number_format($c->amount_affecte ?? 0, 0, ',', ' ') . ' FCFA',
                    'montant_brut' => $c->amount_affecte ?? 0,
                ]);
            }

            if ($showReaffecte) {
                $results[] = array_merge($base, [
                    'type_tarif' => 'Réaffectés',
                    'montant' => number_format($c->amount_reaffecte ?? 0, 0, ',', ' ') . ' FCFA',
                    'montant_brut' => $c->amount_reaffecte ?? 0,
                ]);
            }

            if ($showNonAffecte) {
                $results[] = array_merge($base, [
                    'type_tarif' => 'Non affectés',
                    'montant' => number_format($c->amount_non_affecte ?? 0, 0, ',', ' ') . ' FCFA',
                    'montant_brut' => $c->amount_non_affecte ?? 0,
                ]);
            }
        }

        return [
            'results' => $results,
            'count' => count($results),
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.frais.index') ? route('esbtp.frais.index') : null,
        ];
    }
}
