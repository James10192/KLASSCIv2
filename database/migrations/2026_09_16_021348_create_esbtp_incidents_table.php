<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actif_id')->nullable();
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->string('type_maintenance', 32)->default('reparation');
            $table->string('statut', 32)->default('ouvert');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_incidents');
    }
};
