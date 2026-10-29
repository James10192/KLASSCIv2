<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Log::info('Starting migration: Adding ville_naissance and commune_naissance fields to esbtp_etudiants table');

        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_etudiants', 'ville_naissance')) {
                $table->string('ville_naissance')->nullable()->after('lieu_naissance');
                Log::info('Added ville_naissance field to esbtp_etudiants table');
            }

            if (!Schema::hasColumn('esbtp_etudiants', 'commune_naissance')) {
                $table->string('commune_naissance')->nullable()->after('ville_naissance');
                Log::info('Added commune_naissance field to esbtp_etudiants table');
            }
        });

        Log::info('Completed migration: Added ville_naissance and commune_naissance fields to esbtp_etudiants table');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_etudiants', 'ville_naissance')) {
                $table->dropColumn('ville_naissance');
            }

            if (Schema::hasColumn('esbtp_etudiants', 'commune_naissance')) {
                $table->dropColumn('commune_naissance');
            }
        });
    }
};
