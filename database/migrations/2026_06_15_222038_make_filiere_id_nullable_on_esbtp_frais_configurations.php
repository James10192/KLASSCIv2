<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les configurations de frais LMD sont rattachées à un PARCOURS, pas à une
     * filière (FraisScopeResolver met filiere_id=null pour le LMD). Or la colonne
     * filiere_id était restée NOT NULL → « Column filiere_id cannot be null » lors
     * de la configuration de frais par niveau sur un tenant LMD (ephrata, juin 2026).
     * On rend filiere_id NULLABLE (la FK nullable reste valide).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('esbtp_frais_configurations', 'filiere_id')) {
            return;
        }
        // MODIFY direct : conserve la FK (une FK sur colonne nullable est valide en MySQL).
        DB::statement('ALTER TABLE `esbtp_frais_configurations` MODIFY `filiere_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('esbtp_frais_configurations', 'filiere_id')) {
            return;
        }
        // Best-effort : ne repasse en NOT NULL que s'il n'existe aucune ligne à filiere_id null.
        $hasNull = DB::table('esbtp_frais_configurations')->whereNull('filiere_id')->exists();
        if (!$hasNull) {
            DB::statement('ALTER TABLE `esbtp_frais_configurations` MODIFY `filiere_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};
