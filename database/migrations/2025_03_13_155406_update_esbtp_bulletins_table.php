<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEsbtpBulletinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            // Add is_published field if it doesn't exist
            if (!Schema::hasColumn('esbtp_bulletins', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('decision_conseil');
            }

            // Add user_id field if it doesn't exist
            if (!Schema::hasColumn('esbtp_bulletins', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('is_published')->constrained('users');
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
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_bulletins', 'is_published')) {
                $table->dropColumn('is_published');
            }

            if (Schema::hasColumn('esbtp_bulletins', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
}
