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
        Schema::create('esbtp_pv_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_code', 64);
            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')->restrictOnDelete();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_code', 'annee_universitaire_id'], 'epvs_tenant_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_pv_sequences');
    }
};
