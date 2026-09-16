<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_missions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 32)->default('demandee');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_missions');
    }
};
