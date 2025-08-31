<?php

echo "🧹 Nettoyage complet des caches...\n";

// Vider tous les caches Laravel
$commands = [
    'cache:clear',
    'config:clear', 
    'route:clear',
    'view:clear',
    'optimize:clear'
];

foreach ($commands as $command) {
    echo "Exécution: php artisan {$command}\n";
    exec("php artisan {$command}", $output, $return_var);
    if ($return_var === 0) {
        echo "✅ Succès\n";
    } else {
        echo "❌ Erreur\n";
    }
}

// Vider le cache OPcache si possible
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OPcache vidé\n";
    } else {
        echo "❌ Échec du vidage OPcache\n";
    }
} else {
    echo "⚠️ OPcache non disponible\n";
}

// Reconstruire l'autoload de Composer
echo "Reconstruction de l'autoload Composer...\n";
exec("composer dump-autoload", $output, $return_var);
if ($return_var === 0) {
    echo "✅ Autoload reconstruit\n";
} else {
    echo "❌ Erreur lors de la reconstruction de l'autoload\n";
}

echo "✨ Nettoyage terminé !\n";