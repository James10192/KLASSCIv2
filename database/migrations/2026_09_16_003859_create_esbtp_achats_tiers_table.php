<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_achats_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale');
            $table->string('ifu', 32)->nullable();
            $table->string('iban', 64)->nullable();
            $table->timestamp('iban_vise_at')->nullable();
            $table->unsignedBigInteger('iban_vise_par')->nullable();
            $table->timestamps();

            $table->index('ifu');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_achats_tiers');
    }
};
