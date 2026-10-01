<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Des réglages lus comme des décimaux (coefficients de semestre, seuils et
 * mentions LMD) étaient enregistrés avec le type `integer`, alors que l'écran
 * propose des pas de 0,1 ou 0,5. L'écran tronquait 1,5 en 1 sans le dire ; la
 * règle commune (ModificationDeReglages) le refuse désormais. Le bon remède est
 * le type : tous leurs lecteurs les lisent en float.
 *
 * Ce qui ne doit PAS bouger : la valeur que l'école utilisait réellement. Sous
 * le type `integer`, Setting::castValue lisait « 10.5 » (écrit par le CLI, par
 * exemple) comme 10. Passer la ligne en float la ferait lire 10,5 : un seuil de
 * validation changerait en silence. Une telle valeur est donc d'abord réécrite
 * sous sa forme entière, celle qui servait, et chaque réécriture est journalisée.
 *
 * Seules les lignes encore en `integer` changent. Elles sont gardées dans une
 * sauvegarde de réglages (backup_type `migration`) : down() ne remet que ces
 * lignes-là, avec leur valeur d'origine — jamais une ligne déjà en float (les
 * coefficients BTS sont créés en float par BtsBulletinPolicy).
 */
return new class extends Migration
{
    public const SAUVEGARDE = 'Migration convertir_reglages_decimaux_en_float';

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
        $lignes = DB::table('settings')->whereIn('key', self::CLES)->where('type', 'integer')
            ->get(['id', 'key', 'value', 'type', 'validation_rules']);
        if ($lignes->isEmpty()) {
            return;
        }

        $auteur = DB::table('users')->min('id');
        if ($auteur === null) {
            Log::warning('[reglages] aucun utilisateur : conversion en float sans trace, down() ne pourra rien remettre', ['migration' => self::SAUVEGARDE]);
        }
        if ($auteur !== null) {
            DB::table('settings_backups')->insert([
                'backup_name' => self::SAUVEGARDE,
                'description' => 'Lignes passées de integer à float, avec leur valeur d\'origine (pour down()).',
                'settings_data' => json_encode($lignes->map(fn ($l) => (array) $l)->values()->all()),
                'backup_type' => 'migration',
                // Archivée : jamais proposée comme une sauvegarde d'école, et
                // SettingsBackup::restore() refuse le type `migration`.
                'status' => 'archived',
                'backup_date' => now(),
                'created_by' => $auteur,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($lignes as $ligne) {
            $maj = ['type' => 'float'];
            $valeur = $ligne->value === null ? '' : trim((string) $ligne->value);
            if ($valeur !== '' && ! preg_match('/^-?[0-9]+$/', $valeur)) {
                $maj['value'] = (string) (int) $valeur;
                Log::warning('[reglages] valeur non entiere ramenee a ce qui etait lu avant le passage en float', [
                    'key' => $ligne->key, 'avant' => $ligne->value, 'apres' => $maj['value'],
                ]);
            }
            $regles = json_decode((string) $ligne->validation_rules, true);
            if (is_array($regles) && in_array('integer', $regles, true)) {
                $maj['validation_rules'] = json_encode(array_values(array_map(fn ($r) => $r === 'integer' ? 'numeric' : $r, $regles)));
            }
            DB::table('settings')->where('id', $ligne->id)->update($maj);
        }

        Setting::clearCache();
    }

    public function down(): void
    {
        $sauvegarde = DB::table('settings_backups')->where('backup_type', 'migration')
            ->where('backup_name', self::SAUVEGARDE)->orderByDesc('id')->first();
        if ($sauvegarde === null) {
            Log::warning('[reglages] down() sans trace de migration : aucune ligne remise en integer', ['migration' => self::SAUVEGARDE]);

            return;
        }

        foreach ((array) json_decode((string) $sauvegarde->settings_data, true) as $ligne) {
            DB::table('settings')->where('id', $ligne['id'])->where('type', 'float')->update([
                'type' => $ligne['type'],
                'value' => $ligne['value'],
                'validation_rules' => $ligne['validation_rules'],
            ]);
        }
        DB::table('settings_backups')->where('id', $sauvegarde->id)->delete();

        Setting::clearCache();
    }
};
