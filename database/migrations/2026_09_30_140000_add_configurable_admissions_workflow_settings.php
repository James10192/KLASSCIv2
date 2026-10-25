<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'inscriptions.workflow.mode',
            'value' => 'standard',
            'type' => 'string',
            'default_value' => 'standard',
            'sort_order' => 194,
            'description' => "Ordre du parcours d'inscription : standard, caisse_puis_pieces ou pieces_puis_caisse. Le defaut preserve le fonctionnement historique.",
        ],
        [
            'key' => 'inscriptions.workflow.student_class_choice',
            'value' => '0',
            'type' => 'boolean',
            'default_value' => '0',
            'sort_order' => 195,
            'description' => "Autoriser l'etudiant a choisir lui-meme sa classe dans son espace apres les etapes prealables du workflow. Desactive par defaut.",
        ],
        [
            'key' => 'inscriptions.workflow.class_choice_once',
            'value' => '1',
            'type' => 'boolean',
            'default_value' => '1',
            'sort_order' => 196,
            'description' => "Verrouiller le choix de classe apres la premiere confirmation par l'etudiant. Une correction ulterieure passe par une permission d'administration et reste auditee.",
        ],
        [
            'key' => 'inscriptions.workflow.activation_stage',
            'value' => 'validation',
            'type' => 'string',
            'default_value' => 'validation',
            'sort_order' => 197,
            'description' => "Etape qui ouvre l'acces etudiant : payment, documents, class ou validation. Le defaut validation preserve le fonctionnement historique.",
        ],
    ];

    public function up(): void
    {
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $reglage) {
            // Idempotent : une instance qui a deja le reglage conserve sa valeur.
            // Un deploiement commun ne doit jamais ecraser le workflow choisi par
            // une ecole.
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
