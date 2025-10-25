<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotKnowledgeBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de navigation intelligente multi-niveaux
 *
 * Gère l'exploration progressive des routes et la vérification des données en BDD
 * pour éviter les hallucinations et fournir des réponses précises.
 */
class ChatbotNavigationService
{
    /**
     * Vérifier progressivement l'existence des données
     *
     * @param string $intent
     * @param array $filters Filtres extraits de la question utilisateur
     * @param ChatbotKnowledgeBase $knowledge
     * @return array{exists: bool, level: int, data: mixed, suggestion: string, deep_link: string|null}
     */
    public function verifyDataExists(string $intent, array $filters, ChatbotKnowledgeBase $knowledge): array
    {
        Log::info('🔍 ChatbotNavigation - START verification', [
            'intent' => $intent,
            'filters' => $filters,
            'model' => $knowledge->model,
        ]);

        // Exemple: get_frais → vérification multi-niveaux
        if (str_starts_with($intent, 'get_frais')) {
            return $this->verifyFraisHierarchy($filters, $knowledge);
        }

        // Exemple: get_inscriptions
        if (str_starts_with($intent, 'get_inscriptions')) {
            return $this->verifyInscriptionsHierarchy($filters, $knowledge);
        }

        // Exemple: get_paiements
        if (str_starts_with($intent, 'get_paiements')) {
            return $this->verifyPaiementsHierarchy($filters, $knowledge);
        }

        // Fallback: vérification simple
        return $this->verifySimple($filters, $knowledge);
    }

