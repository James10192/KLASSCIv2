<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('esbtp_lmd_bulletins', 'affectation_status')) {
            Schema::table('esbtp_lmd_bulletins', function (Blueprint $table) {
                $table->string('affectation_status', 30)->nullable()->after('parcours_label');
            });
        }

        if (! Schema::hasColumn('esbtp_lmd_resultats_ecues', 'enseignant_snapshot_nom')) {
            Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
                $table->string('enseignant_snapshot_nom', 255)->nullable()->after('enseignant_id');
            });
        }

        // Les bulletins déjà générés n'ont pas de snapshot d'affectation. On le
        // fige une seule fois depuis leur inscription correspondante afin que les
        // anciens PDF profitent eux aussi d'Affecté / Réaffecté / Non affecté.
        DB::table('esbtp_lmd_bulletins')
            ->where(function ($query) {
                $query->whereNull('affectation_status')->orWhere('affectation_status', '');
            })
            ->orderBy('id')
            ->chunkById(200, function ($bulletins): void {
                foreach ($bulletins as $bulletin) {
                    $statut = DB::table('esbtp_inscriptions')
                        ->where('etudiant_id', $bulletin->etudiant_id)
                        ->where('classe_id', $bulletin->classe_id)
                        ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
                        ->orderByDesc('id')
                        ->value('affectation_status');

                    if ($statut !== null && trim((string) $statut) !== '') {
                        DB::table('esbtp_lmd_bulletins')
                            ->where('id', $bulletin->id)
                            ->update(['affectation_status' => (string) $statut]);
                    }
                }
            });

        $settings = [
            // Bloc officiel / visibilité
            'lmd_bulletin_show_republic_info' => ['1', 'boolean', 'Afficher les informations République sur le bulletin LMD'],
            'lmd_bulletin_show_ministry_info' => ['1', 'boolean', 'Afficher les informations Ministère sur le bulletin LMD'],
            'lmd_bulletin_republic_text' => ["REPUBLIQUE DE COTE D'IVOIRE", 'string', 'Texte République du bulletin LMD'],
            'lmd_bulletin_union_text' => ['Union - Discipline - Travail', 'string', 'Devise nationale du bulletin LMD'],
            'lmd_bulletin_ministry_text' => ["MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE", 'string', 'Texte Ministère du bulletin LMD'],
            'lmd_bulletin_show_etablissement_box' => ['1', 'boolean', 'Afficher code / statut / direction du bulletin LMD'],
            'lmd_bulletin_code_etablissement' => ['', 'string', 'Code établissement affiché sur le bulletin LMD'],
            'lmd_bulletin_statut' => ['Privé', 'string', 'Statut établissement affiché sur le bulletin LMD'],
            'lmd_bulletin_direction' => ['', 'string', 'Direction affichée sur le bulletin LMD'],
            'lmd_bulletin_show_domaine' => ['1', 'boolean', 'Afficher Domaine sur le bulletin LMD'],
            'lmd_bulletin_show_mention' => ['1', 'boolean', 'Afficher Mention sur le bulletin LMD'],
            'lmd_bulletin_show_specialite' => ['0', 'boolean', 'Afficher Spécialité sur le bulletin LMD'],
            'lmd_bulletin_show_parcours' => ['1', 'boolean', 'Afficher Parcours sur le bulletin LMD'],
            'lmd_bulletin_label_domaine' => ['', 'string', 'Libellé Domaine du bulletin LMD'],
            'lmd_bulletin_label_mention' => ['', 'string', 'Libellé Mention du bulletin LMD'],
            'lmd_bulletin_label_specialite' => ['SPÉCIALITÉ', 'string', 'Libellé Spécialité du bulletin LMD'],
            'lmd_bulletin_label_parcours' => ['', 'string', 'Libellé Parcours du bulletin LMD'],
            'lmd_bulletin_parcours_auto' => ['1', 'boolean', 'Construire automatiquement le libellé parcours du bulletin LMD'],
            'lmd_bulletin_notice_text' => ["Un ECUE n'est ni transférable ni capitalisable. Les crédits d'une UE non acquise ne sont capitalisés qu'après validation de celle-ci.", 'string', 'Notice du bulletin LMD'],
            'lmd_bulletin_bottom_text' => ['Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.', 'string', 'Texte de pied du bulletin LMD'],

            // Typographie indépendante par zone
            'lmd_bulletin_font_republic' => ['8.5', 'float', 'Taille République / Ministère du bulletin LMD'],
            'lmd_bulletin_font_school_name' => ['13', 'float', 'Taille du nom établissement du bulletin LMD'],
            'lmd_bulletin_font_school_meta' => ['7.5', 'float', 'Taille des coordonnées établissement du bulletin LMD'],
            'lmd_bulletin_font_title' => ['12', 'float', 'Taille du titre du bulletin LMD'],
            'lmd_bulletin_font_header_meta' => ['8', 'float', 'Taille année / niveau / semestre du bulletin LMD'],
            'lmd_bulletin_font_establishment' => ['8.5', 'float', 'Taille code / statut / direction du bulletin LMD'],
            'lmd_bulletin_font_student' => ['9.5', 'float', 'Taille identité étudiant du bulletin LMD'],
            'lmd_bulletin_font_structure' => ['9', 'float', 'Taille domaine / mention / parcours du bulletin LMD'],
            'lmd_bulletin_font_table_header' => ['8', 'float', 'Taille en-tête du tableau du bulletin LMD'],
            'lmd_bulletin_font_table' => ['8.5', 'float', 'Taille lignes UE / ECUE du bulletin LMD'],
            'lmd_bulletin_font_teacher' => ['7.5', 'float', 'Taille nom enseignant du bulletin LMD'],
            'lmd_bulletin_font_summary' => ['12', 'float', 'Taille synthèse moyenne / crédits du bulletin LMD'],
            'lmd_bulletin_font_decision' => ['10', 'float', 'Taille décision du bulletin LMD'],
            'lmd_bulletin_font_notice' => ['8', 'float', 'Taille notice du bulletin LMD'],
            'lmd_bulletin_font_signature' => ['9', 'float', 'Taille signature du bulletin LMD'],
            'lmd_bulletin_font_legend' => ['7.5', 'float', 'Taille légende du bulletin LMD'],
            'lmd_bulletin_font_bottom' => ['8', 'float', 'Taille pied de page du bulletin LMD'],
        ];

        $sortOrder = 300;
        foreach ($settings as $key => [$value, $type, $description]) {
            // Ne jamais écraser la charte ou les textes déjà configurés d'un tenant.
            // Cette migration ajoute uniquement les clés manquantes.
            if (DB::table('settings')->where('key', $key)->exists()) {
                $sortOrder++;
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => $description,
                'is_required' => false,
                'default_value' => $value,
                'validation_rules' => $type === 'float'
                    ? json_encode(['nullable', 'numeric', 'min:6', 'max:24'])
                    : ($type === 'boolean' ? json_encode(['nullable', 'in:0,1']) : null),
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Les réglages sont des données tenant : on ne les supprime pas au rollback,
        // car certaines clés pouvaient préexister à cette migration.
        if (Schema::hasColumn('esbtp_lmd_resultats_ecues', 'enseignant_snapshot_nom')) {
            Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
                $table->dropColumn('enseignant_snapshot_nom');
            });
        }

        if (Schema::hasColumn('esbtp_lmd_bulletins', 'affectation_status')) {
            Schema::table('esbtp_lmd_bulletins', function (Blueprint $table) {
                $table->dropColumn('affectation_status');
            });
        }
    }
};
