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
            $routeCandidates = $this->findRouteCandidatesInSidebar($keyword);
            if (empty($routeCandidates)) {
                $explorationLog[] = "❌ Route not found for keyword: {$keyword}";
                return null;
            }
            $selectedRoute = $this->selectRouteCandidate($routeCandidates, $userRoles);
            if (!$selectedRoute) {
                $explorationLog[] = "❌ No route candidate matched user roles";
                return null;
            }

            $route = $selectedRoute['route'];
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
            $permissions = $selectedRoute['permissions'] ?? [];
            $allowedRoles = $selectedRoute['roles'] ?? [];

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
                'required_permissions' => array_values($permissions),
                'allowed_roles' => array_values($allowedRoles),
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

    protected function findRouteCandidatesInSidebar(string $keyword): array
    {
        $sidebarPath = resource_path('views/layouts/app.blade.php');

        if (!File::exists($sidebarPath)) {
            return [];
        }

        $content = File::get($sidebarPath);
        $lines = preg_split('/\R/', $content);

        $pattern = '/(@role\([^\)]+\)|@hasRole\([^\)]+\)|@hasAnyRole\([^\)]+\)|@elserole\([^\)]+\)|@endrole|@endhasrole|@endhasanyrole|@can\([^\)]+\)|@canany\([^\)]+\)|@endcan|@endcanany|@else|route\([\'"][^\'"]+[\'"]\))/i';

        $roleStack = [];
        $permissionStack = [];
        $candidates = [];

        foreach ($lines as $line) {
            if (!preg_match_all($pattern, $line, $tokens, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($tokens as $tokenMatch) {
                $token = $tokenMatch[0];
                $lowerToken = strtolower($token);

                if (Str::startsWith($lowerToken, '@role(') || Str::startsWith($lowerToken, '@hasrole(') || Str::startsWith($lowerToken, '@hasanyrole(')) {
                    $roles = $this->extractRolesFromDirective($token);
                    $roleStack[] = $roles;
                } elseif (Str::startsWith($lowerToken, '@elserole(')) {
                    if (!empty($roleStack)) {
                        array_pop($roleStack);
                    }
                    $roleStack[] = $this->extractRolesFromDirective($token);
                } elseif (preg_match('/@end(role|hasrole|hasanyrole)/i', $token)) {
                    if (!empty($roleStack)) {
                        array_pop($roleStack);
                    }
                } elseif (Str::startsWith($lowerToken, '@can(') || Str::startsWith($lowerToken, '@canany(')) {
                    $permissionStack[] = $this->extractPermissionsFromDirective($token);
                } elseif (preg_match('/@endcan(any)?/i', $token)) {
                    if (!empty($permissionStack)) {
                        array_pop($permissionStack);
                    }
                } elseif ($lowerToken === '@else') {
                    if (!empty($roleStack)) {
                        array_pop($roleStack);
                        $roleStack[] = [];
                    }
                } elseif (Str::startsWith($lowerToken, 'route(')) {
                    if (preg_match('/route\([\'"]([^\'"]+)[\'"]/', $token, $routeMatch)) {
                        $routeName = $routeMatch[1];
                        if (Str::contains($routeName, $keyword)) {
                            $candidates[] = [
                                'route' => $routeName,
                                'roles' => $this->flattenRoleStack($roleStack),
                                'permissions' => $this->flattenPermissionStack($permissionStack),
                            ];
                        }
                    }
                }
            }
        }

        return $candidates;
    }

    protected function selectRouteCandidate(array $candidates, array $userRoles): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        if (!empty($userRoles)) {
            foreach ($candidates as $candidate) {
                $candidateRoles = $candidate['roles'] ?? [];
                if (empty($candidateRoles) || array_intersect($candidateRoles, $userRoles)) {
                    return $candidate;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (empty($candidate['roles'])) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
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

        // Trouver la méthode et tout le fichier pour les méthodes privées appelées
        $methodPattern = '/(?:public|private|protected)\s+function\s+' . $methodName . '\s*\([^)]*\)\s*{/s';
        if (!preg_match($methodPattern, $source)) {
            return $filters;
        }

        // Liste des filtres communs et utiles pour les utilisateurs finaux
        $allowedQueryParams = [
            'status', 'statut',           // Statut (paiements, inscriptions, etc.)
            'date_debut', 'date_fin',     // Plage de dates
            'month', 'year',              // Période mensuelle/annuelle
            'classe_id', 'filiere_id', 'niveau_id',  // Filtres académiques
            'etudiant_id', 'matricule',   // Étudiant spécifique
            'mode_paiement',              // Mode de paiement
            'category_id', 'type',        // Catégorie/type
        ];

        // Pattern: $request->input('filter_name')
        if (preg_match_all('/\$request->input\([\'"](\w+)[\'"]\)/', $source, $matches)) {
            foreach ($matches[1] as $paramName) {
                // Garder uniquement les filtres utiles
                if (in_array($paramName, $allowedQueryParams, true)) {
                    $filters[$paramName] = 'query_param';
                }
            }
        }

        // Pattern: $request->query('filter_name')
        if (preg_match_all('/\$request->query\([\'"](\w+)[\'"]\)/', $source, $matches)) {
            foreach ($matches[1] as $paramName) {
                if (in_array($paramName, $allowedQueryParams, true)) {
                    $filters[$paramName] = 'query_param';
                }
            }
        }

        // Pattern: whereMonth('column', ...) - important pour les dates
        if (preg_match_all('/->whereMonth\([\'"](\w+)[\'"]/', $source, $matches)) {
            foreach ($matches[1] as $column) {
                $filters['month'] = $column;
            }
        }

        // Pattern: whereDate('column', '>=', ...) - date de début
        if (preg_match_all('/->whereDate\([\'"](\w+)[\'"],\s*[\'"]>=[\'"]]/', $source, $matches)) {
            foreach ($matches[1] as $column) {
                if (!isset($filters['date_debut'])) {
                    $filters['date_debut'] = $column;
                }
            }
        }

        // Pattern: whereDate('column', '<=', ...) - date de fin
        if (preg_match_all('/->whereDate\([\'"](\w+)[\'"],\s*[\'"]<=[\'"]]/', $source, $matches)) {
            foreach ($matches[1] as $column) {
                if (!isset($filters['date_fin'])) {
                    $filters['date_fin'] = $column;
                }
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

    protected function extractRolesFromDirective(string $directive): array
    {
        if (preg_match('/\(([^)]+)\)/', $directive, $match)) {
            $raw = $match[1];
            $raw = str_replace(['"', "'"], '', $raw);
            $parts = preg_split('/[|,]/', $raw);
            $roles = [];
            foreach ($parts as $part) {
                $role = trim($part);
                if ($role !== '') {
                    $roles[] = $role;
                }
            }
            return $roles;
        }
        return [];
    }

    protected function extractPermissionsFromDirective(string $directive): array
    {
        if (preg_match('/\(([^)]+)\)/', $directive, $match)) {
            $raw = $match[1];
            $raw = str_replace(['"', "'"], '', $raw);
            $parts = preg_split('/[|,]/', $raw);
            $permissions = [];
            foreach ($parts as $part) {
                $permission = trim($part);
                if ($permission !== '') {
                    $permissions[] = $permission;
                }
            }
            return $permissions;
        }
        return [];
    }

    protected function flattenRoleStack(array $stack): array
    {
        $roles = [];
        foreach ($stack as $entry) {
            $roles = array_merge($roles, $entry);
        }
        return array_values(array_unique(array_filter($roles)));
    }

    protected function flattenPermissionStack(array $stack): array
    {
        $permissions = [];
        foreach ($stack as $entry) {
            $permissions = array_merge($permissions, $entry);
        }
        return array_values(array_unique(array_filter($permissions)));
    }
}
