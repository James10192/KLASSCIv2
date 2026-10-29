<?php

// This script simulates a browser request to test the route

// Set up cURL
$ch = curl_init();

// Set the URL
$url = 'http://localhost/ESBTP-yAKRO/public/esbtp/emploi-temps/1/edit';

// Set up cookies to maintain session
$cookieFile = __DIR__ . '/cookies.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile); // Delete existing cookie file to start fresh
}
file_put_contents($cookieFile, '');

// First, let's login to get a valid session
$loginUrl = 'http://localhost/ESBTP-yAKRO/public/login';

// Get CSRF token from login page
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Extract CSRF token
preg_match('/<input type="hidden" name="_token" value="([^"]+)"/', $response, $matches);
if (empty($matches[1])) {
    echo "Could not find CSRF token\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
    exit;
}
$csrfToken = $matches[1];
echo "CSRF Token: " . $csrfToken . "\n";

// Now login with the token
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'username' => 'Marcel',
    'password' => 'Marcel@123', // Replace with the actual password
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Check if login was successful by looking for a redirect to dashboard
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Login HTTP Code: " . $httpCode . "\n";

if ($httpCode !== 302 && strpos($response, 'dashboard') === false) {
    echo "Login failed\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
    exit;
}

echo "Login successful\n";

// Now try to access the edit route
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Check the response
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Edit Route HTTP Code: " . $httpCode . "\n";

if ($httpCode === 403) {
    echo "Access denied! The route is forbidden.\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
} elseif ($httpCode === 200) {
    echo "Access granted! The route is accessible.\n";
    echo "Response contains edit form: " . (strpos($response, 'form') !== false ? 'Yes' : 'No') . "\n";
} else {
    echo "Unexpected status code.\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
}

// Close cURL
curl_close($ch);

echo "Done!\n";
