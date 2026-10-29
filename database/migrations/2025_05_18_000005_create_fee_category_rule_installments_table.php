<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_category_rule_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_category_rule_id')->nullable()->comment('References fee_category_rules table - constraint disabled for migration compatibility');
            $table->string('label')->nullable(); // Ex: 1ère tranche, 2e tranche
            $table->integer('offset_days'); // Nombre de jours après la date de référence (rentrée)
            $table->decimal('amount', 12, 2)->nullable(); // Montant fixe (optionnel)
            $table->integer('pourcentage')->nullable(); // Pourcentage du montant total (optionnel)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_category_rule_installments');
    }
};
