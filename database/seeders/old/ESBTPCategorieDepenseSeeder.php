<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPCategorieDepense;
use Illuminate\Support\Str;

class ESBTPCategorieDepenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define the expense categories to create
        $categories = [
            [
                'nom' => 'Salaires et charges sociales',
                'description' => 'Dépenses liées aux salaires, primes, indemnités et charges sociales du personnel',
                'code' => 'SALAIRES',
            ],
            [
                'nom' => 'Fournitures pédagogiques',
                'description' => 'Matériel pédagogique, manuels, supports de cours, etc.',
                'code' => 'PEDAGOG',
            ],
            [
                'nom' => 'Équipements informatiques',
                'description' => 'Ordinateurs, imprimantes, projecteurs, et autres matériels informatiques',
                'code' => 'EQUIP_INFO',
            ],
            [
                'nom' => 'Maintenance et réparations',
                'description' => 'Entretien des bâtiments, réparations diverses, maintenance des équipements',
                'code' => 'MAINT',
            ],
            [
                'nom' => 'Frais administratifs',
                'description' => 'Dépenses administratives générales',
                'code' => 'ADMIN',
            ],
            [
                'nom' => 'Factures d\'eau et électricité',
                'description' => 'Factures d\'eau, d\'électricité et autres services publics',
                'code' => 'UTIL',
            ],
            [
                'nom' => 'Services de nettoyage',
                'description' => 'Services de nettoyage et d\'entretien des locaux',
                'code' => 'NETTOYAGE',
            ],
            [
                'nom' => 'Sécurité',
                'description' => 'Services de sécurité, équipements de sécurité, etc.',
                'code' => 'SECURITE',
            ],
            [
                'nom' => 'Assurances',
                'description' => 'Assurances diverses (responsabilité civile, locaux, etc.)',
                'code' => 'ASSUR',
            ],
            [
                'nom' => 'Frais de communication',
                'description' => 'Téléphone, internet, frais postaux, etc.',
                'code' => 'COMM',
            ],
            [
                'nom' => 'Matériel de bureau',
                'description' => 'Fournitures de bureau, papeterie, etc.',
                'code' => 'BUREAU',
            ],
            [
                'nom' => 'Logiciels et licences',
                'description' => 'Logiciels, licences, abonnements à des services numériques',
                'code' => 'LOGICIEL',
            ],
            [
                'nom' => 'Frais de déplacement',
                'description' => 'Transports, missions, frais de déplacement du personnel',
                'code' => 'DEPLACE',
            ],
            [
                'nom' => 'Frais de formation du personnel',
                'description' => 'Formations, séminaires, conférences pour le personnel',
                'code' => 'FORMATION',
            ],
        ];

        // Create each category if it doesn't already exist
        foreach ($categories as $categoryData) {
            ESBTPCategorieDepense::firstOrCreate(
                ['code' => $categoryData['code']],
                [
                    'nom' => $categoryData['nom'],
                    'description' => $categoryData['description'],
                    'est_actif' => true,
                ]
            );
        }

        $this->command->info('Catégories de dépenses créées avec succès!');
    }
} 