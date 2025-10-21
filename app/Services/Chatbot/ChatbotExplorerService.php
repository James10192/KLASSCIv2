<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotKnowledgeBase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Service d'exploration autonome du code KLASSCI
 *
 * Ce service permet au chatbot d'apprendre automatiquement comment récupérer
 * les données en analysant :
 * - La sidebar (app.blade.php) pour trouver les routes
 * - Les routes (web.php) pour trouver les controllers
 * - Les controllers pour comprendre la logique  
 * - Les modèles pour connaître la structure BDD
 * - Les vues pour comprendre l'affichage
 *
 * Toute cette connaissance est stockée dans chatbot_knowledge_base pour
 * ne pas avoir à re-explorer à chaque fois.
 */
class ChatbotExplorerService
{
    /**
     * Explorer et apprendre comment récupérer une ressource
     */
    public function explore(string $intent, ?string $userInput = null, array $userRoles = []): ?array
    {
        Log::info('🔍 ChatbotExplorer - START exploration', [
            'intent' => $intent,
            'user_roles' => $userRoles,
        ]);

        $explorationLog = [];
        $startTime = microtime(true);

        try {
            // Étape 1: Extraire le mot-clé
            $keyword = $this->extractKeywordFromIntent($intent);
            $explorationLog[] = "Keyword extracted: {$keyword}";

            // Étape 2: Chercher la route dans la sidebar
            $route = $this->findRouteInSidebar($keyword);
            if (!$route) {
                $explorationLog[] = "❌ Route not found for keyword: {$keyword}";
                return null;
            }
            $explorationLog[] = "✅ Route found: {$route}";

            // Étape 3: Trouver le controller
            $controllerInfo = $this->findControllerFromRoute($route);
            if (!$controllerInfo) {
                $explorationLog[] = "❌ Controller not found for route: {$route}";
                return null;
            }
            $explorationLog[] = "✅ Controller: {$controllerInfo['controller']}@{$controllerInfo['method']}";

            // Étape 4: Analyser le controller
            $controllerAnalysis = $this->analyzeController(
                $controllerInfo['controller'],
                $controllerInfo['method']
            );
            if (!$controllerAnalysis) {
                $explorationLog[] = "❌ Controller analysis failed";
                return null;
            }
            $explorationLog[] = "✅ Model found: {$controllerAnalysis['model']}";

            // Étape 5: Analyser le modèle
            $modelAnalysis = $this->analyzeModel($controllerAnalysis['model']);
            if (!$modelAnalysis) {
                $explorationLog[] = "❌ Model analysis failed";
                return null;
            }
            $explorationLog[] = "✅ Table: {$modelAnalysis['table']}";

            // Étape 6: Construire le deep link
            $deepLinkPattern = $this->buildDeepLinkPattern($route, $controllerAnalysis['filters'] ?? []);
            $explorationLog[] = "Deep link: {$deepLinkPattern}";

            // Étape 7: Extraire permissions et rôles
            $permissions = $this->extractPermissionsForRoute($route);
            $allowedRoles = $this->extractAllowedRolesForRoute($route);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $explorationLog[] = "✅ Completed in {$duration}ms";

            $knowledge = [
                'intent' => $intent,
                'route' => $route,
                'controller' => $controllerInfo['controller'],
                'model' => $controllerAnalysis['model'],
                'table_name' => $modelAnalysis['table'],
                'columns_mapping' => $controllerAnalysis['filters'] ?? [],
                'display_columns' => $modelAnalysis['fillable'] ?? [],
                'deep_link_pattern' => $deepLinkPattern,
                'required_permissions' => $permissions,
                'allowed_roles' => $allowedRoles,
                'exploration_log' => implode("\n", $explorationLog),
            ];

            Log::info('✅ ChatbotExplorer - SUCCESS', ['intent' => $intent, 'duration_ms' => $duration]);

            return $knowledge;

        } catch (\Exception $e) {
            Log::error('❌ ChatbotExplorer - FAILED', [
                'intent' => $intent,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function extractKeywordFromIntent(string $intent): string
    {
        // get_paiements => paiements
        $keyword = preg_replace('/^(get|create|update|delete)_/', '', $intent);
        return $keyword;
    }

    protected function findRouteInSidebar(string $keyword): ?string
    {
        $sidebarPath = resource_path('views/layouts/app.blade.php');

        if (!File::exists($sidebarPath)) {
            return null;
        }

        $content = File::get($sidebarPath);

        // Regex: route('esbtp.paiements.index')
        preg_match_all('/route\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);

        foreach ($matches[1] as $routeName) {
            if (Str::contains($routeName, $keyword)) {
                return $routeName;
            }
        }

        return null;
    }

    protected function findControllerFromRoute(string $routeName): ?array
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            if ($route->getName() === $routeName) {
                $action = $route->getActionName();

                if (Str::contains($action, '@')) {
                    [$controller, $method] = explode('@', $action);
                    $controller = class_basename($controller);

                    return [
                        'controller' => $controller,
                        'method' => $method,
                        'uri' => $route->uri(),
                    ];
                }
            }
        }

        return null;
    }

    protected function analyzeController(string $controllerName, string $methodName): ?array
    {
        $possiblePaths = [
            app_path("Http/Controllers/{$controllerName}.php"),
            app_path("Http/Controllers/ESBTP/{$controllerName}.php"),
        ];

        $controllerPath = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $controllerPath = $path;
                break;
            }
        }

        if (!$controllerPath) {
            return null;
        }

        $source = File::get($controllerPath);

        $model = $this->extractModelFromSource($source);
        $filters = $this->extractFiltersFromSource($source, $methodName);
        $view = $this->extractViewFromSource($source, $methodName);

        return [
            'model' => $model,
            'filters' => $filters,
            'view' => $view,
        ];
    }

    protected function extractModelFromSource(string $source): ?string
    {
        // Pattern: use App\Models\ESBTPPaiement;
        if (preg_match('/use App\\\\Models\\\\(ESBTP\w+);/', $source, $matches)) {
            return $matches[1];
        }

        // Pattern: ESBTPPaiement::
        if (preg_match('/(ESBTP\w+)::/', $source, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function extractFiltersFromSource(string $source, string $methodName): array
    {
        $filters = [];

        // Trouver la méthode
        $methodPattern = '/public\s+function\s+' . $methodName . '\s*\([^)]*\)\s*{([^}]*(?:{[^}]*}[^}]*)*)}/s';
        if (!preg_match($methodPattern, $source, $methodMatch)) {
            return $filters;
        }

        $methodBody = $methodMatch[1];

        // where('column', ...)
        if (preg_match_all('/->where\([\'"](\w+)[\'"]/', $methodBody, $matches)) {
            foreach ($matches[1] as $column) {
                $filters[$column] = 'column';
            }
        }

        // whereMonth('column', ...)
        if (preg_match_all('/->whereMonth\([\'"](\w+)[\'"]/', $methodBody, $matches)) {
            foreach ($matches[1] as $column) {
                $filters['month'] = $column;
            }
        }

        return $filters;
    }

    protected function extractViewFromSource(string $source, string $methodName): ?string
    {
        $methodPattern = '/public\s+function\s+' . $methodName . '\s*\([^)]*\)\s*{([^}]*(?:{[^}]*}[^}]*)*)}/s';
        if (!preg_match($methodPattern, $source, $methodMatch)) {
            return null;
        }

        $methodBody = $methodMatch[1];

        // return view('esbtp.paiements.index')
        if (preg_match('/return\s+view\([\'"]([^\'"]+)[\'"]/', $methodBody, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function analyzeModel(string $modelName): ?array
    {
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass();

            return [
                'table' => $model->getTable(),
                'fillable' => $model->getFillable(),
                'casts' => $model->getCasts(),
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractPermissionsForRoute(string $routeName): array
    {
        $sidebarPath = resource_path('views/layouts/app.blade.php');

        if (!File::exists($sidebarPath)) {
            return [];
        }

        $content = File::get($sidebarPath);

        // @can('permission')
        if (preg_match_all('/@can\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            return array_unique($matches[1]);
        }

        return [];
    }

    protected function extractAllowedRolesForRoute(string $routeName): array
    {
        $sidebarPath = resource_path('views/layouts/app.blade.php');

        if (!File::exists($sidebarPath)) {
            return [];
        }

        $content = File::get($sidebarPath);

        $roles = [];

        // @hasRole('superAdmin')
        if (preg_match_all('/@hasRole\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $roles = array_merge($roles, $matches[1]);
        }

        // @role('coordinateur')
        if (preg_match_all('/@role\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $roles = array_merge($roles, $matches[1]);
        }

        return array_unique($roles);
    }

    protected function buildDeepLinkPattern(string $routeName, array $filters): string
    {
        try {
            $baseUrl = route($routeName);
        } catch (\Exception $e) {
            $baseUrl = '/' . str_replace('.', '/', $routeName);
        }

        if (!empty($filters)) {
            $params = [];
            foreach ($filters as $filterKey => $filterValue) {
                $params[] = "{$filterKey}={{$filterKey}}";
            }
            $baseUrl .= '?' . implode('&', $params);
        }

        return $baseUrl;
    }

    public function saveKnowledge(array $knowledge): ChatbotKnowledgeBase
    {
        return ChatbotKnowledgeBase::updateOrCreate(
            ['intent' => $knowledge['intent']],
            $knowledge
        );
    }

    public function getKnowledge(string $intent): ?ChatbotKnowledgeBase
    {
        return ChatbotKnowledgeBase::byIntent($intent)->first();
    }

    public function hasKnowledge(string $intent): bool
    {
        return ChatbotKnowledgeBase::byIntent($intent)->exists();
    }
}
