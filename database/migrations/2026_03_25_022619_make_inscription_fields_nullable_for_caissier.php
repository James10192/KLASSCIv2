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
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            $table->string('sexe')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->change();
        });
    }
};
