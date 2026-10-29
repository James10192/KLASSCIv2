<?php
// Script to directly create test users for different roles

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Create roles if they don't exist
$roles = ['superAdmin', 'secretaire', 'etudiant', 'teacher'];

foreach ($roles as $roleName) {
    if (!Role::where('name', $roleName)->exists()) {
        Role::create(['name' => $roleName]);
        echo "Created role: {$roleName}\n";
    } else {
        echo "Role already exists: {$roleName}\n";
    }
}

// Function to create a user
function createUser($name, $firstName, $lastName, $email, $password, $roleName) {
    $existingUser = App\Models\User::where('email', $email)->first();
    
    if (!$existingUser) {
        echo "Creating user: $firstName $lastName ($email) with role: $roleName\n";
        
        $user = new App\Models\User();
        $user->name = $name;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->username = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName));
        $user->email_verified_at = now();
        $user->save();
    } else {
        echo "User already exists: $email, updating...\n";
        
        $existingUser->name = $name;
        $existingUser->first_name = $firstName;
        $existingUser->last_name = $lastName;
        $existingUser->password = Hash::make($password);
        $existingUser->username = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName));
        $existingUser->save();
        
        $user = $existingUser;
    }
    
    // Assign role
    if (!$user->hasRole($roleName)) {
        $user->assignRole($roleName);
        echo "Assigned role: $roleName to user: $email\n";
    } else {
        echo "User already has role: $roleName\n";
    }
    
    // If student, create a student profile if it doesn't exist
    if ($roleName === 'etudiant') {
        $student = App\Models\Student::where('user_id', $user->id)->first();
        if (!$student) {
            echo "Creating student profile for: $email\n";
            
            // Get the first class for demonstration
            $class = App\Models\Classe::first();
            
            if ($class) {
                $student = new App\Models\Student();
                $student->matricule = 'STD' . rand(1000, 9999);
                $student->name = $user->name;
                $student->date_birth = '2000-01-01';
                $student->user_id = $user->id;
                $student->classe_id = $class->id;
                $student->save();
                
                echo "Created student profile with matricule: " . $student->matricule . "\n";
            } else {
                echo "Warning: No classes found to assign to student\n";
            }
        }
    }
    
    return $user;
}

// Create SuperAdmin user
$superAdmin = createUser('Super Admin', 'Super', 'Admin', 'superadmin@esbtp.ci', 'Admin@123', 'superAdmin');

// Create Secretary user
$secretaire = createUser('Secretaire Test', 'Secretaire', '', 'secretaire@esbtp.ci', 'Secret@123', 'secretaire');

// Create Student user
$etudiant = createUser('Etudiant Test', 'Etudiant', '', 'etudiant@esbtp.ci', 'Etudiant@123', 'etudiant');

// Create Teacher user
$teacher = createUser('Enseignant Test', 'Enseignant', '', 'enseignant@esbtp.ci', 'Enseignant@123', 'teacher');

// Create student profile for the student user
if ($etudiant) {
    $etudiantProfile = ESBTPEtudiant::where('user_id', $etudiant->id)->first();
    
    if (!$etudiantProfile) {
        try {
            // Try to find a class for the student
            $classe = ESBTPClasse::first();
            
            $etudiantProfile = new ESBTPEtudiant();
            $etudiantProfile->user_id = $etudiant->id;
            $etudiantProfile->matricule = 'ETU' . date('Y') . rand(100, 999);
            $etudiantProfile->nom = 'Test';
            $etudiantProfile->prenoms = 'Etudiant';
            $etudiantProfile->date_naissance = '2000-01-01';
            $etudiantProfile->lieu_naissance = 'Abidjan';
            $etudiantProfile->sexe = 'M';
            $etudiantProfile->adresse = 'Cocody';
            $etudiantProfile->telephone = '0700000000';
            $etudiantProfile->email = 'etudiant@esbtp.ci';
            $etudiantProfile->classe_id = $classe ? $classe->id : null;
            $etudiantProfile->created_by = $superAdmin->id;
            $etudiantProfile->save();
            
            echo "Created student profile for etudiant@esbtp.ci\n";
        } catch (Exception $e) {
            echo "ERROR creating student profile: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Student profile already exists for etudiant@esbtp.ci\n";
    }
}

echo "\n========= LOGIN CREDENTIALS =========\n";
echo "SuperAdmin: superadmin@esbtp.ci / Admin@123\n";
echo "Secretary: secretaire@esbtp.ci / Secret@123\n";
echo "Student: etudiant@esbtp.ci / Etudiant@123\n";
echo "Teacher: enseignant@esbtp.ci / Enseignant@123\n";
echo "======================================\n"; 