    /**
     * Vérification hiérarchique pour les frais
     *
     * Niveau 1: Catégorie de frais existe ?
     * Niveau 2: Combinaison filière+niveau existe ?
     * Niveau 3: Configuration frais pour cette classe existe ?
     */
    protected function verifyFraisHierarchy(array $filters, ChatbotKnowledgeBase $knowledge): array
    {
        $startTime = microtime(true);

        // Niveau 1: Vérifier catégorie de frais
        $categorie = $this->findCategoriesFrais($filters);

        if (!$categorie) {
            return [
                'exists' => false,
                'level' => 0,
                'data' => null,
                'suggestion' => "La catégorie de frais demandée n'existe pas encore. Vous pouvez la créer depuis la page 'Gestion des Frais'.",
                'deep_link' => route('esbtp.frais.index'),
                'action_label' => 'Créer une catégorie',
            ];
        }

        Log::info('✅ Niveau 1: Catégorie trouvée', [
            'categorie_id' => $categorie->id,
            'name' => $categorie->name,
        ]);

        // Niveau 2: Vérifier combinaison filière + niveau
        $classes = $this->findClassesByFiltersAndLevel($filters);

        if ($classes->isEmpty()) {
            return [
                'exists' => false,
                'level' => 1,
                'data' => ['categorie' => $categorie],
                'suggestion' => "La catégorie '{$categorie->name}' existe, mais aucune classe ne correspond à la combinaison demandée (filière/niveau).",
                'deep_link' => route('esbtp.classes.index'),
                'action_label' => 'Voir les classes disponibles',
            ];
        }

        Log::info('✅ Niveau 2: Classes trouvées', [
            'count' => $classes->count(),
            'classes' => $classes->pluck('name')->toArray(),
        ]);

        // Niveau 3: Vérifier configuration frais
        $configurations = [];
        foreach ($classes as $classe) {
            $config = DB::table('esbtp_frais_configurations')
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_id', $classe->niveau_etude_id)
                ->where('frais_category_id', $categorie->id)
                ->where('is_active', true)
                ->first();

            if ($config) {
                $configurations[] = [
                    'classe' => $classe,
                    'config' => $config,
                ];
            }
        }

        if (empty($configurations)) {
            return [
                'exists' => false,
                'level' => 2,
                'data' => [
                    'categorie' => $categorie,
                    'classes' => $classes,
                ],
                'suggestion' => "La classe existe mais les frais '{$categorie->name}' ne sont pas encore configurés pour cette combinaison filière/niveau.",
                'deep_link' => route('esbtp.frais.index'),
                'action_label' => 'Configurer les frais',
            ];
        }

        Log::info('✅ Niveau 3: Configurations trouvées', [
            'count' => count($configurations),
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Succès: tout existe
        return [
            'exists' => true,
            'level' => 3,
            'data' => [
                'categorie' => $categorie,
                'classes' => $classes,
                'configurations' => $configurations,
            ],
            'suggestion' => null,
            'deep_link' => route('esbtp.frais.index'),
            'action_label' => 'Voir les frais',
        ];
    }

    /**
     * Trouver catégorie de frais par nom (recherche fuzzy)
     */
    protected function findCategoriesFrais(array $filters): ?object
    {
        // Chercher dans les filtres: type_frais, categorie, nom, etc.
        $searchTerms = [
            $filters['type_frais'] ?? null,
            $filters['categorie'] ?? null,
            $filters['nom'] ?? null,
        ];

        // Si pas de filtre explicite, essayer de détecter depuis le message raw
        if (empty(array_filter($searchTerms)) && isset($filters['_raw_message'])) {
            $message = strtolower($filters['_raw_message']);

            // Détection de mots-clés courants pour types de frais
            if (str_contains($message, 'inscription')) {
                $searchTerms[] = 'inscription';
            } elseif (str_contains($message, 'scolarité') || str_contains($message, 'scolarite')) {
                $searchTerms[] = 'scolarité';
            } elseif (str_contains($message, 'frais scolaire')) {
                $searchTerms[] = 'scolarité';
            }
        }

        $searchTerms = array_filter($searchTerms);

        if (empty($searchTerms)) {
            // Par défaut, chercher "scolarité" si rien de spécifié
            $searchTerms = ['scolarité'];
        }

        foreach ($searchTerms as $term) {
            $categorie = DB::table('esbtp_frais_categories')
                ->where('name', 'LIKE', "%{$term}%")
                ->first();

            if ($categorie) {
                return $categorie;
            }
        }

        return null;
    }

    /**
     * Trouver classes par filière + niveau
     */
    protected function findClassesByFiltersAndLevel(array $filters): \Illuminate\Support\Collection
    {
        $query = DB::table('esbtp_classes as c')
            ->select('c.*')
            ->where('c.is_active', true);

        // Filtre par filière - Recherche exacte sur la chaîne complète
        if (!empty($filters['filiere']) || !empty($filters['formation'])) {
            $filiereSearch = $filters['filiere'] ?? $filters['formation'];

            // Extraire le mot-clé principal (ignorer BTS, Année, etc.)
            $keywords = explode(' ', $filiereSearch);
            $filiereKeywords = array_filter($keywords, function($word) {
                return !in_array(strtolower($word), ['bts', 'année', 'année', '1ère', '2ème', 'première', 'deuxième']);
            });

            if (!empty($filiereKeywords)) {
                $query->join('esbtp_filieres as f', 'c.filiere_id', '=', 'f.id');
                // Tous les mots-clés doivent être présents (AND)
                foreach ($filiereKeywords as $keyword) {
                    $query->where('f.name', 'LIKE', "%{$keyword}%");
                }
            }
        }

        // Filtre par niveau - Recherche exacte sur la chaîne complète
        if (!empty($filters['niveau'])) {
            $niveauSearch = $filters['niveau'];

            $query->join('esbtp_niveau_etudes as n', 'c.niveau_etude_id', '=', 'n.id');
            // Recherche directe sur la chaîne complète du niveau
            $query->where('n.name', 'LIKE', "%{$niveauSearch}%");
        }

        return collect($query->get());
    }

    /**
     * Vérification hiérarchique pour les inscriptions
     */
    protected function verifyInscriptionsHierarchy(array $filters, ChatbotKnowledgeBase $knowledge): array
    {
        // TODO: Implémenter vérification inscriptions
        return $this->verifySimple($filters, $knowledge);
    }

    /**
     * Vérification hiérarchique pour les paiements
     */
    protected function verifyPaiementsHierarchy(array $filters, ChatbotKnowledgeBase $knowledge): array
    {
        // Les paiements sont directs (pas de hiérarchie complexe)
        return $this->verifySimple($filters, $knowledge);
    }

    /**
     * Vérification simple (fallback)
     */
    protected function verifySimple(array $filters, ChatbotKnowledgeBase $knowledge): array
    {
        $modelClass = "App\\Models\\{$knowledge->model}";

        if (!class_exists($modelClass)) {
            return [
                'exists' => false,
                'level' => 0,
                'data' => null,
                'suggestion' => "Impossible de vérifier les données (modèle introuvable).",
                'deep_link' => null,
            ];
        }

        $query = $modelClass::query();

        // Appliquer filtres simples
        foreach ($filters as $key => $value) {
            // Ignorer les clés spéciales qui ne sont pas des colonnes SQL
            if (in_array($key, ['search', 'page', 'per_page', 'limit', '_raw_message'])) {
                continue;
            }

            if (method_exists($query, 'where')) {
                $query->where($key, $value);
            }
        }

        $count = $query->count();

        return [
            'exists' => $count > 0,
            'level' => 1,
            'data' => $count > 0 ? $query->limit(5)->get() : null,
            'suggestion' => $count === 0 ? "Aucune donnée trouvée pour ces critères." : null,
            'deep_link' => $knowledge->deep_link_pattern,
        ];
    }

    /**
     * Générer deep link avec filtres appliqués
     */
    public function buildDeepLinkWithFilters(string $pattern, array $filters): string
    {
        $link = $pattern;

        foreach ($filters as $key => $value) {
            $placeholder = "{{$key}}";

            if (str_contains($link, $placeholder)) {
                $link = str_replace($placeholder, urlencode((string) $value), $link);
            }
        }

        // Supprimer les placeholders non remplis
        $link = preg_replace('/[?&]\w+={[^}]+}/', '', $link);
        $link = rtrim($link, '?&');

        return $link;
    }
}
