<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatriculeConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'ID de l'établissement ESBTP-ABIDJAN
        $etablissementAbidjan = DB::table('esbtp_etablissements')
            ->where('code', 'ESBTP-ABIDJAN')
            ->first();

        if (!$etablissementAbidjan) {
            $this->command->error('Établissement ESBTP-ABIDJAN non trouvé !');
            return;
        }

        // Supprimer les configurations existantes pour cet établissement
        DB::table('esbtp_matricule_configs')
            ->where('etablissement_id', $etablissementAbidjan->id)
            ->delete();

        // Configuration pour BTS
        $configBTS = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'BTS',
            'niveau_etude_name' => 'BTS (Brevet de Technicien Supérieur)',
            'pattern' => '{GENRE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => null,
            'annee_format' => 2, // 2 chiffres (25, 26, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique BTS - Format: MESBTP25-0001, FESBTP25-0001',
            'exemple' => json_encode([
                'masculin' => 'MESBTP25-0001',
                'feminin' => 'FESBTP25-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour LICENCE
        $configLICENCE = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'LICENCE',
            'niveau_etude_name' => 'LICENCE',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L',
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique LICENCE - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Insérer les configurations
        DB::table('esbtp_matricule_configs')->insert([$configBTS, $configLICENCE]);

        $this->command->info('✅ Configurations matricules créées avec succès !');
        $this->command->info('📋 BTS: MESBTP25-0001, FESBTP25-0001');
        $this->command->info('📋 LICENCE: MLESBTP2025-0001, FLESBTP2025-0001');
    }
}
