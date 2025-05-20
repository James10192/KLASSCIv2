<?php

/**
 * Fix script for diagnosing and resolving route conflicts
 * 
 * This script identifies conflicts between routes and suggests solutions.
 */

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

// Get all routes
$routes = Route::getRoutes();

// Extract all route names and URIs
$routeMap = [];
foreach ($routes as $route) {
    $name = $route->getName();
    $uri = $route->uri();
    $methods = implode('|', $route->methods());
    
    if ($name) {
        if (!isset($routeMap[$name])) {
            $routeMap[$name] = [];
        }
        
        $routeMap[$name][] = [
            'uri' => $uri,
            'methods' => $methods,
            'action' => $route->getActionName()
        ];
    }
}

// Find duplicate route names
$duplicates = array_filter($routeMap, function($routes) {
    return count($routes) > 1;
});

echo "==== Route Conflict Analysis ====\n\n";

if (count($duplicates) > 0) {
    echo "Found " . count($duplicates) . " duplicate route names:\n\n";
    
    foreach ($duplicates as $name => $routes) {
        echo "Route name: $name\n";
        echo "Defined " . count($routes) . " times:\n";
        
        foreach ($routes as $i => $route) {
            echo "  " . ($i + 1) . ". URI: {$route['uri']}, Methods: {$route['methods']}, Action: {$route['action']}\n";
        }
        
        echo "\n";
    }
} else {
    echo "No duplicate route names found.\n";
}

// Look specifically for routes related to attendances
echo "\n==== Attendance Routes ====\n\n";

$attendanceRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    $name = $route->getName();
    $methods = implode('|', $route->methods());
    
    if (strpos($uri, 'attendances') !== false || (isset($name) && strpos($name, 'attendances') !== false)) {
        $attendanceRoutes[] = [
            'name' => $name,
            'uri' => $uri,
            'methods' => $methods,
            'action' => $route->getActionName()
        ];
    }
}

usort($attendanceRoutes, function($a, $b) {
    return strcmp($a['uri'], $b['uri']);
});

echo "Found " . count($attendanceRoutes) . " attendance-related routes:\n\n";

foreach ($attendanceRoutes as $route) {
    echo "Name: " . ($route['name'] ?? 'unnamed') . "\n";
    echo "URI: {$route['uri']}, Methods: {$route['methods']}\n";
    echo "Action: {$route['action']}\n";
    echo "\n";
}

// Look for the specific routes causing issues
echo "\n==== Potential Route Conflicts ====\n\n";

$indexRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    $name = $route->getName();
    
    if ($uri === 'esbtp/attendances' || (isset($name) && ($name === 'esbtp.attendances.index' || $name === 'teacher.attendances'))) {
        $indexRoutes[] = [
            'name' => $name,
            'uri' => $uri, 
            'methods' => implode('|', $route->methods()),
            'action' => $route->getActionName()
        ];
    }
}

if (count($indexRoutes) > 0) {
    echo "Found " . count($indexRoutes) . " routes that could be conflicting for 'esbtp/attendances':\n\n";
    
    foreach ($indexRoutes as $route) {
        echo "Name: " . ($route['name'] ?? 'unnamed') . "\n";
        echo "URI: {$route['uri']}, Methods: {$route['methods']}\n";
        echo "Action: {$route['action']}\n";
        echo "\n";
    }
    
    echo "Recommendation: Update the teacher route to use a different URI, such as '/teacher/attendances'.\n";
    echo "Add this route to web.php:\n\n";
    echo "Route::get('/teacher/attendances', [ESBTPAttendanceController::class, 'index'])->name('teacher.attendances');\n";
    echo "\nAnd remove or comment out:\n";
    echo "Route::get('/esbtp/attendances', [ESBTPAttendanceController::class, 'index'])->name('teacher.attendances');\n";
} else {
    echo "No specific conflicts found for 'esbtp/attendances' routes.\n";
}

echo "\n==== Analysis Complete ====\n"; 