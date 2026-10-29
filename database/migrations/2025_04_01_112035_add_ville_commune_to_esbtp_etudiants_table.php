<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            // Check if 'ville' column doesn't exist and add it
            if (!Schema::hasColumn('esbtp_etudiants', 'ville')) {
                $table->string('ville')->nullable()->after('adresse');
                \Log::info('Added ville field to esbtp_etudiants table');
            } else {
                \Log::info('ville field already exists in esbtp_etudiants table');
            }

            // Check if 'commune' column doesn't exist and add it
            if (!Schema::hasColumn('esbtp_etudiants', 'commune')) {
                $table->string('commune')->nullable()->after('ville');
                \Log::info('Added commune field to esbtp_etudiants table');
            } else {
                \Log::info('commune field already exists in esbtp_etudiants table');
            }
        });

        // Verify columns were added successfully
        if (Schema::hasColumn('esbtp_etudiants', 'ville') && Schema::hasColumn('esbtp_etudiants', 'commune')) {
            \Log::info('Successfully verified ville and commune fields exist in esbtp_etudiants table');
        } else {
            \Log::error('Failed to verify ville and commune fields in esbtp_etudiants table');
            throw new \Exception('Failed to add required fields to esbtp_etudiants table');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            // Drop the columns if they exist
            if (Schema::hasColumn('esbtp_etudiants', 'ville')) {
                $table->dropColumn('ville');
            }

            if (Schema::hasColumn('esbtp_etudiants', 'commune')) {
                $table->dropColumn('commune');
            }
        });
    }
};
