<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldsToEsbtpBonsSortieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_bons_sortie', function (Blueprint $table) {
            $table->foreignId('approbateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_bons_sortie', function (Blueprint $table) {
            $table->dropForeign(['approbateur_id']);
            $table->dropColumn(['approbateur_id', 'notification_sent_at', 'approved_at']);
        });
    }
} 