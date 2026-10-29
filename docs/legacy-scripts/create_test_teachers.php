<?php

/**
 * Script pour créer 2 enseignants de test
 * Usage: php create_test_teachers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPDepartment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    echo "Création de 2 enseignants de test...\n\n";

    // Vérifier qu'il y a un département
    $department = ESBTPDepartment::first();
    if (!$department) {
        echo "Création d'un département de test...\n";
        $department = ESBTPDepartment::create([
            'name' => 'Département Général',
            'code' => 'DEPT-GEN',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1
        ]);
    }

    echo "Département utilisé: {$department->name}\n\n";

    // Enseignant 1: Prof. KOUASSI Jean
    echo "1. Création de Prof. KOUASSI Jean...\n";

    $user1 = User::create([
        'name' => 'KOUASSI Jean',
        'email' => 'kouassi.jean@esbtp.ci',
        'username' => 'kouassi.jean',
        'password' => Hash::make('password123'),
        'phone' => '+225 07 00 00 01',
    ]);

    $user1->assignRole('enseignant');

    $teacher1 = ESBTPTeacher::create([
        'user_id' => $user1->id,
        'matricule' => 'ENS' . str_pad($user1->id, 4, '0', STR_PAD_LEFT),
        'title' => 'Dr.',
        'specialization' => 'Mathématiques et Physique',
        'department_id' => $department->id,
        'grade' => 'Professeur',
        'status' => 'permanent',
        'is_active' => true,
        'type_contrat' => 'permanent',
        'statut_emploi' => 'temps_plein',
        'date_embauche' => now()->subYears(5),
        'taux_horaire' => 5000,
        'charge_horaire_max_semaine' => 20,
        'bio' => 'Enseignant expérimenté en mathématiques et physique avec 15 ans d\'expérience.',
        'created_by' => 1,
        'updated_by' => 1
    ]);

    echo "   ✓ Créé: ID={$teacher1->id}, Matricule={$teacher1->matricule}, Email={$user1->email}\n";
    echo "   → Mot de passe: password123\n\n";

    // Enseignant 2: Prof. BAMBA Marie
    echo "2. Création de Prof. BAMBA Marie...\n";

    $user2 = User::create([
        'name' => 'BAMBA Marie',
        'email' => 'bamba.marie@esbtp.ci',
        'username' => 'bamba.marie',
        'password' => Hash::make('password123'),
        'phone' => '+225 07 00 00 02',
    ]);

    $user2->assignRole('enseignant');

    $teacher2 = ESBTPTeacher::create([
        'user_id' => $user2->id,
        'matricule' => 'ENS' . str_pad($user2->id, 4, '0', STR_PAD_LEFT),
        'title' => 'Mme',
        'specialization' => 'Génie Civil et Construction',
        'department_id' => $department->id,
        'grade' => 'Maître de Conférences',
        'status' => 'permanent',
        'is_active' => true,
        'type_contrat' => 'permanent',
        'statut_emploi' => 'temps_plein',
        'date_embauche' => now()->subYears(3),
        'taux_horaire' => 4500,
        'charge_horaire_max_semaine' => 18,
        'bio' => 'Spécialiste en génie civil avec une expertise en structures et matériaux de construction.',
        'created_by' => 1,
        'updated_by' => 1
    ]);

    echo "   ✓ Créé: ID={$teacher2->id}, Matricule={$teacher2->matricule}, Email={$user2->email}\n";
    echo "   → Mot de passe: password123\n\n";

    DB::commit();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ SUCCÈS: 2 enseignants créés avec succès!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "Informations de connexion:\n";
    echo "  1. Email: kouassi.jean@esbtp.ci | Mot de passe: password123\n";
    echo "  2. Email: bamba.marie@esbtp.ci  | Mot de passe: password123\n\n";

    echo "Vous pouvez maintenant tester la sélection multiple d'enseignants\n";
    echo "dans la page: http://localhost:8000/esbtp/planning-general\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
