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
        if (! Schema::hasTable('esbtp_academic_metric_snapshots')
            || Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision')) {
            return;
        }

        Schema::table('esbtp_academic_metric_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('source_revision')->default(0)->after('is_dirty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('esbtp_academic_metric_snapshots')
            || ! Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision')) {
            return;
        }

        Schema::table('esbtp_academic_metric_snapshots', function (Blueprint $table) {
            $table->dropColumn('source_revision');
        });
    }
};
