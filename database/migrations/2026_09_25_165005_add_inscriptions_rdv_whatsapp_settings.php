<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'inscriptions.rdv.whatsapp_relais',
            'value' => '0',
            'type' => 'boolean',
            'default_value' => '0',
            'sort_order' => 194,
            'description' => "Propose d'envoyer la convocation par WhatsApp aux familles que le courriel n'a pas atteintes, apres leur accord. Desactive par defaut.",
        ],
        [
            'key' => 'inscriptions.rdv.whatsapp_texte_accord',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 195,
            'description' => "Message de demande d'accord WhatsApp. Vide = texte par defaut. Reperes : {ecole}, {candidat}, {date}, {heure}, {reference}.",
        ],
    ];

    public function up(): void
    {
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $reglage) {
            if (DB::table('settings')->where('key', $reglage['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $reglage['key'],
                'value' => $reglage['value'],
                'type' => $reglage['type'],
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $reglage['default_value'],
                'description' => $reglage['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $reglage['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_column(self::REGLAGES, 'key'))
            ->delete();
    }
};
