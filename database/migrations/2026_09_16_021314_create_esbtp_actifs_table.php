<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_actifs', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('nature', 32);
            $table->string('statut', 32)->default('en_service');
            $table->unsignedBigInteger('salle_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_actifs');
    }
};
