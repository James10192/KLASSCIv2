<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ESBTPAbidjanMatriculeConfigSeeder extends Seeder
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

        // Configuration pour BTS 1ère Année (code: 1A)
        $config1A = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => '1A',
            'niveau_etude_name' => 'Première Année BTS',
            'pattern' => '{GENRE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => null,
            'annee_format' => 2, // 2 chiffres (25, 26, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique BTS 1ère Année - Format: MESBTP25-0001, FESBTP25-0001',
            'exemple' => json_encode([
                'masculin' => 'MESBTP25-0001',
                'feminin' => 'FESBTP25-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour BTS 2ème Année (code: 2A)
        $config2A = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => '2A',
            'niveau_etude_name' => 'Deuxième Année BTS',
            'pattern' => '{GENRE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => null,
            'annee_format' => 2, // 2 chiffres (25, 26, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique BTS 2ème Année - Format: MESBTP25-0001, FESBTP25-0001',
            'exemple' => json_encode([
                'masculin' => 'MESBTP25-0001',
                'feminin' => 'FESBTP25-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Licence 1 (code: L1)
        $configL1 = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'L1',
            'niveau_etude_name' => 'Licence 1',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L',
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique Licence 1 - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Licence 2 (code: L2)
        $configL2 = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'L2',
            'niveau_etude_name' => 'Licence 2',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L',
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique Licence 2 - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Vérifier les configurations existantes pour éviter les doublons
        $existingConfigs = DB::table('esbtp_matricule_configs')
            ->where('etablissement_id', $etablissementAbidjan->id)
            ->pluck('niveau_etude_code')
            ->toArray();

        $this->command->info('Configurations existantes : ' . implode(', ', $existingConfigs));

        // Configuration pour L3 (Licence 3) - Même préfixe que LICENCE
        $configL3 = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'L3',
            'niveau_etude_name' => 'Licence 3',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L', // Même préfixe que LICENCE
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique L3 - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Master 1 - Même préfixe que Licence (L1, L2, L3, M1, M2)
        $configM1 = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'M1',
            'niveau_etude_name' => 'Master 1',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L', // Même préfixe pour L1, L2, L3, M1, M2
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique Master 1 - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Master 2 - Même préfixe que Licence (L1, L2, L3, M1, M2)
        $configM2 = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'M2',
            'niveau_etude_name' => 'Master 2',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L', // Même préfixe pour L1, L2, L3, M1, M2
            'annee_format' => 4, // 4 chiffres (2025, 2026, etc.)
            'numero_digits' => 4, // 4 chiffres (0001, 0002, etc.)
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique Master 2 - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Licence 3 Pro (code: L3Pro)
        $configL3Pro = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => 'L3Pro',
            'niveau_etude_name' => 'Licence 3 Pro',
            'pattern' => '{GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => 'L',
            'annee_format' => 4,
            'numero_digits' => 4,
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique Licence 3 Pro - Format: MLESBTP2025-0001, FLESBTP2025-0001',
            'exemple' => json_encode([
                'masculin' => 'MLESBTP2025-0001',
                'feminin' => 'FLESBTP2025-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Configuration pour Cinquième Année (code: 5A)
        $config5A = [
            'etablissement_id' => $etablissementAbidjan->id,
            'niveau_etude_code' => '5A',
            'niveau_etude_name' => 'Cinquième Année',
            'pattern' => '{GENRE}ESBTP{ANNEE}-{NUMERO}',
            'prefixe' => null,
            'annee_format' => 2,
            'numero_digits' => 4,
            'etablissement_code' => 'ESBTP',
            'is_active' => true,
            'description' => 'Configuration automatique 5ème Année - Format: MESBTP25-0001, FESBTP25-0001',
            'exemple' => json_encode([
                'masculin' => 'MESBTP25-0001',
                'feminin' => 'FESBTP25-0001'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Préparer les configurations à insérer (uniquement les manquantes)
        $configsToInsert = [];

        if (!in_array('1A', $existingConfigs)) {
            $configsToInsert[] = $config1A;
        }
        if (!in_array('2A', $existingConfigs)) {
            $configsToInsert[] = $config2A;
        }
        if (!in_array('L1', $existingConfigs)) {
            $configsToInsert[] = $configL1;
        }
        if (!in_array('L2', $existingConfigs)) {
            $configsToInsert[] = $configL2;
        }
        if (!in_array('L3', $existingConfigs)) {
            $configsToInsert[] = $configL3;
        }
        if (!in_array('M1', $existingConfigs)) {
            $configsToInsert[] = $configM1;
        }
        if (!in_array('M2', $existingConfigs)) {
            $configsToInsert[] = $configM2;
        }
        if (!in_array('L3Pro', $existingConfigs)) {
            $configsToInsert[] = $configL3Pro;
        }
        if (!in_array('5A', $existingConfigs)) {
            $configsToInsert[] = $config5A;
        }

        if (empty($configsToInsert)) {
            $this->command->info('✅ Toutes les configurations existent déjà !');
            return;
        }

        // Insérer les nouvelles configurations
        DB::table('esbtp_matricule_configs')->insert($configsToInsert);

        // Afficher les résultats
        $this->command->info('✅ Configurations matricules ESBTP-ABIDJAN mises à jour avec succès !');
        
        foreach ($configsToInsert as $config) {
            $exemples = json_decode($config['exemple'], true);
            $this->command->info("📋 {$config['niveau_etude_name']}: {$exemples['masculin']}, {$exemples['feminin']}");
        }

        $this->command->info('');
        $this->command->info('🎯 Configurations disponibles :');
        $this->command->info('   ➤ 1A (Première Année BTS) - 37 classes');
        $this->command->info('   ➤ 2A (Deuxième Année BTS) - 39 classes');
        $this->command->info('   ➤ 5A (Cinquième Année) - 1 classe');
        $this->command->info('   ➤ L1, L2, L3, L3Pro (Licence) - 16 classes');
        $this->command->info('   ➤ M1, M2 (Master) - 3 classes');
        $this->command->info('');
        $this->command->info('📝 Format matricules :');
        $this->command->info('   • Hommes BTS (1A/2A/5A) : MESBTP25-0001');
        $this->command->info('   • Femmes BTS (1A/2A/5A) : FESBTP25-0001');
        $this->command->info('   • Hommes L1/L2/L3/L3Pro/M1/M2 : MLESBTP2025-0001');
        $this->command->info('   • Femmes L1/L2/L3/L3Pro/M1/M2 : FLESBTP2025-0001');
    }
}
