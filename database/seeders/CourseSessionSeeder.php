<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourseSession;
use App\Models\ElementConstitutif;
use App\Models\Classroom;
use App\Models\User;
use Carbon\Carbon;

class CourseSessionSeeder extends Seeder
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
        $ecAlgoTD = ElementConstitutif::where('code', 'INFO-L1-S1-ALGO-TD')->first();
        $ecAlgoTP = ElementConstitutif::where('code', 'INFO-L1-S1-ALGO-TP')->first();
        $ecMathCM = ElementConstitutif::where('code', 'INFO-L1-S1-MATH-CM')->first();
        $ecMathTD = ElementConstitutif::where('code', 'INFO-L1-S1-MATH-TD')->first();
        $ecWebCM = ElementConstitutif::where('code', 'INFO-L1-S2-WEB-CM')->first();
        $ecWebTP = ElementConstitutif::where('code', 'INFO-L1-S2-WEB-TP')->first();

        // Récupérer quelques salles
        $amphiA = Classroom::where('code', 'AMPHI-A')->first();
        $amphiB = Classroom::where('code', 'AMPHI-B')->first();
        $td101 = Classroom::where('code', 'TD-101')->first();
        $td102 = Classroom::where('code', 'TD-102')->first();
        $labInfo1 = Classroom::where('code', 'LAB-INFO-1')->first();
        $labInfo2 = Classroom::where('code', 'LAB-INFO-2')->first();

        // Création des sessions de cours
        // Les sessions de cours sont comme des rendez-vous dans un agenda
        // Chacune représente un moment précis où un cours a lieu
        $courseSessions = [
            // Sessions pour le CM d'Algorithmique
            [
                'title' => 'CM Algorithmique - Introduction',
                'description' => 'Introduction aux concepts de base de l\'algorithmique',
                'start_time' => Carbon::parse('2023-09-04 08:00:00'),
                'end_time' => Carbon::parse('2023-09-04 10:00:00'),
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'CM Algorithmique - Structures de contrôle',
                'description' => 'Les structures conditionnelles et les boucles',
                'start_time' => Carbon::parse('2023-09-11 08:00:00'),
                'end_time' => Carbon::parse('2023-09-11 10:00:00'),
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'CM Algorithmique - Tableaux et fonctions',
                'description' => 'Manipulation des tableaux et création de fonctions',
                'start_time' => Carbon::parse('2023-09-18 08:00:00'),
                'end_time' => Carbon::parse('2023-09-18 10:00:00'),
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour les TD d'Algorithmique (2 groupes)
            [
                'title' => 'TD Algorithmique Groupe 1 - Exercices sur les structures de contrôle',
                'description' => 'Exercices d\'application sur les structures conditionnelles et les boucles',
                'start_time' => Carbon::parse('2023-09-05 10:00:00'),
                'end_time' => Carbon::parse('2023-09-05 12:00:00'),
                'element_constitutif_id' => $ecAlgoTD ? $ecAlgoTD->id : 2,
                'classroom_id' => $td101 ? $td101->id : 3,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TD Algorithmique Groupe 2 - Exercices sur les structures de contrôle',
                'description' => 'Exercices d\'application sur les structures conditionnelles et les boucles',
                'start_time' => Carbon::parse('2023-09-05 14:00:00'),
                'end_time' => Carbon::parse('2023-09-05 16:00:00'),
                'element_constitutif_id' => $ecAlgoTD ? $ecAlgoTD->id : 2,
                'classroom_id' => $td102 ? $td102->id : 4,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour les TP d'Algorithmique (2 groupes)
            [
                'title' => 'TP Algorithmique Groupe 1 - Implémentation en C',
                'description' => 'Mise en pratique des algorithmes en langage C',
                'start_time' => Carbon::parse('2023-09-06 08:00:00'),
                'end_time' => Carbon::parse('2023-09-06 10:00:00'),
                'element_constitutif_id' => $ecAlgoTP ? $ecAlgoTP->id : 3,
                'classroom_id' => $labInfo1 ? $labInfo1->id : 6,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP Algorithmique Groupe 2 - Implémentation en C',
                'description' => 'Mise en pratique des algorithmes en langage C',
                'start_time' => Carbon::parse('2023-09-06 10:00:00'),
                'end_time' => Carbon::parse('2023-09-06 12:00:00'),
                'element_constitutif_id' => $ecAlgoTP ? $ecAlgoTP->id : 3,
                'classroom_id' => $labInfo1 ? $labInfo1->id : 6,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour le CM de Mathématiques
            [
                'title' => 'CM Mathématiques - Logique et ensembles',
                'description' => 'Introduction à la logique mathématique et théorie des ensembles',
                'start_time' => Carbon::parse('2023-09-04 14:00:00'),
                'end_time' => Carbon::parse('2023-09-04 16:00:00'),
                'element_constitutif_id' => $ecMathCM ? $ecMathCM->id : 4,
                'classroom_id' => $amphiB ? $amphiB->id : 2,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'CM Mathématiques - Algèbre linéaire',
                'description' => 'Introduction aux concepts d\'algèbre linéaire',
                'start_time' => Carbon::parse('2023-09-11 14:00:00'),
                'end_time' => Carbon::parse('2023-09-11 16:00:00'),
                'element_constitutif_id' => $ecMathCM ? $ecMathCM->id : 4,
                'classroom_id' => $amphiB ? $amphiB->id : 2,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour les TD de Mathématiques
            [
                'title' => 'TD Mathématiques Groupe 1 - Exercices de logique',
                'description' => 'Exercices d\'application sur la logique mathématique',
                'start_time' => Carbon::parse('2023-09-07 08:00:00'),
                'end_time' => Carbon::parse('2023-09-07 10:00:00'),
                'element_constitutif_id' => $ecMathTD ? $ecMathTD->id : 5,
                'classroom_id' => $td101 ? $td101->id : 3,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TD Mathématiques Groupe 2 - Exercices de logique',
                'description' => 'Exercices d\'application sur la logique mathématique',
                'start_time' => Carbon::parse('2023-09-07 10:00:00'),
                'end_time' => Carbon::parse('2023-09-07 12:00:00'),
                'element_constitutif_id' => $ecMathTD ? $ecMathTD->id : 5,
                'classroom_id' => $td102 ? $td102->id : 4,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour le CM de Développement Web (semestre 2)
            [
                'title' => 'CM Web - Introduction au développement web',
                'description' => 'Introduction aux technologies du web: HTML, CSS, JavaScript',
                'start_time' => Carbon::parse('2024-01-08 08:00:00'),
                'end_time' => Carbon::parse('2024-01-08 10:00:00'),
                'element_constitutif_id' => $ecWebCM ? $ecWebCM->id : 15,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'CM Web - HTML et CSS',
                'description' => 'Approfondissement des langages HTML et CSS',
                'start_time' => Carbon::parse('2024-01-15 08:00:00'),
                'end_time' => Carbon::parse('2024-01-15 10:00:00'),
                'element_constitutif_id' => $ecWebCM ? $ecWebCM->id : 15,
                'classroom_id' => $amphiA ? $amphiA->id : 1,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Sessions pour les TP de Développement Web (semestre 2)
            [
                'title' => 'TP Web Groupe 1 - Création d\'une page HTML',
                'description' => 'Mise en pratique des concepts HTML et CSS',
                'start_time' => Carbon::parse('2024-01-10 08:00:00'),
                'end_time' => Carbon::parse('2024-01-10 10:00:00'),
                'element_constitutif_id' => $ecWebTP ? $ecWebTP->id : 16,
                'classroom_id' => $labInfo2 ? $labInfo2->id : 7,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP Web Groupe 2 - Création d\'une page HTML',
                'description' => 'Mise en pratique des concepts HTML et CSS',
                'start_time' => Carbon::parse('2024-01-10 10:00:00'),
                'end_time' => Carbon::parse('2024-01-10 12:00:00'),
                'element_constitutif_id' => $ecWebTP ? $ecWebTP->id : 16,
                'classroom_id' => $labInfo2 ? $labInfo2->id : 7,
                'status' => 'scheduled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($courseSessions as $session) {
            CourseSession::create($session);
        }

        $this->command->info('Sessions de cours créées avec succès!');
    }
}
