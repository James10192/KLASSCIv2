<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_frais_configurations', 'systeme_academique')) {
                $table->string('systeme_academique')->nullable()->after('frais_category_id');
            }

            if (!Schema::hasColumn('esbtp_frais_configurations', 'parcours_id')) {
                $table->foreignId('parcours_id')
                    ->nullable()
                    ->after('filiere_id')
                    ->constrained('esbtp_lmd_parcours')
                    ->nullOnDelete();
            }
        });

        Schema::table('esbtp_option_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_option_assignments', 'parcours_id')) {
                $table->foreignId('parcours_id')
                    ->nullable()
                    ->after('niveau_id')
                    ->constrained('esbtp_lmd_parcours')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_option_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_option_assignments', 'parcours_id')) {
                $table->dropConstrainedForeignId('parcours_id');
            }
        });

        Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_frais_configurations', 'parcours_id')) {
                $table->dropConstrainedForeignId('parcours_id');
            }

            if (Schema::hasColumn('esbtp_frais_configurations', 'systeme_academique')) {
                $table->dropColumn('systeme_academique');
            }
        });
    }
};
