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

        $settings = [
            'lmd_bulletin_code_etablissement' => ['', 'string', 'Code établissement affiché sur le bulletin LMD'],
            'lmd_bulletin_statut' => ['Privé', 'string', 'Statut établissement affiché sur le bulletin LMD'],
            'lmd_bulletin_direction' => ['', 'string', 'Direction affichée sur le bulletin LMD'],
            'lmd_bulletin_notice_text' => ['', 'string', 'Notice du bulletin LMD'],
            'lmd_bulletin_bottom_text' => ['', 'string', 'Texte de pied du bulletin LMD'],
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
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => 'bulletin',
                    'category' => 'bulletin',
                    'description' => $description,
                    'is_required' => false,
                    'default_value' => $value,
                    'validation_rules' => $type === 'float'
                        ? json_encode(['nullable', 'numeric', 'min:6', 'max:24'])
                        : null,
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'lmd_bulletin_code_etablissement',
            'lmd_bulletin_statut',
            'lmd_bulletin_direction',
            'lmd_bulletin_notice_text',
            'lmd_bulletin_bottom_text',
            'lmd_bulletin_font_republic',
            'lmd_bulletin_font_school_name',
            'lmd_bulletin_font_school_meta',
            'lmd_bulletin_font_title',
            'lmd_bulletin_font_header_meta',
            'lmd_bulletin_font_establishment',
            'lmd_bulletin_font_student',
            'lmd_bulletin_font_structure',
            'lmd_bulletin_font_table_header',
            'lmd_bulletin_font_table',
            'lmd_bulletin_font_teacher',
            'lmd_bulletin_font_summary',
            'lmd_bulletin_font_decision',
            'lmd_bulletin_font_notice',
            'lmd_bulletin_font_signature',
            'lmd_bulletin_font_legend',
            'lmd_bulletin_font_bottom',
        ])->delete();

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
