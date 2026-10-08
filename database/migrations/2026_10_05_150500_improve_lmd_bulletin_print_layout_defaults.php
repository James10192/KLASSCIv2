<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Le gabarit LMD a désormais assez de souplesse pour utiliser une
        // typographie réellement lisible et, si nécessaire, s'étaler sur deux
        // pages. On ne relève QUE les tenants restés sur les valeurs d'origine :
        // toute charte déjà personnalisée par une école est conservée.
        $fontDefaults = [
            'lmd_bulletin_font_republic' => ['8.5', '9'],
            'lmd_bulletin_font_school_name' => ['13', '15'],
            'lmd_bulletin_font_school_meta' => ['7.5', '8.5'],
            'lmd_bulletin_font_title' => ['12', '14'],
            'lmd_bulletin_font_header_meta' => ['8', '9'],
            'lmd_bulletin_font_establishment' => ['8.5', '9.5'],
            'lmd_bulletin_font_student' => ['9.5', '10.5'],
            'lmd_bulletin_font_structure' => ['9', '10'],
            'lmd_bulletin_font_table_header' => ['8', '9'],
            'lmd_bulletin_font_table' => ['8.5', '9.5'],
            'lmd_bulletin_font_teacher' => ['7.5', '8.5'],
            'lmd_bulletin_font_summary' => ['12', '13'],
            'lmd_bulletin_font_decision' => ['10', '10.5'],
            'lmd_bulletin_font_notice' => ['8', '8.5'],
            'lmd_bulletin_font_signature' => ['9', '10'],
            'lmd_bulletin_font_legend' => ['7.5', '8'],
            'lmd_bulletin_font_bottom' => ['8', '8.5'],
        ];

        $sortOrder = 330;
        foreach ($fontDefaults as $key => [$oldDefault, $newDefault]) {
            $existing = DB::table('settings')->where('key', $key)->first();
            $fontValidationRules = json_encode(['nullable', 'numeric', 'min:6', 'max:32']);

            if (! $existing) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $newDefault,
                    'type' => 'float',
                    'group' => 'bulletin',
                    'category' => 'bulletin',
                    'description' => 'Typographie du bulletin LMD',
                    'is_required' => false,
                    'default_value' => $newDefault,
                    'validation_rules' => $fontValidationRules,
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            $updates = [
                'default_value' => $newDefault,
                'validation_rules' => $fontValidationRules,
                'updated_at' => now(),
            ];

            if ((string) $existing->value === $oldDefault) {
                $updates['value'] = $newDefault;
            }

            DB::table('settings')->where('key', $key)->update($updates);
            $sortOrder++;
        }

        // Certaines bases historiques ont reçu l'écran LMD avant la migration
        // qui semait cette clé. Sans ligne en base, le champ Direction visible
        // dans les paramètres ne pouvait pas être persisté par la sauvegarde
        // générique. On garantit donc ici son existence.
        if (! DB::table('settings')->where('key', 'lmd_bulletin_direction')->exists()) {
            DB::table('settings')->insert([
                'key' => 'lmd_bulletin_direction',
                'value' => '',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Direction affichée dans le bandeau du bulletin LMD',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'string', 'max:160']),
                'is_active' => true,
                'sort_order' => 329,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Les valeurs de typographie peuvent avoir été modifiées après la
        // migration ; un rollback ne doit jamais réécrire la charte d'un tenant.
    }
};
