<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->dateTime('video_joined_at')->nullable()->comment('Heure de connexion à la visio');
            $table->dateTime('video_left_at')->nullable()->comment('Heure de déconnexion');
            $table->integer('video_duration_minutes')->nullable()->comment('Durée de présence en visio (minutes)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->dropColumn(['video_joined_at', 'video_left_at', 'video_duration_minutes']);
        });
    }
};
