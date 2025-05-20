<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check database connection
try {
    $connection = DB::connection()->getPdo();
    echo "Database connection successful. Database name: " . DB::connection()->getDatabaseName() . "\n";
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Get the first admin user for testing
try {
    $user = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'superAdmin');
    })->first();

    if (!$user) {
        echo "Error: No admin user found for testing\n";
        exit(1);
    }

    // Log in the user
    \Illuminate\Support\Facades\Auth::login($user);
    echo "Logged in as user: " . $user->name . " (ID: " . $user->id . ")\n";

    // Create a request to the emploi-temps.show route
    $request = Illuminate\Http\Request::create('/esbtp/emploi-temps/1', 'GET');

    // Set the authenticated user for the request
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // Get the HTTP kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // Handle the request
    $response = $kernel->handle($request);

    // Check if the response is successful
    $statusCode = $response->getStatusCode();
    echo "Status code: " . $statusCode . "\n";

    // If the response is successful, check if it contains the expected content
    if ($statusCode === 200) {
        $content = $response->getContent();

        // Check if the content contains the error message about undefined variable $timeSlots
        if (strpos($content, 'Undefined variable $timeSlots') !== false) {
            echo "Error: Undefined variable \$timeSlots still exists\n";
        } else {
            echo "Success: No error about undefined variable \$timeSlots\n";
        }

        // Check if the content contains the timetable
        if (strpos($content, 'timetable-container') !== false) {
            echo "Success: Timetable container found\n";
        } else {
            echo "Error: Timetable container not found\n";
        }
    } else {
        echo "Error: Failed to load the page\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    exit(1);
}
