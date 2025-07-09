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
        Schema::create('esbtp_kpis', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->decimal('valeur', 15, 2);
            $table->string('unite', 20)->default('FCFA');
            $table->enum('periode', ['jour', 'semaine', 'mois', 'trimestre', 'annee']);
            $table->date('date_calcul');
            $table->enum('type', ['recette', 'depense', 'performance', 'ratio']);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['nom', 'periode', 'date_calcul']);
            $table->index(['type', 'date_calcul']);
            $table->unique(['nom', 'periode', 'date_calcul'], 'unique_kpi_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_kpis');
    }
};
