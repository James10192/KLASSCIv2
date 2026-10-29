<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table immutable : pas de updated_at, pas de soft-delete. Append-only.
 * Sert d'audit trail dense complémentaire à OwenIt\Auditing (qui loggue
 * les updates du Model). Cette table loggue spécifiquement les mutations
 * faites DANS LE CONTEXTE d'une session de réconciliation (motif obligatoire,
 * snapshot avant/après JSON, delta lisible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->onDelete('cascade');
            $table->unsignedBigInteger('paiement_id');
            $table->enum('action_type', [
                'adjust_montant',
                'adjust_mode',
                'adjust_date',
                'adjust_motif',
                'create',
                'cancel',
                'validate',
                'revalidate',
            ]);
            $table->json('snapshot_before')->comment('État complet du paiement avant mutation');
            $table->json('snapshot_after')->comment('État complet après mutation');
            $table->json('delta')->comment('Diff lisible des champs modifiés');
            $table->text('motif')->comment('Justification obligatoire ≥ 10 chars');
            $table->foreignId('performed_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('performed_at');

            // Pas de timestamps() — append-only immutable.
            $table->index('paiement_id');
            $table->index('performed_at');
            $table->foreign('paiement_id')->references('id')->on('esbtp_paiements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_logs');
    }
};
