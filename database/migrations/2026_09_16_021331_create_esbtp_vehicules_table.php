<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_vehicules', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation', 32);
            $table->string('statut', 32)->default('disponible');
            $table->unsignedInteger('compteur')->nullable();
            $table->timestamps();
            $table->unique('immatriculation');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_vehicules');
    }
};
