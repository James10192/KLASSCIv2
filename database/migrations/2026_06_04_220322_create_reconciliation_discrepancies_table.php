<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->onDelete('cascade');
            $table->foreignId('cash_count_id')->nullable()->constrained('cash_counts')->onDelete('set null');
            $table->enum('type', [
                'paiement_manquant',
                'paiement_en_trop',
                'montant_errone',
                'mode_errone',
                'date_erronee',
                'autre',
            ]);
            $table->decimal('montant_ecart', 15, 2);
            $table->unsignedBigInteger('paiement_concerne_id')->nullable();
            $table->enum('action', ['a_traiter', 'en_revue', 'resolu', 'rejete'])->default('a_traiter');
            $table->enum('resolution_type', [
                'adjust_payment',
                'create_corrective',
                'cancel_payment',
                'no_action',
            ])->nullable();
            $table->unsignedBigInteger('resolution_payment_id')->nullable();
            $table->text('motif');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('action');
            $table->index('paiement_concerne_id');
            $table->foreign('paiement_concerne_id')->references('id')->on('esbtp_paiements')->onDelete('set null');
            $table->foreign('resolution_payment_id')->references('id')->on('esbtp_paiements')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_discrepancies');
    }
};
