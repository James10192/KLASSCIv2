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
        Schema::create('esbtp_frais_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frais_category_id')->constrained('esbtp_frais_categories')->onDelete('cascade');
            $table->string('name'); // Nom du variant (ex: "Arrêt Centre-ville", "Menu Standard")
            $table->text('description')->nullable(); // Description détaillée
            $table->decimal('amount', 10, 2); // Montant spécifique pour ce variant
            $table->json('additional_data')->nullable(); // Données supplémentaires (horaires, détails, etc.)
            $table->boolean('is_default')->default(false); // Variant par défaut
            $table->boolean('is_active')->default(true); // Variant actif
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            $table->index(['frais_category_id', 'is_active']);
            $table->index(['is_default', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_frais_variants');
    }
};