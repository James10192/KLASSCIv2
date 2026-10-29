<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ElementConstitutif;
use App\Models\UniteEnseignement;
use App\Models\User;
use App\Models\Teacher;

class ElementConstitutifSeeder extends Seeder
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

        // Récupérer un enseignant pour l'attribution
        $teacher = Teacher::first();
        $teacherId = $teacher ? $teacher->id : null;

        // Récupérer les unités d'enseignement
        $ueAlgo1 = UniteEnseignement::where('code', 'INFO-L1-S1-ALGO')->first();
        $ueMath = UniteEnseignement::where('code', 'INFO-L1-S1-MATH')->first();
        $ueArchi = UniteEnseignement::where('code', 'INFO-L1-S1-ARCHI')->first();
        $ueLang = UniteEnseignement::where('code', 'INFO-L1-S1-LANG')->first();
        $ueAlgo2 = UniteEnseignement::where('code', 'INFO-L1-S2-ALGO')->first();
        $ueBdd = UniteEnseignement::where('code', 'INFO-L1-S2-BDD')->first();
        $ueWeb = UniteEnseignement::where('code', 'INFO-L1-S2-WEB')->first();

        // Création des éléments constitutifs
        // Les EC sont comme les ingrédients d'une recette
        // Chacun apporte une saveur particulière à l'UE
        $elements = [
            // EC pour l'UE Algorithmique et Programmation 1
            [
                'code' => 'INFO-L1-S1-ALGO-CM',
                'name' => 'Cours Magistral Algorithmique',
                'description' => 'Cours théorique sur les bases de l\'algorithmique',
                'hours' => 24,
                'coefficient' => 0.5,
                'type' => 'CM', // Cours Magistral
                'unite_enseignement_id' => $ueAlgo1 ? $ueAlgo1->id : 1,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S1-ALGO-TD',
                'name' => 'Travaux Dirigés Algorithmique',
                'description' => 'Exercices d\'application des concepts algorithmiques',
                'hours' => 24,
                'coefficient' => 0.3,
                'type' => 'TD', // Travaux Dirigés
                'unite_enseignement_id' => $ueAlgo1 ? $ueAlgo1->id : 1,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S1-ALGO-TP',
                'name' => 'Travaux Pratiques Programmation',
                'description' => 'Mise en pratique des algorithmes en langage C',
                'hours' => 24,
                'coefficient' => 0.2,
                'type' => 'TP', // Travaux Pratiques
                'unite_enseignement_id' => $ueAlgo1 ? $ueAlgo1->id : 1,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Mathématiques pour l'informatique
            [
                'code' => 'INFO-L1-S1-MATH-CM',
                'name' => 'Cours Magistral Mathématiques',
                'description' => 'Cours théorique sur les bases mathématiques pour l\'informatique',
                'hours' => 24,
                'coefficient' => 0.6,
                'type' => 'CM',
                'unite_enseignement_id' => $ueMath ? $ueMath->id : 2,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S1-MATH-TD',
                'name' => 'Travaux Dirigés Mathématiques',
                'description' => 'Exercices d\'application des concepts mathématiques',
                'hours' => 24,
                'coefficient' => 0.4,
                'type' => 'TD',
                'unite_enseignement_id' => $ueMath ? $ueMath->id : 2,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Architecture des ordinateurs
            [
                'code' => 'INFO-L1-S1-ARCHI-CM',
                'name' => 'Cours Magistral Architecture',
                'description' => 'Cours théorique sur l\'architecture des ordinateurs',
                'hours' => 18,
                'coefficient' => 0.5,
                'type' => 'CM',
                'unite_enseignement_id' => $ueArchi ? $ueArchi->id : 3,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S1-ARCHI-TP',
                'name' => 'Travaux Pratiques Architecture',
                'description' => 'Travaux pratiques sur l\'assemblage et la configuration matérielle',
                'hours' => 18,
                'coefficient' => 0.5,
                'type' => 'TP',
                'unite_enseignement_id' => $ueArchi ? $ueArchi->id : 3,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Langues et Communication
            [
                'code' => 'INFO-L1-S1-LANG-ANG',
                'name' => 'Anglais Technique',
                'description' => 'Cours d\'anglais orienté informatique et technique',
                'hours' => 24,
                'coefficient' => 0.5,
                'type' => 'TD',
                'unite_enseignement_id' => $ueLang ? $ueLang->id : 4,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S1-LANG-COM',
                'name' => 'Techniques de Communication',
                'description' => 'Cours sur les techniques de communication écrite et orale',
                'hours' => 18,
                'coefficient' => 0.5,
                'type' => 'TD',
                'unite_enseignement_id' => $ueLang ? $ueLang->id : 4,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Algorithmique et Programmation 2
            [
                'code' => 'INFO-L1-S2-ALGO-CM',
                'name' => 'Cours Magistral Structures de Données',
                'description' => 'Cours théorique sur les structures de données avancées',
                'hours' => 24,
                'coefficient' => 0.5,
                'type' => 'CM',
                'unite_enseignement_id' => $ueAlgo2 ? $ueAlgo2->id : 5,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S2-ALGO-TP',
                'name' => 'Travaux Pratiques Programmation Avancée',
                'description' => 'Mise en pratique des structures de données en langage C++',
                'hours' => 36,
                'coefficient' => 0.5,
                'type' => 'TP',
                'unite_enseignement_id' => $ueAlgo2 ? $ueAlgo2->id : 5,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Bases de données
            [
                'code' => 'INFO-L1-S2-BDD-CM',
                'name' => 'Cours Magistral Bases de Données',
                'description' => 'Cours théorique sur les bases de données relationnelles',
                'hours' => 24,
                'coefficient' => 0.4,
                'type' => 'CM',
                'unite_enseignement_id' => $ueBdd ? $ueBdd->id : 6,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S2-BDD-TD',
                'name' => 'Travaux Dirigés Modélisation',
                'description' => 'Exercices de modélisation de bases de données',
                'hours' => 18,
                'coefficient' => 0.3,
                'type' => 'TD',
                'unite_enseignement_id' => $ueBdd ? $ueBdd->id : 6,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S2-BDD-TP',
                'name' => 'Travaux Pratiques SQL',
                'description' => 'Mise en pratique du langage SQL',
                'hours' => 18,
                'coefficient' => 0.3,
                'type' => 'TP',
                'unite_enseignement_id' => $ueBdd ? $ueBdd->id : 6,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // EC pour l'UE Développement Web
            [
                'code' => 'INFO-L1-S2-WEB-CM',
                'name' => 'Cours Magistral Web',
                'description' => 'Cours théorique sur les technologies web',
                'hours' => 12,
                'coefficient' => 0.3,
                'type' => 'CM',
                'unite_enseignement_id' => $ueWeb ? $ueWeb->id : 7,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'INFO-L1-S2-WEB-TP',
                'name' => 'Travaux Pratiques Web',
                'description' => 'Mise en pratique des technologies web: HTML, CSS, JavaScript',
                'hours' => 24,
                'coefficient' => 0.7,
                'type' => 'TP',
                'unite_enseignement_id' => $ueWeb ? $ueWeb->id : 7,
                'teacher_id' => $teacherId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($elements as $element) {
            ElementConstitutif::create($element);
        }

        $this->command->info('Éléments constitutifs créés avec succès!');
    }
}
