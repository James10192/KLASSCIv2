<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->onDelete('cascade');
            $table->string('mode_paiement', 30)->comment('Enum App\\Enums\\ModePaiement');
            $table->decimal('montant_compte', 15, 2)->comment('Montant physique compté (saisie comptable)');
            $table->decimal('montant_systeme', 15, 2)->comment('Somme paiements validés sur période/mode (snapshot figé)');
            // L'écart est calculé à la lecture (Eloquent accessor) pour éviter
            // les GENERATED COLUMNS qui peuvent diverger MySQL/MariaDB versions.
            $table->foreignId('counted_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('counted_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['reconciliation_session_id', 'mode_paiement'], 'cash_counts_unique_idx');
            $table->index('counted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
