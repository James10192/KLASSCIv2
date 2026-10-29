<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentaireToEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('esbtp_notes') && !Schema::hasColumn('esbtp_notes', 'commentaire')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->text('commentaire')->nullable()->after('is_absent');
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
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'commentaire')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->dropColumn('commentaire');
            });
        }
    }
}
