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
        Schema::create('esbtp_frais_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frais_category_id')->constrained('esbtp_frais_categories')->onDelete('cascade');
            $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
            $table->foreignId('annee_universitaire_id')->nullable()->constrained('esbtp_annee_universitaires')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->integer('payment_deadline_days')->default(30);
            $table->boolean('installments_allowed')->default(false);
            $table->integer('max_installments')->default(1);
            $table->decimal('min_installment_amount', 10, 2)->nullable();
            $table->decimal('late_fee_percentage', 5, 2)->default(0);
            $table->decimal('late_fee_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->default(now());
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimiser les requêtes
            $table->index(['frais_category_id', 'filiere_id', 'niveau_id', 'annee_universitaire_id'], 'frais_rules_context_index');
            $table->index(['is_active', 'effective_date', 'expiry_date']);
            $table->index(['filiere_id', 'niveau_id']);
            
            // Contrainte d'unicité pour éviter les doublons
            $table->unique(['frais_category_id', 'filiere_id', 'niveau_id', 'annee_universitaire_id'], 'frais_rules_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_frais_rules');
    }
};