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
        Schema::create('esbtp_etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ESBTP-ABIDJAN, ESBTP-BOUAKE, etc.
            $table->string('nom'); // Nom complet de l'établissement
            $table->string('ville');
            $table->string('code_court')->nullable(); // ESBTP (pour les matricules)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insérer l'établissement par défaut
        DB::table('esbtp_etablissements')->insert([
            [
                'code' => 'ESBTP-ABIDJAN',
                'nom' => 'ESBTP Abidjan',
                'ville' => 'Abidjan',
                'code_court' => 'ESBTP',
                'description' => 'École Supérieure du Bâtiment et des Travaux Publics - Campus Abidjan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'ESBTP-BOUAKE',
                'nom' => 'ESBTP Bouaké',
                'ville' => 'Bouaké',
                'code_court' => 'ESBTP',
                'description' => 'École Supérieure du Bâtiment et des Travaux Publics - Campus Bouaké',
                'is_active' => true,
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
        Schema::dropIfExists('esbtp_etablissements');
    }
};