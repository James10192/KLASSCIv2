<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpBonsSortieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_bons_sortie')) {
            Schema::create('esbtp_bons_sortie', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->string('titre');
                $table->text('description')->nullable();
                $table->string('destinataire')->nullable();
                $table->dateTime('date_sortie');
                $table->string('statut')->default('brouillon'); // brouillon, en_attente, approuve, rejete
                $table->foreignId('createur_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_bons_sortie');
    }
} 