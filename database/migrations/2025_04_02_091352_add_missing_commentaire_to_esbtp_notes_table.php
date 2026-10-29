<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingCommentaireToEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_notes', 'commentaire')) {
                $table->text('commentaire')->nullable()->after('is_absent');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_notes', 'commentaire')) {
                $table->dropColumn('commentaire');
            }
        });
    }
}
