<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_services_faits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seance_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('statut', 32)->default('a_viser_sp');
            $table->unsignedBigInteger('vise_par')->nullable();
            $table->timestamp('vise_at')->nullable();
            $table->text('motif')->nullable();
            $table->timestamps();

            $table->unique('seance_id');
            $table->foreign('seance_id')->references('id')->on('esbtp_seance_cours')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('esbtp_teachers')->cascadeOnDelete();
            $table->foreign('vise_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_services_faits');
    }
};
