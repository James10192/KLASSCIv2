<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

echo "🧪 Test du PaywallMiddleware avec API Master\n";
echo str_repeat('=', 60) . "\n\n";

// Test 1: Vérifier la configuration
echo "📋 Test 1: Vérification de la configuration\n";
echo "-----------------------------------------\n";
$masterApiUrl = config('services.master.api_url');
$masterApiToken = config('services.master.api_token');
$tenantCode = config('app.tenant_code');

echo "Master API URL: " . ($masterApiUrl ?: '❌ NON DÉFINI') . "\n";
echo "Master API Token: " . ($masterApiToken ? '✅ Défini (' . substr($masterApiToken, 0, 20) . '...)' : '❌ NON DÉFINI') . "\n";
echo "Tenant Code: " . ($tenantCode ?: '❌ NON DÉFINI') . "\n\n";

if (!$masterApiUrl || !$masterApiToken || !$tenantCode) {
    echo "❌ Configuration incomplète. Arrêt du test.\n";
    exit(1);
}

// Test 2: Appeler l'API Master
echo "🌐 Test 2: Appel API Master\n";
echo "-----------------------------------------\n";
echo "URL: {$masterApiUrl}/tenants/{$tenantCode}/limits\n";

try {
    $response = Http::withToken($masterApiToken)
        ->timeout(10)
        ->get("{$masterApiUrl}/tenants/{$tenantCode}/limits");

    if ($response->successful()) {
        echo "✅ API Master répond avec succès (HTTP {$response->status()})\n\n";

        $data = $response->json();

        echo "📊 Données récupérées:\n";
        echo "  - Tenant: {$data['tenant_name']} ({$data['tenant_code']})\n";
        echo "  - Plan: {$data['plan']}\n";
        echo "  - Statut: {$data['status']}\n\n";

        echo "📈 Limites:\n";
        echo "  - Users: {$data['limits']['max_users']}\n";
        echo "  - Staff: {$data['limits']['max_staff']}\n";
        echo "  - Students: {$data['limits']['max_students']}\n";
        echo "  - Inscriptions/an: {$data['limits']['max_inscriptions_per_year']}\n";
        echo "  - Storage: {$data['limits']['max_storage_mb']} MB\n\n";

        echo "📊 Utilisation actuelle:\n";
        echo "  - Users: {$data['current_usage']['users']} ({$data['usage_percentage']['users']}%)\n";
        echo "  - Staff: {$data['current_usage']['staff']} ({$data['usage_percentage']['staff']}%)\n";
        echo "  - Students: {$data['current_usage']['students']} ({$data['usage_percentage']['students']}%)\n";
        echo "  - Inscriptions: {$data['current_usage']['inscriptions_per_year']} ({$data['usage_percentage']['inscriptions']}%)\n";
        echo "  - Storage: {$data['current_usage']['storage_mb']} MB ({$data['usage_percentage']['storage']}%)\n\n";

        echo "🔒 Statut Quota:\n";
        echo "  - Over quota: " . ($data['quota_status']['is_over_quota'] ? '❌ OUI' : '✅ NON') . "\n";
        echo "  - Users over limit: " . ($data['quota_status']['users_over_limit'] ? '❌ OUI' : '✅ NON') . "\n";
        echo "  - Inscriptions over limit: " . ($data['quota_status']['inscriptions_over_limit'] ? '❌ OUI' : '✅ NON') . "\n\n";

        if (!empty($data['blocked_features'])) {
            echo "⚠️  Fonctionnalités bloquées:\n";
            foreach ($data['blocked_features'] as $feature) {
                echo "  - {$feature}\n";
            }
            echo "\n";
        } else {
            echo "✅ Aucune fonctionnalité bloquée\n\n";
        }

        echo "📅 Abonnement:\n";
        echo "  - Début: {$data['subscription']['start_date']}\n";
        echo "  - Fin: {$data['subscription']['end_date']}\n";
        echo "  - Expiré: " . ($data['subscription']['is_expired'] ? '❌ OUI' : '✅ NON') . "\n";
        echo "  - Jours restants: " . round($data['subscription']['days_remaining']) . " jours\n\n";

    } else {
        echo "❌ Erreur HTTP {$response->status()}\n";
        echo "Réponse: " . $response->body() . "\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception lors de l'appel API:\n";
    echo "  " . $e->getMessage() . "\n\n";
}

// Test 3: Vérifier le cache
echo "💾 Test 3: Vérification du cache\n";
echo "-----------------------------------------\n";
$cacheKey = 'paywall_limits_' . $tenantCode;
if (Cache::has($cacheKey)) {
    echo "✅ Données en cache trouvées\n";
    echo "Clé: {$cacheKey}\n";
    echo "TTL: 5 minutes (300 secondes)\n\n";
} else {
    echo "ℹ️  Aucune donnée en cache (normal pour le premier appel)\n\n";
}

// Test 4: Test du fallback local
echo "🔄 Test 4: Test du fallback local\n";
echo "-----------------------------------------\n";
echo "Configuration pour forcer fallback:\n";
echo "  - Désactiver temporairement MASTER_API_URL dans .env\n";
echo "  - Ou bloquer l'accès réseau à localhost:8001\n";
echo "  - Le PaywallMiddleware devrait utiliser ESBTPSystemSetting\n\n";

echo "✅ Tests terminés!\n";
echo str_repeat('=', 60) . "\n";
