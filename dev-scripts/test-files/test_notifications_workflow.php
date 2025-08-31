#!/usr/bin/env php
<?php

/**
 * Script de test pour le workflow de notifications d'émargement
 * 
 * Ce script simule les différentes étapes du workflow d'émargement
 * et vérifie que les notifications sont bien envoyées.
 */

// Autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\NotificationService;
use App\Models\User;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPEtudiant;
use Illuminate\Support\Facades\DB;

echo "=== TEST DU WORKFLOW DE NOTIFICATIONS D'ÉMARGEMENT ===\n\n";

try {
    $notificationService = app(NotificationService::class);
    
    // 1. Trouver un enseignant test
    $teacherUser = User::role(['teacher', 'enseignant'])->first();
    
    if (!$teacherUser) {
        echo "❌ Aucun enseignant trouvé dans la base de données\n";
        exit(1);
    }
    
    echo "✅ Enseignant test trouvé: {$teacherUser->name}\n";
    
    // 2. Trouver une séance de cours test
    $seanceCours = ESBTPSeanceCours::with(['matiere', 'classe', 'emploiTemps'])
        ->where('teacher_id', $teacherUser->id)
        ->first();
    
    if (!$seanceCours) {
        // Créer une séance de test si elle n'existe pas
        $seanceCours = new ESBTPSeanceCours();
        $seanceCours->id = 9999;
        $seanceCours->teacher_id = $teacherUser->id;
        $seanceCours->matiere_id = 1;
        $seanceCours->heure_debut = '08:00:00';
        $seanceCours->heure_fin = '10:00:00';
        
        // Mock des relations
        $seanceCours->setRelation('matiere', (object)['name' => 'Test Matière']);
        $seanceCours->setRelation('classe', (object)['name' => 'Test Classe']);
        $seanceCours->setRelation('emploiTemps', (object)[
            'classe' => (object)['name' => 'Test Classe']
        ]);
    }
    
    echo "✅ Séance de cours test prête\n";
    
    // 3. Trouver des étudiants test
    $etudiants = ESBTPEtudiant::with('user')->take(3)->get();
    
    if ($etudiants->isEmpty()) {
        echo "⚠️ Aucun étudiant trouvé, simulation sans étudiants\n";
    } else {
        echo "✅ {$etudiants->count()} étudiants test trouvés\n";
    }
    
    echo "\n=== TEST 1: ÉMARGEMENT ENSEIGNANT ===\n";
    
    $notificationService->notifyCoordinateurTeacherAttendanceSigned($teacherUser, $seanceCours);
    echo "✅ Notification d'émargement enseignant envoyée\n";
    
    echo "\n=== TEST 2: APPEL DES ÉTUDIANTS ===\n";
    
    $attendanceData = [
        1 => 'present',
        2 => 'absent',
        3 => 'present'
    ];
    
    $notificationService->notifyCoordinateurStudentRollCallCompleted($teacherUser, $seanceCours, $attendanceData);
    echo "✅ Notification d'appel des étudiants envoyée\n";
    
    // Notifier les étudiants absents
    if (!$etudiants->isEmpty()) {
        $absentStudents = $etudiants->take(1); // Simuler un étudiant absent
        $notificationService->notifyStudentsAbsence($absentStudents, $seanceCours, $teacherUser);
        echo "✅ Notification d'absence envoyée aux étudiants\n";
    }
    
    echo "\n=== TEST 3: CLÔTURE DE COURS ===\n";
    
    $notificationService->notifyCoordinateurCourseClosed($teacherUser, $seanceCours, 'Notes de test pour la clôture du cours');
    echo "✅ Notification de clôture de cours envoyée\n";
    
    echo "\n=== TEST 4: RETARD D'ÉMARGEMENT ===\n";
    
    $notificationService->notifyCoordinateurTeacherAttendanceDelay($seanceCours, 15);
    echo "✅ Notification de retard d'émargement envoyée\n";
    
    echo "\n=== TEST 5: RÉCAPITULATIF QUOTIDIEN ===\n";
    
    $notificationService->sendDailyAttendanceSummaryToCoordinators();
    echo "✅ Récapitulatif quotidien envoyé\n";
    
    echo "\n=== VÉRIFICATION DES NOTIFICATIONS CRÉÉES ===\n";
    
    // Compter les notifications créées pour les coordinateurs
    $coordinateurCount = User::role(['coordinateur'])->count();
    $notificationsCount = DB::table('custom_notifications')
        ->whereIn('user_id', function($query) {
            $query->select('users.id')
                  ->from('users')
                  ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                  ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                  ->where('roles.name', 'coordinateur');
        })
        ->where('created_at', '>=', now()->subMinutes(5))
        ->count();
    
    echo "📊 {$coordinateurCount} coordinateur(s) dans le système\n";
    echo "📊 {$notificationsCount} notifications créées dans les 5 dernières minutes\n";
    
    // Afficher quelques notifications récentes
    $recentNotifications = DB::table('custom_notifications')
        ->where('created_at', '>=', now()->subMinutes(5))
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get(['title', 'type', 'created_at']);
    
    if ($recentNotifications->isNotEmpty()) {
        echo "\n📋 Notifications récentes créées:\n";
        foreach ($recentNotifications as $notification) {
            echo "   - [{$notification->type}] {$notification->title} ({$notification->created_at})\n";
        }
    }
    
    echo "\n✅ TESTS TERMINÉS AVEC SUCCÈS !\n";
    echo "\n💡 Consultez l'interface des notifications en tant que coordinateur pour voir les résultats.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERREUR LORS DES TESTS: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== FIN DES TESTS ===\n";