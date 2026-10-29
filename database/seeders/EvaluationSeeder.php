<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Evaluation;
use App\Models\ElementConstitutif;
use App\Models\Classroom;
use App\Models\User;
use Carbon\Carbon;

class EvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer un utilisateur administrateur pour l'attribution
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : null;

        // Récupérer quelques éléments constitutifs
        $ecAlgoCM = ElementConstitutif::where('code', 'INFO-L1-S1-ALGO-CM')->first();
        $ecAlgoTP = ElementConstitutif::where('code', 'INFO-L1-S1-ALGO-TP')->first();
        $ecMathCM = ElementConstitutif::where('code', 'INFO-L1-S1-MATH-CM')->first();
        $ecMathTD = ElementConstitutif::where('code', 'INFO-L1-S1-MATH-TD')->first();
        $ecBddCM = ElementConstitutif::where('code', 'INFO-L1-S2-BDD-CM')->first();
        $ecBddTP = ElementConstitutif::where('code', 'INFO-L1-S2-BDD-TP')->first();

        // Récupérer quelques salles
        $amphiA = Classroom::where('code', 'AMPHI-A')->first();
        $amphiB = Classroom::where('code', 'AMPHI-B')->first();
        $td101 = Classroom::where('code', 'TD-101')->first();
        $td102 = Classroom::where('code', 'TD-102')->first();
        $labInfo1 = Classroom::where('code', 'LAB-INFO-1')->first();

        // Création des évaluations
        // Les évaluations sont comme des tests de qualité
        // Elles permettent de vérifier si les connaissances ont bien été acquises
        $evaluations = [
            // Évaluations pour l'Algorithmique
            [
                'title' => 'Examen Partiel Algorithmique',
                'description' => 'Examen écrit sur les concepts de base de l\'algorithmique',
                'type' => 'Partiel',
                'date' => Carbon::parse('2023-10-16 08:00:00'),
                'duration' => 120, // en minutes
                'total_points' => 20,
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP Noté Algorithmique',
                'description' => 'Travail pratique noté sur l\'implémentation d\'algorithmes en C',
                'type' => 'TP Noté',
                'date' => Carbon::parse('2023-11-06 08:00:00'),
                'duration' => 120, // en minutes
                'total_points' => 20,
                'element_constitutif_id' => $ecAlgoTP ? $ecAlgoTP->id : 3,
                'classroom_id' => $labInfo1 ? $labInfo1->id : 6,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Examen Final Algorithmique',
                'description' => 'Examen final sur l\'ensemble du module d\'algorithmique',
                'type' => 'Final',
                'date' => Carbon::parse('2023-12-18 08:00:00'),
                'duration' => 180, // en minutes
                'total_points' => 40,
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Évaluations pour les Mathématiques
            [
                'title' => 'Contrôle Continu Mathématiques 1',
                'description' => 'Premier contrôle continu sur la logique mathématique',
                'type' => 'Contrôle Continu',
                'date' => Carbon::parse('2023-09-28 14:00:00'),
                'duration' => 60, // en minutes
                'total_points' => 10,
                'element_constitutif_id' => $ecMathTD ? $ecMathTD->id : 5,
                'classroom_id' => $td101 ? $td101->id : 3,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Contrôle Continu Mathématiques 2',
                'description' => 'Deuxième contrôle continu sur l\'algèbre linéaire',
                'type' => 'Contrôle Continu',
                'date' => Carbon::parse('2023-11-02 14:00:00'),
                'duration' => 60, // en minutes
                'total_points' => 10,
                'element_constitutif_id' => $ecMathTD ? $ecMathTD->id : 5,
                'classroom_id' => $td102 ? $td102->id : 4,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Examen Final Mathématiques',
                'description' => 'Examen final sur l\'ensemble du module de mathématiques',
                'type' => 'Final',
                'date' => Carbon::parse('2023-12-20 14:00:00'),
                'duration' => 180, // en minutes
                'total_points' => 40,
                'element_constitutif_id' => $ecMathCM ? $ecMathCM->id : 4,
                'classroom_id' => $amphiB ? $amphiB->id : 2,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Évaluations pour les Bases de données (semestre 2)
            [
                'title' => 'Examen Partiel Bases de données',
                'description' => 'Examen écrit sur la modélisation des bases de données',
                'type' => 'Partiel',
                'date' => Carbon::parse('2024-03-11 08:00:00'),
                'duration' => 120, // en minutes
                'total_points' => 20,
                'element_constitutif_id' => $ecBddCM ? $ecBddCM->id : 12,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP Noté SQL',
                'description' => 'Travail pratique noté sur les requêtes SQL',
                'type' => 'TP Noté',
                'date' => Carbon::parse('2024-04-08 08:00:00'),
                'duration' => 120, // en minutes
                'total_points' => 20,
                'element_constitutif_id' => $ecBddTP ? $ecBddTP->id : 14,
                'classroom_id' => $labInfo1 ? $labInfo1->id : 6,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Projet Bases de données',
                'description' => 'Projet de conception et implémentation d\'une base de données',
                'type' => 'Projet',
                'date' => Carbon::parse('2024-05-13 08:00:00'),
                'duration' => 0, // Pas de durée fixe pour un projet
                'total_points' => 30,
                'element_constitutif_id' => $ecBddCM ? $ecBddCM->id : 12,
                'classroom_id' => null, // Pas de salle spécifique pour un projet
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($evaluations as $evaluation) {
            Evaluation::create($evaluation);
        }

        $this->command->info('Évaluations créées avec succès!');
    }
}
