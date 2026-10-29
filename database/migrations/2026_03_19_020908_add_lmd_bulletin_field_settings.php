<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'lmd_bulletin_show_domaine', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Afficher le champ Domaine sur le bulletin', 'default_value' => '1', 'sort_order' => 50],
            ['key' => 'lmd_bulletin_show_mention', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Afficher le champ Mention sur le bulletin', 'default_value' => '1', 'sort_order' => 51],
            ['key' => 'lmd_bulletin_show_specialite', 'value' => '0', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Afficher le champ Spécialité sur le bulletin', 'default_value' => '0', 'sort_order' => 52],
            ['key' => 'lmd_bulletin_show_parcours', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Afficher le champ Parcours sur le bulletin', 'default_value' => '1', 'sort_order' => 53],
            ['key' => 'lmd_bulletin_label_domaine', 'value' => 'DOMAINE', 'type' => 'string', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Libellé du champ Domaine sur le bulletin', 'default_value' => 'DOMAINE', 'sort_order' => 54],
            ['key' => 'lmd_bulletin_label_mention', 'value' => 'MENTION', 'type' => 'string', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Libellé du champ Mention sur le bulletin', 'default_value' => 'MENTION', 'sort_order' => 55],
            ['key' => 'lmd_bulletin_label_specialite', 'value' => 'SPÉCIALITÉ', 'type' => 'string', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Libellé du champ Spécialité sur le bulletin', 'default_value' => 'SPÉCIALITÉ', 'sort_order' => 56],
            ['key' => 'lmd_bulletin_label_parcours', 'value' => 'PARCOURS', 'type' => 'string', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Libellé du champ Parcours sur le bulletin', 'default_value' => 'PARCOURS', 'sort_order' => 57],
            ['key' => 'lmd_bulletin_parcours_auto', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'bulletin_fields', 'description' => 'Générer automatiquement le parcours depuis Niveau + Filière', 'default_value' => '1', 'sort_order' => 58],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                array_merge($s, ['is_active' => true, 'is_required' => false])
            );
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'lmd_bulletin_show_domaine', 'lmd_bulletin_show_mention',
            'lmd_bulletin_show_specialite', 'lmd_bulletin_show_parcours',
            'lmd_bulletin_label_domaine', 'lmd_bulletin_label_mention',
            'lmd_bulletin_label_specialite', 'lmd_bulletin_label_parcours',
            'lmd_bulletin_parcours_auto',
        ])->delete();
    }
};
