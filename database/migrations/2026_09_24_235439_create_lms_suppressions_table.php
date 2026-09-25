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
        // Les « pierres tombales » de la synchronisation LMS (lot 2) : sans
        // elles, le LMS ne saurait jamais qu'une inscription ou une seance a
        // disparu, et garderait des eleves partis. L'id, croissant, sert de
        // curseur ; aucune cle etrangere, l'objet n'existe plus.
        Schema::create('lms_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->unsignedBigInteger('objet_id');
            $table->timestamp('supprime_le')->useCurrent();
            $table->index(['type', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lms_suppressions');
    }
};
