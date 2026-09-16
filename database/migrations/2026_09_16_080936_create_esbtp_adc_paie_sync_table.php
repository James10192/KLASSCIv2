<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_adc_paie_sync', function (Blueprint $table) {
            $table->id();
            $table->string('cle_idempotence', 191);
            $table->string('type_evenement', 64);
            $table->string('statut', 32)->default('en_attente');
            $table->json('payload')->nullable();
            $table->json('erreurs')->nullable();
            $table->timestamp('timeout_at')->nullable();
            $table->timestamps();

            $table->unique('cle_idempotence');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_adc_paie_sync');
    }
};
