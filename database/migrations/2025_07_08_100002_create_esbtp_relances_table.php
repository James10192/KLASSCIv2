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
        Schema::create('esbtp_relances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->foreignId('facture_id')->nullable()->constrained('esbtp_factures')->onDelete('set null');
            $table->enum('type', ['email', 'sms', 'courrier', 'appel'])->default('email');
            $table->integer('niveau')->default(1);
            $table->string('template_utilise', 100)->nullable();
            $table->text('contenu_message')->nullable();
            $table->dateTime('date_envoi')->nullable();
            $table->enum('statut', ['planifiee', 'envoyee', 'echec'])->default('planifiee');
            $table->json('response_data')->nullable();
            $table->timestamps();
            
            $table->index(['etudiant_id', 'statut']);
            $table->index(['niveau', 'type']);
            $table->index('date_envoi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_relances');
    }
};
