<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_user_composantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('domaine_id');
            $table->timestamps();

            $table->unique(['user_id', 'domaine_id']);
            $table->foreign('domaine_id')->references('id')->on('esbtp_lmd_domaines')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_user_composantes');
    }
};
