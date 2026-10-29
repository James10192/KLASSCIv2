<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La nature du premier rang de la structure LMD : UFR, faculte, ecole, institut.
 *
 * Nullable : un domaine sans nature reste un domaine du referentiel LMD, et
 * aucune instance existante ne change. Valeurs portees par App\Enums\NatureComposante.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_lmd_domaines', 'nature')) {
            return;
        }

        Schema::table('esbtp_lmd_domaines', function (Blueprint $table) {
            $table->string('nature', 30)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_lmd_domaines', 'nature')) {
            return;
        }

        Schema::table('esbtp_lmd_domaines', function (Blueprint $table) {
            $table->dropColumn('nature');
        });
    }
};
