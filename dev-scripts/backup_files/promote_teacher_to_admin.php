<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Analyse des arguments de ligne de commande
$options = getopt('', ['email:', 'id:', 'username:', 'help']);

// Afficher l'aide si demandé ou si aucun argument n'est fourni
if (isset($options['help']) || empty($options)) {
    echo "Utilisation: php promote_teacher_to_admin.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --email=EMAIL        Email de l'enseignant à promouvoir\n";
    echo "  --id=ID              ID de l'enseignant à promouvoir\n";
    echo "  --username=USERNAME  Nom d'utilisateur de l'enseignant à promouvoir\n";
    echo "  --help               Affiche cette aide\n";
    exit(0);
}

// Recherche de l'utilisateur
$user = null;

if (isset($options['email'])) {
    $user = User::where('email', $options['email'])->first();
} elseif (isset($options['id'])) {
    $user = User::find($options['id']);
} elseif (isset($options['username'])) {
    $user = User::where('username', $options['username'])->first();
}

if (!$user) {
    echo "❌ Utilisateur non trouvé. Veuillez vérifier les informations fournies.\n";
    exit(1);
}

// Vérifier si l'utilisateur est un enseignant
if (!$user->hasRole('teacher') && !$user->hasRole('enseignant')) {
    echo "⚠️ ATTENTION: L'utilisateur {$user->name} n'est pas un enseignant.\n";
    echo "Voulez-vous quand même le promouvoir au rang de Super Admin? (o/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) != 'o') {
        echo "Opération annulée.\n";
        exit(0);
    }
}

// Vérifier si l'utilisateur est déjà un superAdmin
if ($user->hasRole('superAdmin')) {
    echo "ℹ️ L'utilisateur {$user->name} est déjà un Super Admin.\n";
    exit(0);
}

// Vérifier si le rôle superAdmin existe
$superAdminRole = Role::where('name', 'superAdmin')->first();
if (!$superAdminRole) {
    echo "Création du rôle 'superAdmin'...\n";
    $superAdminRole = Role::create(['name' => 'superAdmin']);
}

// Attribuer le rôle superAdmin tout en conservant les rôles existants
$user->assignRole('superAdmin');

echo "✅ L'utilisateur {$user->name} (email: {$user->email}) a été promu au rang de Super Admin avec succès.\n";
echo "Ses rôles actuels sont: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";

// Afficher les instructions de connexion
echo "\n=== INFORMATIONS DE CONNEXION ===\n";
echo "Nom d'utilisateur: {$user->username}\n";
echo "Email: {$user->email}\n";
echo "Mot de passe: Utilisez le mot de passe actuel de l'utilisateur.\n";
echo "\nL'utilisateur peut maintenant se connecter et accéder aux fonctionnalités Super Admin.\n"; 