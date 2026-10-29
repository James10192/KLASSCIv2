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
        Schema::create('esbtp_system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // matricule_mode, current_etablissement, etc.
            $table->text('value'); // manuel/automatique, etablissement_id, etc.
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer les paramètres par défaut
        DB::table('esbtp_system_settings')->insert([
            [
                'key' => 'matricule_mode',
                'value' => 'automatique',
                'type' => 'string',
                'description' => 'Mode de génération des matricules: manuel ou automatique',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'current_etablissement_id',
                'value' => '1',
                'type' => 'integer',
                'description' => 'ID de l\'établissement actuellement configuré',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_system_settings');
    }
};