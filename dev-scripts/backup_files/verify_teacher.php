<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Rechercher l'utilisateur enseignant
$teacher = \App\Models\User::where('email', 'enseignant@test.com')->first();

echo "=== VÉRIFICATION DU COMPTE ENSEIGNANT ===\n\n";

if ($teacher) {
    echo "✅ Le compte enseignant existe avec les détails suivants :\n";
    echo "-----------------------------------\n";
    echo "ID : " . $teacher->id . "\n";
    echo "Nom : " . $teacher->name . "\n";
    echo "Email : " . $teacher->email . "\n";
    echo "Rôles : " . implode(', ', $teacher->getRoleNames()->toArray()) . "\n";
    echo "-----------------------------------\n\n";
    
    // Vérifier si le mot de passe correspond
    if (\Illuminate\Support\Facades\Hash::check('password123', $teacher->password)) {
        echo "✅ Le mot de passe 'password123' est correct.\n\n";
    } else {
        echo "❌ Le mot de passe 'password123' ne correspond pas au mot de passe enregistré.\n\n";
        echo "Note: Si le mot de passe est incorrect, vous pouvez réinitialiser le mot de passe avec le script suivant :\n";
        echo "$ php reset_teacher_password.php\n\n";
    }
} else {
    echo "❌ Aucun compte enseignant n'a été trouvé avec l'email 'enseignant@test.com'.\n\n";
    echo "Note: Vous pouvez créer un compte enseignant de test avec le script suivant :\n";
    echo "$ php create_teacher.php\n\n";
}

// Vérifier l'existence de séances de cours assignées à cet enseignant
if ($teacher) {
    $seances = \App\Models\ESBTPSeanceCours::where('enseignant', $teacher->name)->get();
    
    echo "=== SÉANCES DE COURS ASSIGNÉES ===\n\n";
    
    if ($seances->count() > 0) {
        echo "✅ " . $seances->count() . " séance(s) de cours assignée(s) à cet enseignant.\n";
        echo "Première séance : \n";
        $premiereSeance = $seances->first();
        echo "- Jour : " . $premiereSeance->jour . "\n";
        echo "- Heure : " . $premiereSeance->plage_horaire . "\n";
        echo "- Matière ID : " . $premiereSeance->matiere_id . "\n";
        echo "- Classe ID : " . $premiereSeance->classe_id . "\n";
    } else {
        echo "❌ Aucune séance de cours n'est assignée à cet enseignant.\n";
        echo "Note: Sans séances de cours assignées, le tableau de bord de l'enseignant pourrait sembler vide.\n";
    }
}

echo "\n=== INSTRUCTIONS DE CONNEXION ===\n";
echo "Pour vous connecter en tant qu'enseignant :\n";
echo "1. Accédez à : http://localhost:8000/login\n";
echo "2. Saisissez les identifiants suivants :\n";
echo "   - Email : enseignant@test.com\n";
echo "   - Mot de passe : password123\n";
echo "3. Cliquez sur 'Se connecter'\n"; 