<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentPathToEsbtpAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('commentaire');
            $table->timestamp('justified_at')->nullable()->after('document_path');
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
            $table->dropColumn('document_path');
            $table->dropColumn('justified_at');
        });
    }
}
