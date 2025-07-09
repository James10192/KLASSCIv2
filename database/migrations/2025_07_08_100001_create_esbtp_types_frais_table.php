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
        Schema::create('esbtp_types_frais', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->decimal('montant_fixe', 10, 2)->nullable();
            $table->enum('periodicite', ['unique', 'mensuel', 'trimestriel', 'semestriel', 'annuel']);
            $table->json('conditions')->nullable();
            $table->boolean('est_obligatoire')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->index(['actif', 'est_obligatoire']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_types_frais');
    }
};
