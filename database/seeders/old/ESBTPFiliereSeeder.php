<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPFiliere;

class ESBTPFiliereSeeder extends Seeder
{
    public function run()
    {
        $filieres = [
            [
                'name' => 'BTS1 Tronc commun',
                'description' => 'BTS première année Tronc Commun',
                'code' => 'BTS1-TC',
            ],
            [
                'name' => 'BTS1 BATIMENT',
                'description' => 'BTS première année Bâtiment',
                'code' => 'BTS1-BAT',
            ],
            [
                'name' => 'BTS1 GTP',
                'description' => 'BTS première année Génie civil option TRAVAUX PUBLICS',
                'code' => 'BTS1-GTP',
            ],
            [
                'name' => 'BTS1 GGT',
                'description' => 'BTS première année Génie civil option GEOMETRE-TOPOGRAPHE',
                'code' => 'BTS1-GGT',
            ],
            [
                'name' => 'BTS1 MGP',
                'description' => 'BTS première année MINE - GEOLOGIE - PETROLE',
                'code' => 'BTS1-MGP',
            ],
            [
                'name' => 'BTS1 URBANISME',
                'description' => 'BTS première année Génie civil option URBANISME',
                'code' => 'BTS1-URB',
            ],
            [
                'name' => 'BTS2 Tronc commun',
                'description' => 'BTS deuxième année Tronc Commun',
                'code' => 'BTS2-TC',
            ],
            [
                'name' => 'BTS2 BAT',
                'description' => 'BTS deuxième année Bâtiment',
                'code' => 'BTS2-BAT',
            ],
            [
                'name' => 'BTS2 GTP',
                'description' => 'BTS deuxième année Génie des Travaux Publics',
                'code' => 'BTS2-GTP',
            ],
            [
                'name' => 'BTS2 GGT',
                'description' => 'BTS deuxième année Génie civil option GEOMETRE-TOPOGRAPHE',
                'code' => 'BTS2-GGT',
            ],
            [
                'name' => 'BTS2 MGP',
                'description' => 'BTS deuxième année MINE - GEOLOGIE - PETROLE',
                'code' => 'BTS2-MGP',
            ],
            [
                'name' => 'BTS2 URBANISME',
                'description' => 'BTS deuxième année Génie civil option URBANISME',
                'code' => 'BTS2-URB',
            ],
        ];

        foreach ($filieres as $filiere) {
            ESBTPFiliere::create($filiere);
        }
    }
}
