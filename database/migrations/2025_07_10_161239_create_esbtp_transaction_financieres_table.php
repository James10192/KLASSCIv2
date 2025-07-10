<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpTransactionFinancieresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_transaction_financieres', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // revenu, depense, etc.
            $table->string('transactionable_type'); // Type de modèle polymorphe
            $table->unsignedBigInteger('transactionable_id'); // ID de l'objet lié
            $table->decimal('montant', 15, 2); // Montant de la transaction
            $table->enum('sens', ['crédit', 'débit']); // Sens de la transaction
            $table->string('categorie'); // Catégorie de la transaction
            $table->string('reference')->nullable(); // Référence de la transaction
            $table->datetime('date_transaction'); // Date de la transaction
            $table->text('description')->nullable(); // Description de la transaction
            $table->foreignId('createur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index pour optimiser les recherches
            $table->index(['transactionable_type', 'transactionable_id'], 'esbtp_trans_fin_morph_idx');
            $table->index(['type', 'sens'], 'esbtp_trans_fin_type_sens_idx');
            $table->index(['date_transaction'], 'esbtp_trans_fin_date_idx');
            $table->index(['categorie'], 'esbtp_trans_fin_cat_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_transaction_financieres');
    }
}
