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
        Schema::create('esbtp_frais_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('esbtp_inscriptions')->onDelete('cascade');
            $table->foreignId('frais_category_id')->constrained('esbtp_frais_categories')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->useCurrent();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Une inscription ne peut souscrire qu'une seule fois à un type de frais
            $table->unique(['inscription_id', 'frais_category_id']);
            
            // Index pour les requêtes fréquentes
            $table->index(['inscription_id', 'is_active']);
            $table->index(['frais_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_frais_subscriptions');
    }
};