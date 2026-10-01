<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Des réglages lus comme des décimaux (coefficients de semestre, seuils et
 * mentions LMD) étaient enregistrés avec le type `integer`, alors que l'écran
 * propose des pas de 0,1 ou 0,5. L'écran tronquait 1,5 en 1 sans le dire ; la
 * règle commune (ModificationDeReglages) le refuse désormais. Le bon remède est
 * le type : tous leurs lecteurs les lisent en float.
 *
 * Seules les lignes encore en `integer` changent ; la valeur n'est pas touchée.
 */
return new class extends Migration
{
    private const CLES = [
        'bulletin_semester1_weight',
        'bulletin_semester2_weight',
        'bulletin_bts1_semester1_weight',
        'bulletin_bts1_semester2_weight',
        'bulletin_bts2_semester1_weight',
        'bulletin_bts2_semester2_weight',
        'lmd_validation_threshold',
        'lmd_note_eliminatoire',
        'lmd_mention_tb_threshold',
        'lmd_mention_b_threshold',
        'lmd_mention_ab_threshold',
        'lmd_mention_p_threshold',
    ];

    public function up(): void
    {
        $this->changer('integer', 'float', 'integer', 'numeric');
    }

    public function down(): void
    {
        $this->changer('float', 'integer', 'numeric', 'integer');
    }

    private function changer(string $de, string $vers, string $regleDe, string $regleVers): void
    {
        $lignes = DB::table('settings')->whereIn('key', self::CLES)->where('type', $de)->get(['id', 'key', 'validation_rules']);
        foreach ($lignes as $ligne) {
            $regles = json_decode((string) $ligne->validation_rules, true);
            $maj = ['type' => $vers];
            if (is_array($regles) && in_array($regleDe, $regles, true)) {
                $maj['validation_rules'] = json_encode(array_values(array_map(fn ($r) => $r === $regleDe ? $regleVers : $r, $regles)));
            }
            DB::table('settings')->where('id', $ligne->id)->update($maj);
            Cache::forget('setting_'.$ligne->key);
        }
    }
};
