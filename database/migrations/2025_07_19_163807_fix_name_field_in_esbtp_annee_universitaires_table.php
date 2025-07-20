<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixNameFieldInEsbtpAnneeUniversitairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            // Make the name field nullable to avoid "doesn't have a default value" error
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            // Revert the name field back to not nullable
            $table->string('name')->nullable(false)->change();
        });
    }
}
