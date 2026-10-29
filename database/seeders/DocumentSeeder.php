<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\ElementConstitutif;
use App\Models\User;

class DocumentSeeder extends Seeder
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

        // Création des documents
        // Les documents sont comme des livres dans une bibliothèque
        // Chacun contient des connaissances précieuses pour les étudiants
        $documents = [
            // Documents pour le CM d'Algorithmique
            [
                'title' => 'Cours 1 - Introduction à l\'algorithmique',
                'description' => 'Support de cours sur les concepts de base de l\'algorithmique',
                'type' => 'Cours',
                'file_path' => 'documents/algo/cours_intro_algo.pdf',
                'file_size' => 1024, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Cours 2 - Structures de contrôle',
                'description' => 'Support de cours sur les structures conditionnelles et les boucles',
                'type' => 'Cours',
                'file_path' => 'documents/algo/cours_structures_controle.pdf',
                'file_size' => 1536, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Cours 3 - Tableaux et fonctions',
                'description' => 'Support de cours sur les tableaux et les fonctions',
                'type' => 'Cours',
                'file_path' => 'documents/algo/cours_tableaux_fonctions.pdf',
                'file_size' => 1792, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoCM ? $ecAlgoCM->id : 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour les TD d'Algorithmique
            [
                'title' => 'TD1 - Exercices sur les structures de contrôle',
                'description' => 'Série d\'exercices sur les structures conditionnelles et les boucles',
                'type' => 'TD',
                'file_path' => 'documents/algo/td1_structures_controle.pdf',
                'file_size' => 512, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoTD ? $ecAlgoTD->id : 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TD2 - Exercices sur les tableaux',
                'description' => 'Série d\'exercices sur la manipulation des tableaux',
                'type' => 'TD',
                'file_path' => 'documents/algo/td2_tableaux.pdf',
                'file_size' => 640, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoTD ? $ecAlgoTD->id : 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour les TP d'Algorithmique
            [
                'title' => 'TP1 - Prise en main de l\'environnement C',
                'description' => 'Guide de prise en main de l\'environnement de développement C',
                'type' => 'TP',
                'file_path' => 'documents/algo/tp1_environnement_c.pdf',
                'file_size' => 768, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoTP ? $ecAlgoTP->id : 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP2 - Implémentation d\'algorithmes de tri',
                'description' => 'Travaux pratiques sur l\'implémentation d\'algorithmes de tri en C',
                'type' => 'TP',
                'file_path' => 'documents/algo/tp2_tri.pdf',
                'file_size' => 896, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecAlgoTP ? $ecAlgoTP->id : 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour le CM de Mathématiques
            [
                'title' => 'Cours 1 - Logique et ensembles',
                'description' => 'Support de cours sur la logique mathématique et la théorie des ensembles',
                'type' => 'Cours',
                'file_path' => 'documents/math/cours_logique_ensembles.pdf',
                'file_size' => 1280, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecMathCM ? $ecMathCM->id : 4,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Cours 2 - Algèbre linéaire',
                'description' => 'Support de cours sur les concepts d\'algèbre linéaire',
                'type' => 'Cours',
                'file_path' => 'documents/math/cours_algebre_lineaire.pdf',
                'file_size' => 1664, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecMathCM ? $ecMathCM->id : 4,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour les TD de Mathématiques
            [
                'title' => 'TD1 - Exercices de logique',
                'description' => 'Série d\'exercices sur la logique mathématique',
                'type' => 'TD',
                'file_path' => 'documents/math/td1_logique.pdf',
                'file_size' => 576, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecMathTD ? $ecMathTD->id : 5,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour le CM de Développement Web
            [
                'title' => 'Cours 1 - Introduction au développement web',
                'description' => 'Support de cours sur les technologies du web',
                'type' => 'Cours',
                'file_path' => 'documents/web/cours_intro_web.pdf',
                'file_size' => 1408, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecWebCM ? $ecWebCM->id : 15,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'Cours 2 - HTML et CSS',
                'description' => 'Support de cours sur les langages HTML et CSS',
                'type' => 'Cours',
                'file_path' => 'documents/web/cours_html_css.pdf',
                'file_size' => 1920, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecWebCM ? $ecWebCM->id : 15,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Documents pour les TP de Développement Web
            [
                'title' => 'TP1 - Création d\'une page HTML',
                'description' => 'Travaux pratiques sur la création d\'une page HTML simple',
                'type' => 'TP',
                'file_path' => 'documents/web/tp1_html.pdf',
                'file_size' => 704, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecWebTP ? $ecWebTP->id : 16,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP2 - Mise en forme avec CSS',
                'description' => 'Travaux pratiques sur la mise en forme d\'une page web avec CSS',
                'type' => 'TP',
                'file_path' => 'documents/web/tp2_css.pdf',
                'file_size' => 832, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecWebTP ? $ecWebTP->id : 16,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'title' => 'TP3 - Introduction à JavaScript',
                'description' => 'Travaux pratiques sur les bases de JavaScript',
                'type' => 'TP',
                'file_path' => 'documents/web/tp3_javascript.pdf',
                'file_size' => 960, // en KB
                'mime_type' => 'application/pdf',
                'is_public' => true,
                'element_constitutif_id' => $ecWebTP ? $ecWebTP->id : 16,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($documents as $document) {
            Document::create($document);
        }

        $this->command->info('Documents créés avec succès!');
    }
}
